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
 * Generates an inquiry reference code formatted as INQ-YYYY-XXXX (e.g. INQ-2026-0001)
 * as specified in Section 3.4.2.5 and Section 3.7.6 of the thesis documentation.
 *
 * @return string
 */
function generate_inquiry_code(): string {
    $year = date('Y');
    try {
        $db = getDB();
        $stmt = $db->query('SELECT COUNT(*) FROM inquiries');
        $count = (int)$stmt->fetchColumn() + 1;
        $code = 'INQ-' . $year . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);

        $stmtCheck = $db->prepare('SELECT id FROM inquiries WHERE inquiry_code = ? LIMIT 1');
        $stmtCheck->execute([$code]);
        while ($stmtCheck->fetch()) {
            $count++;
            $code = 'INQ-' . $year . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
            $stmtCheck->execute([$code]);
        }
        return $code;
    } catch (Exception $e) {
        return 'INQ-' . $year . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

/**
 * Generates SQL WHERE condition for date range filtering with support for:
 * - 'all' (All time historical data - no limitation)
 * - 'today' (Current date)
 * - 'week' (Last 7 days)
 * - 'month' (Last 30 days)
 * - 'year' (Last 365 days)
 * - 'custom' (Specific date range with start_date / end_date)
 *
 * @param string $column (e.g. 'created_at' or 'i.created_at')
 * @return string SQL clause (e.g. " AND created_at >= ...")
 */
function get_date_filter_sql(string $column = 'created_at'): string {
    $range = sanitize_input($_GET['range'] ?? 'all');
    $startDate = sanitize_input($_GET['start_date'] ?? $_GET['from'] ?? '');
    $endDate   = sanitize_input($_GET['end_date'] ?? $_GET['to'] ?? '');

    if ($range === 'today') {
        return " AND DATE($column) = CURDATE()";
    } elseif ($range === 'week') {
        return " AND $column >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($range === 'month') {
        return " AND $column >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    } elseif ($range === 'year') {
        return " AND $column >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
    } elseif ($range === 'custom' || (!empty($startDate) || !empty($endDate))) {
        $clause = '';
        if (!empty($startDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $clause .= " AND DATE($column) >= " . getDB()->quote($startDate);
        }
        if (!empty($endDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $clause .= " AND DATE($column) <= " . getDB()->quote($endDate);
        }
        return $clause;
    }
    return ''; // 'all' - no date cap, all time accessible
}

/**
 * Validates password strength against the system-wide policy.
 * Requirements: minimum 8 characters, at least 1 uppercase, 1 lowercase, and 1 special character.
 *
 * @param string $password The password to validate
 * @return string|null Null if valid, or the error message describing the failure
 */
function validate_password_strength(string $password): ?string {
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter (A-Z).';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter (a-z).';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Password must contain at least one special character (e.g. @, #, $, !, %).';
    }
    return null;
}

// Automatically load customer auth utilities
require_once __DIR__ . '/customer_auth.php';

