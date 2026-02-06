<?php
$page_title = 'Search Results';
$page_description = 'Find the perfect Christian merchandise and gifts at Angel Marketplace.';

require_once 'includes/functions.php';

// Load settings
$settings = getSettings();

// Get search parameters
$query = trim($_GET['q'] ?? '');
$categorySlug = $_GET['category'] ?? '';
$sortBy = $_GET['sort'] ?? 'name';
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = $settings['items_per_page'] ?? 12;

// Get categories for filter
$categories = getCategories();
$selectedCategory = null;

if ($categorySlug) {
    $selectedCategory = getCategoryBySlug($categorySlug);
}

// Perform search
$products = [];
$searchPerformed = false;

if (!empty($query)) {
    $searchPerformed = true;
    $products = searchProducts($query, $selectedCategory ? $selectedCategory['id'] : null);
    $page_title = 'Search Results for "' . htmlspecialchars($query) . '"';
}

// Sort products
if (!empty($products)) {
    switch ($sortBy) {
        case 'price_low':
            usort($products, function($a, $b) { return $a['price'] <=> $b['price']; });
            break;
        case 'price_high':
            usort($products, function($a, $b) { return $b['price'] <=> $a['price']; });
            break;
        case 'featured':
            usort($products, function($a, $b) { 
                if ($a['featured'] == $b['featured']) {
                    return strcasecmp($a['name'], $b['name']);
                }
                return $b['featured'] <=> $a['featured'];
            });
            break;
        case 'name':
        default:
            usort($products, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
            break;
    }
}

// Pagination
$totalProducts = count($products);
$pagination = paginate($totalProducts, $itemsPerPage, $page);
$paginatedProducts = array_slice($products, $pagination['offset'], $itemsPerPage);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">
                Search<?php echo !empty($query) ? ': "' . htmlspecialchars($query) . '"' : ''; ?>
            </span>
        </nav>
    </div>
</div>

<!-- Search Hero Section -->
<section class="relative bg-charcoal-900 py-20 md:py-28" style="z-index: 10;">
    <!-- Background Image with Overlay + Decorative Elements (clipped) -->
    <div class="absolute inset-0 z-0 overflow-hidden">
        <img src="<?php echo getAssetUrl('images/general/hero-bg.jpg'); ?>" alt="Search" class="w-full h-full object-cover opacity-30" onerror="this.style.display='none'">
        <div class="absolute inset-0 bg-gradient-to-r from-charcoal-900 via-charcoal-900/90 to-charcoal-900/70"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-folly rounded-full mix-blend-overlay filter blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 bg-tangerine rounded-full mix-blend-overlay filter blur-3xl opacity-20"></div>
    </div>
    
    <div class="relative z-10 container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center mb-8 sm:mb-12">
            <h1 class="text-2xl sm:text-4xl md:text-6xl font-bold text-white mb-4 sm:mb-6 font-display tracking-tight">
                <?php if (!empty($query)): ?>
                    Search Results for <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-folly-400 to-tangerine">"<?php echo htmlspecialchars($query); ?>"</span>
                <?php else: ?>
                    Find Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-folly-400 to-tangerine">Perfect Item</span>
                <?php endif; ?>
            </h1>
            
            <?php if (!empty($query)): ?>
                <p class="text-sm sm:text-lg text-gray-300 mb-6 sm:mb-8 leading-relaxed font-light">
                    We found <?php echo $totalProducts; ?> results matching your search
                    <?php if ($selectedCategory): ?>
                        in <span class="font-semibold text-white"><?php echo htmlspecialchars($selectedCategory['name']); ?></span>
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="text-sm sm:text-lg text-gray-300 mb-6 sm:mb-8 leading-relaxed font-light max-w-2xl mx-auto">
                    Search our complete collection of inspiring products. Find exactly what you're looking for.
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Search Form -->
        <div class="max-w-5xl mx-auto bg-white/10 backdrop-blur-xl p-4 sm:p-6 md:p-8 rounded-2xl sm:rounded-3xl shadow-2xl border border-white/20">
            <form method="GET" action="<?php echo getBaseUrl('search.php'); ?>" class="space-y-6">
                <!-- Main Search -->
                <div class="relative group" style="z-index: 20;">
                    <input 
                        type="text" 
                        name="q" 
                        value="<?php echo htmlspecialchars($query); ?>"
                        placeholder="Search for products, brands, or keywords..." 
                        class="w-full px-6 py-5 pl-14 bg-charcoal-800/80 border border-gray-700 rounded-2xl text-white placeholder-gray-400 focus:ring-2 focus:ring-folly focus:border-transparent transition-all duration-300 shadow-inner text-lg"
                        autofocus
                    >
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <svg class="h-6 w-6 text-gray-400 group-focus-within:text-folly transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <!-- Category Filter -->
                    <div class="md:col-span-5">
                        <div class="relative">
                            <select name="category" class="w-full px-4 py-3 bg-charcoal-800/80 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-folly focus:border-transparent transition-all appearance-none cursor-pointer">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['slug']; ?>" <?php echo $categorySlug === $category['slug'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sort -->
                    <div class="md:col-span-4">
                        <div class="relative">
                            <select name="sort" class="w-full px-4 py-3 bg-charcoal-800/80 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-folly focus:border-transparent transition-all appearance-none cursor-pointer">
                                <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="featured" <?php echo $sortBy === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                                <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="md:col-span-3">
                        <button
                            type="submit"
                            class="w-full h-full bg-gradient-to-r from-folly to-folly-500 hover:from-folly-600 hover:to-folly text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-folly/30 flex items-center justify-center gap-2"
                        >
                            Search
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($query) || !empty($categorySlug) || $sortBy !== 'name'): ?>
                    <div class="text-center">
                        <a href="<?php echo getBaseUrl('search.php'); ?>" class="inline-flex items-center text-sm text-gray-400 hover:text-white transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            Clear All Filters
                        </a>
                    </div>
                <?php endif; ?>
                
                <input type="hidden" name="page" value="1">
            </form>
        </div>
    </div>
