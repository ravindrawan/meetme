<?php 
require '../../core/config.php';

// Access Control
if (!in_array($_SESSION['user']['role'], ['admin', 'office_admin'])) {
    die('Access denied');
}

// Ensure office_id is loaded in session
if (!isset($_SESSION['user']['office_id'])) {
    $stmt = $pdo->prepare("SELECT office_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $_SESSION['user']['office_id'] = $stmt->fetchColumn();
}

$currentUser     = $_SESSION['user'];
$currentOfficeId = $currentUser['office_id'] ?? null;
$currentRole     = $currentUser['role'];

// Determine allowed child level
$allowedLevel = null;
if ($currentRole === 'admin') {
    $allowedLevel = 'Level 1';
} elseif ($currentRole === 'office_admin' && $currentOfficeId) {
    $stmt = $pdo->prepare("SELECT office_level FROM provincial_offices WHERE id = ?");
    $stmt->execute([$currentOfficeId]);
    $currentLevel = $stmt->fetchColumn();
    if ($currentLevel === 'Level 1') $allowedLevel = 'Level 2';
    elseif ($currentLevel === 'Level 2') $allowedLevel = 'Level 3';
    elseif ($currentLevel === 'Level 3') $allowedLevel = 'Level 4';
    elseif ($currentLevel === 'Level 4') $allowedLevel = 'Level 5';
}

// Fetch offices for dropdown
$user_office_id = $currentOfficeId;
$user_role      = $currentRole;

if ($user_role === 'admin' && empty($user_office_id)) {
    $offices = $pdo->query("SELECT * FROM provincial_offices WHERE office_level = 'Level 1' ORDER BY office_name")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM provincial_offices WHERE parent_office_id = ? OR id = ? ORDER BY office_name");
    $stmt->execute([$user_office_id, $user_office_id]);
    $offices = $stmt->fetchAll();
}

$message = '';

// ── Create User ─────────────────────────────────────────────────────────────
if (isset($_POST['create'])) {
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];
    $role      = $_POST['role'];
    $office_id = $_POST['office'] ?: $user_office_id;

    $roleCheck = $pdo->prepare("SELECT role_key FROM user_roles WHERE role_key = ?");
    $roleCheck->execute([$role]);
    if (!$roleCheck->fetch() && !in_array($role, ['office_admin','office_user','front_office_user'])) {
        die("Invalid role selected.");
    }
    if ($currentRole !== 'admin' && in_array($role, ['admin'])) {
        die("You cannot create admin users.");
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $message = '<div class="alert alert-danger">Username already taken</div>';
    } else {
        $hash       = password_hash($password, PASSWORD_DEFAULT);
        $officer_id = !empty($_POST['officer']) ? $_POST['officer'] : null;
        $section_id = !empty($_POST['section']) ? $_POST['section'] : null;
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, officer_id, section_id, office_id, created_by) VALUES(?,?,?,?,?,?,?)");
        $stmt->execute([$username, $hash, $role, $officer_id, $section_id, $office_id, $currentUser['id']]);
        $message = '<div class="alert alert-success">User created successfully!</div>';
    }
}

// ── Delete User ──────────────────────────────────────────────────────────────
if (isset($_GET['del'])) {
    // admin: delete any; office_admin: only users in same office
    if ($currentRole === 'admin') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['del']]);
    } else {
        // Allow delete if target is in same office OR in a direct child office
        // (mirrors the password-reset permission logic)
        $verify = $pdo->prepare("
            SELECT u.id FROM users u
            LEFT JOIN provincial_offices po ON u.office_id = po.id
            WHERE u.id = ?
              AND u.role != 'admin'
              AND (
                  u.office_id = ?
                  OR po.parent_office_id = ?
              )");
        $verify->execute([$_GET['del'], $currentOfficeId, $currentOfficeId]);
        if ($verify->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$_GET['del']]);
        }
    }
    header("Location: create_user.php");
    exit;
}

// ── Password Reset ───────────────────────────────────────────────────────────
if (isset($_POST['reset_password'])) {
    $target_id    = (int)$_POST['target_user_id'];
    $new_password = $_POST['new_password'];

    $canReset = false;
    if ($currentRole === 'admin') {
        $canReset = true;
    } else {
        // Allow reset if: target user is in same office OR in a direct child office (as office_admin)
        $check = $pdo->prepare("
            SELECT u.id FROM users u
            LEFT JOIN provincial_offices po ON u.office_id = po.id
            WHERE u.id = ?
              AND u.role != 'admin'
              AND (
                  u.office_id = ?
                  OR po.parent_office_id = ?
              )");
        $check->execute([$target_id, $currentOfficeId, $currentOfficeId]);
        $canReset = (bool)$check->fetch();
    }

    if ($canReset && strlen($new_password) >= 6) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $target_id]);
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Password reset successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Password reset failed. Minimum 6 characters required or access denied.</div>';
    }
}


