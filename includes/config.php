<?php
/**
 * =============================================================================
 * Moal General Suppliers - System Configuration
 * =============================================================================
 * Centralized application metadata, database parameters, contact details,
 * and security constants.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// -----------------------------------------------------------------------------
// Application Metadata & Branding
// -----------------------------------------------------------------------------
define('APP_NAME', 'Moal General Suppliers');
define('APP_TAGLINE', 'Commercial Truck Dealership & Fleet Sourcing');
define('APP_VERSION', '1.0.0');

// Primary Branding Colors
define('COLOR_PRIMARY_NAVY', '#1F2421');
define('COLOR_ACCENT_ORANGE', '#D9825B');
define('COLOR_BG_WHITE', '#ffffff');

// -----------------------------------------------------------------------------
// Official Dealership Contact Details
// -----------------------------------------------------------------------------
define('CONTACT_PHONE_1', '07069219001');
define('CONTACT_PHONE_2', '08151111181');
define('CONTACT_PHONE_INTL', '+2347069219001');
define('CONTACT_WHATSAPP', '2347069219001');
define('CONTACT_EMAIL', 'Moal4gs@gmail.com');
define('CONTACT_ADDRESS', 'No. 2 Oluwakemi Street, Ojodu Berger, Lagos, Nigeria');
define('CONTACT_INSTAGRAM_HANDLE', '@moal_general_suppliers');
define('CONTACT_INSTAGRAM_URL', 'https://instagram.com/moal_general_suppliers');

// -----------------------------------------------------------------------------
// URL & Path Configuration
// -----------------------------------------------------------------------------
define('BASE_URL', 'http://localhost/moal-truck-inventory/');
define('ADMIN_URL', BASE_URL . 'admin/');

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'trucks' . DIRECTORY_SEPARATOR);

// -----------------------------------------------------------------------------
// Database Credentials (Local MySQL Server)
// -----------------------------------------------------------------------------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'moal_truck_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// -----------------------------------------------------------------------------
// Environment Variables & .env Support
// -----------------------------------------------------------------------------
if (file_exists(ROOT_PATH . '.env')) {
    $envLines = @file(ROOT_PATH . '.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($envLines)) {
        foreach ($envLines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (str_contains($line, '=')) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                if (getenv($key) === false) {
                    putenv("$key=$val");
                    $_ENV[$key] = $val;
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// Outbound Email / SMTP Configuration
// -----------------------------------------------------------------------------
// Live authenticated outbound email delivery via Google SMTP relay.
define('SMTP_ENABLED', true); // Live external SMTP delivery active
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USER', getenv('SMTP_USER') ?: 'obenderuth001@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'syipmateoskahyfh'); // 16-character Google App Password
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls'); // 'tls' (port 587)
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'obenderuth001@gmail.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Moal General Suppliers');
define('ALLOW_MAILPIT_FALLBACK', false); // Strictly false: never send to local sandbox Mailpit

// -----------------------------------------------------------------------------
// Regional Settings
// -----------------------------------------------------------------------------
define('CURRENCY_SYMBOL', '₦');
define('CURRENCY_CODE', 'NGN');
date_default_timezone_set('Africa/Lagos');

// -----------------------------------------------------------------------------
// Error Reporting (Development Mode)
// -----------------------------------------------------------------------------
define('APP_ENV', 'development');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'error_log.log');
}