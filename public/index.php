<?php
// public/index.php
declare(strict_types=1);

// Enable CORS for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error handling
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($exception) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => 'Internal Server Error: ' . $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
});

// Load required files
require_once __DIR__ . '/../classes/Router.php';

try {
    // Initialize router
    $router = new Router();

    // Load routes configuration
    $router->loadRoutes(__DIR__ . '/../config/routes.php');

    // Resolve current request
    $route = $router->resolve();

    // Dispatch to appropriate controller
    $router->dispatch($route);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    header('Content-Type: application/json');

    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}