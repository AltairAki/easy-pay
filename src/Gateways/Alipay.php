<?php

namespace AltairAki\EasyPay\Gateways;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AltairAki\EasyPay\Contracts\GatewayApplicationInterface;
use AltairAki\EasyPay\Contracts\GatewayInterface;
use AltairAki\EasyPay\Events;
use AltairAki\EasyPay\Exceptions\GatewayException;
use AltairAki\EasyPay\Exceptions\InvalidArgumentException;
use AltairAki\EasyPay\Exceptions\InvalidConfigException;
use AltairAki\EasyPay\Exceptions\InvalidGatewayException;
use AltairAki\EasyPay\Exceptions\InvalidSignException;
use AltairAki\EasyPay\Gateways\Alipay\Support;
use AltairAki\EasyPay\Supports\Collection;
use AltairAki\EasyPay\Supports\Config;
use AltairAki\EasyPay\Supports\Str;

/**
 * @method Response   app(array $config)      APP 支付
 * @method Collection pos(array $config)      刷卡支付
 * @method Collection scan(array $config)     扫码支付
 * @method Collection transfer(array $config) 帐户转账
 * @method Response   wap(array $config)      手机网站支付
 * @method Response   web(array $config)      电脑支付
 * @method Collection mini(array $config)     小程序支付
 */
class Alipay implements GatewayApplicationInterface
{
    /**
     * Const mode_normal.
     */
    const MODE_NORMAL = 'normal';

    /**
     * Const mode_dev.
     */
    const MODE_DEV = 'dev';

    /**
     * Const mode_service.
     */
    const MODE_SERVICE = 'service';

    /**
     * Const url.
     */
    const URL = [
        self::MODE_NORMAL => 'https://openapi.alipay.com/gateway.do?charset=utf-8',
        self::MODE_DEV => 'https://openapi.alipaydev.com/gateway.do?charset=utf-8',
    ];

    /**
     * Alipay payload.
     *
     * @var array
     */
    protected $payload;

    /**
     * Alipay gateway.
     *
     * @var string
     */
    protected $gateway;

    /**
     * extends.
     *
     * @var array
     */
    protected $extends;

    /**
     * Bootstrap.
     *
     * @throws \Exception
     */
    public function __construct(Config $config)
    {
        $this->gateway = Support::create($config)->getBaseUri();
        $this->payload = [
            'app_id' => $config->get('app_id'),
            'method' => '',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'version' => '1.0',
            'return_url' => $config->get('return_url'),
            'notify_url' => $config->get('notify_url'),
            'timestamp' => date('Y-m-d H:i:s'),
            'sign' => '',
            'biz_content' => '',
            'app_auth_token' => $config->get('app_auth_token'),
        ];
        if ($config->get('app_cert_public_key') && $config->get('alipay_root_cert')) {
            $this->payload['app_cert_sn'] = Support::getCertSN($config->get('app_cert_public_key'));
            $this->payload['alipay_root_cert_sn'] = Support::getRootCertSN($config->get('alipay_root_cert'));
        }
    }

    /**
     * Magic pay.
     *
     * @param string $method
     * @param array  $params
     *
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidGatewayException
     * @throws InvalidSignException
     *
     * @return Response|Collection
     */
    public function __call($method, $params)
    {
        if (isset($this->extends[$method])) {
            return $this->makeExtend($method, ...$params);
        }

        return $this->pay($method, ...$params);
    }

    /**
     * Create a new driver instance.
     *
     * @param string $gateway
     * @param array  $params
     *
     * @throws InvalidGatewayException
     *
     * @return Response|Collection
     */
    public function pay($gateway, $params = [])
    {
        $this->payload['return_url'] = $params['return_url'] ?? $this->payload['return_url'];
        $this->payload['notify_url'] = $params['notify_url'] ?? $this->payload['notify_url'];

        unset($params['return_url'], $params['notify_url']);

        $this->payload['biz_content'] = json_encode($params);

        $gateway = get_class($this).'\\'.Str::studly($gateway).'Gateway';

        if (class_exists($gateway)) {
            return $this->makePayGateway($gateway);
        }

        throw new InvalidGatewayException("Pay Gateway [{$gateway}] not exists");
    }

