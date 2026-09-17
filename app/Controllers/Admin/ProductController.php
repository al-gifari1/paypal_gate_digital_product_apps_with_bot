<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Security;
use App\Core\Session;
use App\Core\View;
use App\Database\Database;
use PDO;

class ProductController
{
    public function index(): void
    {
        AuthController::requireAuth();

        $pdo = Database::getConnection();
        $stmt = $pdo->query('
            SELECT p.*,
                (SELECT COUNT(*) FROM cards_pool c WHERE c.product_id = p.id AND c.status = "available") as available_cards,
                (SELECT COUNT(*) FROM orders o WHERE o.product_id = p.id AND o.payment_status = "completed") as sales_count
            FROM products p
            ORDER BY p.id DESC
        ');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/products/index', [
            'pageTitle' => 'Products Manager',
            'activeMenu' => 'products',
            'products' => $products,
        ], 'admin/layout');
    }

    public function create(): void
    {
        AuthController::requireAuth();

        View::render('admin/products/create', [
            'pageTitle' => 'Add New Digital Product',
            'activeMenu' => 'products',
        ], 'admin/layout');
    }

    public function store(): void
    {
        AuthController::requireAuth();

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::flash('error', 'Security session expired. Please retry.');
            View::redirect('/admin/products/create');
        }

        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0.00);
        $description = trim($_POST['description'] ?? '');
        $productType = $_POST['product_type'] ?? 'downloadable_file';
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));

        if (empty($name) || $price < 0.01) {
            Session::flash('error', 'Product name and a valid price (> 0) are required.');
            View::redirect('/admin/products/create');
        }

        $slug = Security::sanitizeSlug($name);
        $pdo = Database::getConnection();

        // Ensure unique slug
        $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
        $check->execute([$slug]);
        if ((int)$check->fetchColumn() > 0) {
            $slug .= '-' . time();
        }

        $filePath = null;
        $fileName = null;
        $fileSize = 0;

        // Handle downloadable file upload
        if ($productType === 'downloadable_file' && !empty($_FILES['product_file']['name'])) {
            $file = $_FILES['product_file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $fileName = basename($file['name']);
                $fileSize = (int)$file['size'];
                $safeTarget = UPLOADS_PATH . '/' . uniqid('file_', true) . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);
                if (move_uploaded_file($file['tmp_name'], $safeTarget)) {
                    $filePath = $safeTarget;
                } else {
                    Session::flash('error', 'Failed to store uploaded file on server storage.');
                    View::redirect('/admin/products/create');
                }
            }
        }

        $imagePath = null;
        // Handle product image upload
        if (!empty($_FILES['product_image']['name'])) {
            $img = $_FILES['product_image'];
            if ($img['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
                if (in_array($ext, $allowed, true)) {
                    $imgName = uniqid('prod_', true) . '.' . $ext;
                    $publicUploadDir = APP_ROOT . '/public/uploads/products';
                    if (!is_dir($publicUploadDir)) {
                        mkdir($publicUploadDir, 0755, true);
                    }
                    $targetPath = $publicUploadDir . '/' . $imgName;
                    if (move_uploaded_file($img['tmp_name'], $targetPath)) {
                        $imagePath = '/uploads/products/' . $imgName;
                    }
                }
            }
        } elseif (!empty($_POST['image_url'])) {
            $imagePath = trim((string)$_POST['image_url']);
        }

        $stmt = $pdo->prepare('
            INSERT INTO products (name, slug, description, price, currency, product_type, file_path, file_name, file_size, image_path, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ');
        $stmt->execute([$name, $slug, $description, $price, $currency, $productType, $filePath, $fileName, $fileSize, $imagePath]);

        $productId = (int)$pdo->lastInsertId();

        // If card_license and initial keys provided in textarea
        if ($productType === 'card_license' && !empty($_POST['initial_keys'])) {
            $lines = explode("\n", (string)$_POST['initial_keys']);
            $cardStmt = $pdo->prepare('INSERT INTO cards_pool (product_id, card_data, status) VALUES (?, ?, "available")');
            foreach ($lines as $line) {
                $cardData = trim($line);
                if (!empty($cardData)) {
                    $cardStmt->execute([$productId, $cardData]);
                }
            }
        }

        Session::flash('success', "Product '{$name}' created successfully!");
        View::redirect('/admin/products');
    }

    public function toggle(array $params): void
    {
        AuthController::requireAuth();
        $id = (int)($params['id'] ?? 0);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE products SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?');
        $stmt->execute([$id]);

        Session::flash('success', 'Product visibility toggled.');
        View::redirect('/admin/products');
    }

    public function delete(array $params): void
    {
        AuthController::requireAuth();
        $id = (int)($params['id'] ?? 0);

        $pdo = Database::getConnection();
        // Fetch file to unlink
        $stmt = $pdo->prepare('SELECT file_path FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prod && !empty($prod['file_path']) && file_exists($prod['file_path'])) {
            @unlink($prod['file_path']);
        }

        $del = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $del->execute([$id]);

        Session::flash('success', 'Product removed successfully.');
        View::redirect('/admin/products');
    }
}
