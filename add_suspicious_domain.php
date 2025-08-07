<?php
/**
 * Helper script to add suspicious domains to the bot protection system
 * Usage: php add_suspicious_domain.php domain1.com domain2.com domain3.com
 */

if ($argc < 2) {
    echo "Usage: php add_suspicious_domain.php domain1.com domain2.com ...\n";
    echo "Example: php add_suspicious_domain.php spam-site.com fake-email.org\n";
    exit(1);
}

$botProtectionFile = 'includes/bot_protection.php';

if (!file_exists($botProtectionFile)) {
    echo "Error: Bot protection file not found at $botProtectionFile\n";
    exit(1);
}

// Read the current file
$content = file_get_contents($botProtectionFile);

// Get domains to add from command line arguments
$domainsToAdd = array_slice($argv, 1);

echo "Adding domains to bot protection:\n";
foreach ($domainsToAdd as $domain) {
    echo "- $domain\n";
    
    // Check if domain already exists
    if (strpos($content, "'$domain'") !== false) {
        echo "  (already exists, skipping)\n";
        continue;
    }
    
    // Add the domain to the suspicious domains array
    $pattern = "/('contract-market\.com')/";
    $replacement = "$1,\n        '$domain'";
    $content = preg_replace($pattern, $replacement, $content);
    
    echo "  (added)\n";
}

// Write the updated content back to the file
if (file_put_contents($botProtectionFile, $content)) {
    echo "\nSuccessfully updated bot protection file!\n";
    echo "New domains have been added to the suspicious domains list.\n";
} else {
    echo "\nError: Could not write to bot protection file!\n";
    exit(1);
}

echo "\nTo test the new domains, run: php test_enhanced_protection.php\n";
?>
