<?php
return [
    // Vector tile configuration
    'tile_type' => 'vector', // 'raster' or 'vector'
    'vector_tile_source' => 'https://api.maptiler.com/tiles/v3/{z}/{x}/{y}.pbf', // Example endpoint
    'raster_tile_source' => 'https://tile.openstreetmap.org', // Fallback

    // OpenMapTiles specific settings
    'maptiler_api_key' => 'Z79GA32MUk4yEaBP3i0o', // Get from maptiler.com
    'vector_tile_format' => 'pbf', // Protocol Buffer format
    'style_json_url' => 'https://api.maptiler.com/maps/streets/style.json',

    // Cache settings
    'tile_cache_dir' => '/tmp/tile_cache/',
    'cache_vector_tiles' => true,
    'cache_ttl' => 86400, // 24 hours for vector tiles (larger files)

    // Tile server limits
    'max_zoom' => 14, // Vector tiles typically go up to 14-16
    'min_zoom' => 0,

    // API settings
    'enable_cors' => true,
    'supported_formats' => ['pbf', 'mvt'], // Vector tile formats
];