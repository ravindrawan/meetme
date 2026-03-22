<?php 
require '../../core/config.php';

// Access Control - Must be logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit;
}

$user = $_SESSION['user'];

if (isset($_POST['change_pw'])) {
    $current_pw = $_POST['current_password'];
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];
    
    // Fetch the user's current hashed password from database to verify
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $db_hash = $stmt->fetchColumn();
    
    if (!password_verify($current_pw, $db_hash)) {
        $error = "Incorrect current password.";
    } elseif ($new_pw !== $confirm_pw) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pw) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Hash and update the new password
        $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user['id']]);
        
        $success = "Password changed successfully! You can now log out and use your new password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                <h2>Change My Password</h2>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            
                            <?php if(isset($error)): ?>
                                <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
                            <?php elseif(isset($success)): ?>
                                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
                            <?php endif; ?>
                            
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                    <div class="form-text">Must be at least 6 characters.</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                                
                                <button type="submit" name="change_pw" class="btn btn-primary"><i class="fas fa-save me-2"></i> Update Password</button>
                            </form>
                            
                        </div>
                    </div>
                </div>
                <div class="col-md-6 d-none d-md-block">
                    <!-- Optional decorative column -->
                    <div class="p-4 bg-white border rounded shadow-sm h-100 d-flex flex-column justify-content-center align-items-center text-muted">
                        <i class="fas fa-shield-alt fa-4x mb-3 text-primary" style="opacity: 0.2"></i>
                        <h5 class="fw-bold">Keep Your Account Secure</h5>
                        <p class="text-center small px-4">Regularly updating your password helps prevent unauthorized access. Choose a strong password you haven't used elsewhere.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
