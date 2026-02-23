<?php
// Role-based where
$where = " WHERE 1=1 ";
$params = [];
$join = ""; // New JOIN part

if (isset($_SESSION['user']['office_id'])) {
    if ($user['role'] !== 'admin') {
         if (in_array($user['role'], ['office_admin', 'office_user'])) {
            $join .= " JOIN sections s ON v.section_id = s.id ";
            $where .= " AND s.office_id = ? ";
            $params[] = $_SESSION['user']['office_id'];
         }
    }
}

if ($user['role'] === 'officer' && !empty($user['officer_id'])) {
    $where .= " AND v.officer_id = ?";
    $params[] = $user['officer_id'];
} elseif ($user['role'] === 'section_head' && !empty($user['section_id'])) {
    $where .= " AND v.section_id = ?";
    $params[] = $user['section_id'];
}

// Counts - using alias 'v' for visits
$stmt = $pdo->prepare("SELECT COUNT(*) FROM visits v $join $where AND v.status='pending'");
$stmt->execute($params);
$pending = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM visits v $join $where AND v.status='ongoing'");
$stmt->execute($params);
$ongoing = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM visits v $join $where AND v.status='completed'");
$stmt->execute($params);
$completed = $stmt->fetchColumn();

$total = $pending + $ongoing + $completed;
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Total Visits</h6>
                    <h2 class="mb-0 fw-bold"><?= $total ?></h2>
                </div>
                <div class="icon-box bg-light text-primary rounded-circle p-3">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
            <a href="../visits/list.php" class="stretched-link"></a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Pending</h6>
                    <h2 class="mb-0 fw-bold text-warning"><?= $pending ?></h2>
                </div>
                <div class="icon-box bg-light text-warning rounded-circle p-3">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
            </div>
             <a href="../visits/list.php?status=pending" class="stretched-link"></a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex justify-content-between align-items-center">
                 <div>
                    <h6 class="text-muted mb-2">Ongoing</h6>
                    <h2 class="mb-0 fw-bold text-primary"><?= $ongoing ?></h2>
                </div>
                <div class="icon-box bg-light text-primary rounded-circle p-3">
                    <i class="fas fa-spinner fa-2x"></i>
                </div>
            </div>
             <a href="../visits/list.php?status=ongoing" class="stretched-link"></a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 card-hover">
             <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Completed</h6>
                    <h2 class="mb-0 fw-bold text-success"><?= $completed ?></h2>
                </div>
                <div class="icon-box bg-light text-success rounded-circle p-3">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
             <a href="../visits/list.php?status=completed" class="stretched-link"></a>
        </div>
    </div>
</div>

<style>
    .card-hover { transition: transform 0.2s; }
    .card-hover:hover { transform: translateY(-5px); }
</style>