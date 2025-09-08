<?php

return [
    // Vector Tile Routes
    'GET /tiles/{z}/{x}/{y}.pbf' => 'TileController@getTile',
    'GET /tiles/{z}/{x}/{y}.mvt' => 'TileController@getTile',

    // Raster Tile Routes (fallback)
    'GET /tiles/{z}/{x}/{y}' => 'TileController@getTile',
    'GET /tiles/{z}/{x}/{y}.png' => 'TileController@getTile',
    'GET /tiles/{z}/{x}/{y}.jpg' => 'TileController@getTile',

    // Vector Tile Metadata
    'GET /tiles.json' => 'TileController@getTilesJson',
    'GET /style.json' => 'TileController@getStyle',
    'GET /tiles/{z}/{x}/{y}/info' => 'TileController@getTileInfo',

    // Coordinate Conversion Routes
    'GET /convert/latlng-to-tile' => 'CoordinateController@latLngToTile',
    'GET /convert/tile-to-latlng' => 'CoordinateController@tileToLatLng',

    // GeoJSON Generation Routes
    'POST /geo/generate/layers' => 'GeoController@generateRandomFeatures',
    'POST /geo/generate/layer' => 'GeoController@generateSingleLayer',
    'GET /geo/stats' => 'GeoController@getFeatureStats',

    // Data Routes
    'GET /data/properties' => 'DataController@getProperties',
    'POST /data/properties' => 'DataController@addProperty',
    'PUT /data/properties/{id}' => 'DataController@updateProperty',
    'DELETE /data/properties/{id}' => 'DataController@deleteProperty',

    // Filter Routes
    'GET /filter/tiles' => 'FilterController@filterTiles',
    'POST /filter/apply' => 'FilterController@applyFilter',

    // External Data Routes
    'GET /external/data' => 'ExternalController@getExternalData',
    'POST /external/sync' => 'ExternalController@syncExternalData',

    // Health and Info Routes
    'GET /' => 'InfoController@index',
    'GET /health' => 'InfoController@health',
    'GET /api/info' => 'InfoController@apiInfo',

    // Maps Interface
    'GET /maps' => 'MapController@index',
    'GET /maps/vector' => 'MapController@vectorMap',
];