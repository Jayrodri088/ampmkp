<?php
$page_title = 'Home';
$page_description = 'Angel Marketplace - Premium Christian merchandise, apparel, gifts and spiritual resources. Shop now for faith-based products.';

require_once 'includes/functions.php';

// Debug paths if requested
debugPaths();

// Load settings early (used for homepage category limit, etc.)
$settings = getSettings();

// Selected currency for homepage pricing
$selectedCurrency = getSelectedCurrency();

// Get featured products sorted by rating count and categories
$featuredProducts = getFeaturedProductsByRating(8);
$categories = getCategories();

// Precompute total counts (including sub-categories) once for this request
$categoryTotalCounts = getTotalProductCountsForAllCategories();

// Filter to show only root categories (parent_id = 0) for homepage
$rootCategories = array_filter($categories, function($category) {
    return ($category['parent_id'] ?? 0) == 0;
});

// Sort root categories by product count including subcategories (highest to lowest)
usort($rootCategories, function($a, $b) use ($categoryTotalCounts) {
    $idA = (int)($a['id'] ?? 0);
    $idB = (int)($b['id'] ?? 0);
    $countA = (int)($categoryTotalCounts[$idA] ?? 0);
    $countB = (int)($categoryTotalCounts[$idB] ?? 0);
    return $countB - $countA; // Descending order (highest first)
});

// Limit categories for homepage display based on settings
$homepageCategoriesCount = $settings['homepage_categories_count'] ?? 6;
$categories = array_slice($rootCategories, 0, $homepageCategoriesCount);

