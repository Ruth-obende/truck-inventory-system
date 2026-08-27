<?php
/**
 * =============================================================================
 * Moal General Suppliers - Administrator Profile & Password Security
 * =============================================================================
 * Allows staff to manage their account details and securely update their password.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'My Profile & Security';
$db = getDB();
$adminId = (int)$_SESSION['admin_id'];

$errors = [];
$success = '';

// 1. Fetch Current Admin Profile
$stmt = $db->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $adminId]);
$admin = $stmt->fetch();

if (!$admin) {
    redirect(ADMIN_URL . 'logout.php');
}

// 2. Handle Profile / Password Update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Security session expired. Please reload the page.';
    } else {
        $action = sanitize_input($_POST['action'] ?? '');

        // A. Update Profile Information
        if ($action === 'update_profile') {
            $fullName = sanitize_input($_POST['full_name'] ?? '');
            $email    = sanitize_input($_POST['email'] ?? '');

            if (empty($fullName) || strlen($fullName) < 3) {
                $errors[] = 'Full name must be at least 3 characters.';
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please provide a valid email address.';
            }

            // Check email uniqueness
            $stmtCheck = $db->prepare('SELECT id FROM admins WHERE email = :email AND id != :id');
            $stmtCheck->execute([':email' => $email, ':id' => $adminId]);
            if ($stmtCheck->fetch()) {
                $errors[] = 'This email address is already registered to another account.';
            }

            if (empty($errors)) {
                $stmtUpdate = $db->prepare('UPDATE admins SET full_name = :name, email = :email WHERE id = :id');
                $stmtUpdate->execute([':name' => $fullName, ':email' => $email, ':id' => $adminId]);
                
                $_SESSION['admin_name'] = $fullName;
                $admin['full_name'] = $fullName;
                $admin['email'] = $email;
                set_flash_message('success', 'Profile information updated successfully.');
                redirect(ADMIN_URL . 'profile.php');
            }
        }

        // B. Change Password
        if ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass     = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
                $errors[] = 'All password fields are required.';
            } elseif (!password_verify($currentPass, $admin['password_hash'])) {
                $errors[] = 'Current password entered is incorrect.';
            } elseif (strlen($newPass) < 8) {
                $errors[] = 'New password must be at least 8 characters long.';
            } elseif ($newPass !== $confirmPass) {
                $errors[] = 'New password and confirmation do not match.';
            }

            if (empty($errors)) {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $stmtPass = $db->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
                $stmtPass->execute([':hash' => $newHash, ':id' => $adminId]);
                
                set_flash_message('success', 'Your password has been changed successfully.');
                redirect(ADMIN_URL . 'profile.php');
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="font-size: 1.4rem; color: var(--admin-navy);">Staff Profile &amp; Security Settings</h2>
        <p style="color: var(--admin-text-muted); font-size: 0.9rem;">Manage your administrator credentials, email, and login password.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <strong style="display: block; margin-bottom: 4px;">Please correct the errors below:</strong>
        <ul style="margin-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
                <li><?php echo sanitize_output($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: flex-start;">
    
    <!-- Profile Info Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title">Profile Information</div>
            <span class="badge badge-navy"><?php echo sanitize_output($admin['role']); ?></span>
        </div>

        <form method="POST" action="<?php echo ADMIN_URL; ?>profile.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="update_profile">

            <div style="margin-bottom: 1.25rem;">
                <label class="filter-label" for="username">Username (Fixed Identifier)</label>
                <input type="text" id="username" class="form-control" value="<?php echo sanitize_output($admin['username']); ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                <small style="color: var(--admin-text-muted);">Username cannot be modified.</small>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="filter-label" for="full_name">Full Name *</label>
                <input type="text" name="full_name" id="full_name" class="form-control" required value="<?php echo sanitize_output($_POST['full_name'] ?? $admin['full_name']); ?>">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="filter-label" for="email">Official Email Address *</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?php echo sanitize_output($_POST['email'] ?? $admin['email']); ?>">
            </div>

            <div style="border-top: 1px solid var(--admin-border); padding-top: 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: var(--admin-text-muted);">
                Last Logged In: <strong><?php echo $admin['last_login'] ? date('M j, Y &bull; g:ia', strtotime($admin['last_login'])) : 'First Session'; ?></strong>
            </div>

            <button type="submit" class="btn btn-navy btn-block">
                Save Profile Changes
            </button>
        </form>
    </div>

    <!-- Password Change Card -->
    <div class="admin-card" style="border-top: 4px solid var(--admin-orange);">
        <div class="admin-card-header">
            <div class="admin-card-title">Change Password</div>
            <span class="badge badge-warning">Bcrypt Security</span>
        </div>

        <form method="POST" action="<?php echo ADMIN_URL; ?>profile.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="change_password">

            <div style="margin-bottom: 1.25rem;">
                <label class="filter-label" for="current_password">Current Password *</label>
                <input type="password" name="current_password" id="current_password" class="form-control" required placeholder="••••••••">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="filter-label" for="new_password">New Password * (Min. 8 characters)</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8" placeholder="••••••••">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="filter-label" for="confirm_password">Confirm New Password *</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8" placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Update Password &rarr;
            </button>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