// ── Search & Pagination params ────────────────────────────────────────────────
$search    = trim($_GET['search'] ?? '');
$per_page  = 25;
$page1     = max(1, (int)($_GET['page1'] ?? 1));   // own-office page
$page2     = max(1, (int)($_GET['page2'] ?? 1));   // child-admins page
$offset1   = ($page1 - 1) * $per_page;
$offset2   = ($page2 - 1) * $per_page;
$searchLike = "%$search%";

// ── List Users ───────────────────────────────────────────────────────────────
$users       = [];
$childAdmins = [];
$total1 = $total2 = 0;

$baseSelect = "SELECT u.*, s.section_name, o.name AS officer_name, po.office_name, po.office_level
               FROM users u
               LEFT JOIN sections s  ON u.section_id = s.id
               LEFT JOIN officers o  ON u.officer_id = o.id
               LEFT JOIN provincial_offices po ON u.office_id = po.id";

if ($currentRole === 'admin') {
    $whereSearch = $search ? " WHERE (u.username LIKE ? OR po.office_name LIKE ?)" : "";
    $searchParams = $search ? [$searchLike, $searchLike] : [];

    // Count
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN provincial_offices po ON u.office_id = po.id $whereSearch");
    $cStmt->execute($searchParams);
    $total1 = (int)$cStmt->fetchColumn();

    // Data
    $stmt = $pdo->prepare("$baseSelect $whereSearch ORDER BY u.id DESC LIMIT $per_page OFFSET $offset1");
    $stmt->execute($searchParams);
    $users = $stmt->fetchAll();

} else {
    // 1. Own-office users
    $whereOwn = "WHERE u.office_id = ?" . ($search ? " AND (u.username LIKE ? OR s.section_name LIKE ?)" : "");
    $paramsOwn = $search ? [$currentOfficeId, $searchLike, $searchLike] : [$currentOfficeId];

    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN sections s ON u.section_id = s.id LEFT JOIN provincial_offices po ON u.office_id = po.id $whereOwn");
    $cStmt->execute($paramsOwn);
    $total1 = (int)$cStmt->fetchColumn();

    $stmt = $pdo->prepare("$baseSelect $whereOwn ORDER BY u.id DESC LIMIT $per_page OFFSET $offset1");
    $stmt->execute($paramsOwn);
    $users = $stmt->fetchAll();

    // 2. Child-office admins
    $whereChild = "WHERE po.parent_office_id = ? AND u.role = 'office_admin'" . ($search ? " AND (u.username LIKE ? OR po.office_name LIKE ?)" : "");
    $paramsChild = $search ? [$currentOfficeId, $searchLike, $searchLike] : [$currentOfficeId];

    $cStmt2 = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN provincial_offices po ON u.office_id = po.id $whereChild");
    $cStmt2->execute($paramsChild);
    $total2 = (int)$cStmt2->fetchColumn();

    $stmt2 = $pdo->prepare("$baseSelect $whereChild ORDER BY po.office_name, u.id DESC LIMIT $per_page OFFSET $offset2");
    $stmt2->execute($paramsChild);
    $childAdmins = $stmt2->fetchAll();
}

