<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dbPath = DATABASE_PATH;
            $dbDir = dirname($dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            $isNew = !file_exists($dbPath);

            try {
                self::$instance = new PDO('sqlite:' . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                // Performance & Integrity settings
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                self::$instance->exec('PRAGMA journal_mode = WAL;');
                self::$instance->exec('PRAGMA synchronous = NORMAL;');

                // Run schema migration if database is newly created or tables are missing
                self::ensureSchema();

            } catch (PDOException $e) {
                throw new RuntimeException('Database Connection Failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return self::$instance;
    }

    public static function ensureSchema(): void
    {
        $pdo = self::$instance;
        $schemaFile = APP_ROOT . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = (string)file_get_contents($schemaFile);
            $pdo->exec($sql);
        }

        // Check if default admin exists
        $stmt = $pdo->query('SELECT COUNT(*) FROM admins');
        if ($stmt && (int)$stmt->fetchColumn() === 0) {
            // Default admin: admin / admin123!
            $defaultHash = password_hash('admin123!', PASSWORD_BCRYPT);
            $insert = $pdo->prepare('INSERT INTO admins (username, password_hash, email) VALUES (?, ?, ?)');
            $insert->execute(['admin', $defaultHash, 'admin@example.com']);
        }

        // Check default settings
        $settingsDefaults = [
            'site_title' => 'Digital Vault & Bot',
            'currency' => 'USD',
            'paypal_mode' => 'sandbox',
            'paypal_client_id' => '',
            'paypal_client_secret' => '',
            'paypal_webhook_id' => '',
            'telegram_bot_token' => '',
            'telegram_admin_chat_id' => '',
            'app_url' => 'http://localhost:8080',
        ];

        foreach ($settingsDefaults as $key => $val) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE key = ?');
            $check->execute([$key]);
            if ((int)$check->fetchColumn() === 0) {
                $ins = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
                $ins->execute([$key, $val]);
            }
        }
    }
}
