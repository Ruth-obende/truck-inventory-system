<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Authentication Architecture (Stage 1 OTP)
 * =============================================================================
 * Secure client session handling, client-defined password registration,
 * 6-digit email OTP verification, password reset, and protected client routes.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

/**
 * Checks if a customer is currently authenticated in the active session.
 * 
 * @return bool
 */
function is_customer_logged_in(): bool {
    return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
}

/**
 * Retrieves the profile details of the currently logged-in customer.
 * 
 * @return array|null
 */
function get_logged_in_customer(): ?array {
    static $cachedCustomer = null;

    if (!is_customer_logged_in()) {
        return null;
    }

    if ($cachedCustomer !== null) {
        return $cachedCustomer;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, full_name, phone, email, delivery_address, business_name, status, is_verified, created_at FROM customers WHERE id = :id AND status = "active" LIMIT 1');
        $stmt->execute([':id' => $_SESSION['customer_id']]);
        $customer = $stmt->fetch();

        if ($customer) {
            $cachedCustomer = $customer;
            return $customer;
        } else {
            logout_customer();
            return null;
        }
    } catch (Exception $e) {
        error_log('[Customer Auth Error] ' . $e->getMessage());
        return null;
    }
}

/**
 * Enforces customer authentication on protected client pages (Inventory, Specs, Dashboard).
 * Redirects unauthenticated visitors to login page.
 * 
 * @param string|null $redirectUrl Optional return URL after login
 * @return void
 */
function require_customer_login(?string $redirectUrl = null): void {
    if (!is_customer_logged_in()) {
        $url = $redirectUrl ?? $_SERVER['REQUEST_URI'];
        set_flash_message('info', 'Sign in to view our full truck inventory.');
        header('Location: ' . BASE_URL . 'customer-login.php?redirect=' . urlencode($url));
        exit;
    }
}

/**
 * Generates a secure, 6-digit numeric OTP code.
 * 
 * @return string
 */
function generate_otp(): string {
    return sprintf('%06d', random_int(100000, 999999));
}

/**
 * Registers a new customer account, generates a 6-digit OTP, and dispatches the OTP email.
 * 
 * @param array $data Form submission data
 * @return array Result array with success boolean and error messages / otp info
 */
