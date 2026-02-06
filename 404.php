<?php
$page_title = 'Page Not Found';
$page_description = 'The page you are looking for could not be found.';

require_once 'includes/functions.php';

// Get some featured products for suggestions
$featuredProducts = getFeaturedProductsByRating(4);

include 'includes/header.php';
?>

<!-- 404 Page -->
<section class="min-h-[70vh] flex items-center bg-gradient-to-b from-gray-50 via-white to-gray-50 relative overflow-hidden mt-24">
    <!-- Background decorative elements -->
    <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[20rem] md:text-[28rem] font-black text-gray-100/60 leading-none tracking-tighter font-display">404</div>
        <div class="absolute top-20 right-20 w-72 h-72 bg-folly/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 left-20 w-72 h-72 bg-tangerine/5 rounded-full blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10 py-16 md:py-24">
        <div class="max-w-xl mx-auto text-center">
            <!-- Small label -->
            <span class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-4 block">Page Not Found</span>

            <!-- Main heading -->
            <h1 class="text-4xl md:text-5xl font-bold text-charcoal-900 mb-4 font-display tracking-tight">
                We can't find that page
            </h1>

            <p class="text-lg text-gray-500 mb-10 leading-relaxed max-w-md mx-auto">
                The page you're looking for doesn't exist or has been moved. Try searching or head back home.
            </p>

            <!-- Search -->
            <form action="<?php echo getBaseUrl('search.php'); ?>" method="GET" class="mb-8">
                <div class="glass-strong rounded-2xl p-2 flex items-center shadow-soft max-w-md mx-auto">
                    <div class="pl-4 pr-2 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="q"
                        placeholder="Search products..."
                        class="flex-1 py-3 px-2 bg-transparent border-0 focus:ring-0 focus:outline-none text-charcoal-900 placeholder-gray-400"
                    >
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-folly to-folly-500 text-white rounded-xl font-bold text-sm transition-all hover:shadow-lg hover:shadow-folly/20">
                        Search
                    </button>
                </div>
            </form>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?php echo getBaseUrl(); ?>" class="px-8 py-3.5 bg-gradient-to-r from-charcoal-900 to-charcoal-800 hover:from-folly hover:to-folly-500 text-white rounded-xl font-bold transition-all duration-300 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go Home
                </a>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="px-8 py-3.5 glass text-charcoal-900 rounded-xl font-bold transition-all hover:shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Browse Shop
                </a>
                <a href="<?php echo getBaseUrl('contact.php'); ?>" class="px-8 py-3.5 glass text-charcoal-900 rounded-xl font-bold transition-all hover:shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Get Help
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<?php if (!empty($featuredProducts)): ?>
<section class="bg-gradient-to-b from-gray-50 to-white py-16 md:py-20">
    <div class="container mx-auto px-4">
        <div class="text-center mb-10">
            <span class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-3 block">While You're Here</span>
            <h2 class="text-3xl md:text-4xl font-bold text-charcoal-900 font-display tracking-tight">Popular Products</h2>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 max-w-5xl mx-auto">
            <?php
            $selectedCurrency = getSelectedCurrency();
            foreach ($featuredProducts as $product): ?>
                <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="glass rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <div class="aspect-square bg-gray-100 overflow-hidden">
                        <img
                            src="<?php echo getAssetUrl('images/' . $product['image']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                        >
                    </div>
                    <div class="p-3 md:p-4">
                        <h3 class="font-semibold text-charcoal-900 text-sm mb-1 line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <span class="text-base font-bold text-charcoal-900"><?php echo formatProductPrice($product, $selectedCurrency); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
