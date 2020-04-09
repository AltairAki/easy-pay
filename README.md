<h1 align="center">Easy Pay</h1>

<p align="center">一款满足你的多种支付方式的组件</p>

## 特点

1. 根据支付宝、微信最新 API 开发而成
2. 一套写法兼容微信支付宝支付，不用再纠结命名
3. 简单配置即可使用，免去各种拼json与xml的痛苦

## 平台支持

- [微信支付](https://pay.weixin.qq.com/wiki/doc/api/index.html/)
- [支付宝支付](https://opendocs.alipay.com/apis)

## 环境需求

- PHP >= 7.1

## 安装

```shell
$ composer require "altairaki/easy-pay"
```

## 使用

### 微信支付  

支持的方法 | 描述
----|----
app | APP支付
oa | 公众号支付
mini | 小程序支付
wap | H5支付
pos | 刷卡支付
scan | 扫码支付
transfer | 企业付款
redpack | 普通红包
groupRedpack | 分裂红包 

```php
use AltairAki\EasyPay\Pay;

$config = [
    'appid'         => 'wxc9d7aa3c5f3c5123', // APP APPID
    'app_id'        => 'wx21111df6eb31d123', // 公众号 APPID
    'mini_id'    => 'wx0cb12db05346eb50', // 小程序 APPID
    'mch_id'        => '1222040111',
    'key'           => '', //
    'cert_client'   => './cert/apiclient_cert.pem', // optional，退款等情况时用到
    'cert_key'      => './cert/apiclient_key.pem',// optional，退款等情况时用到
];

Pay::wechat($config)->app([
  'out_trade_no' => time(),
  'body' => 'Body',
  'total_fee' => 101,
  'notify_url' => 'your notify_url',
  'openid' => 'owufD0Wzz6oZX1AUiYcHzYafPDFA',
]);
```

## 支付宝支付

开发中

## 自定义网关

开发中


## 各平台配置说明

### [微信支付](https://pay.weixin.qq.com/wiki/doc/api/index.html)

```php
    'wecaht' => [
        'appid'         => 'wxc9d7aa3c5f3c5123', // APP APPID
        'app_id'        => 'wx21111df6eb31d123', // 公众号 APPID
        'mini_id'    => 'wx0cb12db05346eb50', // 小程序 APPID
        'mch_id'        => '1222040111',
        'key'           => 'your key', //商户平台设置的密钥key
        'cert_client'   => 'xxx/apiclient_cert.pem', // optional，退款等情况时用到
        'cert_key'      => 'xxx/apiclient_key.pem',// optional，退款等情况时用到
    ],
```
由于使用多网关支付，查询订单|退款|等需要指定gateway
```php
    'wecaht' => [
        ...
        'gateway' => 'app', //APP-app,小程序-mini,公众号-oa
    ],
```


## License

MIT
