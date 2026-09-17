<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function any(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
        $this->addRoute('POST', $path, $handler);
        $this->addRoute('PUT', $path, $handler);
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        // Convert {param} into regex capture groups
        $pattern = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);

        // Support HTTP Method Override for RESTful clients
        if ($method === 'POST') {
            if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper((string)$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            } elseif (!empty($_POST['_method'])) {
                $method = strtoupper((string)$_POST['_method']);
            } elseif (!empty($_GET['_method'])) {
                $method = strtoupper((string)$_GET['_method']);
            }
        }

        $parsedUri = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Auto-detect and strip base path when hosted in a subfolder (e.g. /abi)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($baseDir !== '' && $baseDir !== '/' && str_starts_with($parsedUri, $baseDir)) {
            $parsedUri = substr($parsedUri, strlen($baseDir));
            if ($parsedUri === '' || $parsedUri === false) {
                $parsedUri = '/';
            }
        }

        $trimmedUri = rtrim($parsedUri, '/');
        if ($trimmedUri === '') {
            $trimmedUri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $trimmedUri, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (!is_int($k)) {
                        $params[$k] = $v;
                    }
                }

                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    $controller->$action($params);
                    return;
                }

                $handler($params);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        if (str_starts_with($trimmedUri, '/api/')) {
            View::json([
                'error' => [
                    'code' => 404,
                    'message' => 'API endpoint not found: ' . $trimmedUri,
                    'status' => 'NOT_FOUND'
                ]
            ], 404);
        } else {
            echo '<!DOCTYPE html><html><head><title>404 Not Found</title><style>body{background:#0b1120;color:#f8fafc;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;flex-direction:column;}a{color:#38bdf8;text-decoration:none;margin-top:16px;}</style></head><body><h1>404 | Page Not Found</h1><p>The requested destination does not exist.</p><a href="/">Return Home</a></body></html>';
        }
    }
}
