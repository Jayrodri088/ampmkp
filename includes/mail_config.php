<?php
/**
 * Mail Configuration
 * This file contains email configuration settings and functions for sending emails
 *
 * IMPORTANT: To use this file, you need to install PHPMailer:
 * 1. Run: composer require phpmailer/phpmailer
 * 2. Or download from: https://github.com/PHPMailer/PHPMailer
 *
 * For IDE type resolution, see stubs/PHPMailer.php (do not require at runtime).
 */

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Read env values with support for .env file and process environment.
 */
function getMailEnv(string $key, ?string $default = null): ?string {
    static $env = null;
    if ($env === null) {
        $env = [];
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $line = trim((string)$line);
                    if ($line === '' || strpos($line, '#') === 0) {
                        continue;
                    }
                    $eq = strpos($line, '=');
                    if ($eq === false) {
                        continue;
                    }
                    $k = trim(substr($line, 0, $eq));
                    $v = trim(substr($line, $eq + 1));
                    if ($k !== '') {
                        $env[$k] = $v;
                    }
                }
            }
        }
    }

    if (array_key_exists($key, $env) && $env[$key] !== '') {
        return $env[$key];
    }

    $runtime = getenv($key);
    if ($runtime !== false && $runtime !== '') {
        return $runtime;
    }

    return $default;
}

// Email server settings (env-driven)
define('SMTP_HOST', (string)getMailEnv('MAIL_SMTP_HOST', 'smtp.hostinger.com'));
define('SMTP_PORT', (int)getMailEnv('MAIL_SMTP_PORT', '465'));
define('SMTP_USERNAME', (string)getMailEnv('MAIL_SMTP_USERNAME', ''));
define('SMTP_PASSWORD', (string)getMailEnv('MAIL_SMTP_PASSWORD', ''));
define('SMTP_ENCRYPTION', (string)getMailEnv('MAIL_SMTP_ENCRYPTION', 'ssl'));

// Email addresses (env-driven)
define('ADMIN_EMAIL', (string)getMailEnv('MAIL_ADMIN_EMAIL', 'admin@angelmarketplace.org'));
define('SALES_EMAIL', (string)getMailEnv('MAIL_SALES_EMAIL', ADMIN_EMAIL));
define('NOREPLY_EMAIL', (string)getMailEnv('MAIL_NOREPLY_EMAIL', ADMIN_EMAIL));
define('MAIL_FROM_NAME', (string)getMailEnv('MAIL_FROM_NAME', 'Angel Marketplace'));
define('ADMIN_ORDERS_URL', (string)getMailEnv('MAIL_ADMIN_ORDERS_URL', 'https://angelmarketplace.org/admin/orders.php'));

