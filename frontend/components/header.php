<?php
/**
 * =============================================================================
 * Moal General Suppliers - Main Header & Navigation Component (Stage 1)
 * =============================================================================
 * Minimalist desktop header: MOAL LOGO | Home | About | Contact | <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
 * Secondary functions inside modern slide-over drawer.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = get_flash_message();
$isCustomer = is_customer_logged_in();
$customerData = $isCustomer ? get_logged_in_customer() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize_output($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' — Commercial Truck Dealership'; ?></title>
    <meta name="description" content="Premier Commercial Truck Dealership in Lagos, Nigeria. Heavy-duty tippers, prime movers, and cargo trucks with certified customs clearance.">
    
    <!-- Design System Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Header Navigation -->
<header class="site-header">
    <div class="container nav-container">
        <!-- Brand Logo -->
        <a href="<?php echo BASE_URL; ?>" class="brand-logo" title="<?php echo APP_NAME; ?>">
            <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.png" alt="<?php echo APP_NAME; ?>">
        </a>

        <!-- Desktop Navigation Links & Hamburger (NO "Trucks" in Main Header) -->
        <div class="nav-right-group">
            <nav class="desktop-nav" aria-label="Main Navigation">
                <ul class="nav-menu">
                    <li><a href="<?php echo BASE_URL; ?>" class="nav-link <?php echo ($currentPage === 'index') ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>about.php" class="nav-link <?php echo ($currentPage === 'about') ? 'active' : ''; ?>">About</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contact.php" class="nav-link <?php echo ($currentPage === 'contact') ? 'active' : ''; ?>">Contact</a></li>
                    <?php if ($isCustomer && $customerData): ?>
                        <li><a href="<?php echo BASE_URL; ?>inventory.php" class="nav-link <?php echo ($currentPage === 'inventory' || $currentPage === 'truck-details') ? 'active' : ''; ?>">Inventory</a></li>
                        <li><a href="<?php echo BASE_URL; ?>customer-dashboard.php" class="nav-link <?php echo ($currentPage === 'customer-dashboard') ? 'active' : ''; ?>">My Account</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- Hamburger Button (Visible on Desktop, Tablet, and Mobile) -->
            <button class="menu-toggle" id="menuToggleBtn" aria-label="Toggle navigation menu" aria-expanded="false" title="Menu">
                <span class="menu-toggle-bars">
                    <span class="bar bar-1"></span>
                    <span class="bar bar-2"></span>
                    <span class="bar bar-3"></span>
                </span>
            </button>
        </div>
    </div>
</header>

<!-- Slide-Over Navigation Drawer -->
<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="drawer" id="mobileDrawer" role="dialog" aria-modal="true" aria-label="Navigation Menu">
    <div class="drawer-header">
        <div class="drawer-brand">
            <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.png" alt="<?php echo APP_NAME; ?>">
        </div>
        <button class="drawer-close" id="drawerCloseBtn" aria-label="Close menu">&times;</button>
    </div>

    <div class="drawer-body">
        <!-- Mobile-Only Primary Navigation Links -->
        <div class="drawer-section drawer-primary-links">
            <div class="drawer-section-label">Navigation</div>
            <a href="<?php echo BASE_URL; ?>" class="drawer-nav-item <?php echo ($currentPage === 'index') ? 'active' : ''; ?>">
                <span>Home</span>
            </a>
            <a href="<?php echo BASE_URL; ?>about.php" class="drawer-nav-item <?php echo ($currentPage === 'about') ? 'active' : ''; ?>">
                <span>About</span>
            </a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="drawer-nav-item <?php echo ($currentPage === 'contact') ? 'active' : ''; ?>">
                <span>Contact</span>
            </a>
        </div>

        <div class="drawer-divider drawer-primary-divider"></div>

        <!-- Secondary Functions -->
        <div class="drawer-section">
            <div class="drawer-section-label">Menu</div>
            
            <?php if ($isCustomer && $customerData): ?>
                <a href="<?php echo BASE_URL; ?>customer-dashboard.php" class="drawer-action-card <?php echo ($currentPage === 'customer-dashboard') ? 'active' : ''; ?>">
                    <div class="drawer-action-icon"></div>
                    <div class="drawer-action-text">
                        <div class="drawer-action-title"><?php echo sanitize_output(explode(' ', $customerData['full_name'])[0]); ?></div>
                        <div class="drawer-action-desc">My Inquiries &amp; Quotes</div>
                    </div>
                </a>
                <a href="<?php echo BASE_URL; ?>inventory.php" class="drawer-nav-item <?php echo ($currentPage === 'inventory' || $currentPage === 'truck-details') ? 'active' : ''; ?>">
                    <span>Truck Inventory</span>
                </a>
                <a href="<?php echo BASE_URL; ?>recommend.php" class="drawer-action-card drawer-card-recommend <?php echo ($currentPage === 'recommend') ? 'active' : ''; ?>">
                    <div class="drawer-action-icon"></div>
                    <div class="drawer-action-text">
                        <div class="drawer-action-title">Get Recommendation</div>
                        <div class="drawer-action-desc">Find the ideal commercial truck</div>
                    </div>
                </a>
                <a href="<?php echo BASE_URL; ?>customer-logout.php" class="drawer-nav-item drawer-link-logout" style="margin-top: 0.5rem;">
                    <span>Sign Out</span>
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>customer-signup.php" class="drawer-action-card <?php echo ($currentPage === 'customer-signup') ? 'active' : ''; ?>">
                    <div class="drawer-action-icon"></div>
                    <div class="drawer-action-text">
                        <div class="drawer-action-title">Sign Up</div>
                        <div class="drawer-action-desc">Create your account</div>
                    </div>
                </a>

                <a href="<?php echo BASE_URL; ?>customer-login.php" class="drawer-action-card <?php echo ($currentPage === 'customer-login') ? 'active' : ''; ?>">
                    <div class="drawer-action-icon"></div>
                    <div class="drawer-action-text">
                        <div class="drawer-action-title">Sign In</div>
                        <div class="drawer-action-desc">Manage quotes &amp; inquiries</div>
                    </div>
                </a>

                <a href="<?php echo BASE_URL; ?>recommend.php" class="drawer-action-card drawer-card-recommend <?php echo ($currentPage === 'recommend') ? 'active' : ''; ?>">
                    <div class="drawer-action-icon"></div>
                    <div class="drawer-action-text">
                        <div class="drawer-action-title">Get Recommendation</div>
                        <div class="drawer-action-desc">Find the ideal commercial truck</div>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="drawer-footer">
        <div class="drawer-footer-item"> Ojodu Berger Yard, Lagos</div>
        <div class="drawer-footer-item drawer-footer-phone"> 07069219001 &bull; 08151111181</div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('menuToggleBtn');
    var closeBtn = document.getElementById('drawerCloseBtn');
    var backdrop = document.getElementById('drawerBackdrop');
    var drawer = document.getElementById('mobileDrawer');

    function openDrawer() {
        drawer.classList.add('active');
        backdrop.classList.add('active');
        toggleBtn.classList.add('is-active');
        toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.classList.remove('active');
        backdrop.classList.remove('active');
        toggleBtn.classList.remove('is-active');
        toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (toggleBtn && drawer && backdrop) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (drawer.classList.contains('active')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        backdrop.addEventListener('click', closeDrawer);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && drawer.classList.contains('active')) {
                closeDrawer();
            }
        });
    }
});
</script>

<?php if ($flash): ?>
    <div class="container" style="margin-top: 1.5rem;">
        <div style="background: <?php echo $flash['type'] === 'success' ? '#E8F5E9' : ($flash['type'] === 'info' ? '#E0F2FE' : '#FFEBEE'); ?>; border: 1px solid <?php echo $flash['type'] === 'success' ? '#A5D6A7' : ($flash['type'] === 'info' ? '#7DD3FC' : '#FFCDD2'); ?>; color: <?php echo $flash['type'] === 'success' ? '#2E7D32' : ($flash['type'] === 'info' ? '#0369A1' : '#C62828'); ?>; padding: 12px 18px; border-radius: 8px; font-weight: 500; font-size: 0.95rem;">
            <?php echo sanitize_output($flash['message']); ?>
        </div>
    </div>
<?php endif; ?>

<main>