// Get active advertisements
$activeAds = getActiveAds();

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="relative overflow-hidden hero-mesh-gradient">
    <!-- Animated gradient orbs -->
    <div class="absolute -top-32 -right-32 w-[500px] h-[500px] bg-folly/8 rounded-full blur-[100px] hero-orb-1"></div>
    <div class="absolute top-1/2 -left-32 w-[400px] h-[400px] bg-tangerine/8 rounded-full blur-[100px] hero-orb-2"></div>
    <div class="absolute bottom-0 right-1/4 w-[300px] h-[300px] bg-purple-400/5 rounded-full blur-[80px] hero-orb-3"></div>
    <!-- Grain texture overlay -->
    <div class="absolute inset-0 hero-grain opacity-[0.03] pointer-events-none"></div>

    <div class="container mx-auto px-4 relative pt-10 pb-16 lg:py-24">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Text Content -->
            <div class="text-center lg:text-left space-y-6 max-w-xl mx-auto lg:mx-0">
                <!-- Badge with shimmer -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/40 text-sm font-medium text-charcoal-600 animate-fade-in-up hero-badge-shimmer">
                    <span class="w-2 h-2 rounded-full bg-folly animate-pulse"></span>
                    New Collection Available
                </div>

                <!-- Headline with weight contrast -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl leading-[1.1] tracking-tight animate-fade-in-up animation-delay-200">
                    <span class="font-light text-charcoal-700">Discover Your</span> <br>
                    <span class="font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-folly via-folly-600 to-tangerine">Divine Style</span>
                </h1>

                <!-- Description -->
                <p class="text-base text-charcoal-500 leading-relaxed animate-fade-in-up animation-delay-400 max-w-md mx-auto lg:mx-0">
                    Explore our premium collection of faith-inspired merchandise. Find products that resonate with your purpose.
                </p>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start animate-fade-in-up animation-delay-600">
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center justify-center px-7 py-3.5 text-sm font-bold text-white transition-all duration-300 bg-gradient-to-r from-folly to-folly-500 rounded-xl hover:shadow-xl hover:shadow-folly/25 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-folly">
                        Start Shopping
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                    <a href="<?php echo getBaseUrl('categories.php'); ?>" class="inline-flex items-center justify-center px-7 py-3.5 text-sm font-bold text-charcoal-700 transition-all duration-300 glass border border-white/40 rounded-xl hover:bg-white/90 hover:text-folly hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                        View Categories
                    </a>
                </div>

                <!-- Trust Indicators - horizontal strip -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-0 animate-fade-in-up animation-delay-800 pt-6">
                    <div class="flex items-center gap-2 px-4 py-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span class="text-xs font-semibold text-charcoal-600">Secure Payment</span>
                    </div>
                    <div class="w-px h-4 bg-gray-300 hidden sm:block"></div>
                    <div class="flex items-center gap-2 px-4 py-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-xs font-semibold text-charcoal-600">Quality Guarantee</span>
                    </div>
                    <div class="w-px h-4 bg-gray-300 hidden sm:block"></div>
                    <div class="flex items-center gap-2 px-4 py-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span class="text-xs font-semibold text-charcoal-600">Fast Delivery</span>
                    </div>
                </div>
            </div>

            <!-- Bento Grid Product Showcase -->
            <div class="relative hidden lg:block">
                <?php
                $latestHeroProducts = getLatestProducts(3);
                ?>
                <div class="bento-grid" style="height: 480px;">
                    <?php if (isset($latestHeroProducts[0])): $product = $latestHeroProducts[0]; ?>
                    <!-- Featured large card -->
                    <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="bento-featured group relative rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 block">
                        <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>
                        <picture>
                            <source media="(max-width: 1023px)" srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                            <img src="<?php echo getAssetUrl('images/' . $product['image']); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy" decoding="async"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 opacity-0 relative z-10"
                                 onload="this.classList.remove('opacity-0')"
                                 onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')">
                        </picture>
                        <div class="absolute inset-0 bg-gradient-to-t from-charcoal-900/80 via-charcoal-900/20 to-transparent z-20"></div>
                        <div class="absolute top-4 left-4 z-30">
                            <span class="glass text-charcoal-800 text-[10px] font-bold px-3 py-1 rounded-full border border-white/30"><?php echo htmlspecialchars($product['category_name'] ?? 'Featured'); ?></span>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-5 z-30 transform translate-y-1 group-hover:translate-y-0 transition-transform duration-300">
                            <h3 class="text-white text-lg font-bold mb-1 line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <span class="text-white font-bold text-xl"><?php echo formatProductPrice($product, $selectedCurrency); ?></span>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (isset($latestHeroProducts[1])): $product = $latestHeroProducts[1]; ?>
                    <!-- Small card 1 -->
                    <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="group relative rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 block">
                        <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>
                        <picture>
                            <source media="(max-width: 1023px)" srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                            <img src="<?php echo getAssetUrl('images/' . $product['image']); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy" decoding="async"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 opacity-0 relative z-10"
                                 onload="this.classList.remove('opacity-0')"
                                 onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')">
                        </picture>
                        <div class="absolute inset-0 bg-gradient-to-t from-charcoal-900/80 via-charcoal-900/10 to-transparent z-20"></div>
                        <div class="absolute top-3 left-3 z-30">
                            <span class="glass text-charcoal-800 text-[10px] font-bold px-2.5 py-1 rounded-full border border-white/30"><?php echo htmlspecialchars($product['category_name'] ?? 'New'); ?></span>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-4 z-30">
                            <h3 class="text-white text-sm font-bold mb-0.5 line-clamp-1"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <span class="text-white font-bold text-base"><?php echo formatProductPrice($product, $selectedCurrency); ?></span>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (isset($latestHeroProducts[2])): $product = $latestHeroProducts[2]; ?>
                    <!-- Small card 2 -->
                    <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="group relative rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 block">
                        <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>
                        <picture>
                            <source media="(max-width: 1023px)" srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                            <img src="<?php echo getAssetUrl('images/' . $product['image']); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy" decoding="async"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 opacity-0 relative z-10"
                                 onload="this.classList.remove('opacity-0')"
                                 onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')">
                        </picture>
                        <div class="absolute inset-0 bg-gradient-to-t from-charcoal-900/80 via-charcoal-900/10 to-transparent z-20"></div>
                        <div class="absolute top-3 left-3 z-30">
                            <span class="glass text-charcoal-800 text-[10px] font-bold px-2.5 py-1 rounded-full border border-white/30"><?php echo htmlspecialchars($product['category_name'] ?? 'New'); ?></span>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 p-4 z-30">
                            <h3 class="text-white text-sm font-bold mb-0.5 line-clamp-1"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <span class="text-white font-bold text-base"><?php echo formatProductPrice($product, $selectedCurrency); ?></span>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Bar -->
