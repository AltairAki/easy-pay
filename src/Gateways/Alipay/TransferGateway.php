<?php

namespace AltairAki\EasyPay\Gateways\Alipay;

use AltairAki\EasyPay\Contracts\GatewayInterface;
use AltairAki\EasyPay\Events;
use AltairAki\EasyPay\Exceptions\GatewayException;
use AltairAki\EasyPay\Exceptions\InvalidConfigException;
use AltairAki\EasyPay\Exceptions\InvalidSignException;
use AltairAki\EasyPay\Supports\Collection;

class TransferGateway implements GatewayInterface
{
    /**
     * Pay an order.
     *
     * @throws GatewayException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function pay(array $payload): Collection
    {
        $payload['method'] = 'alipay.fund.trans.uni.transfer';
        $payload['sign'] = Support::generateSign($payload);

        return Support::requestApi($payload);
    }

    /**
     * Find.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $order
     */
    public function find($order): array
    {
        return [
            'method' => 'alipay.fund.trans.order.query',
            'biz_content' => json_encode(is_array($order) ? $order : ['out_biz_no' => $order]),
        ];
    }
}
