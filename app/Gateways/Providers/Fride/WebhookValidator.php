<?php
declare(strict_types=1);

namespace App\Gateways\Providers\Fride;

use App\Core\Payment\Security\ZeroTrustVerifier;

class WebhookValidator
{
    /**
     * Official Fride IP Addresses for additional zero-trust verification
     */
    public const OFFICIAL_IPS = [
        '37.27.193.34',
        '159.69.50.37',
    ];

    /**
     * Verify incoming X-Signature with HMAC-SHA256
     */
    public static function verify(string $rawBody, string $headerSignature, string $secretKey): bool
    {
        if (empty($headerSignature) || empty($secretKey) || empty($rawBody)) {
            return false;
        }

        return ZeroTrustVerifier::verifyFrideHmac($rawBody, $headerSignature, $secretKey);
    }
}
