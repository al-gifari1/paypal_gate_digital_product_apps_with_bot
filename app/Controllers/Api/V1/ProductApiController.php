<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Security;
use App\Database\Database;
use PDO;

class ProductApiController extends ApiController
{
    /**
     * List all digital products with pagination & filtering.
     * GET /api/v1/products
     */
    public function index(): void
    {
        $pdo = Database::getConnection();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($_GET['page_size'] ?? 20)));
        $offset = ($page - 1) * $pageSize;

        $type = $_GET['type'] ?? null;
        $isActive = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
        $search = trim($_GET['search'] ?? '');

        $where = [];
        $params = [];

        if ($type !== null && in_array($type, ['downloadable_file', 'card_license'], true)) {
            $where[] = 'p.product_type = ?';
            $params[] = $type;
        }

        if ($isActive !== null) {
            $where[] = 'p.is_active = ?';
            $params[] = $isActive;
        }

        if ($search !== '') {
            $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch products with card & order stats
        $sql = "
            SELECT 
                p.id, p.name, p.slug, p.description, p.price, p.currency, 
                p.product_type, p.image_path, p.is_active, p.created_at,
                (SELECT COUNT(*) FROM cards_pool c WHERE c.product_id = p.id AND c.status = 'available') as available_cards,
                (SELECT COUNT(*) FROM orders o WHERE o.product_id = p.id AND o.payment_status = 'completed') as sales_count
            FROM products p
            $whereClause
            ORDER BY p.id DESC
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

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cast numeric types
        foreach ($products as &$p) {
            $p['id'] = (int)$p['id'];
            $p['price'] = (float)$p['price'];
            $p['is_active'] = (bool)$p['is_active'];
            $p['available_cards'] = (int)$p['available_cards'];
            $p['sales_count'] = (int)$p['sales_count'];
        }

        $this->sendSuccess($products, 200, [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => (int)ceil($total / $pageSize),
        ]);
    }

    /**
     * Get single product details.
     * GET /api/v1/products/{id}
     */
    public function show(array $routeParams): void
    {
        $id = (int)($routeParams['id'] ?? 0);
        if ($id <= 0) {
            $this->sendError('Invalid product ID provided.', 400, 'INVALID_ARGUMENT');
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT 
                p.id, p.name, p.slug, p.description, p.price, p.currency, 
                p.product_type, p.file_name, p.file_size, p.image_path, 
                p.is_active, p.created_at,
                (SELECT COUNT(*) FROM cards_pool c WHERE c.product_id = p.id AND c.status = "available") as available_cards,
                (SELECT COUNT(*) FROM cards_pool c WHERE c.product_id = p.id AND c.status = "assigned") as assigned_cards,
                (SELECT COUNT(*) FROM orders o WHERE o.product_id = p.id AND o.payment_status = "completed") as total_sales,
                (SELECT COALESCE(SUM(o.amount), 0) FROM orders o WHERE o.product_id = p.id AND o.payment_status = "completed") as total_revenue
            FROM products p
            WHERE p.id = ?
        ');
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $this->sendError("Product with ID {$id} was not found.", 404, 'NOT_FOUND');
            return;
        }

        $product['id'] = (int)$product['id'];
        $product['price'] = (float)$product['price'];
        $product['file_size'] = (int)$product['file_size'];
        $product['is_active'] = (bool)$product['is_active'];
        $product['available_cards'] = (int)$product['available_cards'];
        $product['assigned_cards'] = (int)$product['assigned_cards'];
        $product['total_sales'] = (int)$product['total_sales'];
        $product['total_revenue'] = (float)$product['total_revenue'];

        $this->sendSuccess($product);
    }

    /**
     * Create a new digital product (LLM-friendly payload).
     * POST /api/v1/products
     */
    public function create(): void
    {
        $payload = $this->getJsonPayload();

        $name = trim((string)($payload['name'] ?? ''));
        $price = (float)($payload['price'] ?? 0.0);
        $currency = strtoupper(trim((string)($payload['currency'] ?? 'USD')));
        $description = trim((string)($payload['description'] ?? ''));
        $productType = (string)($payload['product_type'] ?? 'downloadable_file');
        $imageUrl = !empty($payload['image_url']) ? trim((string)$payload['image_url']) : null;
        $isActive = isset($payload['is_active']) ? (int)(bool)$payload['is_active'] : 1;

        if ($name === '') {
            $this->sendError("Field 'name' is required.", 400, 'INVALID_ARGUMENT');
            return;
        }

        if ($price < 0.01) {
            $this->sendError("Field 'price' must be greater than 0.00.", 400, 'INVALID_ARGUMENT');
            return;
        }

        if (!in_array($productType, ['downloadable_file', 'card_license'], true)) {
            $this->sendError("Field 'product_type' must be 'downloadable_file' or 'card_license'.", 400, 'INVALID_ARGUMENT');
            return;
        }

        $pdo = Database::getConnection();
        $slug = Security::sanitizeSlug($name);

        // Ensure unique slug
        $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
        $check->execute([$slug]);
        if ((int)$check->fetchColumn() > 0) {
            $slug .= '-' . time();
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('
                INSERT INTO products (name, slug, description, price, currency, product_type, image_path, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ');
            $stmt->execute([$name, $slug, $description, $price, $currency, $productType, $imageUrl, $isActive]);
            $productId = (int)$pdo->lastInsertId();

            $insertedKeysCount = 0;
            // Handle optional initial license keys for card_license
            if ($productType === 'card_license' && !empty($payload['initial_keys'])) {
                $keysList = is_array($payload['initial_keys']) 
                    ? $payload['initial_keys'] 
                    : explode("\n", (string)$payload['initial_keys']);

                $cardStmt = $pdo->prepare('INSERT INTO cards_pool (product_id, card_data, status) VALUES (?, ?, "available")');
                foreach ($keysList as $keyLine) {
                    $cleanedKey = trim((string)$keyLine);
                    if ($cleanedKey !== '') {
                        $cardStmt->execute([$productId, $cleanedKey]);
                        $insertedKeysCount++;
                    }
                }
            }

            $pdo->commit();

            $this->sendSuccess([
                'id' => $productId,
                'name' => $name,
                'slug' => $slug,
                'price' => $price,
                'currency' => $currency,
                'product_type' => $productType,
                'image_path' => $imageUrl,
                'is_active' => (bool)$isActive,
                'available_cards' => $insertedKeysCount,
                'message' => 'Product created successfully.'
            ], 201);

        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->sendError('Failed to create product: ' . $e->getMessage(), 500, 'INTERNAL');
        }
    }

    /**
     * Update an existing product.
     * PUT /api/v1/products/{id}
     */
    public function update(array $routeParams): void
    {
        $id = (int)($routeParams['id'] ?? 0);
        if ($id <= 0) {
            $this->sendError('Invalid product ID.', 400, 'INVALID_ARGUMENT');
            return;
        }

        $pdo = Database::getConnection();
        $findStmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $findStmt->execute([$id]);
        $existing = $findStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $this->sendError("Product with ID {$id} was not found.", 404, 'NOT_FOUND');
            return;
        }

        $payload = $this->getJsonPayload();
        $fields = [];
        $params = [];

        if (isset($payload['name'])) {
            $name = trim((string)$payload['name']);
            if ($name === '') {
                $this->sendError("Field 'name' cannot be empty.", 400, 'INVALID_ARGUMENT');
                return;
            }
            $fields[] = 'name = ?';
            $params[] = $name;

            // Update slug if requested or auto-sync
            $newSlug = Security::sanitizeSlug($name);
            $fields[] = 'slug = ?';
            $params[] = $newSlug;
        }

        if (isset($payload['price'])) {
            $price = (float)$payload['price'];
            if ($price < 0.01) {
                $this->sendError("Field 'price' must be greater than 0.", 400, 'INVALID_ARGUMENT');
                return;
            }
            $fields[] = 'price = ?';
            $params[] = $price;
        }

        if (isset($payload['currency'])) {
            $fields[] = 'currency = ?';
            $params[] = strtoupper(trim((string)$payload['currency']));
        }

        if (isset($payload['description'])) {
            $fields[] = 'description = ?';
            $params[] = trim((string)$payload['description']);
        }

        if (isset($payload['image_url'])) {
            $fields[] = 'image_path = ?';
            $params[] = trim((string)$payload['image_url']);
        }

        if (isset($payload['is_active'])) {
            $fields[] = 'is_active = ?';
            $params[] = (int)(bool)$payload['is_active'];
        }

        if (empty($fields)) {
            $this->sendError('No valid updatable fields provided in request body.', 400, 'INVALID_ARGUMENT');
            return;
        }

        $params[] = $id;
        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute($params);

        // Fetch refreshed record
        $findStmt->execute([$id]);
        $updated = $findStmt->fetch(PDO::FETCH_ASSOC);
        $updated['id'] = (int)$updated['id'];
        $updated['price'] = (float)$updated['price'];
        $updated['is_active'] = (bool)$updated['is_active'];

        $this->sendSuccess($updated);
    }

    /**
     * Delete a product.
     * DELETE /api/v1/products/{id}
     */
    public function delete(array $routeParams): void
    {
        $id = (int)($routeParams['id'] ?? 0);
        if ($id <= 0) {
            $this->sendError('Invalid product ID.', 400, 'INVALID_ARGUMENT');
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, file_path FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $this->sendError("Product with ID {$id} was not found.", 404, 'NOT_FOUND');
            return;
        }

        // Unlink protected file if exists
        if (!empty($product['file_path']) && file_exists($product['file_path'])) {
            @unlink($product['file_path']);
        }

        $delStmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $delStmt->execute([$id]);

        $this->sendSuccess([
            'deleted' => true,
            'id' => $id,
            'message' => "Product #{$id} was successfully removed."
        ]);
    }
}
