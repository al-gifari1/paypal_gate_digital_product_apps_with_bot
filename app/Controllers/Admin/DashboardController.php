<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\View;
use App\Database\Database;
use PDO;

class DashboardController
{
    public function index(): void
    {
        AuthController::requireAuth();

        $pdo = Database::getConnection();

        // 1. Total Revenue
        $revStmt = $pdo->query('SELECT SUM(amount) FROM orders WHERE payment_status = "completed"');
        $totalRevenue = (float)($revStmt->fetchColumn() ?: 0.00);

        // 2. Total Completed Orders
        $ordStmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE payment_status = "completed"');
        $completedOrders = (int)$ordStmt->fetchColumn();

        // 3. Active Products
        $prodStmt = $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1');
        $activeProducts = (int)$prodStmt->fetchColumn();

        // 4. Available Cards in Pool
        $cardStmt = $pdo->query('SELECT COUNT(*) FROM cards_pool WHERE status = "available"');
        $availableCards = (int)$cardStmt->fetchColumn();

        // Recent Orders
        $recentOrdersStmt = $pdo->query('
            SELECT o.*, p.name as product_name
            FROM orders o
            JOIN products p ON o.product_id = p.id
            ORDER BY o.id DESC LIMIT 8
        ');
        $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent Transactions
        $transStmt = $pdo->query('SELECT * FROM transactions ORDER BY id DESC LIMIT 6');
        $recentTransactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/dashboard', [
            'pageTitle' => 'Mission Control Dashboard',
            'activeMenu' => 'dashboard',
            'totalRevenue' => $totalRevenue,
            'completedOrders' => $completedOrders,
            'activeProducts' => $activeProducts,
            'availableCards' => $availableCards,
            'recentOrders' => $recentOrders,
            'recentTransactions' => $recentTransactions,
        ], 'admin/layout');
    }
}
