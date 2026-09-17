<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\View;
use App\Database\Database;
use PDO;

class CustomerController
{
    public function index(): void
    {
        AuthController::requireAuth();

        $pdo = Database::getConnection();

        // Get customers with total orders and saved cards count
        $stmt = $pdo->query('
            SELECT c.*,
                (SELECT COUNT(*) FROM customer_cards cc WHERE cc.customer_id = c.id) as saved_cards_count,
                (SELECT COUNT(*) FROM orders o WHERE o.buyer_email = c.email AND o.payment_status = "completed") as total_purchases,
                (SELECT SUM(amount) FROM orders o WHERE o.buyer_email = c.email AND o.payment_status = "completed") as total_spent
            FROM customers c
            ORDER BY c.id DESC
        ');
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get all saved cards joined with customer info
        $cardsStmt = $pdo->query('
            SELECT cc.*, c.email as customer_email, c.name as customer_name
            FROM customer_cards cc
            JOIN customers c ON cc.customer_id = c.id
            ORDER BY cc.id DESC
        ');
        $cards = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/customers/index', [
            'pageTitle' => 'Customer Profiles & Saved Wallets',
            'activeMenu' => 'customers',
            'customers' => $customers,
            'cards' => $cards,
        ], 'admin/layout');
    }
}
