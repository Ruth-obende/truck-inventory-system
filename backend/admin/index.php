<?php
/**
 * =============================================================================
 * Moal General Suppliers - Admin Gateway Router
 * =============================================================================
 * Central entry point for http://localhost/moal-truck-inventory/admin/
 * Redirects authenticated administrators to the dashboard and guests to the login page.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !empty($_SESSION['admin_id'])) {
    redirect(ADMIN_URL . 'dashboard.php');
} else {
    redirect(ADMIN_URL . 'login.php');
}
