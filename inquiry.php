<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Inquiries & Custom Truck Requests
 * =============================================================================
 * Allows potential buyers to submit specific truck inquiries, general inquiries,
 * or custom truck procurement requests.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Inquiries & Custom Requests';
$db = getDB();

$errors = [];
$successInquiry = null;

// -----------------------------------------------------------------------------
// 1. Capture Pre-Filled Context Parameters (GET)
// -----------------------------------------------------------------------------
$truckId      = isset($_GET['truck_id']) ? (int)$_GET['truck_id'] : 0;
$inquiryType  = sanitize_input($_GET['type'] ?? 'General Inquiry');
$prefPurpose  = sanitize_input($_GET['purpose'] ?? '');
$prefTonnage  = sanitize_input($_GET['tonnage'] ?? '');
$prefBudget   = sanitize_input($_GET['budget'] ?? '');

$linkedTruck = null;
if ($truckId > 0) {
    $stmt = $db->prepare('SELECT id, truck_code, title, brand, model, price, purpose_category, availability_status FROM trucks WHERE id = :id');
    $stmt->execute([':id' => $truckId]);
    $linkedTruck = $stmt->fetch();
    if ($linkedTruck) {
        $inquiryType = 'Specific Truck';
    }
}

if ($inquiryType === 'custom') {
    $inquiryType = 'Custom Request';
} elseif ($inquiryType === 'recommendation') {
    $inquiryType = 'Recommendation Followup';
}

