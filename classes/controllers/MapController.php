<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Map.php';

class MapController extends BaseController
{
    private Map $map;

    public function __construct()
    {
        parent::__construct();
        $this->map = new Map($this->config);
    }

    public function vectorMap(): void
    {
        // Render the vector map interface
        $this->view('maps.vector', [
            'title' => 'Vector Maps Service Engine',
            'description' => 'Interactive vector maps with GeoJSON generation capabilities',
            'config' => [
                'max_zoom' => $this->config['max_zoom'] ?? 14,
                'min_zoom' => $this->config['min_zoom'] ?? 0,
                'tile_size' => $this->config['tile_size'] ?? 512,
                'center' => [103.146, 5.329], // Malaysia
                'default_zoom' => 10
            ]
        ]);
    }

    public function index(): void
    {
        // Redirect to vector map (since we only support vector now)
        $this->vectorMap();
    }
}