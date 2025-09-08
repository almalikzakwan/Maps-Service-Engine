<?php
ob_start();
?>

<div class="flex-grow w-full relative">
    <!-- Custom Control Panel -->
    <div id="control-panel" class="absolute top-4 right-4 z-1000 bg-white rounded-lg shadow-lg p-4 max-w-sm">
        <h3 class="text-lg font-semibold mb-3">GeoJSON Generator</h3>

        <div class="space-y-3">
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
                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition">
                    Generate Random
                </button>
                <button id="clear-btn"
                    class="flex-1 bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 transition">
                    Clear All
                </button>
            </div>

            <div id="generation-status" class="text-xs text-gray-600 hidden">
                <div class="flex items-center">
                    <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-600 mr-2"></div>
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
        width: 280px;
        max-height: 600px;
        overflow-y: auto;
    }

    .z-1000 {
        z-index: 1000;
    }
</style>

<script>
    class GeoJSONMapManager {
        constructor() {
            this.map = null;
            this.geoJsonLayers = new Map();
            this.layerGroup = null;
            this.colors = [
                '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
                '#DDA0DD', '#98D8C8', '#A8E6CF', '#FFD93D', '#6C5CE7'
            ];
            this.initMap();
            this.bindEvents();
        }

        initMap() {
            // Initialize map
            this.map = L.map('map').setView([5.329, 103.146], 16);

            // Add base tile layer
            L.tileLayer('/tiles/{z}/{x}/{y}', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(this.map);

            // Initialize layer group for generated features
            this.layerGroup = L.layerGroup().addTo(this.map);
        }

        bindEvents() {
            document.getElementById('generate-btn').addEventListener('click', () => {
                this.generateRandomFeatures();
            });

            document.getElementById('clear-btn').addEventListener('click', () => {
                this.clearAllLayers();
            });
        }

        async generateRandomFeatures() {
            const featureCount = parseInt(document.getElementById('feature-count').value);
            const layerCount = parseInt(document.getElementById('layer-count').value);

            // Show loading status
            this.showGenerationStatus(true);

            const startTime = Date.now();

            try {
                // Get map bounds
                const bounds = this.map.getBounds();
                const boundsData = {
                    north: bounds.getNorth(),
                    south: bounds.getSouth(),
                    east: bounds.getEast(),
                    west: bounds.getWest()
                };

                console.log('Generating features with bounds:', boundsData);

                const response = await fetch('/geo/generate/layers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        count: featureCount,
                        layers: layerCount,
                        bounds: boundsData
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

                    await this.addLayersToMap(data.data.layers);

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

        async addLayersToMap(layers) {
            // Clear existing layers
            this.clearAllLayers();

            const layerControlsContainer = document.getElementById('layer-controls');
            layerControlsContainer.innerHTML = '';

            let validLayersAdded = 0;

            // Add each layer with batch processing for performance
            for (let i = 0; i < layers.length; i++) {
                const layerData = layers[i];
                const color = this.colors[i % this.colors.length];

                try {
                    // Validate layer data first
                    if (!layerData.features || !Array.isArray(layerData.features) || layerData.features.length === 0) {
                        console.warn('Layer has no valid features:', layerData.id || i);
                        continue;
                    }

                    // Create layer with optimized styling
                    const geoJsonLayer = L.geoJSON(layerData, {
                        style: (feature) => this.getFeatureStyle(feature, color),
                        pointToLayer: (feature, latlng) => this.createMarker(feature, latlng, color),
                        onEachFeature: (feature, layer) => this.bindFeaturePopup(feature, layer)
                    });

                    // Only add layer if it has valid features
                    if (geoJsonLayer.getLayers().length > 0) {
                        this.layerGroup.addLayer(geoJsonLayer);
                        this.geoJsonLayers.set(layerData.id || i, geoJsonLayer);
                        validLayersAdded++;

                        // Add layer control
                        this.addLayerControl(layerData, color);
                    } else {
                        console.warn('GeoJSON layer created but has no Leaflet layers:', layerData.id || i);
                    }

                    // Process in batches to avoid blocking UI
                    if (i % 10 === 0) {
                        await this.sleep(10);
                    }
                } catch (layerError) {
                    console.error('Error processing layer:', layerData.id || i, layerError);
                }
            }

            console.log(`Successfully added ${validLayersAdded} layers out of ${layers.length}`);

            // Fit map to show all layers - with safe bounds checking
            this.fitMapToLayers();
        }

        fitMapToLayers() {
            try {
                // Check if we have any layers in the layer group
                const layerCount = this.layerGroup.getLayers().length;

                if (layerCount === 0) {
                    console.log('No layers to fit bounds to, using default view');
                    this.fallbackToDefaultView();
                    return;
                }

                console.log(`Attempting to fit bounds for ${layerCount} layers`);

                // Method 1: Try to get bounds directly from layerGroup
                try {
                    const bounds = this.layerGroup.getBounds();
                    if (bounds && this.isValidBounds(bounds)) {
                        this.map.fitBounds(bounds, {
                            padding: [20, 20],
                            maxZoom: 16
                        });
                        console.log('Successfully fitted bounds using layerGroup.getBounds()');
                        return;
                    }
                } catch (boundsError) {
                    console.warn('layerGroup.getBounds() failed:', boundsError.message);
                }

                // Method 2: Try to collect bounds from individual layers
                let validBounds = [];
                this.layerGroup.eachLayer((layer) => {
                    try {
                        if (layer.getBounds && typeof layer.getBounds === 'function') {
                            const layerBounds = layer.getBounds();
                            if (layerBounds && this.isValidBounds(layerBounds)) {
                                validBounds.push(layerBounds);
                            }
                        }
                    } catch (e) {
                        console.warn('Could not get bounds for individual layer:', e.message);
                    }
                });

                if (validBounds.length > 0) {
                    // Create combined bounds from all valid layer bounds
                    let minLat = 90, maxLat = -90, minLng = 180, maxLng = -180;

                    validBounds.forEach(bounds => {
                        const sw = bounds.getSouthWest();
                        const ne = bounds.getNorthEast();

                        minLat = Math.min(minLat, sw.lat);
                        maxLat = Math.max(maxLat, ne.lat);
                        minLng = Math.min(minLng, sw.lng);
                        maxLng = Math.max(maxLng, ne.lng);
                    });

                    const combinedBounds = L.latLngBounds([minLat, minLng], [maxLat, maxLng]);

                    if (this.isValidBounds(combinedBounds)) {
                        this.map.fitBounds(combinedBounds, {
                            padding: [20, 20],
                            maxZoom: 16
                        });
                        console.log('Successfully fitted bounds using combined layer bounds');
                        return;
                    }
                }

                // Method 3: Fallback to default view
                console.log('All bounds calculation methods failed, using fallback');
                this.fallbackToDefaultView();

            } catch (error) {
                console.error('Error in fitMapToLayers:', error);
                this.fallbackToDefaultView();
            }
        }

        isValidBounds(bounds) {
            if (!bounds) return false;

            try {
                const sw = bounds.getSouthWest();
                const ne = bounds.getNorthEast();

                // Check if bounds have valid coordinates
                if (!sw || !ne) return false;

                const isValid = (
                    isFinite(sw.lat) && isFinite(sw.lng) &&
                    isFinite(ne.lat) && isFinite(ne.lng) &&
                    sw.lat >= -90 && sw.lat <= 90 &&
                    ne.lat >= -90 && ne.lat <= 90 &&
                    sw.lng >= -180 && sw.lng <= 180 &&
                    ne.lng >= -180 && ne.lng <= 180 &&
                    sw.lat < ne.lat && sw.lng < ne.lng
                );

                return isValid;
            } catch (e) {
                console.warn('Error validating bounds:', e.message);
                return false;
            }
        }

        fallbackToDefaultView() {
            console.log('Using fallback map view');
            this.map.setView([5.329, 103.146], 14);
        }

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
                        <p class="text-xs text-gray-600 mt-1">${feature.properties.description || 'No description available'}</p>
                    </div>
                `;
                layer.bindPopup(popupContent);
            }
        }

        addLayerControl(layerData, color) {
            const layerControlsContainer = document.getElementById('layer-controls');

            const controlDiv = document.createElement('div');
            controlDiv.className = 'layer-toggle';
            const layerId = layerData.id || Math.random().toString(36).substr(2, 9);
            const layerName = layerData.name || `Layer ${layerId}`;
            const featureCount = layerData.features ? layerData.features.length : 0;

            controlDiv.innerHTML = `
                <input type="checkbox" id="layer-${layerId}" checked data-layer-id="${layerId}">
                <label for="layer-${layerId}" style="color: ${color}">
                    ${layerName} (${featureCount} features)
                </label>
            `;

            const checkbox = controlDiv.querySelector('input');
            checkbox.addEventListener('change', (e) => {
                this.toggleLayer(layerId, e.target.checked);
            });

            layerControlsContainer.appendChild(controlDiv);
        }

        toggleLayer(layerId, show) {
            const layer = this.geoJsonLayers.get(layerId);
            if (layer) {
                if (show) {
                    this.layerGroup.addLayer(layer);
                } else {
                    this.layerGroup.removeLayer(layer);
                }
            }
        }

        clearAllLayers() {
            this.layerGroup.clearLayers();
            this.geoJsonLayers.clear();
            document.getElementById('layer-controls').innerHTML = '';
            this.hideLayerInfo();
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
                generateBtn.textContent = 'Generate Random';
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
        window.geoMapManager = new GeoJSONMapManager();
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>