function register_customer(array $data): array {
    $errors = [];

    $fullName        = trim($data['full_name'] ?? '');
    $email           = strtolower(trim($data['email'] ?? ''));
    $phone           = trim($data['phone'] ?? '');
    $password        = $data['password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    // 1. Validation
    if (empty($fullName) || strlen($fullName) < 3) {
        $errors['full_name'] = 'Please enter your full name (minimum 3 characters).';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please provide a valid email address.';
    }

    if (empty($phone) || strlen($phone) < 7) {
        $errors['phone'] = 'Please enter a valid phone number (e.g. 0803 000 0000).';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } else {
        $pwError = validate_password_strength($password);
        if ($pwError !== null) {
            $errors['password'] = $pwError;
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    try {
        $db = getDB();

        // 2. Check if email already registered
        $stmtCheck = $db->prepare('SELECT id, is_verified FROM customers WHERE email = :email LIMIT 1');
        $stmtCheck->execute([':email' => $email]);
        $existing = $stmtCheck->fetch();

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $otpCode = generate_otp();
        $otpExpires = date('Y-m-d H:i:s', time() + 900); // 15 minutes
        $verificationToken = bin2hex(random_bytes(32));
        $verificationExpires = date('Y-m-d H:i:s', time() + 86400); // 24 hours
        $now = date('Y-m-d H:i:s');
        $isRenewal = false;

        if ($existing) {
            if ((int)$existing['is_verified'] === 1) {
                return [
                    'success' => false,
                    'errors' => ['email' => 'An account with this email address already exists. Please sign in or reset your password.']
                ];
            } else {
                // Account previously attempted but unverified - renew with fresh password & OTP
                $isRenewal = true;
                $customerId = (int)$existing['id'];
                $stmtUpdate = $db->prepare('
                    UPDATE customers 
                    SET full_name = :full_name,
                        phone = :phone,
                        password_hash = :password_hash,
                        otp_code = :otp_code,
                        otp_expires_at = :otp_expires,
                        otp_attempts = 0,
                        last_otp_sent_at = :sent_at,
                        verification_token = :ver_token,
                        verification_expires_at = :ver_expires
                    WHERE id = :id
                ');
                $stmtUpdate->execute([
                    ':full_name'     => $fullName,
                    ':phone'         => $phone,
                    ':password_hash' => $passwordHash,
                    ':otp_code'      => $otpCode,
                    ':otp_expires'   => $otpExpires,
                    ':sent_at'       => $now,
                    ':ver_token'     => $verificationToken,
                    ':ver_expires'   => $verificationExpires,
                    ':id'            => $customerId
                ]);
            }
        } else {
            // New Registration
            $stmtInsert = $db->prepare('
                INSERT INTO customers (
                    full_name, phone, email, password_hash, status, is_verified, 
                    otp_code, otp_expires_at, otp_attempts, last_otp_sent_at,
                    verification_token, verification_expires_at
                ) VALUES (
                    :full_name, :phone, :email, :password_hash, "active", 0, 
                    :otp_code, :otp_expires, 0, :sent_at,
                    :ver_token, :ver_expires
                )
            ');

            $stmtInsert->execute([
                ':full_name'     => $fullName,
                ':phone'         => $phone,
                ':email'         => $email,
                ':password_hash' => $passwordHash,
                ':otp_code'      => $otpCode,
                ':otp_expires'   => $otpExpires,
                ':sent_at'       => $now,
                ':ver_token'     => $verificationToken,
                ':ver_expires'   => $verificationExpires
            ]);

            $customerId = (int)$db->lastInsertId();
        }

        // 4. Dispatch Email with Direct Link AND 6-digit OTP
        $verifyLink = BASE_URL . 'verify-email.php?token=' . $verificationToken . '&email=' . urlencode($email);

        $emailHtml = '
            <h2 style="color: #1F2421; font-size: 22px; margin-top: 0; font-weight: 700;">Verify Your Email Address</h2>
            <p>Dear <strong>' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
            <p>Thank you for creating an account with <strong>' . APP_NAME . '</strong>. Please verify your email address to unlock our commercial truck inventory, technical specifications, and fleet quote requests.</p>
            
            <div style="text-align: center; margin: 28px 0 20px 0;">
                <a href="' . $verifyLink . '" style="background-color: #D9825B; color: #FFFFFF; padding: 14px 28px; text-decoration: none; font-weight: 700; border-radius: 6px; display: inline-block; font-size: 15px; letter-spacing: 0.3px;">
                    Verify Email &amp; View Inventory &rarr;
                </a>
            </div>

            <p style="text-align: center; color: #6B6B67; font-size: 13px; margin: 16px 0 8px 0;">Or enter this 6-digit one-time code on the verification screen:</p>
            <div style="background-color: #FAF8F4; border: 2px dashed #D9825B; border-radius: 8px; padding: 14px; text-align: center; margin: 0 auto 20px auto; max-width: 260px;">
                <span style="font-size: 28px; font-weight: 800; letter-spacing: 6px; color: #1F2421; font-family: monospace;">' . $otpCode . '</span>
            </div>
            
            <p style="font-size: 13px; color: #6B6B67; line-height: 1.5;">This verification code is valid for 15 minutes, and the link remains active for 24 hours.</p>
            <p style="font-size: 12px; color: #94a3b8;">If you did not request this verification, you can safely ignore this email.</p>
        ';

        $emailSent = send_system_email(
            $email, 
            $fullName, 
            'Verify Your Email - ' . APP_NAME, 
            $emailHtml, 
            "Verify your " . APP_NAME . " account by visiting: $verifyLink\nOr use this 6-digit code: $otpCode (valid 15 minutes)."
        );

        if (!$emailSent) {
            // If new registration failed email dispatch, remove unverified row so user isn't stuck
            if (!$isRenewal) {
                $stmtDel = $db->prepare('DELETE FROM customers WHERE id = :id AND is_verified = 0');
                $stmtDel->execute([':id' => $customerId]);
            }

            $mailErr = get_last_mail_error();
            return [
                'success' => false,
                'errors'  => [
                    'email_delivery' => 'Could not deliver verification email to ' . htmlspecialchars($email) . '. ' . (!empty($mailErr) ? $mailErr : 'Please check SMTP settings in includes/config.php.')
                ]
            ];
        }

        $_SESSION['pending_verification_email'] = $email;

        return [
            'success'     => true,
            'customer_id' => $customerId,
            'email'       => $email,
            'otp'         => $otpCode,
            'token'       => $verificationToken
        ];

    } catch (Exception $e) {
        error_log('[Customer Registration Error] ' . $e->getMessage());
        return ['success' => false, 'errors' => ['general' => 'An unexpected error occurred during registration. Please try again.']];
    }
}

/**
 * Verifies a 6-digit OTP code and activates the client account.
 * 
 * @param string $email
 * @param string $otp
 * @return array
 */
function verify_customer_otp(string $email, string $otp): array {
    $email = strtolower(trim($email));
    $otp   = trim($otp);

    if (empty($email) || empty($otp)) {
        return ['success' => false, 'error' => 'Please enter the 6-digit verification code.'];
    }

    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        return ['success' => false, 'error' => 'The verification code must be exactly 6 digits.'];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, full_name, phone, email, delivery_address, business_name, is_verified, otp_code, otp_expires_at, otp_attempts FROM customers WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return ['success' => false, 'error' => 'No account found with this email address.'];
        }

        if ((int)$customer['is_verified'] === 1) {
            return ['success' => true, 'already_verified' => true, 'customer' => $customer];
        }

        // Check attempts limit (anti-brute-force)
        if ((int)$customer['otp_attempts'] >= 5) {
            return ['success' => false, 'error' => 'Too many failed attempts. Please request a new verification code.'];
        }

        // Check expiration
        if (!empty($customer['otp_expires_at']) && strtotime($customer['otp_expires_at']) < time()) {
            return ['success' => false, 'error' => 'Your verification code has expired. Please request a new code.'];
        }

        // Verify OTP
        if ($customer['otp_code'] !== $otp) {
            // Increment failed attempts
            $db->prepare('UPDATE customers SET otp_attempts = otp_attempts + 1 WHERE id = :id')->execute([':id' => $customer['id']]);
            return ['success' => false, 'error' => 'That verification code is incorrect. Please check your email and try again.'];
        }

        // OTP is correct -> Activate account & clear OTP and tokens
        $stmtActivate = $db->prepare('
            UPDATE customers 
            SET is_verified = 1, 
                otp_code = NULL, 
                otp_expires_at = NULL, 
                otp_attempts = 0, 
                verification_token = NULL,
                verification_expires_at = NULL,
                status = "active" 
            WHERE id = :id
        ');
        $stmtActivate->execute([':id' => $customer['id']]);

        // Automatically log in the customer
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['customer_id']       = (int)$customer['id'];
        $_SESSION['customer_name']     = $customer['full_name'];
        $_SESSION['customer_email']    = $customer['email'];
        $_SESSION['customer_phone']    = $customer['phone'];
        $_SESSION['customer_address']  = $customer['delivery_address'];
        $_SESSION['customer_business'] = $customer['business_name'];

        unset($_SESSION['pending_verification_email']);

        return ['success' => true, 'customer' => $customer];

    } catch (Exception $e) {
        error_log('[OTP Verification Error] ' . $e->getMessage());
        return ['success' => false, 'error' => 'An error occurred during verification. Please try again.'];
    }
}

/**
 * Verifies a customer's email via a 64-character one-click verification link token.
 * 
 * @param string $email
 * @param string $token
 * @return array
 */
function verify_customer_token(string $email, string $token): array {
    $email = strtolower(trim($email));
    $token = trim($token);

    if (empty($email) || empty($token) || strlen($token) !== 64) {
        return ['success' => false, 'error' => 'Invalid or malformed verification link.'];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('
            SELECT id, full_name, phone, email, delivery_address, business_name, is_verified, verification_expires_at 
            FROM customers 
            WHERE email = :email AND verification_token = :token 
            LIMIT 1
        ');
        $stmt->execute([':email' => $email, ':token' => $token]);
        $customer = $stmt->fetch();

        if (!$customer) {
            // Check if already verified
            $stmtCheck = $db->prepare('SELECT id, full_name, email, is_verified FROM customers WHERE email = :email LIMIT 1');
            $stmtCheck->execute([':email' => $email]);
            $existing = $stmtCheck->fetch();
            if ($existing && (int)$existing['is_verified'] === 1) {
                return ['success' => true, 'already_verified' => true, 'customer' => $existing];
            }
            return ['success' => false, 'error' => 'This verification link is invalid or has already been used.'];
        }

        if ((int)$customer['is_verified'] === 1) {
            return ['success' => true, 'already_verified' => true, 'customer' => $customer];
        }

        // Check expiry
        if (!empty($customer['verification_expires_at']) && strtotime($customer['verification_expires_at']) < time()) {
            return ['success' => false, 'error' => 'This verification link has expired. Please request a new verification code.'];
        }

        // Activate account & clear tokens
        $stmtActivate = $db->prepare('
            UPDATE customers 
            SET is_verified = 1, 
                verification_token = NULL, 
                verification_expires_at = NULL, 
                otp_code = NULL, 
                otp_expires_at = NULL, 
                otp_attempts = 0, 
                status = "active" 
            WHERE id = :id
        ');
        $stmtActivate->execute([':id' => $customer['id']]);

        // Automatically log in the customer
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['customer_id']       = (int)$customer['id'];
        $_SESSION['customer_name']     = $customer['full_name'];
        $_SESSION['customer_email']    = $customer['email'];
        $_SESSION['customer_phone']    = $customer['phone'];
        $_SESSION['customer_address']  = $customer['delivery_address'];
        $_SESSION['customer_business'] = $customer['business_name'];

        unset($_SESSION['pending_verification_email']);

        return ['success' => true, 'customer' => $customer];

    } catch (Exception $e) {
        error_log('[Token Verification Error] ' . $e->getMessage());
        return ['success' => false, 'error' => 'An error occurred during verification. Please try again.'];
    }
}

/**
 * Resends a fresh 6-digit OTP code and verification link with cooldown protection.
 * 
 * @param string $email
 * @return array
 */
function resend_customer_otp(string $email): array {
    $email = strtolower(trim($email));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please provide a valid email address.'];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, full_name, email, is_verified, last_otp_sent_at FROM customers WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return ['success' => true, 'message' => 'If an account exists with this email, a new verification email has been sent.'];
        }

        if ((int)$customer['is_verified'] === 1) {
            return ['success' => true, 'already_verified' => true, 'message' => 'Your email is already verified. You can sign in directly.'];
        }

        // Cooldown check (60 seconds)
        if (!empty($customer['last_otp_sent_at'])) {
            $secondsSinceLast = time() - strtotime($customer['last_otp_sent_at']);
            if ($secondsSinceLast < 60) {
                $wait = 60 - $secondsSinceLast;
                return ['success' => false, 'cooldown' => true, 'wait_seconds' => $wait, 'error' => "Please wait {$wait} seconds before requesting another code."];
            }
        }

        $newOtp = generate_otp();
        $newExpires = date('Y-m-d H:i:s', time() + 900); // 15 mins
        $newVerificationToken = bin2hex(random_bytes(32));
        $newVerExpires = date('Y-m-d H:i:s', time() + 86400); // 24 hours
        $now = date('Y-m-d H:i:s');

        $stmtUpdate = $db->prepare('
            UPDATE customers 
            SET otp_code = :otp, 
                otp_expires_at = :expires, 
                verification_token = :token,
                verification_expires_at = :ver_expires,
                otp_attempts = 0, 
                last_otp_sent_at = :sent_at 
            WHERE id = :id
        ');
        $stmtUpdate->execute([
            ':otp'         => $newOtp,
            ':expires'     => $newExpires,
            ':token'       => $newVerificationToken,
            ':ver_expires' => $newVerExpires,
            ':sent_at'     => $now,
            ':id'          => $customer['id']
        ]);

        $verifyLink = BASE_URL . 'verify-email.php?token=' . $newVerificationToken . '&email=' . urlencode($customer['email']);

        $emailHtml = '
            <h2 style="color: #1F2421; font-size: 22px; margin-top: 0; font-weight: 700;">New Verification Code &amp; Link</h2>
            <p>Dear <strong>' . htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>
            <p>You requested a new verification for your <strong>' . APP_NAME . '</strong> account. Click the button below to verify your email and start browsing our commercial truck inventory:</p>
            
            <div style="text-align: center; margin: 28px 0 20px 0;">
                <a href="' . $verifyLink . '" style="background-color: #D9825B; color: #FFFFFF; padding: 14px 28px; text-decoration: none; font-weight: 700; border-radius: 6px; display: inline-block; font-size: 15px; letter-spacing: 0.3px;">
                    Verify Email &amp; View Inventory &rarr;
                </a>
            </div>

            <p style="text-align: center; color: #6B6B67; font-size: 13px; margin: 16px 0 8px 0;">Or enter this 6-digit one-time code on the verification screen:</p>
            <div style="background-color: #FAF8F4; border: 2px dashed #D9825B; border-radius: 8px; padding: 14px; text-align: center; margin: 0 auto 20px auto; max-width: 260px;">
                <span style="font-size: 28px; font-weight: 800; letter-spacing: 6px; color: #1F2421; font-family: monospace;">' . $newOtp . '</span>
            </div>
            
            <p style="font-size: 13px; color: #6B6B67;">This code is valid for 15 minutes, and the link remains active for 24 hours.</p>
        ';

        $emailSent = send_system_email(
            $customer['email'], 
            $customer['full_name'], 
            'Your New Verification Link & Code - ' . APP_NAME, 
            $emailHtml, 
            "Verify your " . APP_NAME . " account by visiting: $verifyLink\nOr use this 6-digit code: $newOtp (valid 15 minutes)."
        );

        if (!$emailSent) {
            $mailErr = get_last_mail_error();
            return [
                'success' => false,
                'error'   => 'Could not deliver verification email to ' . htmlspecialchars($customer['email']) . '. ' . (!empty($mailErr) ? $mailErr : 'Please check SMTP settings in includes/config.php.')
            ];
        }

        return [
            'success' => true,
            'message' => 'A new verification link and 6-digit code have been sent to your email.',
            'otp'     => $newOtp,
            'token'   => $newVerificationToken
        ];

    } catch (Exception $e) {
        error_log('[Resend OTP Error] ' . $e->getMessage());
        return ['success' => false, 'error' => 'An error occurred while resending the code. Please try again.'];
    }
}

/**
 * Authenticates a customer with email and password.
 * Checks verification status and blocks unverified access.
 * 
 * @param string $email
 * @param string $password
 * @return array
 */
function login_customer(string $email, string $password): array {
    $email = strtolower(trim($email));

    if (empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Please enter both your email address and password.'];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('
            SELECT id, full_name, phone, email, delivery_address, business_name, password_hash, status, is_verified 
            FROM customers 
            WHERE email = :email 
            LIMIT 1
        ');
        $stmt->execute([':email' => $email]);
        $customer = $stmt->fetch();

        // 1. Password check
        if (!$customer || !password_verify($password, $customer['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email address or password.'];
        }

        // 2. Email verification check
        if (empty($customer['is_verified']) || (int)$customer['is_verified'] !== 1) {
            $_SESSION['pending_verification_email'] = $customer['email'];
            return [
                'success'    => false,
                'unverified' => true,
                'email'      => $customer['email'],
                'error'      => 'Please verify your email before continuing.'
            ];
        }

        // 3. Status check
        if ($customer['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account is currently inactive or suspended. Please contact customer support.'];
        }

        // 4. Session handling
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['customer_id']       = (int)$customer['id'];
        $_SESSION['customer_name']     = $customer['full_name'];
        $_SESSION['customer_email']    = $customer['email'];
        $_SESSION['customer_phone']    = $customer['phone'];
        $_SESSION['customer_address']  = $customer['delivery_address'];
        $_SESSION['customer_business'] = $customer['business_name'];

        return ['success' => true, 'customer' => $customer];

    } catch (Exception $e) {
        error_log('[Customer Login Error] ' . $e->getMessage());
        return ['success' => false, 'error' => 'Unable to authenticate at this time. Please try again.'];
    }
}

/**
 * Initiates password reset request.
 */
function request_password_reset(string $email): array {
    $email = strtolower(trim($email));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please provide a valid email address.'];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, full_name, email FROM customers WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $customer = $stmt->fetch();

        if ($customer) {
            $resetToken = bin2hex(random_bytes(32));
            $resetExpires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $stmtUpdate = $db->prepare('UPDATE customers SET reset_token = :token, reset_token_expiry = :expires WHERE id = :id');
            $stmtUpdate->execute([':token' => $resetToken, ':expires' => $resetExpires, ':id' => $customer['id']]);

            $resetLink = BASE_URL . 'reset-password.php?token=' . urlencode($resetToken);
            $emailHtml = '
                <h2 style="color: #1F2421; font-size: 20px; margin-top: 0;">Reset Your Password</h2>
                <p>Dear <strong>' . htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                <p>We received a request to reset your password for <strong>' . APP_NAME . '</strong>.</p>
                <div style="text-align: center; margin: 25px 0;">
                    <a href="' . $resetLink . '" style="background-color: #D9825B; color: #ffffff; padding: 12px 24px; text-decoration: none; font-weight: 700; border-radius: 6px; display: inline-block;">
                        Reset Password &rarr;
                    </a>
                </div>
                <p style="font-size: 13px; color: #6B6B67;">Link expires in 1 hour.</p>
            ';

            send_system_email($customer['email'], $customer['full_name'], 'Password Reset - ' . APP_NAME, $emailHtml);

            return ['success' => true, 'message' => 'If an account exists with this email, you will receive reset instructions shortly.', 'reset_token' => $resetToken];
        }

        return ['success' => true, 'message' => 'If an account exists with this email, you will receive reset instructions shortly.'];

    } catch (Exception $e) {
        error_log('[Password Reset Error] ' . $e->getMessage());
        return ['success' => false, 'error' => 'An error occurred while processing your request.'];
    }
}

/**
 * Validates password reset token.
 */
function verify_password_reset_token(string $token): array {
    $token = trim($token);
    if (empty($token) || strlen($token) !== 64) {
        return ['valid' => false, 'error' => 'Invalid or malformed reset link.'];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, full_name, email FROM customers WHERE reset_token = :token AND reset_token_expiry >= NOW() LIMIT 1');
        $stmt->execute([':token' => $token]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return ['valid' => false, 'error' => 'This password reset link is invalid or has expired.'];
        }

        return ['valid' => true, 'customer' => $customer];

    } catch (Exception $e) {
        error_log('[Verify Reset Token Error] ' . $e->getMessage());
        return ['valid' => false, 'error' => 'Unable to validate reset link.'];
    }
}

/**
 * Resets customer password.
 */
function reset_customer_password(string $token, string $newPassword, string $confirmPassword): array {
    $val = verify_password_reset_token($token);
    if (!$val['valid']) {
        return ['success' => false, 'error' => $val['error']];
    }

    if (empty($newPassword)) {
        return ['success' => false, 'error' => 'New password is required.'];
    }

    $pwError = validate_password_strength($newPassword);
    if ($pwError !== null) {
        return ['success' => false, 'error' => $pwError];
    }

    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'error' => 'New passwords do not match.'];
    }

    try {
        $db = getDB();
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $db->prepare('UPDATE customers SET password_hash = :hash, reset_token = NULL, reset_token_expiry = NULL, is_verified = 1 WHERE id = :id');
        $stmt->execute([':hash' => $newHash, ':id' => $val['customer']['id']]);

        return ['success' => true, 'message' => 'Your password has been updated. Please sign in with your new password.'];

    } catch (Exception $e) {
        error_log('[Reset Password Save Error] ' . $e->getMessage());
        return ['success' => false, 'error' => 'An error occurred while resetting your password.'];
    }
}

/**
 * Logs out customer.
 */
function logout_customer(): void {
    unset(
        $_SESSION['customer_id'],
        $_SESSION['customer_name'],
        $_SESSION['customer_email'],
        $_SESSION['customer_phone'],
        $_SESSION['customer_address'],
        $_SESSION['customer_business'],
        $_SESSION['pending_verification_email']
    );
}

/**
 * Generates unique fleet request code.
 */
function generate_request_code(): string {
    return 'REQ-' . date('Y') . '-' . mt_rand(1000, 9999) . chr(mt_rand(65, 90));
}