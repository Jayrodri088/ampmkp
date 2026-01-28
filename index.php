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
<section class="relative bg-gray-50 overflow-hidden">
    <div class="absolute inset-0 bg-white/50"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-folly/10 rounded-full blur-3xl"></div>
    <div class="absolute top-1/2 -left-24 w-72 h-72 bg-tangerine/10 rounded-full blur-3xl"></div>
    
    <div class="container mx-auto px-4 relative pt-12 pb-20 lg:py-32">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Text Content -->
            <div class="text-center lg:text-left space-y-8 max-w-2xl mx-auto lg:mx-0">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 shadow-sm text-sm font-medium text-charcoal-600 animate-fade-in-up">
                    <span class="w-2 h-2 rounded-full bg-folly animate-pulse"></span>
                    New Collection Available
                </div>
                
                <h1 class="text-5xl lg:text-7xl font-bold text-charcoal-900 leading-tight tracking-tight animate-fade-in-up animation-delay-200">
                    Discover Your <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly to-tangerine">Divine Style</span>
                </h1>
                
                <p class="text-lg text-charcoal-600 leading-relaxed animate-fade-in-up animation-delay-400">
                    Explore our premium collection of faith-inspired merchandise. From apparel to spiritual resources, find products that resonate with your purpose.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start animate-fade-in-up animation-delay-600">
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white transition-all duration-200 bg-folly border border-transparent rounded-xl hover:bg-folly-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-folly shadow-lg hover:shadow-xl hover:-translate-y-1">
                        Start Shopping
                    </a>
                    <a href="<?php echo getBaseUrl('categories.php'); ?>" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-charcoal-700 transition-all duration-200 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-folly focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 shadow-sm hover:shadow-md">
                        View Categories
                    </a>
                </div>
                
                <!-- Trust Indicators -->
                <div class="pt-8 border-t border-gray-200 flex flex-wrap justify-center lg:justify-start gap-8 animate-fade-in-up animation-delay-800">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 rounded-lg text-green-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-sm font-semibold text-charcoal-700">Secure Payment</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-sm font-semibold text-charcoal-700">Quality Guarantee</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 rounded-lg text-purple-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-sm font-semibold text-charcoal-700">Fast Delivery</span>
                    </div>
                </div>
            </div>
            
            <!-- Image/Visual Content -->
            <div class="relative hidden lg:block">
                <div class="absolute inset-0 bg-gradient-to-tr from-folly/20 to-tangerine/20 rounded-[3rem] transform rotate-3"></div>
                <div class="relative bg-white rounded-[2.5rem] shadow-2xl overflow-hidden transform -rotate-2 hover:rotate-0 transition-transform duration-500">
                    <!-- Desktop Hero Grid (Latest Products) -->
                <div class="hidden md:grid grid-cols-3 gap-6 h-full">
                    <?php 
                    // Fetch truly latest 3 products from database
                    $latestHeroProducts = getLatestProducts(3); 
                    foreach ($latestHeroProducts as $product): 
                    ?>
                    <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="group relative h-full rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 block">
                        <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>
                        
                        <picture>
                            <!-- Don't download these images on mobile -->
                            <source media="(max-width: 1023px)" srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
                            <img src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy"
                                 decoding="async"
                                 class="w-full h-full object-cover transition-all duration-700 group-hover:scale-110 opacity-0 relative z-10"
                                 onload="this.classList.remove('opacity-0')"
                                 onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')">
                        </picture>
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-80 group-hover:opacity-90 transition-opacity z-20"></div>
                        
                        <div class="absolute top-4 left-4 z-30">
                            <span class="bg-folly text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">NEW ARRIVAL</span>
                        </div>
                        
                        <div class="absolute bottom-0 left-0 right-0 p-6 z-30 transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                            <p class="text-white/80 text-xs font-bold tracking-wider uppercase mb-1"><?php echo htmlspecialchars($product['category_name'] ?? 'New'); ?></p>
                            <h3 class="text-white text-xl font-bold mb-2 line-clamp-1"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="flex items-center justify-between">
                                <span class="text-white font-bold text-lg"><?php echo formatProductPrice($product, $selectedCurrency); ?></span>
                                <span class="w-8 h-8 bg-white text-charcoal-900 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-4 group-hover:translate-x-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Bar -->