// Helper: build pagination HTML
function buildPagination(int $total, int $current_page, int $per_page, string $page_param, string $search): string {
    $total_pages = (int)ceil($total / $per_page);
    if ($total_pages <= 1) return '';
    $s = htmlspecialchars($search);
    $html = '<nav class="mt-2"><ul class="pagination pagination-sm mb-0 flex-wrap">';
    for ($p = 1; $p <= $total_pages; $p++) {
        $active = $p === $current_page ? ' active' : '';
        $qs = http_build_query(array_merge($_GET, [$page_param => $p, 'search' => $search]));
        $html .= "<li class='page-item$active'><a class='page-link' href='?$qs'>$p</a></li>";
    }
    $html .= '</ul></nav>';
    return $html;
}

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
    <div class="sidebar-container d-none d-lg-block">
        <?php include '../../includes/sidebar.php'; ?>
    </div>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <?php if ($message) echo $message; ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Users <?= $allowedLevel ? "<small class='text-muted fs-6'>(Creating $allowedLevel Users)</small>" : '' ?></h2>
            </div>

            <!-- ── Create User Form ─────────────────────────────────────── -->
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
                                <?php $allRoles = $pdo->query("SELECT * FROM user_roles ORDER BY role_name")->fetchAll(); ?>
                                <?php if ($currentRole === 'admin'): ?>
                                    <?php foreach ($allRoles as $r): ?>
                                        <?php if (!in_array($r['role_key'], ['office_admin','office_user','front_office_user'])): ?>
                                            <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php elseif ($allowedLevel): ?>
                                    <?php foreach ($allRoles as $r): ?>
                                        <?php if ($r['role_key'] !== 'admin'): ?>
                                            <option value="<?= $r['role_key'] ?>"><?= $r['role_name'] ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if ($allowedLevel): ?>
                                    <option value="office_admin">Office Admin (<?= $allowedLevel ?>)</option>
                                <?php endif; ?>
                            </select>
                            <?php if ($currentRole === 'admin'): ?>
                                <div class="mt-2">
                                    <a href="manage_roles.php" class="small"><i class="fas fa-cog me-1"></i> Manage Roles</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($allowedLevel): ?>
                        <div class="col-md-3" id="officeDiv">
                            <label class="form-label">Office (<?= $allowedLevel ?>)</label>
                            <select name="office" class="form-select" required onchange="loadSections(this.value)">
                                <option value="">Select Office</option>
                                <?php foreach ($offices as $o): ?>
                                    <option value="<?= $o['id'] ?>" <?= ($o['id'] == $user_office_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($o['office_name']) ?> (<?= $o['office_level'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

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

            <!-- ── Search bar (shared) ──────────────────────────────────── -->
            <form method="GET" class="mb-3 d-flex gap-2 align-items-center">
                <div class="input-group" style="max-width:360px">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search username / office / section..." value="<?= htmlspecialchars($search) ?>">
                    <?php if ($search): ?>
                    <a href="?" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm px-3">Search</button>
                <?php if ($search): ?>
                <span class="text-muted small">Showing results for: <strong><?= htmlspecialchars($search) ?></strong></span>
                <?php endif; ?>
            </form>

            <!-- ── Section 1: Own-office users ─────────────────────────── -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex align-items-center" style="background:#f0f4ff;border-left:4px solid #0d6efd">
                    <i class="fas fa-users text-primary me-2"></i>
                    <span class="fw-semibold"><?= $currentRole === 'admin' ? 'All System Users' : 'Users in Your Office' ?></span>
                    <span class="badge bg-primary ms-2"><?= $total1 ?></span>
                    <span class="ms-auto text-muted small">Showing <?= count($users) ?> of <?= $total1 ?> &nbsp;|&nbsp; Page <?= $page1 ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Username</th><th>Role</th><th>Context</th><th>Office</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><i class="fas fa-user-circle text-secondary me-1"></i><?= htmlspecialchars($u['username']) ?></td>
                                <td>
                                    <?php
                                    $roleColors = ['admin'=>'danger','office_admin'=>'primary','office_user'=>'purple','front_office_user'=>'info'];
                                    $rc = $roleColors[$u['role']] ?? 'secondary';
                                    $rl = ucfirst(str_replace('_',' ',$u['role']));
                                    $bgStyle = $rc === 'purple' ? 'background-color:#6f42c1;color:#fff' : '';
                                    ?>
                                    <span class="badge bg-<?= $rc ?>" style="<?= $bgStyle ?>"><?= $rl ?></span>
                                </td>
                                <td class="small">
                                    <?php if ($u['section_name']) echo "Section: <strong>{$u['section_name']}</strong>"; ?>
                                    <?php if ($u['officer_name']) echo "<br>Officer: {$u['officer_name']}"; ?>
                                    <?php if (!$u['section_name'] && !$u['officer_name'] && !$u['office_name']) echo '<span class="text-muted">System</span>'; ?>
                                </td>
                                <td>
                                    <?php if ($u['office_name']): ?>
                                        <strong><?= htmlspecialchars($u['office_name']) ?></strong><br>
                                        <small class="text-muted"><?= $u['office_level'] ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($u['role'] !== 'admin' || $currentRole === 'admin'): ?>
                                            <!-- Password Reset button -->
                                            <button class="btn btn-warning" title="Reset Password"
                                                onclick="openResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <!-- Delete button -->
                                            <a href="?del=<?= $u['id'] ?>" class="btn btn-danger"
                                               onclick="return confirm('Delete user &quot;<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>&quot;?')"
                                               title="Delete User">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No users found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total1 > $per_page): ?>
                    <div class="px-3 pb-2">
                        <?= buildPagination($total1, $page1, $per_page, 'page1', $search) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Section 2: Child-office Admins (office_admin only) ──────── -->
            <?php if ($currentRole !== 'admin'): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex align-items-center" style="background:#fff8f0;border-left:4px solid #fd7e14">
                    <i class="fas fa-sitemap text-warning me-2"></i>
                    <span class="fw-semibold">Sub-Office Admins <small class="text-muted fw-normal">(<?= $allowedLevel ?? 'Child Level' ?> Offices)</small></span>
                    <span class="badge bg-warning text-dark ms-2"><?= $total2 ?></span>
                    <span class="ms-auto text-muted small">Showing <?= count($childAdmins) ?> of <?= $total2 ?> &nbsp;|&nbsp; Page <?= $page2 ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($childAdmins)): ?>
                        <p class="text-muted text-center py-3 mb-0">No sub-office admins found.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background:#fff3e0">
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Office</th>
                                    <th>Level</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($childAdmins as $u): ?>
                            <tr>
                                <td><i class="fas fa-user-shield text-warning me-1"></i><?= htmlspecialchars($u['username']) ?></td>
                                <td><span class="badge bg-primary">Office Admin</span></td>
                                <td><strong><?= htmlspecialchars($u['office_name'] ?? '-') ?></strong></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($u['office_level'] ?? '-') ?></span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-warning" title="Reset Password"
                                            onclick="openResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <a href="?del=<?= $u['id'] ?>" class="btn btn-danger"
                                           onclick="return confirm('Delete user &quot;<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>&quot;?')"
                                           title="Delete User">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total2 > $per_page): ?>
                    <div class="px-3 pb-2">
                        <?= buildPagination($total2, $page2, $per_page, 'page2', $search) ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>


