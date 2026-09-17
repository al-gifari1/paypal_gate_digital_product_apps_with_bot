<?php
declare(strict_types=1);

namespace App\Core;

class Security
{
    public static function escape(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function generateToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function sanitizeSlug(string $title): string
    {
        $slug = preg_replace('~[^\pL\d]+~u', '-', $title);
        $slug = iconv('utf-8', 'us-ascii//TRANSLIT', (string)$slug);
        $slug = preg_replace('~[^-\w]+~', '', (string)$slug);
        $slug = trim((string)$slug, '-');
        $slug = preg_replace('~-+~', '-', $slug);
        return strtolower($slug ?: 'product-' . time());
    }
}
