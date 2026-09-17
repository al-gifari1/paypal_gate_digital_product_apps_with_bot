<?php
declare(strict_types=1);

namespace App\Core\Payment\DTO;

class ChargeResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $gatewayOrderId,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = []
    ) {}
}
