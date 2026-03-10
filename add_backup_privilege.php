<?php
require 'core/config.php';
$stmt = $pdo->prepare("INSERT IGNORE INTO privileges (privilege_name, privilege_key, category) VALUES ('Backup System', 'tile_backup', 'System Administration')");
$stmt->execute();
echo "Inserted tile_backup privilege.";
?>
