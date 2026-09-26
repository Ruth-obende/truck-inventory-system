<?php
/**
 * =============================================================================
 * Moal General Suppliers - Forgot Password (Stage 1 Clean UI)
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_customer_logged_in()) {
    redirect(BASE_URL . 'customer-dashboard.php');
}

$pageTitle = 'Forgot Password';
$submitted = false;
$emailSent = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($submittedCsrf)) {
        $email = sanitize_input($_POST['email'] ?? '');
        request_password_reset($email);
        $submitted = true;
        $emailSent = $email;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-box">
        
        <h1 class="auth-title">Forgot Password</h1>

        <?php if ($submitted): ?>
            <p class="auth-subtitle">
                We sent a password reset link to your email.
            </p>
            <a href="<?php echo BASE_URL; ?>customer-login.php" class="btn btn-primary" style="width: 100%;">
                Back to Sign In
            </a>

        <?php else: ?>

            <form method="POST" action="<?php echo BASE_URL; ?>forgot-password.php">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="auth-form-group">
                    <label for="email" class="auth-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required autofocus autocomplete="email">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Send Reset Link
                </button>
            </form>

            <div class="auth-footer-links">
                <div>
                    <a href="<?php echo BASE_URL; ?>customer-login.php">Back to Sign In</a>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>