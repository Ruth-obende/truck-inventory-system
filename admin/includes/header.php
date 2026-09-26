<?php
/**
 * =============================================================================
 * Moal General Suppliers - Professional Staff Portal Header & Navigation
 * =============================================================================
 */

require_once dirname(__DIR__) . '/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$currentAdminPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = get_flash_message();

// Get Pending Notifications (Inquiries & Requests)
$db = getDB();
$pendingInquiriesCount = 0;
$unreadNotifications = [];

try {
    $stmtInq = $db->query('SELECT id, inquiry_code, customer_name, created_at FROM inquiries WHERE status = "Pending" ORDER BY id DESC LIMIT 5');
    $pendingInquiriesList = $stmtInq->fetchAll();
    $pendingInquiriesCount = count($pendingInquiriesList);

    foreach ($pendingInquiriesList as $pi) {
        $unreadNotifications[] = [
            'title' => 'New Inquiry: ' . sanitize_output($pi['customer_name']),
            'sub'   => sanitize_output($pi['inquiry_code'] ?? 'Inquiry #' . $pi['id']),
            'url'   => ADMIN_URL . 'inquiry-details.php?id=' . (int)$pi['id']
        ];
    }
} catch (Exception $e) {}

// Admin Display Identity (strictly Admin or Staff)
$adminDisplayName = 'Admin';
if (!empty($_SESSION['admin_username']) && $_SESSION['admin_username'] === 'staff') {
    $adminDisplayName = 'Staff';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize_output($pageTitle) . ' | Staff Portal' : 'Overview Dashboard | ' . APP_NAME; ?></title>
    
    <!-- Base & Admin Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>assets/css/admin.css?v=<?php echo time(); ?>">

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">

<!-- Sidebar Navigation -->
<aside class="admin-sidebar" id="adminSidebar">
    
    <!-- Sidebar Header: Moal Official Dealership Branding -->
    <div class="admin-sidebar-header">
        <a href="<?php echo ADMIN_URL; ?>dashboard.php" class="admin-sidebar-brand" title="<?php echo APP_NAME; ?> — Staff Operations">
            <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.png" alt="<?php echo APP_NAME; ?>" class="admin-sidebar-logo">
        </a>
    </div>

    <!-- Green Admin Online Status Bar -->
    <div class="sidebar-status-banner">
        <span class="status-indicator-dot"></span>
        <span class="status-indicator-text"><?php echo $adminDisplayName; ?> &bull; Online</span>
    </div>

    <!-- Navigation Menu Items (Standard Order) -->
    <ul class="admin-nav">
        
        <!-- 1. Overview (Pure Dashboard) -->
        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>dashboard.php" class="admin-nav-link <?php echo ($currentAdminPage === 'dashboard' || $currentAdminPage === 'index') ? 'active' : ''; ?>" data-tooltip="Overview">
                <span class="admin-nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></span>
                <span class="admin-nav-text">Overview</span>
            </a>
        </li>

        <!-- 2. Client Information -->
        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>customers.php" class="admin-nav-link <?php echo ($currentAdminPage === 'customers' || $currentAdminPage === 'customer-details') ? 'active' : ''; ?>" data-tooltip="Client Information">
                <span class="admin-nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
                <span class="admin-nav-text">Client Information</span>
            </a>
        </li>

        <!-- 3. Available Trucks -->
        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>trucks.php" class="admin-nav-link <?php echo ($currentAdminPage === 'trucks' || $currentAdminPage === 'truck-form') ? 'active' : ''; ?>" data-tooltip="Available Trucks">
                <span class="admin-nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
                <span class="admin-nav-text">Available Trucks</span>
            </a>
        </li>

        <!-- 4. Reports (Print & Downloadable) -->
        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>reports.php" class="admin-nav-link <?php echo ($currentAdminPage === 'reports') ? 'active' : ''; ?>" data-tooltip="Reports">
                <span class="admin-nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></span>
                <span class="admin-nav-text">Reports</span>
            </a>
        </li>

        <!-- 5. All Activities (with Filters) -->
        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>activities.php" class="admin-nav-link <?php echo ($currentAdminPage === 'activities' || $currentAdminPage === 'inquiries' || $currentAdminPage === 'inquiry-details' || $currentAdminPage === 'requests' || $currentAdminPage === 'request-details') ? 'active' : ''; ?>" data-tooltip="All Activities">
                <span class="admin-nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></span>
                <span class="admin-nav-text">All Activities</span>
                <?php if ($pendingInquiriesCount > 0): ?>
                    <span class="admin-nav-badge"><?php echo $pendingInquiriesCount; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- 6. Settings -->
        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>settings.php" class="admin-nav-link <?php echo ($currentAdminPage === 'settings' || $currentAdminPage === 'profile') ? 'active' : ''; ?>" data-tooltip="Settings">
                <span class="admin-nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></span>
                <span class="admin-nav-text">Settings</span>
            </a>
        </li>

    </ul>

    <!-- Admin Profile Section in Sidebar (Near Bottom) -->
    <a href="<?php echo ADMIN_URL; ?>settings.php" class="admin-sidebar-profile" data-tooltip="<?php echo $adminDisplayName; ?> (Online)">
        <div class="admin-avatar">
            <?php echo strtoupper(substr($adminDisplayName, 0, 1)); ?>
        </div>
        <div class="admin-profile-info">
            <div class="admin-profile-name"><?php echo $adminDisplayName; ?></div>
            <div class="admin-profile-status">
                <span class="online-dot"></span> Online
            </div>
        </div>
    </a>

    <!-- Logout at Bottom -->
    <ul class="admin-nav" style="flex: 0; padding-top: 0;">
        <li class="admin-nav-item">
            <a href="<?php echo ADMIN_URL; ?>logout.php" class="admin-nav-link" data-tooltip="Logout" style="color: #F87171;">
                <span class="admin-nav-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></span>
                <span class="admin-nav-text">Logout</span>
            </a>
        </li>
    </ul>

    <!-- Single Attached Sidebar Collapse Toggle Control (< / >) -->
    <div class="admin-sidebar-collapse-row">
        <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse / Expand Sidebar">
            <span id="collapseIcon">&lt;</span>
            <span class="collapse-text">Collapse</span>
        </button>
    </div>

</aside>

<!-- Main Workspace Area -->
<div class="admin-main">
    
    <!-- Topbar (Clean search & quick actions) -->
    <header class="admin-topbar">
        <!-- Quick Search -->
        <div class="admin-topbar-search">
            <span class="admin-topbar-search-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></span>
            <input type="text" placeholder="Search trucks or client inquiries..." onkeydown="if(event.key==='Enter'){ window.location.href='<?php echo ADMIN_URL; ?>trucks.php?search='+encodeURIComponent(this.value); }">
        </div>

        <!-- Topbar Actions: Notifications + Profile -->
        <div class="admin-topbar-actions">
            
            <!-- Notification Bell & Dropdown -->
            <button type="button" class="notif-btn" id="notifBtn" title="Notifications">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <?php if ($pendingInquiriesCount > 0): ?>
                    <span class="notif-badge"><?php echo $pendingInquiriesCount; ?></span>
                <?php endif; ?>
            </button>

            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <span>Notifications</span>
                    <span style="font-size: 0.75rem; color: var(--c-orange); font-weight: 600;"><?php echo $pendingInquiriesCount; ?> Pending</span>
                </div>
                <div class="notif-list">
                    <?php if (!empty($unreadNotifications)): ?>
                        <?php foreach ($unreadNotifications as $n): ?>
                            <a href="<?php echo $n['url']; ?>" class="notif-item">
                                <div class="notif-item-title"><?php echo $n['title']; ?></div>
                                <div class="notif-item-sub"><?php echo $n['sub']; ?></div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-empty">No pending notifications</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Compact Admin Pill -->
            <a href="<?php echo ADMIN_URL; ?>settings.php" class="admin-user-pill">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($adminDisplayName, 0, 1)); ?>
                </div>
                <span><?php echo $adminDisplayName; ?></span>
            </a>
        </div>
    </header>

    <div class="admin-container">
        
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo ($flash['type'] === 'success') ? 'success' : (($flash['type'] === 'warning') ? 'warning' : 'danger'); ?>">
                <div><?php echo sanitize_output($flash['message']); ?></div>
            </div>
        <?php endif; ?>