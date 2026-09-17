<?php
declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\View;
use App\Database\Database;
use PDO;

class ProductController
{
    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

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
            http_response_code(404);
            View::render('storefront/404', ['pageTitle' => 'Product Not Found'], 'storefront/layout');
            return;
        }

        View::render('storefront/product_details', [
            'pageTitle' => $product['name'],
            'product' => $product,
        ], 'storefront/layout');
    }
}
