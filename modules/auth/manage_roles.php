<?php
require '../../core/config.php';

// Access Control: Only Super Admin
if($_SESSION['user']['role']!='admin') die('Access denied');

$message = '';

// Add Role
if(isset($_POST['add'])){
    $role_key = strtolower(trim(str_replace(' ', '_', $_POST['role_name'])));
    $role_name = trim($_POST['role_name']);
    
    if($role_name){
        try {
            $stmt = $pdo->prepare("INSERT INTO user_roles (role_key, role_name) VALUES (?, ?)");
            $stmt->execute([$role_key, $role_name]);
            $message = '<div class="alert alert-success">Role added successfully!</div>';
        } catch(PDOException $e) {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// Delete Role
if(isset($_GET['del'])){
    $id = $_GET['del'];
    try {
        // Check if used
        $stmt = $pdo->prepare("SELECT role_key FROM user_roles WHERE id = ?");
        $stmt->execute([$id]);
        $roleInfo = $stmt->fetch();
        
        if($roleInfo){
            $key = $roleInfo['role_key'];
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
            $check->execute([$key]);
            
            if($check->fetchColumn() > 0){
                 $message = '<div class="alert alert-warning">Cannot delete role. It is assigned to users.</div>';
            } else {
                $pdo->prepare("DELETE FROM user_roles WHERE id = ?")->execute([$id]);
                $message = '<div class="alert alert-success">Role deleted successfully.</div>';
            }
        }
    } catch(PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

$roles = $pdo->query("SELECT * FROM user_roles ORDER BY role_name")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage User Roles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<?php include '../../includes/navbar.php'; ?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage User Roles</h2>
        <a href="create_user.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Create User</a>
    </div>

    <?= $message ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">Add New Role</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input name="role_name" class="form-control" placeholder="e.g. IT Support" required>
                            <div class="form-text">Key will be auto-generated (e.g. it_support).</div>
                        </div>
                        <button name="add" class="btn btn-success w-100"><i class="fas fa-plus me-1"></i> Add Role</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">Existing Roles</div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Role Name</th>
                                <th>Key</th>
                                <th style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($roles as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['role_name']) ?></td>
                                <td><code><?= htmlspecialchars($r['role_key']) ?></code></td>
                                <td>
                                    <?php if(!in_array($r['role_key'], ['admin'])): // Protect Admin role ?>
                                    <a href="?del=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this role?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
</body>
</html>
