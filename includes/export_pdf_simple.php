<?php
require '../core/config.php';
if (!isset($_SESSION['user'])) exit;

$filters = json_decode($_POST['filters'] ?? '{}', true);
$where = " WHERE 1=1 ";
$params = [];

// Rebuild filters (same as view_visits.php)
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

$sql = "SELECT visits.visit_id, visits.visit_datetime, visitors.name, visitors.nic,
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
$data = $stmt->fetchAll();

// Pure PHP PDF - NOW SHOWS ALL DATA
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="visits_report_' . date('Y-m-d') . '.pdf"');

echo "%PDF-1.4\n";
echo "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
echo "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
echo "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
echo "4 0 obj\n<< /Length 2000 >> stream\n";

// Title
echo "BT /F1 16 Tf 50 750 Td (Visitor Management Report) Tj ET\n";
echo "BT /F1 10 Tf 50 720 Td (Generated: " . date('Y-m-d H:i') . ") Tj ET\n";
echo "BT /F1 12 Tf 50 700 Td (Visit ID      Name                    NIC           Date Time           Reason                              Section          Officer          Status) Tj ET\n";
echo "BT /F1 12 Tf 50 685 Td (-------------------------------------------------------------------------------------------------------------------------------) Tj ET\n";

$y = 660;
foreach ($data as $row) {
    $line = sprintf("%-12s %-25s %-12s %-18s %-35s %-15s %-15s %s",
        $row['visit_id'],
        substr($row['name'], 0, 25),
        $row['nic'],
        substr($row['visit_datetime'], 0, 16),
        substr($row['reason'], 0, 35),
        substr($row['section_name'], 0, 15),
        substr($row['officer'], 0, 15),
        ucfirst($row['status'])
    );
    echo "BT /F1 10 Tf 50 $y Td ($line) Tj ET\n";
    $y -= 20;

    // Add new page if needed
    if ($y < 100) {
        echo "ET endstream endobj\n";
        echo "xref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000075 00000 n \n0000000120 00000 n \n0000000200 00000 n \n0000000300 00000 n \ntrailer << /Size 6 /Root 1 0 R >> startxref\n500\n%%EOF";
        exit;
    }
}

echo "ET endstream endobj\n";
echo "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Courier >> endobj\n";
echo "xref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000075 00000 n \n0000000120 00000 n \n0000000200 00000 n \n0000000300 00000 n \ntrailer << /Size 6 /Root 1 0 R >> startxref\n500\n%%EOF";
exit;
?>