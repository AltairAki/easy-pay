<?php

namespace AltairAki\EasyPay\Gateways\Alipay;

use AltairAki\EasyPay\Exceptions\GatewayException;
use AltairAki\EasyPay\Exceptions\InvalidArgumentException;
use AltairAki\EasyPay\Exceptions\InvalidConfigException;
use AltairAki\EasyPay\Exceptions\InvalidSignException;
use AltairAki\EasyPay\Gateways\Alipay;
use AltairAki\EasyPay\Supports\Collection;

class PosGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @param array $payload
     *
     * @throws InvalidArgumentException
     * @throws GatewayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function pay(array $payload): Collection
    {
        $payload['method'] = 'alipay.trade.pay';
        $biz_array = json_decode($payload['biz_content'], true);
        if ((Alipay::MODE_SERVICE === $this->mode) && (!empty(Support::getInstance()->pid))) {
            $biz_array['extend_params'] = is_array($biz_array['extend_params']) ? array_merge(['sys_service_provider_id' => Support::getInstance()->pid], $biz_array['extend_params']) : ['sys_service_provider_id' => Support::getInstance()->pid];
        }
        $payload['biz_content'] = json_encode(array_merge(
            $biz_array,
            [
                'product_code' => 'FACE_TO_FACE_PAYM',
                'scene' => 'bar_code',
            ]
        ));
        $payload['sign'] = Support::generateSign($payload);
        return Support::requestApi($payload);
    }
}
