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
<div class="bg-white border-b border-gray-100 py-4 mt-24">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium">
                Search<?php echo !empty($query) ? ': "' . htmlspecialchars($query) . '"' : ''; ?>
            </span>
        </nav>
    </div>
</div>

<!-- Search Header -->
<section class="relative bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-20 overflow-hidden">
    <!-- Background decorative elements -->
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
    <div class="absolute top-10 left-10 w-72 h-72 bg-folly-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    <div class="absolute top-20 right-10 w-72 h-72 bg-tangerine-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h1 class="text-5xl md:text-7xl font-bold text-gray-900 mb-8 leading-tight">
                    <?php if (!empty($query)): ?>
                        Search 
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                            Results
                        </span>
                    <?php else: ?>
                        Find Your 
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                            Perfect Item
                        </span>
                    <?php endif; ?>
                </h1>
                <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-8"></div>
                <?php if (!empty($query)): ?>
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                        Showing results for "<span class="font-bold text-folly"><?php echo htmlspecialchars($query); ?></span>"
                        <?php if ($selectedCategory): ?>
                            in <span class="font-bold text-folly"><?php echo htmlspecialchars($selectedCategory['name']); ?></span>
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="text-xl text-gray-600 mb-8 leading-relaxed max-w-3xl mx-auto">
                        Search our complete collection of inspiring products. 
                        <span class="font-semibold text-folly">Find exactly what you're looking for</span>.
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Search Form -->
            <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-gray-200 mb-12">
                <form method="GET" action="<?php echo getBaseUrl('search.php'); ?>" class="space-y-6">
                    <!-- Main Search -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-3">Search Products</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                name="q" 
                                value="<?php echo htmlspecialchars($query); ?>"
                                placeholder="Enter product name, description, or keywords..." 
                                class="w-full px-6 py-4 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 shadow-sm text-lg"
                                autofocus
                            >
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Category Filter -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3">Category</label>
                            <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['slug']; ?>" <?php echo $categorySlug === $category['slug'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Sort -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3">Sort By</label>
                            <select name="sort" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200">
                                <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="featured" <?php echo $sortBy === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                                <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button 
                            type="submit" 
                            class="flex-1 bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-3"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Search Products
                        </button>
                        <?php if (!empty($query) || !empty($categorySlug) || $sortBy !== 'name'): ?>
                            <a href="<?php echo getBaseUrl('search.php'); ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-4 rounded-xl font-semibold text-center transition-all duration-200 border-2 border-gray-300 hover:border-gray-400">
                                Clear Search
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <input type="hidden" name="page" value="1">
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Search Results -->
<section class="bg-gradient-to-br from-gray-50 to-charcoal-50 pb-16">
    <div class="container mx-auto px-4">
        <?php if (!$searchPerformed): ?>
            <!-- Search Suggestions -->
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Popular Categories</h2>
                    <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6"></div>
                    <p class="text-gray-600 text-lg">Browse our most popular product categories</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                    <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                        <a 
                            href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" 
                            class="group bg-white rounded-2xl p-8 text-center hover:shadow-xl transition-all duration-300 border border-gray-200 transform hover:-translate-y-2"
                        >
                            <div class="mb-6">
                                <img 
                                    src="<?php echo getAssetUrl('images/' . $category['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($category['name']); ?>"
                                    class="w-20 h-20 mx-auto rounded-2xl object-cover border border-gray-200 shadow-lg group-hover:shadow-xl transition-shadow duration-300"
                                    onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                >
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-folly transition-colors duration-300">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </h3>
                            <p class="text-gray-600 leading-relaxed">
                                <?php echo htmlspecialchars($category['description']); ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Popular Searches -->
                <div class="text-center bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-lg border border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Popular Searches</h3>
                    <div class="flex flex-wrap justify-center gap-3">
                        <?php 
                        $popularSearches = ['sweatshirts', 'hoodies', 'grace', 'loveworld', 'iexcel', 'tap 2 read', 'affirmation'];
                        foreach ($popularSearches as $search): 
                        ?>
                            <a 
                                href="<?php echo getBaseUrl('search.php?q=' . urlencode($search)); ?>" 
                                class="bg-gradient-to-r from-folly-50 to-tangerine-50 hover:from-folly-100 hover:to-tangerine-100 text-charcoal-800 px-6 py-3 rounded-full font-semibold transition-all duration-200 transform hover:scale-105 shadow-sm hover:shadow-lg"
                            >
                                <?php echo htmlspecialchars($search); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php elseif (empty($products)): ?>
            <!-- No Results -->
            <div class="max-w-2xl mx-auto text-center py-16">
                <div class="text-gray-400 mb-6">
                    <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">No products found</h2>
                <p class="text-gray-500 mb-8">
                    We couldn't find any products matching your search criteria. Try adjusting your search terms or browse our categories.
                </p>
                
                <!-- Search Suggestions -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Try searching for:</h3>
                    <div class="flex flex-wrap justify-center gap-2">
                        <?php 
                        $suggestions = ['sweatshirts', 'hoodies', 'grace', 'loveworld', 'iexcel'];
                        foreach ($suggestions as $suggestion): 
                        ?>
                            <a 
                                href="<?php echo getBaseUrl('search.php?q=' . urlencode($suggestion)); ?>" 
                                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-full text-sm hover:bg-gray-200 transition-colors duration-200"
                            >
                                <?php echo htmlspecialchars($suggestion); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mt-8">
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-folly hover:bg-folly-600 text-white px-8 py-3 rounded-md font-medium transition-colors duration-200">
                        Browse All Products
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Results Info -->
            <div class="flex justify-between items-center mb-6">
                <p class="text-gray-600">
                    <?php if ($totalProducts > 0): ?>
                        Showing <?php echo ($pagination['offset'] + 1); ?>-<?php echo min($pagination['offset'] + $itemsPerPage, $totalProducts); ?> 
                        of <?php echo $totalProducts; ?> results
                    <?php else: ?>
                        Showing 0 results
                    <?php endif; ?>
                </p>
                            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-folly hover:text-folly-600 text-sm">
                Browse all products →
            </a>
            </div>
            
            <!-- Products Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 mb-16">
                <?php foreach ($paginatedProducts as $product): ?>
                    <div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden border border-gray-100 transform hover:-translate-y-2">
                        <!-- Background gradient overlay -->
                        <div class="absolute inset-0 bg-gradient-to-br from-folly/5 to-tangerine/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <!-- Featured Badge -->
                        <?php if ($product['featured']): ?>
                            <div class="absolute top-4 left-4 bg-gradient-to-r from-folly to-folly-600 text-white text-xs px-3 py-1.5 rounded-full z-10 font-bold shadow-lg">
                                Featured
                            </div>
                        <?php endif; ?>
                        
                        <div class="aspect-w-1 aspect-h-1 bg-gradient-to-br from-gray-100 to-gray-200 rounded-t-2xl overflow-hidden">
                            <img 
                                loading="lazy"
                                src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                class="w-full h-64 object-cover transition-transform duration-500 group-hover:scale-110"
                                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                            >
                            <!-- Image overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>
                        <div class="relative p-6">
                            <h3 class="font-bold text-gray-900 mb-3 text-lg group-hover:text-folly transition-colors duration-300 line-clamp-2">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2 leading-relaxed">
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
                                <span class="text-2xl font-bold text-gray-900">
                                    <?php echo formatProductPrice($product, getSelectedCurrency()); ?>
                                </span>
                                <?php if ($product['stock'] <= 5): ?>
                                    <span class="text-xs text-orange-600 font-bold bg-orange-50 px-2 py-1 rounded-full">
                                        Only <?php echo $product['stock']; ?> left
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col gap-2">
                                <a 
                                    href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" 
                                    class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-4 py-3 rounded-xl text-sm font-semibold text-center transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                                >
                                    View Details
                                </a>
                                <button 
                                    onclick="addToCart(<?php echo $product['id']; ?>)"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-3 rounded-xl text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2"
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l-2.5 5m12.5 0H9"></path>
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
                <div class="flex justify-center">
                    <nav class="flex space-x-2">
                        <?php if ($pagination['has_prev']): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])); ?>" 
                                class="px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                class="px-4 py-2 text-sm font-medium <?php echo $i === $pagination['current_page'] ? 'text-white bg-folly border border-folly' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-md"
                            >
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])); ?>" 
                                class="px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
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

<style>
.bg-grid-pattern {
    background-image: radial-gradient(circle at 1px 1px, rgba(255, 0, 85, 0.15) 1px, transparent 0);
    background-size: 20px 20px;
}

@keyframes fade-in-down {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}



.animation-delay-200 {
    animation-delay: 0.2s;
}

.animation-delay-400 {
    animation-delay: 0.4s;
}

.animation-delay-600 {
    animation-delay: 0.6s;
}

.animation-delay-800 {
    animation-delay: 0.8s;
}


</style>

<!-- Cart functionality is now handled by cart.js -->

<?php include 'includes/footer.php'; ?>