<?php 
require '../../core/config.php';
if($_SESSION['user']['role'] !== 'admin' && !hasPrivilege('tile_settings')) die('Access denied');

// Ensure office_id is loaded
if (!isset($_SESSION['user']['office_id']) && isset($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare("SELECT office_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $_SESSION['user']['office_id'] = $stmt->fetchColumn();
}
// Cast to int if it exists, otherwise null
if (isset($_SESSION['user']['office_id'])) {
    $_SESSION['user']['office_id'] = (int)$_SESSION['user']['office_id'];
}
// DEBUG: Remove after fixing
// echo "Role: " . $_SESSION['user']['role'] . "<br>";
// echo "Office ID: " . var_export($_SESSION['user']['office_id'], true) . "<br>";

// Add Section
if(isset($_POST['add_section']) && !empty($_POST['section_name'])) {
    $office_id = $_SESSION['user']['role']=='admin' ? null : ($_SESSION['user']['office_id'] ?? null);
    $pdo->prepare("INSERT IGNORE INTO sections (section_name, office_id) VALUES (?, ?)")->execute([trim($_POST['section_name']), $office_id]);
}

// Add Officer
if(isset($_POST['add_officer']) && !empty($_POST['officer_name']) && !empty($_POST['section'])) {
    $office_id = $_SESSION['user']['role']=='admin' ? null : ($_SESSION['user']['office_id'] ?? null);
    $pdo->prepare("INSERT INTO officers (name, section_id, office_id) VALUES (?, ?, ?)")->execute([trim($_POST['officer_name']), $_POST['section'], $office_id]);
}

// Delete Section
if(isset($_GET['del_section'])) {
    try {
        $pdo->prepare("DELETE FROM sections WHERE id = ?")->execute([$_GET['del_section']]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Cannot delete section because it contains officers or visits.";
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Delete Officer
if(isset($_GET['del_officer'])) {
    try {
        $pdo->prepare("DELETE FROM officers WHERE id = ?")->execute([$_GET['del_officer']]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Cannot delete officer because they have registered visits.";
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }
}
// Update Organization Settings
if(isset($_POST['update_org_settings'])) {
    if(!empty($_POST['org_name'])) {
        $pdo->prepare("UPDATE system_settings SET organization_name = ? WHERE id = 1")->execute([trim($_POST['org_name'])]);
    }

    if(isset($_FILES['org_logo']) && $_FILES['org_logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['org_logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_name = "logo_" . time() . "." . $ext;
            // Physical path relative to this script
            $destination = "../../assets/uploads/" . $new_name;
            // DB path relative to root
            $db_path = "assets/uploads/" . $new_name;
            
            if(move_uploaded_file($_FILES['org_logo']['tmp_name'], $destination)) {
                $pdo->prepare("UPDATE system_settings SET organization_logo = ? WHERE id = 1")->execute([$db_path]);
            }
        }
    }
}

// Get current settings
$stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $stmt->fetch() ?: ['organization_name' => 'VMS', 'organization_logo' => null];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
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
            <div class="d-flex justify-content-between mb-4">
                <h2>Settings</h2>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">

                <!-- Organization Settings -->
                <?php if($_SESSION['user']['role'] === 'admin'): ?>
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header bg-dark text-white"><h5>Organization Settings</h5></div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data" class="row align-items-center">
                                <div class="col-md-6 mb-3">
                                    <label>Organization Name</label>
                                    <input name="org_name" class="form-control" value="<?= htmlspecialchars($settings['organization_name']) ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Logo (Optional)</label>
                                    <input type="file" name="org_logo" class="form-control" accept="image/*">
                                </div>
                                <div class="col-md-2 mb-3">
                                     <button name="update_org_settings" class="btn btn-primary w-100 mt-4">Save</button>
                                </div>
                                <?php if($settings['organization_logo']): ?>
                                <div class="col-12">
                                    <small>Current Logo:</small><br>
                                    <img src="../../<?= $settings['organization_logo'] ?>" alt="Logo" style="height: 50px; background: #ddd; padding: 5px; border-radius: 5px;">
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Add Section -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Add Section</h5>
                            <form method="post" class="input-group">
                                <input name="section_name" class="form-control" placeholder="New Section Name" required>
                                <button name="add_section" class="btn btn-primary">Add</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Add Officer -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Add Officer</h5>
                            <form method="post">
                                <input name="officer_name" class="form-control mb-2" placeholder="Officer Name" required>
                                <select name="section" class="form-select mb-2" required>
                                    <option value="">Select Section</option>
                                    <?php 
                                    $sql = "SELECT * FROM sections WHERE 1=1";
                                    $params = [];
                                    if ($role !== 'admin' && $office_id) {
                                        $sql .= " AND office_id = ?";
                                        $params[] = $office_id;
                                    }
                                    $stmt = $pdo->prepare($sql . " ORDER BY section_name");
                                    $stmt->execute($params);
                                    foreach($stmt as $s): 
                                    ?>
                                        <option value="<?= $s['id'] ?>"><?= $s['section_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button name="add_officer" class="btn btn-success w-100">Add Officer</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- List Sections -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white"><h5>Sections</h5></div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <?php 
                                $office_id = $_SESSION['user']['office_id'] ?? null;
                                $role = $_SESSION['user']['role'];
                                $sql = "SELECT * FROM sections WHERE 1=1";
                                $params = [];
                                
                                if ($role !== 'admin' && $office_id) {
                                    $sql .= " AND office_id = ?";
                                    $params[] = $office_id;
                                }

                                $stmt = $pdo->prepare($sql . " ORDER BY section_name");
                                $stmt->execute($params);
                                foreach($stmt as $s): 
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['section_name']) ?></td>
                                    <td class="text-end"><a href="?del_section=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete section?')">Delete</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- List Officers -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white"><h5>Officers</h5></div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <?php 
                                $sql = "SELECT o.id, o.name, s.section_name FROM officers o LEFT JOIN sections s ON o.section_id=s.id WHERE 1=1";
                                $params = [];

                                if ($role !== 'admin' && $office_id) {
                                    $sql .= " AND (o.office_id = ? OR s.office_id = ?)";
                                    $params[] = $office_id;
                                    $params[] = $office_id;
                                }

                                $stmt = $pdo->prepare($sql . " ORDER BY s.section_name, o.name");
                                $stmt->execute($params);
                                foreach($stmt as $o): ?>
                                <tr>
                                    <td><?= htmlspecialchars($o['name']) ?></td>
                                    <td><?= htmlspecialchars($o['section_name'] ?? 'No Section') ?></td>
                                    <td class="text-end"><a href="?del_officer=<?= $o['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete officer?')">Delete</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>