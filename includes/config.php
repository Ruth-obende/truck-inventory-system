<?php
/**
 * =============================================================================
 * Moal General Suppliers - System Configuration
 * =============================================================================
 * Defines application-wide constants, database parameters, currency settings,
 * security defaults, and environment configurations.
 */

// Prevent direct script execution if requested
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// -----------------------------------------------------------------------------
// Application Metadata & Branding
// -----------------------------------------------------------------------------
define('APP_NAME', 'Moal General Suppliers');
define('APP_TAGLINE', 'Truck Inventory & Customer Inquiry Management System');
define('APP_VERSION', '1.0.0-dev');

// Primary Branding Colors
define('COLOR_PRIMARY_NAVY', '#0b1e36');
define('COLOR_ACCENT_ORANGE', '#ff6600');
define('COLOR_BG_WHITE', '#ffffff');

// -----------------------------------------------------------------------------
// URL & Path Configuration
// -----------------------------------------------------------------------------
// Base URL for web routing
define('BASE_URL', 'http://localhost/moal-truck-inventory/');
define('ADMIN_URL', BASE_URL . 'admin/');

// Absolute Server Paths
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'trucks' . DIRECTORY_SEPARATOR);

// -----------------------------------------------------------------------------
// Database Credentials (Local Laragon MySQL Server)
// -----------------------------------------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'moal_truck_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// -----------------------------------------------------------------------------
// Regional & Display Settings
// -----------------------------------------------------------------------------
define('CURRENCY_SYMBOL', '₦');
define('CURRENCY_CODE', 'NGN');
date_default_timezone_set('Africa/Lagos');

// -----------------------------------------------------------------------------
// Error Reporting (Development Mode vs Production)
// -----------------------------------------------------------------------------
define('APP_ENV', 'development'); // 'development' or 'production'

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'error_log.log');
}
