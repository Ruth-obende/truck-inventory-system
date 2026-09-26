<?php
/**
 * =============================================================================
 * Moal General Suppliers - Admin Request Details & Offline Sales Management
 * =============================================================================
 * View customer request, update status, assign dealership agent, and log notes.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$requestId = (int)($_GET['id'] ?? 0);
if ($requestId <= 0) {
    redirect(ADMIN_URL . 'requests.php');
}

$db = getDB();
$pageTitle = 'Request Management';

// Handle Form Update (Status, Agent, Notes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = sanitize_input($_POST['status'] ?? 'New');
    $assignedAgent = sanitize_input($_POST['assigned_agent'] ?? '');
    $adminNotes = sanitize_input($_POST['admin_notes'] ?? '');

    if (in_array($status, ['New', 'Assigned to Agent', 'Contacted', 'Closed'], true)) {
        try {
            $stmtUpdate = $db->prepare('
                UPDATE customer_requests 
                SET status = :status, assigned_agent = :assigned_agent, admin_notes = :admin_notes 
                WHERE id = :id
            ');
            $stmtUpdate->execute([
                ':status' => $status,
                ':assigned_agent' => !empty($assignedAgent) ? $assignedAgent : null,
                ':admin_notes' => !empty($adminNotes) ? $adminNotes : null,
                ':id' => $requestId
            ]);

            set_flash_message('success', 'Customer request updated successfully.');
            redirect(ADMIN_URL . 'request-details.php?id=' . $requestId);

        } catch (Exception $e) {
            error_log('[Admin Request Update Error] ' . $e->getMessage());
            set_flash_message('error', 'Failed to update request.');
        }
    }
}

// Fetch Request Record
$request = null;
try {
    $stmt = $db->prepare('
        SELECT r.*, c.full_name, c.phone, c.email, c.delivery_address, c.business_name, c.created_at AS customer_since
        FROM customer_requests r
        JOIN customers c ON r.customer_id = c.id
        WHERE r.id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => $requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        set_flash_message('error', 'Request record not found.');
        redirect(ADMIN_URL . 'requests.php');
    }

    // Fetch Requested Items
    $stmtItems = $db->prepare('
        SELECT ri.*, t.truck_code, t.title, t.brand, t.model, t.price, t.tonnage_capacity, t.condition_type, t.transmission, t.wheel_configuration, t.availability_status,
        (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
        FROM request_items ri
        JOIN trucks t ON ri.truck_id = t.id
        WHERE ri.request_id = :request_id
    ');
    $stmtItems->execute([':request_id' => $requestId]);
    $request['items'] = $stmtItems->fetchAll();

} catch (Exception $e) {
    error_log('[Admin Request Fetch Error] ' . $e->getMessage());
    redirect(ADMIN_URL . 'requests.php');
}

$waPhone = preg_replace('/[^0-9]/', '', $request['phone']);
if (str_starts_with($waPhone, '0')) {
    $waPhone = '234' . substr($waPhone, 1);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <a href="<?php echo ADMIN_URL; ?>requests.php" style="color: var(--accent-orange); text-decoration: none; font-size: 0.88rem; font-weight: 600;">&larr; Back to Requests Queue</a>
        <h1 class="admin-page-title" style="margin-top: 4px;">
            Request: <span style="font-family: monospace; color: var(--accent-orange);"><?php echo sanitize_output($request['request_code']); ?></span>
        </h1>
        <p class="admin-page-subtitle">Submitted on <?php echo date('F d, Y \a\t h:i A', strtotime($request['created_at'])); ?></p>
    </div>

    <!-- Quick Communication Action Buttons -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="https://wa.me/<?php echo $waPhone; ?>?text=Hello%20<?php echo urlencode($request['full_name']); ?>,%20I%20am%20following%20up%20from%20Moal%20General%20Suppliers%20regarding%20your%20fleet%20request%20<?php echo urlencode($request['request_code']); ?>" target="_blank" class="btn btn-sm" style="background: var(--admin-orange, #D9825B); color: #fff; padding: 8px 14px; font-weight: 600;">
             WhatsApp Chat
        </a>
        <a href="tel:<?php echo sanitize_output($request['phone']); ?>" class="btn btn-sm btn-navy" style="padding: 8px 14px; font-weight: 600;">
             Direct Call
        </a>
        <a href="mailto:<?php echo sanitize_output($request['email']); ?>" class="btn btn-sm btn-secondary" style="padding: 8px 14px;">
             Send Email
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 2rem; margin-top: 1.5rem;" class="admin-detail-layout">
    
    <!-- Left Column: Requested Products & Notes -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Requested Products Card -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="font-size: 1.1rem; color: var(--admin-navy); margin-bottom: 1.25rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;">
                 Requested Commercial Vehicles (<?php echo count($request['items']); ?>)
            </h3>

            <?php if (!empty($request['items'])): ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($request['items'] as $item): ?>
                        <div style="display: flex; gap: 1rem; align-items: center; justify-content: space-between; flex-wrap: wrap; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <?php if (!empty($item['primary_image'])): ?>
                                    <img src="<?php echo BASE_URL . sanitize_output($item['primary_image']); ?>" alt="<?php echo sanitize_output($item['title']); ?>" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                                <?php endif; ?>
                                <div>
                                    <span style="font-size: 0.75rem; background: var(--admin-navy); color: #fff; padding: 2px 6px; border-radius: 3px; font-weight: 600;">
                                        Stock #<?php echo sanitize_output($item['truck_code']); ?>
                                    </span>
                                    <h4 style="margin: 4px 0; font-size: 1rem; color: #0f172a;">
                                        <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$item['truck_id']; ?>" target="_blank" style="color: inherit; text-decoration: none;">
                                            <?php echo sanitize_output($item['title']); ?> &nearr;
                                        </a>
                                    </h4>
                                    <div style="font-size: 0.8rem; color: #64748b;">
                                        <?php echo sanitize_output($item['tonnage_capacity']); ?> Tons &bull; <?php echo sanitize_output($item['wheel_configuration']); ?> &bull; <?php echo sanitize_output($item['condition_type']); ?>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.15rem; font-weight: 800; color: var(--accent-orange);">
                                    <?php echo format_naira((float)$item['price']); ?>
                                </div>
                                <span style="font-size: 0.75rem; color: #166534; background: #dcfce7; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                    <?php echo sanitize_output($item['availability_status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #64748b;">No products attached.</p>
            <?php endif; ?>
        </div>

        <!-- Customer Special Requirements / Notes Card -->
        <?php if (!empty($request['notes'])): ?>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size: 1rem; color: var(--admin-navy); margin-bottom: 0.75rem;">
                     Customer Special Instructions / Notes
                </h3>
                <div style="background: #fffbeb; border-left: 4px solid var(--admin-orange, #D9825B); padding: 12px 16px; font-size: 0.92rem; color: var(--admin-text-main, #1F2421); line-height: 1.6; border-radius: 4px;">
                    <?php echo nl2br(sanitize_output($request['notes'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Dealership Workflow Guidance -->
        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 1.25rem; font-size: 0.88rem; color: #0369a1; line-height: 1.6;">
            <strong>Dealership Sales Procedure:</strong>
            <ol style="margin: 6px 0 0 18px; padding: 0;">
                <li>Assign this request to an available sales consultant.</li>
                <li>Contact customer via WhatsApp or phone to confirm vehicle availability and transit logistics.</li>
                <li>Issue authentic proforma invoice and schedule physical inspection at Ojodu Berger yard.</li>
                <li>Once transaction terms are agreed offline, update status to <strong>Closed</strong>.</li>
            </ol>
        </div>

    </div>

    <!-- Right Column: Status & Agent Assignment Form + Customer Profile Card -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Management Update Form -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="font-size: 1.1rem; color: var(--admin-navy); margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                 Status &amp; Agent Allocation
            </h3>

            <form method="POST" action="<?php echo ADMIN_URL; ?>request-details.php?id=<?php echo $requestId; ?>">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="status" class="form-label" style="font-weight: 700;">Request Status</label>
                    <select id="status" name="status" class="form-control" style="font-weight: 600;">
                        <option value="New" <?php echo ($request['status'] === 'New') ? 'selected' : ''; ?>>● New (Unassigned / Pending)</option>
                        <option value="Assigned to Agent" <?php echo ($request['status'] === 'Assigned to Agent') ? 'selected' : ''; ?>>● Assigned to Agent</option>
                        <option value="Contacted" <?php echo ($request['status'] === 'Contacted') ? 'selected' : ''; ?>>● Contacted (In Negotiation)</option>
                        <option value="Closed" <?php echo ($request['status'] === 'Closed') ? 'selected' : ''; ?>>● Closed (Completed / Archived)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="assigned_agent" class="form-label" style="font-weight: 700;">Assigned Dealership Agent</label>
                    <input type="text" id="assigned_agent" name="assigned_agent" class="form-control" value="<?php echo sanitize_output($request['assigned_agent'] ?? ''); ?>" placeholder="e.g. Alhaji Mustapha (Lead Consultant)">
                    <small style="font-size: 0.75rem; color: #64748b;">Staff member assigned to handle customer calls and proforma invoices.</small>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="admin_notes" class="form-label" style="font-weight: 700;">Internal Follow-up Log / Notes</label>
                    <textarea id="admin_notes" name="admin_notes" rows="4" class="form-control" placeholder="e.g. Spoke with customer on WhatsApp. Scheduled inspection for Thursday morning..."><?php echo sanitize_output($request['admin_notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 11px;">
                    Update Request Status &rarr;
                </button>
            </form>
        </div>

        <!-- Customer Profile Card -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="font-size: 1.1rem; color: var(--admin-navy); margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                 Customer Account Details
            </h3>

            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.88rem;">
                <div>
                    <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block;">Full Name:</span>
                    <strong style="color: #0f172a; font-size: 1rem;"><?php echo sanitize_output($request['full_name']); ?></strong>
                </div>

                <div>
                    <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block;">Company / Entity:</span>
                    <span style="color: #0f172a; font-weight: 600;"><?php echo !empty($request['business_name']) ? sanitize_output($request['business_name']) : 'Private Commercial Buyer'; ?></span>
                </div>

                <div>
                    <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block;">Direct Phone / WhatsApp:</span>
                    <a href="tel:<?php echo sanitize_output($request['phone']); ?>" style="color: var(--admin-orange, #D9825B); font-weight: 700; text-decoration: none;"><?php echo sanitize_output($request['phone']); ?></a>
                </div>

                <div>
                    <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block;">Email:</span>
                    <a href="mailto:<?php echo sanitize_output($request['email']); ?>" style="color: var(--admin-orange, #D9825B); text-decoration: none;"><?php echo sanitize_output($request['email']); ?></a>
                </div>

                <div>
                    <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block;">Delivery Yard / Operating Location:</span>
                    <span style="color: #334155; line-height: 1.4;"><?php echo sanitize_output($request['delivery_address']); ?></span>
                </div>

                <div>
                    <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block;">Account Registered On:</span>
                    <span style="color: #64748b;"><?php echo date('M d, Y', strtotime($request['customer_since'])); ?></span>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
