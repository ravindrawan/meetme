<?php require '../../core/config.php';
$id = $_GET['id'] ?? '';
if(!$id) die('Invalid ID');

$stmt = $pdo->prepare("SELECT v.visit_id, v.visit_datetime, v.reason,
                              vis.name, vis.nic,
                              s.section_name, COALESCE(o.name, 'Not Assigned') AS officer
                       FROM visits v
                       JOIN visitors vis ON v.nic = vis.nic
                       JOIN sections s ON v.section_id = s.id
                       LEFT JOIN officers o ON v.officer_id = o.id
                       WHERE v.visit_id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();
if(!$data) die('Visit not found');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Token <?= $id ?></title>
    <style>
        @media print { 
            body { width:70mm; font-family: 'Poppins', sans-serif; margin:0; padding:5px; font-size:12px; } 
            @page { margin: 0; }
        }
        body { font-family: sans-serif; }
        .rating-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 5px; 
            margin: 10px 0; 
            text-align: left;
        }
        .rating-item { 
            display: flex; 
            align-items: center; 
            font-size: 11px;
        }
        .box { 
            border: 1px solid #000; 
            min-width: 25px; 
            height: 25px; 
            text-align: center; 
            line-height: 25px; 
            font-size: 16px; 
            margin-right: 5px; 
            border-radius: 4px;
        }
        .feedback-line { margin-top: 5px; border-bottom: 1px dashed #000; height: 20px; }
        h3 { margin: 5px 0; }
        p { margin: 3px 0; }
        hr { border-top: 1px dashed #000; margin: 5px 0; }
    </style>
</head>
<body onload="window.print()">
<div style="text-align:center;">
    <h3>Visitor Token</h3>
    <hr>
    <div style="text-align: left; padding-left: 5px;">
        <p><b>ID:</b> <?= $data['visit_id'] ?></p>
        <p><b>Time:</b> <?= date('Y-m-d H:i', strtotime($data['visit_datetime'])) ?></p>
        <p><b>Name:</b> <?= htmlspecialchars($data['name']) ?></p>
        <p><b>Sec:</b> <?= $data['section_name'] ?></p>
        <p><b>Off:</b> <?= $data['officer'] ?></p>
    </div>
    <hr>
    <p style="font-weight:bold; margin-bottom:5px;">Rate our Service</p>
    <div class="rating-grid">
        <div class="rating-item"><div class="box">☹</div> 1-ඉතා අසතුටුදායකයි</div>
        <div class="rating-item"><div class="box">😐</div> 2-අසතුටුදායකයි</div>
        <div class="rating-item"><div class="box">🙂</div> 3-සාමාන්‍යයි</div>
        <div class="rating-item"><div class="box">😊</div> 4-සතුටුදායකයි</div>
        <div class="rating-item"><div class="box">😍</div> 5-ඉතා සතුටුදායකයි</div>
    </div>
    <div style="text-align: left; margin-top: 10px;">
        <span style="font-weight: bold;">Feedback:</span>
        <div class="feedback-line"></div>
    </div>
    <br><small>Thank you!</small>
</div>
</body>
</html>