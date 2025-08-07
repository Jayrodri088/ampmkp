<?php
// Strengthen session cookies and set security headers early
if (!headers_sent()) {
    // Security headers
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

if (session_status() == PHP_SESSION_NONE) {
    // Secure session cookie params
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
require_once __DIR__ . '/functions.php';

$settings = getSettings();
$cartCount = getCartItemCount();
$categories = getCategories();

// Determine selected currency for frontend
$availableCurrenciesCodes = array_map(function($c){ return $c['code']; }, $settings['currencies'] ?? []);
$selectedCurrency = $_SESSION['selected_currency'] ?? ($settings['currency_code'] ?? 'GBP');
if (!in_array($selectedCurrency, $availableCurrenciesCodes)) {
    $selectedCurrency = $settings['currency_code'] ?? 'GBP';
}

// Get category hierarchy for navigation
$categoryHierarchy = getCategoryHierarchy();

// Helper function to render hierarchical categories in navigation
function renderNavCategoryHierarchy($categories, $isMobile = false, $level = 0) {
    $html = '';
    foreach ($categories as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
        $levelClass = $level > 0 ? 'text-gray-600 hover:text-folly' : 'text-charcoal-700 hover:text-folly';
        $mobileClass = $isMobile ? 'text-charcoal-600 hover:text-folly' : $levelClass;
        $paddingClass = $level > 0 ? 'pl-' . ($level * 4 + 4) : 'px-4';
        
        if ($isMobile) {
            $html .= '<a href="' . getBaseUrl('category.php?slug=' . $category['slug']) . '" class="block ' . $paddingClass . ' py-3 text-sm ' . $mobileClass . ' hover:bg-gray-50 rounded-lg touch-manipulation" @click="$store.mobileMenu.open = false">';
            $html .= $indent . htmlspecialchars($category['name']);
            if (!empty($category['children'])) {
                $html .= ' <span class="text-xs text-gray-500">(' . count($category['children']) . ')</span>';
            }
            $html .= '</a>';
        } else {
            $html .= '<a href="' . getBaseUrl('category.php?slug=' . $category['slug']) . '" class="block px-4 py-3 text-sm ' . $levelClass . ' hover:bg-folly-50 hover:text-folly transition-colors duration-200 mx-2 rounded-lg">';
            $html .= $indent . htmlspecialchars($category['name']);
            if (!empty($category['children'])) {
                $html .= ' <span class="text-xs text-gray-500">(' . count($category['children']) . ')</span>';
            }
            $html .= '</a>';
        }
        
        // Render children
        if (!empty($category['children'])) {
            $html .= renderNavCategoryHierarchy($category['children'], $isMobile, $level + 1);
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo htmlspecialchars($settings['site_name']); ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : htmlspecialchars($settings['site_description']); ?>">
    
    <!-- Google Fonts - Coves -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Coves:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Load Tailwind CSS first -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Then override with custom config -->
    <script>
        tailwind.config = {
            theme: {
                fontFamily: {
                    sans: ['Coves', 'sans-serif'],
                    brand: ['Coves', 'sans-serif'],
                },
                extend: {
                    colors: {
                        'folly': {
                            DEFAULT: '#FF0055',
                            50: '#ffccdd',
                            100: '#ff99bb',
                            200: '#ff6699',
                            300: '#ff3377',
                            400: '#ff0055',
                            500: '#cc0044',
                            600: '#990033',
                            700: '#660022',
                            800: '#330011',
                            900: '#1a0008'
                        },
                        'charcoal': {
                            DEFAULT: '#3B4255',
                            50: '#d4d7e1',
                            100: '#a8afc3',
                            200: '#7d88a5',
                            300: '#596380',
                            400: '#3b4255',
                            500: '#2f3443',
                            600: '#232733',
                            700: '#171a22',
                            800: '#0c0d11',
                            900: '#060608'
                        },
                        'tangerine': {
                            DEFAULT: '#F5884B',
                            50: '#fde8db',
                            100: '#fbd0b8',
                            200: '#f9b994',
                            300: '#f7a270',
                            400: '#f5884b',
                            500: '#f16310',
                            600: '#b64a0b',
                            700: '#793107',
                            800: '#3d1904',
                            900: '#1e0c02'
                        },
                        'primary': '#FF0055',
                        'secondary': '#3B4255',
                        'accent': '#F5884B'
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('scrolled', {
            isScrolled: false,
            init() {
                window.addEventListener('scroll', () => {
                    this.isScrolled = window.scrollY > 10;
                });
            }
        });
        
        Alpine.store('mobileMenu', {
            open: false,
            toggle() {
                this.open = !this.open;
            }
        });
    });

    // Page Loading Animation Control
    document.addEventListener('DOMContentLoaded', function() {
        const loader = document.getElementById('page-loader');
        
        // Hide loader when page is fully loaded
        window.addEventListener('load', function() {
            setTimeout(() => {
                loader.classList.add('hidden');
                // Remove loader from DOM after animation completes
                setTimeout(() => {
                    if (loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                }, 500);
            }, 200); // Small delay to ensure smooth transition
        });
        
        // Fallback: Hide loader after maximum 5 seconds
        setTimeout(() => {
            if (loader && !loader.classList.contains('hidden')) {
                loader.classList.add('hidden');
                setTimeout(() => {
                    if (loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                }, 500);
            }
        }, 5000);
    });
    </script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <!-- Inject currency context -->
    <script>
        window.currentCurrency = <?php echo json_encode($selectedCurrency); ?>;
        window.availableCurrencies = <?php 
            $currs = $settings['currencies'] ?? [];
            $safe = array_map(function($c){ 
                return [
                    'code' => $c['code'],
                    'symbol' => $c['symbol'],
                    'name' => $c['name'] ?? $c['code'],
                    'default' => !empty($c['default'])
                ];
            }, $currs);
            echo json_encode($safe);
        ?>;
    </script>

    <!-- Global SweetAlert Configuration -->
    <script>
        // Configure SweetAlert2 global defaults
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            
            // Store original Swal.fire function
            const originalSwalFire = Swal.fire;
            
            // Set global defaults for all SweetAlert instances
            Swal.fire = function(options) {
                // If no custom colors are provided, use brand colors
                if (typeof options === 'object' && options !== null) {
                    if (!options.confirmButtonColor) {
                        options.confirmButtonColor = '#FF0055'; // folly
                    }
                    if (!options.cancelButtonColor) {
                        options.cancelButtonColor = '#3B4255'; // charcoal
                    }
                }
                return originalSwalFire.call(Swal, options);
            };
            
            // Make Toast available globally
            window.Toast = Toast;
        });
    </script>
    
    <!-- Custom CSS -->
    <style>
        /* Apply Coves font to all elements */
        * {
            font-family: 'Coves', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
        }
        
        /* Fix for specific elements that might be overriding */
        body, button, input, optgroup, select, textarea,
        h1, h2, h3, h4, h5, h6,
        .font-sans, .font-brand,
        .btn, .button, button, [type='button'], [type='submit'] {
            font-family: 'Coves', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
        }
        
        /* Page Loading Animation */
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        
        #page-loader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .loader {
            position: relative;
            width: 50px;
            height: 50px;
        }
        
        @keyframes loader_5191 {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .square {
            background: #FF0055; /* Using folly brand color */
            width: 10px;
            height: 10px;
            position: absolute;
            top: 50%;
            left: 50%;
            margin-top: -5px;
            margin-left: -5px;
            border-radius: 2px;
            box-shadow: 0 2px 4px rgba(255, 0, 85, 0.3);
        }
        
        #sq1 {
            margin-top: -25px;
            margin-left: -25px;
            animation: loader_5191 675ms ease-in-out 0s infinite alternate;
        }
        
        #sq2 {
            margin-top: -25px;
            animation: loader_5191 675ms ease-in-out 75ms infinite alternate;
        }
        
        #sq3 {
            margin-top: -25px;
            margin-left: 15px;
            animation: loader_5191 675ms ease-in-out 150ms infinite alternate;
        }
        
        #sq4 {
            margin-left: -25px;
            animation: loader_5191 675ms ease-in-out 225ms infinite alternate;
        }
        
        #sq5 {
            animation: loader_5191 675ms ease-in-out 300ms infinite alternate;
        }
        
        #sq6 {
            margin-left: 15px;
            animation: loader_5191 675ms ease-in-out 375ms infinite alternate;
        }
        
        #sq7 {
            margin-top: 15px;
            margin-left: -25px;
            animation: loader_5191 675ms ease-in-out 450ms infinite alternate;
        }
        
        #sq8 {
            margin-top: 15px;
            animation: loader_5191 675ms ease-in-out 525ms infinite alternate;
        }
        
        #sq9 {
            margin-top: 15px;
            margin-left: 15px;
            animation: loader_5191 675ms ease-in-out 600ms infinite alternate;
        }
        
        /* Loading text */
        .loading-text {
            margin-top: 80px;
            color: #3B4255; /* charcoal color */
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            /* Ensure touch targets are at least 44px */
            .touch-manipulation {
                min-height: 44px;
                min-width: 44px;
            }
            
            /* Improve scrolling performance */
            body {
                -webkit-overflow-scrolling: touch;
            }
            
            /* Better tap highlighting */
            * {
                -webkit-tap-highlight-color: rgba(255, 0, 85, 0.2);
            }
            
            /* Prevent zoom on input focus */
            input[type="text"], input[type="email"], input[type="number"], textarea, select {
                font-size: 16px;
            }
            
            /* Smooth transitions for mobile */
            .transition-colors {
                transition: color 150ms ease-in-out, background-color 150ms ease-in-out;
            }
        }
        
        /* iOS specific fixes */
        @supports (-webkit-touch-callout: none) {
            /* Fix for iOS Safari header spacing */
            .fixed {
                position: -webkit-sticky;
                position: sticky;
            }
        }
    </style>
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/custom.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/loading-spinner.css'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo getAssetUrl('images/general/favicon.png'); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo getAssetUrl('images/general/favicon.ico'); ?>">
    <link rel="apple-touch-icon" href="<?php echo getAssetUrl('images/general/logo.png'); ?>">
