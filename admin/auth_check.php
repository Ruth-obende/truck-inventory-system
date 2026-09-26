<?php
/**
 * =============================================================================
 * Moal General Suppliers - Staff & Admin Authentication Guard
 * =============================================================================
 * Enforces strict server-side authentication for all staff routes.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || empty($_SESSION['admin_id'])) {
    set_flash_message('error', 'Authorized personnel only. Please sign in to access the Staff Portal.');
    redirect(BASE_URL . 'staff-login.php');
}