<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Map.php';

class CoordinateController extends BaseController
{
    private Map $map;

    public function __construct()
    {
        parent::__construct();
        $this->map = new Map($this->config);
    }

    public function latLngToTile(array $params): void
    {
        $data = $this->getRequestData();

        $lat = floatval($data['lat'] ?? 0);
        $lng = floatval($data['lng'] ?? $data['lon'] ?? 0);
        $zoom = intval($data['zoom'] ?? $data['z'] ?? 10);

        if (!$this->validateLatLng($lat, $lng)) {
            return;
        }

        if ($zoom < 0 || $zoom > 18) {
            $this->errorResponse('Invalid zoom level: must be between 0 and 18');
            return;
        }

        try {
            $tileCoords = $this->map->convertCoordinatesToTiles($lat, $lng, $zoom);
            $this->successResponse([
                'input' => [
                    'lat' => $lat,
                    'lng' => $lng,
                    'zoom' => $zoom
                ],
                'tile' => $tileCoords
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Error converting coordinates: ' . $e->getMessage(), 500);
        }
    }

    public function tileToLatLng(array $params): void
    {
        $data = $this->getRequestData();

        $x = intval($data['x'] ?? 0);
        $y = intval($data['y'] ?? 0);
        $z = intval($data['z'] ?? $data['zoom'] ?? 0);

        if (!$this->validateCoordinates($x, $y, $z)) {
            return;
        }

        try {
            $latLng = $this->map->convertTilesToCoordinates($x, $y, $z);
            $this->successResponse([
                'input' => [
                    'x' => $x,
                    'y' => $y,
                    'z' => $z
                ],
                'coordinates' => $latLng
            ]);
        } catch (Exception $e) {
            $this->errorResponse('Error converting tile coordinates: ' . $e->getMessage(), 500);
        }
    }

    public function getBounds(array $params): void
    {
        $data = $this->getRequestData();

        $x = intval($data['x'] ?? 0);
        $y = intval($data['y'] ?? 0);
        $z = intval($data['z'] ?? 0);

        if (!$this->validateCoordinates($x, $y, $z)) {
            return;
        }

        try {
            $bounds = $this->map->getTileBounds($x, $y, $z);
            $this->successResponse($bounds);
        } catch (Exception $e) {
            $this->errorResponse('Error getting tile bounds: ' . $e->getMessage(), 500);
        }
    }

    private function validateLatLng(float $lat, float $lng): bool
    {
        if ($lat < -90 || $lat > 90) {
            $this->errorResponse('Invalid latitude: must be between -90 and 90');
            return false;
        }

        if ($lng < -180 || $lng > 180) {
            $this->errorResponse('Invalid longitude: must be between -180 and 180');
            return false;
        }

        return true;
    }
}