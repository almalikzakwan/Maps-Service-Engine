<?php

declare(strict_types=1);
require_once __DIR__ . '/../View.php';

abstract class BaseController
{
    protected array $config;
    protected View $view;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';
        $this->view = new View(__DIR__ . '/../../views/');
    }

    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function errorResponse(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse([
            'error' => true,
            'message' => $message
        ], $statusCode);
    }

    protected function successResponse(array $data = [], string $message = 'Success'): void
    {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function validateRequired(array $data, array $required): bool
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->errorResponse("Missing required field: {$field}");
                return false;
            }
        }
        return true;
    }

    protected function getRequestData(): array
    {
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                return $_GET;
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                return $data ?: $_POST;
            default:
                return [];
        }
    }

    protected function validateCoordinates(int $x, int $y, int $z): bool
    {
        if ($x < 0 || $y < 0 || $z < 0) {
            $this->errorResponse('Invalid coordinates: x, y, z must be non-negative integers');
            return false;
        }

        if ($z > 18) { // OpenStreetMap max zoom is typically 18
            $this->errorResponse('Invalid zoom level: z must be between 0 and 18');
            return false;
        }

        $maxTile = pow(2, $z) - 1;
        if ($x > $maxTile || $y > $maxTile) {
            $this->errorResponse("Invalid tile coordinates for zoom level {$z}");
            return false;
        }

        return true;
    }

    protected function view(string $viewName, array $data = []): void
    {
        try {
            $html = $this->view->render($viewName, $data);
            header('Content-Type: text/html');
            echo $html;
        } catch (Exception $e) {
            $this->errorResponse('View rendering error: ' . $e->getMessage(), 500);
        }
    }

    protected function renderView(string $viewName, array $data = []): string
    {
        return $this->view->render($viewName, $data);
    }
}