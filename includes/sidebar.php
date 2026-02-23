<div class="d-flex flex-column flex-shrink-0 p-3 bg-white shadow-sm" style="width: 250px; min-height: calc(100vh - 56px);">
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>modules/dashboard/index.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'dashboard') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        
        <li class="nav-item mt-3 text-uppercase small text-muted fw-bold ps-3">Visitors</li>
        
        <?php if(hasPrivilege('tile_register_visitor')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/visitors/register.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'visitors/register.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-user-plus me-2"></i> Register Visitor
            </a>
        </li>
        <?php endif; ?>

        <?php if(hasPrivilege('tile_active_visitors') || hasPrivilege('tile_view_visits')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/visits/list.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'visits/list.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-list-alt me-2"></i> View Visits
            </a>
        </li>
        <?php endif; ?>

        <?php if(hasPrivilege('tile_reports')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/reports/index.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'reports/index.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-chart-bar me-2"></i> Reports
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-item mt-3 text-uppercase small text-muted fw-bold ps-3">Feedback</li>

        <?php if(hasPrivilege('tile_add_feedback')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/feedback/add.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'feedback/add.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-smile-beam me-2"></i> Add Feedback
            </a>
        </li>
        <?php endif; ?>
        
        <?php if(hasPrivilege('tile_view_feedback')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/feedback/view.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'feedback/view.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-comments me-2"></i> View Feedback
            </a>
        </li>
        <?php endif; ?>

        <?php if(hasPrivilege('tile_section_feedback')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/feedback/section.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'feedback/section.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-chart-pie me-2"></i> Section Feedback
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item mt-3 text-uppercase small text-muted fw-bold ps-3">Administration</li>
        
        <?php if(hasPrivilege('tile_create_user')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/auth/create_user.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'auth/create_user.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-user-shield me-2"></i> Create User
            </a>
        </li>
        <?php endif; ?>

        <?php if(hasPrivilege('tile_manage_offices')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/settings/offices.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'settings/offices.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-building me-2"></i> Manage Offices
            </a>
        </li>
        <?php endif; ?>
        
        <?php if(hasPrivilege('tile_office_hierarchy')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/settings/office_hierarchy.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'settings/office_hierarchy.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-sitemap me-2"></i> Office Hierarchy
            </a>
        </li>
        <?php endif; ?>
        
        <?php if(hasPrivilege('tile_settings')): ?>
        <li class="nav-item mt-3 text-uppercase small text-muted fw-bold ps-3">System</li>
        
        <li>
            <a href="<?= BASE_URL ?>modules/settings/index.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'settings/index.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-cogs me-2"></i> General Settings
            </a>
        </li>

        <?php if(hasPrivilege('tile_manage_reasons')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/settings/reasons.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'settings/reasons.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-list-ul me-2"></i> Visit Reasons
            </a>
        </li>
        <?php endif; ?>

        <?php if(hasPrivilege('tile_manage_privileges')): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/settings/privileges.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'settings/privileges.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-user-lock me-2"></i> Privileges
            </a>
        </li>
        <?php endif; ?>

        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
        <li>
            <a href="<?= BASE_URL ?>modules/settings/backup.php" class="nav-link link-dark <?= (strpos($_SERVER['PHP_SELF'], 'settings/backup.php') !== false) ? 'active text-white' : '' ?>">
                <i class="fas fa-database me-2"></i> Backup
            </a>
        </li>
        <?php endif; ?>
        <?php endif; ?>
    </ul>
    <hr>
    <?php
    // Determine user level label
    $levelLabel = "";
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] == 'admin') {
        $levelLabel = "Super Admin";
    } elseif (isset($_SESSION['user']['office_id'])) {
         // Fetch level from DB if not in session, or just show role
         // For now, let's show the Role nicely
         $levelLabel = ucfirst(str_replace('_', ' ', $_SESSION['user']['role']));
    }
    ?>
    <div class="text-muted small">
        Logged in as: <strong><?= $levelLabel ?></strong>
    </div>
</div>
