<?php
/**
 * Created by PhpStorm
 * Author: Altair
 * Date: 2020/4/2
 * Time: 14:22
 */

namespace altairAki\Pay\Gateways\Wechat;


use altairAki\Pay\Exceptions\BusinessException;
use altairAki\Pay\Exceptions\Exception;
use altairAki\Pay\Exceptions\GatewayException;
use altairAki\Pay\Exceptions\InvalidArgumentException;
use altairAki\Pay\Exceptions\InvalidSignException;
use altairAki\Pay\Gateways\Wechat;
use altairAki\Pay\Supports\Collection;
use altairAki\Pay\Supports\Config;
use altairAki\Pay\Supports\Str;
use altairAki\Pay\Supports\Traits\HasHttpRequest;

class Support
{
    use HasHttpRequest;

    /**
     * Wechat gateway.
     *
     * @var string
     */
    protected $baseUri;

    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Instance.
     *
     * @var Support
     */
    private static $instance;

    /**
     * Bootstrap.
     */
    private function __construct(Config $config)
    {
        $this->baseUri = Wechat::URL[$config->get('mode', Wechat::MODE_NORMAL)];
        $this->config = $config;

        $this->setHttpOptions();
    }

    /**
     * __get.
     *
     * @param $key
     *
     * @return mixed|Config|null

     *
     */
    public function __get($key)
    {
        return $this->getConfig($key);
    }

    /**
     * @param Config $config
     * @return Support
     * @throws InvalidArgumentException
     */
    public static function create(Config $config)
    {
        if ('cli' === php_sapi_name() || !(self::$instance instanceof self)) {
            self::$instance = new self($config);

            self::setDevKey();
        }

        return self::$instance;
    }

    /**
     * getInstance.
     *
     * @return Support
     * @throws InvalidArgumentException
     *

     *
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            throw new InvalidArgumentException('You Should [Create] First Before Using');
        }

        return self::$instance;
    }

    /**
     * clear.
     *

     */
    public static function clear()
    {
        self::$instance = null;
    }

    /**
     * Request wechat api.
     *
     * @param string $endpoint
     * @param array $data
     * @param bool $cert
     *
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     */
    public static function requestApi($endpoint, $data, $cert = false): Collection
    {
        $result = self::$instance->post(
            $endpoint,
            self::toXml($data),
            $cert ? [
                'cert' => self::$instance->cert_client,
                'ssl_key' => self::$instance->cert_key,
            ] : []
        );
        $result = is_array($result) ? $result : self::fromXml($result);

        return self::processingApiResult($endpoint, $result);
    }

    /**
     * Filter payload.
     *
     * @param array $payload
     * @param array|string $params
     * @param bool $preserve_notify_url
     *
     * @throws InvalidArgumentException
     *
     */
    public static function filterpayload($payload, $params, $preserve_notify_url = false): array
    {
        $type = self::getTypeName($params['type'] ?? '');

        $payload = array_merge(
            $payload,
            is_array($params) ? $params : ['out_trade_no' => $params]
        );
        $payload['appid'] = self::$instance->getConfig($type, '');

        if (Wechat::MODE_SERVICE === self::$instance->getConfig('mode', Wechat::MODE_NORMAL)) {
            $payload['sub_appid'] = self::$instance->getConfig('sub_' . $type, '');
        }

        unset($payload['trade_type'], $payload['type']);
        if (!$preserve_notify_url) {
            unset($payload['notify_url']);
        }

        $payload['sign'] = self::generateSign($payload);

        return $payload;
    }

    /**
     * Generate wechat sign.
     *
     * @param array $data
     *
     * @throws InvalidArgumentException
     */
    public static function generateSign($data): string
    {
        $key = self::$instance->key;

        if (is_null($key)) {
            throw new InvalidArgumentException('Missing Wechat Config -- [key]');
        }

        ksort($data);

        $string = md5(self::getSignContent($data) . '&key=' . $key);

        return strtoupper($string);
    }

    /**
     * Generate sign content.
     *
     * @param array $data
     */
    public static function getSignContent($data): string
    {
        $buff = '';

        foreach ($data as $k => $v) {
            $buff .= ('sign' != $k && '' != $v && !is_array($v)) ? $k . '=' . $v . '&' : '';
        }

        return trim($buff, '&');
    }

    /**
     * Decrypt refund contents.
     *
     * @param string $contents
     */
    public static function decryptRefundContents($contents): string
    {
        return openssl_decrypt(
            base64_decode($contents),
            'AES-256-ECB',
            md5(self::$instance->key),
            OPENSSL_RAW_DATA
        );
    }

    /**
     * Convert array to xml.
     *
     * @param array $data
     * @throws InvalidArgumentException
     */
    public static function toXml($data): string
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new InvalidArgumentException('Convert To Xml Error! Invalid Array!');
        }

        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= is_numeric($val) ? '<' . $key . '>' . $val . '</' . $key . '>' :
                '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * Convert xml to array.
     *
     * @param string $xml
     *
     * @throws InvalidArgumentException

     *
     */
    public static function fromXml($xml): array
    {
        if (!$xml) {
            throw new InvalidArgumentException('Convert To Array Error! Invalid Xml!');
        }

        libxml_disable_entity_loader(true);

        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * Get service config.
     *
     * @param string|null $key
     * @param mixed|null $default
     *
     * @return mixed|null

     *
     */
    public function getConfig($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->config->all();
        }

        if ($this->config->has($key)) {
            return $this->config[$key];
        }

        return $default;
    }

    /**
     * Get app id according to param type.
     *
     * @param string $type

     *
     */
    public static function getTypeName($type = ''): string
    {
        switch ($type) {
            case '':
                $type = 'app_id';
                break;
            case 'app':
                $type = 'appid';
                break;
            default:
                $type = $type . '_id';
        }

        return $type;
    }

    /**
     * Get Base Uri.
     *
     * @return string

     *
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * processingApiResult.
     *
     * @param $endpoint
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     *
     * @throws GatewayException
     *
     */
    protected static function processingApiResult($endpoint, array $result)
    {
        if (!isset($result['return_code']) || 'SUCCESS' != $result['return_code']) {
            throw new GatewayException('Get Wechat API Error:' . ($result['return_msg'] ?? $result['retmsg'] ?? ''), $result);
        }

        if (isset($result['result_code']) && 'SUCCESS' != $result['result_code']) {
            throw new BusinessException('Wechat Business Error: ' . $result['err_code'] . ' - ' . $result['err_code_des'], $result);
        }

        if ('pay/getsignkey' === $endpoint ||
            false !== strpos($endpoint, 'mmpaymkttransfers') ||
            self::generateSign($result) === $result['sign']) {
            return new Collection($result);
        }
        throw new InvalidSignException('Wechat Sign Verify FAILED', $result);
    }

    /**
     * setDevKey.
     *
     * @return Support
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     * @throws Exception
     *
     * @throws GatewayException
     *
     */
    private static function setDevKey()
    {
        if (Wechat::MODE_DEV == self::$instance->mode) {
            $data = [
                'mch_id' => self::$instance->mch_id,
                'nonce_str' => Str::random(),
            ];
            $data['sign'] = self::generateSign($data);

            $result = self::requestApi('pay/getsignkey', $data);

            self::$instance->config->set('key', $result['sandbox_signkey']);
        }

        return self::$instance;
    }

    /**
     * Set Http options.
     *

     */
    private function setHttpOptions(): self
    {
        if ($this->config->has('http') && is_array($this->config->get('http'))) {
            $this->config->forget('http.base_uri');
            $this->httpOptions = $this->config->get('http');
        }

        return $this;
    }
}