<?php
require '../../core/config.php';
if (!isset($_SESSION['user'])) header('Location: ../../index.php');
$user = $_SESSION['user'];

// Get settings
$stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $stmt->fetch() ?: ['organization_name' => 'VMS', 'organization_logo' => null];

// Fetch Today's Visits
$today = date('Y-m-d');
$whereVal = "WHERE DATE(v.visit_datetime) = ?";
$paramsVal = [$today];

// Filter by Office/Role (Similar logic to reports)
if (isset($_SESSION['user']['office_id'])) {
    if ($user['role'] !== 'admin') {
         if (in_array($user['role'], ['office_admin', 'office_user'])) {
            $whereVal .= " AND s.office_id = ?";
            $paramsVal[] = $_SESSION['user']['office_id'];
         }
    }
}

// Filter by Officer/Section Head
if ($user['role'] === 'officer' && !empty($user['officer_id'])) {
    $whereVal .= " AND v.officer_id = ?";
    $paramsVal[] = $user['officer_id'];
} elseif ($user['role'] === 'section_head' && !empty($user['section_id'])) {
    $whereVal .= " AND v.section_id = ?";
    $paramsVal[] = $user['section_id'];
}

$sql = "SELECT v.*, s.section_name, co.name as officer_name, vis.name as visitor_name, vis.nic 
        FROM visits v 
        JOIN visitors vis ON v.nic = vis.nic
        JOIN sections s ON v.section_id = s.id 
        LEFT JOIN officers co ON v.officer_id = co.id 
        $whereVal 
        ORDER BY v.visit_datetime DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($paramsVal);
$recentVisits = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= htmlspecialchars($settings['organization_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .sidebar-container { min-height: 100vh; }
        .main-content { width: 100%; padding: 20px; }
        .table-card { border-radius: 10px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
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
        <div class="container-fluid">
            
            <h4 class="mb-4 text-dark fw-bold">Dashboard Overview</h4>

            <!-- Status Cards -->
            <?php include '../../includes/status_cards.php'; ?>

            <!-- Today's Visits -->
            <div class="row">
                <div class="col-12">
                    <div class="card table-card">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-day me-2"></i> Today's Visits</h5>
                            <a href="../visits/list.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-secondary">
                                        <tr>
                                            <th class="ps-4">Visitor</th>
                                            <th>Purpose</th>
                                            <th>Section/Officer</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($recentVisits)): ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">No visits recorded today yet.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($recentVisits as $rv): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-light rounded-circle p-2 me-3 text-primary">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($rv['visitor_name']) ?></div>
                                                                <small class="text-muted"><?= htmlspecialchars($rv['nic']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars(substr($rv['reason'], 0, 30)) . (strlen($rv['reason'])>30 ? '...' : '') ?></td>
                                                    <td>
                                                        <div class="small fw-bold"><?= htmlspecialchars($rv['section_name']) ?></div>
                                                        <small class="text-muted"><?= $rv['officer_name'] ?: 'Not Assigned' ?></small>
                                                    </td>
                                                    <td>
                                                        <i class="far fa-clock text-muted me-1"></i> 
                                                        <?= date('h:i A', strtotime($rv['visit_datetime'])) ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $statusColor = 'secondary';
                                                            if($rv['status']=='completed') $statusColor='success';
                                                            if($rv['status']=='pending') $statusColor='warning';
                                                            if($rv['status']=='ongoing') $statusColor='primary';
                                                        ?>
                                                        <span class="badge bg-<?= $statusColor ?>"><?= ucfirst($rv['status']) ?></span>
                                                    </td>

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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>