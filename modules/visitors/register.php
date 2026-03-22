<?php require '../../core/config.php';
if(!isset($_SESSION['user'])) header('Location: ../../index.php');
$user = $_SESSION['user'];

// Get settings
$stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $stmt->fetch() ?: ['organization_name' => 'VMS', 'organization_logo' => null];

function validate_nic($nic) {
    $nic = str_replace(' ', '', strtoupper($nic));
    if(preg_match('/^[0-9]{9}[VX]$/', $nic) || preg_match('/^[0-9]{12}$/', $nic)) {
        return $nic;
    }
    return false;
}

function generate_visit_id($pdo) {
    $stmt = $pdo->query("SELECT visit_id FROM visits ORDER BY visit_id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $num = $last ? (int)$last + 1 : 1;
    return str_pad($num, 8, '0', STR_PAD_LEFT);
}

if(isset($_POST['nic'])) {
    $nic = validate_nic($_POST['nic']);
    if(!$nic) die("Invalid NIC format");
   
    $stmt = $pdo->prepare("SELECT * FROM visitors WHERE nic = ?");
    $stmt->execute([$nic]);
    $visitor = $stmt->fetch();
   
    if(!$visitor) {
        $stmt = $pdo->prepare("INSERT INTO visitors (nic,name,phone,whatsapp) VALUES (?,?,?,?)");
        $stmt->execute([$nic, $_POST['name'], $_POST['phone'], $_POST['whatsapp']]);
    }
   
    // Create visit - Officer optional
    $visit_id = generate_visit_id($pdo);
    $officer_id = !empty($_POST['officer']) ? $_POST['officer'] : NULL;
    $client_time = !empty($_POST['client_time']) ? $_POST['client_time'] : date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("INSERT INTO visits (visit_id, nic, reason, section_id, officer_id, visit_datetime) VALUES (?, ?, ?, ?, ?, ?)");
	
	$reason = $_POST['reason'] === 'Other' ? $_POST['other_reason'] : $_POST['reason'];
	$stmt->execute([$visit_id, $nic, $reason, $_POST['section'], $officer_id, $client_time]);
   
    echo "<script>window.open('../visits/receipt.php?id=$visit_id','_blank');</script>";
    echo "<script>alert('Visitor registered: $visit_id');</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Visit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css"> <!-- Shared layout CSS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
                <h2>Front Office - Register Visitor</h2>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" id="visitForm">
                        <input type="hidden" name="client_time" id="client_time">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">NIC Number *</label>
                                <input name="nic" id="nic" class="form-control mb-3" required>
                                <label class="form-label">Name *</label>
                                <input name="name" id="name" class="form-control mb-3" required>
                                <label class="form-label">Phone</label>
                                <input name="phone" id="phone" class="form-control mb-3">
                                <label class="form-label">Whatsapp</label>
                                <input name="whatsapp" id="whatsapp" class="form-control mb-3">
                            </div>
                            <div class="col-md-6">
                                    
                                
                                <label class="form-label">Reason for Visit *</label>
                <select name="reason" id="reason" class="form-select mb-3" required onchange="toggleOther()">
                    <option value="">Select Reason</option>
                    <?php 
                    $office_id = $_SESSION['user']['office_id'] ?? 0;
                    $stmt = $pdo->prepare("SELECT * FROM visit_reasons WHERE office_id = ? ORDER BY reason_text");
                    $stmt->execute([$office_id]);
                    foreach($stmt->fetchAll() as $r): 
                    ?>
                        <option value="<?= htmlspecialchars($r['reason_text']) ?>"><?= htmlspecialchars($r['reason_text']) ?></option>
                    <?php endforeach; ?>
                    <option value="Other">Other</option>
                </select>
                <div id="otherReason" style="display:none" class="mt-2 text-muted">
                    <textarea name="other_reason" class="form-control" placeholder="Explain other reason" rows="3"></textarea>
                </div>
                                
                                
                                <label class="form-label">Section *</label>
                                <select name="section" id="section" class="form-select mb-3" required>
                                    <option value="">Select</option>
                                    <?php
                                    // Ensure office_id is loaded
                                    if (!isset($_SESSION['user']['office_id']) && isset($_SESSION['user']['id'])) {
                                        $stmt = $pdo->prepare("SELECT office_id FROM users WHERE id = ?");
                                        $stmt->execute([$_SESSION['user']['id']]);
                                        $_SESSION['user']['office_id'] = $stmt->fetchColumn();
                                        if(isset($_SESSION['user']['office_id'])) $_SESSION['user']['office_id'] = (int)$_SESSION['user']['office_id'];
                                    }
                                    
                                    $office_id = (isset($_SESSION['user']['office_id']) && in_array($_SESSION['user']['role'], ['office_admin', 'office_user'])) ? $_SESSION['user']['office_id'] : null;
                                    
                                    $sql = "SELECT * FROM sections WHERE 1=1"; 
                                    $params = [];
                                    
                                    if ($office_id) {
                                        $sql .= " AND office_id = ?";
                                        $params[] = $office_id;
                                    }
                                    
                                    $sql .= " ORDER BY section_name";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute($params);
                                    $secs = $stmt->fetchAll();
                                    
                                    foreach($secs as $s) echo "<option value='{$s['id']}'>{$s['section_name']}</option>";
                                    ?>
                                </select>
                                <label class="form-label">Officer (Optional)</label>
                                <select name="officer" id="officer" class="form-select mb-3">
                                    <option value="">-- Select Officer Later --</option>
                                </select>
                                <label class="d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-success w-100">Register & Print Token</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div id="previousVisits" class="mt-4"></div>

            <?php include '../../includes/footer.php'; ?>
        </div>
    </div>
</div>

<script>
$('#nic').on('blur', function(){
    let nic = $(this).val().trim();
    if(nic.length >= 10){
        $.post('../../core/ajax.php', {nic_check: nic}, function(data){
            if(data != 'new'){
                let v = JSON.parse(data);
                $('#name').val(v.name);
                $('#phone').val(v.phone);
                $('#whatsapp').val(v.whatsapp);
            }
        });
    }
});

$('#section').on('change', function(){
    let sid = $(this).val();
    if(sid){
        $.post('../../core/ajax.php', {section_officers: sid}, function(data){
            $('#officer').html('<option value="">-- Select Officer Later --</option>' + data);
        });
    } else {
        $('#officer').html('<option value="">-- Select Officer Later --</option>');
    }
});

// Previous visits
$('#nic').on('blur', function(){
    let nic = $(this).val().trim().toUpperCase();
    if(nic.length >= 10){
        $.post('../../core/ajax.php', {previous_nic: nic}, function(data){
            $('#previousVisits').html(data);
        });
    } else {
        $('#previousVisits').html('');
    }
});
</script>

<script>
function toggleOther() {
    let sel = $('#reason').val();
    $('#otherReason').toggle(sel === 'Other');
}

$('#visitForm').on('submit', function() {
    let dt = new Date();
    let Y = dt.getFullYear();
    let m = String(dt.getMonth() + 1).padStart(2, '0');
    let d = String(dt.getDate()).padStart(2, '0');
    let H = String(dt.getHours()).padStart(2, '0');
    let i = String(dt.getMinutes()).padStart(2, '0');
    let s = String(dt.getSeconds()).padStart(2, '0');
    $('#client_time').val(`${Y}-${m}-${d} ${H}:${i}:${s}`);
});
</script>

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>