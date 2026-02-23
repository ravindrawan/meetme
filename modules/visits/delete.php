<?php
require '../../core/config.php';
if($_POST['id']){
    $pdo->prepare("DELETE FROM visits WHERE visit_id=?")->execute([$_POST['id']]);
}
?>