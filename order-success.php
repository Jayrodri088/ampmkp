<?php
$page_title = 'Order Confirmed';
$page_description = 'Thank you for your order! Your order has been successfully placed at Angel Marketplace.';

require_once 'includes/functions.php';

// Get order ID from URL
$orderId = $_GET['order'] ?? '';

if (empty($orderId)) {
    header('Location: ' . getBaseUrl());
    exit;
}

// Find the order (JSON or MySQL backend)
$order = getOrderById($orderId);

if (!$order) {
    header('Location: ' . getBaseUrl());
    exit;
}

// Get some featured products for recommendations
$featuredProducts = getFeaturedProductsByRating(4);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">Order Confirmation</span>
        </nav>
    </div>
</div>

<!-- Success Hero -->
<section class="bg-charcoal-900 py-16 md:py-24 relative overflow-hidden">
    <!-- Confetti/Pattern Background -->
    <div class="absolute inset-0 opacity-20 bg-[url('assets/images/pattern.png')]"></div>
    <div class="absolute top-0 left-0 w-96 h-96 bg-folly rounded-full mix-blend-overlay filter blur-3xl opacity-20 animate-pulse"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-tangerine rounded-full mix-blend-overlay filter blur-3xl opacity-20"></div>
    
    <div class="container mx-auto px-4 relative z-10 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-500 rounded-full mb-8 shadow-lg shadow-green-500/30 animate-bounce-slow">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        
        <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 font-display tracking-tight">
            Order Confirmed!
        </h1>
        <p class="text-xl text-gray-300 max-w-2xl mx-auto leading-relaxed font-light">
            Thank you for choosing Angel Marketplace. Your order <span class="text-white font-mono font-bold">#<?php echo htmlspecialchars($order['id']); ?></span> has been successfully placed.
        </p>
    </div>
</section>

