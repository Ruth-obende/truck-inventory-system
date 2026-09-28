<?php
/**
 * =============================================================================
 * Moal General Suppliers - Staff Logout Handler
 * =============================================================================
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);

set_flash_message('success', 'You have been successfully signed out of the Staff Portal.');
redirect(BASE_URL . 'staff-login.php');