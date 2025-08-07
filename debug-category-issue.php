<?php
require_once 'includes/functions.php';

// Replicate the exact flow from category.php
echo "<h1>Debug Category Issue</h1>";
echo "<h2>URL: " . $_SERVER['REQUEST_URI'] . "</h2>";

// Get category slug from URL (same as category.php)
$slug = $_GET['slug'] ?? '';
echo "<p><strong>Received slug:</strong> '$slug'</p>";

if (empty($slug)) {
    echo "<p><strong>ERROR:</strong> Slug is empty!</p>";
    exit;
}

// Get category details (same as category.php)
$category = getCategoryBySlug($slug);
echo "<p><strong>Category lookup result:</strong></p>";
if ($category) {
    echo "<pre>" . print_r($category, true) . "</pre>";
} else {
    echo "<p>NULL - Category not found!</p>";
    exit;
}

// Get products for this category (same as category.php)
$products = getProducts($category['id']);
echo "<p><strong>Products in category {$category['id']} ({$category['name']}):</strong></p>";
echo "<pre>" . print_r($products, true) . "</pre>";

// Let's also check what each product's category resolves to
echo "<h3>Product category verification:</h3>";
foreach ($products as $product) {
    $productCategory = getCategoryById($product['category_id']);
    echo "<p>Product '{$product['name']}' has category_id {$product['category_id']} which resolves to: " . 
         ($productCategory ? "'{$productCategory['name']}'" : "NULL") . "</p>";
}

// Also test the opposite - what category does ID 1 resolve to?
echo "<h3>Category ID verification:</h3>";
for ($i = 1; $i <= 6; $i++) {
    $cat = getCategoryById($i);
    echo "<p>Category ID $i resolves to: " . ($cat ? "'{$cat['name']}' (slug: {$cat['slug']})" : "NULL") . "</p>";
}
?> 