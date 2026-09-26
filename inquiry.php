<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Inquiry & Request for Quote
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Request a Quote & Customer Inquiry';
$db = getDB();

$errors = [];
$successInquiry = null;

// Check if customer is logged in for auto-filling contact information
$isLoggedIn = is_customer_logged_in();
$loggedInCust = $isLoggedIn ? get_logged_in_customer() : null;

// -----------------------------------------------------------------------------
// 1. Capture Pre-Filled Context Parameters (GET)
// -----------------------------------------------------------------------------
$truckId      = isset($_GET['truck_id']) ? (int)$_GET['truck_id'] : 0;
$inquiryType  = sanitize_input($_GET['type'] ?? 'General Inquiry');
$prefPurpose  = sanitize_input($_GET['purpose'] ?? '');
$prefTonnage  = sanitize_input($_GET['tonnage'] ?? '');

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
    
    // CSRF Protection
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security token expired or invalid. Please refresh the page and submit again.';
    }

    $custName       = sanitize_input($_POST['customer_name'] ?? '');
    $custEmail      = sanitize_input($_POST['customer_email'] ?? '');
    $custPhone      = sanitize_input($_POST['customer_phone'] ?? '');
    $typeSubmitted  = sanitize_input($_POST['inquiry_type'] ?? 'General Inquiry');
    $postTruckId    = !empty($_POST['truck_id']) ? (int)$_POST['truck_id'] : null;
    $budgetMax      = !empty($_POST['preferred_budget_max']) ? (float)$_POST['preferred_budget_max'] : null;
    $tonnage        = !empty($_POST['preferred_tonnage']) ? (float)$_POST['preferred_tonnage'] : null;
    $message        = sanitize_input($_POST['message'] ?? '');

    // Validation
    if (empty($custName) || strlen($custName) < 3) {
        $errors[] = 'Please provide your full name (minimum 3 characters).';
    }

    if (empty($custEmail) || !filter_var($custEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($custPhone) || strlen($custPhone) < 7) {
        $errors[] = 'Please provide a valid telephone/WhatsApp contact number.';
    }

    if (empty($message) || strlen($message) < 8) {
        $errors[] = 'Please include details or questions in your message.';
    }

    $validTypes = ['Specific Truck', 'Custom Request', 'Recommendation Followup', 'General Inquiry'];
    if (!in_array($typeSubmitted, $validTypes, true)) {
        $typeSubmitted = 'General Inquiry';
    }

    if (empty($errors)) {
        $inquiryCode = generate_inquiry_code();

        try {
            $insertSql = '
                INSERT INTO inquiries (
                    inquiry_code, truck_id, inquiry_type, customer_name, 
                    customer_email, customer_phone, preferred_budget_max, 
                    preferred_tonnage, message, status
                ) VALUES (
                    :code, :truck_id, :type, :name, 
                    :email, :phone, :b_max, 
                    :tonnage, :msg, "Pending"
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
                ':b_max'    => $budgetMax,
                ':tonnage'  => $tonnage,
                ':msg'      => $message
            ]);

            $successInquiry = [
                'code'   => $inquiryCode,
                'name'   => $custName,
                'phone'  => $custPhone,
                'email'  => $custEmail,
                'type'   => $typeSubmitted,
                'truck'  => $linkedTruck ? $linkedTruck['title'] : null
            ];

        } catch (Exception $e) {
            error_log('[Inquiry Submission Error] ' . $e->getMessage());
            $errors[] = 'A server error occurred while processing your request. Please call our sales desk directly.';
        }
    }
}

// Pre-fill values based on logged in customer or POST data
$formName = $_POST['customer_name'] ?? ($loggedInCust['full_name'] ?? '');
$formEmail = $_POST['customer_email'] ?? ($loggedInCust['email'] ?? '');
$formPhone = $_POST['customer_phone'] ?? ($loggedInCust['phone'] ?? '');

