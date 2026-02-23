<?php
require '../../core/config.php';

// Access Control
// if(!hasPrivilege('manage_privileges')) die('Access denied'); // To be implemented
if($_SESSION['user']['role']!='admin') die('Access denied');

$message = '';

// Handle Update
if (isset($_POST['save_privileges'])) {
    $role_key = $_POST['role_key'];
    
    // We update by deleting all for this role, then re-inserting checked ones
    // This is simple and effective for checkbox lists
    try {
        $pdo->beginTransaction();
        
        $stmtDel = $pdo->prepare("DELETE FROM role_privileges WHERE role_key = ?");
        $stmtDel->execute([$role_key]);
        
        if (isset($_POST['privileges']) && is_array($_POST['privileges'])) {
            $stmtIns = $pdo->prepare("INSERT INTO role_privileges (role_key, privilege_key) VALUES (?, ?)");
            foreach ($_POST['privileges'] as $priv_key) {
                $stmtIns->execute([$role_key, $priv_key]);
            }
        }
        
        $pdo->commit();
        $message = '<div class="alert alert-success">Privileges updated for role: <strong>' . htmlspecialchars($role_key) . '</strong></div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Fetch Roles
$roles = $pdo->query("SELECT * FROM user_roles ORDER BY role_name")->fetchAll();

// Get Selected Role
$selected_role = $_GET['role'] ?? ($roles[0]['role_key'] ?? '');

// Fetch All Privileges Grouped by Category
$raw_privileges = $pdo->query("SELECT * FROM privileges ORDER BY category, privilege_name")->fetchAll();
$grouped_privileges = [];
foreach($raw_privileges as $p){
    $grouped_privileges[$p['category']][] = $p;
}

// Fetch Current Privileges for Selected Role
$current_privs = $pdo->prepare("SELECT privilege_key FROM role_privileges WHERE role_key = ?");
$current_privs->execute([$selected_role]);
$active_privs = $current_privs->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Role Privileges</title>
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
                <h2>Manage Role Privileges</h2>
            </div>

            <?= $message ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <form method="get" class="row align-items-center">
                        <div class="col-auto">
                            <label class="fw-bold">Select Role:</label>
                        </div>
                        <div class="col-auto">
                            <select name="role" class="form-select" onchange="this.form.submit()">
                                <?php foreach($roles as $r): ?>
                                    <option value="<?= $r['role_key'] ?>" <?= $selected_role == $r['role_key'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="role_key" value="<?= htmlspecialchars($selected_role) ?>">
                        
                        <div class="row">
                            <?php foreach($grouped_privileges as $category => $privs): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-primary text-white">
                                            <?= htmlspecialchars($category) ?>
                                        </div>
                                        <div class="card-body">
                                            <?php foreach($privs as $p): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="privileges[]" 
                                                           value="<?= $p['privilege_key'] ?>" 
                                                           id="priv_<?= $p['id'] ?>"
                                                           <?= in_array($p['privilege_key'], $active_privs) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="priv_<?= $p['id'] ?>">
                                                        <?= htmlspecialchars($p['privilege_name']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr>
                        <div class="text-end">
                            <button type="submit" name="save_privileges" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i> Save Privileges
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
