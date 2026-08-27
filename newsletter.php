<?php
/**
 * =============================================================================
 * Moal General Suppliers - Newsletter Subscription Handler
 * =============================================================================
 * Securely captures and persists customer newsletter email subscriptions.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['newsletter_email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('error', 'Please provide a valid email address for the newsletter.');
    } else {
        $db = getDB();
        try {
            $stmt = $db->prepare('
                INSERT INTO newsletter_subscribers (email) 
                VALUES (:email) 
                ON DUPLICATE KEY UPDATE email = VALUES(email)
            ');
            $stmt->execute([':email' => $email]);
            set_flash_message('success', 'Thank you for subscribing! You will receive updates on new truck arrivals.');
        } catch (PDOException $e) {
            error_log('[Newsletter Subscription Error] ' . $e->getMessage());
            set_flash_message('error', 'Unable to process subscription at this time. Please try again.');
        }
    }
}

redirect($referer);
