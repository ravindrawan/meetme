<?php
require 'core/config.php';

try {
    // Add office_id to sections
    $pdo->exec("ALTER TABLE sections ADD COLUMN office_id INT NULL");
    echo "Added office_id to sections table.\n";

    // Add office_id to officers
    $pdo->exec("ALTER TABLE officers ADD COLUMN office_id INT NULL");
    echo "Added office_id to officers table.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
