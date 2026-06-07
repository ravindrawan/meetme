<?php
require '../../core/config.php';
if (!isset($_SESSION['user'])) header('Location: ../../index.php');
if (!hasPrivilege('tile_office_performance')) die('Access denied');
$user = $_SESSION['user'];

// ── AJAX: return child offices as JSON ──────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'children') {
    $parent_id = intval($_GET['parent_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, office_name FROM provincial_offices WHERE parent_office_id = ? ORDER BY office_name");
    $stmt->execute([$parent_id]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ── Load Level-1 offices ────────────────────────────────────────────────────
$level1Offices = $pdo->query("SELECT id, office_name FROM provincial_offices WHERE parent_office_id IS NULL ORDER BY office_name")->fetchAll();

$sel_l1 = $_GET['l1'] ?? '';
$sel_l2 = $_GET['l2'] ?? '';
$sel_l3 = $_GET['l3'] ?? '';

// Deepest selected office drives the data query
$selected_office_id = $sel_l3 ?: ($sel_l2 ?: $sel_l1);

// Pre-load L2 / L3 lists for form re-population after GET
$level2Offices = $level3Offices = [];
if ($sel_l1) {
    $st = $pdo->prepare("SELECT id, office_name FROM provincial_offices WHERE parent_office_id = ? ORDER BY office_name");
    $st->execute([$sel_l1]);
    $level2Offices = $st->fetchAll();
}
if ($sel_l2) {
    $st = $pdo->prepare("SELECT id, office_name FROM provincial_offices WHERE parent_office_id = ? ORDER BY office_name");
    $st->execute([$sel_l2]);
    $level3Offices = $st->fetchAll();
}

// ── Date / sort params ──────────────────────────────────────────────────────
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to   = $_GET['date_to']   ?? date('Y-m-d');
$sort  = $_GET['sort']  ?? 'total';
$order = (($_GET['order'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC';

// ── Build WHERE ─────────────────────────────────────────────────────────────
$where  = '';
$params = [];
if (!empty($selected_office_id)) {
    $where    = " AND (o.id = ? OR o.parent_office_id = ?)";
    $params[] = $selected_office_id;
    $params[] = $selected_office_id;
}

$expTotal     = "COUNT(v.visit_id)";
$expCompleted = "SUM(CASE WHEN v.status='completed' THEN 1 ELSE 0 END)";
$orderBy = ($sort === 'percent')
    ? "($expCompleted / NULLIF($expTotal, 0)) $order"
    : "$expTotal $order";

function sortLink($col, $label, $currentSort, $currentOrder) {
    global $sel_l1, $sel_l2, $sel_l3, $date_from, $date_to;
    $newOrder = ($currentSort === $col && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
    $icon = ($currentSort === $col) ? ($currentOrder === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort';
    $qs = http_build_query(['l1'=>$sel_l1,'l2'=>$sel_l2,'l3'=>$sel_l3,'sort'=>$col,'order'=>$newOrder,'date_from'=>$date_from,'date_to'=>$date_to]);
    return "<a href='?$qs' class='text-decoration-none text-dark'>$label <i class='fas $icon'></i></a>";
}

// ── Fetch table data ────────────────────────────────────────────────────────
$sql = "SELECT o.id, o.office_name, o.office_level,
               COUNT(v.visit_id) as total,
               SUM(CASE WHEN v.status='pending'   THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN v.status='ongoing'   THEN 1 ELSE 0 END) as ongoing,
               SUM(CASE WHEN v.status='completed' THEN 1 ELSE 0 END) as completed
        FROM provincial_offices o
        LEFT JOIN sections s ON s.office_id = o.id
        LEFT JOIN visits v ON v.section_id = s.id
        WHERE 1=1 $where
        GROUP BY o.id, o.office_name, o.office_level
        ORDER BY $orderBy";
$stmtData = $pdo->prepare($sql);
$stmtData->execute($params);
$rows = $stmtData->fetchAll();

// Label for breadcrumb
$selectedOfficeName = '';
if ($selected_office_id) {
    $stN = $pdo->prepare("SELECT office_name FROM provincial_offices WHERE id = ?");
    $stN->execute([$selected_office_id]);
    $selectedOfficeName = $stN->fetchColumn();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>VMS | Office Performance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        .filter-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .level-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; }
        .level-1 { background:#e8f4fd; color:#0d6efd; }
        .level-2 { background:#fff3cd; color:#856404; }
        .level-3 { background:#d1e7dd; color:#0a3622; }
        .cascade-arrow { color:#adb5bd; font-size:1.2rem; margin: 0 4px; }
        .select-wrapper { position:relative; }
        .select-wrapper .loading-spinner { position:absolute; right:36px; top:50%; transform:translateY(-50%); display:none; }
        .card { border:none; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.07); }
        .table th { background:#f8f9fa; font-size:.85rem; font-weight:600; }
        .progress { height:6px; border-radius:4px; }
        .breadcrumb-nav { font-size:.85rem; }
    </style>
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="d-flex">
    <div class="sidebar-container d-none d-lg-block">
        <?php include '../../includes/sidebar.php'; ?>
    </div>

    <div class="main-content">
        <div class="container-fluid mt-4">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0 fw-bold"><i class="fas fa-chart-bar text-primary me-2"></i>Office Performance</h4>
                    <?php if ($selectedOfficeName): ?>
                    <nav class="breadcrumb-nav mt-1">
                        <span class="text-muted">Viewing: </span>
                        <strong class="text-primary"><?= htmlspecialchars($selectedOfficeName) ?></strong>
                        <?php if ($sel_l2 || $sel_l3): ?>
                            <a href="?l1=<?= urlencode($sel_l1) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="ms-2 btn btn-sm btn-outline-secondary py-0">
                                <i class="fas fa-arrow-up fa-xs"></i> Go Up
                            </a>
                        <?php endif; ?>
                    </nav>
                    <?php endif; ?>
                </div>
                <?php if (!empty($rows)): ?>
                <span class="badge bg-primary fs-6"><?= count($rows) ?> Office(s)</span>
                <?php endif; ?>
            </div>

            <!-- ── Filter Card ── -->
            <form method="GET" id="filterForm" class="filter-card p-3 mb-4">
                <!-- Hidden fields to preserve other params on sort links -->
                <input type="hidden" name="sort"  value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">

                <div class="row g-2 align-items-end">

                    <!-- Level 1 -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold mb-1">
                            <span class="level-badge level-1">Level 1</span> Ministry / Body
                        </label>
                        <div class="select-wrapper">
                            <select name="l1" id="sel_l1" class="form-select form-select-sm">
                                <option value="">-- All Offices --</option>
                                <?php foreach ($level1Offices as $o): ?>
                                    <option value="<?= $o['id'] ?>" <?= $sel_l1 == $o['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($o['office_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-auto cascade-arrow d-none d-md-block"><i class="fas fa-chevron-right"></i></div>

                    <!-- Level 2 -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold mb-1">
                            <span class="level-badge level-2">Level 2</span> Department
                        </label>
                        <select name="l2" id="sel_l2" class="form-select form-select-sm" <?= empty($level2Offices) ? 'disabled' : '' ?>>
                            <option value="">-- Select Level 2 --</option>
                            <?php foreach ($level2Offices as $o): ?>
                                <option value="<?= $o['id'] ?>" <?= $sel_l2 == $o['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($o['office_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-auto cascade-arrow d-none d-md-block"><i class="fas fa-chevron-right"></i></div>

                    <!-- Level 3 -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold mb-1">
                            <span class="level-badge level-3">Level 3</span> Sub-Office
                        </label>
                        <select name="l3" id="sel_l3" class="form-select form-select-sm" <?= empty($level3Offices) ? 'disabled' : '' ?>>
                            <option value="">-- Select Level 3 --</option>
                            <?php foreach ($level3Offices as $o): ?>
                                <option value="<?= $o['id'] ?>" <?= $sel_l3 == $o['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($o['office_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Search button -->
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                        <a href="?" class="btn btn-outline-secondary btn-sm ms-1">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>

            <?php if (empty($selected_office_id)): ?>
            <div class="alert alert-info border-0 rounded-3 d-flex align-items-center shadow-sm">
                <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                <div>
                    <h6 class="mb-1 fw-bold">Select an Office to view performance</h6>
                    <p class="mb-0 small">Use the cascading dropdowns above — start with a Level 1 Ministry, then drill down to departments.</p>
                </div>
            </div>

            <?php else: ?>

            <!-- ── Summary Cards ── -->
            <?php
            $grandTotal     = array_sum(array_column($rows, 'total'));
            $grandPending   = array_sum(array_column($rows, 'pending'));
            $grandOngoing   = array_sum(array_column($rows, 'ongoing'));
            $grandCompleted = array_sum(array_column($rows, 'completed'));
            $grandPct       = $grandTotal > 0 ? round(($grandCompleted / $grandTotal) * 100, 1) : 0;
            ?>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center border-start border-4 border-primary">
                        <div class="fs-2 fw-bold text-primary"><?= $grandTotal ?></div>
                        <div class="text-muted small">Total Visits</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center border-start border-4 border-warning">
                        <div class="fs-2 fw-bold text-warning"><?= $grandPending ?></div>
                        <div class="text-muted small">Pending</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center border-start border-4 border-info">
                        <div class="fs-2 fw-bold text-info"><?= $grandOngoing ?></div>
                        <div class="text-muted small">Ongoing</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center border-start border-4 border-success">
                        <div class="fs-2 fw-bold text-success"><?= $grandPct ?>%</div>
                        <div class="text-muted small">Completion Rate</div>
                    </div>
                </div>
            </div>

            <!-- ── Data Table ── -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-table me-2"></i>Office-wise Visit Summary</h6>
                    <small><?= htmlspecialchars($selectedOfficeName) ?> — Sub-offices</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-3">Office Name</th>
                                    <th class="text-center">Level</th>
                                    <th class="text-center"><?= sortLink('total', 'Total', $sort, $order) ?></th>
                                    <th class="text-center">Pending</th>
                                    <th class="text-center">Ongoing</th>
                                    <th class="text-center">Completed</th>
                                    <th class="text-center"><?= sortLink('percent', 'Rate %', $sort, $order) ?></th>
                                    <th class="text-center">Drill Down</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No data found for the selected office.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $row):
                                $pct = $row['total'] > 0 ? round(($row['completed'] / $row['total']) * 100, 1) : 0;
                                $lvlNum = (int) filter_var($row['office_level'], FILTER_SANITIZE_NUMBER_INT);
                                $lvlClass = "level-$lvlNum";
                            ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= htmlspecialchars($row['office_name']) ?></td>
                                    <td class="text-center">
                                        <span class="level-badge <?= $lvlClass ?>"><?= htmlspecialchars($row['office_level']) ?></span>
                                    </td>
                                    <td class="text-center fw-bold"><?= $row['total'] ?></td>
                                    <td class="text-center text-warning fw-semibold"><?= $row['pending'] ?></td>
                                    <td class="text-center text-info fw-semibold"><?= $row['ongoing'] ?></td>
                                    <td class="text-center text-success fw-semibold"><?= $row['completed'] ?></td>
                                    <td class="text-center" style="min-width:120px">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                                            </div>
                                            <small class="fw-semibold"><?= $pct ?>%</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        // Build drill-down link: push this office into the next level slot
                                        if ($lvlNum === 1) $drillUrl = "?l1={$row['id']}&date_from=$date_from&date_to=$date_to";
                                        elseif ($lvlNum === 2) $drillUrl = "?l1=$sel_l1&l2={$row['id']}&date_from=$date_from&date_to=$date_to";
                                        else $drillUrl = "?l1=$sel_l1&l2=$sel_l2&l3={$row['id']}&date_from=$date_from&date_to=$date_to";
                                        ?>
                                        <a href="<?= $drillUrl ?>" class="btn btn-outline-primary btn-sm py-0" title="Drill down into this office">
                                            <i class="fas fa-search-plus fa-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── Chart ── -->
            <?php if (!empty($rows)): ?>
            <div class="card">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-chart-bar text-primary me-2"></i>Performance Chart
                </div>
                <div class="card-body">
                    <canvas id="officeChart" style="max-height:380px"></canvas>
                </div>
            </div>
            <script>
            const labels    = <?= json_encode(array_map(fn($r) => $r['office_name'], $rows)) ?>;
            const pending   = <?= json_encode(array_map(fn($r) => (int)$r['pending'],   $rows)) ?>;
            const ongoing   = <?= json_encode(array_map(fn($r) => (int)$r['ongoing'],   $rows)) ?>;
            const completed = <?= json_encode(array_map(fn($r) => (int)$r['completed'], $rows)) ?>;

            new Chart(document.getElementById('officeChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Pending',   data: pending,   backgroundColor: '#ffc107' },
                        { label: 'Ongoing',   data: ongoing,   backgroundColor: '#0dcaf0' },
                        { label: 'Completed', data: completed, backgroundColor: '#198754' }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        x: { stacked: true, ticks: { maxRotation: 45, font: { size: 10 } } },
                        y: { stacked: true, beginAtZero: true }
                    }
                }
            });
            </script>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Cascading dropdown via AJAX ──────────────────────────────────────────────
function loadChildren(parentId, targetSelect, resetBelow) {
    if (!parentId) {
        $(targetSelect).html('<option value="">-- Select --</option>').prop('disabled', true);
        resetBelow && resetBelow();
        return;
    }
    $(targetSelect).html('<option value="">Loading...</option>').prop('disabled', true);
    $.getJSON('index.php', { ajax: 'children', parent_id: parentId }, function(data) {
        let html = '<option value="">-- Select --</option>';
        data.forEach(function(o) {
            html += `<option value="${o.id}">${o.office_name}</option>`;
        });
        $(targetSelect).html(html).prop('disabled', data.length === 0);
    });
}

$('#sel_l1').on('change', function() {
    // Reset L2 and L3
    $('#sel_l2').html('<option value="">-- Select Level 2 --</option>').prop('disabled', true);
    $('#sel_l3').html('<option value="">-- Select Level 3 --</option>').prop('disabled', true);
    loadChildren($(this).val(), '#sel_l2');
});

$('#sel_l2').on('change', function() {
    $('#sel_l3').html('<option value="">-- Select Level 3 --</option>').prop('disabled', true);
    loadChildren($(this).val(), '#sel_l3');
});
</script>
</body>
</html>