// Check if PHPMailer is installed via Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Verify PHPMailer class exists
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        define('PHPMAILER_LOADED', true);
    } else {
        define('PHPMAILER_LOADED', false);
        error_log("PHPMailer class not found in autoloader. Using PHP mail() function as fallback.");
    }
} 
// Check if PHPMailer is installed manually
else if (file_exists(__DIR__ . '/../lib/PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
    require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
    define('PHPMAILER_LOADED', true);
} else {
    define('PHPMAILER_LOADED', false);
    error_log("PHPMailer not found. Using PHP mail() function as fallback.");
}

// Load helpers for prices/settings used in order emails
require_once __DIR__ . '/functions.php';

/**
 * Send contact form email to admin and confirmation to sender
 * 
 * @param string $name Sender's name
 * @param string $email Sender's email
 * @param string $phone Sender's phone
 * @param string $message Message content
 * @return bool Whether the email was sent successfully
 */
function sendContactEmail($name, $email, $phone, $message) {
    $success = true;
    
    // 1. Send notification to admin
    $adminSuccess = sendContactEmailToAdmin($name, $email, $phone, $message);
    
    // 2. Send confirmation to the person who submitted the form
    $senderSuccess = sendContactConfirmationToSender($name, $email);
    
    // Return true only if both emails were sent successfully
    return $adminSuccess && $senderSuccess;
}

/**
 * Send contact form notification to admin
 * 
 * @param string $name Sender's name
 * @param string $email Sender's email
 * @param string $phone Sender's phone
 * @param string $message Message content
 * @return bool Whether the email was sent successfully
 */
function sendContactEmailToAdmin($name, $email, $phone, $message) {
    // Email subject
    $subject = "New Contact Form Submission from " . $name;
    
    // Email body
    $body = "
    <html>
    <head>
        <title>New Contact Form Submission</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h2 { color: #FF0055; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .info { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .label { font-weight: bold; }
            .message { background: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>New Contact Form Submission</h2>
            <div class='info'>
                <p><span class='label'>Name:</span> " . htmlspecialchars($name) . "</p>
                <p><span class='label'>Email:</span> " . htmlspecialchars($email) . "</p>
                <p><span class='label'>Phone:</span> " . htmlspecialchars($phone) . "</p>
                <p><span class='label'>Date:</span> " . date('F j, Y, g:i a') . "</p>
            </div>
            <div class='message'>
                <p><span class='label'>Message:</span></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Try to use PHPMailer if available
    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            if (property_exists($mail, 'Sender')) { $mail->Sender = NOREPLY_EMAIL; }
            $mail->addAddress(ADMIN_EMAIL);
            $mail->addReplyTo($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error (admin contact notification): " . $e->getMessage());
            // Fall back to PHP mail function
            return sendMailFallback(ADMIN_EMAIL, $subject, $body, $email, $name);
        }
    } else {
        // Use PHP mail function as fallback
        return sendMailFallback(ADMIN_EMAIL, $subject, $body, $email, $name);
    }
}

/**
 * Send confirmation email to contact form sender
 * 
 * @param string $name Sender's name
 * @param string $email Sender's email
 * @return bool Whether the email was sent successfully
 */
function sendContactConfirmationToSender($name, $email) {
    // Email subject
    $subject = "Thank You for Contacting Angel Marketplace";
    
    // Email body
    $body = "
    <html>
    <head>
        <title>Contact Form Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h2 { color: #FF0055; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .content { background: #f9f9f9; padding: 15px; border-radius: 5px; }
            .footer { margin-top: 20px; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Thank You for Contacting Us!</h2>
            <div class='content'>
                <p>Hello " . htmlspecialchars($name) . ",</p>
                <p>Thank you for reaching out to Angel Marketplace. We have received your message and will get back to you as soon as possible.</p>
                <p>Our team typically responds within 24-48 hours during business days.</p>
                <p>Best regards,<br>The Angel Marketplace Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated response. Please do not reply to this email.</p>
                <p>© " . date('Y') . " Angel Marketplace. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Try to use PHPMailer if available
    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            if (property_exists($mail, 'Sender')) { $mail->Sender = NOREPLY_EMAIL; }
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error (contact confirmation): " . $e->getMessage());
            // Fall back to PHP mail function
            return sendMailFallback($email, $subject, $body);
        }
    } else {
        // Use PHP mail function as fallback
        return sendMailFallback($email, $subject, $body);
    }
}

/**
 * Send newsletter subscription confirmation
 * 
 * @param string $email Subscriber's email
 * @return bool Whether the email was sent successfully
 */
function sendNewsletterConfirmation($email) {
    $success = true;
    
    // 1. Send confirmation email to subscriber
    $subscriberSuccess = sendNewsletterEmailToSubscriber($email);
    
    // 2. Send notification to admin
    $adminSuccess = sendNewsletterNotificationToAdmin($email);
    
    // Return true only if both emails were sent successfully
    return $subscriberSuccess && $adminSuccess;
}

/**
 * Send login code email for passwordless customer sign-in
 *
 * @param string $email Customer's email
 * @param string $code 6-digit code
 * @return bool Whether the email was sent successfully
 */
function sendLoginCode($email, $code) {
    $subject = 'Your Angel Marketplace login code';
    $body = "
    <html>
    <head>
        <title>Your Login Code</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h2 { color: #FF0055; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .code { font-size: 24px; font-weight: bold; letter-spacing: 4px; padding: 12px 20px; background: #f5f5f5; border-radius: 8px; margin: 16px 0; }
            .footer { margin-top: 20px; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Your login code</h2>
            <p>Use this code to sign in to your Angel Marketplace account:</p>
            <div class='code'>" . htmlspecialchars($code) . "</div>
            <p>This code expires in 15 minutes. If you did not request it, you can ignore this email.</p>
            <div class='footer'>
                <p>© " . date('Y') . " Angel Marketplace. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    $usePhpMailer = defined('PHPMAILER_LOADED') && PHPMAILER_LOADED && SMTP_USERNAME !== '' && SMTP_PASSWORD !== '';

    if ($usePhpMailer) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($email);
            $mail->addReplyTo(NOREPLY_EMAIL, MAIL_FROM_NAME);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            $msg = 'Login code PHPMailer: ' . $e->getMessage();
            error_log($msg);
            if (function_exists('logError')) {
                logError($msg);
            }
            $fallback = sendMailFallback($email, $subject, $body);
            if (!$fallback && function_exists('logError')) {
                logError('Login code: sendMailFallback failed for ' . $email);
            }
            return $fallback;
        }
    }

    if (function_exists('logError')) {
        if (!defined('PHPMAILER_LOADED') || !PHPMAILER_LOADED) {
            logError('Login code: PHPMailer not loaded (vendor/autoload or lib/PHPMailer)');
        } elseif (SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
            logError('Login code: SMTP credentials empty (check .env MAIL_SMTP_USERNAME, MAIL_SMTP_PASSWORD)');
        }
    }
    $fallbackOk = sendMailFallback($email, $subject, $body);
    if (!$fallbackOk && function_exists('logError')) {
        logError('Login code: PHP mail() fallback failed for ' . $email);
    }
    return $fallbackOk;
}

/**
 * Send confirmation email to newsletter subscriber
 * 
 * @param string $email Subscriber's email
 * @return bool Whether the email was sent successfully
 */
function sendNewsletterEmailToSubscriber($email) {
    // Email subject
    $subject = "Thank You for Subscribing to Our Newsletter";
    
    // Email body
    $body = "
    <html>
    <head>
        <title>Newsletter Subscription Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h2 { color: #FF0055; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .content { background: #f9f9f9; padding: 15px; border-radius: 5px; }
            .footer { margin-top: 20px; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Thank You for Subscribing!</h2>
            <div class='content'>
                <p>Hello,</p>
                <p>Thank you for subscribing to the Angel Marketplace newsletter. You'll now receive updates about our latest products, promotions, and news.</p>
                <p>We're excited to have you join our community!</p>
                <p>Best regards,<br>The Angel Marketplace Team</p>
            </div>
            <div class='footer'>
                <p>If you did not subscribe to our newsletter, please disregard this email.</p>
                <p>© " . date('Y') . " Angel Marketplace. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Try to use PHPMailer if available
    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($email);
            
            // Set Reply-To header to the same as From to avoid SPF/DKIM issues
            $mail->addReplyTo(NOREPLY_EMAIL, MAIL_FROM_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error (subscriber email): " . $e->getMessage());
            // Fall back to PHP mail function
            return sendMailFallback($email, $subject, $body);
        }
    } else {
        // Use PHP mail function as fallback
        return sendMailFallback($email, $subject, $body);
    }
}

/**
 * Send notification to admin about new newsletter subscriber
 * 
 * @param string $email Subscriber's email
 * @return bool Whether the email was sent successfully
 */
function sendNewsletterNotificationToAdmin($email) {
    // Email subject
    $subject = "New Newsletter Subscription";
    
    // Email body
    $body = "
    <html>
    <head>
        <title>New Newsletter Subscription</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h2 { color: #FF0055; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .info { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .label { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>New Newsletter Subscription</h2>
            <div class='info'>
                <p><span class='label'>Email:</span> " . htmlspecialchars($email) . "</p>
                <p><span class='label'>Date:</span> " . date('F j, Y, g:i a') . "</p>
            </div>
            <p>A new user has subscribed to your newsletter. You can view all subscribers in your admin panel.</p>
        </div>
    </body>
    </html>
    ";
    
    // Try to use PHPMailer if available
    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress(ADMIN_EMAIL);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error (admin notification): " . $e->getMessage());
            // Fall back to PHP mail function
            return sendMailFallback(ADMIN_EMAIL, $subject, $body);
        }
    } else {
        // Use PHP mail function as fallback
        return sendMailFallback(ADMIN_EMAIL, $subject, $body);
    }
}

/**
 * Fallback function to send email using PHP's mail() function
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $replyTo Reply-to email (optional)
 * @param string $replyToName Reply-to name (optional)
 * @return bool Whether the email was sent successfully
 */
function sendMailFallback($to, $subject, $body, $replyTo = '', $replyToName = '') {
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . NOREPLY_EMAIL . "\r\n";
    
    if (!empty($replyTo)) {
        $headers .= "Reply-To: " . ($replyToName ? "$replyToName <$replyTo>" : $replyTo) . "\r\n";
    }
    
    try {
        return mail($to, $subject, $body, $headers);
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Big Church Festival lead to a dedicated admin inbox
 *
 * @param string $name Lead name
 * @param string $email Lead email
 * @param string $phone Lead phone
 * @return bool
 */
function sendFestivalLeadEmail($name, $email, $phone) {
    $targetInbox = ADMIN_EMAIL;
    // Admin notification
    $subjectAdmin = 'New Big Church Festival Lead: ' . $name;
    $bodyAdmin = "
    <html>
    <head>
        <title>New Festival Lead</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h2 { color: #FF0055; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .info { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .label { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>New Big Church Festival Lead</h2>
            <div class='info'>
                <p><span class='label'>Name:</span> " . htmlspecialchars($name) . "</p>
                <p><span class='label'>Email:</span> " . htmlspecialchars($email) . "</p>
                <p><span class='label'>Phone:</span> " . htmlspecialchars($phone) . "</p>
                <p><span class='label'>Received:</span> " . date('F j, Y, g:i a') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $adminOk = false;
    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($targetInbox);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($email, $name);
            }
            $mail->isHTML(true);
            $mail->Subject = $subjectAdmin;
            $mail->Body = $bodyAdmin;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $bodyAdmin));
            $mail->send();
            $adminOk = true;
        } catch (Exception $e) {
            error_log("PHPMailer Error (festival lead admin): " . $e->getMessage());
            $adminOk = sendMailFallback($targetInbox, $subjectAdmin, $bodyAdmin, $email, $name);
        }
    } else {
        $adminOk = sendMailFallback($targetInbox, $subjectAdmin, $bodyAdmin, $email, $name);
    }

    // Thank-you email to submitter
    $senderOk = true;
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $subjectSender = 'Thank you for your response';
        $bodySender = "
        <html>
        <head>
            <title>Thank You</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                h2 { color: #FF0055; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                .content { background: #f9f9f9; padding: 15px; border-radius: 5px; }
                .footer { margin-top: 20px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Thank You!</h2>
                <div class='content'>
                    <p>Hello " . htmlspecialchars($name) . ",</p>
                    <p>Thanks for your response. We've received your details and will be in touch shortly.</p>
                    <p>Best regards,<br/>Angel Marketplace Team</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Angel Marketplace</p>
                </div>
            </div>
        </body>
        </html>
        ";

        if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
            try {
                $mail2 = new PHPMailer(true);
                $mail2->isSMTP();
                $mail2->Host = SMTP_HOST;
                $mail2->SMTPAuth = true;
                $mail2->Username = SMTP_USERNAME;
                $mail2->Password = SMTP_PASSWORD;
                $mail2->SMTPSecure = SMTP_ENCRYPTION;
                $mail2->Port = SMTP_PORT;

                $mail2->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
                $mail2->addAddress($email, $name);
                $mail2->isHTML(true);
                $mail2->Subject = $subjectSender;
                $mail2->Body = $bodySender;
                $mail2->AltBody = strip_tags(str_replace('<br>', "\n", $bodySender));
                $mail2->send();
                $senderOk = true;
            } catch (Exception $e) {
                error_log("PHPMailer Error (festival thank-you): " . $e->getMessage());
                $senderOk = sendMailFallback($email, $subjectSender, $bodySender);
            }
        } else {
            $senderOk = sendMailFallback($email, $subjectSender, $bodySender);
        }
    }

    return ($adminOk && $senderOk);
}