<!-- Order Details Section -->
<section class="bg-gradient-to-b from-gray-50 to-white py-12 md:py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">

            <!-- Status Steps -->
            <div class="glass-strong rounded-2xl shadow-soft p-8 mb-12">
                <h2 class="text-center text-xl font-bold text-charcoal-900 mb-8 font-display">What Happens Next?</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                    <!-- Connecting Line (Desktop) -->
                    <div class="hidden md:block absolute top-8 left-0 w-full h-1 bg-gray-100 -z-10 transform -translate-y-1/2"></div>
                    
                    <!-- Step 1 -->
                    <div class="text-center relative">
                        <div class="w-16 h-16 mx-auto bg-folly text-white rounded-full flex items-center justify-center shadow-lg shadow-folly/30 mb-4 relative z-10">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="font-bold text-charcoal-900 mb-2">Order Confirmed</h3>
                        <p class="text-sm text-gray-500">We've received your order and sent a confirmation email.</p>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="text-center relative">
                        <div class="w-16 h-16 mx-auto bg-white border-2 border-gray-200 text-gray-400 rounded-full flex items-center justify-center mb-4 relative z-10">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"></path></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2">Processing</h3>
                        <p class="text-sm text-gray-500">We'll prepare your items for shipment within 1-2 days.</p>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="text-center relative">
                        <div class="w-16 h-16 mx-auto bg-white border-2 border-gray-200 text-gray-400 rounded-full flex items-center justify-center mb-4 relative z-10">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2">Shipped</h3>
                        <p class="text-sm text-gray-500">Your order will be on its way to you!</p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order Info -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Items -->
                    <div class="glass-strong rounded-2xl shadow-soft overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-charcoal-900 font-display text-lg">Order Items</h3>
                            <span class="text-sm text-gray-500"><?php echo count($order['items']); ?> items</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <?php foreach ($order['items'] as $item): ?>
                                <?php 
                                // Handle both old and new item structures
                                if (isset($item['product'])) {
                                    $productImage = $item['product']['image'];
                                    $productName = $item['product']['name'];
                                    $productPrice = $item['product']['price'];
                                    $itemTotal = $item['item_total'];
                                } else {
                                    $product = getProductById($item['product_id']);
                                    $productImage = $product ? $product['image'] : 'general/placeholder.jpg';
                                    $productName = $item['product_name'];
                                    $productPrice = $item['price'];
                                    $itemTotal = $item['subtotal'];
                                }
                                $orderCurrency = $order['currency'] ?? (getSettings()['currency_code'] ?? 'GBP');
                                ?>
                                <div class="p-6 flex gap-4">
                                    <div class="w-20 h-20 rounded-xl bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                                        <img 
                                            src="<?php echo getAssetUrl('images/' . $productImage); ?>" 
                                            alt="<?php echo htmlspecialchars($productName); ?>"
                                            class="w-full h-full object-cover"
                                            onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-charcoal-900 mb-1"><?php echo htmlspecialchars($productName); ?></h4>
                                        <p class="text-sm text-gray-500 mb-2">
                                            Qty: <?php echo $item['quantity']; ?> × <?php echo formatPriceWithCurrency($productPrice, $orderCurrency); ?>
                                        </p>
                                        <?php if (isset($item['size']) || isset($item['color'])): ?>
                                            <div class="flex gap-2 text-xs text-gray-500">
                                                <?php if (isset($item['size'])): ?>
                                                    <span class="bg-gray-100 px-2 py-1 rounded">Size: <?php echo htmlspecialchars($item['size']); ?></span>
                                                <?php endif; ?>
                                                <?php if (isset($item['color'])): ?>
                                                    <span class="bg-gray-100 px-2 py-1 rounded">Color: <?php echo htmlspecialchars($item['color']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="font-bold text-charcoal-900">
                                        <?php echo formatPriceWithCurrency($itemTotal, $orderCurrency); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Customer Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="glass-strong rounded-2xl shadow-soft p-6">
                            <h3 class="font-bold text-charcoal-900 font-display mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Shipping Address
                            </h3>
                            <div class="text-sm text-gray-600 leading-relaxed">
                                <?php if (isset($order['customer_name'])): ?>
                                    <p class="font-bold text-charcoal-900 mb-1"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p><?php echo htmlspecialchars($order['shipping_address']['line1']); ?></p>
                                    <?php if (!empty($order['shipping_address']['line2'])): ?>
                                        <p><?php echo htmlspecialchars($order['shipping_address']['line2']); ?></p>
                                    <?php endif; ?>
                                    <p><?php echo htmlspecialchars($order['shipping_address']['city'] . ', ' . $order['shipping_address']['postcode']); ?></p>
                                    <p><?php echo htmlspecialchars($order['shipping_address']['country']); ?></p>
                                <?php else: ?>
                                    <p class="font-bold text-charcoal-900 mb-1"><?php echo htmlspecialchars($order['customer']['first_name'] . ' ' . $order['customer']['last_name']); ?></p>
                                    <p><?php echo htmlspecialchars($order['customer']['address']); ?></p>
                                    <p><?php echo htmlspecialchars($order['customer']['city'] . ', ' . $order['customer']['postal_code']); ?></p>
                                    <p><?php echo htmlspecialchars($order['customer']['country']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="glass-strong rounded-2xl shadow-soft p-6">
                            <h3 class="font-bold text-charcoal-900 font-display mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Payment Info
                            </h3>
                            <div class="text-sm text-gray-600 leading-relaxed">
                                <p class="mb-2">
                                    <span class="text-gray-500">Method:</span> 
                                    <span class="font-medium text-charcoal-900 capitalize"><?php echo str_replace('_', ' ', $order['payment_method'] ?? 'Unknown'); ?></span>
                                </p>
                                <p class="mb-2">
                                    <span class="text-gray-500">Status:</span> 
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </p>
                                <p class="mb-2">
                                    <span class="text-gray-500">Date:</span> 
                                    <span class="font-medium text-charcoal-900"><?php echo date('F j, Y', strtotime($order['created_at'] ?? $order['date'])); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Sidebar -->
                <div class="lg:col-span-1">
                    <div class="glass-strong rounded-2xl shadow-soft p-6">
                        <h3 class="font-bold text-charcoal-900 font-display mb-6">Order Summary</h3>
                        
                        <div class="space-y-3 pb-6 border-b border-gray-100">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium text-charcoal-900"><?php echo formatPriceWithCurrency($order['subtotal'], $orderCurrency); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium text-charcoal-900"><?php echo formatPriceWithCurrency($order['shipping_cost'], $orderCurrency); ?></span>
                            </div>
                            <?php if (isset($order['tax']) && $order['tax'] > 0): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax</span>
                                <span class="font-medium text-charcoal-900"><?php echo formatPriceWithCurrency($order['tax'], $orderCurrency); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex justify-between items-center py-4">
                            <span class="font-bold text-charcoal-900 text-lg">Total</span>
                            <span class="font-bold text-folly text-2xl"><?php echo formatPriceWithCurrency($order['total'], $orderCurrency); ?></span>
                        </div>
                        
                        <div class="mt-6 space-y-3">
                            <button onclick="window.print()" class="w-full flex items-center justify-center px-4 py-3 border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                Print Receipt
                            </button>
                            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-folly to-folly-500 hover:from-folly-600 hover:to-folly text-white rounded-xl text-sm font-bold transition-colors shadow-lg hover:shadow-folly/30">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Recommendations -->
<?php if (!empty($featuredProducts)): ?>
<section class="bg-gradient-to-b from-white to-gray-50 py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-10">
            <span class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-3 block">Recommendations</span>
            <h2 class="text-3xl font-bold text-charcoal-900 font-display tracking-tight">You Might Also Like</h2>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featuredProducts as $product): ?>
                <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="group glass rounded-xl hover:shadow-lg transition-all duration-300 overflow-hidden">
                    <div class="aspect-w-1 aspect-h-1 bg-gray-100 overflow-hidden">
                        <img 
                            src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="w-full h-48 object-cover transition-transform duration-500 group-hover:scale-110"
                            onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                        >
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-charcoal-900 text-sm mb-1 truncate group-hover:text-folly transition-colors">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>
                        <p class="font-bold text-gray-900">
                            <?php echo formatProductPrice($product, $orderCurrency ?? getSelectedCurrency()); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>