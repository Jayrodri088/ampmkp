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
    <div class="container mx-auto px-4 py-16 text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Category Not Found</h1>
        <p class="text-gray-600 mb-8">The category you're looking for doesn't exist or has been removed.</p>
        <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-folly hover:bg-folly-600 text-white px-6 py-3 rounded-md font-medium">
            Continue Shopping
        </a>
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
<div class="bg-white border-b border-gray-100 py-3 md:py-4 mt-16 md:mt-20">
    <div class="container mx-auto px-4">
        <nav class="text-xs md:text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-folly hover:text-folly-600 hover:underline">Shop</a>
            <span class="text-gray-400">/</span>
            <?php 
            $breadcrumb = getCategoryBreadcrumb($category['id']);
            foreach ($breadcrumb as $index => $crumb):
                if ($index === count($breadcrumb) - 1): // Last item (current category)
            ?>
                    <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($crumb['name']); ?></span>
                <?php else: // Parent categories ?>
                    <a href="<?php echo getBaseUrl('category.php?slug=' . htmlspecialchars($crumb['slug'])); ?>" class="text-folly hover:text-folly-600 hover:underline"><?php echo htmlspecialchars($crumb['name']); ?></a>
                    <span class="text-gray-400">/</span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<!-- Category Header -->
<section class="relative bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-12 md:py-20 overflow-hidden">
    <!-- Background decorative elements -->
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
    <div class="absolute top-10 left-10 w-48 h-48 md:w-72 md:h-72 bg-folly-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    <div class="absolute top-20 right-10 w-48 h-48 md:w-72 md:h-72 bg-tangerine-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    <div class="container mx-auto px-4">
        <div class="text-center mb-8 md:mb-16">
            <div class="mb-6 md:mb-8">
                <img 
                    src="<?php echo getAssetUrl('images/' . $category['image']); ?>" 
                    alt="<?php echo htmlspecialchars($category['name']); ?>"
                    class="w-24 h-24 md:w-40 md:h-40 mx-auto rounded-3xl object-cover border border-gray-200 shadow-xl"
                    onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                >
            </div>
            <h1 class="text-2xl md:text-5xl lg:text-7xl font-bold text-gray-900 mb-6 md:mb-8 leading-tight px-4">
                <?php echo htmlspecialchars($category['name']); ?>
            </h1>
            <div class="w-16 md:w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6 md:mb-8"></div>
            <p class="text-base md:text-xl text-gray-600 max-w-4xl mx-auto leading-relaxed px-4">
                <?php echo htmlspecialchars($category['description']); ?>
            </p>
        </div>
        
        <!-- Search and Filters -->
        <div class="bg-white/80 backdrop-blur-sm p-4 md:p-8 rounded-2xl shadow-xl border border-gray-200 mb-6 md:mb-12">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                <!-- Search -->
                <div>
                    <label class="block text-xs md:text-sm font-bold text-gray-700 mb-2 md:mb-3">Search in <?php echo htmlspecialchars($category['name']); ?></label>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                        placeholder="Search products..." 
                        class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-sm md:text-base"
                    >
                </div>
                
                <!-- Sort -->
                <div>
                    <label class="block text-xs md:text-sm font-bold text-gray-700 mb-2 md:mb-3">Sort By</label>
                    <select name="sort" class="w-full px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-sm md:text-base">
                        <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                        <option value="featured" <?php echo $sortBy === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                        <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
                
                <!-- Submit -->
                <div class="flex items-end">
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-xl font-semibold text-sm md:text-base transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl touch-manipulation"
                    >
                        Apply Filters
                    </button>
                </div>
                
                <!-- Hidden field to preserve category -->
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                <input type="hidden" name="page" value="1">
            </form>
            
            <!-- Clear Filters -->
            <?php if ($searchQuery || $sortBy !== 'name'): ?>
                <div class="mt-4 md:mt-6">
                    <a href="<?php echo getBaseUrl('category.php?slug=' . htmlspecialchars($slug)); ?>" class="text-xs md:text-sm text-folly hover:text-folly-600 font-medium bg-folly-50 hover:bg-folly-100 px-3 md:px-4 py-2 rounded-lg transition-colors duration-200 touch-manipulation">
                        Clear all filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Results Info -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 md:mb-12 gap-3">
            <p class="text-gray-600 text-sm md:text-base">
                Showing <?php echo ($pagination['offset'] + 1); ?>-<?php echo min($pagination['offset'] + $itemsPerPage, $totalProducts); ?> 
                of <?php echo $totalProducts; ?> products in <?php echo htmlspecialchars($category['name']); ?>
            </p>
            <a href="<?php echo getBaseUrl('categories.php'); ?>" class="relative z-10 inline-flex items-center gap-2 text-folly hover:text-folly-600 text-xs md:text-sm font-medium bg-white hover:bg-folly-50 px-3 md:px-4 py-2 rounded-lg border border-gray-200 hover:border-folly-300 transition-all duration-200 shadow-sm hover:shadow-md touch-manipulation">
                Browse all categories 
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Sub-Categories Section -->
<?php if (!empty($subCategories)): ?>
<section class="bg-white py-12 border-b border-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">Explore Sub-Categories</h2>
            <div class="w-16 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-4"></div>
            <p class="text-gray-600">Browse specific collections within <?php echo htmlspecialchars($category['name']); ?></p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($subCategories as $subCategory): ?>
                <a 
                    href="<?php echo getBaseUrl('category.php?slug=' . htmlspecialchars($subCategory['slug'])); ?>" 
                    class="group bg-white rounded-xl border border-gray-200 hover:border-folly hover:shadow-lg transition-all duration-300 p-6 text-center transform hover:-translate-y-1"
                >
                    <div class="mb-4">
                        <img 
                            src="<?php echo getAssetUrl('images/' . $subCategory['image']); ?>" 
                            alt="<?php echo htmlspecialchars($subCategory['name']); ?>"
                            class="w-16 h-16 mx-auto rounded-lg object-cover border border-gray-200 group-hover:border-folly transition-colors duration-300"
                            onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                        >
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-folly transition-colors duration-300">
                        <?php echo htmlspecialchars($subCategory['name']); ?>
                    </h3>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                        <?php echo htmlspecialchars($subCategory['description']); ?>
                    </p>
                    <div class="flex items-center justify-center text-xs text-gray-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-6m-10 0h6"></path>
                        </svg>
                        <?php echo count(getProducts($subCategory['id'])); ?> products
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Products Grid -->
<section class="bg-gradient-to-br from-gray-50 to-charcoal-50 pb-12 md:pb-16">
    <div class="container mx-auto px-4">
        <!-- Products Info Header -->
        <div class="text-center mb-8 pt-12">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                <?php if (!empty($subCategories)): ?>
                    All Products in <?php echo htmlspecialchars($category['name']); ?>
                <?php else: ?>
                    Products
                <?php endif; ?>
            </h2>
            <p class="text-gray-600">
                <?php if (!empty($subCategories)): ?>
                    Including products from all sub-categories
                <?php else: ?>
                    Discover our collection in this category
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="text-center py-12 md:py-16">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-6m-10 0h6"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No products found</h3>
                <p class="text-gray-500 mb-6">
                    <?php if ($searchQuery): ?>
                        No products match your search criteria in this category.
                    <?php else: ?>
                        This category doesn't have any products yet.
                    <?php endif; ?>
                </p>
                <div class="space-x-4">
                    <?php if ($searchQuery): ?>
                        <a href="<?php echo getBaseUrl('category.php?slug=' . htmlspecialchars($slug)); ?>" class="bg-folly hover:bg-folly-600 text-white px-6 py-3 rounded-md font-medium transition-colors duration-200">
                            View All in <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-md font-medium transition-colors duration-200">
                        Browse All Products
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php foreach ($products as $product): ?>
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
                                    <?php echo formatProductPrice($product, $selectedCurrency); ?>
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
                <div class="mt-12 flex justify-center">
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

