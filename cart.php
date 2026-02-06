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
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid currency']);
    }
    exit;
}

$cart = getCart();
$cartItems = [];
$subtotal = 0;

// Track which currencies are available for ALL products in cart
$cartProductCurrencies = null; // Will hold intersection of all product currencies

// Get detailed cart information
foreach ($cart as $item) {
    $product = getProductById($item['product_id']);
    if ($product) {
        // Track currencies available for this product
        $productCurrencies = [];
        if (isset($product['prices']) && is_array($product['prices'])) {
            $productCurrencies = array_keys($product['prices']);
        }
        // If product uses old single price field, assume default currency only
        if (empty($productCurrencies) && isset($product['price'])) {
            $productCurrencies = [$settings['currency_code'] ?? 'GBP'];
        }

        // Intersect with previous products' currencies
        if ($cartProductCurrencies === null) {
            $cartProductCurrencies = $productCurrencies;
        } else {
            $cartProductCurrencies = array_intersect($cartProductCurrencies, $productCurrencies);
        }

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

        // Compute cart key to allow per-variant removal
        $cartKey = $product['id'] .
                   (isset($item['size']) && !empty($item['size']) ? '_size_' . $item['size'] : '') .
                   (isset($item['color']) && !empty($item['color']) ? '_color_' . $item['color'] : '');
        $cartItem['cart_key'] = $cartKey;

        $cartItems[] = $cartItem;
        $subtotal += $itemTotal;
    }
}

// Filter available currencies to only those supported by ALL cart products
if ($cartProductCurrencies !== null && !empty($cartProductCurrencies)) {
    $availableCurrencies = array_intersect($availableCurrencies, $cartProductCurrencies);
}

// If selected currency is no longer available, switch to first available
if (!empty($availableCurrencies) && !in_array($selectedCurrency, $availableCurrencies)) {
    $selectedCurrency = reset($availableCurrencies);
    $_SESSION['selected_currency'] = $selectedCurrency;
    // Recalculate prices with new currency
    $subtotal = 0;
    foreach ($cartItems as &$cartItem) {
        $cartItem['unit_price'] = getProductPrice($cartItem['product'], $selectedCurrency);
        $cartItem['item_total'] = $cartItem['unit_price'] * $cartItem['quantity'];
        $subtotal += $cartItem['item_total'];
    }
    unset($cartItem);
}

// Calculate shipping costs based on settings and selected currency
$shippingSettings = getShippingSettings();
$selectedMethod = $_SESSION['shipping_method'] ?? getDefaultShippingMethod($shippingSettings);
$selectedMethod = validateShippingMethod($selectedMethod, $shippingSettings);
$shippingCost = computeShippingCost($subtotal, $selectedCurrency, $selectedMethod, $shippingSettings);

$total = $subtotal + $shippingCost;

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">Shopping Cart</span>
        </nav>
    </div>
</div>