</section>

<!-- Search Results -->
<section class="bg-gradient-to-b from-gray-50 to-white py-16 md:py-24">
    <div class="container mx-auto px-4">
        <?php if (!$searchPerformed): ?>
            <!-- Search Suggestions -->
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <span class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-3 block">Browse</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-charcoal-900 mb-4 font-display tracking-tight">Popular Categories</h2>
                    <p class="text-gray-500">Browse our most popular product categories</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                    <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                        <a href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" class="group relative glass rounded-2xl p-6 hover:shadow-xl transition-all duration-300 flex items-center gap-4">
                            <div class="w-20 h-20 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
                                <img 
                                    src="<?php echo getAssetUrl('images/' . $category['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($category['name']); ?>"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                    onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                >
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-charcoal-900 group-hover:text-folly transition-colors mb-1 font-display">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h3>
                                <p class="text-sm text-gray-500 line-clamp-2">
                                    <?php echo htmlspecialchars($category['description']); ?>
                                </p>
                            </div>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity transform translate-x-2 group-hover:translate-x-0">
                                <svg class="w-5 h-5 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Popular Searches -->
                <div class="text-center glass-strong p-10 rounded-3xl max-w-4xl mx-auto">
                    <h3 class="text-xl font-bold text-charcoal-900 mb-6 font-display tracking-tight">Trending Searches</h3>
                    <div class="flex flex-wrap justify-center gap-3">
                        <?php
                        // Build trending searches from actual product/category data
                        $allProducts = readJsonFile('products.json');
                        $allProducts = array_filter($allProducts, function($p) { return !empty($p['active']); });

                        // Featured product names (shortened to first 2-3 words)
                        $featuredProducts = array_filter($allProducts, function($p) { return !empty($p['featured']); });
                        usort($featuredProducts, function($a, $b) { return ($b['id'] ?? 0) <=> ($a['id'] ?? 0); }); // newest first
                        $trendingTerms = [];
                        foreach (array_slice($featuredProducts, 0, 5) as $p) {
                            $words = explode(' ', $p['name']);
                            $trendingTerms[] = implode(' ', array_slice($words, 0, min(3, count($words))));
                        }

                        // Add top active category names
                        $activeCategories = array_filter($categories, function($c) { return !empty($c['active']) && !empty($c['featured']); });
                        foreach (array_slice($activeCategories, 0, 4) as $cat) {
                            $trendingTerms[] = $cat['name'];
                        }

                        // Deduplicate and limit
                        $trendingTerms = array_unique($trendingTerms);
                        $trendingTerms = array_slice($trendingTerms, 0, 8);

                        foreach ($trendingTerms as $search):
                        ?>
                            <a 
                                href="<?php echo getBaseUrl('search.php?q=' . urlencode($search)); ?>" 
                                class="px-5 py-2 rounded-full glass text-gray-600 hover:bg-folly hover:text-white transition-all duration-300 text-sm font-medium hover:border-folly"
                            >
                                <?php echo htmlspecialchars($search); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php elseif (empty($products)): ?>
            <!-- No Results -->
            <div class="max-w-2xl mx-auto text-center py-12">
                <div class="w-24 h-24 glass rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-charcoal-900 mb-4 font-display">No products found</h2>
                <p class="text-gray-500 mb-10 text-lg">
                    We couldn't find any products matching "<span class="text-charcoal-900 font-medium"><?php echo htmlspecialchars($query); ?></span>".<br>
                    Try adjusting your search terms or browse our categories.
                </p>
                
                <div class="flex justify-center gap-4">
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="px-8 py-3 bg-gradient-to-r from-folly to-folly-500 text-white rounded-xl font-bold hover:from-folly-600 hover:to-folly transition-all shadow-lg hover:shadow-folly/30">
                        Browse All Products
                    </a>
                    <a href="<?php echo getBaseUrl('categories.php'); ?>" class="px-8 py-3 glass text-charcoal-900 rounded-xl font-bold hover:shadow-lg transition-all">
                        View Categories
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Results Info -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-8 pb-6 border-b border-gray-200">
                <p class="text-gray-600 mb-4 md:mb-0">
                    <?php if ($totalProducts > 0): ?>
                        Showing <span class="font-bold text-charcoal-900"><?php echo ($pagination['offset'] + 1); ?>-<?php echo min($pagination['offset'] + $itemsPerPage, $totalProducts); ?></span> 
                        of <span class="font-bold text-charcoal-900"><?php echo $totalProducts; ?></span> results
                    <?php endif; ?>
                </p>
                <div class="flex items-center gap-2">
                     <span class="text-sm text-gray-500">Sort by:</span>
                     <select onchange="window.location.href=this.value" class="text-sm border-gray-300 rounded-lg focus:ring-folly focus:border-folly py-2 pl-3 pr-8">
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name'])); ?>" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'featured'])); ?>" <?php echo $sortBy === 'featured' ? 'selected' : ''; ?>>Featured</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                     </select>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6 md:gap-8 mb-16">
                <?php foreach ($paginatedProducts as $index => $product): 
                    $isLcp = $index < 4; // Eager load first 4 products
                ?>
                    <div class="group relative glass rounded-2xl hover:shadow-xl transition-all duration-500 overflow-hidden flex flex-col h-full">
                        
                        <!-- Image Container -->
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            <!-- Loading Skeleton -->
                            <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>

                            <!-- Badges -->
                            <?php if ($product['featured']): ?>
                                <div class="absolute top-3 left-3 z-20">
                                    <span class="px-3 py-1 bg-folly text-white text-[10px] font-bold uppercase tracking-wider rounded-full shadow-lg">
                                        Featured
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($product['discount_percent']) && $product['discount_percent'] > 0): ?>
                                <div class="absolute top-3 right-3 z-20">
                                    <span class="px-3 py-1 bg-tangerine text-white text-[10px] font-bold uppercase tracking-wider rounded-full shadow-lg">
                                        -<?php echo $product['discount_percent']; ?>%
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <img 
                                src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                <?php if (!$isLcp): ?>
                                loading="lazy"
                                class="w-full h-full object-cover transition-all duration-300 group-hover:scale-110 opacity-0 relative z-10"
                                onload="this.classList.remove('opacity-0')"
                                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')"
                                <?php else: ?>
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110 relative z-10"
                                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                <?php endif; ?>
                            >
                            
                            <!-- Overlay Actions -->
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                                <button onclick="addToCart(<?php echo $product['id']; ?>)" class="p-3 bg-white text-charcoal-900 rounded-full hover:bg-folly hover:text-white transition-colors shadow-lg transform translate-y-4 group-hover:translate-y-0 duration-300" title="Add to Cart" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l-2.5 5m12.5 0H9"></path></svg>
                                </button>
                                <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="p-3 bg-white text-charcoal-900 rounded-full hover:bg-folly hover:text-white transition-colors shadow-lg transform translate-y-4 group-hover:translate-y-0 duration-300 delay-75" title="View Details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="p-3 sm:p-5 flex flex-col flex-grow">
                            <div class="mb-2">
                                <p class="text-xs text-gray-500 uppercase tracking-wider font-medium mb-1">
                                    <?php 
                                    // Find category name if possible, otherwise generic
                                    echo isset($selectedCategory) ? htmlspecialchars($selectedCategory['name']) : 'Product'; 
                                    ?>
                                </p>
                                <h3 class="font-bold text-charcoal-900 text-sm sm:text-lg leading-tight group-hover:text-folly transition-colors line-clamp-2">
                                    <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                            </div>
                            
                            <!-- Rating -->
                            <div class="flex items-center mb-2 sm:mb-4">
                                <?php 
                                $ratingStats = getProductRatingStats($product['id']);
                                echo renderStars($ratingStats['average']);
                                ?>
                                <span class="ml-2 text-xs text-gray-400">(<?php echo $ratingStats['count']; ?>)</span>
                            </div>
                            
                            <div class="mt-auto flex items-center justify-between">
                                <span class="text-base sm:text-xl font-bold text-charcoal-900">
                                    <?php echo formatProductPrice($product, getSelectedCurrency()); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="flex justify-center">
                    <nav class="flex space-x-2 glass-strong p-2 rounded-xl">
                        <?php if ($pagination['has_prev']): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])); ?>" 
                                class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-folly hover:bg-folly-50 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                class="w-10 h-10 flex items-center justify-center text-sm font-bold rounded-lg transition-all <?php echo $i === $pagination['current_page'] ? 'bg-folly text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'; ?>"
                            >
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])); ?>" 
                                class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-folly hover:bg-folly-50 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>