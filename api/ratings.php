<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => ''];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'add_rating':
                $productId = (int)($input['product_id'] ?? 0);
                $rating = (int)($input['rating'] ?? 0);
                $review = trim($input['review'] ?? '');
                $reviewerName = trim($input['reviewer_name'] ?? '');
                $reviewerEmail = trim($input['reviewer_email'] ?? '');
                
                // Validation
                if ($productId <= 0) {
                    throw new Exception('Invalid product ID');
                }
                
                if ($rating < 1 || $rating > 5) {
                    throw new Exception('Rating must be between 1 and 5');
                }
                
                if (empty($review) || strlen($review) < 10) {
                    throw new Exception('Review must be at least 10 characters long');
                }
                
                if (empty($reviewerName)) {
                    throw new Exception('Name is required');
                }
                
                if (!validateEmail($reviewerEmail)) {
                    throw new Exception('Valid email is required');
                }
                
                // Check if product exists
                $product = getProductById($productId);
                if (!$product) {
                    throw new Exception('Product not found');
                }
                
                // Add the rating
                $newRating = addRating($productId, $rating, $review, $reviewerName, $reviewerEmail);
                
                if ($newRating) {
                    $response['success'] = true;
                    $response['message'] = 'Rating submitted successfully';
                    $response['rating'] = $newRating;
                    $response['stats'] = getProductRatingStats($productId);
                } else {
                    throw new Exception('Failed to save rating');
                }
                break;
                
            case 'get_stats':
                $productId = (int)($input['product_id'] ?? 0);
                
                if ($productId <= 0) {
                    throw new Exception('Invalid product ID');
                }
                
                $stats = getProductRatingStats($productId);
                $ratings = getProductRatings($productId);
                
                $response['success'] = true;
                $response['stats'] = $stats;
                $response['ratings'] = array_reverse($ratings); // Most recent first
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } elseif ($method === 'GET') {
        $productId = (int)($_GET['product_id'] ?? 0);
        
        if ($productId <= 0) {
            throw new Exception('Invalid product ID');
        }
        
        $stats = getProductRatingStats($productId);
        $ratings = getProductRatings($productId);
        
        $response['success'] = true;
        $response['stats'] = $stats;
        $response['ratings'] = array_reverse($ratings); // Most recent first
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error for debugging
    error_log("Ratings API Error: " . $e->getMessage());
}

echo json_encode($response);
?> 