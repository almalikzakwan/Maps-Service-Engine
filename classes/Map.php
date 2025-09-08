<?php
// classes/Map.php - Vector Tiles Only
declare(strict_types=1);

class Map
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
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
        if (!$this->isValidTile($x, $y, $z)) {
            throw new InvalidArgumentException("Invalid tile coordinates: x={$x}, y={$y}, z={$z}");
        }

        // Check cache first
        if ($this->config['cache_vector_tiles'] ?? true) {
            $cachedTile = $this->getCachedTile($x, $y, $z);
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
            CURLOPT_USERAGENT => 'Maps-Service-Engine/3.0-Vector-Only',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/x-protobuf',
                'Accept-Encoding: gzip, deflate',
                'User-Agent: Maps-Service-Engine/3.0'
            ]
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $data === false) {
            error_log("Failed to fetch vector tile {$z}/{$x}/{$y}: HTTP {$httpCode}, Error: {$error}");
            return null;
        }

        // Cache the tile
        if ($this->config['cache_vector_tiles'] ?? true) {
            $this->cacheTile($x, $y, $z, $data);
        }

        return $data;
    }

    public function getTileInfo(int $x, int $y, int $z): array
    {
        if (!$this->isValidTile($x, $y, $z)) {
            throw new InvalidArgumentException("Invalid tile coordinates");
        }

        $bounds = $this->getTileBounds($x, $y, $z);

        return [
            'x' => $x,
            'y' => $y,
            'z' => $z,
            'bounds' => $bounds,
            'size' => $this->config['tile_size'] ?? 512,
            'type' => 'vector',
            'url' => $this->getVectorTileUrl($x, $y, $z),
            'format' => 'pbf',
            'layers' => $this->getVectorTileLayers(),
            'source' => 'OpenMapTiles via MapTiler'
        ];
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
            // Fetch remote style with API key
            $url = $styleUrl;
            if (!empty($this->config['maptiler_api_key'])) {
                $url .= '?key=' . $this->config['maptiler_api_key'];
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
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
                $style = json_decode($styleData, true);
                if ($style && is_array($style)) {
                    // Update tile URLs to point to our server
                    $style = $this->updateStyleUrls($style);
                    return $style;
                }
            }
        }

        return $this->getDefaultVectorStyle();
    }

    private function updateStyleUrls(array $style): array
    {
        $baseUrl = $this->getBaseUrl();

        // Update sources to point to our tile server
        if (isset($style['sources'])) {
            foreach ($style['sources'] as $sourceId => &$source) {
                if (isset($source['type']) && $source['type'] === 'vector') {
                    if (isset($source['url'])) {
                        $source['url'] = $baseUrl . '/tiles.json';
                    }
                    if (isset($source['tiles'])) {
                        $source['tiles'] = [$baseUrl . '/tiles/{z}/{x}/{y}.pbf'];
                    }
                }
            }
        }

        return $style;
    }

    private function getDefaultVectorStyle(): array
    {
        $baseUrl = $this->getBaseUrl();

        return [
            "version" => 8,
            "name" => "Vector Maps Service Engine",
            "metadata" => [
                "maputnik:renderer" => "maplibre",
                "description" => "Vector-only tile service"
            ],
            "sources" => [
                "openmaptiles" => [
                    "type" => "vector",
                    "url" => $baseUrl . "/tiles.json"
                ]
            ],
            "sprite" => $baseUrl . "/sprites/basic",
            "glyphs" => $baseUrl . "/fonts/{fontstack}/{range}.pbf",
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
                    "paint" => [
                        "fill-color" => "#a0c8f0",
                        "fill-opacity" => 1
                    ]
                ],
                [
                    "id" => "landcover",
                    "type" => "fill",
                    "source" => "openmaptiles",
                    "source-layer" => "landcover",
                    "filter" => ["==", "class", "grass"],
                    "paint" => ["fill-color" => "#d8e8c8"]
                ],
                [
                    "id" => "landuse-residential",
                    "type" => "fill",
                    "source" => "openmaptiles",
                    "source-layer" => "landuse",
                    "filter" => ["==", "class", "residential"],
                    "paint" => ["fill-color" => "#f0f0f0"]
                ],
                [
                    "id" => "roads-highway",
                    "type" => "line",
                    "source" => "openmaptiles",
                    "source-layer" => "transportation",
                    "filter" => ["==", "class", "motorway"],
                    "paint" => [
                        "line-color" => "#fc8",
                        "line-width" => ["interpolate", ["linear"], ["zoom"], 8, 2, 14, 8]
                    ]
                ],
                [
                    "id" => "roads-primary",
                    "type" => "line",
                    "source" => "openmaptiles",
                    "source-layer" => "transportation",
                    "filter" => ["==", "class", "primary"],
                    "paint" => [
                        "line-color" => "#fea",
                        "line-width" => ["interpolate", ["linear"], ["zoom"], 8, 1, 14, 6]
                    ]
                ],
                [
                    "id" => "roads-secondary",
                    "type" => "line",
                    "source" => "openmaptiles",
                    "source-layer" => "transportation",
                    "filter" => ["in", "class", "secondary", "tertiary"],
                    "paint" => [
                        "line-color" => "#fff",
                        "line-width" => ["interpolate", ["linear"], ["zoom"], 8, 0.5, 14, 4]
                    ]
                ],
                [
                    "id" => "buildings",
                    "type" => "fill",
                    "source" => "openmaptiles",
                    "source-layer" => "building",
                    "minzoom" => 13,
                    "paint" => [
                        "fill-color" => "#e0e0e0",
                        "fill-outline-color" => "#cccccc",
                        "fill-opacity" => 0.8
                    ]
                ],
                [
                    "id" => "place-labels",
                    "type" => "symbol",
                    "source" => "openmaptiles",
                    "source-layer" => "place",
                    "filter" => ["in", "class", "city", "town", "village"],
                    "layout" => [
                        "text-field" => ["get", "name"],
                        "text-font" => ["Open Sans Regular"],
                        "text-size" => 12,
                        "text-transform" => "uppercase"
                    ],
                    "paint" => [
                        "text-color" => "#333333",
                        "text-halo-color" => "#ffffff",
                        "text-halo-width" => 1
                    ]
                ],
                [
                    "id" => "road-labels",
                    "type" => "symbol",
                    "source" => "openmaptiles",
                    "source-layer" => "transportation_name",
                    "filter" => ["in", "class", "motorway", "primary", "secondary"],
                    "layout" => [
                        "text-field" => ["get", "name"],
                        "text-font" => ["Open Sans Regular"],
                        "text-size" => 10,
                        "symbol-placement" => "line"
                    ],
                    "paint" => [
                        "text-color" => "#666666",
                        "text-halo-color" => "#ffffff",
                        "text-halo-width" => 1
                    ]
                ]
            ]
        ];
    }

    private function getCachedTile(int $x, int $y, int $z): ?string
    {
        $cacheDir = $this->config['tile_cache_dir'] ?? '/tmp/tile_cache/vector/';
        $cachePath = $cacheDir . "$z/$x/$y.pbf";

        if (file_exists($cachePath)) {
            $cacheTime = filemtime($cachePath);
            $ttl = $this->config['cache_ttl'] ?? 86400;

            if (time() - $cacheTime < $ttl) {
                return file_get_contents($cachePath);
            }
        }

        return null;
    }

    private function cacheTile(int $x, int $y, int $z, string $data): void
    {
        $cacheDir = $this->config['tile_cache_dir'] ?? '/tmp/tile_cache/vector/';
        $cachePath = $cacheDir . "$z/$x/";

        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }

        file_put_contents($cachePath . "$y.pbf", $data);
    }

    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    // Coordinate conversion methods remain the same
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

        $maxZoom = $this->config['max_zoom'] ?? 14;
        $minZoom = $this->config['min_zoom'] ?? 0;

        if ($z > $maxZoom || $z < $minZoom) {
            return false;
        }

        $maxTile = pow(2, $z) - 1;
        return $x <= $maxTile && $y <= $maxTile;
    }
}