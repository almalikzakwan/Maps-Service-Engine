<?php 
$content = ob_start(); 
?>

<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold mb-4">Interactive Map</h2>
    <div id="map" style="height: 400px;" class="rounded-lg"></div>
</div>

<script>

var map = L.map('map').setView([5.329, 103.146], 16);

L.tileLayer('/tiles/{z}/{x}/{y}', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18
}).addTo(map);

L.marker([5.329, 103.146])
  .addTo(map)
  .bindPopup('Bulatan Batu Bersurat<br>Kuala Terengganu')
  .openPopup();
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>