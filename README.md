# Maps Service Engine - Project Structure Guide

## Current Project Structure

```
maps-service-engine/
├── public/
│   └── index.php                    # Main entry point with routing and error handling
├── config/
│   ├── config.php                   # Configuration settings (tile cache, CORS, etc.)
│   └── routes.php                   # Route definitions
├── classes/
│   ├── Router.php                   # Route management with pattern matching
│   ├── Map.php                      # Core Map class with tile operations
│   ├── View.php                     # View rendering system
│   └── controllers/
│       ├── BaseController.php       # Base controller with common methods
│       ├── TileController.php       # Tile-related requests (implemented)
│       ├── CoordinateController.php # Coordinate conversions (implemented)
│       ├── InfoController.php       # API info and health (implemented)
│       ├── MapController.php        # Map interface controller (implemented)
│       ├── DataController.php       # Data properties (route defined, controller missing)
│       ├── FilterController.php     # Filtering (route defined, controller missing)
│       └── ExternalController.php   # External data sources (route defined, controller missing)
├── views/
│   ├── layout.php                   # Main HTML layout template
│   ├── welcome.php                  # API welcome page
│   └── maps/
│       └── index.php                # Interactive map interface
├── template/                        # Ignored directory (per .gitignore)
├── .gitignore                       # Git ignore rules
└── README.md                        # Project documentation
```

## Implemented Features

### ✅ Core Routing System
- **Router.php**: Advanced routing with pattern matching (`{parameter}` support)
- **Route Resolution**: Automatic parameter extraction and controller dispatch
- **Error Handling**: Comprehensive exception handling with JSON responses
- **CORS Support**: Cross-origin resource sharing enabled

### ✅ Tile Management (TileController)
- `GET /tiles/{z}/{x}/{y}` - Fetch and serve map tiles
- `GET /tiles/{z}/{x}/{y}.png` - PNG tile format
- `GET /tiles/{z}/{x}/{y}.jpg` - JPG tile format
- Tile validation and boundary checking
- Caching headers for performance

### ✅ Coordinate Operations (CoordinateController)
- `GET /convert/latlng-to-tile` - Convert lat/lng to tile coordinates
- `GET /convert/tile-to-latlng` - Convert tile coordinates to lat/lng
- Parameter validation for coordinates and zoom levels
- Support for multiple input formats (lat/lng, lon/lng, z/zoom)

### ✅ Information & Health (InfoController)
- `GET /` - API welcome page with route documentation
- `GET /health` - System health check
- `GET /api/info` - API documentation
- `GET /dashboard` - Dashboard view (route available)

### ✅ Map Interface (MapController)
- `GET /maps` - Interactive Leaflet map interface
- Integration with tile service
- Responsive design with Tailwind CSS

### ✅ View System
- **View.php**: Template rendering engine
- **Layout System**: Consistent HTML structure
- **Data Binding**: Extract variables for view templates
- **Tailwind CSS**: Modern responsive styling
- **Leaflet Integration**: Interactive mapping library

## Available API Routes

### Tile Routes
```
GET /tiles/{z}/{x}/{y}      # Get tile image (PNG)
GET /tiles/{z}/{x}/{y}.png  # Get tile as PNG
GET /tiles/{z}/{x}/{y}.jpg  # Get tile as JPG
```

### Coordinate Conversion Routes
```
GET /convert/latlng-to-tile?lat={lat}&lng={lng}&zoom={zoom}
GET /convert/tile-to-latlng?x={x}&y={y}&z={z}
```

### Information Routes
```
GET /                       # API welcome page
GET /health                 # Health check
GET /api/info              # API documentation
GET /maps                  # Interactive map interface
```

### Planned Routes (Controllers Missing)
```
# Data Management
GET /data/properties        # Get all properties
POST /data/properties       # Add new property
PUT /data/properties/{id}   # Update property
DELETE /data/properties/{id} # Delete property

# Filtering
GET /filter/tiles          # Filter tiles based on criteria
POST /filter/apply         # Apply custom filters

# External Data
GET /external/data         # Get external data
POST /external/sync        # Sync with external sources
```

