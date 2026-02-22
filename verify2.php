<?php
require 'core/config.php';
$pdo->exec("INSERT INTO vms_nw.visit_reasons (reason_text, office_id) VALUES ('Test Reason unique', 1)");
echo "Inserted first\n";
$pdo->exec("INSERT INTO vms_nw.visit_reasons (reason_text, office_id) VALUES ('Test Reason unique', 2)");
echo "Inserted second\n";

// clean up
$pdo->exec("DELETE FROM vms_nw.visit_reasons WHERE reason_text = 'Test Reason unique'");
echo "Cleaned up\n";
?>
