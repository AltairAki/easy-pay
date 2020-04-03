<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 16:03
 */

namespace altairAki\Pay\Gateways\Wechat;


use altairAki\Pay\Contracts\GatewayInterface;
use altairAki\Pay\Exceptions\InvalidArgumentException;
use altairAki\Pay\Supports\Collection;

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
     * @throws \altairAki\Pay\Exceptions\GatewayException
     * @throws \altairAki\Pay\Exceptions\InvalidSignException
     */
    protected function preOrder($payload): Collection
    {
        $payload['sign'] = Support::generateSign($payload);
        return Support::requestApi('pay/unifiedorder', $payload);
    }


}