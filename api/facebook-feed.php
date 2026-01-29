<?php
/**
 * Facebook/Instagram Product Feed Generator
 * Generates a TSV (Tab-Separated Values) feed for Meta Commerce Manager
 * 
 * Usage: Access this file directly or schedule a cron job to update it
 * The feed will be used by Meta Commerce Manager to sync products to Instagram Shop
 */

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="facebook_products.txt"');

require_once __DIR__ . '/../includes/functions.php';

$env = loadEnvFile(__DIR__ . '/../.env');
$fbCurrency = $env['FACEBOOK_FEED_CURRENCY'] ?? 'GBP';
$baseUrl = getBaseUrl();

// Get all active products and categories
$products = readJsonFile('products.json');
$categories = getAllCategories(); // Include inactive for proper mapping
$categories = is_array($categories) ? $categories : [];

// Build category mapping
$categoryMap = [];
foreach ($categories as $cat) {
    $categoryMap[$cat['id']] = [
        'name' => $cat['name'],
        'path' => getCategoryPath($cat['id'], ' > ')
    ];
}

// Build Facebook-compatible category path map
// Google Product Categories for Facebook
$googleCategories = [
    // Add mappings for your specific categories
    'Apparel & Accessories' => 'Apparel & Accessories > Clothing',
    'Books' => 'Media > Books',
    'Gifts' => 'Home & Garden > Home Decor',
    'Music' => 'Media > Music',
    'Christian' => 'Books > Books > Religious & Spirituality'
];

// Output TSV header
// Required fields: id, title, description, availability, condition, price, link, image_link, brand
echo "id\ttitle\tdescription\tavailability\tcondition\tprice\tlink\timage_link\tbrand\tmpn\tgoogle_product_category\n";

// Generate feed entries
foreach ($products as $product) {
    // Skip inactive products
    if (!$product['active']) {
        continue;
    }
    
    // Extract product data
    $id = $product['id'];
    $title = cleanFeedText($product['name']);
    
    // Use description or generate from title if not available
    $description = !empty($product['description']) 
        ? cleanFeedText($product['description']) 
        : substr($title, 0, 5000);
    
    // Determine availability
    $availability = ($product['stock'] > 0) ? 'in stock' : 'out of stock';
    
    // All products are new
    $condition = 'new';
    
    // Get price for Facebook currency (default to GBP if not available)
    $price = isset($product['prices'][$fbCurrency]) 
        ? $product['prices'][$fbCurrency] 
        : ($product['prices']['GBP'] ?? 0);
    
    $price = number_format($price, 2) . ' ' . $fbCurrency;
    
    // Generate URLs
    $productSlug = $product['slug'] ?? $product['id'];
    $link = $baseUrl . '/product.php?slug=' . urlencode($productSlug);
    
    // Image URL - use first image if multiple, or placeholder
    $imagePath = $product['image'] ?? 'products/placeholder.jpg';
    $imageLink = $baseUrl . '/assets/images/' . $imagePath;
    
    // Brand (use site name from settings)
    $settings = getSettings();
    $brand = $settings['site_name'] ?? 'Angel Marketplace';
    
    // MPN (Manufacturer Part Number) - use product ID as fallback
    $mpn = 'AMP-' . str_pad($product['id'], 6, '0', STR_PAD_LEFT);
    
    // Category path
    $categoryName = $categoryMap[$product['category_id']]['name'] ?? '';
    $googleCategory = $googleCategories[$categoryName] ?? 'Home & Garden > Home Decor';
    
    // Output TSV row
    echo implode("\t", [
        $id,
        $title,
        $description,
        $availability,
        $condition,
        $price,
        $link,
        $imageLink,
        $brand,
        $mpn,
        $googleCategory
    ]) . "\n";
}

/**
 * Clean text for Facebook feed
 * Remove special characters, limit length, escape tabs and newlines
 */
function cleanFeedText($text) {
    // Remove HTML tags
    $text = strip_tags($text);
    
    // Convert special characters to HTML entities
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Remove carriage returns and newlines
    $text = preg_replace('/[\r\n]+/', ' ', $text);
    
    // Replace multiple spaces with single space
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Trim whitespace
    $text = trim($text);
    
    // Limit length (Facebook recommends 5000 chars max)
    $text = mb_substr($text, 0, 5000);
    
    return $text;
}
?>
