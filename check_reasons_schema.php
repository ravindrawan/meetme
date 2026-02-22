<?php
require 'core/config.php';
$stmt = $pdo->query('SHOW CREATE TABLE vms_nw.visit_reasons');
echo $stmt->fetchColumn(1);
?>
