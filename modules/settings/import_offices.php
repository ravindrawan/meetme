<?php
require '../../core/config.php';
if($_SESSION['user']['role']!='admin') die('Access denied');

$message = '';

if(isset($_POST['import'])){
    if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0){
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        if($handle !== FALSE){
            $row = 0;
            $success = 0;
            $errors = 0;
            $debug_log = [];
            $idIdx = 0; // Default Col 1
            $nameIdx = 1; // Default Col 2
            $levelIdx = 2; // Default Col 3
            
            while(($data = fgetcsv($handle, 1000, ",")) !== FALSE){
                $row++;
                if($row == 1) {
                     // specific header detection
                     foreach($data as $index => $cell) {
                         $cell = strtolower(trim($cell));
                         if(strpos($cell, 'id') !== false || $cell === 'no') $idIdx = $index;
                         if(strpos($cell, 'name') !== false) $nameIdx = $index;
                         if(strpos($cell, 'level') !== false || strpos($cell, 'grade') !== false) $levelIdx = $index;
                     }
                     $debug_log[] = "Row 1: Headers Detected. Name: Col " . ($nameIdx+1) . ", Level: Col " . ($levelIdx+1) . ", ID: Col " . ($idIdx+1);
                     continue; 
                } 
                
                $id = trim($data[$idIdx] ?? '');
                $name = trim($data[$nameIdx] ?? '');
                $level = trim($data[$levelIdx] ?? '');
                
                if($name && $level){
                    // Validate ID
                    if($id && !is_numeric($id)){
                         $errors++;
                         $debug_log[] = "Row $row: Skipped (Invalid ID: '$id')";
                         continue;
                    }

                    // Validate Level
                    $allowedLevels = ['Level 1', 'Level 2', 'Level 3', 'Level 4', 'Level 5'];
                    // Clean level string
                    $validLevel = null;
                    foreach($allowedLevels as $al) {
                        if(strcasecmp($level, $al) == 0) {
                            $validLevel = $al;
                            break;
                        }
                        $num = str_replace('Level ', '', $al);
                        if($level == $num) {
                            $validLevel = $al;
                            break;
                        }
                    }

                    if(!$validLevel){
                        $errors++;
                        $debug_log[] = "Row $row: Skipped (Invalid Level: '$level')";
                        continue;
                    }
                    $level = $validLevel;
                    
                    // Check duplicate ID
                    if($id){
                        $stmt = $pdo->prepare("SELECT id FROM provincial_offices WHERE id = ?");
                        $stmt->execute([$id]);
                        if($stmt->fetch()){
                            $errors++;
                            $debug_log[] = "Row $row: Skipped (Duplicate ID: '$id')";
                            continue;
                        }
                    }

                    // Check duplicate Name
                    $stmt = $pdo->prepare("SELECT id FROM provincial_offices WHERE office_name = ?");
                    $stmt->execute([$name]);
                    if($stmt->fetch()){
                        $errors++;
                        $debug_log[] = "Row $row: Skipped (Duplicate Name: '$name')";
                        continue;
                    }
                    
                    try {
                        if($id){
                            $stmt = $pdo->prepare("INSERT INTO provincial_offices (id, office_name, office_level) VALUES (?, ?, ?)");
                            $stmt->execute([$id, $name, $level]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO provincial_offices (office_name, office_level) VALUES (?, ?)");
                            $stmt->execute([$name, $level]);
                        }
                        $success++;
                        $debug_log[] = "Row $row: Success (ID: " . ($id?:'Auto') . ", '$name', '$level')";
                    } catch(PDOException $e){
                        $errors++;
                        $debug_log[] = "Row $row: Database Error (" . $e->getMessage() . ")";
                    }
                } else {
                    $errors++;
                    $debug_log[] = "Row $row: Skipped (Empty Name or Level)";
                }
            }
            fclose($handle);
            $message = "<div class='alert alert-info'>Import Processed.<br>Success: $success<br>Skipped/Failed: $errors</div>";
            if(!empty($debug_log)){
                $message .= "<div class='card mt-3'><div class='card-header'>Detailed Log</div><div class='card-body' style='max-height: 300px; overflow-y: auto;'><pre>" . implode("\n", $debug_log) . "</pre></div></div>";
            }
        } else {
            $message = '<div class="alert alert-danger">Could not open file.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please upload a valid CSV file.</div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Import Offices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<?php include '../../includes/navbar.php'; ?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Import Offices from CSV</h2>
        <a href="offices.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Offices</a>
    </div>

    <?= $message ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Please upload a CSV file with the following columns (order is flexible if headers are used, otherwise assumes <strong>ID, Name, Level</strong>):
                <ol>
                    <li><strong>ID</strong> (Optional, will auto-increment if empty)</li>
                    <li><strong>Office Name</strong></li>
                    <li><strong>Level</strong> (Level 1 - 5)</li>
                </ol>
                <p class="mb-0">Note: The system tries to detect columns from the header row (row 1).</p>
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="csv_file" class="form-label">Select CSV File</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
                </div>
                <button type="submit" name="import" class="btn btn-primary"><i class="fas fa-file-import me-2"></i>Import</button>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
</body>
</html>
