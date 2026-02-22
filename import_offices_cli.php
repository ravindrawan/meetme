<?php
require 'core/config.php';

$file = 'csv/office_list.csv';
if(!file_exists($file)){
    die("File not found: $file\n");
}

$handle = fopen($file, "r");
if($handle === FALSE){
    die("Could not open file.\n");
}

$row = 0;
$success = 0;
$errors = 0;
// Hardcoded indices based on verified CSV structure
$idIdx = 0; 
$nameIdx = 1; 
$levelIdx = 2; 

echo "Starting Import...\n";
echo "Using Hardcoded Indices: ID=0, Name=1, Level=2\n";

while(($data = fgetcsv($handle, 1000, ",")) !== FALSE){
    $row++;
    
    // Skip Header Row explicitly
    if($row == 1) {
         echo "Row 1: Skipped (Header)\n";
         continue; 
    } 
    
    $id = trim($data[$idIdx] ?? '');
    $name = trim($data[$nameIdx] ?? '');
    $level = trim($data[$levelIdx] ?? '');
    
    if($name && $level){
        // Validate ID
        if($id && !is_numeric($id)){
             $errors++;
             echo "Row $row: Skipped (Invalid ID: '$id')\n";
             continue;
        }

        // Validate Level
        $allowedLevels = ['Level 1', 'Level 2', 'Level 3', 'Level 4', 'Level 5'];
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
            echo "Row $row: Skipped (Invalid Level: '$level')\n";
            continue;
        }
        $level = $validLevel;
        
        // Check duplicate ID
        if($id){
            $stmt = $pdo->prepare("SELECT id FROM provincial_offices WHERE id = ?");
            $stmt->execute([$id]);
            if($stmt->fetch()){
                $errors++;
                echo "Row $row: Skipped (Duplicate ID: '$id')\n";
                continue;
            }
        }

        // Check duplicate Name
        $stmt = $pdo->prepare("SELECT id FROM provincial_offices WHERE office_name = ?");
        $stmt->execute([$name]);
        if($stmt->fetch()){
            $errors++;
            echo "Row $row: Skipped (Duplicate Name: '$name')\n";
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
            echo "Row $row: Success (ID: " . ($id?:'Auto') . ", '$name', '$level')\n";
        } catch(PDOException $e){
            $errors++;
            echo "Row $row: Database Error (" . $e->getMessage() . ")\n";
        }
    } else {
        $errors++;
        echo "Row $row: Skipped (Empty Name or Level)\n";
    }
}
fclose($handle);
echo "--------------------------------------------------\n";
echo "Import Completed.\n";
echo "Total Rows Processed: " . ($row-1) . "\n"; 
echo "Success: $success\n";
echo "Failed/Skipped: $errors\n";
?>
