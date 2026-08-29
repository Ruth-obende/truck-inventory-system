<?php
/**
 * =============================================================================
 * Moal General Suppliers - Contact & Dealership Yard Location Page
 * =============================================================================
 * Official contact channels, physical yard address in Ojodu Berger Lagos,
 * direct phone/WhatsApp links, and contact message handler.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact Us';
$db = getDB();

$errors = [];
$successMessage = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security session expired. Please refresh and try again.';
    } else {
        $custName    = sanitize_input($_POST['customer_name'] ?? '');
        $custEmail   = sanitize_input($_POST['customer_email'] ?? '');
        $custPhone   = sanitize_input($_POST['customer_phone'] ?? '');
        $subject     = sanitize_input($_POST['subject'] ?? 'General Contact');
        $messageBody = sanitize_input($_POST['message'] ?? '');

        if (empty($custName) || strlen($custName) < 3) {
            $errors[] = 'Please enter your full name (minimum 3 characters).';
        }
        if (empty($custEmail) || !filter_var($custEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }
        if (empty($custPhone) || strlen($custPhone) < 7) {
            $errors[] = 'Please provide a valid phone number.';
        }
        if (empty($messageBody) || strlen($messageBody) < 10) {
            $errors[] = 'Please enter your message (minimum 10 characters).';
        }

        if (empty($errors)) {
            $inquiryCode = 'INQ-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            
            try {
                $stmt = $db->prepare('
                    INSERT INTO inquiries (
                        inquiry_code, inquiry_type, customer_name, customer_email, 
                        customer_phone, message, status
                    ) VALUES (
                        :code, :type, :name, :email, :phone, :msg, "Pending"
                    )
                ');
                $stmt->execute([
                    ':code'  => $inquiryCode,
                    ':type'  => 'General Inquiry',
                    ':name'  => $custName,
                    ':email' => $custEmail,
                    ':phone' => $custPhone,
                    ':msg'   => "[Subject: $subject] " . $messageBody
                ]);

                $successMessage = $inquiryCode;
            } catch (PDOException $e) {
                error_log('[Contact Form Error] ' . $e->getMessage());
                $errors[] = 'An error occurred while saving your message. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a> &rsaquo; <span>Contact Us</span>
        </div>
        <h1>Contact Moal General Suppliers</h1>
        <p>Get in touch with our commercial truck sales team or visit our dealership yard in Lagos.</p>
    </div>
</div>

<div class="container" style="margin-bottom: 4rem;">

    <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 3rem; align-items: flex-start;">
        
        <!-- Contact Information Column -->
        <div>
            <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                <span class="badge badge-orange" style="margin-bottom: 0.5rem;">Official Headquarters &amp; Yard</span>
                <h3 style="color: var(--primary-navy); font-size: 1.3rem; margin-bottom: 1.25rem;">Dealership Contact Details</h3>

                <div style="margin-bottom: 1.5rem; display: flex; gap: 14px; align-items: flex-start;">
                    <div style="font-size: 1.5rem; background: var(--accent-orange-light); padding: 10px; border-radius: 8px;">📍</div>
                    <div>
                        <strong style="color: var(--primary-navy); display: block; font-size: 0.95rem;">Physical Yard Address:</strong>
                        <p style="color: var(--text-body); font-size: 0.92rem; margin-top: 2px; line-height: 1.5;">
                            <?php echo CONTACT_ADDRESS; ?>
                        </p>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem; display: flex; gap: 14px; align-items: flex-start;">
                    <div style="font-size: 1.5rem; background: var(--accent-orange-light); padding: 10px; border-radius: 8px;">📞</div>
                    <div>
                        <strong style="color: var(--primary-navy); display: block; font-size: 0.95rem;">Telephone &amp; WhatsApp:</strong>
                        <p style="color: var(--text-body); font-size: 0.92rem; margin-top: 2px;">
                            <a href="tel:<?php echo CONTACT_PHONE_1; ?>" style="color: var(--primary-navy); font-weight: 600;"><?php echo CONTACT_PHONE_1; ?></a><br>
                            <a href="tel:<?php echo CONTACT_PHONE_2; ?>" style="color: var(--primary-navy); font-weight: 600;"><?php echo CONTACT_PHONE_2; ?></a>
                        </p>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem; display: flex; gap: 14px; align-items: flex-start;">
                    <div style="font-size: 1.5rem; background: var(--accent-orange-light); padding: 10px; border-radius: 8px;">✉️</div>
                    <div>
                        <strong style="color: var(--primary-navy); display: block; font-size: 0.95rem;">Official Email:</strong>
                        <p style="color: var(--text-body); font-size: 0.92rem; margin-top: 2px;">
                            <a href="mailto:<?php echo CONTACT_EMAIL; ?>" style="color: var(--accent-orange); font-weight: 600;"><?php echo CONTACT_EMAIL; ?></a>
                        </p>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem; display: flex; gap: 14px; align-items: flex-start;">
                    <div style="font-size: 1.5rem; background: var(--accent-orange-light); padding: 10px; border-radius: 8px;">📸</div>
                    <div>
                        <strong style="color: var(--primary-navy); display: block; font-size: 0.95rem;">Instagram Official:</strong>
                        <p style="color: var(--text-body); font-size: 0.92rem; margin-top: 2px;">
                            <a href="<?php echo CONTACT_INSTAGRAM_URL; ?>" target="_blank" rel="noopener noreferrer" style="color: var(--primary-navy); font-weight: 600;">
                                <?php echo CONTACT_INSTAGRAM_HANDLE; ?>
                            </a>
                        </p>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                    🕒 <strong>Yard Working Hours:</strong> Monday – Saturday: 8:00 AM – 6:00 PM (Closed Sundays)
                </div>
            </div>

            <!-- Fast WhatsApp Button -->
            <a href="https://wa.me/<?php echo CONTACT_WHATSAPP; ?>?text=Hello%20Moal%20General%20Suppliers,%20I%20would%20like%20to%20inquire%20about%20commercial%20trucks." target="_blank" class="btn btn-block" style="background: #25d366; color: #fff; padding: 14px; font-size: 1rem; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); text-align: center;">
                💬 Chat Directly on WhatsApp &rarr;
            </a>
        </div>

        <!-- Contact Message Form Column -->
        <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 2.5rem; box-shadow: var(--shadow-sm);">
            
            <h3 style="font-size: 1.35rem; color: var(--primary-navy); margin-bottom: 0.5rem;">Send Us a Message</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                Have questions about truck availability, inspection bookings, or custom sourcing? Fill out the form below and our team will respond within 24 hours.
            </p>

            <?php if ($successMessage): ?>
                <div style="background: #dcfce7; border: 2px solid #22c55e; color: #166534; padding: 1.5rem; border-radius: var(--radius-md); text-align: center; margin-bottom: 1.5rem;">
                    <div style="font-size: 1.8rem; margin-bottom: 6px;">✓</div>
                    <strong style="font-size: 1.1rem; display: block;">Message Successfully Received!</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        Your tracking reference code is: <strong style="color: var(--accent-orange);"><?php echo sanitize_output($successMessage); ?></strong>
                    </p>
                    <p style="font-size: 0.85rem; color: #15803d; margin-top: 6px;">
                        A sales engineer will contact you shortly.
                    </p>
                </div>
            <?php endif; ?>

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

            <form method="POST" action="<?php echo BASE_URL; ?>contact.php">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label class="filter-label" for="customer_name">Your Full Name *</label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control" required placeholder="e.g. Engr. Adeola" value="<?php echo sanitize_output($_POST['customer_name'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="filter-label" for="customer_phone">Phone / WhatsApp *</label>
                        <input type="tel" name="customer_phone" id="customer_phone" class="form-control" required placeholder="e.g. 08012345678" value="<?php echo sanitize_output($_POST['customer_phone'] ?? ''); ?>">
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label class="filter-label" for="customer_email">Email Address *</label>
                    <input type="email" name="customer_email" id="customer_email" class="form-control" required placeholder="name@company.com" value="<?php echo sanitize_output($_POST['customer_email'] ?? ''); ?>">
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label class="filter-label" for="subject">Subject / Inquiry Purpose</label>
                    <select name="subject" id="subject" class="form-control">
                        <option value="Vehicle Availability & Inspection">Vehicle Availability &amp; Inspection</option>
                        <option value="Custom Truck Sourcing Request">Custom Truck Sourcing Request</option>
                        <option value="Fleet Procurement Advisory">Fleet Procurement Advisory</option>
                        <option value="General Inquiry">General Dealership Inquiry</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.75rem;">
                    <label class="filter-label" for="message">Message *</label>
                    <textarea name="message" id="message" rows="5" class="form-control" required placeholder="How can Moal General Suppliers assist your commercial fleet operations?"><?php echo sanitize_output($_POST['message'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem;">
                    Send Message to Dealership &rarr;
                </button>
            </form>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
