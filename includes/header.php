<?php
require_once __DIR__ . '/functions.php';
$env = loadEnvFile(__DIR__ . '/../.env');

// Strengthen session cookies and set security headers early
if (!headers_sent()) {
    // Security headers
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

if (session_status() == PHP_SESSION_NONE) {
    // Secure session cookie params (30-day cookie for customer sessions; inactivity enforced below)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $cookieDays = defined('CUSTOMER_SESSION_COOKIE_DAYS') ? CUSTOMER_SESSION_COOKIE_DAYS : 30;
    session_set_cookie_params([
        'lifetime' => $cookieDays * 86400,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
    // Enforce 9-day inactivity for customer login: clear customer session if idle too long
    $inactivityDays = defined('CUSTOMER_INACTIVITY_DAYS') ? CUSTOMER_INACTIVITY_DAYS : 9;
    $inactivityThreshold = time() - ($inactivityDays * 86400);
    if (!empty($_SESSION['customer_id'])) {
        if (!empty($_SESSION['customer_last_activity']) && (int)$_SESSION['customer_last_activity'] < $inactivityThreshold) {
            unset($_SESSION['customer_id'], $_SESSION['customer_email'], $_SESSION['customer_last_activity']);
        } else {
            $_SESSION['customer_last_activity'] = time();
        }
    }
}
require_once __DIR__ . '/functions.php';

$settings = getSettings();
$customerLoggedIn = isCustomerLoggedIn();
$customerEmail = $customerLoggedIn ? getLoggedInCustomerEmail() : null;
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
        $levelClass = $level > 0 ? 'text-gray-500 hover:text-folly font-normal' : 'text-charcoal-800 hover:text-folly font-medium';
        $mobileClass = $isMobile ? 'text-charcoal-600 hover:text-folly font-medium' : $levelClass;
        $paddingClass = $level > 0 ? 'pl-' . ($level * 4 + 4) : 'px-4';
        
        if ($isMobile) {
            $html .= '<a href="' . getBaseUrl('category.php?slug=' . $category['slug']) . '" class="block ' . $paddingClass . ' py-3 text-sm ' . $mobileClass . ' hover:bg-gray-50 rounded-lg touch-manipulation transition-colors duration-200" @click="$store.mobileMenu.open = false">';
            $html .= $indent . htmlspecialchars($category['name']);
            if (!empty($category['children'])) {
                $html .= ' <span class="text-xs text-gray-400 ml-1">(' . count($category['children']) . ')</span>';
            }
            $html .= '</a>';
        } else {
            $html .= '<a href="' . getBaseUrl('category.php?slug=' . $category['slug']) . '" class="block px-4 py-2.5 text-sm ' . $levelClass . ' hover:bg-folly-50 hover:text-folly transition-all duration-200 mx-1 rounded-md">';
            $html .= $indent . htmlspecialchars($category['name']);
            if (!empty($category['children'])) {
                $html .= ' <span class="text-xs text-gray-400 ml-1">(' . count($category['children']) . ')</span>';
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
<html lang="en" class="overflow-x-clip">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo htmlspecialchars($settings['site_name']); ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : htmlspecialchars($settings['site_description']); ?>">
    <!-- Meta / Facebook domain verification for angelmarketplace.org -->
    <meta name="facebook-domain-verification" content="xmvafrvv54tics9gc6oyb6mgj7zotm" />
    
    <!-- Google Fonts - Coves & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Coves font is usually local or custom, assuming it's loaded or falling back to sans -->
    
    <!-- Load Tailwind CSS first -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Then override with custom config -->
    <script>
        tailwind.config = {
            theme: {
                fontFamily: {
                    sans: ['Poppins', 'sans-serif'],
                    display: ['Coves', 'Poppins', 'sans-serif'],
                    brand: ['Coves', 'Poppins', 'sans-serif'],
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
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                        'glow': '0 0 15px rgba(255, 0, 85, 0.3)',
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js with Collapse plugin -->
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
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

    <!-- Facebook Pixel -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php echo $env['FACEBOOK_PIXEL_ID'] ?? ''; ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=<?php echo $env['FACEBOOK_PIXEL_ID'] ?? ''; ?>&ev=PageView&noscript=1"
    /></noscript>

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

        window.trendingSearches = <?php
            // Build trending searches from real product/category data
            $allProds = readJsonFile('products.json');
            $activeProds = array_filter($allProds, function($p) { return !empty($p['active']); });
            $featuredProds = array_filter($activeProds, function($p) { return !empty($p['featured']); });
            usort($featuredProds, function($a, $b) { return ($b['id'] ?? 0) <=> ($a['id'] ?? 0); });

            $trending = [];
            foreach (array_slice($featuredProds, 0, 4) as $p) {
                $words = explode(' ', $p['name']);
                $trending[] = implode(' ', array_slice($words, 0, min(3, count($words))));
            }

            $allCats = getCategories();
            $featCats = array_filter($allCats, function($c) { return !empty($c['active']) && !empty($c['featured']); });
            foreach (array_slice(array_values($featCats), 0, 4) as $cat) {
                $trending[] = $cat['name'];
            }

            echo json_encode(array_values(array_unique($trending)));
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
        /* Font Configuration - Only load if fonts exist */
        <?php
        $fontPath = __DIR__ . '/../assets/fonts/';
        if (file_exists($fontPath . 'Coves-Bold.otf')):
        ?>
        @font-face {
            font-family: 'Coves';
            src: url('<?php echo getAssetUrl("fonts/Coves-Bold.otf"); ?>') format('opentype');
            font-weight: bold;
            font-style: normal;
        }
        <?php endif; ?>
        <?php if (file_exists($fontPath . 'Coves-Light.otf')): ?>
        @font-face {
            font-family: 'Coves';
            src: url('<?php echo getAssetUrl("fonts/Coves-Light.otf"); ?>') format('opentype');
            font-weight: 300;
            font-style: normal;
        }
        <?php endif; ?>

        h1, h2, h3, h4, h5, h6, .font-brand, .font-display {
            font-family: 'Coves', 'Poppins', sans-serif !important;
        }
        
        body, input, select, textarea, button {
            font-family: 'Poppins', sans-serif;
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
        
        #sq1 { margin-top: -25px; margin-left: -25px; animation: loader_5191 675ms ease-in-out 0s infinite alternate; }
        #sq2 { margin-top: -25px; animation: loader_5191 675ms ease-in-out 75ms infinite alternate; }
        #sq3 { margin-top: -25px; margin-left: 15px; animation: loader_5191 675ms ease-in-out 150ms infinite alternate; }
        #sq4 { margin-left: -25px; animation: loader_5191 675ms ease-in-out 225ms infinite alternate; }
        #sq5 { animation: loader_5191 675ms ease-in-out 300ms infinite alternate; }
        #sq6 { margin-left: 15px; animation: loader_5191 675ms ease-in-out 375ms infinite alternate; }
        #sq7 { margin-top: 15px; margin-left: -25px; animation: loader_5191 675ms ease-in-out 450ms infinite alternate; }
        #sq8 { margin-top: 15px; animation: loader_5191 675ms ease-in-out 525ms infinite alternate; }
        #sq9 { margin-top: 15px; margin-left: 15px; animation: loader_5191 675ms ease-in-out 600ms infinite alternate; }
        
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
            .touch-manipulation { min-height: 44px; min-width: 44px; }
            body { -webkit-overflow-scrolling: touch; }
            * { -webkit-tap-highlight-color: rgba(255, 0, 85, 0.2); }
            input[type="text"], input[type="email"], input[type="number"], textarea, select { font-size: 16px; }
            .transition-colors { transition: color 150ms ease-in-out, background-color 150ms ease-in-out; }
        }
        
        /* iOS specific fixes */
        @supports (-webkit-touch-callout: none) {
            .fixed { position: -webkit-sticky; position: sticky; }
        }
    </style>
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/custom.css'); ?>">
    <link rel="stylesheet" href="<?php echo getAssetUrl('css/loading-spinner.css'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo getAssetUrl('images/general/logo.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo getAssetUrl('images/general/logo.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo getAssetUrl('images/general/logo.png'); ?>">
</head>
<body class="font-sans bg-gray-50 min-h-screen flex flex-col selection:bg-folly selection:text-white overflow-x-clip">
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
    <header id="main-header" x-data="{ showMobileMenu: false, searchOpen: false }"
           x-init="$store.scrolled.init()"
           class="w-full z-50 transition-all duration-300 header-glass sticky top-0">

        <!-- Main Header Area -->
        <div class="transition-all duration-500">
            <div class="container mx-auto px-4 py-3 md:py-4">
                <div class="flex items-center justify-between gap-4 md:gap-8">
                    <!-- Logo -->
                    <a href="<?php echo getBaseUrl(); ?>" class="flex-shrink-0 flex items-center gap-3 group">
                        <img src="<?php echo getAssetUrl('images/general/logo.png'); ?>" alt="Logo" class="h-10 md:h-12 w-auto transition-all duration-500 group-hover:scale-105 group-hover:drop-shadow-lg">
                        <div class="flex flex-col">
                            <span class="font-display text-lg md:text-xl font-bold text-charcoal-900 tracking-tight group-hover:text-folly transition-colors duration-300 leading-none">
                                <?php echo htmlspecialchars($settings['site_name']); ?>
                            </span>
                            <span class="text-[10px] text-charcoal-400 tracking-[0.2em] uppercase hidden md:block mt-0.5">Premium Marketplace</span>
                        </div>
                    </a>

                    <!-- Search Bar (Desktop) -->
                    <div class="hidden lg:block flex-1 max-w-2xl mx-auto px-8">
                        <form action="<?php echo getBaseUrl('search.php'); ?>" method="GET" class="relative group">
                            <div class="relative flex items-center">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400 group-focus-within:text-folly transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text" name="q" placeholder="Search products..."
                                       class="w-full bg-white/60 text-charcoal-800 border border-gray-200/60 rounded-full py-2.5 pl-11 pr-12 focus:outline-none focus:bg-white focus:ring-2 focus:ring-folly/30 focus:border-folly/50 focus:shadow-[0_0_20px_rgba(255,0,85,0.1)] transition-all duration-300 placeholder-gray-400 font-sans text-sm backdrop-blur-sm"
                                       value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                                <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-folly rounded-full transition-all duration-300 hover:bg-folly/10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Right Actions -->
                    <div class="flex items-center space-x-2 md:space-x-4">
                        <!-- Mobile Search Toggle -->
                        <button @click="searchOpen = !searchOpen" class="lg:hidden p-2 text-charcoal-600 hover:text-folly rounded-full transition-all duration-300 hover:bg-folly/5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </button>

                        <!-- Cart -->
                        <div class="relative group">
                            <button onclick="toggleMiniCart()" class="flex items-center gap-2 text-charcoal-600 hover:text-folly transition-all duration-300 group relative z-10 p-2 rounded-full hover:bg-folly/5">
                                <div class="relative">
                                    <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"></path></svg>
                                    <span class="cart-counter absolute -top-1.5 -right-1.5 bg-folly text-white text-[9px] font-bold h-4 w-4 flex items-center justify-center rounded-full shadow-sm ring-1.5 ring-white"
                                          style="display: <?php echo $cartCount > 0 ? 'flex' : 'none'; ?>;">
                                        <?php echo $cartCount; ?>
                                    </span>
                                </div>
                                <span class="hidden md:block text-xs font-medium tracking-wide">Cart</span>
                            </button>

                            <!-- Mini Cart Dropdown -->
                            <div id="mini-cart" class="absolute right-0 top-full mt-3 w-80 rounded-2xl shadow-2xl border border-gray-200/60 z-50 hidden transform transition-all origin-top-right" style="background: rgba(255,255,255,0.93); backdrop-filter: blur(40px) saturate(180%); -webkit-backdrop-filter: blur(40px) saturate(180%);">
                                <div class="p-4 border-b border-gray-100/50 flex justify-between items-center rounded-t-2xl">
                                    <h3 class="font-semibold text-charcoal-900 font-display text-sm">Shopping Cart</h3>
                                    <span class="text-[11px] text-gray-400 font-sans"><?php echo $cartCount; ?> Items</span>
                                </div>
                                <div id="mini-cart-items" class="max-h-72 overflow-y-auto p-2 scrollbar-thin scrollbar-thumb-gray-200">
                                    <!-- Items loaded via JS -->
                                </div>
                                <div class="p-4 border-t border-gray-100/50 rounded-b-2xl">
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-gray-500 text-sm">Subtotal:</span>
                                        <span id="mini-cart-total" class="font-bold text-lg text-folly">£0.00</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="<?php echo getBaseUrl('cart.php'); ?>" class="px-4 py-2.5 bg-white/80 border border-gray-200/60 text-charcoal-700 rounded-xl text-sm font-semibold hover:bg-white hover:border-gray-300 transition-all text-center backdrop-blur-sm">View Cart</a>
                                        <button onclick="proceedToCheckout()" class="px-4 py-2.5 bg-gradient-to-r from-folly to-folly-500 hover:shadow-lg hover:shadow-folly/25 text-white rounded-xl text-sm font-semibold transition-all">Checkout</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Menu Button -->
                        <button @click="$store.mobileMenu.toggle()" class="md:hidden p-2 text-charcoal-600 hover:text-folly rounded-full transition-all duration-300 hover:bg-folly/5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation Bar (Desktop) -->
            <div class="hidden md:block border-t border-gray-100/50">
                <div class="container mx-auto px-4">
                    <nav class="flex items-center">
                        <!-- Categories Dropdown -->
                        <div class="relative group z-30" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <button class="flex items-center space-x-2 px-4 py-2.5 text-sm font-semibold text-charcoal-700 hover:text-folly transition-all duration-300 nav-link-underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                <span class="tracking-wide">Categories</span>
                                <svg class="w-3 h-3 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <!-- Dropdown -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="absolute left-0 top-full w-64 rounded-xl shadow-2xl border border-gray-200/60 py-2 mt-0.5"
                                 style="display: none; background: rgba(255,255,255,0.93); backdrop-filter: blur(40px) saturate(180%); -webkit-backdrop-filter: blur(40px) saturate(180%);">
                                <?php echo renderNavCategoryHierarchy($categoryHierarchy, false); ?>
                                <div class="border-t border-gray-100/50 mt-2 pt-2">
                                    <a href="<?php echo getBaseUrl('categories.php'); ?>" class="block px-6 py-3 text-xs font-bold text-folly hover:bg-folly/5 transition-colors uppercase tracking-wider">
                                        View All Categories →
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 flex items-center justify-center space-x-1">
                            <a href="<?php echo getBaseUrl(); ?>" class="nav-link-underline px-4 py-2.5 text-sm font-medium text-charcoal-600 hover:text-folly transition-all duration-300 tracking-wide">Home</a>
                            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="nav-link-underline px-4 py-2.5 text-sm font-medium text-charcoal-600 hover:text-folly transition-all duration-300 tracking-wide">Shop</a>
                            <a href="<?php echo getBaseUrl('angelprints.php'); ?>" class="nav-link-underline px-4 py-2.5 text-sm font-medium text-charcoal-600 hover:text-folly transition-all duration-300 tracking-wide">Angel Prints</a>
                            <a href="<?php echo getBaseUrl('about.php'); ?>" class="nav-link-underline px-4 py-2.5 text-sm font-medium text-charcoal-600 hover:text-folly transition-all duration-300 tracking-wide">About Us</a>
                            <a href="<?php echo getBaseUrl('contact.php'); ?>" class="nav-link-underline px-4 py-2.5 text-sm font-medium text-charcoal-600 hover:text-folly transition-all duration-300 tracking-wide">Contact Us</a>
                            <?php if ($customerLoggedIn): ?>
                            <a href="<?php echo getBaseUrl(); ?>" class="nav-link-underline px-4 py-2.5 text-sm font-medium text-charcoal-600 hover:text-folly transition-all duration-300 tracking-wide">My account</a>
                            <a href="<?php echo getBaseUrl('logout.php'); ?>" class="nav-link-underline px-4 py-2.5 text-sm font-medium text-charcoal-600 hover:text-folly transition-all duration-300 tracking-wide">Log out</a>
                            <?php else: ?>
                            <a href="<?php echo getBaseUrl('login.php'); ?>" class="nav-link-underline px-4 py-2.5 text-sm font-medium text-charcoal-600 hover:text-folly transition-all duration-300 tracking-wide">Log in</a>
                            <?php endif; ?>
                        </div>

                        <a href="<?php echo getBaseUrl('shop.php?sort=deals'); ?>" class="px-4 py-2.5 text-sm font-medium text-folly flex items-center gap-1.5 hover:bg-folly/5 rounded-full transition-all duration-300">
                             <span class="w-1.5 h-1.5 rounded-full bg-folly animate-pulse"></span>
                             Special Deals
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Mobile Search Panel -->
        <div x-show="searchOpen" x-collapse class="lg:hidden glass border-t border-white/20 p-4">
            <form action="<?php echo getBaseUrl('search.php'); ?>" method="GET">
                <div class="relative flex items-center">
                    <input type="text" name="q" placeholder="Search products..." class="w-full bg-white/70 border border-gray-200/50 rounded-full py-3 pl-5 pr-14 focus:ring-2 focus:ring-folly/30 focus:border-folly/50 font-sans backdrop-blur-sm" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit" class="absolute right-1.5 w-9 h-9 flex items-center justify-center bg-folly text-white rounded-full hover:bg-folly-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </div>
            </form>
        </div>

    </header>

    <!-- Mobile Menu Overlay (outside header to avoid sticky stacking context) -->
    <div class="md:hidden fixed inset-0 z-[100] bg-charcoal-900/40 backdrop-blur-md" x-data x-show="$store.mobileMenu.open" x-transition.opacity @click="$store.mobileMenu.open = false" style="display: none;"></div>
    <!-- Mobile Menu Drawer -->
    <div class="md:hidden fixed top-0 left-0 bottom-0 w-[85%] max-w-sm z-[101] shadow-2xl transform transition-transform duration-300 flex flex-col border-r border-gray-200/40"
         x-data
         style="background: rgba(255,255,255,0.96); backdrop-filter: blur(40px) saturate(180%); -webkit-backdrop-filter: blur(40px) saturate(180%);"
         :class="$store.mobileMenu.open ? 'translate-x-0' : '-translate-x-full'">

        <div class="p-5 border-b border-gray-100/30 flex justify-between items-center">
            <span class="font-display text-lg font-bold text-charcoal-900">Menu</span>
            <button @click="$store.mobileMenu.open = false" class="p-2 text-gray-400 hover:text-folly transition-all duration-300 rounded-full hover:bg-folly/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-0.5">
            <a href="<?php echo getBaseUrl(); ?>" class="block px-4 py-3 text-[15px] font-medium text-charcoal-700 hover:text-folly hover:bg-folly/5 rounded-xl transition-all duration-200">Home</a>
            <div x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex justify-between items-center px-4 py-3 text-[15px] font-medium text-charcoal-700 hover:text-folly hover:bg-folly/5 rounded-xl transition-all duration-200">
                    <span>Categories</span>
                    <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" x-collapse class="ml-4 pl-4 border-l border-gray-200/50 space-y-0.5 mt-1">
                     <?php echo renderNavCategoryHierarchy($categoryHierarchy, true); ?>
                     <a href="<?php echo getBaseUrl('categories.php'); ?>" class="block px-4 py-2 text-sm font-bold text-folly mt-2">View All Categories →</a>
                </div>
            </div>
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="block px-4 py-3 text-[15px] font-medium text-charcoal-700 hover:text-folly hover:bg-folly/5 rounded-xl transition-all duration-200">Shop</a>
            <a href="<?php echo getBaseUrl('angelprints.php'); ?>" class="block px-4 py-3 text-[15px] font-medium text-charcoal-700 hover:text-folly hover:bg-folly/5 rounded-xl transition-all duration-200">Angel Prints</a>
            <a href="<?php echo getBaseUrl('about.php'); ?>" class="block px-4 py-3 text-[15px] font-medium text-charcoal-700 hover:text-folly hover:bg-folly/5 rounded-xl transition-all duration-200">About Us</a>
            <a href="<?php echo getBaseUrl('contact.php'); ?>" class="block px-4 py-3 text-[15px] font-medium text-charcoal-700 hover:text-folly hover:bg-folly/5 rounded-xl transition-all duration-200">Contact Us</a>
            <?php if ($customerLoggedIn): ?>
            <a href="<?php echo getBaseUrl(); ?>" class="block px-4 py-3 text-[15px] font-medium text-charcoal-700 hover:text-folly hover:bg-folly/5 rounded-xl transition-all duration-200">My account</a>
            <a href="<?php echo getBaseUrl('logout.php'); ?>" class="block px-4 py-3 text-[15px] font-medium text-charcoal-700 hover:text-folly hover:bg-folly/5 rounded-xl transition-all duration-200">Log out</a>
            <?php else: ?>
            <a href="<?php echo getBaseUrl('login.php'); ?>" class="block px-4 py-3 text-[15px] font-medium text-charcoal-700 hover:text-folly hover:bg-folly/5 rounded-xl transition-all duration-200">Log in</a>
            <?php endif; ?>
        </div>

        <div class="p-4 border-t border-gray-100/30">
            <a href="<?php echo getBaseUrl('shop.php?sort=deals'); ?>" class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-folly hover:bg-folly/5 rounded-xl transition-all">
                <span class="w-1.5 h-1.5 rounded-full bg-folly animate-pulse"></span>
                Special Deals
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="relative">
