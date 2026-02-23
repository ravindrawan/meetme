<?php
require '../../core/config.php';
if (!isset($_SESSION['user'])) header('Location: index.php');

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Ensure office_id is loaded
if (!isset($_SESSION['user']['office_id']) && isset($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare("SELECT office_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $_SESSION['user']['office_id'] = $stmt->fetchColumn();
    if(isset($_SESSION['user']['office_id'])) $_SESSION['user']['office_id'] = (int)$_SESSION['user']['office_id'];
}

$user = $_SESSION['user'];
$office_id = ($user['role'] !== 'admin' && isset($user['office_id'])) ? $user['office_id'] : null;

// Filter query
$where = "WHERE DATE(v.visit_datetime) BETWEEN ? AND ?";
$params = [$date_from, $date_to];

if ($office_id) {
    $where .= " AND s.office_id = ?";
    $params[] = $office_id;
}

$sql = "SELECT f.*, v.visit_datetime, vis.name, vis.nic, s.section_name 
        FROM visit_feedback f 
        JOIN visits v ON f.visit_id = v.visit_id 
        JOIN visitors vis ON v.nic = vis.nic 
        JOIN sections s ON v.section_id = s.id
        $where
        ORDER BY f.created_at DESC 
        LIMIT $offset, $per_page";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedback = $stmt->fetchAll();

$count_sql = "SELECT COUNT(*) FROM visit_feedback f 
              JOIN visits v ON f.visit_id = v.visit_id 
              JOIN sections s ON v.section_id = s.id
              $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// Fetch stats for graph
$graph_sql = "SELECT f.rating, COUNT(*) as count 
              FROM visit_feedback f 
              JOIN visits v ON f.visit_id = v.visit_id 
              JOIN sections s ON v.section_id = s.id
              $where 
              GROUP BY f.rating";
$stmt = $pdo->prepare($graph_sql);
$stmt->execute($params);
$data = array_fill(1, 5, 0);
while ($row = $stmt->fetch()) {
    $data[$row['rating']] = (int)$row['count'];
}
$chartData = array_values($data);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <h2>Customer Feedback</h2>
            </div>

            <!-- Filter -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rating Graph -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Rating Distribution (<?= $date_from ?> to <?= $date_to ?>)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartDaily" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (count($feedback) == 0): ?>
                <div class="alert alert-info">No feedback entries found for this period.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover bg-white shadow-sm rounded">
                        <thead class="table-dark">
                            <tr>
                                <th>Visit ID</th>
                                <th>Visitor</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Visit Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($feedback as $f): ?>
                                <tr>
                                    <td><?= htmlspecialchars($f['visit_id']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($f['name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($f['nic']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $rating = (int)$f['rating'];
                                        $colors = [1 => '#dc3545', 2 => '#fd7e14', 3 => '#ffc107', 4 => '#20c997', 5 => '#198754'];
                                        $icons = [1 => 'fa-frown', 2 => 'fa-frown-open', 3 => 'fa-meh', 4 => 'fa-smile', 5 => 'fa-laugh-beam'];
                                        ?>
                                        <i class="fas <?= $icons[$rating] ?>" style="color: <?= $colors[$rating] ?>; font-size: 1.5rem;"></i>
                                        <span class="ms-2 badge bg-light text-dark border"><?= $rating ?>/5</span>
                                    </td>
                                    <td><?= nl2br(htmlspecialchars($f['comment'])) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($f['visit_datetime'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <nav>
                    <ul class="pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page-1 ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">Previous</a>
                        </li>
                        <?php 
                        $total_pages = ceil($total_records / $per_page);
                        for($i=1; $i<=$total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page+1 ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

    <script>
        const chartLabels = ['Angry (1)', 'Unhappy (2)', 'Neutral (3)', 'Happy (4)', 'Very Happy (5)'];
        const chartColors = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#198754'];
        
        function createChart(canvasId, data) {
            new Chart(document.getElementById(canvasId), {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Number of Feedbacks',
                        data: data,
                        backgroundColor: chartColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    },
                    plugins: { legend: { display: false } },
                    responsive: true
                }
            });
        }

        createChart('chartDaily', <?= json_encode($chartData) ?>);
    </script>
<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
