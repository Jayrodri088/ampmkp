<?php
require_once 'includes/functions.php';

// Load settings
$settings = getSettings();

// Selected currency
$selectedCurrency = getSelectedCurrency();

// Get category slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . getBaseUrl('shop.php'));
    exit;
}

// Get category details
$category = getCategoryBySlug($slug);

if (!$category) {
    header('HTTP/1.0 404 Not Found');
    $page_title = 'Category Not Found';
    include 'includes/header.php';
    ?>
    <div class="min-h-[60vh] flex items-center justify-center bg-gray-50">
        <div class="text-center px-4">
            <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-charcoal-900 mb-4 font-display">Category Not Found</h1>
            <p class="text-gray-600 mb-8 max-w-md mx-auto">The category you're looking for doesn't exist or has been removed.</p>
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center gap-2 bg-folly hover:bg-folly-600 text-white px-8 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Continue Shopping
            </a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// Get filter parameters
$searchQuery = $_GET['search'] ?? '';
$sortBy = $_GET['sort'] ?? 'name';
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = $settings['items_per_page'] ?? 12;

// Get all categories for filter
$categories = getCategories();

// Get products for this category
if (!empty($searchQuery)) {
    $products = searchProducts($searchQuery, $category['id']);
} else {
    // Use the new function to get products from category and all sub-categories
    $products = getProductsFromCategoryTree($category['id']);
}

// Get sub-categories for display
$subCategories = getSubCategories($category['id'], false); // Only active sub-categories

// Sort products
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

// Pagination
$totalProducts = count($products);
$pagination = paginate($totalProducts, $itemsPerPage, $page);
$products = array_slice($products, $pagination['offset'], $itemsPerPage);

$page_title = $category['name'];
$page_description = $category['description'];

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-gray-500 hover:text-folly transition-colors">Shop</a>
            <span class="text-gray-300">/</span>
            <?php 
            $breadcrumb = getCategoryBreadcrumb($category['id']);
            foreach ($breadcrumb as $index => $crumb):
                if ($index === count($breadcrumb) - 1): // Last item (current category)
            ?>
                    <span class="text-charcoal-900 font-medium"><?php echo htmlspecialchars($crumb['name']); ?></span>
                <?php else: // Parent categories ?>
                    <a href="<?php echo getBaseUrl('category.php?slug=' . htmlspecialchars($crumb['slug'])); ?>" class="text-gray-500 hover:text-folly transition-colors"><?php echo htmlspecialchars($crumb['name']); ?></a>
                    <span class="text-gray-300">/</span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<!-- Category Hero -->
<section class="relative bg-charcoal-900 py-20 overflow-hidden">
    <!-- Background Elements -->
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80')] bg-cover bg-center opacity-20"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-charcoal-900/90 via-charcoal-900/80 to-charcoal-900"></div>
    
    <div class="relative container mx-auto px-4 text-center">
        <div class="mb-8 inline-block p-1 rounded-2xl bg-gradient-to-br from-folly/20 to-tangerine/20 backdrop-blur-sm border border-white/10">
            <img 
                src="<?php echo getAssetUrl('images/' . $category['image']); ?>" 
                alt="<?php echo htmlspecialchars($category['name']); ?>"
                class="w-24 h-24 rounded-xl object-cover"
                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
            >
        </div>
        
        <h1 class="text-2xl sm:text-4xl md:text-6xl font-bold text-white mb-4 sm:mb-6 font-display">
            <?php echo htmlspecialchars($category['name']); ?>
        </h1>
        
        <div class="w-16 sm:w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-4 sm:mb-8"></div>
        
        <p class="text-sm sm:text-lg md:text-xl text-gray-300 max-w-3xl mx-auto leading-relaxed font-light px-2">
            <?php echo htmlspecialchars($category['description']); ?>
        </p>
    </div>
</section>

<!-- Main Content -->
<section class="py-12 bg-gradient-to-b from-gray-50 to-white min-h-screen">
    <div class="container mx-auto px-4">
        
        <!-- Filter & Search Bar -->
        <div class="glass-strong rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-6 mb-8 sm:mb-10 -mt-16 sm:-mt-20 relative z-10">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                <!-- Search -->
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Search</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            placeholder="Search in <?php echo htmlspecialchars($category['name']); ?>..." 
                            class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Sort -->
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Sort By</label>
                    <div class="relative">
                        <select name="sort" class="w-full pl-4 pr-10 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none appearance-none cursor-pointer">
                            <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="featured" <?php echo $sortBy === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                            <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                        <svg class="w-5 h-5 text-gray-400 absolute right-4 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="md:col-span-3 flex gap-3">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-charcoal-900 to-charcoal-800 hover:from-folly hover:to-folly-500 text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        Apply
                    </button>
                    <?php if ($searchQuery || $sortBy !== 'name'): ?>
                        <a href="<?php echo getBaseUrl('category.php?slug=' . htmlspecialchars($slug)); ?>" class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl font-bold transition-colors" title="Clear Filters">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    <?php endif; ?>
                </div>

                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                <input type="hidden" name="page" value="1">
            </form>
        </div>

        <!-- Sub-Categories Grid -->
        <?php if (!empty($subCategories)): ?>
        <div class="mb-10 sm:mb-16">
            <h2 class="text-lg sm:text-2xl font-bold text-charcoal-900 mb-4 sm:mb-8 flex items-center gap-2 sm:gap-3">
                <span class="w-1.5 sm:w-2 h-6 sm:h-8 bg-folly rounded-full"></span>
                Sub-Categories
            </h2>
            <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-6">
                <?php foreach ($subCategories as $subCategory): ?>
                    <a href="<?php echo getBaseUrl('category.php?slug=' . htmlspecialchars($subCategory['slug'])); ?>" class="group glass rounded-xl sm:rounded-2xl p-3 sm:p-6 hover:shadow-xl transition-all duration-300 text-center transform hover:-translate-y-1">
                        <div class="w-10 h-10 sm:w-16 sm:h-16 mx-auto mb-2 sm:mb-4 rounded-full bg-gray-50 flex items-center justify-center group-hover:scale-110 transition-transform duration-300 overflow-hidden">
                            <img 
                                src="<?php echo getAssetUrl('images/' . $subCategory['image']); ?>" 
                                alt="<?php echo htmlspecialchars($subCategory['name']); ?>"
                                class="w-full h-full object-cover"
                                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                            >
                        </div>
                        <h3 class="font-bold text-charcoal-900 group-hover:text-folly transition-colors line-clamp-1 text-xs sm:text-base">
                            <?php echo htmlspecialchars($subCategory['name']); ?>
                        </h3>
                        <p class="text-[10px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1 hidden sm:block"><?php echo count(getProducts($subCategory['id'])); ?> Products</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <div class="mb-4 sm:mb-8 flex justify-between items-center">
            <h2 class="text-lg sm:text-2xl font-bold text-charcoal-900 flex items-center gap-2 sm:gap-3">
                <span class="w-1.5 sm:w-2 h-6 sm:h-8 bg-tangerine rounded-full"></span>
                <?php echo !empty($subCategories) ? 'All Products' : 'Products'; ?>
                <span class="text-xs sm:text-sm font-normal text-gray-500 ml-1 sm:ml-2">(<?php echo $totalProducts; ?>)</span>
            </h2>
        </div>

        <?php if (empty($products)): ?>
            <div class="glass-strong rounded-3xl p-12 text-center shadow-sm">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-6m-10 0h6"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-charcoal-900 mb-2">No products found</h3>
                <p class="text-gray-600 mb-8">
                    <?php if ($searchQuery): ?>
                        We couldn't find any products matching "<?php echo htmlspecialchars($searchQuery); ?>" in this category.
                    <?php else: ?>
                        This category doesn't have any products yet. Check back soon!
                    <?php endif; ?>
                </p>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center gap-2 bg-gradient-to-r from-charcoal-900 to-charcoal-800 hover:from-folly hover:to-folly-500 text-white px-8 py-3 rounded-xl font-bold transition-all duration-300">
                    Browse All Products
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6 md:gap-8">
                <?php foreach ($products as $index => $product): 
                    $isLcp = $index < 4; // Eager load first 4 products
                ?>
                    <div class="group glass rounded-2xl sm:rounded-3xl hover:shadow-xl sm:hover:shadow-2xl transition-all duration-500 overflow-hidden flex flex-col h-full transform hover:-translate-y-1 sm:hover:-translate-y-2">
                        <!-- Image Container -->
                        <div class="relative aspect-[4/5] overflow-hidden bg-gray-100">
                            <!-- Loading Skeleton -->
                            <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>

                            <?php if ($product['featured']): ?>
                                <div class="absolute top-2 left-2 sm:top-4 sm:left-4 z-20">
                                    <span class="px-2 py-0.5 sm:px-3 sm:py-1 bg-folly text-white text-[10px] sm:text-xs font-bold rounded-full shadow-lg backdrop-blur-md bg-opacity-90">
                                        FEATURED
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($product['stock'] <= 0): ?>
                                <div class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-10 flex items-center justify-center">
                                    <span class="px-4 py-2 bg-charcoal-900 text-white text-sm font-bold rounded-xl shadow-xl">
                                        OUT OF STOCK
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
                            
                            <!-- Quick Actions Overlay -->
                            <div class="absolute inset-x-0 bottom-0 p-4 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-4 group-hover:translate-y-0 z-20 bg-gradient-to-t from-black/50 to-transparent">
                                <button 
                                    onclick="addToCart(<?php echo $product['id']; ?>)"
                                    class="w-full bg-white text-charcoal-900 font-bold py-3 rounded-xl shadow-lg hover:bg-folly hover:text-white transition-colors flex items-center justify-center gap-2"
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                    Add to Cart
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-3 sm:p-6 flex flex-col flex-grow">
                            <div class="mb-1 sm:mb-2">
                                <span class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-sm sm:text-lg font-bold text-charcoal-900 mb-1 sm:mb-2 line-clamp-2 group-hover:text-folly transition-colors">
                                <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            
                            <!-- Rating -->
                            <div class="flex items-center mb-2 sm:mb-4">
                                <?php 
                                $ratingStats = getProductRatingStats($product['id']);
                                echo renderStars($ratingStats['average'], 5, 'w-3 h-3 sm:w-4 sm:h-4');
                                ?>
                                <span class="ml-1 sm:ml-2 text-[10px] sm:text-xs text-gray-400 font-medium">
                                    (<?php echo $ratingStats['count']; ?>)
                                </span>
                            </div>
                            
                            <div class="mt-auto flex items-center justify-between">
                                <div class="flex flex-col">
                                    <span class="text-base sm:text-2xl font-bold text-charcoal-900">
                                        <?php echo formatProductPrice($product, $selectedCurrency); ?>
                                    </span>
                                </div>
                                <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="hidden sm:flex w-10 h-10 rounded-full bg-gray-50 items-center justify-center text-gray-400 hover:bg-folly hover:text-white transition-all duration-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="mt-16 flex justify-center">
                    <nav class="flex items-center gap-2 glass-strong p-2 rounded-2xl">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-600 hover:bg-folly hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl font-bold transition-colors <?php echo $i === $pagination['current_page'] ? 'bg-folly text-white shadow-lg shadow-folly/30' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-600 hover:bg-folly hover:text-white transition-colors">
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