<!-- Cart Section -->
<section class="py-12 md:py-20 bg-gradient-to-b from-gray-50 to-white min-h-screen">
    <div class="container mx-auto px-4">
        <p class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-3">Shopping Cart</p>
        <h1 class="text-3xl md:text-4xl font-bold text-charcoal-900 mb-8 font-display tracking-tight">Your Cart</h1>
        
        <?php if (empty($cartItems)): ?>
            <div class="glass-strong rounded-3xl p-12 text-center max-w-2xl mx-auto">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-charcoal-900 mb-4">Your cart is empty</h2>
                <p class="text-gray-600 mb-8">Looks like you haven't added any items to your cart yet.</p>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center gap-2 bg-folly hover:bg-folly-600 text-white px-8 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Cart Items List -->
                <div class="lg:w-2/3 space-y-6">
                    <div class="glass-strong rounded-3xl overflow-hidden">
                        <div class="p-6 md:p-8">
                            <div class="space-y-8">
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="flex flex-col md:flex-row gap-6 pb-8 border-b border-gray-100 last:border-0 last:pb-0">
                                        <!-- Product Image -->
                                        <div class="w-full md:w-32 h-32 flex-shrink-0 bg-gray-50 rounded-2xl overflow-hidden">
                                            <img 
                                                src="<?php echo getAssetUrl('images/' . $item['product']['image']); ?>" 
                                                alt="<?php echo htmlspecialchars($item['product']['name']); ?>"
                                                class="w-full h-full object-cover"
                                                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                            >
                                        </div>
                                        
                                        <!-- Product Info -->
                                        <div class="flex-grow">
                                            <div class="flex justify-between items-start mb-2">
                                                <h3 class="text-lg font-bold text-charcoal-900">
                                                    <a href="<?php echo getBaseUrl('product.php?slug=' . $item['product']['slug']); ?>" class="hover:text-folly transition-colors">
                                                        <?php echo htmlspecialchars($item['product']['name']); ?>
                                                    </a>
                                                </h3>
                                                <button onclick="removeFromCartKey('<?php echo $item['cart_key']; ?>')" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                            
                                            <div class="flex flex-wrap gap-2 mb-4">
                                                <?php if (isset($item['size']) && !empty($item['size'])): ?>
                                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg">
                                                        Size: <?php echo htmlspecialchars($item['size']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($item['color']) && !empty($item['color'])): ?>
                                                    <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg flex items-center gap-2">
                                                        Color: <?php echo htmlspecialchars($item['color']); ?>
                                                        <span class="w-3 h-3 rounded-full border border-gray-300" style="background-color: <?php echo htmlspecialchars($item['color']); ?>"></span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="flex flex-wrap items-center justify-between gap-4 mt-auto">
                                                <div class="flex items-center bg-gray-50 rounded-xl p-1 border border-gray-200">
                                                    <button 
                                                        onclick="updateQuantity(<?php echo $item['product']['id']; ?>, <?php echo $item['quantity'] - 1; ?>)"
                                                        class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-charcoal-900 hover:bg-white rounded-lg transition-all"
                                                        <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                                    </button>
                                                    <span class="w-10 text-center font-bold text-charcoal-900 text-sm"><?php echo $item['quantity']; ?></span>
                                                    <button 
                                                        onclick="updateQuantity(<?php echo $item['product']['id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                                        class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-charcoal-900 hover:bg-white rounded-lg transition-all"
                                                        <?php echo $item['quantity'] >= $item['product']['stock'] ? 'disabled' : ''; ?>
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                                    </button>
                                                </div>
                                                
                                                <div class="text-right">
                                                    <div class="text-lg font-bold text-charcoal-900">
                                                        <?php echo formatPriceWithCurrency($item['item_total'], $selectedCurrency); ?>
                                                    </div>
                                                    <?php if ($item['quantity'] > 1): ?>
                                                        <div class="text-xs text-gray-500">
                                                            <?php echo formatPriceWithCurrency($item['unit_price'], $selectedCurrency); ?> each
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Cart Actions -->
                        <div class="bg-gray-50 p-6 border-t border-gray-100 flex justify-between items-center">
                            <button onclick="clearCart()" class="text-red-500 hover:text-red-700 font-medium text-sm flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Clear Cart
                            </button>
                            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-charcoal-900 hover:text-folly font-bold text-sm flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:w-1/3">
                    <div class="glass-strong rounded-3xl p-6 md:p-8 sticky top-24">
                        <h2 class="text-xl font-bold text-charcoal-900 mb-6">Order Summary</h2>
                        
                        <!-- Currency Selector (only shows currencies available for ALL cart products) -->
                        <?php if (count($availableCurrencies) > 1): ?>
                            <div class="mb-6">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Currency</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <?php foreach ($settings['currencies'] as $currency):
                                        // Only show currencies that are available for all cart products
                                        if (!in_array($currency['code'], $availableCurrencies)) continue;
                                    ?>
                                        <button
                                            type="button"
                                            class="currency-selector px-3 py-2 rounded-xl border text-sm font-bold transition-all <?php echo $selectedCurrency === $currency['code'] ? 'border-folly bg-folly text-white shadow-md' : 'border-gray-200 text-gray-600 hover:border-folly hover:text-folly'; ?>"
                                            onclick="changeCurrency('<?php echo $currency['code']; ?>')"
                                        >
                                            <?php echo $currency['code']; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="space-y-4 mb-6 pb-6 border-b border-gray-100">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal</span>
                                <span class="font-bold text-charcoal-900"><?php echo formatPriceWithCurrency($subtotal, $selectedCurrency); ?></span>
                            </div>
                            
                            <?php if (!empty($shippingSettings['show_shipping_pre_checkout'])): ?>
                                <div class="flex justify-between text-gray-600">
                                    <span>Shipping</span>
                                    <span class="font-bold <?php echo $shippingCost > 0 ? 'text-charcoal-900' : 'text-green-600'; ?>">
                                        <?php echo $shippingCost > 0 ? formatPriceWithCurrency($shippingCost, $selectedCurrency) : 'Free'; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex justify-between items-end mb-8">
                            <span class="text-lg font-bold text-charcoal-900">Total</span>
                            <span class="text-3xl font-bold text-folly font-display"><?php echo formatPriceWithCurrency($total, $selectedCurrency); ?></span>
                        </div>
                        
                        <a href="<?php echo getBaseUrl('checkout.php'); ?>" class="w-full block bg-gradient-to-r from-charcoal-900 to-charcoal-800 hover:from-folly hover:to-folly-500 text-white text-center font-semibold py-4 rounded-2xl transition-all duration-300 shadow-lg hover:shadow-folly/25">
                            Proceed to Checkout
                        </a>
                        
                        <div class="mt-6 flex items-center justify-center gap-4 text-gray-400">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span class="text-xs font-medium">Secure Checkout</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Helper function to construct API URLs properly
function getApiUrl(endpoint) {
    // Simple logic to handle base path if needed, assuming relative for now
    return endpoint;
}

function updateQuantity(productId, newQuantity) {
    if (newQuantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    fetch('api/cart.php', {
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
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: data.message || 'Failed to update quantity.',
                confirmButtonColor: '#FF0055'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function removeFromCart(productId) {
    fetch('api/cart.php', {
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
        if (data.success) {
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: data.message || 'Error removing item.',
                confirmButtonColor: '#FF0055'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function removeFromCartKey(cartKey) {
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            cart_key: cartKey
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: data.message || 'Error removing item.',
                confirmButtonColor: '#FF0055'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function clearCart() {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will remove all items from your cart.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FF0055',
        cancelButtonColor: '#3B4255',
        confirmButtonText: 'Yes, clear it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/cart.php', {
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
                if (data.success) {
                    location.reload();
                }
            });
        }
    });
}

async function changeCurrency(newCurrency) {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=change_currency&currency=${encodeURIComponent(newCurrency)}`
        });
        
        const data = await response.json();
        if (data.success) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Currency change error:', error);
    }
}
</script>

<?php include 'includes/footer.php'; ?>