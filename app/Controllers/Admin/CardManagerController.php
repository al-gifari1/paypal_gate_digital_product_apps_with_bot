<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Core\View;
use App\Database\Database;
use PDO;

class CardManagerController
{
    public function index(): void
    {
        AuthController::requireAuth();

        $pdo = Database::getConnection();

        // Products dropdown
        $prodStmt = $pdo->query('SELECT id, name FROM products WHERE product_type = "card_license" ORDER BY name ASC');
        $cardProducts = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter by product if specified
        $filterProductId = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null;

        $sql = '
            SELECT c.*, p.name as product_name, o.order_number
            FROM cards_pool c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN orders o ON c.assigned_order_id = o.id
        ';
        $params = [];
        if ($filterProductId !== null) {
            $sql .= ' WHERE c.product_id = ?';
            $params[] = $filterProductId;
        }
        $sql .= ' ORDER BY c.id DESC LIMIT 200';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/cards/index', [
            'pageTitle' => 'Cards & License Keys Manager',
            'activeMenu' => 'cards',
            'cardProducts' => $cardProducts,
            'cards' => $cards,
            'selectedProduct' => $filterProductId,
        ], 'admin/layout');
    }

    public function import(): void
    {
        AuthController::requireAuth();

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::flash('error', 'Security token mismatch.');
            View::redirect('/admin/cards');
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $keysRaw = trim($_POST['keys_raw'] ?? '');

        if ($productId <= 0 || empty($keysRaw)) {
            Session::flash('error', 'Please select a product and enter at least one card/license line.');
            View::redirect('/admin/cards');
        }

        $pdo = Database::getConnection();
        $lines = explode("\n", $keysRaw);
        $stmt = $pdo->prepare('INSERT INTO cards_pool (product_id, card_data, status) VALUES (?, ?, "available")');

        $count = 0;
        foreach ($lines as $line) {
            $data = trim($line);
            if (!empty($data)) {
                $stmt->execute([$productId, $data]);
                $count++;
            }
        }

        Session::flash('success', "Imported {$count} cards/license keys into inventory!");
        View::redirect('/admin/cards?product_id=' . $productId);
    }

    public function delete(array $params): void
    {
        AuthController::requireAuth();
        $id = (int)($params['id'] ?? 0);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM cards_pool WHERE id = ?');
        $stmt->execute([$id]);

        Session::flash('success', 'Card removed from inventory.');
        View::redirect('/admin/cards');
    }
}
