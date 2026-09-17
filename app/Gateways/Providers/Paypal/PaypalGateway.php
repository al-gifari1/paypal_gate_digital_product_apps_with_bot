<?php
declare(strict_types=1);

namespace App\Gateways\Providers\Paypal;

use App\Core\Payment\Contracts\PaymentGatewayInterface;
use App\Core\Payment\DTO\ChargeRequest;
use App\Core\Payment\DTO\ChargeResponse;
use App\Core\Payment\DTO\WebhookResult;
use App\Services\PayPalService;
use App\Services\SettingsService;

class PaypalGateway implements PaymentGatewayInterface
{
    public function getId(): string
    {
        return 'paypal';
    }

    public function getName(): string
    {
        return 'PayPal Business';
    }

    public function getIcon(): string
    {
        return 'fa-brands fa-paypal';
    }

    public function getDescription(): string
    {
        return 'Pay with PayPal balance, Bank Account, or International Debit/Credit Card.';
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'];
    }

    public function isConfigured(): bool
    {
        return !empty(SettingsService::getPaypalClientId()) && !empty(SettingsService::getPaypalClientSecret());
    }

    public function isEnabled(): bool
    {
        return (string)SettingsService::get('gateway_paypal_enabled', '1') === '1';
    }

    public function createCharge(ChargeRequest $request): ChargeResponse
    {
        try {
            $order = PayPalService::createOrder(
                $request->amount,
                $request->currency,
                $request->returnUrl,
                $request->cancelUrl,
                "Purchase: {$request->productName} (Order #{$request->orderNumber})",
                (string)$request->orderId
            );

            return new ChargeResponse(
                success: true,
                gatewayOrderId: (string)($order['id'] ?? ''),
                redirectUrl: (string)($order['approval_url'] ?? ''),
                rawResponse: $order
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
        $isValid = PayPalService::verifyWebhookSignature($headers, $rawPayload);
        if (!$isValid) {
            return new WebhookResult(
                isValid: false,
                gatewayOrderId: '',
                errorMessage: 'PayPal Webhook cryptographic verification failed.'
            );
        }

        $event = json_decode($rawPayload, true) ?: [];
        $eventType = $event['event_type'] ?? '';
        $resource = $event['resource'] ?? [];

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $captureId = $resource['id'] ?? '';
            $customId = $resource['custom_id'] ?? '';
            $amount = (float)($resource['amount']['value'] ?? 0.00);
            $currency = (string)($resource['amount']['currency_code'] ?? 'USD');
            $payerEmail = (string)($resource['payer']['email_address'] ?? '');

            return new WebhookResult(
                isValid: true,
                gatewayOrderId: (string)($resource['supplementary_data']['related_ids']['order_id'] ?? $customId),
                captureId: $captureId,
                orderNumber: null,
                orderId: is_numeric($customId) ? (int)$customId : null,
                amount: $amount,
                currency: $currency,
                payerEmail: $payerEmail,
                status: 'COMPLETED',
                rawPayload: $event
            );
        }

        return new WebhookResult(
            isValid: true,
            gatewayOrderId: (string)($resource['id'] ?? ''),
            status: 'IGNORED_EVENT',
            rawPayload: $event
        );
    }
}
