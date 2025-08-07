<?php

// JSON Database Helper Functions

function readJsonFile($filename) {
    $filepath = __DIR__ . '/../data/' . $filename;
    if (!file_exists($filepath)) {
        return [];
    }
    $content = file_get_contents($filepath);
    return json_decode($content, true) ?: [];
}

function writeJsonFile($filename, $data) {
    $filepath = __DIR__ . '/../data/' . $filename;
    $json = json_encode($data, JSON_PRETTY_PRINT);
    $fp = fopen($filepath, 'c+');
    if ($fp === false) {
        return false;
    }
    $ok = false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        rewind($fp);
        $ok = fwrite($fp, $json) !== false;
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return $ok;
}

function getSettings() {
    static $settings = null;
    if ($settings === null) {
        $settings = readJsonFile('settings.json');
    }
    return $settings;
}

function getCategories() {
    $categories = readJsonFile('categories.json');
    return array_filter($categories, function($cat) {
        return $cat['active'];
    });
}

function getProducts($categoryId = null, $featured = null, $limit = null) {
    $products = readJsonFile('products.json');
    
    // Filter active products
    $products = array_filter($products, function($product) {
        return $product['active'];
    });
    
    // Filter by category
    if ($categoryId !== null) {
        $products = array_filter($products, function($product) use ($categoryId) {
            return $product['category_id'] == $categoryId;
        });
    }
    
    // Filter by featured
    if ($featured !== null) {
        $products = array_filter($products, function($product) use ($featured) {
            return $product['featured'] == $featured;
        });
    }
    
    // Apply limit
    if ($limit !== null) {
        $products = array_slice($products, 0, $limit);
    }
    
    return array_values($products);
}

function getFeaturedProductsByRating($limit = null) {
    $products = readJsonFile('products.json');
    
    // Filter for active and featured products
    $products = array_filter($products, function($product) {
        return $product['active'] && $product['featured'];
    });
    
    // Add rating stats to each product for sorting
    $productsWithRatings = array_map(function($product) {
        $ratingStats = getProductRatingStats($product['id']);
        $product['rating_count'] = $ratingStats['count'];
        $product['rating_average'] = $ratingStats['average'];
        return $product;
    }, $products);
    
    // Sort by rating count (descending), then by average rating (descending) as tiebreaker
    usort($productsWithRatings, function($a, $b) {
        if ($a['rating_count'] == $b['rating_count']) {
            return $b['rating_average'] <=> $a['rating_average'];
        }
        return $b['rating_count'] <=> $a['rating_count'];
    });
    
    // Apply limit
    if ($limit !== null) {
        $productsWithRatings = array_slice($productsWithRatings, 0, $limit);
    }
    
    return array_values($productsWithRatings);
}

function getProductById($id) {
    $products = readJsonFile('products.json');
    foreach ($products as $product) {
        if ($product['id'] == $id && $product['active']) {
            return $product;
        }
    }
    return null;
}

function getProductBySlug($slug) {
    $products = readJsonFile('products.json');
    foreach ($products as $product) {
        if ($product['slug'] == $slug && $product['active']) {
            return $product;
        }
    }
    return null;
}

function getCategoryBySlug($slug) {
    $categories = getCategories();
    foreach ($categories as $category) {
        if ($category['slug'] === $slug) {
            return $category;
        }
    }
    return null;
}

function getCategoryById($id) {
    $categories = getCategories();
    foreach ($categories as $category) {
        if ($category['id'] == $id) {
            return $category;
        }
    }
    return null;
}

function searchProducts($query, $categoryId = null) {
    $products = readJsonFile('products.json');
    $query = strtolower(trim($query));
    
    if (empty($query)) {
        return [];
    }
    
    $results = array_filter($products, function($product) use ($query, $categoryId) {
        $match = $product['active'] && (
            strpos(strtolower($product['name']), $query) !== false ||
            strpos(strtolower($product['description']), $query) !== false
        );
        
        if ($match && $categoryId !== null) {
            $match = $product['category_id'] == $categoryId;
        }
        
        return $match;
    });
    
    return array_values($results);
}

function formatPrice($price) {
    $settings = getSettings();
    return $settings['currency_symbol'] . number_format($price, 2);
}

// Determine currently selected currency code from session with fallback to settings default
function getSelectedCurrency() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $settings = getSettings();
    $availableCurrencies = [];
    if (isset($settings['currencies']) && is_array($settings['currencies'])) {
        foreach ($settings['currencies'] as $curr) {
            $availableCurrencies[] = $curr['code'];
        }
    }
    $selected = $_SESSION['selected_currency'] ?? ($settings['currency_code'] ?? 'GBP');
    if (!in_array($selected, $availableCurrencies)) {
        $selected = $settings['currency_code'] ?? 'GBP';
    }
    return $selected;
}

