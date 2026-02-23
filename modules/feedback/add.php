<?php
require '../../core/config.php';
if (!isset($_SESSION['user'])) header('Location: index.php');

$message = '';
$visit = null;

if (isset($_GET['search_visit_id'])) {
    $visit_id = str_pad($_GET['search_visit_id'], 8, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("SELECT v.*, vis.name, vis.nic FROM visits v JOIN visitors vis ON v.nic = vis.nic WHERE v.visit_id = ?");
    $stmt->execute([$visit_id]);
    $visit = $stmt->fetch();
    if (!$visit) {
        $message = '<div class="alert alert-danger">Visit ID not found (Searched: ' . htmlspecialchars($visit_id) . ').</div>';
    }
}

if (isset($_POST['submit_feedback'])) {
    $visit_id = $_POST['visit_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    // Check if feedback already exists
    $stmt = $pdo->prepare("SELECT id FROM visit_feedback WHERE visit_id = ?");
    $stmt->execute([$visit_id]);
    if ($stmt->fetch()) {
        $message = '<div class="alert alert-warning">Feedback for this visit already exists.</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO visit_feedback (visit_id, rating, comment) VALUES (?, ?, ?)");
        if ($stmt->execute([$visit_id, $rating, $comment])) {
            $message = '<div class="alert alert-success">Feedback submitted successfully!</div>';
            echo "<script>setTimeout(() => window.location.href = 'add.php', 2000);</script>";
        } else {
            $message = '<div class="alert alert-danger">Error submitting feedback.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .rating-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            font-size: 3rem;
            cursor: pointer;
        }
        .rating-icon {
            color: #ddd;
            transition: color 0.2s, transform 0.2s;
        }
        .rating-icon:hover {
            transform: scale(1.2);
        }
        .rating-icon.selected {
            color: #ffc107;
            transform: scale(1.1);
        }
        /* Specific colors for emotions if needed, or just gold for stars/smileys */
        .rating-icon[data-value="1"].selected { color: #dc3545; } /* Angry */
        .rating-icon[data-value="2"].selected { color: #fd7e14; }
        .rating-icon[data-value="3"].selected { color: #ffc107; }
        .rating-icon[data-value="4"].selected { color: #20c997; }
        .rating-icon[data-value="5"].selected { color: #198754; } /* Happy */
    </style>
</head>
<body>
<?php include '../../includes/navbar.php'; ?>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar-container d-none d-lg-block">
        <?php include '../../includes/sidebar.php'; ?>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Add Customer Feedback</h4>
                        </div>
                        <div class="card-body">
                            <?= $message ?>
                            
                            <!-- Search Form -->
                            <form method="GET" class="mb-4">
                                <div class="input-group">
                                    <input type="text" name="search_visit_id" class="form-control" placeholder="Enter Visit ID" value="<?= isset($_GET['search_visit_id']) ? htmlspecialchars($_GET['search_visit_id']) : '' ?>" required>
                                    <button class="btn btn-outline-primary" type="submit">Search</button>
                                </div>
                            </form>

                            <?php if ($visit): ?>
                                <div class="alert alert-info">
                                    <strong>Visitor:</strong> <?= htmlspecialchars($visit['name']) ?> (<?= htmlspecialchars($visit['nic']) ?>)<br>
                                    <strong>Visit Date:</strong> <?= htmlspecialchars($visit['visit_datetime']) ?>
                                </div>

                                <form method="POST">
                                    <input type="hidden" name="visit_id" value="<?= htmlspecialchars($visit['visit_id']) ?>">
                                    
                                    <div class="mb-4 text-center">
                                        <label class="form-label h5 mb-3">Service Satisfaction (1-5)</label>
                                        <div class="rating-container">
                                            <i class="fas fa-frown rating-icon" data-value="1" title="Very Dissatisfied"></i>
                                            <i class="fas fa-frown-open rating-icon" data-value="2" title="Dissatisfied"></i>
                                            <i class="fas fa-meh rating-icon" data-value="3" title="Neutral"></i>
                                            <i class="fas fa-smile rating-icon" data-value="4" title="Satisfied"></i>
                                            <i class="fas fa-laugh-beam rating-icon" data-value="5" title="Very Satisfied"></i>
                                        </div>
                                        <input type="hidden" name="rating" id="ratingValue" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="comment" class="form-label">Additional Comments</label>
                                        <textarea name="comment" id="comment" class="form-control" rows="4" placeholder="Enter text feedback from the receipt..."></textarea>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" name="submit_feedback" class="btn btn-success btn-lg">Submit Feedback</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        document.querySelectorAll('.rating-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                // Remove selected class from all
                document.querySelectorAll('.rating-icon').forEach(i => i.classList.remove('selected'));
                
                // Add selected class to clicked info
                this.classList.add('selected');
                
                // Set hidden input value
                document.getElementById('ratingValue').value = this.getAttribute('data-value');
            });
        });
    </script>
<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
