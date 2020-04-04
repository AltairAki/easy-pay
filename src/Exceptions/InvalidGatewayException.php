<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 12:09
 */

namespace altairaki\pay\Exceptions;


class InvalidGatewayException extends Exception
{
    public function __construct($message = '', $raw = [], $code = self::UNKNOWN_ERROR)
    {
        parent::__construct('INVALID_GATEWAY: '.$message, $raw, self::INVALID_GATEWAY);
    }
}