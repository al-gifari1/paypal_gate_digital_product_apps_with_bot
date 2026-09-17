<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Database\Database;
use PDO;

class CardApiController extends ApiController
{
    /**
     * Get card pool inventory stats for a product.
     * GET /api/v1/products/{id}/cards
     */
    public function index(array $routeParams): void
    {
        $productId = (int)($routeParams['id'] ?? 0);
        if ($productId <= 0) {
            $this->sendError('Invalid product ID.', 400, 'INVALID_ARGUMENT');
            return;
        }

        $pdo = Database::getConnection();

        // Check product exists
        $check = $pdo->prepare('SELECT id, name, product_type FROM products WHERE id = ?');
        $check->execute([$productId]);
        $product = $check->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $this->sendError("Product with ID {$productId} was not found.", 404, 'NOT_FOUND');
            return;
        }

        $stmt = $pdo->prepare('
            SELECT 
                COUNT(*) as total_cards,
                SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) as available_cards,
                SUM(CASE WHEN status = "assigned" THEN 1 ELSE 0 END) as assigned_cards
            FROM cards_pool
            WHERE product_id = ?
        ');
        $stmt->execute([$productId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        $response = [
            'product_id' => $productId,
            'product_name' => $product['name'],
            'total_cards' => (int)($counts['total_cards'] ?? 0),
            'available_cards' => (int)($counts['available_cards'] ?? 0),
            'assigned_cards' => (int)($counts['assigned_cards'] ?? 0),
        ];

        // If include_available=1 requested, fetch available keys
        if (!empty($_GET['include_available'])) {
            $keyStmt = $pdo->prepare('SELECT id, card_data, created_at FROM cards_pool WHERE product_id = ? AND status = "available" ORDER BY id ASC');
            $keyStmt->execute([$productId]);
            $response['available_inventory'] = $keyStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->sendSuccess($response);
    }

    /**
     * Bulk upload cards or license keys into the pool.
     * POST /api/v1/products/{id}/cards
     */
    public function bulkCreate(array $routeParams): void
    {
        $productId = (int)($routeParams['id'] ?? 0);
        if ($productId <= 0) {
            $this->sendError('Invalid product ID.', 400, 'INVALID_ARGUMENT');
            return;
        }

        $pdo = Database::getConnection();

        // Check product
        $check = $pdo->prepare('SELECT id, name, product_type FROM products WHERE id = ?');
        $check->execute([$productId]);
        $product = $check->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $this->sendError("Product with ID {$productId} was not found.", 404, 'NOT_FOUND');
            return;
        }

        $payload = $this->getJsonPayload();
        $keys = [];

        if (isset($payload['keys']) && is_array($payload['keys'])) {
            $keys = $payload['keys'];
        } elseif (!empty($payload['card_data'])) {
            $keys = explode("\n", (string)$payload['card_data']);
        } elseif (!empty($payload['key'])) {
            $keys = [(string)$payload['key']];
        }

        $cleanKeys = [];
        foreach ($keys as $k) {
            $str = trim((string)$k);
            if ($str !== '') {
                $cleanKeys[] = $str;
            }
        }

        if (empty($cleanKeys)) {
            $this->sendError("No valid keys provided. Send an array in 'keys' or newline-separated string in 'card_data'.", 400, 'INVALID_ARGUMENT');
            return;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO cards_pool (product_id, card_data, status, created_at) VALUES (?, ?, "available", CURRENT_TIMESTAMP)');
            $inserted = 0;
            foreach ($cleanKeys as $key) {
                $stmt->execute([$productId, $key]);
                $inserted++;
            }
            $pdo->commit();

            // Total available now
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM cards_pool WHERE product_id = ? AND status = "available"');
            $countStmt->execute([$productId]);
            $availableNow = (int)$countStmt->fetchColumn();

            $this->sendSuccess([
                'product_id' => $productId,
                'inserted_count' => $inserted,
                'available_cards_now' => $availableNow,
                'message' => "Successfully imported {$inserted} keys into inventory pool."
            ], 201);

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->sendError('Failed to import keys: ' . $e->getMessage(), 500, 'INTERNAL');
        }
    }
}
