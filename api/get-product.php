<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/functions.php';

try {
    // Get product ID from URL parameter
    $productId = intval($_GET['id'] ?? 0);
    
    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    // Get product data
    $product = getProductById($productId);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Return product data
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 