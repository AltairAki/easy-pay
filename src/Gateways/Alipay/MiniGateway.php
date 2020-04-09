<?php

namespace AltairAki\EasyPay\Gateways\Alipay;

use AltairAki\EasyPay\Exceptions\GatewayException;
use AltairAki\EasyPay\Exceptions\InvalidArgumentException;
use AltairAki\EasyPay\Exceptions\InvalidConfigException;
use AltairAki\EasyPay\Exceptions\InvalidSignException;
use AltairAki\EasyPay\Gateways\Alipay;
use AltairAki\EasyPay\Supports\Collection;

class MiniGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @param array $payload
     *
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     *
     * @see https://docs.alipay.com/mini/introduce/pay
     */
    public function pay(array $payload): Collection
    {
        $biz_array = json_decode($payload['biz_content'], true);
        if (empty($biz_array['buyer_id'])) {
            throw new InvalidArgumentException('buyer_id required');
        }
        if ((Alipay::MODE_SERVICE === $this->mode) && (!empty(Support::getInstance()->pid))) {
            $biz_array['extend_params'] = is_array($biz_array['extend_params']) ? array_merge(['sys_service_provider_id' => Support::getInstance()->pid], $biz_array['extend_params']) : ['sys_service_provider_id' => Support::getInstance()->pid];
        }
        $payload['biz_content'] = json_encode($biz_array);
        $payload['method'] = 'alipay.trade.create';
        $payload['sign'] = Support::generateSign($payload);

        return Support::requestApi($payload);
    }
}
