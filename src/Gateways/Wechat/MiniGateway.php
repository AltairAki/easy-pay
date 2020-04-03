<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 14:06
 */

namespace altairAki\Pay\Gateways\Wechat;


use altairAki\Pay\Supports\Collection;
use altairAki\Pay\Supports\Str;

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
     * @throws \altairAki\Pay\Exceptions\GatewayException
     * @throws \altairAki\Pay\Exceptions\InvalidArgumentException
     * @throws \altairAki\Pay\Exceptions\InvalidSignException
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