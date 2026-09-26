<?php
/**
 * =============================================================================
 * Moal General Suppliers - Staff Portal Overview Dashboard
 * =============================================================================
 * Pure simplified dashboard layout: essential widgets only, spacious cards,
 * interactive fleet charts, real-time action queues, and condensed activity feeds.
 * 100% data-driven from MySQL.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Overview Dashboard';
$db = getDB();

// -----------------------------------------------------------------------------
// 1. Date Filter Logic
// -----------------------------------------------------------------------------
$dateRange = isset($_GET['range']) ? trim($_GET['range']) : 'all';
$validRanges = ['all', 'month', 'week', 'today'];
if (!in_array($dateRange, $validRanges)) {
    $dateRange = 'all';
}

$dateSqlCondition = '';
if ($dateRange === 'today') {
    $dateSqlCondition = ' AND DATE(created_at) = CURDATE()';
} elseif ($dateRange === 'week') {
    $dateSqlCondition = ' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
} elseif ($dateRange === 'month') {
    $dateSqlCondition = ' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
}

// -----------------------------------------------------------------------------
// 2. Real Metric Aggregations
// -----------------------------------------------------------------------------
// A. Truck Inventory Breakdown
$stmt = $db->query('SELECT 
    COUNT(*) AS total_trucks,
    SUM(CASE WHEN availability_status = "Available" THEN 1 ELSE 0 END) AS available_trucks,
    SUM(CASE WHEN availability_status = "Reserved" THEN 1 ELSE 0 END) AS reserved_trucks,
    SUM(CASE WHEN availability_status = "Maintenance" THEN 1 ELSE 0 END) AS maintenance_trucks,
    SUM(CASE WHEN availability_status = "Sold" THEN 1 ELSE 0 END) AS sold_trucks,
    SUM(CASE WHEN availability_status IN ("Available", "Reserved") THEN price ELSE 0 END) AS active_asset_val
FROM trucks');
$truckMetrics = $stmt->fetch();

$totalTrucks       = (int)($truckMetrics['total_trucks'] ?? 0);
$availableTrucks   = (int)($truckMetrics['available_trucks'] ?? 0);
$reservedTrucks    = (int)($truckMetrics['reserved_trucks'] ?? 0);
$maintenanceTrucks = (int)($truckMetrics['maintenance_trucks'] ?? 0);
$soldTrucks        = (int)($truckMetrics['sold_trucks'] ?? 0);
$activeAssetVal    = (float)($truckMetrics['active_asset_val'] ?? 0);

// B. Client Inquiries & Leads Metrics
$stmtInq = $db->query("SELECT 
    COUNT(*) AS total_inquiries,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_inquiries,
    SUM(CASE WHEN status IN ('In Review', 'Contacted') THEN 1 ELSE 0 END) AS active_inquiries,
    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_inquiries
FROM inquiries WHERE 1=1" . $dateSqlCondition);
$inqMetrics = $stmtInq->fetch();

$totalInquiries   = (int)($inqMetrics['total_inquiries'] ?? 0);
$pendingInquiries = (int)($inqMetrics['pending_inquiries'] ?? 0);
$activeInquiries  = (int)($inqMetrics['active_inquiries'] ?? 0);
$resolvedInquiries= (int)($inqMetrics['resolved_inquiries'] ?? 0);

// C. Registered Customers Metrics
$stmtCust = $db->query('SELECT COUNT(*) AS total_cust, SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) AS verified_cust FROM customers');
$custMetrics = $stmtCust->fetch();
$totalCustomers    = (int)($custMetrics['total_cust'] ?? 0);
$verifiedCustomers = (int)($custMetrics['verified_cust'] ?? 0);

// D. Fleet Quote Requests
$stmtReq = $db->query("SELECT COUNT(*) FROM customer_requests WHERE status IN ('New', 'Assigned to Agent')" . $dateSqlCondition);
$pendingFleetRequests = (int)$stmtReq->fetchColumn();

// Total Action Items count
$totalActionCount = $pendingInquiries + $pendingFleetRequests;

// -----------------------------------------------------------------------------
// 3. Chart Data
// -----------------------------------------------------------------------------
// Donut: Truck Status Breakdown
$statusCounts = [
    'Available'   => $availableTrucks,
    'Reserved'    => $reservedTrucks,
    'Maintenance' => $maintenanceTrucks,
    'Sold'        => $soldTrucks
];

// Line Chart: Inquiries Activity Timeline (Last 7 Days)
$activityDays = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $activityDays[$d] = 0;
}

try {
    $stmtAct = $db->query('SELECT DATE(created_at) AS inq_date, COUNT(*) AS cnt 
        FROM inquiries 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)');
    while ($row = $stmtAct->fetch()) {
        if (isset($activityDays[$row['inq_date']])) {
            $activityDays[$row['inq_date']] = (int)$row['cnt'];
        }
    }
} catch (Exception $e) {}

$activityLabels = array_map(function($d) { return date('M j', strtotime($d)); }, array_keys($activityDays));
$activityCounts = array_values($activityDays);

// -----------------------------------------------------------------------------
// 4. Action Queue: Pending Inquiries Awaiting Immediate Attention
// -----------------------------------------------------------------------------
$pendingActionItems = [];
try {
    $stmtPending = $db->query('SELECT id, inquiry_code, customer_name, customer_phone, inquiry_type, created_at 
        FROM inquiries 
        WHERE status = "Pending" 
        ORDER BY id DESC LIMIT 4');
    $pendingActionItems = $stmtPending->fetchAll();
} catch (Exception $e) {}

// -----------------------------------------------------------------------------
// 5. Recent Condensed Activity Feed
// -----------------------------------------------------------------------------
$recentActivities = [];
try {
    // Inquiries
    $stmtRecentInq = $db->query('SELECT id, inquiry_code, customer_name, status, created_at, "inquiry" AS act_type FROM inquiries ORDER BY created_at DESC LIMIT 4');
    while ($row = $stmtRecentInq->fetch()) {
        $recentActivities[] = $row;
    }
    // Requests
    $stmtRecentReq = $db->query('SELECT r.id, r.request_code, c.full_name AS customer_name, r.status, r.created_at, "request" AS act_type FROM customer_requests r LEFT JOIN customers c ON r.customer_id = c.id ORDER BY r.created_at DESC LIMIT 3');
    while ($row = $stmtRecentReq->fetch()) {
        $recentActivities[] = $row;
    }
    // Sort all by created_at DESC
    usort($recentActivities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $recentActivities = array_slice($recentActivities, 0, 5);
} catch (Exception $e) {}

// Greeting helper
$hour = (int)date('G');
$greeting = ($hour < 12) ? 'Good morning' : (($hour < 17) ? 'Good afternoon' : 'Good evening');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Overview Header Bar -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-heading-title"><?php echo $greeting; ?>, <?php echo sanitize_output($adminDisplayName); ?></h1>
        <div class="admin-heading-sub">Real-time commercial fleet status, customer inquiries, and operations overview</div>
    </div>
    
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <!-- Date Range Filter -->
        <select class="date-filter-select" onchange="window.location.href='<?php echo ADMIN_URL; ?>dashboard.php?range='+this.value">
            <option value="all" <?php echo ($dateRange === 'all') ? 'selected' : ''; ?>>All Time Activity</option>
            <option value="today" <?php echo ($dateRange === 'today') ? 'selected' : ''; ?>>Today Only</option>
            <option value="week" <?php echo ($dateRange === 'week') ? 'selected' : ''; ?>>Last 7 Days</option>
            <option value="month" <?php echo ($dateRange === 'month') ? 'selected' : ''; ?>>Last 30 Days</option>
        </select>

        <a href="<?php echo ADMIN_URL; ?>truck-form.php" class="btn btn-primary btn-sm" style="padding: 7px 14px;">
            + Add Truck
        </a>
    </div>
</div>

<!-- =============================================================================
     WIDGET GRID 1: 4 Essential KPI Metric Cards (Simplified & Spacious)
     ============================================================================= -->
<div class="summary-grid-4">
    
    <!-- 1. Available Trucks -->
    <a href="<?php echo ADMIN_URL; ?>trucks.php?status=Available" class="summary-card highlight-orange">
        <div class="summary-card-top">
            <span class="summary-card-label">Available Trucks</span>
            <span class="badge badge-success" style="font-size: 0.68rem;">Ready for Sale</span>
        </div>
        <div class="summary-card-value"><?php echo $availableTrucks; ?></div>
        <div class="summary-card-sub"><strong><?php echo $reservedTrucks; ?></strong> reserved &bull; <strong><?php echo $maintenanceTrucks; ?></strong> in maintenance</div>
    </a>

    <!-- 2. Active Inquiries & Leads -->
    <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries" class="summary-card highlight-navy">
        <div class="summary-card-top">
            <span class="summary-card-label">Active Inquiries</span>
            <span class="badge badge-primary" style="font-size: 0.68rem;"><?php echo $pendingInquiries; ?> New</span>
        </div>
        <div class="summary-card-value"><?php echo $totalInquiries; ?></div>
        <div class="summary-card-sub"><strong><?php echo $pendingInquiries; ?></strong> pending staff review</div>
    </a>

    <!-- 3. Pending Action Items -->
    <a href="<?php echo ADMIN_URL; ?>activities.php?category=inquiries" class="summary-card">
        <div class="summary-card-top">
            <span class="summary-card-label">Action Items</span>
            <span class="badge badge-warning" style="font-size: 0.68rem;">Action Required</span>
        </div>
        <div class="summary-card-value"><?php echo $totalActionCount; ?></div>
        <div class="summary-card-sub">Pending inquiries &amp; fleet requests</div>
    </a>

    <!-- 4. Registered Clients -->
    <a href="<?php echo ADMIN_URL; ?>customers.php" class="summary-card">
        <div class="summary-card-top">
            <span class="summary-card-label">Registered Clients</span>
            <span class="badge badge-success" style="font-size: 0.68rem;">Verified</span>
        </div>
        <div class="summary-card-value"><?php echo $totalCustomers; ?></div>
        <div class="summary-card-sub"><strong><?php echo $verifiedCustomers; ?></strong> verified client accounts</div>
    </a>

</div>

<!-- =============================================================================
     WIDGET GRID 2: Fleet Utilization Chart (~55%) + Inquiry Trend (~45%)
     ============================================================================= -->
<div class="dashboard-row-split">
    
    <!-- Widget A: Fleet Utilization Breakdown (Donut Chart) -->
    <div class="clean-card">
        <div class="clean-card-header">
            <div>
                <div class="clean-card-title">Truck Fleet Allocation</div>
                <div class="clean-card-sub">Live stock distribution across availability states</div>
            </div>
            <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline btn-sm">Inventory &rarr;</a>
        </div>
        <div class="clean-card-body">
            <div class="donut-box">
                <canvas id="fleetDonutChart"></canvas>
                <div class="donut-center-overlay">
                    <div class="donut-center-num"><?php echo $totalTrucks; ?></div>
                    <div class="donut-center-txt">Fleet Units</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget B: Inquiry & Lead Volume Activity (Line Trend) -->
    <div class="clean-card">
        <div class="clean-card-header">
            <div>
                <div class="clean-card-title">Inquiry Activity Trend</div>
                <div class="clean-card-sub">Customer demand volume over the last 7 days</div>
            </div>
            <a href="<?php echo ADMIN_URL; ?>reports.php?type=inquiries" class="btn btn-outline btn-sm">Full Report &rarr;</a>
        </div>
        <div class="clean-card-body">
            <?php if (!empty($activityLabels)): ?>
                <div class="chart-box">
                    <canvas id="inquiryChart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-state-card" style="padding: 2rem;">
                    <div class="empty-state-title">No Inquiry Activity Recorded</div>
                    <div class="empty-state-text">Customer inquiries will generate live time-series charts here.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- =============================================================================
     WIDGET GRID 3: Action Queue (~50%) + Recent Activity Feed (~50%)
     ============================================================================= -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    
    <!-- Widget C: Action Queue (Pending Inquiries Awaiting Review) -->
    <div class="clean-card">
        <div class="clean-card-header">
            <div>
                <div class="clean-card-title">Action Queue</div>
                <div class="clean-card-sub">Pending client inquiries awaiting staff attention</div>
            </div>
            <span class="badge badge-warning"><?php echo count($pendingActionItems); ?> Pending</span>
        </div>
        
        <?php if (!empty($pendingActionItems)): ?>
            <div class="activity-list">
                <?php foreach ($pendingActionItems as $item): ?>
                    <a href="<?php echo ADMIN_URL; ?>inquiry-details.php?id=<?php echo (int)$item['id']; ?>" class="activity-item">
                        <div class="activity-item-left">
                            <div class="admin-avatar" style="width: 32px; height: 32px; font-size: 0.78rem;">
                                <?php echo strtoupper(substr($item['customer_name'], 0, 1)); ?>
                            </div>
                            <div style="min-width: 0;">
                                <div class="activity-item-title"><?php echo sanitize_output($item['customer_name']); ?></div>
                                <div class="activity-item-sub"><?php echo sanitize_output($item['inquiry_code']); ?> &bull; <?php echo sanitize_output($item['inquiry_type']); ?></div>
                            </div>
                        </div>
                        <div class="activity-item-right">
                            <span class="btn btn-outline btn-sm" style="font-size: 0.72rem; padding: 3px 8px;">Review &rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-card" style="padding: 2.5rem 1rem; border: none;">
                <div class="empty-state-title">All Caught Up</div>
                <div class="empty-state-text" style="margin-bottom: 0;">No pending inquiries awaiting staff response.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Widget D: Recent Activity Feed (Condensed Live Events) -->
    <div class="clean-card">
        <div class="clean-card-header">
            <div>
                <div class="clean-card-title">Recent Activity Feed</div>
                <div class="clean-card-sub">Real-time interactions across inquiries &amp; fleet requests</div>
            </div>
            <a href="<?php echo ADMIN_URL; ?>activities.php" class="btn btn-outline btn-sm">All Activities &rarr;</a>
        </div>

        <?php if (!empty($recentActivities)): ?>
            <div class="activity-list">
                <?php foreach ($recentActivities as $act): ?>
                    <a href="<?php echo ($act['act_type'] === 'inquiry') ? ADMIN_URL . 'inquiry-details.php?id=' . (int)$act['id'] : ADMIN_URL . 'request-details.php?id=' . (int)$act['id']; ?>" class="activity-item">
                        <div class="activity-item-left">
                            <div style="width: 28px; height: 28px; border-radius: 6px; background: <?php echo ($act['act_type'] === 'inquiry') ? 'var(--c-orange-light)' : '#EFF6FF'; ?>; color: <?php echo ($act['act_type'] === 'inquiry') ? 'var(--c-orange)' : '#1E40AF'; ?>; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800;">
                                <?php echo ($act['act_type'] === 'inquiry') ? 'INQ' : 'REQ'; ?>
                            </div>
                            <div style="min-width: 0;">
                                <div class="activity-item-title"><?php echo sanitize_output($act['customer_name'] ?? 'Client'); ?></div>
                                <div class="activity-item-sub"><?php echo sanitize_output($act['inquiry_code'] ?? $act['request_code'] ?? ''); ?></div>
                            </div>
                        </div>
                        <div class="activity-item-right">
                            <span class="badge badge-subtle"><?php echo sanitize_output($act['status']); ?></span>
                            <div class="activity-item-time"><?php echo date('M j, g:ia', strtotime($act['created_at'])); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-card" style="padding: 2.5rem 1rem; border: none;">
                <div class="empty-state-title">No Recent Activity</div>
                <div class="empty-state-text" style="margin-bottom: 0;">New customer events will appear here in real time.</div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Chart Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Inquiry Activity Line Chart
    var inqCanvas = document.getElementById('inquiryChart');
    if (inqCanvas) {
        var labels = <?php echo json_encode($activityLabels); ?>;
        var data = <?php echo json_encode($activityCounts); ?>;
        
        new Chart(inqCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Inquiries',
                    data: data,
                    borderColor: '#D9825B',
                    backgroundColor: 'rgba(217, 130, 91, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#D9825B',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0F172A',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 6
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#64748B' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#F1F5F9' },
                        ticks: { stepSize: 1, font: { size: 11 }, color: '#64748B' }
                    }
                }
            }
        });
    }

    // 2. Fleet Utilization Donut Chart
    var donutCanvas = document.getElementById('fleetDonutChart');
    if (donutCanvas) {
        new Chart(donutCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'Reserved', 'In Maintenance', 'Sold'],
                datasets: [{
                    data: [
                        <?php echo $statusCounts['Available']; ?>,
                        <?php echo $statusCounts['Reserved']; ?>,
                        <?php echo $statusCounts['Maintenance']; ?>,
                        <?php echo $statusCounts['Sold']; ?>
                    ],
                    backgroundColor: ['#0F172A', '#D9825B', '#94A3B8', '#CBD5E1'],
                    borderWidth: 2,
                    borderColor: '#FFFFFF',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 12,
                            font: { size: 11, weight: '600' },
                            color: '#64748B'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0F172A',
                        padding: 10,
                        cornerRadius: 6
                    }
                }
            }
        });
    }

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>