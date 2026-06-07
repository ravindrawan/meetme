<?php
$_SESSION['user'] = ['role' => 'admin', 'id' => 1];
ob_start();
include 'create_user.php';
$output = ob_get_clean();
$pos = strpos($output, "function toggleFields");
echo substr($output, $pos, 1000);
