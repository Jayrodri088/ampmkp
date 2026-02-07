<?php

/**
 * Storage Backend Configuration
 * Set STORAGE_BACKEND in .env to 'mysql' to use MySQL, defaults to 'json'
 */
function getStorageBackend(): string {
    static $backend = null;
    if ($backend === null) {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    if (strpos($line, 'STORAGE_BACKEND=') === 0) {
                        $backend = trim(substr($line, strlen('STORAGE_BACKEND=')));
                        break;
                    }
                }
            }
        }
        $backend = $backend ?: 'json';
    }
    return $backend;
}

function isMySQLBackend(): bool {
    return getStorageBackend() === 'mysql';
}

// Include repository classes (always load so IDE resolves types; used only when STORAGE_BACKEND=mysql)
require_once __DIR__ . '/repositories/ProductRepository.php';
require_once __DIR__ . '/repositories/CategoryRepository.php';
require_once __DIR__ . '/repositories/OrderRepository.php';
require_once __DIR__ . '/repositories/RatingRepository.php';
require_once __DIR__ . '/repositories/SettingsRepository.php';
if (isMySQLBackend()) {
    if (file_exists(__DIR__ . '/database.php')) {
        require_once __DIR__ . '/database.php';
    }
}

/**
 * Load .env file into associative array (KEY => value).
 * Use this instead of parse_ini_file() to avoid "unexpected '='" on .env format.
 */
function loadEnvFile($filepath) {
    $env = [];
    if (!file_exists($filepath)) {
        return $env;
    }
    $lines = @file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $env;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || (strlen($line) > 0 && $line[0] === '#')) {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq !== false) {
            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));
            if ($key !== '') {
                $env[$key] = $val;
            }
        }
    }
    return $env;
}

// JSON Database Helper Functions

function readJsonFile($filename) {
    // Request-level cache (and updateable within the same request via writeJsonFile()).
    // This dramatically reduces homepage TTFB by avoiding repeated disk reads/JSON decoding.
    if (!isset($GLOBALS['__AMP_JSON_CACHE']) || !is_array($GLOBALS['__AMP_JSON_CACHE'])) {
        $GLOBALS['__AMP_JSON_CACHE'] = [];
    }

    $filepath = __DIR__ . '/../data/' . $filename;
    if (!file_exists($filepath)) {
        // Cache missing files too (so we don't stat repeatedly).
        $GLOBALS['__AMP_JSON_CACHE'][$filename] = ['mtime' => null, 'data' => []];
        return [];
    }

    $mtime = @filemtime($filepath) ?: null;
    $cached = $GLOBALS['__AMP_JSON_CACHE'][$filename] ?? null;
    if (is_array($cached) && array_key_exists('mtime', $cached) && $cached['mtime'] === $mtime) {
        return $cached['data'] ?? [];
    }

    $content = @file_get_contents($filepath);
    $data = json_decode($content ?: '', true);
    if (!is_array($data)) {
        $data = [];
    }

    $GLOBALS['__AMP_JSON_CACHE'][$filename] = ['mtime' => $mtime, 'data' => $data];
    return $data;
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
    // Keep in-request reads consistent after writes.
    if (!isset($GLOBALS['__AMP_JSON_CACHE']) || !is_array($GLOBALS['__AMP_JSON_CACHE'])) {
        $GLOBALS['__AMP_JSON_CACHE'] = [];
    }
    if ($ok) {
        $mtime = @filemtime($filepath) ?: null;
        $GLOBALS['__AMP_JSON_CACHE'][$filename] = ['mtime' => $mtime, 'data' => is_array($data) ? $data : []];
    } else {
        unset($GLOBALS['__AMP_JSON_CACHE'][$filename]);
    }
    return $ok;
}

function getSettings() {
    static $settings = null;
    if ($settings === null) {
        if (isMySQLBackend()) {
            $settings = SettingsRepository::getAll();
        } else {
            $settings = readJsonFile('settings.json');
        }
    }
    return $settings;
}

