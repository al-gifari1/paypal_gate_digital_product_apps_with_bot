<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Core\View;
use App\Database\Database;
use App\Services\DeliveryService;
use PDO;

class OrderController
{
    public function index(): void
    {
        AuthController::requireAuth();

        $pdo = Database::getConnection();
        $stmt = $pdo->query('
            SELECT o.*, p.name as product_name, p.product_type
            FROM orders o
            JOIN products p ON o.product_id = p.id
            ORDER BY o.id DESC LIMIT 100
        ');
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/orders/index', [
            'pageTitle' => 'Order Manager',
            'activeMenu' => 'orders',
            'orders' => $orders,
        ], 'admin/layout');
    }

    public function fulfill(array $params): void
    {
        AuthController::requireAuth();
        $id = (int)($params['id'] ?? 0);

        try {
            DeliveryService::fulfillOrder($id);
            Session::flash('success', "Order #$id fulfillment executed successfully.");
        } catch (\Throwable $e) {
            Session::flash('error', "Fulfillment failed: " . $e->getMessage());
        }

        View::redirect('/admin/orders');
    }
}