<section class="bg-white border-y border-gray-100 py-10">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="flex flex-col items-center text-center group">
                <div class="w-12 h-12 bg-folly-50 text-folly rounded-full flex items-center justify-center mb-4 group-hover:bg-folly group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
                <h3 class="font-bold text-charcoal-900 mb-1">Premium Products</h3>
                <p class="text-sm text-charcoal-500">Curated high-quality items</p>
            </div>
            <div class="flex flex-col items-center text-center group">
                <div class="w-12 h-12 bg-folly-50 text-folly rounded-full flex items-center justify-center mb-4 group-hover:bg-folly group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-bold text-charcoal-900 mb-1">Fast Shipping</h3>
                <p class="text-sm text-charcoal-500">Delivery within 24 hours</p>
            </div>
            <div class="flex flex-col items-center text-center group">
                <div class="w-12 h-12 bg-folly-50 text-folly rounded-full flex items-center justify-center mb-4 group-hover:bg-folly group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <h3 class="font-bold text-charcoal-900 mb-1">Secure Payment</h3>
                <p class="text-sm text-charcoal-500">100% secure transactions</p>
            </div>
            <div class="flex flex-col items-center text-center group">
                <div class="w-12 h-12 bg-folly-50 text-folly rounded-full flex items-center justify-center mb-4 group-hover:bg-folly group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <h3 class="font-bold text-charcoal-900 mb-1">24/7 Support</h3>
                <p class="text-sm text-charcoal-500">Dedicated support team</p>
            </div>
        </div>
    </div>
</section>

