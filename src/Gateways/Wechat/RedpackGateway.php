<?php

namespace AltairAki\EasyPay\Gateways\Wechat;

use Symfony\Component\HttpFoundation\Request;
use AltairAki\EasyPay\Exceptions\GatewayException;
use AltairAki\EasyPay\Exceptions\InvalidArgumentException;
use AltairAki\EasyPay\Exceptions\InvalidSignException;
use AltairAki\EasyPay\Gateways\Wechat;
use AltairAki\Supports\Collection;

class RedpackGateway extends Gateway
{
    /**
     * 发送微信红包给个人用户
     * 文档详见:
     * 发送普通红包 https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon.php?chapter=13_4&index=3
     * 接口地址：https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack
     *
     * @param array $payload
     *
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     */
    public function pay(array $payload): Collection
    {
        $payload['wxappid'] = $payload['appid'];
        $payload['notify_way'] = 'MINI_PROGRAM_JSAPI';
        $payload['total_num'] = 1;

        if($payload['total_amount'] > 2000 && !isset($payload['scene_id'])){
            throw new InvalidArgumentException("When the amount of red packets is greater than 200, it must be transferred to the scene_id");
        }
        if ('cli' !== php_sapi_name()) {
            $payload['client_ip'] = Request::createFromGlobals()->server->get('SERVER_ADDR');
        }

        if (Wechat::MODE_SERVICE === $this->mode) {
            $payload['msgappid'] = $payload['appid'];
        }

        unset($payload['appid'], $payload['trade_type'],
              $payload['notify_url'], $payload['spbill_create_ip']);

        $payload['sign'] = Support::generateSign($payload);

        return Support::requestApi(
            'mmpaymkttransfers/sendredpack',
            $payload,
            true
        );
    }
}
