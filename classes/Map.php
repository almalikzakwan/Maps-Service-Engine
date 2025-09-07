<?php

class Map {
    private $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function getTile(int $x, int $y, int $z): string {
        //check if x,y,z are invalid
        if ($x < 0 || $y < 0 || $z < 0) {
            return "Invalid tile coordinates.";
        }

        $tileUrl = "https://tile.openstreetmap.org/$z/$x/$y.png";
    }

    public function convertCoordinatesToTiles(int $lat, int $lon, int $zoom): array {
        $latRad = deg2rad($lat);
        $n = pow(2,$zoom);
        
        $x = floor(($lon + 180.0) / 360.0 * $n);
        
        
    }

    
}

