<?php

namespace altairAki\Pay\Exceptions;

class InvalidSignException extends Exception
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
        parent::__construct('INVALID_SIGN: '.$message, $raw, self::INVALID_SIGN);
    }
}
