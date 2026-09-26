<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Sign Up (Stage 1 Clean UI)
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_customer_logged_in()) {
    redirect(BASE_URL . 'customer-dashboard.php');
}

$pageTitle = 'Sign Up';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors['csrf'] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $result = register_customer($_POST);
        if ($result['success']) {
            redirect(BASE_URL . 'verify-otp.php?email=' . urlencode($result['email']));
        } else {
            $errors = $result['errors'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-box">
        
        <h1 class="auth-title">Sign Up</h1>

        <?php if (!empty($errors)): ?>
            <div class="auth-alert-box error">
                <?php foreach ($errors as $err): ?>
                    <div><?php echo sanitize_output($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo BASE_URL; ?>customer-signup.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="auth-form-group">
                <label for="full_name" class="auth-label">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo sanitize_output($_POST['full_name'] ?? ''); ?>" required autofocus autocomplete="name">
            </div>

            <div class="auth-form-group">
                <label for="email" class="auth-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo sanitize_output($_POST['email'] ?? ''); ?>" required autocomplete="email">
            </div>

            <div class="auth-form-group">
                <label for="phone" class="auth-label">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo sanitize_output($_POST['phone'] ?? ''); ?>" required autocomplete="tel">
            </div>

            <div class="auth-form-group">
                <label for="password" class="auth-label">Password</label>
                <div class="password-input-group">
                    <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password">
                    <button type="button" class="password-toggle-btn" data-target="password" aria-label="Toggle password visibility">
                        <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
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

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                Create Account
            </button>
        </form>

        <div class="auth-footer-links">
            <div>
                Already have an account? 
                <a href="<?php echo BASE_URL; ?>customer-login.php">Sign In</a>
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