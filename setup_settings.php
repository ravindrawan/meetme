<?php
require 'config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        organization_name VARCHAR(255) DEFAULT 'VMS',
        organization_logo VARCHAR(255) DEFAULT NULL
    )";
    
    $pdo->exec($sql);
    
    // Insert default row if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_settings");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO system_settings (organization_name) VALUES ('VMS')");
    }
    
    echo "System settings table created successfully.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
