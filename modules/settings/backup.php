<?php
require '../../core/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Access denied');
}

// OpenShift/Linux වල mysqldump තියෙන නිවැරදි Path එක
$dump_path = '/usr/bin/mysqldump'; 

// Linux වලදී file_exists() වෙනුවට is_executable() පාවිච්චි කිරීම වඩාත් සුදුසුයි
if (!is_executable($dump_path)) {
    // සමහරවිට Path එක වෙනස් විය හැකිනම් කෙලින්ම නම පමණක් පාවිච්චි කර බලමු
    $dump_path = 'mysqldump'; 
}

$backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
// Linux වල temp directory එක සාමාන්‍යයෙන් /tmp/
$temp_path = sys_get_temp_dir() . '/' . $backup_file;

// Command එක සැකසීම - මෙහිදී host එක ලෙස 'meetmedb' යන නම config.php හරහා ලැබෙනවා
// Linux Shell එකේදී password එක සහ අනෙක්වා 'escapeshellarg' හරහා ආරක්ෂිතව යෙදිය යුතුයි
$cmd = $dump_path . 
       ' --user=' . escapeshellarg($user) . 
       ' --password=' . escapeshellarg($pass) . 
       ' --host=' . escapeshellarg($host) . 
       ' ' . escapeshellarg($db) . 
       ' > ' . escapeshellarg($temp_path) . ' 2>&1';

// Execute
exec($cmd, $output, $return_var);

// දෝෂ පරීක්ෂාව
if ($return_var !== 0) {
    $error_msg = implode("\n", $output);
    die("Backup failed. Error code: $return_var. <br>Details: <pre>$error_msg</pre>");
}

// Download කිරීමේ කොටස
if (file_exists($temp_path) && filesize($temp_path) > 0) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($backup_file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($temp_path));
    
    // ෆයිල් එක කියවා අවසානයේ එය සේවාදායකයෙන් මකා දැමීම
    readfile($temp_path);
    unlink($temp_path);
    exit;
} else {
    die("Backup failed. The generated file is empty or not found.");
}
