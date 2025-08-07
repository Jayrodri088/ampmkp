<?php
$page_title = 'Page Not Found';
$page_description = 'The page you are looking for could not be found.';

require_once 'includes/functions.php';

// Get some featured products for suggestions
$featuredProducts = getFeaturedProductsByRating(4);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-4 mt-24">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium">404 Error</span>
        </nav>
    </div>
</div>

<!-- 404 Error Page -->
<section class="bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-20">
    <div class="container mx-auto px-4 text-center">
        <div class="max-w-4xl mx-auto">
            <!-- Error Icon -->
            <div class="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-r from-gray-400 to-gray-600 rounded-full mb-8 shadow-xl">
                <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            
            <!-- Error Message -->
            <h1 class="text-8xl md:text-9xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800 mb-6">404</h1>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                Oops! Page 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                    Not Found
                </span>
            </h2>
            <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-8"></div>
            <p class="text-xl text-gray-600 mb-12 leading-relaxed max-w-2xl mx-auto">
                The page you're looking for seems to have wandered off into the digital wilderness. 
                Don't worry though - let's get you back on track!
            </p>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="<?php echo getBaseUrl(); ?>" class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Go to Homepage
                </a>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-white border-2 border-gray-300 hover:border-folly hover:bg-folly-50 text-gray-700 hover:text-folly-700 px-8 py-4 rounded-xl font-bold text-lg transition-all duration-200 transform hover:scale-105 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Browse Products
                </a>
                <button onclick="history.back()" class="bg-white border-2 border-gray-300 hover:border-tangerine hover:bg-tangerine-50 text-gray-700 hover:text-tangerine-700 px-8 py-4 rounded-xl font-bold text-lg transition-all duration-200 transform hover:scale-105 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Go Back
                </button>
            </div>
            
            <!-- Search Box -->
            <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-gray-200 max-w-lg mx-auto mb-12">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">Can't find what you need?</h3>
                <form action="<?php echo getBaseUrl('search.php'); ?>" method="GET">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="q" 
                            placeholder="Search our entire store..." 
                            class="w-full px-6 py-4 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-lg"
                            autofocus
                        >
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <button 
                            type="submit" 
                            class="absolute right-2 top-2 bottom-2 px-6 bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white rounded-lg font-bold transition-all duration-200 transform hover:scale-105 shadow-md hover:shadow-lg"
                        >
                            Search
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Popular Links -->
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Quick Navigation</h3>
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="<?php echo getBaseUrl(); ?>" class="bg-gradient-to-r from-folly-100 to-folly-200 text-folly-700 hover:from-folly-200 hover:to-folly-300 px-6 py-3 rounded-full font-semibold hover:shadow-md transition-all duration-200 transform hover:scale-105 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Home
                    </a>
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-gradient-to-r from-tangerine-100 to-tangerine-200 text-tangerine-700 hover:from-tangerine-200 hover:to-tangerine-300 px-6 py-3 rounded-full font-semibold hover:shadow-md transition-all duration-200 transform hover:scale-105 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        Shop
                    </a>
                    <a href="<?php echo getBaseUrl('category.php?slug=apparels'); ?>" class="bg-gradient-to-r from-purple-100 to-purple-200 text-purple-700 hover:from-purple-200 hover:to-purple-300 px-6 py-3 rounded-full font-semibold hover:shadow-md transition-all duration-200 transform hover:scale-105 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM7 3H5a2 2 0 00-2 2v12a4 4 0 004 4h2a2 2 0 002-2V5a2 2 0 00-2-2H7z"></path>
                        </svg>
                        Apparels
                    </a>
                    <a href="<?php echo getBaseUrl('category.php?slug=gift-items'); ?>" class="bg-gradient-to-r from-green-100 to-green-200 text-green-700 hover:from-green-200 hover:to-green-300 px-6 py-3 rounded-full font-semibold hover:shadow-md transition-all duration-200 transform hover:scale-105 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                        </svg>
                        Gift Items
                    </a>
                    <a href="<?php echo getBaseUrl('about.php'); ?>" class="bg-gradient-to-r from-blue-100 to-blue-200 text-blue-700 hover:from-blue-200 hover:to-blue-300 px-6 py-3 rounded-full font-semibold hover:shadow-md transition-all duration-200 transform hover:scale-105 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        About Us
                    </a>
                    <a href="<?php echo getBaseUrl('contact.php'); ?>" class="bg-gradient-to-r from-indigo-100 to-indigo-200 text-indigo-700 hover:from-indigo-200 hover:to-indigo-300 px-6 py-3 rounded-full font-semibold hover:shadow-md transition-all duration-200 transform hover:scale-105 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Contact
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<?php if (!empty($featuredProducts)): ?>
<section class="bg-gradient-to-br from-gray-50 via-white to-folly-50 py-20">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                While You're Here, 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                    Explore These
                </span>
            </h2>
            <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6"></div>
            <p class="text-xl text-gray-600">Check out some of our most popular products</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php 
            $selectedCurrency = getSelectedCurrency();
            foreach ($featuredProducts as $product): ?>
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200 hover:shadow-xl transition-all duration-200 overflow-hidden transform hover:scale-105">
                    <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                        <img 
                            src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="w-full h-48 object-cover"
                            onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                        >
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 mb-2 text-sm">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900">
                                <?php echo formatProductPrice($product, $selectedCurrency); ?>
                            </span>
                            <a 
                                href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" 
                                class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200 transform hover:scale-105 shadow-md hover:shadow-lg"
                            >
                                View
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Help Section -->
<section class="bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    Need 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                        Help?
                    </span>
                </h2>
                <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6"></div>
                <p class="text-xl text-gray-600">We're here to help you get back on track</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-gray-200 text-center group hover:shadow-2xl transition-all duration-200 transform hover:scale-105">
                    <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-r from-folly to-folly-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-200">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Call Us</h3>
                    <p class="text-gray-600 mb-4">
                        Speak directly with our customer service team
                    </p>
                    <a href="tel:+447918154909" class="text-folly hover:text-folly-600 font-bold text-lg transition-colors duration-200">
                        +44 791 815 4909
                    </a>
                </div>
                
                <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-gray-200 text-center group hover:shadow-2xl transition-all duration-200 transform hover:scale-105">
                    <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-r from-tangerine to-tangerine-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-200">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Email Us</h3>
                    <p class="text-gray-600 mb-4">
                        Send us an email and we'll respond within 24 hours
                    </p>
                    <a href="mailto:sales@angelmarketplace.org" class="text-tangerine hover:text-tangerine-600 font-bold text-lg transition-colors duration-200">
                        sales@angelmarketplace.org
                    </a>
                </div>
                
                <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-gray-200 text-center group hover:shadow-2xl transition-all duration-200 transform hover:scale-105">
                    <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-r from-charcoal-600 to-charcoal-800 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-200">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Contact Form</h3>
                    <p class="text-gray-600 mb-4">
                        Fill out our contact form with your questions
                    </p>
                    <a href="<?php echo getBaseUrl('contact.php'); ?>" class="text-charcoal-600 hover:text-charcoal-800 font-bold text-lg transition-colors duration-200">
                        Contact Form
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>