</head>
<body class="font-brand bg-gray-50 min-h-screen flex flex-col">
    <!-- Page Loading Animation -->
    <div id="page-loader">
        <div class="text-center">
            <div class="loader">
                <div class="square" id="sq1"></div>
                <div class="square" id="sq2"></div>
                <div class="square" id="sq3"></div>
                <div class="square" id="sq4"></div>
                <div class="square" id="sq5"></div>
                <div class="square" id="sq6"></div>
                <div class="square" id="sq7"></div>
                <div class="square" id="sq8"></div>
                <div class="square" id="sq9"></div>
            </div>
            <div class="loading-text">Loading...</div>
        </div>
    </div>

    <div class="flex-grow">
    <!-- Header -->
    <header x-data="{ showMobileMenu: false }" 
           x-init="$store.scrolled.init()"
           :class="{'bg-white/95 backdrop-blur-md shadow-xl': $store.scrolled.isScrolled}" 
           class="fixed top-0 left-0 right-0 z-50 bg-white transition-all duration-300 border-b border-gray-200 shadow-sm">
        
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-charcoal-50 to-charcoal-100 border-b border-charcoal-200">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center py-3 px-6 text-sm text-gray-600">
                    <div class="hidden md:block">
                        <span class="font-medium">24/7 SUPPORT: <?php echo htmlspecialchars($settings['site_phone']); ?></span>
                    </div>
                    <div class="flex space-x-6">
                        <a href="https://recruitments.angeldiscounts.sale" class="hover:text-folly transition-colors duration-200 font-medium">Sign Up as a Representative</a>
                        <a href="https://angeldiscounts.sale" class="hover:text-folly transition-colors duration-200 font-medium">Discounts</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Header -->
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4 md:py-6 px-3 md:px-6">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo getBaseUrl(); ?>" class="flex items-center hover:opacity-80 transition-opacity duration-200">
                        <img 
                            src="<?php echo getAssetUrl('images/general/logo.png'); ?>" 
                            alt="Angel Marketplace Logo" 
                            class="h-8 md:h-12 w-auto mr-2 md:mr-3"
                        >
                        <span class="text-lg md:text-2xl font-bold text-charcoal-800 hover:text-folly transition-colors duration-200">
                            <?php echo htmlspecialchars($settings['site_name']); ?>
                        </span>
                    </a>
                </div>
                
                <!-- Search Bar -->
                <div class="hidden md:flex flex-1 max-w-xl mx-8">
                    <form action="<?php echo getBaseUrl('search.php'); ?>" method="GET" class="w-full">
                        <div class="relative">
                            <input 
                                type="text" 
                                name="q" 
                                placeholder="Search products..." 
                                class="w-full px-5 py-3 pr-16 border border-charcoal-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly shadow-sm transition-all duration-200"
                                value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                            >
                            <button 
                                type="submit" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 h-10 w-10 bg-folly text-white rounded-lg hover:bg-folly-500 transition-colors duration-200 flex items-center justify-center"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Cart & Mobile Menu -->
                <div class="flex items-center space-x-2 md:space-x-4">
                    <!-- Cart -->
                    <div class="relative">
                        <button 
                            onclick="toggleMiniCart()" 
                            class="relative p-2 md:p-3 text-charcoal-600 hover:text-folly transition-colors duration-200 bg-charcoal-50 hover:bg-folly-50 rounded-xl touch-manipulation"
                            id="cart-button"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"></path>
                            </svg>
                            <span class="cart-counter absolute -top-1 -right-1 bg-folly text-white text-xs rounded-full h-5 w-5 flex items-center justify-center shadow-lg" style="display: <?php echo $cartCount > 0 ? 'flex' : 'none'; ?>;">
                                <?php echo $cartCount; ?>
                            </span>
                        </button>
                        
                        <!-- Mini Cart Dropdown -->
                        <div 
                            id="mini-cart" 
                            class="absolute right-0 top-full mt-2 w-80 sm:w-72 md:w-80 bg-white rounded-2xl shadow-2xl border border-gray-200 z-50 hidden max-w-[calc(100vw-2rem)]"
                        >
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-900">Shopping Cart</h3>
                            </div>
                            <div id="mini-cart-items" class="max-h-64 overflow-y-auto">
                                <!-- Cart items will be loaded here -->
                            </div>
                            <div class="p-4 border-t border-gray-100">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="font-semibold text-gray-900">Total:</span>
                                    <span id="mini-cart-total" class="font-bold text-lg text-gray-900">£0.00</span>
                                </div>
                                <div class="flex gap-2">
                                    <a href="<?php echo getBaseUrl('cart.php'); ?>" class="flex-1 bg-charcoal-100 hover:bg-charcoal-200 text-charcoal-800 px-4 py-2 rounded-xl text-sm font-medium text-center transition-colors duration-200">
                                        View Cart
                                    </a>
                                    <button 
                                        onclick="proceedToCheckout()"
                                        class="flex-1 bg-folly hover:bg-folly-500 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors duration-200"
                                    >
                                        Checkout
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Menu Button -->
                    <button class="md:hidden p-2 text-charcoal-600 bg-charcoal-50 hover:bg-folly-50 hover:text-folly rounded-xl transition-colors duration-200 touch-manipulation" x-data x-on:click="$store.mobileMenu.toggle()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             :class="{'bg-white/95 backdrop-blur-md': $store.scrolled.isScrolled}" 
             class="hidden md:block border-t border-charcoal-200 transition-all duration-300 bg-gradient-to-r from-charcoal-50 to-tangerine-50">
            <div class="container mx-auto px-4">
                <div class="py-4 px-6">
                    <div class="flex space-x-8 justify-center">
                        <a href="<?php echo getBaseUrl(); ?>" class="text-charcoal-700 hover:text-folly font-semibold text-sm uppercase tracking-wider transition-colors duration-200 px-4 py-2 rounded-lg hover:bg-white/50">Home</a>
                        <!-- Categories Dropdown -->
                        <div class="relative group" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-1 text-charcoal-700 hover:text-folly font-semibold text-sm uppercase tracking-wider transition-colors duration-200 px-4 py-2 rounded-lg hover:bg-white/50">
                                <span>Categories</span>
                                <svg class="w-4 h-4 transition-transform duration-200" :class="{'transform rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-200 z-50 py-2 max-h-80 overflow-y-auto" style="display: none;">
                                <?php echo renderNavCategoryHierarchy($categoryHierarchy, false); ?>
                                <div class="border-t border-gray-100 mt-2 pt-2">
                                    <a href="<?php echo getBaseUrl('categories.php'); ?>" class="block px-4 py-3 text-sm text-folly font-semibold hover:bg-folly-50 transition-colors duration-200 mx-2 rounded-lg">
                                        View All Categories →
                                    </a>
                                </div>
                            </div>
                        </div>
                        <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-charcoal-700 hover:text-folly font-semibold text-sm uppercase tracking-wider transition-colors duration-200 px-4 py-2 rounded-lg hover:bg-white/50">Shop</a>
                        <a href="https://recruitments.angeldiscounts.sale/" target="_blank" rel="noopener noreferrer" class="text-charcoal-700 hover:text-folly font-semibold text-sm uppercase tracking-wider transition-colors duration-200 px-4 py-2 rounded-lg hover:bg-white/50">Hire</a>
                        <a href="<?php echo getBaseUrl('about.php'); ?>" class="text-charcoal-700 hover:text-folly font-semibold text-sm uppercase tracking-wider transition-colors duration-200 px-4 py-2 rounded-lg hover:bg-white/50">About Us</a>
                        <a href="<?php echo getBaseUrl('contact.php'); ?>" class="text-charcoal-700 hover:text-folly font-semibold text-sm uppercase tracking-wider transition-colors duration-200 px-4 py-2 rounded-lg hover:bg-white/50">Contact Us</a>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Mobile Menu -->
        <div class="md:hidden" x-data x-show="$store.mobileMenu.open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2" style="display: none;" @click.away="$store.mobileMenu.open = false">
            <div class="px-4 pt-4 pb-6 bg-white border-t border-charcoal-200 shadow-lg">
                <!-- Mobile Search -->
                <form action="<?php echo getBaseUrl('search.php'); ?>" method="GET" class="mb-6">
                    <div class="relative">
                        <input type="text" name="q" placeholder="Search products..." class="w-full px-4 py-3 pr-14 border border-charcoal-300 rounded-lg focus:ring-2 focus:ring-folly focus:border-folly text-base" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                        <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 h-10 w-10 bg-folly text-white rounded-lg hover:bg-folly-500 transition-colors duration-200 flex items-center justify-center touch-manipulation">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
                
                <!-- Close Button -->
                <div class="flex justify-end mb-4">
                    <button @click="$store.mobileMenu.open = false" class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 touch-manipulation">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Mobile Navigation -->
                <div class="space-y-1">
                    <a href="<?php echo getBaseUrl(); ?>" class="block px-4 py-3 text-base font-medium text-charcoal-700 hover:text-folly hover:bg-gray-50 rounded-lg touch-manipulation" @click="$store.mobileMenu.open = false">Home</a>
                    <!-- Mobile Categories Dropdown -->
                    <div x-data="{ open: false }">
                        <button @click="open = !open" class="w-full flex justify-between items-center px-4 py-3 text-base font-medium text-charcoal-700 hover:text-folly hover:bg-gray-50 rounded-lg touch-manipulation">
                            <span>Categories</span>
                            <svg :class="{'transform rotate-180': open}" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" class="mt-1 space-y-1 max-h-64 overflow-y-auto">
                            <?php echo renderNavCategoryHierarchy($categoryHierarchy, true); ?>
                            <div class="border-t border-gray-100 mt-2 pt-2 mx-4">
                                <a href="<?php echo getBaseUrl('categories.php'); ?>" class="block px-4 py-3 text-sm text-folly font-semibold hover:bg-gray-50 rounded-lg touch-manipulation" @click="$store.mobileMenu.open = false">
                                    View All Categories →
                                </a>
                            </div>
                        </div>
                    </div>
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="block px-4 py-3 text-base font-medium text-charcoal-700 hover:text-folly hover:bg-gray-50 rounded-lg touch-manipulation" @click="$store.mobileMenu.open = false">Shop</a>
                    <a href="https://recruitments.angeldiscounts.sale/" target="_blank" rel="noopener noreferrer" class="block px-4 py-3 text-base font-medium text-charcoal-700 hover:text-folly hover:bg-gray-50 rounded-lg touch-manipulation" @click="$store.mobileMenu.open = false">Hire</a>
                    <a href="<?php echo getBaseUrl('about.php'); ?>" class="block px-4 py-3 text-base font-medium text-charcoal-700 hover:text-folly hover:bg-gray-50 rounded-lg touch-manipulation" @click="$store.mobileMenu.open = false">About Us</a>
                    <a href="<?php echo getBaseUrl('contact.php'); ?>" class="block px-4 py-3 text-base font-medium text-charcoal-700 hover:text-folly hover:bg-gray-50 rounded-lg touch-manipulation" @click="$store.mobileMenu.open = false">Contact Us</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Spacer for fixed header - matches header height -->
    <div class="h-24 md:h-36"></div>
    
    <!-- Main Content -->
    <main class="relative px-4">
