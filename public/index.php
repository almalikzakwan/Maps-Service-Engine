<?php 
declare(strict_types=1);

require_once __DIR__ ."classes/Map.php";
$config = require __DIR__ ."/../config/config.php";

$x = $_GET["x"] ?? 0;
$y = $_GET["y"] ?? 0;
$z = $_GET["z"] ?? 0;

$map = new Map($config);

echo $map->getTile($x,$y,$z);

header("Content-Type: text/plain");

