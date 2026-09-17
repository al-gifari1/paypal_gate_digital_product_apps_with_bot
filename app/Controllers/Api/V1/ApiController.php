<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\View;
use App\Middleware\ApiAuthMiddleware;

abstract class ApiController
{
    public function __construct()
    {
        // Enforce API Authentication across all API endpoints
        ApiAuthMiddleware::authenticate();
    }

    /**
     * Send standard JSON success envelope
     */
    protected function sendSuccess(mixed $data, int $status = 200, array $meta = []): void
    {
        $response = [
            'status' => 'success',
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        View::json($response, $status);
    }

    /**
     * Send Google AIP-158 compliant JSON error envelope
     */
    protected function sendError(string $message, int $code = 400, string $status = 'INVALID_ARGUMENT', array $details = []): void
    {
        $response = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'status' => $status,
            ]
        ];

        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        View::json($response, $code);
    }

    /**
     * Parse and validate incoming JSON body or fallback to POST variables
     */
    protected function getJsonPayload(): array
    {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return !empty($_POST) ? $_POST : [];
    }
}
