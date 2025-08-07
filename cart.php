<?php
$page_title = 'Shopping Cart';
$page_description = 'Review your selected items and proceed to checkout at Angel Marketplace.';

require_once 'includes/functions.php';

// Start session for currency persistence
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load settings for shipping calculation
$settings = getSettings();

// Handle currency selection
$availableCurrencies = [];
foreach ($settings['currencies'] as $curr) {
    $availableCurrencies[] = $curr['code'];
}

// Get selected currency from GET, POST, session, or default to settings
$selectedCurrency = $_POST['selected_currency'] ?? $_GET['currency'] ?? $_SESSION['selected_currency'] ?? $settings['currency_code'] ?? 'GBP';

// Validate selected currency
if (!in_array($selectedCurrency, $availableCurrencies)) {
    $selectedCurrency = $settings['currency_code'] ?? 'GBP';
}

// Store selected currency in session for persistence
$_SESSION['selected_currency'] = $selectedCurrency;

// Handle AJAX currency change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_currency') {
    header('Content-Type: application/json');
    
    $newCurrency = sanitizeInput($_POST['currency'] ?? '');
    if (in_array($newCurrency, $availableCurrencies)) {
        // Store the new currency in session for persistence
        $_SESSION['selected_currency'] = $newCurrency;
        $selectedCurrency = $newCurrency;
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid currency']);
        exit;
    }
}

$cart = getCart();
$cartItems = [];
$subtotal = 0;

