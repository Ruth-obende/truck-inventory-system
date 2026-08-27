<?php
/**
 * =============================================================================
 * Moal General Suppliers - Admin Authentication Guard
 * =============================================================================
 * Ensures only authenticated administrators can access backend management pages.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || empty($_SESSION['admin_id'])) {
    set_flash_message('error', 'Please log in to access the dealership administration portal.');
    redirect(ADMIN_URL . 'login.php');
}