/**
 * Build HTML for an order summary email
 *
 * @param array $orderData
 * @param string $receiptUrl Optional Stripe receipt URL
 * @param string $invoiceUrl Optional Stripe hosted invoice or PDF URL
 * @return string
 */
function buildOrderEmailHtml(array $orderData, string $receiptUrl = '', string $invoiceUrl = ''): string {
    $settings = getSettings();
    $currencyCode = $orderData['currency'] ?? ($settings['currency_code'] ?? 'GBP');

    // Resolve symbol from settings currencies
    $symbol = $settings['currency_symbol'] ?? '£';
    if (!empty($settings['currencies']) && is_array($settings['currencies'])) {
        foreach ($settings['currencies'] as $c) {
            if (($c['code'] ?? '') === $currencyCode && !empty($c['symbol'])) {
                $symbol = $c['symbol'];
                break;
            }
        }
    }
    $fmt = function($amount) use ($currencyCode) {
        if (function_exists('formatPriceWithCurrency')) {
            return formatPriceWithCurrency((float)$amount, $currencyCode);
        }
        return number_format((float)$amount, 2);
    };

    $itemsHtml = '';
    if (!empty($orderData['items']) && is_array($orderData['items'])) {
        foreach ($orderData['items'] as $item) {
            $lineName = htmlspecialchars($item['product_name'] ?? $item['name'] ?? 'Item');
            $qty = (int)($item['quantity'] ?? 1);
            $price = (float)($item['price'] ?? $item['unit_price'] ?? 0);
            $subtotalLine = (float)($item['subtotal'] ?? ($item['item_total'] ?? ($price * $qty)));
            $itemsHtml .= "<tr><td style=\"padding:8px;border-bottom:1px solid #eee;\">{$lineName}</td><td style=\"padding:8px;text-align:center;border-bottom:1px solid #eee;\">{$qty}</td><td style=\"padding:8px;text-align:right;border-bottom:1px solid #eee;\">{$fmt($price)}</td><td style=\"padding:8px;text-align:right;border-bottom:1px solid #eee;\">{$fmt($subtotalLine)}</td></tr>";
        }
    }

    $shipping = (float)($orderData['shipping_cost'] ?? 0);
    $subtotalAll = (float)($orderData['subtotal'] ?? 0);
    $tax = (float)($orderData['tax'] ?? 0);
    $total = (float)($orderData['total'] ?? ($subtotalAll + $shipping + $tax));

    $linksHtml = '';
    if (!empty($invoiceUrl)) {
        $linksHtml .= '<p>You can download your Stripe invoice here: <a href="' . htmlspecialchars($invoiceUrl) . '">View Invoice</a></p>';
    }
    if (!empty($receiptUrl)) {
        $linksHtml .= '<p>Your Stripe receipt is available here: <a href="' . htmlspecialchars($receiptUrl) . '">View Receipt</a></p>';
    }

    // Resolve addresses; fallback to customer flat fields
    $shippingAddr = $orderData['shipping_address'] ?? $orderData['shippingAddress'] ?? [];
    $billingAddr = $orderData['billing_address'] ?? $orderData['billingAddress'] ?? [];
    $fallbackAddr = function() use ($orderData) {
        $cust = $orderData['customer'] ?? [];
        $addr = [];
        foreach (['address' => 'line1', 'city' => 'city', 'postal_code' => 'postcode', 'country' => 'country'] as $src => $dst) {
            if (!empty($cust[$src])) { $addr[$dst] = $cust[$src]; }
        }
        return $addr;
    };
    if (empty(array_filter($shippingAddr))) {
        $shippingAddr = $fallbackAddr();
    }
    if (empty(array_filter($billingAddr))) {
        $billingAddr = $fallbackAddr();
    }

    $formatAddr = function($addr) {
        $parts = [];
        foreach (['line1','line2','city','postcode','country'] as $key) {
            if (!empty($addr[$key])) { $parts[] = htmlspecialchars((string)$addr[$key]); }
        }
        return implode(', ', $parts);
    };

    $orderId = htmlspecialchars($orderData['id'] ?? '');
    $orderDate = htmlspecialchars($orderData['date'] ?? $orderData['created_at'] ?? date('Y-m-d H:i:s'));

    // Payment method display
    $pm = $orderData['payment_method'] ?? ($orderData['customer']['payment_method'] ?? '');
    $pmLabel = match($pm) {
        'card' => 'Card (Stripe)',
        'bank_transfer' => 'Bank Transfer',
        'paypal' => 'PayPal',
        'espees' => 'Espees',
        default => ucfirst($pm ?: 'Unknown'),
    };

    $shippingMethod = $orderData['shipping_method'] ?? '';

    // Customer/contact/payment helper fields
    $customerEmail = $orderData['customer_email'] ?? ($orderData['customer']['email'] ?? '');
    $customerName = $orderData['customer_name'] ?? trim(($orderData['customer']['first_name'] ?? '') . ' ' . ($orderData['customer']['last_name'] ?? ''));
    $customerPhone = $orderData['customer_phone'] ?? ($orderData['customer']['phone'] ?? '');
    $accountHolder = $orderData['customer']['account_holder'] ?? '';
    $specialInstructions = $orderData['notes'] ?? ($orderData['customer']['special_instructions'] ?? '');

    $html = "
    <html>
    <head>
        <title>Order Confirmation {$orderId}</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 680px; margin: 0 auto; padding: 20px; }
            h2 { color: #FF0055; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; }
            th { text-align: left; padding: 8px; border-bottom: 2px solid #ddd; }
            td { padding: 8px; }
            .totals td { border-top: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Thank you for your order!</h2>
            <p><strong>Order ID:</strong> {$orderId}<br/>
               <strong>Date:</strong> {$orderDate}<br/>
               <strong>Payment Method:</strong> {$pmLabel}<br/>
               " . ($shippingMethod ? "<strong>Shipping Method:</strong> " . htmlspecialchars($shippingMethod) . "<br/>" : "") . "
            </p>

            <h3>Customer & Payment Details</h3>
            <p>
                " . ($customerName ? "<strong>Name:</strong> " . htmlspecialchars($customerName) . "<br/>" : "") . "
                " . ($customerEmail ? "<strong>Email:</strong> " . htmlspecialchars($customerEmail) . "<br/>" : "") . "
                " . ($customerPhone ? "<strong>Phone:</strong> " . htmlspecialchars($customerPhone) . "<br/>" : "") . "
                " . ($accountHolder ? "<strong>Account Holder (bank transfer):</strong> " . htmlspecialchars($accountHolder) . "<br/>" : "") . "
                " . ($specialInstructions ? "<strong>Special Instructions:</strong> " . nl2br(htmlspecialchars($specialInstructions)) . "<br/>" : "") . "
            </p>

            <h3>Items</h3>
            <table>
                <thead>
                    <tr><th>Product</th><th style=\"text-align:center\">Qty</th><th style=\"text-align:right\">Price</th><th style=\"text-align:right\">Subtotal</th></tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
                <tfoot>
                    <tr class=\"totals\"><td colspan=\"3\" style=\"text-align:right\"><strong>Subtotal</strong></td><td style=\"text-align:right\">{$fmt($subtotalAll)}</td></tr>
                    <tr class=\"totals\"><td colspan=\"3\" style=\"text-align:right\"><strong>Shipping</strong></td><td style=\"text-align:right\">{$fmt($shipping)}</td></tr>
                    <tr class=\"totals\"><td colspan=\"3\" style=\"text-align:right\"><strong>Tax</strong></td><td style=\"text-align:right\">{$fmt($tax)}</td></tr>
                    <tr class=\"totals\"><td colspan=\"3\" style=\"text-align:right\"><strong>Total</strong></td><td style=\"text-align:right\"><strong>{$fmt($total)}</strong></td></tr>
                </tfoot>
            </table>

            <h3>Addresses</h3>
            <p><strong>Shipping:</strong> " . $formatAddr($shippingAddr) . "<br/>
               <strong>Billing:</strong> " . $formatAddr($billingAddr) . "</p>

            {$linksHtml}

            <p>We appreciate your business.<br/>Angel Marketplace</p>
        </div>
    </body>
    </html>";

    return $html;
}

/**
 * Send order confirmation to customer
 */
function sendOrderConfirmationToCustomer(array $orderData, string $receiptUrl = '', string $invoiceUrl = ''): bool {
    $customerEmail = trim((string)($orderData['customer_email'] ?? ''));
    $customerName = trim((string)($orderData['customer_name'] ?? 'Customer'));
    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $subject = 'Payment received - Order ' . ($orderData['id'] ?? '');
    $body = buildOrderEmailHtml($orderData, $receiptUrl, $invoiceUrl);

    $sent = false;

    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            if (property_exists($mail, 'Sender')) { $mail->Sender = NOREPLY_EMAIL; }
            $mail->addAddress($customerEmail, $customerName);
            $mail->addReplyTo(NOREPLY_EMAIL, MAIL_FROM_NAME);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

            // Attach invoice PDF when available
            if (!empty($invoiceUrl) && preg_match('/\.pdf(\?|$)/i', $invoiceUrl)) {
                try {
                    $ctx = stream_context_create(['http' => ['timeout' => 8]]);
                    $pdfData = @file_get_contents($invoiceUrl, false, $ctx);
                    if ($pdfData !== false) {
                        $mail->addStringAttachment($pdfData, 'invoice-' . ($orderData['id'] ?? 'order') . '.pdf', 'base64', 'application/pdf');
                    }
                } catch (Exception $e) {
                    error_log('Invoice PDF attach failed: ' . $e->getMessage());
                }
            }

            $mail->send();
            $sent = true;
        } catch (Exception $e) {
            error_log('PHPMailer Error (order confirmation to customer): ' . $e->getMessage());
        }
    }

    if (!$sent) {
        $ok = sendMailFallback($customerEmail, $subject, $body);
        $sent = $ok;
    }

    if (!$sent && function_exists('logError')) {
        logError('Customer order email failed', ['order_id' => $orderData['id'] ?? null, 'email' => $customerEmail]);
    }
    return $sent;
}

