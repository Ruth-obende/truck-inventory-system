</main>

<!-- Dealership Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-brand">
                <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.jpg" alt="<?php echo APP_NAME; ?>">
                <p class="footer-brand-p">
                    Premier commercial vehicle dealership in Lagos, Nigeria. Supplying verified heavy-duty tippers, prime movers, and logistics trucks with genuine customs documentation.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="footer-title">Navigation</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL; ?>" class="footer-link">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>inventory.php" class="footer-link">Truck Inventory</a></li>
                    <li><a href="<?php echo BASE_URL; ?>recommend.php" class="footer-link">Find My Truck</a></li>
                    <li><a href="<?php echo BASE_URL; ?>inquiry.php" class="footer-link">Request a Quote</a></li>
                    <li><a href="<?php echo BASE_URL; ?>about.php" class="footer-link">About Dealership</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contact.php" class="footer-link">Contact &amp; Yard</a></li>
                </ul>
            </div>

            <!-- Fleet Categories -->
            <div>
                <h4 class="footer-title">Fleet Categories</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL; ?>inventory.php?category=Construction+%26+Mining" class="footer-link">Tipper Trucks</a></li>
                    <li><a href="<?php echo BASE_URL; ?>inventory.php?category=Heavy+Haulage" class="footer-link">Tractor Heads / Haulage</a></li>
                    <li><a href="<?php echo BASE_URL; ?>inventory.php?category=Distribution+%26+Logistics" class="footer-link">Cargo &amp; Box Trucks</a></li>
                    <li><a href="<?php echo BASE_URL; ?>inventory.php?category=Specialized+Transport" class="footer-link">Specialized Tankers</a></li>
                </ul>
            </div>

            <!-- Dealership Yard & Contact -->
            <div>
                <h4 class="footer-title">Ojodu Berger Yard</h4>
                <div class="footer-contact-item">
                    <span></span>
                    <span>No. 2 Oluwakemi Street, Ojodu Berger, Lagos State</span>
                </div>
                <div class="footer-contact-item">
                    <span></span>
                    <span>07069219001 / 08151111181</span>
                </div>
                <div class="footer-contact-item">
                    <span></span>
                    <span>Moal4gs@gmail.com</span>
                </div>
                <div class="footer-contact-item">
                    <span>️</span>
                    <span>Mon – Sat: 8:00 AM – 6:00 PM</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <a href="<?php echo BASE_URL; ?>customer-login.php" style="color: #A3A39E; font-size: 0.85rem;">Customer Account</a>
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp Quick Connect Button -->
<a href="https://wa.me/2347069219001?text=Hello%20Moal%20General%20Suppliers,%20I%20am%20inquiring%20about%20your%20commercial%20truck%20inventory." target="_blank" rel="noopener noreferrer" style="position: fixed; bottom: 24px; right: 24px; z-index: 999; background: var(--color-primary); color: #FFFFFF; width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(0,0,0,0.2); transition: transform 0.2s ease;" title="Chat with Sales Desk on WhatsApp" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
</a>

</body>
</html>
