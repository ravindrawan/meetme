<?php
require '../../core/config.php';
if (!isset($_SESSION['user'])) header('Location: index.php');

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

// Build Query
$where = "";
$params = [$date_from, $date_to];

if ($office_id) {
    $where = " WHERE s.office_id = ? ";
    $params[] = $office_id;
}

// Fetch data
$sql = "SELECT s.section_name, 
               COUNT(f.id) as total_feedback, 
               AVG(f.rating) as avg_rating,
               SUM(CASE WHEN f.rating = 5 THEN 1 ELSE 0 END) as count_5,
               SUM(CASE WHEN f.rating = 4 THEN 1 ELSE 0 END) as count_4,
               SUM(CASE WHEN f.rating = 3 THEN 1 ELSE 0 END) as count_3,
               SUM(CASE WHEN f.rating = 2 THEN 1 ELSE 0 END) as count_2,
               SUM(CASE WHEN f.rating = 1 THEN 1 ELSE 0 END) as count_1
        FROM sections s
        LEFT JOIN visits v ON s.id = v.section_id AND DATE(v.visit_datetime) BETWEEN ? AND ?
        LEFT JOIN visit_feedback f ON v.visit_id = f.visit_id
        $where
        GROUP BY s.id, s.section_name
        HAVING total_feedback > 0
        ORDER BY avg_rating DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare Chart Data
$labels = [];
$ratings = [];
$counts = [];

foreach ($data as $row) {
    $labels[] = $row['section_name'];
    $ratings[] = round($row['avg_rating'], 2);
    $counts[] = $row['total_feedback'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section-wise Feedback</title>
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
                <h2>Section-wise Feedback</h2>
            </div>

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

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Average Rating by Section</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="sectionChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Section Name</th>
                                    <th>Total Feedback</th>
                                    <th>Average Rating</th>
                                    <th>Happy (5)</th>
                                    <th>Good (4)</th>
                                    <th>Neutral (3)</th>
                                    <th>Bad (2)</th>
                                    <th>Angry (1)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data)): ?>
                                    <tr><td colspan="8" class="text-center">No data found for the selected period.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($data as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['section_name']) ?></td>
                                            <td><?= $row['total_feedback'] ?></td>
                                            <td>
                                                <?php
                                                $avg = round($row['avg_rating'], 1);
                                                $color = $avg >= 4 ? 'text-success' : ($avg >= 3 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <strong class="<?= $color ?>"><?= $avg ?> / 5.0</strong>
                                            </td>
                                            <td><?= $row['count_5'] ?></td>
                                            <td><?= $row['count_4'] ?></td>
                                            <td><?= $row['count_3'] ?></td>
                                            <td><?= $row['count_2'] ?></td>
                                            <td><?= $row['count_1'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
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
        const ctx = document.getElementById('sectionChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Average Rating',
                    data: <?= json_encode($ratings) ?>,
                    backgroundColor: '#0d6efd',
                    borderColor: '#0d6efd',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        title: { display: true, text: 'Rating (1-5)' }
                    }
                }
            }
        });
    </script>
<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
