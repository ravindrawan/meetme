<?php
require '../../core/config.php';

// Get settings for logo/name
$stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $stmt->fetch() ?: ['organization_name' => 'VMS', 'organization_logo' => null];

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
        'visit_history' => 'Visit History',
        'no_records' => 'No visit records found.',
        'section' => 'Section',
        'officer' => 'Officer',
        'actions_taken' => 'Actions Taken',
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
        'visit_history' => 'පැමිණීමේ ඉතිහාසය',
        'no_records' => 'කිසිදු පැමිණීමේ වාර්තාවක් හමු නොවීය.',
        'section' => 'අංශය',
        'officer' => 'නිලධාරි',
        'actions_taken' => 'ගනු ලැබූ ක්‍රියාමාර්ග',
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
        'visit_history' => 'வருகை வரலாறு',
        'no_records' => 'வருகை பதிவுகள் எதுவும் காணப்படவில்லை.',
        'section' => 'பிரிவு',
        'officer' => 'அதிகாரி',
        'actions_taken' => 'எடுக்கப்பட்ட நடவடிக்கைகள்',
        'btn_en' => 'ஆங்கிலம்',
        'btn_si' => 'சிங்களம்',
        'btn_ta' => 'தமிழ்'
    ]
];

$t = $translations[$lang] ?? $translations['en'];

$visitor = null;
$visits = [];
$error = '';
$search_query = '';

if (isset($_POST['search'])) {
    $search_query = trim($_POST['search_query']);
    
    if (!empty($search_query)) {
        // Search by NIC or Visitor ID
        // First try to find by NIC
        $stmt = $pdo->prepare("SELECT * FROM visitors WHERE nic = ?");
        $stmt->execute([$search_query]);
        $visitor = $stmt->fetch();

        if (!$visitor) {
            // Try finding by Visit ID in visits table
            $stmt = $pdo->prepare("SELECT nic FROM visits WHERE visit_id = ?");
            $stmt->execute([$search_query]);
            $found_nic = $stmt->fetchColumn();
            
            // If not found, try with padding (e.g. 434 -> 00000434)
            if (!$found_nic) {
                 $padded_id = str_pad($search_query, 8, '0', STR_PAD_LEFT);
                 $stmt = $pdo->prepare("SELECT nic FROM visits WHERE visit_id = ?");
                 $stmt->execute([$padded_id]);
                 $found_nic = $stmt->fetchColumn();
                 if($found_nic) $search_query = $padded_id; // Update query for later use
            }
            
            if ($found_nic) {
                 $stmt = $pdo->prepare("SELECT * FROM visitors WHERE nic = ?");
                 $stmt->execute([$found_nic]);
                 $visitor = $stmt->fetch();
                 $is_visit_search = true; // Flag to indicate search was by Visit ID
            }
        }

        if ($visitor) {
            // Fetch visits
            if (isset($is_visit_search) && $is_visit_search) {
                 // If searched by Visit ID, show ONLY that visit
                 $stmt = $pdo->prepare("SELECT v.*, s.section_name, COALESCE(o.name, 'Not Assigned') AS officer 
                           FROM visits v 
                           JOIN sections s ON v.section_id = s.id 
                           LEFT JOIN officers o ON v.officer_id = o.id 
                           WHERE v.visit_id = ?");
                $stmt->execute([$search_query]);
            } else {
                 // If searched by NIC, show all visits
                 $stmt = $pdo->prepare("SELECT v.*, s.section_name, COALESCE(o.name, 'Not Assigned') AS officer 
                           FROM visits v 
                           JOIN sections s ON v.section_id = s.id 
                           LEFT JOIN officers o ON v.officer_id = o.id 
                           WHERE v.nic = ? ORDER BY v.visit_datetime DESC");
                $stmt->execute([$visitor['nic']]);
            }
            $visits = $stmt->fetchAll();

            // Fetch actions for each visit
            foreach ($visits as &$visit_ref) {
                $stmt_actions = $pdo->prepare("SELECT a.*, u.username as user_name 
                                             FROM actions a 
                                             LEFT JOIN users u ON a.user_id = u.id 
                                             WHERE a.visit_id = ? 
                                             ORDER BY a.action_datetime DESC");
                $stmt_actions->execute([$visit_ref['visit_id']]);
                $visit_ref['actions'] = $stmt_actions->fetchAll();
            }
            unset($visit_ref); // Break reference

        } else {
             // User-friendly error message
            $error = "<i class='fas fa-exclamation-circle'></i> " . $t['error_not_found'] . " <strong>" . htmlspecialchars($search_query) . "</strong>. <br>" . $t['error_empty'];
        }
    } else {
        $error = $t['error_empty'];
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
                    <form method="post" class="d-flex gap-2 justify-content-center flex-wrap">
                        <input type="text" name="search_query" class="form-control form-control-lg w-75" placeholder="<?= $t['search_placeholder'] ?>" value="<?= htmlspecialchars($search_query) ?>" required>
                        <button type="submit" name="search" class="btn btn-search btn-lg px-4"><i class="fas fa-search me-2"></i> <?= $t['search_btn'] ?></button>
                    </form>
                    <?php if($error): ?>
                        <div class="alert alert-danger mt-3 mb-0" style="background: rgba(220, 53, 69, 0.2); color: #ffcdd2; border: 1px solid rgba(220, 53, 69, 0.3);"><?= $error ?></div>
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
