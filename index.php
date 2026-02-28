<?php
require 'core/config.php';
// Get settings
$stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $stmt->fetch() ?: ['organization_name' => 'VMS', 'organization_logo' => null];
if(isset($_SESSION['user'])) header('Location: modules/dashboard/index.php');

// Language Logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'public_view' => 'Public Visitor View',
        'public_desc' => 'Check your visit history and status by entering your ID or Visitor ID.',
        'check_history' => 'Check Visit History',
        'staff_login' => 'Staff Login',
        'username' => 'Username',
        'password' => 'Password',
        'login' => 'Login',
        'error_login' => 'Wrong username or password',
        'btn_en' => 'English',
        'btn_si' => 'Sinhala',
        'btn_ta' => 'Tamil'
    ],
    'si' => [
        'public_view' => 'මහජන අමුත්තන්ගේ ද්වාරය',
        'public_desc' => 'ඔබගේ ජාතික හැඳුනුම්පත් අංකය හෝ අමුත්තාගේ අංකය ඇතුළත් කර ඔබගේ පැමිණීමේ ඉතිහාසය සහ තත්ත්වය පරීක්ෂා කරන්න.',
        'check_history' => 'පැමිණීමේ ඉතිහාසය',
        'staff_login' => 'කාර්ය මණ්ඩල පිවිසුම',
        'username' => 'පරිශීලක නාමය',
        'password' => 'මුරපදය',
        'login' => 'ඇතුල් වන්න',
        'error_login' => 'පරිශීලක නාමය හෝ මුරපදය වැරදිය',
        'btn_en' => 'ඉංග්‍රීසි',
        'btn_si' => 'සිංහල',
        'btn_ta' => 'දෙමළ'
    ],
    'ta' => [
        'public_view' => 'பொதுப் பார்வையாளர் பார்வை',
        'public_desc' => 'உங்கள் அடையாள அட்டை எண் அல்லது பார்வையாளர் எண்ணை உள்ளிட்டு உங்கள் வருகை வரலாறு மற்றும் நிலையை சரிபார்க்கவும்.',
        'check_history' => 'வருகை வரலாறு',
        'staff_login' => 'ஊழியர் உள்நுழைவு',
        'username' => 'பயனர் பெயர்',
        'password' => 'கடவுச்சொல்',
        'login' => 'உள்நுழைய',
        'error_login' => 'தவறான பயனர் பெயர் அல்லது கடவுச்சொல்',
        'btn_en' => 'ஆங்கிலம்',
        'btn_si' => 'சிங்களம்',
        'btn_ta' => 'தமிழ்'
    ]
];

$t = $translations[$lang] ?? $translations['en'];

$user_error = '';

if($_POST){
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if($user && password_verify($password, $user['password'])){
        $_SESSION['user'] = $user;
        
        // Load Privileges
        $stmtPriv = $pdo->prepare("SELECT privilege_key FROM role_privileges WHERE role_key = ?");
        $stmtPriv->execute([$user['role']]);
        $_SESSION['user_privileges'] = $stmtPriv->fetchAll(PDO::FETCH_COLUMN);
        
        header('Location: modules/dashboard/index.php');
        exit;
    } else {
        $user_error = $t['error_login'];
    }
}
// Auto create admin
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin') ON DUPLICATE KEY UPDATE password = ?")
    ->execute([$hash, $hash]);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMS | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Noto+Sans+Sinhala:wght@400;700&family=Noto+Sans+Tamil:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { font-family: 'Poppins', 'Noto Sans Sinhala', 'Noto Sans Tamil', sans-serif; }
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background: linear-gradient(rgba(45, 0, 85, 0.9), rgba(120, 20, 180, 0.85)),
                        url('assets/img/bgimg.jpg') center/cover no-repeat fixed;
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

        .main-container {
            display: flex;
            width: 90%;
            max-width: 900px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            transition: all 0.3s ease;
        }

        .section-public {
            background: rgba(255, 255, 255, 0.05);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .section-staff {
            background: rgba(255, 255, 255, 0.15);
        }

        .section-title {
            color: #fff;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .section-icon {
            font-size: 4rem;
            color: #d500f9;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(213, 0, 249, 0.5);
        }

        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 15px;
        }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.7); }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: #d500f9;
            box-shadow: 0 0 15px rgba(213, 0, 249, 0.4);
            color: white;
        }

        .btn-action {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-public {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .btn-public:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
        }

        .btn-login {
            background: linear-gradient(45deg, #6200ea, #c51162);
            border: none;
            color: white;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(98, 0, 234, 0.4);
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #d500f9;
            border-radius: 12px 0 0 12px;
        }
        
        @media (max-width: 768px) {
            .main-container { flex-direction: column; }
            .section { padding: 30px 20px; }
            .section-public { border-right: none; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
            .top-bar { padding: 0 20px; height: auto; padding-top: 10px; padding-bottom: 10px; flex-direction: column; gap:10px; }
            .top-bar-right { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="top-bar-left">
            <i class="fa fa-handshake fa-fw fa-3x me-3"></i>
            <img src="assets/img/logo.png" alt="Logo" style="height: 60px; width: auto;">
        </div>
        <div class="top-bar-right">
            <a href="?lang=si" class="lang-btn <?= $lang == 'si' ? 'active' : '' ?>">Sinhala</a>
            <a href="?lang=ta" class="lang-btn <?= $lang == 'ta' ? 'active' : '' ?>">Tamil</a>
            <a href="?lang=en" class="lang-btn <?= $lang == 'en' ? 'active' : '' ?>">English</a>
        </div>
    </div>

    <div class="main-container">
        <!-- Public Section -->
        <div class="section section-public">
            <div class="section-icon">
                <i class="fas fa-users"></i>
            </div>
            <h2 class="section-title"><?= $t['public_view'] ?></h2>
            <p class="text-white mb-4" style="opacity: 0.8;"><?= $t['public_desc'] ?></p>
            <a href="modules/visitors/search.php" class="btn btn-action btn-public">
                <i class="fas fa-search me-2"></i> <?= $t['check_history'] ?>
            </a>
        </div>

        <!-- Staff Section -->
        <div class="section section-staff">
            <div class="text-center mb-4">
                <?php if (!empty($settings['organization_logo'])): ?>
                    <img src="<?= htmlspecialchars($settings['organization_logo']) ?>" class="mb-3" alt="Logo" style="max-height: 80px; max-width: 100%;">
                <?php else: ?>
                    <i class="fas fa-user-shield section-icon" style="font-size: 3rem;"></i>
                <?php endif; ?>
                <h3 class="section-title mb-0"><?= htmlspecialchars($settings['organization_name']) ?></h3>
                <small style="color: #e1bee7;"><?= $t['staff_login'] ?></small>
            </div>

            <?php if($user_error): ?>
                <div class="alert alert-danger w-100 text-center py-2 mb-3" style="font-size: 0.9rem;"><?= $user_error ?></div>
            <?php endif; ?>

            <form method="post" class="w-100">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input name="username" type="text" class="form-control mb-0" placeholder="<?= $t['username'] ?>" required>
                </div>
                <div class="input-group mb-4">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input name="password" type="password" class="form-control mb-0" placeholder="<?= $t['password'] ?>" required>
                </div>
                <button type="submit" class="btn btn-action btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> <?= $t['login'] ?>
                </button>
            </form>
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
    </style>
    <div class="footer-bar">
        Powered by Digital Division | Chief Secretariat - North Western Province
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
