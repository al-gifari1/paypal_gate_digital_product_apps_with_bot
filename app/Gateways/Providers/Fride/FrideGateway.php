<?php
declare(strict_types=1);

namespace App\Gateways\Providers\Fride;

use App\Core\Payment\Contracts\PaymentGatewayInterface;
use App\Core\Payment\DTO\ChargeRequest;
use App\Core\Payment\DTO\ChargeResponse;
use App\Core\Payment\DTO\WebhookResult;
use App\Services\SettingsService;

class FrideGateway implements PaymentGatewayInterface
{
    public function getId(): string
    {
        return 'fride';
    }

    public function getName(): string
    {
        return 'Fride.io Pay';
    }

    public function getIcon(): string
    {
        return 'fa-solid fa-bolt';
    }

    public function getDescription(): string
    {
        return 'Fast checkout via Crypto (USDT, TRX, BTC), SBP, and Bank Cards.';
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'RUB', 'UAH'];
    }

    public function isConfigured(): bool
    {
        return !empty($this->getApiKey()) && !empty($this->getMerchantId());
    }

    public function isEnabled(): bool
    {
        return (string)SettingsService::get('gateway_fride_enabled', '0') === '1';
    }

    public function getApiKey(): string
    {
        return (string)SettingsService::get('fride_api_key', '');
    }

    public function getMerchantId(): string
    {
        return (string)SettingsService::get('fride_merchant_id', '');
    }

    public function getWebhookSecret(): string
    {
        return (string)SettingsService::get('fride_webhook_secret', '');
    }

    public function createCharge(ChargeRequest $request): ChargeResponse
    {
        try {
            $client = new FrideClient($this->getApiKey());

            $payload = [
                'merchant_id' => $this->getMerchantId(),
                'order_id' => $request->orderNumber,
                'amount' => (float)number_format($request->amount, 2, '.', ''),
                'currency' => strtoupper($request->currency),
                'comment' => "Purchase: {$request->productName} (#{$request->orderNumber})",
                'email' => $request->buyerEmail,
                'expire' => 360,
                'custom_fields' => [
                    'order_id' => (string)$request->orderId,
                    'user_id' => (string)($request->telegramUserId ?: '0'),
                ],
            ];

            if (!empty($request->telegramUserId) && is_numeric($request->telegramUserId)) {
                $payload['telegram_id_client'] = (int)$request->telegramUserId;
            }

            $response = $client->createInvoice($payload);

            return new ChargeResponse(
                success: true,
                gatewayOrderId: (string)($response['id'] ?? ''),
                redirectUrl: (string)($response['url'] ?? ''),
                rawResponse: $response
            );
        } catch (\Throwable $e) {
            return new ChargeResponse(
                success: false,
                gatewayOrderId: '',
                redirectUrl: null,
                errorMessage: $e->getMessage()
            );
        }
    }

    public function handleWebhook(array $headers, string $rawPayload): WebhookResult
    {
        $signature = $headers['x-signature'] ?? $headers['X-Signature'] ?? '';
        $secretKey = $this->getWebhookSecret();

        if (!empty($secretKey)) {
            $isValid = WebhookValidator::verify($rawPayload, (string)$signature, $secretKey);
            if (!$isValid) {
                return new WebhookResult(
                    isValid: false,
                    gatewayOrderId: '',
                    errorMessage: 'Fride HMAC-SHA256 signature verification failed.'
                );
            }
        }

        $data = json_decode($rawPayload, true) ?: [];
        $status = strtolower((string)($data['status'] ?? ''));

        // Both 'paid' and 'hold' mean successful payment in Fride
        $isSuccessful = in_array($status, ['paid', 'hold', 'success'], true);

        $invoiceId = (string)($data['invoice_id'] ?? '');
        $orderNumber = (string)($data['order_id'] ?? '');
        $amount = (float)($data['amount'] ?? 0.0);
        $currency = (string)($data['currency'] ?? 'USD');
        $customOrderId = $data['custom_fields']['order_id'] ?? null;

        return new WebhookResult(
            isValid: true,
            gatewayOrderId: $invoiceId,
            captureId: $invoiceId,
            orderNumber: $orderNumber,
            orderId: is_numeric($customOrderId) ? (int)$customOrderId : null,
            amount: $amount,
            currency: $currency,
            payerEmail: null,
            status: $isSuccessful ? 'COMPLETED' : strtoupper($status),
            rawPayload: $data
        );
    }
}