    /**
     * Verify sign.
     *
     * @param array|null $data
     *
     * @throws InvalidSignException
     * @throws InvalidConfigException
     */
    public function verify($data = null, bool $refund = false): Collection
    {
        if (is_null($data)) {
            $request = Request::createFromGlobals();

            $data = $request->request->count() > 0 ? $request->request->all() : $request->query->all();
        }
        if (isset($data['fund_bill_list'])) {
            $data['fund_bill_list'] = htmlspecialchars_decode($data['fund_bill_list']);
        }

        if (Support::verifySign($data)) {
            return new Collection($data);
        }

        throw new InvalidSignException('Alipay Sign Verify Faild', $data);
    }

    /**
     * 查询订单
     *
     * @param string|array $order
     *
     * @throws GatewayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function find($order, string $type = 'wap'): Collection
    {
        $gateway = get_class($this).'\\'.Str::studly($type).'Gateway';

        if (!class_exists($gateway) || !is_callable([new $gateway(), 'find'])) {
            throw new GatewayException("{$gateway} Done Not Exist Or Done Not Has FIND Method");
        }

        $config = call_user_func([new $gateway(), 'find'], $order);

        $this->payload['method'] = $config['method'];
        $this->payload['biz_content'] = $config['biz_content'];
        $this->payload['sign'] = Support::generateSign($this->payload);

        return Support::requestApi($this->payload);
    }

    /**
     * Refund an order.
     *
     * @throws GatewayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function refund(array $order): Collection
    {
        $this->payload['method'] = 'alipay.trade.refund';
        $this->payload['biz_content'] = json_encode($order);
        $this->payload['sign'] = Support::generateSign($this->payload);

        return Support::requestApi($this->payload);
    }

    /**
     * Cancel an order.
     *
     * @param array|string $order
     *
     * @throws GatewayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function cancel($order): Collection
    {
        $this->payload['method'] = 'alipay.trade.cancel';
        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);
        $this->payload['sign'] = Support::generateSign($this->payload);

        return Support::requestApi($this->payload);
    }

    /**
     * Close an order.
     *
     * @param string|array $order
     *
     * @throws GatewayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function close($order): Collection
    {
        $this->payload['method'] = 'alipay.trade.close';
        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);
        $this->payload['sign'] = Support::generateSign($this->payload);

        return Support::requestApi($this->payload);
    }

    /**
     * Download bill.
     *
     * @param string|array $bill
     *
     * @throws GatewayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function download($bill): string
    {
        $this->payload['method'] = 'alipay.data.dataservice.bill.downloadurl.query';
        $this->payload['biz_content'] = json_encode(is_array($bill) ? $bill : ['bill_type' => 'trade', 'bill_date' => $bill]);
        $this->payload['sign'] = Support::generateSign($this->payload);

        $result = Support::requestApi($this->payload);

        return ($result instanceof Collection) ? $result->get('bill_download_url') : '';
    }

    /**
     * Reply success to alipay.
     */
    public function success(): Response
    {
        return Response::create('success');
    }

    /**
     * extend.
     *
     * @throws GatewayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws InvalidArgumentException
     */
    public function extend(string $method, callable $function, bool $now = true): ?Collection
    {
        if (!$now && !method_exists($this, $method)) {
            $this->extends[$method] = $function;
            return null;
        }
        $customize = $function($this->payload);

        if (!is_array($customize) && !($customize instanceof Collection)) {
            throw new InvalidArgumentException('Return Type Must Be Array Or Collection');
        }

        if (is_array($customize)) {
            $this->payload = $customize;
            $this->payload['sign'] = Support::generateSign($this->payload);

            return Support::requestApi($this->payload);
        }

        return $customize;
    }

    /**
     * Make pay gateway.
     *
     * @throws InvalidGatewayException
     *
     * @return Response|Collection
     */
    protected function makePayGateway(string $gateway)
    {
        $app = new $gateway();

        if ($app instanceof GatewayInterface) {
            return $app->pay(array_filter($this->payload, function ($value) {
                return '' !== $value && !is_null($value);
            }));
        }
        throw new InvalidGatewayException("Pay Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
    }

    /**
     * makeExtend.
     *
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    protected function makeExtend(string $method, array ...$params): Collection
    {
        $params = count($params) >= 1 ? $params[0] : $params;

        $function = $this->extends[$method];

        $customize = $function($this->payload, $params);

        if (!is_array($customize) && !($customize instanceof Collection)) {
            throw new InvalidArgumentException('Return Type Must Be Array Or Collection');
        }

        if (is_array($customize)) {
            $this->payload = $customize;
            $this->payload['sign'] = Support::generateSign($this->payload);

            return Support::requestApi($this->payload);
        }

        return $customize;
    }
}
