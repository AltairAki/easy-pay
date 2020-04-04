<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 14:11
 */

namespace altairaki\pay\Contracts;


use altairaki\pay\Supports\Collection;
use Symfony\Component\HttpFoundation\Response;

interface GatewayInterface
{
    /**
     * pay a order
     *
     * @param $baseUri
     * @param $payload
     * @return Collection|Response
     */
    public function pay($baseUri, array $payload);
}