<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Detailed Profile & Transaction History
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($customerId <= 0) {
    redirect(ADMIN_URL . 'customers.php');
}

$db = getDB();

// Fetch Customer Info
$stmtCust = $db->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
$stmtCust->execute([':id' => $customerId]);
$customer = $stmtCust->fetch();

if (!$customer) {
    set_flash_message('error', 'Customer profile not found.');
    redirect(ADMIN_URL . 'customers.php');
}

// Fetch Customer's Inquiries (by email)
$stmtInq = $db->prepare('
    SELECT i.*, t.truck_code, t.title AS truck_title
    FROM inquiries i
    LEFT JOIN trucks t ON i.truck_id = t.id
    WHERE i.customer_email = :email
    ORDER BY i.id DESC
');
$stmtInq->execute([':email' => $customer['email']]);
$customerInquiries = $stmtInq->fetchAll();

// Fetch Customer's Fleet Requests
$stmtReq = $db->prepare('
    SELECT r.*,
    (SELECT COUNT(*) FROM request_items WHERE request_id = r.id) AS item_count
    FROM customer_requests r
    WHERE r.customer_id = :cid
    ORDER BY r.id DESC
');
$stmtReq->execute([':cid' => $customerId]);
$customerRequests = $stmtReq->fetchAll();

$pageTitle = 'Client: ' . $customer['full_name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <div style="font-size: 0.85rem; color: var(--c-muted); margin-bottom: 4px;">
            <a href="<?php echo ADMIN_URL; ?>customers.php" style="color: var(--c-muted);">Client Information</a> &rsaquo; <span><?php echo sanitize_output($customer['full_name']); ?></span>
        </div>
        <h1 class="admin-heading-title">
            Client Profile: <span style="color: var(--c-orange);"><?php echo sanitize_output($customer['full_name']); ?></span>
        </h1>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="<?php echo ADMIN_URL; ?>customers.php" class="btn btn-outline">
            &larr; Back to Client Directory
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem; align-items: flex-start;">
    
    <!-- Left Column: Customer Profile Card -->
    <div class="admin-card">
        <div style="text-align: center; padding: 1.5rem 1.25rem 1.25rem; border-bottom: 1px solid var(--c-border-light);">
            <div class="admin-avatar" style="width: 60px; height: 60px; font-size: 1.5rem; margin: 0 auto 0.75rem auto;">
                <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
            </div>
            <h2 style="font-size: 1.2rem; color: var(--c-navy); margin-bottom: 4px; font-weight: 700;">
                <?php echo sanitize_output($customer['full_name']); ?>
            </h2>
            <?php if (!empty($customer['business_name'])): ?>
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--c-orange);"><?php echo sanitize_output($customer['business_name']); ?></div>
            <?php endif; ?>
            <div style="margin-top: 8px;">
                <?php if ($customer['is_verified']): ?>
                    <span class="badge badge-success">Email Verified</span>
                <?php else: ?>
                    <span class="badge badge-warning">Pending Verification</span>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding: 1.25rem; font-size: 0.9rem; line-height: 1.9; color: var(--c-slate);">
            <div>
                <strong style="color: var(--c-muted); font-size: 0.75rem; text-transform: uppercase; display: block;">Email:</strong>
                <a href="mailto:<?php echo sanitize_output($customer['email']); ?>" style="color: var(--c-navy); font-weight: 600; text-decoration: none;">
                    <?php echo sanitize_output($customer['email']); ?>
                </a>
            </div>

            <div style="margin-top: 8px;">
                <strong style="color: var(--c-muted); font-size: 0.75rem; text-transform: uppercase; display: block;">Phone Number:</strong>
                <a href="tel:<?php echo sanitize_output($customer['phone']); ?>" style="color: var(--c-orange); font-weight: 600; text-decoration: none;">
                    <?php echo sanitize_output($customer['phone']); ?>
                </a>
            </div>

            <?php if (!empty($customer['delivery_address'])): ?>
                <div style="margin-top: 8px;">
                    <strong style="color: var(--c-muted); font-size: 0.75rem; text-transform: uppercase; display: block;">Delivery Yard / Address:</strong>
                    <span><?php echo nl2br(sanitize_output($customer['delivery_address'])); ?></span>
                </div>
            <?php endif; ?>

            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--c-border-light); font-size: 0.8rem; color: var(--c-muted);">
                Member since <?php echo date('F j, Y', strtotime($customer['created_at'])); ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Inquiries & Requests History -->
    <div>
        
        <!-- Fleet Requests History -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">Fleet Quote Requests (<?php echo count($customerRequests); ?>)</div>
            </div>

            <?php if (!empty($customerRequests)): ?>
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Ref Code</th>
                                <th>Truck Units</th>
                                <th>Assigned Agent</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customerRequests as $req): ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--c-orange); font-family: monospace;"><?php echo sanitize_output($req['request_code']); ?></strong>
                                    </td>
                                    <td><?php echo (int)$req['item_count']; ?> Vehicle(s)</td>
                                    <td><?php echo !empty($req['assigned_agent']) ? sanitize_output($req['assigned_agent']) : '<span style="color: var(--c-muted);">Unassigned</span>'; ?></td>
                                    <td>
                                        <?php 
                                            $bClass = match($req['status']) {
                                                'New' => 'badge-warning',
                                                'Assigned to Agent' => 'badge-primary',
                                                'Contacted' => 'badge-dark',
                                                'Closed' => 'badge-success',
                                                default => 'badge-subtle'
                                            };
                                        ?>
                                        <span class="badge <?php echo $bClass; ?>"><?php echo sanitize_output($req['status']); ?></span>
                                    </td>
                                    <td style="font-size: 0.82rem; color: var(--c-muted);"><?php echo date('M j, Y', strtotime($req['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo ADMIN_URL; ?>request-details.php?id=<?php echo (int)$req['id']; ?>" class="btn btn-outline btn-sm">
                                            Manage &rarr;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state-card" style="padding: 2rem 1rem; border: none;">
                    <div class="empty-state-title" style="font-size: 0.95rem;">No Fleet Requests</div>
                    <div class="empty-state-text" style="margin-bottom: 0;">No multi-truck procurement requests submitted by this client yet.</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Inquiries History -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">Customer Inquiries (<?php echo count($customerInquiries); ?>)</div>
            </div>

            <?php if (!empty($customerInquiries)): ?>
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Ref Code</th>
                                <th>Type</th>
                                <th>Vehicle Subject</th>
                                <th>Status</th>
                                <th>Received</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customerInquiries as $inq): ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--c-orange); font-family: monospace;"><?php echo sanitize_output($inq['inquiry_code']); ?></strong>
                                    </td>
                                    <td><span class="badge badge-subtle"><?php echo sanitize_output($inq['inquiry_type']); ?></span></td>
                                    <td>
                                        <?php if (!empty($inq['truck_code'])): ?>
                                            <strong><?php echo sanitize_output($inq['truck_code']); ?></strong> - <?php echo sanitize_output($inq['truck_title']); ?>
                                        <?php else: ?>
                                            <span style="color: var(--c-muted); font-style: italic;">Custom Sourcing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $stClass = match($inq['status']) {
                                                'Pending' => 'badge-warning',
                                                'In Review' => 'badge-primary',
                                                'Contacted' => 'badge-dark',
                                                'Resolved' => 'badge-success',
                                                'Cancelled' => 'badge-danger',
                                                default => 'badge-subtle'
                                            };
                                        ?>
                                        <span class="badge <?php echo $stClass; ?>"><?php echo sanitize_output($inq['status']); ?></span>
                                    </td>
                                    <td style="font-size: 0.82rem; color: var(--c-muted);"><?php echo date('M j, Y', strtotime($inq['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo ADMIN_URL; ?>inquiry-details.php?id=<?php echo (int)$inq['id']; ?>" class="btn btn-outline btn-sm">
                                            Review &rarr;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state-card" style="padding: 2rem 1rem; border: none;">
                    <div class="empty-state-title" style="font-size: 0.95rem;">No Inquiries</div>
                    <div class="empty-state-text" style="margin-bottom: 0;">No inquiries submitted under this email address yet.</div>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>