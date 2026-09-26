<?php
/**
 * =============================================================================
 * Moal General Suppliers - Global Helper Functions
 * =============================================================================
 * Reusable utility functions for sanitization, formatting, session flashes,
 * and security tokens.
 */

require_once __DIR__ . '/config.php';

/**
 * Escapes string data for safe HTML output (XSS defense).
 *
 * @param mixed $data
 * @return string
 */
function sanitize_output($data): string {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars((string)$data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Strips dangerous whitespace and tags from user input.
 *
 * @param mixed $data
 * @return string
 */
function sanitize_input($data): string {
    if (!is_string($data)) {
        return '';
    }
    return trim(strip_tags($data));
}

/**
 * Formats a numerical monetary value into Nigerian Naira currency format.
 * Example: 48500000 -> ₦48,500,000.00
 *
 * @param float|int|string $amount
 * @param bool $includeDecimals
 * @return string
 */
function format_currency($amount, bool $includeDecimals = false): string {
    $num = (float)$amount;
    $decimals = $includeDecimals ? 2 : 0;
    return CURRENCY_SYMBOL . ' ' . number_format($num, $decimals);
}

/**
 * Formats tonnage capacity into readable string.
 * Example: 30.00 -> 30 Tons
 *
 * @param float|int|string $tonnage
 * @return string
 */
function format_tonnage($tonnage): string {
    $num = (float)$tonnage;
    return ($num == (int)$num ? (int)$num : number_format($num, 1)) . ' Tons';
}

/**
 * Formats mileage with thousands separator.
 * Example: 142000 -> 142,000 km
 *
 * @param int|float|string $mileage
 * @return string
 */
function format_mileage($mileage): string {
    return number_format((int)$mileage) . ' km';
}

/**
 * Generates and stores a CSRF token in the active session.
 *
 * @return string
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the session token.
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sets a flash message for display on the subsequent request.
 *
 * @param string $type ('success', 'error', 'warning', 'info')
 * @param string $message
 */
function set_flash_message(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieves and clears the active flash message if present.
 *
 * @return array|null
 */
function get_flash_message(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Safely redirects the browser to a target URL.
 *
 * @param string $url
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Generates a formatted inquiry reference code.
 * Example: INQ-2026-8F2B
 *
 * @return string
 */
function generate_inquiry_code(): string {
    return 'INQ-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

// Automatically load customer auth utilities
require_once __DIR__ . '/customer_auth.php';