<!-- Trending Products -->
<section class="py-16 md:py-24 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-charcoal-900 mb-4">Trending Now</h2>
            <div class="w-20 h-1 bg-folly mx-auto rounded-full mb-6"></div>
            <p class="text-charcoal-600">Our most popular products, loved by customers for their quality and style.</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-8">
            <?php foreach ($featuredProducts as $index => $product): 
                $isLcp = $index < 2; // Eager load first 2 products
            ?>
            <div class="group bg-white rounded-2xl border border-gray-100 hover:border-gray-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col h-full">
                <div class="relative aspect-[4/5] overflow-hidden rounded-t-2xl bg-gray-100">
                    <!-- Loading Skeleton -->
                    <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>

                    <!-- Badges -->
                    <?php if (isset($product['is_new']) && $product['is_new']): ?>
                    <span class="absolute top-2 left-2 md:top-3 md:left-3 bg-green-500 text-white text-[10px] md:text-xs font-bold px-2 py-0.5 md:px-2.5 md:py-1 rounded-full z-20">NEW</span>
                    <?php endif; ?>
                    <?php if (isset($product['discount_percent']) && $product['discount_percent'] > 0): ?>
                    <span class="absolute top-2 right-2 md:top-3 md:right-3 bg-folly text-white text-[10px] md:text-xs font-bold px-2 py-0.5 md:px-2.5 md:py-1 rounded-full z-20">-<?php echo $product['discount_percent']; ?>%</span>
                    <?php endif; ?>
                    
                    <img src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         <?php if (!$isLcp): ?>
                         loading="lazy"
                         decoding="async"
                         class="absolute inset-0 w-full h-full object-cover transition-all duration-300 group-hover:scale-110 opacity-0 z-10"
                         onload="this.classList.remove('opacity-0')"
                         onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')"
                         <?php else: ?>
                         fetchpriority="high"
                         class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-110 z-10"
                         onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                         <?php endif; ?>>
                    
                    <!-- Quick Actions (Hidden on mobile) -->
                    <div class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 flex gap-2 hidden md:flex z-20">
                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="flex-1 bg-white text-charcoal-900 font-semibold py-3 rounded-xl hover:bg-folly hover:text-white transition-colors shadow-lg text-sm flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m-.4-5L2 1m5 12v2a2 2 0 002 2h10a2 2 0 002-2v-2m-6 4h.01M9 19h.01"></path></svg>
                            Add to Cart
                        </button>
                        <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="w-12 bg-white text-charcoal-900 rounded-xl hover:bg-charcoal-900 hover:text-white transition-colors shadow-lg flex items-center justify-center" title="View Details">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </a>
                    </div>
                </div>
                
                <div class="p-3 md:p-5 flex flex-col flex-grow">
                    <div class="mb-1 md:mb-2 text-[10px] md:text-xs text-charcoal-500 uppercase tracking-wider font-medium truncate">
                        <?php echo htmlspecialchars($product['category_name'] ?? 'Product'); ?>
                    </div>
                    <h3 class="text-sm md:text-lg font-bold text-charcoal-900 mb-1 md:mb-2 line-clamp-2 group-hover:text-folly transition-colors h-10 md:h-auto">
                        <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    
                    <!-- Rating -->
                    <div class="flex items-center mb-2 md:mb-3">
                        <?php 
                        $avg = (float)($product['rating_average'] ?? 0);
                        $cnt = (int)($product['rating_count'] ?? 0);
                        echo renderStars($avg, 5, 'w-3 h-3 md:w-4 md:h-4');
                        ?>
                        <span class="text-[10px] md:text-xs text-gray-400 ml-1 md:ml-2">(<?php echo $cnt; ?>)</span>
                    </div>
                    
                    <div class="mt-auto flex flex-col md:flex-row md:items-center justify-between gap-2">
                        <div class="flex flex-col">
                            <span class="text-base md:text-xl font-bold text-charcoal-900"><?php echo formatProductPrice($product, $selectedCurrency); ?></span>
                            <?php if (isset($product['old_price']) && $product['old_price'] > $product['price']): ?>
                            <span class="text-[10px] md:text-sm text-gray-400 line-through"><?php echo formatPriceWithCurrency($product['old_price'], $selectedCurrency); ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- Mobile Add Button -->
                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="md:hidden w-full bg-charcoal-900 text-white text-xs font-bold py-2 rounded-lg hover:bg-folly transition-colors">
                            Add
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-16 text-center">
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white transition-all duration-200 bg-charcoal-900 border border-transparent rounded-xl hover:bg-folly focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-charcoal-900 shadow-lg hover:shadow-xl">
                Explore All Products
            </a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-end mb-12">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-charcoal-900 mb-4">Shop by Category</h2>
                <p class="text-charcoal-600 max-w-xl">Browse our wide range of categories to find exactly what you're looking for.</p>
            </div>
            <a href="<?php echo getBaseUrl('categories.php'); ?>" class="hidden md:flex items-center text-folly font-semibold hover:text-folly-600 transition-colors">
                View All Categories <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-8">
            <?php foreach ($categories as $index => $category): 
                $isLcp = $index < 2; // Eager load first 2 categories (top row on mobile)
            ?>
            <a href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" class="group relative block h-40 md:h-80 rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300">
                <!-- Loading Skeleton -->
                <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>
                
                <img src="<?php echo getAssetUrl('images/' . $category['image']); ?>" 
                     alt="<?php echo htmlspecialchars($category['name']); ?>" 
                     <?php if (!$isLcp): ?>
                     loading="lazy"
                     decoding="async"
                     class="absolute inset-0 w-full h-full object-cover transition-all duration-300 group-hover:scale-110 opacity-0 relative z-10"
                     onload="this.classList.remove('opacity-0')"
                     onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')"
                     <?php else: ?>
                     fetchpriority="high"
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-110 relative z-10"
                     onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                     <?php endif; ?>>
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-80 group-hover:opacity-90 transition-opacity z-20"></div>
                
                <div class="absolute bottom-0 left-0 p-4 md:p-8 w-full z-20">
                    <h3 class="text-lg md:text-2xl font-bold text-white mb-1 md:mb-2 transform translate-y-0 transition-transform duration-300"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <div class="flex items-center justify-between text-white/90 md:transform md:translate-y-4 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100 transition-all duration-300">
                        <span class="text-[10px] md:text-sm font-medium">
                            <?php 
                            $count = (int)($categoryTotalCounts[(int)($category['id'] ?? 0)] ?? 0);
                            echo $count . ' Product' . ($count != 1 ? 's' : ''); 
                            ?>
                        </span>
                        <span class="w-6 h-6 md:w-8 md:h-8 bg-white text-folly rounded-full flex items-center justify-center hidden md:flex">
                            <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-8 text-center md:hidden">
            <a href="<?php echo getBaseUrl('categories.php'); ?>" class="inline-flex items-center text-folly font-semibold hover:text-folly-600 transition-colors">
                View All Categories <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
    </div>
