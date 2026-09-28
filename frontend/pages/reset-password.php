<?php
/**
 * =============================================================================
 * Moal General Suppliers - Reset Password (Stage 1 Clean UI)
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_customer_logged_in()) {
    redirect(BASE_URL . 'customer-dashboard.php');
}

$pageTitle = 'Create New Password';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$tokenValid = false;

if (!empty($token)) {
    $val = verify_password_reset_token($token);
    if ($val['valid']) {
        $tokenValid = true;
    } else {
        $errors[] = $val['error'];
    }
} else {
    $errors[] = 'Missing password reset token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $result = reset_customer_password($token, $newPassword, $confirmPassword);
        if ($result['success']) {
            set_flash_message('success', 'Your password has been updated. Please sign in.');
            redirect(BASE_URL . 'customer-login.php');
        } else {
            $errors[] = $result['error'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-box">
        
        <h1 class="auth-title">Create New Password</h1>

        <?php if (!empty($errors)): ?>
            <div class="auth-alert-box error">
                <?php foreach ($errors as $err): ?>
                    <div><?php echo sanitize_output($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($tokenValid): ?>
            <form method="POST" action="<?php echo BASE_URL; ?>reset-password.php?token=<?php echo urlencode($token); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="token" value="<?php echo sanitize_output($token); ?>">

                <div class="auth-form-group">
                    <label for="new_password" class="auth-label">New Password</label>
                    <div class="password-input-group">
                        <input type="password" id="new_password" name="new_password" class="form-control" required autofocus autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" data-target="new_password" aria-label="Toggle password visibility">
                            <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    <div class="password-hint" style="font-size: 0.78rem; color: var(--color-text-muted); margin-top: 6px; line-height: 1.5;">
                        Minimum 8 characters, with at least one uppercase letter, one lowercase letter, and one special character.
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="confirm_password" class="auth-label">Confirm Password</label>
                    <div class="password-input-group">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" data-target="confirm_password" aria-label="Toggle password visibility">
                            <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Update Password
                </button>
            </form>

        <?php else: ?>
            <div style="text-align: center;">
                <a href="<?php echo BASE_URL; ?>forgot-password.php" class="btn btn-primary" style="width: 100%;">
                    Request New Link
                </a>
            </div>
        <?php endif; ?>

        <div class="auth-footer-links">
            <div>
                <a href="<?php echo BASE_URL; ?>customer-login.php">Back to Sign In</a>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.password-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (!input) return;
            var eyeClosed = this.querySelector('.eye-closed');
            var eyeOpen = this.querySelector('.eye-open');
            if (input.type === 'password') {
                input.type = 'text';
                if (eyeClosed) eyeClosed.style.display = 'none';
                if (eyeOpen) eyeOpen.style.display = 'block';
            } else {
                input.type = 'password';
                if (eyeClosed) eyeClosed.style.display = 'block';
                if (eyeOpen) eyeOpen.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>