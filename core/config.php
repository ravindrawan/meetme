<?php
$host = 'meetmedb';
$db   = 'meetmedb';
$user = 'admin';
$pass = 'RaviDb@2026';

// Define base paths
if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost/vms_nw/');
if (!defined('ROOT_PATH')) define('ROOT_PATH', __DIR__ . '/../');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Colombo');

// Privilege Helper Function
if (!function_exists('hasPrivilege')) {
    function hasPrivilege($key) {
        if (!isset($_SESSION['user'])) return false;
        
        // Admin role has all privileges by default
        if ($_SESSION['user']['role'] === 'admin') return true; 
        
        // Check session loaded privileges
        $privs = $_SESSION['user_privileges'] ?? [];
        return in_array($key, $privs);
    }
}
?>
