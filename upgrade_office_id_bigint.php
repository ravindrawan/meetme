<?php
require 'core/config.php';

try {
    // Upgrade sections.office_id to BIGINT
    $pdo->exec("ALTER TABLE sections MODIFY COLUMN office_id BIGINT NULL");
    echo "Upgraded sections.office_id to BIGINT.\n";

    // Upgrade officers.office_id to BIGINT
    $pdo->exec("ALTER TABLE officers MODIFY COLUMN office_id BIGINT NULL");
    echo "Upgraded officers.office_id to BIGINT.\n";
    
    // Attempt to fix truncated data if possible?
    // We can't know the original ID unless we assume logged in user created them recently.
    // For now, let's just fix the schema. User might need to re-enter data or manually fix.

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