function getCategories() {
    if (isMySQLBackend()) {
        return CategoryRepository::getAll();
    }

    static $cache = ['mtime' => null, 'data' => null];

    $categories = readJsonFile('categories.json');
    $mtime = $GLOBALS['__AMP_JSON_CACHE']['categories.json']['mtime'] ?? null;

    if (is_array($cache['data']) && $cache['mtime'] === $mtime) {
        return $cache['data'];
    }

    $active = array_values(array_filter($categories, function($cat) {
        return !empty($cat['active']);
    }));

    $cache = ['mtime' => $mtime, 'data' => $active];
    return $active;
}

/**
 * Sort key for "newest first": use created_at if present, else id (higher = newer).
 */
function getProductSortTimestamp($product) {
    if (!empty($product['created_at'])) {
        $ts = strtotime($product['created_at']);
        if ($ts !== false) {
            return $ts;
        }
    }
    return (int)($product['id'] ?? 0);
}

function getProducts($categoryId = null, $featured = null, $limit = null) {
    if (isMySQLBackend()) {
        return ProductRepository::getAll($categoryId, $featured, $limit);
    }

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

    // Sort newest first (created_at if present, else id)
    usort($products, function($a, $b) {
        return getProductSortTimestamp($b) <=> getProductSortTimestamp($a);
    });

    // Apply limit
    if ($limit !== null) {
        $products = array_slice($products, 0, $limit);
    }

    return array_values($products);
}

