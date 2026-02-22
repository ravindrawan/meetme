<?php
require '../core/config.php';
if (!isset($_SESSION['user'])) exit;

$format = $_POST['format'] ?? 'excel';
$filters = json_decode($_POST['filters'] ?? '{}', true);

// Build filter text for header
$filter_text = "All Visits";
if (!empty($filters)) {
    $parts = [];
    if (!empty($filters['visit_id'])) $parts[] = "Visit ID: {$filters['visit_id']}";
    if (!empty($filters['name'])) $parts[] = "Name: {$filters['name']}";
    if (!empty($filters['nic'])) $parts[] = "NIC: {$filters['nic']}";
    if (!empty($filters['reason'])) $parts[] = "Reason: {$filters['reason']}";
    if (!empty($filters['section'])) {
        $s = $pdo->query("SELECT section_name FROM sections WHERE id = " . (int)$filters['section'])->fetchColumn();
        $parts[] = "Section: $s";
    }
    if (!empty($filters['officer'])) {
        $o = $pdo->query("SELECT name FROM officers WHERE id = " . (int)$filters['officer'])->fetchColumn();
        $parts[] = "Officer: $o";
    }
    if (!empty($filters['status'])) $parts[] = "Status: " . ucfirst($filters['status']);
    if (!empty($filters['date_from'])) $parts[] = "From: {$filters['date_from']}";
    if (!empty($filters['date_to'])) $parts[] = "To: {$filters['date_to']}";
    if (!empty($parts)) $filter_text = "Filtered: " . implode(" | ", $parts);
}

// [Same filtering logic - unchanged]
$where = " WHERE 1=1 "; $params = [];
foreach ($filters as $key => $value) {
    if (empty($value)) continue;
    switch ($key) {
        case 'visit_id': case 'name': case 'nic': case 'reason':
            $where .= " AND `$key` LIKE ?"; $params[] = "%$value%"; break;
        case 'section': case 'officer': case 'status':
            $where .= " AND `$key` = ?"; $params[] = $value; break;
        case 'date_from':
            $where .= " AND DATE(visits.visit_datetime) >= ?"; $params[] = $value; break;
        case 'date_to':
            $where .= " AND DATE(visits.visit_datetime) <= ?"; $params[] = $value; break;
    }
}

// In the SQL query, add phone and whatsapp
$sql = "SELECT visits.visit_id, visits.visit_datetime, visitors.name, visitors.nic,
               visitors.phone, visitors.whatsapp,
               visits.reason, sections.section_name, COALESCE(officers.name, 'Not Assigned') AS officer,
               visits.status
        FROM visits
        JOIN visitors ON visits.nic = visitors.nic
        JOIN sections ON visits.section_id = sections.id
        LEFT JOIN officers ON visits.officer_id = officers.id
        $where
        ORDER BY visits.visit_datetime DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Excel (unchanged)
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment;filename="visits_report_' . date('Y-m-d') . '.xls"');
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
    echo "<table border='1'>";
echo "<tr><th>Visit ID</th><th>Date Time</th><th>Name</th><th>NIC</th><th>Phone</th><th>Whatsapp</th><th>Reason</th><th>Section</th><th>Officer</th><th>Status</th></tr>";
foreach ($data as $row) {
    echo "<tr>
        <td>{$row['visit_id']}</td>
        <td>{$row['visit_datetime']}</td>
        <td>" . htmlspecialchars($row['name']) . "</td>
        <td>{$row['nic']}</td>
        <td>{$row['phone']}</td>
        <td>{$row['whatsapp']}</td>
        <td>" . htmlspecialchars($row['reason']) . "</td>
        <td>" . htmlspecialchars($row['section_name']) . "</td>
        <td>" . htmlspecialchars($row['officer']) . "</td>
        <td>" . ucfirst($row['status']) . "</td>
    </tr>";
}
    echo "</table>";
    exit;
}

// PDF – LANDSCAPE A4 + PERFECT FIT + SINHALA
if ($format === 'pdf') {
    require_once '../core/lib/fpdf/fpdf.php'; // ← tFPDF

    class PDF extends tFPDF {
        var $filter_text;
        function SetFilterText($text) { $this->filter_text = $text; }
        function Header() {
            $this->SetFont('Iskoola','B',18);
            $this->Cell(0,12,'ප්‍රවේශකයින් කළමනාකරණ වාර්තාව',0,1,'C');
            $this->Ln(5);
            $this->SetFont('Iskoola','',9);
            $this->Cell(0,8,'නිකුත් කළ දිනය: ' . date('Y-m-d H:i:s'),0,1,'C');
            if ($this->filter_text) {
                $this->Ln(3);
                $this->SetFont('Iskoola','',8);
                $this->MultiCell(0,6,$this->filter_text,0,'C');
            }
            $this->Ln(8);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Iskoola','',8);
            $this->Cell(0,10,'පිටුව ' . $this->PageNo() . ' / {nb}',0,0,'C');
        }
    }

    $pdf = new PDF('L'); // ← LANDSCAPE A4
    $pdf->AliasNbPages();
    $pdf->SetFilterText($filter_text);

    $fontPath = dirname(__DIR__) . '/core/lib/fpdf/font/';
$pdf->AddFont('Iskoola','','iskpota.ttf',true);
$pdf->AddFont('Iskoola','B','iskpotab.ttf',true);

    $pdf->AddPage();
    $pdf->SetFont('Iskoola','B',9);

    // PERFECT COLUMN WIDTHS FOR LANDSCAPE A4
    $w = [18, 35, 45, 25, 65, 35, 35, 20]; // Total = 278mm → fits perfectly
    $pdf->Cell($w[0],8,'Visit ID',1);
    $pdf->Cell($w[1],8,'දිනය/වේලාව',1);
    $pdf->Cell($w[2],8,'නම',1);
    $pdf->Cell($w[3],8,'හැඳුනුම්පත',1);
    $pdf->Cell($w[4],8,'හේතුව',1);
    $pdf->Cell($w[5],8,'අංශය',1);
    $pdf->Cell($w[6],8,'නිලධාරි',1);
    $pdf->Cell($w[7],8,'තත්ත්වය',1);
    $pdf->Ln();

    $pdf->SetFont('Iskoola','',8);
    foreach ($data as $row) {
        $pdf->Cell($w[0],7,$row['visit_id'],1);
        $pdf->Cell($w[1],7,substr($row['visit_datetime'],0,16),1);
        $pdf->Cell($w[2],7,$row['name'],1);
        $pdf->Cell($w[3],7,$row['nic'],1);
        $pdf->Cell($w[4],7,mb_substr($row['reason'],0,40,'UTF-8'),1);
        $pdf->Cell($w[5],7,$row['section_name'],1);
        $pdf->Cell($w[6],7,$row['officer'],1);
        $pdf->Cell($w[7],7,ucfirst($row['status']),1);
        $pdf->Ln();
    }

    $pdf->Output('D', 'visits_report_' . date('Y-m-d') . '.pdf');
    exit;
}
?>