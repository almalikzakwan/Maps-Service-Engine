<?php

declare(strict_types=1);

class Router
{
    private array $routes = [];
    private string $basePath = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function loadRoutes(string $routeFile): void
    {
        $routes = require $routeFile;
        $this->routes = array_merge($this->routes, $routes);
    }

    public function addRoute(string $method, string $path, string $handler): void
    {
        $key = strtoupper($method) . ' ' . $path;
        $this->routes[$key] = $handler;
    }

    public function resolve(): array
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove base path if set
        if ($this->basePath && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        $uri = rtrim($uri, '/') ?: '/';

        // Try exact match first
        $routeKey = $method . ' ' . $uri;
        if (isset($this->routes[$routeKey])) {
            return $this->parseHandler($this->routes[$routeKey], []);
        }

        // Try pattern matching
        foreach ($this->routes as $route => $handler) {
            if (strpos($route, $method . ' ') === 0) {
                $pattern = substr($route, strlen($method) + 1);
                $params = $this->matchRoute($pattern, $uri);

                if ($params !== false) {
                    return $this->parseHandler($handler, $params);
                }
            }
        }

        throw new Exception('Route not found', 404);
    }

    private function matchRoute(string $pattern, string $uri): array|false
    {
        // Convert route pattern to regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches); // Remove full match

            // Extract parameter names
            preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
            $paramNames = $paramNames[1];

            $params = [];
            foreach ($paramNames as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }

            return $params;
        }

        return false;
    }

    private function parseHandler(string $handler, array $params): array
    {
        if (strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler, 2);
            return [
                'controller' => $controller,
                'method' => $method,
                'params' => $params
            ];
        }

        return [
            'controller' => null,
            'method' => $handler,
            'params' => $params
        ];
    }

    public function dispatch(array $route): void
    {
        try {
            if ($route['controller']) {
                $controllerFile = __DIR__ . '/controllers/' . $route['controller'] . '.php';

                if (!file_exists($controllerFile)) {
                    throw new Exception("Controller file not found: {$route['controller']}", 404);
                }

                require_once $controllerFile;

                if (!class_exists($route['controller'])) {
                    throw new Exception("Controller class not found: {$route['controller']}", 500);
                }

                $controller = new $route['controller']();

                if (!method_exists($controller, $route['method'])) {
                    throw new Exception("Method not found: {$route['method']}", 500);
                }

                $controller->{$route['method']}($route['params']);
            } else {
                // Direct function call
                if (function_exists($route['method'])) {
                    $route['method']($route['params']);
                } else {
                    throw new Exception("Function not found: {$route['method']}", 500);
                }
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    private function handleError(Exception $e): void
    {
        $statusCode = $e->getCode() ?: 500;
        http_response_code($statusCode);

        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'code' => $statusCode
        ]);
    }
}