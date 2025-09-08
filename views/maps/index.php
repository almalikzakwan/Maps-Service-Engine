<?php
ob_start();
?>

<div class="flex-grow w-full relative">
    <!-- Custom Control Panel -->
    <div id="control-panel" class="absolute top-4 right-4 z-1000 bg-white rounded-lg shadow-lg p-4 max-w-sm">
        <h3 class="text-lg font-semibold mb-3">Vector Map Controls</h3>

        <!-- Tile Type Selector -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tile Type</label>
            <div class="flex gap-2">
                <button id="vector-btn" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition">
                    Vector Tiles
                </button>
                <button id="raster-btn" class="flex-1 bg-gray-600 text-white px-3 py-2 rounded text-sm hover:bg-gray-700 transition">
                    Raster Tiles
                </button>
            </div>
        </div>

        <!-- GeoJSON Generator -->
        <div class="space-y-3 border-t pt-3">
            <h4 class="text-sm font-semibold">GeoJSON Generator</h4>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Features Count</label>
                <input type="number" id="feature-count" value="100000" min="1000" max="500000"
                    class="w-full px-3 py-1 border border-gray-300 rounded text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Layers Count</label>
                <input type="number" id="layer-count" value="100" min="1" max="200"
                    class="w-full px-3 py-1 border border-gray-300 rounded text-sm">
            </div>

            <div class="flex gap-2">
                <button id="generate-btn"
                    class="flex-1 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 transition">
                    Generate
                </button>
                <button id="clear-btn"
                    class="flex-1 bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 transition">
                    Clear All
                </button>
            </div>

            <div id="generation-status" class="text-xs text-gray-600 hidden">
                <div class="flex items-center">
                    <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-green-600 mr-2"></div>
                    <span>Generating layers...</span>
                </div>
            </div>

            <div id="layer-info" class="text-xs text-gray-600 hidden">
                <div class="bg-gray-50 p-2 rounded">
                    <div>Layers: <span id="layers-loaded">0</span></div>
                    <div>Features: <span id="features-loaded">0</span></div>
                    <div>Generation Time: <span id="generation-time">0s</span></div>
                </div>
            </div>
        </div>

        <!-- Layer Control -->
        <div class="mt-4 border-t pt-3">
            <h4 class="text-sm font-semibold mb-2">Layer Controls</h4>
            <div class="max-h-40 overflow-y-auto" id="layer-controls">
                <!-- Layer toggles will be inserted here -->
            </div>
        </div>
    </div>

    <!-- Main Map Container -->
    <div id="map" class="w-full h-full"></div>
</div>

<style>
    .leaflet-control-layers {
        max-height: 300px;
        overflow-y: auto;
    }

    .layer-toggle {
        display: flex;
        align-items: center;
        padding: 2px 0;
    }

    .layer-toggle input {
        margin-right: 6px;
    }

    .layer-toggle label {
        font-size: 11px;
        cursor: pointer;
    }

    #control-panel {
        width: 300px;
        max-height: 600px;
        overflow-y: auto;
    }

    .z-1000 {
        z-index: 1000;
    }

    /* Vector tile loading indicator */
    .maplibre-ctrl-loading {
        background-color: rgba(0, 0, 0, 0.1);
    }
</style>

<script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
<link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />

<script>
    class VectorMapManager {
        constructor() {
            this.leafletMap = null;
            this.vectorMap = null;
            this.currentMapType = 'vector'; // 'vector' or 'raster'
            this.geoJsonLayers = new Map();
            this.layerGroup = null;
            this.colors = [
                '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
                '#DDA0DD', '#98D8C8', '#A8E6CF', '#FFD93D', '#6C5CE7'
            ];
            this.initMaps();
            this.bindEvents();
        }

        async initMaps() {
            // Initialize vector map first
            await this.initVectorMap();
            this.showVectorMap();
        }

        async initVectorMap() {
            try {
                // Fetch style from server
                const styleResponse = await fetch('/style.json');
                let style;
                
                if (styleResponse.ok) {
                    style = await styleResponse.json();
                } else {
                    // Fallback style if server style fails
                    style = this.getDefaultVectorStyle();
                }

                this.vectorMap = new maplibregl.Map({
                    container: 'map',
                    style: style,
                    center: [103.146, 5.329], // Malaysia
                    zoom: 10,
                    maxZoom: 18
                });

                // Add navigation controls
                this.vectorMap.addControl(new maplibregl.NavigationControl());
                
                // Add scale control
                this.vectorMap.addControl(new maplibregl.ScaleControl());

                // Wait for map to load
                await new Promise((resolve) => {
                    this.vectorMap.on('load', resolve);
                });

                console.log('Vector map initialized successfully');
            } catch (error) {
                console.error('Error initializing vector map:', error);
                // Fallback to raster map
                this.initRasterMap();
                this.showRasterMap();
            }
        }

        initRasterMap() {
            this.leafletMap = L.map('map').setView([5.329, 103.146], 10);

            // Add raster tile layer
            L.tileLayer('/tiles/{z}/{x}/{y}', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(this.leafletMap);

            // Initialize layer group for generated features
            this.layerGroup = L.layerGroup().addTo(this.leafletMap);
            
            console.log('Raster map initialized successfully');
        }

        getDefaultVectorStyle() {
            return {
                "version": 8,
                "name": "Basic Vector Style",
                "sources": {
                    "openmaptiles": {
                        "type": "vector",
                        "tiles": [window.location.origin + "/tiles/{z}/{x}/{y}.pbf"],
                        "minzoom": 0,
                        "maxzoom": 14
                    }
                },
                "layers": [
                    {
                        "id": "background",
                        "type": "background",
                        "paint": {
                            "background-color": "#f8f8f8"
                        }
                    },
                    {
                        "id": "water",
                        "type": "fill",
                        "source": "openmaptiles",
                        "source-layer": "water",
                        "paint": {
                            "fill-color": "#a0c8f0"
                        }
                    },
                    {
                        "id": "landcover",
                        "type": "fill",
                        "source": "openmaptiles",
                        "source-layer": "landcover",
                        "paint": {
                            "fill-color": "#d8e8c8"
                        }
                    },
                    {
                        "id": "roads",
                        "type": "line",
                        "source": "openmaptiles",
                        "source-layer": "transportation",
                        "paint": {
                            "line-color": "#ffffff",
                            "line-width": ["interpolate", ["linear"], ["zoom"], 8, 1, 14, 4]
                        }
                    },
                    {
                        "id": "buildings",
                        "type": "fill",
                        "source": "openmaptiles",
                        "source-layer": "building",
                        "minzoom": 13,
                        "paint": {
                            "fill-color": "#e0e0e0",
                            "fill-outline-color": "#cccccc"
                        }
                    },
                    {
                        "id": "place-labels",
                        "type": "symbol",
                        "source": "openmaptiles",
                        "source-layer": "place",
                        "layout": {
                            "text-field": ["get", "name"],
                            "text-font": ["Open Sans Regular"],
                            "text-size": 12
                        },
                        "paint": {
                            "text-color": "#333333"
                        }
                    }
                ]
            };
        }

        bindEvents() {
            // Map type switcher
            document.getElementById('vector-btn').addEventListener('click', () => {
                this.switchToVector();
            });

            document.getElementById('raster-btn').addEventListener('click', () => {
                this.switchToRaster();
            });

            // GeoJSON controls
            document.getElementById('generate-btn').addEventListener('click', () => {
                this.generateRandomFeatures();
            });

            document.getElementById('clear-btn').addEventListener('click', () => {
                this.clearAllLayers();
            });
        }

        async switchToVector() {
            if (!this.vectorMap) {
                await this.initVectorMap();
            }
            this.showVectorMap();
            this.currentMapType = 'vector';
            this.updateButtonStates();
        }

        switchToRaster() {
            if (!this.leafletMap) {
                this.initRasterMap();
            }
            this.showRasterMap();
            this.currentMapType = 'raster';
            this.updateButtonStates();
        }

        showVectorMap() {
            if (this.leafletMap) {
                document.getElementById('map').innerHTML = '';
            }
            
            if (this.vectorMap) {
                this.vectorMap.getContainer().style.display = 'block';
                this.vectorMap.resize();
            }
        }

        showRasterMap() {
            if (this.vectorMap) {
                this.vectorMap.getContainer().style.display = 'none';
            }

            if (!this.leafletMap) {
                this.initRasterMap();
            }
        }

        updateButtonStates() {
            const vectorBtn = document.getElementById('vector-btn');
            const rasterBtn = document.getElementById('raster-btn');

            if (this.currentMapType === 'vector') {
                vectorBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                vectorBtn.classList.remove('bg-gray-400');
                rasterBtn.classList.add('bg-gray-400');
                rasterBtn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
            } else {
                rasterBtn.classList.add('bg-gray-600', 'hover:bg-gray-700');
                rasterBtn.classList.remove('bg-gray-400');
                vectorBtn.classList.add('bg-gray-400');
                vectorBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            }
        }

        async generateRandomFeatures() {
            const featureCount = parseInt(document.getElementById('feature-count').value);
            const layerCount = parseInt(document.getElementById('layer-count').value);

            this.showGenerationStatus(true);

            const startTime = Date.now();

            try {
                // Get current bounds based on active map
                let bounds;
                if (this.currentMapType === 'vector' && this.vectorMap) {
                    const mapBounds = this.vectorMap.getBounds();
                    bounds = {
                        north: mapBounds.getNorth(),
                        south: mapBounds.getSouth(),
                        east: mapBounds.getEast(),
                        west: mapBounds.getWest()
                    };
                } else if (this.currentMapType === 'raster' && this.leafletMap) {
                    const mapBounds = this.leafletMap.getBounds();
                    bounds = {
                        north: mapBounds.getNorth(),
                        south: mapBounds.getSouth(),
                        east: mapBounds.getEast(),
                        west: mapBounds.getWest()
                    };
                } else {
                    // Default bounds
                    bounds = {
                        north: 5.340,
                        south: 5.320,
                        east: 103.160,
                        west: 103.140
                    };
                }

                console.log('Generating features with bounds:', bounds);

                const response = await fetch('/geo/generate/layers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        count: featureCount,
                        layers: layerCount,
                        bounds: bounds
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server response error:', response.status, errorText);
                    throw new Error(`Server error ${response.status}: ${errorText.slice(0, 100)}...`);
                }

                const data = await response.json();

                if (data.success && data.data && data.data.layers) {
                    console.log(`Received ${data.data.layers.length} layers from server`);

                    if (this.currentMapType === 'vector') {
                        await this.addLayersToVectorMap(data.data.layers);
                    } else {
                        await this.addLayersToRasterMap(data.data.layers);
                    }

                    const endTime = Date.now();
                    const generationTime = ((endTime - startTime) / 1000).toFixed(2);

                    this.showLayerInfo(data.data.layers.length, featureCount, generationTime);
                } else {
                    throw new Error(data.message || 'Invalid response format from server');
                }

            } catch (error) {
                console.error('Error generating features:', error);
                alert('Error generating features: ' + error.message);
            } finally {
                this.showGenerationStatus(false);
            }
        }

        async addLayersToVectorMap(layers) {
            // Clear existing GeoJSON layers from vector map
            this.clearVectorLayers();

            const layerControlsContainer = document.getElementById('layer-controls');
            layerControlsContainer.innerHTML = '';

            let validLayersAdded = 0;

            for (let i = 0; i < layers.length; i++) {
                const layerData = layers[i];
                const color = this.colors[i % this.colors.length];

                try {
                    if (!layerData.features || !Array.isArray(layerData.features) || layerData.features.length === 0) {
                        console.warn('Layer has no valid features:', layerData.id || i);
                        continue;
                    }

                    const layerId = `geojson-layer-${layerData.id || i}`;

                    // Add source
                    this.vectorMap.addSource(layerId, {
                        type: 'geojson',
                        data: layerData
                    });

                    // Add layers based on geometry types
                    this.addVectorLayersByGeometry(layerId, layerData, color);

                    validLayersAdded++;
                    this.addLayerControl(layerData, color, true); // true for vector map

                    // Process in batches
                    if (i % 5 === 0) {
                        await this.sleep(10);
                    }
                } catch (layerError) {
                    console.error('Error processing vector layer:', layerData.id || i, layerError);
                }
            }

            console.log(`Successfully added ${validLayersAdded} vector layers out of ${layers.length}`);
            this.fitVectorMapToLayers();
        }

        addVectorLayersByGeometry(sourceId, layerData, color) {
            // Analyze geometry types in the layer
            const geometryTypes = new Set();
            layerData.features.forEach(feature => {
                if (feature.geometry && feature.geometry.type) {
                    geometryTypes.add(feature.geometry.type);
                }
            });

            // Add appropriate layers for each geometry type
            geometryTypes.forEach(geomType => {
                const layerId = `${sourceId}-${geomType.toLowerCase()}`;
                
                switch (geomType) {
                    case 'Point':
                        this.vectorMap.addLayer({
                            id: layerId,
                            type: 'circle',
                            source: sourceId,
                            filter: ['==', ['geometry-type'], 'Point'],
                            paint: {
                                'circle-color': color,
                                'circle-radius': 4,
                                'circle-opacity': 0.8
                            }
                        });
                        break;
                    
                    case 'LineString':
                    case 'MultiLineString':
                        this.vectorMap.addLayer({
                            id: layerId,
                            type: 'line',
                            source: sourceId,
                            filter: ['in', ['geometry-type'], ['literal', ['LineString', 'MultiLineString']]],
                            paint: {
                                'line-color': color,
                                'line-width': 2,
                                'line-opacity': 0.8
                            }
                        });
                        break;
                    
                    case 'Polygon':
                    case 'MultiPolygon':
                        this.vectorMap.addLayer({
                            id: layerId,
                            type: 'fill',
                            source: sourceId,
                            filter: ['in', ['geometry-type'], ['literal', ['Polygon', 'MultiPolygon']]],
                            paint: {
                                'fill-color': color,
                                'fill-opacity': 0.3,
                                'fill-outline-color': color
                            }
                        });
                        break;
                }

                // Store layer reference
                this.geoJsonLayers.set(layerId, {
                    sourceId,
                    visible: true,
                    geometryType: geomType
                });
            });
        }

        async addLayersToRasterMap(layers) {
            // Use existing Leaflet implementation
            this.clearAllLayers();

            const layerControlsContainer = document.getElementById('layer-controls');
            layerControlsContainer.innerHTML = '';

            let validLayersAdded = 0;

            for (let i = 0; i < layers.length; i++) {
                const layerData = layers[i];
                const color = this.colors[i % this.colors.length];

                try {
                    if (!layerData.features || !Array.isArray(layerData.features) || layerData.features.length === 0) {
                        console.warn('Layer has no valid features:', layerData.id || i);
                        continue;
                    }

                    const geoJsonLayer = L.geoJSON(layerData, {
                        style: (feature) => this.getFeatureStyle(feature, color),
                        pointToLayer: (feature, latlng) => this.createMarker(feature, latlng, color),
                        onEachFeature: (feature, layer) => this.bindFeaturePopup(feature, layer)
                    });

                    if (geoJsonLayer.getLayers().length > 0) {
                        this.layerGroup.addLayer(geoJsonLayer);
                        this.geoJsonLayers.set(layerData.id || i, geoJsonLayer);
                        validLayersAdded++;

                        this.addLayerControl(layerData, color, false); // false for raster map
                    }

                    if (i % 10 === 0) {
                        await this.sleep(10);
                    }
                } catch (layerError) {
                    console.error('Error processing raster layer:', layerData.id || i, layerError);
                }
            }

            console.log(`Successfully added ${validLayersAdded} raster layers out of ${layers.length}`);
            this.fitMapToLayers();
        }

        fitVectorMapToLayers() {
            if (!this.vectorMap || this.geoJsonLayers.size === 0) {
                return;
            }

            // Simple approach: use current view or default bounds
            // In a real implementation, you'd calculate bounds from all sources
            this.vectorMap.fitBounds([
                [103.140, 5.320], // Southwest
                [103.160, 5.340]  // Northeast
            ], {
                padding: 20
            });
        }

        clearVectorLayers() {
            this.geoJsonLayers.forEach((layerInfo, layerId) => {
                if (this.vectorMap.getLayer(layerId)) {
                    this.vectorMap.removeLayer(layerId);
                }
                if (this.vectorMap.getSource(layerInfo.sourceId)) {
                    this.vectorMap.removeSource(layerInfo.sourceId);
                }
            });
            this.geoJsonLayers.clear();
        }

        clearAllLayers() {
            if (this.currentMapType === 'vector') {
                this.clearVectorLayers();
            } else if (this.layerGroup) {
                this.layerGroup.clearLayers();
                this.geoJsonLayers.clear();
            }
            
            document.getElementById('layer-controls').innerHTML = '';
            this.hideLayerInfo();
        }

        // Keep existing helper methods for raster map...
        getFeatureStyle(feature, color) {
            const geometryType = feature.geometry.type;

            switch (geometryType) {
                case 'LineString':
                case 'MultiLineString':
                    return {
                        color: color,
                        weight: 2,
                        opacity: 0.7
                    };
                case 'Polygon':
                    return {
                        fillColor: color,
                        color: color,
                        weight: 1,
                        opacity: 0.8,
                        fillOpacity: 0.3
                    };
                default:
                    return {
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.6
                    };
            }
        }

        createMarker(feature, latlng, color) {
            return L.circleMarker(latlng, {
                radius: 4,
                fillColor: color,
                color: color,
                weight: 1,
                opacity: 0.8,
                fillOpacity: 0.6
            });
        }

        bindFeaturePopup(feature, layer) {
            if (feature.properties) {
                const popupContent = `
                    <div class="text-sm">
                        <h4 class="font-semibold">${feature.properties.name || 'Unnamed Feature'}</h4>
                        <p><strong>Type:</strong> ${feature.properties.type || 'Unknown'}</p>
                        <p><strong>ID:</strong> ${feature.properties.id || 'N/A'}</p>
                        <p><strong>Theme:</strong> ${feature.properties.theme || 'N/A'}</p>
                    </div>
                `;
                layer.bindPopup(popupContent);
            }
        }

        addLayerControl(layerData, color, isVector = false) {
            const layerControlsContainer = document.getElementById('layer-controls');

            const controlDiv = document.createElement('div');
            controlDiv.className = 'layer-toggle';
            const layerId = layerData.id || Math.random().toString(36).substr(2, 9);
            const layerName = layerData.name || `Layer ${layerId}`;
            const featureCount = layerData.features ? layerData.features.length : 0;

            controlDiv.innerHTML = `
                <input type="checkbox" id="layer-${layerId}" checked data-layer-id="${layerId}">
                <label for="layer-${layerId}" style="color: ${color}">
                    ${layerName} (${featureCount} features) ${isVector ? '[Vector]' : '[Raster]'}
                </label>
            `;

            const checkbox = controlDiv.querySelector('input');
            checkbox.addEventListener('change', (e) => {
                this.toggleLayer(layerId, e.target.checked);
            });

            layerControlsContainer.appendChild(controlDiv);
        }

        toggleLayer(layerId, show) {
            if (this.currentMapType === 'vector') {
                // Toggle vector layers
                this.geoJsonLayers.forEach((layerInfo, fullLayerId) => {
                    if (fullLayerId.includes(layerId)) {
                        const visibility = show ? 'visible' : 'none';
                        if (this.vectorMap.getLayer(fullLayerId)) {
                            this.vectorMap.setLayoutProperty(fullLayerId, 'visibility', visibility);
                        }
                    }
                });
            } else {
                // Toggle raster layers
                const layer = this.geoJsonLayers.get(layerId);
                if (layer && this.layerGroup) {
                    if (show) {
                        this.layerGroup.addLayer(layer);
                    } else {
                        this.layerGroup.removeLayer(layer);
                    }
                }
            }
        }

        fitMapToLayers() {
            if (!this.layerGroup || this.layerGroup.getLayers().length === 0) {
                return;
            }

            try {
                const bounds = this.layerGroup.getBounds();
                if (bounds && this.isValidBounds(bounds)) {
                    this.leafletMap.fitBounds(bounds, {
                        padding: [20, 20],
                        maxZoom: 16
                    });
                }
            } catch (error) {
                console.error('Error fitting map to layers:', error);
            }
        }

        isValidBounds(bounds) {
            if (!bounds) return false;
            try {
                const sw = bounds.getSouthWest();
                const ne = bounds.getNorthEast();
                return sw && ne && isFinite(sw.lat) && isFinite(sw.lng) && isFinite(ne.lat) && isFinite(ne.lng);
            } catch (e) {
                return false;
            }
        }

        showGenerationStatus(show) {
            const statusElement = document.getElementById('generation-status');
            const generateBtn = document.getElementById('generate-btn');

            if (show) {
                statusElement.classList.remove('hidden');
                generateBtn.disabled = true;
                generateBtn.textContent = 'Generating...';
            } else {
                statusElement.classList.add('hidden');
                generateBtn.disabled = false;
                generateBtn.textContent = 'Generate';
            }
        }

        showLayerInfo(layerCount, featureCount, generationTime) {
            document.getElementById('layers-loaded').textContent = layerCount;
            document.getElementById('features-loaded').textContent = featureCount.toLocaleString();
            document.getElementById('generation-time').textContent = generationTime + 's';
            document.getElementById('layer-info').classList.remove('hidden');
        }

        hideLayerInfo() {
            document.getElementById('layer-info').classList.add('hidden');
        }

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    }

    // Initialize the map manager when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        window.vectorMapManager = new VectorMapManager();
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>