// Get the appropriate price for a product based on current currency
function getProductPrice($product, $currencyCode = null) {
    $settings = getSettings();
    
    // Use provided currency or fall back to default
    if (!$currencyCode) {
        $currencyCode = $settings['currency_code'] ?? 'GBP';
    }
    
    // Check if product has multi-currency pricing
    if (isset($product['prices']) && is_array($product['prices'])) {
        // Return specific currency price if available
        if (isset($product['prices'][$currencyCode])) {
            return $product['prices'][$currencyCode];
        }
        
        // Fall back to default currency from the prices array
        $defaultCurrency = null;
        if (isset($settings['currencies'])) {
            foreach ($settings['currencies'] as $currency) {
                if ($currency['default']) {
                    $defaultCurrency = $currency['code'];
                    break;
                }
            }
        }
        
        if ($defaultCurrency && isset($product['prices'][$defaultCurrency])) {
            return $product['prices'][$defaultCurrency];
        }
        
        // If no default found, return first available price
        $firstPrice = reset($product['prices']);
        if ($firstPrice !== false) {
            return $firstPrice;
        }
    }
    
    // Fall back to old single price field
    return $product['price'] ?? 0;
}

// Format price for a product with proper currency
function formatProductPrice($product, $currencyCode = null) {
    $price = getProductPrice($product, $currencyCode);
    $settings = getSettings();
    
    // Get currency symbol
    $symbol = $settings['currency_symbol'] ?? '£';
    if ($currencyCode && isset($settings['currencies'])) {
        foreach ($settings['currencies'] as $currency) {
            if ($currency['code'] === $currencyCode) {
                $symbol = $currency['symbol'];
                break;
            }
        }
    }
    
    return $symbol . number_format($price, 2);
}

// Format price with specific currency
function formatPriceWithCurrency($price, $currencyCode) {
    $settings = getSettings();
    
    // Find currency info
    $currencySymbol = $currencyCode; // fallback
    if (isset($settings['currencies'])) {
        foreach ($settings['currencies'] as $currency) {
            if ($currency['code'] === $currencyCode) {
                $currencySymbol = $currency['symbol'];
                break;
            }
        }
    }
    
    return $currencySymbol . number_format($price, 2);
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/\s+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Get count of active products in a specific category
function getCategoryProductCount($categoryId) {
    $products = readJsonFile('products.json');
    return count(array_filter($products, function($product) use ($categoryId) {
        return $product['active'] && $product['category_id'] == $categoryId;
    }));
}

// Get product counts for all categories
function getCategoryProductCounts() {
    $categories = getCategories();
    $products = readJsonFile('products.json');
    $counts = [];
    
    foreach ($categories as $category) {
        $counts[$category['id']] = count(array_filter($products, function($product) use ($category) {
            return $product['active'] && $product['category_id'] == $category['id'];
        }));
    }
    
    return $counts;
}

// Cart Functions
function getCart() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['cart'] ?? [];
}

function addToCart($productId, $quantity = 1, $options = []) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $product = getProductById($productId);
    if (!$product) {
        return false;
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Create a unique identifier for cart items (product + size + color)
    $cartKey = $productId . 
               (isset($options['size']) ? '_size_' . $options['size'] : '') . 
               (isset($options['color']) ? '_color_' . $options['color'] : '');
    
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        $itemKey = $item['product_id'] . 
                  (isset($item['size']) ? '_size_' . $item['size'] : '') . 
                  (isset($item['color']) ? '_color_' . $item['color'] : '');
        if ($itemKey == $cartKey) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $cartItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'added_at' => date('Y-m-d H:i:s')
        ];
        
        // Add size if provided
        if (isset($options['size']) && !empty($options['size'])) {
            $cartItem['size'] = $options['size'];
        }
        
        // Add color if provided
        if (isset($options['color']) && !empty($options['color'])) {
            $cartItem['color'] = $options['color'];
        }
        
        $_SESSION['cart'][] = $cartItem;
    }
    
    return true;
}

function removeFromCart($productId) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($productId) {
        return $item['product_id'] != $productId;
    });
    
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    return true;
}

// Remove a specific cart item by composite key (product + options)
function removeFromCartByKey($cartKey) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($cartKey) {
        $itemKey = $item['product_id'] .
                  (isset($item['size']) ? '_size_' . $item['size'] : '') .
                  (isset($item['color']) ? '_color_' . $item['color'] : '');
        return $itemKey !== $cartKey;
    });
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    return true;
}

function updateCartQuantity($productId, $quantity) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($quantity <= 0) {
        return removeFromCart($productId);
    }
    
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $productId) {
            $item['quantity'] = $quantity;
            return true;
        }
    }
    
    return false;
}

