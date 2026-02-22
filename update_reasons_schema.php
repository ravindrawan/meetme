<?php
require 'core/config.php';
try {
    $pdo->exec("ALTER TABLE vms_nw.visit_reasons DROP INDEX reason_text");
    echo "Successfully dropped index \n";
} catch(PDOException $e) {
    echo "Fail to drop: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE vms_nw.visit_reasons ADD UNIQUE INDEX unique_reason_per_office (reason_text, office_id)");
    echo "Successfully added composite index \n";
} catch(PDOException $e) {
    echo "Fail to add index: " . $e->getMessage() . "\n";
}
?>
