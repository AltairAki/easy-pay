<?php

namespace AltairAki\EasyPay\Gateways\Alipay;

use AltairAki\EasyPay\Contracts\GatewayInterface;
use AltairAki\EasyPay\Exceptions\InvalidArgumentException;
use AltairAki\EasyPay\Supports\Collection;

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
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        $this->mode = Support::getInstance()->mode;
    }

    /**
     * Pay an order.
     *
     * @param string $endpoint
     *
     * @return Collection
     */
    abstract public function pay(array $payload);
}
