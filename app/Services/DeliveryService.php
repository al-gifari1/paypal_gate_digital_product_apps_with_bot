<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Security;
use App\Database\Database;
use PDO;
use RuntimeException;

class DeliveryService
{
    /**
     * Fulfill Order after verified payment
     */
    public static function fulfillOrder(int $orderId): array
    {
        $pdo = Database::getConnection();

        // Fetch Order & Product
        $stmt = $pdo->prepare('
            SELECT o.*, p.name as product_name, p.product_type, p.file_path, p.file_name
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.id = ?
        ');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new RuntimeException("Order #$orderId not found for fulfillment.");
        }

        $deliveredContent = '';
        $downloadUrl = null;

        if ($order['product_type'] === 'card_license') {
            // Find available card/license key
            $cardStmt = $pdo->prepare('
                SELECT id, card_data FROM cards_pool
                WHERE product_id = ? AND status = "available"
                ORDER BY id ASC LIMIT 1
            ');
            $cardStmt->execute([$order['product_id']]);
            $card = $cardStmt->fetch(PDO::FETCH_ASSOC);

            if ($card) {
                // Assign card
                $assign = $pdo->prepare('
                    UPDATE cards_pool
                    SET status = "assigned", assigned_order_id = ?, assigned_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ');
                $assign->execute([$orderId, $card['id']]);
                $deliveredContent = $card['card_data'];
            } else {
                $deliveredContent = "[OUT OF STOCK NOTICE] All license keys are currently assigned. Support will contact you shortly.";
            }

        } else {
            // Downloadable file
            $tokenHash = Security::generateToken(32);
            $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 hours

            $tokStmt = $pdo->prepare('
                INSERT INTO download_tokens (order_id, product_id, token_hash, expires_at, max_downloads)
                VALUES (?, ?, ?, ?, 3)
            ');
            $tokStmt->execute([$orderId, $order['product_id'], $tokenHash, $expiresAt]);

            $downloadUrl = SettingsService::getAppUrl() . '/download/' . $tokenHash;
            $deliveredContent = "Secure Download URL: " . $downloadUrl;
        }

        // Update order status
        $update = $pdo->prepare('
            UPDATE orders
            SET payment_status = "completed", delivery_status = "delivered", delivered_content = ?
            WHERE id = ?
        ');
        $update->execute([$deliveredContent, $orderId]);

        // Send Telegram notifications
        self::sendDeliveryNotifications($order, $deliveredContent, $downloadUrl);

        return [
            'order_id' => $orderId,
            'delivered_content' => $deliveredContent,
            'download_url' => $downloadUrl,
        ];
    }

    private static function sendDeliveryNotifications(array $order, string $deliveredContent, ?string $downloadUrl): void
    {
        // 1. Admin Alert Notification
        $adminChatId = SettingsService::getTelegramAdminChatId();
        if (!empty($adminChatId)) {
            $adminMsg = "<b>🔔 [NEW SALE CONFIRMED]</b>\n"
                . "<b>Order:</b> <code>#{$order['order_number']}</code>\n"
                . "<b>Product:</b> " . htmlspecialchars($order['product_name']) . "\n"
                . "<b>Amount:</b> $" . number_format((float)$order['amount'], 2) . " " . htmlspecialchars($order['currency']) . "\n"
                . "<b>Buyer:</b> " . htmlspecialchars($order['buyer_email']) . "\n"
                . "<b>PayPal Capture:</b> <code>" . ($order['paypal_capture_id'] ?: 'N/A') . "</code>\n"
                . "<b>Date:</b> " . date('Y-m-d H:i:s');

            TelegramBotService::sendMessage((int)$adminChatId, $adminMsg);
        }

        // 2. Customer Direct Delivery (if ordered through Telegram)
        if (!empty($order['telegram_user_id'])) {
            $userMsg = "<b>🎉 Payment Received Successfully!</b>\n\n"
                . "Thank you for purchasing <b>" . htmlspecialchars($order['product_name']) . "</b>!\n"
                . "<b>Order ID:</b> <code>#{$order['order_number']}</code>\n\n";

            if ($downloadUrl !== null) {
                $userMsg .= "📥 <b>Your Download Link:</b>\n" . $downloadUrl . "\n\n<i>⚠️ Note: Link expires in 24 hours.</i>";
            } else {
                $userMsg .= "🔑 <b>Your License / Card Details:</b>\n<pre>" . htmlspecialchars($deliveredContent) . "</pre>";
            }

            TelegramBotService::sendMessage((int)$order['telegram_user_id'], $userMsg);
        }
    }
}
