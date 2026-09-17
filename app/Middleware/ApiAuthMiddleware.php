<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\View;
use App\Services\SettingsService;

class ApiAuthMiddleware
{
    /**
     * Enforce Zero-Trust API Key Authentication.
     * Supports 'X-API-Key: <key>' or 'Authorization: Bearer <key>'.
     */
    public static function authenticate(): bool
    {
        $providedKey = self::extractApiKey();

        if ($providedKey === null || trim($providedKey) === '') {
            View::json([
                'error' => [
                    'code' => 401,
                    'message' => "Missing API credentials. Provide your Master API key via 'X-API-Key: <key>' or 'Authorization: Bearer <key>' header.",
                    'status' => 'UNAUTHENTICATED'
                ]
            ], 401);
            return false;
        }

        $configuredKey = SettingsService::getApiKey();

        // Timing-attack resistant string comparison
        if (!hash_equals($configuredKey, trim($providedKey))) {
            View::json([
                'error' => [
                    'code' => 401,
                    'message' => 'Invalid or revoked API key.',
                    'status' => 'UNAUTHENTICATED'
                ]
            ], 401);
            return false;
        }

        return true;
    }

    private static function extractApiKey(): ?string
    {
        // 1. Direct X-API-Key header
        if (!empty($_SERVER['HTTP_X_API_KEY'])) {
            return (string)$_SERVER['HTTP_X_API_KEY'];
        }

        // 2. Authorization header (Bearer token)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!empty($authHeader) && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // 3. Fallback query parameter for quick development/GET requests
        if (!empty($_GET['api_key'])) {
            return (string)$_GET['api_key'];
        }

        return null;
    }
}
