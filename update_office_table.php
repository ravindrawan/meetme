<?php
require 'core/config.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM provincial_offices LIKE 'parent_office_id'");
    if(!$stmt->fetch()){
        $sql = "ALTER TABLE provincial_offices ADD COLUMN parent_office_id INT NULL DEFAULT NULL AFTER office_level";
        $pdo->exec($sql);
        echo "Column parent_office_id added.\n";
        
        $sql = "ALTER TABLE provincial_offices ADD CONSTRAINT fk_office_parent FOREIGN KEY (parent_office_id) REFERENCES provincial_offices(id) ON DELETE SET NULL";
        $pdo->exec($sql);
        echo "FK fk_office_parent added.\n";
    } else {
        echo "Column parent_office_id already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
