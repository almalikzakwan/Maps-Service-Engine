<?php
ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-blue-600 mb-4">
            <?= htmlspecialchars($title ?? 'Vector Maps Service Engine') ?>
        </h1>
        <p class="text-xl text-gray-600 mb-2">
            Version <?= htmlspecialchars($version ?? '3.0.0') ?> • <?= htmlspecialchars($type ?? 'Vector Tiles Only') ?>
        </p>
        <p class="text-lg text-gray-500">
            High-performance vector tile service with interactive mapping capabilities
        </p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <div class="bg-blue-50 rounded-lg p-6 text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2">PBF/MVT</div>
            <div class="text-sm text-blue-800">Vector Tile Formats</div>
        </div>
        <div class="bg-green-50 rounded-lg p-6 text-center">
            <div class="text-3xl font-bold text-green-600 mb-2">14</div>
            <div class="text-sm text-green-800">Max Zoom Level</div>
        </div>
        <div class="bg-purple-50 rounded-lg p-6 text-center">
            <div class="text-3xl font-bold text-purple-600 mb-2">500K+</div>
            <div class="text-sm text-purple-800">Features per Generation</div>
        </div>
        <div class="bg-orange-50 rounded-lg p-6 text-center">
            <div class="text-3xl font-bold text-orange-600 mb-2">MapLibre</div>
            <div class="text-sm text-orange-800">GL JS Powered</div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Key Features</h2>
            <div class="space-y-3">
                <?php foreach ($features as $feature => $description): ?>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-800"><?= htmlspecialchars($feature) ?></div>
                            <div class="text-sm text-gray-600"><?= htmlspecialchars($description) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Quick Start</h2>
            <div class="space-y-4">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="font-medium text-gray-800 mb-2">1. Access Interactive Map</div>
                    <a href="/maps" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                        <span>Open Vector Map Interface</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="font-medium text-gray-800 mb-2">2. Get Vector Tiles</div>
                    <div class="text-sm font-mono bg-white p-2 rounded border">
                        <?= htmlspecialchars($endpoints['tiles']) ?>
                    </div>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="font-medium text-gray-800 mb-2">3. Fetch Style JSON</div>
                    <div class="text-sm font-mono bg-white p-2 rounded border">
                        <?= htmlspecialchars($endpoints['style']) ?>
                    </div>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="font-medium text-gray-800 mb-2">4. Generate GeoJSON</div>
                    <div class="text-sm font-mono bg-white p-2 rounded border">
                        POST /geo/generate/layers
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Routes Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">API Endpoints</h2>

        <?php foreach ($routes as $category => $categoryRoutes): ?>
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-700 mb-3"><?= htmlspecialchars($category) ?></h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($categoryRoutes as $route => $description): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 pr-4">
                                        <code class="bg-blue-100 text-blue-800 px-3 py-1 rounded text-sm font-mono">
                                                            <?= htmlspecialchars($route) ?>
                                                        </code>
                                    </td>
                                    <td class="py-3 text-sm text-gray-600">
                                        <?= htmlspecialchars($description) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Example Usage Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Example Usage</h2>

        <div class="space-y-6">
            <!-- Vector Tile Example -->
            <div>
                <h3 class="text-lg font-medium text-gray-700 mb-2">1. Fetch Vector Tile</h3>
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                    <div class="text-gray-400"># Get vector tile for zoom 10, x=537, y=369</div>
                    <div>curl "<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/tiles/10/537/369.pbf"</div>
                </div>
            </div>

            <!-- MapLibre GL JS Example -->
            <div>
                <h3 class="text-lg font-medium text-gray-700 mb-2">2. MapLibre GL JS Integration</h3>
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                    <div class="text-gray-400">// Initialize map with vector tiles</div>
                    <div><span class="text-blue-400">const</span> map = <span class="text-blue-400">new</span>
                        maplibregl.Map({</div>
                    <div> container: <span class="text-yellow-400">'map'</span>,</div>
                    <div> style: <span
                            class="text-yellow-400">'<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/style.json'</span>,
                    </div>
                    <div> center: [<span class="text-purple-400">103.146</span>, <span
                            class="text-purple-400">5.329</span>],</div>
                    <div> zoom: <span class="text-purple-400">10</span></div>
                    <div>});</div>
                </div>
            </div>

            <!-- GeoJSON Generation Example -->
            <div>
                <h3 class="text-lg font-medium text-gray-700 mb-2">3. Generate GeoJSON Features</h3>
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                    <div class="text-gray-400"># Generate 10,000 features across 25 layers</div>
                    <div>curl -X POST
                        "<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/geo/generate/layers" \</div>
                    <div> -H <span class="text-yellow-400">"Content-Type: application/json"</span> \</div>
                    <div> -d <span class="text-yellow-400">'{"count": 10000, "layers": 25}'</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vector Tile Advantages -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Why Vector Tiles?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="bg-blue-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-800 mb-2">Performance</h3>
                <p class="text-gray-600 text-sm">Smaller file sizes, faster loading, and smooth zooming experience</p>
            </div>

            <div class="text-center">
                <div class="bg-green-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z">
                        </path>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-800 mb-2">Flexibility</h3>
                <p class="text-gray-600 text-sm">Client-side styling, dynamic rendering, and interactive features</p>
            </div>

            <div class="text-center">
                <div class="bg-purple-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064">
                        </path>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-800 mb-2">Scalability</h3>
                <p class="text-gray-600 text-sm">Efficient data encoding, caching, and multi-zoom level support</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>