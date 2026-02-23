<?php 
require '../../core/config.php'; 
if($_SESSION['user']['role']!='admin') die('Access denied'); 

if(isset($_POST['add'])){
    $name = trim($_POST['name']);
    $level = $_POST['level'];
    if($name && $level){
        try {
            $pdo->prepare("INSERT INTO provincial_offices (office_name, office_level) VALUES (?,?)")->execute([$name, $level]);
            $message = '<div class="alert alert-success">Office added successfully!</div>';
        } catch(PDOException $e) {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

if(isset($_GET['del'])){
    $pdo->prepare("DELETE FROM provincial_offices WHERE id=?")->execute([$_GET['del']]);
    header("Location: offices.php");
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Provincial Offices</title>
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
                <h2>Manage Provincial Council Offices</h2>
            </div>

            <?= $message ?? '' ?>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span>Add New Office</span>
                    <a href="import_offices.php" class="btn btn-light btn-sm"><i class="fas fa-file-import me-1"></i> Import CSV</a>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <div class="col-md-6">
                            <input name="name" class="form-control" placeholder="Office Name" required>
                        </div>
                        <div class="col-md-4">
                            <select name="level" class="form-select" required>
                                <option value="">Select Level</option>
                                <option value="Level 1">Level 1</option>
                                <option value="Level 2">Level 2</option>
                                <option value="Level 3">Level 3</option>
                                <option value="Level 4">Level 4</option>
                                <option value="Level 5">Level 5</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button name="add" class="btn btn-success w-100"><i class="fas fa-plus me-1"></i> Add</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            // Pagination Logic
            $limit = 100;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;

            // Count Total
            $total_stmt = $pdo->query("SELECT COUNT(*) FROM provincial_offices");
            $total_rows = $total_stmt->fetchColumn();
            $total_pages = ceil($total_rows / $limit);

            // Fetch with Limit
            $offices = $pdo->query("SELECT * FROM provincial_offices ORDER BY office_level, office_name LIMIT $limit OFFSET $offset")->fetchAll();
            ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Existing Offices (Total: <?= $total_rows ?>)</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Office Name</th>
                                    <th>Level</th>
                                    <th style="width: 150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($offices as $o): ?>
                                <tr>
                                    <td><?= htmlspecialchars($o['id']) ?></td>
                                    <td><?= htmlspecialchars($o['office_name']) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                if($o['office_level']=='Level 1') echo 'bg-danger';
                                                elseif($o['office_level']=='Level 2') echo 'bg-warning text-dark';
                                                elseif($o['office_level']=='Level 3') echo 'bg-info text-dark';
                                                elseif($o['office_level']=='Level 4') echo 'bg-primary';
                                                else echo 'bg-secondary';
                                            ?>">
                                            <?= htmlspecialchars($o['office_level']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_office.php?id=<?= $o['id'] ?>" class="btn btn-warning btn-sm text-dark me-1">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?del=<?= $o['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this office?')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
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
                                <a class="page-link" href="?page=1">First</a>
                            </li>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
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
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if($end < $total_pages): ?>
                                 <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>

                            <!-- Next & Last -->
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $total_pages ?>">Last</a>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
