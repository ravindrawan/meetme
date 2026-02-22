<?php
require 'config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS visit_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        visit_id VARCHAR(20) NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (visit_id) REFERENCES visits(visit_id) ON DELETE CASCADE
    )");
    echo "Table 'visit_feedback' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
