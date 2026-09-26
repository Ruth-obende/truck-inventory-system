<?php
/**
 * =============================================================================
 * Moal General Suppliers - Email OTP Verification (Stage 1)
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_customer_logged_in()) {
    redirect(BASE_URL . 'customer-dashboard.php');
}

$pageTitle = 'Verify Email';
$email = trim($_GET['email'] ?? $_SESSION['pending_verification_email'] ?? '');
$errors = [];
$successMessage = '';

// Handle OTP Verification Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $submittedEmail = trim($_POST['email'] ?? '');
        $submittedOtp   = trim($_POST['otp'] ?? '');

        $result = verify_customer_otp($submittedEmail, $submittedOtp);
        if ($result['success']) {
            set_flash_message('success', 'Email verified successfully! Welcome to your Moal General Suppliers client dashboard.');
            redirect(BASE_URL . 'customer-dashboard.php');
        } else {
            $errors[] = $result['error'];
            $email = $submittedEmail;
        }
    }
}

// Handle Resend OTP Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend_otp') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $resendEmail = trim($_POST['email'] ?? '');
        $resendResult = resend_customer_otp($resendEmail);
        if ($resendResult['success']) {
            $successMessage = $resendResult['message'];
            $email = $resendEmail;
        } else {
            $errors[] = $resendResult['error'];
            $email = $resendEmail;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-box">
        
        <h1 class="auth-title">Verify Your Email</h1>
        <p class="auth-subtitle">
            Enter the 6-digit verification code sent to<br>
            <strong style="color: var(--color-dark);"><?php echo sanitize_output($email); ?></strong>
        </p>

        <?php if (!empty($errors)): ?>
            <div class="auth-alert-box error">
                <?php foreach ($errors as $err): ?>
                    <div><?php echo sanitize_output($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="auth-alert-box success">
                <?php echo sanitize_output($successMessage); ?>
            </div>
        <?php endif; ?>

        <!-- OTP Input Form -->
        <form method="POST" action="<?php echo BASE_URL; ?>verify-otp.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="verify_otp">
            <input type="hidden" name="email" value="<?php echo sanitize_output($email); ?>">

            <div class="auth-form-group">
                <label for="otp" class="auth-label" style="text-align: center;">Verification Code (OTP)</label>
                <input type="text" id="otp" name="otp" class="form-control" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="------" style="text-align: center; font-size: 1.5rem; letter-spacing: 8px; font-weight: 700; font-family: monospace;" required autofocus autocomplete="one-time-code">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Verify Code &rarr;
            </button>
        </form>

        <!-- Resend OTP Action -->
        <form method="POST" action="<?php echo BASE_URL; ?>verify-otp.php" style="margin-top: 1rem;">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="resend_otp">
            <input type="hidden" name="email" value="<?php echo sanitize_output($email); ?>">

            <button type="submit" class="btn btn-outline" style="width: 100%; border: 1px dashed var(--color-border); font-size: 0.88rem; padding: 10px;">
                Resend OTP Code
            </button>
        </form>

        <div class="auth-footer-links">
            <div>
                <a href="<?php echo BASE_URL; ?>customer-login.php">Sign In with a different account</a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>