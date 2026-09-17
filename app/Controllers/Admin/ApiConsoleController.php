<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Security;
use App\Core\Session;
use App\Core\View;
use App\Database\Database;
use App\Services\SettingsService;
use PDO;

class ApiConsoleController
{
    public function index(): void
    {
        AuthController::requireAuth();

        $apiKey = SettingsService::getApiKey();
        $appUrl = SettingsService::getAppUrl();

        $pdo = Database::getConnection();
        $products = $pdo->query('SELECT id, name, product_type, price, currency FROM products ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/api/index', [
            'pageTitle' => 'System API & LLM Automation',
            'activeMenu' => 'api',
            'apiKey' => $apiKey,
            'appUrl' => $appUrl,
            'products' => $products,
        ], 'admin/layout');
    }

    public function regenerate(): void
    {
        AuthController::requireAuth();

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::flash('error', 'Security token expired.');
            View::redirect('/admin/api');
        }

        $newKey = SettingsService::regenerateApiKey();
        Session::flash('success', 'Master API Key regenerated successfully! All previous integrations must be updated.');
        View::redirect('/admin/api');
    }
}
