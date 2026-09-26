<?php
/**
 * =============================================================================
 * Moal General Suppliers - Staff & Dealership Login
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect(ADMIN_URL . 'dashboard.php');
}

$pageTitle = 'Staff Management Sign In';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $errors[] = 'Please enter both your authorized staff username/email and password.';
        } else {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM admins WHERE (username = :u1 OR email = :u2) AND status = "active" LIMIT 1');
            $stmt->execute([':u1' => $username, ':u2' => $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = (int)$admin['id'];
                $_SESSION['admin_username']  = $admin['username'];
                $_SESSION['admin_name']      = $admin['full_name'];
                $_SESSION['admin_role']      = $admin['role'];

                $db->prepare('UPDATE admins SET last_login = NOW() WHERE id = :id')->execute([':id' => $admin['id']]);

                set_flash_message('success', 'Welcome back, ' . $admin['full_name'] . '!');
                redirect(ADMIN_URL . 'dashboard.php');
            } else {
                $errors[] = 'Invalid authorized credentials or staff account is inactive.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-box">
        
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; color: var(--color-primary); background: rgba(217, 130, 91, 0.12); padding: 4px 10px; border-radius: 4px;">
                Authorized Personnel Only
            </span>
            <h1 class="auth-title" style="margin-top: 0.75rem;">Staff Management</h1>
            <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-top: 4px;">
                Sign in to manage inventory, inquiries, and customer requests.
            </p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="auth-alert-box error">
                <?php foreach ($errors as $err): ?>
                    <div><?php echo sanitize_output($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo BASE_URL; ?>staff-login.php" id="staffLoginForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="auth-form-group">
                <label for="username" class="auth-label">Staff Username or Email</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo sanitize_output($_POST['username'] ?? ''); ?>" required autofocus autocomplete="username" placeholder="e.g. admin or staff@moal.com">
            </div>

            <div class="auth-form-group">
                <label for="password" class="auth-label">Password</label>
                <div class="password-input-group">
                    <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password" placeholder="Enter your password">
                    <button type="button" class="password-toggle-btn" data-target="password" aria-label="Toggle password visibility">
                        <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-dark" style="width: 100%; padding: 12px; margin-top: 0.5rem;" id="loginSubmitBtn">
                <span id="btnText">Sign In to Dashboard</span>
                <span id="btnSpinner" style="display: none;">Authenticating...</span>
            </button>
        </form>

        <div class="auth-footer-links" style="margin-top: 1.5rem; text-align: center; border-top: 1px solid var(--color-border); padding-top: 1rem;">
            <a href="<?php echo BASE_URL; ?>" style="color: var(--color-text-muted); font-size: 0.88rem; text-decoration: none;">
                &larr; Return to Public Dealership Website
            </a>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('staffLoginForm');
    var btn = document.getElementById('loginSubmitBtn');
    var text = document.getElementById('btnText');
    var spin = document.getElementById('btnSpinner');

    if (form && btn) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            if (text) text.style.display = 'none';
            if (spin) spin.style.display = 'inline';
        });
    }

    document.querySelectorAll('.password-toggle-btn').forEach(function(b) {
        b.addEventListener('click', function(e) {
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