<?php
/**
 * =============================================================================
 * Moal General Suppliers - System Mailer Utility
 * =============================================================================
 * Sends real transaction emails via authenticated SMTP relay,
 * tracks status accurately without fake success returns,
 * and maintains an audit trail in logs/emails.log.
 */

require_once __DIR__ . '/config.php';

$GLOBALS['last_mail_error'] = '';

/**
 * Returns the last recorded mailer error message.
 *
 * @return string
 */
function get_last_mail_error(): string {
    return $GLOBALS['last_mail_error'] ?? '';
}

/**
 * Dispatches an HTML email with dealership branding.
 *
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient full name
 * @param string $subject Email subject line
 * @param string $htmlContent Main HTML message body
 * @param string $plainText Optional fallback plain text
 * @return bool True if accepted for outbound delivery by SMTP server, false otherwise
 */
function send_system_email(string $toEmail, string $toName, string $subject, string $htmlContent, string $plainText = ''): bool {
    global $last_mail_error;
    $last_mail_error = '';

    $toEmail = trim($toEmail);
    $toName  = trim($toName);

    // 1. Audit Log File (always record intention and payload)
    $logsDir = ROOT_PATH . 'logs';
    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0777, true);
    }
    $logFile = $logsDir . DIRECTORY_SEPARATOR . 'emails.log';
    $logEntry = sprintf(
        "[%s] ATTEMPT TO: %s <%s> | SUBJECT: %s\n------------------------------------------------------------\n%s\n============================================================\n\n",
        date('Y-m-d H:i:s'),
        $toName,
        $toEmail,
        $subject,
        !empty($plainText) ? $plainText : strip_tags($htmlContent)
    );
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    // 2. Build clean HTML email body
    $fullHtml = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #FAF8F4; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #2D312E;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #FAF8F4; padding: 30px 15px;">
            <tr>
                <td align="center">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 540px; background-color: #FFFFFF; border-radius: 8px; border: 1px solid #E5DFD5; overflow: hidden;">
                        <tr>
                            <td style="background-color: #1F2421; padding: 22px; text-align: center; border-bottom: 3px solid #D9825B;">
                                <div style="color: #FFFFFF; font-size: 17px; font-weight: 700; letter-spacing: 0.5px;">
                                    ' . APP_NAME . '
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 28px 24px; line-height: 1.6; font-size: 15px; color: #2D312E;">
                                ' . $htmlContent . '
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: #FAF8F4; padding: 18px 24px; text-align: center; font-size: 12px; color: #6B6B67; border-top: 1px solid #E5DFD5;">
                                <div>' . APP_NAME . ' &bull; ' . CONTACT_ADDRESS . '</div>
                                <div style="margin-top: 4px;">Tel: ' . CONTACT_PHONE_1 . ' &bull; ' . CONTACT_EMAIL . '</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    // 3. Outbound SMTP Delivery
    if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
        if (!defined('SMTP_HOST') || empty(SMTP_HOST) || !defined('SMTP_USER') || empty(SMTP_USER) || !defined('SMTP_PASS') || empty(SMTP_PASS)) {
            $last_mail_error = 'SMTP credentials are incomplete. Please verify SMTP_USER and SMTP_PASS in includes/config.php.';
            error_log('[Mailer Error] ' . $last_mail_error);
            return false;
        }

        $smtpSuccess = smtp_socket_send(
            SMTP_HOST,
            (int)SMTP_PORT,
            SMTP_USER,
            SMTP_PASS,
            SMTP_ENCRYPTION,
            MAIL_FROM_ADDRESS,
            MAIL_FROM_NAME,
            $toEmail,
            $toName,
            $subject,
            $fullHtml,
            $plainText
        );

        if ($smtpSuccess) {
            @file_put_contents($logFile, sprintf("[%s] RESULT: SUCCESS (Delivered to SMTP server)\n\n", date('Y-m-d H:i:s')), FILE_APPEND | LOCK_EX);
            return true;
        } else {
            @file_put_contents($logFile, sprintf("[%s] RESULT: FAILED - %s\n\n", date('Y-m-d H:i:s'), $last_mail_error), FILE_APPEND | LOCK_EX);
            return false;
        }
    }

    // 4. If SMTP is NOT enabled, report failure honestly to avoid deceptive "Email sent" messages
    $last_mail_error = 'Outbound email delivery is disabled. Set SMTP_ENABLED to true and configure valid SMTP credentials in includes/config.php to deliver to external inboxes.';
    error_log('[Mailer Error] ' . $last_mail_error);
    @file_put_contents($logFile, sprintf("[%s] RESULT: BLOCKED - %s\n\n", date('Y-m-d H:i:s'), $last_mail_error), FILE_APPEND | LOCK_EX);
    return false;
}

