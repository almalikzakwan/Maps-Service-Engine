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

        $tileType = $this->config['tile_type'] ?? 'raster';

        try {
            if ($tileType === 'vector') {
                $this->serveVectorTile($x, $y, $z);
            } else {
                $this->serveRasterTile($x, $y, $z);
            }
        } catch (Exception $e) {
            $this->errorResponse('Error fetching tile: ' . $e->getMessage(), 500);
        }
    }

    private function serveVectorTile(int $x, int $y, int $z): void
    {
        $tileData = $this->map->getVectorTileData($x, $y, $z);

        if ($tileData === null) {
            $this->errorResponse('Vector tile not found', 404);
            return;
        }

        // Set appropriate headers for vector tile response
        header('Content-Type: application/x-protobuf');
        header('Content-Encoding: gzip'); // Vector tiles are usually gzipped
        header('Cache-Control: public, max-age=86400'); // Cache for 24 hours
        header('Content-Length: ' . strlen($tileData));
        header('Access-Control-Allow-Origin: *');

        echo $tileData;
    }

    private function serveRasterTile(int $x, int $y, int $z): void
    {
        $tileData = $this->map->getRasterTileData($x, $y, $z);

        if ($tileData === null) {
            $this->errorResponse('Raster tile not found', 404);
            return;
        }

        // Set appropriate headers for raster tile response
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
        header('Content-Length: ' . strlen($tileData));

        echo $tileData;
    }

    public function getTileInfo(array $params): void
    {
        $x = (int) ($params['x'] ?? 0);
        $y = (int) ($params['y'] ?? 0);
        $z = (int) ($params['z'] ?? 0);

        if (!$this->validateCoordinates($x, $y, $z)) {
            return;
        }

        $tileInfo = $this->map->getTileInfo($x, $y, $z);
        $this->successResponse($tileInfo);
    }

    public function getTileBounds(array $params): void
    {
        $x = (int) ($params['x'] ?? 0);
        $y = (int) ($params['y'] ?? 0);
        $z = (int) ($params['z'] ?? 0);

        if (!$this->validateCoordinates($x, $y, $z)) {
            return;
        }

        $bounds = $this->map->getTileBounds($x, $y, $z);
        $this->successResponse($bounds);
    }

    public function getTilesJson(array $params): void
    {
        // TileJSON specification for vector tiles
        $baseUrl = $this->getBaseUrl();

        $tilesJson = [
            "tilejson" => "3.0.0",
            "name" => "Maps Service Engine Vector Tiles",
            "description" => "Vector tiles powered by OpenMapTiles",
            "version" => "1.0.0",
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
            "vector_layers" => $this->getVectorLayerMetadata()
        ];

        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=3600');
        echo json_encode($tilesJson, JSON_PRETTY_PRINT);
    }

    public function getStyle(array $params): void
    {
        // Serve MapLibre/Mapbox GL JS style
        $style = $this->map->getStyleJson();

        // Update sources to point to this server
        $baseUrl = $this->getBaseUrl();
        if (isset($style['sources']['openmaptiles'])) {
            $style['sources']['openmaptiles']['url'] = $baseUrl . '/tiles.json';
        }

        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=3600');
        echo json_encode($style, JSON_PRETTY_PRINT);
    }

    private function getVectorLayerMetadata(): array
    {
        // OpenMapTiles standard vector layers with metadata
        return [
            [
                "id" => "water",
                "description" => "Water polygons",
                "minzoom" => 0,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String",
                    "intermittent" => "Number"
                ]
            ],
            [
                "id" => "waterway",
                "description" => "Waterway lines",
                "minzoom" => 4,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String",
                    "name" => "String",
                    "name_en" => "String"
                ]
            ],
            [
                "id" => "landcover",
                "description" => "Landcover polygons",
                "minzoom" => 0,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String",
                    "subclass" => "String"
                ]
            ],
            [
                "id" => "landuse",
                "description" => "Landuse polygons",
                "minzoom" => 5,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String"
                ]
            ],
            [
                "id" => "transportation",
                "description" => "Transportation lines",
                "minzoom" => 0,
                "maxzoom" => 14,
                "fields" => [
                    "class" => "String",
                    "subclass" => "String",
                    "layer" => "Number",
                    "level" => "Number"
                ]
            ],
            [
                "id" => "building",
                "description" => "Building polygons",
                "minzoom" => 13,
                "maxzoom" => 14,
                "fields" => [
                    "colour" => "String",
                    "hide_3d" => "Boolean"
                ]
            ],
            [
                "id" => "transportation_name",
                "description" => "Transportation names",
                "minzoom" => 6,
                "maxzoom" => 14,
                "fields" => [
                    "name" => "String",
                    "name_en" => "String",
                    "class" => "String",
                    "subclass" => "String"
                ]
            ],
            [
                "id" => "place",
                "description" => "Place names",
                "minzoom" => 0,
                "maxzoom" => 14,
                "fields" => [
                    "name" => "String",
                    "name_en" => "String",
                    "class" => "String",
                    "rank" => "Number"
                ]
            ],
            [
                "id" => "poi",
                "description" => "Points of interest",
                "minzoom" => 12,
                "maxzoom" => 14,
                "fields" => [
                    "name" => "String",
                    "name_en" => "String",
                    "class" => "String",
                    "subclass" => "String",
                    "layer" => "Number",
                    "level" => "Number",
                    "indoor" => "Boolean"
                ]
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