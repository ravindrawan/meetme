<?php 
require '../../core/config.php'; 
if($_SESSION['user']['role']!='admin') die('Access denied'); 

if(isset($_POST['add'])){
    $pdo->prepare("INSERT IGNORE INTO gn_divisions (gn_code, gn_name) VALUES (?,?)")->execute([$_POST['code'], $_POST['name']]);
}
if(isset($_GET['del'])){
    $pdo->prepare("DELETE FROM gn_divisions WHERE id=?")->execute([$_GET['del']]);
}
$gns = $pdo->query("SELECT * FROM gn_divisions ORDER BY gn_code")->fetchAll();
?>
<!DOCTYPE html><html><head><title>GN Divisions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<?php include '../../includes/navbar.php'; ?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage GN Divisions</h2>
        <a href="../dashboard/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
    </div>
    <div class="card mb-4"><div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-auto"><input name="code" class="form-control" placeholder="GN Code" required></div>
            <div class="col"><input name="name" class="form-control" placeholder="GN Name" required></div>
            <div class="col-auto"><button name="add" class="btn btn-primary">Add</button></div>
        </form>
    </div></div>
    <table class="table"><thead><tr><th>Code</th><th>Name</th><th>Action</th></tr></thead><tbody>
    <?php foreach($gns as $g): ?>
    <tr><td><?= htmlspecialchars($g['gn_code']) ?></td><td><?= htmlspecialchars($g['gn_name']) ?></td>
    <td><a href="?del=<?= $g['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a></td></tr>
    <?php endforeach; ?>
    </tbody></table>
</div></body></html>