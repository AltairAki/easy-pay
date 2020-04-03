<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 15:02
 */

namespace AltairAki\Pay\Exceptions;


class InvalidArgumentException extends Exception
{
    /**
     * InvalidArgumentException constructor.
     * @param $message
     * @param array $raw
     */
    public function __construct($message, $raw = [])
    {
        parent::__construct('INVALID_GATEWAY: '.$message, $raw, self::INVALID_ARGUMENT);
    }
}