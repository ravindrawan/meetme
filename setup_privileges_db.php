<?php
require 'config.php';

try {
    // 1. Table to store all available dashboard cards
    $pdo->exec("CREATE TABLE IF NOT EXISTS dashboard_cards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        card_key VARCHAR(50) UNIQUE NOT NULL, -- e.g., 'register_visitor', 'view_visits'
        card_name VARCHAR(100) NOT NULL,      -- Display name e.g., 'Register New Visitor'
        default_roles TEXT                    -- Comma-separated roles that have this by default (for reset/init)
    )");

    // 2. Table to store permissions (which role can see which card)
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role VARCHAR(50) NOT NULL,
        card_key VARCHAR(50) NOT NULL,
        is_visible BOOLEAN DEFAULT TRUE,
        UNIQUE KEY role_card (role, card_key),
        FOREIGN KEY (card_key) REFERENCES dashboard_cards(card_key) ON DELETE CASCADE
    )");

    // 3. Populate initial cards
    $cards = [
        ['register_visitor', 'Register New Visitor', 'admin,front_officer'],
        ['view_visits', 'View Visits', 'admin,front_officer,section_head,supervisor,officer'],
        ['settings', 'Settings', 'admin'],
        ['manage_gn', 'Manage GN Divisions', 'admin'],
        ['create_user', 'Create User', 'admin'],
        ['reports', 'Reports', 'admin,section_head,supervisor'],
        ['manage_reasons', 'Manage Visit Reasons', 'admin,front_officer'],
        ['add_feedback', 'Add Customer Feedback', 'admin,front_officer'],
        ['view_feedback', 'View Customer Feedback', 'admin,front_officer,section_head,supervisor'],
        ['section_feedback', 'Section-wise Feedback', 'admin,section_head,supervisor'],
        ['gn_feedback', 'GN Division-wise Feedback', 'admin,section_head,supervisor']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO dashboard_cards (card_key, card_name, default_roles) VALUES (?, ?, ?)");
    $permStmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role, card_key, is_visible) VALUES (?, ?, 1)");

    foreach ($cards as $card) {
        $stmt->execute($card);
        
        // Auto-assign permissions based on default roles string
        $roles = explode(',', $card[2]);
        foreach ($roles as $role) {
            $permStmt->execute([trim($role), $card[0]]);
        }
    }

    echo "Privileges tables created and populated successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