function getCartTotal() {
    $cart = getCart();
    $total = 0;
    
    foreach ($cart as $item) {
        $product = getProductById($item['product_id']);
        if ($product) {
            $price = getProductPrice($product);
            $total += $price * $item['quantity'];
        }
    }
    
    return $total;
}

function getCartItemCount() {
    $cart = getCart();
    $count = 0;
    
    foreach ($cart as $item) {
        $count += $item['quantity'];
    }
    
    return $count;
}

function clearCart() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['cart'] = [];
    return true;
}

// Pagination helper
function paginate($totalItems, $itemsPerPage, $currentPage = 1) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($totalPages, $currentPage));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
        'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null
    ];
}

// Error handling
function logError($message, $file = 'error.log') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    error_log($logMessage, 3, $logDir . $file);
}

// Debug function to track issues
function debugLog($message, $context = []) {
    $logMessage = $message;
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }
    logError('[DEBUG] ' . $logMessage, 'debug.log');
}

// Asset validation function
function validateAsset($path) {
    $fullPath = __DIR__ . '/../' . $path;
    $exists = file_exists($fullPath);
    
    if (!$exists) {
        debugLog("Missing asset detected: $path");
    }
    
    return $exists;
}

// Base path helper functions
function getBasePath() {
    // Get the document root and current script directory
    $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    $currentDir = dirname(__DIR__); // Go up one level from includes/
    
    // Normalize paths
    $documentRoot = str_replace('\\', '/', $documentRoot);
    $currentDir = str_replace('\\', '/', $currentDir);
    
    // Calculate the relative path from document root
    $relativePath = str_replace($documentRoot, '', $currentDir);
    
    // Clean up the path
    $basePath = '/' . ltrim($relativePath, '/');
    
    // Remove trailing slash if not root
    if ($basePath !== '/') {
        $basePath = rtrim($basePath, '/');
    }
    
    // If we end up with just '/', it means we're in the document root
    return $basePath === '/' ? '' : $basePath;
}

function getBaseUrl($path = '') {
    $basePath = getBasePath();
    
    // Handle empty path
    if (empty($path)) {
        return $basePath ?: '/';
    }
    
    // Clean the path
    $path = ltrim($path, '/');
    
    // If basePath is root, just add the path
    if ($basePath === '/' || empty($basePath)) {
        return '/' . $path;
    }
    
    // Otherwise combine basePath and path
    return $basePath . '/' . $path;
}

function getAssetUrl($assetPath) {
    return getBaseUrl('assets/' . ltrim($assetPath, '/'));
}

// Debug function to help troubleshoot path issues
function debugPaths() {
    if (isset($_GET['debug_paths'])) {
        echo "<div style='background: #f0f0f0; padding: 20px; margin: 10px; border: 1px solid #ccc;'>";
        echo "<h3>Path Debug Information</h3>";
        echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
        echo "<strong>Current Directory:</strong> " . dirname(__DIR__) . "<br>";
        echo "<strong>Base Path:</strong> '" . getBasePath() . "'<br>";
        echo "<strong>Base URL (empty):</strong> '" . getBaseUrl() . "'<br>";
        echo "<strong>Base URL (shop.php):</strong> '" . getBaseUrl('shop.php') . "'<br>";
        echo "<strong>Asset URL (css/custom.css):</strong> '" . getAssetUrl('css/custom.css') . "'<br>";
        echo "<strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "<br>";
        echo "<strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "<br>";
        echo "</div>";
    }
}

// Image helper function
function getProductImageSrc($imagePath) {
    $fullPath = __DIR__ . '/../assets/images/' . $imagePath;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return getAssetUrl('images/' . $imagePath);
    }
    return getAssetUrl('images/general/placeholder.jpg');
}

// Advertisement Functions
function getAds() {
    return readJsonFile('ads.json');
}

function getActiveAds() {
    $ads = getAds();
    return array_filter($ads, function($ad) {
        return $ad['active'] == 1;
    });
}

function getAdById($id) {
    $ads = getAds();
    foreach ($ads as $ad) {
        if ($ad['id'] == $id) {
            return $ad;
        }
    }
    return null;
}

function getDestinationInfo($ad) {
    $destinationType = $ad['destination_type'] ?? 'product';
    
    switch ($destinationType) {
        case 'category':
            $category = getCategoryById($ad['category_id']);
            return [
                'icon' => '<i class="bi bi-tags text-blue-600"></i>',
                'label' => 'Category',
                'description' => $category ? htmlspecialchars($category['name']) : 'Category not found'
            ];
            
        case 'search':
            return [
                'icon' => '<i class="bi bi-search text-green-600"></i>',
                'label' => 'Search Query',
                'description' => htmlspecialchars($ad['search_query'] ?? '')
            ];
            
        case 'custom':
            return [
                'icon' => '<i class="bi bi-link-45deg text-purple-600"></i>',
                'label' => 'Custom URL',
                'description' => htmlspecialchars($ad['custom_url'] ?? '')
            ];
            
        case 'product':
        default:
            $product = getProductById($ad['product_id']);
            return [
                'icon' => '<i class="bi bi-box-seam text-folly"></i>',
                'label' => 'Product',
                'description' => $product ? htmlspecialchars($product['name']) : 'Product not found'
            ];
    }
}

