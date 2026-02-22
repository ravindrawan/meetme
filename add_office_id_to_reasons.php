<?php
require 'core/config.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM visit_reasons LIKE 'office_id'");
    if ($stmt->fetch()) {
        echo "Column 'office_id' already exists in 'visit_reasons'.\n";
    } else {
        // Add column
        $pdo->exec("ALTER TABLE visit_reasons ADD COLUMN office_id INT DEFAULT NULL");
        echo "Column 'office_id' added to 'visit_reasons'.\n";
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
