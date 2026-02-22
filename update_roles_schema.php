<?php
require 'core/config.php';

try {
    $pdo->beginTransaction();

    // 1. Change role column to VARCHAR
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL");
    echo "Changed 'role' column to VARCHAR.\n";

    // 2. Create user_roles table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_key VARCHAR(50) NOT NULL UNIQUE,
        role_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created 'user_roles' table.\n";

    // 3. Seed default roles
    $default_roles = [
        ['front_officer', 'Front Officer'],
        ['officer', 'Officer'],
        ['section_head', 'Section Head'],
        ['supervisor', 'Supervisor'],
        ['admin', 'Admin'],
        ['office_admin', 'Office Admin'],
        ['office_user', 'Office User']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO user_roles (role_key, role_name) VALUES (?, ?)");
    foreach ($default_roles as $role) {
        $stmt->execute($role);
    }
    echo "Seeded default roles.\n";

    $pdo->commit();
    echo "Schema update completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error updating schema: " . $e->getMessage() . "\n");
}
?>
