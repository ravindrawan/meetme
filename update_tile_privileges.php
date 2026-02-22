<?php
require 'core/config.php';

try {
    $pdo->beginTransaction();

    $privileges = [
        // Main Actions
        ['tile_register_visitor', 'Tile: Register Visitor', 'Dashboard'],
        ['tile_view_visits', 'Tile: View Visits', 'Dashboard'],
        
        // Settings & Management
        ['tile_settings', 'Tile: General Settings', 'Dashboard'],
        ['tile_manage_gn', 'Tile: Manage GN Divisions', 'Dashboard'],
        ['tile_manage_offices', 'Tile: Manage Offices', 'Dashboard'],
        ['tile_office_hierarchy', 'Tile: Office Hierarchy', 'Dashboard'],
        ['tile_manage_reasons', 'Tile: Manage Reasons', 'Dashboard'],
        ['tile_create_user', 'Tile: Create User', 'Dashboard'],
        ['tile_manage_privileges', 'Tile: Manage Privileges', 'Dashboard'],
        
        // Reports & Feedback
        ['tile_reports', 'Tile: Reports', 'Dashboard'],
        ['tile_add_feedback', 'Tile: Add Feedback', 'Dashboard'],
        ['tile_view_feedback', 'Tile: View Feedback', 'Dashboard'],
        ['tile_section_feedback', 'Tile: Section Feedback', 'Dashboard'],
        ['tile_gn_feedback', 'Tile: GN Feedback', 'Dashboard'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO privileges (privilege_key, privilege_name, category) VALUES (?, ?, ?)");
    foreach ($privileges as $p) {
        $stmt->execute($p);
    }
    
    // Grant strict admin access to all
    $stmtGrant = $pdo->prepare("INSERT IGNORE INTO role_privileges (role_key, privilege_key) VALUES (?, ?)");
    foreach ($privileges as $p) {
        $stmtGrant->execute(['admin', $p[0]]);
    }

    $pdo->commit();
    echo "Dashboard tile privileges added successfully.\n";

} catch (PDOException $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    die("Error: " . $e->getMessage() . "\n");
}
?>
