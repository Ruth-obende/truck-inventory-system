<?php
/**
 * =============================================================================
 * Moal General Suppliers - Main Header & Navigation Component
 * =============================================================================
 * Standardized semantic HTML5 navigation with quick contact bar and clean links.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize_output($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE; ?></title>
    <meta name="description" content="Premier Commercial Truck Dealership & Fleet Sourcing for Moal General Suppliers. High-performance tractor heads, tippers, and distribution trucks.">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Top Quick Contact Bar -->
<div class="topbar">
    <div class="container topbar-container">
        <div class="topbar-info">
            <span>📞 <a href="tel:<?php echo CONTACT_PHONE_1; ?>"><?php echo CONTACT_PHONE_1; ?></a> / <a href="tel:<?php echo CONTACT_PHONE_2; ?>"><?php echo CONTACT_PHONE_2; ?></a></span>
            <span>✉️ <a href="mailto:<?php echo CONTACT_EMAIL; ?>"><?php echo CONTACT_EMAIL; ?></a></span>
            <span class="topbar-badge">📍 Ojodu Berger, Lagos &bull; Nationwide Delivery</span>
        </div>
        <div class="topbar-links">
            <a href="<?php echo CONTACT_INSTAGRAM_URL; ?>" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 4px;">
                <span>📸</span> <?php echo CONTACT_INSTAGRAM_HANDLE; ?>
            </a>
            <a href="<?php echo ADMIN_URL; ?>login.php" class="topbar-admin">🔐 Staff Portal</a>
        </div>
    </div>
</div>

<!-- Main Sticky Navbar -->
<header class="navbar">
    <div class="container nav-container">
        <a href="<?php echo BASE_URL; ?>" class="brand-logo-container">
            <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.jpg" alt="Moal General Suppliers Logo" class="navbar-logo">
        </a>

        <nav>
            <ul class="nav-links">
                <li>
                    <a href="<?php echo BASE_URL; ?>" class="<?php echo ($currentPage === 'index') ? 'active' : ''; ?>">
                        Home
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>about.php" class="<?php echo ($currentPage === 'about') ? 'active' : ''; ?>">
                        About Us
                    </a>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>inventory.php" class="<?php echo ($currentPage === 'inventory' || $currentPage === 'truck-details') ? 'active' : ''; ?>">
                        Our Products
                    </a>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>services.php" class="<?php echo ($currentPage === 'services') ? 'active' : ''; ?>">
                        Services
                    </a>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>why-choose-us.php" class="<?php echo ($currentPage === 'why-choose-us') ? 'active' : ''; ?>">
                        Why Choose Us
                    </a>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>recommend.php" class="<?php echo ($currentPage === 'recommend') ? 'active' : ''; ?>">
                        Find My Truck
                    </a>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>inquiry.php" class="<?php echo ($currentPage === 'inquiry') ? 'active' : ''; ?>">
                        Inquiry &amp; Sourcing
                    </a>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>contact.php" class="<?php echo ($currentPage === 'contact') ? 'active' : ''; ?>">
                        Contact
                    </a>
                </li>

                <li>
                    <a href="<?php echo ADMIN_URL; ?>login.php" class="nav-staff-btn" title="Dealership Staff Portal">
                        🔐 Staff Portal
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>

<?php if ($flash): ?>
    <div class="container" style="margin-top: 1.5rem;">
        <div style="background: <?php echo $flash['type'] === 'success' ? '#dcfce7' : '#fee2e2'; ?>; border: 1px solid <?php echo $flash['type'] === 'success' ? '#86efac' : '#fca5a5'; ?>; color: <?php echo $flash['type'] === 'success' ? '#166534' : '#991b1b'; ?>; padding: 12px 18px; border-radius: 8px; font-weight: 500; font-size: 0.95rem;">
            <?php echo sanitize_output($flash['message']); ?>
        </div>
    </div>
<?php endif; ?>

<main>
