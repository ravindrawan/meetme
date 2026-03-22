<?php
require '../../core/config.php';
if (!isset($_SESSION['user'])) header('Location: ../../index.php');
if (!hasPrivilege('tile_office_performance')) {
    die('Access denied');
}
$user = $_SESSION['user'];

// Ensure office_id is loaded
if (!isset($_SESSION['user']['office_id']) && isset($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare("SELECT office_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $_SESSION['user']['office_id'] = $stmt->fetchColumn();
    if(isset($_SESSION['user']['office_id'])) $_SESSION['user']['office_id'] = (int)$_SESSION['user']['office_id'];
}

// Fetch available offices for filter
$officeListQuery = "SELECT id, office_name FROM provincial_offices o WHERE 1=1";
$officeListParams = [];
if ($user['role'] !== 'admin' && !empty($user['office_id'])) {
    $officeListQuery .= " AND (o.id = ? OR o.parent_office_id = ?)";
    $officeListParams[] = $user['office_id'];
    $officeListParams[] = $user['office_id'];
}
$officeListQuery .= " ORDER BY office_name";
$stmtOffices = $pdo->prepare($officeListQuery);
$stmtOffices->execute($officeListParams);
$availableOffices = $stmtOffices->fetchAll();

$selected_office_id = $_GET['office_id'] ?? '';

$where = "";
$params = [];

if (!empty($selected_office_id)) {
    $where = " AND o.id = ?";
    $params[] = $selected_office_id;
}

// Sorting Logic
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$sort = $_GET['sort'] ?? 'total';
$order = $_GET['order'] ?? 'DESC';
$order = ($order === 'ASC') ? 'ASC' : 'DESC'; // Sanitize

// Base expressions
$expTotal = "COUNT(v.visit_id)";
$expCompleted = "SUM(CASE WHEN v.status='completed' THEN 1 ELSE 0 END)";

$orderBy = "$expTotal $order";
if ($sort === 'percent') {
    // Avoid division by zero
    $orderBy = "($expCompleted / NULLIF($expTotal, 0)) $order";
}

function sortLink($col, $label, $currentSort, $currentOrder) {
    global $date_from, $date_to, $selected_office_id; 
    $newOrder = ($currentSort === $col && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
    $icon = 'fa-sort';
    if ($currentSort === $col) {
        $icon = ($currentOrder === 'ASC') ? 'fa-sort-up' : 'fa-sort-down';
    }
    return "<a href='?office_id=".htmlspecialchars((string)$selected_office_id)."&sort=$col&order=$newOrder' class='text-decoration-none' style='color: black !important;'>$label <i class='fas $icon'></i></a>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>VMS | Office Performance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; }
        .card { box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        th { color: black; background-color: #f8f9fa; } 
    </style>
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar-container d-none d-lg-block">
        <?php include '../../includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid mt-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-chart-line"></i> Office Performance</h2>
            </div>
            
            <form method="GET" class="card p-3 mb-4 shadow-sm border-0 rounded-3">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Select Office</label>
                        <select name="office_id" class="form-select" required onchange="this.form.submit()">
                            <option value="">-- Choose an Office --</option>
                            <?php foreach($availableOffices as $off): ?>
                                <option value="<?= $off['id'] ?>" <?= $selected_office_id == $off['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($off['office_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
            
            <?php if(empty($selected_office_id)): ?>
                <div class="alert alert-info shadow-sm d-flex align-items-center rounded-3">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                        <h5 class="mb-1 fw-bold">Select an Office</h5>
                        <p class="mb-0">Please use the dropdown menu above to select a specific office and view its performance metrics.</p>
                    </div>
                </div>
            <?php else: ?>

            <!-- Office-wise Report -->
            <div class="card">
                <div class="card-header bg-primary text-white"><h5>Office-wise Visit Summary</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Office Name</th>
                                    <th><?= sortLink('total', 'Total Visits', $sort, $order) ?></th>
                                    <th>Pending</th>
                                    <th>Ongoing</th>
                                    <th>Completed</th>
                                    <th><?= sortLink('percent', 'Completion %', $sort, $order) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sql = "SELECT o.office_name,
                                           COUNT(v.visit_id) as total,
                                            SUM(CASE WHEN v.status='pending'  THEN 1 ELSE 0 END) as pending,
                                            SUM(CASE WHEN v.status='ongoing'  THEN 1 ELSE 0 END) as ongoing,
                                            SUM(CASE WHEN v.status='completed' THEN 1 ELSE 0 END) as completed
                                    FROM provincial_offices o
                                    LEFT JOIN sections s ON s.office_id = o.id
                                    LEFT JOIN visits v ON v.section_id = s.id 
                                    WHERE 1=1 $where
                                    GROUP BY o.id, o.office_name
                                    ORDER BY $orderBy";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                            foreach ($stmt->fetchAll() as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['office_name']) ?></strong></td>
                                    <td><?= $row['total'] ?></td>
                                    <td class="text-warning"><?= $row['pending'] ?></td>
                                    <td class="text-primary"><?= $row['ongoing'] ?></td>
                                    <td class="text-success"><?= $row['completed'] ?></td>
                                    <td>
                                        <?php 
                                        $pct = $row['total'] > 0 ? round(($row['completed'] / $row['total']) * 100, 1) : 0;
                                        echo $pct . '%';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Office Chart -->
            <div class="card mt-4">
                <div class="card-header bg-white"><h5>Office Performance Chart</h5></div>
                <div class="card-body">
                    <canvas id="officeChart" style="max-height: 400px;"></canvas>
                </div>
            </div>

            <script>
            const ctxOff = document.getElementById('officeChart').getContext('2d');
            const offLabels = [];
            const offPending = [];
            const offOngoing = [];
            const offCompleted = [];

            <?php
            $stmt->execute($params); // Re-execute for chart data
            foreach ($stmt->fetchAll() as $row) {
                echo "offLabels.push('".addslashes($row['office_name'])."');";
                echo "offPending.push({$row['pending']});";
                echo "offOngoing.push({$row['ongoing']});";
                echo "offCompleted.push({$row['completed']});";
            }
            ?>

            new Chart(ctxOff, {
                type: 'bar',
                data: {
                    labels: offLabels,
                    datasets: [
                        { label: 'Pending', data: offPending, backgroundColor: '#ffc107' },
                        { label: 'Ongoing', data: offOngoing, backgroundColor: '#0d6efd' },
                        { label: 'Completed', data: offCompleted, backgroundColor: '#198754' }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { 
                        x: { stacked: true }, 
                        y: { stacked: true, beginAtZero: true } 
                    }
                }
            });
            </script>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
</body>
</html>
