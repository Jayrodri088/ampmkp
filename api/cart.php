<?php
header('Content-Type: application/json');
// Restrict CORS to same-origin for safety (adjust if API is used cross-origin)
if (isset($_SERVER['HTTP_ORIGIN']) && strpos($_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_HOST']) !== false) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin: ' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/functions.php';

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            handleGetCart();
            break;
        case 'POST':
            // Basic CSRF protection for JSON POST: require same-origin and a session token if available
            if (session_status() == PHP_SESSION_NONE) { session_start(); }
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
            if (!empty($origin) && strpos($origin, $_SERVER['HTTP_HOST']) === false) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                return;
            }
            handleCartAction($input);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGetCart() {
    $cart = getCart();
    $settings = getSettings();
    // Determine selected currency
    $availableCurrencies = [];
    if (isset($settings['currencies']) && is_array($settings['currencies'])) {
        foreach ($settings['currencies'] as $curr) {
            $availableCurrencies[] = $curr['code'];
        }
    }
    $selectedCurrency = $_GET['currency'] ?? ($_SESSION['selected_currency'] ?? ($settings['currency_code'] ?? 'GBP'));
    if (!in_array($selectedCurrency, $availableCurrencies)) {
        $selectedCurrency = $settings['currency_code'] ?? 'GBP';
    }
    $cartItems = [];
    $total = 0;
    
    foreach ($cart as $item) {
        $product = getProductById($item['product_id']);
        if ($product) {
            $unitPrice = getProductPrice($product, $selectedCurrency);
            $itemTotal = $unitPrice * $item['quantity'];
            // Generate unique cart key for this item
            $cartKey = $product['id'] . 
                       (isset($item['size']) ? '_size_' . $item['size'] : '') . 
                       (isset($item['color']) ? '_color_' . $item['color'] : '');
            
            $cartItem = [
                'product_id' => $product['id'],
                'cart_key' => $cartKey, // Unique identifier for this cart item
                'name' => $product['name'],
                'slug' => $product['slug'],
                'price' => $unitPrice,
                'image' => $product['image'],
                'quantity' => $item['quantity'],
                'item_total' => $itemTotal,
                'stock' => $product['stock']
            ];
            
            // Add size if available
            if (isset($item['size']) && !empty($item['size'])) {
                $cartItem['size'] = $item['size'];
            }
            
            // Add color if available
            if (isset($item['color']) && !empty($item['color'])) {
                $cartItem['color'] = $item['color'];
            }
            
            $cartItems[] = $cartItem;
            $total += $itemTotal;
        }
    }
    
    echo json_encode([
        'success' => true,
        'cart' => $cartItems,
        'total' => $total,
        'count' => getCartItemCount(),
        'currency' => $selectedCurrency
    ]);
}

function handleCartAction($input) {
    if (!isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action is required']);
        return;
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'add':
            if (!isset($input['product_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                return;
            }
            
            $productId = intval($input['product_id']);
            $quantity = intval($input['quantity'] ?? 1);
            $options = $input['options'] ?? [];
            
            // Validate product exists
            $product = getProductById($productId);
            if (!$product) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                return;
            }
            
            // Check if size is required
            if (isset($product['has_sizes']) && $product['has_sizes'] && 
                isset($product['available_sizes']) && !empty($product['available_sizes'])) {
                if (!isset($options['size']) || empty($options['size'])) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Size selection is required for this product'
                    ]);
                    return;
                }
                
                // Validate selected size
                if (!in_array($options['size'], $product['available_sizes'])) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Invalid size selected'
                    ]);
                    return;
                }
            }
            
            // Check if color is required
            if (isset($product['has_colors']) && $product['has_colors'] && 
                isset($product['available_colors']) && !empty($product['available_colors'])) {
                if (!isset($options['color']) || empty($options['color'])) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Color selection is required for this product'
                    ]);
                    return;
                }
                
                // Validate selected color
                if (!in_array($options['color'], $product['available_colors'])) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Invalid color selected'
                    ]);
                    return;
                }
            }
            
            // Check stock (considering size and color as separate items)
            $currentCart = getCart();
            $currentQuantityInCart = 0;
            $cartKey = $productId . 
                       (isset($options['size']) ? '_size_' . $options['size'] : '') . 
                       (isset($options['color']) ? '_color_' . $options['color'] : '');
            
            foreach ($currentCart as $item) {
                $itemKey = $item['product_id'] . 
                          (isset($item['size']) ? '_size_' . $item['size'] : '') . 
                          (isset($item['color']) ? '_color_' . $item['color'] : '');
                if ($itemKey == $cartKey) {
                    $currentQuantityInCart = $item['quantity'];
                    break;
                }
            }
            
            if (($currentQuantityInCart + $quantity) > $product['stock']) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Not enough stock available. Available: ' . ($product['stock'] - $currentQuantityInCart)
                ]);
                return;
            }
            
            if (addToCart($productId, $quantity, $options)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added to cart',
                    'cart_count' => getCartItemCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
            }
            break;
            
        case 'update':
            if (!isset($input['product_id']) || !isset($input['quantity'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID and quantity are required']);
                return;
            }
            
            $productId = intval($input['product_id']);
            $quantity = intval($input['quantity']);
            
            if ($quantity < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Quantity must be non-negative']);
                return;
            }
            
            // Check stock if quantity > 0
            if ($quantity > 0) {
                $product = getProductById($productId);
                if (!$product) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Product not found']);
                    return;
                }
                
                if ($quantity > $product['stock']) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Not enough stock available. Available: ' . $product['stock']
                    ]);
                    return;
                }
            }
            
            if (updateCartQuantity($productId, $quantity)) {
                echo json_encode([
                    'success' => true,
                    'message' => $quantity > 0 ? 'Cart updated' : 'Product removed from cart',
                    'cart_count' => getCartItemCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
            }
            break;
            
        case 'remove':
            if (!isset($input['product_id']) && !isset($input['cart_key'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID or cart key is required']);
                return;
            }
            
            // Use cart_key if provided (for specific variant removal), otherwise fallback to product_id
            if (isset($input['cart_key']) && !empty($input['cart_key'])) {
                $result = removeFromCartByKey($input['cart_key']);
            } else {
                $productId = intval($input['product_id']);
                $result = removeFromCart($productId);
            }
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product removed from cart',
                    'cart_count' => getCartItemCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove product from cart']);
            }
            break;
            
        case 'clear':
            if (clearCart()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart cleared'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
?>