<?php
require 'core/config.php';

try {
    $sql = "ALTER TABLE provincial_offices MODIFY COLUMN office_level ENUM('Level 1', 'Level 2', 'Level 3', 'Level 4', 'Level 5') NOT NULL";
    $pdo->exec($sql);
    echo "Enum updated to include Level 5.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
