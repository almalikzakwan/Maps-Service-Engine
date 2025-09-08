<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';

class GeoController extends BaseController
{
    private array $geometryTypes = ['Point', 'LineString', 'Polygon', 'MultiLineString'];

    public function generateRandomFeatures(array $params): void
    {
        $data = $this->getRequestData();

        $featureCount = intval($data['count'] ?? 100000);
        $layerCount = intval($data['layers'] ?? 100);
        $bounds = $data['bounds'] ?? $this->getDefaultBounds();

        // Validate inputs
        if ($featureCount > 500000) {
            $this->errorResponse('Feature count too high. Maximum 500,000 features allowed.', 400);
            return;
        }

        if ($layerCount > 200) {
            $this->errorResponse('Layer count too high. Maximum 200 layers allowed.', 400);
            return;
        }

        try {
            $layers = $this->generateLayers($featureCount, $layerCount, $bounds);

            $this->successResponse([
                'layers' => $layers,
                'summary' => [
                    'total_features' => $featureCount,
                    'total_layers' => $layerCount,
                    'bounds' => $bounds,
                    'generated_at' => date('c')
                ]
            ], 'GeoJSON layers generated successfully');

        } catch (Exception $e) {
            $this->errorResponse('Error generating GeoJSON: ' . $e->getMessage(), 500);
        }
    }

    public function generateSingleLayer(array $params): void
    {
        $data = $this->getRequestData();

        $featureCount = intval($data['count'] ?? 1000);
        $geometryType = $data['type'] ?? null;
        $bounds = $data['bounds'] ?? $this->getDefaultBounds();

        if ($geometryType && !in_array($geometryType, $this->geometryTypes)) {
            $this->errorResponse('Invalid geometry type. Allowed: ' . implode(', ', $this->geometryTypes), 400);
            return;
        }

        try {
            $layer = $this->createGeoJSONLayer($featureCount, $bounds, $geometryType);

            $this->successResponse([
                'layer' => $layer,
                'summary' => [
                    'feature_count' => $featureCount,
                    'geometry_type' => $geometryType ?: 'mixed',
                    'bounds' => $bounds
                ]
            ]);

        } catch (Exception $e) {
            $this->errorResponse('Error generating layer: ' . $e->getMessage(), 500);
        }
    }

    private function generateLayers(int $totalFeatures, int $layerCount, array $bounds): array
    {
        $layers = [];
        $featuresPerLayer = intval($totalFeatures / $layerCount);
        $remainingFeatures = $totalFeatures % $layerCount;

        for ($i = 0; $i < $layerCount; $i++) {
            $currentFeatureCount = $featuresPerLayer;

            // Distribute remaining features among first layers
            if ($i < $remainingFeatures) {
                $currentFeatureCount++;
            }

            $layerName = "Layer_" . ($i + 1);
            $layer = $this->createGeoJSONLayer($currentFeatureCount, $bounds);
            $layer['name'] = $layerName;
            $layer['id'] = $i + 1;

            $layers[] = $layer;
        }

        return $layers;
    }

    private function createGeoJSONLayer(int $featureCount, array $bounds, ?string $forceType = null): array
    {
        $features = [];

        for ($i = 0; $i < $featureCount; $i++) {
            $geometryType = $forceType ?: $this->geometryTypes[array_rand($this->geometryTypes)];
            $feature = $this->createFeature($geometryType, $bounds, $i + 1);
            $features[] = $feature;
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
            'properties' => [
                'featureCount' => $featureCount,
                'bounds' => $bounds,
                'createdAt' => date('c')
            ]
        ];
    }

    private function createFeature(string $type, array $bounds, int $id): array
    {
        $geometry = $this->generateGeometry($type, $bounds);

        return [
            'type' => 'Feature',
            'id' => $id,
            'geometry' => $geometry,
            'properties' => [
                'id' => $id,
                'name' => ucfirst(strtolower($type)) . " Feature {$id}",
                'type' => $type,
                'color' => $this->generateRandomColor(),
                'createdAt' => date('c'),
                'description' => "Random {$type} feature generated for testing"
            ]
        ];
    }

    private function generateGeometry(string $type, array $bounds): array
    {
        switch ($type) {
            case 'Point':
                return [
                    'type' => 'Point',
                    'coordinates' => $this->generateRandomPoint($bounds)
                ];

            case 'LineString':
                $pointCount = rand(2, 10);
                $coordinates = [];
                for ($i = 0; $i < $pointCount; $i++) {
                    $coordinates[] = $this->generateRandomPoint($bounds);
                }
                return [
                    'type' => 'LineString',
                    'coordinates' => $coordinates
                ];

            case 'Polygon':
                $vertexCount = rand(4, 8);
                $center = $this->generateRandomPoint($bounds);
                $coordinates = [$this->generatePolygonRing($center, $vertexCount, 0.01)];

                // Sometimes add holes
                if (rand(0, 10) > 7) {
                    $coordinates[] = $this->generatePolygonRing($center, 4, 0.005);
                }

                return [
                    'type' => 'Polygon',
                    'coordinates' => $coordinates
                ];

            case 'MultiLineString':
                $lineCount = rand(2, 5);
                $coordinates = [];
                for ($l = 0; $l < $lineCount; $l++) {
                    $pointCount = rand(2, 6);
                    $line = [];
                    for ($p = 0; $p < $pointCount; $p++) {
                        $line[] = $this->generateRandomPoint($bounds);
                    }
                    $coordinates[] = $line;
                }
                return [
                    'type' => 'MultiLineString',
                    'coordinates' => $coordinates
                ];

            default:
                throw new Exception("Unsupported geometry type: {$type}");
        }
    }

    private function generateRandomPoint(array $bounds): array
    {
        $lng = $bounds['west'] + (($bounds['east'] - $bounds['west']) * (rand(0, 10000) / 10000));
        $lat = $bounds['south'] + (($bounds['north'] - $bounds['south']) * (rand(0, 10000) / 10000));

        return [round($lng, 6), round($lat, 6)];
    }

    private function generatePolygonRing(array $center, int $vertexCount, float $radius): array
    {
        $coordinates = [];
        $angleStep = 2 * M_PI / $vertexCount;

        for ($i = 0; $i <= $vertexCount; $i++) {
            $angle = $i * $angleStep;
            $lat = $center[1] + ($radius * cos($angle));
            $lng = $center[0] + ($radius * sin($angle));
            $coordinates[] = [round($lng, 6), round($lat, 6)];
        }

        return $coordinates;
    }

    private function generateRandomColor(): string
    {
        return sprintf('#%06X', rand(0, 0xFFFFFF));
    }

    private function getDefaultBounds(): array
    {
        // Default to Kuala Terengganu area
        return [
            'north' => 5.4,
            'south' => 5.2,
            'east' => 103.2,
            'west' => 103.0
        ];
    }

    public function getFeatureStats(array $params): void
    {
        $data = $this->getRequestData();
        $layerId = $data['layer_id'] ?? null;

        // This would typically query a database
        // For now, return mock statistics
        $stats = [
            'total_features' => rand(800, 1200),
            'geometry_types' => [
                'Point' => rand(200, 400),
                'LineString' => rand(150, 300),
                'Polygon' => rand(100, 250),
                'MultiLineString' => rand(50, 150)
            ],
            'layer_id' => $layerId,
            'bounds' => $this->getDefaultBounds(),
            'last_updated' => date('c')
        ];

        $this->successResponse($stats);
    }
}