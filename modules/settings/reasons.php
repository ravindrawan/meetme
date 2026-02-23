<?php 
require '../../core/config.php'; 
if (!in_array($_SESSION['user']['role'], ['admin', 'front_officer']) && !hasPrivilege('tile_settings')) {
    die('Access denied');
}

if (isset($_POST['add'])) {
    $office_id = $_SESSION['user']['office_id'] ?? null;
    $pdo->prepare("INSERT IGNORE INTO visit_reasons (reason_text, office_id) VALUES (?, ?)")
        ->execute([trim($_POST['reason']), $office_id]);
}
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM visit_reasons WHERE id = ?")->execute([$_GET['del']]);
}

// Filter reasons based on user role/office
$office_id = $_SESSION['user']['office_id'] ?? null;
if ($_SESSION['user']['role'] == 'admin') {
    $reasons = $pdo->query("SELECT vr.*, o.office_name as office_name 
                           FROM visit_reasons vr 
                           LEFT JOIN provincial_offices o ON vr.office_id = o.id 
                           ORDER BY vr.reason_text")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM visit_reasons WHERE office_id = ? ORDER BY reason_text");
    $stmt->execute([$office_id]);
    $reasons = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html><head><title>Manage Reasons</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<?php include '../../includes/navbar.php'; ?>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar-container d-none d-lg-block">
        <?php include '../../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Visit Reasons</h2>
            </div>
            <div class="card mb-4"><div class="card-body">
                <form method="post" class="row g-3">
                    <div class="col"><input name="reason" class="form-control" placeholder="New reason" required></div>
                    <div class="col-auto"><button name="add" class="btn btn-primary">Add</button></div>
                </form>
            </div></div>
            <table class="table table-striped">
                <thead><tr><th>Reason</th><?php if($_SESSION['user']['role'] == 'admin'): ?><th>Office</th><?php endif; ?><th>Action</th></tr></thead>
                <tbody>
                <?php foreach($reasons as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['reason_text']) ?></td>
                        <?php if($_SESSION['user']['role'] == 'admin'): ?>
                            <td><?= htmlspecialchars($r['office_name'] ?? 'All Offices') ?></td>
                        <?php endif; ?>
                    <td><a href="?del=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>