// Fetch all available trucks for select dropdown
$allTrucks = $db->query('SELECT id, truck_code, title, price FROM trucks WHERE availability_status = "Available" ORDER BY title ASC')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem;">
    <div class="container" style="max-width: 900px;">
        
        <?php if ($successInquiry): ?>
            <!-- Success Confirmation Card -->
            <div style="background: var(--color-white); border: 2px solid var(--color-primary); border-radius: var(--radius-lg); padding: clamp(2rem, 5vw, 3.5rem); text-align: center; box-shadow: var(--shadow-md);">
                <div style="font-size: 3.5rem; margin-bottom: 1rem;"></div>
                <span class="badge badge-primary" style="font-size: 0.9rem; padding: 6px 14px; margin-bottom: 1rem;">Request Logged Successfully</span>
                
                <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: var(--color-dark);">
                    Thank You, <?php echo sanitize_output($successInquiry['name']); ?>!
                </h1>

                <p style="color: var(--color-text-muted); font-size: 1.05rem; max-width: 580px; margin: 0 auto 2rem auto;">
                    Your quote request has been transmitted directly to the Moal General Suppliers commercial sales desk.
                </p>

                <!-- Tracking Ref Box -->
                <div style="background: var(--color-bg-subtle); border: 1px dashed var(--color-border); border-radius: var(--radius-md); padding: 1.5rem; max-width: 440px; margin: 0 auto 2rem auto;">
                    <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">
                        Reference Tracking Code
                    </span>
                    <span style="font-size: 1.6rem; font-family: monospace; font-weight: 800; color: var(--color-primary); letter-spacing: 1px;">
                        <?php echo sanitize_output($successInquiry['code']); ?>
                    </span>
                    <span style="font-size: 0.78rem; color: var(--color-text-muted); display: block; margin-top: 6px;">
                        Please quote this reference number during any correspondence.
                    </span>
                </div>

                <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                    <a href="https://wa.me/2347069219001?text=Hello%20Moal%20Sales%20Desk,%20I%20just%20submitted%20inquiry%20<?php echo urlencode($successInquiry['code']); ?>%20for%20<?php echo urlencode($successInquiry['name']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp btn-lg">
                        <span></span> Chat with Sales Desk on WhatsApp
                    </a>
                    <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-secondary btn-lg">
                        Return to Inventory
                    </a>
                </div>
            </div>

        <?php else: ?>

            <div class="section-header" style="text-align: left; margin-bottom: 2rem;">
                <span class="section-tag">Direct Dealership Inquiry</span>
                <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.4rem); margin-bottom: 0.5rem;">Request a Quote &amp; Fleet Sourcing</h1>
                <p class="section-subtitle" style="margin: 0;">
                    Submit your fleet specifications, inspection requests, or vehicle quote inquiry below.
                </p>
            </div>

            <?php if ($isLoggedIn && $loggedInCust): ?>
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 1.5rem; font-size: 0.88rem; color: #1e40af; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                         <strong>Signed in as:</strong> <?php echo sanitize_output($loggedInCust['full_name']); ?> (<?php echo sanitize_output($loggedInCust['email']); ?>) — Your contact details have been automatically pre-filled.
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div style="background: #FFEBEE; border: 1px solid #FFCDD2; color: #C62828; padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 2rem; font-size: 0.95rem;">
                    <div style="font-weight: 700; margin-bottom: 4px;">Please correct the following:</div>
                    <ul style="margin: 0 0 0 18px; padding: 0;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo sanitize_output($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="form-card">
                
                <!-- Linked Truck Banner if selected -->
                <?php if ($linkedTruck): ?>
                    <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--color-primary); letter-spacing: 0.5px;">Selected Vehicle For Quote:</span>
                            <h3 style="font-size: 1.15rem; margin: 2px 0; color: var(--color-dark);"><?php echo sanitize_output($linkedTruck['title']); ?></h3>
                            <span style="font-size: 0.85rem; color: var(--color-text-muted);">Stock #<?php echo sanitize_output($linkedTruck['truck_code']); ?> &bull; <?php echo format_currency($linkedTruck['price']); ?></span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$linkedTruck['id']; ?>" class="btn btn-outline btn-sm">
                            View Vehicle &rarr;
                        </a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo BASE_URL; ?>inquiry.php<?php echo $truckId > 0 ? '?truck_id=' . $truckId : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="truck_id" value="<?php echo (int)$truckId; ?>">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem;">
                        <!-- Full Name -->
                        <div class="form-group">
                            <label for="customer_name" class="form-label">Full Name / Contact Person *</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" placeholder="Enter your full name" value="<?php echo sanitize_output($formName); ?>" required>
                        </div>

                        <!-- Phone -->
                        <div class="form-group">
                            <label for="customer_phone" class="form-label">Phone Number / WhatsApp *</label>
                            <input type="tel" id="customer_phone" name="customer_phone" class="form-control" placeholder="e.g. 0803 000 0000" value="<?php echo sanitize_output($formPhone); ?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem;">
                        <!-- Email -->
                        <div class="form-group">
                            <label for="customer_email" class="form-label">Email Address *</label>
                            <input type="email" id="customer_email" name="customer_email" class="form-control" placeholder="name@company.com" value="<?php echo sanitize_output($formEmail); ?>" required>
                        </div>

                        <!-- Inquiry Nature -->
                        <div class="form-group">
                            <label for="inquiry_type" class="form-label">Nature of Inquiry</label>
                            <select id="inquiry_type" name="inquiry_type" class="form-control">
                                <option value="Specific Truck" <?php echo ($inquiryType === 'Specific Truck') ? 'selected' : ''; ?>>Specific Truck Quote / Inquiry</option>
                                <option value="Custom Request" <?php echo ($inquiryType === 'Custom Request') ? 'selected' : ''; ?>>Custom Fleet Sourcing Request</option>
                                <option value="Recommendation Followup" <?php echo ($inquiryType === 'Recommendation Followup') ? 'selected' : ''; ?>>Recommendation Advisor Follow-up</option>
                                <option value="General Inquiry" <?php echo ($inquiryType === 'General Inquiry') ? 'selected' : ''; ?>>General Dealership Inquiry</option>
                            </select>
                        </div>
                    </div>

                    <?php if (!$linkedTruck): ?>
                        <div class="form-group">
                            <label for="select_truck" class="form-label">Select Truck From Stock (Optional)</label>
                            <select id="select_truck" name="truck_id" class="form-control">
                                <option value="">-- Or specify custom requirements below --</option>
                                <?php foreach ($allTrucks as $t): ?>
                                    <option value="<?php echo (int)$t['id']; ?>" <?php echo ($truckId == $t['id']) ? 'selected' : ''; ?>>
                                        [<?php echo sanitize_output($t['truck_code']); ?>] <?php echo sanitize_output($t['title']); ?> (<?php echo format_currency($t['price']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Message / Requirements -->
                    <div class="form-group">
                        <label for="message" class="form-label">Message / Operational Requirements *</label>
                        <textarea id="message" name="message" rows="5" class="form-control" placeholder="Describe your required payload, operating route/terrain, target quantity, or any questions regarding physical yard inspection..." required><?php echo sanitize_output($_POST['message'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        Submit Official Quote Request &rarr;
                    </button>
                </form>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>