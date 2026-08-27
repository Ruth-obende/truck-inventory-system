<?php
/**
 * =============================================================================
 * Moal General Suppliers - Admin Header & Navigation
 * =============================================================================
 */

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$currentAdminPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = get_flash_message();

// Get Pending Inquiries Count for Sidebar Notification Badge
$db = getDB();
$pendingCount = 0;
try {
    $stmtPending = $db->query('SELECT COUNT(*) FROM inquiries WHERE status = "Pending"');
    $pendingCount = (int)$stmtPending->fetchColumn();
} catch (Exception $e) {
    // Graceful fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize_output($pageTitle) . ' | Admin Portal' : 'Admin Portal | ' . APP_NAME; ?></title>
    
    <!-- Base App Styles & Admin Layout Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body class="admin-body">

<!-- Admin Sidebar -->
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <a href="<?php echo ADMIN_URL; ?>dashboard.php">
            <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.jpg" alt="<?php echo APP_NAME; ?>">
        </a>
        <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-top: 6px;">
            Dealership Admin Panel
        </div>
    </div>

    <ul class="admin-nav">
        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>dashboard.php" class="admin-nav-link <?php echo ($currentAdminPage === 'dashboard') ? 'active' : ''; ?>">
                <span>📊</span> Dashboard
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>trucks.php" class="admin-nav-link <?php echo ($currentAdminPage === 'trucks' || $currentAdminPage === 'truck-form') ? 'active' : ''; ?>">
                <span>🚛</span> Truck Inventory
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>truck-form.php" class="admin-nav-link">
                <span>➕</span> Add New Truck
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>inquiries.php" class="admin-nav-link <?php echo ($currentAdminPage === 'inquiries' || $currentAdminPage === 'inquiry-details') ? 'active' : ''; ?>">
                <span>📬</span> Inquiries
                <?php if ($pendingCount > 0): ?>
                    <span class="admin-nav-badge"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="admin-nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
            <a href="<?php echo BASE_URL; ?>" target="_blank" class="admin-nav-link">
                <span>🌐</span> View Public Site &nearr;
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>logout.php" class="admin-nav-link" style="color: #f87171;">
                <span>🚪</span> Log Out
            </a>
        </li>
    </ul>

    <div class="admin-sidebar-footer">
        <div style="font-weight: 700; color: #fff;"><?php echo sanitize_output($_SESSION['admin_name'] ?? 'Administrator'); ?></div>
        <div style="color: #94a3b8; font-size: 0.75rem;">Role: <?php echo sanitize_output($_SESSION['admin_role'] ?? 'Admin'); ?></div>
    </div>
</aside>

<!-- Admin Main Content -->
<div class="admin-main">
    
    <!-- Topbar -->
    <header class="admin-topbar">
        <div class="admin-topbar-title">
            <?php echo isset($pageTitle) ? sanitize_output($pageTitle) : 'Administration'; ?>
        </div>

        <div class="admin-user-pill">
            <div class="admin-avatar">
                <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
            </div>
            <span><?php echo sanitize_output($_SESSION['admin_username'] ?? 'Admin'); ?></span>
        </div>
    </header>

    <div class="admin-container">
        
        <?php if ($flash): ?>
            <div style="background: <?php echo $flash['type'] === 'success' ? '#dcfce7' : '#fee2e2'; ?>; border: 1px solid <?php echo $flash['type'] === 'success' ? '#86efac' : '#fca5a5'; ?>; color: <?php echo $flash['type'] === 'success' ? '#166534' : '#991b1b'; ?>; padding: 12px 16px; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; font-size: 0.92rem;">
                <?php echo sanitize_output($flash['message']); ?>
            </div>
        <?php endif; ?>
