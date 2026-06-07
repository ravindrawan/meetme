<!-- includes/filter_form.php -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <strong>Search &amp; Filter</strong>
    </div>
    <div class="card-body">

        <!-- ── Admin-only: Office Hierarchy Filter ─────────────────────── -->
        <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
        <?php
        // Load Level 1 offices for admin filter
        $l1Offices = $pdo->query("SELECT id, office_name FROM provincial_offices WHERE parent_office_id IS NULL ORDER BY office_name")->fetchAll();
        $f_l1 = $_GET['f_l1'] ?? '';
        $f_l2 = $_GET['f_l2'] ?? '';
        $f_l3 = $_GET['f_l3'] ?? '';

        // Pre-load L2 / L3 for form re-population
        $f_l2Offices = $f_l3Offices = [];
        if ($f_l1) {
            $st = $pdo->prepare("SELECT id, office_name FROM provincial_offices WHERE parent_office_id = ? ORDER BY office_name");
            $st->execute([$f_l1]);
            $f_l2Offices = $st->fetchAll();
        }
        if ($f_l2) {
            $st = $pdo->prepare("SELECT id, office_name FROM provincial_offices WHERE parent_office_id = ? ORDER BY office_name");
            $st->execute([$f_l2]);
            $f_l3Offices = $st->fetchAll();
        }
        ?>
        <div class="border rounded-2 p-3 mb-3" style="background:#f0f4ff;border-color:#c5d5f7 !important">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-building text-primary me-2"></i>
                <span class="fw-semibold text-primary small">Filter by Office <span class="badge bg-primary ms-1">Admin Only</span></span>
            </div>
            <div class="row g-2">
                <!-- Level 1 -->
                <div class="col-md-4">
                    <label class="form-label small mb-1 fw-semibold">
                        <span class="badge rounded-pill" style="background:#e8f4fd;color:#0d6efd;font-size:.7rem">Level 1</span> Ministry / Body
                    </label>
                    <select name="f_l1" id="f_sel_l1" class="form-select form-select-sm">
                        <option value="">-- All Offices --</option>
                        <?php foreach ($l1Offices as $o): ?>
                            <option value="<?= $o['id'] ?>" <?= $f_l1 == $o['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['office_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Level 2 -->
                <div class="col-md-4">
                    <label class="form-label small mb-1 fw-semibold">
                        <span class="badge rounded-pill" style="background:#fff3cd;color:#856404;font-size:.7rem">Level 2</span> Department
                    </label>
                    <select name="f_l2" id="f_sel_l2" class="form-select form-select-sm" <?= empty($f_l2Offices) ? 'disabled' : '' ?>>
                        <option value="">-- All --</option>
                        <?php foreach ($f_l2Offices as $o): ?>
                            <option value="<?= $o['id'] ?>" <?= $f_l2 == $o['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['office_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Level 3 -->
                <div class="col-md-4">
                    <label class="form-label small mb-1 fw-semibold">
                        <span class="badge rounded-pill" style="background:#d1e7dd;color:#0a3622;font-size:.7rem">Level 3</span> Sub-Office
                    </label>
                    <select name="f_l3" id="f_sel_l3" class="form-select form-select-sm" <?= empty($f_l3Offices) ? 'disabled' : '' ?>>
                        <option value="">-- All --</option>
                        <?php foreach ($f_l3Offices as $o): ?>
                            <option value="<?= $o['id'] ?>" <?= $f_l3 == $o['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['office_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Standard filters ────────────────────────────────────────── -->
        <form method="get" class="row g-3" id="filterForm">
            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <!-- Preserve office filter values in form submit -->
            <input type="hidden" name="f_l1" id="hidden_f_l1" value="<?= htmlspecialchars($f_l1 ?? '') ?>">
            <input type="hidden" name="f_l2" id="hidden_f_l2" value="<?= htmlspecialchars($f_l2 ?? '') ?>">
            <input type="hidden" name="f_l3" id="hidden_f_l3" value="<?= htmlspecialchars($f_l3 ?? '') ?>">
            <?php endif; ?>

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
                    <?php 
                    $office_id = $_SESSION['user']['office_id'] ?? null;
                    $role = $_SESSION['user']['role'];
                    // Admin: if office filter active, show sections of that office
                    $filter_office = !empty($_GET['f_l3']) ? $_GET['f_l3'] : (!empty($_GET['f_l2']) ? $_GET['f_l2'] : (!empty($_GET['f_l1']) ? $_GET['f_l1'] : null));
                    $sql_sections = "SELECT * FROM sections WHERE 1=1";
                    $params_sections = [];
                    if ($role === 'admin' && $filter_office) {
                        $sql_sections .= " AND office_id = ?";
                        $params_sections[] = $filter_office;
                    } elseif ($role !== 'admin' && $office_id) {
                        $sql_sections .= " AND office_id = ?";
                        $params_sections[] = $office_id;
                    }
                    $stmt = $pdo->prepare($sql_sections . " ORDER BY section_name");
                    $stmt->execute($params_sections);
                    foreach($stmt as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($_GET['section'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['section_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="officer" class="form-select">
                    <option value="">All Officers</option>
                    <?php 
                    $sql_officers = "SELECT o.id, o.name FROM officers o LEFT JOIN sections s ON o.section_id=s.id WHERE 1=1";
                    $params_officers = [];
                    if ($role === 'admin' && $filter_office) {
                        $sql_officers .= " AND (o.office_id = ? OR s.office_id = ?)";
                        $params_officers[] = $filter_office;
                        $params_officers[] = $filter_office;
                    } elseif ($role !== 'admin' && $office_id) {
                        $sql_officers .= " AND (o.office_id = ? OR s.office_id = ?)";
                        $params_officers[] = $office_id;
                        $params_officers[] = $office_id;
                    }
                    $stmt = $pdo->prepare($sql_officers . " ORDER BY o.name");
                    $stmt->execute($params_officers);
                    foreach($stmt as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= ($_GET['officer'] ?? '') == $o['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($o['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending"   <?= ($_GET['status'] ?? '') === 'pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="ongoing"   <?= ($_GET['status'] ?? '') === 'ongoing'   ? 'selected' : '' ?>>Ongoing</option>
                    <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select">
                    <option value="desc" <?= ($sort ?? 'desc') === 'desc' ? 'selected' : '' ?>>Newest First</option>
                    <option value="asc"  <?= ($sort ?? '') === 'asc'  ? 'selected' : '' ?>>Oldest First</option>
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

<?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
<script>
// Sync cascading office dropdowns → hidden fields before form submit
document.getElementById('filterForm').addEventListener('submit', function() {
    document.getElementById('hidden_f_l1').value = document.getElementById('f_sel_l1').value;
    document.getElementById('hidden_f_l2').value = document.getElementById('f_sel_l2').value;
    document.getElementById('hidden_f_l3').value = document.getElementById('f_sel_l3')?.value ?? '';
});

// Cascading AJAX for Level 1 → Level 2
document.getElementById('f_sel_l1').addEventListener('change', function() {
    const pid = this.value;
    const l2  = document.getElementById('f_sel_l2');
    const l3  = document.getElementById('f_sel_l3');
    l2.innerHTML = '<option value="">Loading...</option>';
    l2.disabled  = true;
    l3.innerHTML = '<option value="">-- All --</option>';
    l3.disabled  = true;
    document.getElementById('hidden_f_l2').value = '';
    document.getElementById('hidden_f_l3').value = '';
    if (!pid) { l2.innerHTML = '<option value="">-- All --</option>'; return; }
    fetch('../../core/ajax.php?office_children=' + pid)
        .then(r => r.json())
        .then(data => {
            let html = '<option value="">-- All --</option>';
            data.forEach(o => html += `<option value="${o.id}">${o.office_name}</option>`);
            l2.innerHTML = html;
            l2.disabled  = data.length === 0;
        });
});

// Cascading AJAX for Level 2 → Level 3
document.getElementById('f_sel_l2').addEventListener('change', function() {
    const pid = this.value;
    const l3  = document.getElementById('f_sel_l3');
    l3.innerHTML = '<option value="">Loading...</option>';
    l3.disabled  = true;
    document.getElementById('hidden_f_l3').value = '';
    if (!pid) { l3.innerHTML = '<option value="">-- All --</option>'; return; }
    fetch('../../core/ajax.php?office_children=' + pid)
        .then(r => r.json())
        .then(data => {
            let html = '<option value="">-- All --</option>';
            data.forEach(o => html += `<option value="${o.id}">${o.office_name}</option>`);
            l3.innerHTML = html;
            l3.disabled  = data.length === 0;
        });
});
</script>
<?php endif; ?>