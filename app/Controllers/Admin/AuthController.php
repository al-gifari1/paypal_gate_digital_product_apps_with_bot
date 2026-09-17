<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Security;
use App\Core\Session;
use App\Core\View;
use App\Database\Database;
use PDO;

class AuthController
{
    public static function requireAuth(): void
    {
        if (!Session::isAdminLoggedIn()) {
            Session::flash('error', 'Authentication required.');
            View::redirect('/admin/login');
        }
    }

    public function showLogin(): void
    {
        if (Session::isAdminLoggedIn()) {
            View::redirect('/admin');
        }
        View::render('admin/login');
    }

    public function login(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::flash('error', 'Invalid security token.');
            View::redirect('/admin/login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            Session::flash('error', 'Please enter both username and password.');
            View::redirect('/admin/login');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && Security::verifyPassword($password, $admin['password_hash'])) {
            Session::setAdmin([
                'id' => $admin['id'],
                'username' => $admin['username'],
                'email' => $admin['email'],
            ]);
            Session::flash('success', "Welcome back, {$admin['username']}!");
            View::redirect('/admin');
        }

        Session::flash('error', 'Invalid username or password.');
        View::redirect('/admin/login');
    }

    public function logout(): void
    {
        Session::destroy();
        View::redirect('/admin/login');
    }
}