/**
 * Socket-based SMTP transport supporting STARTTLS and SSL authentication.
 */
function smtp_socket_send(string $host, int $port, string $user, string $pass, string $encryption, string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, string $htmlBody, string $plainText = ''): bool {
    global $last_mail_error;
    $timeout = 15;
    $targetHost = ($encryption === 'ssl') ? 'ssl://' . $host : $host;
    
    $socket = @fsockopen($targetHost, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        $last_mail_error = "Could not connect to SMTP server ($host:$port): $errstr ($errno)";
        error_log("[SMTP Error] $last_mail_error");
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $readCode = function($s, $expected, &$rawResponse = null) {
        $response = '';
        while ($line = fgets($s, 515)) {
            $response .= $line;
            if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
                break;
            }
        }
        $rawResponse = trim($response);
        return ((int)substr($response, 0, 3) === $expected);
    };

    $raw = '';
    if (!$readCode($socket, 220, $raw)) {
        $last_mail_error = "SMTP Banner Error: $raw";
        error_log("[SMTP Error] $last_mail_error");
        fclose($socket);
        return false;
    }

    $hostname = gethostname() ?: 'localhost';
    fputs($socket, "EHLO $hostname\r\n");
    if (!$readCode($socket, 250, $raw)) {
        fputs($socket, "HELO $hostname\r\n");
        if (!$readCode($socket, 250, $raw)) {
            $last_mail_error = "EHLO/HELO rejected: $raw";
            error_log("[SMTP Error] $last_mail_error");
            fclose($socket);
            return false;
        }
    }

    if ($encryption === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        if (!$readCode($socket, 220, $raw)) {
            $last_mail_error = "STARTTLS rejected: $raw";
            error_log("[SMTP Error] $last_mail_error");
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $last_mail_error = "TLS handshake failed with $host";
            error_log("[SMTP Error] $last_mail_error");
            fclose($socket);
            return false;
        }
        fputs($socket, "EHLO $hostname\r\n");
        if (!$readCode($socket, 250, $raw)) {
            $last_mail_error = "Post-TLS EHLO rejected: $raw";
            error_log("[SMTP Error] $last_mail_error");
            fclose($socket);
            return false;
        }
    }

    // Authenticate
    fputs($socket, "AUTH LOGIN\r\n");
    if (!$readCode($socket, 334, $raw)) {
        $last_mail_error = "AUTH LOGIN rejected: $raw";
        error_log("[SMTP Error] $last_mail_error");
        fclose($socket);
        return false;
    }

    fputs($socket, base64_encode($user) . "\r\n");
    if (!$readCode($socket, 334, $raw)) {
        $last_mail_error = "SMTP Username rejected: $raw";
        error_log("[SMTP Error] $last_mail_error");
        fclose($socket);
        return false;
    }

    fputs($socket, base64_encode($pass) . "\r\n");
    if (!$readCode($socket, 235, $raw)) {
        $last_mail_error = "SMTP Authentication failed: $raw";
        error_log("[SMTP Error] $last_mail_error");
        fclose($socket);
        return false;
    }

    // Envelope
    fputs($socket, "MAIL FROM: <$fromEmail>\r\n");
    if (!$readCode($socket, 250, $raw)) {
        $last_mail_error = "MAIL FROM rejected: $raw";
        error_log("[SMTP Error] $last_mail_error");
        fclose($socket);
        return false;
    }

    fputs($socket, "RCPT TO: <$toEmail>\r\n");
    if (!$readCode($socket, 250, $raw)) {
        $last_mail_error = "RCPT TO rejected: $raw";
        error_log("[SMTP Error] $last_mail_error");
        fclose($socket);
        return false;
    }

    fputs($socket, "DATA\r\n");
    if (!$readCode($socket, 354, $raw)) {
        $last_mail_error = "DATA command rejected: $raw";
        error_log("[SMTP Error] $last_mail_error");
        fclose($socket);
        return false;
    }

    // Headers & Payload
    $boundary = "=_mb_" . md5(uniqid((string)time(), true));
    $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
    $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <$toEmail>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";

    $payload = $headers . "\r\n";
    $payload .= "--$boundary\r\n";
    $payload .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $payload .= (!empty($plainText) ? $plainText : strip_tags($htmlBody)) . "\r\n\r\n";
    $payload .= "--$boundary\r\n";
    $payload .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $payload .= $htmlBody . "\r\n\r\n";
    $payload .= "--$boundary--\r\n";
    $payload .= ".\r\n";

    fputs($socket, $payload);
    $sent = $readCode($socket, 250, $raw);
    if (!$sent) {
        $last_mail_error = "Message rejected by SMTP server: $raw";
        error_log("[SMTP Error] $last_mail_error");
    }

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return $sent;
}