<?php
/**
 * =============================================================================
 * Moal General Suppliers - System-Wide Activities & Audit Stream
 * =============================================================================
 * Filterable live log of customer interactions, fleet requests, inventory updates.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'All System Activities';
$db = getDB();

$categoryFilter = sanitize_input($_GET['category'] ?? 'all');
$validCategories = ['all', 'inquiries', 'requests', 'trucks', 'clients'];
if (!in_array($categoryFilter, $validCategories)) {
    $categoryFilter = 'all';
}
$dateRange = sanitize_input($_GET['range'] ?? 'all');
$validRanges = ['all', 'today', 'week', 'month', 'year', 'custom'];
if (!in_array($dateRange, $validRanges)) {
    $dateRange = 'all';
}
$startDate = sanitize_input($_GET['start_date'] ?? $_GET['from'] ?? '');
$endDate   = sanitize_input($_GET['end_date'] ?? $_GET['to'] ?? '');

$searchKey = sanitize_input($_GET['search'] ?? '');

$dateSql = get_date_filter_sql('created_at');

$allActivities = [];

// 1. Inquiries
if ($categoryFilter === 'all' || $categoryFilter === 'inquiries') {
    $sql = "SELECT id, inquiry_code AS code, customer_name, customer_email, inquiry_type AS sub_type, status, message AS notes, created_at, 'inquiry' AS act_type FROM inquiries WHERE 1=1 $dateSql";
    if (!empty($searchKey)) {
        $sql .= " AND (inquiry_code LIKE " . $db->quote("%$searchKey%") . " OR customer_name LIKE " . $db->quote("%$searchKey%") . " OR customer_email LIKE " . $db->quote("%$searchKey%") . ")";
    }
    $sql .= " ORDER BY created_at DESC LIMIT 50";
    $stmt = $db->query($sql);
    while ($row = $stmt->fetch()) {
        $allActivities[] = $row;
    }
}

// 2. Fleet Requests
if ($categoryFilter === 'all' || $categoryFilter === 'requests') {
    $sql = "SELECT r.id, r.request_code AS code, c.full_name AS customer_name, c.email AS customer_email, 'Fleet Sourcing' AS sub_type, r.status, r.notes, r.created_at, 'request' AS act_type FROM customer_requests r LEFT JOIN customers c ON r.customer_id = c.id WHERE 1=1 $dateSql";
    if (!empty($searchKey)) {
        $sql .= " AND (r.request_code LIKE " . $db->quote("%$searchKey%") . " OR c.full_name LIKE " . $db->quote("%$searchKey%") . ")";
    }
    $sql .= " ORDER BY r.created_at DESC LIMIT 50";
    $stmt = $db->query($sql);
    while ($row = $stmt->fetch()) {
        $allActivities[] = $row;
    }
}

// 3. Truck Inventory Updates
if ($categoryFilter === 'all' || $categoryFilter === 'trucks') {
    $sql = "SELECT id, truck_code AS code, title AS customer_name, brand AS customer_email, purpose_category AS sub_type, availability_status AS status, description AS notes, updated_at AS created_at, 'truck' AS act_type FROM trucks WHERE 1=1";
    if (!empty($searchKey)) {
        $sql .= " AND (truck_code LIKE " . $db->quote("%$searchKey%") . " OR title LIKE " . $db->quote("%$searchKey%") . " OR brand LIKE " . $db->quote("%$searchKey%") . ")";
    }
    $sql .= " ORDER BY updated_at DESC LIMIT 30";
    $stmt = $db->query($sql);
    while ($row = $stmt->fetch()) {
        $allActivities[] = $row;
    }
}

// 4. Clients Registrations
if ($categoryFilter === 'all' || $categoryFilter === 'clients') {
    $sql = "SELECT id, CONCAT('CLI-', id) AS code, full_name AS customer_name, email AS customer_email, business_name AS sub_type, IF(is_verified=1, 'Verified', 'Pending OTP') AS status, delivery_address AS notes, created_at, 'client' AS act_type FROM customers WHERE 1=1 $dateSql";
    if (!empty($searchKey)) {
        $sql .= " AND (full_name LIKE " . $db->quote("%$searchKey%") . " OR email LIKE " . $db->quote("%$searchKey%") . " OR business_name LIKE " . $db->quote("%$searchKey%") . ")";
    }
    $sql .= " ORDER BY created_at DESC LIMIT 30";
    $stmt = $db->query($sql);
    while ($row = $stmt->fetch()) {
        $allActivities[] = $row;
    }
}

// Sort unified stream by created_at DESC
usort($allActivities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-heading-title">All System Activities</h1>
        <div class="admin-heading-sub">Real-time audit log of customer inquiries, fleet requests, and inventory events</div>
    </div>
</div>

<!-- Category Tabs -->
<div class="filter-tabs">
    <a href="<?php echo ADMIN_URL; ?>activities.php?category=all&range=<?php echo urlencode($dateRange); ?>" class="filter-tab <?php echo ($categoryFilter === 'all') ? 'active' : ''; ?>">
        All Activities
    </a>
    <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries&range=<?php echo urlencode($dateRange); ?>" class="filter-tab <?php echo ($categoryFilter === 'inquiries') ? 'active' : ''; ?>">
        Inquiries &amp; Quotes
    </a>
    <a href="<?php echo ADMIN_URL; ?>activities.php?category=requests&range=<?php echo urlencode($dateRange); ?>" class="filter-tab <?php echo ($categoryFilter === 'requests') ? 'active' : ''; ?>">
        Fleet Sourcing
    </a>
    <a href="<?php echo ADMIN_URL; ?>activities.php?category=trucks&range=<?php echo urlencode($dateRange); ?>" class="filter-tab <?php echo ($categoryFilter === 'trucks') ? 'active' : ''; ?>">
        Inventory Logs
    </a>
    <a href="<?php echo ADMIN_URL; ?>activities.php?category=clients&range=<?php echo urlencode($dateRange); ?>" class="filter-tab <?php echo ($categoryFilter === 'clients') ? 'active' : ''; ?>">
        Client Accounts
    </a>
</div>

<!-- Search & Scope Filters -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>activities.php" style="display: grid; grid-template-columns: 2fr 1.3fr auto auto auto auto; gap: 10px; align-items: center;">
        <input type="hidden" name="category" value="<?php echo sanitize_output($categoryFilter); ?>">

        <input type="text" name="search" class="form-control" placeholder="Search code, client name, keyword..." value="<?php echo sanitize_output($searchKey); ?>">

        <select name="range" class="form-control">
            <option value="all" <?php echo ($dateRange === 'all') ? 'selected' : ''; ?>>All Time Events</option>
            <option value="today" <?php echo ($dateRange === 'today') ? 'selected' : ''; ?>>Today's Events</option>
            <option value="week" <?php echo ($dateRange === 'week') ? 'selected' : ''; ?>>Last 7 Days</option>
            <option value="month" <?php echo ($dateRange === 'month') ? 'selected' : ''; ?>>Last 30 Days</option>
            <option value="year" <?php echo ($dateRange === 'year') ? 'selected' : ''; ?>>Past 365 Days</option>
            <option value="custom" <?php echo ($dateRange === 'custom' || !empty($startDate) || !empty($endDate)) ? 'selected' : ''; ?>>Custom Range</option>
        </select>

        <input type="date" name="start_date" class="form-control" value="<?php echo sanitize_output($startDate); ?>" placeholder="From">
        <input type="date" name="end_date" class="form-control" value="<?php echo sanitize_output($endDate); ?>" placeholder="To">

        <button type="submit" class="btn btn-dark btn-sm" style="padding: 9px 16px;">Filter</button>
        <a href="<?php echo ADMIN_URL; ?>activities.php?category=<?php echo urlencode($categoryFilter); ?>" class="btn btn-outline btn-sm" style="padding: 9px 12px;">Reset</a>
    </form>
</div>

<!-- Activities Stream -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Activity Stream (<?php echo count($allActivities); ?> Events Recorded)</div>
    </div>

    <div class="clean-card-body">
        <?php if (!empty($allActivities)): ?>
            <div class="activity-feed-timeline">
                <?php foreach ($allActivities as $act): ?>
                    <?php 
                        $iconClass = 'icon-inquiry';
                        $iconText = 'INQ';
                        $targetUrl = ADMIN_URL . 'inquiries.php';

                        if ($act['act_type'] === 'request') {
                            $iconClass = 'icon-request';
                            $iconText = 'REQ';
                            $targetUrl = ADMIN_URL . 'request-details.php?id=' . (int)$act['id'];
                        } elseif ($act['act_type'] === 'truck') {
                            $iconClass = 'icon-truck';
                            $iconText = 'TRK';
                            $targetUrl = ADMIN_URL . 'truck-form.php?id=' . (int)$act['id'];
                        } elseif ($act['act_type'] === 'client') {
                            $iconClass = 'icon-customer';
                            $iconText = 'CLI';
                            $targetUrl = ADMIN_URL . 'customer-details.php?id=' . (int)$act['id'];
                        } elseif ($act['act_type'] === 'inquiry') {
                            $targetUrl = ADMIN_URL . 'inquiry-details.php?id=' . (int)$act['id'];
                        }
                    ?>
                    <div class="activity-feed-card">
                        <div class="activity-feed-icon <?php echo $iconClass; ?>">
                            <?php echo $iconText; ?>
                        </div>
                        <div class="activity-feed-content">
                            <div class="activity-feed-header">
                                <div>
                                    <span class="activity-feed-title"><?php echo sanitize_output($act['customer_name']); ?></span>
                                    <span style="font-family: monospace; font-size: 0.8rem; color: var(--c-orange); margin-left: 6px;"><?php echo sanitize_output($act['code']); ?></span>
                                </div>
                                <div class="activity-feed-time">
                                    <?php echo date('M j, Y - g:ia', strtotime($act['created_at'])); ?>
                                </div>
                            </div>
                            <div class="activity-feed-desc">
                                <?php if (!empty($act['sub_type'])): ?>
                                    <strong><?php echo sanitize_output($act['sub_type']); ?></strong> &bull; 
                                <?php endif; ?>
                                Status: <span class="badge badge-subtle" style="font-size: 0.68rem;"><?php echo sanitize_output($act['status']); ?></span>
                                <?php if (!empty($act['notes'])): ?>
                                    <div style="margin-top: 4px; color: var(--c-muted); font-size: 0.8rem;"><?php echo sanitize_output(substr(strip_tags($act['notes']), 0, 140)); ?><?php echo strlen($act['notes']) > 140 ? '...' : ''; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <a href="<?php echo $targetUrl; ?>" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 0.75rem;">
                                View &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-card">
                <div class="empty-state-title">No Activities Found</div>
                <div class="empty-state-text">
                    <?php if (!empty($searchKey) || $dateRange !== 'all'): ?>
                        No operational events match your filter parameters. Try clearing your search query.
                    <?php else: ?>
                        No system activity recorded yet in this category.
                    <?php endif; ?>
                </div>
                <div class="empty-state-actions">
                    <a href="<?php echo ADMIN_URL; ?>activities.php" class="btn btn-outline btn-sm">Reset Filters</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
