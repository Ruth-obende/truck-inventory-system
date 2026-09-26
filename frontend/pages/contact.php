<?php
/**
 * =============================================================================
 * Moal General Suppliers - Contact & Ojodu Berger Yard Location
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact Us & Dealership Yard Location';
$db = getDB();

$contactSent = false;
$contactError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $contactError = 'Security token expired. Please refresh and try again.';
    } else {
        $cName    = sanitize_input($_POST['name'] ?? '');
        $cPhone   = sanitize_input($_POST['phone'] ?? '');
        $cEmail   = sanitize_input($_POST['email'] ?? '');
        $cSubject = sanitize_input($_POST['subject'] ?? 'General Contact');
        $cMessage = sanitize_input($_POST['message'] ?? '');

        if (empty($cName) || empty($cPhone) || empty($cEmail) || empty($cMessage)) {
            $contactError = 'Please complete all required fields.';
        } else {
            try {
                $code = generate_inquiry_code();
                $stmt = $db->prepare('
                    INSERT INTO inquiries (inquiry_code, customer_name, customer_email, customer_phone, inquiry_type, message, status)
                    VALUES (:code, :name, :email, :phone, :type, :msg, "Pending")
                ');
                $stmt->execute([
                    ':code'  => $code,
                    ':name'  => $cName,
                    ':email' => $cEmail,
                    ':phone' => $cPhone,
                    ':type'  => $cSubject,
                    ':msg'   => $cMessage
                ]);
                $contactSent = true;
            } catch (Exception $e) {
                error_log('[Contact Form Error] ' . $e->getMessage());
                $contactError = 'Failed to record your message. Please reach out to us directly via phone or WhatsApp.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem;">
    <div class="container">
        
        <div class="section-header" style="text-align: left; margin-bottom: 2.5rem;">
            <span class="section-tag">Direct Communication</span>
            <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.4rem); margin-bottom: 0.5rem;">Contact &amp; Dealership Yard</h1>
            <p class="section-subtitle" style="margin: 0;">
                Reach our commercial vehicle sales consultants or arrange physical yard inspections.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 3rem; align-items: flex-start;" class="contact-grid">
            
            <!-- Left: Yard Details -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                
                <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2rem;">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.25rem; color: var(--color-dark);">Ojodu Berger Dealership Yard</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1.25rem; font-size: 0.95rem;">
                        <div style="display: flex; gap: 12px;">
                            <span style="font-size: 1.2rem;"></span>
                            <div>
                                <strong style="color: var(--color-dark); display: block;">Physical Address</strong>
                                <span style="color: var(--color-text-muted);">No. 2 Oluwakemi Street, Ojodu Berger, Lagos State, Nigeria</span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <span style="font-size: 1.2rem;"></span>
                            <div>
                                <strong style="color: var(--color-dark); display: block;">Direct Sales Lines</strong>
                                <span><a href="tel:07069219001" style="color: var(--color-dark); font-weight: 600;">07069219001</a></span> &bull; 
                                <span><a href="tel:08151111181" style="color: var(--color-dark); font-weight: 600;">08151111181</a></span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <span style="font-size: 1.2rem;"></span>
                            <div>
                                <strong style="color: var(--color-dark); display: block;">Official Email</strong>
                                <a href="mailto:Moal4gs@gmail.com" style="color: var(--color-primary); font-weight: 600;">Moal4gs@gmail.com</a>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <span style="font-size: 1.2rem;">️</span>
                            <div>
                                <strong style="color: var(--color-dark); display: block;">Operating Hours</strong>
                                <span style="color: var(--color-text-muted);">Monday – Saturday: 8:00 AM – 6:00 PM</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border);">
                        <a href="https://wa.me/2347069219001?text=Hello%20Moal%20General%20Suppliers,%20I%20would%20like%20to%20schedule%20a%20yard%20inspection." target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp" style="width: 100%;">
                            <span></span> Chat with Sales Desk on WhatsApp
                        </a>
                    </div>
                </div>

            </div>

            <!-- Right: Contact Form -->
            <div class="form-card">
                <?php if ($contactSent): ?>
                    <div style="text-align: center; padding: 2rem 1rem;">
                        <div style="font-size: 3rem; margin-bottom: 0.75rem;"></div>
                        <h3 style="color: var(--color-dark); margin-bottom: 0.5rem;">Message Received!</h3>
                        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
                            Thank you for reaching out. A Moal commercial sales consultant will contact you shortly.
                        </p>
                        <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">
                            Browse Trucks &rarr;
                        </a>
                    </div>
                <?php else: ?>
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.25rem; color: var(--color-dark);">Send Us a Message</h3>

                    <?php if ($contactError): ?>
                        <div style="background: #FFEBEE; border: 1px solid #FFCDD2; color: #C62828; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.92rem;">
                            <?php echo sanitize_output($contactError); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo BASE_URL; ?>contact.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Your name" required>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" class="form-control" placeholder="0803 000 0000" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="name@company.com" required>
                            </div>

                            <div class="form-group">
                                <label for="subject" class="form-label">Subject</label>
                                <select id="subject" name="subject" class="form-control">
                                    <option value="General Contact">General Inquiry</option>
                                    <option value="Yard Inspection Booking">Schedule Yard Inspection</option>
                                    <option value="Fleet Purchase">Bulk Fleet Purchase</option>
                                    <option value="Logistics & Delivery">Nationwide Delivery Inquiry</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message" class="form-label">Your Message *</label>
                            <textarea id="message" name="message" rows="4" class="form-control" placeholder="How can our sales desk assist you?" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                            Send Message &rarr;
                        </button>
                    </form>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<style>
@media (max-width: 800px) {
    .contact-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
