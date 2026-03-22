<?php 
require '../../core/config.php';

// Access Control
if (!in_array($_SESSION['user']['role'], ['admin', 'office_admin'])) {
    die('Access denied');
}

// Ensure office_id is loaded in session if available in DB
if (!isset($_SESSION['user']['office_id'])) {
    $stmt = $pdo->prepare("SELECT office_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $_SESSION['user']['office_id'] = $stmt->fetchColumn();
}

$currentUser = $_SESSION['user'];
$currentOfficeId = $currentUser['office_id'] ?? null;
$currentRole = $currentUser['role'];

// Determine Level Logic
$allowedLevel = null;
$officeQuery = "";

if ($currentRole === 'admin') {
    // Admin creates Level 1 Office Admins
    $allowedLevel = 'Level 1';
} elseif ($currentRole === 'office_admin' && $currentOfficeId) {
    // Fetch current user's office level
    $stmt = $pdo->prepare("SELECT office_level FROM provincial_offices WHERE id = ?");
    $stmt->execute([$currentOfficeId]);
    $currentLevel = $stmt->fetchColumn();

    if ($currentLevel === 'Level 1') $allowedLevel = 'Level 2';
    elseif ($currentLevel === 'Level 2') $allowedLevel = 'Level 3';
    elseif ($currentLevel === 'Level 3') $allowedLevel = 'Level 4';
    elseif ($currentLevel === 'Level 4') $allowedLevel = 'Level 5';
}

// Fetch values for dropdowns
$user_office_id = $_SESSION['user']['office_id'] ?? null;
$user_role = $_SESSION['user']['role'];

if ($user_role === 'admin' && empty($user_office_id)) {
    // Super Admin (No Office) - Show Only Level 1 Offices
    $offices = $pdo->query("SELECT * FROM provincial_offices WHERE office_level = 'Level 1' ORDER BY office_name")->fetchAll();
} else {
    // Office User - Show Only Direct Child Offices OR Current Office
    $stmt = $pdo->prepare("SELECT * FROM provincial_offices WHERE parent_office_id = ? OR id = ? ORDER BY office_name");
    $stmt->execute([$user_office_id, $user_office_id]);
    $offices = $stmt->fetchAll();
}

// Create User
if(isset($_POST['create'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $office_id = $_POST['office'] ?: $user_office_id; // Set explicitly or fallback to creator's office_id
    // For office admin, make sure the assigned role isn't 'admin'
    // To accommodate dynamic roles, check if the role exists in the db first
    $roleCheck = $pdo->prepare("SELECT role_key FROM user_roles WHERE role_key = ?");
    $roleCheck->execute([$role]);
    if(!$roleCheck->fetch() && $role !== 'office_admin' && $role !== 'office_user' && $role !== 'front_office_user') {
        die("Invalid role selected.");
    }
    
    if ($currentRole !== 'admin' && in_array($role, ['admin'])) {
        die("You cannot create admin users.");
    }
    
    $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
    $check->execute([$username]);
    if($check->fetch()){
        $message = '<div class="alert alert-danger">Username already taken</div>';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $officer_id = !empty($_POST['officer']) ? $_POST['officer'] : null;
        $section_id = !empty($_POST['section']) ? $_POST['section'] : null;
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, officer_id, section_id, office_id, created_by) VALUES(?,?,?,?,?,?,?)");
        $stmt->execute([$username, $hash, $role, $officer_id, $section_id, $office_id, $currentUser['id']]);
        $message = '<div class="alert alert-success">User created successfully! Linked to ' . htmlspecialchars($allowedLevel??'Office') . '</div>';
    }
}

// Delete User
if(isset($_GET['del'])){
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND created_by=?");
    $stmt->execute([$_GET['del'], $currentUser['id']]);
    header("Location: create_user.php");
    exit;
}

// List Users
if ($currentRole === 'admin') {
    // Admin sees only users CREATED BY THEM (e.g., Level 1 Admins)
    $stmt = $pdo->prepare("SELECT u.*, s.section_name, o.name AS officer_name, po.office_name, po.office_level 
                         FROM users u 
                         LEFT JOIN sections s ON u.section_id=s.id 
                         LEFT JOIN officers o ON u.officer_id=o.id 
                         LEFT JOIN provincial_offices po ON u.office_id=po.id 
                         WHERE u.created_by = ? OR u.created_by IS NULL OR u.role NOT IN ('office_admin', 'office_user')
                         ORDER BY u.id DESC"); 
    $stmt->execute([$currentUser['id']]);
} else {
    // Office Admins see only users they created
    $stmt = $pdo->prepare("SELECT u.*, s.section_name, o.name AS officer_name, po.office_name, po.office_level 
                           FROM users u 
                           LEFT JOIN sections s ON u.section_id=s.id 
                           LEFT JOIN officers o ON u.officer_id=o.id 
                           LEFT JOIN provincial_offices po ON u.office_id=po.id 
                           WHERE u.created_by = ? 
                           ORDER BY u.id DESC");
    $stmt->execute([$currentUser['id']]);
}
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
            <?php if(isset($message)) echo $message; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Users (<?= $allowedLevel ? "Creating $allowedLevel Users" : "General" ?>)</h2>
            </div>

            <!-- Create User Form -->
            <?php if ($allowedLevel || $currentRole === 'admin'): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">Create New User</div>
                <div class="card-body">
                    <form method="post" class="row g-3" onsubmit="return validateForm()">
                        <div class="col-md-3">
                            <label class="form-label">Username</label>
                            <input name="username" id="username" class="form-control" placeholder="Username" required onkeypress="return event.charCode != 32">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Password</label>
                            <input name="password" id="password" type="password" class="form-control" placeholder="Password" required onkeypress="return event.charCode != 32">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Confirm Password</label>
                            <input name="confirm_password" id="confirm_password" type="password" class="form-control" placeholder="Confirm Password" required onkeypress="return event.charCode != 32">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select name="role" id="role" class="form-select" required onchange="toggleFields()">
                                <option value="">Select Role</option>
                                <?php 
                                // Fetch dynamic roles
                                $allRoles = $pdo->query("SELECT * FROM user_roles ORDER BY role_name")->fetchAll();
                                ?>
                                
                                <?php if($currentRole === 'admin'): ?>
                                     <?php foreach($allRoles as $r): ?>
                                        <?php if(!in_array($r['role_key'], ['office_admin', 'office_user', 'front_office_user'])): ?>
                                             <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                                        <?php endif; ?>
                                     <?php endforeach; ?>
                                <?php elseif ($allowedLevel): ?>
                                     <?php foreach($allRoles as $r): ?>
                                        <?php if(!in_array($r['role_key'], ['admin'])): ?>
                                             <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                                        <?php endif; ?>
                                     <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if($allowedLevel): ?>
                                    <option value="office_admin">Office Admin (<?= $allowedLevel ?>)</option>
                                <?php endif; ?>
                            </select>
                            <?php if($currentRole === 'admin'): ?>
                                <div class="mt-2">
                                    <a href="manage_roles.php" class="small"><i class="fas fa-cog me-1"></i> Manage Roles</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Office Selection -->
                        <?php if ($allowedLevel): ?>
                        <div class="col-md-3" id="officeDiv">
                            <label class="form-label">Office (<?= $allowedLevel ?>)</label>
                            <select name="office" class="form-select" required onchange="loadSections(this.value)">
                                <option value="">Select Office</option>
                                <?php foreach($offices as $o): ?>
                                    <option value="<?= $o['id'] ?>" <?= ($o['id'] == $user_office_id) ? 'selected' : '' ?>><?= htmlspecialchars($o['office_name']) ?> (<?= $o['office_level'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Existing Logic -->
                        <div class="col-md-3" id="sectionDiv" style="display:none">
                            <label class="form-label">Section</label>
                            <select name="section" id="section" class="form-select" onchange="loadOfficers()">
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="officerDiv" style="display:none">
                            <label class="form-label">Officer</label>
                            <select name="officer" id="officer" class="form-select">
                                <option value="">Select Officer</option>
                            </select>
                        </div>
                        
                        <div class="col-12 text-end">
                            <button name="create" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Create User</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-info">You cannot create deeper level users (Max level reached or no office assigned).</div>
            <?php endif; ?>

            <!-- User List -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Users Created by You</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Context</th>
                                    <th>Office</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td>
                                    <?php if($u['role'] == 'office_user'): ?>
                                        <span class="badge bg-purple text-white" style="background-color: #6f42c1;">Office User</span>
                                    <?php else: ?>
                                        <?= ucfirst(str_replace('_',' ',$u['role'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($u['section_name']) echo "Section: " . $u['section_name']; ?>
                                    <?php if($u['officer_name']) echo "<br>Officer: " . $u['officer_name']; ?>
                                    <?php if(!$u['section_name'] && !$u['officer_name'] && !$u['office_name']) echo "System"; ?>
                                </td>
                                <td>
                                    <?php if($u['office_name']): ?>
                                        <strong><?= htmlspecialchars($u['office_name']) ?></strong><br>
                                        <small class="text-muted"><?= $u['office_level'] ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($u['role'] != 'admin' || $currentRole == 'admin'): ?>
                                        <a href="?del=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($users)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No users found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFields(){
    let role = $('#role').val();
    
    let isOfficeContext = <?= (in_array($currentRole, ['office_admin', 'office_user']) ? 'true' : 'false') ?>;
    let showSection = isOfficeContext;
    let showOfficer = isOfficeContext;
    
    // Hide office div if looking at standard roles, show if office_user (though PHP handles rendering primarily)
    $('#sectionDiv').toggle(showSection);
    $('#officerDiv').toggle(showOfficer);
    
    // Auto load sections if office is already selected (e.g. for Level options)
    if (showSection) {
        let officeElem = $('select[name="office"]');
        let selectedOfficeId = null;
        
        if (officeElem.length > 0 && officeElem.val() !== "") {
            selectedOfficeId = officeElem.val();
        } else {
            selectedOfficeId = '<?= $user_office_id ?>';
        }
        
        if (selectedOfficeId) {
            loadSections(selectedOfficeId);
        }
    } else {
        // Only clear if we are hiding the sections/officers
        $('#section').html('<option value="">Select Section</option>');
        $('#officer').html('<option value="">Select Officer</option>');
    }
}

function loadSections(officeId) {
    if(officeId){
        $.post('../../core/ajax.php', {office_sections: officeId}, function(data){
            $('#section').html(data);
            $('#officer').html('<option value="">Select Officer</option>'); // reset officers
        });
    } else {
        $('#section').html('<option value="">Select Section</option>');
        $('#officer').html('<option value="">Select Officer</option>');
    }
}

function loadOfficers(){
    let sid = $('#section').val();
    if(sid){
        $.post('../../core/ajax.php', {section_officers: sid}, function(data){
            $('#officer').html('<option value="">Select Officer</option>' + data);
        });
    }
}

function validateForm() {
    var pw = document.getElementById("password").value;
    var cpw = document.getElementById("confirm_password").value;
    if (pw !== cpw) {
        alert("Passwords do not match!");
        return false;
    }
    return true;
}
</script>
<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>