## Core Classes Documentation

### Map.php
**Key Methods:**
- `getTileData(int $x, int $y, int $z)` - Fetch tile from OpenStreetMap
- `getTileInfo(int $x, int $y, int $z)` - Get tile metadata and bounds
- `convertCoordinatesToTiles(float $lat, float $lon, int $zoom)` - Coordinate conversion
- `convertTilesToCoordinates(int $x, int $y, int $z)` - Reverse conversion
- `isValidTile(int $x, int $y, int $z)` - Validation with zoom limits

### BaseController.php
**Common Methods:**
- `jsonResponse()` - Standard JSON response formatting
- `successResponse()` - Success response wrapper
- `errorResponse()` - Error response with status codes
- `validateCoordinates()` - Tile coordinate validation
- `view()` - Render view templates
- `getRequestData()` - Parse request data (GET/POST/PUT/DELETE)

### Router.php
**Features:**
- Pattern matching with `{parameter}` syntax
- HTTP method-based routing
- Automatic parameter extraction
- Controller and method dispatching
- Comprehensive error handling

## Configuration

### config.php Settings
```php
'tile_cache_dir' => '/tmp/tile_cache/',     # Cache directory
'tile_source' => 'https://tile.openstreetmap.org', # Tile provider
'max_zoom' => 18,                           # Maximum zoom level
'min_zoom' => 0,                            # Minimum zoom level
'enable_cors' => true,                      # CORS support
'cache_tiles' => true,                      # Enable tile caching
'cache_ttl' => 3600,                        # Cache duration (1 hour)
```

## Usage Examples

### Interactive Map Interface
```
GET /maps
# Returns full HTML page with Leaflet map
# Displays map centered on Kuala Terengganu with marker
```

### API Endpoint Usage
```bash
# Get a tile
curl "https://your-domain.com/tiles/10/512/384"

# Convert coordinates
curl "https://your-domain.com/convert/latlng-to-tile?lat=5.329&lng=103.146&zoom=16"

# Response:
{
  "success": true,
  "message": "Success",
  "data": {
    "input": {"lat": 5.329, "lng": 103.146, "zoom": 16},
    "tile": {"x": 50537, "y": 32369, "z": 16}
  }
}
```

## Implementation Status

| Component | Status | Notes |
|-----------|--------|-------|
| Router System | ✅ Complete | Advanced pattern matching |
| Tile Service | ✅ Complete | Full tile operations |
| Coordinate Conversion | ✅ Complete | Bi-directional conversion |
| View System | ✅ Complete | Template engine with layouts |
| Map Interface | ✅ Complete | Interactive Leaflet map |
| Info/Health Endpoints | ✅ Complete | API documentation |
| Data Management | ❌ Missing | Controllers not implemented |
| Filtering System | ❌ Missing | Controllers not implemented |
| External Data | ❌ Missing | Controllers not implemented |

## Next Implementation Steps

### 1. Complete Missing Controllers
- **DataController.php** - Implement property management endpoints
- **FilterController.php** - Add tile filtering capabilities
- **ExternalController.php** - External data source integration

### 2. Enhanced Features
- **Caching Layer** - Implement tile caching using config settings
- **Authentication** - Add API key or JWT authentication
- **Rate Limiting** - Prevent API abuse
- **Database Integration** - For property and data management
- **Logging System** - Request logging and monitoring

### 3. Documentation & Testing
- **API Documentation** - OpenAPI/Swagger specification
- **Unit Tests** - Controller and method testing
- **Integration Tests** - End-to-end API testing
- **Performance Monitoring** - Response time and usage metrics

## Technical Architecture

**Frontend:**
- Tailwind CSS for responsive design
- Leaflet.js for interactive mapping
- Native PHP templating system

**Backend:**
- Pure PHP 8+ with strict typing
- RESTful API architecture
- MVC pattern implementation
- Exception-based error handling

**External Services:**
- OpenStreetMap tile server integration
- cURL-based HTTP client for tile fetching

This architecture provides a solid foundation for a scalable mapping service with clean separation of concerns, comprehensive error handling, and extensible controller structure.