<?php
/**
 * =============================================================================
 * Moal General Suppliers - Main Footer Component, Newsletter & Floating WhatsApp
 * =============================================================================
 */
require_once __DIR__ . '/config.php';
?>
</main>

<!-- Floating WhatsApp Quick Connect Button -->
<a href="https://wa.me/<?php echo CONTACT_WHATSAPP; ?>?text=Hello%20Moal%20General%20Suppliers,%20I%20am%20inquiring%20about%20your%20commercial%20trucks%20and%20fleet%20services." target="_blank" class="floating-whatsapp" title="Chat with Sales on WhatsApp" rel="noopener noreferrer">
    <span class="whatsapp-icon">💬</span>
    <span class="whatsapp-text">Chat with Us</span>
</a>

<footer class="footer">
    <div class="container footer-content">
        
        <!-- Column 1: Brand & Slogan -->
        <div class="footer-brand">
            <a href="<?php echo BASE_URL; ?>">
                <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.jpg" alt="Moal General Suppliers" class="footer-logo">
            </a>
            <p style="font-size: 0.9rem; color: #94a3b8; margin-top: 12px; line-height: 1.6;">
                Nigeria's trusted commercial truck dealership and custom fleet sourcing partner. Supplying verified heavy tractor heads, construction tippers, and distribution trucks nationwide.
            </p>
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--accent-orange); margin-top: 10px; text-transform: uppercase; letter-spacing: 1px;">
                Driving Business Forward
            </div>

            <!-- Social Media Links -->
            <div style="margin-top: 1rem; display: flex; align-items: center; gap: 12px;">
                <a href="<?php echo CONTACT_INSTAGRAM_URL; ?>" target="_blank" rel="noopener noreferrer" style="color: #cbd5e1; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px;" title="Follow on Instagram">
                    <span style="background: rgba(255,255,255,0.1); padding: 6px 10px; border-radius: 6px;">📸 Instagram: <?php echo CONTACT_INSTAGRAM_HANDLE; ?></span>
                </a>
            </div>
        </div>

        <!-- Column 2: Quick Navigation -->
        <div class="footer-links">
            <h4>Navigation</h4>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>about.php">About Us</a></li>
                <li><a href="<?php echo BASE_URL; ?>inventory.php">Our Products</a></li>
                <li><a href="<?php echo BASE_URL; ?>services.php">Dealership Services</a></li>
                <li><a href="<?php echo BASE_URL; ?>why-choose-us.php">Why Choose Us</a></li>
                <li><a href="<?php echo BASE_URL; ?>recommend.php">Find My Truck</a></li>
                <li><a href="<?php echo BASE_URL; ?>inquiry.php">Inquiry &amp; Sourcing</a></li>
                <li><a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
            </ul>
        </div>

        <!-- Column 3: Dealership Contact Details -->
        <div class="footer-contact">
            <h4>Dealership Location</h4>
            <p style="margin-bottom: 10px;">
                📍 <strong>Dealership Yard:</strong><br>
                <?php echo CONTACT_ADDRESS; ?>
            </p>
            <p style="margin-bottom: 10px;">
                📞 <strong>Direct Lines / WhatsApp:</strong><br>
                <a href="tel:<?php echo CONTACT_PHONE_1; ?>" style="color: #cbd5e1;"><?php echo CONTACT_PHONE_1; ?></a><br>
                <a href="tel:<?php echo CONTACT_PHONE_2; ?>" style="color: #cbd5e1;"><?php echo CONTACT_PHONE_2; ?></a>
            </p>
            <p style="margin-bottom: 10px;">
                ✉️ <strong>Official Email:</strong><br>
                <a href="mailto:<?php echo CONTACT_EMAIL; ?>" style="color: #cbd5e1;"><?php echo CONTACT_EMAIL; ?></a>
            </p>
            <p style="font-size: 0.8rem; color: #64748b;">
                🕒 <strong>Operating Hours:</strong> Mon – Sat: 8:00 AM – 6:00 PM
            </p>
        </div>

        <!-- Column 4: Newsletter Signup -->
        <div class="footer-newsletter">
            <h4>Stay Updated</h4>
            <p style="font-size: 0.88rem; color: #94a3b8; line-height: 1.5; margin-bottom: 1rem;">
                Subscribe to receive instant updates on newly imported trucks, special arrivals, and haulage fleet offers.
            </p>

            <form method="POST" action="<?php echo BASE_URL; ?>newsletter.php" class="newsletter-form">
                <input type="email" name="newsletter_email" required placeholder="Enter your email address" class="form-control" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); color: #fff; margin-bottom: 8px;">
                <button type="submit" class="btn btn-primary btn-block btn-sm" style="padding: 9px;">
                    Subscribe to Updates
                </button>
            </form>
        </div>

    </div>

    <!-- Academic Capstone Notice & Copyright -->
    <div class="container footer-bottom">
        <div>
            &copy; <?php echo date('Y'); ?> Moal General Suppliers. All rights reserved.
        </div>
        <div style="color: #64748b; font-size: 0.8rem;">
            Final-Year Software Engineering Capstone Project &bull; Academic Case Study
        </div>
    </div>
</footer>

</body>
</html>
