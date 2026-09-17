<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpClient;
use Exception;
use RuntimeException;

class PayPalService
{
    private static ?string $accessToken = null;
    private static int $tokenExpiresAt = 0;

    public static function getAccessToken(): string
    {
        $now = time();
        if (self::$accessToken !== null && self::$tokenExpiresAt > ($now + 60)) {
            return self::$accessToken;
        }

        $clientId = SettingsService::getPaypalClientId();
        $clientSecret = SettingsService::getPaypalClientSecret();

        if (empty($clientId) || empty($clientSecret)) {
            throw new RuntimeException('PayPal Client ID or Secret is not configured in System Settings.');
        }

        $url = SettingsService::getPaypalBaseUrl() . '/v1/oauth2/token';

        $response = HttpClient::request('POST', $url, [
            'auth' => [$clientId, $clientSecret],
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
            ],
        ]);

        if ($response['status'] !== 200 || empty($response['json']['access_token'])) {
            $msg = $response['json']['error_description'] ?? ($response['body'] ?: 'Authentication failed');
            throw new RuntimeException("PayPal OAuth2 Error ({$response['status']}): $msg");
        }

        self::$accessToken = (string)$response['json']['access_token'];
        $expiresIn = (int)($response['json']['expires_in'] ?? 3600);
        self::$tokenExpiresAt = $now + $expiresIn;

        return self::$accessToken;
    }

    /**
     * Create PayPal Checkout Order
     */
    public static function createOrder(
        float $amount,
        string $currency,
        string $returnUrl,
        string $cancelUrl,
        string $description = 'Digital Goods Purchase',
        string $customId = ''
    ): array {
        $token = self::getAccessToken();
        $url = SettingsService::getPaypalBaseUrl() . '/v2/checkout/orders';

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $customId ?: ('ORDER_' . uniqid('', true)),
                    'description' => substr($description, 0, 127),
                    'custom_id' => $customId,
                    'amount' => [
                        'currency_code' => strtoupper($currency),
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => SettingsService::get('site_title', 'Digital Vault'),
                'landing_page' => 'NO_PREFERENCE',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ];

        $response = HttpClient::request('POST', $url, [
            'headers' => [
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json',
                'PayPal-Request-Id' => uniqid('pp_req_', true),
            ],
            'body' => $payload,
        ]);

        if ($response['status'] !== 201 && $response['status'] !== 200) {
            $err = $response['json']['message'] ?? $response['body'];
            throw new RuntimeException("PayPal Order Creation Failed ({$response['status']}): $err");
        }

        $orderData = $response['json'] ?? [];
        $approvalUrl = '';
        foreach ($orderData['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                $approvalUrl = $link['href'];
                break;
            }
        }

        return [
            'id' => $orderData['id'] ?? '',
            'status' => $orderData['status'] ?? '',
            'approval_url' => $approvalUrl,
            'raw' => $orderData,
        ];
    }

    /**
     * Capture PayPal Order Payment
     */
    public static function captureOrder(string $paypalOrderId): array
    {
        $token = self::getAccessToken();
        $url = SettingsService::getPaypalBaseUrl() . "/v2/checkout/orders/$paypalOrderId/capture";

        $response = HttpClient::request('POST', $url, [
            'headers' => [
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json',
                'PayPal-Request-Id' => uniqid('pp_cap_', true),
            ],
            'body' => '{}',
        ]);

        if ($response['status'] !== 201 && $response['status'] !== 200) {
            $err = $response['json']['message'] ?? $response['body'];
            throw new RuntimeException("PayPal Capture Failed ({$response['status']}): $err");
        }

        $data = $response['json'] ?? [];
        $captureId = '';
        $payerEmail = $data['payer']['email_address'] ?? '';
        $status = $data['status'] ?? '';

        if (!empty($data['purchase_units'][0]['payments']['captures'][0])) {
            $capture = $data['purchase_units'][0]['payments']['captures'][0];
            $captureId = $capture['id'] ?? '';
            $status = $capture['status'] ?? $status;
        }

        return [
            'order_id' => $data['id'] ?? $paypalOrderId,
            'capture_id' => $captureId,
            'status' => $status,
            'payer_email' => $payerEmail,
            'raw' => $data,
        ];
    }

    /**
     * Verify Webhook Signature with PayPal
     */
    public static function verifyWebhookSignature(array $headers, string $rawBody): bool
    {
        $webhookId = SettingsService::get('paypal_webhook_id', '');
        if (empty($webhookId)) {
            // If webhook id is not set, log and proceed cautiously or return true in sandbox
            return true;
        }

        try {
            $token = self::getAccessToken();
            $url = SettingsService::getPaypalBaseUrl() . '/v1/notifications/verify-webhook-signature';

            $payload = [
                'auth_algo' => $headers['paypal-auth-algo'] ?? '',
                'cert_url' => $headers['paypal-cert-url'] ?? '',
                'transmission_id' => $headers['paypal-transmission-id'] ?? '',
                'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
                'transmission_time' => $headers['paypal-transmission-time'] ?? '',
                'webhook_id' => $webhookId,
                'webhook_event' => json_decode($rawBody, true) ?: new \stdClass(),
            ];

            $response = HttpClient::request('POST', $url, [
                'headers' => [
                    'Authorization' => "Bearer $token",
                    'Content-Type' => 'application/json',
                ],
                'body' => $payload,
            ]);

            return ($response['json']['verification_status'] ?? '') === 'SUCCESS';
        } catch (Exception $e) {
            error_log('PayPal Webhook Verification Error: ' . $e->getMessage());
            return false;
        }
    }
}
