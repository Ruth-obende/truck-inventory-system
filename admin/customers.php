<?php
/**
 * =============================================================================
 * Moal General Suppliers - Client Information & Accounts Directory
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Client Information';
$db = getDB();

$searchKey = sanitize_input($_GET['search'] ?? '');
$statusFilter = sanitize_input($_GET['status'] ?? '');

$sql = "
    SELECT c.*,
    (SELECT COUNT(*) FROM inquiries WHERE customer_email = c.email) AS inquiry_count,
    (SELECT COUNT(*) FROM customer_requests WHERE customer_id = c.id) AS request_count
    FROM customers c
    WHERE 1=1
";
$params = [];

if (!empty($searchKey)) {
    $sql .= " AND (c.full_name LIKE :s1 OR c.email LIKE :s2 OR c.phone LIKE :s3 OR c.business_name LIKE :s4)";
    $like = '%' . $searchKey . '%';
    $params[':s1'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
    $params[':s4'] = $like;
}

if (!empty($statusFilter)) {
    if ($statusFilter === 'verified') {
        $sql .= " AND c.is_verified = 1";
    } elseif ($statusFilter === 'unverified') {
        $sql .= " AND c.is_verified = 0";
    }
}

$sql .= " ORDER BY c.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="moal_clients_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Full Name', 'Company', 'Email', 'Phone', 'Address', 'Verified', 'Inquiries Count', 'Requests Count', 'Joined Date']);
    foreach ($customers as $c) {
        fputcsv($output, [
            $c['id'],
            $c['full_name'],
            $c['business_name'] ?? 'N/A',
            $c['email'],
            $c['phone'],
            $c['delivery_address'] ?? 'N/A',
            $c['is_verified'] ? 'Verified' : 'Pending OTP',
            $c['inquiry_count'],
            $c['request_count'],
            $c['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$totalCust = (int)$db->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$verifiedCust = (int)$db->query('SELECT COUNT(*) FROM customers WHERE is_verified = 1')->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-heading-title">Client Information</h1>
        <div class="admin-heading-sub">Registered client directory, contact records, and fleet quote history</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="<?php echo ADMIN_URL; ?>customers.php?export=csv<?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchKey) ? '&search=' . urlencode($searchKey) : ''; ?>" class="btn btn-outline btn-sm">
            Download Client Records (CSV)
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>customers.php" style="display: grid; grid-template-columns: 2.5fr 1.5fr auto auto; gap: 10px; align-items: center;">
        <input type="text" name="search" class="form-control" placeholder="Search by customer name, email, phone, or company..." value="<?php echo sanitize_output($searchKey); ?>">

        <select name="status" class="form-control">
            <option value="">-- All Accounts (<?php echo $totalCust; ?>) --</option>
            <option value="verified" <?php echo ($statusFilter === 'verified') ? 'selected' : ''; ?>>Verified Clients (<?php echo $verifiedCust; ?>)</option>
            <option value="unverified" <?php echo ($statusFilter === 'unverified') ? 'selected' : ''; ?>>Pending Verification</option>
        </select>

        <button type="submit" class="btn btn-dark btn-sm" style="padding: 9px 16px;">Search</button>
        <a href="<?php echo ADMIN_URL; ?>customers.php" class="btn btn-outline btn-sm" style="padding: 9px 12px;">Reset</a>
    </form>
</div>

<!-- Customers Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Client Accounts (<?php echo count($customers); ?> Total)</div>
        <a href="<?php echo ADMIN_URL; ?>customers.php?export=csv<?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchKey) ? '&search=' . urlencode($searchKey) : ''; ?>" class="btn btn-outline btn-sm">
            Export CSV
        </a>
    </div>

    <?php if (!empty($customers)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Inquiries</th>
                        <th>Fleet Quotes</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td>
                                <strong><?php echo sanitize_output($c['full_name']); ?></strong>
                            </td>
                            <td>
                                <?php echo !empty($c['business_name']) ? sanitize_output($c['business_name']) : '<span style="color: var(--c-muted);">&mdash;</span>'; ?>
                            </td>
                            <td>
                                <a href="mailto:<?php echo sanitize_output($c['email']); ?>" style="color: var(--c-navy); text-decoration: none;">
                                    <?php echo sanitize_output($c['email']); ?>
                                </a>
                            </td>
                            <td>
                                <a href="tel:<?php echo sanitize_output($c['phone']); ?>" style="color: var(--c-navy); text-decoration: none;">
                                    <?php echo sanitize_output($c['phone']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-subtle"><?php echo (int)$c['inquiry_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?php echo (int)$c['request_count']; ?></span>
                            </td>
                            <td>
                                <?php if ($c['is_verified']): ?>
                                    <span class="badge badge-success">Verified</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending OTP</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--c-muted); white-space: nowrap;">
                                <?php echo date('M j, Y', strtotime($c['created_at'])); ?>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>customer-details.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-primary btn-sm">
                                    View Profile &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <div class="empty-state-title">No Clients Found</div>
            <div class="empty-state-text">
                <?php if (!empty($searchKey) || !empty($statusFilter)): ?>
                    No customer accounts match your active search filters. Try clearing your search parameters.
                <?php else: ?>
                    There are no registered client accounts in the system yet.
                <?php endif; ?>
            </div>
            <div class="empty-state-actions">
                <a href="<?php echo ADMIN_URL; ?>customers.php" class="btn btn-outline btn-sm">Reset Filters</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>