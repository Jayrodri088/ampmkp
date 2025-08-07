<?php
$page_title = 'About Us';
$page_description = 'Learn about Angel Marketplace - your trusted source for quality Christian merchandise, apparel, and spiritual resources.';

require_once 'includes/functions.php';
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-3 md:py-4 mt-16 md:mt-20">
    <div class="container mx-auto px-4">
        <nav class="text-xs md:text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium">About Us</span>
        </nav>
    </div>
</div>

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-12 md:py-20 overflow-hidden">
    <!-- Background decorative elements -->
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
    <div class="absolute top-10 left-10 w-48 h-48 md:w-72 md:h-72 bg-folly-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    <div class="absolute top-20 right-10 w-48 h-48 md:w-72 md:h-72 bg-tangerine-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    
    <div class="relative container mx-auto px-4">
        <div class="max-w-5xl mx-auto text-center">
            <h1 class="text-2xl md:text-5xl lg:text-7xl font-bold text-gray-900 mb-6 md:mb-8 leading-tight px-4">
                Your Journey Starts at 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                    Angel Marketplace
                </span>
            </h1>
            <div class="w-16 md:w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6 md:mb-8"></div>
            <p class="text-base md:text-xl text-gray-600 mb-8 md:mb-12 leading-relaxed max-w-3xl mx-auto px-4">
                More than just a marketplace—we're your destination for products that 
                <span class="font-semibold text-folly">inspire, delight, and bring people together</span>.
            </p>
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

<!-- Mission Section -->
<section class="bg-gradient-to-br from-gray-50 to-charcoal-50 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Main Content -->
            <div class="bg-gradient-to-br from-white to-tangerine-50 rounded-3xl shadow-xl p-12 mb-16 border border-gray-200">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-8 text-center">
                    A Unique Experience at 
                    <span class="text-folly">Angel Marketplace</span>
                </h2>
                <div class="space-y-8 text-gray-700 leading-relaxed text-lg max-w-5xl mx-auto">
                    <div class="text-center">
                        <p class="text-2xl text-gray-800 font-semibold mb-6 text-folly">
                            Welcome to Angel Marketplace, your destination for unique and meaningful products!
                        </p>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-8 items-center">
                        <div class="space-y-6">
                            <p>
                                At Angel Marketplace, we believe in offering items that <strong>inspire, delight, and bring people together</strong>. Our collection includes stylish apparel, thoughtful gift items, one-of-a-kind souvenirs, and much more, designed to suit every occasion and personality.
                            </p>
                            <p>
                                We take pride in blending <strong>modern style with timeless messages</strong> of hope, love, and positivity. Our products are crafted to resonate with a wide audience — from young trendsetters to families, professionals, and anyone who values quality and inspiration.
                            </p>
                        </div>
                        <div class="bg-white rounded-2xl p-8 shadow-lg">
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Our Values</h3>
                            <ul class="space-y-3">
                                <li class="flex items-center gap-3">
                                    <div class="w-2 h-2 bg-folly rounded-full"></div>
                                    <span>Quality & Inspiration</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <div class="w-2 h-2 bg-charcoal-500 rounded-full"></div>
                                    <span>Meaningful Connections</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <div class="w-2 h-2 bg-tangerine rounded-full"></div>
                                    <span>Customer Satisfaction</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <div class="w-2 h-2 bg-folly-400 rounded-full"></div>
                                    <span>Thoughtful Expression</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 shadow-lg">
                        <p class="text-center text-lg leading-relaxed">
                            Angel Marketplace isn't just a store; it's a <strong>space where meaningful connections and thoughtful expressions come to life</strong>. Thank you for letting us be part of your journey in creating cherished memories and sharing joy with the ones you love.
                        </p>
                    </div>
                </div>
            </div>
            
            
            <!-- CTA Section -->
            <div class="text-center bg-gradient-to-r from-folly to-folly-600 rounded-3xl p-12 text-white">
                <h3 class="text-3xl font-bold mb-6">Ready to Start Your Journey?</h3>
                <p class="text-xl mb-8 opacity-90">Discover our complete collection of inspiring products</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-white hover:bg-gray-100 text-folly px-8 py-4 rounded-xl font-semibold text-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                        Explore Our Collection
                    </a>
                    <a href="<?php echo getBaseUrl('categories.php'); ?>" class="bg-transparent hover:bg-white/10 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-colors duration-200 border-2 border-white/30 hover:border-white/50">
                        Browse Categories
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>






<?php include 'includes/footer.php'; ?>