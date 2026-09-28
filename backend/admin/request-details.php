<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Fleet Request Details, Quotes & Dialogue
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

// Handle Status, Consultant Assignment & Staff Replies (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        set_flash_message('error', 'Security token expired. Please try again.');
    } else {
        $action = $_POST['action'] ?? 'update_request';

        // 1. If sending a direct staff reply in the thread
        if ($action === 'send_staff_reply') {
            $replyMsg = trim(sanitize_input($_POST['staff_reply_message'] ?? ''));
            if (!empty($replyMsg)) {
                // Determine sender name
                $stmtCur = $db->prepare('SELECT assigned_agent, status FROM customer_requests WHERE id = :id');
                $stmtCur->execute([':id' => $requestId]);
                $cur = $stmtCur->fetch();
                $senderName = !empty($cur['assigned_agent']) ? $cur['assigned_agent'] : 'Moal Fleet Sales Desk';

                $stmtIns = $db->prepare('
                    INSERT INTO quote_replies (request_type, reference_id, sender_type, sender_id, sender_name, message, created_at)
                    VALUES ("quote", :ref_id, "staff", :aid, :sname, :msg, NOW())
                ');
                $stmtIns->execute([
                    ':ref_id' => $requestId,
                    ':aid'    => $_SESSION['admin_id'] ?? 1,
                    ':sname'  => $senderName,
                    ':msg'    => $replyMsg
                ]);

                // Update request updated_at and status if New
                $newStatus = ($cur['status'] === 'New') ? 'Contacted' : $cur['status'];
                $stmtUpd = $db->prepare('UPDATE customer_requests SET status = :st, admin_notes = :notes, updated_at = NOW() WHERE id = :id');
                $stmtUpd->execute([
                    ':st'    => $newStatus,
                    ':notes' => $replyMsg,
                    ':id'    => $requestId
                ]);

                set_flash_message('success', 'Staff response posted successfully to the client portal.');
                redirect(ADMIN_URL . 'request-details.php?id=' . $requestId . '#thread');
            } else {
                set_flash_message('error', 'Please enter a response message.');
            }
        } else {
            // General Update (Status, Agent, Admin Notes)
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

                // If notes were provided and not empty, also log as a staff message in quote_replies
                if (!empty($adminNotes)) {
                    $senderName = !empty($assignedAgent) ? $assignedAgent : 'Moal Fleet Sales Desk';
                    $stmtReply = $db->prepare('
                        INSERT INTO quote_replies (request_type, reference_id, sender_type, sender_id, sender_name, message, created_at)
                        VALUES ("quote", :ref_id, "staff", :aid, :sname, :msg, NOW())
                    ');
                    $stmtReply->execute([
                        ':ref_id' => $requestId,
                        ':aid'    => $_SESSION['admin_id'] ?? 1,
                        ':sname'  => $senderName,
                        ':msg'    => $adminNotes
                    ]);
                }

                set_flash_message('success', 'Fleet request status and assigned consultant updated.');
                redirect(ADMIN_URL . 'request-details.php?id=' . $requestId);
            }
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

// Fetch Conversation Thread
$stmtReplies = $db->prepare('
    SELECT * FROM quote_replies 
    WHERE request_type = "quote" AND reference_id = :rid 
    ORDER BY created_at ASC
');
$stmtReplies->execute([':rid' => $requestId]);
$replies = $stmtReplies->fetchAll();

$pageTitle = 'Request ' . $req['request_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <div style="font-size: 0.85rem; color: var(--c-muted); margin-bottom: 4px;">
            <a href="<?php echo ADMIN_URL; ?>requests.php" style="color: var(--c-muted);">Fleet Requests</a> &rsaquo; <span><?php echo sanitize_output($req['request_code']); ?></span>
        </div>
        <h1 class="admin-heading-title">
            Fleet Quote Request: <span style="color: var(--c-orange);"><?php echo sanitize_output($req['request_code']); ?></span>
        </h1>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="<?php echo ADMIN_URL; ?>requests.php" class="btn btn-outline">
            &larr; Back to Requests
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 360px; gap: 1.5rem; align-items: flex-start;">
    
    <!-- Requested Items & Dialogue -->
    <div>
        <!-- Requested Commercial Vehicles -->
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
                                    <img src="<?php echo $imgSrc; ?>" alt="Thumb" style="width: 60px; height: 42px; object-fit: cover; border-radius: 4px; border: 1px solid var(--c-border);">
                                </td>
                                <td>
                                    <strong><?php echo sanitize_output($item['title']); ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--c-muted);">Stock #<?php echo sanitize_output($item['truck_code']); ?></div>
                                </td>
                                <td><?php echo format_tonnage($item['tonnage_capacity']); ?></td>
                                <td><strong><?php echo (int)$item['quantity']; ?> Unit(s)</strong></td>
                                <td><strong style="color: var(--c-navy);"><?php echo format_currency($item['price']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1rem; padding: 1rem; background: #FAFBFD; border: 1px solid var(--c-border); border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: var(--c-navy);">Estimated Total Asset Value:</span>
                <strong style="font-size: 1.25rem; color: var(--c-orange);"><?php echo format_currency($totalVal); ?></strong>
            </div>
        </div>

        <!-- Customer Order Notes & Delivery Address -->
        <div class="admin-card" style="margin-top: 1.5rem;">
            <div class="admin-card-title" style="margin-bottom: 0.75rem;">Customer Delivery &amp; Operational Notes</div>
            <div style="background: #F8FAFC; border: 1px solid var(--c-border); border-radius: 8px; padding: 1rem; font-size: 0.92rem; line-height: 1.6; color: var(--c-slate);">
                <?php echo !empty($req['notes']) ? nl2br(sanitize_output($req['notes'])) : '<em style="color: var(--c-muted);">No specific custom delivery notes provided.</em>'; ?>
            </div>
        </div>

        <!-- =====================================================================
             TWO-WAY CLIENT DIALOGUE & COMMUNICATION HISTORY
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
                        No messages exchanged yet. Use the response form below to send an official quote update or proforma breakdown to the client.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Direct Staff Reply Box -->
            <div style="background: #FAFBFD; border: 1px solid var(--c-border); border-radius: 8px; padding: 1.25rem;">
                <div style="font-weight: 700; color: var(--c-navy); font-size: 0.95rem; margin-bottom: 8px;">
                    Send Response / Quote Update to Client
                </div>
                <form method="POST" action="<?php echo ADMIN_URL; ?>request-details.php?id=<?php echo $requestId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="send_staff_reply">
                    
                    <div class="form-group" style="margin-bottom: 0.75rem;">
                        <textarea name="staff_reply_message" rows="3" class="form-control" placeholder="Type your response to the client (e.g. confirming stock availability, attaching proforma quote details, or answering pricing queries)..." required></textarea>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary" style="font-weight: 700; padding: 9px 18px;">
                            Post Response to Customer Portal &rarr;
                        </button>
                        <span style="font-size: 0.8rem; color: var(--c-muted);">
                            The client will see this instantly in their portal and can respond.
                        </span>
                    </div>
                </form>
            </div>

        </div>

    </div>

    <!-- Sidebar Management Card -->
    <aside>
        
        <!-- Customer Profile Card -->
        <div class="admin-card" style="margin-bottom: 1.5rem;">
            <div class="admin-card-title" style="margin-bottom: 1rem;">Customer Profile</div>
            
            <div style="font-size: 1.05rem; font-weight: 700; color: var(--c-navy); margin-bottom: 0.5rem;">
                <?php echo sanitize_output($req['full_name']); ?>
            </div>

            <div style="font-size: 0.9rem; line-height: 1.8; color: var(--c-slate);">
                <?php if (!empty($req['business_name'])): ?>
                    <div>
                        <strong>Company:</strong> <?php echo sanitize_output($req['business_name']); ?>
                    </div>
                <?php endif; ?>
                <div>
                    <strong>Phone:</strong><br>
                    <a href="tel:<?php echo sanitize_output($req['phone']); ?>" style="color: var(--c-orange); font-weight: 600; text-decoration: none;">
                        <?php echo sanitize_output($req['phone']); ?>
                    </a>
                </div>
                <div style="margin-top: 6px;">
                    <strong>Email:</strong><br>
                    <a href="mailto:<?php echo sanitize_output($req['email']); ?>" style="color: var(--c-navy); text-decoration: none;">
                        <?php echo sanitize_output($req['email']); ?>
                    </a>
                </div>
                <div style="margin-top: 6px;">
                    <strong>Delivery Yard:</strong><br>
                    <span style="color: var(--c-muted); font-size: 0.85rem;"><?php echo sanitize_output($req['customer_address']); ?></span>
                </div>
                <div style="margin-top: 10px; font-size: 0.8rem; color: var(--c-muted); border-top: 1px solid var(--c-border); padding-top: 8px;">
                    Submitted: <?php echo date('M j, Y - g:ia', strtotime($req['created_at'])); ?>
                </div>
            </div>
        </div>

        <!-- Assignment Form -->
        <div class="admin-card" style="border: 2px solid var(--c-orange);">
            <div class="admin-card-title" style="margin-bottom: 1rem;">Assign Sales Consultant</div>

            <form method="POST" action="<?php echo ADMIN_URL; ?>request-details.php?id=<?php echo $requestId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="update_request">

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
                    <input type="text" name="assigned_agent" id="assigned_agent" class="form-control" placeholder="e.g. Mustapha Bello / Sales Desk" value="<?php echo sanitize_output($req['assigned_agent'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="admin_notes">Dealership Internal Note / Quick Message</label>
                    <textarea name="admin_notes" id="admin_notes" rows="3" class="form-control" placeholder="Log proforma invoice number or note..."><?php echo sanitize_output($req['admin_notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-weight: 700;">
                     Update Request &amp; Agent
                </button>
            </form>
        </div>

    </aside>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>