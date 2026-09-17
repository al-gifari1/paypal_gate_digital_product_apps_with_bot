<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class View
{
    public static function render(string $viewPath, array $data = [], ?string $layout = null): void
    {
        $viewFile = APP_ROOT . '/views/' . ltrim($viewPath, '/') . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View file not found: $viewFile");
        }

        // Extract variables into local scope safely
        extract($data, EXTR_SKIP);

        if ($layout !== null) {
            $layoutFile = APP_ROOT . '/views/' . ltrim($layout, '/') . '.php';
            if (!file_exists($layoutFile)) {
                throw new RuntimeException("Layout file not found: $layoutFile");
            }

            ob_start();
            require $viewFile;
            $content = ob_get_clean();

            require $layoutFile;
        } else {
            require $viewFile;
        }
    }

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url): void
    {
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            $url = \App\Services\SettingsService::url($url);
        }
        header("Location: $url");
        exit;
    }
}
