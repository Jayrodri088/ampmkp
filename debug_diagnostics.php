<?php
require_once 'includes/functions.php';

echo "<h1>Angel Marketplace - Debug Diagnostics</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

// Check critical directories
echo "<h2>1. Directory Structure Check</h2>";
$requiredDirs = [
    'logs',
    'assets',
    'assets/css',
    'assets/js',
    'assets/images',
    'assets/images/products',
    'assets/images/general',
    'data',
    'includes',
    'api'
];

foreach ($requiredDirs as $dir) {
    $exists = is_dir($dir);
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo "<p><strong>$dir/</strong> - $status</p>";
    
    if (!$exists) {
        debugLog("Missing directory: $dir");
    }
}

// Check critical files
echo "<h2>2. Critical Files Check</h2>";
$requiredFiles = [
    'assets/css/custom.css',
    'assets/js/main.js',
    'assets/js/cart.js',
    'assets/images/general/placeholder.jpg',
    'assets/images/general/favicon.ico',
    'data/products.json',
    'data/categories.json',
    'data/settings.json'
];

foreach ($requiredFiles as $file) {
    $exists = file_exists($file);
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    $size = $exists ? " (" . filesize($file) . " bytes)" : "";
    echo "<p><strong>$file</strong> - $status$size</p>";
    
    if (!$exists) {
        debugLog("Missing file: $file");
    }
}

// Check product images
echo "<h2>3. Product Images Check</h2>";
$products = readJsonFile('products.json');
$missingImages = [];

foreach ($products as $product) {
    $imagePath = 'assets/images/' . $product['image'];
    if (!file_exists($imagePath)) {
        $missingImages[] = [
            'product' => $product['name'],
            'expected_path' => $imagePath
        ];
    }
}

if (empty($missingImages)) {
    echo "<p>✅ All product images exist</p>";
} else {
    echo "<p>❌ Missing " . count($missingImages) . " product images:</p>";
    echo "<ul>";
    foreach ($missingImages as $missing) {
        echo "<li><strong>{$missing['product']}</strong> - {$missing['expected_path']}</li>";
        debugLog("Missing product image", $missing);
    }
    echo "</ul>";
}

// Check PHP configuration
echo "<h2>4. PHP Configuration Check</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Active" : "❌ Inactive") . "</p>";
echo "<p><strong>JSON Extension:</strong> " . (extension_loaded('json') ? "✅ Loaded" : "❌ Missing") . "</p>";
echo "<p><strong>File Uploads:</strong> " . (ini_get('file_uploads') ? "✅ Enabled" : "❌ Disabled") . "</p>";

// Check data integrity
echo "<h2>5. Data Integrity Check</h2>";
try {
    $products = readJsonFile('products.json');
    $categories = readJsonFile('categories.json');
    $settings = readJsonFile('settings.json');
    
    echo "<p><strong>Products:</strong> " . count($products) . " items loaded ✅</p>";
    echo "<p><strong>Categories:</strong> " . count($categories) . " items loaded ✅</p>";
    echo "<p><strong>Settings:</strong> " . (empty($settings) ? "❌ Empty or missing" : "✅ Loaded") . "</p>";
    
    // Check for orphaned products (products with invalid category_id)
    $categoryIds = array_column($categories, 'id');
    $orphanedProducts = [];
    
    foreach ($products as $product) {
        if (!in_array($product['category_id'], $categoryIds)) {
            $orphanedProducts[] = $product['name'];
        }
    }
    
    if (empty($orphanedProducts)) {
        echo "<p>✅ All products have valid categories</p>";
    } else {
        echo "<p>❌ Orphaned products (invalid category_id): " . implode(', ', $orphanedProducts) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error reading data files: " . $e->getMessage() . "</p>";
    debugLog("Data integrity error: " . $e->getMessage());
}

// Check cart functionality
echo "<h2>6. Cart Functionality Check</h2>";
try {
    session_start();
    $cartCount = getCartItemCount();
    echo "<p><strong>Cart Item Count:</strong> $cartCount ✅</p>";
    
    $cartTotal = getCartTotal();
    echo "<p><strong>Cart Total:</strong> " . formatPrice($cartTotal) . " ✅</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Cart functionality error: " . $e->getMessage() . "</p>";
    debugLog("Cart functionality error: " . $e->getMessage());
}

// Check for JavaScript conflicts
echo "<h2>7. Potential JavaScript Issues</h2>";
$jsConflicts = [];

// Check main.js for cart functions
$mainJsContent = file_exists('assets/js/main.js') ? file_get_contents('assets/js/main.js') : '';
$cartJsContent = file_exists('assets/js/cart.js') ? file_get_contents('assets/js/cart.js') : '';

if (strpos($mainJsContent, 'window.addToCart') !== false && strpos($cartJsContent, 'window.addToCart') !== false) {
    $jsConflicts[] = "addToCart function defined in both main.js and cart.js";
}

if (strpos($mainJsContent, 'updateCartCounter') !== false && strpos($cartJsContent, 'updateCartCounter') !== false) {
    $jsConflicts[] = "updateCartCounter function defined in both files";
}

if (empty($jsConflicts)) {
    echo "<p>✅ No obvious JavaScript conflicts detected</p>";
} else {
    echo "<p>❌ Potential JavaScript conflicts:</p>";
    echo "<ul>";
    foreach ($jsConflicts as $conflict) {
        echo "<li>$conflict</li>";
        debugLog("JavaScript conflict: $conflict");
    }
    echo "</ul>";
}

// Summary and recommendations
echo "<h2>8. Summary & Recommendations</h2>";
$logFile = 'logs/debug.log';
if (file_exists($logFile)) {
    $logContents = file_get_contents($logFile);
    $issueCount = substr_count($logContents, '[DEBUG]');
    echo "<p><strong>Total Issues Logged:</strong> $issueCount</p>";
    echo "<p><strong>Debug Log Location:</strong> $logFile</p>";
    
    if ($issueCount > 0) {
        echo "<h3>Immediate Actions Needed:</h3>";
        echo "<ol>";
        echo "<li>Create missing directories and files</li>";
        echo "<li>Add placeholder images for missing product images</li>";
        echo "<li>Resolve JavaScript function conflicts</li>";
        echo "<li>Verify all file permissions</li>";
        echo "</ol>";
    }
} else {
    echo "<p>✅ No issues detected or log file not created yet</p>";
}

echo "<p><em>Diagnostic completed. Check the debug log for detailed information.</em></p>";
?>