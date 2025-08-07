<?php
/**
 * Test script for bot protection system
 * Run this to verify that the bot protection is working correctly
 */

require_once 'includes/bot_protection.php';

echo "<h1>Bot Protection Test</h1>\n";
echo "<pre>\n";

$botProtection = new BotProtection();

// Test 1: Valid email
echo "Test 1: Valid email (should pass)\n";
$testData1 = ['email' => 'test@example.com'];
$result1 = $botProtection->validateSubmission('test', $testData1);
echo "Result: " . ($result1['valid'] ? 'PASS' : 'FAIL') . "\n";
if (!$result1['valid']) {
    echo "Errors: " . implode(', ', $result1['errors']) . "\n";
}
echo "\n";

// Test 2: Suspicious email (zaim-fin.com)
echo "Test 2: Suspicious email zaim-fin.com (should fail)\n";
$testData2 = ['email' => 'test@zaim-fin.com'];
$result2 = $botProtection->validateSubmission('test', $testData2);
echo "Result: " . ($result2['valid'] ? 'PASS' : 'FAIL') . "\n";
if (!$result2['valid']) {
    echo "Errors: " . implode(', ', $result2['errors']) . "\n";
}
echo "\n";

// Test 3: Temporary email service
echo "Test 3: Temporary email service (should fail)\n";
$testData3 = ['email' => 'test@tempmail.org'];
$result3 = $botProtection->validateSubmission('test', $testData3);
echo "Result: " . ($result3['valid'] ? 'PASS' : 'FAIL') . "\n";
if (!$result3['valid']) {
    echo "Errors: " . implode(', ', $result3['errors']) . "\n";
}
echo "\n";

// Test 4: Honeypot field filled
echo "Test 4: Honeypot field filled (should fail)\n";
$testData4 = ['email' => 'test@example.com', 'website' => 'spam.com'];
$result4 = $botProtection->validateSubmission('test', $testData4);
echo "Result: " . ($result4['valid'] ? 'PASS' : 'FAIL') . "\n";
if (!$result4['valid']) {
    echo "Errors: " . implode(', ', $result4['errors']) . "\n";
}
echo "\n";

// Test 5: Rate limiting (simulate multiple submissions)
echo "Test 5: Rate limiting test\n";
$testEmail = 'ratelimit@example.com';
for ($i = 1; $i <= 3; $i++) {
    $testData5 = ['email' => $testEmail];
    $result5 = $botProtection->validateSubmission('test', $testData5);
    echo "Submission $i: " . ($result5['valid'] ? 'PASS' : 'FAIL');
    if (!$result5['valid']) {
        echo " (Errors: " . implode(', ', $result5['errors']) . ")";
    }
    echo "\n";
}
echo "\n";

// Test 6: Suspicious email patterns
echo "Test 6: Suspicious email patterns\n";
$suspiciousEmails = [
    'abc123@example.com',  // letters + numbers
    '123abc@example.com',  // numbers + letters
    'a@example.com',       // very short username
    'test@example.tk',     // .tk domain
];

foreach ($suspiciousEmails as $email) {
    $testData6 = ['email' => $email];
    $result6 = $botProtection->validateSubmission('test', $testData6);
    echo "Email: $email - " . ($result6['valid'] ? 'PASS' : 'FAIL');
    if (!$result6['valid']) {
        echo " (Errors: " . implode(', ', $result6['errors']) . ")";
    }
    echo "\n";
}

echo "\n";
echo "Test completed! Check the admin panel at /admin/bot-protection-logs.php to see the logs.\n";
echo "</pre>\n";
?>
