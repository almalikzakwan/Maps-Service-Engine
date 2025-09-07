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
            // Get tile data from Map class
            $tileData = $this->map->getTileData($x, $y, $z);

            if ($tileData === null) {
                $this->errorResponse('Tile not found', 404);
                return;
            }

            // Set appropriate headers for image response
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
            header('Content-Length: ' . strlen($tileData));

            echo $tileData;
        } catch (Exception $e) {
            $this->errorResponse('Error fetching tile: ' . $e->getMessage(), 500);
        }
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
}