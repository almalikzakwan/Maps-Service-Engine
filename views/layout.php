<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Maps Service Engine') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto">
            <h1 class="text-xl font-bold">Maps Service Engine</h1>
        </div>
    </nav>

    <main class="container mx-auto mt-8 p-4">
        <?= $content ?? '' ?>
    </main>

    <footer class="bg-gray-800 text-white p-4 mt-16">
        <div class="container mx-auto text-center">
            <p>&copy; 2024 Maps Service Engine. All rights reserved.</p>
        </div>
    </footer>
</body>

</html>