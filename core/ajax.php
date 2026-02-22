<?php require 'config.php';
if(isset($_POST['nic_check'])){
    $nic = strtoupper($_POST['nic_check']);
    $stmt = $pdo->prepare("SELECT * FROM visitors WHERE nic = ?");
    $stmt->execute([$nic]);
    $v = $stmt->fetch();
    echo $v ? json_encode($v) : 'new';
    exit;
}

if(isset($_POST['section_officers'])){
    $sid = $_POST['section_officers'];
    $stmt = $pdo->prepare("SELECT * FROM officers WHERE section_id = ?");
    $stmt->execute([$sid]);
    $html = '<option value="">Select Officer</option>';
    while($o = $stmt->fetch()){
        $html .= "<option value='{$o['id']}'>{$o['name']}</option>";
    }
    echo $html;
    exit;
}




if(isset($_POST['previous_nic'])){
    $nic = strtoupper($_POST['previous_nic']);
    
    // Ensure office_id is loaded
    if (!isset($_SESSION['user']['office_id']) && isset($_SESSION['user']['id'])) {
        $stmt = $pdo->prepare("SELECT office_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $_SESSION['user']['office_id'] = $stmt->fetchColumn();
    }
    
    $isAdmin = (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin');
    $office_id = isset($_SESSION['user']['office_id']) ? (int)$_SESSION['user']['office_id'] : 0;

    $sql = "SELECT v.visit_id, v.visit_datetime, v.reason, v.status, s.section_name, o.name AS officer 
                           FROM visits v 
                           JOIN sections s ON v.section_id = s.id 
                           LEFT JOIN officers o ON v.officer_id = o.id 
                           WHERE v.nic = ?";
    $params = [$nic];
    
    // Strictly filter for non-admins
    if (!$isAdmin) {
        $sql .= " AND s.office_id = ?";
        $params[] = $office_id;
    }
    
    $sql .= " ORDER BY v.visit_datetime DESC LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $visits = $stmt->fetchAll();
    
    if($visits){
        echo '<div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0">Previous Visits ('.count($visits).')</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Visit ID</th>
                                    <th>Date</th>
                                    <th>Reason</th>
                                    <th>Section/Officer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>';
        foreach($visits as $v){
            $badge = $v['status']=='completed' ? 'success' : ($v['status']=='ongoing' ? 'warning' : 'secondary');
            $officerName = $v['officer'] ? $v['officer'] : '<span class="text-muted fst-italic">Not Assigned</span>';
            echo "<tr>
                <td class='ps-3 fw-bold'>{$v['visit_id']}</td>
                <td>".date('d/m/Y H:i', strtotime($v['visit_datetime']))."</td>
                <td>".htmlspecialchars(substr($v['reason'],0,40)).(strlen($v['reason'])>40?'...':'')."</td>
                <td>{$v['section_name']}<br><small class='text-muted'>{$officerName}</small></td>
                <td><span class='badge bg-$badge fs-6'>".ucfirst($v['status'])."</span></td>
            </tr>";
        }
        echo '          </tbody>
                        </table>
                    </div>
                </div>
              </div>';
    }
    exit;
}



?>