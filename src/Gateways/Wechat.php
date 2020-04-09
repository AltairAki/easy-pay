<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 11:37
 */

namespace AltairAki\EasyPay\Gateways;


use AltairAki\EasyPay\Contracts\GatewayApplicationInterface;
use AltairAki\EasyPay\Contracts\GatewayInterface;
use AltairAki\EasyPay\Exceptions\Exception;
use AltairAki\EasyPay\Exceptions\GatewayException;
use AltairAki\EasyPay\Exceptions\InvalidArgumentException;
use AltairAki\EasyPay\Exceptions\InvalidGatewayException;
use AltairAki\EasyPay\Exceptions\InvalidSignException;
use AltairAki\EasyPay\Gateways\Wechat\Support;
use AltairAki\EasyPay\Supports\Collection;
use AltairAki\EasyPay\Supports\Config;
use AltairAki\EasyPay\Supports\Str;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * @method Response         app(array $config)          APP 支付
 * @method Collection       mini(array $config)         小程序支付
 * @method Collection       oa(array $config)           公众号支付
 * @method Collection       pos(array $config)          刷卡支付
 * @method Collection       redpack(array $config)      普通红包
 * @method Collection       scan(array $config)         扫码支付
 * @method Collection       transfer(array $config)     企业付款
 * @method RedirectResponse wap(array $config)          H5 支付
 * @method Collection       groupRedpack(array $config) 分裂红包
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
     * 服务商模式. 服务商为特约商户配置AppID（即sub_appid）操作指引
     * JSAPI支付（支持公众号、小程序）、Native支付、App等支付交易时
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
        Support::create($config);
        $this->payload = [
            'appid' => $config->get('app_id'),
            'mch_id' => $config->get('mch_id'),
            'nonce_str' => Str::random(),
            'notify_url' => $config->get('notify_url'),
            'sign' => '',
            'trade_type' => '',
            'spbill_create_ip' => Request::createFromGlobals()->getClientIp(),
        ];
        $this->payload['type'] = $config->get('gateway');

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
     * @return Response|Collection
     * @throws InvalidGatewayException
     *
     */
    public function __call($method, $params)
    {
        return self::pay($method, ...$params);
    }

    /**
     * pay an order.
     *
     * @param $gateway
     * @param array $params
     *
     * @return Response|Collection
     * @throws InvalidGatewayException
     *
     */
    public function pay($gateway, $params = [])
    {
        $this->payload = array_merge($this->payload, $params);
        $gateway = get_class($this) . '\\' . Str::studly($gateway) . 'Gateway';
        if (class_exists($gateway)) {
            return $this->makepay($gateway);
        }
        throw new InvalidGatewayException("pay Gateway [{$gateway}] Not Exists");
    }

    /**
     * 生成对应支付网关
     *
     * @param string $gateway
     *
     * @return Response|Collection
     * @throws InvalidGatewayException
     *
     */
    protected function makepay($gateway)
    {
        $app = new $gateway();
        if ($app instanceof GatewayInterface) {
            return $app->pay(array_filter($this->payload, function ($value) {
                return '' !== $value && !is_null($value);
            }));
        }
        throw new InvalidGatewayException("pay Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
    }

    /**
     * 验证支付
     *
     * @param string|null $content
     *
     * @throws InvalidSignException
     * @throws InvalidArgumentException
     */
    public function verify($content = null, bool $refund = false): Collection
    {
        $content = $content ?? Request::createFromGlobals()->getContent();

        $data = Support::fromXml($content);
        if ($refund) {
            $decrypt_data = Support::decryptRefundContents($data['req_info']);
            $data = array_merge(Support::fromXml($decrypt_data), $data);
        }
        if ($refund || Support::generateSign($data) === $data['sign']) {
            return new Collection($data);
        }

        throw new InvalidSignException('Wechat Sign Verify Failed', $data);
    }

    /**
     * 查询订单(详见https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_2)
     * 该接口提供所有微信支付订单的查询，商户可以通过查询订单接口主动查询订单状态，完成下一步的业务逻辑。
     * 需要调用查询接口的情况：
     * ◆ 当商户后台、网络、服务器等出现异常，商户系统最终未接收到支付通知；
     * ◆ 调用支付接口后，返回系统错误或未知交易状态情况；
     * ◆ 调用被扫支付API，返回USERPAYING的状态；
     * ◆ 调用关单或撤销接口API之前，需确认支付状态；
     * 接口地址：https://api.mch.weixin.qq.com/pay/orderquery
     *
     * @param string $order
     *
     * @throws GatewayException
     * @throws InvalidSignException
     * @throws InvalidArgumentException
     */
    public function find($order): Collection
    {
        unset($this->payload['spbill_create_ip']);
        $this->payload = Support::filterpayload($this->payload, $order);
        return Support::requestApi(
            'pay/orderquery',
            $this->payload,
            false
        );
    }

    /**
     * 微信支付-申请退款
     * 详见 https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_4
     * 接口链接：https://api.mch.weixin.qq.com/secapi/pay/refund
     *
     * @throws GatewayException
     * @throws InvalidSignException
     * @throws InvalidArgumentException
     */
    public function refund(array $order, $gateway): Collection
    {
        $this->setPayloadType($gateway);
        $this->payload = Support::filterpayload($this->payload, $order, true);
        return Support::requestApi(
            'secapi/pay/refund',
            $this->payload,
            true
        );
    }

    /**
     * 撤销订单API
     *  文档地址：https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_11&index=3
     * 应用场景：
     *  支付交易返回失败或支付系统超时，调用该接口撤销交易。如果此订单用户支付失败，微信支付系统会将此订单关闭；如果用户支付成功，微信支付系统会将此订单资金退还给用户。
     *  注意：7天以内的交易单可调用撤销，其他正常支付的单如需实现相同功能请调用申请退款API。提交支付交易后调用【查询订单API】，没有明确的支付结果再调用【撤销订单API】。
     *  调用支付接口后请勿立即调用撤销订单API，建议支付后至少15s后再调用撤销订单接口。
     *  接口链接 ：https://api.mch.weixin.qq.com/secapi/pay/reverse
     *  是否需要证书：请求需要双向证书。
     * @param array $order
     *
     * @throws GatewayException
     * @throws InvalidSignException
     * @throws InvalidArgumentException
     */
    public function cancel(array $order, $type): Collection
    {
        unset($this->payload['spbill_create_ip']);
        $this->setPayloadType($gateway);

        $this->payload = Support::filterpayload($this->payload, $order, true);

        return Support::requestApi(
            'secapi/pay/reverse',
            $this->payload,
            true
        );
    }

    /**
     * 关闭订单
     * 应用场景
     * 以下情况需要调用关单接口：
     * 1. 商户订单支付失败需要生成新单号重新发起支付，要对原订单号调用关单，避免重复支付；
     * 2. 系统下单后，用户支付超时，系统退出不再受理，避免用户继续，请调用关单接口。
     * 注意：订单生成后不能马上调用关单接口，最短调用时间间隔为5分钟。
     * 接口地址：https://api.mch.weixin.qq.com/pay/closeorder
     * 是否需要证书：   不需要。
     * @param string $order
     * @param $gateway
     * @return Collection
     * @throws InvalidArgumentException
     * @throws \AltairAki\EasyPay\Exceptions\GatewayException
     * @throws \AltairAki\EasyPay\Exceptions\InvalidSignException
     */
    public function close($order, $gateway): Collection
    {
        unset($this->payload['spbill_create_ip']);
        $this->checkGateway($gateway);
        $this->payload = Support::filterpayload($this->payload, $order);

        return Support::requestApi('pay/closeorder', $this->payload);
    }

    /**
     * Echo success to wechat|alipay server.
     * @throws InvalidArgumentException
     */
    public function success(): Response
    {
        return Response::create(
            Support::toXml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']),
            200,
            ['Content-Type' => 'application/xml']
        );
    }

    /**
     * 下载对账单
     * 商户可以通过该接口下载历史交易清单。比如掉单、系统错误等导致商户侧和微信侧数据不一致，通过对账单核对后可校正支付状态。
     * 注意：
     * 1、微信侧未成功下单的交易不会出现在对账单中。支付成功后撤销的交易会出现在对账单中，跟原支付单订单号一致，bill_type为REVOKED；
     * 2、微信在次日9点启动生成前一天的对账单，建议商户10点后再获取；
     * 3、对账单中涉及金额的字段单位为“元”。
     * 4、对账单接口只能下载三个月以内的账单。
     * 接口链接：https://api.mch.weixin.qq.com/pay/downloadbill
     * 详情请见: https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_6
     *
     * @throws GatewayException
     * @throws InvalidArgumentException
     */
    public function download(array $params): string
    {
        unset($this->payload['spbill_create_ip']);

        $this->payload = Support::filterpayload($this->payload, $params, true);

        $result = Support::getInstance()->post(
            'pay/downloadbill',
            Support::getInstance()->toXml($this->payload)
        );

        if (is_array($result)) {
            throw new GatewayException('Get Wechat API Error: ' . $result['return_msg'], $result);
        }

        return $result;
    }
}