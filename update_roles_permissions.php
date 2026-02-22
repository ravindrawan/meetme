<?php
require 'core/config.php';

try {
    // Update Role Enum
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('front_officer','officer','section_head','supervisor','admin','office_user','office_admin') NOT NULL";
    $pdo->exec($sql);
    echo "Role enum updated.\n";
    
    // Add Permission
    // Check if exists first to avoid duplicate error if it partially succeeded
    $stmt = $pdo->prepare("SELECT * FROM role_permissions WHERE role='office_admin' AND card_key='create_user'");
    $stmt->execute();
    if(!$stmt->fetch()){
        $sql = "INSERT INTO role_permissions (role, card_key, is_visible) VALUES ('office_admin', 'create_user', 1)";
        $pdo->exec($sql);
        echo "Permission create_user added to office_admin.\n";
    } else {
        echo "Permission already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
