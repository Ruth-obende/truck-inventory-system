<?php
/**
 * =============================================================================
 * Moal General Suppliers - Main Header & Navigation Component
 * =============================================================================
 * 1. Public Landing & Informational Pages:
 *    - Desktop: Logo | Home | About | Contact | Sign In (NO desktop hamburger)
 *    - Mobile (<= 860px): Logo | Hamburger -> Drawer (Home | About | Contact | Sign In)
 * 2. Authenticated Client Experience (when logged in on client pages):
 *    - Desktop: Logo | Inventory | Recommendation | My Account | Contact | Sign Out
 *    - Mobile (<= 860px): Logo | Hamburger -> Drawer (Client system items)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = get_flash_message();
$isCustomer = is_customer_logged_in();
$customerData = $isCustomer ? get_logged_in_customer() : null;

// Determine if user is in the Authenticated Client Area
$clientSystemPages = ['inventory', 'truck-details', 'recommend', 'customer-dashboard', 'customer-request', 'inquiry'];
$isClientArea = $isCustomer && in_array($currentPage, $clientSystemPages, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize_output($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' | Commercial Truck Dealership'; ?></title>
    <meta name="description" content="Premier Commercial Truck Dealership in Lagos, Nigeria. Heavy tippers, prime movers, and cargo trucks with certified customs clearance.">
    
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

<?php
$isInventoryPage = in_array($currentPage, ['inventory', 'truck-details', 'customer-settings', 'customer-dashboard', 'customer-request'], true);
?>
        <div class="nav-right-group">
            <?php if ($isInventoryPage): ?>
                <!-- =========================================================
                     INVENTORY & CLIENT AREA NAVIGATION
                     Hamburger menu is shown in client area and inventory
                     ========================================================= -->
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <?php if ($currentPage === 'customer-settings'): ?>
                        <a href="<?php echo BASE_URL; ?>customer-settings.php" class="nav-link active" style="font-weight: 600; font-size: 0.95rem;">
                            Settings
                        </a>
                    <?php elseif ($currentPage === 'customer-dashboard' || $currentPage === 'customer-request'): ?>
                        <a href="<?php echo BASE_URL; ?>customer-dashboard.php" class="nav-link active" style="font-weight: 600; font-size: 0.95rem;">
                            My Quotes
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>inventory.php" class="nav-link active" style="font-weight: 600; font-size: 0.95rem;">
                            Inventory
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($isCustomer): ?>
                        <span class="client-greeting-badge" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; color: #E5E7EB; background: rgba(255, 255, 255, 0.08); padding: 5px 12px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.12);">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #10B981; display: inline-block;"></span>
                            <?php echo sanitize_output(explode(' ', $customerData['full_name'] ?? 'Client')[0]); ?>
                        </span>
                    <?php endif; ?>

                    <!-- Hamburger Menu Button: Active only when in inventory / client area -->
                    <button class="menu-toggle client-menu-toggle" id="menuToggleBtn" aria-label="Open navigation menu" aria-expanded="false" title="Menu" style="display: inline-flex !important;">
                        <span class="menu-toggle-bars">
                            <span class="bar bar-1"></span>
                            <span class="bar bar-2"></span>
                            <span class="bar bar-3"></span>
                        </span>
                    </button>
                </div>
            <?php else: ?>
                <!-- =========================================================
                     LANDING PAGE NAVIGATION
                     Displays ONLY: Home | About Us | Contact | Sign In
                     (No hamburger on desktop!)
                     ========================================================= -->
                <nav class="desktop-nav" aria-label="Main Navigation">
                    <ul class="nav-menu">
                        <li><a href="<?php echo BASE_URL; ?>" class="nav-link <?php echo ($currentPage === 'index') ? 'active' : ''; ?>">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>about.php" class="nav-link <?php echo ($currentPage === 'about') ? 'active' : ''; ?>">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>contact.php" class="nav-link <?php echo ($currentPage === 'contact') ? 'active' : ''; ?>">Contact</a></li>
                        <?php if ($isCustomer): ?>
                            <li><a href="<?php echo BASE_URL; ?>customer-logout.php" class="nav-link nav-link-signin">Sign Out</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>customer-login.php" class="nav-link nav-link-signin <?php echo ($currentPage === 'customer-login') ? 'active' : ''; ?>">Sign In</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <!-- Mobile menu toggle for small screens (hidden on desktop) -->
                <button class="menu-toggle" id="menuToggleBtn" aria-label="Toggle navigation menu" aria-expanded="false" title="Menu">
                    <span class="menu-toggle-bars">
                        <span class="bar bar-1"></span>
                        <span class="bar bar-2"></span>
                        <span class="bar bar-3"></span>
                    </span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Slide-Over Responsive Navigation Drawer -->
<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="drawer" id="mobileDrawer" role="dialog" aria-modal="true" aria-label="Navigation Menu">
    <div class="drawer-header">
        <div class="drawer-brand">
            <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.png" alt="<?php echo APP_NAME; ?>">
        </div>
        <button class="drawer-close" id="drawerCloseBtn" aria-label="Close menu">&times;</button>
    </div>

    <div class="drawer-body">
        <?php if ($isInventoryPage): ?>
            <!-- Inventory Navigation Drawer: Home, Sign Out, and System Tools -->
            <?php if ($isCustomer): ?>
                <div class="drawer-section" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-primary); font-weight: 700;">Logged In Client</span>
                        <a href="<?php echo BASE_URL; ?>customer-settings.php" style="font-size: 0.75rem; color: var(--color-primary); text-decoration: none; border: 1px solid rgba(217, 130, 91, 0.4); background: rgba(217, 130, 91, 0.12); padding: 2px 8px; border-radius: 4px; font-weight: 600;">Settings &rsaquo;</a>
                    </div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #FFFFFF;"><?php echo sanitize_output($customerData['full_name'] ?? 'Client'); ?></div>
                    <div style="font-size: 0.82rem; color: #94A3B8;"><?php echo sanitize_output($customerData['email'] ?? ''); ?></div>
                </div>
            <?php endif; ?>

            <div class="drawer-section">
                <div class="drawer-section-label">Navigation &amp; Fleet</div>
                <a href="<?php echo BASE_URL; ?>" class="drawer-nav-item <?php echo ($currentPage === 'index') ? 'active' : ''; ?>">
                    <span>Home</span>
                </a>
                <a href="<?php echo BASE_URL; ?>inventory.php" class="drawer-nav-item <?php echo ($currentPage === 'inventory' || $currentPage === 'truck-details') ? 'active' : ''; ?>">
                    <span>Truck Inventory</span>
                </a>
                <a href="<?php echo BASE_URL; ?>recommend.php" class="drawer-nav-item <?php echo ($currentPage === 'recommend') ? 'active' : ''; ?>">
                    <span>Find My Truck Advisor</span>
                </a>
                <a href="<?php echo BASE_URL; ?>inquiry.php?type=custom" class="drawer-nav-item <?php echo ($currentPage === 'inquiry') ? 'active' : ''; ?>">
                    <span>Request Custom Sourcing</span>
                </a>
            </div>

            <div class="drawer-section" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                <div class="drawer-section-label">Account &amp; Services</div>
                <?php if ($isCustomer): ?>
                    <a href="<?php echo BASE_URL; ?>customer-dashboard.php" class="drawer-nav-item <?php echo ($currentPage === 'customer-dashboard' || $currentPage === 'customer-request') ? 'active' : ''; ?>">
                        <span>My Quotes &amp; Inquiries</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>customer-settings.php" class="drawer-nav-item <?php echo ($currentPage === 'customer-settings') ? 'active' : ''; ?>">
                        <span>Settings</span>
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>services.php" class="drawer-nav-item <?php echo ($currentPage === 'services') ? 'active' : ''; ?>">
                    <span>Dealership Services</span>
                </a>
                <a href="<?php echo BASE_URL; ?>about.php" class="drawer-nav-item <?php echo ($currentPage === 'about') ? 'active' : ''; ?>">
                    <span>About Us</span>
                </a>
                <a href="<?php echo BASE_URL; ?>contact.php" class="drawer-nav-item <?php echo ($currentPage === 'contact') ? 'active' : ''; ?>">
                    <span>Contact</span>
                </a>
            </div>

            <div class="drawer-section" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <?php if ($isCustomer): ?>
                    <a href="<?php echo BASE_URL; ?>customer-logout.php" class="drawer-nav-item drawer-link-logout">
                        <span>Sign Out</span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>customer-login.php" class="drawer-nav-item" style="color: var(--color-primary); font-weight: 600;">
                        <span>Sign In</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- =========================================================
                 LANDING PAGE DRAWER (Mobile)
                 Home | About Us | Contact | Sign In
                 ========================================================= -->
            <div class="drawer-section">
                <div class="drawer-section-label">Navigation</div>
                <a href="<?php echo BASE_URL; ?>" class="drawer-nav-item <?php echo ($currentPage === 'index') ? 'active' : ''; ?>">
                    <span>Home</span>
                </a>
                <a href="<?php echo BASE_URL; ?>about.php" class="drawer-nav-item <?php echo ($currentPage === 'about') ? 'active' : ''; ?>">
                    <span>About Us</span>
                </a>
                <a href="<?php echo BASE_URL; ?>contact.php" class="drawer-nav-item <?php echo ($currentPage === 'contact') ? 'active' : ''; ?>">
                    <span>Contact</span>
                </a>
                <?php if ($isCustomer): ?>
                    <a href="<?php echo BASE_URL; ?>customer-logout.php" class="drawer-nav-item drawer-link-logout" style="margin-top: 0.5rem;">
                        <span>Sign Out</span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>customer-login.php" class="drawer-nav-item <?php echo ($currentPage === 'customer-login') ? 'active' : ''; ?>" style="margin-top: 0.75rem; background: var(--color-primary); color: #FFFFFF !important; font-weight: 600; border-radius: var(--radius-sm); text-align: center; justify-content: center;">
                        <span>Sign In</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="drawer-footer">
        <div class="drawer-footer-item">Ojodu Berger Yard, Lagos</div>
        <div class="drawer-footer-item drawer-footer-phone">07069219001 &bull; 08151111181</div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('menuToggleBtn');
    var closeBtn = document.getElementById('drawerCloseBtn');
    var backdrop = document.getElementById('drawerBackdrop');
    var drawer = document.getElementById('mobileDrawer');

    function openDrawer() {
        if (!drawer || !backdrop) return;
        drawer.classList.add('active');
        backdrop.classList.add('active');
        if (toggleBtn) {
            toggleBtn.classList.add('is-active');
            toggleBtn.setAttribute('aria-expanded', 'true');
        }
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (!drawer || !backdrop) return;
        drawer.classList.remove('active');
        backdrop.classList.remove('active');
        if (toggleBtn) {
            toggleBtn.classList.remove('is-active');
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
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