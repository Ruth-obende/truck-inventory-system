<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Inquiry Management & Tracking
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Customer Inquiries & Leads';
$db = getDB();

$statusFilter = sanitize_input($_GET['status'] ?? '');
$typeFilter   = sanitize_input($_GET['type'] ?? '');
$searchKey    = sanitize_input($_GET['search'] ?? '');

$sql = "
    SELECT i.*, t.truck_code, t.title AS truck_title 
    FROM inquiries i 
    LEFT JOIN trucks t ON i.truck_id = t.id 
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND i.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($typeFilter)) {
    $sql .= " AND i.inquiry_type = :type";
    $params[':type'] = $typeFilter;
}

if (!empty($searchKey)) {
    $sql .= " AND (i.inquiry_code LIKE :s1 OR i.customer_name LIKE :s2 OR i.customer_email LIKE :s3 OR i.customer_phone LIKE :s4 OR i.message LIKE :s5)";
    $like = '%' . $searchKey . '%';
    $params[':s1'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
    $params[':s4'] = $like;
    $params[':s5'] = $like;
}

$sql .= " ORDER BY i.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="moal_inquiries_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Inquiry Code', 'Customer Name', 'Email', 'Phone', 'Type', 'Target Vehicle', 'Status', 'Message', 'Date Received']);
    foreach ($inquiries as $row) {
        fputcsv($output, [
            $row['id'],
            $row['inquiry_code'],
            $row['customer_name'],
            $row['customer_email'],
            $row['customer_phone'],
            $row['inquiry_type'],
            $row['truck_title'] ? $row['truck_code'] . ' - ' . $row['truck_title'] : 'Custom Sourcing',
            $row['status'],
            preg_replace('/\s+/', ' ', $row['message']),
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Get counts for tabs
$counts = [
    'All'         => (int)$db->query('SELECT COUNT(*) FROM inquiries')->fetchColumn(),
    'Pending'     => (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "Pending"')->fetchColumn(),
    'In Review'   => (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "In Review"')->fetchColumn(),
    'Contacted'   => (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "Contacted"')->fetchColumn(),
    'Resolved'    => (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "Resolved"')->fetchColumn(),
    'Cancelled'   => (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "Cancelled"')->fetchColumn()
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Clean Bold Header (No Paragraph Text) -->
<div class="admin-page-header">
    <div>
        <h1 class="admin-heading-title">Customer Inquiries &amp; Leads</h1>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="<?php echo ADMIN_URL; ?>inquiries.php?export=csv<?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchKey) ? '&search=' . urlencode($searchKey) : ''; ?>" class="btn btn-outline btn-sm">
            Download Inquiry Records (CSV)
        </a>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="filter-tabs">
    <a href="<?php echo ADMIN_URL; ?>inquiries.php" class="filter-tab <?php echo empty($statusFilter) ? 'active' : ''; ?>">
        All (<?php echo $counts['All']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php?status=Pending" class="filter-tab <?php echo ($statusFilter === 'Pending') ? 'active' : ''; ?>">
        Pending (<?php echo $counts['Pending']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php?status=In+Review" class="filter-tab <?php echo ($statusFilter === 'In Review') ? 'active' : ''; ?>">
        In Review (<?php echo $counts['In Review']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php?status=Contacted" class="filter-tab <?php echo ($statusFilter === 'Contacted') ? 'active' : ''; ?>">
        Contacted (<?php echo $counts['Contacted']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php?status=Resolved" class="filter-tab <?php echo ($statusFilter === 'Resolved') ? 'active' : ''; ?>">
        Resolved (<?php echo $counts['Resolved']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php?status=Cancelled" class="filter-tab <?php echo ($statusFilter === 'Cancelled') ? 'active' : ''; ?>">
        Cancelled (<?php echo $counts['Cancelled']; ?>)
    </a>
</div>

<!-- Search & Type Filter Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>inquiries.php" style="display: grid; grid-template-columns: 2fr 1.5fr auto auto; gap: 10px; align-items: center;">
        <?php if (!empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?php echo sanitize_output($statusFilter); ?>">
        <?php endif; ?>

        <input type="text" name="search" class="form-control" placeholder="Search by tracking code, customer name, phone, email..." value="<?php echo sanitize_output($searchKey); ?>">

        <select name="type" class="form-control">
            <option value="">-- All Inquiry Types --</option>
            <option value="Specific Truck" <?php echo ($typeFilter === 'Specific Truck') ? 'selected' : ''; ?>>Specific Truck Inquiry</option>
            <option value="Custom Request" <?php echo ($typeFilter === 'Custom Request') ? 'selected' : ''; ?>>Custom Procurement Request</option>
            <option value="Recommendation Followup" <?php echo ($typeFilter === 'Recommendation Followup') ? 'selected' : ''; ?>>Recommendation Followup</option>
            <option value="General Inquiry" <?php echo ($typeFilter === 'General Inquiry') ? 'selected' : ''; ?>>General Dealership Inquiry</option>
        </select>

        <button type="submit" class="btn btn-dark btn-sm" style="padding: 9px 14px;">Filter</button>
        <a href="<?php echo ADMIN_URL; ?>inquiries.php" class="btn btn-outline btn-sm" style="padding: 9px 12px;">Reset</a>
    </form>
</div>

<!-- Inquiries Table Card -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Inquiry Records (<?php echo count($inquiries); ?> Total)</div>
        <a href="<?php echo ADMIN_URL; ?>inquiries.php?export=csv<?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchKey) ? '&search=' . urlencode($searchKey) : ''; ?>" class="btn btn-outline btn-sm">
            Export Records
        </a>
    </div>

    <?php if (!empty($inquiries)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ref Code</th>
                        <th>Customer</th>
                        <th>Phone / Email</th>
                        <th>Type</th>
                        <th>Target Vehicle</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inquiries as $inq): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--admin-orange); font-family: monospace; font-size: 0.95rem;">
                                    <?php echo sanitize_output($inq['inquiry_code']); ?>
                                </strong>
                            </td>
                            <td>
                                <strong><?php echo sanitize_output($inq['customer_name']); ?></strong>
                            </td>
                            <td>
                                <div>
                                    <a href="tel:<?php echo sanitize_output($inq['customer_phone']); ?>" style="color: var(--admin-navy); font-weight: 600; text-decoration: none;">
                                        <?php echo sanitize_output($inq['customer_phone']); ?>
                                    </a>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);">
                                    <?php echo sanitize_output($inq['customer_email']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-subtle"><?php echo sanitize_output($inq['inquiry_type']); ?></span>
                            </td>
                            <td>
                                <?php if (!empty($inq['truck_code'])): ?>
                                    <span style="font-weight: 600; color: var(--admin-navy);"><?php echo sanitize_output($inq['truck_code']); ?></span>
                                    <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?php echo sanitize_output($inq['truck_title']); ?></div>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-muted); font-style: italic;">Custom Sourcing</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $inqBadge = match($inq['status']) {
                                        'Pending' => 'badge-warning',
                                        'In Review' => 'badge-primary',
                                        'Contacted' => 'badge-dark',
                                        'Resolved' => 'badge-success',
                                        'Cancelled' => 'badge-danger',
                                        default => 'badge-subtle'
                                    };
                                ?>
                                <span class="badge <?php echo $inqBadge; ?>"><?php echo sanitize_output($inq['status']); ?></span>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--admin-text-muted); white-space: nowrap;">
                                <?php echo date('M j, Y - g:ia', strtotime($inq['created_at'])); ?>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>inquiry-details.php?id=<?php echo (int)$inq['id']; ?>" class="btn btn-primary btn-sm">
                                    Review &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <div class="empty-state-title">No Inquiries Found</div>
            <div class="empty-state-text">
                <?php if (!empty($searchKey) || !empty($statusFilter) || !empty($typeFilter)): ?>
                    No customer inquiries match your active search or filter criteria. Try resetting filters.
                <?php else: ?>
                    There are no customer inquiries logged in the system yet.
                <?php endif; ?>
            </div>
            <div class="empty-state-actions">
                <a href="<?php echo ADMIN_URL; ?>inquiries.php" class="btn btn-outline btn-sm">Reset Filters</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>