<?php
require 'config.php';
$stmt = $pdo->prepare("INSERT IGNORE INTO privileges (privilege_key, privilege_name, category) VALUES ('tile_office_performance', 'View Office Performance', 'dashboard')");
$stmt->execute();
echo "Privilege added.";
