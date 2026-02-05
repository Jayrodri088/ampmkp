<?php
require_once 'includes/meta-integration.php';

$meta = new MetaIntegration();

// Test Lead event
$result = $meta->trackLead('test', [
    'email' => 'test@example.com',
    'first_name' => 'Test',
    'last_name' => 'User',
    'ip' => '127.0.0.1',
    'user_agent' => 'Local Test'
]);

echo '<pre>';
print_r($result);
echo '</pre>';