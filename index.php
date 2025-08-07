<?php
$page_title = 'Home';
$page_description = 'Angel Marketplace - Premium Christian merchandise, apparel, gifts and spiritual resources. Shop now for faith-based products.';

require_once 'includes/functions.php';

// Debug paths if requested
debugPaths();

// Selected currency for homepage pricing
$selectedCurrency = getSelectedCurrency();

// Get featured products sorted by rating count and categories
$featuredProducts = getFeaturedProductsByRating(8);
$categories = getCategories();
$trendingProducts = getProducts(null, null, 6);

// Get product counts for each category
$categoryProductCounts = getCategoryProductCounts();

// Filter to show only root categories (parent_id = 0) for homepage
$rootCategories = array_filter($categories, function($category) {
    return ($category['parent_id'] ?? 0) == 0;
});

// Sort root categories by product count including subcategories (highest to lowest)
usort($rootCategories, function($a, $b) use ($categoryProductCounts) {
    // Get total count including subcategories
    $countA = getTotalProductCountForCategory($a['id']);
    $countB = getTotalProductCountForCategory($b['id']);
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
<!-- Mobile Hero Section -->
<section class="block md:hidden relative bg-gradient-to-b from-folly-50 via-white to-tangerine-50 py-8 overflow-hidden min-h-screen flex items-center">
    <!-- Mobile Background Elements -->
    <div class="absolute inset-0 bg-mobile-pattern opacity-10"></div>
    <div class="absolute top-20 right-4 w-32 h-32 bg-folly-100 rounded-full opacity-30"></div>
    <div class="absolute bottom-32 left-4 w-24 h-24 bg-tangerine-100 rounded-full opacity-40"></div>
    
    <div class="relative container mx-auto px-6 text-center">
        <!-- Mobile Main Title -->
        <h1 class="text-3xl font-black text-gray-900 mb-3 leading-tight">
            Your One-Stop
            <br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly to-tangerine-600">
                Shopping Hub
            </span>
        </h1>
        
        <!-- Mobile Tagline -->
        <p class="text-gray-600 text-base mb-6 leading-relaxed max-w-sm mx-auto">
            From everyday essentials to <span class="font-semibold text-folly">faith-filled fashion</span> — discover purposeful products.
        </p>
        
        <!-- Mobile Value Props -->
        <div class="grid grid-cols-3 gap-3 mb-8 max-w-xs mx-auto">
            <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 shadow-sm">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <p class="text-xs font-medium text-gray-700">Secure</p>
            </div>
            <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 shadow-sm">
                <div class="w-8 h-8 bg-folly-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <svg class="w-4 h-4 text-folly" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                    </svg>
                </div>
                <p class="text-xs font-medium text-gray-700">Fast</p>
            </div>
            <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 shadow-sm">
                <div class="w-8 h-8 bg-tangerine-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                    <svg class="w-4 h-4 text-tangerine" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <p class="text-xs font-medium text-gray-700">Loved</p>
            </div>
        </div>
        
        <!-- Mobile Action Buttons -->
        <div class="space-y-3 mb-8">
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="block w-full bg-gradient-to-r from-folly to-folly-600 text-white py-4 rounded-2xl font-bold text-lg shadow-lg hover:shadow-xl transition-all duration-300 touch-manipulation">
                Start Shopping
            </a>
            <a href="<?php echo getBaseUrl('about.php'); ?>" class="block w-full bg-white/90 backdrop-blur-sm text-gray-800 py-3 rounded-2xl font-semibold border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 touch-manipulation">
                Learn More
            </a>
        </div>
        
        <!-- Mobile Bottom Message -->
        <div class="bg-gradient-to-r from-charcoal-800 to-charcoal-700 text-white rounded-2xl p-4 mx-4">
            <p class="text-sm font-bold mb-1">Excellence. Identity. Purpose</p>
            <p class="text-xs opacity-90">All in one place</p>
        </div>
    </div>
</section>

<!-- Desktop Hero Section -->
<section class="hidden md:block relative bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-12 md:py-20 lg:py-32 overflow-hidden">
    <!-- Background decorative elements -->
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
    <div class="absolute top-10 left-10 w-72 h-72 bg-folly-200 rounded-full mix-blend-multiply filter blur-xl opacity-20 md:animate-pulse"></div>
    <div class="absolute top-20 right-10 w-72 h-72 bg-tangerine-200 rounded-full mix-blend-multiply filter blur-xl opacity-20 md:animate-pulse animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-charcoal-200 rounded-full mix-blend-multiply filter blur-xl opacity-20 md:animate-pulse animation-delay-4000"></div>
    
    <div class="relative container mx-auto px-4">
        <div class="text-center max-w-5xl mx-auto">
            
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800 mb-8 leading-tight animate-fade-in-up">
                Angel Marketplace
            </h1>
            
            <div class="relative mb-6 md:mb-8">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4 md:mb-6 animate-fade-in-up animation-delay-200">
                    Welcome to 
                    <span class="relative">
                        Angel Marketplace
                        <svg class="absolute -bottom-2 left-0 w-full h-3 text-yellow-300" viewBox="0 0 100 12" fill="currentColor">
                            <path d="M0 8c30-6 70-6 100 0v4H0z" opacity="0.6"/>
                        </svg>
                    </span>
                </h2>
                <p class="text-lg text-gray-700 mt-4 animate-fade-in-up animation-delay-300">
                    — a world of limitless possibilities.
                </p>
            </div>
            
            <p class="text-xl lg:text-2xl text-gray-600 mb-8 md:mb-12 leading-relaxed max-w-4xl mx-auto animate-fade-in-up animation-delay-400 px-4">
                From everyday essentials to <span class="font-semibold text-folly">faith-filled fashion</span> and much more, we're your one-stop hub.<br>
                Discover purposeful products with every search.<br>
                <span class="font-bold text-charcoal-800">Excellence. Identity. Purpose</span> — all in one place.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center animate-fade-in-up animation-delay-600 px-4">
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="group relative bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-8 md:px-10 py-3 md:py-4 rounded-xl font-semibold text-base md:text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl touch-manipulation">
                    <span class="relative z-10">Shop Now</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-folly-600 to-folly-700 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="absolute inset-0 rounded-xl ring-2 ring-folly-300 ring-opacity-0 group-hover:ring-opacity-50 transition-all duration-300"></div>
                </a>
                <a href="<?php echo getBaseUrl('about.php'); ?>" class="group relative bg-white hover:bg-gray-50 text-gray-800 px-8 md:px-10 py-3 md:py-4 rounded-xl font-semibold text-base md:text-lg transition-all duration-300 transform hover:scale-105 border-2 border-gray-200 hover:border-gray-300 hover:shadow-xl touch-manipulation">
                    <span class="relative z-10">Learn More</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>
            </div>
            
            <!-- Trust indicators -->
            <div class="mt-8 md:mt-16 flex flex-wrap justify-center items-center gap-4 md:gap-8 text-xs md:text-sm text-gray-500 animate-fade-in-up animation-delay-800 px-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Secure Shopping</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-folly" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                    </svg>
                    <span>Fast Delivery</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-tangerine" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Customer Love</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Advertisement Section -->
<?php if (!empty($activeAds)): ?>
<section class="bg-white py-4 md:py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <?php if (count($activeAds) === 1): ?>
                <!-- Single Ad Display -->
                <?php $ad = $activeAds[0]; ?>
                <?php $destinationUrl = getAdDestinationUrl($ad); ?>
                <a href="<?php echo $destinationUrl; ?>" class="group block" <?php echo (strpos($destinationUrl, 'http') === 0 && strpos($destinationUrl, getBaseUrl()) !== 0) ? 'target="_blank" rel="noopener"' : ''; ?>>
                    <div class="relative rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                        <img 
                            src="<?php echo getAssetUrl('images/ads/' . $ad['image']); ?>" 
                            alt="<?php echo htmlspecialchars($ad['title']); ?>"
                            class="w-full h-auto object-contain transition-transform duration-300 group-hover:scale-102"
                            onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                        >
                    </div>
                </a>
            <?php else: ?>
                <!-- Multiple Ads Carousel -->
                <div class="relative">
                    <div class="overflow-hidden rounded-2xl">
                        <div id="adCarousel" class="flex transition-transform duration-500 ease-in-out">
                            <?php foreach ($activeAds as $index => $ad): ?>
                                <?php $destinationUrl = getAdDestinationUrl($ad); ?>
                                <div class="w-full flex-shrink-0">
                                    <a href="<?php echo $destinationUrl; ?>" class="group block" <?php echo (strpos($destinationUrl, 'http') === 0 && strpos($destinationUrl, getBaseUrl()) !== 0) ? 'target="_blank" rel="noopener"' : ''; ?>>
                                        <div class="relative overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300">
                                            <img 
                                                src="<?php echo getAssetUrl('images/ads/' . $ad['image']); ?>" 
                                                alt="<?php echo htmlspecialchars($ad['title']); ?>"
                                                class="w-full h-auto object-contain transition-transform duration-300 group-hover:scale-102"
                                                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                            >
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Carousel Controls -->
                    <?php if (count($activeAds) > 1): ?>
                        <button onclick="previousAd()" class="absolute left-2 md:left-4 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 md:p-3 shadow-lg transition-all duration-200 hover:scale-110 touch-manipulation">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button onclick="nextAd()" class="absolute right-2 md:right-4 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full p-2 md:p-3 shadow-lg transition-all duration-200 hover:scale-110 touch-manipulation">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        
                        <!-- Carousel Indicators -->
                        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
                            <?php for ($i = 0; $i < count($activeAds); $i++): ?>
                                <button onclick="goToAd(<?php echo $i; ?>)" class="w-3 h-3 rounded-full bg-white/60 hover:bg-white transition-colors duration-200 ad-indicator <?php echo $i === 0 ? 'active' : ''; ?>"></button>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
.bg-grid-pattern {
    background-image: radial-gradient(circle at 1px 1px, rgba(255, 0, 85, 0.15) 1px, transparent 0);
    background-size: 20px 20px;
}

.bg-mobile-pattern {
    background-image: 
        linear-gradient(45deg, rgba(255, 0, 85, 0.05) 25%, transparent 25%),
        linear-gradient(-45deg, rgba(255, 0, 85, 0.05) 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, rgba(255, 0, 85, 0.05) 75%),
        linear-gradient(-45deg, transparent 75%, rgba(255, 0, 85, 0.05) 75%);
    background-size: 30px 30px;
    background-position: 0 0, 0 15px, 15px -15px, -15px 0px;
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

.animate-fade-in-down {
    animation: fade-in-down 0.8s ease-out forwards;
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out forwards;
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

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

.ad-indicator.active {
    background-color: white !important;
}
</style>

<script>
let currentAdIndex = 0;
const totalAds = <?php echo count($activeAds); ?>;

function nextAd() {
    currentAdIndex = (currentAdIndex + 1) % totalAds;
    updateCarousel();
}

function previousAd() {
    currentAdIndex = (currentAdIndex - 1 + totalAds) % totalAds;
    updateCarousel();
}

function goToAd(index) {
    currentAdIndex = index;
    updateCarousel();
}

function updateCarousel() {
    const carousel = document.getElementById('adCarousel');
    const indicators = document.querySelectorAll('.ad-indicator');
    
    if (carousel) {
        carousel.style.transform = `translateX(-${currentAdIndex * 100}%)`;
    }
    
    // Update indicators
    indicators.forEach((indicator, index) => {
        if (index === currentAdIndex) {
            indicator.classList.add('active');
        } else {
            indicator.classList.remove('active');
        }
    });
}

// Auto-rotate ads every 5 seconds if there are multiple ads
<?php if (count($activeAds) > 1): ?>
setInterval(() => {
    nextAd();
}, 5000);
<?php endif; ?>
</script>

<!-- Categories Section -->
<section class="bg-gradient-to-br from-gray-50 to-charcoal-50 py-12 md:py-20">
    <div class="container mx-auto px-4">
        <div class="text-center mb-8 md:mb-16">
            <h2 class="text-2xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">Shop by Category</h2>
            <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full"></div>
            <p class="text-gray-600 mt-4 md:mt-6 text-base md:text-lg max-w-2xl mx-auto px-4">Discover our carefully curated collections designed to inspire and delight</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-8">
            <?php 
            $featuredCategories = getFeaturedCategories();
            foreach ($featuredCategories as $category): 
            ?>
                <div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden transform hover:-translate-y-2 border border-gray-100">
                    <!-- Featured Badge -->
                    <div class="absolute top-4 left-4 z-20">
                        <div class="flex items-center gap-1 bg-gradient-to-r from-yellow-400 to-yellow-500 text-gray-900 px-3 py-1.5 rounded-full text-xs font-bold shadow-lg">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <span>Featured</span>
                        </div>
                    </div>
                    
                    <!-- Background gradient overlay -->
                    <div class="absolute inset-0 bg-gradient-to-br from-folly/5 to-tangerine/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <!-- Image container with overlay -->
                    <div class="relative overflow-hidden rounded-t-2xl">
                        <div class="aspect-w-16 aspect-h-10 bg-gradient-to-br from-gray-100 to-gray-200">
                            <img 
                                src="<?php echo getAssetUrl('images/' . $category['image']); ?>" 
                                alt="<?php echo htmlspecialchars($category['name']); ?>"
                                class="w-full h-40 md:h-56 object-cover transition-transform duration-500 group-hover:scale-110"
                                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                            >
                        </div>
                        <!-- Gradient overlay on image -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    
                    <!-- Content -->
                    <div class="relative p-4 md:p-6">
                        <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-2 md:mb-3 group-hover:text-folly transition-colors duration-300">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </h3>
                        <p class="text-gray-600 mb-4 md:mb-6 leading-relaxed line-clamp-2 text-sm md:text-base">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </p>
                        
                        <!-- CTA Button -->
                        <div class="flex items-center justify-between">
                            <a 
                                href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" 
                                class="group/btn relative inline-flex items-center gap-2 bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-4 md:px-6 py-2 md:py-3 rounded-xl font-semibold text-sm md:text-base transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl touch-manipulation"
                            >
                                <span>Explore</span>
                                <svg class="w-4 h-4 transition-transform duration-300 group-hover/btn:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                                <!-- Button shine effect -->
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 -translate-x-full group-hover/btn:translate-x-full transition-transform duration-700"></div>
                            </a>
                            
                            <!-- Item count indicator -->
                            <div class="text-sm text-gray-500 bg-gray-100 px-3 py-2 rounded-lg">
                                <?php 
                                $itemCount = getTotalProductCountForCategory($category['id']);
                                $itemText = $itemCount === 1 ? 'item' : 'items';
                                ?>
                                <span class="font-medium"><?php echo $itemCount; ?> <?php echo $itemText; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- View all categories button -->
        <div class="text-center mt-12">
            <a href="<?php echo getBaseUrl('categories.php'); ?>" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-800 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-200 hover:border-gray-300">
                <span>View All Categories</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="bg-white py-12 md:py-20">
    <div class="container mx-auto px-4">
        <div class="text-center mb-8 md:mb-16">
            <h2 class="text-2xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">What's Trending Now</h2>
            <div class="w-24 h-1 bg-gradient-to-r from-folly to-folly-600 mx-auto rounded-full mb-6"></div>
            <p class="text-gray-600 max-w-3xl mx-auto text-base md:text-lg leading-relaxed px-4">
                Discover the most popular products that everyone's talking about. 
                <span class="font-semibold text-folly">Hand-picked</span> for their quality and customer satisfaction.
            </p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-8">
            <?php foreach ($featuredProducts as $index => $product): ?>
                <div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden border border-gray-100">
                    <!-- Trending Badge -->
                    <div class="absolute top-4 left-4 z-20">
                        <div class="flex items-center gap-1 bg-gradient-to-r from-folly to-folly-600 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <span>Trending</span>
                        </div>
                    </div>
                    
                    <!-- Rank Badge -->
                    <div class="absolute top-4 right-4 z-20">
                        <div class="w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center text-sm font-bold text-gray-700 shadow-lg">
                            #<?php echo $index + 1; ?>
                        </div>
                    </div>
                    
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
                                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                            >
                        </div>
                        <!-- Image overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
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
                        <h3 class="font-bold text-gray-900 mb-2 text-base md:text-lg group-hover:text-folly transition-colors duration-300 line-clamp-1">
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
                        
                        <!-- Price and Action -->
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-lg md:text-2xl font-bold text-gray-900">
                                    <?php echo formatProductPrice($product, $selectedCurrency); ?>
                                </span>
                                <?php 
                                $productPrice = getProductPrice($product, $selectedCurrency);
                                $currencySettings = $settings['shipping']['costs'][$selectedCurrency] ?? [];
                                $shippingCost = $currencySettings['standard'] ?? ($settings['shipping']['standard_shipping_cost'] ?? 0);
                                $freeShippingThreshold = $currencySettings['free_threshold'] ?? ($settings['shipping']['free_shipping_threshold'] ?? 0);

                                if ($freeShippingThreshold > 0 && $productPrice >= $freeShippingThreshold) {
                                    echo '<span class="text-xs text-green-600 font-medium">Free shipping</span>';
                                } elseif ($shippingCost > 0) {
                                    echo '<span class="text-xs text-orange-600 font-medium">+ ' . formatPriceWithCurrency($shippingCost, $selectedCurrency) . ' shipping</span>';
                                } else {
                                    echo '<span class="text-xs text-green-600 font-medium">Free shipping</span>';
                                }
                                ?>
                            </div>
                            <div class="flex flex-col gap-2">
                                <a 
                                    href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" 
                                    class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-3 md:px-4 py-2 rounded-xl text-xs md:text-sm font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl text-center touch-manipulation"
                                >
                                    View Product
                                </a>
                                <button 
                                    onclick="addToCart(<?php echo $product['id']; ?>)"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-3 md:px-4 py-2 rounded-xl text-xs font-medium transition-colors duration-200 flex items-center justify-center gap-1 touch-manipulation"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m-.4-5L2 1m5 12v2a2 2 0 002 2h10a2 2 0 002-2v-2m-6 4h.01M9 19h.01"></path>
                                    </svg>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8 md:mt-16">
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="group relative inline-flex items-center gap-3 bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-6 md:px-10 py-3 md:py-4 rounded-xl font-semibold text-base md:text-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-2xl touch-manipulation">
                <span>Explore All Products</span>
                <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
                <!-- Button shine effect -->
                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
            </a>
        </div>
    </div>
</section>


<!-- Call to Action -->
<section class="bg-gradient-to-r from-folly to-folly-600 py-12 md:py-20">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-4xl lg:text-5xl font-bold text-white mb-4 md:mb-6 px-4">Ready to Find Your Perfect Items?</h2>
        <p class="text-white/90 text-lg md:text-xl max-w-3xl mx-auto mb-6 md:mb-8 leading-relaxed px-4">
            Explore our curated collection of quality products designed to inspire and uplift.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 md:gap-6 justify-center px-4">
                            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-white hover:bg-gray-100 text-folly px-6 md:px-8 py-3 md:py-4 rounded-xl font-semibold text-base md:text-lg transition-colors duration-200 shadow-lg hover:shadow-xl touch-manipulation">
                Shop Now
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>