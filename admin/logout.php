<?php
/**
 * =============================================================================
 * Moal General Suppliers - Administrator Logout
 * =============================================================================
 * Cleanly terminates administrator session and redirects to login screen.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Unset all session values
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session for flash message
session_start();
set_flash_message('success', 'You have been successfully logged out.');

redirect(ADMIN_URL . 'login.php');
