<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Fleet Request Submission & Confirmation
 * =============================================================================
 * Generates unique reference code tied to customer profile & selected truck(s).
 * Dispatches request directly to dealership admin queue for offline fulfillment.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Enforce Customer Login
require_customer_login();

$customer = get_logged_in_customer();
$pageTitle = 'Fleet Sourcing Request';
$db = getDB();

$requestCode = sanitize_input($_GET['code'] ?? '');
$confirmedRequest = null;
$selectedTruck = null;
$truckId = (int)($_GET['truck_id'] ?? $_POST['truck_id'] ?? 0);
$truckIds = $_POST['truck_ids'] ?? [];

// If viewing an already created request confirmation
if (!empty($requestCode)) {
    try {
        $stmtReq = $db->prepare('
            SELECT r.*, c.full_name, c.phone, c.email, c.delivery_address, c.business_name
            FROM customer_requests r
            JOIN customers c ON r.customer_id = c.id
            WHERE r.request_code = :code AND r.customer_id = :customer_id
            LIMIT 1
        ');
        $stmtReq->execute([
            ':code' => $requestCode,
            ':customer_id' => $customer['id']
        ]);
        $confirmedRequest = $stmtReq->fetch();

        if ($confirmedRequest) {
            // Fetch requested trucks
            $stmtItems = $db->prepare('
                SELECT ri.*, t.truck_code, t.title, t.brand, t.model, t.price, t.tonnage_capacity, t.condition_type,
                (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
                FROM request_items ri
                JOIN trucks t ON ri.truck_id = t.id
                WHERE ri.request_id = :request_id
            ');
            $stmtItems->execute([':request_id' => $confirmedRequest['id']]);
            $confirmedRequest['items'] = $stmtItems->fetchAll();
        }
    } catch (Exception $e) {
        error_log('[Request View Error] ' . $e->getMessage());
    }
}

// Handle new request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($confirmedRequest)) {
    $notes = sanitize_input($_POST['notes'] ?? '');
    $selectedTruckIds = [];

    if ($truckId > 0) {
        $selectedTruckIds[] = $truckId;
    } elseif (!empty($truckIds) && is_array($truckIds)) {
        foreach ($truckIds as $tid) {
            $tidInt = (int)$tid;
            if ($tidInt > 0) {
                $selectedTruckIds[] = $tidInt;
            }
        }
    }

    if (empty($selectedTruckIds)) {
        set_flash_message('error', 'Please select at least one truck product to submit a request.');
        redirect(BASE_URL . 'inventory.php');
    }

    try {
        $newCode = generate_request_code();

        $db->beginTransaction();

        $stmtInsert = $db->prepare('
            INSERT INTO customer_requests (request_code, customer_id, notes, status)
            VALUES (:request_code, :customer_id, :notes, "New")
        ');
        $stmtInsert->execute([
            ':request_code' => $newCode,
            ':customer_id' => $customer['id'],
            ':notes' => !empty($notes) ? $notes : null
        ]);
        $requestId = (int)$db->lastInsertId();

        $stmtItemInsert = $db->prepare('
            INSERT INTO request_items (request_id, truck_id, quantity)
            VALUES (:request_id, :truck_id, 1)
        ');
        foreach ($selectedTruckIds as $tid) {
            $stmtItemInsert->execute([
                ':request_id' => $requestId,
                ':truck_id' => $tid
            ]);
        }

        $db->commit();

        set_flash_message('success', 'Your fleet request has been successfully generated! A sales advisor will be in touch shortly.');
        redirect(BASE_URL . 'customer-request.php?code=' . urlencode($newCode));

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[Request Submission Error] ' . $e->getMessage());
        set_flash_message('error', 'Failed to process request. Please try again.');
        redirect(BASE_URL . 'inventory.php');
    }
}

// Fetch truck details if preparing a request for a specific truck
if ($truckId > 0 && empty($confirmedRequest)) {
    try {
        $stmtTruck = $db->prepare('
            SELECT t.*, 
            (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
            FROM trucks t
            WHERE t.id = :id
            LIMIT 1
        ');
        $stmtTruck->execute([':id' => $truckId]);
        $selectedTruck = $stmtTruck->fetch();
    } catch (Exception $e) {
        error_log('[Truck Fetch Error] ' . $e->getMessage());
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">

    <?php if ($confirmedRequest): ?>
        <!-- ================================================================= -->
        <!-- CONFIRMATION SCREEN: "We'll Get Back to You" Screen               -->
        <!-- ================================================================= -->
        <div class="request-confirmation-card">
            
            <div class="confirmation-icon-box">
                <span style="font-size: 2.8rem;"></span>
            </div>

            <div class="confirmation-badge">Request Submitted Successfully</div>
            
            <h1 style="font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">
                We'll Get Back to You!
            </h1>
            
            <p style="color: #cbd5e1; font-size: 1.05rem; max-width: 680px; margin: 0 auto 1.5rem auto; line-height: 1.6;">
                Thank you, <strong><?php echo sanitize_output($confirmedRequest['full_name']); ?></strong>. Your commercial fleet sourcing request has been registered in our dealership system.
            </p>

            <!-- Reference Code Banner -->
            <div class="request-code-banner">
                <span class="code-label">YOUR UNIQUE REQUEST REFERENCE CODE</span>
                <span class="code-value"><?php echo sanitize_output($confirmedRequest['request_code']); ?></span>
                <span class="code-hint">Keep this code handy for all phone, WhatsApp, and email follow-ups.</span>
            </div>

            <!-- Customer Details Summary (Auto-linked) -->
            <div class="confirmation-details-grid">
                <div class="detail-box">
                    <span class="detail-label"> Registered Phone</span>
                    <span class="detail-val"><?php echo sanitize_output($confirmedRequest['phone']); ?></span>
                </div>
                <div class="detail-box">
                    <span class="detail-label"> Email Address</span>
                    <span class="detail-val"><?php echo sanitize_output($confirmedRequest['email']); ?></span>
                </div>
                <div class="detail-box">
                    <span class="detail-label"> Destination / Operating Location</span>
                    <span class="detail-val"><?php echo sanitize_output($confirmedRequest['delivery_address']); ?></span>
                </div>
                <div class="detail-box">
                    <span class="detail-label"> Business / Entity</span>
                    <span class="detail-val"><?php echo !empty($confirmedRequest['business_name']) ? sanitize_output($confirmedRequest['business_name']) : 'Private Commercial Buyer'; ?></span>
                </div>
            </div>

            <!-- Requested Trucks List -->
            <?php if (!empty($confirmedRequest['items'])): ?>
                <div style="text-align: left; margin-top: 2rem; background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.08);">
                    <h3 style="color: #fff; font-size: 1.1rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">
                         Requested Commercial Products (<?php echo count($confirmedRequest['items']); ?>)
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($confirmedRequest['items'] as $item): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; background: rgba(255,255,255,0.03); padding: 10px 14px; border-radius: 6px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if (!empty($item['primary_image'])): ?>
                                        <img src="<?php echo BASE_URL . sanitize_output($item['primary_image']); ?>" alt="<?php echo sanitize_output($item['title']); ?>" style="width: 60px; height: 44px; object-fit: cover; border-radius: 4px;">
                                    <?php endif; ?>
                                    <div>
                                        <strong style="color: #fff; display: block; font-size: 0.95rem;"><?php echo sanitize_output($item['title']); ?></strong>
                                        <span style="font-size: 0.8rem; color: #94a3b8;">Code: <?php echo sanitize_output($item['truck_code']); ?> &bull; <?php echo sanitize_output($item['tonnage_capacity']); ?> Tons &bull; <?php echo sanitize_output($item['condition_type']); ?></span>
                                    </div>
                                </div>
                                <div style="font-weight: 700; color: var(--accent-orange); font-size: 1.05rem;">
                                    <?php echo format_naira((float)$item['price']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dealership Followup Notice -->
            <div class="next-steps-panel">
                <h4>What Happens Next?</h4>
                <ol>
                    <li><strong>Offline Agent Assignment:</strong> Our Ojodu Berger dealership team is reviewing your selected trucks and logistics destination.</li>
                    <li><strong>Direct Contact:</strong> A certified commercial sales agent will contact you via phone or WhatsApp at <strong><?php echo sanitize_output($confirmedRequest['phone']); ?></strong>.</li>
                    <li><strong>Proforma &amp; Customs Verification:</strong> We will provide authentic Single Goods Declaration (SGD) customs documents and finalize payment &amp; delivery schedules offline.</li>
                </ol>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>customer-dashboard.php" class="btn btn-primary" style="padding: 12px 24px;">
                    View in Customer Dashboard &rarr;
                </a>
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-outline" style="border-color: #cbd5e1; color: #cbd5e1; padding: 12px 20px;">
                    Browse More Trucks
                </a>
            </div>

        </div>

    <?php elseif ($selectedTruck): ?>
        <!-- ================================================================= -->
        <!-- CONFIRM REQUEST SUBMISSION FOR SELECTED TRUCK                     -->
        <!-- ================================================================= -->
        <div class="auth-card" style="max-width: 680px; margin: 0 auto;">
            <div class="auth-header">
                <div class="auth-badge">Commercial Fleet Request</div>
                <h1 class="auth-title">Submit Sourcing Request</h1>
                <p class="auth-subtitle">
                    Request an official quote and sales agent follow-up for this commercial vehicle.
                </p>
            </div>

            <!-- Selected Truck Overview -->
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center;">
                <?php if (!empty($selectedTruck['primary_image'])): ?>
                    <img src="<?php echo BASE_URL . sanitize_output($selectedTruck['primary_image']); ?>" alt="<?php echo sanitize_output($selectedTruck['title']); ?>" style="width: 100px; height: 75px; object-fit: cover; border-radius: 6px; flex-shrink: 0;">
                <?php endif; ?>
                <div>
                    <span style="font-size: 0.75rem; background: var(--primary-navy); color: #fff; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                        Stock #<?php echo sanitize_output($selectedTruck['truck_code']); ?>
                    </span>
                    <h3 style="font-size: 1.05rem; color: var(--text-dark); margin: 4px 0;">
                        <?php echo sanitize_output($selectedTruck['title']); ?>
                    </h3>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        <?php echo sanitize_output($selectedTruck['tonnage_capacity']); ?> Tons &bull; <?php echo sanitize_output($selectedTruck['condition_type']); ?> &bull; <?php echo sanitize_output($selectedTruck['wheel_configuration']); ?>
                    </div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: var(--accent-orange); margin-top: 4px;">
                        <?php echo format_naira((float)$selectedTruck['price']); ?>
                    </div>
                </div>
            </div>

            <!-- Auto-linked Profile Info Notice -->
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 12px 16px; margin-bottom: 1.5rem; font-size: 0.88rem; color: #1e40af;">
                <strong>Auto-Linked Account Details:</strong><br>
                This request will automatically be filed under <strong><?php echo sanitize_output($customer['full_name']); ?></strong> (Phone: <?php echo sanitize_output($customer['phone']); ?>, Email: <?php echo sanitize_output($customer['email']); ?>, Delivery Yard: <?php echo sanitize_output($customer['delivery_address']); ?>).
            </div>

            <form method="POST" action="<?php echo BASE_URL; ?>customer-request.php">
                <input type="hidden" name="truck_id" value="<?php echo (int)$selectedTruck['id']; ?>">

                <div class="form-group">
                    <label for="notes" class="form-label">Special Fleet Requirements / Custom Notes <span class="text-muted">(Optional)</span></label>
                    <textarea id="notes" name="notes" rows="3" class="form-control" placeholder="e.g. Inquiring about financing terms, delivery timeline to Port Harcourt, or body fabrication specifications..."></textarea>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 13px; font-size: 1.05rem;">
                        Submit Request &amp; Generate Code &rarr;
                    </button>
                    <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-secondary" style="padding: 13px 18px;">
                        Cancel
                    </a>
                </div>
            </form>

            <div style="margin-top: 1.25rem; text-align: center; font-size: 0.8rem; color: #64748b;">
                ℹ️ Submitting this request does not charge your card. A dealership representative will reach out directly.
            </div>
        </div>

    <?php else: ?>
        <!-- No Truck Selected -->
        <div style="text-align: center; padding: 3rem 1rem;">
            <h2>No Product Selected for Request</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Please select a commercial truck from our inventory to generate an official sourcing request.</p>
            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">Browse Commercial Inventory &rarr;</a>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
