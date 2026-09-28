<?php
/**
 * =============================================================================
 * Moal General Suppliers - Staff Portal Dashboard / Overview
 * =============================================================================
 * Compact, highly professional operational dashboard designed for immediate
 * 5-10 second operational comprehension.
 * 
 * 1. Welcome Header (+ Add Truck, View Inquiries)
 * 2. Four Essential KPI Cards (Available Trucks, Active Inquiries, Registered Clients, Newsletter Subscribers)
 * 3. Compact Inventory & Inquiry Status Visualisation (Horizontal Progress Bars)
 * 4. Action Required (Prioritized Operational Tasks) & Recent System Activity
 * 
 * Strictly adheres to Moal General Suppliers brand design system & MySQL backend.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Staff Dashboard / Overview';
$db = getDB();

// -----------------------------------------------------------------------------
// 1. Database Metric Aggregations (KPIs & Statuses)
// -----------------------------------------------------------------------------

// A. Truck Inventory Breakdown
$stmtTrucks = $db->query('SELECT 
    COUNT(*) AS total_trucks,
    SUM(CASE WHEN availability_status = "Available" THEN 1 ELSE 0 END) AS available_trucks,
    SUM(CASE WHEN availability_status = "Reserved" THEN 1 ELSE 0 END) AS reserved_trucks,
    SUM(CASE WHEN availability_status = "Sold" THEN 1 ELSE 0 END) AS sold_trucks,
    SUM(CASE WHEN availability_status = "Maintenance" THEN 1 ELSE 0 END) AS maintenance_trucks
FROM trucks');
$truckMetrics = $stmtTrucks->fetch(PDO::FETCH_ASSOC);

$totalTrucks       = (int)($truckMetrics['total_trucks'] ?? 0);
$availableTrucks   = (int)($truckMetrics['available_trucks'] ?? 0);
$reservedTrucks    = (int)($truckMetrics['reserved_trucks'] ?? 0);
$soldTrucks        = (int)($truckMetrics['sold_trucks'] ?? 0);
$maintenanceTrucks = (int)($truckMetrics['maintenance_trucks'] ?? 0);

// Inventory Percentages
$pctAvailable   = $totalTrucks > 0 ? round(($availableTrucks / $totalTrucks) * 100, 1) : 0;
$pctReserved    = $totalTrucks > 0 ? round(($reservedTrucks / $totalTrucks) * 100, 1) : 0;
$pctSold        = $totalTrucks > 0 ? round(($soldTrucks / $totalTrucks) * 100, 1) : 0;
$pctMaintenance = $totalTrucks > 0 ? round(($maintenanceTrucks / $totalTrucks) * 100, 1) : 0;

// B. Customer Inquiries Breakdown
$stmtInq = $db->query('SELECT 
    COUNT(*) AS total_inquiries,
    SUM(CASE WHEN status = "Pending" THEN 1 ELSE 0 END) AS pending_inquiries,
    SUM(CASE WHEN status = "In Review" THEN 1 ELSE 0 END) AS in_review_inquiries,
    SUM(CASE WHEN status = "Contacted" THEN 1 ELSE 0 END) AS contacted_inquiries,
    SUM(CASE WHEN status = "Resolved" THEN 1 ELSE 0 END) AS resolved_inquiries,
    SUM(CASE WHEN status = "Cancelled" THEN 1 ELSE 0 END) AS cancelled_inquiries,
    SUM(CASE WHEN status = "Pending" AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) AS urgent_inquiries
FROM inquiries');
$inqMetrics = $stmtInq->fetch(PDO::FETCH_ASSOC);

$totalInquiries      = (int)($inqMetrics['total_inquiries'] ?? 0);
$pendingInquiries    = (int)($inqMetrics['pending_inquiries'] ?? 0);
$inReviewInquiries   = (int)($inqMetrics['in_review_inquiries'] ?? 0);
$contactedInquiries  = (int)($inqMetrics['contacted_inquiries'] ?? 0);
$resolvedInquiries   = (int)($inqMetrics['resolved_inquiries'] ?? 0);
$cancelledInquiries  = (int)($inqMetrics['cancelled_inquiries'] ?? 0);
$urgentInquiries     = (int)($inqMetrics['urgent_inquiries'] ?? 0);
$activeInquiries     = $pendingInquiries + $inReviewInquiries;

// Inquiry Percentages
$pctPending   = $totalInquiries > 0 ? round(($pendingInquiries / $totalInquiries) * 100, 1) : 0;
$pctInReview  = $totalInquiries > 0 ? round(($inReviewInquiries / $totalInquiries) * 100, 1) : 0;
$pctContacted = $totalInquiries > 0 ? round(($contactedInquiries / $totalInquiries) * 100, 1) : 0;
$pctResolved  = $totalInquiries > 0 ? round(($resolvedInquiries / $totalInquiries) * 100, 1) : 0;
$pctCancelled = $totalInquiries > 0 ? round(($cancelledInquiries / $totalInquiries) * 100, 1) : 0;

// C. Registered Customers
$stmtCust = $db->query('SELECT 
    COUNT(*) AS total_cust,
    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) AS verified_cust
FROM customers');
$custMetrics = $stmtCust->fetch(PDO::FETCH_ASSOC);

$totalCustomers    = (int)($custMetrics['total_cust'] ?? 0);
$verifiedCustomers = (int)($custMetrics['verified_cust'] ?? 0);

// D. Newsletter Subscribers
$stmtSubs = $db->query('SELECT 
    COUNT(*) AS total_subs,
    SUM(CASE WHEN created_at >= DATE_FORMAT(NOW(), "%Y-%m-01") THEN 1 ELSE 0 END) AS month_subs
FROM newsletter_subscribers');
$subMetrics = $stmtSubs->fetch(PDO::FETCH_ASSOC);

$totalSubscribers    = (int)($subMetrics['total_subs'] ?? 0);
$newSubscribersMonth = (int)($subMetrics['month_subs'] ?? 0);

// -----------------------------------------------------------------------------
// 2. Action Required (Prioritized Operational Tasks - Maximum 3 items)
// -----------------------------------------------------------------------------
$actionTasks = [];

// Priority 1: Unanswered Inquiries > 24 hours (Critical 🔴)
try {
    $stmtCritInq = $db->query('SELECT id, inquiry_code, customer_name, customer_phone, created_at 
        FROM inquiries 
        WHERE status = "Pending" AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        ORDER BY created_at ASC LIMIT 2');
    while ($crit = $stmtCritInq->fetch(PDO::FETCH_ASSOC)) {
        $actionTasks[] = [
            'priority'  => 'Critical',
            'badge'     => '🔴 Critical',
            'badge_cls' => 'badge-critical',
            'title'     => 'Inquiry response overdue > 24h: ' . sanitize_output($crit['customer_name']),
            'sub'       => sanitize_output($crit['inquiry_code']) . ' &bull; Call ' . sanitize_output($crit['customer_phone']),
            'url'       => ADMIN_URL . 'inquiry-details.php?id=' . (int)$crit['id'],
            'time'      => date('M j, g:ia', strtotime($crit['created_at']))
        ];
    }
} catch (Exception $e) {}

// Priority 2: Recent Pending Inquiries (< 24 hours) - High 🟠
if (count($actionTasks) < 3) {
    try {
        $stmtHighInq = $db->query('SELECT id, inquiry_code, customer_name, inquiry_type, created_at 
            FROM inquiries 
            WHERE status = "Pending" AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
            ORDER BY created_at DESC LIMIT 2');
        while ($high = $stmtHighInq->fetch(PDO::FETCH_ASSOC)) {
            if (count($actionTasks) >= 3) break;
            $actionTasks[] = [
                'priority'  => 'High',
                'badge'     => '🟠 High',
                'badge_cls' => 'badge-high',
                'title'     => 'New client inquiry requires review: ' . sanitize_output($high['customer_name']),
                'sub'       => sanitize_output($high['inquiry_code']) . ' &bull; ' . sanitize_output($high['inquiry_type']),
                'url'       => ADMIN_URL . 'inquiry-details.php?id=' . (int)$high['id'],
                'time'      => date('M j, g:ia', strtotime($high['created_at']))
            ];
        }
    } catch (Exception $e) {}
}

// Priority 3: Customer Fleet Requests awaiting assignment or follow-up - High 🟠 / Medium 🟡
if (count($actionTasks) < 3) {
    try {
        $stmtReqTask = $db->query('SELECT r.id, r.request_code, c.full_name, r.status, r.created_at 
            FROM customer_requests r 
            LEFT JOIN customers c ON r.customer_id = c.id 
            WHERE r.status IN ("New", "Assigned to Agent") 
            ORDER BY r.id DESC LIMIT 2');
        while ($rt = $stmtReqTask->fetch(PDO::FETCH_ASSOC)) {
            if (count($actionTasks) >= 3) break;
            $isNew = ($rt['status'] === 'New');
            $actionTasks[] = [
                'priority'  => $isNew ? 'High' : 'Medium',
                'badge'     => $isNew ? '🟠 High' : '🟡 Medium',
                'badge_cls' => $isNew ? 'badge-high' : 'badge-medium',
                'title'     => ($isNew ? 'Fleet request unassigned: ' : 'Fleet follow-up pending: ') . sanitize_output($rt['full_name'] ?? 'Client'),
                'sub'       => sanitize_output($rt['request_code']) . ' &bull; Status: ' . sanitize_output($rt['status']),
                'url'       => ADMIN_URL . 'request-details.php?id=' . (int)$rt['id'],
                'time'      => date('M j, g:ia', strtotime($rt['created_at']))
            ];
        }
    } catch (Exception $e) {}
}

// -----------------------------------------------------------------------------
// 3. Recent Activity Stream (Maximum 3 events)
// -----------------------------------------------------------------------------
$recentActivities = [];
try {
    // Recent Inquiries
    $stmtActInq = $db->query('SELECT id, inquiry_code, customer_name, created_at FROM inquiries ORDER BY created_at DESC LIMIT 3');
    while ($ai = $stmtActInq->fetch(PDO::FETCH_ASSOC)) {
        $recentActivities[] = [
            'type'  => 'inquiry',
            'icon'  => '✉️',
            'title' => 'Inquiry: ' . sanitize_output($ai['customer_name']),
            'sub'   => 'Reference #' . sanitize_output($ai['inquiry_code']),
            'time'  => $ai['created_at'],
            'url'   => ADMIN_URL . 'inquiry-details.php?id=' . (int)$ai['id']
        ];
    }

    // Recent Registrations
    $stmtActCust = $db->query('SELECT id, full_name, email, created_at FROM customers ORDER BY created_at DESC LIMIT 3');
    while ($ac = $stmtActCust->fetch(PDO::FETCH_ASSOC)) {
        $recentActivities[] = [
            'type'  => 'client',
            'icon'  => '👥',
            'title' => 'New Client Registration',
            'sub'   => sanitize_output($ac['full_name']) . ' created account',
            'time'  => $ac['created_at'],
            'url'   => ADMIN_URL . 'customer-details.php?id=' . (int)$ac['id']
        ];
    }

    // Recent Fleet Requests
    $stmtActReq = $db->query('SELECT r.id, r.request_code, c.full_name, r.created_at FROM customer_requests r LEFT JOIN customers c ON r.customer_id = c.id ORDER BY r.created_at DESC LIMIT 3');
    while ($ar = $stmtActReq->fetch(PDO::FETCH_ASSOC)) {
        $recentActivities[] = [
            'type'  => 'request',
            'icon'  => '📋',
            'title' => 'Fleet Quote Request',
            'sub'   => sanitize_output($ar['request_code']) . ' from ' . sanitize_output($ar['full_name'] ?? 'Client'),
            'time'  => $ar['created_at'],
            'url'   => ADMIN_URL . 'request-details.php?id=' . (int)$ar['id']
        ];
    }

    // Sort chronologically and take top 3
    usort($recentActivities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    $recentActivities = array_slice($recentActivities, 0, 3);
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
?>

<!-- =============================================================================
     AREA ONE: WELCOME HEADER
     ============================================================================= -->
<div class="dashboard-header-block" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="admin-heading-title" style="margin-bottom: 2px;">Staff Dashboard / Overview</h1>
        <p style="color: var(--c-muted); font-size: 0.9125rem; margin: 0;">
            Monitor inventory, customer inquiries and client activity.
        </p>
    </div>

    <!-- Right Side Actions: Single Prominent + Add Truck & Secondary View Inquiries -->
    <div style="display: flex; align-items: center; gap: 10px;">
        <a href="<?php echo ADMIN_URL; ?>truck-form.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; padding: 9px 18px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Add Truck
        </a>
        <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries" class="btn btn-outline" style="font-weight: 600; padding: 9px 16px;">
            View Inquiries
        </a>
    </div>
</div>

<!-- =============================================================================
     AREA TWO: FOUR ESSENTIAL KPI CARDS
     ============================================================================= -->
<div class="kpi-grid-4">
    
    <!-- 1. Available Trucks -->
    <a href="<?php echo ADMIN_URL; ?>trucks.php?status=Available" class="kpi-card" title="View Available Trucks">
        <div class="kpi-card-header">
            <span class="kpi-card-label">Available Trucks</span>
            <span class="kpi-card-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            </span>
        </div>
        <div class="kpi-card-val"><?php echo $availableTrucks; ?></div>
        <div class="kpi-card-sub">
            <?php echo $availableTrucks; ?> of <?php echo $totalTrucks; ?> units ready for sale
        </div>
    </a>

    <!-- 2. Active Inquiries -->
    <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries" class="kpi-card" title="View Active Inquiries">
        <div class="kpi-card-header">
            <span class="kpi-card-label">Active Inquiries</span>
            <span class="kpi-card-icon" style="color: #F59E0B; background: rgba(245, 158, 11, 0.1);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </span>
        </div>
        <div class="kpi-card-val"><?php echo $activeInquiries; ?></div>
        <div class="kpi-card-sub">
            <strong style="color: #F59E0B;"><?php echo $pendingInquiries; ?> Pending</strong> &bull; <?php echo $inReviewInquiries; ?> In Review
        </div>
    </a>

    <!-- 3. Registered Clients -->
    <a href="<?php echo ADMIN_URL; ?>customers.php" class="kpi-card" title="View Client Information">
        <div class="kpi-card-header">
            <span class="kpi-card-label">Registered Clients</span>
            <span class="kpi-card-icon" style="color: #3B82F6; background: rgba(59, 130, 246, 0.1);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </span>
        </div>
        <div class="kpi-card-val"><?php echo $totalCustomers; ?></div>
        <div class="kpi-card-sub">
            <strong style="color: #10B981;"><?php echo $verifiedCustomers; ?></strong> verified business accounts
        </div>
    </a>

    <!-- 4. Newsletter Subscribers -->
    <a href="<?php echo ADMIN_URL; ?>newsletter.php" class="kpi-card" title="Manage Newsletter Subscribers">
        <div class="kpi-card-header">
            <span class="kpi-card-label">Newsletter Subscribers</span>
            <span class="kpi-card-icon" style="color: #10B981; background: rgba(16, 185, 129, 0.1);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </span>
        </div>
        <div class="kpi-card-val"><?php echo $totalSubscribers; ?></div>
        <div class="kpi-card-sub">
            <?php if ($newSubscribersMonth > 0): ?>
                <strong style="color: #10B981;">+<?php echo $newSubscribersMonth; ?></strong> joined this month
            <?php else: ?>
                Active subscriber database
            <?php endif; ?>
        </div>
    </a>

</div>

<!-- =============================================================================
     AREA THREE: COMPACT INVENTORY AND INQUIRY VISUALISATION (HORIZONTAL BARS)
     ============================================================================= -->
<div class="compact-panels-grid">

    <!-- LEFT PANEL: INVENTORY STATUS -->
    <div class="admin-card" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
        <div>
            <div class="admin-card-header" style="margin-bottom: 1.25rem;">
                <div>
                    <div class="admin-card-title">Inventory Status</div>
                    <div style="font-size: 0.8125rem; color: var(--c-muted);">Current vehicle distribution by stock condition</div>
                </div>
                <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline btn-sm" style="font-size: 0.78rem;">
                    Available Trucks &rarr;
                </a>
            </div>

            <?php if ($availableTrucks === $totalTrucks && $totalTrucks > 0): ?>
                <!-- Single full-length bar indicator when all trucks are in one category -->
                <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 12px 14px; margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-weight: 700; color: #065F46; font-size: 0.88rem;">
                            ● 100% Available for Immediate Sale
                        </span>
                        <strong style="color: #065F46; font-size: 0.95rem;"><?php echo $availableTrucks; ?> / <?php echo $totalTrucks; ?> Units</strong>
                    </div>
                    <div style="width: 100%; height: 8px; background: #D1FAE5; border-radius: 4px; overflow: hidden;">
                        <div style="width: 100%; height: 100%; background: #10B981; border-radius: 4px;"></div>
                    </div>
                    <div style="font-size: 0.78rem; color: #047857; margin-top: 6px;">
                        All current inventory units in the Lagos yard are verified and ready for commercial inspection.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Distribution Rows -->
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                
                <!-- Available -->
                <a href="<?php echo ADMIN_URL; ?>trucks.php?status=Available" style="text-decoration: none; color: inherit;" class="status-bar-row">
                    <div class="status-bar-meta">
                        <span class="status-bar-label">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10B981;"></span>
                            Available
                        </span>
                        <span class="status-bar-val">
                            <?php echo $availableTrucks; ?> units <span style="font-weight: normal; color: var(--c-muted); font-size: 0.8rem;">(<?php echo $pctAvailable; ?>%)</span>
                        </span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?php echo $pctAvailable; ?>%; background: #10B981;"></div>
                    </div>
                </a>

                <!-- Reserved -->
                <a href="<?php echo ADMIN_URL; ?>trucks.php?status=Reserved" style="text-decoration: none; color: inherit;" class="status-bar-row">
                    <div class="status-bar-meta">
                        <span class="status-bar-label">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--c-orange);"></span>
                            Reserved
                        </span>
                        <span class="status-bar-val">
                            <?php echo $reservedTrucks; ?> units <span style="font-weight: normal; color: var(--c-muted); font-size: 0.8rem;">(<?php echo $pctReserved; ?>%)</span>
                        </span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?php echo $pctReserved; ?>%; background: var(--c-orange);"></div>
                    </div>
                </a>

                <!-- Sold -->
                <a href="<?php echo ADMIN_URL; ?>trucks.php?status=Sold" style="text-decoration: none; color: inherit;" class="status-bar-row">
                    <div class="status-bar-meta">
                        <span class="status-bar-label">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #64748B;"></span>
                            Sold
                        </span>
                        <span class="status-bar-val">
                            <?php echo $soldTrucks; ?> units <span style="font-weight: normal; color: var(--c-muted); font-size: 0.8rem;">(<?php echo $pctSold; ?>%)</span>
                        </span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?php echo $pctSold; ?>%; background: #64748B;"></div>
                    </div>
                </a>

                <!-- Maintenance -->
                <a href="<?php echo ADMIN_URL; ?>trucks.php?status=Maintenance" style="text-decoration: none; color: inherit;" class="status-bar-row">
                    <div class="status-bar-meta">
                        <span class="status-bar-label">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #0F172A;"></span>
                            Maintenance
                        </span>
                        <span class="status-bar-val">
                            <?php echo $maintenanceTrucks; ?> units <span style="font-weight: normal; color: var(--c-muted); font-size: 0.8rem;">(<?php echo $pctMaintenance; ?>%)</span>
                        </span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?php echo $pctMaintenance; ?>%; background: #0F172A;"></div>
                    </div>
                </a>

            </div>
        </div>

        <div style="border-top: 1px solid var(--c-border-light); padding-top: 0.85rem; margin-top: 1.25rem; display: flex; justify-content: space-between; font-size: 0.8125rem; color: var(--c-muted);">
            <span>Total Fleet Units: <strong><?php echo $totalTrucks; ?></strong></span>
            <a href="<?php echo ADMIN_URL; ?>reports.php?type=inventory" style="color: var(--c-orange); text-decoration: none; font-weight: 600;">Full Inventory Report &rarr;</a>
        </div>
    </div>

    <!-- RIGHT PANEL: CUSTOMER INQUIRY OVERVIEW -->
    <div class="admin-card" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
        <div>
            <div class="admin-card-header" style="margin-bottom: 1.25rem;">
                <div>
                    <div class="admin-card-title">Customer Inquiry Overview</div>
                    <div style="font-size: 0.8125rem; color: var(--c-muted);">Status breakdown of commercial buyer inquiries</div>
                </div>
                <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries" class="btn btn-outline btn-sm" style="font-size: 0.78rem;">
                    Manage Inquiries &rarr;
                </a>
            </div>

            <!-- Active / Operational Workload Indicators -->
            <div style="display: flex; gap: 8px; margin-bottom: 1.15rem; flex-wrap: wrap;">
                <span class="badge badge-warning" style="font-size: 0.75rem; padding: 4px 8px;">
                    ● <?php echo $pendingInquiries; ?> Awaiting Staff Review
                </span>
                <span class="badge badge-primary" style="font-size: 0.75rem; padding: 4px 8px;">
                    ● <?php echo $inReviewInquiries; ?> In Consultation
                </span>
            </div>

            <!-- Status Breakdown Bars -->
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                
                <!-- Pending -->
                <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries&status=Pending" style="text-decoration: none; color: inherit;" class="status-bar-row">
                    <div class="status-bar-meta">
                        <span class="status-bar-label">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #F59E0B;"></span>
                            Pending (Action Needed)
                        </span>
                        <span class="status-bar-val" style="color: #D97706;">
                            <?php echo $pendingInquiries; ?> inquiries <span style="font-weight: normal; color: var(--c-muted); font-size: 0.8rem;">(<?php echo $pctPending; ?>%)</span>
                        </span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?php echo $pctPending; ?>%; background: #F59E0B;"></div>
                    </div>
                </a>

                <!-- In Review -->
                <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries&status=In+Review" style="text-decoration: none; color: inherit;" class="status-bar-row">
                    <div class="status-bar-meta">
                        <span class="status-bar-label">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #3B82F6;"></span>
                            In Review
                        </span>
                        <span class="status-bar-val">
                            <?php echo $inReviewInquiries; ?> inquiries <span style="font-weight: normal; color: var(--c-muted); font-size: 0.8rem;">(<?php echo $pctInReview; ?>%)</span>
                        </span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?php echo $pctInReview; ?>%; background: #3B82F6;"></div>
                    </div>
                </a>

                <!-- Contacted -->
                <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries&status=Contacted" style="text-decoration: none; color: inherit;" class="status-bar-row">
                    <div class="status-bar-meta">
                        <span class="status-bar-label">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #8B5CF6;"></span>
                            Contacted
                        </span>
                        <span class="status-bar-val">
                            <?php echo $contactedInquiries; ?> inquiries <span style="font-weight: normal; color: var(--c-muted); font-size: 0.8rem;">(<?php echo $pctContacted; ?>%)</span>
                        </span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?php echo $pctContacted; ?>%; background: #8B5CF6;"></div>
                    </div>
                </a>

                <!-- Resolved -->
                <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries&status=Resolved" style="text-decoration: none; color: inherit;" class="status-bar-row">
                    <div class="status-bar-meta">
                        <span class="status-bar-label">
                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10B981;"></span>
                            Resolved
                        </span>
                        <span class="status-bar-val">
                            <?php echo $resolvedInquiries; ?> inquiries <span style="font-weight: normal; color: var(--c-muted); font-size: 0.8rem;">(<?php echo $pctResolved; ?>%)</span>
                        </span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?php echo $pctResolved; ?>%; background: #10B981;"></div>
                    </div>
                </a>

            </div>
        </div>

        <div style="border-top: 1px solid var(--c-border-light); padding-top: 0.85rem; margin-top: 1.25rem; display: flex; justify-content: space-between; font-size: 0.8125rem; color: var(--c-muted);">
            <span>Active Pipeline: <strong><?php echo $activeInquiries; ?></strong> / <?php echo $totalInquiries; ?></span>
            <a href="<?php echo ADMIN_URL; ?>reports.php?type=inquiries" style="color: var(--c-orange); text-decoration: none; font-weight: 600;">Inquiry Reports &rarr;</a>
        </div>
    </div>

</div>

<!-- =============================================================================
     AREA FOUR & FIVE: ACTION REQUIRED & RECENT ACTIVITY (COMPACT 2-COLUMN SECTION)
     ============================================================================= -->
<div class="compact-panels-grid">

    <!-- LEFT: ACTION REQUIRED (Max 3 Items) -->
    <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card-header" style="margin-bottom: 1rem;">
            <div>
                <div class="admin-card-title">Action Required</div>
                <div style="font-size: 0.8125rem; color: var(--c-muted);">Operational tasks requiring staff intervention</div>
            </div>
            <a href="<?php echo ADMIN_URL; ?>activities.php" class="btn btn-outline btn-sm" style="font-size: 0.78rem;">
                View All &rarr;
            </a>
        </div>

        <?php if (!empty($actionTasks)): ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($actionTasks as $task): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--bg-canvas); border: 1px solid var(--c-border); border-radius: 6px; gap: 12px; transition: var(--trans);">
                        <div style="min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 3px;">
                                <span class="priority-badge-pill <?php echo $task['badge_cls']; ?>" style="font-size: 0.68rem; padding: 2px 6px;">
                                    <?php echo $task['badge']; ?>
                                </span>
                                <span style="font-size: 0.75rem; color: var(--c-muted);"><?php echo $task['time']; ?></span>
                            </div>
                            <div style="font-size: 0.875rem; font-weight: 700; color: var(--c-navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo $task['title']; ?>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--c-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo $task['sub']; ?>
                            </div>
                        </div>

                        <a href="<?php echo $task['url']; ?>" class="btn btn-outline btn-sm" style="font-size: 0.78rem; white-space: nowrap; padding: 5px 12px; flex-shrink: 0;">
                            Action &rarr;
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: var(--bg-canvas); border: 1px dashed var(--c-border); border-radius: 6px; padding: 2rem 1rem; text-align: center;">
                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">✅</div>
                <div style="font-weight: 700; color: var(--c-navy); font-size: 0.9125rem;">All Caught Up</div>
                <div style="font-size: 0.8125rem; color: var(--c-muted); margin-top: 2px;">
                    No urgent inquiries or unassigned requests require immediate attention.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: RECENT ACTIVITY (Top 3 Events) -->
    <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card-header" style="margin-bottom: 1rem;">
            <div>
                <div class="admin-card-title">Recent Activity</div>
                <div style="font-size: 0.8125rem; color: var(--c-muted);">Latest operational events across the system</div>
            </div>
            <a href="<?php echo ADMIN_URL; ?>activities.php" class="btn btn-outline btn-sm" style="font-size: 0.78rem;">
                View All Activity &rarr;
            </a>
        </div>

        <?php if (!empty($recentActivities)): ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($recentActivities as $act): ?>
                    <a href="<?php echo $act['url']; ?>" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: var(--bg-canvas); border: 1px solid var(--c-border); border-radius: 6px; text-decoration: none; color: inherit; transition: var(--trans);">
                        <div style="width: 32px; height: 32px; border-radius: 6px; background: var(--bg-card); border: 1px solid var(--c-border); display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;">
                            <?php echo $act['icon']; ?>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; font-weight: 700; color: var(--c-navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo $act['title']; ?>
                                </span>
                                <span style="font-size: 0.75rem; color: var(--c-muted); margin-left: 8px; flex-shrink: 0;">
                                    <?php echo date('M j, g:ia', strtotime($act['time'])); ?>
                                </span>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--c-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">
                                <?php echo $act['sub']; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: var(--bg-canvas); border: 1px dashed var(--c-border); border-radius: 6px; padding: 2rem 1rem; text-align: center;">
                <div style="font-size: 0.8125rem; color: var(--c-muted);">No activity logged yet.</div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>