<!-- ── Password Reset Modal ──────────────────────────────────────────────── -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <p class="mb-2">User: <strong id="resetUsername"></strong></p>
                    <input type="hidden" name="target_user_id" id="target_user_id">
                    <label class="form-label">New Password <small class="text-muted">(min 6 chars)</small></label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="new_password" class="form-control" minlength="6" required placeholder="New password">
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePwVisibility()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reset_password" class="btn btn-warning btn-sm">
                        <i class="fas fa-save me-1"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openResetModal(userId, username) {
    document.getElementById('target_user_id').value = userId;
    document.getElementById('resetUsername').textContent = username;
    document.getElementById('new_password').value = '';
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}

function togglePwVisibility() {
    let inp  = document.getElementById('new_password');
    let icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type  = 'text';
        icon.classList.replace('fa-eye','fa-eye-slash');
    } else {
        inp.type  = 'password';
        icon.classList.replace('fa-eye-slash','fa-eye');
    }
}

function toggleFields() {
    let isOfficeContext = <?= in_array($currentRole, ['office_admin','office_user']) ? 'true' : 'false' ?>;
    $('#sectionDiv').toggle(isOfficeContext);
    $('#officerDiv').toggle(isOfficeContext);
    if (isOfficeContext) {
        let officeElem = $('select[name="office"]');
        let sid = officeElem.length > 0 && officeElem.val() ? officeElem.val() : '<?= $user_office_id ?>';
        if (sid) loadSections(sid);
    } else {
        $('#section').html('<option value="">Select Section</option>');
        $('#officer').html('<option value="">Select Officer</option>');
    }
}

function loadSections(officeId) {
    if (officeId) {
        $.post('../../core/ajax.php', {office_sections: officeId}, function(data) {
            $('#section').html(data);
            $('#officer').html('<option value="">Select Officer</option>');
        });
    }
}

function loadOfficers() {
    let sid = $('#section').val();
    if (sid) {
        $.post('../../core/ajax.php', {section_officers: sid}, function(data) {
            $('#officer').html('<option value="">Select Officer</option>' + data);
        });
    }
}

function validateForm() {
    if (document.getElementById('password').value !== document.getElementById('confirm_password').value) {
        alert('Passwords do not match!');
        return false;
    }
    return true;
}
</script>
<?php include '../../includes/footer.php'; ?>
</body>
</html>