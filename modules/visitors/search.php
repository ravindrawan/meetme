<?php
require '../../core/config.php';

// Get settings for logo/name
$stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $stmt->fetch() ?: ['organization_name' => 'VMS', 'organization_logo' => null];




function checkRateLimit($pdo, $ip, $action, $limit, $minutes) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE ip_address = ? AND action = ? AND created_at > NOW() - INTERVAL ? MINUTE");
    $stmt->execute([$ip, $action, $minutes]);
    return $stmt->fetchColumn() < $limit;
}

function clearRateLimit($pdo, $ip, $action) {
    $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE ip_address = ? AND action = ?");
    $stmt->execute([$ip, $action]);
}











// Language Logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'en';



















$translations = [
    'en' => [
        'page_title' => 'Public Visitor View',
        'staff_login' => 'Staff Login',
        'check_history' => 'Check Your Visit History',
        'search_placeholder' => 'Enter your ID Number (NIC) or Visitor ID',
        'search_btn' => 'Search',
        'error_not_found' => 'We couldn\'t find any records for',
        'error_empty' => 'Please enter an ID Number or Visitor ID.',
        'error_rate_limit' => 'Too many search attempts. Please try again later.',
        'visit_history' => 'Visit History',
        'no_records' => 'No visit records found.',
        'section' => 'Section',
        'officer' => 'Officer',
        'actions_taken' => 'Actions Taken',
        'feedback_title' => 'Visitor Feedback',
        'feedback_desc' => 'How was your experience?',
        'feedback_comment' => 'Additional Comments (Optional)',
        'submit_feedback' => 'Submit Feedback',
        'feedback_success' => 'Thank you for your feedback!',
        'btn_en' => 'English',
        'btn_si' => 'Sinhala',
        'btn_ta' => 'Tamil'
    ],
    'si' => [
        'page_title' => 'මහජන අමුත්තන්ගේ ද්වාරය',
        'staff_login' => 'කාර්ය මණ්ඩල පිවිසුම',
        'check_history' => 'ඔබගේ පැමිණීමේ ඉතිහාසය පරීක්ෂා කරන්න',
        'search_placeholder' => 'ඔබගේ ජාතික හැඳුනුම්පත් අංකය හෝ අමුත්තාගේ අංකය ඇතුළත් කරන්න',
        'search_btn' => 'සොයන්න',
        'error_not_found' => 'අපට කිසිදු වාර්තාවක් සොයාගත නොහැකි විය',
        'error_empty' => 'කරුණාකර හැඳුනුම්පත් අංකය හෝ අමුත්තාගේ අංකය ඇතුළත් කරන්න.',
        'error_rate_limit' => 'සෙවුම් උත්සාහයන් වැඩියි. කරුණාකර පසුව නැවත උත්සාහ කරන්න.',
        'visit_history' => 'පැමිණීමේ ඉතිහාසය',
        'no_records' => 'කිසිදු පැමිණීමේ වාර්තාවක් හමු නොවීය.',
        'section' => 'අංශය',
        'officer' => 'නිලධාරි',
        'actions_taken' => 'ගනු ලැබූ ක්‍රියාමාර්ග',
        'feedback_title' => 'අමුත්තන්ගේ ප්‍රතිපෝෂණය',
        'feedback_desc' => 'ඔබගේ අත්දැකීම කෙසේද?',
        'feedback_comment' => 'අමතර අදහස් (විකල්ප)',
        'submit_feedback' => 'ප්‍රතිපෝෂණය යවන්න',
        'feedback_success' => 'ඔබගේ ප්‍රතිපෝෂණයට ස්තූතියි!',
        'btn_en' => 'ඉංග්‍රීසි',
        'btn_si' => 'සිංහල',
        'btn_ta' => 'දෙමළ'
    ],
    'ta' => [
        'page_title' => 'பொதுப் பார்வையாளர் பார்வை',
        'staff_login' => 'ஊழியர் உள்நுழைவு',
        'check_history' => 'உங்கள் வருகை வரலாற்றைச் சரிபார்க்கவும்',
        'search_placeholder' => 'உங்கள் அடையாள அட்டை எண் அல்லது பார்வையாளர் எண்ணை உள்ளிடவும்',
        'search_btn' => 'தேடு',
        'error_not_found' => 'எங்களால் எந்த பதிவுகளையும் காண முடியவில்லை',
        'error_empty' => 'தயவுசெய்து அடையாள அட்டை எண் அல்லது பார்வையாளர் எண்ணை உள்ளிடவும்.',
        'error_rate_limit' => 'தேடல் முயற்சிகள் அதிகமாக உள்ளன. சிறிது நேரம் கழித்து மீண்டும் முயற்சிக்கவும்.',
        'visit_history' => 'வருகை வரலாறு',
        'no_records' => 'வருகை பதிவுகள் எதுவும் காணப்படவில்லை.',
        'section' => 'பிரிவு',
        'officer' => 'அதிகாரி',
        'actions_taken' => 'எடுக்கப்பட்ட நடவடிக்கைகள்',
        'feedback_title' => 'பார்வையாளர் கருத்து',
        'feedback_desc' => 'உங்கள் அனுபவம் எப்படி இருந்தது?',
        'feedback_comment' => 'கூடுதல் கருத்துக்கள் (விருப்பமாக)',
        'submit_feedback' => 'கருத்தை சமர்ப்பிக்க',
        'feedback_success' => 'உங்கள் கருத்துக்கு நன்றி!',
        'btn_en' => 'ஆங்கிலம்',
        'btn_si' => 'சிங்களம்',
        'btn_ta' => 'தமிழ்'
    ]
];