function getAdDestinationUrl($ad) {
    $destinationType = $ad['destination_type'] ?? 'product';
    
    switch ($destinationType) {
        case 'category':
            $category = getCategoryById($ad['category_id']);
            return $category ? getBaseUrl('category.php?slug=' . $category['slug']) : getBaseUrl('shop.php');
            
        case 'search':
            $searchQuery = urlencode($ad['search_query'] ?? '');
            return getBaseUrl('search.php?q=' . $searchQuery);
            
        case 'custom':
            return $ad['custom_url'] ?? '#';
            
        case 'product':
        default:
            $product = getProductById($ad['product_id']);
            return $product ? getBaseUrl('product.php?slug=' . $product['slug']) : getBaseUrl('shop.php');
    }
}

function addAd($data, $files) {
    try {
        // Validate input
        if (empty($data['title']) || empty($data['destination_type'])) {
            return ['success' => false, 'message' => 'Title and destination type are required.'];
        }

        $destinationType = $data['destination_type'];
        
        // Validate destination-specific fields
        switch ($destinationType) {
            case 'product':
                if (empty($data['product_id'])) {
                    return ['success' => false, 'message' => 'Product selection is required.'];
                }
                $product = getProductById($data['product_id']);
                if (!$product) {
                    return ['success' => false, 'message' => 'Selected product does not exist.'];
                }
                break;
                
            case 'category':
                if (empty($data['category_id'])) {
                    return ['success' => false, 'message' => 'Category selection is required.'];
                }
                $category = getCategoryById($data['category_id']);
                if (!$category) {
                    return ['success' => false, 'message' => 'Selected category does not exist.'];
                }
                break;
                
            case 'search':
                if (empty($data['search_query'])) {
                    return ['success' => false, 'message' => 'Search query is required.'];
                }
                break;
                
            case 'custom':
                if (empty($data['custom_url']) || !filter_var($data['custom_url'], FILTER_VALIDATE_URL)) {
                    return ['success' => false, 'message' => 'Valid custom URL is required.'];
                }
                break;
                
            default:
                return ['success' => false, 'message' => 'Invalid destination type.'];
        }

        // Handle image upload
        if (!isset($files['image']) || $files['image']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Image upload is required.'];
        }

        $uploadResult = uploadAdImage($files['image']);
        if (!$uploadResult['success']) {
            return $uploadResult;
        }

        // Get existing ads to generate new ID
        $ads = getAds();
        $newId = 1;
        if (!empty($ads)) {
            $newId = max(array_column($ads, 'id')) + 1;
        }

        // Create new ad
        $newAd = [
            'id' => $newId,
            'title' => sanitizeInput($data['title']),
            'description' => sanitizeInput($data['description'] ?? ''),
            'image' => $uploadResult['filename'],
            'destination_type' => $destinationType,
            'active' => isset($data['active']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Add destination-specific fields
        switch ($destinationType) {
            case 'product':
                $newAd['product_id'] = (int)$data['product_id'];
                break;
            case 'category':
                $newAd['category_id'] = (int)$data['category_id'];
                break;
            case 'search':
                $newAd['search_query'] = sanitizeInput($data['search_query']);
                break;
            case 'custom':
                $newAd['custom_url'] = $data['custom_url'];
                break;
        }

        $ads[] = $newAd;
        writeJsonFile('ads.json', $ads);

        return ['success' => true, 'message' => 'Advertisement added successfully.'];
    } catch (Exception $e) {
        logError('Error adding advertisement: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while adding the advertisement.'];
    }
}

function updateAd($id, $data, $files) {
    try {
        $ads = getAds();
        $adIndex = -1;
        
        foreach ($ads as $index => $ad) {
            if ($ad['id'] == $id) {
                $adIndex = $index;
                break;
            }
        }
        
        if ($adIndex === -1) {
            return ['success' => false, 'message' => 'Advertisement not found.'];
        }

        // Validate input
        if (empty($data['title']) || empty($data['destination_type'])) {
            return ['success' => false, 'message' => 'Title and destination type are required.'];
        }

        $destinationType = $data['destination_type'];
        
        // Validate destination-specific fields
        switch ($destinationType) {
            case 'product':
                if (empty($data['product_id'])) {
                    return ['success' => false, 'message' => 'Product selection is required.'];
                }
                $product = getProductById($data['product_id']);
                if (!$product) {
                    return ['success' => false, 'message' => 'Selected product does not exist.'];
                }
                break;
                
            case 'category':
                if (empty($data['category_id'])) {
                    return ['success' => false, 'message' => 'Category selection is required.'];
                }
                $category = getCategoryById($data['category_id']);
                if (!$category) {
                    return ['success' => false, 'message' => 'Selected category does not exist.'];
                }
                break;
                
            case 'search':
                if (empty($data['search_query'])) {
                    return ['success' => false, 'message' => 'Search query is required.'];
                }
                break;
                
            case 'custom':
                if (empty($data['custom_url']) || !filter_var($data['custom_url'], FILTER_VALIDATE_URL)) {
                    return ['success' => false, 'message' => 'Valid custom URL is required.'];
                }
                break;
                
            default:
                return ['success' => false, 'message' => 'Invalid destination type.'];
        }

        $currentAd = $ads[$adIndex];
        $imageName = $currentAd['image']; // Keep current image by default

        // Handle new image upload if provided
        if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadAdImage($files['image']);
            if (!$uploadResult['success']) {
                return $uploadResult;
            }
            
            // Delete old image
            $oldImagePath = __DIR__ . '/../assets/images/ads/' . $currentAd['image'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            
            $imageName = $uploadResult['filename'];
        }

        // Update ad
        $updatedAd = [
            'id' => (int)$id,
            'title' => sanitizeInput($data['title']),
            'description' => sanitizeInput($data['description'] ?? ''),
            'image' => $imageName,
            'destination_type' => $destinationType,
            'active' => isset($data['active']) ? 1 : 0,
            'created_at' => $currentAd['created_at'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Add destination-specific fields and clear old ones
        $updatedAd['product_id'] = null;
        $updatedAd['category_id'] = null;
        $updatedAd['search_query'] = null;
        $updatedAd['custom_url'] = null;

        switch ($destinationType) {
            case 'product':
                $updatedAd['product_id'] = (int)$data['product_id'];
                break;
            case 'category':
                $updatedAd['category_id'] = (int)$data['category_id'];
                break;
            case 'search':
                $updatedAd['search_query'] = sanitizeInput($data['search_query']);
                break;
            case 'custom':
                $updatedAd['custom_url'] = $data['custom_url'];
                break;
        }

        $ads[$adIndex] = $updatedAd;
        writeJsonFile('ads.json', $ads);

        return ['success' => true, 'message' => 'Advertisement updated successfully.'];
    } catch (Exception $e) {
        logError('Error updating advertisement: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating the advertisement.'];
    }
}

function deleteAd($id) {
    try {
        $ads = getAds();
        $adIndex = -1;
        
        foreach ($ads as $index => $ad) {
            if ($ad['id'] == $id) {
                $adIndex = $index;
                break;
            }
        }
        
        if ($adIndex === -1) {
            return ['success' => false, 'message' => 'Advertisement not found.'];
        }

        $ad = $ads[$adIndex];
        
        // Delete image file
        $imagePath = __DIR__ . '/../assets/images/ads/' . $ad['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Remove from array
        array_splice($ads, $adIndex, 1);
        writeJsonFile('ads.json', $ads);

        return ['success' => true, 'message' => 'Advertisement deleted successfully.'];
    } catch (Exception $e) {
        logError('Error deleting advertisement: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting the advertisement.'];
    }
}

function toggleAdStatus($id) {
    try {
        $ads = getAds();
        $adIndex = -1;
        
        foreach ($ads as $index => $ad) {
            if ($ad['id'] == $id) {
                $adIndex = $index;
                break;
            }
        }
        
        if ($adIndex === -1) {
            return ['success' => false, 'message' => 'Advertisement not found.'];
        }

        $ads[$adIndex]['active'] = $ads[$adIndex]['active'] ? 0 : 1;
        $ads[$adIndex]['updated_at'] = date('Y-m-d H:i:s');
        
        writeJsonFile('ads.json', $ads);

        $status = $ads[$adIndex]['active'] ? 'activated' : 'deactivated';
        return ['success' => true, 'message' => "Advertisement $status successfully."];
    } catch (Exception $e) {
        logError('Error toggling advertisement status: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating the advertisement status.'];
    }
}

function uploadAdImage($file) {
    try {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload failed.'];
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $file['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.'];
        }

        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'ad_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
        
        $uploadDir = __DIR__ . '/../assets/images/ads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            return ['success' => false, 'message' => 'Failed to save uploaded file.'];
        }
    } catch (Exception $e) {
        logError('Error uploading ad image: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during file upload.'];
    }
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Rating Functions

function getRatings() {
    return readJsonFile('ratings.json');
}

function getProductRatings($productId) {
    $ratings = getRatings();
    return array_filter($ratings, function($rating) use ($productId) {
        return $rating['product_id'] == $productId;
    });
}

function getProductRatingStats($productId) {
    $ratings = getProductRatings($productId);
    
    if (empty($ratings)) {
        return [
            'average' => 0,
            'count' => 0,
            'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
        ];
    }
    
    $total = 0;
    $count = count($ratings);
    $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    
    foreach ($ratings as $rating) {
        $total += $rating['rating'];
        $distribution[$rating['rating']]++;
    }
    
    return [
        'average' => round($total / $count, 1),
        'count' => $count,
        'distribution' => $distribution
    ];
}

function addRating($productId, $rating, $review, $reviewerName, $reviewerEmail) {
    $ratings = getRatings();
    
    // Get next ID
    $nextId = 1;
    if (!empty($ratings)) {
        $nextId = max(array_column($ratings, 'id')) + 1;
    }
    
    $newRating = [
        'id' => $nextId,
        'product_id' => (int)$productId,
        'rating' => (int)$rating,
        'review' => sanitizeInput($review),
        'reviewer_name' => sanitizeInput($reviewerName),
        'reviewer_email' => sanitizeInput($reviewerEmail),
        'date' => date('Y-m-d'),
        'verified_purchase' => false // Can be updated later based on order history
    ];
    
    $ratings[] = $newRating;
    
    if (writeJsonFile('ratings.json', $ratings)) {
        return $newRating;
    }
    
    return false;
}

function renderStars($rating, $maxStars = 5, $size = 'w-4 h-4') {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = $maxStars - $fullStars - $halfStar;
    
    $output = '<div class="flex text-yellow-400">';
    
    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $output .= '<svg class="' . $size . ' fill-current" viewBox="0 0 20 20">';
        $output .= '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>';
        $output .= '</svg>';
    }
    
    // Half star
    if ($halfStar) {
        $output .= '<svg class="' . $size . ' text-yellow-400" viewBox="0 0 20 20">';
        $output .= '<defs><clipPath id="half"><rect x="0" y="0" width="10" height="20"/></clipPath></defs>';
        $output .= '<path class="fill-current" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" clip-path="url(#half)"></path>';
        $output .= '<path class="fill-gray-300" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>';
        $output .= '</svg>';
    }
    
    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $output .= '<svg class="' . $size . ' fill-current text-gray-300" viewBox="0 0 20 20">';
        $output .= '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>';
        $output .= '</svg>';
    }
    
    $output .= '</div>';
    return $output;
}

// Sub-Category Helper Functions

/**
 * Get all categories including inactive ones (for admin use)
 */
function getAllCategories() {
    return readJsonFile('categories.json');
}

/**
 * Build category hierarchy tree from flat array
 */
function buildCategoryTree($categories, $parentId = 0) {
    $tree = [];
    foreach ($categories as $category) {
        $categoryParentId = $category['parent_id'] ?? 0;
        if ($categoryParentId == $parentId) {
            $category['children'] = buildCategoryTree($categories, $category['id']);
            $tree[] = $category;
        }
    }
    return $tree;
}

/**
 * Get category hierarchy organized as tree
 */
function getCategoryHierarchy($includeInactive = false) {
    $categories = $includeInactive ? getAllCategories() : getCategories();
    return buildCategoryTree($categories);
}

/**
 * Get the full path of a category (Parent > Child > Grandchild)
 */
function getCategoryPath($categoryId, $separator = ' > ') {
    $categories = getAllCategories();
    $path = [];
    
    // Find the starting category from all categories (including inactive)
    $category = null;
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryId) {
            $category = $cat;
            break;
        }
    }
    
    if (!$category) {
        return '';
    }
    
    $currentId = $categoryId;
    while ($currentId) {
        $current = null;
        foreach ($categories as $cat) {
            if ($cat['id'] == $currentId) {
                $current = $cat;
                break;
            }
        }
        
        if (!$current) break;
        
        array_unshift($path, $current['name']);
        $currentId = $current['parent_id'] ?? 0;
    }
    
    return implode($separator, $path);
}

