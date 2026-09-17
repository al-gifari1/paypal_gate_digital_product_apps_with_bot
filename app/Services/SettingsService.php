<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use PDO;

class SettingsService
{
    private static ?array $cached = null;

    public static function getAll(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT key, value FROM settings');
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['key']] = $row['value'];
        }

        self::$cached = $result;
        return $result;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::getAll();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO settings (key, value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP');
        $stmt->execute([$key, (string)$value]);

        if (self::$cached !== null) {
            self::$cached[$key] = (string)$value;
        }
    }

    public static function updateMany(array $data): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO settings (key, value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP');
            foreach ($data as $k => $v) {
                $stmt->execute([$k, (string)$v]);
            }
            $pdo->commit();
            self::$cached = null; // reset cache
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getPaypalMode(): string
    {
        return self::get('paypal_mode', 'sandbox') === 'live' ? 'live' : 'sandbox';
    }

    public static function getPaypalBaseUrl(): string
    {
        return self::getPaypalMode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public static function getPaypalClientId(): string
    {
        return (string)self::get('paypal_client_id', '');
    }

    public static function getPaypalClientSecret(): string
    {
        return (string)self::get('paypal_client_secret', '');
    }

    public static function getTelegramBotToken(): string
    {
        return (string)self::get('telegram_bot_token', '');
    }

    public static function getTelegramAdminChatId(): string
    {
        return (string)self::get('telegram_admin_chat_id', '');
    }

    public static function getAppUrl(): string
    {
        $url = (string)self::get('app_url', '');
        if (empty($url)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
            $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            return "$protocol://$host$scriptDir";
        }
        return rtrim($url, '/');
    }

    public static function url(string $path = ''): string
    {
        $base = rtrim(self::getAppUrl(), '/');
        $cleanPath = ltrim($path, '/');
        return $cleanPath === '' ? $base : "$base/$cleanPath";
    }

    public static function getCurrency(): string
    {
        return strtoupper((string)self::get('currency', 'USD'));
    }

    public static function getApiKey(): string
    {
        $key = (string)self::get('api_secret_key', '');
        if (empty($key)) {
            $key = self::regenerateApiKey();
        }
        return $key;
    }

    public static function regenerateApiKey(): string
    {
        $newKey = 'sk_live_' . bin2hex(random_bytes(32));
        self::set('api_secret_key', $newKey);
        return $newKey;
    }
}
