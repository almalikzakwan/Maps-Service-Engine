<?php 
ob_start(); 
?>

<div class="flex-grow w-full">
    <div id="map" class="w-full h-full"></div>
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
