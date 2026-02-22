<?php
require 'core/config.php';

try {
    echo "Starting schema upgrade to BIGINT...\n";
    
    // 1. Drop Foreign Keys
    // Need to find constraint names first usually, but assuming standard naming or checking
    // MySQL constraint names are often auto-generated if not named explicitly.
    // I named `fk_office_parent` explicitly.
    // `users` table FK needs checking.
    
    // Helper to drop FK if exists
    function dropFK($pdo, $table, $constraint) {
        try {
            $pdo->exec("ALTER TABLE $table DROP FOREIGN KEY $constraint");
            echo "Dropped FK $constraint on $table\n";
        } catch (PDOException $e) {
            echo "FK $constraint not found or already dropped on $table\n";
        }
    }

    // Drop known FKs
    dropFK($pdo, 'provincial_offices', 'fk_office_parent');
    
    // Check users table FK for office_id. It might be named something else. 
    // Let's try to find it.
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'office_id' AND TABLE_SCHEMA = 'vms_nw'");
    $fk = $stmt->fetchColumn();
    if($fk){
        dropFK($pdo, 'users', $fk);
    }

    // 2. Modify Columns to BIGINT
    echo "Modifying provincial_offices.id to BIGINT...\n";
    $pdo->exec("ALTER TABLE provincial_offices MODIFY COLUMN id BIGINT NOT NULL AUTO_INCREMENT");
    
    echo "Modifying provincial_offices.parent_office_id to BIGINT...\n";
    $pdo->exec("ALTER TABLE provincial_offices MODIFY COLUMN parent_office_id BIGINT NULL DEFAULT NULL");

    echo "Modifying users.office_id to BIGINT...\n";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN office_id BIGINT NULL DEFAULT NULL");

    // 3. Re-add Foreign Keys
    echo "Re-adding FK fk_office_parent...\n";
    $pdo->exec("ALTER TABLE provincial_offices ADD CONSTRAINT fk_office_parent FOREIGN KEY (parent_office_id) REFERENCES provincial_offices(id) ON DELETE SET NULL");

    echo "Re-adding users FK...\n";
    $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_users_office FOREIGN KEY (office_id) REFERENCES provincial_offices(id) ON DELETE SET NULL");

    echo "Schema upgrade complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
