<?php 
require '../../core/config.php'; 
if($_SESSION['user']['role']!='admin') die('Access denied'); 

// Handle Updates
// Handle Updates
if(isset($_POST['update_parent'])){
    $office_id = $_POST['office_id'];
    $parent_id = $_POST['parent_id'] ?: null; // Handle empty string as NULL
    
    try {
        $stmt = $pdo->prepare("UPDATE provincial_offices SET parent_office_id = ? WHERE id = ?");
        $stmt->execute([$parent_id, $office_id]);
        $message = '<div class="alert alert-success">Parent office updated successfully.</div>';
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Handle Auto-Assign Hierarchy
if(isset($_POST['auto_assign'])){
    try {
        $pdo->beginTransaction();
        
        // Level 5 (Matches first 9 digits of parent) -> Divisor 100
        $pdo->exec("UPDATE provincial_offices AS c 
                    JOIN provincial_offices AS p ON p.id = (c.id DIV 100) * 100 
                    SET c.parent_office_id = p.id 
                    WHERE c.office_level = 'Level 5'");

        // Level 4 (Matches first 6 digits of parent) -> Divisor 100000
        $pdo->exec("UPDATE provincial_offices AS c 
                    JOIN provincial_offices AS p ON p.id = (c.id DIV 100000) * 100000 
                    SET c.parent_office_id = p.id 
                    WHERE c.office_level = 'Level 4'");

        // Level 3 (Matches first 3 digits of parent) -> Divisor 100000000
        $pdo->exec("UPDATE provincial_offices AS c 
                    JOIN provincial_offices AS p ON p.id = (c.id DIV 100000000) * 100000000 
                    SET c.parent_office_id = p.id 
                    WHERE c.office_level = 'Level 3'");

        // Level 2 (Matches first 1 digit of parent) -> Divisor 10000000000
        $pdo->exec("UPDATE provincial_offices AS c 
                    JOIN provincial_offices AS p ON p.id = (c.id DIV 10000000000) * 10000000000 
                    SET c.parent_office_id = p.id 
                    WHERE c.office_level = 'Level 2'");

        $pdo->commit();
        $message = '<div class="alert alert-success">Hierarchy auto-assigned successfully based on ID structure!</div>';
    } catch(PDOException $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger">Error auto-assigning hierarchy: ' . $e->getMessage() . '</div>';
    }
}

// Pagination Logic
$limit = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build Query Conditions
$where = "WHERE 1=1";
$params = [];

if(isset($_GET['search']) && !empty($_GET['search'])){
    $search = trim($_GET['search']);
    $where .= " AND (o.office_name LIKE ? OR p.office_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if(isset($_GET['level']) && !empty($_GET['level'])){
    $where .= " AND o.office_level = ?";
    $params[] = $_GET['level'];
}

// Count Total for Pagination
$count_sql = "SELECT COUNT(*) 
              FROM provincial_offices o 
              LEFT JOIN provincial_offices p ON o.parent_office_id = p.id 
              $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Data with Limit
$sql = "SELECT o.id, o.office_name, o.office_level, o.parent_office_id, p.office_name as parent_office 
        FROM provincial_offices o 
        LEFT JOIN provincial_offices p ON o.parent_office_id = p.id 
        $where 
        ORDER BY o.id 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$offices = $stmt->fetchAll();

// Fetch all offices for dropdowns (kept separate for filters)
$all_offices = $pdo->query("SELECT id, office_name, office_level FROM provincial_offices ORDER BY office_level, office_name")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Office Hierarchy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container { width: 100% !important; }
    </style>
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
                <h2>Manage Office Hierarchy</h2>
                <div>
                    <form method="post" style="display:inline;">
                        <button name="auto_assign" class="btn btn-warning me-2" onclick="return confirm('This will automatically link offices to their parents based on their IDs. Continue?')">
                            <i class="fas fa-magic me-1"></i> Auto-Detect Parents
                        </button>
                    </form>
                </div>
            </div>

            <?= $message ?? '' ?>

            <!-- Assign Parent Form -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <span>Assign Parent Office</span>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Child Office</label>
                            <select name="child_id" class="form-select select2" required>
                                <option value="">Select Child Office</option>
                                <?php foreach($all_offices as $o): ?>
                                    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['office_name']) ?> (<?= $o['office_level'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Parent Office</label>
                            <select name="parent_id" class="form-select select2" required>
                                <option value="">Select Parent Office</option>
                                <?php foreach($all_offices as $o): ?>
                                    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['office_name']) ?> (<?= $o['office_level'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button name="assign" class="btn btn-success w-100"><i class="fas fa-link me-1"></i> Assign</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search Office..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="level" class="form-select">
                                <option value="">All Levels</option>
                                <option value="Level 1" <?= ($_GET['level']??'')=='Level 1'?'selected':'' ?>>Level 1</option>
                                <option value="Level 2" <?= ($_GET['level']??'')=='Level 2'?'selected':'' ?>>Level 2</option>
                                <option value="Level 3" <?= ($_GET['level']??'')=='Level 3'?'selected':'' ?>>Level 3</option>
                                <option value="Level 4" <?= ($_GET['level']??'')=='Level 4'?'selected':'' ?>>Level 4</option>
                                <option value="Level 5" <?= ($_GET['level']??'')=='Level 5'?'selected':'' ?>>Level 5</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="office_hierarchy.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Hierarchy Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Current Hierarchy (Total: <?= $total_rows ?>)</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Office Name</th>
                                    <th>Level</th>
                                    <th>Parent Office</th>
                                    <th style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($offices as $o): ?>
                                <tr>
                                    <td><?= htmlspecialchars($o['id']) ?></td>
                                    <td><?= htmlspecialchars($o['office_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($o['office_level']) ?></span></td>
                                    <td>
                                        <?php if($o['parent_office']): ?>
                                            <span class="text-success fw-bold"><?= htmlspecialchars($o['parent_office']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">No Parent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($o['parent_office_id']): // Use parent_office_id from the main query ?>
                                        <form method="post" onsubmit="return confirm('Remove parent assignment?');">
                                            <input type="hidden" name="child_id" value="<?= $o['id'] ?>">
                                            <button name="remove" class="btn btn-danger btn-sm"><i class="fas fa-unlink"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($offices)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No offices found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <!-- First & Previous -->
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=1&search=<?= urlencode($_GET['search']??'') ?>&level=<?= urlencode($_GET['level']??'') ?>">First</a>
                            </li>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($_GET['search']??'') ?>&level=<?= urlencode($_GET['level']??'') ?>">Previous</a>
                            </li>

                            <!-- Page Numbers (Windowed) -->
                            <?php
                            $window = 2; // Show 2 pages around current
                            $start = max(1, $page - $window);
                            $end = min($total_pages, $page + $window);
                            
                            if($start > 1) {
                                 echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }

                            for ($i = $start; $i <= $end; $i++): 
                            ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($_GET['search']??'') ?>&level=<?= urlencode($_GET['level']??'') ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if($end < $total_pages): ?>
                                 <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>

                            <!-- Next & Last -->
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($_GET['search']??'') ?>&level=<?= urlencode($_GET['level']??'') ?>">Next</a>
                            </li>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($_GET['search']??'') ?>&level=<?= urlencode($_GET['level']??'') ?>">Last</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Select an office",
            allowClear: true
        });
    });
</script>
</body>
</html>
