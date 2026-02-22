<?php
require 'core/config.php';

try {
    $pdo->beginTransaction();

    // 1. Remove GN Privileges
    $gn_keys = ['tile_manage_gn', 'tile_gn_feedback'];
    $placeholders = implode(',', array_fill(0, count($gn_keys), '?'));
    
    // Delete from role_privileges first (FK constraint)
    $stmt = $pdo->prepare("DELETE FROM role_privileges WHERE privilege_key IN ($placeholders)");
    $stmt->execute($gn_keys);
    
    // Delete from privileges
    $stmt = $pdo->prepare("DELETE FROM privileges WHERE privilege_key IN ($placeholders)");
    $stmt->execute($gn_keys);
    
    echo "Removed GN privileges.\n";

    // 2. Add Visit Action Privileges
    $new_privs = [
        ['visit_action', 'Visit: Actions Button', 'Visits'],
        ['visit_edit', 'Visit: Edit Button', 'Visits'],
        ['visit_delete', 'Visit: Delete Button', 'Visits']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO privileges (privilege_key, privilege_name, category) VALUES (?, ?, ?)");
    
    foreach ($new_privs as $p) {
        $stmt->execute($p);
    }
    
    echo "Added Visit Action privileges.\n";

    $pdo->commit();
    echo "Migration successful.";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
