<?php
/**
 * =============================================================================
 * Moal General Suppliers - Administrator Login
 * =============================================================================
 * Secure authentication handler for dealership staff with bcrypt password verification.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Redirect if already logged in
if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect(ADMIN_URL . 'dashboard.php');
}

$error = '';
$flash = get_flash_message();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($submittedCsrf)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please provide both username and password.';
        } else {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM admins WHERE (username = :u OR email = :u) AND status = "active" LIMIT 1');
            $stmt->execute([':u' => $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Prevent Session Fixation
                session_regenerate_id(true);

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = (int)$admin['id'];
                $_SESSION['admin_username']  = $admin['username'];
                $_SESSION['admin_name']      = $admin['full_name'];
                $_SESSION['admin_role']      = $admin['role'];

                // Update last login timestamp
                $stmtUpdate = $db->prepare('UPDATE admins SET last_login = NOW() WHERE id = :id');
                $stmtUpdate->execute([':id' => $admin['id']]);

                redirect(ADMIN_URL . 'dashboard.php');
            } else {
                $error = 'Invalid username/email or password.';
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
    <title>Staff Portal Login | <?php echo APP_NAME; ?></title>
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
            max-width: 440px;
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
            Dealership Staff Portal
        </div>
    </div>

    <?php if ($flash): ?>
        <div style="background: <?php echo $flash['type'] === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $flash['type'] === 'success' ? '#166534' : '#991b1b'; ?>; padding: 10px 14px; border-radius: var(--radius-md); font-size: 0.88rem; margin-bottom: 1.25rem;">
            <?php echo sanitize_output($flash['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: var(--radius-md); font-size: 0.88rem; margin-bottom: 1.25rem;">
            <?php echo sanitize_output($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo ADMIN_URL; ?>login.php">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

        <div style="margin-bottom: 1.25rem;">
            <label class="filter-label" for="username">Username or Email</label>
            <input type="text" name="username" id="username" class="form-control" required autofocus placeholder="admin" value="<?php echo sanitize_output($_POST['username'] ?? ''); ?>">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label class="filter-label" for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem;">
            Authenticate &amp; Enter Dashboard &rarr;
        </button>
    </form>

    <div style="border-top: 1px solid var(--border-color); margin-top: 1.5rem; padding-top: 1rem; text-align: center;">
        <a href="<?php echo BASE_URL; ?>" style="font-size: 0.85rem; color: var(--text-muted);">
            &larr; Return to Public Website
        </a>
    </div>
</div>

</body>
</html>
