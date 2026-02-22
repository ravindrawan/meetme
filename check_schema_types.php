<?php
require 'core/config.php';
try {
    echo "--- Provincial Offices Table ---\n";
    $stmt = $pdo->query("DESCRIBE provincial_offices");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
