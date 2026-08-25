<?php
/**
 * =============================================================================
 * Moal General Suppliers - Header Component
 * =============================================================================
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
    <meta name="description" content="Premier Commercial Truck Dealership & Inquiries for Moal General Suppliers">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<header class="navbar">
    <div class="container nav-container">
        <a href="<?php echo BASE_URL; ?>" class="brand-logo-container">
            <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.jpg" alt="Moal General Suppliers Logo" class="navbar-logo">
        </a>

        <nav>
            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>" class="<?php echo ($currentPage === 'index') ? 'active' : ''; ?>">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>inventory.php" class="<?php echo ($currentPage === 'inventory') ? 'active' : ''; ?>">Truck Inventory</a></li>
                <li><a href="<?php echo BASE_URL; ?>recommend.php" class="<?php echo ($currentPage === 'recommend') ? 'active' : ''; ?>">Recommendation Tool</a></li>
                <li><a href="<?php echo BASE_URL; ?>inquiry.php" class="<?php echo ($currentPage === 'inquiry') ? 'active' : ''; ?>">Inquiries &amp; Custom Requests</a></li>
                <li><a href="<?php echo ADMIN_URL; ?>" class="btn btn-primary btn-sm" style="color: #fff;">Staff Portal</a></li>
            </ul>
        </nav>
    </div>
</header>
<main>
