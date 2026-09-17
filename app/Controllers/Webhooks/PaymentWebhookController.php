<?php
declare(strict_types=1);

namespace App\Controllers\Webhooks;

use App\Core\Payment\GatewayManager;
use App\Core\View;
use App\Database\Database;
use App\Services\DeliveryService;
use PDO;

class PaymentWebhookController
{
    public function handle(array $params): void
    {
        $gatewayId = strtolower($params['gateway'] ?? '');
        $gateway = GatewayManager::getInstance()->get($gatewayId);

        if (!$gateway) {
            View::json(['error' => "Gateway provider '{$gatewayId}' not supported"], 404);
            return;
        }

        $rawBody = (string)file_get_contents('php://input');
        if (empty($rawBody)) {
            View::json(['error' => 'Empty webhook body received'], 400);
            return;
        }

        // Collect all HTTP headers normalize
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($k, 5)));
                $headers[$headerName] = $v;
            }
        }

        $result = $gateway->handleWebhook($headers, $rawBody);

        if (!$result->isValid) {
            error_log("Payment Webhook verification failed for [{$gatewayId}]: " . ($result->errorMessage ?? 'unknown'));
            View::json(['error' => $result->errorMessage ?: 'Invalid signature'], 400);
            return;
        }

        if ($result->status !== 'COMPLETED') {
            View::json(['received' => true, 'status' => $result->status]);
            return;
        }

        $pdo = Database::getConnection();
        $order = null;

        // Lookup order by internal ID or Order Number
        if ($result->orderId) {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
            $stmt->execute([$result->orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$order && $result->orderNumber) {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = ?');
            $stmt->execute([$result->orderNumber]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$order && $result->gatewayOrderId) {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE gateway_order_id = ? OR paypal_order_id = ?');
            $stmt->execute([$result->gatewayOrderId, $result->gatewayOrderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($order && $order['payment_status'] !== 'completed') {
            // Record audit ledger transaction
            $transStmt = $pdo->prepare('
                INSERT INTO transactions (order_id, gateway, gateway_order_id, gateway_capture_id, paypal_order_id, paypal_capture_id, payer_email, amount, currency, status, raw_payload)
                VALUES (?, ?, ?, ?, ?, ?, "COMPLETED", ?)
            ');
            $transStmt->execute([
                $order['id'],
                $gatewayId,
                $result->gatewayOrderId,
                $result->captureId,
                $gatewayId === 'paypal' ? $result->gatewayOrderId : null,
                $gatewayId === 'paypal' ? $result->captureId : null,
                $result->payerEmail ?: $order['buyer_email'],
                $result->amount ?: $order['amount'],
                $result->currency ?: $order['currency'],
                $rawBody,
            ]);

            // Update order record
            $upd = $pdo->prepare('
                UPDATE orders 
                SET gateway = ?, gateway_order_id = ?, gateway_capture_id = ?, payment_status = "completed"
                WHERE id = ?
            ');
            $upd->execute([
                $gatewayId,
                $result->gatewayOrderId,
                $result->captureId,
                $order['id']
            ]);

            // Auto-fulfill delivery (send license/file + telegram notification)
            DeliveryService::fulfillOrder((int)$order['id']);
        }

        View::json(['received' => true, 'fulfilled' => true]);
    }
}
