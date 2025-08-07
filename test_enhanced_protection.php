<?php
/**
 * Test enhanced bot protection against real spam examples
 */

require_once 'includes/bot_protection.php';

echo "<h1>Enhanced Bot Protection Test</h1>\n";
echo "<pre>\n";

$botProtection = new BotProtection();

// Test 1: The email that got through before
echo "Test 1: Previous spam email (mayo@imfger.co)\n";
$testData1 = [
    'email' => 'mayo@imfger.co',
    'name' => 'mayo',
    'message' => 'vkvjnwbvljebwljrv,nwrvlj,nervlwjrnvwj,vnwrvjlw rvwejnvl this is a tesst'
];
$result1 = $botProtection->validateSubmission('contact', $testData1);
echo "Result: " . ($result1['valid'] ? 'PASS (BAD!)' : 'BLOCKED (GOOD!)') . "\n";
if (!$result1['valid']) {
    echo "Errors: " . implode(', ', $result1['errors']) . "\n";
}
echo "\n";

// Test 2: Russian spam message
echo "Test 2: Russian spam message\n";
$russianSpam = "Агрегатор.Топ – интегратор бизнеса. 
Рынок коммерческих контрактов - размещайте контракты, находите сделки. 
 
Для развития бизнеса на портале представлен автоматизированный подбор услуг страхования, кредитования, банковских гарантий и лизинга. 
Охватите более 70 банков одним кликом. 
 
Быстрая заявка в один клик – экономия времени и денег. 
Наш сайт https://aggregator.top/";

$testData2 = [
    'email' => 'spam@aggregator.top',
    'name' => 'Business',
    'message' => $russianSpam
];
$result2 = $botProtection->validateSubmission('contact', $testData2);
echo "Result: " . ($result2['valid'] ? 'PASS (BAD!)' : 'BLOCKED (GOOD!)') . "\n";
if (!$result2['valid']) {
    echo "Errors: " . implode(', ', $result2['errors']) . "\n";
}
echo "\n";

// Test 3: Message with URL
echo "Test 3: Message with suspicious URL\n";
$testData3 = [
    'email' => 'test@example.com',
    'name' => 'John',
    'message' => 'Check out this amazing business opportunity at https://scam-site.com/make-money-fast'
];
$result3 = $botProtection->validateSubmission('contact', $testData3);
echo "Result: " . ($result3['valid'] ? 'PASS (BAD!)' : 'BLOCKED (GOOD!)') . "\n";
if (!$result3['valid']) {
    echo "Errors: " . implode(', ', $result3['errors']) . "\n";
}
echo "\n";

// Test 4: Gibberish message
echo "Test 4: Gibberish message\n";
$testData4 = [
    'email' => 'test@example.com',
    'name' => 'Test',
    'message' => 'qwertylkjhgfdsamnbvcxzpoiuytrewqlkjhgfdsamnbvcxz'
];
$result4 = $botProtection->validateSubmission('contact', $testData4);
echo "Result: " . ($result4['valid'] ? 'PASS (BAD!)' : 'BLOCKED (GOOD!)') . "\n";
if (!$result4['valid']) {
    echo "Errors: " . implode(', ', $result4['errors']) . "\n";
}
echo "\n";

// Test 5: Legitimate message (should pass)
echo "Test 5: Legitimate message (should pass)\n";
$testData5 = [
    'email' => 'customer@gmail.com',
    'name' => 'John Smith',
    'message' => 'Hello, I am interested in your products. Could you please send me more information about pricing and availability? Thank you.'
];
$result5 = $botProtection->validateSubmission('contact', $testData5);
echo "Result: " . ($result5['valid'] ? 'PASS (GOOD!)' : 'BLOCKED (BAD!)') . "\n";
if (!$result5['valid']) {
    echo "Errors: " . implode(', ', $result5['errors']) . "\n";
}
echo "\n";

// Test 6: All suspicious domains
echo "Test 6: Testing all suspicious domains\n";
$suspiciousDomains = [
    'zaim-fin.com',
    'tempmail.org',
    '10minutemail.com',
    'guerrillamail.com',
    'mailinator.com',
    'yopmail.com',
    'temp-mail.org',
    'throwaway.email',
    'imfger.co',
    'aggregator.top',
    'business-portal.ru',
    'contract-market.com'
];

foreach ($suspiciousDomains as $domain) {
    $testData6 = [
        'email' => "test@$domain",
        'name' => 'Test',
        'message' => 'Test message'
    ];
    $result6 = $botProtection->validateSubmission('contact', $testData6);
    echo "Domain: $domain - " . ($result6['valid'] ? 'PASS (BAD!)' : 'BLOCKED (GOOD!)') . "\n";
}

echo "\n";
echo "Enhanced protection test completed!\n";
echo "All suspicious emails and content should be BLOCKED.\n";
echo "Only legitimate messages should PASS.\n";
echo "</pre>\n";
?>
