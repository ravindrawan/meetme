<?php
require 'core/config.php';

try {
    $pdo->beginTransaction();

    // 1. Create privileges table
    $pdo->exec("CREATE TABLE IF NOT EXISTS privileges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        privilege_key VARCHAR(50) NOT NULL UNIQUE,
        privilege_name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created 'privileges' table.\n";

    // 2. Create role_privileges table
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_privileges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_key VARCHAR(50) NOT NULL,
        privilege_key VARCHAR(50) NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY role_priv_unique (role_key, privilege_key)
    )");
    echo "Created 'role_privileges' table.\n";

    // 3. Seed default privileges
    $privileges = [
        // Dashboard
        ['view_dashboard', 'View Dashboard', 'Dashboard'],
        
        // Users
        ['manage_users', 'Manage Users', 'Users'],
        ['create_user', 'Create User', 'Users'],
        ['edit_user', 'Edit User', 'Users'],
        ['delete_user', 'Delete User', 'Users'],
        
        // Offices
        ['manage_offices', 'Manage Offices', 'Offices'],
        ['import_offices', 'Import Offices', 'Offices'],
        ['manage_office_hierarchy', 'Manage Hierarchy', 'Offices'],
        
        // Settings
        ['manage_roles', 'Manage Roles', 'Settings'],
        ['manage_privileges', 'Manage Privileges', 'Settings'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO privileges (privilege_key, privilege_name, category) VALUES (?, ?, ?)");
    foreach ($privileges as $p) {
        $stmt->execute($p);
    }
    echo "Seeded default privileges.\n";
    
    // 4. Grant All to Admin
    $admin_privs = array_map(function($p){ return $p[0]; }, $privileges);
    $stmtGrant = $pdo->prepare("INSERT IGNORE INTO role_privileges (role_key, privilege_key) VALUES (?, ?)");
    foreach ($admin_privs as $pk) {
        $stmtGrant->execute(['admin', $pk]);
    }
    echo "Granted all privileges to 'admin'.\n";

    $pdo->commit();
    echo "Privilege system setup completed successfully.\n";

} catch (PDOException $e) {
    echo "Full Error: " . $e->getMessage();
    if($pdo->inTransaction()) $pdo->rollBack();
    die("Error updating schema: " . $e->getMessage() . "\n");
}
?>
