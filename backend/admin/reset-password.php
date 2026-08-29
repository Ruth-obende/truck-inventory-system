<?php
/**
 * =============================================================================
 * Moal General Suppliers - Administrator Password Reset Form
 * =============================================================================
 * Validates cryptographically signed reset token and updates the staff password
 * with Bcrypt hashing in MySQL.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Redirect if already logged in
if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect(ADMIN_URL . 'dashboard.php');
}

$db = getDB();
$token = sanitize_input($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$tokenValid = false;
$adminRecord = null;

if (!empty($token)) {
    $stmtCheck = $db->prepare('
        SELECT id, username, email, full_name, reset_token_expiry 
        FROM admins 
        WHERE reset_token = :token 
        AND reset_token_expiry > NOW() 
        AND status = "active" 
        LIMIT 1
    ');
    $stmtCheck->execute([':token' => $token]);
    $adminRecord = $stmtCheck->fetch();

    if ($adminRecord) {
        $tokenValid = true;
    } else {
        $error = 'This password reset link is invalid or has expired. Please request a new one.';
    }
} else {
    $error = 'Missing reset authorization token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($submittedCsrf)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please fill in all password fields.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation password do not match.';
        } else {
            // Hash the new password with Bcrypt
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update database and invalidate token
            $stmtUpdate = $db->prepare('
                UPDATE admins 
                SET password_hash = :hash, 
                    reset_token = NULL, 
                    reset_token_expiry = NULL 
                WHERE id = :id
            ');
            $stmtUpdate->execute([
                ':hash' => $newHash,
                ':id'   => $adminRecord['id']
            ]);

            set_flash_message('success', 'Your password has been reset successfully! You can now log in.');
            redirect(ADMIN_URL . 'login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-navy-dark) 0%, var(--primary-navy) 100%);
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 2px solid var(--accent-orange);
            padding: 2.5rem;
            width: 100%;
            max-width: 460px;
            box-shadow: var(--shadow-lg);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-logo img {
            height: 48px;
            width: auto;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-logo">
        <img src="<?php echo BASE_URL; ?>assets/images/branding/logo.jpg" alt="<?php echo APP_NAME; ?>">
        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 8px;">
            Set New Staff Password
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: var(--radius-md); font-size: 0.88rem; margin-bottom: 1.25rem;">
            <?php echo sanitize_output($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($tokenValid): ?>
        <p style="font-size: 0.9rem; color: var(--text-body); margin-bottom: 1.25rem;">
            Resetting password for staff account: <strong><?php echo sanitize_output($adminRecord['username']); ?></strong> (<?php echo sanitize_output($adminRecord['email']); ?>)
        </p>

        <form method="POST" action="<?php echo ADMIN_URL; ?>reset-password.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="token" value="<?php echo sanitize_output($token); ?>">

            <div style="margin-bottom: 1.25rem;">
                <label class="filter-label" for="new_password">New Password (Min. 6 characters)</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6" autofocus placeholder="Enter your new simple password">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="filter-label" for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6" placeholder="Repeat your new password">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem;">
                Save New Password &amp; Log In &rarr;
            </button>
        </form>
    <?php else: ?>
        <div style="text-align: center; margin-top: 1rem;">
            <a href="<?php echo ADMIN_URL; ?>forgot-password.php" class="btn btn-primary btn-block" style="padding: 10px;">
                Request New Password Reset Link &rarr;
            </a>
        </div>
    <?php endif; ?>

    <div style="border-top: 1px solid var(--border-color); margin-top: 1.5rem; padding-top: 1rem; text-align: center;">
        <a href="<?php echo ADMIN_URL; ?>login.php" style="font-size: 0.85rem; color: var(--text-muted);">
            &larr; Back to Login
        </a>
    </div>
</div>

</body>
</html>