// -----------------------------------------------------------------------------
// 2. Handle Form Submission (POST)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF Protection Verification
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security token expired or invalid. Please refresh the page and try again.';
    }

    // Input Sanitization
    $custName       = sanitize_input($_POST['customer_name'] ?? '');
    $custEmail      = sanitize_input($_POST['customer_email'] ?? '');
    $custPhone      = sanitize_input($_POST['customer_phone'] ?? '');
    $typeSubmitted  = sanitize_input($_POST['inquiry_type'] ?? 'General Inquiry');
    $postTruckId    = !empty($_POST['truck_id']) ? (int)$_POST['truck_id'] : null;
    $budgetMin      = !empty($_POST['preferred_budget_min']) ? (float)$_POST['preferred_budget_min'] : null;
    $budgetMax      = !empty($_POST['preferred_budget_max']) ? (float)$_POST['preferred_budget_max'] : null;
    $tonnage        = !empty($_POST['preferred_tonnage']) ? (float)$_POST['preferred_tonnage'] : null;
    $message        = sanitize_input($_POST['message'] ?? '');

    // Form Validation
    if (empty($custName) || strlen($custName) < 3) {
        $errors[] = 'Please provide your full name (minimum 3 characters).';
    }

    if (empty($custEmail) || !filter_var($custEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (empty($custPhone) || strlen($custPhone) < 7) {
        $errors[] = 'Please provide a valid contact telephone number.';
    }

    if (empty($message) || strlen($message) < 10) {
        $errors[] = 'Please enter your message / operational requirements (minimum 10 characters).';
    }

    $validTypes = ['Specific Truck', 'Custom Request', 'Recommendation Followup', 'General Inquiry'];
    if (!in_array($typeSubmitted, $validTypes, true)) {
        $typeSubmitted = 'General Inquiry';
    }

    // Process Valid Submission
    if (empty($errors)) {
        
        // Generate Unique Reference Tracking Code (e.g. INQ-2026-7A9F1B)
        $inquiryCode = 'INQ-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

        try {
            $insertSql = '
                INSERT INTO inquiries (
                    inquiry_code, truck_id, inquiry_type, customer_name, 
                    customer_email, customer_phone, preferred_budget_min, 
                    preferred_budget_max, preferred_tonnage, message, status
                ) VALUES (
                    :code, :truck_id, :type, :name, 
                    :email, :phone, :b_min, 
                    :b_max, :tonnage, :msg, "Pending"
                )
            ';

            $stmtInsert = $db->prepare($insertSql);
            $stmtInsert->execute([
                ':code'     => $inquiryCode,
                ':truck_id' => $postTruckId,
                ':type'     => $typeSubmitted,
                ':name'     => $custName,
                ':email'    => $custEmail,
                ':phone'    => $custPhone,
                ':b_min'    => $budgetMin,
                ':b_max'    => $budgetMax,
                ':tonnage'  => $tonnage,
                ':msg'      => $message
            ]);

            $successInquiry = [
                'code'    => $inquiryCode,
                'name'    => $custName,
                'email'   => $custEmail,
                'type'    => $typeSubmitted,
                'message' => $message
            ];

        } catch (PDOException $e) {
            error_log('[Inquiry Submission Error] ' . $e->getMessage());
            $errors[] = 'An error occurred while saving your inquiry. Please try again or contact us directly.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a> &rsaquo; <span>Inquiries &amp; Custom Requests</span>
        </div>
        <h1>Customer Inquiries &amp; Custom Sourcing</h1>
        <p>Connect directly with Moal General Suppliers for truck purchases, fleet procurement, inspections, or custom orders.</p>
    </div>
</div>

<div class="container" style="max-width: 980px; margin-bottom: 4rem;">

    <?php if ($successInquiry): ?>
        <!-- Success Confirmation View -->
        <div style="background: #ffffff; border-radius: var(--radius-lg); border: 2px solid #22c55e; padding: 3rem 2rem; text-align: center; box-shadow: var(--shadow-md);">
            <div style="width: 60px; height: 60px; background: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem auto;">
                ✓
            </div>
            
            <span class="badge badge-success" style="font-size: 0.9rem; padding: 6px 14px; margin-bottom: 1rem;">Inquiry Successfully Logged</span>
            
            <h2 style="color: var(--primary-navy); font-size: 1.8rem; margin-bottom: 0.75rem;">Thank You, <?php echo sanitize_output($successInquiry['name']); ?>!</h2>
            
            <p style="color: var(--text-body); max-width: 600px; margin: 0 auto 1.5rem auto; font-size: 1.05rem; line-height: 1.6;">
                Your inquiry has been registered with Moal General Suppliers. Our commercial sales representatives will review your request and reach out to you within 24 business hours.
            </p>

            <div style="background: #f8fafc; border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; max-width: 450px; margin: 0 auto 2rem auto;">
                <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Your Tracking Reference</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent-orange); letter-spacing: 1px; margin-top: 4px;">
                    <?php echo sanitize_output($successInquiry['code']); ?>
                </div>
                <small style="color: #64748b;">Please retain this tracking code for any follow-up communications.</small>
            </div>

            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">Browse Truck Inventory</a>
                <a href="<?php echo BASE_URL; ?>inquiry.php" class="btn btn-outline">Submit Another Request</a>
            </div>
        </div>

    <?php else: ?>

        <!-- Submission Form Layout -->
        <div class="details-layout" style="grid-template-columns: 1fr 340px;">
            
            <!-- Main Form Card -->
            <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 2rem; box-shadow: var(--shadow-sm);">
                
                <h2 style="font-size: 1.35rem; color: var(--primary-navy); margin-bottom: 0.5rem;">Submit Your Request</h2>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                    Please fill out the form below. All inquiries are tracked securely in our dealership management system.
                </p>

                <?php if (!empty($errors)): ?>
                    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.9rem;">
                        <strong style="display: block; margin-bottom: 4px;">Please correct the following:</strong>
                        <ul style="margin-left: 1.25rem;">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo sanitize_output($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo BASE_URL; ?>inquiry.php<?php echo $truckId > 0 ? '?truck_id=' . $truckId : ''; ?>">
                    
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <?php if ($linkedTruck): ?>
                        <input type="hidden" name="truck_id" value="<?php echo (int)$linkedTruck['id']; ?>">
                    <?php endif; ?>

                    <!-- Contact Details -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label class="filter-label" for="customer_name">Full Name *</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control" required placeholder="e.g. Alhaji Aminu Bello" value="<?php echo sanitize_output($_POST['customer_name'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="filter-label" for="customer_phone">Phone / WhatsApp Number *</label>
                            <input type="tel" name="customer_phone" id="customer_phone" class="form-control" required placeholder="e.g. +234 803 123 4567" value="<?php echo sanitize_output($_POST['customer_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label class="filter-label" for="customer_email">Email Address *</label>
                        <input type="email" name="customer_email" id="customer_email" class="form-control" required placeholder="name@company.com" value="<?php echo sanitize_output($_POST['customer_email'] ?? ''); ?>">
                    </div>

                    <!-- Inquiry Classification -->
                    <div style="margin-bottom: 1.25rem;">
                        <label class="filter-label" for="inquiry_type">Inquiry Nature</label>
                        <select name="inquiry_type" id="inquiry_type" class="form-control">
                            <option value="Specific Truck" <?php echo ($inquiryType === 'Specific Truck') ? 'selected' : ''; ?>>Inquiry for Specific Truck in Inventory</option>
                            <option value="Custom Request" <?php echo ($inquiryType === 'Custom Request') ? 'selected' : ''; ?>>Custom Truck Procurement Request (Sourcing)</option>
                            <option value="Recommendation Followup" <?php echo ($inquiryType === 'Recommendation Followup') ? 'selected' : ''; ?>>Follow-Up on Rule-Based Recommendation</option>
                            <option value="General Inquiry" <?php echo ($inquiryType === 'General Inquiry') ? 'selected' : ''; ?>>General Dealership &amp; Services Inquiry</option>
                        </select>
                    </div>

                    <!-- Budget & Payload Preferences (Optional for custom requests) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label class="filter-label" for="preferred_budget_max">Budget Estimate (₦ Max)</label>
                            <input type="number" name="preferred_budget_max" id="preferred_budget_max" class="form-control" placeholder="e.g. 45000000" value="<?php echo sanitize_output($_POST['preferred_budget_max'] ?? ''); ?>">
                        </div>

                        <div>
                            <label class="filter-label" for="preferred_tonnage">Required Capacity (Tons)</label>
                            <input type="number" step="0.5" name="preferred_tonnage" id="preferred_tonnage" class="form-control" placeholder="e.g. 30" value="<?php echo sanitize_output($_POST['preferred_tonnage'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Message Body -->
                    <div style="margin-bottom: 1.75rem;">
                        <label class="filter-label" for="message">Detailed Message / Specifications *</label>
                        <textarea name="message" id="message" rows="5" class="form-control" required placeholder="Describe your operational needs, desired brand/model, inspection preferences, or delivery location..."><?php 
                            if (!empty($_POST['message'])) {
                                echo sanitize_output($_POST['message']);
                            } elseif ($linkedTruck) {
                                echo 'Hello Moal General Suppliers, I am interested in inspecting the ' . sanitize_output($linkedTruck['title']) . ' (' . sanitize_output($linkedTruck['truck_code']) . ') listed for ' . format_currency($linkedTruck['price']) . '. Please contact me with details.';
                            } elseif ($prefPurpose) {
                                echo 'Hello, I am seeking a commercial truck for ' . sanitize_output($prefPurpose) . ' operations. Please review and provide available procurement options.';
                            }
                        ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 12px 20px; font-size: 1rem;">
                        Submit Inquiry to Moal General Suppliers &rarr;
                    </button>

                </form>

            </div>

            <!-- Sidebar Context / Dealership Card -->
            <aside>
                <?php if ($linkedTruck): ?>
                    <div style="background: #fff; border-radius: var(--radius-lg); border: 2px solid var(--accent-orange); padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);">
                        <span class="badge badge-orange" style="margin-bottom: 6px;">Vehicle of Interest</span>
                        <div class="truck-card-code"><?php echo sanitize_output($linkedTruck['truck_code']); ?></div>
                        <h4 style="color: var(--primary-navy); margin-bottom: 0.5rem; font-size: 1.1rem;"><?php echo sanitize_output($linkedTruck['title']); ?></h4>
                        <div style="font-size: 1.3rem; font-weight: 800; color: var(--accent-orange); margin-bottom: 0.75rem;">
                            <?php echo format_currency($linkedTruck['price']); ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                            Category: <strong><?php echo sanitize_output($linkedTruck['purpose_category']); ?></strong><br>
                            Status: <strong><?php echo sanitize_output($linkedTruck['availability_status']); ?></strong>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 1.5rem; box-shadow: var(--shadow-sm);">
                    <h4 style="color: var(--primary-navy); margin-bottom: 0.75rem; font-size: 1rem;">Why Inquire with Moal?</h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 1rem;">
                        Moal General Suppliers provides direct physical inspection access, authentic vehicle documents, and professional procurement advisory.
                    </p>
                    <div style="font-size: 0.85rem; color: var(--text-body); line-height: 1.8;">
                        <div>📍 <strong>Dealership Base:</strong> Abuja &amp; Nationwide Delivery</div>
                        <div>⏱ <strong>Response Time:</strong> Within 24 hours</div>
                        <div>🛡 <strong>Inquiry Tracking:</strong> Verified Reference Code</div>
                    </div>
                </div>
            </aside>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
