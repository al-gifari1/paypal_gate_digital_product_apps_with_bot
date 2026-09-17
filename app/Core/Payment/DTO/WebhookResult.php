<?php
declare(strict_types=1);

namespace App\Core\Payment\DTO;

class WebhookResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly string $gatewayOrderId,
        public readonly ?string $captureId = null,
        public readonly ?string $orderNumber = null,
        public readonly ?int $orderId = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $payerEmail = null,
        public readonly string $status = 'COMPLETED',
        public readonly ?string $errorMessage = null,
        public readonly array $rawPayload = []
    ) {}
}