function getFeaturedProductsByRating($limit = null) {
    if (isMySQLBackend()) {
        return ProductRepository::getFeaturedByRating($limit);
    }

    $products = readJsonFile('products.json');

    // Filter for active and featured products
    $products = array_filter($products, function($product) {
        return !empty($product['active']) && !empty($product['featured']);
    });

    // Compute rating stats once, then just map-lookup per product (avoids N+1 reads/filters of ratings.json)
    $allStats = getAllProductRatingStats();

    // Add rating stats to each product for sorting
    $productsWithRatings = array_map(function($product) use ($allStats) {
        $productId = (int)($product['id'] ?? 0);
        $ratingStats = $allStats[$productId] ?? ['average' => 0, 'count' => 0];
        $product['rating_count'] = (int)($ratingStats['count'] ?? 0);
        $product['rating_average'] = (float)($ratingStats['average'] ?? 0);
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
    if (isMySQLBackend()) {
        return ProductRepository::getById((int)$id);
    }

    $products = readJsonFile('products.json');
    foreach ($products as $product) {
        if ($product['id'] == $id && $product['active']) {
            return $product;
        }
    }
    return null;
}

function getProductBySlug($slug) {
    if (isMySQLBackend()) {
        return ProductRepository::getBySlug($slug);
    }

    $products = readJsonFile('products.json');
    foreach ($products as $product) {
        if ($product['slug'] == $slug && $product['active']) {
            return $product;
        }
    }
    return null;
}

function getCategoryBySlug($slug) {
    if (isMySQLBackend()) {
        return CategoryRepository::getBySlug($slug);
    }

    $categories = getCategories();
    foreach ($categories as $category) {
        if ($category['slug'] === $slug) {
            return $category;
        }
    }
    return null;
}

function getCategoryById($id) {
    if (isMySQLBackend()) {
        return CategoryRepository::getById((int)$id);
    }

    $categories = getCategories();
    foreach ($categories as $category) {
        if ($category['id'] == $id) {
            return $category;
        }
    }
    return null;
}

function searchProducts($query, $categoryId = null) {
    if (isMySQLBackend()) {
        return ProductRepository::search($query, $categoryId);
    }

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

// Shipping helpers
function getShippingSettings(): array {
    $settings = getSettings();
    $shipping = $settings['shipping'] ?? [];
    // Defaults
    if (!isset($shipping['free_shipping_threshold'])) {
        $shipping['free_shipping_threshold'] = 0;
    }
    if (!isset($shipping['costs']) || !is_array($shipping['costs'])) {
        $shipping['costs'] = [];
    }
    if (!isset($shipping['standard_shipping_cost'])) {
        // Back-compat single cost
        $shipping['standard_shipping_cost'] = 5.99;
    }
    if (!isset($shipping['enable_pickup'])) {
        $shipping['enable_pickup'] = true; // enabled by default
    }
    if (!isset($shipping['enable_delivery'])) {
        $shipping['enable_delivery'] = true; // delivery enabled by default
    }
    if (!isset($shipping['allow_method_selection'])) {
        $shipping['allow_method_selection'] = true;
    }
    if (!isset($shipping['default_method'])) {
        $shipping['default_method'] = 'delivery';
    }
    if (!isset($shipping['pickup_label'])) {
        $shipping['pickup_label'] = 'Pickup';
    }
    if (!isset($shipping['pickup_instructions'])) {
        $shipping['pickup_instructions'] = '';
    }
    if (!isset($shipping['show_shipping_pre_checkout'])) {
        $shipping['show_shipping_pre_checkout'] = false; // hide by default on product/listing pages
    }
    return $shipping;
}

function isPickupEnabled(?array $shipping = null): bool {
    if ($shipping === null) {
        $shipping = getShippingSettings();
    }
    return (bool)($shipping['enable_pickup'] ?? false);
}

function getDefaultShippingMethod(?array $shipping = null): string {
    if ($shipping === null) {
        $shipping = getShippingSettings();
    }
    $method = strtolower($shipping['default_method'] ?? 'delivery');
    $deliveryOn = (bool)($shipping['enable_delivery'] ?? true);
    $pickupOn = (bool)($shipping['enable_pickup'] ?? false);
    if ($method === 'pickup' && !$pickupOn) {
        return $deliveryOn ? 'delivery' : 'pickup';
    }
    if ($method === 'delivery' && !$deliveryOn) {
        return $pickupOn ? 'pickup' : 'delivery';
    }
    if (!$deliveryOn && !$pickupOn) {
        return 'delivery';
    }
    return in_array($method, ['delivery', 'pickup'], true) ? $method : ($deliveryOn ? 'delivery' : 'pickup');
}

function validateShippingMethod(?string $method, ?array $shipping = null): string {
    $method = strtolower(trim((string)$method));
    if ($shipping === null) {
        $shipping = getShippingSettings();
    }
    $deliveryOn = (bool)($shipping['enable_delivery'] ?? true);
    $pickupOn = (bool)($shipping['enable_pickup'] ?? false);
    if ($method === 'pickup') {
        return $pickupOn ? 'pickup' : ($deliveryOn ? 'delivery' : 'pickup');
    }
    if ($method === 'delivery') {
        return $deliveryOn ? 'delivery' : ($pickupOn ? 'pickup' : 'delivery');
    }
    return getDefaultShippingMethod($shipping);
}

function computeShippingCost(float $subtotal, ?string $currencyCode, string $method = 'delivery', ?array $shipping = null): float {
    if ($shipping === null) {
        $shipping = getShippingSettings();
    }
    $method = validateShippingMethod($method, $shipping);
    if ($method === 'pickup') {
        return 0.0;
    }
    // Delivery: use per-currency config when available
    $settings = getSettings();
    if ($currencyCode === null) {
        $currencyCode = $settings['currency_code'] ?? 'GBP';
    }
    $currencySettings = $shipping['costs'][$currencyCode] ?? [];
    $freeShippingThreshold = $currencySettings['free_threshold'] ?? ($shipping['free_shipping_threshold'] ?? 0);
    $standardShippingCost = $currencySettings['standard'] ?? ($shipping['standard_shipping_cost'] ?? 0);
    if ($freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold) {
        return 0.0;
    }
    return (float)$standardShippingCost;
}

function isAddressRequiredForMethod(string $method, ?array $shipping = null): bool {
    $method = validateShippingMethod($method, $shipping ?? getShippingSettings());
    return $method === 'delivery';
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return strip_tags(trim((string)$input));
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

/**
 * Get total product counts for ALL active categories, including all subcategories.
 * Returns an array keyed by category_id => total_count.
 *
 * This is designed to avoid calling getTotalProductCountForCategory() repeatedly in loops.
 */
function getTotalProductCountsForAllCategories(): array {
    if (isMySQLBackend()) {
        return CategoryRepository::getAllTotalProductCounts();
    }

    static $cache = ['mtimes' => null, 'data' => null];

    // Touch the sources so __AMP_JSON_CACHE mtimes are populated.
    getCategories();
    readJsonFile('products.json');

    $mtimes = [
        'categories' => $GLOBALS['__AMP_JSON_CACHE']['categories.json']['mtime'] ?? null,
        'products' => $GLOBALS['__AMP_JSON_CACHE']['products.json']['mtime'] ?? null,
    ];

    if (is_array($cache['data']) && $cache['mtimes'] === $mtimes) {
        return $cache['data'];
    }

    $categories = getCategories(); // active categories only
    if (empty($categories)) {
        $cache = ['mtimes' => $mtimes, 'data' => []];
        return [];
    }

    $childrenByParent = [];
    $parentById = [];
    foreach ($categories as $cat) {
        $id = (int)($cat['id'] ?? 0);
        $parentId = (int)($cat['parent_id'] ?? 0);
        $parentById[$id] = $parentId;
        if (!isset($childrenByParent[$parentId])) {
            $childrenByParent[$parentId] = [];
        }
        $childrenByParent[$parentId][] = $id;
    }

    // Direct product counts per category
    $directCounts = [];
    $products = readJsonFile('products.json');
    foreach ($products as $p) {
        if (empty($p['active'])) {
            continue;
        }
        $cid = (int)($p['category_id'] ?? 0);
        $directCounts[$cid] = ($directCounts[$cid] ?? 0) + 1;
    }

    // Post-order traversal to compute totals bottom-up
    $totalCounts = [];
    $visited = [];

    $compute = function($categoryId) use (&$compute, &$totalCounts, &$childrenByParent, &$directCounts, &$visited): int {
        if (isset($visited[$categoryId])) {
            return (int)($totalCounts[$categoryId] ?? 0);
        }
        $visited[$categoryId] = true;
        $total = (int)($directCounts[$categoryId] ?? 0);
        foreach (($childrenByParent[$categoryId] ?? []) as $childId) {
            $total += $compute($childId);
        }
        $totalCounts[$categoryId] = $total;
        return $total;
    };

    // Compute totals for every category id we know about
    foreach (array_keys($parentById) as $id) {
        $compute($id);
    }

    $cache = ['mtimes' => $mtimes, 'data' => $totalCounts];
    return $totalCounts;
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
    if (isMySQLBackend()) {
        return RatingRepository::getAll();
    }
    return readJsonFile('ratings.json');
}

function getProductRatings($productId) {
    if (isMySQLBackend()) {
        return RatingRepository::getByProductId((int)$productId);
    }

    $ratings = getRatings();
    return array_filter($ratings, function($rating) use ($productId) {
        return $rating['product_id'] == $productId;
    });
}

function getProductRatingStats($productId) {
    if (isMySQLBackend()) {
        return RatingRepository::getProductStats((int)$productId);
    }

    $productId = (int)$productId;
    $all = getAllProductRatingStats();
    return $all[$productId] ?? [
        'average' => 0,
        'count' => 0,
        'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
    ];
}

/**
 * Aggregate rating stats for ALL products in one pass over ratings.json.
 * Returns: [product_id => ['average'=>float,'count'=>int,'distribution'=>array]]
 */
function getAllProductRatingStats(): array {
    if (isMySQLBackend()) {
        return RatingRepository::getAllProductStats();
    }

    static $cache = ['mtime' => null, 'data' => null];

    // Touch ratings source so __AMP_JSON_CACHE mtime is populated.
    getRatings();
    $mtime = $GLOBALS['__AMP_JSON_CACHE']['ratings.json']['mtime'] ?? null;

    if (is_array($cache['data']) && $cache['mtime'] === $mtime) {
        return $cache['data'];
    }

    $ratings = readJsonFile('ratings.json');
    $totals = [];
    $counts = [];
    $distributions = [];

    foreach ($ratings as $r) {
        $pid = (int)($r['product_id'] ?? 0);
        $stars = (int)($r['rating'] ?? 0);
        if ($pid <= 0 || $stars < 1 || $stars > 5) {
            continue;
        }
        $totals[$pid] = ($totals[$pid] ?? 0) + $stars;
        $counts[$pid] = ($counts[$pid] ?? 0) + 1;
        if (!isset($distributions[$pid])) {
            $distributions[$pid] = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        }
        $distributions[$pid][$stars] = ($distributions[$pid][$stars] ?? 0) + 1;
    }

    $out = [];
    foreach ($counts as $pid => $count) {
        $avg = $count > 0 ? round(($totals[$pid] ?? 0) / $count, 1) : 0;
        $out[$pid] = [
            'average' => $avg,
            'count' => (int)$count,
            'distribution' => $distributions[$pid] ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
        ];
    }

    $cache = ['mtime' => $mtime, 'data' => $out];
    return $out;
}

function addRating($productId, $rating, $review, $reviewerName, $reviewerEmail) {
    if (isMySQLBackend()) {
        $id = RatingRepository::create([
            'product_id' => (int)$productId,
            'rating' => (int)$rating,
            'review' => sanitizeInput($review),
            'reviewer_name' => sanitizeInput($reviewerName),
            'reviewer_email' => sanitizeInput($reviewerEmail),
            'date' => date('Y-m-d'),
            'verified_purchase' => 0,
        ]);
        if ($id) {
            return RatingRepository::getById($id);
        }
        return false;
    }

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
    if (isMySQLBackend()) {
        return CategoryRepository::getAllIncludingInactive();
    }
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
    if (isMySQLBackend()) {
        return CategoryRepository::getTree($includeInactive);
    }
    $categories = $includeInactive ? getAllCategories() : getCategories();
    return buildCategoryTree($categories);
}

/**
 * Get the full path of a category (Parent > Child > Grandchild)
 */
function getCategoryPath($categoryId, $separator = ' > ') {
    $categories = getAllCategories();
    $categories = is_array($categories) ? $categories : [];
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
    $categories = is_array($categories) ? $categories : [];
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
    if (isMySQLBackend()) {
        return CategoryRepository::getSubcategories((int)$parentId, $includeInactive);
    }

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
 * Get latest products added to the store (newest first).
 * Adds category_name for display (e.g. homepage hero).
 */
function getLatestProducts($limit = 6) {
    if (isMySQLBackend()) {
        return ProductRepository::getLatest($limit);
    }

    $products = getProducts(); // Already sorted newest first
    $products = array_slice($products, 0, $limit);
    foreach ($products as &$p) {
        $cat = getCategoryById($p['category_id'] ?? 0);
        $p['category_name'] = $cat ? ($cat['name'] ?? '') : '';
    }
    unset($p);
    return $products;
}

/**
 * Get featured categories
 */
function getFeaturedCategories() {
    if (isMySQLBackend()) {
        return CategoryRepository::getFeatured();
    }

    $categories = getCategories();
    return array_filter($categories, function($category) {
        return ($category['featured'] ?? false) === true;
    });
}

/**
 * Get products from category and all its sub-categories (for frontend)
 */
function getProductsFromCategoryTree($categoryId, $featured = null, $limit = null) {
    if (isMySQLBackend()) {
        return ProductRepository::getByCategory((int)$categoryId, $featured, $limit);
    }

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

    // Sort newest first (created_at if present, else id)
    usort($products, function($a, $b) {
        return getProductSortTimestamp($b) <=> getProductSortTimestamp($a);
    });

    // Apply limit
    if ($limit !== null) {
        $products = array_slice($products, 0, $limit);
    }

    return array_values($products);
}

// Customer account (passwordless email + code) constants and helpers
define('CUSTOMER_CODE_EXPIRY_SECONDS', 900);   // 15 minutes
define('CUSTOMER_INACTIVITY_DAYS', 9);
define('CUSTOMER_SESSION_COOKIE_DAYS', 30);

/**
 * Get customer account by email (normalized to lowercase).
 */
function getAccountByEmail($email) {
    $accounts = readJsonFile('accounts.json');
    $email = strtolower(trim($email));
    foreach ($accounts as $account) {
        if (strtolower((string)($account['email'] ?? '')) === $email) {
            return $account;
        }
    }
    return null;
}

/**
 * Create a new customer account. Returns account array or false on failure.
 */
function createAccount($email) {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (getAccountByEmail($email)) {
        return getAccountByEmail($email);
    }
    $accounts = readJsonFile('accounts.json');
    $newId = 1;
    if (!empty($accounts)) {
        $ids = array_column($accounts, 'id');
        $newId = (is_array($ids) && count($ids) > 0) ? (max(array_map('intval', $ids)) + 1) : 1;
    }
    $account = [
        'id' => $newId,
        'email' => $email,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login_at' => null
    ];
    $accounts[] = $account;
    if (!writeJsonFile('accounts.json', $accounts)) {
        return false;
    }
    return $account;
}

/**
 * Update account last_login_at.
 */
function updateAccountLastLogin($email) {
    $email = strtolower(trim($email));
    $accounts = readJsonFile('accounts.json');
    foreach ($accounts as $i => $acc) {
        if (strtolower((string)($acc['email'] ?? '')) === $email) {
            $accounts[$i]['last_login_at'] = date('Y-m-d H:i:s');
            return writeJsonFile('accounts.json', $accounts);
        }
    }
    return false;
}

/**
 * Create a one-time login code for email. Returns code string or false.
 */
function createEmailCode($email) {
    $email = strtolower(trim($email));
    $code = sprintf('%06d', random_int(0, 999999));
    $expiresAt = time() + CUSTOMER_CODE_EXPIRY_SECONDS;
    $codes = readJsonFile('email_codes.json');
    // Remove any existing code for this email
    $codes = array_values(array_filter($codes, function ($row) use ($email) {
        return strtolower((string)($row['email'] ?? '')) !== $email;
    }));
    $codes[] = ['email' => $email, 'code' => $code, 'expires_at' => $expiresAt];
    if (!writeJsonFile('email_codes.json', $codes)) {
        return false;
    }
    return $code;
}

/**
 * Validate code for email and consume it (remove). Returns true if valid.
 */
function validateAndConsumeEmailCode($email, $code) {
    $email = strtolower(trim($email));
    $code = trim((string)$code);
    $codes = readJsonFile('email_codes.json');
    $now = time();
    $found = false;
    $remaining = [];
    foreach ($codes as $row) {
        if (strtolower((string)($row['email'] ?? '')) === $email && (string)($row['code'] ?? '') === $code) {
            if (($row['expires_at'] ?? 0) > $now) {
                $found = true;
            }
            continue;
        }
        if (($row['expires_at'] ?? 0) > $now) {
            $remaining[] = $row;
        }
    }
    if ($found) {
        writeJsonFile('email_codes.json', $remaining);
        return true;
    }
    return false;
}

/**
 * Check if customer is logged in (session has customer_id and not expired by inactivity).
 * Call after session_start(). Inactivity check is done in header.
 */
function isCustomerLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    return !empty($_SESSION['customer_id']) && !empty($_SESSION['customer_email']);
}

/**
 * Get logged-in customer ID or null.
 */
function getLoggedInCustomerId() {
    if (!isCustomerLoggedIn()) {
        return null;
    }
    return $_SESSION['customer_id'] ?? null;
}

/**
 * Get logged-in customer email or null.
 */
function getLoggedInCustomerEmail() {
    if (!isCustomerLoggedIn()) {
        return null;
    }
    return $_SESSION['customer_email'] ?? null;
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