<?php
/**
 * =============================================================================
 * Moal General Suppliers - Main Footer Component & Floating WhatsApp Widget
 * =============================================================================
 */
require_once __DIR__ . '/config.php';
?>
</main>

<!-- Floating WhatsApp Quick Connect Button -->
<a href="https://wa.me/2348031234567?text=Hello%20Moal%20General%20Suppliers,%20I%20am%20interested%20in%20inquiring%20about%20commercial%20trucks." target="_blank" class="floating-whatsapp" title="Chat on WhatsApp" rel="noopener noreferrer">
    <span class="whatsapp-icon">💬</span>
    <span class="whatsapp-text">Chat with Sales</span>
</a>

<footer class="footer">
    <div class="container footer-content">
        
        <!-- Column 1: Brand & Slogan -->
        <div class="footer-brand">
            <a href="<?php echo BASE_URL; ?>">
                <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.jpg" alt="Moal General Suppliers" class="footer-logo">
            </a>
            <p style="font-size: 0.9rem; color: #94a3b8; margin-top: 12px; line-height: 1.6;">
                Nigeria's trusted commercial truck dealership and custom fleet sourcing partner. Supplying premium tractor heads, heavy tippers, and distribution trucks nationwide.
            </p>
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--accent-orange); margin-top: 10px; text-transform: uppercase; letter-spacing: 1px;">
                Driving Business Forward
            </div>
        </div>

        <!-- Column 2: Quick Navigation -->
        <div class="footer-links">
            <h4>Quick Navigation</h4>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>inventory.php">Truck Inventory Catalogue</a></li>
                <li><a href="<?php echo BASE_URL; ?>recommend.php">3-Question Recommendation Finder</a></li>
                <li><a href="<?php echo BASE_URL; ?>services.php">Dealership Services</a></li>
                <li><a href="<?php echo BASE_URL; ?>inquiry.php">Inquiries &amp; Custom Sourcing</a></li>
                <li><a href="<?php echo ADMIN_URL; ?>">Staff Portal Login</a></li>
            </ul>
        </div>

        <!-- Column 3: Commercial Services -->
        <div class="footer-links">
            <h4>Commercial Services</h4>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>services.php#truck-sales">Heavy Commercial Truck Sales</a></li>
                <li><a href="<?php echo BASE_URL; ?>services.php#custom-sourcing">Custom Vehicle Procurement</a></li>
                <li><a href="<?php echo BASE_URL; ?>services.php#fleet-procurement">Fleet Expansion Advisory</a></li>
                <li><a href="<?php echo BASE_URL; ?>services.php#inspection">Mechanical Diagnostic Testing</a></li>
                <li><a href="<?php echo BASE_URL; ?>services.php#documentation">Customs Paperwork &amp; SGD</a></li>
                <li><a href="<?php echo BASE_URL; ?>services.php#delivery">Nationwide Yard Delivery</a></li>
            </ul>
        </div>

        <!-- Column 4: Dealership Contact & Location -->
        <div class="footer-contact">
            <h4>Contact &amp; Dealership Yard</h4>
            <p style="margin-bottom: 8px;">
                📍 <strong>Abuja Commercial Dealership Yard:</strong><br>
                Plot 104 Industrial Layout, Idu / Airport Corridor, Abuja, FCT, Nigeria
            </p>
            <p style="margin-bottom: 8px;">
                📞 <strong>Telephone / WhatsApp:</strong><br>
                <a href="tel:+2348031234567" style="color: #cbd5e1;">+234 803 123 4567</a> / <a href="tel:+2348029876543" style="color: #cbd5e1;">+234 802 987 6543</a>
            </p>
            <p style="margin-bottom: 8px;">
                ✉️ <strong>Sales &amp; Inquiries:</strong><br>
                <a href="mailto:sales@moalsuppliers.com" style="color: #cbd5e1;">sales@moalsuppliers.com</a>
            </p>
            <p style="font-size: 0.8rem; color: #64748b; margin-top: 6px;">
                🕒 <strong>Yard Hours:</strong> Mon – Sat: 8:00 AM – 6:00 PM
            </p>
        </div>

    </div>

    <!-- Academic Capstone Notice & Copyright -->
    <div class="container footer-bottom">
        <div>
            &copy; <?php echo date('Y'); ?> Moal General Suppliers. All rights reserved. &bull; Case Study Implementation
        </div>
        <div style="color: #64748b; font-size: 0.8rem;">
            Designed &amp; Developed for Final-Year Software Engineering Project Defense
        </div>
    </div>
</footer>

</body>
</html>
