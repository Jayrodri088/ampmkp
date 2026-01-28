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
<div class="bg-white border-b border-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <a href="<?php echo getBaseUrl('cart.php'); ?>" class="text-gray-500 hover:text-folly transition-colors">Cart</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">Payment Cancelled</span>
        </nav>
    </div>
</div>

<!-- Cancel Hero -->
<section class="bg-charcoal-900 py-16 md:py-24 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[url('assets/images/pattern.png')]"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-red-500 rounded-full mix-blend-overlay filter blur-3xl opacity-10"></div>
    
    <div class="container mx-auto px-4 relative z-10 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-sm rounded-full mb-8 border border-white/20">
            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 font-display tracking-tight">
            Payment Cancelled
        </h1>
        <p class="text-xl text-gray-300 max-w-2xl mx-auto leading-relaxed font-light">
            No worries! Your payment has been cancelled and no charges were made.
        </p>
    </div>
</section>

<!-- Content -->
<section class="bg-gray-50 py-12 md:py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto">
            
            <div class="bg-white rounded-2xl shadow-soft border border-gray-100 p-8 md:p-12 text-center">
                <h2 class="text-2xl font-bold text-charcoal-900 mb-4 font-display">What would you like to do?</h2>
                <p class="text-gray-500 mb-10 max-w-lg mx-auto">
                    Your items are safe in your cart. You can try paying again with a different method or continue shopping.
                </p>
                
                <?php if ($cartCount > 0): ?>
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-10 inline-flex items-center gap-3 text-blue-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        <span class="font-medium">You have <?php echo $cartCount; ?> item(s) waiting in your cart</span>
                    </div>
                <?php endif; ?>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo getBaseUrl('checkout.php'); ?>" class="px-8 py-4 bg-folly text-white rounded-xl font-bold hover:bg-folly-600 transition-all shadow-lg hover:shadow-folly/30 flex items-center justify-center gap-2">
                        <span>Return to Checkout</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="px-8 py-4 bg-white text-charcoal-900 border border-gray-200 rounded-xl font-bold hover:bg-gray-50 transition-all flex items-center justify-center gap-2">
                        <span>Continue Shopping</span>
                    </a>
                </div>
            </div>
            
            <!-- Help Section -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-charcoal-900 mb-1">Having Trouble?</h3>
                        <p class="text-sm text-gray-500 mb-2">Contact our support team for assistance with your order.</p>
                        <a href="<?php echo getBaseUrl('contact.php'); ?>" class="text-sm font-bold text-folly hover:text-folly-700">Contact Support &rarr;</a>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-green-600 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-charcoal-900 mb-1">Secure Payment</h3>
                        <p class="text-sm text-gray-500">Your payment information is always secure and encrypted.</p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>