<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 11:43
 */

namespace AltairAki\EasyPay\Contracts;


use AltairAki\EasyPay\Supports\Collection;
use Symfony\Component\HttpFoundation\Response;

interface GatewayApplicationInterface
{
    /**
     * To pay.
     * @param $params
     * @return Collection|Response
     */
    public function pay($params);

    /**
     * Echo success to server.
     *
     * @return Response
     */
    public function success();
}