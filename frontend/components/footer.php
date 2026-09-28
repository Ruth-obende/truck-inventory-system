</main>

<!-- Dealership Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-brand">
                <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.jpg" alt="<?php echo APP_NAME; ?>">
                <p class="footer-brand-p">
                    Premier commercial vehicle dealership in Lagos, Nigeria. Supplying verified heavy tippers, prime movers, and logistics trucks with genuine customs documentation.
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
                    <li><a href="<?php echo BASE_URL; ?>contact.php" class="footer-link">Contact</a></li>
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
                    <span>No. 2 Oluwakemi Street, Ojodu Berger, Lagos State</span>
                </div>
                <div class="footer-contact-item">
                    <span>07069219001 / 08151111181</span>
                </div>
                <div class="footer-contact-item">
                    <span>Moal4gs@gmail.com</span>
                </div>
                <div class="footer-contact-item">
                    <span>Mon to Sat: 8:00 AM to 6:00 PM</span>
                </div>
            </div>

            <!-- Newsletter Subscription -->
            <div class="footer-newsletter">
                <h4 class="footer-title">Stay Updated</h4>
                <p style="font-size: 0.88rem; color: #A3A39E; line-height: 1.55; margin-bottom: 1rem;">
                    Subscribe to receive instant updates on newly cleared trucks, special arrivals, and haulage fleet offers.
                </p>

                <form method="POST" action="<?php echo BASE_URL; ?>newsletter.php" class="newsletter-form">
                    <input type="email" name="newsletter_email" required placeholder="Enter your email address" class="form-control" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.18); color: #FFFFFF; font-size: 0.88rem; padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 8px; width: 100%;">
                    <button type="submit" class="btn btn-primary btn-block btn-sm" style="padding: 10px; width: 100%; font-weight: 700; font-size: 0.88rem; justify-content: center;">
                        Subscribe to Updates
                    </button>
                </form>
                <div style="font-size: 0.75rem; color: #858580; margin-top: 8px;">
                    🔒 No spam. Only verified vehicle arrivals.
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

<!-- Sign Out / Log Out Confirmation Modal -->
<div class="signout-modal-backdrop" id="signoutModalBackdrop" role="dialog" aria-modal="true" aria-labelledby="signoutModalTitle">
    <div class="signout-modal">
        <div class="signout-modal-header">
            <h3 class="signout-modal-title" id="signoutModalTitle">Confirm Sign Out</h3>
            <button type="button" class="signout-modal-close" id="signoutModalCloseBtn" aria-label="Close modal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="signout-modal-body">
            <p class="signout-modal-text">
                Are you sure you want to sign out of your account? You will need to sign in again to view vehicle prices, request proforma quotes, or access your saved inquiries.
            </p>
            <div class="signout-modal-actions">
                <button type="button" class="signout-modal-btn-cancel" id="signoutModalCancelBtn">Stay Signed In</button>
                <a href="<?php echo BASE_URL; ?>customer-logout.php" class="signout-modal-btn-confirm" id="signoutModalConfirmBtn">Yes, Sign Out</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalBackdrop = document.getElementById('signoutModalBackdrop');
    var closeBtn = document.getElementById('signoutModalCloseBtn');
    var cancelBtn = document.getElementById('signoutModalCancelBtn');
    var confirmBtn = document.getElementById('signoutModalConfirmBtn');

    if (!modalBackdrop) return;

    function openSignoutModal() {
        modalBackdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
        if (cancelBtn) cancelBtn.focus();
    }

    function closeSignoutModal() {
        modalBackdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Intercept only navigation signout/logout links, NOT the modal's own confirm button
    var logoutTriggers = document.querySelectorAll('a[href*="customer-logout.php"]:not(#signoutModalConfirmBtn)');
    logoutTriggers.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            openSignoutModal();
        });
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function(e) {
            // Guarantee immediate redirection to logout handler
            window.location.href = '<?php echo BASE_URL; ?>customer-logout.php';
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeSignoutModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeSignoutModal);

    modalBackdrop.addEventListener('click', function(e) {
        if (e.target === modalBackdrop) {
            closeSignoutModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalBackdrop.classList.contains('active')) {
            closeSignoutModal();
        }
    });
});
</script>

</body>
</html>
