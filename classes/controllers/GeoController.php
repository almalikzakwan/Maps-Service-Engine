<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';

class GeoController extends BaseController
{
    private array $geometryTypes = ['LineString', 'Polygon', 'MultiLineString'];

    // Realistic feature types with properties
    private array $roadTypes = [
        'highway' => ['width' => 0.0008, 'lanes' => 4, 'speed_limit' => 90],
        'primary' => ['width' => 0.0006, 'lanes' => 2, 'speed_limit' => 60],
        'secondary' => ['width' => 0.0004, 'lanes' => 2, 'speed_limit' => 50],
        'residential' => ['width' => 0.0003, 'lanes' => 1, 'speed_limit' => 30],
        'tertiary' => ['width' => 0.0003, 'lanes' => 1, 'speed_limit' => 40]
    ];

    private array $buildingTypes = [
        'residential' => ['height' => [3, 15], 'size' => [0.0001, 0.0003]],
        'commercial' => ['height' => [10, 40], 'size' => [0.0002, 0.0008]],
        'industrial' => ['height' => [5, 20], 'size' => [0.0003, 0.001]],
        'office' => ['height' => [15, 80], 'size' => [0.0002, 0.0006]],
        'retail' => ['height' => [3, 8], 'size' => [0.0001, 0.0004]]
    ];

    private array $naturalFeatures = [
        'park' => ['size' => [0.0005, 0.002]],
        'forest' => ['size' => [0.001, 0.005]],
        'water' => ['size' => [0.0008, 0.003]],
        'agricultural' => ['size' => [0.002, 0.01]]
    ];

    private array $malayStreetNames = [
        'Jalan',
        'Lorong',
        'Persiaran',
        'Lebuh',
        'Jalan Raja',
        'Jalan Sultan',
        'Jalan Datuk',
        'Jalan Tun',
        'Jalan Tengku',
        'Jalan Dato'
    ];

    private array $malayAreaNames = [
        'Kampung',
        'Taman',
        'Bandar',
        'Pekan',
        'Desa',
        'Felda',
        'Kg',
        'Tmn',
        'Bdr',
        'Psr'
    ];

