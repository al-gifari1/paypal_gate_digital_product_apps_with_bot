<?php
declare(strict_types=1);

/**
 * Global Application Configuration & Path Constants
 */

define('APP_ROOT', dirname(__DIR__));
define('STORAGE_PATH', APP_ROOT . '/storage');
define('UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('DATABASE_PATH', APP_ROOT . '/database/database.sqlite');
define('APP_VERSION', trim((string)@file_get_contents(APP_ROOT . '/VERSION') ?: '1.0.0'));

// Ensure storage directories exist
if (!is_dir(STORAGE_PATH)) {
    mkdir(STORAGE_PATH, 0755, true);
}
if (!is_dir(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}
if (!is_dir(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0755, true);
}

// Auto-loader for App namespace
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));

    if (str_starts_with($relativeClass, 'Database\\')) {
        $dbFile = APP_ROOT . '/database/' . str_replace('\\', '/', substr($relativeClass, 9)) . '.php';
        if (file_exists($dbFile)) {
            require_once $dbFile;
            return;
        }
    }

    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

if (!function_exists('url')) {
    function url(string $path = ''): string {
        return \App\Services\SettingsService::url($path);
    }
}
