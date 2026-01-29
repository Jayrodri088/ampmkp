<?php
/**
 * Debug script: sends a test ViewContent event to Meta Conversions API
 * and prints the full response. Use this to see why server events don't appear in Events Manager.
 *
 * Run: http://localhost/ampmkp/test-conversions-api-debug.php
 * Then check the output for success/failure and any error message from Meta.
 */
require_once 'includes/functions.php';
require_once 'includes/meta-integration.php';

// Simulate a product view
$productId = 1;
$productName = 'Test Product';
$price = 29.99;
$category = 'Test Category';
$eventId = MetaIntegration::generateEventId('ViewContent', [$productId]);

$meta = new MetaIntegration();

$result = $meta->trackViewContent(
    $productId,
    $productName,
    $price,
    $category,
    [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'DebugScript/1.0',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ],
    $eventId
);

header('Content-Type: text/plain; charset=utf-8');
echo "=== Meta Conversions API – Debug Output ===\n\n";
echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
if (isset($result['http_code'])) {
    echo "HTTP Code: " . $result['http_code'] . "\n";
}
if (isset($result['error'])) {
    echo "Error: " . $result['error'] . "\n";
}
if (isset($result['response'])) {
    echo "\nFull API response:\n";
    print_r($result['response']);
}
echo "\n=== If success=NO or HTTP Code is not 200, check:\n";
echo "1. FACEBOOK_ACCESS_TOKEN in .env – token may be expired (generate a new one in Graph API Explorer).\n";
echo "2. Token must have permission: ads_management OR pages_manage_metadata (for Conversions API).\n";
echo "3. Use a Page Access Token or System User token, not a short-lived User token.\n";
echo "4. In Events Manager: open your Pixel → Test Events → use test_event_code TEST12345 (set FACEBOOK_CONVERSIONS_TEST_MODE=true in .env).\n";
