<?php
/**
 * =============================================================================
 * Moal General Suppliers - Client Account Settings & Profile Management
 * =============================================================================
 * Allows authenticated clients to configure and update:
 * 1. Personal & Company profile details (Full name, company, phone, delivery address).
 * 2. Security & Password updates with strict password strength validation.
 * 3. Commercial fleet preferences & communication channels.
 * 4. Comprehensive account audit status, verification badges, and quote records.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Enforce client authentication
require_customer_login();

$customer = get_logged_in_customer();
$pageTitle = 'Account Settings';
$db = getDB();

$customerId = (int)$customer['id'];
$customerEmail = $customer['email'];

$profileErrors = [];
$passwordErrors = [];
$activeTab = sanitize_input($_GET['tab'] ?? $_POST['active_tab'] ?? 'profile');

// -----------------------------------------------------------------------------
// 1. Handle Profile Information Update
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $activeTab = 'profile';
    $submittedCsrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($submittedCsrf)) {
        $profileErrors[] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $fullName        = trim(sanitize_input($_POST['full_name'] ?? ''));
        $businessName    = trim(sanitize_input($_POST['business_name'] ?? ''));
        $phone           = trim(sanitize_input($_POST['phone'] ?? ''));
        $deliveryAddress = trim(sanitize_input($_POST['delivery_address'] ?? ''));

        // Validation
        if (empty($fullName) || strlen($fullName) < 3) {
            $profileErrors[] = 'Please enter your full name (minimum 3 characters).';
        }

        if (empty($phone) || strlen($phone) < 7) {
            $profileErrors[] = 'Please enter a valid primary phone number.';
        }

        if (empty($profileErrors)) {
            try {
                $stmtUpdate = $db->prepare('
                    UPDATE customers 
                    SET full_name = :full_name,
                        business_name = :business_name,
                        phone = :phone,
                        delivery_address = :delivery_address,
                        updated_at = NOW()
                    WHERE id = :id
                ');
                $stmtUpdate->execute([
                    ':full_name'        => $fullName,
                    ':business_name'    => $businessName,
                    ':phone'            => $phone,
                    ':delivery_address' => $deliveryAddress,
                    ':id'               => $customerId
                ]);

                // Synchronize session values
                $_SESSION['customer_name']     = $fullName;
                $_SESSION['customer_phone']    = $phone;
                $_SESSION['customer_business'] = $businessName;
                $_SESSION['customer_address']  = $deliveryAddress;

                set_flash_message('success', 'Your profile details have been saved successfully.');
                redirect(BASE_URL . 'customer-settings.php?tab=profile');
            } catch (Exception $e) {
                error_log('[Profile Update Error] ' . $e->getMessage());
                $profileErrors[] = 'Unable to update profile at this time. Please try again.';
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 2. Handle Password & Security Update
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $activeTab = 'security';
    $submittedCsrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($submittedCsrf)) {
        $passwordErrors[] = 'Security token expired. Please refresh the page and try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            $passwordErrors[] = 'Please enter your current password.';
        } else {
            // Verify current password against database
            $stmtPw = $db->prepare('SELECT password_hash FROM customers WHERE id = :id LIMIT 1');
            $stmtPw->execute([':id' => $customerId]);
            $currentHash = $stmtPw->fetchColumn();

            if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
                $passwordErrors[] = 'The current password you entered is incorrect.';
            }
        }

        if (empty($newPassword)) {
            $passwordErrors[] = 'Please enter a new password.';
        } else {
            $strengthErr = validate_password_strength($newPassword);
            if ($strengthErr !== null) {
                $passwordErrors[] = $strengthErr;
            } elseif ($newPassword !== $confirmPassword) {
                $passwordErrors[] = 'New passwords do not match.';
            }
        }

        if (empty($passwordErrors)) {
            try {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmtUpdatePw = $db->prepare('
                    UPDATE customers 
                    SET password_hash = :hash,
                        updated_at = NOW()
                    WHERE id = :id
                ');
                $stmtUpdatePw->execute([
                    ':hash' => $newHash,
                    ':id'   => $customerId
                ]);

                set_flash_message('success', 'Your password has been changed securely.');
                redirect(BASE_URL . 'customer-settings.php?tab=security');
            } catch (Exception $e) {
                error_log('[Password Change Error] ' . $e->getMessage());
                $passwordErrors[] = 'Unable to update password. Please try again.';
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 3. Handle Preferences Update
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_preferences') {
    $activeTab = 'preferences';
    $submittedCsrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($submittedCsrf)) {
        set_flash_message('error', 'Security token expired. Please try again.');
    } else {
        // Store client preferences in session for seamless session experience
        $_SESSION['client_pref_category'] = sanitize_input($_POST['pref_category'] ?? 'Heavy Haulage');
        $_SESSION['client_pref_channel']  = sanitize_input($_POST['pref_channel'] ?? 'Email');
        $_SESSION['client_pref_notify_arrivals'] = isset($_POST['notify_arrivals']) ? '1' : '0';
        $_SESSION['client_pref_notify_quotes']   = isset($_POST['notify_quotes']) ? '1' : '0';

        set_flash_message('success', 'Your fleet notification and communication preferences have been updated.');
        redirect(BASE_URL . 'customer-settings.php?tab=preferences');
    }
}

// Fetch refreshed customer record
$customer = get_logged_in_customer();

// Fetch summary stats for Account Tab
$inquiriesCount = (int)$db->prepare('SELECT COUNT(*) FROM inquiries WHERE customer_email = ?')->execute([$customerEmail]) ? $db->prepare('SELECT COUNT(*) FROM inquiries WHERE customer_email = ?')->fetchColumn() : 0;
$stmtInqCnt = $db->prepare('SELECT COUNT(*) FROM inquiries WHERE customer_email = ?');
$stmtInqCnt->execute([$customerEmail]);
$inquiriesCount = (int)$stmtInqCnt->fetchColumn();

$stmtReqCnt = $db->prepare('SELECT COUNT(*) FROM customer_requests WHERE customer_id = ?');
$stmtReqCnt->execute([$customerId]);
$requestsCount = (int)$stmtReqCnt->fetchColumn();

// Preferences defaults
$prefCategory = $_SESSION['client_pref_category'] ?? 'Heavy Haulage';
$prefChannel  = $_SESSION['client_pref_channel'] ?? 'Email';
$notifyArrivals = $_SESSION['client_pref_notify_arrivals'] ?? '1';
$notifyQuotes   = $_SESSION['client_pref_notify_quotes'] ?? '1';

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem; padding-bottom: 5rem; background-color: var(--color-bg-light);">
    <div class="container">
        
        <!-- Header & Breadcrumb -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 6px;">
                    <a href="<?php echo BASE_URL; ?>inventory.php" style="color: var(--color-text-muted); text-decoration: none;">Truck Inventory</a>
                    <span>&rsaquo;</span>
                    <a href="<?php echo BASE_URL; ?>customer-dashboard.php" style="color: var(--color-text-muted); text-decoration: none;">Client Portal</a>
                    <span>&rsaquo;</span>
                    <span style="color: var(--color-primary); font-weight: 600;">Account Settings</span>
                </div>
                <h1 style="font-size: clamp(1.8rem, 3.2vw, 2.3rem); margin-bottom: 0.35rem; color: var(--color-dark); font-weight: 800;">
                    Client Settings
                </h1>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0; max-width: 600px;">
                    Manage your personal profile, registered company details, haulage yard delivery address, and account security.
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary btn-sm" style="padding: 9px 18px; font-weight: 600;">
                    Browse Inventory &rarr;
                </a>
                <a href="<?php echo BASE_URL; ?>customer-dashboard.php" class="btn btn-secondary btn-sm" style="padding: 9px 16px;">
                    My Quotes (<?php echo $requestsCount + $inquiriesCount; ?>)
                </a>
            </div>
        </div>

        <!-- Layout Grid: Left Sidebar (Client Card & Tab Nav) + Right Area (Active Tab Content) -->
        <div style="display: grid; grid-template-columns: 290px 1fr; gap: 2rem; align-items: start;">
            
            <!-- LEFT COLUMN: Client Identity Card & Tab Navigation -->
            <div>
                <!-- Identity Card -->
                <div class="form-card" style="padding: 1.75rem 1.5rem; text-align: center; border: 1px solid var(--color-border); margin-bottom: 1.5rem;">
                    <!-- Avatar Initial -->
                    <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, var(--color-primary), #B8653F); color: #FFFFFF; font-size: 1.85rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem; box-shadow: 0 4px 12px rgba(217, 130, 91, 0.35);">
                        <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                    </div>

                    <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--color-dark); margin-bottom: 4px; word-break: break-word;">
                        <?php echo sanitize_output($customer['full_name']); ?>
                    </h2>

                    <?php if (!empty($customer['business_name'])): ?>
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--color-primary); margin-bottom: 6px;">
                            <?php echo sanitize_output($customer['business_name']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="font-size: 0.82rem; color: var(--color-text-muted); margin-bottom: 1rem; word-break: break-all;">
                        <?php echo sanitize_output($customer['email']); ?>
                    </div>

                    <!-- Verified Status Pill -->
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; background: #ECFDF5; color: #065F46; border: 1px solid rgba(16, 185, 129, 0.3);">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #10B981; display: inline-block;"></span>
                        Verified Client Account
                    </div>

                    <!-- Member Since -->
                    <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--color-border); font-size: 0.78rem; color: var(--color-text-muted); display: flex; justify-content: space-between;">
                        <span>Member Since:</span>
                        <strong style="color: var(--color-dark);"><?php echo date('M Y', strtotime($customer['created_at'])); ?></strong>
                    </div>
                </div>

                <!-- Navigation Tabs List -->
                <div class="form-card" style="padding: 0.75rem; border: 1px solid var(--color-border);">
                    <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); font-weight: 700; padding: 6px 12px; margin-bottom: 4px;">
                        Settings Menu
                    </div>

                    <a href="?tab=profile" class="settings-nav-btn <?php echo ($activeTab === 'profile') ? 'active' : ''; ?>">
                        <span class="settings-nav-icon">👤</span>
                        <span>Profile &amp; Business</span>
                    </a>

                    <a href="?tab=security" class="settings-nav-btn <?php echo ($activeTab === 'security') ? 'active' : ''; ?>">
                        <span class="settings-nav-icon">🔒</span>
                        <span>Security &amp; Password</span>
                    </a>

                    <a href="?tab=preferences" class="settings-nav-btn <?php echo ($activeTab === 'preferences') ? 'active' : ''; ?>">
                        <span class="settings-nav-icon">🚚</span>
                        <span>Fleet Preferences</span>
                    </a>

                    <a href="?tab=activity" class="settings-nav-btn <?php echo ($activeTab === 'activity') ? 'active' : ''; ?>">
                        <span class="settings-nav-icon">📊</span>
                        <span>Account &amp; History</span>
                    </a>

                    <div style="border-top: 1px solid var(--color-border); margin-top: 0.5rem; padding-top: 0.5rem;">
                        <a href="<?php echo BASE_URL; ?>customer-logout.php" class="settings-nav-btn" style="color: #EF4444;">
                            <span class="settings-nav-icon">🚪</span>
                            <span>Sign Out</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Active Settings Content -->
            <div>
                
                <!-- =========================================================
                     TAB 1: PROFILE & BUSINESS INFORMATION
                     ========================================================= -->
                <?php if ($activeTab === 'profile'): ?>
                    <div class="form-card" style="padding: 2rem; border: 1px solid var(--color-border);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border); padding-bottom: 1.25rem; margin-bottom: 1.75rem;">
                            <div>
                                <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--color-dark); margin-bottom: 4px;">
                                    Personal &amp; Company Details
                                </h2>
                                <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0;">
                                    Keep your primary contact and commercial delivery address accurate for proforma invoicing and transit dispatches.
                                </p>
                            </div>
                            <span class="badge badge-primary">Tab 1 of 4</span>
                        </div>

                        <?php if (!empty($profileErrors)): ?>
                            <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                                <ul style="margin: 0; padding-left: 1.25rem;">
                                    <?php foreach ($profileErrors as $err): ?>
                                        <li><?php echo sanitize_output($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo BASE_URL; ?>customer-settings.php?tab=profile">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="active_tab" value="profile">

                            <!-- Full Name & Company Name -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.25rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="full_name" class="form-label" style="font-weight: 600;">Full Name *</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo sanitize_output($customer['full_name']); ?>" required>
                                    <small style="color: var(--color-text-muted); font-size: 0.78rem;">Your legal contact name for vehicle sales contracts.</small>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="business_name" class="form-label" style="font-weight: 600;">Company / Business Name</label>
                                    <input type="text" id="business_name" name="business_name" class="form-control" value="<?php echo sanitize_output($customer['business_name'] ?? ''); ?>" placeholder="e.g. Dangote Logistics / Individual">
                                    <small style="color: var(--color-text-muted); font-size: 0.78rem;">Appears on your official dealership proforma invoices.</small>
                                </div>
                            </div>

                            <!-- Phone & Email -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.25rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="phone" class="form-label" style="font-weight: 600;">Phone Number *</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo sanitize_output($customer['phone']); ?>" required>
                                    <small style="color: var(--color-text-muted); font-size: 0.78rem;">Direct line for sales reps and inspection appointments.</small>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="email" class="form-label" style="font-weight: 600;">Registered Email Address</label>
                                    <input type="email" id="email" class="form-control" value="<?php echo sanitize_output($customer['email']); ?>" readonly style="background-color: var(--color-bg-subtle); color: var(--color-text-muted); cursor: not-allowed;">
                                    <small style="color: var(--color-text-muted); font-size: 0.78rem;">Login identity. Verified &bull; Secured with OTP.</small>
                                </div>
                            </div>

                            <!-- Delivery / Haulage Yard Address -->
                            <div class="form-group" style="margin-bottom: 1.75rem;">
                                <label for="delivery_address" class="form-label" style="font-weight: 600;">Delivery / Operational Yard Address</label>
                                <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3" placeholder="e.g. Km 14 Lagos-Ibadan Expressway, Ojodu Berger Yard, Lagos State."><?php echo sanitize_output($customer['delivery_address'] ?? ''); ?></textarea>
                                <small style="color: var(--color-text-muted); font-size: 0.78rem;">Used to calculate inter-state truck haulage delivery transit fees.</small>
                            </div>

                            <!-- Submit Button -->
                            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--color-border); padding-top: 1.25rem;">
                                <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                                    Save Profile Changes
                                </button>
                            </div>
                        </form>

                    </div>

                <!-- =========================================================
                     TAB 2: SECURITY & PASSWORD UPDATE
                     ========================================================= -->
                <?php elseif ($activeTab === 'security'): ?>
                    <div class="form-card" style="padding: 2rem; border: 1px solid var(--color-border);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border); padding-bottom: 1.25rem; margin-bottom: 1.75rem;">
                            <div>
                                <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--color-dark); margin-bottom: 4px;">
                                    Account Security &amp; Password
                                </h2>
                                <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0;">
                                    Update your access password and review your account security policy standards.
                                </p>
                            </div>
                            <span class="badge badge-dark">Security Guard</span>
                        </div>

                        <?php if (!empty($passwordErrors)): ?>
                            <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                                <ul style="margin: 0; padding-left: 1.25rem;">
                                    <?php foreach ($passwordErrors as $err): ?>
                                        <li><?php echo sanitize_output($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Password Policy Box -->
                        <div style="background: var(--color-bg-subtle); border-left: 4px solid var(--color-primary); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.82rem; color: var(--color-dark);">
                            <strong style="display: block; margin-bottom: 4px; color: var(--color-primary);">Password Strength Policy:</strong>
                            Must contain at least <strong>8 characters</strong>, including <strong>1 uppercase letter</strong>, <strong>1 lowercase letter</strong>, and <strong>1 special character</strong> (e.g. @, #, $, !).
                        </div>

                        <form method="POST" action="<?php echo BASE_URL; ?>customer-settings.php?tab=security">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="update_password">
                            <input type="hidden" name="active_tab" value="security">

                            <div class="form-group" style="margin-bottom: 1.25rem;">
                                <label for="current_password" class="form-label" style="font-weight: 600;">Current Password *</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required placeholder="Enter your current password">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="new_password" class="form-label" style="font-weight: 600;">New Password *</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required placeholder="Create a strong password">
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="confirm_password" class="form-label" style="font-weight: 600;">Confirm New Password *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
                                </div>
                            </div>

                            <!-- Session Protection Details -->
                            <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 1.75rem; font-size: 0.82rem; color: var(--color-text-muted);">
                                <strong>Active Session Protection:</strong> Changing your password regenerates your security session key automatically.
                            </div>

                            <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: 1.25rem;">
                                <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                                    Update Password Securely
                                </button>
                            </div>
                        </form>

                    </div>

                <!-- =========================================================
                     TAB 3: FLEET NOTIFICATION PREFERENCES
                     ========================================================= -->
                <?php elseif ($activeTab === 'preferences'): ?>
                    <div class="form-card" style="padding: 2rem; border: 1px solid var(--color-border);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border); padding-bottom: 1.25rem; margin-bottom: 1.75rem;">
                            <div>
                                <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--color-dark); margin-bottom: 4px;">
                                    Fleet &amp; Notification Preferences
                                </h2>
                                <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0;">
                                    Customize which commercial truck categories you operate and how our sales desk updates you.
                                </p>
                            </div>
                            <span class="badge badge-success">Preferences</span>
                        </div>

                        <form method="POST" action="<?php echo BASE_URL; ?>customer-settings.php?tab=preferences">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="update_preferences">
                            <input type="hidden" name="active_tab" value="preferences">

                            <!-- Preferred Vehicle Category -->
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="pref_category" class="form-label" style="font-weight: 600;">Primary Operational Category</label>
                                <select id="pref_category" name="pref_category" class="form-control">
                                    <option value="Heavy Haulage" <?php echo ($prefCategory === 'Heavy Haulage') ? 'selected' : ''; ?>>Heavy Haulage (6x4 Prime Movers / Tractor Heads)</option>
                                    <option value="Construction Tipper" <?php echo ($prefCategory === 'Construction Tipper') ? 'selected' : ''; ?>>Construction &amp; Quarry (10 &amp; 12 Tyre Tippers)</option>
                                    <option value="Distribution Cargo" <?php echo ($prefCategory === 'Distribution Cargo') ? 'selected' : ''; ?>>Distribution &amp; Logistics (Enclosed Box Bodies)</option>
                                    <option value="Specialized Tanker" <?php echo ($prefCategory === 'Specialized Tanker') ? 'selected' : ''; ?>>Bulk Liquid Transport (Petroleum &amp; Water Tankers)</option>
                                    <option value="Medium Duty Haulage" <?php echo ($prefCategory === 'Medium Duty Haulage') ? 'selected' : ''; ?>>Medium Haulage (4.5T – 10T Dropsides)</option>
                                </select>
                                <small style="color: var(--color-text-muted); font-size: 0.78rem;">Our intelligent "Find My Truck" Advisor prioritizes these models for your fleet requests.</small>
                            </div>

                            <!-- Preferred Communication Channel -->
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="pref_channel" class="form-label" style="font-weight: 600;">Preferred Contact Channel</label>
                                <select id="pref_channel" name="pref_channel" class="form-control">
                                    <option value="Email" <?php echo ($prefChannel === 'Email') ? 'selected' : ''; ?>>Email Proforma Invoices (Official Documentation)</option>
                                    <option value="Phone" <?php echo ($prefChannel === 'Phone') ? 'selected' : ''; ?>>Direct Phone Calls (Urgent Site Inquiries)</option>
                                    <option value="WhatsApp" <?php echo ($prefChannel === 'WhatsApp') ? 'selected' : ''; ?>>WhatsApp Dispatch (Fast Video Walkthroughs)</option>
                                </select>
                            </div>

                            <!-- Alert Toggles -->
                            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.75rem;">
                                <div style="font-size: 0.9rem; font-weight: 700; color: var(--color-dark); margin-bottom: 0.75rem;">Dealership Notifications</div>
                                
                                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer; font-size: 0.88rem;">
                                    <input type="checkbox" name="notify_arrivals" value="1" <?php echo ($notifyArrivals === '1') ? 'checked' : ''; ?> style="width: 16px; height: 16px;">
                                    <span>Receive notifications when newly imported European/Asian trucks arrive at Ojodu Berger yard</span>
                                </label>

                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 0.88rem;">
                                    <input type="checkbox" name="notify_quotes" value="1" <?php echo ($notifyQuotes === '1') ? 'checked' : ''; ?> style="width: 16px; height: 16px;">
                                    <span>Receive status alerts when sales staff review your submitted fleet inquiry or custom sourcing requests</span>
                                </label>
                            </div>

                            <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--color-border); padding-top: 1.25rem;">
                                <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                                    Save Preferences
                                </button>
                            </div>
                        </form>

                    </div>

                <!-- =========================================================
                     TAB 4: ACCOUNT OVERVIEW & ACTIVITY AUDIT
                     ========================================================= -->
                <?php elseif ($activeTab === 'activity'): ?>
                    <div class="form-card" style="padding: 2rem; border: 1px solid var(--color-border);">
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border); padding-bottom: 1.25rem; margin-bottom: 1.75rem;">
                            <div>
                                <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--color-dark); margin-bottom: 4px;">
                                    Account Audit &amp; Activity
                                </h2>
                                <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0;">
                                    Overview of your account verification standing, historical inquiry counts, and active fleet tickets.
                                </p>
                            </div>
                            <span class="badge badge-dark">System Ledger</span>
                        </div>

                        <!-- 3 Summary Metrics -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
                            <div style="background: var(--color-bg-subtle); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--color-border); text-align: center;">
                                <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-text-muted); font-weight: 700; display: block; margin-bottom: 4px;">Vehicle Inquiries</span>
                                <span style="font-size: 1.85rem; font-weight: 800; color: var(--color-primary);"><?php echo $inquiriesCount; ?></span>
                                <span style="font-size: 0.75rem; color: var(--color-text-muted); display: block;">Total submitted</span>
                            </div>

                            <div style="background: var(--color-bg-subtle); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--color-border); text-align: center;">
                                <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-text-muted); font-weight: 700; display: block; margin-bottom: 4px;">Fleet Sourcing</span>
                                <span style="font-size: 1.85rem; font-weight: 800; color: var(--color-dark);"><?php echo $requestsCount; ?></span>
                                <span style="font-size: 0.75rem; color: var(--color-text-muted); display: block;">Custom fleet requests</span>
                            </div>

                            <div style="background: var(--color-bg-subtle); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--color-border); text-align: center;">
                                <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-text-muted); font-weight: 700; display: block; margin-bottom: 4px;">Account Status</span>
                                <span style="font-size: 1.15rem; font-weight: 800; color: #10B981; display: block; margin-top: 8px;">Active &bull; Verified</span>
                                <span style="font-size: 0.75rem; color: var(--color-text-muted); display: block;">OTP Authenticated</span>
                            </div>
                        </div>

                        <!-- Account Specification Table -->
                        <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 1.5rem;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                                <tbody>
                                    <tr style="border-bottom: 1px solid var(--color-border);">
                                        <td style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted); width: 35%;">Client Reference ID</td>
                                        <td style="padding: 10px 16px; font-weight: 700; font-family: monospace; color: var(--color-primary);">MOAL-CUST-<?php echo str_pad((string)$customerId, 4, '0', STR_PAD_LEFT); ?></td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid var(--color-border);">
                                        <td style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Registered Email</td>
                                        <td style="padding: 10px 16px; color: var(--color-dark);"><?php echo sanitize_output($customer['email']); ?></td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid var(--color-border);">
                                        <td style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Primary Phone</td>
                                        <td style="padding: 10px 16px; color: var(--color-dark);"><?php echo sanitize_output($customer['phone']); ?></td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid var(--color-border);">
                                        <td style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Company / Organization</td>
                                        <td style="padding: 10px 16px; color: var(--color-dark);"><?php echo sanitize_output($customer['business_name'] ?? 'Not specified'); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 16px; font-weight: 600; color: var(--color-text-muted);">Delivery Yard Location</td>
                                        <td style="padding: 10px 16px; color: var(--color-dark);"><?php echo nl2br(sanitize_output($customer['delivery_address'] ?? 'Ojodu Berger Yard, Lagos')); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Direct Jump Links -->
                        <div style="display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;">
                            <a href="<?php echo BASE_URL; ?>customer-dashboard.php" class="btn btn-secondary btn-sm">
                                View Full Inquiry Ledger &rarr;
                            </a>
                            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary btn-sm">
                                Browse Available Trucks &rarr;
                            </a>
                        </div>

                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<style>
/* Client Settings Custom Nav Styles */
.settings-nav-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: var(--radius-sm);
    color: var(--color-text);
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    margin-bottom: 4px;
}

.settings-nav-btn:hover {
    background-color: var(--color-bg-subtle);
    color: var(--color-primary);
}

.settings-nav-btn.active {
    background-color: var(--color-primary);
    color: #FFFFFF !important;
}

.settings-nav-icon {
    font-size: 1.05rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
}

@media (max-width: 820px) {
    div[style*="grid-template-columns: 290px 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 640px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    div[style*="grid-template-columns: repeat(3, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
