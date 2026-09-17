<?php
declare(strict_types=1);

namespace App\Core\Payment\Security;

class ZeroTrustVerifier
{
    /**
     * Constant-time string comparison against timing attacks
     */
    public static function hashEquals(string $knownString, string $userString): bool
    {
        return hash_equals($knownString, $userString);
    }

    /**
     * Verify Fride.io sorted JSON payload with HMAC-SHA256
     */
    public static function verifyFrideHmac(string $rawBody, string $headerSignature, string $secretKey): bool
    {
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return false;
        }

        ksort($payload);
        if (isset($payload['custom_fields'])) {
            $payload['custom_fields'] = (object) $payload['custom_fields'];
        }

        $sortedJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $computedSignature = hash_hmac('sha256', (string)$sortedJson, $secretKey);

        return hash_equals($headerSignature, $computedSignature);
    }

    /**
     * Nonce or replay defense checker (timestamp window within allowed drift)
     */
    public static function verifyTimestampWindow(int $timestamp, int $maxDriftSeconds = 300): bool
    {
        return abs(time() - $timestamp) <= $maxDriftSeconds;
    }
}