<section class="py-12 md:py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-10">
            <div class="flex flex-col items-center text-center group">
                <div class="w-14 h-14 glass rounded-2xl flex items-center justify-center mb-4 group-hover:shadow-lg transition-all duration-300 border border-white/50">
                    <svg class="w-6 h-6 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
                <h3 class="font-semibold text-charcoal-900 text-sm mb-0.5">Premium Products</h3>
                <p class="text-xs text-charcoal-400">Curated high-quality items</p>
            </div>
            <div class="flex flex-col items-center text-center group">
                <div class="w-14 h-14 glass rounded-2xl flex items-center justify-center mb-4 group-hover:shadow-lg transition-all duration-300 border border-white/50">
                    <svg class="w-6 h-6 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-semibold text-charcoal-900 text-sm mb-0.5">Fast Shipping</h3>
                <p class="text-xs text-charcoal-400">Delivery within 24 hours</p>
            </div>
            <div class="flex flex-col items-center text-center group">
                <div class="w-14 h-14 glass rounded-2xl flex items-center justify-center mb-4 group-hover:shadow-lg transition-all duration-300 border border-white/50">
                    <svg class="w-6 h-6 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <h3 class="font-semibold text-charcoal-900 text-sm mb-0.5">Secure Payment</h3>
                <p class="text-xs text-charcoal-400">100% secure transactions</p>
            </div>
            <div class="flex flex-col items-center text-center group">
                <div class="w-14 h-14 glass rounded-2xl flex items-center justify-center mb-4 group-hover:shadow-lg transition-all duration-300 border border-white/50">
                    <svg class="w-6 h-6 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <h3 class="font-semibold text-charcoal-900 text-sm mb-0.5">24/7 Support</h3>
                <p class="text-xs text-charcoal-400">Dedicated support team</p>
            </div>
        </div>
    </div>
</section>

