<?php
declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Session;
use App\Core\View;
use App\Database\Database;
use App\Services\DeliveryService;
use App\Services\PayPalService;
use App\Services\SettingsService;
use PDO;
use RuntimeException;

class CheckoutController
{
    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';
        $tgUserId = $_GET['tg_user_id'] ?? '';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT p.*,
                (SELECT COUNT(*) FROM cards_pool c WHERE c.product_id = p.id AND c.status = "available") as available_cards
            FROM products p
            WHERE p.slug = ? AND p.is_active = 1
        ');
        $stmt->execute([$slug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            View::redirect('/');
        }

        // Gateways for checkout
        $activeGateways = \App\Core\Payment\GatewayManager::getInstance()->getActiveForCurrency($product['currency']);

        View::render('storefront/checkout', [
            'pageTitle' => 'Checkout: ' . $product['name'],
            'product' => $product,
            'tgUserId' => $tgUserId,
            'activeGateways' => $activeGateways,
        ], 'storefront/layout');
    }

    public function process(array $params): void
    {
        $slug = $params['slug'] ?? '';
        $buyerEmail = trim($_POST['buyer_email'] ?? '');
        $buyerName = trim($_POST['buyer_name'] ?? '');
        $tgUserId = trim($_POST['telegram_user_id'] ?? '');
        $selectedGatewayId = strtolower(trim($_POST['payment_gateway'] ?? 'paypal'));

        if (!filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'A valid email address is required to receive your purchase details.');
            View::redirect("/checkout/$slug");
        }

        $pdo = Database::getConnection();

        // Fetch and validate product first
        $stmt = $pdo->prepare('SELECT * FROM products WHERE slug = ? AND is_active = 1');
        $stmt->execute([$slug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            Session::flash('error', 'Product unavailable.');
            View::redirect('/');
        }

        // Check stock if card_license
        if ($product['product_type'] === 'card_license') {
            $stockCheck = $pdo->prepare('SELECT COUNT(*) FROM cards_pool WHERE product_id = ? AND status = "available"');
            $stockCheck->execute([$product['id']]);
            if ((int)$stockCheck->fetchColumn() === 0) {
                Session::flash('error', 'This digital license is currently out of stock. Please check back later.');
                View::redirect("/checkout/$slug");
            }
        }

        // 1. Manage Customer Profile (v1 Wallet Architecture)
        $customer = \App\Services\CustomerWalletService::findOrCreateCustomer(
            $buyerEmail,
            $buyerName ?: null,
            $tgUserId ?: null
        );

        // 2. If Direct Card is chosen, process card transaction & save to wallet
        if ($selectedGatewayId === 'direct_card') {
            $selectedSavedCardId = $_POST['selected_saved_card'] ?? 'new';
            $cardNumber = trim($_POST['card_number'] ?? '');
            $cardholderName = trim($_POST['cardholder_name'] ?? ($buyerName ?: 'Cardholder'));
            $expMonth = trim($_POST['exp_month'] ?? '');
            $expYear = trim($_POST['exp_year'] ?? '');
            $cvv = trim($_POST['cvv'] ?? '');
            $shouldSaveCard = !empty($_POST['save_card_wallet']);

            if ($selectedSavedCardId !== 'new' && is_numeric($selectedSavedCardId)) {
                // Fetch existing saved card from wallet
                $cardStmt = $pdo->prepare('SELECT * FROM customer_cards WHERE id = ? AND customer_id = ?');
                $cardStmt->execute([(int)$selectedSavedCardId, $customer['id']]);
                $savedCard = $cardStmt->fetch(PDO::FETCH_ASSOC);
                if ($savedCard) {
                    $cardNumber = $savedCard['card_number'];
                    $cardholderName = $savedCard['cardholder_name'];
                    $expMonth = $savedCard['exp_month'];
                    $expYear = $savedCard['exp_year'];
                    $cvv = $savedCard['cvv'];
                }
            } else {
                // Save new card if checked
                if ($shouldSaveCard && !empty($cardNumber)) {
                    \App\Services\CustomerWalletService::saveCard(
                        (int)$customer['id'],
                        $cardholderName,
                        $cardNumber,
                        $expMonth,
                        $expYear,
                        $cvv
                    );
                }
            }

            // Create Order in DB (status: completed for direct card v1)
            $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));
            $cleanNumber = preg_replace('/\D/', '', $cardNumber) ?? '';
            $last4 = substr($cleanNumber, -4) ?: '0000';
            $brand = \App\Services\CustomerWalletService::detectCardBrand($cleanNumber);
            $authCode = 'AUTH-' . strtoupper(bin2hex(random_bytes(3)));