/**
 * Send order status update to customer
 *
 * @param array $orderData Order payload with at least id, customer_email, customer_name
 * @param string $newStatus One of pending|processing|completed|cancelled
 * @return bool
 */
function sendOrderStatusUpdateToCustomer(array $orderData, string $newStatus): bool {
    $customerEmail = trim((string)($orderData['customer_email'] ?? ''));
    $customerName = trim((string)($orderData['customer_name'] ?? 'Customer'));
    $orderId = (string)($orderData['id'] ?? '');
    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $statusKey = strtolower(trim($newStatus));
    $statusLabel = ucfirst($statusKey ?: 'updated');

    switch ($statusKey) {
        case 'completed':
            $subject = 'Your order ' . $orderId . ' is completed';
            $lead = 'Great news! Your order has been completed. Thank you for shopping with us.';
            break;
        case 'processing':
            $subject = 'Your order ' . $orderId . ' is being processed';
            $lead = 'We are currently processing your order. We will let you know when it is completed.';
            break;
        case 'cancelled':
            $subject = 'Your order ' . $orderId . ' has been cancelled';
            $lead = 'Your order has been cancelled. If you believe this was a mistake or have questions, please reply to this email.';
            break;
        case 'pending':
        default:
            $subject = 'Order ' . $orderId . ' status updated: ' . $statusLabel;
            $lead = 'Your order status has been updated to: ' . $statusLabel . '.';
            break;
    }

    // Include a summary for completed and cancelled statuses
    $includeSummary = in_array($statusKey, ['completed', 'cancelled'], true);
    $summaryHtml = $includeSummary ? buildOrderEmailHtml($orderData) : '';

    $body = "
    <html>
    <head>
        <title>{$subject}</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 680px; margin: 0 auto; padding: 20px; }
            .lead { background:#f9f9f9; padding:12px 14px; border-radius:6px; }
            .footer { margin-top: 18px; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>{$subject}</h2>
            <div class='lead'>
                <p>Hello " . htmlspecialchars($customerName) . ",</p>
                <p>" . htmlspecialchars($lead) . "</p>
            </div>
            " . $summaryHtml . "
            <div class='footer'>
                <p>If you have any questions, reply to this email.</p>
                <p>© " . date('Y') . " Angel Marketplace</p>
            </div>
        </div>
    </body>
    </html>
    ";

    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            if (property_exists($mail, 'Sender')) { $mail->Sender = NOREPLY_EMAIL; }
            $mail->addAddress($customerEmail, $customerName);
            $mail->addReplyTo(NOREPLY_EMAIL, MAIL_FROM_NAME);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer Error (order status update to customer): ' . $e->getMessage());
            return sendMailFallback($customerEmail, $subject, $body);
        }
    }

    return sendMailFallback($customerEmail, $subject, $body);
}

