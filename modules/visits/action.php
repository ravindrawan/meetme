<?php
require '../../core/config.php';
header('Content-Type: text/html; charset=utf-8');

$id = $_GET['id'] ?? $_POST['id'] ?? '';
if (!$id) die('No ID');

// AJAX add action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action_text'])) {
    $stmt = $pdo->prepare("INSERT INTO actions (visit_id, action_text, user_id) VALUES (?, ?, ?)");
    $stmt->execute([$id, trim($_POST['action_text']), $_SESSION['user']['id']]);
    exit;
}

// Update Status
if (isset($_POST['status'])) {
    $pdo->prepare("UPDATE visits SET status=? WHERE visit_id=?")->execute([$_POST['status'], $id]);
    exit;
}

// Fetch
$stmt = $pdo->prepare("SELECT status FROM visits WHERE visit_id=?");
$stmt->execute([$id]);
$visit = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM actions WHERE visit_id=? ORDER BY action_datetime DESC");
$stmt->execute([$id]);
$actions = $stmt->fetchAll();
?>

<div class="p-3">
    <h5>Visit <?= $id ?> 
        <?php if ($visit['status'] == 'completed'): ?><span class="badge bg-success float-end">Completed</span><?php endif; ?>
    </h5>
    <hr>

    <div class="mb-3">
        <label class="form-label d-block fw-bold">Update Status:</label>
        <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check status-radio" name="status" id="s1" value="pending" <?= $visit['status']=='pending'?'checked':'' ?>>
            <label class="btn btn-outline-secondary" for="s1">Pending</label>

            <input type="radio" class="btn-check status-radio" name="status" id="s2" value="ongoing" <?= $visit['status']=='ongoing'?'checked':'' ?>>
            <label class="btn btn-outline-warning" for="s2">Ongoing</label>

            <input type="radio" class="btn-check status-radio" name="status" id="s3" value="completed" <?= $visit['status']=='completed'?'checked':'' ?>>
            <label class="btn btn-outline-success" for="s3">Completed</label>
        </div>
    </div>

    <?php if ($visit['status'] != 'completed'): ?>
    <form id="actionForm">
        <input type="hidden" name="id" value="<?= $id ?>">
        <textarea name="action_text" class="form-control mb-2" rows="3" required placeholder="Add action details..."></textarea>
        <button type="submit" class="btn btn-primary w-100">Add Action</button>
    </form>
    <?php endif; ?>

    <hr>
<div id="history">
<?php 
foreach ($actions as $a):
    $user_stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $user_stmt->execute([$a['user_id'] ?? 0]);
    $user = $user_stmt->fetchColumn() ?: 'Unknown';
?>
    <div class="border p-2 mb-2 rounded bg-light">
        <small class="text-muted">
            <?= $a['action_datetime'] ?> (<?= htmlspecialchars($user) ?>)
        </small>
        <p class="mb-0"><?= nl2br(htmlspecialchars($a['action_text'])) ?></p>
    </div>
<?php 
endforeach; 
?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$('#actionForm').submit(function(e){
    e.preventDefault();
    $.post('action.php', $(this).serialize(), function(){
        $('#modalBody').load('action.php?id=<?= $id ?>');
    });
});
function closeVisit(id){
    if(confirm('Close visit?')) {
        $('#modalBody').load('action.php?id='+id+'&close=1');
    }
}
$('.status-radio').change(function(){
    let status = $(this).val();
    $.post('action.php', {id: '<?= $id ?>', status: status}, function(){
        // Reload modal content to reflect changes (e.g. show/hide action form)
        $('#modalBody').load('action.php?id=<?= $id ?>');
        // Ideally reload main list too when modal closes
    });
});
</script>