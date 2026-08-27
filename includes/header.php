<?php
/**
 * =============================================================================
 * Moal General Suppliers - Main Navigation Header Component
 * =============================================================================
 * Standardized semantic HTML5 navigation with top contact bar, services menu,
 * and responsive mobile navigation links.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize_output($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE; ?></title>
    <meta name="description" content="Premier Commercial Truck Dealership & Inquiries for Moal General Suppliers. High-performance tractor heads, tippers, and fleet sourcing.">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Top Quick Contact Bar -->
<div class="topbar">
    <div class="container topbar-container">
        <div class="topbar-info">
            <span>📞 <a href="tel:+2348031234567">+234 803 123 4567</a></span>
            <span>✉️ <a href="mailto:sales@moalsuppliers.com">sales@moalsuppliers.com</a></span>
            <span class="topbar-badge">🇳🇬 Nationwide Delivery Coverage</span>
        </div>
        <div class="topbar-links">
            <a href="<?php echo BASE_URL; ?>services.php">Dealership Services</a>
            <a href="<?php echo BASE_URL; ?>inquiry.php">Request Quote</a>
            <a href="<?php echo ADMIN_URL; ?>" class="topbar-admin">Staff Portal</a>
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
                    <a href="<?php echo BASE_URL; ?>inventory.php" class="<?php echo ($currentPage === 'inventory') ? 'active' : ''; ?>">
                        Truck Inventory
                    </a>
                </li>

                <!-- Services Dropdown Menu -->
                <li class="nav-dropdown">
                    <a href="<?php echo BASE_URL; ?>services.php" class="<?php echo ($currentPage === 'services') ? 'active' : ''; ?>">
                        Services ▾
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo BASE_URL; ?>services.php#truck-sales">Commercial Truck Sales</a></li>
                        <li><a href="<?php echo BASE_URL; ?>services.php#custom-sourcing">Custom Vehicle Sourcing</a></li>
                        <li><a href="<?php echo BASE_URL; ?>services.php#fleet-procurement">Fleet Expansion Advisory</a></li>
                        <li><a href="<?php echo BASE_URL; ?>services.php#inspection">Mechanical Inspection &amp; Testing</a></li>
                        <li><a href="<?php echo BASE_URL; ?>services.php#documentation">Registration &amp; Customs Papers</a></li>
                        <li><a href="<?php echo BASE_URL; ?>services.php#delivery">Nationwide Delivery Logistics</a></li>
                    </ul>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>recommend.php" class="<?php echo ($currentPage === 'recommend') ? 'active' : ''; ?>">
                        Recommendation Finder
                    </a>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>inquiry.php" class="<?php echo ($currentPage === 'inquiry') ? 'active' : ''; ?>">
                        Inquiries &amp; Sourcing
                    </a>
                </li>

                <li>
                    <a href="<?php echo BASE_URL; ?>inquiry.php?type=quote" class="btn btn-primary btn-sm" style="color: #fff; padding: 8px 16px;">
                        Get a Quote
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>
<main>