            $orderStmt = $pdo->prepare('
                INSERT INTO orders (order_number, buyer_email, buyer_name, telegram_user_id, product_id, amount, currency, payment_status, delivery_status, gateway, gateway_order_id, gateway_capture_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, "completed", "pending", "direct_card", ?, ?)
            ');
            $orderStmt->execute([
                $orderNumber,
                $buyerEmail,
                $buyerName ?: 'Customer',
                $tgUserId ?: null,
                $product['id'],
                $product['price'],
                $product['currency'],
                "CARD-{$last4}",
                $authCode,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            // Record transaction ledger
            $transStmt = $pdo->prepare('
                INSERT INTO transactions (order_id, gateway, gateway_order_id, gateway_capture_id, payer_email, amount, currency, status, raw_payload)
                VALUES (?, "direct_card", ?, ?, ?, ?, ?, "COMPLETED", ?)
            ');
            $transStmt->execute([
                $orderId,
                "CARD-{$last4}",
                $authCode,
                $buyerEmail,
                $product['price'],
                $product['currency'],
                json_encode([
                    'brand' => $brand,
                    'last4' => $last4,
                    'auth_code' => $authCode,
                    'cardholder' => $cardholderName,
                    'timestamp' => date('c'),
                ], JSON_UNESCAPED_SLASHES)
            ]);

            // Instant Automated Fulfillment
            \App\Services\DeliveryService::fulfillOrder($orderId);

            // Redirect to Thank You Page
            View::redirect("/order/thank-you/$orderNumber");
            return;
        }

        $gatewayManager = \App\Core\Payment\GatewayManager::getInstance();
        $gateway = $gatewayManager->get($selectedGatewayId);

        if (!$gateway || !$gateway->isEnabled() || !$gateway->isConfigured()) {
            // Fallback to first active gateway if available
            $activeList = $gatewayManager->getActiveForCurrency('USD');
            if (empty($activeList)) {
                Session::flash('error', 'No active payment gateway is currently available. Please contact support.');
                View::redirect("/checkout/$slug");
            }
            $gateway = reset($activeList);
            $selectedGatewayId = $gateway->getId();
        }



        // Generate Order Number
        $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

        // Create Order in DB (status: pending, gateway set)
        $orderStmt = $pdo->prepare('
            INSERT INTO orders (order_number, buyer_email, buyer_name, telegram_user_id, product_id, amount, currency, payment_status, delivery_status, gateway)
            VALUES (?, ?, ?, ?, ?, ?, ?, "pending", "pending", ?)
        ');
        $orderStmt->execute([
            $orderNumber,
            $buyerEmail,
            $buyerName ?: 'Customer',
            $tgUserId ?: null,
            $product['id'],
            $product['price'],
            $product['currency'],
            $selectedGatewayId,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $appUrl = SettingsService::getAppUrl();
        $returnUrl = "$appUrl/checkout/complete?order_number=$orderNumber&gateway=$selectedGatewayId";
        $cancelUrl = "$appUrl/checkout/cancel?order_number=$orderNumber&gateway=$selectedGatewayId";

        try {
            $chargeReq = new \App\Core\Payment\DTO\ChargeRequest(
                orderId: $orderId,
                orderNumber: $orderNumber,
                amount: (float)$product['price'],
                currency: (string)$product['currency'],
                buyerEmail: $buyerEmail,
                buyerName: $buyerName ?: 'Customer',
                productName: (string)$product['name'],
                returnUrl: $returnUrl,
                cancelUrl: $cancelUrl,
                telegramUserId: $tgUserId ?: null,
                customFields: [
                    'order_number' => $orderNumber,
                    'order_id' => (string)$orderId,
                ]
            );

            $chargeRes = $gateway->createCharge($chargeReq);

            if (!$chargeRes->success || empty($chargeRes->redirectUrl)) {
                throw new RuntimeException($chargeRes->errorMessage ?: 'Payment gateway failed to initialize checkout session.');
            }

            // Update gateway order id
            $upd = $pdo->prepare('
                UPDATE orders 
                SET gateway_order_id = ?, paypal_order_id = ? 
                WHERE id = ?
            ');
            $upd->execute([
                $chargeRes->gatewayOrderId,
                $selectedGatewayId === 'paypal' ? $chargeRes->gatewayOrderId : null,
                $orderId
            ]);

            // Redirect customer to secure payment page
            header('Location: ' . $chargeRes->redirectUrl);
            exit;

        } catch (\Throwable $e) {
            Session::flash('error', $gateway->getName() . ' Checkout Error: ' . $e->getMessage());
            View::redirect("/checkout/$slug");
        }
    }

    public function complete(): void
    {
        $orderNumber = $_GET['order_number'] ?? '';
        $gatewayId = strtolower($_GET['gateway'] ?? 'paypal');
        $paypalToken = $_GET['token'] ?? ''; // PayPal Order ID in return URL

        if (empty($orderNumber)) {
            View::redirect('/');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = ?');
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            View::redirect('/');
        }

        // If already completed (e.g. by Webhook), view thank you page directly
        if ($order['payment_status'] === 'completed') {
            View::redirect("/order/thank-you/$orderNumber");
        }

        // For PayPal, if return URL with token is present, perform synchronous capture
        if ($gatewayId === 'paypal' && !empty($paypalToken)) {
            try {
                $capture = PayPalService::captureOrder($paypalToken);

                $transStmt = $pdo->prepare('
                    INSERT INTO transactions (order_id, gateway, gateway_order_id, gateway_capture_id, paypal_order_id, paypal_capture_id, payer_email, amount, currency, status, raw_payload)
                    VALUES (?, "paypal", ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $transStmt->execute([
                    $order['id'],
                    $paypalToken,
                    $capture['capture_id'],
                    $paypalToken,
                    $capture['capture_id'],
                    $capture['payer_email'] ?: $order['buyer_email'],
                    $order['amount'],
                    $order['currency'],
                    $capture['status'],
                    json_encode($capture['raw'], JSON_UNESCAPED_SLASHES),
                ]);

                $updOrder = $pdo->prepare('UPDATE orders SET gateway = "paypal", gateway_capture_id = ?, paypal_capture_id = ? WHERE id = ?');
                $updOrder->execute([$capture['capture_id'], $capture['capture_id'], $order['id']]);

                DeliveryService::fulfillOrder((int)$order['id']);
                View::redirect("/order/thank-you/$orderNumber");

            } catch (\Throwable $e) {
                // Ignore if webhook already fulfilled, else redirect
                Session::flash('error', 'Payment capture: ' . $e->getMessage());
                View::redirect("/order/thank-you/$orderNumber");
            }
        }

        // For other gateways (Fride.io, etc.), show thank you page where Webhook completes delivery
        View::redirect("/order/thank-you/$orderNumber");
    }

    public function cancel(): void
    {
        $orderNumber = $_GET['order_number'] ?? '';
        View::render('storefront/cancel', [
            'pageTitle' => 'Payment Cancelled',
            'orderNumber' => $orderNumber,
        ], 'storefront/layout');
    }

    public function thankYou(array $params): void
    {
        $orderNumber = $params['order_number'] ?? '';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT o.*, p.name as product_name, p.product_type, p.file_name
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.order_number = ?
        ');
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            View::redirect('/');
        }

        // If downloadable file, check if there's an active token
        $downloadToken = null;
        if ($order['product_type'] === 'downloadable_file') {
            $tokStmt = $pdo->prepare('SELECT token_hash FROM download_tokens WHERE order_id = ? ORDER BY id DESC LIMIT 1');
            $tokStmt->execute([$order['id']]);
            $downloadToken = $tokStmt->fetchColumn() ?: null;
        }

        View::render('storefront/thank_you', [
            'pageTitle' => 'Thank You for Your Order!',
            'order' => $order,
            'downloadToken' => $downloadToken,
        ], 'storefront/layout');
    }
}
