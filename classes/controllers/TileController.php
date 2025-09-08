<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Map.php';

class TileController extends BaseController
{
    private Map $map;

    public function __construct()
    {
        parent::__construct();
        $this->map = new Map($this->config);
    }

    public function getTile(array $params): void
    {
        $x = (int) ($params['x'] ?? 0);
        $y = (int) ($params['y'] ?? 0);
        $z = (int) ($params['z'] ?? 0);

        if (!$this->validateCoordinates($x, $y, $z)) {
            return;
        }

        try {
            $this->serveVectorTile($x, $y, $z);
        } catch (Exception $e) {
            error_log("TileController error: " . $e->getMessage());
            $this->errorResponse('Error fetching vector tile: ' . $e->getMessage(), 500);
        }
    }

    private function serveVectorTile(int $x, int $y, int $z): void
    {
        $tileData = $this->map->getVectorTileData($x, $y, $z);

        if ($tileData === null) {
            $this->errorResponse('Vector tile not found', 404);
            return;
        }

        // Detect if tile is gzipped
        $isGzipped = $this->isGzipEncoded($tileData);

        // Set appropriate headers for vector tile response
        header('Content-Type: application/x-protobuf');

        if ($isGzipped) {
            header('Content-Encoding: gzip');
        }

        header('Cache-Control: public, max-age=86400'); // Cache for 24 hours
        header('Content-Length: ' . strlen($tileData));
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: *');

        // Add vector tile specific headers
        header('X-Tile-Format: pbf');
        header('X-Tile-Type: vector');
        header('X-Tile-Coordinates: ' . "$z/$x/$y");

        echo $tileData;
    }

    private function isGzipEncoded(string $data): bool
    {
        // Check for gzip magic number (1f 8b)
        return strlen($data) >= 2 && ord($data[0]) === 0x1f && ord($data[1]) === 0x8b;
    }

    public function getTileInfo(array $params): void
    {
        $x = (int) ($params['x'] ?? 0);
        $y = (int) ($params['y'] ?? 0);
        $z = (int) ($params['z'] ?? 0);

        if (!$this->validateCoordinates($x, $y, $z)) {
            return;
        }

        try {
            $tileInfo = $this->map->getTileInfo($x, $y, $z);
            $this->successResponse($tileInfo);
        } catch (Exception $e) {
            $this->errorResponse('Error getting tile info: ' . $e->getMessage(), 500);
        }
    }

    public function getTilesJson(array $params): void
    {
        // TileJSON specification for vector tiles
        $baseUrl = $this->getBaseUrl();

        $tilesJson = [
            "tilejson" => "3.0.0",
            "name" => "Vector Maps Service Engine",
            "description" => "Vector tiles powered by OpenMapTiles via MapTiler",
            "version" => "3.0.0",
            "attribution" => "© OpenMapTiles © OpenStreetMap contributors",
            "scheme" => "xyz",
            "tiles" => [
                $baseUrl . "/tiles/{z}/{x}/{y}.pbf"
            ],
            "minzoom" => $this->config['min_zoom'] ?? 0,
            "maxzoom" => $this->config['max_zoom'] ?? 14,
            "bounds" => [-180, -85.0511, 180, 85.0511],
            "center" => [103.146, 5.329, 10], // Default to Malaysia
            "format" => "pbf",
            "type" => "vector",
            "vector_layers" => $this->getVectorLayerMetadata(),
            "fillzoom" => $this->config['max_zoom'] ?? 14
        ];

        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=3600');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($tilesJson, JSON_PRETTY_PRINT);
    }

