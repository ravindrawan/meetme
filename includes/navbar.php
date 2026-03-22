<?php
if (!isset($settings)) {
    if (isset($pdo)) {
        $stmt_settings = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
        $settings = $stmt_settings->fetch() ?: ['organization_name' => 'VMS', 'organization_logo' => null];
    } else {
        $settings = ['organization_name' => 'VMS', 'organization_logo' => null];
    }
}

// Load Navbar Configuration
$navbarConfig = require __DIR__ . '/navbar_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch Office Name if not in session
$officeName = "VMS"; // Default
if (isset($_SESSION['user']['office_id'])) {
    if (!isset($_SESSION['user']['office_name'])) {
        require_once __DIR__ . '/../core/config.php';
        $stmt = $pdo->prepare("SELECT office_name FROM provincial_offices WHERE id = ?");
        $stmt->execute([$_SESSION['user']['office_id']]);
        $_SESSION['user']['office_name'] = $stmt->fetchColumn();
    }
    $officeName = $_SESSION['user']['office_name'] ?? "VMS";
}
?>
<nav class="navbar navbar-expand-lg <?= $navbarConfig['navbar_theme'] ?> <?= $navbarConfig['bg_class'] ?>" style="<?= $navbarConfig['custom_style'] ?? '' ?>">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>modules/dashboard/index.php">
            <?php if (!empty($settings['organization_logo'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($settings['organization_logo']) ?>" alt="Logo" class="me-2" style="height: 40px; border-radius: 4px; background: white; padding: 2px;">
            <?php else: ?>
                <i class="fas fa-building me-2" style="font-size: 1.5rem;"></i>
            <?php endif; ?>
            
            <div class="d-flex flex-column" style="<?= $navbarConfig['brand_color'] ? 'color:'.$navbarConfig['brand_color'] : '' ?>">
                <span style="font-size: 1.1rem; font-weight: 600; line-height: 1.2;">
                    <?= htmlspecialchars($settings['organization_name']) ?>
                </span>
                <?php if($navbarConfig['show_office_name']): ?>
                <span style="font-size: 0.8rem; opacity: 0.9; font-weight: 400;">
                    <?= htmlspecialchars($officeName) ?>
                </span>
                <?php endif; ?>
            </div>
        </a>
        
        <!-- Mobile Sidebar Toggle -->
        <?php if(isset($_SESSION['user'])): ?>
        <button class="navbar-toggler border-0 shadow-none px-2 me-2 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" style="order: -1;">
            <span class="navbar-toggler-icon"></span>
        </button>
        <?php endif; ?>
        
        <div class="d-flex align-items-center ms-auto">
             <ul class="navbar-nav flex-row">
                <?php if(isset($_SESSION['user'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-display="static" style="color: inherit;">
                            <span class="me-2 text-end d-none d-md-block" style="line-height:1.2;">
                                <small class="d-block fw-bold"><?= htmlspecialchars($_SESSION['user']['username']) ?></small>
                                <small class="d-block" style="font-size:0.75rem; opacity:0.8;"><?= ucfirst($_SESSION['user']['role']) ?></small>
                            </span>
                            <i class="fas fa-user-circle fa-2x"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end position-absolute">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if(isset($_SESSION['user'])): ?>
<!-- Offcanvas Sidebar for Mobile -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel" style="width: 250px;">
  <div class="offcanvas-header bg-light border-bottom">
    <h5 class="offcanvas-title fw-bold" id="mobileSidebarLabel">Menu</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0" style="overflow-x: hidden;">
    <?php include __DIR__ . '/sidebar.php'; ?>
  </div>
</div>
<?php endif; ?>
