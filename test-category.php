<?php
require_once 'includes/functions.php';

// Get the slug from URL
$slug = $_GET['slug'] ?? 'apparels';

echo "<!DOCTYPE html>";
echo "<html><head><title>Category Test</title></head><body>";
echo "<h1>Category Test Page</h1>";
echo "<p><strong>Testing slug:</strong> $slug</p>";

// Test the lookup
$category = getCategoryBySlug($slug);
if ($category) {
    echo "<p><strong>Found category:</strong> {$category['name']} (ID: {$category['id']})</p>";
    echo "<p><strong>Category slug:</strong> {$category['slug']}</p>";
    
    // Get products
    $products = getProducts($category['id']);
    echo "<p><strong>Products in category:</strong> " . count($products) . "</p>";
    
    if (!empty($products)) {
        echo "<ul>";
        foreach ($products as $product) {
            echo "<li>{$product['name']} (Category ID: {$product['category_id']})</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'><strong>ERROR:</strong> Category not found for slug: $slug</p>";
}

echo "<hr>";
echo "<p><strong>All available categories:</strong></p>";
echo "<ul>";
$categories = getCategories();
foreach ($categories as $cat) {
    $testUrl = "test-category.php?slug=" . urlencode($cat['slug']);
    echo "<li><a href='$testUrl'>{$cat['name']} ({$cat['slug']})</a></li>";
}
echo "</ul>";

echo "</body></html>";
?> 