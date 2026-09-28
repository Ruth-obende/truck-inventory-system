<?php
/**
 * Moal General Suppliers - Email Verification Alias
 * Forwards to the dedicated verify-email.php page.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
redirect(BASE_URL . 'verify-email.php' . $query);