<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Fleet Requests Management
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Fleet Quote Requests';
$db = getDB();

$statusFilter = sanitize_input($_GET['status'] ?? '');
$searchKey    = sanitize_input($_GET['search'] ?? '');

$sql = "
    SELECT r.*, c.full_name, c.phone, c.email, c.business_name,
    (SELECT COUNT(*) FROM request_items WHERE request_id = r.id) AS item_count
    FROM customer_requests r
    JOIN customers c ON r.customer_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND r.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($searchKey)) {
    $sql .= " AND (r.request_code LIKE :s1 OR c.full_name LIKE :s2 OR c.phone LIKE :s3 OR c.email LIKE :s4 OR c.business_name LIKE :s5)";
    $like = '%' . $searchKey . '%';
    $params[':s1'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
    $params[':s4'] = $like;
    $params[':s5'] = $like;
}

$sql .= " ORDER BY r.id DESC";

$requests = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('[Admin Requests Query Error] ' . $e->getMessage() . ' | SQL: ' . $sql);
    $requests = [];
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="moal_fleet_requests_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Request Code', 'Customer Name', 'Company', 'Phone', 'Email', 'Truck Units', 'Assigned Agent', 'Status', 'Date']);
    foreach ($requests as $r) {
        fputcsv($output, [
            $r['id'],
            $r['request_code'],
            $r['full_name'],
            $r['business_name'] ?? 'N/A',
            $r['phone'],
            $r['email'],
            $r['item_count'],
            $r['assigned_agent'] ?? 'Unassigned',
            $r['status'],
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Counts
$reqCounts = [
    'All'               => (int)$db->query('SELECT COUNT(*) FROM customer_requests')->fetchColumn(),
    'New'               => (int)$db->query('SELECT COUNT(*) FROM customer_requests WHERE status = "New"')->fetchColumn(),
    'Assigned to Agent' => (int)$db->query('SELECT COUNT(*) FROM customer_requests WHERE status = "Assigned to Agent"')->fetchColumn(),
    'Contacted'         => (int)$db->query('SELECT COUNT(*) FROM customer_requests WHERE status = "Contacted"')->fetchColumn(),
    'Closed'            => (int)$db->query('SELECT COUNT(*) FROM customer_requests WHERE status = "Closed"')->fetchColumn()
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-heading-title">Customer Fleet Quote Requests</h1>
        <div class="admin-heading-sub">Manage multi-vehicle sourcing requests, assigned sales consultants, and deal progress</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="<?php echo ADMIN_URL; ?>requests.php?export=csv<?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchKey) ? '&search=' . urlencode($searchKey) : ''; ?>" class="btn btn-outline btn-sm">
            Download Requests (CSV)
        </a>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="filter-tabs">
    <a href="<?php echo ADMIN_URL; ?>requests.php" class="filter-tab <?php echo empty($statusFilter) ? 'active' : ''; ?>">
        All (<?php echo $reqCounts['All']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>requests.php?status=New" class="filter-tab <?php echo ($statusFilter === 'New') ? 'active' : ''; ?>">
        New (<?php echo $reqCounts['New']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>requests.php?status=Assigned+to+Agent" class="filter-tab <?php echo ($statusFilter === 'Assigned to Agent') ? 'active' : ''; ?>">
        Assigned (<?php echo $reqCounts['Assigned to Agent']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>requests.php?status=Contacted" class="filter-tab <?php echo ($statusFilter === 'Contacted') ? 'active' : ''; ?>">
        Contacted (<?php echo $reqCounts['Contacted']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>requests.php?status=Closed" class="filter-tab <?php echo ($statusFilter === 'Closed') ? 'active' : ''; ?>">
        Closed (<?php echo $reqCounts['Closed']; ?>)
    </a>
</div>

<!-- Search Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>requests.php" style="display: grid; grid-template-columns: 3fr auto auto; gap: 10px; align-items: center;">
        <?php if (!empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?php echo sanitize_output($statusFilter); ?>">
        <?php endif; ?>

        <input type="text" name="search" class="form-control" placeholder="Search by request ref, customer name, company, phone..." value="<?php echo sanitize_output($searchKey); ?>">

        <button type="submit" class="btn btn-dark btn-sm" style="padding: 9px 16px;">Search</button>
        <a href="<?php echo ADMIN_URL; ?>requests.php" class="btn btn-outline btn-sm" style="padding: 9px 12px;">Reset</a>
    </form>
</div>

<!-- Requests Table Card -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Fleet Requests Queue (<?php echo count($requests); ?> Found)</div>
        <a href="<?php echo ADMIN_URL; ?>requests.php?export=csv<?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchKey) ? '&search=' . urlencode($searchKey) : ''; ?>" class="btn btn-outline btn-sm">
            Export CSV
        </a>
    </div>

    <?php if (!empty($requests)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ref Code</th>
                        <th>Customer / Company</th>
                        <th>Phone</th>
                        <th>Items</th>
                        <th>Assigned Agent</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--c-orange); font-family: monospace; font-size: 0.95rem;">
                                    <?php echo sanitize_output($req['request_code']); ?>
                                </strong>
                            </td>
                            <td>
                                <strong><?php echo sanitize_output($req['full_name']); ?></strong>
                                <?php if (!empty($req['business_name'])): ?>
                                    <div style="font-size: 0.78rem; color: var(--c-muted);"><?php echo sanitize_output($req['business_name']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo sanitize_output($req['phone']); ?></td>
                            <td><span class="badge badge-primary"><?php echo (int)$req['item_count']; ?> Trucks</span></td>
                            <td>
                                <?php if (!empty($req['assigned_agent'])): ?>
                                    <span style="font-weight: 600; color: var(--c-navy);"><?php echo sanitize_output($req['assigned_agent']); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--c-muted); font-style: italic;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $reqBadge = match($req['status']) {
                                        'New' => 'badge-warning',
                                        'Assigned to Agent' => 'badge-primary',
                                        'Contacted' => 'badge-dark',
                                        'Closed' => 'badge-success',
                                        default => 'badge-subtle'
                                    };
                                ?>
                                <span class="badge <?php echo $reqBadge; ?>"><?php echo sanitize_output($req['status']); ?></span>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--c-muted); white-space: nowrap;">
                                <?php echo date('M j, Y', strtotime($req['created_at'])); ?>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>request-details.php?id=<?php echo (int)$req['id']; ?>" class="btn btn-primary btn-sm">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <div class="empty-state-title">No Fleet Requests Found</div>
            <div class="empty-state-text">
                <?php if (!empty($searchKey) || !empty($statusFilter)): ?>
                    No fleet quote requests match your selected filters. Try clearing your search parameters.
                <?php else: ?>
                    Customer fleet procurement requests will be listed here.
                <?php endif; ?>
            </div>
            <div class="empty-state-actions">
                <a href="<?php echo ADMIN_URL; ?>requests.php" class="btn btn-outline btn-sm">Reset Filters</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>