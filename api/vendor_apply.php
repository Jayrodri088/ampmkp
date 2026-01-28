<?php
// Handle vendor application submissions
require_once __DIR__ . '/../includes/functions.php';

// Ensure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// CSRF validation
$postedToken = $_POST['csrf_token'] ?? '';
if (empty($postedToken) || !isset($_SESSION['public_csrf_token']) || !hash_equals($_SESSION['public_csrf_token'], $postedToken)) {
    http_response_code(403);
    echo 'Invalid CSRF token';
    exit;
}

// Helper to safely fetch string
function post_str($key) {
    return isset($_POST[$key]) ? sanitizeInput($_POST[$key]) : '';
}

// Build vendor application payload
$vendor = [
    'id' => 0, // set later
    'status' => 'pending',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    'business' => [
        'name' => post_str('businessName'),
        'type' => post_str('businessType'),
        'description' => post_str('businessDescription'),
        'website' => post_str('businessWebsite'),
        'license' => post_str('businessLicense'),
        'tax_id' => post_str('taxId'),
    ],
    'contact' => [
        'name' => post_str('contactName'),
        'email' => post_str('contactEmail'),
        'phone' => trim(post_str('countryCode') . ' ' . post_str('contactPhone')),
    ],
    'consents' => [
        'termsAccepted' => isset($_POST['termsAccepted']) ? 1 : 0,
        'privacyAccepted' => isset($_POST['privacyAccepted']) ? 1 : 0,
        'marketingConsent' => isset($_POST['marketingConsent']) ? 1 : 0,
    ],
    'products' => [],
    'uploads' => [],
];

// Basic server-side validation
$errors = [];
if ($vendor['business']['name'] === '') $errors[] = 'Business name is required';
if ($vendor['business']['type'] === '') $errors[] = 'Business type is required';
if ($vendor['business']['description'] === '') $errors[] = 'Business description is required';
if ($vendor['contact']['name'] === '') $errors[] = 'Contact name is required';
if ($vendor['contact']['email'] === '' || !validateEmail($vendor['contact']['email'])) $errors[] = 'Valid email is required';
if ($vendor['contact']['phone'] === '') $errors[] = 'Phone number is required';
if (!$vendor['consents']['termsAccepted'] || !$vendor['consents']['privacyAccepted']) $errors[] = 'You must accept Terms and Privacy Policy';

if (!empty($errors)) {
    // Redirect back with message
    $_SESSION['vendor_form_errors'] = $errors;
    header('Location: ' . getBaseUrl('vendors/index.php?error=1'));
    exit;
}

// Build products from parallel arrays
$names = $_POST['productNames'] ?? [];
$categories = $_POST['productCategories'] ?? [];
$customCategories = $_POST['customCategories'] ?? [];
$prices = $_POST['productPrices'] ?? [];
$stocks = $_POST['productStock'] ?? [];
$descriptions = $_POST['productDescriptions'] ?? [];

$count = max(count($names), count($categories), count($prices), count($stocks), count($descriptions));
for ($i = 0; $i < $count; $i++) {
    $cat = sanitizeInput($categories[$i] ?? '');
    $customCat = sanitizeInput($customCategories[$i] ?? '');
    $finalCategory = ($cat === 'other' && $customCat) ? strtolower($customCat) : $cat;

    $vendor['products'][] = [
        'name' => sanitizeInput($names[$i] ?? ''),
        'category' => $finalCategory,
        'price' => (float)($prices[$i] ?? 0),
        'stock' => (int)($stocks[$i] ?? 0),
        'description' => sanitizeInput($descriptions[$i] ?? ''),
    ];
}

// Handle images (store at vendor-level for now)
$uploadDir = __DIR__ . '/../assets/images/vendors/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!empty($_FILES['productImages']) && is_array($_FILES['productImages']['name'])) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    $fileCount = count($_FILES['productImages']['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        $error = $_FILES['productImages']['error'][$i];
        if ($error !== UPLOAD_ERR_OK) {
            continue;
        }
        $type = $_FILES['productImages']['type'][$i];
        $size = $_FILES['productImages']['size'][$i];
        $tmp = $_FILES['productImages']['tmp_name'][$i];
        $name = $_FILES['productImages']['name'][$i];

        if (!in_array($type, $allowedTypes)) {
            continue;
        }
        if ($size > $maxSize) {
            continue;
        }
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        if ($safeExt === '') { $safeExt = 'jpg'; }
        $filename = 'vendor_' . time() . '_' . mt_rand(1000, 9999) . '.' . $safeExt;
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($tmp, $dest)) {
            $vendor['uploads'][] = 'vendors/' . $filename;
        }
    }
}

// Persist to vendors.json
$vendors = readJsonFile('vendors.json');
$newId = 1;
if (!empty($vendors)) {
    $ids = array_column($vendors, 'id');
    $newId = (max($ids) + 1);
}
$vendor['id'] = $newId;
$vendors[] = $vendor;
writeJsonFile('vendors.json', $vendors);

// Success redirect back to step 4 view
header('Location: ' . getBaseUrl('vendors/index.php?success=1'));
exit;


