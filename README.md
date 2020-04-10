<h1 align="center">Easy Pay</h1>

<p align="center">一款满足你的多种支付方式的组件</p>

## 特点

1. 根据支付宝、微信最新 API 开发而成
2. 一套写法兼容微信支付宝支付，不用再纠结命名
3. 简单配置即可使用，免去各种拼json与xml的痛苦

## 平台支持

- [微信支付](https://pay.weixin.qq.com/wiki/doc/api/index.html)
- [支付宝支付](https://opendocs.alipay.com/apis/api_1/)

## 环境需求

- PHP >= 7.1

## 安装

```shell
$ composer require "altairaki/easy-pay"
```

## 使用

### 微信支付  

支持的应用 | 描述
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

class PayController
{
    $config = [
        'appid'         => 'wxc9d7aa3c5f3c5123', // APP APPID
        'app_id'        => 'wx21111df6eb31d123', // 公众号 APPID
        'mini_id'    => 'wx0cb12db05346eb50', // 小程序 APPID
        'mch_id'        => '',
        'key'           => '', //
        'cert_client'   => './cert/apiclient_cert.pem', // 可选项，退款等情况时用到
        'cert_key'      => './cert/apiclient_key.pem',  // 可选项，退款等情况时用到
    ];
    
    public function payByWechat(Order $order, Request $request) {
        // 校验权限
        $this->authorize('own', $order);
        // 校验订单状态
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
        $app = Pay::wechat($this->config);
        // scan 方法为拉起微信扫码支付
        return $app->scan([
            'out_trade_no' => time(),  // 商户订单号
            'total_fee' => 101, // 微信支付的金额单位是分。
            'body'      => 'Test Body', // 订单描述
        ]);
    }

    public function wechatNotify()
    {
        $app = Pay::wechat($this->config);
        // 校验回调参数是否正确
        $data  = $app->verify();
        // 找到对应的订单
        $order = Order::where('no', $data->out_trade_no)...;
        // 订单不存在则告知微信支付
        if (!$order) {
            return 'fail';
        }
        // 订单已支付
        if ($order->paid_at) {
            // 告知微信支付此订单已处理
            return app('wechat_pay')->success()->send();
        }

        // 将订单标记为已支付
        $order->update([
            'paid_at'        => time(),
            'payment_method' => 'wechat',
            'payment_no'     => $data->transaction_id,
        ]);

        return app('wechat_pay')->success()->send();
    }
}
```

## 支付宝支付

支持的应用 | 描述
----|----
app | APP支付
web | 电脑支付
wap | 手机网站支付
mini | 小程序支付
pos | 刷卡支付
scan | 扫码支付
transfer | 帐户转账

```php
use AltairAki\EasyPay\Pay;
        
class PayController{
    public function pay()
    {
        $config = [
            'app_id' => '2016092400583112',
            'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsHBArzr7jdkPF63gGbcnCQy1/oa5Pg/Qgrvg+bwl1O8wDFu3SXz4RHM12680V5E5IM07PRzc4svwjxj6jjS+vPmGOqRe1GRnvdaRE5XUDWCCEwJABSllmGksbGeCP6gZ222FliRVG6d380NrC8Bmv6X+5TDQDmcg30vmDCf4sGwLB/MCatbuj/1PwBDuNtVUCmzdp7bNc/+Rs5AdSUCL+SbIaAlb5dzljujSCGZUIxhBd8h8PdjEBkA3yNHRg61zw7pGJjsHDya5g2lOeINL99nnCMWaYw7RQuRQxCYWiDk4AUVsmesswDDfB6SJzRjktfZicCjBy5XCBw3OCfFw6QIDAQAB',
            'private_key' => 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCwcECvOvuN2Q8XreAZtycJDLX+hrk+D9CCu+D5vCXU7zAMW7dJfPhEczXbrzRXkTkgzTs9HNziy/CPGPqONL68+YY6pF7UZGe91pETldQNYIITAkAFKWWYaSxsZ4I/qBnbbYWWJFUbp3fzQ2sLwGa/pf7lMNAOZyDfS+YMJ/iwbAsH8wJq1u6P/U/AEO421VQKbN2nts1z/5GzkB1JQIv5JshoCVvl3OWO6NIIZlQjGEF3yHw92MQGQDfI0dGDrXPDukYmOwcPJrmDaU54g0v32ecIxZpjDtFC5FDEJhaIOTgBRWyZ6yzAMN8HpInNGOS19mJwKMHLlcIHDc4J8XDpAgMBAAECggEAH/56/EuJyhMONZEGDiO0JGP1rI3pkWN0wAApr596jL5CzDrlZaIPsvnhTlDbAPYIkfYlQ9O0CjxJBunUpWzTGZl1ybR8ra73UOlTrWWB6lsRuzixOz5iedy4fX/Xkot9BNk7XBqChF091xLmml7tQttq+Ux8rd/tihBNSu8EnZjscokVYnaJlG387XAGzYEi1IAERZALEssu72Mp+uYP7/CEmy7ne5J0Fr3QwR8k/AevBGtv9VEvyhYQvai9sQ1MMIFUw04WnVCOEVT+TtfAgbE+LslFCF/t+JUKPfrexqaCUeTh1DXP6OZTC1QKQ4OXc0T+3eRYG1AC14WoIKt4sQKBgQDsfWMzhQS8C0OXBAvIH7ZDBKMKnI2sYOiDrxMz7kFp9Jql2PMm+VwtfrRGtt0frpVkqEWu5tcWaHg5pk+wQQ4EL2NXCRIWMezj67YOpMKeUhycc4ENbop8E0/+3Bl8nFul253fhOPHBOeNkBOPfSvLlftHubTph8ZvjIWgHBV0owKBgQC+/phBE+v1yyH0HYovMOLqTHg1U0ZperMQjBGE11VpD9NL7NigZ+/3isdh5TolIqOKQtWvmKP92lDvqjAzJOrN+pYRDLoW9meECeQG9gCgsP1QyVLxu/ibUG4WYvwKggaQrG+MiqdRxM1/ndgH2ZR0kmZr58GyL9EV0+ApqCjRAwKBgF9IQcDPNlIhY7Ejwy91f3TPGHW9D+PFA8mSr3T76MUs9WYe3BD25Sm7ZB0drkgGilCM786BWWXA37eyh2bnPyN2iFrX376rjNtj6+1IetVZFgf/DZ8Ay7EkAtYXjflD8jUIIDqfizpzgmvqAceNUijrm9uROg/hUZU+E9SnnAlrAoGADKvfdhHYScpcSlHbZR4dL+Y6427O8RiO4L4qO2H97KZ8IkFobdv3c7jlWX1XyjbuGrIscyXxW1osnHnyELKWUWwaoK7zeaqHW588XanciMy1QbLZqegKqmM/qoSOrDPMM7T9AZoBV89ywtC6EGtDCijcWrRZiXTarlQMPzdE3fkCgYB/1TswTkmSn4aALTVlSBcDey3vDUvJfCMV0QsWEHb5yytKKvHpq7lsTvw3qHTSm7fbItfDzXu7Pw9Nns2co17hAyGes8TOBUGk1DBev49lbhkbKUODCK523W8V+p8N9mNje8rXu60cnqXf4/5xeaL8dtUi7MYh9fERF1Qu+OFYBg==',
            // 使用公钥证书模式，请配置下面两个参数，同时修改ali_public_key为以.crt结尾的支付宝公钥证书路径，如（./cert/alipayCertPublicKey_RSA2.crt）
            // 'app_cert_public_key' => './cert/appCertPublicKey.crt', //应用公钥证书路径
            // 'alipay_root_cert' => './cert/alipayRootCert.crt', //支付宝根证书路径
        ];

        Pay::wechat($config)->web([
            'out_trade_no' => time(),
            'body' => 'Body',
            'total_fee' => 101,
            'notify_url' => 'http://dev.com/notify.php',
            'return_url' => 'http://dev.com/return.php',
            'openid' => 'owufD0Wzz6oZX1AUiYcHzYafPDFA',
        ]);
    }

    // 前端回调页面
    public function alipayReturn()
    {
       try {
           app('alipay')->verify();
       } catch (\Exception $e) {
           return view('pages.error', ['msg' => '数据不正确']);
       }
       return view('pages.success', ['msg' => '付款成功']);
    }

    // 服务器端回调
    public function alipayNotify()
    {
        // 校验输入参数
        $data = app('alipay')->verify();
        // 如果订单状态不是成功或者结束，则不走后续的逻辑
        // 所有交易状态：https://docs.open.alipay.com/59/103672
        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success()->send();
        }

        $order = Order::where('no', $data->out_trade_no)->first();
        // 正常来说不太可能出现支付了一笔不存在的订单，这个判断只是加强系统健壮性。
        if (!$order) {
            return 'fail';
        }
        // 如果这笔订单的状态已经是已支付
        if ($order->paid_at) {
            // 返回数据给支付宝
            return app('alipay')->success();
        }
        $order->update([
            'paid_at' => time(), // 支付时间
            'payment_method' => 'alipay', // 支付方式
            'payment_no' => $data->trade_no, // 支付宝订单号
        ]);
        return app('alipay')->success()->send(); //lavavel 直接 return app('alipay')->success();
    }
}

```


## 各平台配置说明

### [微信支付](https://pay.weixin.qq.com/wiki/doc/api/index.html)



```php
    'wecaht' => [
        'appid'         => 'wxc9d7aa3c5f3c5123', // APP APPID
        'app_id'        => 'wx21111df6eb31d123', // 公众号 APPID
        'mini_id'    => 'wx0cb12db05346eb50', // 小程序 APPID
        'mch_id'        => '1222040111',
        'key'           => 'your key', //商户平台设置的密钥key
        'cert_client'   => './cert/apiclient_cert.pem', // optional，退款等情况时用到
        'cert_key'      => './cert/apiclient_key.pem',// optional，退款等情况时用到
    ],
```
由于使用多网关支付，且微信APP，公众号，小程序都有自己的appid，因此在 查询订单|退款|等操作时需要指定gateway
```php
    'wecaht' => [
        ...
        'gateway' => 'app', //APP-app,小程序-mini,公众号-oa
    ],
```


## License

MIT
