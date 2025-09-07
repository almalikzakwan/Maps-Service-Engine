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

        $tileUrl = "https://tile.openstreetmap.org/$z/$x/$y.png";
        return $tileUrl;
    }

    public function getTileData(int $x, int $y, int $z): ?string
    {
        if ($x < 0 || $y < 0 || $z < 0) {
            throw new InvalidArgumentException("Invalid tile coordinates.");
        }

        $tileUrl = "https://tile.openstreetmap.org/$z/$x/$y.png";

        // Use cURL to fetch the tile
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $tileUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Maps-Service-Engine/1.0',
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

        return [
            'x' => $x,
            'y' => $y,
            'z' => $z,
            'bounds' => $bounds,
            'url' => "https://tile.openstreetmap.org/$z/$x/$y.png",
            'size' => 256 // Standard tile size
        ];
    }

    public function getTileBounds(int $x, int $y, int $z): array
    {
        // Calculate bounding box for tile
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

    public function isValidTile(int $x, int $y, int $z): bool
    {
        if ($x < 0 || $y < 0 || $z < 0) {
            return false;
        }

        if ($z > 18) { // Max zoom for OSM
            return false;
        }

        $maxTile = pow(2, $z) - 1;
        return $x <= $maxTile && $y <= $maxTile;
    }
}