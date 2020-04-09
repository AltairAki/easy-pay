<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 16:03
 */

namespace AltairAki\EasyPay\Gateways\Wechat;


use AltairAki\EasyPay\Contracts\GatewayInterface;
use AltairAki\EasyPay\Exceptions\GatewayException;
use AltairAki\EasyPay\Exceptions\InvalidArgumentException;
use AltairAki\EasyPay\Exceptions\InvalidSignException;
use AltairAki\EasyPay\Supports\Collection;

abstract class Gateway implements GatewayInterface
{
    /**
     * Mode.
     *
     * @var string
     */
    protected $mode;

    /**
     * @var bool
     */
    protected $payRequestUseSubAppId = false;

    /**
     * Bootstrap.
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        $this->mode = Support::getInstance()->mode;
    }


    abstract public function pay(array $payload);

    /**
     * 统一下单(详见https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1)
     * 在发起微信支付前，需要调用统一下单接口，获取"预支付交易会话标识"
     * 接口地址：https://api.mch.weixin.qq.com/pay/unifiedorder
     * @param $payload
     * @return Collection
     * @throws InvalidArgumentException
     * @throws GatewayException
     * @throws InvalidSignException
     */
    protected function preOrder($payload): Collection
    {
        $payload['sign'] = Support::generateSign($payload);
        return Support::requestApi('pay/unifiedorder', $payload);
    }

    protected function getTradeType()
    {
        return 'JSAPI';
    }


}