<!-- includes/filter_form.php -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <strong>Search & Filter</strong>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="visit_id" class="form-control" placeholder="Visit ID" value="<?= $_GET['visit_id'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="name" class="form-control" placeholder="Name" value="<?= $_GET['name'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="nic" class="form-control" placeholder="NIC" value="<?= $_GET['nic'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="reason" class="form-control" placeholder="Reason" value="<?= $_GET['reason'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control" value="<?= $_GET['date_from'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control" value="<?= $_GET['date_to'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <select name="section" class="form-select">
                    <option value="">All Sections</option>
                    <?php foreach($pdo->query("SELECT * FROM sections ORDER BY section_name") as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($_GET['section'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['section_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="officer" class="form-select">
                    <option value="">All Officers</option>
                    <?php foreach($pdo->query("SELECT * FROM officers ORDER BY name") as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= ($_GET['officer'] ?? '') == $o['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($o['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="ongoing" <?= ($_GET['status'] ?? '') === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select">
                    <option value="desc" <?= ($sort ?? 'desc') === 'desc' ? 'selected' : '' ?>>Newest First</option>
                    <option value="asc" <?= ($sort ?? '') === 'asc' ? 'selected' : '' ?>>Oldest First</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
            </div>
            <div class="col-md-3">
                <a href="list.php" class="btn btn-secondary w-100">Clear All</a>
            </div>
        </form>
    </div>
</div>