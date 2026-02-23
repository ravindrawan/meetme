<?php require '../../core/config.php';
$nic = $_GET['nic'];
$stmt = $pdo->prepare("SELECT * FROM visitors WHERE nic = ?");
$stmt->execute([$nic]);
$visitor = $stmt->fetch();

// Ensure office_id is loaded
if (!isset($_SESSION['user']['office_id']) && isset($_SESSION['user']['id'])) {
    $stmt = $pdo->prepare("SELECT office_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $_SESSION['user']['office_id'] = $stmt->fetchColumn();
}

$office_id = (isset($_SESSION['user']['office_id']) && $_SESSION['user']['role'] !== 'admin') ? (int)$_SESSION['user']['office_id'] : null;

$sql = "SELECT v.*, s.section_name, COALESCE(o.name, 'Not Assigned') AS officer 
                       FROM visits v 
                       JOIN sections s ON v.section_id = s.id 
                       LEFT JOIN officers o ON v.officer_id = o.id 
                       WHERE v.nic = ?";
$params = [$nic];

if ($office_id) {
    $sql .= " AND s.office_id = ?";
    $params[] = $office_id;
}

$sql .= " ORDER BY v.visit_datetime DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

// Fetch actions for each visit (Added for enhancement)
foreach ($visits as &$visit_ref) {
    $stmt_actions = $pdo->prepare("SELECT a.*, u.username as user_name 
                                 FROM actions a 
                                 LEFT JOIN users u ON a.user_id = u.id 
                                 WHERE a.visit_id = ? 
                                 ORDER BY a.action_datetime DESC");
    $stmt_actions->execute([$visit_ref['visit_id']]);
    $visit_ref['actions'] = $stmt_actions->fetchAll();
}
unset($visit_ref); 
?>
<div class="p-3">
    <h5>Visitor: <?= htmlspecialchars($visitor['name']) ?> (<?= $nic ?>)</h5>
    <hr>
    <hr>
    <p><strong>Phone:</strong> <?= htmlspecialchars($visitor['phone']) ?></p>
    <p><strong>Whatsapp:</strong> <?= htmlspecialchars($visitor['whatsapp']) ?></p>
    
    <hr>
    <h6>Previous Visits (<?= count($visits) ?>)</h6>
    <?php foreach($visits as $v): ?>
    <div class="border rounded p-2 mb-2 bg-light">
        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($v['visit_datetime'])) ?></small><br>
        <strong><?= $v['visit_id'] ?></strong> - <?= $v['section_name'] ?> / <?= $v['officer'] ?><br>
        Reason: <?= htmlspecialchars($v['reason']) ?><br>
        Status: <?= ucfirst($v['status']) ?>

        <?php if (!empty($v['actions'])): ?>
            <div class="mt-2 ps-3 border-start" style="font-size: 0.9em;">
                <strong class="text-muted">Actions Taken:</strong>
                <?php foreach($v['actions'] as $action): ?>
                    <div class="mb-1">
                        <small class="text-dark fw-bold"><?= date('d M Y, h:i A', strtotime($action['action_datetime'])) ?></small>
                        <span class="text-muted">- <?= nl2br(htmlspecialchars($action['action_text'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>