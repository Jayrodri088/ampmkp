<?php
// Ensure no PHP notices/warnings break JSON output
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');
if (isset($_SERVER['HTTP_ORIGIN']) && strpos($_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_HOST']) !== false) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin: ' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
}
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail_config.php';
require_once __DIR__ . '/../includes/bot_protection.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($origin) && strpos($origin, $_SERVER['HTTP_HOST']) === false) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';

switch ($action) {
    case 'request_code':
        handleRequestCode($input);
        break;
    case 'verify_code':
        handleVerifyCode($input);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleRequestCode($input) {
    $email = trim($input['email'] ?? '');
    if ($email === '') {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }

    $botProtection = new BotProtection();
    $botValidation = $botProtection->validateSubmission('customer_login', ['email' => $email]);
    if (!$botValidation['valid']) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many attempts or security check failed. Please try again later.']);
        $botProtection->logSuspiciousActivity('customer_login_request', ['email' => $email], $botValidation['errors']);
        return;
    }

    $account = getAccountByEmail($email);
    if (!$account) {
        $account = createAccount($email);
        if (!$account) {
            echo json_encode(['success' => false, 'message' => 'Could not create account. Please try again.']);
            return;
        }
    }

    $code = createEmailCode($email);
    if ($code === false) {
        echo json_encode(['success' => false, 'message' => 'Could not generate code. Please try again.']);
        return;
    }

    $sent = sendLoginCode($email, $code);
    if (!$sent) {
        if (function_exists('logError')) {
            logError('Login code email failed: ' . $email);
        }
        echo json_encode([
            'success' => false,
            'message' => 'We couldn\'t send the code to that email. Check the address or try again later.'
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Check your email for the login code. It expires in 15 minutes.'
    ]);
}

function handleVerifyCode($input) {
    $email = trim($input['email'] ?? '');
    $code = trim($input['code'] ?? '');
    if ($email === '' || $code === '') {
        echo json_encode(['success' => false, 'message' => 'Email and code are required']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }

    if (!validateAndConsumeEmailCode($email, $code)) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired code. Request a new one.']);
        return;
    }

    $account = getAccountByEmail($email);
    if (!$account) {
        $account = createAccount($email);
    }
    if (!$account) {
        echo json_encode(['success' => false, 'message' => 'Account error. Please try again.']);
        return;
    }

    updateAccountLastLogin($email);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['customer_id'] = (int) $account['id'];
    $_SESSION['customer_email'] = $account['email'];
    $_SESSION['customer_last_activity'] = time();

    echo json_encode([
        'success' => true,
        'message' => 'You are signed in.',
        'customer_id' => (int) $account['id'],
        'email' => $account['email']
    ]);
}
