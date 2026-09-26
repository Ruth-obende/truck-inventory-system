<?php
/**
 * =============================================================================
 * Moal General Suppliers - Administrator Password Reset Request
 * =============================================================================
 * Allows staff to initiate a secure password reset using their registered
 * username or email address.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Redirect if already logged in
if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect(ADMIN_URL . 'dashboard.php');
}

$error = '';
$success = '';
$resetUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($submittedCsrf)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $identifier = sanitize_input($_POST['identifier'] ?? '');

        if (empty($identifier)) {
            $error = 'Please enter your username or registered email address.';
        } else {
            $db = getDB();
            $stmt = $db->prepare('
                SELECT id, username, email, full_name 
                FROM admins 
                WHERE (LOWER(username) = :id1 OR LOWER(email) = :id2) 
                AND status = "active" 
                LIMIT 1
            ');
            $stmt->execute([
                ':id1' => strtolower($identifier),
                ':id2' => strtolower($identifier)
            ]);
            $admin = $stmt->fetch();

            if ($admin) {
                // Generate a cryptographically secure 64-character token
                $token = bin2hex(random_bytes(32));

                // Save token in database with 1-hour expiry
                $stmtToken = $db->prepare('
                    UPDATE admins 
                    SET reset_token = :token, 
                        reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                    WHERE id = :id
                ');
                $stmtToken->execute([
                    ':token' => $token,
                    ':id'    => $admin['id']
                ]);

                $resetUrl = ADMIN_URL . 'reset-password.php?token=' . urlencode($token);
                $success = 'Password reset authorization generated successfully.';
            } else {
                $error = 'No active administrator account was found with that username or email.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?php echo APP_NAME; ?></title>
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
            Password Reset Authorization
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: var(--radius-md); font-size: 0.88rem; margin-bottom: 1.25rem;">
            <?php echo sanitize_output($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success) && !empty($resetUrl)): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px; border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.5rem;">
            <strong> Reset Link Ready!</strong>
            <p style="margin-top: 6px; font-size: 0.85rem; line-height: 1.5;">
                Click the button below to set your new password immediately (valid for 1 hour):
            </p>
            <div style="margin-top: 12px; text-align: center;">
                <a href="<?php echo $resetUrl; ?>" class="btn btn-primary btn-block" style="padding: 10px; font-size: 0.95rem;">
                    Set New Password Now &rarr;
                </a>
            </div>
        </div>
    <?php else: ?>
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
            Enter your staff <strong>username</strong> (e.g. <code>admin</code>) or registered <strong>email</strong> (e.g. <code>Moal4gs@gmail.com</code>) to generate a secure reset link.
        </p>

        <form method="POST" action="<?php echo ADMIN_URL; ?>forgot-password.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div style="margin-bottom: 1.5rem;">
                <label class="filter-label" for="identifier">Username or Email</label>
                <input type="text" name="identifier" id="identifier" class="form-control" required autofocus placeholder="admin or Moal4gs@gmail.com" value="<?php echo sanitize_output($_POST['identifier'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem;">
                Generate Password Reset Link &rarr;
            </button>
        </form>
    <?php endif; ?>

    <div style="border-top: 1px solid var(--border-color); margin-top: 1.5rem; padding-top: 1rem; text-align: center;">
        <a href="<?php echo ADMIN_URL; ?>login.php" style="font-size: 0.85rem; color: var(--text-muted);">
            &larr; Back to Login
        </a>
    </div>
</div>

</body>
</html>
