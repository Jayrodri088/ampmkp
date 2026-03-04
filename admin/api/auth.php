<?php
/**
 * Mobile Admin API - Authentication Endpoint
 * Supports RBAC (email + password) and legacy password-only auth
 */

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/admin_functions.php';

$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $eq = strpos($line, '=');
        if ($eq !== false) {
            $name = trim(substr($line, 0, $eq));
            $value = trim(substr($line, $eq + 1));
            if ($name !== '' && $value !== '') {
                putenv($name . '=' . $value);
            }
        }
    }
}

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isRequestHttps() ? 1 : 0);
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'message' => 'Only POST requests are allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$adminType = trim($data['admin_type'] ?? 'mobile');

if (empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'validation_error', 'message' => 'Password is required']);
    exit;
}

$authenticated = false;
$role = null;
$permissions = [];
$adminId = null;
$adminName = null;
$adminEmail = null;

// 1) RBAC: email + password (same as web admin)
if ($email !== '' && Database::isMySQLEnabled()) {
    try {
        $hasAdminsTable = Database::fetchOne("SHOW TABLES LIKE 'admins'") !== null;
        if ($hasAdminsTable) {
            $admin = verifyAdminCredentials($email, $password);
            if ($admin) {
                $authenticated = true;
                $adminId = $admin['id'];
                $adminName = $admin['name'];
                $adminEmail = $admin['email'];
                $role = $admin['role_slug'];
                $permissions = is_string($admin['permissions'] ?? null)
                    ? (json_decode($admin['permissions'], true) ?? [])
                    : ($admin['permissions'] ?? []);
                if ($role === 'super_admin') {
                    $permissions['is_super'] = true;
                } else {
                    $permissions['is_super'] = false;
                }
            }
        }
    } catch (Exception $e) {
        // fall through to legacy auth
    }
}

// 2) Legacy: password-only from .env (fallback)
if (!$authenticated) {
    $ADMIN_PASSWORDS = [
        'web' => getenv('WEBSITE_ADMIN_PASSWORD') ?: getenv('JOURNAL_DASHBOARD_PASSWORD') ?: 'amp@2026',
        'mobile' => getenv('MOBILE_ADMIN_PASSWORD') ?: 'amp@2026',
        'super' => getenv('SUPER_ADMIN_PASSWORD') ?: 'Amp@2026!Super',
    ];

    if ($password === $ADMIN_PASSWORDS['super']) {
        $authenticated = true;
        $role = 'super_admin';
        $adminId = 1;
        $adminName = 'Administrator';
        $adminEmail = '';
        $permissions = [
            'is_super' => true,
            'web' => [
                'products' => true, 'categories' => true, 'orders' => true, 'customers' => true,
                'vendors' => true, 'ads' => true, 'settings' => true
            ],
            'mobile' => [
                'products' => true, 'categories' => true, 'orders' => true, 'customers' => true,
                'vendors' => true, 'ads' => true, 'settings' => true, 'api_access' => true
            ]
        ];
    } elseif ($password === $ADMIN_PASSWORDS['web']) {
        $authenticated = true;
        $role = 'web_admin';
        $adminId = 1;
        $adminName = 'Administrator';
        $adminEmail = '';
        $permissions = [
            'is_super' => false,
            'web' => [
                'products' => true, 'categories' => true, 'orders' => true, 'customers' => true,
                'vendors' => true, 'ads' => true, 'settings' => true
            ],
            'mobile' => [
                'products' => false, 'categories' => false, 'orders' => false, 'customers' => false,
                'vendors' => false, 'ads' => false, 'settings' => false, 'api_access' => false
            ]
        ];
    } elseif ($password === $ADMIN_PASSWORDS['mobile']) {
        $authenticated = true;
        $role = 'mobile_admin';
        $adminId = 1;
        $adminName = 'Administrator';
        $adminEmail = '';
        $permissions = [
            'is_super' => false,
            'web' => [
                'products' => false, 'categories' => false, 'orders' => false, 'customers' => false,
                'vendors' => false, 'ads' => false, 'settings' => false
            ],
            'mobile' => [
                'products' => true, 'categories' => true, 'orders' => true, 'customers' => true,
                'vendors' => true, 'ads' => true, 'settings' => true, 'api_access' => true
            ]
        ];
    }
}

if (!$authenticated) {
    http_response_code(401);
    echo json_encode([
        'error' => 'authentication_failed',
        'message' => $email !== '' ? 'Invalid email or password.' : 'Invalid password.'
    ]);
    exit;
}

$isSuper = ($role === 'super_admin') || ($permissions['is_super'] ?? false);
$hasMobileAccess = $isSuper || ($permissions['mobile']['api_access'] ?? false);

if (!$hasMobileAccess) {
    http_response_code(403);
    echo json_encode([
        'error' => 'access_denied',
        'message' => 'You do not have permission to access the mobile admin API'
    ]);
    exit;
}

$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = $adminId ?? 1;
$_SESSION['admin_name'] = $adminName ?? 'Administrator';
$_SESSION['admin_email'] = $adminEmail ?? '';
$_SESSION['admin_role'] = $permissions['is_super'] ? 'Super Admin' : ($role === 'mobile_admin' ? 'Mobile Admin' : 'Website Admin');
$_SESSION['admin_role_slug'] = $role;
$_SESSION['admin_permissions'] = $permissions;
$_SESSION['login_time'] = time();
$_SESSION['is_mobile_api'] = true;

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'data' => [
        'admin' => [
            'id' => $_SESSION['admin_id'],
            'name' => $_SESSION['admin_name'],
            'email' => $_SESSION['admin_email'],
            'role' => $_SESSION['admin_role'],
            'role_slug' => $role
        ],
        'permissions' => [
            'is_super' => $isSuper,
            'web' => $permissions['web'] ?? [],
            'mobile' => $permissions['mobile'] ?? []
        ],
        'session_id' => session_id()
    ]
], JSON_PRETTY_PRINT);
