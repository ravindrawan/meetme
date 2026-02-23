<?php
require '../../core/config.php';
if (!isset($_SESSION['user'])) header('Location: ../../index.php');
$user = $_SESSION['user'];

// Role-based filtering
$where = " WHERE 1=1 ";
$params = [];

// Office Isolation
if ($user['role'] !== 'admin' && !empty($user['office_id'])) {
    $where .= " AND sections.office_id = ?";
    $params[] = $user['office_id'];
}

if ($user['role'] === 'officer' && !empty($user['officer_id'])) {
    $where .= " AND visits.officer_id = ?";
    $params[] = $user['officer_id'];
} elseif ($user['role'] === 'section_head' && !empty($user['section_id'])) {
    $where .= " AND visits.section_id = ?";
    $params[] = $user['section_id'];
}

// Filters
if ($_GET) {
    if (!empty($_GET['visit_id'])) { $where .= " AND visits.visit_id LIKE ?"; $params[] = "%{$_GET['visit_id']}%"; }
    if (!empty($_GET['name'])) { $where .= " AND visitors.name LIKE ?"; $params[] = "%{$_GET['name']}%"; }
    if (!empty($_GET['nic'])) { $where .= " AND visitors.nic LIKE ?"; $params[] = "%{$_GET['nic']}%"; }
    if (!empty($_GET['reason'])) { $where .= " AND visits.reason LIKE ?"; $params[] = "%{$_GET['reason']}%"; }
    if (!empty($_GET['section'])) { $where .= " AND visits.section_id = ?"; $params[] = $_GET['section']; }
    if (!empty($_GET['officer'])) { $where .= " AND visits.officer_id = ?"; $params[] = $_GET['officer']; }
    if (!empty($_GET['status'])) { $where .= " AND visits.status = ?"; $params[] = $_GET['status']; }
    if (!empty($_GET['date_from'])) { $where .= " AND DATE(visits.visit_datetime) >= ?"; $params[] = $_GET['date_from']; }
    if (!empty($_GET['date_to'])) { $where .= " AND DATE(visits.visit_datetime) <= ?"; $params[] = $_GET['date_to']; }
}

$sort = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Count total
$count_sql = "SELECT COUNT(*) FROM visits 
              JOIN visitors ON visits.nic = visitors.nic 
              JOIN sections ON visits.section_id = sections.id $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Main query
$sql = "SELECT visits.visit_id, visits.visit_datetime, visits.reason, visits.status,
               visitors.name, visitors.nic,
               sections.section_name, COALESCE(officers.name, 'Not Assigned') AS officer
        FROM visits
        JOIN visitors ON visits.nic = visitors.nic
        JOIN sections ON visits.section_id = sections.id
        LEFT JOIN officers ON visits.officer_id = officers.id
        $where
        ORDER BY visits.visit_datetime $sort
        LIMIT $offset, $per_page";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Visits</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Visit List</h2>
            </div>

            <!-- Filter Form -->
            <?php include '../../includes/filter_form.php'; ?>

            <!-- Export Buttons -->
            <?php if ($total_records > 0): ?>
                <div class="mb-3">
                    <form method="post" action="../../includes/export.php" class="d-inline">
                        <input type="hidden" name="format" value="excel">
                        <input type="hidden" name="filters" value="<?= htmlspecialchars(json_encode($_GET)) ?>">
                        <button type="submit" class="btn btn-success me-2">
                            Export to Excel
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($total_records == 0): ?>
                <div class="alert alert-info">No visits found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Visit ID</th><th>Name</th><th>NIC</th><th>Reason</th>
                                <th>Date Time</th><th>Section / Officer</th><th>Status</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($visits as $v): ?>
                            <tr>
                                <td><a href="javascript:void(0)" onclick="showVisitor('<?= $v['nic'] ?>')"><strong><?= $v['visit_id'] ?></strong></a></td>
                                <td><?= htmlspecialchars($v['name']) ?></td>
                                <td><?= $v['nic'] ?></td>
                                <td><?= htmlspecialchars(substr($v['reason'],0,50)) ?>...</td>
                                <td><?= date('d/m/Y H:i', strtotime($v['visit_datetime'])) ?></td>
                                <td><?= $v['section_name'] ?> / <?= $v['officer'] ?></td>
                                <td>
                                    <?php 
                                        $badge = $v['status']=='completed' ? 'success' : ($v['status']=='ongoing' ? 'warning' : 'secondary');
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($v['status']) ?></span>
                                </td>
        <td>
            <div class="btn-group">
                <?php if (hasPrivilege('visit_action')): ?>
                    <button class="btn btn-sm btn-primary" onclick="showActions('<?= $v['visit_id'] ?>')">Actions</button>
                <?php endif; ?>
                <?php if (hasPrivilege('visit_edit')): ?>
                    <button class="btn btn-sm btn-warning" onclick="editVisit('<?= $v['visit_id'] ?>')">Edit</button>
                <?php endif; ?>
                <?php if (hasPrivilege('visit_delete')): ?>
                    <button class="btn btn-sm btn-danger" onclick="deleteVisit('<?= $v['visit_id'] ?>')">Delete</button>
                <?php endif; ?>
            </div>
        </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php 
                $total = $total_records;
                $page = max(1, (int)($_GET['page'] ?? 1));
                include '../../includes/pagination.php'; 
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal & Scripts -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visit Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">Loading...</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showActions(id) {
    $('#modalBody').load('action.php?id=' + id, () => new bootstrap.Modal('#actionModal').show());
}
function editVisit(id) {
    $('#modalBody').load('edit.php?id=' + id, () => {
        $('#actionModal .modal-title').text('Edit Visit');
        new bootstrap.Modal('#actionModal').show();
    });
}
function deleteVisit(id) {
    if(confirm('Delete visit ' + id + '?')) {
        $.post('delete.php', {id: id}, () => location.reload());
    }
}

</script>

<script>
function showVisitor(nic) {
    $('#modalBody').load('../visitors/details.php?nic=' + nic, function(){
        $('#actionModal .modal-title').text('Visitor Details');
        new bootstrap.Modal('#actionModal').show();
    });
}

// Reload list when modal closes to reflect status changes
document.getElementById('actionModal').addEventListener('hidden.bs.modal', function () {
    location.reload();
});
</script>

<?php include '../../includes/footer.php'; ?>
</body>
</html>