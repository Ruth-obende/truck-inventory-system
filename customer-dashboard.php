<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Fleet Request, Quote & Inquiry Dashboard
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_customer_login();

$customer = get_logged_in_customer();
$pageTitle = 'My Account';
$db = getDB();

// -----------------------------------------------------------------------------
// 1. Handle Customer Reply Submission (Quotes & Inquiries)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'customer_reply') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        set_flash_message('error', 'Session expired. Please try submitting your response again.');
        redirect(BASE_URL . 'customer-dashboard.php');
    }

    $replyType = sanitize_input($_POST['reply_type'] ?? 'quote'); // 'quote' or 'inquiry'
    $refId     = (int)($_POST['reference_id'] ?? 0);
    $replyMsg  = trim(sanitize_input($_POST['reply_message'] ?? ''));

    if (empty($replyMsg)) {
        set_flash_message('error', 'Please write a message before sending your response.');
        redirect(BASE_URL . 'customer-dashboard.php#item-' . $replyType . '-' . $refId);
    }

    if ($replyType === 'quote') {
        // Validate that this quote request belongs to this customer
        $stmtChk = $db->prepare('SELECT id, request_code FROM customer_requests WHERE id = :id AND customer_id = :cid LIMIT 1');
        $stmtChk->execute([':id' => $refId, ':cid' => $customer['id']]);
        $quoteRow = $stmtChk->fetch();

        if (!$quoteRow) {
            set_flash_message('error', 'Unauthorized quote reference.');
            redirect(BASE_URL . 'customer-dashboard.php');
        }

        // Insert reply
        $stmtIns = $db->prepare('
            INSERT INTO quote_replies (request_type, reference_id, sender_type, sender_id, sender_name, message, created_at)
            VALUES ("quote", :ref_id, "customer", :cid, :cname, :msg, NOW())
        ');
        $stmtIns->execute([
            ':ref_id' => $refId,
            ':cid'    => $customer['id'],
            ':cname'  => $customer['full_name'],
            ':msg'    => $replyMsg
        ]);

        // Update customer_requests updated_at
        $stmtUpd = $db->prepare('UPDATE customer_requests SET updated_at = NOW() WHERE id = :id');
        $stmtUpd->execute([':id' => $refId]);

        set_flash_message('success', 'Your response has been sent to our sales consultants regarding Quote Ref: ' . sanitize_output($quoteRow['request_code']) . '.');
        redirect(BASE_URL . 'customer-dashboard.php#item-quote-' . $refId);

    } elseif ($replyType === 'inquiry') {
        // Validate inquiry belongs to customer's email
        $stmtChk = $db->prepare('SELECT id, inquiry_code FROM inquiries WHERE id = :id AND customer_email = :email LIMIT 1');
        $stmtChk->execute([':id' => $refId, ':email' => $customer['email']]);
        $inqRow = $stmtChk->fetch();

        if (!$inqRow) {
            set_flash_message('error', 'Unauthorized inquiry reference.');
            redirect(BASE_URL . 'customer-dashboard.php');
        }

        // Insert reply
        $stmtIns = $db->prepare('
            INSERT INTO quote_replies (request_type, reference_id, sender_type, sender_id, sender_name, message, created_at)
            VALUES ("inquiry", :ref_id, "customer", :cid, :cname, :msg, NOW())
        ');
        $stmtIns->execute([
            ':ref_id' => $refId,
            ':cid'    => $customer['id'],
            ':cname'  => $customer['full_name'],
            ':msg'    => $replyMsg
        ]);

        // Update inquiries updated_at
        $stmtUpd = $db->prepare('UPDATE inquiries SET updated_at = NOW() WHERE id = :id');
        $stmtUpd->execute([':id' => $refId]);

        set_flash_message('success', 'Your response has been sent to our sales consultants regarding Inquiry Ref: ' . sanitize_output($inqRow['inquiry_code']) . '.');
        redirect(BASE_URL . 'customer-dashboard.php#item-inquiry-' . $refId);
    }
}

// -----------------------------------------------------------------------------
// 2. Fetch Customer's Fleet Requests (Quotes)
// -----------------------------------------------------------------------------
$stmtReq = $db->prepare('
    SELECT r.*,
    (SELECT COUNT(*) FROM request_items WHERE request_id = r.id) AS item_count
    FROM customer_requests r
    WHERE r.customer_id = :cid
    ORDER BY r.created_at DESC
');
$stmtReq->execute([':cid' => $customer['id']]);
$requests = $stmtReq->fetchAll();

// Fetch items and replies for each quote request
foreach ($requests as &$r) {
    // Items
    $stmtItems = $db->prepare('
        SELECT ri.*, t.truck_code, t.title, t.brand, t.price, t.tonnage_capacity,
        (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
        FROM request_items ri
        JOIN trucks t ON ri.truck_id = t.id
        WHERE ri.request_id = :request_id
    ');
    $stmtItems->execute([':request_id' => $r['id']]);
    $r['items'] = $stmtItems->fetchAll();

    // Replies Thread
    $stmtRep = $db->prepare('
        SELECT * FROM quote_replies 
        WHERE request_type = "quote" AND reference_id = :rid 
        ORDER BY created_at ASC
    ');
    $stmtRep->execute([':rid' => $r['id']]);
    $r['replies'] = $stmtRep->fetchAll();
}
unset($r);

// -----------------------------------------------------------------------------
// 3. Fetch Customer's Inquiries & Dealership Responses (matched by email)
// -----------------------------------------------------------------------------
$stmtInq = $db->prepare('
    SELECT i.*, t.truck_code, t.title AS truck_title,
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM inquiries i
    LEFT JOIN trucks t ON i.truck_id = t.id
    WHERE i.customer_email = :email
    ORDER BY i.id DESC
');
$stmtInq->execute([':email' => $customer['email']]);
$inquiries = $stmtInq->fetchAll();

// Fetch replies for each inquiry
foreach ($inquiries as &$inq) {
    $stmtRep = $db->prepare('
        SELECT * FROM quote_replies 
        WHERE request_type = "inquiry" AND reference_id = :inqid 
        ORDER BY created_at ASC
    ');
    $stmtRep->execute([':inqid' => $inq['id']]);
    $inq['replies'] = $stmtRep->fetchAll();
}
unset($inq);

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    <div class="container">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="section-tag">Account Overview</span>
                <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.2rem); margin-bottom: 0.25rem;">
                    Welcome, <?php echo sanitize_output($customer['full_name']); ?>
                </h1>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
                    Track your commercial truck quotes, view dealership responses, and reply directly to staff.
                </p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">
                    Browse Trucks
                </a>
                <a href="<?php echo BASE_URL; ?>customer-settings.php" class="btn btn-secondary" style="font-weight: 600;">
                    Settings
                </a>
                <a href="<?php echo BASE_URL; ?>customer-logout.php" class="btn btn-outline" style="border: 1px solid var(--color-border); background: var(--color-white); color: var(--color-text);">
                    Log Out
                </a>
            </div>
        </div>

        <!-- Section Navigation Pills -->
        <div style="display: flex; gap: 12px; margin-bottom: 2rem; border-bottom: 1px solid var(--color-border); padding-bottom: 1rem; flex-wrap: wrap;">
            <a href="#quotes-section" class="btn btn-outline btn-sm" style="font-weight: 700; border-color: var(--color-primary); color: var(--color-primary); background: rgba(217, 130, 91, 0.08);">
                My Quotes &amp; Fleet Sourcing (<?php echo count($requests); ?>)
            </a>
            <a href="#inquiries-section" class="btn btn-outline btn-sm" style="font-weight: 600;">
                Vehicle Inquiries (<?php echo count($inquiries); ?>)
            </a>
        </div>

        <!-- =====================================================================
             1. MY QUOTES & FLEET SOURCING REQUESTS (WITH INTERACTIVE REPLIES)
             ===================================================================== -->
        <div id="quotes-section" style="margin-bottom: 3.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                <h2 style="font-size: 1.35rem; color: var(--color-dark); margin: 0;">
                    Commercial Fleet Quotes (<?php echo count($requests); ?>)
                </h2>
                <span style="font-size: 0.85rem; color: var(--color-text-muted);">
                    Official price proposals and two-way staff consultation
                </span>
            </div>

            <?php if (!empty($requests)): ?>
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <?php foreach ($requests as $req): ?>
                        <div class="quote-card" id="item-quote-<?php echo (int)$req['id']; ?>">
                            
                            <!-- Quote Meta Bar -->
                            <div class="quote-meta-bar">
                                <div>
                                    <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700; display: block; margin-bottom: 2px;">Quote Reference</span>
                                    <span class="quote-ref-badge">
                                        #<?php echo sanitize_output($req['request_code']); ?>
                                    </span>
                                    <span style="font-size: 0.85rem; color: var(--color-text-muted); margin-left: 10px;">
                                        Requested on <?php echo date('M d, Y - g:ia', strtotime($req['created_at'])); ?>
                                    </span>
                                </div>

                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <?php 
                                        $statusBadge = match($req['status']) {
                                            'New' => 'badge-warning',
                                            'Assigned to Agent' => 'badge-primary',
                                            'Contacted' => 'badge-dark',
                                            'Closed' => 'badge-success',
                                            default => 'badge-subtle'
                                        };
                                    ?>
                                    <span class="badge <?php echo $statusBadge; ?>" style="font-size: 0.85rem; padding: 6px 12px;">
                                        ● <?php echo sanitize_output($req['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Requested Trucks -->
                            <?php if (!empty($req['items'])): ?>
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem;">
                                        Requested Fleet Vehicles (<?php echo count($req['items']); ?>)
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        <?php 
                                            $totalVal = 0;
                                            foreach ($req['items'] as $item): 
                                                $totalVal += ($item['price'] * $item['quantity']);
                                        ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; background: var(--color-bg-subtle); padding: 0.9rem 1.15rem; border-radius: var(--radius-sm); border: 1px solid var(--color-border); flex-wrap: wrap; gap: 0.75rem;">
                                                <div style="display: flex; gap: 1rem; align-items: center;">
                                                    <?php if (!empty($item['primary_image'])): ?>
                                                        <img src="<?php echo BASE_URL . 'assets/images/trucks/' . sanitize_output($item['primary_image']); ?>" alt="Thumb" style="width: 70px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <h4 style="margin: 0; font-size: 0.98rem; color: var(--color-dark);">
                                                            <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$item['truck_id']; ?>" target="_blank" style="color: inherit; text-decoration: none;">
                                                                <?php echo sanitize_output($item['title']); ?> &nearr;
                                                            </a>
                                                        </h4>
                                                        <span style="font-size: 0.8rem; color: var(--color-text-muted);">
                                                            Stock #<?php echo sanitize_output($item['truck_code']); ?> &bull; Capacity: <?php echo format_tonnage($item['tonnage_capacity']); ?> &bull; Qty: <?php echo (int)$item['quantity']; ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div style="font-weight: 800; color: var(--color-dark); font-size: 1.05rem;">
                                                    <?php echo format_currency($item['price']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($totalVal > 0): ?>
                                        <div style="display: flex; justify-content: flex-end; margin-top: 0.75rem; font-size: 0.92rem; color: var(--color-text-muted);">
                                            <span>Estimated Fleet Value:&nbsp;</span>
                                            <strong style="color: var(--color-primary); font-size: 1rem;"><?php echo format_currency($totalVal); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Customer's Initial Notes/Requirements -->
                            <?php if (!empty($req['notes'])): ?>
                                <div style="margin-bottom: 1.5rem; background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 1rem 1.25rem;">
                                    <strong style="font-size: 0.82rem; text-transform: uppercase; color: var(--color-text-muted); display: block; margin-bottom: 4px;">
                                        Your Initial Specifications / Request Notes:
                                    </strong>
                                    <div style="color: var(--color-dark); font-size: 0.92rem; line-height: 1.6;">
                                        <?php echo nl2br(sanitize_output($req['notes'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Assigned Sales Consultant Banner -->
                            <div style="background: var(--color-bg-subtle); border: 1px dashed var(--color-border); border-radius: var(--radius-sm); padding: 0.85rem 1.25rem; font-size: 0.88rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                                <div>
                                    <strong style="color: var(--color-dark);">Assigned Sales Consultant:</strong>
                                    <span style="color: var(--color-primary); font-weight: 700; margin-left: 4px;">
                                        <?php echo !empty($req['assigned_agent']) ? sanitize_output($req['assigned_agent']) : 'Dealership Commercial Fleet Desk'; ?>
                                    </span>
                                </div>
                                <div>
                                    <a href="https://wa.me/2347069219001?text=Hello%20Moal%20Sales%20Desk,%20following%20up%20on%20my%20quote%20<?php echo urlencode($req['request_code']); ?>" target="_blank" rel="noopener noreferrer" style="color: #16A34A; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                        <span>💬</span> WhatsApp Dealership Desk
                                    </a>
                                </div>
                            </div>

                            <!-- =========================================================
                                 TWO-WAY CONVERSATION & STAFF RESPONSE THREAD
                                 ========================================================= -->
                            <div class="quote-thread-container">
                                <div class="quote-thread-title">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span>💬 Dealership Responses &amp; Communication</span>
                                        <span class="badge badge-primary" style="font-size: 0.72rem; padding: 2px 7px;">
                                            <?php echo count($req['replies']); ?> <?php echo count($req['replies']) === 1 ? 'Message' : 'Messages'; ?>
                                        </span>
                                    </div>
                                    <span style="font-size: 0.78rem; color: #64748B; font-weight: normal;">
                                        Real-time dialogue with your assigned consultant
                                    </span>
                                </div>

                                <!-- Messages List -->
                                <div class="quote-messages-list">
                                    <?php if (!empty($req['replies'])): ?>
                                        <?php foreach ($req['replies'] as $rep): ?>
                                            <?php $isStaff = ($rep['sender_type'] === 'staff'); ?>
                                            <div class="quote-msg-bubble <?php echo $isStaff ? 'quote-msg-staff' : 'quote-msg-client'; ?>">
                                                <div class="quote-msg-header">
                                                    <div class="quote-msg-sender <?php echo $isStaff ? 'quote-sender-staff' : 'quote-sender-client'; ?>">
                                                        <?php if ($isStaff): ?>
                                                            <span>🏢 Dealership Consultant: <strong><?php echo sanitize_output($rep['sender_name']); ?></strong></span>
                                                        <?php else: ?>
                                                            <span>👤 You (Client Response): <strong><?php echo sanitize_output($rep['sender_name']); ?></strong></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="quote-msg-date">
                                                        <?php echo date('M j, Y - g:ia', strtotime($rep['created_at'])); ?>
                                                    </div>
                                                </div>
                                                <div class="quote-msg-content"><?php echo nl2br(sanitize_output($rep['message'])); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php elseif (!empty($req['admin_notes'])): ?>
                                        <!-- Fallback for legacy admin notes -->
                                        <div class="quote-msg-bubble quote-msg-staff">
                                            <div class="quote-msg-header">
                                                <div class="quote-msg-sender quote-sender-staff">
                                                    <span>🏢 Dealership Consultant: <strong><?php echo !empty($req['assigned_agent']) ? sanitize_output($req['assigned_agent']) : 'Moal Commercial Fleet Desk'; ?></strong></span>
                                                </div>
                                                <div class="quote-msg-date">
                                                    <?php echo date('M j, Y - g:ia', strtotime($req['updated_at'] ?: $req['created_at'])); ?>
                                                </div>
                                            </div>
                                            <div class="quote-msg-content"><?php echo nl2br(sanitize_output($req['admin_notes'])); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="quote-empty-notice">
                                            ⏳ <strong>Awaiting Dealership Response:</strong> Our fleet sales consultants are reviewing vehicle availability and preparing the official proforma quotation for this quote. You will see their response here and can reply directly.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Interactive Response Form (Client responds to staff) -->
                                <div class="quote-reply-box">
                                    <form method="POST" action="<?php echo BASE_URL; ?>customer-dashboard.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="customer_reply">
                                        <input type="hidden" name="reply_type" value="quote">
                                        <input type="hidden" name="reference_id" value="<?php echo (int)$req['id']; ?>">

                                        <label for="reply_quote_<?php echo $req['id']; ?>" style="font-size: 0.88rem; font-weight: 700; color: var(--color-dark); margin-bottom: 6px; display: block;">
                                            Respond to Dealership Staff regarding this Quote:
                                        </label>
                                        <textarea id="reply_quote_<?php echo $req['id']; ?>" name="reply_message" placeholder="Type your response to the staff's quote (e.g. asking about discounts, delivery arrangements, payment terms, or inspection schedule)..." required></textarea>
                                        
                                        <div class="quote-reply-actions">
                                            <button type="submit" class="btn btn-primary btn-sm" style="font-weight: 700; padding: 10px 22px;">
                                                Send Response &rarr;
                                            </button>
                                            <span style="font-size: 0.82rem; color: var(--color-text-muted);">
                                                Staff is notified immediately upon submitting your response.
                                            </span>
                                        </div>
                                    </form>
                                </div>

                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 3.5rem 2rem; text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">📋</div>
                    <h3 style="margin-bottom: 0.5rem; color: var(--color-dark);">No Active Fleet Quotes</h3>
                    <p style="color: var(--color-text-muted); max-width: 480px; margin: 0 auto 1.5rem auto;">
                        You have not submitted any commercial truck fleet requests yet. Browse our inventory to request a formal proforma quote.
                    </p>
                    <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">
                        Explore Truck Inventory &rarr;
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- =====================================================================
             2. VEHICLE INQUIRIES & DEALERSHIP RESPONSES (WITH TWO-WAY REPLIES)
             ===================================================================== -->
        <div id="inquiries-section" style="margin-bottom: 3rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                <h2 style="font-size: 1.35rem; color: var(--color-dark); margin: 0;">
                    Vehicle Inquiries (<?php echo count($inquiries); ?>)
                </h2>
                <span style="font-size: 0.85rem; color: var(--color-text-muted);">
                    Direct inquiries submitted for individual trucks in inventory
                </span>
            </div>

            <?php if (!empty($inquiries)): ?>
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <?php foreach ($inquiries as $inq): ?>
                        <div class="quote-card" id="item-inquiry-<?php echo (int)$inq['id']; ?>">
                            
                            <div class="quote-meta-bar">
                                <div>
                                    <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700; display: block; margin-bottom: 2px;">Inquiry Reference</span>
                                    <span class="quote-ref-badge">
                                        #<?php echo sanitize_output($inq['inquiry_code']); ?>
                                    </span>
                                    <span style="font-size: 0.85rem; color: var(--color-text-muted); margin-left: 10px;">
                                        Submitted on <?php echo date('M d, Y - g:ia', strtotime($inq['created_at'])); ?>
                                    </span>
                                </div>

                                <div>
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
                                    <span class="badge <?php echo $inqBadge; ?>">● <?php echo sanitize_output($inq['status']); ?></span>
                                </div>
                            </div>

                            <!-- Vehicle Details if linked -->
                            <?php if (!empty($inq['truck_code'])): ?>
                                <div style="display: flex; gap: 14px; align-items: center; margin-bottom: 1.25rem; background: var(--color-bg-subtle); padding: 12px 16px; border-radius: var(--radius-sm); border: 1px solid var(--color-border);">
                                    <?php if (!empty($inq['primary_image'])): ?>
                                        <img src="<?php echo BASE_URL . 'assets/images/trucks/' . sanitize_output($inq['primary_image']); ?>" alt="Thumb" style="width: 70px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php endif; ?>
                                    <div>
                                        <strong style="color: var(--color-dark); font-size: 1rem;">
                                            <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$inq['truck_id']; ?>" target="_blank" style="color: inherit; text-decoration: none;">
                                                <?php echo sanitize_output($inq['truck_title']); ?> &nearr;
                                            </a>
                                        </strong>
                                        <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 2px;">
                                            Stock Unit: #<?php echo sanitize_output($inq['truck_code']); ?> &bull; Type: <?php echo sanitize_output($inq['inquiry_type']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Original Message -->
                            <div style="margin-bottom: 1.25rem; background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 1rem 1.25rem;">
                                <strong style="font-size: 0.82rem; text-transform: uppercase; color: var(--color-text-muted); display: block; margin-bottom: 4px;">
                                    Your Original Inquiry:
                                </strong>
                                <div style="color: var(--color-dark); font-size: 0.92rem; line-height: 1.6;">
                                    <?php echo nl2br(sanitize_output($inq['message'])); ?>
                                </div>
                            </div>

                            <!-- Conversation Thread -->
                            <div class="quote-thread-container">
                                <div class="quote-thread-title">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span>💬 Staff Responses &amp; Follow-up</span>
                                        <span class="badge badge-primary" style="font-size: 0.72rem; padding: 2px 7px;">
                                            <?php echo count($inq['replies']); ?> Messages
                                        </span>
                                    </div>
                                </div>

                                <div class="quote-messages-list">
                                    <?php if (!empty($inq['replies'])): ?>
                                        <?php foreach ($inq['replies'] as $rep): ?>
                                            <?php $isStaff = ($rep['sender_type'] === 'staff'); ?>
                                            <div class="quote-msg-bubble <?php echo $isStaff ? 'quote-msg-staff' : 'quote-msg-client'; ?>">
                                                <div class="quote-msg-header">
                                                    <div class="quote-msg-sender <?php echo $isStaff ? 'quote-sender-staff' : 'quote-sender-client'; ?>">
                                                        <?php if ($isStaff): ?>
                                                            <span>🏢 Dealership Staff: <strong><?php echo sanitize_output($rep['sender_name']); ?></strong></span>
                                                        <?php else: ?>
                                                            <span>👤 You (Client Response): <strong><?php echo sanitize_output($rep['sender_name']); ?></strong></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="quote-msg-date">
                                                        <?php echo date('M j, Y - g:ia', strtotime($rep['created_at'])); ?>
                                                    </div>
                                                </div>
                                                <div class="quote-msg-content"><?php echo nl2br(sanitize_output($rep['message'])); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php elseif (!empty($inq['admin_notes'])): ?>
                                        <div class="quote-msg-bubble quote-msg-staff">
                                            <div class="quote-msg-header">
                                                <div class="quote-msg-sender quote-sender-staff">
                                                    <span>🏢 Dealership Staff Response</span>
                                                </div>
                                                <div class="quote-msg-date">
                                                    <?php echo date('M j, Y - g:ia', strtotime($inq['updated_at'] ?: $inq['created_at'])); ?>
                                                </div>
                                            </div>
                                            <div class="quote-msg-content"><?php echo nl2br(sanitize_output($inq['admin_notes'])); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="quote-empty-notice">
                                            ⏳ Dealership staff is reviewing your vehicle inquiry. Once an update is logged, you can reply directly here.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Client Response Form for Inquiry -->
                                <div class="quote-reply-box">
                                    <form method="POST" action="<?php echo BASE_URL; ?>customer-dashboard.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="customer_reply">
                                        <input type="hidden" name="reply_type" value="inquiry">
                                        <input type="hidden" name="reference_id" value="<?php echo (int)$inq['id']; ?>">

                                        <label for="reply_inq_<?php echo $inq['id']; ?>" style="font-size: 0.88rem; font-weight: 700; color: var(--color-dark); margin-bottom: 6px; display: block;">
                                            Reply to Dealership:
                                        </label>
                                        <textarea id="reply_inq_<?php echo $inq['id']; ?>" name="reply_message" placeholder="Type your follow-up message to the sales team regarding this vehicle inquiry..." required></textarea>
                                        
                                        <div class="quote-reply-actions">
                                            <button type="submit" class="btn btn-primary btn-sm" style="font-weight: 700; padding: 10px 22px;">
                                                Send Response &rarr;
                                            </button>
                                        </div>
                                    </form>
                                </div>

                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2.5rem 1.5rem; text-align: center;">
                    <p style="color: var(--color-text-muted); margin: 0;">
                        No individual vehicle inquiries recorded for this email.
                    </p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>