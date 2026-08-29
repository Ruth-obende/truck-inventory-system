<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Inquiry Detailed Review & Staff Action
 * =============================================================================
 * Allows dealership staff to review customer requirements, update workflow status,
 * and log internal communication notes.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$inquiryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($inquiryId <= 0) {
    redirect(ADMIN_URL . 'inquiries.php');
}

$db = getDB();

// 1. Handle Status & Notes Update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        set_flash_message('error', 'Security token expired. Please try again.');
    } else {
        $newStatus  = sanitize_input($_POST['status'] ?? 'Pending');
        $adminNotes = sanitize_input($_POST['admin_notes'] ?? '');

        $validStatuses = ['Pending', 'In Review', 'Contacted', 'Resolved', 'Cancelled'];
        if (in_array($newStatus, $validStatuses, true)) {
            $stmtUpdate = $db->prepare('
                UPDATE inquiries 
                SET status = :status, admin_notes = :notes, updated_at = NOW() 
                WHERE id = :id
            ');
            $stmtUpdate->execute([
                ':status' => $newStatus,
                ':notes'  => $adminNotes,
                ':id'     => $inquiryId
            ]);
            set_flash_message('success', 'Inquiry workflow status and internal notes updated.');
        }
    }
}

// 2. Fetch Inquiry & Linked Vehicle Details
$stmt = $db->prepare('
    SELECT i.*, t.truck_code, t.title AS truck_title, t.brand, t.model, t.price AS truck_price, 
           t.purpose_category, t.tonnage_capacity, t.availability_status AS truck_status,
           (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM inquiries i 
    LEFT JOIN trucks t ON i.truck_id = t.id 
    WHERE i.id = :id 
    LIMIT 1
');
$stmt->execute([':id' => $inquiryId]);
$inquiry = $stmt->fetch();

if (!$inquiry) {
    set_flash_message('error', 'Inquiry record not found.');
    redirect(ADMIN_URL . 'inquiries.php');
}

$pageTitle = 'Inquiry ' . $inquiry['inquiry_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="font-size: 0.85rem; color: var(--admin-text-muted); margin-bottom: 4px;">
            <a href="<?php echo ADMIN_URL; ?>inquiries.php" style="color: var(--admin-text-muted);">Inquiries</a> &rsaquo; <span><?php echo sanitize_output($inquiry['inquiry_code']); ?></span>
        </div>
        <h2 style="font-size: 1.4rem; color: var(--admin-navy);">
            Inquiry Review: <span style="color: var(--admin-orange);"><?php echo sanitize_output($inquiry['inquiry_code']); ?></span>
        </h2>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="<?php echo ADMIN_URL; ?>inquiries.php" class="btn btn-outline">
            &larr; Back to Inquiries
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 360px; gap: 1.5rem; align-items: flex-start;">
    
    <!-- Main Customer Request Details -->
    <div>
        
        <!-- Customer Profile & Message Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="admin-card-title">Customer Message &amp; Operational Needs</div>
                <span class="badge badge-navy"><?php echo sanitize_output($inquiry['inquiry_type']); ?></span>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--admin-border); border-radius: 8px; padding: 1.25rem; font-size: 0.98rem; line-height: 1.7; color: var(--admin-text-main); margin-bottom: 1.5rem;">
                <?php echo nl2br(sanitize_output($inquiry['message'])); ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;">
                <?php if ($inquiry['preferred_budget_max']): ?>
                    <div>
                        <span style="color: var(--admin-text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700; display: block;">Indicated Budget Max:</span>
                        <strong style="color: var(--admin-orange); font-size: 1.1rem;"><?php echo format_currency($inquiry['preferred_budget_max']); ?></strong>
                    </div>
                <?php endif; ?>

                <?php if ($inquiry['preferred_tonnage']): ?>
                    <div>
                        <span style="color: var(--admin-text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700; display: block;">Target Payload Tonnage:</span>
                        <strong style="font-size: 1.1rem;"><?php echo format_tonnage($inquiry['preferred_tonnage']); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Linked Vehicle Information (If Applicable) -->
        <?php if (!empty($inquiry['truck_code'])): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-title">Vehicle of Interest in Inventory</div>
                    <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$inquiry['truck_id']; ?>" target="_blank" class="btn btn-outline btn-sm">
                        Open Public Specs Page &nearr;
                    </a>
                </div>

                <div style="display: flex; gap: 1.25rem; align-items: center;">
                    <?php 
                        $hasImg = !empty($inquiry['primary_image']) && file_exists(UPLOADS_PATH . $inquiry['primary_image']);
                    ?>
                    <?php if ($hasImg): ?>
                        <img src="<?php echo BASE_URL . 'assets/images/trucks/' . $inquiry['primary_image']; ?>" style="width: 100px; height: 75px; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border);">
                    <?php endif; ?>

                    <div>
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--admin-orange);"><?php echo sanitize_output($inquiry['truck_code']); ?></div>
                        <h4 style="color: var(--admin-navy); font-size: 1.1rem; margin: 2px 0;"><?php echo sanitize_output($inquiry['truck_title']); ?></h4>
                        <div style="font-size: 0.9rem; color: var(--admin-text-muted);">
                            Price: <strong style="color: var(--admin-navy);"><?php echo format_currency($inquiry['truck_price']); ?></strong> &bull; 
                            Capacity: <?php echo format_tonnage($inquiry['tonnage_capacity']); ?> &bull; 
                            Status: <span class="badge badge-success"><?php echo sanitize_output($inquiry['truck_status']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Sidebar / Action Card -->
    <aside>
        
        <!-- Customer Contact Card -->
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-title" style="margin-bottom: 1rem;">Customer Contact</div>
            
            <div style="font-size: 1.05rem; font-weight: 700; color: var(--admin-navy); margin-bottom: 0.5rem;">
                <?php echo sanitize_output($inquiry['customer_name']); ?>
            </div>

            <div style="font-size: 0.9rem; line-height: 1.8; color: var(--admin-text-main);">
                <div>
                    <strong>Phone:</strong><br>
                    <a href="tel:<?php echo sanitize_output($inquiry['customer_phone']); ?>" style="color: var(--admin-orange); font-weight: 600;">
                        <?php echo sanitize_output($inquiry['customer_phone']); ?>
                    </a>
                </div>
                <div style="margin-top: 6px;">
                    <strong>Email:</strong><br>
                    <a href="mailto:<?php echo sanitize_output($inquiry['customer_email']); ?>" style="color: var(--admin-navy);">
                        <?php echo sanitize_output($inquiry['customer_email']); ?>
                    </a>
                </div>
                <div style="margin-top: 8px; font-size: 0.8rem; color: var(--admin-text-muted); border-top: 1px solid var(--admin-border); padding-top: 8px;">
                    Received: <?php echo date('M j, Y - g:ia', strtotime($inquiry['created_at'])); ?>
                </div>
            </div>
        </div>

        <!-- Workflow Status & Internal Notes Form -->
        <div class="admin-card" style="border: 2px solid var(--admin-orange);">
            <div class="admin-card-title" style="margin-bottom: 1rem;">Staff Workflow Action</div>

            <form method="POST" action="<?php echo ADMIN_URL; ?>inquiry-details.php?id=<?php echo $inquiryId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div style="margin-bottom: 1.25rem;">
                    <label class="filter-label" for="status">Update Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="Pending" <?php echo ($inquiry['status'] === 'Pending') ? 'selected' : ''; ?>>Pending (New Lead)</option>
                        <option value="In Review" <?php echo ($inquiry['status'] === 'In Review') ? 'selected' : ''; ?>>In Review (Sourcing Options)</option>
                        <option value="Contacted" <?php echo ($inquiry['status'] === 'Contacted') ? 'selected' : ''; ?>>Contacted (Customer Called/Emailed)</option>
                        <option value="Resolved" <?php echo ($inquiry['status'] === 'Resolved') ? 'selected' : ''; ?>>Resolved (Deal Closed / Inspection Done)</option>
                        <option value="Cancelled" <?php echo ($inquiry['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled (Not Interested)</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label class="filter-label" for="admin_notes">Internal Dealership Staff Notes</label>
                    <textarea name="admin_notes" id="admin_notes" rows="4" class="form-control" placeholder="Log physical inspection date, discussed price discount, or customer follow-up notes..."><?php echo sanitize_output($inquiry['admin_notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 10px;">
                    Save Workflow Changes
                </button>
            </form>
        </div>

    </aside>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
