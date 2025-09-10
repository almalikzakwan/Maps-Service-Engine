# Maps Service Engine
A powerful, lightweight mapping service engine built with PHP that provides tile serving, coordinate conversion, and interactive map interfaces. Perfect for applications requiring custom map implementations with OpenStreetMap integration.

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Architecture](https://img.shields.io/badge/Architecture-MVC-orange.svg)](#architecture)
[![Mapping](https://img.shields.io/badge/Maps-OpenStreetMap-red.svg)](https://www.openstreetmap.org/)
[![Frontend](https://img.shields.io/badge/Frontend-Leaflet.js-yellow.svg)](https://leafletjs.com/)
[![Styling](https://img.shields.io/badge/CSS-Tailwind-blueviolet.svg)](https://tailwindcss.com/)
[![API](https://img.shields.io/badge/API-RESTful-success.svg)](#api-endpoints)
[![Tiles](https://img.shields.io/badge/Tiles-Cached-brightgreen.svg)](#tile-management)
[![Status](https://img.shields.io/badge/Build-Development-informational.svg)](#implementation-status)
[![Contributions](https://img.shields.io/badge/Contributions-Welcome-brightgreen.svg)](CONTRIBUTING.md)

## 🗺️ What This Project Does

**Maps Service Engine** is a comprehensive mapping solution that:

- **🎯 Serves Map Tiles**: Fetches and caches tiles from OpenStreetMap with automatic optimization
- **🔄 Converts Coordinates**: Seamless conversion between geographic coordinates and tile coordinates  
- **🖥️ Provides Interactive Maps**: Full-featured web interface with Leaflet.js integration
- **⚡ Handles High Performance**: Built-in caching, CORS support, and optimized routing
- **🛠️ RESTful API**: Clean, documented endpoints for integration with any frontend
- **📱 Responsive Design**: Mobile-friendly interface using Tailwind CSS
- **🧩 Extensible Architecture**: MVC pattern with modular controller system

### Key Features

- **Zero Dependencies**: Pure PHP implementation with no external frameworks
- **OpenStreetMap Integration**: Direct tile fetching with proper attribution
- **Coordinate System Support**: WGS84 to tile coordinate conversion and vice versa
- **Caching Layer**: Configurable tile caching for improved performance
- **Interactive Interface**: Ready-to-use map viewer with markers and controls
- **API Documentation**: Built-in route documentation and health monitoring
- **Error Handling**: Comprehensive exception handling with meaningful responses

## 🏗️ Project Structure

```
maps-service-engine/
├── 📁 public/
│   └── index.php                    # 🚪 Main entry point with routing and CORS
├── 📁 config/
│   ├── config.php                   # ⚙️ Application configuration settings
│   └── routes.php                   # 🛣️ Route definitions and mappings
├── 📁 classes/
│   ├── Router.php                   # 🧭 Advanced routing with pattern matching
│   ├── Map.php                      # 🗺️ Core mapping operations and tile management
│   ├── View.php                     # 🎨 Template rendering system
│   └── 📁 controllers/
│       ├── BaseController.php       # 🏗️ Base controller with common methods
│       ├── TileController.php       # 🎯 Tile serving and metadata (✅ Complete)
│       ├── CoordinateController.php # 📍 Coordinate conversions (✅ Complete)
│       ├── InfoController.php       # ℹ️ API documentation and health (✅ Complete)
│       ├── MapController.php        # 🖥️ Interactive map interface (✅ Complete)
│       ├── DataController.php       # 💾 Data properties (🚧 Planned)
│       ├── FilterController.php     # 🔍 Tile filtering (🚧 Planned)
│       └── ExternalController.php   # 🌐 External data sources (🚧 Planned)
├── 📁 views/
│   ├── layout.php                   # 🎭 Main HTML layout template
│   ├── welcome.php                  # 👋 API welcome and documentation page
│   └── 📁 maps/
│       └── index.php                # 🗺️ Interactive Leaflet map interface
├── 📁 template/                     # 📋 Template files (gitignored)
├── .gitignore                       # 🚫 Git ignore configuration
└── README.md                        # 📖 Project documentation
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- cURL extension enabled
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/amzhnpi/maps-service-engine.git
   cd maps-service-engine
   ```

2. **Configure your web server**
   Point document root to the `public/` directory

3. **Or use PHP built-in server**
   ```bash
   cd public
   php -S localhost:8000
   ```

4. **Access the application**
   ```
   http://localhost:8000           # API documentation
   http://localhost:8000/maps      # Interactive map interface
   ```

## 🔌 API Endpoints

### 🎯 Tile Operations
```http
GET /tiles/{z}/{x}/{y}      # Fetch tile image (PNG format)
GET /tiles/{z}/{x}/{y}.png  # Fetch tile as PNG
GET /tiles/{z}/{x}/{y}.jpg  # Fetch tile as JPG
```

### 📍 Coordinate Conversion
```http
GET /convert/latlng-to-tile?lat={lat}&lng={lng}&zoom={zoom}
GET /convert/tile-to-latlng?x={x}&y={y}&z={z}
```

### ℹ️ Information & Health
```http
GET /                       # API welcome page with documentation
GET /health                 # System health check
GET /api/info              # Detailed API information
GET /maps                  # Interactive map interface
```

### Example API Usage

**Convert coordinates to tile:**
```bash
curl "http://localhost:8000/convert/latlng-to-tile?lat=5.329&lng=103.146&zoom=16"
```

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "input": {"lat": 5.329, "lng": 103.146, "zoom": 16},
    "tile": {"x": 50537, "y": 32369, "z": 16}
  }
}
```

## 🏛️ Architecture

### Core Components

**🧭 Router System (`Router.php`)**
- Advanced pattern matching with `{parameter}` syntax
- HTTP method-based routing
- Automatic parameter extraction and validation
- Controller and method dispatching

**🗺️ Map Engine (`Map.php`)**
- OpenStreetMap tile fetching with cURL
- Coordinate system conversions (WGS84 ↔ Tile coordinates)  
- Tile boundary calculations
- Validation and error handling

**🎨 View System (`View.php`)**
- PHP template rendering engine
- Data binding and variable extraction
- Layout system with inheritance
- Responsive design with Tailwind CSS

**🏗️ Controller Architecture**
- MVC pattern implementation
- Base controller with common functionality
- JSON API responses with consistent formatting
- Error handling with proper HTTP status codes

### Technology Stack

- **Backend**: Pure PHP 8+ with strict typing
- **Frontend**: Tailwind CSS + Leaflet.js
- **Mapping**: OpenStreetMap tile services
- **HTTP Client**: cURL for tile fetching
- **Template Engine**: Native PHP with output buffering

## ⚙️ Configuration

Configure your application in `config/config.php`:

```php
return [
    'tile_cache_dir' => '/tmp/tile_cache/',           # Cache directory for tiles
    'tile_source' => 'https://tile.openstreetmap.org', # Tile provider URL
    'max_zoom' => 18,                                 # Maximum zoom level
    'min_zoom' => 0,                                  # Minimum zoom level  
    'enable_cors' => true,                            # Enable CORS headers
    'cache_tiles' => true,                            # Enable tile caching
    'cache_ttl' => 3600,                             # Cache duration (1 hour)
];
```

## 📊 Implementation Status

| Component | Status | Notes |
|-----------|--------|-------|
| 🧭 Router System | ✅ Complete | Advanced pattern matching with parameters |
| 🎯 Tile Service | ✅ Complete | Full tile operations with caching headers |
| 📍 Coordinate Conversion | ✅ Complete | Bi-directional coordinate conversion |
| 🎨 View System | ✅ Complete | Template engine with layout inheritance |
| 🗺️ Map Interface | ✅ Complete | Interactive Leaflet map with markers |
| ℹ️ Info/Health Endpoints | ✅ Complete | API documentation and health checks |
| 💾 Data Management | 🚧 Planned | Property management endpoints |
| 🔍 Filtering System | 🚧 Planned | Advanced tile filtering capabilities |
| 🌐 External Data | 🚧 Planned | External data source integration |

## 🎯 Use Cases

- **🗺️ Custom Mapping Applications**: Build tailored mapping solutions
- **📍 Location-Based Services**: Integrate geographic coordinate handling  
- **🎯 Tile Server Proxy**: Cache and serve OpenStreetMap tiles efficiently
- **🖥️ Interactive Dashboards**: Create responsive mapping interfaces
- **📱 Mobile App Backend**: RESTful API for mobile mapping applications
- **🧩 Microservice Architecture**: Standalone mapping service component

## 🛣️ Roadmap

### Phase 1: Core Enhancement (Current)
- ✅ Complete basic tile serving and coordinate conversion
- ✅ Implement interactive map interface
- ✅ Add comprehensive error handling

### Phase 2: Advanced Features (Next)
- 🚧 Implement missing controllers (Data, Filter, External)
- 🚧 Add tile caching layer with configurable TTL
- 🚧 Implement API authentication (API keys/JWT)
- 🚧 Add rate limiting and request monitoring

### Phase 3: Scaling & Optimization (Future)
- 🔮 Database integration for persistent data
- 🔮 Advanced caching strategies (Redis/Memcached)
- 🔮 Multi-tile source support
- 🔮 Performance monitoring and analytics
- 🔮 Docker containerization
- 🔮 OpenAPI/Swagger documentation

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

1. **🍴 Fork the repository**
2. **🌿 Create a feature branch** (`git checkout -b feature/amazing-feature`)
3. **💾 Commit your changes** (`git commit -m 'Add amazing feature'`)
4. **📤 Push to the branch** (`git push origin feature/amazing-feature`)
5. **🔃 Open a Pull Request**

### Development Guidelines
- Follow PSR-12 coding standards
- Add type hints for all method parameters and return types
- Include comprehensive error handling
- Write clear, descriptive commit messages
- Test your changes across different PHP versions

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **OpenStreetMap** contributors for providing free mapping data
- **Leaflet.js** for the excellent mapping library
- **Tailwind CSS** for the utility-first CSS framework
- The PHP community for continuous improvement and support

---

**Built with ❤️ by [amzhnpi](https://github.com/amzhnpi)**

*Maps Service Engine - Making geographic data accessible and interactive*