<!-- Trending Products -->
<section class="py-14 md:py-20 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-gray-50/80 to-white"></div>
    <div class="container mx-auto px-4 relative">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10 md:mb-14">
            <div>
                <span class="text-xs font-semibold text-folly tracking-[0.2em] uppercase mb-2 block">Curated For You</span>
                <h2 class="text-2xl md:text-4xl font-bold text-charcoal-900 tracking-tight">Trending Now</h2>
            </div>
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="hidden md:inline-flex items-center text-sm font-medium text-charcoal-500 hover:text-folly transition-colors mt-4 md:mt-0">
                View all products
                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
            <?php foreach ($featuredProducts as $index => $product):
                $isLcp = $index < 2;
            ?>
            <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="group flex flex-col h-full">
                <div class="relative aspect-[3/4] overflow-hidden rounded-2xl bg-gray-100 mb-3 md:mb-4">
                    <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>

                    <?php if (isset($product['is_new']) && $product['is_new']): ?>
                    <span class="absolute top-2.5 left-2.5 glass text-charcoal-800 text-[10px] font-bold px-2.5 py-1 rounded-full z-20 border border-white/40">NEW</span>
                    <?php endif; ?>
                    <?php if (isset($product['discount_percent']) && $product['discount_percent'] > 0): ?>
                    <span class="absolute top-2.5 right-2.5 bg-folly text-white text-[10px] font-bold px-2.5 py-1 rounded-full z-20">-<?php echo $product['discount_percent']; ?>%</span>
                    <?php endif; ?>

                    <img src="<?php echo getAssetUrl('images/' . $product['image']); ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         <?php if (!$isLcp): ?>
                         loading="lazy" decoding="async"
                         class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 opacity-0 z-10"
                         onload="this.classList.remove('opacity-0')"
                         onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')"
                         <?php else: ?>
                         fetchpriority="high"
                         class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 z-10"
                         onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                         <?php endif; ?>>

                    <!-- Quick add overlay -->
                    <div class="absolute inset-x-3 bottom-3 z-20 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 hidden md:block">
                        <button onclick="event.preventDefault();addToCart(<?php echo $product['id']; ?>)" class="w-full glass-strong py-2.5 rounded-xl text-sm font-semibold text-charcoal-900 hover:bg-folly hover:text-white transition-colors border border-white/50">
                            Add to Cart
                        </button>
                    </div>
                </div>

                <div class="flex flex-col flex-grow px-0.5">
                    <h3 class="text-sm md:text-[15px] font-medium text-charcoal-800 mb-1 line-clamp-1 group-hover:text-folly transition-colors">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h3>
                    <div class="flex items-center gap-1 mb-1.5">
                        <?php
                        $avg = (float)($product['rating_average'] ?? 0);
                        $cnt = (int)($product['rating_count'] ?? 0);
                        echo renderStars($avg, 5, 'w-3 h-3');
                        ?>
                        <span class="text-[10px] text-gray-400">(<?php echo $cnt; ?>)</span>
                    </div>
                    <div class="mt-auto flex items-baseline gap-2">
                        <span class="text-sm md:text-base font-bold text-charcoal-900"><?php echo formatProductPrice($product, $selectedCurrency); ?></span>
                        <?php if (isset($product['old_price']) && $product['old_price'] > $product['price']): ?>
                        <span class="text-[11px] text-gray-400 line-through"><?php echo formatPriceWithCurrency($product['old_price'], $selectedCurrency); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="mt-10 text-center md:hidden">
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center text-sm font-medium text-charcoal-500 hover:text-folly transition-colors">
                View all products
                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-14 md:py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-10 md:mb-14">
            <div>
                <span class="text-xs font-semibold text-folly tracking-[0.2em] uppercase mb-2 block">Collections</span>
                <h2 class="text-2xl md:text-4xl font-bold text-charcoal-900 tracking-tight">Shop by Category</h2>
            </div>
            <a href="<?php echo getBaseUrl('categories.php'); ?>" class="hidden md:inline-flex items-center text-sm font-medium text-charcoal-500 hover:text-folly transition-colors mt-4 md:mt-0">
                View all categories
                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 md:gap-5">
            <?php foreach ($categories as $index => $category):
                $isLcp = $index < 2;
            ?>
            <a href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" class="group relative block aspect-[4/3] md:aspect-[3/2] rounded-2xl overflow-hidden">
                <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>
                <img src="<?php echo getAssetUrl('images/' . $category['image']); ?>"
                     alt="<?php echo htmlspecialchars($category['name']); ?>"
                     <?php if (!$isLcp): ?>
                     loading="lazy" decoding="async"
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 opacity-0 z-10"
                     onload="this.classList.remove('opacity-0')"
                     onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')"
                     <?php else: ?>
                     fetchpriority="high"
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 z-10"
                     onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                     <?php endif; ?>>
                <div class="absolute inset-0 bg-gradient-to-t from-charcoal-900/70 via-charcoal-900/10 to-transparent z-20"></div>

                <div class="absolute bottom-0 left-0 right-0 p-4 md:p-6 z-30 flex items-end justify-between">
                    <div>
                        <h3 class="text-base md:text-xl font-bold text-white mb-0.5"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <span class="text-[11px] md:text-xs text-white/70 font-medium">
                            <?php
                            $count = (int)($categoryTotalCounts[(int)($category['id'] ?? 0)] ?? 0);
                            echo $count . ' Product' . ($count != 1 ? 's' : '');
                            ?>
                        </span>
                    </div>
                    <span class="w-8 h-8 glass rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-2 group-hover:translate-x-0 border border-white/30 hidden md:flex">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 text-center md:hidden">
            <a href="<?php echo getBaseUrl('categories.php'); ?>" class="inline-flex items-center text-sm font-medium text-charcoal-500 hover:text-folly transition-colors">
                View all categories
                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
    </div>
