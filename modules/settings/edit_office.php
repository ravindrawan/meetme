<?php
require '../../core/config.php';
if($_SESSION['user']['role']!='admin') die('Access denied');

$id = $_GET['id'] ?? null;
if(!$id){
    header("Location: offices.php");
    exit;
}

$message = '';

// Handle Update
if(isset($_POST['update'])){
    $name = trim($_POST['name']);
    $level = $_POST['level'];
    
    if($name && $level){
        try {
            $stmt = $pdo->prepare("UPDATE provincial_offices SET office_name = ?, office_level = ? WHERE id = ?");
            $stmt->execute([$name, $level, $id]);
            $message = '<div class="alert alert-success">Office updated successfully! <a href="offices.php">Back to List</a></div>';
        } catch(PDOException $e) {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please fill in all fields.</div>';
    }
}

// Fetch Office
$stmt = $pdo->prepare("SELECT * FROM provincial_offices WHERE id = ?");
$stmt->execute([$id]);
$office = $stmt->fetch();

if(!$office){
    die("Office not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<?php include '../../includes/navbar.php'; ?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Office</h2>
        <a href="offices.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Offices</a>
    </div>

    <?= $message ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Office Name</label>
                    <input name="name" class="form-control" value="<?= htmlspecialchars($office['office_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Office Level</label>
                    <select name="level" class="form-select" required>
                        <option value="Level 1" <?= $office['office_level'] == 'Level 1' ? 'selected' : '' ?>>Level 1</option>
                        <option value="Level 2" <?= $office['office_level'] == 'Level 2' ? 'selected' : '' ?>>Level 2</option>
                        <option value="Level 3" <?= $office['office_level'] == 'Level 3' ? 'selected' : '' ?>>Level 3</option>
                        <option value="Level 4" <?= $office['office_level'] == 'Level 4' ? 'selected' : '' ?>>Level 4</option>
                        <option value="Level 5" <?= $office['office_level'] == 'Level 5' ? 'selected' : '' ?>>Level 5</option>
                    </select>
                </div>
                <button type="submit" name="update" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Office</button>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
</body>
</html>
