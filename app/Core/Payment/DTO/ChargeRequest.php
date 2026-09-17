<?php
declare(strict_types=1);

namespace App\Core\Payment\DTO;

class ChargeRequest
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $buyerEmail,
        public readonly string $buyerName,
        public readonly string $productName,
        public readonly string $returnUrl,
        public readonly string $cancelUrl,
        public readonly ?string $telegramUserId = null,
        public readonly array $customFields = []
    ) {}
}
