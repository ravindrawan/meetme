<?php
require '../../core/config.php';

$id = $_GET['id'] ?? '';
if (!$id) die('No ID');

// Save
if (isset($_POST['save_edit'])) {
    $officer = !empty($_POST['officer']) ? $_POST['officer'] : null;
    $pdo->prepare("UPDATE visits SET reason = ?, section_id = ?, officer_id = ? WHERE visit_id = ?")
        ->execute([$_POST['reason'], $_POST['section'], $officer, $id]);
    die('<div class="alert alert-success">Saved!</div><script>setTimeout(() => parent.location.reload(), 1000);</script>');
}

// Load visit
$stmt = $pdo->prepare("SELECT * FROM visits WHERE visit_id = ?");
$stmt->execute([$id]);
$visit = $stmt->fetch();
?>

<div class="p-4">
    <form id="editForm">
        <input type="hidden" name="save_edit" value="1">
        <div class="mb-3">
            <label>Reason</label>
            <textarea name="reason" class="form-control" rows="4" required><?= htmlspecialchars($visit['reason']) ?></textarea>
        </div>
        <div class="mb-3">
            <label>Section</label>
            <select name="section" id="section" class="form-select" required>
                <?php foreach ($pdo->query("SELECT * FROM sections") as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $visit['section_id'] ? 'selected' : '' ?>><?= $s['section_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Officer (Optional)</label>
            <select name="officer" id="officer" class="form-select">
                <option value="">-- Loading... --</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$('#editForm').on('submit', function(e) {
    e.preventDefault();
    $.post('edit_visit.php?id=<?= $id ?>', $(this).serialize(), function(data) {
        $('#modalBody').html(data);
    });
});

function loadOfficers() {
    $.post('../../core/ajax.php', {section_officers: $('#section').val()}, function(data) {
        $('#officer').html('<option value="">-- Not Assigned --</option>' + data);
        $('#officer').val('<?= $visit['officer_id'] ?? "" ?>');
    });
}
$('#section').on('change', loadOfficers);
loadOfficers();
</script>