<!-- Other Categories -->
<section class="bg-gradient-to-br from-charcoal-50 to-tangerine-50 py-20">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">Explore Other Categories</h2>
            <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6"></div>
            <p class="text-gray-600 text-lg">Discover more amazing product collections</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php 
            $otherCategories = array_filter($categories, function($cat) use ($category) {
                return $cat['id'] !== $category['id'];
            });
            $otherCategories = array_slice($otherCategories, 0, 6);
            ?>
            <?php foreach ($otherCategories as $otherCategory): ?>
                <!-- DEBUG: getBaseUrl output: <?php echo getBaseUrl('category.php?slug=' . $otherCategory['slug']); ?> -->
                <a 
                    href="<?php echo getBaseUrl('category.php?slug=' . $otherCategory['slug']); ?>" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="group bg-white rounded-2xl border border-gray-100 hover:shadow-xl transition-all duration-300 p-8 text-center transform hover:-translate-y-2"
                >
                    <div class="mb-6">
                        <img 
                            src="<?php echo getAssetUrl('images/' . $otherCategory['image']); ?>" 
                            alt="<?php echo htmlspecialchars($otherCategory['name']); ?>"
                            class="w-20 h-20 mx-auto rounded-2xl object-cover border border-gray-200 shadow-lg group-hover:shadow-xl transition-shadow duration-300"
                            onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                        >
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-folly transition-colors duration-300">
                        <?php echo htmlspecialchars($otherCategory['name']); ?>
                    </h3>
                    <p class="text-gray-600 leading-relaxed">
                        <?php echo htmlspecialchars($otherCategory['description']); ?>
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
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