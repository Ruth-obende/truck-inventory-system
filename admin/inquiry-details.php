<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Inquiry Detailed Review & Staff Action
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$inquiryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($inquiryId <= 0) {
    redirect(ADMIN_URL . 'inquiries.php');
}

$db = getDB();

// Handle Status & Staff Response Update (POST)
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
            set_flash_message('success', 'Staff response and inquiry status updated successfully.');
            redirect(ADMIN_URL . 'inquiry-details.php?id=' . $inquiryId);
        }
    }
}

// Fetch Inquiry Details & Linked Truck
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

<div class="admin-page-header">
    <div>
        <div style="font-size: 0.85rem; color: var(--c-muted); margin-bottom: 4px;">
            <a href="<?php echo ADMIN_URL; ?>inquiries.php" style="color: var(--c-muted);">Inquiries</a> &rsaquo; <span><?php echo sanitize_output($inquiry['inquiry_code']); ?></span>
        </div>
        <h1 class="admin-heading-title">
            Inquiry Review: <span style="color: var(--c-orange);"><?php echo sanitize_output($inquiry['inquiry_code']); ?></span>
        </h1>
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
        
        <!-- Customer Message Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <div class="admin-card-title">Customer Inquiry Message</div>
                    <div style="font-size: 0.82rem; color: var(--c-muted);">Submitted on <?php echo date('F j, Y - g:ia', strtotime($inquiry['created_at'])); ?></div>
                </div>
                <span class="badge badge-primary"><?php echo sanitize_output($inquiry['inquiry_type']); ?></span>
            </div>

            <div style="background: #F8FAFC; border: 1px solid var(--c-border); border-radius: 8px; padding: 1.25rem; font-size: 0.95rem; line-height: 1.7; color: var(--c-slate); margin-bottom: 1.5rem;">
                <?php echo nl2br(sanitize_output($inquiry['message'])); ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;">
                <?php if ($inquiry['preferred_budget_max']): ?>
                    <div style="background: #FAFBFD; padding: 10px 14px; border-radius: 6px; border: 1px solid var(--c-border);">
                        <span style="color: var(--c-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block;">Indicated Budget Max:</span>
                        <strong style="color: var(--c-orange); font-size: 1.1rem;"><?php echo format_currency($inquiry['preferred_budget_max']); ?></strong>
                    </div>
                <?php endif; ?>

                <?php if ($inquiry['preferred_tonnage']): ?>
                    <div style="background: #FAFBFD; padding: 10px 14px; border-radius: 6px; border: 1px solid var(--c-border);">
                        <span style="color: var(--c-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block;">Target Payload Capacity:</span>
                        <strong style="font-size: 1.1rem; color: var(--c-navy);"><?php echo format_tonnage($inquiry['preferred_tonnage']); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Linked Vehicle Information (If Applicable) -->
        <?php if (!empty($inquiry['truck_code'])): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-title">Vehicle of Interest in Inventory</div>
                    <a href="<?php echo ADMIN_URL; ?>truck-form.php?id=<?php echo (int)$inquiry['truck_id']; ?>" class="btn btn-outline btn-sm">
                        Edit Vehicle Details &rarr;
                    </a>
                </div>

                <div style="display: flex; gap: 1.25rem; align-items: center; flex-wrap: wrap; padding: 1.25rem;">
                    <?php 
                        $imgSrc = !empty($inquiry['primary_image']) ? BASE_URL . 'assets/images/trucks/' . sanitize_output($inquiry['primary_image']) : BASE_URL . 'assets/images/branding/logo.jpg';
                    ?>
                    <img src="<?php echo $imgSrc; ?>" alt="Vehicle Thumb" style="width: 120px; height: 85px; object-fit: cover; border-radius: 6px; border: 1px solid var(--c-border);">

                    <div>
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--c-orange); font-family: monospace;"><?php echo sanitize_output($inquiry['truck_code']); ?></div>
                        <h3 style="color: var(--c-navy); font-size: 1.1rem; margin: 2px 0; font-weight: 700;"><?php echo sanitize_output($inquiry['truck_title']); ?></h3>
                        <div style="font-size: 0.88rem; color: var(--c-muted); margin-top: 4px;">
                            Price: <strong style="color: var(--c-navy);"><?php echo format_currency($inquiry['truck_price']); ?></strong> &bull; 
                            Capacity: <?php echo format_tonnage($inquiry['tonnage_capacity']); ?> &bull; 
                            Status: <span class="badge badge-success"><?php echo sanitize_output($inquiry['truck_status']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Sidebar Action & Contact Card -->
    <aside>
        
        <!-- Customer Contact Card -->
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-title" style="margin-bottom: 1rem;">Customer Information</div>
            
            <div style="font-size: 1.05rem; font-weight: 700; color: var(--c-navy); margin-bottom: 0.5rem;">
                <?php echo sanitize_output($inquiry['customer_name']); ?>
            </div>

            <div style="font-size: 0.9rem; line-height: 1.8; color: var(--c-slate);">
                <div>
                    <strong>Phone:</strong><br>
                    <a href="tel:<?php echo sanitize_output($inquiry['customer_phone']); ?>" style="color: var(--c-orange); font-weight: 600; text-decoration: none;">
                        <?php echo sanitize_output($inquiry['customer_phone']); ?>
                    </a>
                </div>
                <div style="margin-top: 6px;">
                    <strong>Email:</strong><br>
                    <a href="mailto:<?php echo sanitize_output($inquiry['customer_email']); ?>" style="color: var(--c-navy); text-decoration: none;">
                        <?php echo sanitize_output($inquiry['customer_email']); ?>
                    </a>
                </div>
                <div style="margin-top: 10px; font-size: 0.8rem; color: var(--c-muted); border-top: 1px solid var(--c-border-light); padding-top: 8px;">
                    Received: <?php echo date('M j, Y - g:ia', strtotime($inquiry['created_at'])); ?>
                </div>
            </div>
        </div>

        <!-- Workflow Status & Staff Response Form -->
        <div class="admin-card" style="border: 2px solid var(--c-orange);">
            <div class="admin-card-title" style="margin-bottom: 1rem;">Staff Response &amp; Action</div>

            <form method="POST" action="<?php echo ADMIN_URL; ?>inquiry-details.php?id=<?php echo $inquiryId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="form-group">
                    <label class="form-label" for="status">Workflow Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="Pending" <?php echo ($inquiry['status'] === 'Pending') ? 'selected' : ''; ?>>Pending (New Lead)</option>
                        <option value="In Review" <?php echo ($inquiry['status'] === 'In Review') ? 'selected' : ''; ?>>In Review (Pricing / Sourcing)</option>
                        <option value="Contacted" <?php echo ($inquiry['status'] === 'Contacted') ? 'selected' : ''; ?>>Contacted (Customer Called)</option>
                        <option value="Resolved" <?php echo ($inquiry['status'] === 'Resolved') ? 'selected' : ''; ?>>Resolved (Deal Closed / Finished)</option>
                        <option value="Cancelled" <?php echo ($inquiry['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled (Declined)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label" for="admin_notes">Staff Response &amp; Follow-up Notes</label>
                    <textarea name="admin_notes" id="admin_notes" rows="5" class="form-control" placeholder="Enter quote details, inspection schedule, or communication summary with the client..."><?php echo sanitize_output($inquiry['admin_notes'] ?? ''); ?></textarea>
                    <small style="font-size: 0.75rem; color: var(--c-muted); display: block; margin-top: 4px;">
                        This response is securely recorded and saved to the customer's portal timeline.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 700; margin-top: 1rem; justify-content: center;">
                    Save Response &amp; Status
                </button>
            </form>
        </div>

    </aside>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>