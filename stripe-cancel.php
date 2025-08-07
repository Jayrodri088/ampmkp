<?php
$page_title = 'Payment Cancelled';
$page_description = 'Your payment was cancelled. You can return to your cart to complete your order.';

require_once 'includes/functions.php';

// Get cart for display
$cart = getCart();
$cartCount = count($cart);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-4 mt-24">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <a href="<?php echo getBaseUrl('cart.php'); ?>" class="text-folly hover:text-folly-600 hover:underline">Cart</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium">Payment Cancelled</span>
        </nav>
    </div>
</div>

<!-- Cancel Message -->
<section class="bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Cancel Icon -->
            <div class="text-orange-500 mb-8">
                <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.232 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <!-- Cancel Message -->
            <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                Payment 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 via-orange-600 to-red-600">
                    Cancelled
                </span>
            </h1>
            <div class="w-24 h-1 bg-gradient-to-r from-orange-500 to-red-600 mx-auto rounded-full mb-8"></div>
            
            <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-gray-200 mb-8">
                <p class="text-xl text-gray-600 mb-6 leading-relaxed">
                    Your payment was cancelled. Don't worry - your cart items are still saved!
                </p>
                
                <?php if ($cartCount > 0): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span class="text-blue-800 font-medium">
                                You have <?php echo $cartCount; ?> item(s) waiting in your cart
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <p class="text-gray-600 mb-8">
                    Forgot to add something to your cart? You can continue shopping or return to your cart to complete your purchase.
                </p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                <a href="<?php echo getBaseUrl('cart.php'); ?>" class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-10 py-4 rounded-xl font-bold text-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl inline-flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Return to Cart
                </a>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-white hover:bg-gray-50 text-gray-800 px-10 py-4 rounded-xl font-bold text-lg transition-all duration-200 border-2 border-gray-300 hover:border-gray-400 inline-flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Continue Shopping
                </a>
            </div>
            
            <!-- Help Section -->
            <div class="bg-gray-50 rounded-lg p-6 text-left">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 text-center">Need Help?</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900 mb-1">Contact Support</h3>
                        <p class="text-gray-600">Having trouble with checkout? We're here to help.</p>
                        <a href="<?php echo getBaseUrl('contact.php'); ?>" class="text-folly hover:text-folly-600 font-medium mt-2 inline-block">Get Support</a>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900 mb-1">Secure Checkout</h3>
                        <p class="text-gray-600">Your data is protected with SSL encryption and secure payment processing.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 