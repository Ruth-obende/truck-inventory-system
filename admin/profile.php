<?php
/**
 * =============================================================================
 * Moal General Suppliers - Staff Account Settings & Security
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Staff Settings';
$db = getDB();
$adminId = (int)$_SESSION['admin_id'];

$stmt = $db->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $adminId]);
$admin = $stmt->fetch();

if (!$admin) {
    redirect(ADMIN_URL . 'logout.php');
}

$profileErrors = [];
$securityErrors = [];

// Handle Profile Update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $profileErrors[] = 'Security token expired. Please try again.';
    } else {
        $fullName = sanitize_input($_POST['full_name'] ?? '');
        $email    = sanitize_input($_POST['email'] ?? '');

        if (empty($fullName)) $profileErrors[] = 'Full name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $profileErrors[] = 'A valid email address is required.';

        if (empty($profileErrors)) {
            $stmtUp = $db->prepare('UPDATE admins SET full_name = :fn, email = :em, updated_at = NOW() WHERE id = :id');
            $stmtUp->execute([':fn' => $fullName, ':em' => $email, ':id' => $adminId]);

            $_SESSION['admin_name'] = $fullName;
            set_flash_message('success', 'Your staff profile information was successfully updated.');
            redirect(ADMIN_URL . 'settings.php');
        }
    }
}

// Handle Password Change (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $securityErrors[] = 'Security token expired. Please try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $securityErrors[] = 'All password fields are required.';
        } elseif (!password_verify($currentPassword, $admin['password_hash'])) {
            $securityErrors[] = 'Your current password was entered incorrectly.';
        } elseif (strlen($newPassword) < 6) {
            $securityErrors[] = 'New password must be at least 6 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $securityErrors[] = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtPass = $db->prepare('UPDATE admins SET password_hash = :p, updated_at = NOW() WHERE id = :id');
            $stmtPass->execute([':p' => $newHash, ':id' => $adminId]);

            set_flash_message('success', 'Your password has been changed successfully.');
            redirect(ADMIN_URL . 'settings.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 800; color: var(--admin-navy);">Staff Profile &amp; Account Settings</h1>
        <p style="color: var(--admin-text-muted); font-size: 0.88rem; margin-top: 2px;">
            Manage your authorized administrative credentials and password security.
        </p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: flex-start;">
    
    <!-- Profile Information Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title">Staff Profile Details</div>
        </div>

        <?php if (!empty($profileErrors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($profileErrors as $err): ?>
                    <div><?php echo sanitize_output($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo ADMIN_URL; ?>settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label for="username" class="form-label">Staff Username (Fixed)</label>
                <input type="text" id="username" class="form-control" value="<?php echo sanitize_output($admin['username']); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="role" class="form-label">Authorized Role</label>
                <input type="text" id="role" class="form-control" value="<?php echo sanitize_output(ucfirst($admin['role'])); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="full_name" class="form-label">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo sanitize_output($_POST['full_name'] ?? $admin['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo sanitize_output($_POST['email'] ?? $admin['email']); ?>" required>
            </div>

            <button type="submit" class="btn btn-dark btn-block" style="padding: 11px; margin-top: 1rem;">
                 Update Profile Info
            </button>
        </form>
    </div>

    <!-- Password Security Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title">Change Password</div>
        </div>

        <?php if (!empty($securityErrors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($securityErrors as $err): ?>
                    <div><?php echo sanitize_output($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo ADMIN_URL; ?>settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="current_password" class="form-label">Current Password *</label>
                <div class="password-input-group" style="position: relative;">
                    <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password">
                    <button type="button" class="password-toggle-btn" data-target="current_password" aria-label="Toggle password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--admin-text-muted);">
                        <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password" class="form-label">New Password (Min. 6 chars) *</label>
                <div class="password-input-group" style="position: relative;">
                    <input type="password" id="new_password" name="new_password" class="form-control" required autocomplete="new-password">
                    <button type="button" class="password-toggle-btn" data-target="new_password" aria-label="Toggle password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--admin-text-muted);">
                        <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                <div class="password-input-group" style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
                    <button type="button" class="password-toggle-btn" data-target="confirm_password" aria-label="Toggle password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--admin-text-muted);">
                        <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                        <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 11px; margin-top: 1rem;">
                 Change Password
            </button>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>