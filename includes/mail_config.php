<?php
/**
 * Mail Configuration
 * This file contains email configuration settings and functions for sending emails
 * 
 * IMPORTANT: To use this file, you need to install PHPMailer:
 * 1. Run: composer require phpmailer/phpmailer
 * 2. Or download from: https://github.com/PHPMailer/PHPMailer
 */

// Email server settings
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'sales@angelmarketplace.org'); // Your Hostinger email address
define('SMTP_PASSWORD', 'Angelmp@2025'); // Email password
define('SMTP_ENCRYPTION', 'ssl');

// Email addresses
define('ADMIN_EMAIL', 'sales@angelmarketplace.org');
// Using the authenticated email as the from address to comply with SMTP server requirements
define('NOREPLY_EMAIL', 'sales@angelmarketplace.org');

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
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(NOREPLY_EMAIL, 'Angel Marketplace');
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
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(NOREPLY_EMAIL, 'Angel Marketplace');
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
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(NOREPLY_EMAIL, 'Angel Marketplace');
            $mail->addAddress($email);
            
            // Set Reply-To header to the same as From to avoid SPF/DKIM issues
            $mail->addReplyTo(NOREPLY_EMAIL, 'Angel Marketplace');
            
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
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(NOREPLY_EMAIL, 'Angel Marketplace');
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