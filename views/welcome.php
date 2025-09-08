<?php
$content = ob_start();
?>

<div class="bg-white rounded-lg shadow-lg p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($title) ?></h1>
    <p class="text-lg text-gray-600 mb-8">Version <?= htmlspecialchars($version) ?></p>
    
    <div class="grid md:grid-cols-2 gap-6">
        <?php foreach ($routes as $category => $categoryRoutes): ?>
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-xl font-semibold mb-4"><?= htmlspecialchars($category) ?></h3>
                <ul class="space-y-2">
                    <?php foreach ($categoryRoutes as $route => $description): ?>
                        <li class="flex justify-between items-center">
                            <code class="bg-blue-100 px-2 py-1 rounded text-sm"><?= htmlspecialchars($route) ?></code>
                            <span class="text-sm text-gray-600"><?= htmlspecialchars($description) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="mt-8 text-center">
        <a href="/dashboard" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
            View Dashboard
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
