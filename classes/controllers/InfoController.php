<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Map.php';

class InfoController extends BaseController
{
    public function index(): void
    {
        // Welcome page with vector-only information
        $this->view('welcome', [
            'title' => 'Vector Maps Service Engine',
            'version' => '3.0.0',
            'type' => 'Vector Tiles Only',
            'routes' => $this->getAvailableRoutes(),
            'features' => [
                'Vector Tiles Only' => 'PBF/MVT format support',
                'Interactive Maps' => 'MapLibre GL JS powered interface',
                'GeoJSON Generation' => 'Realistic Malaysian geographic features',
                'Coordinate Conversion' => 'Lat/lng to tile coordinate conversion',
                'TileJSON Support' => 'Standard vector tile metadata',
                'Style JSON' => 'MapLibre/Mapbox GL compatible styles'
            ],
            'endpoints' => [
                'tiles' => '/tiles/{z}/{x}/{y}.pbf',
                'metadata' => '/tiles.json',
                'style' => '/style.json',   
                'interface' => '/maps'
            ]
        ]);
    }

    public function health(): void
    {
        $this->successResponse([
            'status' => 'healthy',
            'service' => 'Vector Maps Service Engine',
            'version' => '3.0.0',
            'type' => 'vector-only',
            'timestamp' => date('c'),
            'endpoints' => [
                'vector_tiles' => 'active',
                'geojson_generation' => 'active',
                'coordinate_conversion' => 'active'
            ]
        ]);
    }

    public function apiInfo(): void
    {
        $baseUrl = $this->getBaseUrl();

        $this->successResponse([
            'service' => 'Vector Maps Service Engine',
            'version' => '3.0.0',
            'description' => 'Vector tile service with GeoJSON generation capabilities',
            'tile_format' => 'pbf/mvt',
            'max_zoom' => 14,
            'base_url' => $baseUrl,
            'endpoints' => [
                'vector_tiles' => $baseUrl . '/tiles/{z}/{x}/{y}.pbf',
                'tile_metadata' => $baseUrl . '/tiles.json',
                'map_style' => $baseUrl . '/style.json',
                'coordinate_convert' => $baseUrl . '/convert/latlng-to-tile',
                'geojson_generate' => $baseUrl . '/geo/generate/layers',
                'interactive_map' => $baseUrl . '/maps'
            ],
            'supported_operations' => [
                'Vector tile serving',
                'Coordinate system conversion',
                'GeoJSON feature generation',
                'Interactive map interface',
                'Layer management'
            ]
        ]);
    }

    private function getAvailableRoutes(): array
    {
        return [
            'Vector Tile Routes' => [
                'GET /tiles/{z}/{x}/{y}.pbf' => 'Get vector tile (PBF format)',
                'GET /tiles/{z}/{x}/{y}.mvt' => 'Get vector tile (MVT format)',
                'GET /tiles.json' => 'Get TileJSON metadata',
                'GET /style.json' => 'Get MapLibre GL style',
                'GET /tiles/{z}/{x}/{y}/info' => 'Get tile information'
            ],
            'Coordinate Routes' => [
                'GET /convert/latlng-to-tile' => 'Convert coordinates to tile',
                'GET /convert/tile-to-latlng' => 'Convert tile to coordinates'
            ],
            'GeoJSON Routes' => [
                'POST /geo/generate/layers' => 'Generate multiple GeoJSON layers',
                'POST /geo/generate/layer' => 'Generate single GeoJSON layer',
                'GET /geo/stats' => 'Get generation statistics'
            ],
            'Interface Routes' => [
                'GET /maps' => 'Interactive vector map interface',
                'GET /' => 'API welcome page',
                'GET /health' => 'Service health check'
            ]
        ];
    }

    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}