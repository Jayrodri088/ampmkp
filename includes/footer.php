    </main>
    
    <!-- Footer -->
    </div> <!-- Close .flex-grow -->
    <?php
    // Load settings for footer if not already loaded
    if (!isset($settings)) {
        $settings = getSettings();
    }
    ?>
    <footer class="bg-gradient-to-br from-charcoal-800 via-charcoal-900 to-charcoal text-white mt-auto">
        <div class="container mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About Section -->
                <div>
                    <div class="flex items-center mb-6">
                        <img 
                            src="<?php echo getAssetUrl('images/general/logo.png'); ?>" 
                            alt="Angel Marketplace Logo" 
                            class="h-10 w-auto mr-3"
                        >
                        <h3 class="text-xl font-bold text-tangerine-300"><?php echo htmlspecialchars($settings['site_name']); ?></h3>
                    </div>
                    <p class="text-gray-300 text-sm leading-relaxed mb-4">
                        <?php echo htmlspecialchars($settings['site_description']); ?>
                    </p>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-tangerine-300">Quick Links</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo getBaseUrl(); ?>" class="text-gray-300 hover:text-tangerine-300 transition-colors duration-200 flex items-center group">
                            <span class="w-2 h-2 bg-folly rounded-full mr-3 group-hover:bg-tangerine-300 transition-colors duration-200"></span>
                            Home
                        </a></li>
                        <li><a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-gray-300 hover:text-tangerine-300 transition-colors duration-200 flex items-center group">
                            <span class="w-2 h-2 bg-folly rounded-full mr-3 group-hover:bg-tangerine-300 transition-colors duration-200"></span>
                            Shop
                        </a></li>
                        <li><a href="<?php echo getBaseUrl('categories.php'); ?>" class="text-gray-300 hover:text-tangerine-300 transition-colors duration-200 flex items-center group">
                            <span class="w-2 h-2 bg-folly rounded-full mr-3 group-hover:bg-tangerine-300 transition-colors duration-200"></span>
                            All Categories
                        </a></li>
                        <li><a href="<?php echo getBaseUrl('about.php'); ?>" class="text-gray-300 hover:text-tangerine-300 transition-colors duration-200 flex items-center group">
                            <span class="w-2 h-2 bg-folly rounded-full mr-3 group-hover:bg-tangerine-300 transition-colors duration-200"></span>
                            About Us
                        </a></li>
                        <li><a href="<?php echo getBaseUrl('contact.php'); ?>" class="text-gray-300 hover:text-tangerine-300 transition-colors duration-200 flex items-center group">
                            <span class="w-2 h-2 bg-folly rounded-full mr-3 group-hover:bg-tangerine-300 transition-colors duration-200"></span>
                            Contact Us
                        </a></li>
                    </ul>
                </div>
                
                <!-- Categories -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-tangerine-300">Categories</h4>
                    <ul class="space-y-3 text-sm">
                        <?php 
                        $footerCategories = getCategories();
                        $displayCategories = array_slice($footerCategories, 0, 6);
                        foreach ($displayCategories as $category): 
                        ?>
                            <li><a href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" class="text-gray-300 hover:text-tangerine-300 transition-colors duration-200 flex items-center group">
                                <span class="w-2 h-2 bg-folly rounded-full mr-3 group-hover:bg-tangerine-300 transition-colors duration-200"></span>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-tangerine-300">Contact Info</h4>
                    <div class="space-y-4 text-sm text-gray-300">
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-folly/20 rounded-xl flex items-center justify-center mr-3 mt-0.5">
                                <svg class="w-5 h-5 text-folly-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-white">Phone</div>
                                <div class="space-y-2">
                                    <div class="font-medium text-gray-200">United Kingdom</div>
                                    <div>+441708 556604</div>
                                    <div>+44800 1310604 (UK Freephone)</div>
                                    
                                    <div class="font-medium text-gray-200 mt-3">United States</div>
                                    <div>+14696561284</div>
                                    <div>+18006208522</div>
                                    
                                    <div class="font-medium text-gray-200 mt-3">Nigeria</div>
                                    <div>+2348036495283</div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-10 h-10 bg-folly/20 rounded-xl flex items-center justify-center mr-3 mt-0.5">
                                <svg class="w-5 h-5 text-folly-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-white">Email</div>
                                <span><?php echo htmlspecialchars($settings['site_email']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Newsletter Section -->
            <div class="mt-12 pt-8 border-t border-gray-700">
                <div class="max-w-2xl mx-auto text-center">
                    <h4 class="text-xl font-bold mb-4 text-tangerine-300">Stay Updated</h4>
                    <p class="text-gray-300 mb-6">Subscribe to our newsletter for the latest products and exclusive offers.</p>
                    <form id="newsletter-form" class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto" onsubmit="return subscribeNewsletter(event)">
                        <input 
                            type="email" 
                            id="newsletter-email"
                            name="email"
                            placeholder="Enter your email" 
                            required
                            class="flex-1 px-4 py-3 bg-gray-800 border border-gray-600 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200"
                        >
                        <button 
                            type="submit" 
                            id="newsletter-submit"
                            class="bg-folly hover:bg-folly-500 text-white px-6 py-3 rounded-xl font-semibold transition-colors duration-200 whitespace-nowrap flex items-center justify-center gap-2"
                        >
                            <span>Subscribe</span>
                            <span class="loading-spinner hidden">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-center items-center">
                <div class="text-sm text-gray-300">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?>. All Rights Reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    
    <!-- Newsletter Success Popup -->
    <div id="newsletter-popup" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="newsletter-popup-content">
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Thank You!</h3>
                <p class="text-gray-600 mb-6">You've successfully subscribed to our newsletter. Stay tuned for amazing deals and updates!</p>
                <button onclick="closeNewsletterPopup()" class="bg-folly hover:bg-folly-600 text-white px-8 py-3 rounded-xl font-semibold transition-colors duration-200">
                    Got it!
                </button>
            </div>
        </div>
    </div>

    <!-- Custom JavaScript -->
    <script src="<?php echo getAssetUrl('js/main.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('js/cart.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('js/newsletter.js'); ?>"></script>
</body>
</html>
