<?php
ob_start();
?>

<div class="flex-grow w-full relative">
    <!-- Vector Map Control Panel -->
    <div id="control-panel" class="absolute top-4 right-4 z-1000 bg-white rounded-lg shadow-lg p-4 max-w-sm">
        <h3 class="text-lg font-semibold mb-3 text-blue-600">Vector Maps Engine</h3>

        <!-- Map Info -->
        <div class="mb-4 p-3 bg-blue-50 rounded">
            <div class="text-sm text-blue-800">
                <div class="font-medium">Vector Tiles Only</div>
                <div>Format: PBF/MVT</div>
                <div>Max Zoom: <?= $config['max_zoom'] ?></div>
                <div>Tile Size: <?= $config['tile_size'] ?>px</div>
            </div>
        </div>

        <!-- GeoJSON Generator -->
        <div class="space-y-3 border-t pt-3">
            <h4 class="text-sm font-semibold text-gray-700">GeoJSON Generator</h4>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Features Count</label>
                <input type="number" id="feature-count" value="50000" min="1000" max="500000"
                    class="w-full px-3 py-1 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Layers Count</label>
                <input type="number" id="layer-count" value="25" min="1" max="200"
                    class="w-full px-3 py-1 border border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="flex gap-2">
                <button id="generate-btn"
                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition duration-200 font-medium">
                    Generate GeoJSON
                </button>
                <button id="clear-btn"
                    class="flex-1 bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 transition duration-200 font-medium">
                    Clear All
                </button>
            </div>

            <div id="generation-status" class="text-xs text-blue-600 hidden">
                <div class="flex items-center bg-blue-50 p-2 rounded">
                    <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-600 mr-2"></div>
                    <span>Generating vector layers...</span>
                </div>
            </div>

            <div id="layer-info" class="text-xs text-gray-600 hidden">
                <div class="bg-green-50 p-2 rounded border border-green-200">
                    <div class="font-medium text-green-800 mb-1">Generation Complete</div>
                    <div>Layers: <span id="layers-loaded" class="font-semibold">0</span></div>
                    <div>Features: <span id="features-loaded" class="font-semibold">0</span></div>
                    <div>Time: <span id="generation-time" class="font-semibold">0s</span></div>
                </div>
            </div>
        </div>

        <!-- Layer Control -->
        <div class="mt-4 border-t pt-3">
            <h4 class="text-sm font-semibold mb-2 text-gray-700">Vector Layers</h4>
            <div class="max-h-48 overflow-y-auto" id="layer-controls">
                <div class="text-xs text-gray-500 italic">No layers generated yet</div>
            </div>
        </div>

        <!-- Vector Map Stats -->
        <div class="mt-4 border-t pt-3">
            <h4 class="text-sm font-semibold mb-2 text-gray-700">Map Stats</h4>
            <div class="space-y-1 text-xs text-gray-600">
                <div>Zoom: <span id="current-zoom"><?= $config['default_zoom'] ?></span></div>
                <div>Center: <span id="current-center"><?= implode(', ', $config['center']) ?></span></div>
                <div>Loaded Tiles: <span id="loaded-tiles">0</span></div>
            </div>
        </div>
    </div>

    <!-- Main Vector Map Container -->
    <div id="map" class="w-full h-full"></div>
</div>

<style>
    .layer-toggle {
        display: flex;
        align-items: center;
        padding: 3px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .layer-toggle:last-child {
        border-bottom: none;
    }

    .layer-toggle input {
        margin-right: 8px;
        transform: scale(0.9);
    }

    .layer-toggle label {
        font-size: 11px;
        cursor: pointer;
        line-height: 1.2;
        flex: 1;
    }

    #control-panel {
        width: 320px;
        max-height: 90vh;
        overflow-y: auto;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .z-1000 {
        z-index: 1000;
    }

    /* Vector map specific styles */
    .maplibregl-ctrl-group {
        box-shadow: 0 0 0 2px rgba(0,0,0,.1);
    }

    .maplibregl-popup-content {
        padding: 10px 15px;
        border-radius: 6px;
    }

    /* Custom scrollbar */
    #layer-controls::-webkit-scrollbar {
        width: 4px;
    }

    #layer-controls::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 2px;
    }

    #layer-controls::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 2px;
    }

    /* Loading indicator */
    .vector-loading {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(59, 130, 246, 0.9);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 999;
    }
</style>

<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
<link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />

<script>
    class VectorMapEngine {
        constructor() {
            this.map = null;
            this.geoJsonLayers = new Map();
            this.loadedTiles = 0;
            this.colors = [
                '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
                '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1'
            ];
            
            this.init();
        }

        async init() {
            try {
                await this.initVectorMap();
                this.bindEvents();
                this.updateStats();
                console.log('Vector Maps Engine initialized successfully');
            } catch (error) {
                console.error('Failed to initialize Vector Maps Engine:', error);
                this.showError('Failed to load vector map. Please check your connection and configuration.');
            }
        }

        async initVectorMap() {
            // Fetch style from our server
            const styleResponse = await fetch('/style.json');
            let style;
            
            if (styleResponse.ok) {
                style = await styleResponse.json();
            } else {
                console.warn('Failed to fetch server style, using fallback');
                style = this.getDefaultStyle();
            }

            this.map = new maplibregl.Map({
                container: 'map',
                style: style,
                center: [<?= $config['center'][0] ?>, <?= $config['center'][1] ?>],
                zoom: <?= $config['default_zoom'] ?>,
                minZoom: <?= $config['min_zoom'] ?>,
                maxZoom: <?= $config['max_zoom'] ?>,
                antialias: true,
                attributionControl: true
            });

            // Add controls
            this.map.addControl(new maplibregl.NavigationControl(), 'top-left');
            this.map.addControl(new maplibregl.ScaleControl(), 'bottom-left');
            this.map.addControl(new maplibregl.FullscreenControl(), 'top-left');

            // Bind map events
            this.map.on('load', () => {
                console.log('Vector map loaded successfully');
                this.showLoadingIndicator(false);
            });

            this.map.on('moveend', () => {
                this.updateStats();
            });

            this.map.on('zoomend', () => {
                this.updateStats();
            });

            this.map.on('sourcedata', (e) => {
                if (e.sourceId && e.isSourceLoaded) {
                    this.loadedTiles++;
                    this.updateStats();
                }
            });

            // Wait for map to load
            return new Promise((resolve) => {
                this.map.on('load', resolve);
            });
        }

        getDefaultStyle() {
            return {
                "version": 8,
                "name": "Vector Maps Default",
                "sources": {
                    "openmaptiles": {
                        "type": "vector",
                        "tiles": [window.location.origin + "/tiles/{z}/{x}/{y}.pbf"]
                    }
                },
                "layers": [
                    {
                        "id": "background",
                        "type": "background",
                        "paint": {"background-color": "#f8f8f8"}
                    },
                    {
                        "id": "water",
                        "type": "fill",
                        "source": "openmaptiles",
                        "source-layer": "water",
                        "paint": {"fill-color": "#a0c8f0"}
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
                    }
                ]
            };
        }

        bindEvents() {
            document.getElementById('generate-btn').addEventListener('click', () => {
                this.generateGeoJSON();
            });

            document.getElementById('clear-btn').addEventListener('click', () => {
                this.clearAllLayers();
            });
        }

        async generateGeoJSON() {
            const featureCount = parseInt(document.getElementById('feature-count').value);
            const layerCount = parseInt(document.getElementById('layer-count').value);

            this.showGenerationStatus(true);
            const startTime = Date.now();

            try {
                const bounds = this.map.getBounds();
                const boundsObj = {
                    north: bounds.getNorth(),
                    south: bounds.getSouth(),
                    east: bounds.getEast(),
                    west: bounds.getWest()
                };

                console.log('Generating GeoJSON with bounds:', boundsObj);

                const response = await fetch('/geo/generate/layers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        count: featureCount,
                        layers: layerCount,
                        bounds: boundsObj
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Server error ${response.status}: ${errorText}`);
                }

                const data = await response.json();

                if (data.success && data.data && data.data.layers) {
                    console.log(`Received ${data.data.layers.length} layers`);
                    await this.addGeoJSONLayers(data.data.layers);

                    const endTime = Date.now();
                    const generationTime = ((endTime - startTime) / 1000).toFixed(2);

                    this.showLayerInfo(data.data.layers.length, featureCount, generationTime);
                } else {
                    throw new Error(data.message || 'Invalid response from server');
                }

            } catch (error) {
                console.error('Error generating GeoJSON:', error);
                alert('Error generating GeoJSON: ' + error.message);
            } finally {
                this.showGenerationStatus(false);
            }
        }

        async addGeoJSONLayers(layers) {
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

                    const layerId = `geojson-layer-${layerData.id || i}`;
                    const sourceId = `geojson-source-${layerData.id || i}`;

                    // Add source
                    this.map.addSource(sourceId, {
                        type: 'geojson',
                        data: layerData,
                        lineMetrics: true
                    });

                    // Add layers based on geometry types
                    this.addVectorLayersByGeometry(sourceId, layerId, layerData, color);

                    validLayersAdded++;
                    this.addLayerControl(layerData, color, layerId);

                    // Process in batches to avoid blocking
                    if (i % 10 === 0) {
                        await this.sleep(5);
                    }
                } catch (layerError) {
                    console.error('Error processing layer:', layerData.id || i, layerError);
                }
            }

            console.log(`Successfully added ${validLayersAdded} vector layers`);
            
            // Fit map to show all generated features
            this.fitMapToLayers();
        }

        addVectorLayersByGeometry(sourceId, baseLayerId, layerData, color) {
            // Analyze geometry types
            const geometryTypes = new Set();
            layerData.features.forEach(feature => {
                if (feature.geometry && feature.geometry.type) {
                    geometryTypes.add(feature.geometry.type);
                }
            });

            // Add appropriate layers for each geometry type
            geometryTypes.forEach(geomType => {
                const layerId = `${baseLayerId}-${geomType.toLowerCase()}`;
                
                let layer = {
                    id: layerId,
                    source: sourceId
                };

                switch (geomType) {
                    case 'Point':
                        layer.type = 'circle';
                        layer.filter = ['==', ['geometry-type'], 'Point'];
                        layer.paint = {
                            'circle-color': color,
                            'circle-radius': ['interpolate', ['linear'], ['zoom'], 8, 2, 16, 8],
                            'circle-opacity': 0.8,
                            'circle-stroke-color': '#ffffff',
                            'circle-stroke-width': 1
                        };
                        break;
                    
                    case 'LineString':
                    case 'MultiLineString':
                        layer.type = 'line';
                        layer.filter = ['in', ['geometry-type'], ['literal', ['LineString', 'MultiLineString']]];
                        layer.paint = {
                            'line-color': color,
                            'line-width': ['interpolate', ['linear'], ['zoom'], 8, 1, 16, 4],
                            'line-opacity': 0.8
                        };
                        layer.layout = {
                            'line-join': 'round',
                            'line-cap': 'round'
                        };
                        break;
                    
                    case 'Polygon':
                    case 'MultiPolygon':
                        layer.type = 'fill';
                        layer.filter = ['in', ['geometry-type'], ['literal', ['Polygon', 'MultiPolygon']]];
                        layer.paint = {
                            'fill-color': color,
                            'fill-opacity': 0.3,
                            'fill-outline-color': color
                        };
                        break;
                }

                this.map.addLayer(layer);
                
                // Store layer reference
                this.geoJsonLayers.set(layerId, {
                    sourceId,
                    visible: true,
                    geometryType: geomType,
                    baseLayerId
                });

                // Add click event for feature inspection
                this.map.on('click', layerId, (e) => {
                    this.showFeaturePopup(e);
                });

                // Change cursor on hover
                this.map.on('mouseenter', layerId, () => {
                    this.map.getCanvas().style.cursor = 'pointer';
                });

                this.map.on('mouseleave', layerId, () => {
                    this.map.getCanvas().style.cursor = '';
                });
            });
        }

        addLayerControl(layerData, color, layerId) {
            const layerControlsContainer = document.getElementById('layer-controls');

            const controlDiv = document.createElement('div');
            controlDiv.className = 'layer-toggle';
            const layerName = layerData.name || `Layer ${layerData.id}`;
            const featureCount = layerData.features ? layerData.features.length : 0;

            controlDiv.innerHTML = `
                <input type="checkbox" id="toggle-${layerId}" checked data-layer-id="${layerId}">
                <label for="toggle-${layerId}" style="color: ${color}">
                    <div class="font-medium">${layerName}</div>
                    <div class="text-gray-500">${featureCount} features • ${layerData.theme || 'mixed'}</div>
                </label>
            `;

            const checkbox = controlDiv.querySelector('input');
            checkbox.addEventListener('change', (e) => {
                this.toggleLayer(layerId, e.target.checked);
            });

            layerControlsContainer.appendChild(controlDiv);
        }

        toggleLayer(baseLayerId, show) {
            this.geoJsonLayers.forEach((layerInfo, layerId) => {
                if (layerInfo.baseLayerId === baseLayerId) {
                    const visibility = show ? 'visible' : 'none';
                    if (this.map.getLayer(layerId)) {
                        this.map.setLayoutProperty(layerId, 'visibility', visibility);
                    }
                }
            });
        }

        clearAllLayers() {
            this.geoJsonLayers.forEach((layerInfo, layerId) => {
                if (this.map.getLayer(layerId)) {
                    this.map.removeLayer(layerId);
                }
            });

            // Remove sources
            const sourceIds = new Set();
            this.geoJsonLayers.forEach(layerInfo => {
                sourceIds.add(layerInfo.sourceId);
            });

            sourceIds.forEach(sourceId => {
                if (this.map.getSource(sourceId)) {
                    this.map.removeSource(sourceId);
                }
            });

            this.geoJsonLayers.clear();
            document.getElementById('layer-controls').innerHTML = '<div class="text-xs text-gray-500 italic">No layers generated yet</div>';
            this.hideLayerInfo();
        }

        fitMapToLayers() {
            if (this.geoJsonLayers.size === 0) return;

            // Simple approach: use current bounds with padding
            const currentBounds = this.map.getBounds();
            this.map.fitBounds(currentBounds, {
                padding: { top: 50, bottom: 50, left: 50, right: 350 }, // Account for control panel
                maxZoom: 14
            });
        }

        showFeaturePopup(e) {
            const feature = e.features[0];
            const coordinates = e.lngLat;

            // Create popup content
            const props = feature.properties || {};
            let content = `
                <div class="vector-popup">
                    <h4 class="font-semibold text-blue-900 mb-2">${props.name || 'Feature'}</h4>
                    <div class="space-y-1 text-sm">
            `;

            Object.entries(props).forEach(([key, value]) => {
                if (key !== 'name' && value !== null && value !== undefined) {
                    content += `<div><span class="font-medium">${key}:</span> ${value}</div>`;
                }
            });

            content += `
                    <div class="mt-2 pt-2 border-t text-xs text-gray-600">
                        <div>Geometry: ${feature.geometry.type}</div>
                        <div>Layer: ${feature.layer?.id || 'unknown'}</div>
                    </div>
                </div>
            `;

            new maplibregl.Popup({
                closeButton: true,
                closeOnClick: true,
                maxWidth: '300px'
            })
                .setLngLat(coordinates)
                .setHTML(content)
                .addTo(this.map);
        }

        updateStats() {
            if (!this.map) return;

            const center = this.map.getCenter();
            const zoom = this.map.getZoom();

            document.getElementById('current-zoom').textContent = zoom.toFixed(1);
            document.getElementById('current-center').textContent = 
                `${center.lat.toFixed(4)}, ${center.lng.toFixed(4)}`;
            document.getElementById('loaded-tiles').textContent = this.loadedTiles.toString();
        }

        showGenerationStatus(show) {
            const statusElement = document.getElementById('generation-status');
            const generateBtn = document.getElementById('generate-btn');

            if (show) {
                statusElement.classList.remove('hidden');
                generateBtn.disabled = true;
                generateBtn.textContent = 'Generating...';
                generateBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                statusElement.classList.add('hidden');
                generateBtn.disabled = false;
                generateBtn.textContent = 'Generate GeoJSON';
                generateBtn.classList.remove('opacity-50', 'cursor-not-allowed');
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

        showLoadingIndicator(show) {
            let indicator = document.getElementById('vector-loading');
            
            if (show && !indicator) {
                indicator = document.createElement('div');
                indicator.id = 'vector-loading';
                indicator.className = 'vector-loading';
                indicator.textContent = 'Loading vector tiles...';
                document.getElementById('map').appendChild(indicator);
            } else if (!show && indicator) {
                indicator.remove();
            }
        }

        showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'absolute top-4 left-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
            errorDiv.innerHTML = `
                <div class="font-semibold">Error</div>
                <div class="text-sm">${message}</div>
                <button class="text-red-800 hover:text-red-900 float-right" onclick="this.parentElement.remove()">×</button>
            `;
            document.getElementById('map').appendChild(errorDiv);

            // Auto-remove after 10 seconds
            setTimeout(() => {
                if (errorDiv.parentElement) {
                    errorDiv.remove();
                }
            }, 10000);
        }

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    }

    // Initialize the Vector Maps Engine when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        window.vectorMapEngine = new VectorMapEngine();
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>