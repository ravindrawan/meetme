<?php
require 'config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        action VARCHAR(50) NOT NULL,
        attempts INT DEFAULT 1,
        last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY ip_action (ip_address, action)
    )");
    echo "Table 'rate_limits' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
