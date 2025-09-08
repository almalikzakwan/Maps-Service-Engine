<?php
// classes/Map.php
declare(strict_types=1);

class Map
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getTile(int $x, int $y, int $z): string
    {
        // Check if x,y,z are invalid
        if ($x < 0 || $y < 0 || $z < 0) {
            return "Invalid tile coordinates.";
        }

        // Determine tile URL based on configuration
        if ($this->config['tile_type'] === 'vector') {
            $tileUrl = $this->getVectorTileUrl($x, $y, $z);
        } else {
            $tileUrl = "https://tile.openstreetmap.org/$z/$x/$y.png";
        }

        return $tileUrl;
    }

    public function getVectorTileUrl(int $x, int $y, int $z): string
    {
        $baseUrl = $this->config['vector_tile_source'];
        $apiKey = $this->config['maptiler_api_key'] ?? '';

        // Replace placeholders
        $url = str_replace(['{z}', '{x}', '{y}'], [$z, $x, $y], $baseUrl);

        // Add API key if provided
        if ($apiKey) {
            $url .= '?key=' . $apiKey;
        }

        return $url;
    }

    public function getVectorTileData(int $x, int $y, int $z): ?string
    {
        if ($x < 0 || $y < 0 || $z < 0) {
            throw new InvalidArgumentException("Invalid tile coordinates.");
        }

        // Check cache first
        if ($this->config['cache_vector_tiles'] ?? true) {
            $cachedTile = $this->getCachedTile($x, $y, $z, 'pbf');
            if ($cachedTile !== null) {
                return $cachedTile;
            }
        }

        $tileUrl = $this->getVectorTileUrl($x, $y, $z);

        // Use cURL to fetch the vector tile
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $tileUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Maps-Service-Engine/2.0-Vector',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/x-protobuf',
                'Accept-Encoding: gzip, deflate'
            ]
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode !== 200 || $data === false) {
            return null;
        }

        // Cache the tile
        if ($this->config['cache_vector_tiles'] ?? true) {
            $this->cacheTile($x, $y, $z, $data, 'pbf');
        }

        return $data;
    }

    public function getRasterTileData(int $x, int $y, int $z): ?string
    {
        // Fallback to raster tiles
        if ($x < 0 || $y < 0 || $z < 0) {
            throw new InvalidArgumentException("Invalid tile coordinates.");
        }

        $tileUrl = "https://tile.openstreetmap.org/$z/$x/$y.png";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $tileUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Maps-Service-Engine/2.0',
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $data === false) {
            return null;
        }

        return $data;
    }

    public function getTileInfo(int $x, int $y, int $z): array
    {
        $bounds = $this->getTileBounds($x, $y, $z);
        $tileType = $this->config['tile_type'] ?? 'raster';

        $info = [
            'x' => $x,
            'y' => $y,
            'z' => $z,
            'bounds' => $bounds,
            'size' => 256, // Standard tile size
            'type' => $tileType
        ];

        if ($tileType === 'vector') {
            $info['url'] = $this->getVectorTileUrl($x, $y, $z);
            $info['format'] = 'pbf';
            $info['layers'] = $this->getVectorTileLayers();
        } else {
            $info['url'] = "https://tile.openstreetmap.org/$z/$x/$y.png";
            $info['format'] = 'png';
        }

        return $info;
    }

    public function getVectorTileLayers(): array
    {
        // OpenMapTiles standard layers
        return [
            'water',
            'waterway',
            'landcover',
            'landuse',
            'mountain_peak',
            'park',
            'boundary',
            'aeroway',
            'transportation',
            'building',
            'water_name',
            'transportation_name',
            'place',
            'housenumber',
            'poi'
        ];
    }

    public function getStyleJson(): array
    {
        // Return MapLibre/Mapbox GL JS compatible style
        $styleUrl = $this->config['style_json_url'] ?? null;

        if ($styleUrl) {
            // Fetch remote style
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $styleUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json'
                ]
            ]);

            $styleData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $styleData) {
                return json_decode($styleData, true) ?? $this->getDefaultStyle();
            }
        }

        return $this->getDefaultStyle();
    }

    private function getDefaultStyle(): array
    {
        // Basic style for vector tiles
        return [
            "version" => 8,
            "name" => "Maps Service Engine Style",
            "sources" => [
                "openmaptiles" => [
                    "type" => "vector",
                    "url" => "/tiles.json"
                ]
            ],
            "layers" => [
                [
                    "id" => "background",
                    "type" => "background",
                    "paint" => ["background-color" => "#f8f8f8"]
                ],
                [
                    "id" => "water",
                    "type" => "fill",
                    "source" => "openmaptiles",
                    "source-layer" => "water",
                    "paint" => ["fill-color" => "#a0c8f0"]
                ],
                [
                    "id" => "roads",
                    "type" => "line",
                    "source" => "openmaptiles",
                    "source-layer" => "transportation",
                    "paint" => [
                        "line-color" => "#ffffff",
                        "line-width" => ["interpolate", ["linear"], ["zoom"], 8, 1, 14, 4]
                    ]
                ],
                [
                    "id" => "buildings",
                    "type" => "fill",
                    "source" => "openmaptiles",
                    "source-layer" => "building",
                    "paint" => [
                        "fill-color" => "#e0e0e0",
                        "fill-outline-color" => "#cccccc"
                    ]
                ]
            ]
        ];
    }

    private function getCachedTile(int $x, int $y, int $z, string $format): ?string
    {
        $cacheDir = $this->config['tile_cache_dir'] ?? '/tmp/tile_cache/';
        $cachePath = $cacheDir . "vector/$z/$x/$y.$format";

        if (file_exists($cachePath)) {
            $cacheTime = filemtime($cachePath);
            $ttl = $this->config['cache_ttl'] ?? 86400;

            if (time() - $cacheTime < $ttl) {
                return file_get_contents($cachePath);
            }
        }

        return null;
    }

    private function cacheTile(int $x, int $y, int $z, string $data, string $format): void
    {
        $cacheDir = $this->config['tile_cache_dir'] ?? '/tmp/tile_cache/';
        $cachePath = $cacheDir . "vector/$z/$x/";

        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        file_put_contents($cachePath . "$y.$format", $data);
    }

    // Keep existing coordinate conversion methods...
    public function convertCoordinatesToTiles(float $lat, float $lon, int $zoom): array
    {
        $latRad = deg2rad($lat);
        $n = pow(2, $zoom);

        $x = floor(($lon + 180.0) / 360.0 * $n);
        $y = floor((1.0 - asinh(tan($latRad)) / M_PI) / 2.0 * $n);

        return [
            'x' => (int) $x,
            'y' => (int) $y,
            'z' => $zoom
        ];
    }

    public function convertTilesToCoordinates(int $x, int $y, int $z): array
    {
        $lat = $this->tile2lat($y, $z);
        $lng = $this->tile2long($x, $z);

        return [
            'lat' => $lat,
            'lng' => $lng
        ];
    }

    private function tile2lat(int $y, int $z): float
    {
        $n = M_PI - 2.0 * M_PI * $y / pow(2, $z);
        return rad2deg(atan(0.5 * (exp($n) - exp(-$n))));
    }

    private function tile2long(int $x, int $z): float
    {
        return $x / pow(2, $z) * 360.0 - 180.0;
    }

    public function getTileBounds(int $x, int $y, int $z): array
    {
        $north = $this->tile2lat($y, $z);
        $south = $this->tile2lat($y + 1, $z);
        $west = $this->tile2long($x, $z);
        $east = $this->tile2long($x + 1, $z);

        return [
            'north' => $north,
            'south' => $south,
            'east' => $east,
            'west' => $west,
            'center' => [
                'lat' => ($north + $south) / 2,
                'lng' => ($east + $west) / 2
            ]
        ];
    }

    public function isValidTile(int $x, int $y, int $z): bool
    {
        if ($x < 0 || $y < 0 || $z < 0) {
            return false;
        }

        $maxZoom = $this->config['max_zoom'] ?? 18;
        if ($z > $maxZoom) {
            return false;
        }

        $maxTile = pow(2, $z) - 1;
        return $x <= $maxTile && $y <= $maxTile;
    }
}