$t = $translations[$lang] ?? $translations['en'];

$visitor = null;
$visits = [];
$error = '';
$feedback_msg = '';
$search_nic = '';
$search_visit_id = '';

if (isset($_POST['submit_feedback'])) {
    $fb_visit_id = trim($_POST['fb_visit_id']);
    $fb_rating = (int)$_POST['rating'];
    $fb_comment = trim($_POST['comment']);

    // Check if feedback exists
    $stmt = $pdo->prepare("SELECT id FROM visit_feedback WHERE visit_id = ?");
    $stmt->execute([$fb_visit_id]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO visit_feedback (visit_id, rating, comment) VALUES (?, ?, ?)");
        $stmt->execute([$fb_visit_id, $fb_rating, $fb_comment]);
        $feedback_msg = $t['feedback_success'];
    }
}

if (isset($_POST['search']) || isset($_POST['submit_feedback'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $is_rate_limited = false;

    if (isset($_POST['search'])) {
        if (!checkRateLimit($pdo, $ip, 'search_attempt', 20, 10)) {
            $error = "<i class='fas fa-ban'></i> " . $t['error_rate_limit'];
            $is_rate_limited = true;
        }
    }

    if (!$is_rate_limited) {
        $search_nic = trim($_POST['search_nic'] ?? $_POST['original_nic'] ?? '');
        $search_visit_id = trim($_POST['search_visit_id'] ?? $_POST['original_visit_id'] ?? '');
        
        if (!empty($search_nic) && !empty($search_visit_id)) {
            // Try to find visitor by NIC
            $stmt = $pdo->prepare("SELECT * FROM visitors WHERE nic = ?");
            $stmt->execute([$search_nic]);
            $visitor = $stmt->fetch();

            if ($visitor) {
                // Find specific visit matching both NIC and Visit ID
                $stmt = $pdo->prepare("
                    SELECT v.*, s.section_name, COALESCE(o.name, 'Not Assigned') AS officer 
                    FROM visits v 
                    JOIN sections s ON v.section_id = s.id 
                    LEFT JOIN officers o ON v.officer_id = o.id 
                    WHERE v.nic = ? AND (v.visit_id = ? OR v.visit_id = ?)
                ");
                
                $padded_id = str_pad($search_visit_id, 8, '0', STR_PAD_LEFT);
                $stmt->execute([$search_nic, $search_visit_id, $padded_id]);
                $visits = $stmt->fetchAll();

                if (!empty($visits)) {
                    // Fetch actions for the matched visit
                    foreach ($visits as &$visit_ref) {
                        $stmt_actions = $pdo->prepare("SELECT a.*, u.username as user_name 
                                                     FROM actions a 
                                                     LEFT JOIN users u ON a.user_id = u.id 
                                                     WHERE a.visit_id = ? 
                                                     ORDER BY a.action_datetime DESC");
                        $stmt_actions->execute([$visit_ref['visit_id']]);
                        $visit_ref['actions'] = $stmt_actions->fetchAll();

                        $stmt_fb = $pdo->prepare("SELECT * FROM visit_feedback WHERE visit_id = ?");
                        $stmt_fb->execute([$visit_ref['visit_id']]);
                        $visit_ref['feedback'] = $stmt_fb->fetch();
                    }
                    unset($visit_ref);
                } else {
                     $visitor = null; // Don't show visitor info if visit ID doesn't match
                     $error = "<i class='fas fa-exclamation-circle'></i> " . $t['error_not_found'] . " <strong>NIC: " . htmlspecialchars($search_nic) . ", Visit ID: " . htmlspecialchars($search_visit_id) . "</strong>. <br>";
                }

            } else {
                 $error = "<i class='fas fa-exclamation-circle'></i> " . $t['error_not_found'] . " <strong>" . htmlspecialchars($search_nic) . "</strong>. <br>";
            }
        } else {
            $error = $t['error_empty'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['page_title'] ?> - <?= htmlspecialchars($settings['organization_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Noto+Sans+Sinhala:wght@400;700&family=Noto+Sans+Tamil:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { font-family: 'Poppins', 'Noto Sans Sinhala', 'Noto Sans Tamil', sans-serif; }
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(rgba(45, 0, 85, 0.9), rgba(120, 20, 180, 0.85)),
                        url('../../assets/img/bgimg.jpg') center/cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 1.5s ease-in-out;
            position: relative;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .top-bar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            color: white;
        }

        .top-bar-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .lang-btn {
            color: white;
            border: 1px solid white;
            background: transparent;
            padding: 5px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .lang-btn:hover, .lang-btn.active {
            background: white;
            color: #6200ea;
            font-weight: 600;
        }

        .staff-link {
            color: white;
            text-decoration: none;
            border: 1px solid white;
            padding: 5px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            margin-right: 20px;
        }

        .staff-link:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .main-container {
            width: 100%;
            padding: 20px;
            margin-top: 100px; /* Space for top bar */
        }
        
        .search-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
            margin-bottom: 30px;
            color: white;
        }

        .search-title {
            color: white;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 12px;
            padding: 12px 16px;
        }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.7); }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: #d500f9;
            box-shadow: 0 0 15px rgba(213, 0, 249, 0.4);
            color: white;
        }

        .btn-search {
            background: linear-gradient(45deg, #6200ea, #c51162);
            border: none;
            color: white;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(98, 0, 234, 0.4);
            color: white;
        }

        .visitor-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .visitor-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .timeline-item {
            border-left: 3px solid #6200ea;
            padding-left: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            width: 12px;
            height: 12px;
            background: #6200ea;
            border-radius: 50%;
            position: absolute;
            left: -7.5px;
            top: 5px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        
        .rating-stars {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 10px;
        }
        .rating-stars input { display: none; }
        .rating-stars label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ccc;
            transition: color 0.2s;
        }
        .rating-stars input:checked ~ label,
        .rating-stars label:hover,
        .rating-stars label:hover ~ label { color: #f39c12; }
        .feedback-box {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid #dee2e6;
        }

        @media (max-width: 768px) {
            .top-bar { padding: 0 20px; height: auto; padding-top: 10px; padding-bottom: 10px; flex-direction: column; gap:10px; }
            .top-bar-right { width: 100%; justify-content: center; flex-wrap: wrap; }
            .staff-link { margin-right: 0; width: 100%; text-align: center; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="top-bar-left">
        <i class="fa fa-handshake fa-fw fa-3x me-3"></i>
        <img src="../../assets/img/logo.png" alt="Logo" style="height: 60px; width: auto;">
    </div>
    <div class="top-bar-right">
        <a href="../../index.php" class="staff-link"><i class="fas fa-sign-in-alt me-2"></i> <?= $t['staff_login'] ?></a>
        <a href="?lang=si" class="lang-btn <?= $lang == 'si' ? 'active' : '' ?>">Sinhala</a>
        <a href="?lang=ta" class="lang-btn <?= $lang == 'ta' ? 'active' : '' ?>">Tamil</a>
        <a href="?lang=en" class="lang-btn <?= $lang == 'en' ? 'active' : '' ?>">English</a>
    </div>
</div>

<div class="main-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="search-card text-center">
                    <h3 class="search-title"><?= $t['check_history'] ?></h3>
                    <form method="post" class="d-flex flex-column gap-3 justify-content-center align-items-center">
                        <div class="d-flex w-100 gap-2 justify-content-center flex-wrap">
                            <input type="text" name="search_nic" class="form-control form-control-lg" style="max-width:300px;" placeholder="NIC Number" value="<?= htmlspecialchars($search_nic) ?>" required>
                            <input type="text" name="search_visit_id" class="form-control form-control-lg" style="max-width:300px;" placeholder="Visit ID" value="<?= htmlspecialchars($search_visit_id) ?>" required>
                        </div>
                        <button type="submit" name="search" class="btn btn-search btn-lg px-5 mt-2"><i class="fas fa-search me-2"></i> <?= $t['search_btn'] ?></button>
                    </form>
                    <?php if($error): ?>
                        <div class="alert alert-danger mt-3 mb-0" style="background: rgba(220, 53, 69, 0.2); color: #ffcdd2; border: 1px solid rgba(220, 53, 69, 0.3);"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if($feedback_msg): ?>
                        <div class="alert alert-success mt-3 mb-0" style="background: rgba(40, 167, 69, 0.2); color: #c3e6cb; border: 1px solid rgba(40, 167, 69, 0.3);"><i class="fas fa-check-circle me-2"></i><?= $feedback_msg ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($visitor): ?>
        <div class="row justify-content-center fade-in">
            <div class="col-md-10">
                <div class="visitor-card">
                    <div class="visitor-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h4 class="mb-1"><?= htmlspecialchars($visitor['name']) ?></h4>
                            <p class="mb-0 text-muted"><i class="fas fa-id-card"></i> <?= htmlspecialchars($visitor['nic']) ?></p>
                        </div>
                        <?php if($visitor['whatsapp']): ?>
                            <div class="badge bg-success"><i class="fab fa-whatsapp"></i> <?= htmlspecialchars($visitor['whatsapp']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h5 class="mb-4 border-bottom pb-2"><?= $t['visit_history'] ?></h5>
                        
                        <?php if(empty($visits)): ?>
                            <p class="text-muted text-center py-3"><?= $t['no_records'] ?></p>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach($visits as $v): ?>
                                    <?php 
                                        $statusClass = 'bg-secondary text-white';
                                        if(strtolower($v['status']) == 'completed') $statusClass = 'status-completed';
                                        elseif(strtolower($v['status']) == 'pending') $statusClass = 'status-pending';
                                        elseif(strtolower($v['status']) == 'rejected' || strtolower($v['status']) == 'cancelled') $statusClass = 'status-rejected';
                                    ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong><i class="fas fa-calendar-alt"></i> <?= date('d M Y, h:i A', strtotime($v['visit_datetime'])) ?></strong>
                                            <span class="status-badge <?= $statusClass ?>"><?= ucfirst($v['status']) ?></span>
                                        </div>
                                        <p class="mb-1"><strong><?= $t['section'] ?>:</strong> <?= htmlspecialchars($v['section_name']) ?> <i class="fas fa-chevron-right mx-2 text-muted"></i> <strong><?= $t['officer'] ?>:</strong> <?= htmlspecialchars($v['officer']) ?></p>
                                        <p class="mb-1 text-muted"><em>"<?= htmlspecialchars($v['reason']) ?>"</em></p>
                                        <small class="text-muted">Ref: #<?= $v['visit_id'] ?></small>

                                        <?php if (!empty($v['actions'])): ?>
                                            <div class="mt-3 ps-3 border-start">
                                                <h6 class="text-muted" style="font-size: 0.9rem;"><?= $t['actions_taken'] ?>:</h6>
                                                <?php foreach($v['actions'] as $action): ?>
                                                    <div class="mb-2">
                                                        <small class="text-dark fw-bold"><?= date('d M Y, h:i A', strtotime($action['action_datetime'])) ?></small>
                                                        <div class="text-muted small"><?= nl2br(htmlspecialchars($action['action_text'])) ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($v['feedback']): ?>
                                            <div class="feedback-box border-start border-4 border-warning">
                                                <h6 class="text-muted mb-1" style="font-size: 0.9rem;"><i class="fas fa-star text-warning"></i> <?= $t['feedback_title'] ?></h6>
                                                <div>
                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?= $i <= $v['feedback']['rating'] ? 'text-warning' : 'text-muted' ?>" style="font-size: 0.8rem;"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <?php if($v['feedback']['comment']): ?>
                                                    <p class="mb-0 mt-2 text-dark small">"<?= nl2br(htmlspecialchars($v['feedback']['comment'])) ?>"</p>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="feedback-box mt-3 border-start border-4 border-primary">
                                                <h6 class="fw-bold text-primary mb-2"><?= $t['feedback_title'] ?></h6>
                                                <form method="post">
                                                    <input type="hidden" name="fb_visit_id" value="<?= htmlspecialchars($v['visit_id']) ?>">
                                                    <input type="hidden" name="original_nic" value="<?= htmlspecialchars($search_nic) ?>">
                                                    <input type="hidden" name="original_visit_id" value="<?= htmlspecialchars($search_visit_id) ?>">
                                                    
                                                    <p class="mb-1 text-muted small"><?= $t['feedback_desc'] ?></p>
                                                    
                                                    <div class="rating-stars mb-3">
                                                        <input type="radio" id="star5_<?= $v['visit_id'] ?>" name="rating" value="5" required/><label for="star5_<?= $v['visit_id'] ?>" title="5 stars"><i class="fas fa-star"></i></label>
                                                        <input type="radio" id="star4_<?= $v['visit_id'] ?>" name="rating" value="4"/><label for="star4_<?= $v['visit_id'] ?>" title="4 stars"><i class="fas fa-star"></i></label>
                                                        <input type="radio" id="star3_<?= $v['visit_id'] ?>" name="rating" value="3"/><label for="star3_<?= $v['visit_id'] ?>" title="3 stars"><i class="fas fa-star"></i></label>
                                                        <input type="radio" id="star2_<?= $v['visit_id'] ?>" name="rating" value="2"/><label for="star2_<?= $v['visit_id'] ?>" title="2 stars"><i class="fas fa-star"></i></label>
                                                        <input type="radio" id="star1_<?= $v['visit_id'] ?>" name="rating" value="1"/><label for="star1_<?= $v['visit_id'] ?>" title="1 star"><i class="fas fa-star"></i></label>
                                                    </div>

                                                    <textarea name="comment" class="form-control mb-2" rows="2" placeholder="<?= $t['feedback_comment'] ?>" style="background: white; border: 1px solid #ced4da; color: #495057;"></textarea>
                                                    
                                                    <button type="submit" name="submit_feedback" class="btn btn-sm btn-primary px-3 rounded-pill"><?= $t['submit_feedback'] ?></button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

    <style>
        .footer-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            color: white;
            font-size: 0.9rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        /* Ensure main container doesn't overlap footer */
        body { padding-bottom: 60px; } 
    </style>
    <div class="footer-bar">
        System Developed by Digital Division of Chief Secretary Office (NWP)
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
