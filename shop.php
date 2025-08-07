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
<div class="bg-white border-b border-gray-100 py-3 md:py-4 mt-16 md:mt-20">
    <div class="container mx-auto px-4">
        <nav class="text-xs md:text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <?php if ($selectedCategory): ?>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-folly hover:text-folly-600 hover:underline">Shop</a>
                <span class="text-gray-400">/</span>
                <?php 
                $breadcrumb = getCategoryBreadcrumb($selectedCategory['id']);
                foreach ($breadcrumb as $index => $crumb):
                    if ($index === count($breadcrumb) - 1): // Last item (current category)
                ?>
                        <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($crumb['name']); ?></span>
                    <?php else: // Parent categories ?>
                        <a href="<?php echo getBaseUrl('category.php?slug=' . htmlspecialchars($crumb['slug'])); ?>" class="text-folly hover:text-folly-600 hover:underline"><?php echo htmlspecialchars($crumb['name']); ?></a>
                        <span class="text-gray-400">/</span>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-gray-700 font-medium">Shop</span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Shop Header -->
<section class="bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-8 md:py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-6 md:mb-12">
            <h1 class="text-2xl md:text-4xl lg:text-6xl font-bold text-gray-900 mb-4 md:mb-6">
                <?php echo $selectedCategory ? htmlspecialchars($selectedCategory['name']) : 'Shop All Products'; ?>
            </h1>
            <div class="w-16 md:w-24 h-1 bg-gradient-to-r from-folly to-folly-600 mx-auto rounded-full mb-4 md:mb-6"></div>
            <p class="text-gray-600 max-w-3xl mx-auto text-base md:text-lg leading-relaxed px-4">
                <?php echo $selectedCategory ? htmlspecialchars($selectedCategory['description']) : 'Discover our complete collection of quality products designed to inspire and delight. Find exactly what you\'re looking for with our organized product collections.'; ?>
            </p>
            
            <!-- Stats -->
            <div class="flex flex-wrap justify-center items-center gap-4 md:gap-8 mt-6 md:mt-8 text-xs md:text-sm text-gray-600 px-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-folly" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                    </svg>
                    <span><?php echo $totalProducts; ?> Products</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Quality Guaranteed</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-tangerine" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"></path>
                    </svg>
                    <span>Fast Shipping</span>
                </div>
            </div>
        </div>
        
        <!-- Search and Filters -->
        <div class="bg-white/80 backdrop-blur-sm p-4 md:p-8 rounded-2xl shadow-lg border border-gray-200 mb-6 md:mb-8">
            <form method="GET" action="<?php echo getBaseUrl('shop.php'); ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4 md:gap-6">
                <!-- Search -->
                <div>
                    <label class="block text-xs md:text-sm font-semibold text-gray-700 mb-2 md:mb-3">Search Products</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            placeholder="Search products..." 
                            class="w-full px-3 md:px-4 py-2 md:py-3 pl-8 md:pl-10 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-sm md:text-base"
                        >
                        <svg class="absolute left-2 md:left-3 top-2.5 md:top-3.5 w-3 h-3 md:w-4 md:h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Category Filter -->
                <div>
                    <label class="block text-xs md:text-sm font-semibold text-gray-700 mb-2 md:mb-3">Category</label>
                    <select name="category" class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-sm md:text-base">
                        <option value="">All Categories</option>
                        <?php 
                        $hierarchy = getCategoryHierarchy();
                        echo renderFrontendCategoryOptions($hierarchy, $categorySlug, 0);
                        ?>
                    </select>
                </div>
                
                <!-- Sort -->
                <div>
                    <label class="block text-xs md:text-sm font-semibold text-gray-700 mb-2 md:mb-3">Sort By</label>
                    <select name="sort" class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-sm md:text-base">
                        <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                        <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
                
                <!-- Submit -->
                <div class="flex items-end">
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-xl font-semibold text-sm md:text-base transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-2 touch-manipulation"
                    >
                        <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Apply Filters
                    </button>
                </div>
                
                <!-- Hidden fields to preserve current page -->
                <input type="hidden" name="page" value="1">
            </form>
            
            <!-- Clear Filters -->
            <?php if ($searchQuery || $categorySlug || $sortBy !== 'name'): ?>
                <div class="mt-4 md:mt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <p class="text-xs md:text-sm text-gray-600">
                        Active filters applied
                        <?php if ($searchQuery): ?>
                            • Search: "<?php echo htmlspecialchars($searchQuery); ?>"
                        <?php endif; ?>
                        <?php if ($selectedCategory): ?>
                            • Category: <?php echo htmlspecialchars($selectedCategory['name']); ?>
                        <?php endif; ?>
                    </p>
                                            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-xs md:text-sm text-folly hover:text-folly-600 font-medium bg-folly-50 hover:bg-folly-100 px-3 md:px-4 py-2 rounded-lg transition-colors duration-200 touch-manipulation">
                        Clear all filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Results Info -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 md:mb-8 gap-3 md:gap-4">
            <div>
                <p class="text-gray-600 text-sm md:text-lg">
                    <?php if ($totalProducts > 0): ?>
                        Showing <span class="font-semibold text-gray-900"><?php echo ($pagination['offset'] + 1); ?>-<?php echo min($pagination['offset'] + $itemsPerPage, $totalProducts); ?></span> 
                        of <span class="font-semibold text-gray-900"><?php echo $totalProducts; ?></span> products
                    <?php else: ?>
                        Showing <span class="font-semibold text-gray-900">0</span> products
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex items-center gap-3 md:gap-4">
                                    <a href="<?php echo getBaseUrl('categories.php'); ?>" class="text-folly hover:text-folly-600 font-medium text-xs md:text-sm bg-folly-50 hover:bg-folly-100 px-3 md:px-4 py-2 rounded-lg transition-colors duration-200 touch-manipulation">
                    Browse Categories
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Products Grid -->
<section class="bg-white pt-6 md:pt-8 pb-12 md:pb-16">
    <div class="container mx-auto px-4">
        <?php if (empty($products)): ?>
            <div class="text-center py-12 md:py-16">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-6m-10 0h6"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No products found</h3>
                <p class="text-gray-500 mb-6">Try adjusting your search criteria or browse all products.</p>
                                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-folly hover:bg-folly-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-md font-medium text-sm md:text-base transition-colors duration-200 touch-manipulation">
                    View All Products
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-8">
                <?php foreach ($products as $product): ?>
                    <div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden border border-gray-100">
                        <!-- Background gradient overlay -->
                        <div class="absolute inset-0 bg-gradient-to-br from-folly/5 to-tangerine/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <!-- Image container -->
                        <div class="relative overflow-hidden rounded-t-2xl">
                            <div class="aspect-w-1 aspect-h-1 bg-gradient-to-br from-gray-100 to-gray-200">
                                <img 
                                    loading="lazy"
                                    src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    class="w-full h-48 md:h-64 object-cover transition-transform duration-500 group-hover:scale-110"
                                    onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';"
                                >
                            </div>
                            <!-- Image overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            
                            <!-- Stock badge -->
                            <?php if ($product['stock'] <= 5): ?>
                                <div class="absolute top-4 right-4">
                                    <span class="bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-bold shadow-lg">
                                        Only <?php echo $product['stock']; ?> left
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Quick view button -->
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <a 
                                    href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" 
                                    class="bg-white/90 backdrop-blur-sm hover:bg-white text-gray-800 px-4 py-2 rounded-full text-sm font-semibold shadow-lg transform scale-95 group-hover:scale-100 transition-all duration-300"
                                >
                                    Quick View
                                </a>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="relative p-4 md:p-6">
                            <h3 class="font-bold text-gray-900 mb-2 md:mb-3 text-base md:text-lg group-hover:text-folly transition-colors duration-300 line-clamp-2">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>
                            <p class="text-gray-600 text-xs md:text-sm mb-4 line-clamp-2 leading-relaxed">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </p>
                            
                            <!-- Rating stars (real data) -->
                            <div class="flex items-center mb-4">
                                <?php 
                                $ratingStats = getProductRatingStats($product['id']);
                                echo renderStars($ratingStats['average']);
                                ?>
                                <span class="ml-2 text-xs text-gray-500">
                                    <?php if ($ratingStats['count'] > 0): ?>
                                        (<?php echo $ratingStats['average']; ?> - <?php echo $ratingStats['count']; ?> review<?php echo $ratingStats['count'] != 1 ? 's' : ''; ?>)
                                    <?php else: ?>
                                        (No reviews yet)
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-lg md:text-2xl font-bold text-gray-900">
                                    <?php echo formatProductPrice($product, $selectedCurrency); ?>
                                </span>
                                <?php if ($product['stock'] <= 5): ?>
                                    <span class="text-xs text-orange-600 font-bold bg-orange-50 px-2 py-1 rounded-full">
                                        Only <?php echo $product['stock']; ?> left
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex flex-col gap-2">
                                <a 
                                    href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" 
                                    class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-3 md:px-4 py-2 rounded-xl text-xs md:text-sm font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl text-center touch-manipulation"
                                >
                                    View Details
                                </a>
                                <button 
                                    onclick="addToCart(<?php echo $product['id']; ?>)"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-3 md:px-4 py-2 rounded-xl text-xs md:text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 touch-manipulation"
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m-.4-5L2 1m5 12v2a2 2 0 002 2h10a2 2 0 002-2v-2m-6 4h.01M9 19h.01"></path>
                                    </svg>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="mt-8 md:mt-16 flex justify-center">
                    <nav class="flex space-x-1 md:space-x-2">
                        <?php if ($pagination['has_prev']): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])); ?>" 
                                class="px-3 md:px-6 py-2 md:py-3 text-xs md:text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-folly-50 hover:text-folly hover:border-folly-300 transition-all duration-200 shadow-sm touch-manipulation"
                            >
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                class="px-3 md:px-4 py-2 md:py-3 text-xs md:text-sm font-semibold <?php echo $i === $pagination['current_page'] ? 'text-white bg-gradient-to-r from-folly to-folly-600 border border-folly shadow-lg' : 'text-gray-600 bg-white border border-gray-300 hover:bg-folly-50 hover:text-folly hover:border-folly-300'; ?> rounded-xl transition-all duration-200 touch-manipulation"
                            >
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])); ?>" 
                                class="px-3 md:px-6 py-2 md:py-3 text-xs md:text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-folly-50 hover:text-folly hover:border-folly-300 transition-all duration-200 shadow-sm touch-manipulation"
                            >
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Cart functionality is now handled by cart.js -->

<?php include 'includes/footer.php'; ?>