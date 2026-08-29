<?php
/**
 * =============================================================================
 * Moal General Suppliers - CLI Administrator Password Reset Utility
 * =============================================================================
 * Usage via command line:
 *   php scripts/reset_password.php <username_or_email> <new_password>
 *
 * Example:
 *   php scripts/reset_password.php admin admin123
 */

if (php_sapi_name() !== 'cli') {
    die("This utility can only be run from the command line interface (CLI).\n");
}

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$username = $argv[1] ?? '';
$newPass  = $argv[2] ?? '';

if (empty($username) || empty($newPass)) {
    echo "===============================================================\n";
    echo " Moal General Suppliers - Password Reset Utility\n";
    echo "===============================================================\n";
    echo "Usage:\n";
    echo "  php scripts/reset_password.php <username_or_email> <new_password>\n\n";
    echo "Example:\n";
    echo "  php scripts/reset_password.php admin mySimplePass123\n";
    echo "===============================================================\n";
    exit(1);
}

try {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, email FROM admins WHERE (LOWER(username) = :u1 OR LOWER(email) = :u2) LIMIT 1');
    $stmt->execute([
        ':u1' => strtolower($username),
        ':u2' => strtolower($username)
    ]);
    $admin = $stmt->fetch();

    if (!$admin) {
        echo "Error: No administrator found matching '{$username}'.\n";
        exit(1);
    }

    $hashed = password_hash($newPass, PASSWORD_BCRYPT);
    $stmtUpdate = $db->prepare('UPDATE admins SET password_hash = :hash, reset_token = NULL, reset_token_expiry = NULL WHERE id = :id');
    $stmtUpdate->execute([':hash' => $hashed, ':id' => $admin['id']]);

    echo "===============================================================\n";
    echo "SUCCESS: Password for '{$admin['username']}' ({$admin['email']}) has been updated!\n";
    echo "New Password: {$newPass}\n";
    echo "Bcrypt Hash:  {$hashed}\n";
    echo "===============================================================\n";

} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
