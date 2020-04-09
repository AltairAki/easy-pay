<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/8
 * Time: 15:51
 */

namespace AltairAki\EasyPay\Gateways\Wechat;


use AltairAki\EasyPay\Supports\Collection;
use AltairAki\EasyPay\Supports\Str;

class OaGateway extends Gateway
{

    /**
     * 生成公众号预支付订单
     * @param array $payload
     * @return Collection|\Symfony\Component\HttpFoundation\Response
     * @throws \AltairAki\EasyPay\Exceptions\GatewayException
     * @throws \AltairAki\EasyPay\Exceptions\InvalidArgumentException
     * @throws \AltairAki\EasyPay\Exceptions\InvalidSignException
     */
    public function pay(array $payload)
    {
        $payload['appid'] = Support::getInstance()->app_id;
        $payload['trade_type'] = $this->getTradeType();

        $pay_request = [
            'appId' => !$this->payRequestUseSubAppId ? $payload['appid'] : $payload['sub_appid'],
            'timeStamp' => strval(time()),
            'nonceStr' => Str::random(),
            'package' => 'prepay_id=' . $this->preOrder($payload)->get('prepay_id'),
            'signType' => 'MD5',
        ];
        $pay_request['paySign'] = Support::generateSign($pay_request);

        return new Collection($pay_request);
    }
}