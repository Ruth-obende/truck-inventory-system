<?php
/**
 * =============================================================================
 * Moal General Suppliers - Dealership Staff & Admin Gateway
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// If already authenticated as administrator, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect(ADMIN_URL . 'dashboard.php');
}

$pageTitle = 'Staff & Dealership Portal';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedCsrf)) {
        $errors[] = 'Security session expired. Please refresh the page and try again.';
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $errors[] = 'Please enter both your authorized username and password.';
        } else {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM admins WHERE (username = :u OR email = :u) AND is_active = 1 LIMIT 1');
            $stmt->execute([':u' => $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = (int)$admin['id'];
                $_SESSION['admin_username']  = $admin['username'];
                $_SESSION['admin_name']      = $admin['full_name'];
                $_SESSION['admin_role']      = $admin['role'];

                $db->prepare('UPDATE admins SET last_login = NOW() WHERE id = :id')->execute([':id' => $admin['id']]);

                set_flash_message('success', 'Authenticated successfully as ' . $admin['full_name']);
                redirect(ADMIN_URL . 'dashboard.php');
            } else {
                $errors[] = 'Invalid authorized credentials or account is inactive.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 3.5rem; min-height: calc(100vh - 350px); display: flex; align-items: center;">
    <div class="container" style="max-width: 480px;">
        
        <div class="form-card" style="padding: clamp(2rem, 5vw, 2.75rem);">
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <span class="badge badge-dark" style="margin-bottom: 0.75rem;">Staff &amp; Admin Gateway</span>
                <h1 style="font-size: 1.6rem; color: var(--color-dark); margin-bottom: 0.5rem;">Dealership Administration</h1>
                <p style="color: var(--color-text-muted); font-size: 0.92rem; margin: 0;">
                    Secure access for Moal General Suppliers authorized staff and inventory managers.
                </p>
            </div>

            <?php if (!empty($errors)): ?>
                <div style="background: #FFEBEE; border: 1px solid #FFCDD2; color: #C62828; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.92rem;">
                    <?php foreach ($errors as $err): ?>
                        <div><?php echo sanitize_output($err); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>staff-login.php">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div class="form-group">
                    <label for="username" class="form-label">Authorized Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" value="<?php echo sanitize_output($_POST['username'] ?? ''); ?>" required autofocus>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-dark btn-lg" style="width: 100%;">
                    Log In to Admin Dashboard &rarr;
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--color-text-muted);">
                <a href="<?php echo BASE_URL; ?>" style="color: var(--color-text-muted);">&larr; Return to Public Website</a>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
