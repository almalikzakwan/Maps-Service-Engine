<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Map.php';

class InfoController extends BaseController
{
    public function index(): void
    {
        // Instead of echoing plain text, return a proper view
        $this->view('welcome', [
            'title' => 'Maps Service Engine API',
            'version' => '1.0',
            'routes' => $this->getAvailableRoutes()
        ]);
    }

    public function dashboard(): void
    {
        $this->view('dashboard', [
            'title' => 'Maps Dashboard',
            'stats' => [
                'total_requests' => 1234,
                'cache_hit_rate' => '85%',
                'uptime' => '99.9%'
            ]
        ]);
    }

    private function getAvailableRoutes(): array
    {
        return [
            'Tile Routes' => [
                'GET /tiles/{z}/{x}/{y}' => 'Get tile image',
                'GET /tiles/{z}/{x}/{y}/info' => 'Get tile information'
            ],
            'Coordinate Routes' => [
                'GET /convert/latlng-to-tile' => 'Convert coordinates to tile',
                'GET /convert/tile-to-latlng' => 'Convert tile to coordinates'
            ]
        ];
    }
}