    public function generateRandomFeatures(array $params): void
    {
        $data = $this->getRequestData();

        $featureCount = intval($data['count'] ?? 10000);
        $layerCount = intval($data['layers'] ?? 10);
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

        // Validate bounds
        if (!$this->validateBounds($bounds)) {
            $this->errorResponse('Invalid bounds provided', 400);
            return;
        }

        try {
            $layers = $this->generateRealisticLayers($featureCount, $layerCount, $bounds);

            $this->successResponse([
                'layers' => $layers,
                'summary' => [
                    'total_features' => $featureCount,
                    'total_layers' => $layerCount,
                    'bounds' => $bounds,
                    'generated_at' => date('c')
                ]
            ], 'Realistic GeoJSON layers generated successfully');

        } catch (Exception $e) {
            error_log('GeoController Error: ' . $e->getMessage());
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

        if (!$this->validateBounds($bounds)) {
            $this->errorResponse('Invalid bounds provided', 400);
            return;
        }

        try {
            $layer = $this->createRealisticGeoJSONLayer($featureCount, $bounds, $geometryType);

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

    private function validateBounds(array $bounds): bool
    {
        $required = ['north', 'south', 'east', 'west'];
        foreach ($required as $key) {
            if (!isset($bounds[$key]) || !is_numeric($bounds[$key])) {
                return false;
            }
        }

        return (
            $bounds['north'] > $bounds['south'] &&
            $bounds['east'] > $bounds['west'] &&
            $bounds['north'] <= 90 && $bounds['south'] >= -90 &&
            $bounds['east'] <= 180 && $bounds['west'] >= -180
        );
    }

    private function generateRealisticLayers(int $totalFeatures, int $layerCount, array $bounds): array
    {
        $layers = [];
        $featuresPerLayer = intval($totalFeatures / $layerCount);
        $remainingFeatures = $totalFeatures % $layerCount;

        // Create thematic layers
        $layerThemes = [
            'Roads_Highway' => 'roads_highway',
            'Roads_Primary' => 'roads_primary',
            'Roads_Secondary' => 'roads_secondary',
            'Roads_Residential' => 'roads_residential',
            'Buildings_Residential' => 'buildings_residential',
            'Buildings_Commercial' => 'buildings_commercial',
            'Buildings_Industrial' => 'buildings_industrial',
            'Natural_Parks' => 'natural_parks',
            'Natural_Water' => 'natural_water',
            'Mixed_Features' => 'mixed'
        ];

        $themeKeys = array_keys($layerThemes);

        for ($i = 0; $i < $layerCount; $i++) {
            $currentFeatureCount = $featuresPerLayer;

            if ($i < $remainingFeatures) {
                $currentFeatureCount++;
            }

            $layerName = $themeKeys[$i % count($themeKeys)];
            $theme = $layerThemes[$layerName];

            try {
                $layer = $this->createThemedGeoJSONLayer($currentFeatureCount, $bounds, $theme);
                $layer['name'] = $layerName;
                $layer['id'] = $i + 1;
                $layer['theme'] = $theme;

                $layers[] = $layer;
            } catch (Exception $e) {
                error_log("Error creating layer {$i}: " . $e->getMessage());
                // Continue with other layers
            }
        }

        return $layers;
    }

    private function createRealisticGeoJSONLayer(int $featureCount, array $bounds, ?string $forceType = null): array
    {
        return $this->createThemedGeoJSONLayer($featureCount, $bounds, 'mixed', $forceType);
    }

    private function createThemedGeoJSONLayer(int $featureCount, array $bounds, string $theme, ?string $forceType = null): array
    {
        $features = [];
        $successfulFeatures = 0;

        for ($i = 0; $i < $featureCount && $successfulFeatures < $featureCount; $i++) {
            try {
                $feature = $this->createRealisticFeature($theme, $bounds, $i + 1, $forceType);
                if ($feature) {
                    $features[] = $feature;
                    $successfulFeatures++;
                }
            } catch (Exception $e) {
                error_log("Error creating feature {$i}: " . $e->getMessage());
                // Continue with next feature
            }
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
            'properties' => [
                'featureCount' => count($features),
                'bounds' => $bounds,
                'theme' => $theme,
                'createdAt' => date('c')
            ]
        ];
    }

    private function createRealisticFeature(string $theme, array $bounds, int $id, ?string $forceType = null): ?array
    {
        try {
            // Determine geometry type and feature properties based on theme
            if ($forceType) {
                $geometryType = $forceType;
                $featureData = $this->getFeatureDataForType($geometryType, $theme);
            } else {
                $featureData = $this->getFeatureDataForTheme($theme);
                $geometryType = $featureData['geometry'];
            }

            $geometry = $this->generateRealisticGeometry($geometryType, $bounds, $featureData);

            if (!$geometry) {
                return null;
            }

            return [
                'type' => 'Feature',
                'id' => $id,
                'geometry' => $geometry,
                'properties' => array_merge($featureData['properties'], [
                    'id' => $id,
                    'theme' => $theme,
                    'createdAt' => date('c')
                ])
            ];
        } catch (Exception $e) {
            error_log("Error in createRealisticFeature: " . $e->getMessage());
            return null;
        }
    }

    private function getFeatureDataForTheme(string $theme): array
    {
        switch ($theme) {
            case 'roads_highway':
                return [
                    'geometry' => 'LineString',
                    'properties' => [
                        'name' => $this->generateRoadName('highway'),
                        'type' => 'highway',
                        'lanes' => 4,
                        'speed_limit' => 90,
                        'surface' => 'asphalt',
                        'width' => 15
                    ]
                ];

            case 'roads_primary':
                return [
                    'geometry' => 'LineString',
                    'properties' => [
                        'name' => $this->generateRoadName('primary'),
                        'type' => 'primary',
                        'lanes' => 2,
                        'speed_limit' => 60,
                        'surface' => 'asphalt',
                        'width' => 8
                    ]
                ];

            case 'roads_secondary':
                return [
                    'geometry' => rand(0, 3) === 0 ? 'MultiLineString' : 'LineString',
                    'properties' => [
                        'name' => $this->generateRoadName('secondary'),
                        'type' => 'secondary',
                        'lanes' => 2,
                        'speed_limit' => 50,
                        'surface' => 'asphalt',
                        'width' => 6
                    ]
                ];

            case 'roads_residential':
                return [
                    'geometry' => 'LineString',
                    'properties' => [
                        'name' => $this->generateRoadName('residential'),
                        'type' => 'residential',
                        'lanes' => 1,
                        'speed_limit' => 30,
                        'surface' => 'asphalt',
                        'width' => 4
                    ]
                ];

            case 'buildings_residential':
                return [
                    'geometry' => 'Polygon',
                    'properties' => [
                        'name' => $this->generateBuildingName('residential'),
                        'type' => 'residential',
                        'height' => rand(3, 15),
                        'floors' => rand(1, 5),
                        'material' => 'concrete',
                        'use' => 'residential'
                    ]
                ];

            case 'buildings_commercial':
                return [
                    'geometry' => 'Polygon',
                    'properties' => [
                        'name' => $this->generateBuildingName('commercial'),
                        'type' => 'commercial',
                        'height' => rand(10, 40),
                        'floors' => rand(3, 15),
                        'material' => 'steel_concrete',
                        'use' => 'commercial'
                    ]
                ];

            case 'buildings_industrial':
                return [
                    'geometry' => 'Polygon',
                    'properties' => [
                        'name' => $this->generateBuildingName('industrial'),
                        'type' => 'industrial',
                        'height' => rand(5, 20),
                        'floors' => rand(1, 3),
                        'material' => 'steel',
                        'use' => 'industrial'
                    ]
                ];

            case 'natural_parks':
                return [
                    'geometry' => 'Polygon',
                    'properties' => [
                        'name' => $this->generateNaturalName('park'),
                        'type' => 'park',
                        'area_hectares' => rand(1, 50),
                        'facilities' => $this->getRandomFacilities(),
                        'surface' => 'grass',
                        'use' => 'recreation'
                    ]
                ];

            case 'natural_water':
                return [
                    'geometry' => 'Polygon',
                    'properties' => [
                        'name' => $this->generateNaturalName('water'),
                        'type' => 'water',
                        'water_type' => $this->getRandomWaterType(),
                        'depth_meters' => rand(1, 20),
                        'surface' => 'water',
                        'use' => 'water_body'
                    ]
                ];

            default: // mixed
                $types = ['roads', 'buildings', 'natural'];
                $selectedType = $types[array_rand($types)];
                return $this->getFeatureDataForTheme($selectedType . '_' . $this->getRandomSubtype($selectedType));
        }
    }

    private function getFeatureDataForType(string $geometryType, string $theme): array
    {
        $baseData = $this->getFeatureDataForTheme($theme);
        $baseData['geometry'] = $geometryType;
        return $baseData;
    }

    private function generateRealisticGeometry(string $type, array $bounds, array $featureData): ?array
    {
        try {
            switch ($type) {
                case 'LineString':
                    return $this->generateRealisticRoad($bounds, $featureData);

                case 'MultiLineString':
                    return $this->generateRealisticMultiRoad($bounds, $featureData);

                case 'Polygon':
                    if (
                        strpos($featureData['properties']['type'], 'building') !== false ||
                        in_array($featureData['properties']['type'], ['residential', 'commercial', 'industrial'])
                    ) {
                        return $this->generateRealisticBuilding($bounds, $featureData);
                    } else {
                        return $this->generateRealisticArea($bounds, $featureData);
                    }

                default:
                    error_log("Unsupported geometry type: {$type}");
                    return null;
            }
        } catch (Exception $e) {
            error_log("Error generating geometry: " . $e->getMessage());
            return null;
        }
    }

    private function generateRealisticRoad(array $bounds, array $featureData): ?array
    {
        $roadType = $featureData['properties']['type'];
        $segments = $this->getRoadSegmentCount($roadType);

        // Generate road path with realistic curves
        $coordinates = [];
        $startPoint = $this->generateRandomPoint($bounds);
        $coordinates[] = $startPoint;

        $currentPoint = $startPoint;
        $direction = rand(0, 360) * M_PI / 180; // Random initial direction

        for ($i = 1; $i < $segments; $i++) {
            // Add some variation to direction for realistic curves
            $direction += (rand(-30, 30) * M_PI / 180);

            // Distance based on road type
            $distance = $this->getRoadSegmentDistance($roadType);

            $newLat = $currentPoint[1] + ($distance * cos($direction));
            $newLng = $currentPoint[0] + ($distance * sin($direction));

            // Keep within bounds
            $newLat = max($bounds['south'], min($bounds['north'], $newLat));
            $newLng = max($bounds['west'], min($bounds['east'], $newLng));

            // Additional clamping to global coordinate limits
            $newLat = max(-90, min(90, $newLat));
            $newLng = max(-180, min(180, $newLng));

            $currentPoint = [round($newLng, 6), round($newLat, 6)];
            $coordinates[] = $currentPoint;
        }

        // Basic validation
        if (count($coordinates) < 2) {
            error_log("Generated road has too few coordinates: " . count($coordinates));
            return $this->generateFallbackRoad($bounds);
        }

        // Check first and last coordinate validity
        $firstCoord = $coordinates[0];
        $lastCoord = $coordinates[count($coordinates) - 1];

        if (!$this->isValidCoordinatePair($firstCoord) || !$this->isValidCoordinatePair($lastCoord)) {
            error_log("Invalid coordinate pairs in road geometry");
            return $this->generateFallbackRoad($bounds);
        }

        return [
            'type' => 'LineString',
            'coordinates' => $coordinates
        ];
    }

    private function generateRealisticMultiRoad(array $bounds, array $featureData): ?array
    {
        $roadCount = rand(2, 4);
        $coordinates = [];

        for ($i = 0; $i < $roadCount; $i++) {
            $roadGeometry = $this->generateRealisticRoad($bounds, $featureData);
            if ($roadGeometry && isset($roadGeometry['coordinates'])) {
                $coordinates[] = $roadGeometry['coordinates'];
            }
        }

        if (empty($coordinates)) {
            return $this->generateFallbackRoad($bounds);
        }

        return [
            'type' => 'MultiLineString',
            'coordinates' => $coordinates
        ];
    }

    private function generateRealisticBuilding(array $bounds, array $featureData): ?array
    {
        $buildingType = $featureData['properties']['type'];
        $sizeRange = $this->buildingTypes[$buildingType]['size'] ?? [0.0001, 0.0003];

        $center = $this->generateRandomPoint($bounds);
        $width = $sizeRange[0] + (($sizeRange[1] - $sizeRange[0]) * rand(0, 100) / 100);
        $height = $width * (0.7 + (rand(0, 60) / 100)); // Slight rectangular variation

        // Create rectangular building with slight irregularities
        $coordinates = [];
        $baseCoords = [
            [$center[0] - $width / 2, $center[1] - $height / 2],
            [$center[0] + $width / 2, $center[1] - $height / 2],
            [$center[0] + $width / 2, $center[1] + $height / 2],
            [$center[0] - $width / 2, $center[1] + $height / 2],
            [$center[0] - $width / 2, $center[1] - $height / 2] // Close the polygon
        ];

        // Add slight irregularities for realism and validate each coordinate
        foreach ($baseCoords as $coord) {
            $irregularity = 0.00001; // Very small variation
            $newLng = $coord[0] + (rand(-100, 100) / 100) * $irregularity;
            $newLat = $coord[1] + (rand(-100, 100) / 100) * $irregularity;

            // Ensure coordinates stay within bounds
            $newLng = max($bounds['west'], min($bounds['east'], $newLng));
            $newLat = max($bounds['south'], min($bounds['north'], $newLat));

            // Global coordinate validation
            $newLng = max(-180, min(180, $newLng));
            $newLat = max(-90, min(90, $newLat));

            $finalCoord = [round($newLng, 6), round($newLat, 6)];

            if ($this->isValidCoordinatePair($finalCoord)) {
                $coordinates[] = $finalCoord;
            }
        }

        // Ensure we have enough coordinates for a valid polygon
        if (count($coordinates) < 4) {
            error_log("Building polygon has insufficient coordinates, generating fallback");
            return $this->generateFallbackBuilding($bounds);
        }

        return [
            'type' => 'Polygon',
            'coordinates' => [$coordinates]
        ];
    }

    private function generateRealisticArea(array $bounds, array $featureData): ?array
    {
        $areaType = $featureData['properties']['type'];
        $center = $this->generateRandomPoint($bounds);

        // Generate organic shape for natural areas
        $vertices = rand(6, 12);
        $baseRadius = 0.001 + (rand(0, 200) / 100000); // Variable size

        $coordinates = [];
        for ($i = 0; $i <= $vertices; $i++) {
            $angle = ($i / $vertices) * 2 * M_PI;

            // Add organic variation to radius
            $radiusVariation = 0.3 + (rand(0, 140) / 100); // 30% to 170% of base radius
            $currentRadius = $baseRadius * $radiusVariation;

            $lng = $center[0] + ($currentRadius * cos($angle));
            $lat = $center[1] + ($currentRadius * sin($angle));

            // Keep within bounds
            $lng = max($bounds['west'], min($bounds['east'], $lng));
            $lat = max($bounds['south'], min($bounds['north'], $lat));

            $coord = [round($lng, 6), round($lat, 6)];
            if ($this->isValidCoordinatePair($coord)) {
                $coordinates[] = $coord;
            }
        }

        if (count($coordinates) < 4) {
            return $this->generateFallbackBuilding($bounds);
        }

        return [
            'type' => 'Polygon',
            'coordinates' => [$coordinates]
        ];
    }

    // Helper methods for realistic data generation

    private function generateRoadName(string $type): string
    {
        $prefix = $this->malayStreetNames[array_rand($this->malayStreetNames)];

        $suffixes = [
            'Bukit Bintang',
            'Ampang',
            'Cheras',
            'Bangsar',
            'Damansara',
            'Petaling',
            'Shah Alam',
            'Klang',
            'Subang',
            'Puchong',
            'Serdang',
            'Kajang',
            'Seremban',
            'Melaka',
            'Johor',
            'Terengganu',
            'Kelantan',
            'Perak',
            'Penang',
            'Kedah'
        ];

        $suffix = $suffixes[array_rand($suffixes)];

        if ($type === 'highway') {
            return "Lebuhraya " . $suffix;
        }

        return $prefix . " " . $suffix;
    }

    private function generateBuildingName(string $type): string
    {
        $prefixes = [
            'residential' => ['Apartment', 'Kondominium', 'Pangsapuri', 'Rumah'],
            'commercial' => ['Plaza', 'Complex', 'Mall', 'Centre', 'Tower'],
            'industrial' => ['Factory', 'Warehouse', 'Plant', 'Industrial Park']
        ];

        $names = [
            'Suria',
            'Pavilion',
            'KLCC',
            'Mid Valley',
            'Sunway',
            'Genting',
            'Sri Petaling',
            'Mont Kiara',
            'Bangsar',
            'Desa ParkCity'
        ];

        $prefix = $prefixes[$type][array_rand($prefixes[$type])];
        $name = $names[array_rand($names)];

        return $prefix . " " . $name;
    }

    private function generateNaturalName(string $type): string
    {
        $waterNames = ['Sungai Klang', 'Tasik Titiwangsa', 'Sungai Gombak', 'Kolam Air'];
        $parkNames = ['Taman Botani', 'Taman Tasik', 'Hutan Bandar', 'Taman Rekreasi'];

        if ($type === 'water') {
            return $waterNames[array_rand($waterNames)] . " " . rand(1, 99);
        } else {
            return $parkNames[array_rand($parkNames)] . " " . rand(1, 99);
        }
    }

    private function getRoadSegmentCount(string $roadType): int
    {
        switch ($roadType) {
            case 'highway':
                return rand(8, 15);
            case 'primary':
                return rand(6, 12);
            case 'secondary':
                return rand(4, 8);
            case 'residential':
                return rand(3, 6);
            default:
                return rand(4, 8);
        }
    }

    private function getRoadSegmentDistance(string $roadType): float
    {
        switch ($roadType) {
            case 'highway':
                return 0.002 + (rand(0, 100) / 100) * 0.003; // 0.002-0.005
            case 'primary':
                return 0.001 + (rand(0, 100) / 100) * 0.002; // 0.001-0.003
            case 'secondary':
                return 0.0005 + (rand(0, 100) / 100) * 0.0015; // 0.0005-0.002
            case 'residential':
                return 0.0003 + (rand(0, 100) / 100) * 0.0007; // 0.0003-0.001
            default:
                return 0.001;
        }
    }

    private function getRandomFacilities(): array
    {
        $facilities = ['playground', 'jogging_track', 'pond', 'gazebo', 'parking', 'restroom'];
        $selected = [];
        $count = rand(1, 3);

        for ($i = 0; $i < $count; $i++) {
            $facility = $facilities[array_rand($facilities)];
            if (!in_array($facility, $selected)) {
                $selected[] = $facility;
            }
        }

        return $selected;
    }

    private function getRandomWaterType(): string
    {
        $types = ['river', 'lake', 'pond', 'reservoir', 'stream'];
        return $types[array_rand($types)];
    }

    private function getRandomSubtype(string $type): string
    {
        switch ($type) {
            case 'roads':
                return ['highway', 'primary', 'secondary', 'residential'][array_rand(['highway', 'primary', 'secondary', 'residential'])];
            case 'buildings':
                return ['residential', 'commercial', 'industrial'][array_rand(['residential', 'commercial', 'industrial'])];
            case 'natural':
                return ['parks', 'water'][array_rand(['parks', 'water'])];
            default:
                return 'mixed';
        }
    }

    private function generateRandomPoint(array $bounds): array
    {
        // Ensure bounds are valid
        if (!isset($bounds['west'], $bounds['east'], $bounds['north'], $bounds['south'])) {
            error_log('Invalid bounds provided, using default');
            $bounds = $this->getDefaultBounds();
        }

        // Ensure logical bounds (west < east, south < north)
        if ($bounds['west'] >= $bounds['east'] || $bounds['south'] >= $bounds['north']) {
            error_log('Illogical bounds provided, using default');
            $bounds = $this->getDefaultBounds();
        }

        $lng = $bounds['west'] + (($bounds['east'] - $bounds['west']) * (rand(0, 10000) / 10000));
        $lat = $bounds['south'] + (($bounds['north'] - $bounds['south']) * (rand(0, 10000) / 10000));

        // Clamp to valid coordinate ranges
        $lng = max(-180, min(180, $lng));
        $lat = max(-90, min(90, $lat));

        return [round($lng, 6), round($lat, 6)];
    }

    private function isValidCoordinatePair(array $coord): bool
    {
        if (!is_array($coord) || count($coord) < 2) {
            return false;
        }

        $lng = $coord[0];
        $lat = $coord[1];

        return (
            is_numeric($lng) && is_numeric($lat) &&
            is_finite($lng) && is_finite($lat) &&
            $lng >= -180 && $lng <= 180 &&
            $lat >= -90 && $lat <= 90
        );
    }

    private function generateFallbackRoad(array $bounds): array
    {
        // Generate simple 2-point road as fallback
        $point1 = $this->generateRandomPoint($bounds);
        $point2 = $this->generateRandomPoint($bounds);

        return [
            'type' => 'LineString',
            'coordinates' => [$point1, $point2]
        ];
    }

    private function generateFallbackBuilding(array $bounds): array
    {
        // Generate simple square building as fallback
        $center = $this->generateRandomPoint($bounds);
        $size = 0.0002; // Small fixed size

        $coordinates = [
            [$center[0] - $size, $center[1] - $size],
            [$center[0] + $size, $center[1] - $size],
            [$center[0] + $size, $center[1] + $size],
            [$center[0] - $size, $center[1] + $size],
            [$center[0] - $size, $center[1] - $size]
        ];

        return [
            'type' => 'Polygon',
            'coordinates' => [$coordinates]
        ];
    }

    private function getDefaultBounds(): array
    {
        // Default to Kuala Terengganu area - more realistic coordinates
        return [
            'north' => 5.340,
            'south' => 5.320,
            'east' => 103.160,
            'west' => 103.140
        ];
    }

    public function getFeatureStats(array $params): void
    {
        $data = $this->getRequestData();
        $layerId = $data['layer_id'] ?? null;

        // Mock statistics with realistic data
        $stats = [
            'total_features' => rand(8000, 12000),
            'geometry_types' => [
                'LineString' => rand(2000, 4000),    // Roads
                'MultiLineString' => rand(500, 1000), // Complex road networks
                'Polygon' => rand(3000, 6000),        // Buildings and areas
            ],
            'feature_themes' => [
                'roads' => rand(2500, 4500),
                'buildings' => rand(3000, 5000),
                'natural_areas' => rand(800, 1500)
            ],
            'layer_id' => $layerId,
            'bounds' => $this->getDefaultBounds(),
            'last_updated' => date('c')
        ];

        $this->successResponse($stats);
    }
}