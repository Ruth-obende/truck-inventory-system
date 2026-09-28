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
        $action = $_POST['action'] ?? 'update_inquiry';

        if ($action === 'send_staff_reply') {
            $replyMsg = trim(sanitize_input($_POST['staff_reply_message'] ?? ''));
            if (!empty($replyMsg)) {
                $senderName = $_SESSION['admin_username'] ?? 'Moal Sales Consultant';
                $stmtIns = $db->prepare('
                    INSERT INTO quote_replies (request_type, reference_id, sender_type, sender_id, sender_name, message, created_at)
                    VALUES ("inquiry", :ref_id, "staff", :aid, :sname, :msg, NOW())
                ');
                $stmtIns->execute([
                    ':ref_id' => $inquiryId,
                    ':aid'    => $_SESSION['admin_id'] ?? 1,
                    ':sname'  => $senderName,
                    ':msg'    => $replyMsg
                ]);

                // Update inquiry status to Contacted if it was Pending
                $stmtCur = $db->prepare('SELECT status FROM inquiries WHERE id = :id');
                $stmtCur->execute([':id' => $inquiryId]);
                $cur = $stmtCur->fetch();
                $newStatus = ($cur['status'] === 'Pending') ? 'Contacted' : $cur['status'];

                $stmtUpd = $db->prepare('UPDATE inquiries SET status = :st, admin_notes = :notes, updated_at = NOW() WHERE id = :id');
                $stmtUpd->execute([
                    ':st'    => $newStatus,
                    ':notes' => $replyMsg,
                    ':id'    => $inquiryId
                ]);

                set_flash_message('success', 'Staff response posted to customer portal successfully.');
                redirect(ADMIN_URL . 'inquiry-details.php?id=' . $inquiryId . '#thread');
            } else {
                set_flash_message('error', 'Please enter a message before sending.');
            }
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

                if (!empty($adminNotes)) {
                    $senderName = $_SESSION['admin_username'] ?? 'Moal Sales Consultant';
                    $stmtReply = $db->prepare('
                        INSERT INTO quote_replies (request_type, reference_id, sender_type, sender_id, sender_name, message, created_at)
                        VALUES ("inquiry", :ref_id, "staff", :aid, :sname, :msg, NOW())
                    ');
                    $stmtReply->execute([
                        ':ref_id' => $inquiryId,
                        ':aid'    => $_SESSION['admin_id'] ?? 1,
                        ':sname'  => $senderName,
                        ':msg'    => $adminNotes
                    ]);
                }

                set_flash_message('success', 'Staff response and inquiry status updated successfully.');
                redirect(ADMIN_URL . 'inquiry-details.php?id=' . $inquiryId);
            }
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

// Fetch Conversation Thread
$stmtReplies = $db->prepare('
    SELECT * FROM quote_replies 
    WHERE request_type = "inquiry" AND reference_id = :inqid 
    ORDER BY created_at ASC
');
$stmtReplies->execute([':inqid' => $inquiryId]);
$replies = $stmtReplies->fetchAll();

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
            <div class="admin-card" style="margin-top: 1.5rem;">
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

        <!-- =====================================================================
             TWO-WAY CLIENT DIALOGUE & INQUIRY HISTORY
             ===================================================================== -->
        <div class="admin-card" id="thread" style="margin-top: 1.5rem;">
            <div class="admin-card-header">
                <div>
                    <div class="admin-card-title">Client Communication Thread</div>
                    <div style="font-size: 0.82rem; color: var(--c-muted);">
                        Synchronized with client portal &bull; <?php echo count($replies); ?> Total Messages
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                <?php if (!empty($replies)): ?>
                    <?php foreach ($replies as $rep): ?>
                        <?php $isClient = ($rep['sender_type'] === 'customer'); ?>
                        <div style="padding: 1rem 1.25rem; border-radius: 8px; border: 1px solid <?php echo $isClient ? '#93C5FD' : '#FED7AA'; ?>; background: <?php echo $isClient ? '#EFF6FF' : '#FFF7ED'; ?>; border-left: 4px solid <?php echo $isClient ? '#2563EB' : 'var(--c-orange)'; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 0.5rem;">
                                <div style="font-weight: 700; font-size: 0.88rem; color: <?php echo $isClient ? '#1E40AF' : '#C2410C'; ?>;">
                                    <?php if ($isClient): ?>
                                        <span>👤 Customer Response: <strong><?php echo sanitize_output($rep['sender_name']); ?></strong></span>
                                    <?php else: ?>
                                        <span>🏢 Dealership Consultant: <strong><?php echo sanitize_output($rep['sender_name']); ?></strong></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--c-muted);">
                                    <?php echo date('M j, Y - g:ia', strtotime($rep['created_at'])); ?>
                                </div>
                            </div>
                            <div style="color: #1E293B; line-height: 1.6; font-size: 0.92rem; white-space: pre-wrap;">
                                <?php echo nl2br(sanitize_output($rep['message'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="background: #F8FAFC; border: 1px dashed var(--c-border); border-radius: 6px; padding: 1.25rem; text-align: center; color: var(--c-muted); font-size: 0.9rem;">
                        No direct messages logged yet. Use the response form below to send an update to the customer.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Direct Staff Reply Box -->
            <div style="background: #FAFBFD; border: 1px solid var(--c-border); border-radius: 8px; padding: 1.25rem;">
                <div style="font-weight: 700; color: var(--c-navy); font-size: 0.95rem; margin-bottom: 8px;">
                    Send Response / Inspection Update to Client
                </div>
                <form method="POST" action="<?php echo ADMIN_URL; ?>inquiry-details.php?id=<?php echo $inquiryId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="send_staff_reply">
                    
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <textarea name="staff_reply_message" rows="3" class="form-control" placeholder="Type your response to the customer (e.g. confirming inspection availability, pricing details, or vehicle condition report)..." required></textarea>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary" style="font-weight: 700; padding: 9px 18px;">
                            Post Response to Customer Portal &rarr;
                        </button>
                        <span style="font-size: 0.8rem; color: var(--c-muted);">
                            The customer will see this response in their portal immediately and can reply back.
                        </span>
                    </div>
                </form>
            </div>

        </div>

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
                <input type="hidden" name="action" value="update_inquiry">

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
                    <textarea name="admin_notes" id="admin_notes" rows="4" class="form-control" placeholder="Enter quote details, inspection schedule, or communication summary with the client..."><?php echo sanitize_output($inquiry['admin_notes'] ?? ''); ?></textarea>
                    <small style="font-size: 0.75rem; color: var(--c-muted); display: block; margin-top: 4px;">
                        This response is securely saved to the customer's portal timeline.
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