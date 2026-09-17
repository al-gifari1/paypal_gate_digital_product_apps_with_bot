<?php
declare(strict_types=1);

namespace App\Gateways\Providers\Fride;

use App\Core\HttpClient;
use RuntimeException;

class FrideClient
{
    private string $apiKey;
    private string $baseUrl = 'https://api.fride.io';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function createInvoice(array $params): array
    {
        $url = "{$this->baseUrl}/invoice/create";

        $response = HttpClient::request('POST', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Api-Key' => $this->apiKey,
            ],
            'body' => $params,
        ]);

        if ($response['status'] !== 200 || empty($response['json']['url'])) {
            $err = $response['json']['error'] ?? ($response['body'] ?: 'Failed to create Fride invoice');
            throw new RuntimeException("Fride API Error ({$response['status']}): $err");
        }

        return $response['json'];
    }

    public function getInvoiceInfo(string $merchantId, ?string $invoiceId = null, ?string $orderId = null): array
    {
        $queryParams = http_build_query([
            'merchant_id' => $merchantId,
            'invoice_id' => $invoiceId,
            'order_id' => $orderId,
        ]);

        $url = "{$this->baseUrl}/invoice/getInfo?$queryParams";

        $response = HttpClient::request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'X-Api-Key' => $this->apiKey,
            ],
        ]);

        if ($response['status'] !== 200) {
            $err = $response['json']['error'] ?? 'Failed to get invoice info';
            throw new RuntimeException("Fride API Error: $err");
        }

        return $response['json'] ?? [];
    }

    public function getBalance(): array
    {
        $url = "{$this->baseUrl}/user/getBalance";
        $response = HttpClient::request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'X-Api-Key' => $this->apiKey,
            ],
        ]);
        return $response['json'] ?? [];
    }
}
