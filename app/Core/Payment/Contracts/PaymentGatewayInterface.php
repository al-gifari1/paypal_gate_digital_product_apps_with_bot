<?php
declare(strict_types=1);

namespace App\Core\Payment\Contracts;

use App\Core\Payment\DTO\ChargeRequest;
use App\Core\Payment\DTO\ChargeResponse;
use App\Core\Payment\DTO\WebhookResult;

interface PaymentGatewayInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getIcon(): string;
    public function getDescription(): string;
    public function getSupportedCurrencies(): array;
    public function isConfigured(): bool;
    public function isEnabled(): bool;
    public function createCharge(ChargeRequest $request): ChargeResponse;
    public function handleWebhook(array $headers, string $rawPayload): WebhookResult;
}
