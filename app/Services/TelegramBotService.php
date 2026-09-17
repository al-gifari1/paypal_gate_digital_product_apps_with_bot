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

    public static function getMainMenuKeyboard(): array
    {
        $appUrl = SettingsService::getAppUrl();
        $isHttps = str_starts_with($appUrl, 'https://');

        $webAppBtn = $isHttps 
            ? ['text' => '🚀 Open Mini App', 'web_app' => ['url' => $appUrl]]
            : ['text' => '🚀 Open Web Store'];

        return [
            'keyboard' => [
                [
                    ['text' => '🛍️ Browse Products'],
                    $webAppBtn,
                ],
                [
                    ['text' => '📦 My Orders'],
                    ['text' => '💳 Payment Info'],
                ],
                [
                    ['text' => '💬 Contact Support'],
                    ['text' => '❓ Help & Guide'],
                ],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
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

        // Support both slash commands and persistent reply button clicks
        if ($command === '/start' || $text === '🏠 Home') {
            self::sendWelcomeMessage($chatId, $firstName, $userId);
        } elseif ($command === '/shop' || $command === '/products' || $text === '🛍️ Browse Products') {
            self::sendShopCatalog($chatId, $userId);
        } elseif ($command === '/orders' || $command === '/myorders' || $text === '📦 My Orders') {
            self::sendMyOrders($chatId, $userId);
        } elseif ($command === '/order') {
            $orderNumber = $parts[1] ?? '';
            self::handleOrderLookup($chatId, $userId, $orderNumber);
        } elseif ($command === '/deposit' || $command === '/payment' || $text === '💳 Payment Info') {
            self::sendPaymentInstructions($chatId);
        } elseif ($command === '/support' || $command === '/contact' || $text === '💬 Contact Support') {
            self::sendSupportMessage($chatId);
        } elseif ($command === '/help' || $text === '❓ Help & Guide') {
            self::sendHelpMessage($chatId);
        } else {
            if (str_starts_with($command, '/')) {
                self::sendMessage($chatId, "Unknown command. Use the menu buttons below or type /help.");
            }
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
        } elseif ($data === 'nav_orders') {
            self::sendMyOrders($chatId, $userId);
        } elseif ($data === 'nav_payment') {
            self::sendPaymentInstructions($chatId);
        } elseif ($data === 'nav_support') {
            self::sendSupportMessage($chatId);
        }
    }

    private static function sendWelcomeMessage(int $chatId, string $firstName, string $userId): void
    {
        $appUrl = SettingsService::getAppUrl();
        $siteTitle = SettingsService::get('site_title', 'Abi Store');

        $text = "<b>👋 Welcome to {$siteTitle}, {$firstName}!</b>\n\n"
            . "We provide high-grade digital products, software licenses, and automated tools with instant cryptographic delivery.\n\n"
            . "<b>✨ Quick Actions:</b>\n"
            . "• Tap <b>🛍️ Browse Products</b> to inspect our live inventory.\n"
            . "• Tap <b>🚀 Open Mini App</b> for the full visual storefront.\n"
            . "• Tap <b>📦 My Orders</b> to retrieve your purchased keys anytime.\n"
            . "• Tap <b>💬 Contact Support</b> for instant customer assistance.\n\n"
            . "<i>All purchases are backed by 256-bit automated fulfillment.</i>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛍️ Browse Products', 'callback_data' => 'nav_shop'],
                    ['text' => '📦 My Orders', 'callback_data' => 'nav_orders'],
                ],
                [
                    ['text' => '💳 Payment Info', 'callback_data' => 'nav_payment'],
                    ['text' => '💬 Support', 'callback_data' => 'nav_support'],
                ],
            ],
        ];

        if (str_starts_with($appUrl, 'https://')) {
            array_unshift($keyboard['inline_keyboard'], [
                ['text' => '🚀 Launch Full WebApp', 'web_app' => ['url' => $appUrl]],
            ]);
        }

        // Send persistent keyboard along with welcome message
        $replyMarkup = [
            'keyboard' => self::getMainMenuKeyboard()['keyboard'],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];
        self::sendMessage($chatId, $text, $keyboard);
        // Also ensure bottom keyboard is refreshed
        self::sendMessage($chatId, "👇 Use the quick menu below at any time:", $replyMarkup);
    }

    private static function sendShopCatalog(int $chatId, string $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('
            SELECT p.*,
                (SELECT COUNT(*) FROM cards_pool c WHERE c.product_id = p.id AND c.status = "available") as available_cards
            FROM products p
            WHERE p.is_active = 1 
            ORDER BY p.id DESC 
            LIMIT 10
        ');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products)) {
            self::sendMessage($chatId, "⚠️ No products are currently listed in the store. Please check back soon!");
            return;
        }

        $text = "<b>🛍️ Available Products in Store:</b>\n\n";
        $buttons = [];

        foreach ($products as $p) {
            $price = number_format((float)$p['price'], 2);
            $typeIcon = $p['product_type'] === 'card_license' ? '🔑' : '💾';
            
            $stockText = $p['product_type'] === 'card_license'
                ? " (" . ((int)$p['available_cards'] > 0 ? "🟢 {$p['available_cards']} in stock" : "🔴 Sold Out") . ")"
                : " (🟢 Instant Download)";

            $text .= "{$typeIcon} <b>" . htmlspecialchars($p['name']) . "</b>\n";
            $text .= "💰 <b>Price:</b> \${$price} {$p['currency']}{$stockText}\n";
            $text .= "ℹ️ " . htmlspecialchars(substr($p['description'] ?? 'Instant digital delivery', 0, 75)) . "...\n\n";

            $buttons[] = [
                ['text' => "{$typeIcon} View {$p['name']} (\${$price})", 'callback_data' => "prod_{$p['id']}"]
            ];
        }

        $appUrl = SettingsService::getAppUrl();
        if (str_starts_with($appUrl, 'https://')) {
            $buttons[] = [
                ['text' => '🚀 Open Mini App Catalog', 'web_app' => ['url' => $appUrl]]
            ];
        }

        $keyboard = ['inline_keyboard' => $buttons];
        self::sendMessage($chatId, $text, $keyboard);
    }

    private static function sendProductDetail(int $chatId, int $productId, string $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT p.*,
                (SELECT COUNT(*) FROM cards_pool c WHERE c.product_id = p.id AND c.status = "available") as available_cards
            FROM products p 
            WHERE p.id = ? AND p.is_active = 1
        ');
        $stmt->execute([$productId]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            self::sendMessage($chatId, "❌ Product not found or no longer available.");
            return;
        }

        $appUrl = SettingsService::getAppUrl();
        $checkoutUrl = "$appUrl/checkout/{$prod['slug']}?tg_user_id=$userId";
        $price = number_format((float)$prod['price'], 2);
        $typeLabel = $prod['product_type'] === 'card_license' ? 'Digital License / Serial Key' : 'Downloadable Source Code / File';
        
        $stockStatus = $prod['product_type'] === 'card_license'
            ? ((int)$prod['available_cards'] > 0 ? "🟢 In Stock ({$prod['available_cards']} available)" : "🔴 Currently Out of Stock")
            : "🟢 In Stock (Immediate Delivery)";

        $text = "<b>📦 " . htmlspecialchars($prod['name']) . "</b>\n\n"
            . "<b>Category:</b> $typeLabel\n"
            . "<b>Price:</b> \${$price} {$prod['currency']}\n"
            . "<b>Availability:</b> $stockStatus\n\n"
            . "<b>Description:</b>\n" . htmlspecialchars($prod['description'] ?? 'No description.') . "\n\n"
            . "⚡ <i>Instant automated delivery to this chat immediately upon payment!</i>";

        $isHttps = str_starts_with($appUrl, 'https://');

        $buyBtn = $isHttps
            ? ['text' => '💳 Buy Now (In-App Checkout)', 'web_app' => ['url' => $checkoutUrl]]
            : ['text' => '💳 Buy Now (Browser Checkout)', 'url' => $checkoutUrl];

        $keyboard = [
            'inline_keyboard' => [
                [$buyBtn],
                [
                    ['text' => '⬅️ Back to Products', 'callback_data' => 'nav_shop'],
                    ['text' => '💬 Ask Support', 'callback_data' => 'nav_support'],
                ],
            ],
        ];

        self::sendMessage($chatId, $text, $keyboard);
    }

    private static function sendMyOrders(int $chatId, string $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT o.*, p.name as product_name, p.product_type
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.telegram_user_id = ?
            ORDER BY o.id DESC
            LIMIT 5
        ');
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($orders)) {
            $text = "<b>📦 You haven't made any purchases yet!</b>\n\n"
                . "Browse our catalog to explore premium digital products and keys.\n\n"
                . "If you made a purchase on the web without entering your Telegram ID, you can lookup your order using: <code>/order &lt;ORDER_NUMBER&gt;</code>";

            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🛍️ Explore Shop', 'callback_data' => 'nav_shop']],
                ],
            ];
            self::sendMessage($chatId, $text, $keyboard);
            return;
        }

        $text = "<b>📦 Your Recent Orders & Digital Keys:</b>\n\n";

        foreach ($orders as $ord) {
            $statusEmoji = $ord['payment_status'] === 'completed' ? '✅ Paid & Delivered' : '⏳ Pending';
            $text .= "━━━━━━━━━━━━━━━━━━\n";
            $text .= "🔖 <b>Order:</b> <code>#{$ord['order_number']}</code>\n";
            $text .= "📦 <b>Item:</b> " . htmlspecialchars($ord['product_name']) . "\n";
            $text .= "💰 <b>Amount:</b> \${$ord['amount']} {$ord['currency']} ({$statusEmoji})\n";
            $text .= "📅 <b>Date:</b> " . substr($ord['created_at'], 0, 16) . " UTC\n";

            if ($ord['payment_status'] === 'completed' && !empty($ord['delivered_content'])) {
                $text .= "🔑 <b>Your Key / Content:</b>\n<pre>" . htmlspecialchars($ord['delivered_content']) . "</pre>\n";
            } elseif ($ord['payment_status'] === 'pending') {
                $appUrl = SettingsService::getAppUrl();
                $text .= "👉 <a href=\"{$appUrl}/checkout/complete?order_number={$ord['order_number']}\">Resume Checkout</a>\n";
            }
        }

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛍️ Browse More Products', 'callback_data' => 'nav_shop'],
                ],
            ],
        ];

        self::sendMessage($chatId, $text, $keyboard);
    }

    private static function sendSupportMessage(int $chatId): void
    {
        $siteTitle = SettingsService::get('site_title', 'Abi Store');
        $adminChatId = SettingsService::get('telegram_admin_chat_id', '');

        $text = "<b>💬 {$siteTitle} Customer Support</b>\n\n"
            . "Need assistance with your purchase, a license key, or custom inquiries?\n\n"
            . "• <b>Response Time:</b> Usually within 15-30 minutes.\n"
            . "• <b>Order Inquiry:</b> Please include your <code>#ORD-...</code> order number for immediate resolution.\n\n"
            . "Click the button below to message our official support:";

        $buttons = [];
        if (!empty($adminChatId)) {
            $buttons[] = [
                ['text' => '💬 Message Support Directly', 'url' => "tg://user?id={$adminChatId}"]
            ];
        }
        $buttons[] = [
            ['text' => '🛍️ Back to Store', 'callback_data' => 'nav_shop']
        ];

        self::sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
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
