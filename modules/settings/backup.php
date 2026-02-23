<?php
require '../../core/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Access denied');
}

$dump_path = 'C:\\xampp\\mysql\\bin\\mysqldump.exe'; // Default XAMPP path
if (!file_exists($dump_path)) {
    die("Error: mysqldump not found at $dump_path");
}

$backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$temp_path = sys_get_temp_dir() . '/' . $backup_file;

// Use escapeshellarg to handle special characters (like & in the password) safely
// Note: On Windows, escapeshellarg surrounds with double quotes, which is what we want for CMD.
$cmd = sprintf(
    '"%s" --user=%s --password=%s --host=%s %s > "%s" 2>&1',
    $dump_path,
    escapeshellarg($user), // Quotes the value: "root"
    escapeshellarg($pass), // Quotes the value: "s&sdigital"
    escapeshellarg($host),
    escapeshellarg($db),
    $temp_path
);

// Remove the outer quotes that escapeshellarg adds if we are interpolating them into a string that we might want to control more 
// specifically, OR just rely on escapeshellarg.
// However, mysqldump syntax is often --password="pass". escapeshellarg gives "pass". 
// So: --password="pass" becomes --password=""pass"" if I add my own quotes? 
// No, sprintf above: --password=%s becomes --password="s&sdigital". This looks correct.
// WAIT: escapeshellarg on Windows might replace % with space? No. 
// Let's stick to manual quoting which is more predictable for non-shell execution or simple exec.
// But escapeshellarg is best practice.
// Let's rebuild the command string carefully.

$cmd = '"' . $dump_path . '"' . 
       ' --user=' . escapeshellarg($user) . 
       ' --password=' . escapeshellarg($pass) . 
       ' --host=' . escapeshellarg($host) . 
       ' ' . escapeshellarg($db) . 
       ' > ' . escapeshellarg($temp_path) . ' 2>&1';

// Execute
exec($cmd, $output, $return_var);

if ($return_var !== 0) {
    // Return detailed error
    $error_msg = implode("\n", $output);
    die("Backup failed. Error code: $return_var. Details: <pre>$error_msg</pre>");
}

// Download
if (file_exists($temp_path)) {
    if (filesize($temp_path) == 0) {
        unlink($temp_path);
        die("Backup failed. The generated file is empty.");
    }
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($backup_file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($temp_path));
    readfile($temp_path);
    unlink($temp_path);
    exit;
} else {
    die("Backup file creation failed. Output: " . implode("\n", $output));
}
