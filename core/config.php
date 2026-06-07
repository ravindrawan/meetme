<?php
$host = 'localhost';
$db   = 'vms_nw';
$user = 'root';
$pass = 's&sdigital';

// Define base paths
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    
    // HTTP_HOST might not be set in CLI mode
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    if (!defined('ROOT_PATH')) define('ROOT_PATH', realpath(__DIR__ . '/../'));
    
    // Determine the base path based on SCRIPT_NAME which is safer than DOCUMENT_ROOT in some shared hosting
    // This looks for the directory name of the root path inside the script name
    $root_dir_name = basename(ROOT_PATH);
    
    // In CLI or some environments, SCRIPT_NAME might be different. Let's provide a reliable fallback.
    $relative_path = '';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $script_path = $_SERVER['SCRIPT_NAME'];
        $pos = strpos($script_path, '/' . $root_dir_name . '/');
        if ($pos !== false) {
             $relative_path = substr($script_path, 0, $pos + strlen('/' . $root_dir_name));
        } else {
             // Fallback if the folder name is not in the URL (e.g. standard domain setup)
             $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
             if ($doc_root) {
                 $relative_path = str_replace(str_replace('\\', '/', $doc_root), '', str_replace('\\', '/', ROOT_PATH));
             } else {
                 $relative_path = '/' . $root_dir_name;
             }
        }
    } else {
         $relative_path = '/' . $root_dir_name;
    }
    
    // Clean up slashes
    $relative_path = rtrim($relative_path, '/');
    if ($relative_path && $relative_path[0] !== '/') {
        $relative_path = '/' . $relative_path;
    }
    
    $base_url = rtrim($protocol . $host . $relative_path, '/') . '/';
    
    define('BASE_URL', $base_url);
}
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

// Rate Limiting Helper Function
if (!function_exists('checkRateLimit')) {
    function checkRateLimit($pdo, $ip, $action, $max_attempts = 5, $lockout_minutes = 15) {
        // Clean up old records
        $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt->execute([$lockout_minutes]);

        $stmt = $pdo->prepare("SELECT attempts FROM rate_limits WHERE ip_address = ? AND action = ?");
        $stmt->execute([$ip, $action]);
        $record = $stmt->fetch();

        if ($record) {
            if ($record['attempts'] >= $max_attempts) {
                return false; // Rate limit exceeded
            }
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() WHERE ip_address = ? AND action = ?");
            $stmt->execute([$ip, $action]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO rate_limits (ip_address, action, attempts, last_attempt) VALUES (?, ?, 1, NOW())");
            $stmt->execute([$ip, $action]);
        }
        return true; 
    }
}

if (!function_exists('clearRateLimit')) {
    function clearRateLimit($pdo, $ip, $action) {
        $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND action = ?");
        $stmt->execute([$ip, $action]);
    }
}
?>