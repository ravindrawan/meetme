<?php
require 'config.php';

echo "<h2>Users Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE users");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $val) echo "<td>$val</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error describing table: " . $e->getMessage();
}

echo "<h2>Users List</h2>";
try {
    $stmt = $pdo->query("SELECT id, username, role, password FROM users");
    echo "<table border='1'><tr><th>ID</th><th>Username</th><th>Role</th><th>Password Hash Length</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td>" . strlen($row['password']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error listing users: " . $e->getMessage();
}

// Test admin login manually
echo "<h2>Test Admin Login</h2>";
$username = 'admin';
$password = 'admin123';
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$users = $stmt->fetchAll();

echo "Found " . count($users) . " users with username 'admin'.<br>";

foreach ($users as $user) {
    if (password_verify($password, $user['password'])) {
        echo "User ID {$user['id']}: Password matches!<br>";
    } else {
        echo "User ID {$user['id']}: Password does NOT match.<br>";
    }
}
?>