</section>

<!-- Advertisement Section -->
<?php if (!empty($activeAds)): ?>
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="rounded-3xl overflow-hidden shadow-lg bg-white">
            <?php if (count($activeAds) === 1): ?>
                <?php $ad = $activeAds[0]; $destinationUrl = getAdDestinationUrl($ad); ?>
                <a href="<?php echo $destinationUrl; ?>" class="block relative group bg-gray-100">
                    <img src="<?php echo getAssetUrl('images/ads/' . $ad['image']); ?>" 
                         alt="<?php echo htmlspecialchars($ad['title']); ?>" 
                         loading="lazy"
                         class="w-full h-auto object-cover transition-opacity duration-500 opacity-0"
                         onload="this.classList.remove('opacity-0')">
                </a>
            <?php else: ?>
                <!-- Simple Carousel for Ads -->
                <div class="relative" x-data="{ activeSlide: 0, slides: <?php echo count($activeAds); ?> }">
                    <div class="overflow-hidden relative bg-gray-100">
                        <div class="flex transition-transform duration-500 ease-in-out" :style="{ transform: `translateX(-${activeSlide * 100}%)` }">
                            <?php foreach ($activeAds as $ad): ?>
                            <div class="w-full flex-shrink-0">
                                <a href="<?php echo getAdDestinationUrl($ad); ?>" class="block">
                                    <img src="<?php echo getAssetUrl('images/ads/' . $ad['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($ad['title']); ?>" 
                                         loading="lazy"
                                         class="w-full h-auto object-cover transition-opacity duration-500 opacity-0"
                                         onload="this.classList.remove('opacity-0')">
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Controls -->
                    <button @click="activeSlide = activeSlide === 0 ? slides - 1 : activeSlide - 1" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white p-2 rounded-full shadow-md text-charcoal-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <button @click="activeSlide = activeSlide === slides - 1 ? 0 : activeSlide + 1" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white p-2 rounded-full shadow-md text-charcoal-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Newsletter CTA (Simple) -->
<section class="py-16 bg-folly relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('<?php echo getAssetUrl('images/general/pattern.png'); ?>')] opacity-10"></div>
    <div class="container mx-auto px-4 relative z-10 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Join Our Community</h2>
        <p class="text-white/90 text-lg max-w-2xl mx-auto mb-8">Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.</p>
        <div class="flex justify-center">
            <a href="#newsletter-form" onclick="document.getElementById('newsletter-email').focus()" class="bg-white text-folly font-bold py-4 px-8 rounded-xl shadow-lg hover:bg-gray-50 hover:scale-105 transition-all duration-300">
                Subscribe Now
            </a>
        </div>
    </div>
</section>

<style>
@keyframes fade-in-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up { animation: fade-in-up 0.8s ease-out forwards; }
.animation-delay-200 { animation-delay: 0.2s; }
.animation-delay-400 { animation-delay: 0.4s; }
.animation-delay-600 { animation-delay: 0.6s; }
.animation-delay-800 { animation-delay: 0.8s; }
</style>

<?php include 'includes/footer.php'; ?>