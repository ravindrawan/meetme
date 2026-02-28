<?php
// Session එක start වෙලා නැත්නම් විතරක් start කරන්න
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../../core/config.php';

// Admin ද කියලා චෙක් කිරීම
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Access denied');
}

// 1. Database විස්තර (config.php එකෙන් එන විචල්‍යයන් භාවිතා කරන්න)
// මෙහිදී $host, $user, $pass, $db යන ඒවා config.php හි ඇති බව සහතික කරගන්න
$db_host = $host;
$db_user = $user;
$db_pass = $pass;
$db_name = $db;

// 2. Backup ෆයිල් එකේ නම සහ තාවකාලික ගබඩාව (/tmp)
$backup_filename = 'meetme_backup_' . date('Y-m-d_Hi') . '.sql';
$temp_file = '/tmp/' . $backup_filename;

// 3. Command එක සැකසීම
// සටහන: -p සහ password එක අතර හිස්තැනක් නොතිබිය යුතුය
// 2>&1 මගින් errors ද output එකටම ලබා ගනී
$cmd = "/usr/bin/mysqldump --no-tablespaces --host=" . escapeshellarg($db_host) . 
       " --user=" . escapeshellarg($db_user) . 
       " --password=" . escapeshellarg($db_pass) . 
       " " . escapeshellarg($db_name) . " > " . $temp_file . " 2>&1";

// 4. Execute කිරීම
exec($cmd, $output, $return_var);

// 5. දෝෂ පරීක්ෂාව
if ($return_var !== 0) {
    $error_details = implode("\n", $output);
    // ආරක්ෂාව සඳහා password එක error එකේ පෙන්වීම වැලැක්වීමට path එක පමණක් පෙන්වමු
    die("Backup Failed! <br>Error Code: $return_var <br>Details: <pre>$error_details</pre>");
}

// 6. Download සහ ලියාපදිංචිය
if (file_exists($temp_file) && filesize($temp_file) > 0) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $backup_filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($temp_file));
    
    // ෆයිල් එක කියවා අවසානයේ එය මකා දැමීම
    ob_clean();
    flush();
    readfile($temp_file);
    unlink($temp_file); 
    exit;
} else {
    die("Error: The backup file was not created or is empty.");
}
