<?php

namespace AltairAki\EasyPay\Gateways\Alipay;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use AltairAki\EasyPay\Events;
use AltairAki\EasyPay\Exceptions\InvalidArgumentException;
use AltairAki\EasyPay\Exceptions\InvalidConfigException;
use AltairAki\EasyPay\Gateways\Alipay;

class WebGateway extends Gateway
{
    /**
     * Pay an order.
     * @param string $endpoint
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     */
    public function pay(array $payload): Response
    {
        $biz_array = json_decode($payload['biz_content'], true);
        $biz_array['product_code'] = $this->getProductCode();

        $method = $biz_array['http_method'] ?? 'POST';

        unset($biz_array['http_method']);
        if ((Alipay::MODE_SERVICE === $this->mode) && (!empty(Support::getInstance()->pid))) {
            $biz_array['extend_params'] = is_array($biz_array['extend_params']) ? array_merge(['sys_service_provider_id' => Support::getInstance()->pid], $biz_array['extend_params']) : ['sys_service_provider_id' => Support::getInstance()->pid];
        }
        $payload['method'] = $this->getMethod();
        $payload['biz_content'] = json_encode($biz_array);
        $payload['sign'] = Support::generateSign($payload);

        return $this->buildPayHtml($payload, $method);
    }

    /**
     * Find.
     * @param $order
     */
    public function find($order): array
    {
        return [
            'method' => 'alipay.trade.query',
            'biz_content' => json_encode(is_array($order) ? $order : ['out_trade_no' => $order]),
        ];
    }

    /**
     * Build Html response.
     * @param array  $payload
     * @param string $method
     */
    protected function buildPayHtml($payload, $method = 'POST'): Response
    {
        $endpoint = Support::getInstance()->getBaseUri();
        if ('GET' === strtoupper($method)) {
            return RedirectResponse::create($endpoint.'&'.http_build_query($payload));
        }

        $sHtml = "<form id='alipay_submit' name='alipay_submit' action='".$endpoint."' method='".$method."'>";
        foreach ($payload as $key => $val) {
            $val = str_replace("'", '&apos;', $val);
            $sHtml .= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        $sHtml .= "<input type='submit' value='ok' style='display:none;'></form>";
        $sHtml .= "<script>document.forms['alipay_submit'].submit();</script>";

        return Response::create($sHtml);
    }

    /**
     * Get method config.
     */
    protected function getMethod(): string
    {
        return 'alipay.trade.page.pay';
    }

    /**
     * Get productCode config.
     */
    protected function getProductCode(): string
    {
        return 'FAST_INSTANT_TRADE_PAY';
    }
}
