<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 11:19
 */

namespace app\pay;


use app\pay\Contracts\GatewayApplicationInterface;
use app\pay\Exceptions\Exception;
use app\pay\Exceptions\InvalidGatewayException;
use app\pay\Supports\Config;
use app\pay\Supports\Str;

class pay
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
     * @author altair <me@yansonga.cn>
     *
     */
    protected function make($gateway): GatewayApplicationInterface
    {
        $app = new $gateway($this->config);

        if ($app instanceof GatewayApplicationInterface) {
            return $app;
        }

        throw new InvalidGatewayException("Gateway [{$gateway}] Must Be An Instance Of GatewayApplicationInterface");
    }

    /**
     * Register log service.
     *
     *
     * @throws Exception
     */
//    protected function registerLogService()
//    {
//        $config = $this->config->get('log');
//        $config['identify'] = 'altair.pay';
//
//        $logger = new Logger();
//        $logger->setConfig($config);
//
//        Log::setInstance($logger);
//    }
//
//    /**
//     * Register event service.
//     *
//
//     */
//    protected function registerEventService()
//    {
//        Events::setDispatcher(Events::createDispatcher());
//
//        Events::addSubscriber(new KernelLogSubscriber());
//    }
}