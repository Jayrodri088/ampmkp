<?php
$page_title = 'About Us';
$page_description = 'Learn about Angel Marketplace - your trusted source for quality Christian merchandise, apparel, and spiritual resources.';

require_once 'includes/functions.php';
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">About Us</span>
        </nav>
    </div>
</div>

<!-- Hero Section -->
<section class="relative bg-white py-20 md:py-32 overflow-hidden">
    <!-- Decorative Background -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-folly-50 to-transparent opacity-50"></div>
        <div class="absolute bottom-0 left-0 w-1/3 h-full bg-gradient-to-r from-tangerine-50 to-transparent opacity-50"></div>
        <div class="absolute top-20 left-20 w-64 h-64 bg-folly-100 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div class="absolute top-20 right-20 w-64 h-64 bg-tangerine-100 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
    </div>
    
    <div class="relative container mx-auto px-4 text-center">
        <span class="inline-block py-1 px-3 rounded-full bg-folly/10 text-folly font-bold text-sm mb-6 tracking-wide uppercase">Our Story</span>
        <h1 class="text-3xl sm:text-5xl md:text-7xl font-bold text-charcoal-900 mb-6 sm:mb-8 leading-tight font-display">
            Your Journey Starts at <br class="hidden sm:block"/>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly to-tangerine">Angel Marketplace</span>
        </h1>
        <p class="text-xl text-gray-600 mb-10 max-w-3xl mx-auto leading-relaxed">
            More than just a marketplace—we're your destination for products that 
            <span class="font-bold text-charcoal-900">inspire, delight, and bring people together</span>.
        </p>
    </div>
</section>

<!-- Mission Section -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-gray-100">
                <div class="grid md:grid-cols-2">
                    <div class="p-6 sm:p-12 md:p-16 flex flex-col justify-center">
                        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-charcoal-900 mb-4 sm:mb-6">
                            A Unique Experience
                        </h2>
                        <div class="space-y-4 sm:space-y-6 text-gray-600 text-base sm:text-lg leading-relaxed">
                            <p>
                                Welcome to Angel Marketplace, your destination for unique and meaningful products!
                            </p>
                            <p>
                                At Angel Marketplace, we believe in offering items that <strong>inspire, delight, and bring people together</strong>. Our collection includes stylish apparel, thoughtful gift items, one-of-a-kind souvenirs, and much more, designed to suit every occasion and personality.
                            </p>
                            <p>
                                We take pride in blending <strong>modern style with timeless messages</strong> of hope, love, and positivity. Our products are crafted to resonate with a wide audience — from young trendsetters to families, professionals, and anyone who values quality and inspiration.
                            </p>
                        </div>
                    </div>
                    <div class="bg-charcoal-900 p-6 sm:p-12 md:p-16 text-white flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-folly/20 to-tangerine/20"></div>
                        <div class="relative z-10">
                            <h3 class="text-xl sm:text-2xl font-bold mb-6 sm:mb-8 flex items-center gap-3">
                                <span class="w-8 h-1 bg-folly rounded-full"></span>
                                Our Core Values
                            </h3>
                            <ul class="space-y-4 sm:space-y-6">
                                <li class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0 text-folly">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-lg mb-1">Quality & Inspiration</h4>
                                        <p class="text-gray-400 text-sm">Curated products that uplift and inspire.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0 text-tangerine">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-lg mb-1">Meaningful Connections</h4>
                                        <p class="text-gray-400 text-sm">Building a community through shared values.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0 text-blue-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-lg mb-1">Customer Satisfaction</h4>
                                        <p class="text-gray-400 text-sm">Your happiness is our top priority.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0 text-purple-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-lg mb-1">Thoughtful Expression</h4>
                                        <p class="text-gray-400 text-sm">Products that speak from the heart.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-16 text-center max-w-3xl mx-auto">
                <p class="text-xl text-gray-600 leading-relaxed mb-10">
                    Angel Marketplace isn't just a store; it's a <span class="text-charcoal-900 font-bold">space where meaningful connections and thoughtful expressions come to life</span>. Thank you for letting us be part of your journey in creating cherished memories and sharing joy with the ones you love.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-folly hover:bg-folly-600 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Explore Our Collection
                    </a>
                    <a href="<?php echo getBaseUrl('contact.php'); ?>" class="bg-white text-charcoal-900 border-2 border-gray-200 hover:border-charcoal-900 px-8 py-4 rounded-xl font-bold text-lg transition-all duration-300">
                        Get in Touch
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>