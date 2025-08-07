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

// Find the order
$orders = readJsonFile('orders.json');
$order = null;

foreach ($orders as $o) {
    if ($o['id'] === $orderId) {
        $order = $o;
        break;
    }
}

if (!$order) {
    header('Location: ' . getBaseUrl());
    exit;
}

// Get some featured products for recommendations
$featuredProducts = getFeaturedProductsByRating(4);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-4 mt-24">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium">Order Confirmation</span>
        </nav>
    </div>
</div>

<!-- Success Page -->
<section class="bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <!-- Success Icon -->
                <div class="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-r from-green-400 to-green-600 rounded-full mb-8 shadow-xl">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                
                <!-- Success Message -->
                <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                    Order 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                        Confirmed!
                    </span>
                </h1>
                <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6"></div>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Thank you for choosing Angel Marketplace! Your order has been successfully placed and we're already working to get it to you.
                </p>
            </div>
            
            <!-- Order Details Card -->
            <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-gray-200 mb-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Order Info -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 text-folly mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Order Details
                        </h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Order Number:</span>
                                <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($order['id']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Order Date:</span>
                                <span class="text-gray-900"><?php echo date('F j, Y', strtotime($order['created_at'] ?? $order['date'])); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Amount:</span>
                                <?php $orderCurrency = $order['currency'] ?? (getSettings()['currency_code'] ?? 'GBP'); ?>
                                <span class="font-semibold text-gray-900"><?php echo formatPriceWithCurrency($order['total'], $orderCurrency); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Method:</span>
                                <span class="text-gray-900 capitalize"><?php echo str_replace('_', ' ', $order['payment_method'] ?? ($order['customer']['payment_method'] ?? 'Unknown')); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Info -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 text-folly mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Shipping Information
                        </h2>
                        <div class="text-sm text-gray-600">
                            <?php if (isset($order['customer_name'])): ?>
                                <!-- New order structure -->
                                <p class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </p>
                                <p><?php echo htmlspecialchars($order['shipping_address']['line1']); ?></p>
                                <?php if (!empty($order['shipping_address']['line2'])): ?>
                                    <p><?php echo htmlspecialchars($order['shipping_address']['line2']); ?></p>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($order['shipping_address']['city'] . ', ' . $order['shipping_address']['postcode']); ?></p>
                                <p><?php echo htmlspecialchars($order['shipping_address']['country']); ?></p>
                                <p class="mt-2"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                                <?php if (!empty($order['customer_phone'])): ?>
                                    <p><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Legacy order structure -->
                                <p class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($order['customer']['first_name'] . ' ' . $order['customer']['last_name']); ?>
                                </p>
                                <p><?php echo htmlspecialchars($order['customer']['address']); ?></p>
                                <p><?php echo htmlspecialchars($order['customer']['city'] . ', ' . $order['customer']['postal_code']); ?></p>
                                <p><?php echo htmlspecialchars($order['customer']['country']); ?></p>
                                <p class="mt-2"><?php echo htmlspecialchars($order['customer']['email']); ?></p>
                                <?php if (!empty($order['customer']['phone'])): ?>
                                    <p><?php echo htmlspecialchars($order['customer']['phone']); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Items List -->
                <div class="mt-8 border-t border-gray-200 pt-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 text-folly mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"></path>
                        </svg>
                        Items Ordered
                    </h2>
                    <div class="space-y-4">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="flex items-center">
                                <?php 
                                // Handle both old and new item structures
                                if (isset($item['product'])) {
                                    // Legacy structure with full product data
                                    $productImage = $item['product']['image'];
                                    $productName = $item['product']['name'];
                                    $productPrice = $item['product']['price'];
                                    $itemTotal = $item['item_total'];
                                } else {
                                    // New structure with just product reference
                                    $product = getProductById($item['product_id']);
                                    $productImage = $product ? $product['image'] : 'general/placeholder.jpg';
                                    $productName = $item['product_name'];
                                    $productPrice = $item['price'];
                                    $itemTotal = $item['subtotal'];
                                }
                                ?>
                                <img 
                                    src="<?php echo getAssetUrl('images/' . $productImage); ?>" 
                                    alt="<?php echo htmlspecialchars($productName); ?>"
                                    class="w-16 h-16 object-cover rounded border border-gray-200"
                                    onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                >
                                <div class="ml-4 flex-1">
                                    <h4 class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($productName); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        Quantity: <?php echo $item['quantity']; ?> × <?php echo formatPriceWithCurrency($productPrice, $orderCurrency); ?>
                                    </p>
                                </div>
                                <div class="font-semibold text-gray-900">
                                    <?php echo formatPriceWithCurrency($itemTotal, $orderCurrency); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Order Totals -->
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="text-gray-900"><?php echo formatPriceWithCurrency($order['subtotal'], $orderCurrency); ?></span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Shipping:</span>
                            <span class="text-gray-900"><?php echo formatPriceWithCurrency($order['shipping_cost'], $orderCurrency); ?></span>
                        </div>
                        <div class="flex justify-between text-lg font-semibold border-t border-gray-200 pt-2">
                            <span class="text-gray-900">Total:</span>
                            <span class="text-gray-900"><?php echo formatPriceWithCurrency($order['total'], $orderCurrency); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- What's Next -->
            <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-gray-200 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">What Happens Next?</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center group">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-folly to-folly-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-200 transform group-hover:scale-105">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2">Order Confirmation</h3>
                        <p class="text-gray-600">You'll receive a confirmation email shortly with your order details and tracking information.</p>
                    </div>
                    
                    <div class="text-center group">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-tangerine to-tangerine-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-200 transform group-hover:scale-105">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2">Processing</h3>
                        <p class="text-gray-600">We'll carefully prepare and process your order within 1-2 business days.</p>
                    </div>
                    
                    <div class="text-center group">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-charcoal-600 to-charcoal-800 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-200 transform group-hover:scale-105">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-6m-10 0h6"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2">Shipping & Delivery</h3>
                        <p class="text-gray-600">Your order will be shipped within 3-7 business days with full tracking provided.</p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="<?php echo getBaseUrl(); ?>" class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Continue Shopping
                </a>
                <a href="<?php echo getBaseUrl('contact.php'); ?>" class="bg-white border-2 border-gray-300 hover:border-folly hover:bg-folly-50 text-gray-700 hover:text-folly-700 px-8 py-4 rounded-xl font-bold text-lg transition-all duration-200 transform hover:scale-105 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Recommended Products -->
<?php if (!empty($featuredProducts)): ?>
<section class="bg-gradient-to-br from-gray-50 via-white to-folly-50 py-20">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                You Might Also 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                    Love
                </span>
            </h2>
            <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6"></div>
            <p class="text-xl text-gray-600">Discover more amazing products from our curated collection</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 max-w-6xl mx-auto">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200 hover:shadow-xl transition-all duration-200 overflow-hidden transform hover:scale-105">
                    <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                        <img 
                            src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="w-full h-48 object-cover"
                            onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                        >
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 mb-2 text-sm">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>
                        
                        <!-- Rating stars (real data) -->
                        <div class="flex items-center mb-3">
                            <?php 
                            $orderRatingStats = getProductRatingStats($product['id']);
                            echo renderStars($orderRatingStats['average'], 5, 'w-3 h-3');
                            ?>
                            <span class="ml-2 text-xs text-gray-500">
                                <?php if ($orderRatingStats['count'] > 0): ?>
                                    (<?php echo $orderRatingStats['average']; ?>)
                                <?php else: ?>
                                    (No reviews)
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900">
                                <?php echo formatProductPrice($product, $orderCurrency ?? getSelectedCurrency()); ?>
                            </span>
                            <a 
                                href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" 
                                class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 transform hover:scale-105 shadow-md hover:shadow-lg"
                            >
                                View
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>