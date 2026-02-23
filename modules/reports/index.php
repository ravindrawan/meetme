<?php
require '../../core/config.php';
if (!isset($_SESSION['user'])) header('Location: ../../index.php');
if (!isset($_SESSION['user'])) header('Location: ../../index.php');
if (!hasPrivilege('tile_reports')) {
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

$office_filter = "";
$office_params = [];

// Apply Office Filter for Non-Admin
if ($user['role'] !== 'admin' && !empty($user['office_id'])) {
    $office_filter = " AND s.office_id = ?";
    $office_params[] = $user['office_id'];
}

// Role filter
$where = $office_filter;
$params = $office_params;

if ($user['role'] === 'section_head' && !empty($user['section_id'])) {
    $where .= " AND v.section_id = ?";
    $params[] = $user['section_id'];
}

$tab = $_GET['tab'] ?? 'section'; // section | officer | gn

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

function sortLink($col, $label, $currentTab, $currentSort, $currentOrder) {
    global $date_from, $date_to; // Preserve date filter if we add it later, currently not used but good practice
    $newOrder = ($currentSort === $col && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
    $icon = 'fa-sort';
    if ($currentSort === $col) {
        $icon = ($currentOrder === 'ASC') ? 'fa-sort-up' : 'fa-sort-down';
    }
    return "<a href='?tab=$currentTab&sort=$col&order=$newOrder' class='text-decoration-none' style='color: black !important;'>$label <i class='fas $icon'></i></a>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>VMS | Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; }
        .card { box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        th { color: black; background-color: #f8f9fa; } /* Added light background for contrast */
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
                <h2><i class="fas fa-chart-bar"></i> Performance Reports</h2>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $tab=='section'?'active':'' ?>" href="?tab=section">Section-wise</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab=='officer'?'active':'' ?>" href="?tab=officer">Officer-wise</a>
                </li>
            </ul>
            <?php if ($tab === 'section'): ?>
            <!-- Section-wise Report -->
            <div class="card">
                <div class="card-header bg-primary text-white"><h5>Section-wise Visit Summary</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Section</th>
                                    <th><?= sortLink('total', 'Total Visits', $tab, $sort, $order) ?></th>
                                    <th>Pending</th>
                                    <th>Ongoing</th>
                                    <th>Completed</th>
                                    <th><?= sortLink('percent', 'Completion %', $tab, $sort, $order) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sql = "SELECT s.section_name,
                                           COUNT(v.visit_id) as total,
                                           SUM(CASE WHEN v.status='pending' THEN 1 ELSE 0 END) as pending,
                                           SUM(CASE WHEN v.status='ongoing' THEN 1 ELSE 0 END) as ongoing,
                                           SUM(CASE WHEN v.status='completed' THEN 1 ELSE 0 END) as completed
                                    FROM sections s
                                    LEFT JOIN visits v ON v.section_id = s.id 
                                    WHERE 1=1 $where
                                    GROUP BY s.id, s.section_name
                                    ORDER BY $orderBy";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                            foreach ($stmt->fetchAll() as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['section_name']) ?></strong></td>
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

            <!-- Section Chart -->
            <div class="card mt-4">
                <div class="card-header bg-white"><h5>Visits Chart</h5></div>
                <div class="card-body">
                    <canvas id="sectionChart" style="max-height: 400px;"></canvas>
                </div>
            </div>

            <script>
            const ctxSec = document.getElementById('sectionChart').getContext('2d');
            const secLabels = [];
            const secPending = [];
            const secOngoing = [];
            const secCompleted = [];

            <?php
            $stmt->execute($params); // Re-execute for chart data
            foreach ($stmt->fetchAll() as $row) {
                echo "secLabels.push('".addslashes($row['section_name'])."');";
                echo "secPending.push({$row['pending']});";
                echo "secOngoing.push({$row['ongoing']});";
                echo "secCompleted.push({$row['completed']});";
            }
            ?>

            new Chart(ctxSec, {
                type: 'bar',
                data: {
                    labels: secLabels,
                    datasets: [
                        { label: 'Pending', data: secPending, backgroundColor: '#ffc107' },
                        { label: 'Ongoing', data: secOngoing, backgroundColor: '#0d6efd' },
                        { label: 'Completed', data: secCompleted, backgroundColor: '#198754' }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
                }
            });
            </script>

            <?php elseif ($tab === 'officer'): ?>
            <!-- Officer-wise Report -->
            <div class="card">
                <div class="card-header bg-success text-white"><h5>Officer-wise Performance</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Officer</th>
                                    <th>Section</th>
                                    <th><?= sortLink('total', 'Total', $tab, $sort, $order) ?></th>
                                    <th>Pending</th>
                                    <th>Ongoing</th>
                                    <th>Completed</th>
                                    <th><?= sortLink('percent', 'Completion %', $tab, $sort, $order) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sql = "SELECT o.name, s.section_name,
                                           COUNT(v.visit_id) as total,
                                           SUM(CASE WHEN v.status='pending' THEN 1 ELSE 0 END) as pending,
                                           SUM(CASE WHEN v.status='ongoing' THEN 1 ELSE 0 END) as ongoing,
                                           SUM(CASE WHEN v.status='completed' THEN 1 ELSE 0 END) as completed
                                    FROM officers o
                                    JOIN sections s ON o.section_id = s.id
                                    LEFT JOIN visits v ON v.officer_id = o.id 
                                    WHERE 1=1 $where
                                    GROUP BY o.id, o.name, s.section_name
                                    ORDER BY $orderBy";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                            foreach ($stmt->fetchAll() as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['section_name']) ?></td>
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

            <!-- Officer Chart -->
            <div class="card mt-4">
                <div class="card-header bg-white"><h5>Officer Performance Chart</h5></div>
                <div class="card-body">
                    <canvas id="officerChart" style="max-height: 400px;"></canvas>
                </div>
            </div>

            <script>
            const ctxOff = document.getElementById('officerChart').getContext('2d');
            const offLabels = [];
            const offPending = [];
            const offOngoing = [];
            const offCompleted = [];

            <?php
            $stmt->execute($params); // Re-execute for chart data
            foreach ($stmt->fetchAll() as $row) {
                echo "offLabels.push('".addslashes($row['name'])."');";
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
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
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