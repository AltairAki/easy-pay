<?php

namespace altairaki\pay\Exceptions;

class GatewayException extends Exception
{
    /**
     * Bootstrap.
     *
     * @author altair <me@yansonga.cn>
     *
     * @param string       $message
     * @param array|string $raw
     * @param int          $code
     */
    public function __construct($message, $raw = [], $code = self::INVALID_GATEWAY)
    {
        parent::__construct('ERROR_GATEWAY: '.$message, $raw, $code);
    }
}
