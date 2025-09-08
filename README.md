# Vector Maps Service Engine

A high-performance PHP-based vector mapping service that provides vector tile serving, coordinate conversion, and realistic GeoJSON generation with Malaysian geographic features.

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Vector Tiles](https://img.shields.io/badge/Tiles-Vector%20Only-orange.svg)](https://docs.mapbox.com/vector-tiles/)
[![MapLibre](https://img.shields.io/badge/Maps-MapLibre%20GL-green.svg)](https://maplibre.org/)

## 🚀 Features

- **Vector Tiles Only**: High-performance PBF/MVT vector tile serving
- **MapLibre GL JS**: Modern WebGL-powered interactive mapping
- **Coordinate Conversion**: Convert between latitude/longitude and tile coordinates
- **GeoJSON Generation**: Generate realistic geographic features with Malaysian names
- **Interactive Interface**: Advanced web-based mapping interface with real-time controls
- **TileJSON Support**: Standard vector tile metadata specification
- **Style JSON**: Compatible with MapLibre GL JS and Mapbox GL JS
- **CORS Support**: Cross-origin resource sharing for web applications

## 📁 Project Structure

```
vector-maps-engine/
├── 📂 public/                          # Web server document root
│   └── index.php                       # Main entry point with routing
├── 📂 config/                          # Configuration files
│   ├── config.php                      # Vector tile settings
│   └── routes.php                      # Route definitions
├── 📂 classes/                         # Core application classes
│   ├── Router.php                      # Advanced routing with pattern matching
│   ├── Map.php                         # Vector tile operations
│   ├── View.php                        # Template rendering system
│   └── 📂 controllers/                 # MVC Controllers
│       ├── BaseController.php          # Base controller with common methods
│       ├── TileController.php          # Vector tile serving operations
│       ├── CoordinateController.php    # Coordinate conversions
│       ├── GeoController.php           # GeoJSON feature generation
│       ├── InfoController.php          # API info and health checks
│       └── MapController.php           # Interactive vector map interface
├── 📂 views/                           # Template files
│   ├── layout.php                      # Base HTML layout
│   ├── welcome_vector.php              # Vector API welcome page
│   └── 📂 maps/
│       └── vector.php                  # Interactive vector map interface
├── 📂 template/                        # Ignored template directory
├── .gitignore                          # Git ignore rules
└── README.md                           # Project documentation
```

## 🛠️ Installation & Setup

### Prerequisites

- PHP 8.0 or higher
- cURL extension enabled
- Web server (Apache/Nginx)
- MapTiler API key (for vector tiles)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/vector-maps-engine.git
   cd vector-maps-engine
   ```

2. **Configure MapTiler API Key**
   
   Edit `config/config.php`:
   ```php
   'maptiler_api_key' => 'YOUR_API_KEY_HERE', // Get from maptiler.com
   ```

3. **Configure web server**
   
   **Apache (.htaccess)**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ public/index.php [QSA,L]
   ```
   
   **Nginx**
   ```nginx
   location / {
       try_files $uri $uri/ /public/index.php?$query_string;
   }
   ```

4. **Set permissions**
   ```bash
   chmod -R 755 public/
   chmod -R 644 config/
   mkdir -p /tmp/tile_cache/vector
   chmod -R 755 /tmp/tile_cache/
   ```

5. **Test installation**
   ```bash
   curl http://localhost/vector-maps-engine/
   ```

## 📡 API Reference

### Vector Tile Operations

#### Get Vector Tile
```http
GET /tiles/{z}/{x}/{y}.pbf
GET /tiles/{z}/{x}/{y}.mvt
GET /tiles/{z}/{x}/{y}
```

**Parameters:**
- `z` (int): Zoom level (0-14)
- `x` (int): Tile X coordinate
- `y` (int): Tile Y coordinate

**Response:** Binary vector tile data (PBF format)

**Headers:**
- `Content-Type: application/x-protobuf`
- `Content-Encoding: gzip` (if compressed)

**Example:**
```bash
curl "http://localhost/tiles/10/537/369.pbf"
```

#### Get TileJSON Metadata
```http
GET /tiles.json
```

**Response:**
```json
{
  "tilejson": "3.0.0",
  "name": "Vector Maps Service Engine",
  "format": "pbf",
  "tiles": ["http://localhost/tiles/{z}/{x}/{y}.pbf"],
  "minzoom": 0,
  "maxzoom": 14,
  "vector_layers": [...]
}
```

#### Get Style JSON
```http
GET /style.json
```

**Response:** MapLibre GL JS compatible style specification

---

### Coordinate Conversion

#### Convert Lat/Lng to Tile
```http
GET /convert/latlng-to-tile?lat={lat}&lng={lng}&zoom={zoom}
```

**Parameters:**
- `lat` (float): Latitude (-90 to 90)
- `lng` (float): Longitude (-180 to 180)
- `zoom` (int): Zoom level (0-14)

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "input": {
      "lat": 5.329,
      "lng": 103.146,
      "zoom": 10
    },
    "tile": {
      "x": 537,
      "y": 369,
      "z": 10
    }
  }
}
```

---

### GeoJSON Generation

#### Generate Multiple Layers
```http
POST /geo/generate/layers
Content-Type: application/json

{
  "count": 10000,
  "layers": 25,
  "bounds": {
    "north": 5.340,
    "south": 5.320,
    "east": 103.160,
    "west": 103.140
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "layers": [
      {
        "type": "FeatureCollection",
        "name": "Roads_Highway",
        "id": 1,
        "theme": "roads_highway",
        "features": [...]
      }
    ],
    "summary": {
      "total_features": 10000,
      "total_layers": 25,
      "generated_at": "2025-01-15T10:30:00Z"
    }
  }
}
```

---

### System Information

#### API Welcome
```http
GET /
```
Returns HTML welcome page with vector API documentation.

#### Health Check
```http
GET /health
```

Returns JSON health status:
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "service": "Vector Maps Service Engine",
    "version": "3.0.0",
    "type": "vector-only"
  }
}
```

#### Interactive Vector Map
```http
GET /maps
```
Returns full HTML page with MapLibre GL JS interactive map.

## 🎨 Interactive Vector Map Interface

The system includes a sophisticated web interface at `/maps` featuring:

- **Vector-Only Rendering**: Pure vector tile rendering with MapLibre GL JS
- **Real-time GeoJSON Generation**: Generate up to 500,000 features across 200 layers
- **Advanced Layer Management**: Toggle individual vector layers on/off
- **Themed Features**: Realistic Malaysian roads, buildings, and natural areas
- **Performance Optimized**: Efficient vector rendering and caching
- **Responsive Design**: Works on desktop and mobile devices

### Vector Map Controls

- **Feature Count**: 1,000 - 500,000 features
- **Layer Count**: 1 - 200 layers
- **Generate GeoJSON**: Creates themed vector layers
- **Clear All**: Removes all generated features
- **Layer Toggles**: Show/hide individual vector layers
- **Map Stats**: Real-time zoom, center, and tile statistics

## 🏗️ Architecture Overview

### Vector-First Design

```
HTTP Request → Router → Vector Tile Controller → MapTiler API → Vector Tile Response
```

#### Core Components

1. **Router** (`Router.php`): Advanced pattern matching with vector tile routes
2. **TileController**: Vector tile serving with PBF/MVT support
3. **Map Model** (`Map.php`): Vector tile operations and coordinate calculations
4. **View System** (`View.php`): Template rendering with MapLibre GL JS

#### Vector Tile Pipeline

1. **Request**: Client requests vector tile (PBF format)
2. **Validation**: Coordinate and zoom level validation
3. **Cache Check**: Check local vector tile cache
4. **Fetch**: Retrieve from MapTiler API if not cached
5. **Headers**: Set appropriate content-type and encoding headers
6. **Response**: Serve binary vector tile data

### MapLibre GL JS Integration

```javascript
const map = new maplibregl.Map({
    container: 'map',
    style: '/style.json',
    center: [103.146, 5.329],
    zoom: 10
});
```

## 🔧 Configuration

### Vector Tile Settings (`config/config.php`)

```php
return [
    'maptiler_api_key' => 'YOUR_API_KEY',
    'vector_tile_source' => 'https://api.maptiler.com/tiles/v3/{z}/{x}/{y}.pbf',
    'style_json_url' => 'https://api.maptiler.com/maps/streets/style.json',
    'max_zoom' => 14,
    'min_zoom' => 0,
    'tile_size' => 512,
    'cache_vector_tiles' => true,
    'cache_ttl' => 86400
];
```

### Vector Route Definitions (`config/routes.php`)

```php
'GET /tiles/{z}/{x}/{y}.pbf' => 'TileController@getTile',
'GET /tiles.json' => 'TileController@getTilesJson',
'GET /style.json' => 'TileController@getStyle',
```

## 🌍 Vector Layers

The system supports OpenMapTiles standard vector layers:

### Available Layers

1. **water** - Water bodies (seas, lakes, rivers)
2. **waterway** - Waterway lines (rivers, streams)
3. **landcover** - Land cover polygons
4. **landuse** - Land use polygons
5. **transportation** - Roads, railways, paths
6. **building** - Building footprints
7. **transportation_name** - Road labels
8. **place** - Place names and labels
9. **poi** - Points of interest
10. **boundary** - Administrative boundaries

### Malaysian Geographic Features

Generated GeoJSON includes realistic Malaysian naming:

- **Streets**: Jalan, Lorong, Persiaran, Lebuh
- **Areas**: Kampung, Taman, Bandar, Desa
- **Buildings**: Plaza, Complex, Tower, Pangsapuri
- **Natural**: Taman Botani, Sungai Klang, Tasik Titiwangsa

## 🚦 Error Handling

### HTTP Status Codes

- `200`: Success
- `400`: Bad Request (invalid parameters)
- `404`: Not Found (tile/route not found)
- `500`: Internal Server Error

### Vector Tile Specific Errors

```json
{
  "error": true,
  "message": "Vector tile not found",
  "code": 404
}
```

## 🔒 CORS Support

Cross-Origin Resource Sharing is enabled by default:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

## 📊 Performance Considerations

- **Vector Tile Caching**: Local caching of PBF tiles (24-hour TTL)
- **Efficient Encoding**: Protocol Buffer format for minimal bandwidth
- **Client-Side Rendering**: WebGL-powered rendering for smooth performance
- **Optimized Zoom Levels**: Support up to zoom 14 for optimal performance
- **Connection Pooling**: Efficient cURL operations for tile fetching

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/vector-enhancement`
3. Commit your changes: `git commit -m 'Add vector feature'`
4. Push to the branch: `git push origin feature/vector-enhancement`
5. Open a Pull Request

## 📋 TODO / Roadmap

- [ ] **Vector Tile Generation**: Generate custom vector tiles from database
- [ ] **Advanced Styling**: Dynamic style generation and customization
- [ ] **Authentication**: API key or JWT-based authentication
- [ ] **Rate Limiting**: Request throttling for vector tile endpoints
- [ ] **Clustering**: Vector tile clustering for point data
- [ ] **3D Rendering**: Extrusion and 3D building rendering
- [ ] **WebSocket Support**: Real-time vector data updates
- [ ] **Docker Support**: Containerized deployment
- [ ] **Unit Tests**: PHPUnit test suite for vector operations

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [OpenMapTiles](https://openmaptiles.org/) for vector tile specification
- [MapTiler](https://www.maptiler.com/) for vector tile data
- [MapLibre GL JS](https://maplibre.org/) for vector map rendering
- [Tailwind CSS](https://tailwindcss.com/) for responsive design
- Malaysian geographic naming conventions

## 📞 Support

For support and questions:

- Create an [Issue](https://github.com/yourusername/vector-maps-engine/issues)
- Email: your.email@domain.com
- Documentation: [Vector Tiles Spec](https://docs.mapbox.com/vector-tiles/)

---

**Built with ❤️ for the vector mapping community**