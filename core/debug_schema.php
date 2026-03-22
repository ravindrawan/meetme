<?php
require 'c:\xampp\htdocs\vms_nw\core\config.php';
$stmt = $pdo->query("SELECT * FROM user_roles");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