/**
 * Send order notification to admin
 */
function sendOrderNotificationToAdmin(array $orderData, string $receiptUrl = '', string $invoiceUrl = ''): bool {
    $subject = 'New paid order ' . ($orderData['id'] ?? '');
    $body = buildOrderEmailHtml($orderData, $receiptUrl, $invoiceUrl);

    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            if (!empty(SALES_EMAIL)) {
                $mail->addAddress(SALES_EMAIL);
            } else {
                $mail->addAddress(ADMIN_EMAIL);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            // Attach invoice PDF when available
            if (!empty($invoiceUrl) && preg_match('/\.pdf(\?|$)/i', $invoiceUrl)) {
                try {
                    $ctx = stream_context_create(['http' => ['timeout' => 8]]);
                    $pdfData = @file_get_contents($invoiceUrl, false, $ctx);
                    if ($pdfData !== false) {
                        $mail->addStringAttachment($pdfData, 'invoice-' . ($orderData['id'] ?? 'order') . '.pdf', 'base64', 'application/pdf');
                    }
                } catch (Exception $e) {
                    error_log('Invoice PDF attach failed (admin): ' . $e->getMessage());
                }
            }
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer Error (order notification to admin): ' . $e->getMessage());
            $ok = !empty(SALES_EMAIL)
                ? sendMailFallback(SALES_EMAIL, $subject, $body)
                : sendMailFallback(ADMIN_EMAIL, $subject, $body);
            return $ok;
        }
    }

    $ok = !empty(SALES_EMAIL)
        ? sendMailFallback(SALES_EMAIL, $subject, $body)
        : sendMailFallback(ADMIN_EMAIL, $subject, $body);
    return $ok;
}

/**
 * Send pending order notification to admin for manual payment methods
 * (PayPal, Bank Transfer, etc.)
 *
 * @param array $orderData Order payload
 * @return bool
 */
