<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 16:03
 */

namespace AltairAki\Pay\Gateways\Wechat;


use AltairAki\Pay\Contracts\GatewayInterface;
use AltairAki\Pay\Exceptions\InvalidArgumentException;
use AltairAki\Pay\Supports\Collection;

abstract class Gateway implements GatewayInterface
{
    /**
     * Mode.
     *
     * @var string
     */
    protected $mode;

    /**
     * Bootstrap.
     *

     *
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        $this->mode = Support::getInstance()->mode;
    }


    abstract public function pay($baseUri, array $payload);

    /**
     * @param $payload
     * @return Collection
     * @throws InvalidArgumentException
     * @throws \AltairAki\Pay\Exceptions\GatewayException
     * @throws \AltairAki\Pay\Exceptions\InvalidSignException
     */
    protected function preOrder($payload): Collection
    {
        $payload['sign'] = Support::generateSign($payload);
        return Support::requestApi('pay/unifiedorder', $payload);
    }


}