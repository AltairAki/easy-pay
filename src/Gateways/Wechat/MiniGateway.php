<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 14:06
 */

namespace AltairAki\Pay\Gateways\Wechat;


use AltairAki\Pay\Supports\Collection;
use AltairAki\Pay\Supports\Str;

class MiniGateway extends Gateway
{
    /**
     * @var bool
     */
    protected $payRequestUseSubAppId = false;

    /**
     * @param $baseUri
     * @param array $payload
     * @return Collection|\Symfony\Component\HttpFoundation\Response
     * @throws \AltairAki\Pay\Exceptions\GatewayException
     * @throws \AltairAki\Pay\Exceptions\InvalidArgumentException
     * @throws \AltairAki\Pay\Exceptions\InvalidSignException
     */
    public function pay($baseUri, array $payload)
    {
        $payload['appid'] = Support::getInstance()->miniapp_id;
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

    public function getTradeType()
    {
        return 'JSAPI';
    }
}