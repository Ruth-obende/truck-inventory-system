<?php
/**
 * =============================================================================
 * Moal General Suppliers - Verify Your Email
 * =============================================================================
 * Dedicated, responsive email verification page supporting:
 * - 6-digit numeric verification code
 * - 1-click token verification from email link
 * - Resend code with 30-second countdown cooldown
 * - Email address correction option
 * - Direct redirection to protected inventory upon verification
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_customer_logged_in()) {
    redirect(BASE_URL . 'inventory.php');
}

$pageTitle = 'Verify your email';
$email = trim($_GET['email'] ?? $_SESSION['pending_verification_email'] ?? '');
$token = trim($_GET['token'] ?? '');
$errors = [];
$successMessage = '';

// 1. Direct One-Click Token Verification Link Handler
if (!empty($token) && !empty($email) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = verify_customer_token($email, $token);
    if ($result['success']) {
        set_flash_message('success', 'Email verified successfully! You now have full access to our commercial truck inventory and prices.');
        $target = $_SESSION['redirect_after_login'] ?? (BASE_URL . 'inventory.php');
        unset($_SESSION['redirect_after_login']);
        redirect($target);
    } else {
        $errors[] = $result['error'];
    }
}

// 2. Handle 6-Digit Verification Code Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_code') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $submittedEmail = trim($_POST['email'] ?? '');
        $submittedOtp   = trim($_POST['otp'] ?? '');

        $result = verify_customer_otp($submittedEmail, $submittedOtp);
        if ($result['success']) {
            set_flash_message('success', 'Email verified successfully! Welcome to Moal General Suppliers.');
            $target = $_SESSION['redirect_after_login'] ?? (BASE_URL . 'inventory.php');
            unset($_SESSION['redirect_after_login']);
            redirect($target);
        } else {
            $errors[] = $result['error'];
            $email = $submittedEmail;
        }
    }
}

// 3. Handle Resend Verification Code Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend_code') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $resendEmail = trim($_POST['email'] ?? '');
        $resendResult = resend_customer_otp($resendEmail);
        if ($resendResult['success']) {
            $successMessage = 'A new verification code has been sent to your email address.';
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
        
        <h1 class="auth-title">Verify your email</h1>
        
        <p class="auth-subtitle" style="font-size: 0.95rem; line-height: 1.55; color: var(--color-text-muted);">
            We've sent a verification code to the email address you provided<?php if (!empty($email)): ?> (<strong style="color: var(--color-dark);"><?php echo sanitize_output($email); ?></strong>)<?php endif; ?>. Enter the code below to verify your account.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="auth-alert-box error" style="margin-bottom: 1.25rem;">
                <?php foreach ($errors as $err): ?>
                    <div><?php echo sanitize_output($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="auth-alert-box success" style="margin-bottom: 1.25rem;">
                <?php echo sanitize_output($successMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Verification Form -->
        <form method="POST" action="<?php echo BASE_URL; ?>verify-email.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="verify_code">
            
            <?php if (empty($email)): ?>
                <div class="auth-form-group">
                    <label for="email" class="auth-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="your.email@example.com">
                </div>
            <?php else: ?>
                <input type="hidden" name="email" value="<?php echo sanitize_output($email); ?>">
            <?php endif; ?>

            <div class="auth-form-group">
                <label for="otp" class="auth-label" style="text-align: center; font-weight: 600;">Verification Code</label>
                <input type="text" id="otp" name="otp" class="form-control" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="[ ______ ]" style="text-align: center; font-size: 1.6rem; letter-spacing: 10px; font-weight: 700; font-family: monospace;" required autofocus autocomplete="one-time-code">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 1rem;">
                Verify Email
            </button>
        </form>

        <!-- Resend Code Action with 30s Cooldown -->
        <?php if (!empty($email)): ?>
            <form method="POST" action="<?php echo BASE_URL; ?>verify-email.php" id="resendForm" style="margin-top: 1.25rem; text-align: center;">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="resend_code">
                <input type="hidden" name="email" value="<?php echo sanitize_output($email); ?>">

                <button type="submit" id="resendBtn" class="btn btn-outline" style="width: 100%; border: 1px dashed var(--color-border); font-size: 0.9rem; padding: 10px; color: var(--color-text);">
                    Resend Code
                </button>
                <div id="resendTimerText" style="display: none; font-size: 0.85rem; color: var(--color-text-muted); margin-top: 6px;">
                    Resend code in <span id="cooldownSeconds" style="font-weight: 700; color: var(--color-primary);">30</span> seconds
                </div>
            </form>
        <?php endif; ?>

        <!-- Correction & Navigation Options -->
        <div class="auth-footer-links" style="margin-top: 1.75rem; text-align: center; font-size: 0.88rem;">
            <div style="margin-bottom: 0.5rem;">
                Incorrect email address? 
                <a href="<?php echo BASE_URL; ?>customer-signup.php" style="font-weight: 600; color: var(--color-primary);">Change email</a>
            </div>
            <div>
                Already have a verified account? 
                <a href="<?php echo BASE_URL; ?>customer-login.php">Sign In</a>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 30-Second Resend Cooldown
    var resendBtn = document.getElementById('resendBtn');
    var timerText = document.getElementById('resendTimerText');
    var countdownSpan = document.getElementById('cooldownSeconds');

    if (resendBtn && timerText && countdownSpan) {
        var cooldown = 30;
        resendBtn.disabled = true;
        resendBtn.style.opacity = '0.5';
        resendBtn.style.cursor = 'not-allowed';
        timerText.style.display = 'block';

        var interval = setInterval(function() {
            cooldown--;
            countdownSpan.textContent = cooldown;
            if (cooldown <= 0) {
                clearInterval(interval);
                resendBtn.disabled = false;
                resendBtn.style.opacity = '1';
                resendBtn.style.cursor = 'pointer';
                timerText.style.display = 'none';
            }
        }, 1000);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>