function sendPendingOrderNotificationToAdmin(array $orderData): bool {
    // Normalize customer details
    $customerEmail = $orderData['customer_email'] ?? ($orderData['customer']['email'] ?? '');
    $customerName = $orderData['customer_name']
        ?? ($orderData['customer']['name'] ?? trim(($orderData['customer']['first_name'] ?? '') . ' ' . ($orderData['customer']['last_name'] ?? '')));
    $customerPhone = $orderData['customer_phone'] ?? ($orderData['customer']['phone'] ?? '');

    $paymentMethod = $orderData['payment_method'] ?? ($orderData['customer']['payment_method'] ?? 'Unknown');
    $subject = 'New Pending Order ' . ($orderData['id'] ?? '') . ' - ' . ucwords(str_replace('_', ' ', $paymentMethod));
    
    // Build custom HTML for pending orders
    $currency = $orderData['currency'] ?? 'GBP';
    $customerName = $customerName ?: 'Unknown';
    $customerEmail = $customerEmail ?: 'Unknown';
    $customerPhone = $customerPhone ?: 'N/A';
    $orderId = $orderData['id'] ?? '';
    $total = formatPriceWithCurrency($orderData['total'] ?? 0, $currency);
    $dateCreated = $orderData['created_at'] ?? date('Y-m-d H:i:s');
    
    // Build items table
    $itemsHtml = '';
    if (!empty($orderData['items'])) {
        foreach ($orderData['items'] as $item) {
            $itemTotal = formatPriceWithCurrency($item['subtotal'] ?? 0, $currency);
            $itemsHtml .= '<tr>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">' . htmlspecialchars($item['product_name'] ?? '') . '</td>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: center;">' . ($item['quantity'] ?? 0) . '</td>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right;">' . $itemTotal . '</td>
            </tr>';
        }
    }
    
    $subtotal = formatPriceWithCurrency($orderData['subtotal'] ?? 0, $currency);
    $shipping = formatPriceWithCurrency($orderData['shipping_cost'] ?? 0, $currency);
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>New Pending Order</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f9fafb;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
            <div style="background-color: #ea580c; padding: 30px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">⚠️ New Pending Order</h1>
            </div>
            
            <div style="padding: 30px;">
                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #92400e; font-weight: bold;">⏳ Awaiting Payment Confirmation</p>
                    <p style="margin: 5px 0 0 0; color: #92400e; font-size: 14px;">Payment Method: ' . ucwords(str_replace('_', ' ', $paymentMethod)) . '</p>
                </div>
                
                <h2 style="color: #1f2937; margin-top: 0;">Order Details</h2>
                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Order ID:</td>
                        <td style="padding: 8px 0; color: #1f2937; font-weight: bold;">' . htmlspecialchars($orderId) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Date:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . htmlspecialchars($dateCreated) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Total:</td>
                        <td style="padding: 8px 0; color: #1f2937; font-weight: bold; font-size: 18px;">' . $total . '</td>
                    </tr>
                </table>
                
                <h3 style="color: #1f2937;">Customer Information</h3>
                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Name:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . htmlspecialchars($customerName) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Email:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . htmlspecialchars($customerEmail) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Phone:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . htmlspecialchars($customerPhone) . '</td>
                    </tr>
                </table>
                
                <h3 style="color: #1f2937;">Order Items</h3>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr style="background-color: #f9fafb;">
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;">Product</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb;">Qty</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e5e7eb;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $itemsHtml . '
                    </tbody>
                </table>
                
                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 8px; text-align: right; color: #6b7280;">Subtotal:</td>
                        <td style="padding: 8px; text-align: right; color: #1f2937; width: 120px;">' . $subtotal . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; text-align: right; color: #6b7280;">Shipping:</td>
                        <td style="padding: 8px; text-align: right; color: #1f2937;">' . $shipping . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; text-align: right; color: #1f2937; font-weight: bold; font-size: 16px; border-top: 2px solid #e5e7eb;">Total:</td>
                        <td style="padding: 8px; text-align: right; color: #ea580c; font-weight: bold; font-size: 18px; border-top: 2px solid #e5e7eb;">' . $total . '</td>
                    </tr>
                </table>
                
                <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 30px; text-align: center;">
                    <p style="margin: 0 0 15px 0; color: #1f2937; font-weight: bold;">Action Required</p>
                    <p style="margin: 0 0 15px 0; color: #6b7280; font-size: 14px;">Please verify payment and update order status in the admin panel.</p>
                    <a href="' . ADMIN_ORDERS_URL . '" style="display: inline-block; background-color: #ea580c; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;">View Order in Admin</a>
                </div>
            </div>
            
            <div style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0; color: #6b7280; font-size: 12px;">Angel Marketplace - Admin Notification</p>
            </div>
        </div>
    </body>
    </html>';

    $sent = false;

    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            if (!empty(SALES_EMAIL)) {
                $mail->addAddress(SALES_EMAIL);
            } else {
                $mail->addAddress(ADMIN_EMAIL);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            $mail->send();
            $sent = true;
        } catch (Exception $e) {
            error_log('PHPMailer Error (pending order notification to admin): ' . $e->getMessage());
        }
    }

    if (!$sent) {
        $ok = !empty(SALES_EMAIL)
            ? sendMailFallback(SALES_EMAIL, $subject, $body)
            : sendMailFallback(ADMIN_EMAIL, $subject, $body);
        $sent = $ok;
    }

    if (!$sent && function_exists('logError')) {
        logError('Pending order admin email failed', ['order_id' => $orderData['id'] ?? null]);
    }
    return $sent;
}

/**
 * Send pending order confirmation to customer for manual payment methods
 *
 * @param array $orderData Order payload
 * @return bool
 */
