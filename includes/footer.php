<?php
/**
 * =============================================================================
 * Moal General Suppliers - Footer Component
 * =============================================================================
 */
require_once __DIR__ . '/config.php';
?>
</main>

<footer class="footer">
    <div class="container footer-content">
        <div class="footer-brand">
            <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.jpg" alt="Moal General Suppliers" class="footer-logo">
            <p style="font-size: 0.85rem; color: #94a3b8; margin-top: 8px; max-width: 400px;">
                Driving Business Forward with quality commercial trucks, heavy-duty haulage, construction tippers, and specialized fleet solutions.
            </p>
            <div style="font-size: 0.75rem; margin-top: 6px; color: #64748b;">
                Final-Year Software Engineering Capstone Project &bull; Academic Case Study
            </div>
        </div>

        <div class="footer-links">
            <h4>Quick Navigation</h4>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>inventory.php">All Truck Inventory</a></li>
                <li><a href="<?php echo BASE_URL; ?>recommend.php">3-Question Recommendation Finder</a></li>
                <li><a href="<?php echo BASE_URL; ?>inquiry.php">Submit Inquiry or Custom Request</a></li>
                <li><a href="<?php echo ADMIN_URL; ?>">Admin Login</a></li>
            </ul>
        </div>

        <div class="footer-contact">
            <h4>Dealership Case Study</h4>
            <p><strong>Moal General Suppliers</strong></p>
            <p>Commercial Truck Sales, Supply &amp; Logistics</p>
            <p style="color: var(--accent-orange); font-weight: 600; margin-top: 4px;">Driving Business Forward</p>
        </div>
    </div>

    <div class="container footer-bottom">
        <div>&copy; <?php echo date('Y'); ?> Moal General Suppliers. All rights reserved.</div>
        <div style="color: #64748b;">Developed for Final-Year Software Engineering Project Defense</div>
    </div>
</footer>

</body>
</html>
