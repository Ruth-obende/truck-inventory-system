<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Fleet Request Details & Agent Assignment
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($requestId <= 0) {
    redirect(ADMIN_URL . 'requests.php');
}

$db = getDB();

// Handle Status & Agent Assignment (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        set_flash_message('error', 'Security token expired. Please try again.');
    } else {
        $newStatus     = sanitize_input($_POST['status'] ?? 'New');
        $assignedAgent = sanitize_input($_POST['assigned_agent'] ?? '');
        $adminNotes    = sanitize_input($_POST['admin_notes'] ?? '');

        $validStatuses = ['New', 'Assigned to Agent', 'Contacted', 'Closed'];
        if (in_array($newStatus, $validStatuses, true)) {
            $stmtUpdate = $db->prepare('
                UPDATE customer_requests 
                SET status = :status, assigned_agent = :agent, admin_notes = :notes, updated_at = NOW() 
                WHERE id = :id
            ');
            $stmtUpdate->execute([
                ':status' => $newStatus,
                ':agent'  => $assignedAgent,
                ':notes'  => $adminNotes,
                ':id'     => $requestId
            ]);
            set_flash_message('success', 'Fleet request status and assigned consultant updated.');
            redirect(ADMIN_URL . 'request-details.php?id=' . $requestId);
        }
    }
}

