<?php
require 'core/config.php';

try {
    echo "Dropping gn_division and address columns from visitors table...\n";
    $pdo->exec("ALTER TABLE visitors DROP COLUMN gn_division");
    $pdo->exec("ALTER TABLE visitors DROP COLUMN address");
    echo "Columns dropped successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
