<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 16:03
 */

namespace altairaki\pay\Gateways\Wechat;


use altairaki\pay\Contracts\GatewayInterface;
use altairaki\pay\Exceptions\InvalidArgumentException;
use altairaki\pay\Supports\Collection;

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
     * @throws \altairaki\pay\Exceptions\GatewayException
     * @throws \altairaki\pay\Exceptions\InvalidSignException
     */
    protected function preOrder($payload): Collection
    {
        $payload['sign'] = Support::generateSign($payload);
        return Support::requestApi('pay/unifiedorder', $payload);
    }


}