<?php
/**
 * =============================================================================
 * Moal General Suppliers - Dealership Operations & Financial Reports
 * =============================================================================
 * Generates comprehensive printable and downloadable CSV/PDF business reports.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Dealership Reports';
$db = getDB();

$reportType = sanitize_input($_GET['type'] ?? 'inventory');
$validTypes = ['inventory', 'sales', 'inquiries', 'activity'];
if (!in_array($reportType, $validTypes)) {
    $reportType = 'inventory';
}

$dateRange = sanitize_input($_GET['range'] ?? 'all');
$validRanges = ['all', 'year', 'month', 'week', 'today', 'custom'];
if (!in_array($dateRange, $validRanges)) {
    $dateRange = 'all';
}
$startDate = sanitize_input($_GET['start_date'] ?? $_GET['from'] ?? '');
$endDate   = sanitize_input($_GET['end_date'] ?? $_GET['to'] ?? '');

$dateSql = get_date_filter_sql('created_at');

// -----------------------------------------------------------------------------
// Fetch Data based on Report Type
// -----------------------------------------------------------------------------
$reportData = [];
$reportTitle = '';
$summaryStats = [];

if ($reportType === 'inventory') {
    $reportTitle = 'Fleet Inventory & Asset Valuation Report';
    $stmt = $db->query("SELECT * FROM trucks WHERE 1=1 $dateSql ORDER BY price DESC");
    $reportData = $stmt->fetchAll();

    $totalVal = array_sum(array_column($reportData, 'price'));
    $availCount = count(array_filter($reportData, function($t) { return $t['availability_status'] === 'Available'; }));
    $resCount   = count(array_filter($reportData, function($t) { return $t['availability_status'] === 'Reserved'; }));
    $soldCount  = count(array_filter($reportData, function($t) { return $t['availability_status'] === 'Sold'; }));

    $summaryStats = [
        ['label' => 'Total Fleet Units', 'val' => count($reportData)],
        ['label' => 'Total Asset Valuation', 'val' => format_currency($totalVal)],
        ['label' => 'Available for Sale', 'val' => $availCount . ' Units'],
        ['label' => 'Reserved / In-Process', 'val' => $resCount . ' Units']
    ];

} elseif ($reportType === 'sales') {
    $reportTitle = 'Dealership Sales & Reserved Units Report';
    $stmt = $db->query("SELECT * FROM trucks WHERE availability_status IN ('Sold', 'Reserved') $dateSql ORDER BY updated_at DESC");
    $reportData = $stmt->fetchAll();

    $soldVal = array_sum(array_column($reportData, 'price'));
    $soldOnly = count(array_filter($reportData, function($t) { return $t['availability_status'] === 'Sold'; }));

    $summaryStats = [
        ['label' => 'Completed Deals (Sold)', 'val' => $soldOnly],
        ['label' => 'Total Pipeline Volume', 'val' => count($reportData) . ' Units'],
        ['label' => 'Gross Realized Value', 'val' => format_currency($soldVal)],
        ['label' => 'Avg Deal Price', 'val' => count($reportData) > 0 ? format_currency($soldVal / count($reportData)) : '₦0']
    ];

} elseif ($reportType === 'inquiries') {
    $reportTitle = 'Client Inquiries & Lead Conversion Report';
    $stmt = $db->query("SELECT i.*, t.truck_code, t.title AS truck_title FROM inquiries i LEFT JOIN trucks t ON i.truck_id = t.id WHERE 1=1 $dateSql ORDER BY i.id DESC");
    $reportData = $stmt->fetchAll();

    $resolvedCount = count(array_filter($reportData, function($i) { return $i['status'] === 'Resolved'; }));
    $pendingCount  = count(array_filter($reportData, function($i) { return $i['status'] === 'Pending'; }));

    $summaryStats = [
        ['label' => 'Total Lead Volume', 'val' => count($reportData)],
        ['label' => 'Resolved Quotes', 'val' => $resolvedCount],
        ['label' => 'Pending Review', 'val' => $pendingCount],
        ['label' => 'Resolution Rate', 'val' => count($reportData) > 0 ? round(($resolvedCount / count($reportData)) * 100) . '%' : '0%']
    ];

} elseif ($reportType === 'activity') {
    $reportTitle = 'System Operations & Audit Trail Report';
    $stmt = $db->query("SELECT id, inquiry_code AS ref_code, customer_name, 'Inquiry' AS event_type, status, created_at FROM inquiries WHERE 1=1 $dateSql ORDER BY created_at DESC LIMIT 100");
    $reportData = $stmt->fetchAll();

    $summaryStats = [
        ['label' => 'Total Logged Events', 'val' => count($reportData)],
        ['label' => 'Report Timeframe', 'val' => ucfirst($dateRange)],
        ['label' => 'Active Admin User', 'val' => sanitize_output($adminDisplayName ?? 'Admin')],
        ['label' => 'Generated At', 'val' => date('M j, Y')]
    ];
}

// -----------------------------------------------------------------------------
// Handle CSV Download
// -----------------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="moal_report_' . $reportType . '_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');

    if ($reportType === 'inventory' || $reportType === 'sales') {
        fputcsv($output, ['Stock Code', 'Title', 'Brand', 'Model', 'Year', 'Category', 'Tonnage', 'Status', 'Price (NGN)', 'Created Date']);
        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['truck_code'],
                $row['title'],
                $row['brand'],
                $row['model'],
                $row['year_of_manufacture'],
                $row['purpose_category'],
                $row['tonnage_capacity'],
                $row['availability_status'],
                $row['price'],
                $row['created_at']
            ]);
        }
    } elseif ($reportType === 'inquiries') {
        fputcsv($output, ['Inquiry Code', 'Customer Name', 'Email', 'Phone', 'Inquiry Type', 'Vehicle', 'Status', 'Received Date']);
        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['inquiry_code'],
                $row['customer_name'],
                $row['customer_email'],
                $row['customer_phone'],
                $row['inquiry_type'],
                $row['truck_title'] ? $row['truck_code'] . ' - ' . $row['truck_title'] : 'Custom Sourcing',
                $row['status'],
                $row['created_at']
            ]);
        }
    } elseif ($reportType === 'activity') {
        fputcsv($output, ['ID', 'Reference', 'Customer Name', 'Event Type', 'Status', 'Timestamp']);
        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['id'],
                $row['ref_code'],
                $row['customer_name'],
                $row['event_type'],
                $row['status'],
                $row['created_at']
            ]);
        }
    }

    fclose($output);
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Print-Only Dealership Header -->
<div class="print-only-header">
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 2px; color: #000;"><?php echo APP_NAME; ?></h1>
            <div style="font-size: 0.85rem; color: #555;"><?php echo APP_TAGLINE; ?> &bull; Internal Management Report</div>
        </div>
        <div style="text-align: right; font-size: 0.8rem; color: #555;">
            <strong>Generated Date:</strong> <?php echo date('F j, Y - g:ia'); ?><br>
            <strong>Report Scope:</strong> <?php echo $reportTitle; ?> (<?php echo ucfirst($dateRange); ?>)
        </div>
    </div>
</div>

<!-- On-Screen Page Header with Actions -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-heading-title">Dealership Reports</h1>
        <div class="admin-heading-sub">Generate, print, and export official inventory, valuation, and sales conversion ledgers</div>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline" onclick="window.print()" title="Print formal report">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print Report
        </button>
        <a href="<?php echo ADMIN_URL; ?>reports.php?export=csv&type=<?php echo urlencode($reportType); ?>&range=<?php echo urlencode($dateRange); ?>" class="btn btn-primary" title="Export report data to CSV">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Download CSV Export
        </a>
    </div>
</div>

<!-- Report Type Tabs -->
<div class="filter-tabs no-print">
    <a href="<?php echo ADMIN_URL; ?>reports.php?type=inventory&range=<?php echo urlencode($dateRange); ?>" class="filter-tab <?php echo ($reportType === 'inventory') ? 'active' : ''; ?>">
        Fleet Inventory Valuation
    </a>
    <a href="<?php echo ADMIN_URL; ?>reports.php?type=sales&range=<?php echo urlencode($dateRange); ?>" class="filter-tab <?php echo ($reportType === 'sales') ? 'active' : ''; ?>">
        Sales &amp; Deals Pipeline
    </a>
    <a href="<?php echo ADMIN_URL; ?>reports.php?type=inquiries&range=<?php echo urlencode($dateRange); ?>" class="filter-tab <?php echo ($reportType === 'inquiries') ? 'active' : ''; ?>">
        Inquiry &amp; Leads Summary
    </a>
    <a href="<?php echo ADMIN_URL; ?>reports.php?type=activity&range=<?php echo urlencode($dateRange); ?>" class="filter-tab <?php echo ($reportType === 'activity') ? 'active' : ''; ?>">
        Operations Audit Log
    </a>
</div>

<!-- Report Filter Controls -->
<div class="admin-card no-print" style="padding: 1.1rem 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>reports.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <input type="hidden" name="type" value="<?php echo sanitize_output($reportType); ?>">

        <label style="font-weight: 700; font-size: 0.85rem; color: var(--c-navy);">Timeframe Scope:</label>
        
        <select name="range" class="form-control" style="width: auto; min-width: 170px;" onchange="if(this.value !== 'custom') this.form.submit()">
            <option value="all" <?php echo ($dateRange === 'all') ? 'selected' : ''; ?>>All Time Historical</option>
            <option value="today" <?php echo ($dateRange === 'today') ? 'selected' : ''; ?>>Today's Records</option>
            <option value="week" <?php echo ($dateRange === 'week') ? 'selected' : ''; ?>>Last 7 Days</option>
            <option value="month" <?php echo ($dateRange === 'month') ? 'selected' : ''; ?>>Last 30 Days</option>
            <option value="year" <?php echo ($dateRange === 'year') ? 'selected' : ''; ?>>Past 365 Days</option>
            <option value="custom" <?php echo ($dateRange === 'custom' || !empty($startDate) || !empty($endDate)) ? 'selected' : ''; ?>>Custom Date Range</option>
        </select>

        <div style="display: flex; align-items: center; gap: 8px;">
            <input type="date" name="start_date" class="form-control" style="width: auto;" value="<?php echo sanitize_output($startDate); ?>" placeholder="From">
            <span style="color: var(--c-muted);">&ndash;</span>
            <input type="date" name="end_date" class="form-control" style="width: auto;" value="<?php echo sanitize_output($endDate); ?>" placeholder="To">
            <button type="submit" class="btn btn-dark btn-sm" style="padding: 8px 14px;">Apply</button>
            <a href="<?php echo ADMIN_URL; ?>reports.php?type=<?php echo urlencode($reportType); ?>&range=all" class="btn btn-outline btn-sm" style="padding: 8px 12px;">Reset</a>
        </div>
    </form>
</div>

<!-- Key Report Executive Metrics -->
<div class="report-summary-strip">
    <?php foreach ($summaryStats as $st): ?>
        <div class="report-summary-box">
            <div class="report-summary-label"><?php echo sanitize_output($st['label']); ?></div>
            <div class="report-summary-val"><?php echo $st['val']; ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Active Report Table Ledger -->
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <div class="admin-card-title"><?php echo sanitize_output($reportTitle); ?></div>
            <div class="admin-card-sub">Showing <?php echo count($reportData); ?> consolidated record entries</div>
        </div>
        <span class="badge badge-subtle"><?php echo strtoupper($reportType); ?></span>
    </div>

    <?php if (!empty($reportData)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <?php if ($reportType === 'inventory' || $reportType === 'sales'): ?>
                    <thead>
                        <tr>
                            <th>Stock Code</th>
                            <th>Vehicle Name</th>
                            <th>Category</th>
                            <th>Capacity</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Listed Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><strong style="font-family: monospace; color: var(--c-navy);"><?php echo sanitize_output($row['truck_code']); ?></strong></td>
                                <td>
                                    <strong><?php echo sanitize_output($row['title']); ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--c-muted);"><?php echo sanitize_output($row['brand'] . ' ' . $row['model']); ?> &bull; <?php echo (int)$row['year_of_manufacture']; ?></div>
                                </td>
                                <td><span class="badge badge-subtle"><?php echo sanitize_output($row['purpose_category']); ?></span></td>
                                <td><?php echo format_tonnage($row['tonnage_capacity']); ?></td>
                                <td><?php echo sanitize_output($row['condition_type']); ?></td>
                                <td>
                                    <?php 
                                        $bClass = match($row['availability_status']) {
                                            'Available' => 'badge-success',
                                            'Reserved' => 'badge-warning',
                                            'Sold' => 'badge-subtle',
                                            'Maintenance' => 'badge-subtle',
                                            default => 'badge-subtle'
                                        };
                                    ?>
                                    <span class="badge <?php echo $bClass; ?>"><?php echo sanitize_output($row['availability_status']); ?></span>
                                </td>
                                <td><strong style="color: var(--c-navy);"><?php echo format_currency($row['price']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                <?php elseif ($reportType === 'inquiries'): ?>
                    <thead>
                        <tr>
                            <th>Ref Code</th>
                            <th>Customer Name</th>
                            <th>Contact Phone</th>
                            <th>Email</th>
                            <th>Inquiry Type</th>
                            <th>Target Stock</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><strong style="font-family: monospace; color: var(--c-orange);"><?php echo sanitize_output($row['inquiry_code']); ?></strong></td>
                                <td><strong><?php echo sanitize_output($row['customer_name']); ?></strong></td>
                                <td><?php echo sanitize_output($row['customer_phone']); ?></td>
                                <td><?php echo sanitize_output($row['customer_email']); ?></td>
                                <td><span class="badge badge-subtle"><?php echo sanitize_output($row['inquiry_type']); ?></span></td>
                                <td><?php echo !empty($row['truck_title']) ? sanitize_output($row['truck_code'] . ' - ' . $row['truck_title']) : 'Custom Procurement'; ?></td>
                                <td><span class="badge badge-subtle"><?php echo sanitize_output($row['status']); ?></span></td>
                                <td style="font-size: 0.82rem; color: var(--c-muted);"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                <?php elseif ($reportType === 'activity'): ?>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reference</th>
                            <th>Customer Name</th>
                            <th>Event Category</th>
                            <th>Status</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td>#<?php echo (int)$row['id']; ?></td>
                                <td><strong><?php echo sanitize_output($row['ref_code']); ?></strong></td>
                                <td><?php echo sanitize_output($row['customer_name']); ?></td>
                                <td><span class="badge badge-primary"><?php echo sanitize_output($row['event_type']); ?></span></td>
                                <td><span class="badge badge-subtle"><?php echo sanitize_output($row['status']); ?></span></td>
                                <td style="font-size: 0.82rem; color: var(--c-muted);"><?php echo date('M j, Y - g:ia', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <div class="empty-state-title">No Records in Selected Reporting Scope</div>
            <div class="empty-state-text">There are no matching transaction or inventory records recorded for the "<?php echo ucfirst($dateRange); ?>" period.</div>
            <div class="empty-state-actions">
                <a href="<?php echo ADMIN_URL; ?>reports.php?type=<?php echo urlencode($reportType); ?>&range=all" class="btn btn-outline btn-sm">View All Time Data</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
