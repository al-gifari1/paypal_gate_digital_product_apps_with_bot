<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Database\Database;
use App\Services\SettingsService;
use PDO;

class StatsApiController extends ApiController
{
    /**
     * Retrieve high-level store telemetry and stock status.
     * GET /api/v1/stats
     */
    public function stats(): void
    {
        $pdo = Database::getConnection();

        // Revenue & Orders
        $orderStats = $pdo->query('
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN payment_status = "completed" THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN payment_status = "pending" THEN 1 ELSE 0 END) as pending_orders,
                COALESCE(SUM(CASE WHEN payment_status = "completed" THEN amount ELSE 0 END), 0) as gross_revenue
            FROM orders
        ')->fetch(PDO::FETCH_ASSOC);

        // Products & Inventory
        $productStats = $pdo->query('
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
                SUM(CASE WHEN product_type = "card_license" THEN 1 ELSE 0 END) as license_products,
                SUM(CASE WHEN product_type = "downloadable_file" THEN 1 ELSE 0 END) as downloadable_products
            FROM products
        ')->fetch(PDO::FETCH_ASSOC);

        // Card Pool Metrics
        $cardsStats = $pdo->query('
            SELECT 
                COUNT(*) as total_pool_cards,
                SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) as available_cards,
                SUM(CASE WHEN status = "assigned" THEN 1 ELSE 0 END) as assigned_cards
            FROM cards_pool
        ')->fetch(PDO::FETCH_ASSOC);

        // Low stock products (available cards <= 3)
        $lowStockStmt = $pdo->query('
            SELECT p.id, p.name, COUNT(c.id) as available_count
            FROM products p
            LEFT JOIN cards_pool c ON c.product_id = p.id AND c.status = "available"
            WHERE p.product_type = "card_license" AND p.is_active = 1
            GROUP BY p.id
            HAVING available_count <= 3
            ORDER BY available_count ASC
        ');
        $lowStock = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lowStock as &$item) {
            $item['id'] = (int)$item['id'];
            $item['available_count'] = (int)$item['available_count'];
        }

        $this->sendSuccess([
            'currency' => SettingsService::getCurrency(),
            'revenue' => [
                'gross_revenue' => (float)$orderStats['gross_revenue'],
                'completed_orders' => (int)$orderStats['completed_orders'],
                'pending_orders' => (int)$orderStats['pending_orders'],
                'total_orders' => (int)$orderStats['total_orders'],
            ],
            'products' => [
                'total' => (int)$productStats['total_products'],
                'active' => (int)$productStats['active_products'],
                'license_products' => (int)$productStats['license_products'],
                'downloadable_products' => (int)$productStats['downloadable_products'],
            ],
            'inventory_pool' => [
                'total_keys' => (int)$cardsStats['total_pool_cards'],
                'available_keys' => (int)$cardsStats['available_cards'],
                'assigned_keys' => (int)$cardsStats['assigned_cards'],
            ],
            'alerts' => [
                'low_stock_products_count' => count($lowStock),
                'low_stock_products' => $lowStock,
            ]
        ]);
    }

    /**
     * List recent sales & orders.
     * GET /api/v1/orders
     */
    public function orders(): void
    {
        $pdo = Database::getConnection();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
        $offset = ($page - 1) * $pageSize;

        $status = $_GET['status'] ?? null;
        $where = [];
        $params = [];

        if ($status !== null && in_array($status, ['completed', 'pending', 'refunded', 'failed'], true)) {
            $where[] = 'o.payment_status = ?';
            $params[] = $status;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT 
                o.id, o.order_number, o.buyer_email, o.buyer_name, o.telegram_user_id,
                o.product_id, p.name as product_name, p.product_type,
                o.amount, o.currency, o.payment_status, o.delivery_status, 
                o.paypal_order_id, o.created_at
            FROM orders o
            LEFT JOIN products p ON p.id = o.product_id
            $whereClause
            ORDER BY o.id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $pdo->prepare($sql);
        $bindIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($bindIndex++, $param);
        }
        $stmt->bindValue($bindIndex++, $pageSize, PDO::PARAM_INT);
        $stmt->bindValue($bindIndex++, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($orders as &$ord) {
            $ord['id'] = (int)$ord['id'];
            $ord['product_id'] = (int)$ord['product_id'];
            $ord['amount'] = (float)$ord['amount'];
        }

        $this->sendSuccess($orders, 200, [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => (int)ceil($total / $pageSize),
        ]);
    }
}
