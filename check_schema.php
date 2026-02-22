<?php
require 'core/config.php';
$tables = $pdo->query("SHOW TABLES LIKE 'dashboard_cards'")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
?>