    public function getStyle(array $params): void
    {
        try {
            // Serve MapLibre/Mapbox GL JS style
            $style = $this->map->getStyleJson();

            // Ensure sources point to this server
            $baseUrl = $this->getBaseUrl();

            // Update all vector sources to use our tile server
            if (isset($style['sources'])) {
                foreach ($style['sources'] as $sourceId => &$source) {
                    if (isset($source['type']) && $source['type'] === 'vector') {
                        $source['tiles'] = [$baseUrl . '/tiles/{z}/{x}/{y}.pbf'];
                        $source['url'] = $baseUrl . '/tiles.json';
                    }
                }
            }

            header('Content-Type: application/json');
            header('Cache-Control: public, max-age=3600');
            header('Access-Control-Allow-Origin: *');
            echo json_encode($style, JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            $this->errorResponse('Error getting style: ' . $e->getMessage(), 500);
        }
    }

    private function getVectorLayerMetadata(): array
    {
        // OpenMapTiles standard vector layers with detailed metadata
        return [
            [
                "id" => "water",
                "description" => "Water polygons including seas, lakes and rivers",
                "minzoom" => 0,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String",
                    "intermittent" => "Number",
                    "brunnel" => "String"
                ]
            ],
            [
                "id" => "waterway",
                "description" => "Waterway lines including rivers, streams and canals",
                "minzoom" => 4,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String",
                    "name" => "String",
                    "name_en" => "String",
                    "brunnel" => "String",
                    "intermittent" => "Number"
                ]
            ],
            [
                "id" => "landcover",
                "description" => "Landcover polygons from OpenStreetMap",
                "minzoom" => 0,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String",
                    "subclass" => "String"
                ]
            ],
            [
                "id" => "landuse",
                "description" => "Landuse polygons from OpenStreetMap",
                "minzoom" => 5,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String"
                ]
            ],
            [
                "id" => "transportation",
                "description" => "Transportation lines including roads, railways and ferries",
                "minzoom" => 0,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String",
                    "subclass" => "String",
                    "layer" => "Number",
                    "level" => "Number",
                    "brunnel" => "String",
                    "ramp" => "Number",
                    "surface" => "String"
                ]
            ],
            [
                "id" => "building",
                "description" => "Building polygons from OpenStreetMap",
                "minzoom" => 13,
                "maxzoom" => 14,
                "fields" => [
                    "colour" => "String",
                    "hide_3d" => "Boolean",
                    "render_height" => "Number",
                    "render_min_height" => "Number"
                ]
            ],
            [
                "id" => "transportation_name",
                "description" => "Transportation names for labeling",
                "minzoom" => 6,
                "maxzoom" => 14,
                "fields" => [
                    "name" => "String",
                    "name_en" => "String",
                    "name_de" => "String",
                    "class" => "String",
                    "subclass" => "String",
                    "ref" => "String",
                    "ref_length" => "Number",
                    "network" => "String"
                ]
            ],
            [
                "id" => "place",
                "description" => "Place names including countries, states, cities and towns",
                "minzoom" => 0,
                "maxzoom" => 14,
                "fields" => [
                    "name" => "String",
                    "name_en" => "String",
                    "name_de" => "String",
                    "class" => "String",
                    "rank" => "Number",
                    "capital" => "Number"
                ]
            ],
            [
                "id" => "poi",
                "description" => "Points of interest from OpenStreetMap",
                "minzoom" => 12,
                "maxzoom" => 14,
                "fields" => [
                    "name" => "String",
                    "name_en" => "String",
                    "name_de" => "String",
                    "class" => "String",
                    "subclass" => "String",
                    "layer" => "Number",
                    "level" => "Number",
                    "indoor" => "Boolean"
                ]
            ],
            [
                "id" => "boundary",
                "description" => "Administrative boundaries",
                "minzoom" => 0,
                "maxzoom" => 14,
                "fields" => [
                    "admin_level" => "Number",
                    "disputed" => "Number",
                    "maritime" => "Number"
                ]
            ],
            [
                "id" => "aeroway",
                "description" => "Airport related features",
                "minzoom" => 10,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String",
                    "ref" => "String"
                ]
            ]
        ];
    }

    protected function validateCoordinates(int $x, int $y, int $z): bool
    {
        if ($x < 0 || $y < 0 || $z < 0) {
            $this->errorResponse('Invalid coordinates: x, y, z must be non-negative integers');
            return false;
        }

        $maxZoom = $this->config['max_zoom'] ?? 14;
        $minZoom = $this->config['min_zoom'] ?? 0;

        if ($z > $maxZoom || $z < $minZoom) {
            $this->errorResponse("Invalid zoom level: z must be between {$minZoom} and {$maxZoom}");
            return false;
        }

        $maxTile = pow(2, $z) - 1;
        if ($x > $maxTile || $y > $maxTile) {
            $this->errorResponse("Invalid tile coordinates for zoom level {$z}: max tile coordinate is {$maxTile}");
            return false;
        }

        return true;
    }

    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $port = $_SERVER['SERVER_PORT'] ?? '';

        // Don't include port for standard ports
        if (($protocol === 'https' && $port === '443') || ($protocol === 'http' && $port === '80')) {
            $port = '';
        } elseif ($port) {
            $port = ':' . $port;
        }

        return $protocol . '://' . $host . $port;
    }
}