function sendPendingOrderConfirmationToCustomer(array $orderData): bool {
    // Extract customer email - handle both old and new data structures
    $customerEmail = '';
    $customerName = '';

    if (isset($orderData['customer_email'])) {
        $customerEmail = trim((string)$orderData['customer_email']);
        $customerName = trim((string)($orderData['customer_name'] ?? 'Customer'));
    } elseif (isset($orderData['customer'])) {
        $customerEmail = trim((string)($orderData['customer']['email'] ?? ''));
        $firstName = trim((string)($orderData['customer']['first_name'] ?? ''));
        $lastName = trim((string)($orderData['customer']['last_name'] ?? ''));
        $customerName = trim($firstName . ' ' . $lastName) ?: 'Customer';
    }

    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $paymentMethod = $orderData['payment_method'] ?? ($orderData['customer']['payment_method'] ?? 'Unknown');
    $subject = 'Order Confirmation - Order ' . ($orderData['id'] ?? '');

    $currency = $orderData['currency'] ?? 'GBP';
    $orderId = $orderData['id'] ?? '';
    $total = formatPriceWithCurrency($orderData['total'] ?? 0, $currency);
    $subtotal = formatPriceWithCurrency($orderData['subtotal'] ?? 0, $currency);
    $shipping = formatPriceWithCurrency($orderData['shipping_cost'] ?? 0, $currency);
    $dateCreated = $orderData['created_at'] ?? date('Y-m-d H:i:s');

    // Build items table - handle both old and new data structures
    $itemsHtml = '';
    if (!empty($orderData['items'])) {
        foreach ($orderData['items'] as $item) {
            $productName = $item['product_name'] ?? ($item['product']['name'] ?? 'Product');
            $quantity = $item['quantity'] ?? 1;
            $itemPrice = $item['price'] ?? $item['unit_price'] ?? ($item['product']['price'] ?? 0);
            $itemTotal = $item['subtotal'] ?? $item['item_total'] ?? ($itemPrice * $quantity);
            $itemTotalFormatted = formatPriceWithCurrency($itemTotal, $currency);

            $itemsHtml .= '<tr>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">' . htmlspecialchars($productName) . '</td>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: center;">' . $quantity . '</td>
                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right;">' . $itemTotalFormatted . '</td>
            </tr>';
        }
    }

    // Payment instructions based on method
    $paymentInstructions = '';
    $methodName = ucwords(str_replace('_', ' ', $paymentMethod));

    if ($paymentMethod === 'paypal') {
        $paymentInstructions = '
        <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #1e40af; font-weight: bold;">📋 Payment Instructions</p>
            <p style="margin: 10px 0 0 0; color: #1e40af; font-size: 14px;">
                Please complete your payment via PayPal at: <a href="http://paypal.me/amp202247" style="color: #3b82f6;">paypal.me/amp202247</a><br/>
                Amount to send: <strong>' . $total . '</strong><br/>
                Reference: <strong>' . htmlspecialchars($orderId) . '</strong>
            </p>
        </div>';
    } elseif ($paymentMethod === 'bank_transfer') {
        $paymentInstructions = '
        <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #1e40af; font-weight: bold;">📋 Payment Instructions</p>
            <p style="margin: 10px 0 0 0; color: #1e40af; font-size: 14px;">
                Please transfer <strong>' . $total . '</strong> to our bank account.<br/>
                Reference: <strong>' . htmlspecialchars($orderId) . '</strong><br/>
                <br/>
                Bank details have been sent to your email. If you haven\'t received them, please contact us.
            </p>
        </div>';
    }

    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Order Confirmation</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f9fafb;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
            <div style="background-color: #FF0055; padding: 30px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">✅ Order Confirmed!</h1>
            </div>

            <div style="padding: 30px;">
                <p style="font-size: 16px; color: #1f2937;">Hello ' . htmlspecialchars($customerName) . ',</p>
                <p style="font-size: 16px; color: #1f2937;">Thank you for your order! We have received your order and it is now being processed.</p>

                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; color: #92400e; font-weight: bold;">⏳ Payment Pending</p>
                    <p style="margin: 5px 0 0 0; color: #92400e; font-size: 14px;">Your order is waiting for payment confirmation. Please complete the payment to proceed.</p>
                </div>

                ' . $paymentInstructions . '

                <h2 style="color: #1f2937; margin-top: 30px;">Order Details</h2>
                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Order ID:</td>
                        <td style="padding: 8px 0; color: #1f2937; font-weight: bold;">' . htmlspecialchars($orderId) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Date:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . htmlspecialchars($dateCreated) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Payment Method:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . htmlspecialchars($methodName) . '</td>
                    </tr>
                </table>

                <h3 style="color: #1f2937;">Order Items</h3>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr style="background-color: #f9fafb;">
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;">Product</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb;">Qty</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e5e7eb;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $itemsHtml . '
                    </tbody>
                </table>

                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 8px; text-align: right; color: #6b7280;">Subtotal:</td>
                        <td style="padding: 8px; text-align: right; color: #1f2937; width: 120px;">' . $subtotal . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; text-align: right; color: #6b7280;">Shipping:</td>
                        <td style="padding: 8px; text-align: right; color: #1f2937;">' . $shipping . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; text-align: right; color: #1f2937; font-weight: bold; font-size: 16px; border-top: 2px solid #e5e7eb;">Total:</td>
                        <td style="padding: 8px; text-align: right; color: #FF0055; font-weight: bold; font-size: 18px; border-top: 2px solid #e5e7eb;">' . $total . '</td>
                    </tr>
                </table>

                <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 30px;">
                    <p style="margin: 0 0 10px 0; color: #1f2937; font-weight: bold;">Need Help?</p>
                    <p style="margin: 0; color: #6b7280; font-size: 14px;">
                        If you have any questions about your order, please contact us at:<br/>
                        <a href="mailto:' . ADMIN_EMAIL . '" style="color: #FF0055;">' . ADMIN_EMAIL . '</a>
                    </p>
                </div>
            </div>

            <div style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0; color: #6b7280; font-size: 12px;">© ' . date('Y') . ' Angel Marketplace. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';

    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            if (property_exists($mail, 'Sender')) { $mail->Sender = NOREPLY_EMAIL; }
            $mail->addAddress($customerEmail, $customerName);
            $mail->addReplyTo(NOREPLY_EMAIL, MAIL_FROM_NAME);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer Error (pending order confirmation to customer): ' . $e->getMessage());
            return sendMailFallback($customerEmail, $subject, $body);
        }
    }

    return sendMailFallback($customerEmail, $subject, $body);
}

/**
 * Send payment confirmation notification to admin when customer confirms payment
 *
 * @param array $orderData Order payload
 * @return bool
 */
