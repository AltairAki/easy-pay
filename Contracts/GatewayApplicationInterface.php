<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 11:43
 */

namespace app\pay\Contracts;


use app\supports\Collection;
use Symfony\Component\HttpFoundation\Response;

interface GatewayApplicationInterface
{
    /**
     * To pay.
     * @param $gateway
     * @param $params
     * @return Collection|Response
     */
    public function pay($gateway, $params);

    /**
     * Echo success to server.
     *
     * @return Response
     */
    public function success();
}