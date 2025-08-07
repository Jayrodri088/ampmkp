<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/functions.php';
require_once '../includes/mail_config.php';
require_once '../includes/bot_protection.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';

switch ($action) {
    case 'subscribe':
        handleNewsletterSubscription($input);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleNewsletterSubscription($input) {
    $email = trim($input['email'] ?? '');

    // Initialize bot protection
    $botProtection = new BotProtection();

    // Bot protection validation
    $postData = ['email' => $email];
    $botValidation = $botProtection->validateSubmission('newsletter', $postData);

    if (!$botValidation['valid']) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Security validation failed. Please try again later.']);
        // Log suspicious activity
        $botProtection->logSuspiciousActivity('newsletter_signup', $postData, $botValidation['errors']);
        return;
    }

    // Validate email
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Load existing newsletter subscribers
    $newsletter_file = '../data/newsletter.json';
    $subscribers = [];
    
    if (file_exists($newsletter_file)) {
        $content = file_get_contents($newsletter_file);
        $subscribers = json_decode($content, true) ?: [];
    }
    
    // Check if email already exists
    foreach ($subscribers as $subscriber) {
        if (strtolower($subscriber['email']) === strtolower($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is already subscribed']);
            return;
        }
    }
    
    // Add new subscriber
    $newSubscriber = [
        'id' => count($subscribers) + 1,
        'email' => $email,
        'subscribed_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'active' => true
    ];
    
    $subscribers[] = $newSubscriber;
    
    // Save to file
    $json = json_encode($subscribers, JSON_PRETTY_PRINT);
    
    // Create data directory if it doesn't exist
    $data_dir = dirname($newsletter_file);
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    if (file_put_contents($newsletter_file, $json) !== false) {
        // Send confirmation email
        $emailSent = false;
        if (function_exists('sendNewsletterConfirmation')) {
            $emailSent = sendNewsletterConfirmation($email);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully subscribed to newsletter',
            'subscriber_id' => $newSubscriber['id'],
            'confirmation_email_sent' => $emailSent
        ]);
        
        // Log if email sending failed
        if (!$emailSent) {
            error_log("Failed to send newsletter confirmation email to: $email");
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save subscription']);
    }
}
?>