function sendPaymentConfirmationToAdmin(array $orderData): bool {
    $paymentMethod = $orderData['payment_method'] ?? 'Unknown';
    $subject = '✅ Customer Confirmed Payment - Order ' . ($orderData['id'] ?? '');
    
    $currency = $orderData['currency'] ?? 'GBP';
    $customerName = $orderData['customer']['name'] ?? 'Unknown';
    $customerEmail = $orderData['customer']['email'] ?? 'Unknown';
    $customerPhone = $orderData['customer']['phone'] ?? 'N/A';
    $orderId = $orderData['id'] ?? '';
    $total = formatPriceWithCurrency($orderData['total'] ?? 0, $currency);
    $dateCreated = $orderData['created_at'] ?? date('Y-m-d H:i:s');
    $confirmedAt = $orderData['payment_confirmed_at'] ?? date('Y-m-d H:i:s');
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Payment Confirmation</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f9fafb;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
            <div style="background-color: #10b981; padding: 30px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">✅ Customer Confirmed Payment</h1>
            </div>
            
            <div style="padding: 30px;">
                <div style="background-color: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #065f46; font-weight: bold;">The customer has confirmed they have completed payment!</p>
                    <p style="margin: 5px 0 0 0; color: #065f46; font-size: 14px;">Please verify the payment and update the order status.</p>
                </div>
                
                <h2 style="color: #1f2937; margin-top: 0;">Order Information</h2>
                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Order ID:</td>
                        <td style="padding: 8px 0; color: #1f2937; font-weight: bold;">' . htmlspecialchars($orderId) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Payment Method:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . ucwords(str_replace('_', ' ', $paymentMethod)) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Order Date:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . htmlspecialchars($dateCreated) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Confirmed:</td>
                        <td style="padding: 8px 0; color: #10b981; font-weight: bold;">' . htmlspecialchars($confirmedAt) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Total Amount:</td>
                        <td style="padding: 8px 0; color: #1f2937; font-weight: bold; font-size: 18px;">' . $total . '</td>
                    </tr>
                </table>
                
                <h3 style="color: #1f2937;">Customer Details</h3>
                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Name:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . htmlspecialchars($customerName) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Email:</td>
                        <td style="padding: 8px 0; color: #1f2937;"><a href="mailto:' . htmlspecialchars($customerEmail) . '" style="color: #ea580c;">' . htmlspecialchars($customerEmail) . '</a></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #6b7280;">Phone:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . htmlspecialchars($customerPhone) . '</td>
                    </tr>
                </table>
                
                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; color: #92400e; font-weight: bold;">⚠️ Action Required</p>
                    <p style="margin: 5px 0 0 0; color: #92400e; font-size: 14px;">Please check your ' . ucwords(str_replace('_', ' ', $paymentMethod)) . ' account to verify the payment has been received.</p>
                </div>
                
                <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 30px; text-align: center;">
                    <p style="margin: 0 0 15px 0; color: #1f2937; font-weight: bold;">Verify Payment & Update Order</p>
                    <a href="' . ADMIN_ORDERS_URL . '" style="display: inline-block; background-color: #10b981; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;">View Order in Admin Panel</a>
                </div>
            </div>
            
            <div style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0; color: #6b7280; font-size: 12px;">Angel Marketplace - Payment Confirmation Notification</p>
            </div>
        </div>
    </body>
    </html>';

    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            if (!empty(SALES_EMAIL)) {
                $mail->addAddress(SALES_EMAIL);
            } else {
                $mail->addAddress(ADMIN_EMAIL);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer Error (payment confirmation to admin): ' . $e->getMessage());
            $ok = !empty(SALES_EMAIL)
                ? sendMailFallback(SALES_EMAIL, $subject, $body)
                : sendMailFallback(ADMIN_EMAIL, $subject, $body);
            return $ok;
        }
    }

    $ok = !empty(SALES_EMAIL)
        ? sendMailFallback(SALES_EMAIL, $subject, $body)
        : sendMailFallback(ADMIN_EMAIL, $subject, $body);
    return $ok;
}

/**
 * Send payment status update to customer
 *
 * @param array $orderData Order payload with at least id, customer_email, customer_name
 * @param string $newPaymentStatus One of pending|completed|failed|refunded
 * @return bool
 */
function sendPaymentStatusUpdateToCustomer(array $orderData, string $newPaymentStatus): bool {
    $customerEmail = trim((string)($orderData['customer_email'] ?? ''));
    $customerName = trim((string)($orderData['customer_name'] ?? 'Customer'));
    $orderId = (string)($orderData['id'] ?? '');
    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $statusLabel = ucfirst($newPaymentStatus ?: 'updated');
    // Subject per status
    switch ($newPaymentStatus) {
        case 'completed':
            $subject = 'Payment confirmed for Order ' . $orderId;
            $lead = 'We have confirmed your payment. Thank you! Your order will be processed shortly.';
            break;
        case 'refunded':
            $subject = 'Refund processed for Order ' . $orderId;
            $lead = 'Your refund has been processed. Depending on your bank, it may take a few days to appear.';
            break;
        case 'failed':
            $subject = 'Payment failed for Order ' . $orderId;
            $lead = 'Unfortunately, your payment attempt failed. You can try again or contact us for assistance.';
            break;
        default:
            $subject = 'Payment status updated for Order ' . $orderId . ': ' . $statusLabel;
            $lead = 'Your payment status has been updated to: ' . $statusLabel . '.';
    }

    // Build a simple HTML body. For completed/refunded we include an order summary.
    $includeSummary = in_array($newPaymentStatus, ['completed', 'refunded'], true);
    $summaryHtml = $includeSummary ? buildOrderEmailHtml($orderData) : '';

    $body = "
    <html>
    <head>
        <title>{$subject}</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 680px; margin: 0 auto; padding: 20px; }
            .lead { background:#f9f9f9; padding:12px 14px; border-radius:6px; }
            .footer { margin-top: 18px; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>{$subject}</h2>
            <div class='lead'>
                <p>Hello " . htmlspecialchars($customerName) . ",</p>
                <p>" . htmlspecialchars($lead) . "</p>
            </div>
            " . $summaryHtml . "
            <div class='footer'>
                <p>If you have any questions, reply to this email.</p>
                <p>© " . date('Y') . " Angel Marketplace</p>
            </div>
        </div>
    </body>
    </html>
    ";

    if (defined('PHPMAILER_LOADED') && PHPMAILER_LOADED) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(NOREPLY_EMAIL, MAIL_FROM_NAME);
            if (property_exists($mail, 'Sender')) { $mail->Sender = NOREPLY_EMAIL; }
            $mail->addAddress($customerEmail, $customerName);
            $mail->addReplyTo(NOREPLY_EMAIL, MAIL_FROM_NAME);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer Error (payment status update to customer): ' . $e->getMessage());
            return sendMailFallback($customerEmail, $subject, $body);
        }
    }

    return sendMailFallback($customerEmail, $subject, $body);
}
