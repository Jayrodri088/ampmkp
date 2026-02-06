    </main>
    
    <!-- Footer -->
    </div> <!-- Close .flex-grow -->
    <?php
    // Load settings for footer if not already loaded
    if (!isset($settings)) {
        $settings = getSettings();
    }
    ?>
    <footer class="relative bg-charcoal-900 text-white pt-20 pb-8 mt-auto overflow-hidden">
        <!-- Subtle gradient orbs -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-folly/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-tangerine/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="container mx-auto px-4 relative z-10">

            <!-- Top Footer: Newsletter -->
            <div class="border-b border-white/10 pb-14 mb-14">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-10">
                    <div class="lg:w-1/2">
                        <p class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-3">Newsletter</p>
                        <h3 class="text-3xl font-bold mb-2 font-display tracking-tight">Stay in the loop</h3>
                        <p class="text-gray-400 text-sm">Subscribe for exclusive offers and the latest arrivals.</p>
                    </div>
                    <div class="lg:w-1/2 w-full">
                        <form id="newsletter-form" class="flex flex-col sm:flex-row gap-3" onsubmit="return subscribeNewsletter(event)">
                            <input
                                type="email"
                                id="newsletter-email"
                                name="email"
                                placeholder="Enter your email address"
                                required
                                class="flex-grow px-5 py-3.5 bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-folly/50 focus:border-folly/30 focus:bg-white/10 transition-all duration-300"
                            >
                            <button
                                type="submit"
                                id="newsletter-submit"
                                class="px-8 py-3.5 bg-gradient-to-r from-folly to-folly-500 hover:from-folly-500 hover:to-folly text-white font-semibold rounded-2xl transition-all duration-300 shadow-lg hover:shadow-folly/25 flex items-center justify-center whitespace-nowrap"
                            >
                                <span>Subscribe</span>
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                <span class="loading-spinner hidden ml-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-14">
                <!-- Brand Info -->
                <div class="space-y-6">
                    <a href="<?php echo getBaseUrl(); ?>" class="inline-block">
                        <div class="flex items-center gap-2">
                            <img src="<?php echo getAssetUrl('images/general/logo.png'); ?>" alt="Logo" class="h-8 w-auto">
                            <span class="font-display text-xl font-bold tracking-tight"><?php echo htmlspecialchars($settings['site_name']); ?></span>
                        </div>
                    </a>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        <?php echo htmlspecialchars($settings['site_description']); ?>
                    </p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-10 h-10 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10 flex items-center justify-center text-gray-400 hover:bg-folly hover:border-folly hover:text-white transition-all duration-300">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10 flex items-center justify-center text-gray-400 hover:bg-folly hover:border-folly hover:text-white transition-all duration-300">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10 flex items-center justify-center text-gray-400 hover:bg-folly hover:border-folly hover:text-white transition-all duration-300">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-xs font-semibold tracking-[0.2em] uppercase text-gray-400 mb-6">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="<?php echo getBaseUrl(); ?>" class="text-gray-300 hover:text-white transition-colors text-sm">Home</a></li>
                        <li><a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-gray-300 hover:text-white transition-colors text-sm">Shop</a></li>
                        <li><a href="<?php echo getBaseUrl('categories.php'); ?>" class="text-gray-300 hover:text-white transition-colors text-sm">All Categories</a></li>
                        <li><a href="<?php echo getBaseUrl('about.php'); ?>" class="text-gray-300 hover:text-white transition-colors text-sm">About Us</a></li>
                        <li><a href="<?php echo getBaseUrl('contact.php'); ?>" class="text-gray-300 hover:text-white transition-colors text-sm">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h4 class="text-xs font-semibold tracking-[0.2em] uppercase text-gray-400 mb-6">Popular Categories</h4>
                    <ul class="space-y-3">
                        <?php
                        $footerCategories = getCategories();
                        $displayCategories = array_slice($footerCategories, 0, 5);
                        foreach ($displayCategories as $category):
                        ?>
                            <li><a href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" class="text-gray-300 hover:text-white transition-colors text-sm">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-xs font-semibold tracking-[0.2em] uppercase text-gray-400 mb-6">Contact Us</h4>
                    <div class="space-y-5">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <div class="text-sm">
                                <p class="text-white font-medium mb-1">Phone Support</p>
                                <p class="text-gray-400">UK: +441708 556604</p>
                                <p class="text-gray-400">US: +14696561284</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white/5 backdrop-blur-sm border border-white/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="text-sm">
                                <p class="text-white font-medium mb-1">Email Us</p>
                                <p class="text-gray-400"><?php echo htmlspecialchars($settings['site_email']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-500 text-sm text-center md:text-left">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?>. All Rights Reserved.
                </p>

                <!-- Payment Icons -->
                <div class="flex items-center gap-2">
                    <div class="h-8 w-12 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center p-1.5 border border-white/5">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/2560px-Visa_Inc._logo.svg.png" alt="Visa" class="h-full w-auto object-contain brightness-0 invert opacity-60">
                    </div>
                    <div class="h-8 w-12 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center p-1.5 border border-white/5">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png" alt="Mastercard" class="h-full w-auto object-contain brightness-0 invert opacity-60">
                    </div>
                    <div class="h-8 w-12 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center p-1.5 border border-white/5">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/30/American_Express_logo.svg/1200px-American_Express_logo.svg.png" alt="Amex" class="h-full w-auto object-contain brightness-0 invert opacity-60">
                    </div>
                    <div class="h-8 w-12 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center p-1.5 border border-white/5">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/ba/Stripe_Logo%2C_revised_2016.svg/2560px-Stripe_Logo%2C_revised_2016.svg.png" alt="Stripe" class="h-full w-auto object-contain brightness-0 invert opacity-60">
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Newsletter Success Popup -->
    <div id="newsletter-popup" class="fixed inset-0 bg-black/40 backdrop-blur-xl z-50 flex items-center justify-center hidden p-4">
        <div class="glass-strong rounded-3xl shadow-2xl w-full max-w-md mx-auto transform transition-all duration-300 scale-95 opacity-0 p-6 md:p-8 text-center" id="newsletter-popup-content">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-4 md:mb-6">
                <svg class="w-7 h-7 md:w-8 md:h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl md:text-2xl font-bold text-charcoal-900 mb-2 font-display">Welcome Aboard!</h3>
            <p class="text-sm md:text-base text-charcoal-500 mb-6 md:mb-8">You've successfully subscribed to our newsletter. Get ready for amazing deals!</p>
            <button onclick="closeNewsletterPopup()" class="w-full bg-gradient-to-r from-folly to-folly-500 hover:from-folly-500 hover:to-folly text-white px-6 py-3.5 md:px-8 md:py-4 rounded-2xl font-semibold transition-all duration-300 shadow-lg hover:shadow-folly/25 text-sm md:text-base">
                Start Shopping
            </button>
        </div>
    </div>

    <!-- Discord Support Chat Widget -->
    <?php include_once __DIR__ . '/chat-widget.php'; ?>

    <!-- Custom JavaScript -->
    <script src="<?php echo getAssetUrl('js/main.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('js/cart.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('js/newsletter.js'); ?>"></script>
</body>
</html>
