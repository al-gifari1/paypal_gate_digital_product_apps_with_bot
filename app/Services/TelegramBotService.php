<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpClient;
use App\Database\Database;
use PDO;

class TelegramBotService
{
    private static function apiCall(string $method, array $payload = []): array
    {
        $token = SettingsService::getTelegramBotToken();
        if (empty($token)) {
            return ['ok' => false, 'description' => 'Telegram Bot Token not configured'];
        }

        $baseEndpoint = rtrim(SettingsService::get('telegram_api_endpoint', 'https://api.telegram.org'), '/');
        $url = "$baseEndpoint/bot$token/$method";
        $httpTimeout = isset($payload['timeout']) ? ((int)$payload['timeout'] + 10) : 15;
        try {
            $response = HttpClient::request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $payload,
                'timeout' => $httpTimeout,
            ]);

            return $response['json'] ?? ['ok' => false, 'status' => $response['status']];
        } catch (\Throwable $e) {
            error_log("Telegram API Error ({$method}): " . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    public static function getMe(): array
    {
        return self::apiCall('getMe');
    }

    public static function setWebhook(string $url): array
    {
        return self::apiCall('setWebhook', ['url' => $url]);
    }

    public static function deleteWebhook(): array
    {
        return self::apiCall('deleteWebhook');
    }

    public static function getUpdates(int $offset = 0, int $limit = 10, int $timeout = 20): array
    {
        return self::apiCall('getUpdates', [
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => $timeout,
        ]);
    }

    public static function sendMessage(int|string $chatId, string $text, array $replyMarkup = []): array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ];

        if (!empty($replyMarkup)) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $res = self::apiCall('sendMessage', $payload);
        if (empty($res['ok'])) {
            echo "❌ [sendMessage ERROR to {$chatId}]: " . json_encode($res, JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "✅ [sendMessage SUCCESS to {$chatId}]\n";
        }

        return $res;
    }

    /**
     * Process incoming update (from webhook or CLI poller)
     */
    public static function processUpdate(array $update): void
    {
        if (isset($update['message'])) {
            self::handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            self::handleCallbackQuery($update['callback_query']);
        }
    }

    private static function handleMessage(array $msg): void
    {
        $chatId = $msg['chat']['id'] ?? null;
        $text = trim($msg['text'] ?? '');
        $userId = (string)($msg['from']['id'] ?? $chatId);
        $firstName = $msg['from']['first_name'] ?? 'User';

        if (!$chatId || empty($text)) {
            return;
        }

        $parts = explode(' ', $text);
        $command = strtolower($parts[0]);

        switch ($command) {
            case '/start':
                self::sendWelcomeMessage($chatId, $firstName, $userId);
                break;

            case '/shop':
            case '/products':
                self::sendShopCatalog($chatId, $userId);
                break;

            case '/order':
                $orderNumber = $parts[1] ?? '';
                self::handleOrderLookup($chatId, $userId, $orderNumber);
                break;

            case '/deposit':
            case '/payment':
                self::sendPaymentInstructions($chatId);
                break;

            case '/help':
                self::sendHelpMessage($chatId);
                break;

            default:
                if (str_starts_with($command, '/')) {
                    self::sendMessage($chatId, "Unknown command. Type /help to view all available commands.");
                }
                break;
        }
    }

    private static function handleCallbackQuery(array $cq): void
    {
        $chatId = $cq['message']['chat']['id'] ?? null;
        $data = $cq['data'] ?? '';
        $userId = (string)($cq['from']['id'] ?? $chatId);

        if (!$chatId) {
            return;
        }

        if (str_starts_with($data, 'prod_')) {
            $prodId = (int)substr($data, 5);
            self::sendProductDetail($chatId, $prodId, $userId);
        } elseif ($data === 'nav_shop') {
            self::sendShopCatalog($chatId, $userId);
        } elseif ($data === 'nav_payment') {
            self::sendPaymentInstructions($chatId);
        }
    }

    private static function sendWelcomeMessage(int $chatId, string $firstName, string $userId): void
    {
        $appUrl = SettingsService::getAppUrl();
        $siteTitle = SettingsService::get('site_title', 'Digital Vault');

        $text = "<b>👋 Welcome to {$siteTitle}, {$firstName}!</b>\n\n"
            . "We provide premium digital goods with instant automated PayPal delivery.\n\n"
            . "<b>⚡ Available Commands:</b>\n"
            . "🛒 /shop — Browse digital products & cards\n"
            . "📦 /order &lt;order_id&gt; — Track your order & delivery\n"
            . "💳 /deposit or /payment — Payment information\n"
            . "❓ /help — Assistance & guide\n\n"
            . "You can also launch our full WebApp catalog below!";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛍️ Browse Products', 'callback_data' => 'nav_shop'],
                    ['text' => '💳 Payment Info', 'callback_data' => 'nav_payment'],
                ],
            ],
        ];

        if (str_starts_with($appUrl, 'https://')) {
            array_unshift($keyboard['inline_keyboard'], [
                ['text' => '🚀 Open Web Storefront', 'web_app' => ['url' => $appUrl]],
            ]);
        }

        self::sendMessage($chatId, $text, $keyboard);
    }

    private static function sendShopCatalog(int $chatId, string $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC LIMIT 10');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products)) {
            self::sendMessage($chatId, "⚠️ No products are currently listed in the store. Please check back soon!");
            return;
        }

        $text = "<b>🛍️ Digital Product Catalog:</b>\n\n";
        $buttons = [];

        foreach ($products as $p) {
            $price = number_format((float)$p['price'], 2);
            $typeIcon = $p['product_type'] === 'card_license' ? '🔑' : '💾';
            $text .= "{$typeIcon} <b>" . htmlspecialchars($p['name']) . "</b>\n";
            $text .= "💰 Price: \${$price} {$p['currency']}\n";
            $text .= "ℹ️ " . htmlspecialchars(substr($p['description'] ?? 'Instant digital delivery', 0, 80)) . "...\n\n";

            $buttons[] = [
                ['text' => "View {$p['name']} (\${$price})", 'callback_data' => "prod_{$p['id']}"]
            ];
        }

        $keyboard = ['inline_keyboard' => $buttons];
        self::sendMessage($chatId, $text, $keyboard);
    }

    private static function sendProductDetail(int $chatId, int $productId, string $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([$productId]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            self::sendMessage($chatId, "❌ Product not found or no longer available.");
            return;
        }

        $appUrl = SettingsService::getAppUrl();
        $checkoutUrl = "$appUrl/checkout/{$prod['slug']}?tg_user_id=$userId";
        $price = number_format((float)$prod['price'], 2);
        $typeLabel = $prod['product_type'] === 'card_license' ? 'Digital License / Card PIN' : 'Downloadable File';

        $text = "<b>📦 " . htmlspecialchars($prod['name']) . "</b>\n\n"
            . "<b>Category:</b> $typeLabel\n"
            . "<b>Price:</b> \${$price} {$prod['currency']}\n\n"
            . "<b>Description:</b>\n" . htmlspecialchars($prod['description'] ?? 'No description.') . "\n\n"
            . "⚡ <i>Instant automated delivery to this chat immediately after payment!</i>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💳 Buy Now (PayPal / Crypto / Cards)', 'url' => $checkoutUrl],
                ],
                [
                    ['text' => '⬅️ Back to Shop', 'callback_data' => 'nav_shop'],
                ],
            ],
        ];

        self::sendMessage($chatId, $text, $keyboard);
    }

    private static function handleOrderLookup(int $chatId, string $userId, string $orderNumber): void
    {
        if (empty($orderNumber)) {
            self::sendMessage($chatId, "ℹ️ Please provide an Order Number.\nExample: <code>/order ORD-123456</code>");
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT o.*, p.name as product_name, p.product_type
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.order_number = ?
        ');
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            self::sendMessage($chatId, "❌ Order <code>#" . htmlspecialchars($orderNumber) . "</code> not found. Please verify the order number.");
            return;
        }

        $statusEmoji = match ($order['payment_status']) {
            'completed' => '✅ Completed',
            'pending' => '⏳ Pending Payment',
            'failed' => '❌ Failed',
            default => '🔄 ' . ucfirst($order['payment_status']),
        };

        $text = "<b>📦 Order Status Report</b>\n\n"
            . "<b>Order Number:</b> <code>#{$order['order_number']}</code>\n"
            . "<b>Product:</b> " . htmlspecialchars($order['product_name']) . "\n"
            . "<b>Amount:</b> \${$order['amount']} {$order['currency']}\n"
            . "<b>Payment Status:</b> $statusEmoji\n"
            . "<b>Delivery:</b> " . ucfirst($order['delivery_status']) . "\n\n";

        if ($order['payment_status'] === 'completed' && !empty($order['delivered_content'])) {
            $text .= "<b>🎁 Delivered Items:</b>\n<pre>" . htmlspecialchars($order['delivered_content']) . "</pre>";
        } elseif ($order['payment_status'] === 'pending') {
            $text .= "<i>⚠️ Payment is not yet completed. Please complete PayPal checkout to receive your items.</i>";
        }

        self::sendMessage($chatId, $text);
    }

    private static function sendPaymentInstructions(int $chatId): void
    {
        $appUrl = SettingsService::getAppUrl();
        $currency = SettingsService::getCurrency();

        $text = "<b>💳 Secure Payment & Instant Delivery:</b>\n\n"
            . "1. We accept multiple payment options including <b>PayPal</b> and <b>Crypto (USDT, TRX, Cards)</b> ($currency).\n"
            . "2. Every purchase is 100% automated — your digital item or download token is generated instantly once transaction confirms.\n"
            . "3. You will receive your files right here in Telegram and on your Thank You screen.\n\n"
            . "Tap below to visit the store and checkout:";

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🛍️ Visit Store Catalog', 'url' => $appUrl]],
            ],
        ];

        self::sendMessage($chatId, $text, $keyboard);
    }

    private static function sendHelpMessage(int $chatId): void
    {
        $text = "<b>🆘 Support & Command List</b>\n\n"
            . "/shop — Browse all available digital products\n"
            . "/order &lt;order_id&gt; — Check your order status\n"
            . "/payment — How to pay with PayPal\n"
            . "/start — Main menu and WebApp link\n\n"
            . "Need human assistance? Contact our store support.";

        self::sendMessage($chatId, $text);
    }
}
