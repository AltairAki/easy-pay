<?php

namespace app\pay\Exceptions;

class BusinessException extends GatewayException
{
    /**
     * Bootstrap.
     *
     * @author altair <me@yansonga.cn>
     *
     * @param string       $message
     * @param array|string $raw
     */
    public function __construct($message, $raw = [])
    {
        parent::__construct('ERROR_BUSINESS: '.$message, $raw, self::ERROR_BUSINESS);
    }
}
