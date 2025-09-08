<?php
return [
    // Vector tile configuration (raster support removed)
    'maptiler_api_key' => 'Z79GA32MUk4yEaBP3i0o', // Get from maptiler.com
    'vector_tile_source' => 'https://api.maptiler.com/tiles/v3/{z}/{x}/{y}.pbf',
    'style_json_url' => 'https://api.maptiler.com/maps/streets/style.json',

    // Vector tile formats
    'vector_tile_format' => 'pbf', // Protocol Buffer format
    'supported_formats' => ['pbf', 'mvt'], // Vector tile formats only

    // Cache settings for vector tiles
    'tile_cache_dir' => '/tmp/tile_cache/vector/',
    'cache_vector_tiles' => true,
    'cache_ttl' => 86400, // 24 hours for vector tiles

    // Tile server limits
    'max_zoom' => 14, // Vector tiles typically go up to 14
    'min_zoom' => 0,

    // API settings
    'enable_cors' => true,
    'tile_size' => 512, // Vector tiles are often 512x512
];