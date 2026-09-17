<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\View;
use App\Database\Database;
use PDO;

class TransactionController
{
    public function index(): void
    {
        AuthController::requireAuth();

        $pdo = Database::getConnection();
        $stmt = $pdo->query('
            SELECT t.*, o.order_number
            FROM transactions t
            LEFT JOIN orders o ON t.order_id = o.id
            ORDER BY t.id DESC LIMIT 100
        ');
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/transactions/index', [
            'pageTitle' => 'PayPal Transactions Ledger',
            'activeMenu' => 'transactions',
            'transactions' => $transactions,
        ], 'admin/layout');
    }
}