// Get detailed cart information
foreach ($cart as $item) {
    $product = getProductById($item['product_id']);
    if ($product) {
        // Get price in selected currency
        $price = getProductPrice($product, $selectedCurrency);
        $itemTotal = $price * $item['quantity'];
        
        $cartItem = [
            'product' => $product,
            'quantity' => $item['quantity'],
            'unit_price' => $price,
            'item_total' => $itemTotal
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
        $subtotal += $itemTotal;
    }
}

// Calculate shipping costs based on settings and selected currency
$currencySettings = $settings['shipping']['costs'][$selectedCurrency] ?? [];
$freeShippingThreshold = $currencySettings['free_threshold'] ?? $settings['shipping']['free_shipping_threshold'] ?? 0;
$standardShippingCost = $currencySettings['standard'] ?? $settings['shipping']['standard_shipping_cost'] ?? 0;

// Calculate shipping cost
if ($freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold) {
    $shippingCost = 0; // Free shipping
} else {
    $shippingCost = $standardShippingCost; // Standard shipping cost
}

$total = $subtotal + $shippingCost;

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-3 md:py-4 mt-16 md:mt-20">
    <div class="container mx-auto px-4">
        <nav class="text-xs md:text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium">Shopping Cart</span>
        </nav>
    </div>
</div>

<!-- Cart Content -->
<section class="bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                    Shopping 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                        Cart
                    </span>
                </h1>
                <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full"></div>
            </div>
            
            <?php if (empty($cartItems)): ?>
                <!-- Empty Cart -->
                <div class="text-center py-20">
                    <div class="bg-white/80 backdrop-blur-sm p-12 rounded-3xl shadow-xl border border-gray-200 max-w-2xl mx-auto">
                        <div class="text-gray-400 mb-8">
                            <svg class="w-32 h-32 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l-2.5 5m12.5 0H9"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Your cart is empty</h2>
                        <p class="text-gray-600 mb-10 text-lg leading-relaxed">Looks like you haven't added any items to your cart yet. <span class="font-semibold text-folly">Start shopping to discover amazing products!</span></p>
                        <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-10 py-4 rounded-xl font-bold text-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl inline-flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            Continue Shopping
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Cart Items -->
                <div class="space-y-6 mb-12">
                    <?php foreach ($cartItems as $index => $item): ?>
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-8 shadow-xl border border-gray-200 flex flex-col md:flex-row items-center space-y-6 md:space-y-0 md:space-x-8" id="cart-item-<?php echo $item['product']['id']; ?>">
                            <!-- Product Image -->
                            <div class="flex-shrink-0">
                                <img 
                                    src="<?php echo getAssetUrl('images/' . $item['product']['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($item['product']['name']); ?>"
                                    class="w-24 h-24 object-cover rounded-2xl border border-gray-200 shadow-lg"
                                    onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                >
                            </div>
                            
                            <!-- Product Details -->
                            <div class="flex-1 text-center md:text-left">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">
                                    <a href="<?php echo getBaseUrl('product.php?slug=' . $item['product']['slug']); ?>" class="hover:text-folly transition-colors duration-200">
                                        <?php echo htmlspecialchars($item['product']['name']); ?>
                                    </a>
                                </h3>
                                
                                <?php if (isset($item['size']) && !empty($item['size'])): ?>
                                    <div class="inline-block bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium mb-2 mr-2">
                                        Size: <?php echo htmlspecialchars($item['size']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($item['color']) && !empty($item['color'])): ?>
                                    <?php 
                                    $color_hex_map = [
                                        'Black' => '#000000', 'White' => '#FFFFFF', 'Red' => '#FF0000', 
                                        'Blue' => '#0000FF', 'Green' => '#008000', 'Yellow' => '#FFFF00',
                                        'Pink' => '#FFC0CB', 'Purple' => '#800080', 'Orange' => '#FFA500',
                                        'Brown' => '#8B4513', 'Gray' => '#808080', 'Navy' => '#000080'
                                    ];
                                    $hex_color = $color_hex_map[$item['color']] ?? '#6366F1'; // fallback to indigo
                                    $is_light_color = in_array($item['color'], ['White', 'Yellow', 'Pink']);
                                    $text_color = $is_light_color ? '#374151' : '#FFFFFF'; // dark text for light colors, white for dark colors
                                    ?>
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium mb-2 mr-2" 
                                         style="background-color: <?php echo $hex_color; ?>; color: <?php echo $text_color; ?>;">
                                        <div class="w-2.5 h-2.5 rounded-full border border-white/30" style="background-color: <?php echo $hex_color; ?>;"></div>
                                        Color: <?php echo htmlspecialchars($item['color']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="text-gray-600 mb-3 leading-relaxed">
                                    <?php echo htmlspecialchars($item['product']['description']); ?>
                                </p>
                                
                                <!-- Multi-currency pricing display -->
                                <?php if (isset($item['product']['prices']) && is_array($item['product']['prices']) && count($item['product']['prices']) > 1): ?>
                                    <div class="text-sm text-gray-600 mb-2">
                                        <strong>Available prices:</strong>
                                        <?php 
                                        $priceEntries = [];
                                        foreach ($item['product']['prices'] as $currencyCode => $price) {
                                            $currency = null;
                                            foreach ($settings['currencies'] as $curr) {
                                                if ($curr['code'] === $currencyCode) {
                                                    $currency = $curr;
                                                    break;
                                                }
                                            }
                                            $symbol = $currency ? $currency['symbol'] : $currencyCode;
                                            $priceEntries[] = $symbol . number_format($price, 2);
                                        }
                                        echo implode(' | ', $priceEntries);
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="inline-block bg-folly-50 text-folly-800 px-3 py-1 rounded-full text-sm font-semibold">
                                    <?php echo formatProductPrice($item['product']); ?> each
                                </div>
                            </div>
                            
                            <!-- Quantity Controls -->
                            <div class="bg-gray-50 rounded-2xl p-4 flex items-center space-x-4">
                                <button 
                                    onclick="updateQuantity(<?php echo $item['product']['id']; ?>, <?php echo $item['quantity'] - 1; ?>)"
                                    class="w-10 h-10 rounded-xl bg-white hover:bg-gray-100 flex items-center justify-center text-gray-600 transition-all duration-200 shadow-sm hover:shadow-md border border-gray-200"
                                    <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                </button>
                                <span class="w-16 text-center font-bold text-lg text-gray-900" id="quantity-<?php echo $item['product']['id']; ?>">
                                    <?php echo $item['quantity']; ?>
                                </span>
                                <button 
                                    onclick="updateQuantity(<?php echo $item['product']['id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                    class="w-10 h-10 rounded-xl bg-white hover:bg-gray-100 flex items-center justify-center text-gray-600 transition-all duration-200 shadow-sm hover:shadow-md border border-gray-200"
                                    <?php echo $item['quantity'] >= $item['product']['stock'] ? 'disabled' : ''; ?>
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Item Total -->
                            <div class="bg-folly-50 rounded-2xl p-4 min-w-0 md:min-w-[120px] text-center">
                                <div class="text-sm text-folly font-medium mb-1">Total</div>
                                <span class="text-2xl font-bold text-folly-800" id="item-total-<?php echo $item['product']['id']; ?>">
                                    <?php echo formatPriceWithCurrency($item['item_total'], $selectedCurrency); ?>
                                </span>
                            </div>
                            
                            <!-- Remove Button -->
                            <button 
                                onclick="removeFromCart(<?php echo $item['product']['id']; ?>)"
                                class="bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700 p-3 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md border border-red-200"
                                title="Remove item"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Currency Selection -->
                <?php if (count($availableCurrencies) > 1): ?>
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-gray-200 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Select Currency</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                        <?php foreach ($settings['currencies'] as $currency): ?>
                            <button 
                                type="button"
                                class="currency-selector px-4 py-2 rounded-xl border-2 transition-all duration-200 flex items-center justify-center gap-2 <?php echo $selectedCurrency === $currency['code'] ? 'border-folly-500 bg-folly-50 text-folly-800 font-semibold' : 'border-gray-300 hover:border-folly-300 text-gray-700'; ?>"
                                data-currency="<?php echo $currency['code']; ?>"
                                onclick="changeCurrency('<?php echo $currency['code']; ?>')"
                            >
                                <span class="text-lg"><?php echo $currency['symbol']; ?></span>
                                <span><?php echo $currency['code']; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Cart Summary -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-8 shadow-xl border border-gray-200">
                    <div class="flex flex-col lg:flex-row justify-between items-center space-y-6 lg:space-y-0">
                        <div class="text-center lg:text-left">
                            <!-- Cart Totals -->
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between text-lg">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-semibold text-gray-900"><?php echo formatPriceWithCurrency($subtotal, $selectedCurrency); ?></span>
                                </div>
                                <div class="flex justify-between text-lg">
                                    <span class="text-gray-600">Shipping:</span>
                                    <span class="font-semibold <?php echo $shippingCost > 0 ? 'text-gray-900' : 'text-green-600'; ?>">
                                        <?php if ($shippingCost > 0): ?>
                                            <?php echo formatPriceWithCurrency($shippingCost, $selectedCurrency); ?>
                                        <?php else: ?>
                                            Free
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="border-t border-gray-200 pt-2">
                                    <div class="flex justify-between text-2xl font-bold">
                                        <span class="text-gray-900">Total:</span>
                                        <span class="text-folly" id="cart-total"><?php echo formatPriceWithCurrency($total, $selectedCurrency); ?></span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-gray-600 text-lg mb-4">
                                <?php echo count($cartItems); ?> item(s) in your cart
                            </p>
                            <?php if ($freeShippingThreshold > 0 && $subtotal < $freeShippingThreshold): ?>
                                <div class="mt-2 inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                                    🚚 Add <?php echo formatPriceWithCurrency($freeShippingThreshold - $subtotal, $selectedCurrency); ?> more for free shipping
                                </div>
                            <?php elseif ($shippingCost == 0): ?>
                                <div class="mt-2 inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                    🚚 Free shipping included
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                            <button 
                                onclick="clearCart()"
                                class="bg-red-50 hover:bg-red-100 text-red-700 px-6 py-3 rounded-xl font-semibold transition-all duration-200 border border-red-200 hover:border-red-300"
                            >
                                Clear Cart
                            </button>
                            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-xl font-semibold text-center transition-all duration-200 border-2 border-gray-300 hover:border-gray-400">
                                Continue Shopping
                            </a>
                            <a href="<?php echo getBaseUrl('checkout.php'); ?>" class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-10 py-3 rounded-xl font-bold text-center transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>



<script>
// Helper function to construct API URLs properly (same as cart.js)
function getApiUrl(endpoint) {
    const basePath = getBasePath();
    return basePath ? `${basePath}/${endpoint}` : `/${endpoint}`;
}

// Get base path dynamically (same as cart.js)
function getBasePath() {
    // Try to detect from script tag first
    const scriptTags = document.querySelectorAll('script[src*="assets/js"]');
    if (scriptTags.length > 0) {
        const scriptSrc = scriptTags[0].src;
        const url = new URL(scriptSrc);
        const scriptPath = url.pathname;
        // Remove '/assets/js/cart.js' or similar from the end
        let basePath = scriptPath.replace(/\/assets\/js\/.*$/, '');
        
        // If basePath is empty or just '/', return empty string (root)
        if (!basePath || basePath === '/') {
            return '';
        }
        
        return basePath;
    }
    
    // Fallback: try to detect from current path by looking for known files
    const currentPath = window.location.pathname;
    
    // If we're at root, return empty string
    if (currentPath === '/' || currentPath === '') {
        return '';
    }
    
    // Look for application indicators to find base path
    const pathParts = currentPath.split('/').filter(part => part);
    const appFiles = ['index.php', 'shop.php', 'cart.php', 'checkout.php', 'about.php', 'contact.php'];
    
    // Check if current file is an app file (indicates we're in the app root)
    const currentFile = pathParts[pathParts.length - 1];
    if (appFiles.includes(currentFile)) {
        const basePath = '/' + pathParts.slice(0, -1).join('/');
        return basePath === '/' ? '' : basePath;
    }
    
    // Default fallback
    return '';
}

function updateQuantity(productId, newQuantity) {
    if (newQuantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    fetch(getApiUrl('api/cart.php'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            product_id: productId,
            quantity: newQuantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Quantity updated', 'info');
            location.reload();
        } else {
            showNotification('Error: ' + (data.message || 'Failed to update quantity.'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating the cart.', 'error');
    });
}

// Simple remove function like minicart - no confirmation needed
function removeFromCart(productId) {
    console.log('Removing product:', productId);
    
    fetch(getApiUrl('api/cart.php'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Remove response:', data);
        if (data.success) {
            showNotification('Item removed from cart', 'info');
            location.reload();
        } else {
            showNotification('Error removing item: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error removing item from cart', 'error');
    });
}

// Simple clear function like minicart - no confirmation needed
function clearCart() {
    console.log('Clearing cart');
    
    fetch(getApiUrl('api/cart.php'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'clear'
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Clear response:', data);
        if (data.success) {
            showNotification('Cart cleared', 'info');
            location.reload();
        } else {
            showNotification('Error clearing cart: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error clearing cart', 'error');
    });
}

// Simple notification function like the minicart uses
function showNotification(message, type = 'info') {
    // Try to use existing notification system if available
    try {
        if (window.showNotification && typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }
    } catch (e) {
        // Fallback to our custom notification
    }
    
    // Fallback notification system
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full max-w-sm`;
    
    // Set styles based on type
    switch (type) {
        case 'success':
            notification.className += ' bg-green-500 text-white';
            break;
        case 'error':
            notification.className += ' bg-red-500 text-white';
            break;
        case 'warning':
            notification.className += ' bg-orange-500 text-white';
            break;
        default:
            notification.className += ' text-white';
            notification.style.backgroundColor = '#FF0055'; // folly color for info/default
    }
    
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"></path>
            </svg>
            <span>${escapeHtml(message)}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Currency change function
async function changeCurrency(newCurrency) {
    console.log('Currency change requested:', newCurrency);
    try {
        // Disable all currency buttons during change
        const currencyButtons = document.querySelectorAll('.currency-selector');
        currencyButtons.forEach(button => {
            button.disabled = true;
            button.classList.add('opacity-50');
        });
        
        // Show loading indicator
        showNotification('Updating currency...', 'info');
        
        // Send AJAX request
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=change_currency&currency=${encodeURIComponent(newCurrency)}`
        });
        
        // Reload the page with the new currency
        window.location.href = window.location.pathname + '?currency=' + encodeURIComponent(newCurrency);
        
    } catch (error) {
        console.error('Currency change error:', error);
        
        // Re-enable currency buttons
        const currencyButtons = document.querySelectorAll('.currency-selector');
        currencyButtons.forEach(button => {
            button.disabled = false;
            button.classList.remove('opacity-50');
        });
        
        // Show error message
        showNotification('Failed to change currency: ' + (error?.message || 'Unknown error'), 'error');
    }
}
</script>

<?php include 'includes/footer.php'; ?>