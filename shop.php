<?php
$page_title = 'Shop';
$page_description = 'Shop our complete collection of Christian merchandise, apparel, gifts and spiritual resources at Angel Marketplace.';

require_once 'includes/functions.php';

// Load settings
$settings = getSettings();

// Selected currency
$selectedCurrency = getSelectedCurrency();

// Get filter parameters
$categorySlug = $_GET['category'] ?? null;
$searchQuery = $_GET['search'] ?? '';
$sortBy = $_GET['sort'] ?? 'name';
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = $settings['items_per_page'] ?? 12;

// Get categories for filter
$categories = getCategories();
$selectedCategory = null;

if ($categorySlug) {
    $selectedCategory = getCategoryBySlug($categorySlug);
}

// Get products based on filters
if (!empty($searchQuery)) {
    $products = searchProducts($searchQuery, $selectedCategory ? $selectedCategory['id'] : null);
} else {
    $products = getProducts($selectedCategory ? $selectedCategory['id'] : null);
}

// Sort products
switch ($sortBy) {
    case 'price_low':
        usort($products, function($a, $b) { return $a['price'] <=> $b['price']; });
        break;
    case 'price_high':
        usort($products, function($a, $b) { return $b['price'] <=> $a['price']; });
        break;
    case 'name':
    default:
        usort($products, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
        break;
}

// Pagination
$totalProducts = count($products);
$pagination = paginate($totalProducts, $itemsPerPage, $page);
$products = array_slice($products, $pagination['offset'], $itemsPerPage);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <?php if ($selectedCategory): ?>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-gray-500 hover:text-folly transition-colors">Shop</a>
                <span class="text-gray-300">/</span>
                <?php 
                $breadcrumb = getCategoryBreadcrumb($selectedCategory['id']);
                foreach ($breadcrumb as $index => $crumb):
                    if ($index === count($breadcrumb) - 1): // Last item (current category)
                ?>
                        <span class="text-charcoal-900 font-medium"><?php echo htmlspecialchars($crumb['name']); ?></span>
                    <?php else: // Parent categories ?>
                        <a href="<?php echo getBaseUrl('category.php?slug=' . htmlspecialchars($crumb['slug'])); ?>" class="text-gray-500 hover:text-folly transition-colors"><?php echo htmlspecialchars($crumb['name']); ?></a>
                        <span class="text-gray-300">/</span>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-charcoal-900 font-medium">Shop</span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Shop Header -->
<section class="relative bg-gray-50 py-16 md:py-24 overflow-hidden">
    <!-- Decorative Background Elements -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-folly/5 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 -left-24 w-72 h-72 bg-tangerine/5 rounded-full blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center max-w-4xl mx-auto">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-charcoal-900 mb-6 tracking-tight">
                <?php echo $selectedCategory ? htmlspecialchars($selectedCategory['name']) : 'Shop All Products'; ?>
            </h1>
            <div class="w-24 h-1.5 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-8"></div>
            <p class="text-lg text-charcoal-600 leading-relaxed">
                <?php echo $selectedCategory ? htmlspecialchars($selectedCategory['description']) : 'Discover our complete collection of quality products designed to inspire and delight. Find exactly what you\'re looking for with our organized product collections.'; ?>
            </p>
            
            <!-- Stats Badges -->
            <div class="flex flex-wrap justify-center gap-4 mt-8">
                <div class="inline-flex items-center px-4 py-2 bg-white rounded-full shadow-sm border border-gray-100 text-sm font-medium text-charcoal-600">
                    <span class="w-2 h-2 bg-folly rounded-full mr-2"></span>
                    <?php echo $totalProducts; ?> Products Available
                </div>
                <div class="inline-flex items-center px-4 py-2 bg-white rounded-full shadow-sm border border-gray-100 text-sm font-medium text-charcoal-600">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Quality Guaranteed
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-4">
        
        <!-- Filters Bar -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-12 -mt-20 relative z-20">
            <form method="GET" action="<?php echo getBaseUrl('shop.php'); ?>" class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                <!-- Search -->
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-charcoal-500 uppercase tracking-wider mb-2">Search</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            placeholder="Search products..." 
                            class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all"
                        >
                        <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                
                <!-- Category -->
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-charcoal-500 uppercase tracking-wider mb-2">Category</label>
                    <div class="relative">
                        <select name="category" class="w-full pl-4 pr-10 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all appearance-none">
                            <option value="">All Categories</option>
                            <?php 
                            $hierarchy = getCategoryHierarchy();
                            echo renderFrontendCategoryOptions($hierarchy, $categorySlug, 0);
                            ?>
                        </select>
                        <div class="absolute right-3 top-3.5 pointer-events-none text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>
                
                <!-- Sort -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-charcoal-500 uppercase tracking-wider mb-2">Sort By</label>
                    <div class="relative">
                        <select name="sort" class="w-full pl-4 pr-10 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all appearance-none">
                            <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price (Low-High)</option>
                            <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price (High-Low)</option>
                        </select>
                        <div class="absolute right-3 top-3.5 pointer-events-none text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>
                
                <!-- Submit -->
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-charcoal-900 hover:bg-folly text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                        <span>Filter</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    </button>
                </div>

                <input type="hidden" name="page" value="1">
            </form>
            
            <!-- Active Filters -->
            <?php if ($searchQuery || $categorySlug || $sortBy !== 'name'): ?>
                <div class="mt-6 pt-6 border-t border-gray-100 flex flex-wrap items-center gap-3">
                    <span class="text-sm text-gray-500 font-medium">Active Filters:</span>
                    <?php if ($searchQuery): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-folly-50 text-folly border border-folly-100">
                            Search: <?php echo htmlspecialchars($searchQuery); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($selectedCategory): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-folly-50 text-folly border border-folly-100">
                            Category: <?php echo htmlspecialchars($selectedCategory['name']); ?>
                        </span>
                    <?php endif; ?>
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-sm text-gray-400 hover:text-folly underline decoration-dotted transition-colors ml-auto">Clear All</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Results Grid -->
        <?php if (empty($products)): ?>
            <div class="text-center py-24">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-charcoal-900 mb-2">No products found</h3>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">We couldn't find any products matching your criteria. Try adjusting your filters or search terms.</p>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center justify-center px-8 py-3 text-base font-bold text-white transition-all duration-200 bg-folly border border-transparent rounded-xl hover:bg-folly-600 shadow-lg hover:shadow-xl">
                    View All Products
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-8">
                <?php foreach ($products as $index => $product): 
                    $isLcp = $index < 4; // Eager load first 4 products (first row on desktop)
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
                                 class="absolute inset-0 w-full h-full object-cover transition-all duration-300 group-hover:scale-110 opacity-0 z-10"
                                 onload="this.classList.remove('opacity-0')"
                                 onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')"
                                 <?php else: ?>
                                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-110 z-10"
                                 onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                 <?php endif; ?>>
                            
                            <!-- Quick Actions (Hidden on mobile) -->
                            <div class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 flex gap-2 hidden md:flex z-20">
                                <button onclick="addToCart(<?php echo $product['id']; ?>)" class="flex-1 bg-white text-charcoal-900 font-semibold py-3 rounded-xl hover:bg-folly hover:text-white transition-colors shadow-lg text-sm flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m-.4-5L2 1m5 12v2a2 2 0 002 2h10a2 2 0 002-2v-2m-6 4h.01M9 19h.01"></path></svg>
                                    Add
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
                                $ratingStats = getProductRatingStats($product['id']);
                                echo renderStars($ratingStats['average'], 5, 'w-3 h-3 md:w-4 md:h-4');
                                ?>
                                <span class="text-[10px] md:text-xs text-gray-400 ml-1 md:ml-2">(<?php echo $ratingStats['count']; ?>)</span>
                            </div>
                            
                            <div class="mt-auto flex flex-col md:flex-row md:items-center justify-between gap-2">
                                <div class="flex flex-col">
                                    <span class="text-base md:text-xl font-bold text-charcoal-900"><?php echo formatProductPrice($product, $selectedCurrency); ?></span>
                                    <?php if (isset($product['old_price']) && $product['old_price'] > $product['price']): ?>
                                    <span class="text-[10px] md:text-sm text-gray-400 line-through"><?php echo formatPriceWithCurrency($product['old_price'], $selectedCurrency); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($product['stock'] <= 5): ?>
                                    <span class="text-[10px] md:text-xs text-orange-600 font-bold bg-orange-50 px-2 py-1 rounded-full w-fit">
                                        Only <?php echo $product['stock']; ?> left
                                    </span>
                                <?php endif; ?>
                                <!-- Mobile Add Button -->
                                <button onclick="addToCart(<?php echo $product['id']; ?>)" class="md:hidden w-full bg-charcoal-900 text-white text-xs font-bold py-2 rounded-lg hover:bg-folly transition-colors">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="mt-16 flex justify-center">
                    <nav class="flex items-center gap-2">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 text-charcoal-600 hover:bg-folly hover:text-white hover:border-folly transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl font-bold transition-all <?php echo $i === $pagination['current_page'] ? 'bg-folly text-white shadow-lg' : 'border border-gray-200 text-charcoal-600 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 text-charcoal-600 hover:bg-folly hover:text-white hover:border-folly transition-all">
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