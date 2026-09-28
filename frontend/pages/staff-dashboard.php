<?php
/**
 * =============================================================================
 * Moal General Suppliers - Protected Staff Dashboard (/staff-dashboard)
 * =============================================================================
 * Protected route for dealership administrators and inventory managers.
 * Requires active authenticated session via auth_check.php.
 */

require_once __DIR__ . '/admin/auth_check.php';
require_once __DIR__ . '/admin/dashboard.php';