/**
 * Check if a category has sub-categories
 */
function hasSubCategories($categoryId) {
    $categories = getAllCategories();
    foreach ($categories as $category) {
        if (($category['parent_id'] ?? 0) == $categoryId) {
            return true;
        }
    }
    return false;
}

/**
 * Get direct children of a category
 */
function getSubCategories($parentId, $includeInactive = false) {
    $categories = $includeInactive ? getAllCategories() : getCategories();
    $subCategories = [];
    
    foreach ($categories as $category) {
        if (($category['parent_id'] ?? 0) == $parentId) {
            $subCategories[] = $category;
        }
    }
    
    return $subCategories;
}

/**
 * Get all descendants (children, grandchildren, etc.) of a category
 */
function getAllDescendants($parentId, $includeInactive = false) {
    $categories = $includeInactive ? getAllCategories() : getCategories();
    $descendants = [];
    
    // Get direct children
    $children = getSubCategories($parentId, $includeInactive);
    
    foreach ($children as $child) {
        $descendants[] = $child;
        // Recursively get descendants of this child
        $childDescendants = getAllDescendants($child['id'], $includeInactive);
        $descendants = array_merge($descendants, $childDescendants);
    }
    
    return $descendants;
}

/**
 * Render category select dropdown with hierarchy
 */
