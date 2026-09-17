<?php
declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\View;
use App\Database\Database;
use PDO;

class HomeController
{
    public function index(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('
            SELECT p.*,
                (SELECT COUNT(*) FROM cards_pool c WHERE c.product_id = p.id AND c.status = "available") as available_cards
            FROM products p
            WHERE p.is_active = 1
            ORDER BY p.id DESC
        ');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('storefront/home', [
            'pageTitle' => 'Digital Products & Instant Delivery',
            'products' => $products,
        ], 'storefront/layout');
    }
}
