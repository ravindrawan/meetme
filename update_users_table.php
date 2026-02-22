<?php
require 'core/config.php';

try {
    $sql = "ALTER TABLE users ADD COLUMN office_id INT NULL DEFAULT NULL AFTER section_id, ADD COLUMN created_by INT NULL DEFAULT NULL AFTER office_id";
    $pdo->exec($sql);
    echo "Columns added.\n";
    
    $sql = "ALTER TABLE users ADD CONSTRAINT fk_users_office FOREIGN KEY (office_id) REFERENCES provincial_offices(id) ON DELETE SET NULL";
    $pdo->exec($sql);
    echo "FK office added.\n";

    $sql = "ALTER TABLE users ADD CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL";
    $pdo->exec($sql);
    echo "FK created_by added.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
