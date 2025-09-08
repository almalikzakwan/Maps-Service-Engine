# Maps Service Engine

A powerful PHP-based mapping service that provides tile serving, coordinate conversion, and realistic GeoJSON generation with Malaysian geographic features.

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![OpenStreetMap](https://img.shields.io/badge/Tiles-OpenStreetMap-orange.svg)](https://www.openstreetmap.org/)

## 🚀 Features

- **Tile Serving**: Proxy and serve map tiles from OpenStreetMap
- **Coordinate Conversion**: Convert between latitude/longitude and tile coordinates
- **GeoJSON Generation**: Generate realistic geographic features with Malaysian names
- **Interactive Map**: Web-based Leaflet.js interface with real-time controls
- **RESTful API**: Clean API endpoints with comprehensive error handling
- **CORS Support**: Cross-origin resource sharing for web applications

## 📁 Project Structure

```
maps-service-engine/
├── 📂 public/                          # Web server document root
│   └── index.php                       # Main entry point with routing
├── 📂 config/                          # Configuration files
│   ├── config.php                      # System settings
│   └── routes.php                      # Route definitions
├── 📂 classes/                         # Core application classes
│   ├── Router.php                      # Advanced routing with pattern matching
│   ├── Map.php                         # Core mapping operations
│   ├── View.php                        # Template rendering system
│   └── 📂 controllers/                 # MVC Controllers
│       ├── BaseController.php          # Base controller with common methods
│       ├── TileController.php          # Tile serving operations
│       ├── CoordinateController.php    # Coordinate conversions
│       ├── GeoController.php           # GeoJSON feature generation
│       ├── InfoController.php          # API info and health checks
│       └── MapController.php           # Interactive map interface
├── 📂 views/                           # Template files
│   ├── layout.php                      # Base HTML layout
│   ├── welcome.php                     # API welcome page
│   └── 📂 maps/
│       └── index.php                   # Interactive map interface
├── 📂 template/                        # Ignored template directory
├── .gitignore                          # Git ignore rules
└── README.md                           # Project documentation
```

## 🛠️ Installation & Setup

### Prerequisites

- PHP 8.0 or higher
- cURL extension enabled
- Web server (Apache/Nginx)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/maps-service-engine.git
   cd maps-service-engine
   ```

2. **Configure web server**
   
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

3. **Set permissions**
   ```bash
   chmod -R 755 public/
   chmod -R 644 config/
   ```

4. **Test installation**
   ```bash
   curl http://localhost/maps-service-engine/
   ```

## 📡 API Reference

### Tile Operations

#### Get Map Tile
```http
GET /tiles/{z}/{x}/{y}
GET /tiles/{z}/{x}/{y}.png
GET /tiles/{z}/{x}/{y}.jpg
```

**Parameters:**
- `z` (int): Zoom level (0-18)
- `x` (int): Tile X coordinate
- `y` (int): Tile Y coordinate

**Response:** Binary image data (PNG/JPG)

**Example:**
```bash
curl "http://localhost/tiles/16/50537/32369"
```

---

### Coordinate Conversion

#### Convert Lat/Lng to Tile
```http
GET /convert/latlng-to-tile?lat={lat}&lng={lng}&zoom={zoom}
```

**Parameters:**
- `lat` (float): Latitude (-90 to 90)
- `lng` (float): Longitude (-180 to 180)
- `zoom` (int): Zoom level (0-18)

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "input": {
      "lat": 5.329,
      "lng": 103.146,
      "zoom": 16
    },
    "tile": {
      "x": 50537,
      "y": 32369,
      "z": 16
    }
  }
}
```

#### Convert Tile to Lat/Lng
```http
GET /convert/tile-to-latlng?x={x}&y={y}&z={z}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "input": {"x": 50537, "y": 32369, "z": 16},
    "coordinates": {"lat": 5.329, "lng": 103.146}
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
  "layers": 50,
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
      "total_layers": 50,
      "bounds": {...},
      "generated_at": "2025-01-15T10:30:00Z"
    }
  }
}
```

#### Generate Single Layer
```http
POST /geo/generate/layer
Content-Type: application/json

{
  "count": 1000,
  "type": "LineString",
  "bounds": {...}
}
```

---

### System Information

#### API Welcome
```http
GET /
```
Returns HTML welcome page with API documentation.

#### Health Check
```http
GET /health
```

#### Interactive Map
```http
GET /maps
```
Returns full HTML page with Leaflet.js interactive map.

## 🎨 Interactive Map Interface

The system includes a sophisticated web interface at `/maps` featuring:

- **Real-time GeoJSON Generation**: Generate up to 500,000 features across 200 layers
- **Layer Management**: Toggle individual layers on/off
- **Themed Features**: Realistic Malaysian roads, buildings, and natural areas
- **Performance Optimized**: Batch processing and optimized rendering
- **Responsive Design**: Works on desktop and mobile devices

### Map Controls

- **Feature Count**: 1,000 - 500,000 features
- **Layer Count**: 1 - 200 layers
- **Generate Random**: Creates themed GeoJSON layers
- **Clear All**: Removes all generated features
- **Layer Toggles**: Show/hide individual layers

## 🏗️ Architecture Overview

### MVC Pattern Implementation

```
HTTP Request → Router → Controller → Model → View → HTTP Response
```

#### Core Components

1. **Router** (`Router.php`): Advanced pattern matching with parameter extraction
2. **Controllers**: Handle business logic and request processing
3. **Map Model** (`Map.php`): Core mapping operations and coordinate calculations
4. **View System** (`View.php`): Template rendering with layout inheritance

#### Controller Responsibilities

- **TileController**: Tile serving and caching
- **CoordinateController**: Mathematical coordinate conversions
- **GeoController**: Complex GeoJSON feature generation
- **InfoController**: System information and health checks
- **MapController**: Interactive web interface

### Request Flow

1. **Entry Point**: All requests processed by `public/index.php`
2. **Routing**: Pattern matching against `config/routes.php`
3. **Dispatch**: Controller method invocation with parameters
4. **Processing**: Business logic execution
5. **Response**: JSON API data or HTML views

## 🔧 Configuration

### System Settings (`config/config.php`)

```php
return [
    'tile_cache_dir' => '/tmp/tile_cache/',
    'tile_source' => 'https://tile.openstreetmap.org',
    'max_zoom' => 18,
    'min_zoom' => 0,
    'enable_cors' => true,
    'cache_tiles' => true,
    'cache_ttl' => 3600, // 1 hour
];
```

### Route Definitions (`config/routes.php`)

Routes use pattern matching with parameter extraction:

```php
'GET /tiles/{z}/{x}/{y}' => 'TileController@getTile',
'POST /geo/generate/layers' => 'GeoController@generateRandomFeatures',
```

## 🌍 GeoJSON Features

The system generates realistic Malaysian geographic features:

### Feature Types

1. **Roads**
   - Highway (Lebuhraya)
   - Primary roads (Jalan Utama)
   - Secondary roads
   - Residential streets

2. **Buildings**
   - Residential apartments
   - Commercial complexes
   - Industrial facilities
   - Office towers

3. **Natural Features**
   - Parks (Taman)
   - Water bodies (Tasik, Sungai)
   - Forests
   - Agricultural areas

### Malaysian Naming Convention

- Streets: Jalan, Lorong, Persiaran, Lebuh
- Areas: Kampung, Taman, Bandar, Desa
- Buildings: Apartment, Plaza, Complex, Tower
- Natural: Taman Botani, Sungai Klang, Tasik Titiwangsa

## 🚦 Error Handling

### HTTP Status Codes

- `200`: Success
- `400`: Bad Request (validation errors)
- `404`: Not Found (invalid routes)
- `500`: Internal Server Error

### Error Response Format

```json
{
  "error": true,
  "message": "Error description",
  "code": 400
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

- **Tile Caching**: Configurable cache TTL (default: 1 hour)
- **Batch Processing**: Large GeoJSON generation uses batched processing
- **Memory Management**: Optimized for large feature datasets
- **Connection Pooling**: Efficient cURL operations for tile fetching

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

## 📋 TODO / Roadmap

- [ ] **Database Integration**: Property management with MySQL/PostgreSQL
- [ ] **Authentication**: API key or JWT-based authentication
- [ ] **Rate Limiting**: Request throttling and abuse prevention
- [ ] **Tile Caching**: File-based tile cache implementation
- [ ] **WebSocket Support**: Real-time map updates
- [ ] **Docker Support**: Containerized deployment
- [ ] **API Documentation**: OpenAPI/Swagger specification
- [ ] **Unit Tests**: PHPUnit test suite
- [ ] **Performance Monitoring**: Request logging and metrics

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [OpenStreetMap](https://www.openstreetmap.org/) for tile data
- [Leaflet.js](https://leafletjs.com/) for interactive mapping
- [Tailwind CSS](https://tailwindcss.com/) for responsive design
- Malaysian geographic naming conventions

## 📞 Support

For support and questions:

- Create an [Issue](https://github.com/yourusername/maps-service-engine/issues)
- Email: your.email@domain.com
- Documentation: [Wiki](https://github.com/yourusername/maps-service-engine/wiki)

---

**Built with ❤️ for the mapping community**