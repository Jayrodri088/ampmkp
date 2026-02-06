<?php
/**
 * Search API Endpoint
 * Returns product search results as JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../includes/functions.php';

// Get search parameters
$query = trim($_GET['q'] ?? '');
$limit = min(50, max(1, intval($_GET['limit'] ?? 8)));
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;

// Validate query
if (empty($query)) {
    echo json_encode([
        'success' => false,
        'error' => 'Search query is required',
        'results' => []
    ]);
    exit;
}

// Minimum query length
if (strlen($query) < 2) {
    echo json_encode([
        'success' => false,
        'error' => 'Search query must be at least 2 characters',
        'results' => []
    ]);
    exit;
}

try {
    // Use the existing searchProducts function
    $products = searchProducts($query, $categoryId);

    // Limit results
    $products = array_slice($products, 0, $limit);

    // Format results for frontend
    $results = array_map(function($product) {
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'slug' => $product['slug'],
            'price' => $product['price'],
            'image' => getAssetUrl('images/' . $product['image']),
            'url' => getBaseUrl('product.php?slug=' . $product['slug']),
            'category' => $product['category_id'] ?? null,
            'featured' => $product['featured'] ?? false,
            'in_stock' => ($product['stock'] ?? 0) > 0
        ];
    }, $products);

    echo json_encode([
        'success' => true,
        'query' => $query,
        'count' => count($results),
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Search failed',
        'results' => []
    ]);
}