// Fetch Request & Customer Details
$stmt = $db->prepare('
    SELECT r.*, c.full_name, c.phone, c.email, c.business_name, c.delivery_address AS customer_address
    FROM customer_requests r
    JOIN customers c ON r.customer_id = c.id
    WHERE r.id = :id
    LIMIT 1
');
$stmt->execute([':id' => $requestId]);
$req = $stmt->fetch();

if (!$req) {
    set_flash_message('error', 'Fleet request record not found.');
    redirect(ADMIN_URL . 'requests.php');
}

// Fetch Requested Trucks
$stmtItems = $db->prepare('
    SELECT ri.*, t.truck_code, t.title, t.brand, t.price, t.tonnage_capacity,
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM request_items ri
    JOIN trucks t ON ri.truck_id = t.id
    WHERE ri.request_id = :rid
');
$stmtItems->execute([':rid' => $requestId]);
$items = $stmtItems->fetchAll();

$pageTitle = 'Request ' . $req['request_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <div style="font-size: 0.85rem; color: var(--c-muted); margin-bottom: 4px;">
            <a href="<?php echo ADMIN_URL; ?>requests.php" style="color: var(--c-muted);">Fleet Requests</a> &rsaquo; <span><?php echo sanitize_output($req['request_code']); ?></span>
        </div>
        <h1 class="admin-heading-title">
            Fleet Request: <span style="color: var(--c-orange);"><?php echo sanitize_output($req['request_code']); ?></span>
        </h1>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="<?php echo ADMIN_URL; ?>requests.php" class="btn btn-outline">
            &larr; Back to Requests
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 360px; gap: 1.5rem; align-items: flex-start;">
    
    <!-- Requested Items & Delivery Specifications -->
    <div>
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">Requested Commercial Vehicles (<?php echo count($items); ?> Items)</div>
            </div>

            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Vehicle</th>
                            <th>Capacity</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $totalVal = 0;
                            foreach ($items as $item): 
                                $totalVal += ($item['price'] * $item['quantity']);
                                $imgSrc = !empty($item['primary_image']) ? BASE_URL . 'assets/images/trucks/' . sanitize_output($item['primary_image']) : BASE_URL . 'assets/images/branding/logo.jpg';
                        ?>
                            <tr>
                                <td style="width: 70px;">
                                    <img src="<?php echo $imgSrc; ?>" alt="Thumb" style="width: 60px; height: 42px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                                </td>
                                <td>
                                    <strong><?php echo sanitize_output($item['title']); ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--admin-text-muted);">Stock #<?php echo sanitize_output($item['truck_code']); ?></div>
                                </td>
                                <td><?php echo format_tonnage($item['tonnage_capacity']); ?></td>
                                <td><strong><?php echo (int)$item['quantity']; ?> Unit(s)</strong></td>
                                <td><strong style="color: var(--admin-navy);"><?php echo format_currency($item['price']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1rem; padding: 1rem; background: #FAFBFD; border: 1px solid var(--admin-border); border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: var(--admin-navy);">Estimated Total Asset Value:</span>
                <strong style="font-size: 1.25rem; color: var(--admin-orange);"><?php echo format_currency($totalVal); ?></strong>
            </div>
        </div>

        <!-- Customer Order Notes & Delivery Address -->
        <div class="admin-card">
            <div class="admin-card-title" style="margin-bottom: 0.75rem;">Customer Delivery &amp; Operational Notes</div>
            <div style="background: #F8FAFC; border: 1px solid var(--admin-border); border-radius: 8px; padding: 1rem; font-size: 0.92rem; line-height: 1.6; color: var(--admin-text-main);">
                <?php echo !empty($req['notes']) ? nl2br(sanitize_output($req['notes'])) : '<em style="color: var(--admin-text-muted);">No specific custom delivery notes provided.</em>'; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar Management Card -->
    <aside>
        
        <!-- Customer Profile Card -->
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-title" style="margin-bottom: 1rem;">Customer Profile</div>
            
            <div style="font-size: 1.05rem; font-weight: 700; color: var(--admin-navy); margin-bottom: 0.5rem;">
                <?php echo sanitize_output($req['full_name']); ?>
            </div>

            <div style="font-size: 0.9rem; line-height: 1.8; color: var(--admin-text-main);">
                <?php if (!empty($req['business_name'])): ?>
                    <div>
                        <strong>Company:</strong> <?php echo sanitize_output($req['business_name']); ?>
                    </div>
                <?php endif; ?>
                <div>
                    <strong>Phone:</strong><br>
                    <a href="tel:<?php echo sanitize_output($req['phone']); ?>" style="color: var(--admin-orange); font-weight: 600; text-decoration: none;">
                        Phone: <?php echo sanitize_output($req['phone']); ?>
                    </a>
                </div>
                <div style="margin-top: 6px;">
                    <strong>Email:</strong><br>
                    <a href="mailto:<?php echo sanitize_output($req['email']); ?>" style="color: var(--admin-navy); text-decoration: none;">
                        Email: <?php echo sanitize_output($req['email']); ?>
                    </a>
                </div>
                <div style="margin-top: 6px;">
                    <strong>Delivery Yard:</strong><br>
                    <span style="color: var(--admin-text-muted); font-size: 0.85rem;"><?php echo sanitize_output($req['customer_address']); ?></span>
                </div>
                <div style="margin-top: 10px; font-size: 0.8rem; color: var(--admin-text-muted); border-top: 1px solid var(--admin-border); padding-top: 8px;">
                    Submitted: <?php echo date('M j, Y - g:ia', strtotime($req['created_at'])); ?>
                </div>
            </div>
        </div>

        <!-- Assignment Form -->
        <div class="admin-card" style="border: 2px solid var(--admin-orange);">
            <div class="admin-card-title" style="margin-bottom: 1rem;">Assign Sales Consultant</div>

            <form method="POST" action="<?php echo ADMIN_URL; ?>request-details.php?id=<?php echo $requestId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="form-group">
                    <label class="form-label" for="status">Workflow Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="New" <?php echo ($req['status'] === 'New') ? 'selected' : ''; ?>>New Request</option>
                        <option value="Assigned to Agent" <?php echo ($req['status'] === 'Assigned to Agent') ? 'selected' : ''; ?>>Assigned to Consultant</option>
                        <option value="Contacted" <?php echo ($req['status'] === 'Contacted') ? 'selected' : ''; ?>>Contacted (Quote Sent)</option>
                        <option value="Closed" <?php echo ($req['status'] === 'Closed') ? 'selected' : ''; ?>>Closed (Completed)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="assigned_agent">Assigned Sales Consultant</label>
                    <input type="text" name="assigned_agent" id="assigned_agent" class="form-control" placeholder="e.g. Sales Desk / John Doe" value="<?php echo sanitize_output($req['assigned_agent'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="admin_notes">Dealership Internal Notes</label>
                    <textarea name="admin_notes" id="admin_notes" rows="4" class="form-control" placeholder="Log proforma invoice number, discounts applied, or delivery arrangement notes..."><?php echo sanitize_output($req['admin_notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-weight: 700;">
                     Update Request &amp; Agent
                </button>
            </form>
        </div>

    </aside>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>