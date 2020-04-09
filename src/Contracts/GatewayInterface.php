<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 14:11
 */

namespace AltairAki\EasyPay\Contracts;


use AltairAki\EasyPay\Supports\Collection;
use Symfony\Component\HttpFoundation\Response;

interface GatewayInterface
{
    /**
     * pay a order
     *
     * @param $payload
     * @return Collection|Response
     */
    public function pay(array $payload);
}