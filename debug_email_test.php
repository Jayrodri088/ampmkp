<?php
require_once 'includes/bot_protection.php';

$botProtection = new BotProtection();

// Test the specific email that got through
$testEmail = 'mayo@imfger.co';
$testData = [
    'email' => $testEmail,
    'name' => 'mayo',
    'message' => 'vkvjnwbvljebwljrv,nwrvlj,nervlwjrnvwj,vnwrvjlw rvwejnvl this is a tesst'
];

echo "<h2>Testing email: $testEmail</h2>\n";
echo "<pre>\n";

$result = $botProtection->validateSubmission('contact', $testData);

echo "Email: $testEmail\n";
echo "Valid: " . ($result['valid'] ? 'YES' : 'NO') . "\n";
echo "Errors: " . implode(', ', $result['errors']) . "\n";

// Let's also check what domain this is
$domain = strtolower(substr(strrchr($testEmail, "@"), 1));
echo "Domain: $domain\n";

// Check if it matches any suspicious patterns
$suspiciousPatterns = [
    '/^[a-z]+\d+@/',  // letters followed by numbers
    '/^\d+[a-z]+@/',  // numbers followed by letters
    '/^[a-z]{1,3}@/', // very short usernames
    '/\+.*\+/',       // multiple plus signs
    '/\.{2,}/',       // multiple dots
    '/@.*\.tk$/',     // .tk domains
    '/@.*\.ml$/',     // .ml domains
    '/@.*\.ga$/',     // .ga domains
    '/@.*\.cf$/',     // .cf domains
    '/temp.*mail/',   // temporary email services
    '/10.*minute/',   // 10 minute mail
    '/guerrilla/',    // guerrilla mail
    '/mailinator/',   // mailinator
    '/zaim-fin\.com$/', // specific domain mentioned by user
];

echo "\nPattern matching results:\n";
foreach ($suspiciousPatterns as $i => $pattern) {
    $matches = preg_match($pattern, strtolower($testEmail));
    echo "Pattern $i ($pattern): " . ($matches ? 'MATCHES' : 'no match') . "\n";
}

echo "\nMessage analysis:\n";
$message = $testData['message'];
echo "Message length: " . strlen($message) . "\n";
echo "Contains random characters: " . (preg_match('/[a-z]{10,}/', $message) ? 'YES' : 'NO') . "\n";
echo "Gibberish ratio: " . (strlen(preg_replace('/[aeiou\s]/', '', $message)) / strlen($message)) . "\n";

echo "</pre>\n";
?>
