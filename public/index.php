<?php
declare(strict_types=1);

/**
 * Digital Vault & PayPal Gateway Platform v1.0.0
 * Front-Controller Router & Dispatcher
 */

require_once file_exists(__DIR__ . '/config/app.php') ? __DIR__ . '/config/app.php' : dirname(__DIR__) . '/config/app.php';

use App\Core\Router;
use App\Core\Session;

// Controllers
use App\Controllers\Storefront\HomeController;
use App\Controllers\Storefront\ProductController;
use App\Controllers\Storefront\CheckoutController;
use App\Controllers\Storefront\DownloadController;
use App\Controllers\Storefront\PageController;

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ProductController as AdminProductController;
use App\Controllers\Admin\CardManagerController;
use App\Controllers\Admin\OrderController;
use App\Controllers\Admin\TransactionController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\ApiConsoleController;

use App\Controllers\Api\V1\ProductApiController;
use App\Controllers\Api\V1\CardApiController;
use App\Controllers\Api\V1\StatsApiController;
use App\Controllers\Api\V1\SchemaApiController;

use App\Controllers\Webhooks\TelegramWebhookController;
use App\Controllers\Webhooks\PayPalWebhookController;
use App\Controllers\Webhooks\PaymentWebhookController;

Session::start();

$router = new Router();

// ==========================================
// STOREFRONT ROUTES
// ==========================================
$router->get('/', [HomeController::class, 'index']);
$router->get('/product/{slug}', [ProductController::class, 'show']);
$router->get('/checkout/{slug}', [CheckoutController::class, 'show']);
$router->post('/checkout/process/{slug}', [CheckoutController::class, 'process']);
$router->get('/checkout/complete', [CheckoutController::class, 'complete']);
$router->get('/checkout/cancel', [CheckoutController::class, 'cancel']);
$router->get('/order/thank-you/{order_number}', [CheckoutController::class, 'thankYou']);
$router->get('/download/{token}', [DownloadController::class, 'download']);

// Legal & Brand Subpages
$router->get('/about', [PageController::class, 'about']);
$router->get('/contact', [PageController::class, 'contact']);
$router->post('/contact', [PageController::class, 'submitContact']);
$router->get('/privacy', [PageController::class, 'privacy']);
$router->get('/terms', [PageController::class, 'terms']);
$router->get('/refund', [PageController::class, 'refund']);

// ==========================================
// ADMIN PANEL ROUTES
// ==========================================
$router->get('/admin/login', [AuthController::class, 'showLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->get('/admin/logout', [AuthController::class, 'logout']);
$router->get('/admin', [DashboardController::class, 'index']);

// Product Management
$router->get('/admin/products', [AdminProductController::class, 'index']);
$router->get('/admin/products/create', [AdminProductController::class, 'create']);
$router->post('/admin/products/create', [AdminProductController::class, 'store']);
$router->get('/admin/products/toggle/{id}', [AdminProductController::class, 'toggle']);
$router->get('/admin/products/delete/{id}', [AdminProductController::class, 'delete']);

// Cards & License Manager
$router->get('/admin/cards', [CardManagerController::class, 'index']);
$router->post('/admin/cards/import', [CardManagerController::class, 'import']);
$router->get('/admin/cards/delete/{id}', [CardManagerController::class, 'delete']);

// Orders Manager
$router->get('/admin/orders', [OrderController::class, 'index']);
$router->get('/admin/orders/fulfill/{id}', [OrderController::class, 'fulfill']);

// Transactions
$router->get('/admin/transactions', [TransactionController::class, 'index']);

// System Settings
$router->get('/admin/settings', [SettingsController::class, 'index']);
$router->post('/admin/settings/save', [SettingsController::class, 'save']);
$router->get('/admin/settings/test-bot', [SettingsController::class, 'testBot']);
$router->get('/admin/settings/set-webhook', [SettingsController::class, 'setWebhook']);
$router->get('/admin/settings/delete-webhook', [SettingsController::class, 'deleteWebhook']);

// System API & Developer Console
$router->get('/admin/api', [ApiConsoleController::class, 'index']);
$router->post('/admin/api/regenerate', [ApiConsoleController::class, 'regenerate']);

// ==========================================
// SYSTEM REST API V1 (LLM & DEVELOPER ENDPOINTS)
// ==========================================
$router->get('/api/v1/openapi.json', [SchemaApiController::class, 'openapi']);
$router->get('/api/v1/stats', [StatsApiController::class, 'stats']);
$router->get('/api/v1/orders', [StatsApiController::class, 'orders']);

// Products API
$router->get('/api/v1/products', [ProductApiController::class, 'index']);
$router->get('/api/v1/products/{id}', [ProductApiController::class, 'show']);
$router->post('/api/v1/products', [ProductApiController::class, 'create']);
$router->put('/api/v1/products/{id}', [ProductApiController::class, 'update']);
$router->delete('/api/v1/products/{id}', [ProductApiController::class, 'delete']);

// Cards / License Keys API
$router->get('/api/v1/products/{id}/cards', [CardApiController::class, 'index']);
$router->post('/api/v1/products/{id}/cards', [CardApiController::class, 'bulkCreate']);

$router->get('/api/v1/health', function() {
    \App\Core\View::json(['status' => 'HEALTHY', 'version' => APP_VERSION, 'app' => 'Abi Store', 'timestamp' => date('c')]);
});

// ==========================================
// WEBHOOK ROUTES
// ==========================================
// Pluggable Unified Payment Webhook Route
$router->post('/payment/webhook/{gateway}', [PaymentWebhookController::class, 'handle']);
$router->post('/api/payment/webhook/{gateway}', [PaymentWebhookController::class, 'handle']);

// Legacy and Specific Provider Aliases
$router->post('/api/telegram/webhook', [TelegramWebhookController::class, 'handle']);
$router->post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);
$router->get('/telegram/webhook', [TelegramWebhookController::class, 'handle']);
$router->post('/api/paypal/webhook', [PayPalWebhookController::class, 'handle']);
$router->post('/paypal/webhook', [PayPalWebhookController::class, 'handle']);

// Dispatch
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($method, $uri);
