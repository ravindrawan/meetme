<?php
$host = 'meetmedb';
$db   = 'meetmedb';
$user = 'admin';
$pass = 'RaviDb@2026';

// --- BASE_URL එක නිවැරදිව සැකසීම (Dynamic logic) ---
if (!defined('BASE_URL')) {
    // වෙබ් අඩවිය දුවන protocol එක (http/https) සහ domain එක (host) auto ලබා ගනී
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host_name = $_SERVER['HTTP_HOST'];
    
    // OpenShift එකේදී root එකේම දුවන නිසා "/" ලෙස යොදන්න. 
    // localhost එකේදී vms_nw ෆෝල්ඩරය ඇතුළේ වැඩ කරනවා නම් පමණක් "/vms_nw/" ලෙස වෙනස් කරන්න.
    $path = "/"; 
    
    define('BASE_URL', $protocol . "://" . $host_name . $path);
}

if (!defined('ROOT_PATH')) define('ROOT_PATH', __DIR__ . '/../');

// --- Database Connection ---
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