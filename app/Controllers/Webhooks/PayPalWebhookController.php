<?php
declare(strict_types=1);

namespace App\Controllers\Webhooks;

use App\Core\View;
use App\Database\Database;
use App\Services\DeliveryService;
use App\Services\PayPalService;
use PDO;

class PayPalWebhookController
{
    public function handle(): void
    {
        $rawBody = (string)file_get_contents('php://input');
        if (empty($rawBody)) {
            View::json(['error' => 'empty body'], 400);
            return;
        }

        // Collect all HTTP headers
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($k, 5)));
                $headers[$headerName] = $v;
            }
        }

        // Verify webhook signature
        $isValid = PayPalService::verifyWebhookSignature($headers, $rawBody);
        if (!$isValid) {
            error_log('PayPal Webhook signature verification failed.');
            View::json(['error' => 'invalid signature'], 400);
            return;
        }

        $event = json_decode($rawBody, true);
        $eventType = $event['event_type'] ?? '';
        $resource = $event['resource'] ?? [];

        $pdo = Database::getConnection();

        // Handle Capture Completed
        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $captureId = $resource['id'] ?? '';
            $customId = $resource['custom_id'] ?? '';
            $amount = (float)($resource['amount']['value'] ?? 0.00);
            $currency = $resource['amount']['currency_code'] ?? 'USD';
            $payerEmail = $resource['payer']['email_address'] ?? '';

            // Find order
            $order = null;
            if (!empty($customId) && is_numeric($customId)) {
                $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
                $stmt->execute([(int)$customId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($order && $order['payment_status'] !== 'completed') {
                // Log transaction
                $transStmt = $pdo->prepare('
                    INSERT INTO transactions (order_id, paypal_order_id, paypal_capture_id, payer_email, amount, currency, status, raw_payload)
                    VALUES (?, ?, ?, ?, ?, ?, "COMPLETED", ?)
                ');
                $transStmt->execute([
                    $order['id'],
                    $order['paypal_order_id'],
                    $captureId,
                    $payerEmail ?: $order['buyer_email'],
                    $amount,
                    $currency,
                    $rawBody,
                ]);

                // Update order and fulfill
                $upd = $pdo->prepare('UPDATE orders SET paypal_capture_id = ? WHERE id = ?');
                $upd->execute([$captureId, $order['id']]);

                DeliveryService::fulfillOrder((int)$order['id']);
            }
        }

        View::json(['received' => true]);
    }
}
