<?php
$content = ob_start();
?>
<?php phpinfo(); ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
