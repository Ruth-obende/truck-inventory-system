<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Inquiry Management & Tracking
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Manage Inquiries';
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
    $sql .= " AND (i.inquiry_code LIKE :s OR i.customer_name LIKE :s OR i.customer_email LIKE :s OR i.customer_phone LIKE :s OR i.message LIKE :s)";
    $params[':s'] = '%' . $searchKey . '%';
}

$sql .= " ORDER BY i.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

// Get status counts for filter tabs
$counts = [
    'All'         => (int)$db->query('SELECT COUNT(*) FROM inquiries')->fetchColumn(),
    'Pending'     => (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "Pending"')->fetchColumn(),
    'In Review'   => (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "In Review"')->fetchColumn(),
    'Contacted'   => (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "Contacted"')->fetchColumn(),
    'Resolved'    => (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "Resolved"')->fetchColumn()
];

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.4rem; color: var(--admin-navy);">Customer Inquiries &amp; Custom Requests</h2>
        <p style="color: var(--admin-text-muted); font-size: 0.9rem;">Track prospective buyer leads, inspection bookings, custom sourcing requests, and communication logs.</p>
    </div>
</div>

<!-- Status Filter Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <a href="<?php echo ADMIN_URL; ?>inquiries.php" class="btn btn-sm <?php echo empty($statusFilter) ? 'btn-primary' : 'btn-outline'; ?>">
        All (<?php echo $counts['All']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php?status=Pending" class="btn btn-sm <?php echo ($statusFilter === 'Pending') ? 'btn-primary' : 'btn-outline'; ?>">
        Pending (<?php echo $counts['Pending']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php?status=In+Review" class="btn btn-sm <?php echo ($statusFilter === 'In Review') ? 'btn-primary' : 'btn-outline'; ?>">
        In Review (<?php echo $counts['In Review']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php?status=Contacted" class="btn btn-sm <?php echo ($statusFilter === 'Contacted') ? 'btn-primary' : 'btn-outline'; ?>">
        Contacted (<?php echo $counts['Contacted']; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php?status=Resolved" class="btn btn-sm <?php echo ($statusFilter === 'Resolved') ? 'btn-primary' : 'btn-outline'; ?>">
        Resolved (<?php echo $counts['Resolved']; ?>)
    </a>
</div>

<!-- Search & Type Filter Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>inquiries.php" style="display: grid; grid-template-columns: 2fr 1.5fr auto auto; gap: 12px; align-items: center;">
        <?php if (!empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?php echo sanitize_output($statusFilter); ?>">
        <?php endif; ?>

        <input type="text" name="search" class="form-control" placeholder="Search by tracking code, customer name, phone..." value="<?php echo sanitize_output($searchKey); ?>">

        <select name="type" class="form-control">
            <option value="">-- All Inquiry Types --</option>
            <option value="Specific Truck" <?php echo ($typeFilter === 'Specific Truck') ? 'selected' : ''; ?>>Specific Truck Inquiry</option>
            <option value="Custom Request" <?php echo ($typeFilter === 'Custom Request') ? 'selected' : ''; ?>>Custom Procurement Request</option>
            <option value="Recommendation Followup" <?php echo ($typeFilter === 'Recommendation Followup') ? 'selected' : ''; ?>>Recommendation Followup</option>
            <option value="General Inquiry" <?php echo ($typeFilter === 'General Inquiry') ? 'selected' : ''; ?>>General Dealership Inquiry</option>
        </select>

        <button type="submit" class="btn btn-navy btn-sm" style="padding: 9px 16px;">Search</button>
        <a href="<?php echo ADMIN_URL; ?>inquiries.php" class="btn btn-outline btn-sm" style="padding: 9px 12px;">Reset</a>
    </form>
</div>

<!-- Inquiries Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Inquiry Records (<?php echo count($inquiries); ?> Total)</div>
    </div>

    <?php if (!empty($inquiries)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tracking Ref</th>
                    <th>Customer Details</th>
                    <th>Inquiry Nature</th>
                    <th>Linked Vehicle / Sourcing</th>
                    <th>Date Received</th>
                    <th>Workflow Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inquiries as $inq): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--admin-orange); font-size: 0.95rem;">
                                <?php echo sanitize_output($inq['inquiry_code']); ?>
                            </strong>
                        </td>

                        <td>
                            <strong><?php echo sanitize_output($inq['customer_name']); ?></strong>
                            <div style="font-size: 0.8rem; color: var(--admin-text-muted);">
                                📞 <?php echo sanitize_output($inq['customer_phone']); ?><br>
                                ✉️ <?php echo sanitize_output($inq['customer_email']); ?>
                            </div>
                        </td>

                        <td>
                            <span class="badge badge-navy"><?php echo sanitize_output($inq['inquiry_type']); ?></span>
                        </td>

                        <td>
                            <?php if (!empty($inq['truck_code'])): ?>
                                <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$inq['truck_id']; ?>" target="_blank" style="font-weight: 600;">
                                    <?php echo sanitize_output($inq['truck_code']); ?>
                                </a>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?php echo sanitize_output($inq['truck_title']); ?></div>
                            <?php else: ?>
                                <span style="color: var(--admin-orange); font-weight: 600; font-size: 0.85rem;">Custom Request</span>
                                <?php if ($inq['preferred_tonnage']): ?>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);">Target: <?php echo format_tonnage($inq['preferred_tonnage']); ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>

                        <td style="font-size: 0.85rem; color: var(--admin-text-muted); white-space: nowrap;">
                            <?php echo date('M j, Y &bull; g:ia', strtotime($inq['created_at'])); ?>
                        </td>

                        <td>
                            <?php 
                                $statusClass = 'badge-warning';
                                if ($inq['status'] === 'Resolved') $statusClass = 'badge-success';
                                elseif ($inq['status'] === 'In Review') $statusClass = 'badge-orange';
                                elseif ($inq['status'] === 'Contacted') $statusClass = 'badge-navy';
                                elseif ($inq['status'] === 'Cancelled') $statusClass = 'badge-danger';
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo sanitize_output($inq['status']); ?></span>
                        </td>

                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?php echo ADMIN_URL; ?>inquiry-details.php?id=<?php echo (int)$inq['id']; ?>" class="btn btn-primary btn-sm">
                                Review Lead &rarr;
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: var(--admin-text-muted); text-align: center; padding: 2.5rem 0;">No customer inquiries found for this filter.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
