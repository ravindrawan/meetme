<?php
require 'config.php';
if(isset($_POST['id']) && isset($_POST['status'])){
    $id = $_POST['id'];
    $status = $_POST['status'];
    $pdo->prepare("UPDATE visits SET status = ? WHERE visit_id = ?")->execute([$status, $id]);
}
?>