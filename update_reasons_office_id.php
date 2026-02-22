<?php
require 'core/config.php';

try {
    // Modify column to BIGINT
    $pdo->exec("ALTER TABLE visit_reasons MODIFY COLUMN office_id BIGINT(20) DEFAULT NULL");
    echo "Column 'office_id' in 'visit_reasons' updated to BIGINT(20).\n";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
