<?php
declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionPath = defined('STORAGE_PATH') ? STORAGE_PATH . '/sessions' : sys_get_temp_dir();
            if (!is_dir($sessionPath)) {
                @mkdir($sessionPath, 0700, true);
            }
            if (is_dir($sessionPath) && is_writable($sessionPath)) {
                session_save_path($sessionPath);
            }

            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        self::start();
        if ($message !== null) {
            $_SESSION['__flash'][$key] = $message;
            return null;
        }

        $val = $_SESSION['__flash'][$key] ?? null;
        unset($_SESSION['__flash'][$key]);
        return $val;
    }

    public static function getCsrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        self::start();
        $stored = $_SESSION['csrf_token'] ?? '';
        if (empty($stored) || empty($token)) {
            return false;
        }
        return hash_equals((string)$stored, (string)$token);
    }

    public static function setAdmin(array $adminData): void
    {
        self::start();
        $_SESSION['admin_auth'] = $adminData;
        session_regenerate_id(true);
    }

    public static function getAdmin(): ?array
    {
        self::start();
        return $_SESSION['admin_auth'] ?? null;
    }

    public static function isAdminLoggedIn(): bool
    {
        self::start();
        return !empty($_SESSION['admin_auth']['id']);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
