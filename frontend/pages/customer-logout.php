<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Logout Handler
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

logout_customer();
set_flash_message('info', 'You have been successfully signed out of your customer account.');
redirect(BASE_URL);
