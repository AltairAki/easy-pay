<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 11:19
 */

namespace AltairAki\EasyPay;


use AltairAki\EasyPay\Contracts\GatewayApplicationInterface;
use AltairAki\EasyPay\Exceptions\Exception;
use AltairAki\EasyPay\Exceptions\InvalidGatewayException;
use AltairAki\EasyPay\Gateways\Wechat;
use AltairAki\EasyPay\Supports\Config;
use AltairAki\EasyPay\Supports\Str;

/**
 * @method static Wechat wechat(array $config) 微信
 */
class Pay
{
    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Bootstrap.
     *
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * Magic static call.
     *
     * @param string $method
     * @param array $params
     *
     * @throws InvalidGatewayException
     * @throws Exception
     */
    public static function __callStatic($method, $params): GatewayApplicationInterface
    {
        $app = new self(...$params);

        return $app->create($method);
    }

    /**
     * Create a instance.
     *
     * @param string $method
     *
     * @throws InvalidGatewayException
     */
    protected function create($method): GatewayApplicationInterface
    {
        $gateway = __NAMESPACE__ . '\\Gateways\\' . Str::studly($method);
        if (class_exists($gateway)) {
            return self::make($gateway);
        }
        throw new InvalidGatewayException("Gateway [{$method}] Not Exists");
    }

    /**
     * Make a gateway.
     *
     * @param string $gateway
     *
     * @throws InvalidGatewayException
     */
    protected function make($gateway): GatewayApplicationInterface
    {
        $app = new $gateway($this->config);

        if ($app instanceof GatewayApplicationInterface) {
            return $app;
        }

        throw new InvalidGatewayException("Gateway [{$gateway}] Must Be An Instance Of GatewayApplicationInterface");
    }
}