</section>

<!-- Advertisement Section -->
<?php if (!empty($activeAds)): ?>
<section class="py-6 md:py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto rounded-2xl overflow-hidden shadow-lg">
            <?php if (count($activeAds) === 1): ?>
                <?php $ad = $activeAds[0]; $destinationUrl = getAdDestinationUrl($ad); ?>
                <a href="<?php echo $destinationUrl; ?>" class="block relative group bg-gray-50 rounded-2xl overflow-hidden">
                    <img src="<?php echo getAssetUrl('images/ads/' . $ad['image']); ?>"
                         alt="<?php echo htmlspecialchars($ad['title']); ?>"
                         loading="lazy"
                         class="w-full h-auto max-h-64 md:max-h-80 object-cover transition-all duration-500 opacity-0 group-hover:scale-[1.02]"
                         onload="this.classList.remove('opacity-0')">
                </a>
            <?php else: ?>
                <div class="relative" x-data="{ activeSlide: 0, slides: <?php echo count($activeAds); ?> }" x-init="setInterval(() => activeSlide = (activeSlide + 1) % slides, 5000)">
                    <div class="overflow-hidden relative bg-gray-50 rounded-2xl">
                        <div class="flex transition-transform duration-700 ease-in-out" :style="{ transform: `translateX(-${activeSlide * 100}%)` }">
                            <?php foreach ($activeAds as $ad): ?>
                            <div class="w-full flex-shrink-0">
                                <a href="<?php echo getAdDestinationUrl($ad); ?>" class="block">
                                    <img src="<?php echo getAssetUrl('images/ads/' . $ad['image']); ?>"
                                         alt="<?php echo htmlspecialchars($ad['title']); ?>"
                                         loading="lazy"
                                         class="w-full h-auto max-h-64 md:max-h-80 object-cover transition-opacity duration-500 opacity-0"
                                         onload="this.classList.remove('opacity-0')">
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button @click="activeSlide = activeSlide === 0 ? slides - 1 : activeSlide - 1" class="absolute left-3 top-1/2 -translate-y-1/2 glass p-2 rounded-full text-charcoal-700 hover:bg-white/90 transition-all border border-white/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <button @click="activeSlide = activeSlide === slides - 1 ? 0 : activeSlide + 1" class="absolute right-3 top-1/2 -translate-y-1/2 glass p-2 rounded-full text-charcoal-700 hover:bg-white/90 transition-all border border-white/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                    <!-- Dots -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                        <?php foreach ($activeAds as $i => $ad): ?>
                        <button @click="activeSlide = <?php echo $i; ?>" class="w-2 h-2 rounded-full transition-all duration-300" :class="activeSlide === <?php echo $i; ?> ? 'bg-white w-6' : 'bg-white/50'"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Newsletter CTA -->
<section class="py-14 md:py-20 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-charcoal-900 via-charcoal-800 to-charcoal-900"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-folly/20 rounded-full blur-[100px]"></div>
    <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-tangerine/15 rounded-full blur-[80px]"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-2xl mx-auto text-center">
            <span class="text-xs font-semibold text-folly-100 tracking-[0.2em] uppercase mb-3 block">Stay Connected</span>
            <h2 class="text-2xl md:text-4xl font-bold text-white mb-4 tracking-tight">Join Our Community</h2>
            <p class="text-white/60 text-sm md:text-base max-w-lg mx-auto mb-8">Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.</p>
            <a href="#newsletter-form" onclick="document.getElementById('newsletter-email').focus()" class="inline-flex items-center justify-center px-7 py-3.5 text-sm font-bold glass text-white rounded-xl hover:bg-white/20 transition-all duration-300 border border-white/20 hover:border-white/40">
                Subscribe Now
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>