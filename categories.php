<?php
$page_title = 'All Categories';
$page_description = 'Browse all product categories at Angel Marketplace. Find the perfect products organized by category.';

require_once 'includes/functions.php';

// Get all categories
$categories = getCategories();

// Get category hierarchy for display
$categoryHierarchy = getCategoryHierarchy();


// Function to render categories hierarchically
function renderCategoryHierarchy($categories, $level = 0) {
    $html = '';
    if ($level === 0) {
        $html .= '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">';
    }
    
    foreach ($categories as $category) {
        $borderClass = $level > 0 ? 'border-l-4 border-folly-200 ml-4' : '';
        
        $html .= '<div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden transform hover:-translate-y-2 border border-gray-100 ' . $borderClass . '">';
        
        // Background gradient overlay
        $html .= '<div class="absolute inset-0 bg-gradient-to-br from-folly/5 to-tangerine/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>';
        
        // Image container
        $html .= '<div class="relative overflow-hidden rounded-t-2xl">';
        $html .= '<div class="aspect-w-16 aspect-h-10 bg-gradient-to-br from-gray-100 to-gray-200">';
        $html .= '<img src="' . getAssetUrl('images/' . $category['image']) . '" alt="' . htmlspecialchars($category['name']) . '" class="w-full h-48 object-cover transition-transform duration-500 group-hover:scale-110" onerror="this.onerror=null;this.src=\'' . getAssetUrl('images/general/placeholder.jpg') . '\'">';
        $html .= '</div>';
        $html .= '<div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>';
        $html .= '</div>';
        
        // Content
        $html .= '<div class="relative p-6">';
        
        // Category level indicator
        if ($level > 0) {
            $html .= '<div class="flex items-center mb-2">';
            $html .= '<span class="text-xs bg-folly-100 text-folly-800 px-2 py-1 rounded-full font-medium">Sub-category</span>';
            $html .= '</div>';
        }
        
        $html .= '<h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-folly transition-colors duration-300">';
        $html .= htmlspecialchars($category['name']);
        $html .= '</h3>';
        $html .= '<p class="text-gray-600 mb-6 leading-relaxed line-clamp-3 text-sm">';
        $html .= htmlspecialchars($category['description']);
        $html .= '</p>';
        
        // Sub-categories indicator
        $subCount = count($category['children'] ?? []);
        if ($subCount > 0) {
            $html .= '<div class="flex items-center mb-4 text-sm text-gray-600">';
            $html .= '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>';
            $html .= '</svg>';
            $html .= $subCount . ' sub-categor' . ($subCount === 1 ? 'y' : 'ies');
            $html .= '</div>';
        }
        
        // CTA and product count
        $html .= '<div class="flex items-center justify-between">';
        $html .= '<a href="' . getBaseUrl('category.php?slug=' . $category['slug']) . '" class="group/btn relative inline-flex items-center gap-2 bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">';
        $html .= '<span>Explore</span>';
        $html .= '<svg class="w-4 h-4 transition-transform duration-300 group-hover/btn:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>';
        $html .= '</svg>';
        $html .= '<div class="absolute inset-0 rounded-xl bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 -translate-x-full group-hover/btn:translate-x-full transition-transform duration-700"></div>';
        $html .= '</a>';
        $html .= '<div class="text-xs text-gray-500 bg-gray-100 px-2.5 py-1.5 rounded-lg">';
        $html .= '<span class="font-medium">View Products</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>'; // Close content div
        $html .= '</div>'; // Close category card
        
        // Render children in the same grid (they'll appear after parent)
        if (!empty($category['children'])) {
            $html .= renderCategoryHierarchy($category['children'], $level + 1);
        }
    }
    
    if ($level === 0) {
        $html .= '</div>';
    }
    return $html;
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-3 md:py-4 mt-16 md:mt-20">
    <div class="container mx-auto px-4">
        <nav class="text-xs md:text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium">All Categories</span>
        </nav>
    </div>
</div>

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-20 overflow-hidden">
    <!-- Background decorative elements -->
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
    <div class="absolute top-10 left-10 w-72 h-72 bg-folly-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    <div class="absolute top-20 right-10 w-72 h-72 bg-tangerine-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-charcoal-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    
    <div class="relative container mx-auto px-4">
        <div class="text-center max-w-4xl mx-auto">
            <h1 class="text-5xl md:text-7xl font-bold text-gray-900 mb-8 leading-tight">
                All <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">Categories</span>
            </h1>
            <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-8"></div>
            <p class="text-xl text-gray-600 mb-12 leading-relaxed">
                Explore our complete collection of categories. Find exactly what you're looking for 
                with our organized product collections designed to inspire and delight.
            </p>
            
            <!-- Quick Stats -->
            <div class="flex flex-wrap justify-center items-center gap-8 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-folly" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                    </svg>
                    <span><?php echo count($categoryHierarchy); ?> Categories</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Curated Collections</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-tangerine" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Hand-picked Products</span>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Categories Grid -->
<section class="bg-white py-16">
    <div class="container mx-auto px-4">
        <?php if (empty($categoryHierarchy)): ?>
            <div class="text-center py-16">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-6m-10 0h6"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No categories found</h3>
                <p class="text-gray-500 mb-6">
                    There are no categories available at the moment.
                </p>
                <div class="space-x-4">
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-lg font-medium transition-colors duration-200">
                        Browse All Products
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Show hierarchical categories -->
            <?php echo renderCategoryHierarchy($categoryHierarchy); ?>
        <?php endif; ?>
    </div>
</section>

<!-- Call to Action -->
<section class="bg-gradient-to-r from-folly to-folly-600 py-16">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            Can't Find What You're Looking For?
        </h2>
        <p class="text-white/90 text-xl max-w-2xl mx-auto mb-8">
            Browse all our products or use our search feature to find exactly what you need.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-white hover:bg-gray-100 text-folly px-8 py-4 rounded-xl font-semibold text-lg transition-colors duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    Browse All Products
                </a>
                <a href="<?php echo getBaseUrl('search.php'); ?>" class="bg-folly-800 hover:bg-folly-900 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-colors duration-200 border-2 border-folly-500 hover:border-folly-400">
                Advanced Search
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<style>
.bg-grid-pattern {
    background-image: radial-gradient(circle at 1px 1px, rgba(255, 0, 85, 0.15) 1px, transparent 0);
    background-size: 20px 20px;
}
</style>

