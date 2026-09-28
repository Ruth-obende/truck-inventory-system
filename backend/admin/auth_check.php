<?php
/**
 * =============================================================================
 * Moal General Suppliers - Staff & Admin Authentication Guard
 * =============================================================================
 * Enforces strict server-side authentication for all staff routes:
 * 1. Blocks and redirects authenticated clients trying to access staff pages.
 * 2. Blocks and redirects unauthenticated visitors to staff-login.php.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Unauthenticated Staff Access: Must have an active admin session
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || empty($_SESSION['admin_id'])) {
    set_flash_message('info', 'Please sign in with your staff credentials to access the Staff Portal.');
    redirect(BASE_URL . 'staff-login.php');
}