function renderCategorySelect($name, $selectedId = 0, $includeInactive = false, $attributes = '') {
    $categories = getCategoryHierarchy($includeInactive);
    $html = "<select name=\"{$name}\" {$attributes}>";
    $html .= "<option value=\"0\">Select Category</option>";
    $html .= renderCategoryOptions($categories, $selectedId, 0);
    $html .= "</select>";
    return $html;
}

/**
 * Render category options recursively for select dropdown
 */
function renderCategoryOptions($categories, $selectedId = 0, $level = 0) {
    $html = '';
    foreach ($categories as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $selected = ($category['id'] == $selectedId) ? 'selected' : '';
        $html .= "<option value=\"{$category['id']}\" {$selected}>";
        $html .= $indent . htmlspecialchars($category['name']);
        $html .= "</option>";
        
        // Render children
        if (!empty($category['children'])) {
            $html .= renderCategoryOptions($category['children'], $selectedId, $level + 1);
        }
    }
    return $html;
}

/**
 * Get products by category including sub-categories
 */
function getProductsByCategoryAndSubs($categoryId, $featured = null, $limit = null) {
    $products = readJsonFile('products.json');
    
    // Get all descendant categories
    $descendants = getAllDescendants($categoryId);
    $categoryIds = array_merge([$categoryId], array_column($descendants, 'id'));
    
    // Filter active products
    $products = array_filter($products, function($product) use ($categoryIds, $featured) {
        $categoryMatch = in_array($product['category_id'], $categoryIds);
        $activeMatch = $product['active'];
        $featuredMatch = ($featured === null) || ($product['featured'] == $featured);
        
        return $categoryMatch && $activeMatch && $featuredMatch;
    });
    
    // Apply limit
    if ($limit !== null) {
        $products = array_slice($products, 0, $limit);
    }
    
    return array_values($products);
}

