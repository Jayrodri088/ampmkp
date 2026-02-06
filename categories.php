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
        $html .= '<div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6 md:gap-8">';
    }
    
    foreach ($categories as $category) {
        $borderClass = $level > 0 ? 'border-l-4 border-folly-200 ml-4' : '';
        
        $html .= '<div class="group relative glass rounded-2xl hover:shadow-xl transition-all duration-500 overflow-hidden transform hover:-translate-y-2 ' . $borderClass . '">';
        
        // Image container
        $html .= '<div class="relative overflow-hidden aspect-[4/3] bg-gray-100">';
        // Loading Skeleton
        $html .= '<div class="absolute inset-0 bg-gray-200 animate-pulse"></div>';
        
        $html .= '<img src="' . getAssetUrl('images/' . $category['image']) . '" alt="' . htmlspecialchars($category['name']) . '" loading="lazy" class="absolute inset-0 w-full h-full object-cover transition-all duration-300 group-hover:scale-110 opacity-0 z-10" onload="this.classList.remove(\'opacity-0\')" onerror="this.onerror=null;this.src=\'' . getAssetUrl('images/general/placeholder.jpg') . '\';this.classList.remove(\'opacity-0\')">';
        $html .= '<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-60 group-hover:opacity-40 transition-opacity duration-300"></div>';
        
        // Overlay Content (Name on image for level 0)
        $html .= '<div class="absolute bottom-0 left-0 right-0 p-3 sm:p-6 translate-y-2 group-hover:translate-y-0 transition-transform duration-300">';
        $html .= '<h3 class="text-base sm:text-2xl font-bold text-white font-display mb-1 shadow-sm line-clamp-2">';
        $html .= htmlspecialchars($category['name']);
        $html .= '</h3>';
        
        // Sub-categories count badge
        $subCount = count($category['children'] ?? []);
        if ($subCount > 0) {
            $html .= '<span class="inline-flex items-center text-xs font-medium text-white/90 bg-white/20 backdrop-blur-sm px-2 py-1 rounded-lg">';
            $html .= $subCount . ' Sub-categories';
            $html .= '</span>';
        }
        $html .= '</div>';
        
        $html .= '</div>'; // End image container
        
        // Content Body
        $html .= '<div class="p-3 sm:p-6 pt-3 sm:pt-4">';
        
        $html .= '<p class="text-gray-600 mb-3 sm:mb-6 leading-relaxed line-clamp-2 text-xs sm:text-sm">';
        $html .= htmlspecialchars($category['description']);
        $html .= '</p>';
        
        // CTA
        $html .= '<div class="flex items-center justify-between mt-auto">';
        $html .= '<a href="' . getBaseUrl('category.php?slug=' . $category['slug']) . '" class="text-folly font-bold text-xs sm:text-sm uppercase tracking-wider hover:text-folly-700 transition-colors flex items-center gap-1 sm:gap-2 group/link">';
        $html .= 'Explore Category';
        $html .= '<svg class="w-4 h-4 transform transition-transform duration-300 group-hover/link:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>';
        $html .= '</a>';
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
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">All Categories</span>
        </nav>
    </div>
</div>

<!-- Hero Section -->
<section class="relative bg-charcoal-900 py-20 md:py-28 overflow-hidden">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 z-0">
        <img src="<?php echo getAssetUrl('images/general/hero-bg.jpg'); ?>" alt="Categories" class="w-full h-full object-cover opacity-30" onerror="this.style.display='none'">
        <div class="absolute inset-0 bg-gradient-to-r from-charcoal-900 via-charcoal-900/90 to-charcoal-900/70"></div>
    </div>
    
    <!-- Decorative Elements -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-folly rounded-full mix-blend-overlay filter blur-3xl opacity-20 animate-pulse"></div>
    <div class="absolute bottom-0 left-0 w-72 h-72 bg-tangerine rounded-full mix-blend-overlay filter blur-3xl opacity-20"></div>
    
    <div class="relative z-10 container mx-auto px-4 text-center">
        <span class="inline-block py-1.5 px-4 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-xs font-semibold tracking-[0.2em] uppercase mb-6">
            Our Collections
        </span>
        <h1 class="text-3xl sm:text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-4 sm:mb-6 font-display tracking-tight">
            Explore All <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-folly-400 to-tangerine">Categories</span>
        </h1>
        <p class="text-base sm:text-lg md:text-xl text-gray-300 max-w-2xl mx-auto mb-6 sm:mb-10 leading-relaxed font-light">
            Discover our wide range of premium products organized into convenient collections. 
            Find exactly what you need for your lifestyle.
        </p>
        
        <!-- Quick Stats -->
        <div class="flex flex-wrap justify-center gap-4 md:gap-8">
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 px-6 py-3 rounded-2xl">
                <span class="block text-2xl font-bold text-white font-display"><?php echo count($categoryHierarchy); ?>+</span>
                <span class="text-xs text-gray-400 uppercase tracking-wider">Categories</span>
            </div>
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 px-6 py-3 rounded-2xl">
                <span class="block text-2xl font-bold text-white font-display">100%</span>
                <span class="text-xs text-gray-400 uppercase tracking-wider">Quality</span>
            </div>
            <div class="bg-white/5 backdrop-blur-sm border border-white/10 px-6 py-3 rounded-2xl">
                <span class="block text-2xl font-bold text-white font-display">24/7</span>
                <span class="text-xs text-gray-400 uppercase tracking-wider">Support</span>
            </div>
        </div>
    </div>
</section>


<!-- Categories Grid -->
<section class="bg-gradient-to-b from-gray-50 to-white py-16 md:py-24">
    <div class="container mx-auto px-4">
        <?php if (empty($categoryHierarchy)): ?>
            <div class="text-center py-20 glass-strong rounded-3xl shadow-sm max-w-2xl mx-auto">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-charcoal-900 mb-3 font-display">No Categories Found</h3>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">
                    We couldn't find any categories at the moment. Please check back later or browse our shop.
                </p>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center justify-center px-8 py-3 bg-gradient-to-r from-folly to-folly-500 hover:from-folly-600 hover:to-folly text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-folly/30">
                    Browse All Products
                </a>
            </div>
        <?php else: ?>
            <!-- Show hierarchical categories -->
            <?php echo renderCategoryHierarchy($categoryHierarchy); ?>
        <?php endif; ?>
    </div>
</section>

<!-- Call to Action -->
<section class="py-20 bg-gradient-to-b from-white to-gray-50">
    <div class="container mx-auto px-4">
        <div class="bg-gradient-to-r from-charcoal-900 to-charcoal-800 rounded-3xl p-8 md:p-16 text-center relative overflow-hidden shadow-2xl">
            <!-- Decorative circles -->
            <div class="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-folly/10 rounded-full translate-x-1/2 translate-y-1/2"></div>
            
            <div class="relative z-10 max-w-3xl mx-auto">
                <h2 class="text-3xl md:text-5xl font-bold text-white mb-6 font-display">
                    Can't Find What You're Looking For?
                </h2>
                <p class="text-gray-300 text-lg mb-10 leading-relaxed">
                    Our catalog is vast and constantly growing. Try our advanced search to find specific products or brands.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="px-8 py-4 glass text-charcoal-900 rounded-xl font-bold hover:bg-white/90 transition-all shadow-lg transform hover:-translate-y-1">
                        Browse All Products
                    </a>
                    <a href="<?php echo getBaseUrl('search.php'); ?>" class="px-8 py-4 bg-gradient-to-r from-folly to-folly-500 text-white rounded-xl font-bold hover:from-folly-600 hover:to-folly transition-all shadow-lg shadow-folly/30 transform hover:-translate-y-1">
                        Advanced Search
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
