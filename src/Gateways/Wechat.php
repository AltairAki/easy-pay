<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 11:37
 */

namespace AltairAki\Pay\Gateways;


use AltairAki\Pay\Contracts\GatewayApplicationInterface;
use AltairAki\Pay\Contracts\GatewayInterface;
use AltairAki\Pay\Exceptions\Exception;
use AltairAki\Pay\Exceptions\InvalidArgumentException;
use AltairAki\Pay\Exceptions\InvalidGatewayException;
use AltairAki\Pay\Gateways\Wechat\Support;
use AltairAki\Pay\Supports\Collection;
use AltairAki\Pay\Supports\Config;
use AltairAki\Pay\Supports\Str;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * @method Collection  mini(array $config)      小程序支付
 */
class Wechat implements GatewayApplicationInterface
{
    /**
     * 普通模式.
     */
    const MODE_NORMAL = 'normal';

    /**
     * 沙箱模式.
     */
    const MODE_DEV = 'dev';

    /**
     * 香港钱包 API.
     */
    const MODE_HK = 'hk';

    /**
     * 境外 API.
     */
    const MODE_US = 'us';

    /**
     * 服务商模式.
     */
    const MODE_SERVICE = 'service';

    /**
     * Const url.
     */
    const URL = [
        self::MODE_NORMAL => 'https://api.mch.weixin.qq.com/',
        self::MODE_DEV => 'https://api.mch.weixin.qq.com/sandboxnew/',
        self::MODE_HK => 'https://apihk.mch.weixin.qq.com/',
        self::MODE_SERVICE => 'https://api.mch.weixin.qq.com/',
        self::MODE_US => 'https://apius.mch.weixin.qq.com/',
    ];

    /**
     * Wechat payload.
     *
     * @var array
     */
    protected $payload;

    /**
     * Wechat gateway.
     *
     * @var string
     */
    protected $gateway;

    /**
     * Bootstrap.
     *
     * @throws Exception
     */
    public function __construct(Config $config)
    {
        $this->gateway = Support::create($config)->getBaseUri();
        $this->payload = [
            'appid' => $config->get('app_id', ''),
            'mch_id' => $config->get('mch_id', ''),
            'nonce_str' => Str::random(),
            'notify_url' => $config->get('notify_url', ''),
            'sign' => '',
            'trade_type' => '',
            'spbill_create_ip' => Request::createFromGlobals()->getClientIp(),
        ];
        if ($config->get('mode', self::MODE_NORMAL) === static::MODE_SERVICE) {
            $this->payload = array_merge($this->payload, [
                'sub_mch_id' => $config->get('sub_mch_id'),
                'sub_appid' => $config->get('sub_app_id', ''),
            ]);
        }
    }

    /**
     * Magic pay.
     *
     * @param string $method
     * @param string $params
     *
     * @throws InvalidGatewayException
     *
     * @return Response|Collection
     */
    public function __call($method, $params)
    {
        return self::pay($method, ...$params);
    }

