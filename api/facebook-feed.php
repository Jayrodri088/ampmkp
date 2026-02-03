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
// Strict GBP: Meta requires item currency to match shopfront dominant currency. No other currency.
$fbCurrency = 'GBP';
// Meta requires absolute URLs for link and image_link. Prefer env, else build from request.
$baseUrl = $env['SITE_BASE_URL'] ?? '';
if ($baseUrl === '' && !empty($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . getBasePath();
}
$baseUrl = rtrim($baseUrl, '/');
// Meta requires absolute URLs (https://...) for link and image_link. Relative URLs cause "missing" errors.
if ($baseUrl === '' || strpos($baseUrl, 'http') !== 0) {
    $baseUrl = $env['SITE_BASE_URL'] ?? '';
    $baseUrl = rtrim($baseUrl, '/');
}
$hasAbsoluteBase = (strpos($baseUrl, 'http') === 0);

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

// Build Facebook-compatible category path map (Google product category taxonomy)
// Meta requires a valid path; unknown categories fall back to Home & Garden > Home Decor
$googleCategories = [
    'Apparel & Accessories' => 'Apparel & Accessories > Clothing',
    'Apparel' => 'Apparel & Accessories > Clothing',
    'Clothing' => 'Apparel & Accessories > Clothing',
    'Books' => 'Media > Books',
    'Gifts' => 'Home & Garden > Home Decor',
    'Music' => 'Media > Music',
    'Christian' => 'Books > Books > Religious & Spirituality',
    'Household' => 'Home & Garden > Home Decor',
    'Home' => 'Home & Garden > Home Decor',
    'Jewellery' => 'Apparel & Accessories > Jewelry',
    'Jewelry' => 'Apparel & Accessories > Jewelry',
    'Accessories' => 'Apparel & Accessories',
    'Footwear' => 'Apparel & Accessories > Shoes',
    'Kids' => 'Apparel & Accessories > Clothing > Kids',
    'Kiddies' => 'Apparel & Accessories > Clothing > Kids',
];

// Output TSV header
// Required fields: id, title, description, availability, condition, price, link, image_link, brand
echo "id\ttitle\tdescription\tavailability\tcondition\tprice\tlink\timage_link\tbrand\tmpn\tgoogle_product_category\n";

// Generate feed entries (only when we have absolute base URL so Meta doesn't report "missing" link/image)
$settings = getSettings();
$defaultBrand = $settings['site_name'] ?? 'Angel Marketplace';
foreach ($products as $product) {
    if (!$product['active']) {
        continue;
    }
    if (!$hasAbsoluteBase) {
        continue; // skip rows when base URL is not absolute to avoid Meta "missing link/image" errors
    }

    $id = $product['id'];
    $title = cleanFeedText($product['name']);
    $description = !empty($product['description'])
        ? cleanFeedText($product['description'])
        : substr($title, 0, 5000);
    $availability = ($product['stock'] > 0) ? 'in stock' : 'out of stock';
    $condition = 'new';

    // Only GBP; no fallback to other currencies (avoids "item currency and shopfront dominant currency mismatch").
    $priceNum = isset($product['prices']['GBP']) ? (float) $product['prices']['GBP'] : 0;
    $price = number_format($priceNum, 2, '.', '') . ' ' . $fbCurrency;

    $productSlug = $product['slug'] ?? $product['id'];
    $link = $baseUrl . '/product.php?slug=' . urlencode($productSlug);

    $imagePath = isset($product['image']) && trim((string) $product['image']) !== ''
        ? trim(str_replace('\\', '/', $product['image']))
        : 'products/placeholder.jpg';
    $imagePath = ltrim($imagePath, '/');
    $imageLink = $baseUrl . '/assets/images/' . $imagePath;

    $brand = $defaultBrand;
    $mpn = 'AMP-' . str_pad((string) $product['id'], 6, '0', STR_PAD_LEFT);

    $categoryName = $categoryMap[$product['category_id']]['name'] ?? '';
    $googleCategory = isset($googleCategories[$categoryName]) && $googleCategories[$categoryName] !== ''
        ? $googleCategories[$categoryName]
        : 'Home & Garden > Home Decor';
    $googleCategory = trim($googleCategory);

    if ($link === '' || $imageLink === '' || $googleCategory === '') {
        continue;
    }

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