/**
 * Update getCategoryProductCount to include sub-categories
 */
function getCategoryProductCountWithSubs($categoryId) {
    $products = readJsonFile('products.json');
    
    // Get all descendant categories
    $descendants = getAllDescendants($categoryId);
    $categoryIds = array_merge([$categoryId], array_column($descendants, 'id'));
    
    $count = 0;
    foreach ($products as $product) {
        if ($product['active'] && in_array($product['category_id'], $categoryIds)) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Render category options for frontend dropdowns
 */
function renderFrontendCategoryOptions($categories, $selectedSlug = '', $level = 0) {
    $html = '';
    foreach ($categories as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
        $selected = ($category['slug'] === $selectedSlug) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($category['slug']) . '" ' . $selected . '>';
        $html .= $indent . htmlspecialchars($category['name']);
        $html .= '</option>';
        
        // Render children
        if (!empty($category['children'])) {
            $html .= renderFrontendCategoryOptions($category['children'], $selectedSlug, $level + 1);
        }
    }
    return $html;
}

/**
 * Get category breadcrumb path as array
 */
function getCategoryBreadcrumb($categoryId) {
    $categories = getAllCategories();
    $breadcrumb = [];
    
    $currentId = $categoryId;
    while ($currentId) {
        $current = null;
        foreach ($categories as $cat) {
            if ($cat['id'] == $currentId) {
                $current = $cat;
                break;
            }
        }
        
        if (!$current) break;
        
        array_unshift($breadcrumb, $current);
        $currentId = $current['parent_id'] ?? 0;
    }
    
    return $breadcrumb;
}

/**
 * Get total product count for a category including all subcategories
 * (Alias for getCategoryProductCountWithSubs for clearer naming)
 */
function getTotalProductCountForCategory($categoryId) {
    return getCategoryProductCountWithSubs($categoryId);
}

/**
 * Get featured categories
 */
function getFeaturedCategories() {
    $categories = getCategories();
    return array_filter($categories, function($category) {
        return ($category['featured'] ?? false) === true;
    });
}

/**
 * Get products from category and all its sub-categories (for frontend)
 */
function getProductsFromCategoryTree($categoryId, $featured = null, $limit = null) {
    $products = readJsonFile('products.json');
    
    // Get all descendant categories
    $descendants = getAllDescendants($categoryId, false); // Only active categories
    $categoryIds = array_merge([$categoryId], array_column($descendants, 'id'));
    
    // Filter active products
    $products = array_filter($products, function($product) use ($categoryIds, $featured) {
        $categoryMatch = in_array($product['category_id'], $categoryIds);
        $activeMatch = $product['active'];
        $featuredMatch = ($featured === null) || ($product['featured'] == $featured);
        
        return $categoryMatch && $activeMatch && $featuredMatch;
    });
    
    // Apply limit
    if ($limit !== null) {
        $products = array_slice($products, 0, $limit);
    }
    
    return array_values($products);
}

// Newsletter Functions

/**
 * Get all newsletter subscribers
 */
function getNewsletterSubscribers() {
    return readJsonFile('newsletter.json');
}

/**
 * Get active newsletter subscribers
 */
function getActiveNewsletterSubscribers() {
    $subscribers = getNewsletterSubscribers();
    return array_filter($subscribers, function($subscriber) {
        return $subscriber['active'] ?? true;
    });
}

/**
 * Check if email is already subscribed
 */
function isEmailSubscribed($email) {
    $subscribers = getNewsletterSubscribers();
    foreach ($subscribers as $subscriber) {
        if (strtolower($subscriber['email']) === strtolower($email)) {
            return true;
        }
    }
    return false;
}

/**
 * Add newsletter subscriber
 */
function addNewsletterSubscriber($email, $additionalData = []) {
    try {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if already subscribed
        if (isEmailSubscribed($email)) {
            return ['success' => false, 'message' => 'Email is already subscribed'];
        }
        
        $subscribers = getNewsletterSubscribers();
        
        // Generate new ID
        $newId = 1;
        if (!empty($subscribers)) {
            $newId = max(array_column($subscribers, 'id')) + 1;
        }
        
        // Create new subscriber
        $newSubscriber = array_merge([
            'id' => $newId,
            'email' => strtolower(trim($email)),
            'subscribed_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'active' => true
        ], $additionalData);
        
        $subscribers[] = $newSubscriber;
        
        if (writeJsonFile('newsletter.json', $subscribers)) {
            return [
                'success' => true, 
                'message' => 'Successfully subscribed to newsletter',
                'subscriber' => $newSubscriber
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to save subscription'];
        }
    } catch (Exception $e) {
        logError('Error adding newsletter subscriber: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while subscribing'];
    }
}

/**
 * Unsubscribe from newsletter
 */
function unsubscribeFromNewsletter($email) {
    try {
        $subscribers = getNewsletterSubscribers();
        $found = false;
        
        foreach ($subscribers as &$subscriber) {
            if (strtolower($subscriber['email']) === strtolower($email)) {
                $subscriber['active'] = false;
                $subscriber['unsubscribed_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return ['success' => false, 'message' => 'Email not found in newsletter list'];
        }
        
        if (writeJsonFile('newsletter.json', $subscribers)) {
            return ['success' => true, 'message' => 'Successfully unsubscribed from newsletter'];
        } else {
            return ['success' => false, 'message' => 'Failed to process unsubscription'];
        }
    } catch (Exception $e) {
        logError('Error unsubscribing from newsletter: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while unsubscribing'];
    }
}

/**
 * Get newsletter subscriber count
 */
function getNewsletterSubscriberCount($activeOnly = true) {
    if ($activeOnly) {
        return count(getActiveNewsletterSubscribers());
    } else {
        return count(getNewsletterSubscribers());
    }
}

/**
 * Delete newsletter subscriber permanently
 */
function deleteNewsletterSubscriber($id) {
    try {
        $subscribers = getNewsletterSubscribers();
        $subscriberIndex = -1;
        
        foreach ($subscribers as $index => $subscriber) {
            if ($subscriber['id'] == $id) {
                $subscriberIndex = $index;
                break;
            }
        }
        
        if ($subscriberIndex === -1) {
            return ['success' => false, 'message' => 'Subscriber not found'];
        }
        
        array_splice($subscribers, $subscriberIndex, 1);
        
        if (writeJsonFile('newsletter.json', $subscribers)) {
            return ['success' => true, 'message' => 'Subscriber deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete subscriber'];
        }
    } catch (Exception $e) {
        logError('Error deleting newsletter subscriber: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting subscriber'];
    }
}

/**
 * Export newsletter subscribers as CSV
 */
function exportNewsletterSubscribers($activeOnly = true) {
    $subscribers = $activeOnly ? getActiveNewsletterSubscribers() : getNewsletterSubscribers();
    
    $csvData = "ID,Email,Subscribed At,Status,IP Address\n";
    
    foreach ($subscribers as $subscriber) {
        $status = ($subscriber['active'] ?? true) ? 'Active' : 'Unsubscribed';
        $csvData .= sprintf(
            "%d,%s,%s,%s,%s\n",
            $subscriber['id'],
            $subscriber['email'],
            $subscriber['subscribed_at'],
            $status,
            $subscriber['ip_address'] ?? 'unknown'
        );
    }
    
    return $csvData;
}

?>