    /**
     * pay an order.
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
        $this->payload = array_merge($this->payload, $params);
        $gateway = get_class($this).'\\'.Str::studly($gateway).'Gateway';
        if (class_exists($gateway)) {
            return $this->makepay($gateway);
        }
        throw new InvalidGatewayException("pay Gateway [{$gateway}] Not Exists");
    }

    /**
     * Make pay gateway.
     *
     * @param string $gateway
     *
     * @throws InvalidGatewayException
     *
     * @return Response|Collection
     */
    protected function makepay($gateway)
    {
        $app = new $gateway();
        if ($app instanceof GatewayInterface) {
            return $app->pay($this->gateway, array_filter($this->payload, function ($value) {
                return '' !== $value && !is_null($value);
            }));
        }
        throw new InvalidGatewayException("pay Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
    }

//    /**
//     * Verify data.
//     *
//     * @param string|null $content
//     *
//     * @throws InvalidSignException
//     * @throws InvalidArgumentException
//     */
//    public function verify($content = null, bool $refund = false): Collection
//    {
//        $content = $content ?? Request::createFromGlobals()->getContent();
//
//        $data = Support::fromXml($content);
//        if ($refund) {
//            $decrypt_data = Support::decryptRefundContents($data['req_info']);
//            $data = array_merge(Support::fromXml($decrypt_data), $data);
//        }
////        Log::debug('Resolved The Received Wechat Request Data', $data);
//
//        if ($refund || Support::generateSign($data) === $data['sign']) {
//            return new Collection($data);
//        }
//
//        throw new InvalidSignException('Wechat Sign Verify FAILED', $data);
//    }
//
//    /**
//     * Query an order.
//     *
//     * @param string|array $order
//     *
//     * @throws GatewayException
//     * @throws InvalidSignException
//     * @throws InvalidArgumentException
//     */
//    public function find($order, string $type = 'wap'): Collection
//    {
//        if ('wap' != $type) {
//            unset($this->payload['spbill_create_ip']);
//        }
//
//        $gateway = get_class($this).'\\'.Str::studly($type).'Gateway';
//
//        if (!class_exists($gateway) || !is_callable([new $gateway(), 'find'])) {
//            throw new GatewayException("{$gateway} Done Not Exist Or Done Not Has FIND Method");
//        }
//
//        $config = call_user_func([new $gateway(), 'find'], $order);
//
//        $this->payload = Support::filterpayload($this->payload, $config['order']);
//
//        Events::dispatch(new Events\MethodCalled('Wechat', 'Find', $this->gateway, $this->payload));
//
//        return Support::requestApi(
//            $config['endpoint'],
//            $this->payload,
//            $config['cert']
//        );
//    }
//
//    /**
//     * Refund an order.
//     *
//     * @throws GatewayException
//     * @throws InvalidSignException
//     * @throws InvalidArgumentException
//     */
//    public function refund(array $order): Collection
//    {
//        $this->payload = Support::filterpayload($this->payload, $order, true);
//        return Support::requestApi(
//            'secapi/pay/refund',
//            $this->payload,
//            true
//        );
//    }
//
//    /**
//     * Cancel an order.
//     *
//     *
//     * @param array $order
//     *
//     * @throws GatewayException
//     * @throws InvalidSignException
//     * @throws InvalidArgumentException
//     */
//    public function cancel($order): Collection
//    {
//        unset($this->payload['spbill_create_ip']);
//
//        $this->payload = Support::filterpayload($this->payload, $order, true);
//
////        Events::dispatch(new Events\MethodCalled('Wechat', 'Cancel', $this->gateway, $this->payload));
//
//        return Support::requestApi(
//            'secapi/pay/reverse',
//            $this->payload,
//            true
//        );
//    }
//
//    /**
//     * Close an order.
//     *
//     * @param string|array $order
//     *
//     * @throws GatewayException
//     * @throws InvalidSignException
//     * @throws InvalidArgumentException
//     */
//    public function close($order): Collection
//    {
//        unset($this->payload['spbill_create_ip']);
//
//        $this->payload = Support::filterpayload($this->payload, $order);
//
////        Events::dispatch(new Events\MethodCalled('Wechat', 'Close', $this->gateway, $this->payload));
//
//        return Support::requestApi('pay/closeorder', $this->payload);
//    }

    /**
     * Echo success to server.
     *
     * @throws InvalidArgumentException
     */
    public function success(): Response
    {
//        Events::dispatch(new Events\MethodCalled('Wechat', 'Success', $this->gateway));

        return Response::create(
            Support::toXml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']),
            200,
            ['Content-Type' => 'application/xml']
        );
    }
//
//    /**
//     * Download the bill.
//     *
//     * @throws GatewayException
//     * @throws InvalidArgumentException
//     */
//    public function download(array $params): string
//    {
//        unset($this->payload['spbill_create_ip']);
//
//        $this->payload = Support::filterpayload($this->payload, $params, true);
//
////        Events::dispatch(new Events\MethodCalled('Wechat', 'Download', $this->gateway, $this->payload));
//
//        $result = Support::getInstance()->post(
//            'pay/downloadbill',
//            Support::getInstance()->toXml($this->payload)
//        );
//
//        if (is_array($result)) {
//            throw new GatewayException('Get Wechat API Error: '.$result['return_msg'], $result);
//        }
//
//        return $result;
//    }
}