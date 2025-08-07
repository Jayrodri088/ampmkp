<?php
session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: auth.php');
    exit;
}

// Include main functions
require_once '../includes/functions.php';

// Load current settings
$settings = getSettings();

// Handle form submissions
$message = '';
$message_type = '';

// Handle redirect messages
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $message_type = $_GET['type'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get current settings to preserve structure
        $newSettings = $settings;
        
        // Update general settings
        $newSettings['site_name'] = sanitizeInput($_POST['site_name']);
        $newSettings['site_description'] = sanitizeInput($_POST['site_description']);
        $newSettings['site_email'] = sanitizeInput($_POST['site_email']);
        $newSettings['site_phone'] = sanitizeInput($_POST['site_phone']);
        $newSettings['site_phone_alt'] = sanitizeInput($_POST['site_phone_alt']);
        $newSettings['site_address'] = sanitizeInput($_POST['site_address']);
        
        // Update display settings
        $newSettings['items_per_page'] = (int)$_POST['items_per_page'];
        $newSettings['homepage_categories_count'] = (int)$_POST['homepage_categories_count'];
        $newSettings['featured_products_count'] = (int)$_POST['featured_products_count'];
        $newSettings['trending_products_count'] = (int)$_POST['trending_products_count'];
        $newSettings['related_products_count'] = (int)$_POST['related_products_count'];
        
        // Update shipping settings
        $newSettings['shipping']['free_shipping_threshold'] = (float)$_POST['free_shipping_threshold'];
        
        // Process per-currency shipping costs
        if (isset($_POST['shipping_costs']) && is_array($_POST['shipping_costs'])) {
            foreach ($_POST['shipping_costs'] as $shippingCost) {
                if (!empty($shippingCost['currency'])) {
                    $currencyCode = $shippingCost['currency'];
                    $newSettings['shipping']['costs'][$currencyCode] = [
                        'standard' => isset($shippingCost['cost']) ? (float)$shippingCost['cost'] : 0,
                        'free_threshold' => isset($shippingCost['free_threshold']) ? (float)$shippingCost['free_threshold'] : 0
                    ];
                }
            }
        }
        
        // Backward compatibility
        if (isset($newSettings['shipping']['costs'][$newSettings['currency_code']])) {
            $newSettings['shipping']['standard_shipping_cost'] = $newSettings['shipping']['costs'][$newSettings['currency_code']]['standard'];
        } else {
            $newSettings['shipping']['standard_shipping_cost'] = 5.99; // Default fallback
        }
        
        
        
        // Update maintenance mode
        $newSettings['maintenance_mode'] = isset($_POST['maintenance_mode']);
        
        // Handle currencies
        if (isset($_POST['currencies']) && is_array($_POST['currencies'])) {
            $currencies = [];
            $hasDefault = false;
            
            foreach ($_POST['currencies'] as $index => $currencyData) {
                if (!empty($currencyData['code']) && !empty($currencyData['symbol']) && !empty($currencyData['name'])) {
                    $isDefault = isset($currencyData['default']) && $currencyData['default'] === '1';
                    
                    // Ensure only one default
                    if ($isDefault) {
                        if ($hasDefault) {
                            $isDefault = false; // Remove default from subsequent currencies
                        } else {
                            $hasDefault = true;
                            // Update main currency settings for backward compatibility
                            $newSettings['currency_code'] = $currencyData['code'];
                            $newSettings['currency_symbol'] = $currencyData['symbol'];
                        }
                    }
                    
                    $currencies[] = [
                        'code' => strtoupper(sanitizeInput($currencyData['code'])),
                        'symbol' => sanitizeInput($currencyData['symbol']),
                        'name' => sanitizeInput($currencyData['name']),
                        'default' => $isDefault
                    ];
                }
            }
            
            // Ensure at least one currency is marked as default
            if (!$hasDefault && !empty($currencies)) {
                $currencies[0]['default'] = true;
                $newSettings['currency_code'] = $currencies[0]['code'];
                $newSettings['currency_symbol'] = $currencies[0]['symbol'];
            }
            
            $newSettings['currencies'] = $currencies;
        }
        
        // Handle available sizes
        if (isset($_POST['available_sizes'])) {
            $sizes = array_map('trim', explode(',', $_POST['available_sizes']));
            $sizes = array_filter($sizes, function($size) {
                return !empty($size);
            });
            $newSettings['available_sizes'] = array_values($sizes);
        }
        
        // Handle payment methods
        $paymentMethods = [];
        if (isset($_POST['payment_methods']) && is_array($_POST['payment_methods'])) {
            $paymentMethods = $_POST['payment_methods'];
        }
        $newSettings['payment_methods'] = $paymentMethods;
        
        // Save settings
        if (writeJsonFile('settings.json', $newSettings)) {
            $message = 'Settings updated successfully!';
            $message_type = 'success';
            $settings = $newSettings; // Update local copy
        } else {
            throw new Exception('Failed to save settings');
        }
        
    } catch (Exception $e) {
        $message = 'Error updating settings: ' . $e->getMessage();
        $message_type = 'danger';
    }
    
    // Redirect to prevent form resubmission
    header('Location: settings.php?message=' . urlencode($message) . '&type=' . $message_type);
    exit;
}

// Ensure default structure for currencies if not set
if (!isset($settings['currencies']) || empty($settings['currencies'])) {
    $settings['currencies'] = [
        [
            'code' => $settings['currency_code'] ?? 'GBP',
            'symbol' => $settings['currency_symbol'] ?? '£',
            'name' => 'British Pound',
            'default' => true
        ]
    ];
}

// Ensure default structure for available sizes if not set
if (!isset($settings['available_sizes'])) {
    $settings['available_sizes'] = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
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
                            50: '#f7f8fa',
                            100: '#ebeef3',
                            200: '#d4d7e1',
                            300: '#a8afc3',
                            400: '#7d88a5',
                            500: '#596380',
                            600: '#3b4255',
                            700: '#2f3443',
                            800: '#232733',
                            900: '#171a22'
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
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex min-h-screen">
        <!-- Desktop Sidebar -->
        <div class="hidden lg:block w-64 bg-charcoal text-white flex-shrink-0">
            <!-- Header -->
            <div class="p-4 lg:p-6 border-b border-charcoal-500">
                <h2 class="text-lg lg:text-xl font-bold flex items-center">
                    <i class="bi bi-shield-check mr-2 lg:mr-3 text-folly"></i>
                    <span class="hidden lg:inline">Admin Panel</span>
                </h2>
                <p class="text-charcoal-200 text-xs lg:text-sm mt-1 lg:mt-2">
                    Welcome, <?= htmlspecialchars($_SESSION['admin_user']) ?>
                </p>
            </div>
            
            <!-- Navigation -->
            <nav class="p-2 lg:p-4 space-y-1">
                <a href="index.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-speedometer2 mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">Dashboard</span>
                </a>
                <a href="products.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-box-seam mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">Products</span>
                </a>
                <a href="categories.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-tags mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">Categories</span>
                </a>
                <a href="ads.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-megaphone mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">Advertisements</span>
                </a>
                <a href="orders.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-receipt mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">Orders</span>
                </a>
                <a href="contacts.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-envelope mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">Contacts</span>
                </a>
                <a href="settings.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-white bg-folly hover:bg-folly-600 transition-colors touch-manipulation">
                    <i class="bi bi-gear mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">Settings</span>
                </a>
                <a href="file-manager.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-folder mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">File Manager</span>
                </a>
                
                <div class="border-t border-charcoal-500 my-2 lg:my-4"></div>
                
                <a href="../" target="_blank" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-house mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">View Site</span>
                </a>
                <a href="auth.php?logout=1" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-box-arrow-right mr-2 lg:mr-3 w-4 lg:w-5 text-center"></i>
                    <span class="text-sm lg:text-base">Logout</span>
                </a>
            </nav>
        </div>

        <!-- Mobile Menu Overlay -->
        <div id="mobileMenuOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="closeMobileMenu()"></div>
        
        <!-- Mobile Sidebar -->
        <div id="mobileSidebar" class="lg:hidden fixed left-0 top-0 h-full w-64 bg-charcoal text-white z-50 transform -translate-x-full transition-transform duration-300 ease-in-out">
            <!-- Header -->
            <div class="p-4 border-b border-charcoal-500">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-bold flex items-center">
                        <i class="bi bi-shield-check mr-2 text-folly"></i>
                        Admin Panel
                    </h2>
                    <button onclick="closeMobileMenu()" class="text-white hover:text-gray-300 p-1 touch-manipulation">
                        <i class="bi bi-x text-2xl"></i>
                    </button>
                </div>
                <p class="text-charcoal-200 text-sm mt-1">
                    Welcome, <?= htmlspecialchars($_SESSION['admin_user']) ?>
                </p>
            </div>
            
            <!-- Navigation -->
            <nav class="p-2 space-y-1 overflow-y-auto" style="height: calc(100vh - 100px);">
                <a href="index.php" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-speedometer2 mr-3 w-5 text-center"></i>
                    Dashboard
                </a>
                <a href="products.php" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-box-seam mr-3 w-5 text-center"></i>
                    Products
                </a>
                <a href="categories.php" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-tags mr-3 w-5 text-center"></i>
                    Categories
                </a>
                <a href="ads.php" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-megaphone mr-3 w-5 text-center"></i>
                    Advertisements
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-receipt mr-3 w-5 text-center"></i>
                    Orders
                </a>
                <a href="contacts.php" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-envelope mr-3 w-5 text-center"></i>
                    Contacts
                </a>
                <a href="settings.php" class="flex items-center px-4 py-3 text-white bg-folly hover:bg-folly-600 transition-colors touch-manipulation">
                    <i class="bi bi-gear mr-3 w-5 text-center"></i>
                    Settings
                </a>
                <a href="file-manager.php" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-folder mr-3 w-5 text-center"></i>
                    File Manager
                </a>
                
                <div class="border-t border-charcoal-500 my-4"></div>
                
                <a href="../" target="_blank" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-house mr-3 w-5 text-center"></i>
                    View Site
                </a>
                <a href="auth.php?logout=1" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-box-arrow-right mr-3 w-5 text-center"></i>
                    Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Header -->
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 lg:py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center min-w-0">
                        <button onclick="openMobileMenu()" class="lg:hidden mr-3 p-2 text-charcoal-600 hover:text-folly touch-manipulation transition-colors">
                            <i class="bi bi-list text-xl"></i>
                        </button>
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-charcoal truncate">Settings</h1>
                    </div>
                    <div class="text-charcoal-400 text-xs lg:text-sm">
                        <i class="bi bi-gear-fill mr-1 lg:mr-2"></i>System Configuration
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="flex-1 p-3 sm:p-4 lg:p-6 overflow-auto">
                <!-- Messages -->
                <?php if ($message): ?>
                <div class="<?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?> border p-3 lg:p-4 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="bi bi-<?= $message_type === 'success' ? 'check-circle' : 'x-circle' ?> mr-2 flex-shrink-0"></i>
                        <span class="text-sm lg:text-base"><?= htmlspecialchars($message) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6 lg:space-y-8">
                    <!-- General Settings -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-charcoal text-white px-4 lg:px-6 py-3 lg:py-4">
                            <h3 class="text-base lg:text-lg font-semibold flex items-center">
                                <i class="bi bi-gear mr-2 lg:mr-3 text-folly"></i>
                                General Settings
                            </h3>
                        </div>
                        <div class="p-4 lg:p-6 space-y-4 lg:space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Site Name</label>
                                    <input type="text" name="site_name" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                                </div>
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Site Email</label>
                                    <input type="email" name="site_email" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['site_email'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Site Description</label>
                                <textarea name="site_description" rows="3" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation resize-y"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Phone Number</label>
                                    <input type="text" name="site_phone" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['site_phone'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Alternative Phone</label>
                                    <input type="text" name="site_phone_alt" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['site_phone_alt'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Address</label>
                                    <input type="text" name="site_address" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['site_address'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Display Settings -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-charcoal text-white px-4 lg:px-6 py-3 lg:py-4">
                            <h3 class="text-base lg:text-lg font-semibold flex items-center">
                                <i class="bi bi-display mr-2 lg:mr-3 text-folly"></i>
                                Display Settings
                            </h3>
                        </div>
                        <div class="p-4 lg:p-6 space-y-4 lg:space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 lg:gap-6">
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Items Per Page</label>
                                    <input type="number" name="items_per_page" min="1" max="100" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['items_per_page'] ?? 12) ?>">
                                </div>
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Homepage Categories</label>
                                    <input type="number" name="homepage_categories_count" min="1" max="20" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['homepage_categories_count'] ?? 6) ?>">
                                </div>
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Featured Products</label>
                                    <input type="number" name="featured_products_count" min="1" max="20" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['featured_products_count'] ?? 8) ?>">
                                </div>
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Trending Products</label>
                                    <input type="number" name="trending_products_count" min="1" max="20" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['trending_products_count'] ?? 6) ?>">
                                </div>
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Related Products</label>
                                    <input type="number" name="related_products_count" min="1" max="20" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 rounded focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" value="<?= htmlspecialchars($settings['related_products_count'] ?? 4) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Settings -->
                    <div class="bg-white border border-gray-200 overflow-hidden">
                        <div class="bg-charcoal text-white px-6 py-4">
                            <h3 class="text-lg font-semibold flex items-center">
                                <i class="bi bi-truck mr-3 text-folly"></i>
                                Shipping Settings
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-charcoal-600 mb-2">Global Free Shipping Threshold</label>
                                    <input type="number" name="free_shipping_threshold" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" value="<?= htmlspecialchars($settings['shipping']['free_shipping_threshold'] ?? 50) ?>">
                                    <p class="mt-1 text-xs text-gray-500">Set to 0 to disable free shipping</p>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="text-md font-medium text-charcoal-800 mb-4">Shipping Costs by Currency</h4>
                                <div class="space-y-4">
                                    <?php foreach ($settings['currencies'] as $index => $currency): ?>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                        <div>
                                            <label class="block text-sm font-medium text-charcoal-600 mb-2">
                                                Standard Shipping (<?= htmlspecialchars($currency['code']) ?>)
                                                <?php if ($currency['default']): ?>
                                                    <span class="text-xs text-folly-600 ml-1">(Default)</span>
                                                <?php endif; ?>
                                            </label>
                                            <div class="relative rounded-md shadow-sm">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm"><?= htmlspecialchars($currency['symbol']) ?></span>
                                                </div>
                                                <input type="number" 
                                                       name="shipping_costs[<?= $index ?>][cost]" 
                                                       step="0.01" 
                                                       min="0" 
                                                       class="focus:ring-folly focus:border-folly block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                                       value="<?= htmlspecialchars($settings['shipping']['costs'][$currency['code']]['standard'] ?? '5.99') ?>">
                                                <input type="hidden" name="shipping_costs[<?= $index ?>][currency]" value="<?= htmlspecialchars($currency['code']) ?>">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-charcoal-600 mb-2">
                                                Free Shipping Threshold (<?= htmlspecialchars($currency['code']) ?>)
                                            </label>
                                            <div class="relative rounded-md shadow-sm">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm"><?= htmlspecialchars($currency['symbol']) ?></span>
                                                </div>
                                                <input type="number" 
                                                       name="shipping_costs[<?= $index ?>][free_threshold]" 
                                                       step="0.01" 
                                                       min="0" 
                                                       class="focus:ring-folly focus:border-folly block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                                       value="<?= htmlspecialchars($settings['shipping']['costs'][$currency['code']]['free_threshold'] ?? $settings['shipping']['free_shipping_threshold'] ?? '50') ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Currency Settings -->
                    <div class="bg-white border border-gray-200 overflow-hidden">
                        <div class="bg-charcoal text-white px-6 py-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold flex items-center">
                                    <i class="bi bi-currency-pound mr-3 text-folly"></i>
                                    Currency Settings
                                </h3>
                                <button type="button" onclick="addCurrency()" class="px-4 py-2 bg-folly hover:bg-folly-600 text-white font-medium">
                                    <i class="bi bi-plus mr-2"></i>Add Currency
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div id="currencyContainer" class="space-y-4">
                                <?php foreach ($settings['currencies'] as $index => $currency): ?>
                                <div class="bg-gray-50 border border-gray-200 p-4 currency-item">
                                    <div class="flex justify-between items-start mb-4">
                                        <h4 class="font-medium text-charcoal">Currency #<?= $index + 1 ?></h4>
                                        <?php if (count($settings['currencies']) > 1): ?>
                                        <button type="button" onclick="removeCurrency(this)" class="text-red-600 hover:text-red-800">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-charcoal-600 mb-2">Code</label>
                                            <input type="text" name="currencies[<?= $index ?>][code]" class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" value="<?= htmlspecialchars($currency['code']) ?>" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-charcoal-600 mb-2">Symbol</label>
                                            <input type="text" name="currencies[<?= $index ?>][symbol]" class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" value="<?= htmlspecialchars($currency['symbol']) ?>" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-charcoal-600 mb-2">Name</label>
                                            <input type="text" name="currencies[<?= $index ?>][name]" class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" value="<?= htmlspecialchars($currency['name']) ?>" required>
                                        </div>
                                        <div class="flex items-end">
                                            <label class="flex items-center">
                                                <input type="radio" name="default_currency" value="<?= $index ?>" <?= ($currency['default'] ?? false) ? 'checked' : '' ?> class="mr-2">
                                                <span class="text-sm font-medium text-charcoal-600">Default</span>
                                            </label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="currencies[<?= $index ?>][default]" value="<?= ($currency['default'] ?? false) ? '1' : '0' ?>" class="default-input">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Available Sizes -->
                    <div class="bg-white border border-gray-200 overflow-hidden">
                        <div class="bg-charcoal text-white px-6 py-4">
                            <h3 class="text-lg font-semibold flex items-center">
                                <i class="bi bi-rulers mr-3 text-folly"></i>
                                Available Sizes
                            </h3>
                        </div>
                        <div class="p-6">
                            <label class="block text-sm font-medium text-charcoal-600 mb-2">Product Sizes (comma-separated)</label>
                            <input type="text" name="available_sizes" class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" value="<?= htmlspecialchars(implode(', ', $settings['available_sizes'] ?? [])) ?>" placeholder="XS, S, M, L, XL, XXL">
                            <p class="text-sm text-charcoal-400 mt-2">Enter available product sizes separated by commas</p>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="bg-white border border-gray-200 overflow-hidden">
                        <div class="bg-charcoal text-white px-6 py-4">
                            <h3 class="text-lg font-semibold flex items-center">
                                <i class="bi bi-credit-card mr-3 text-folly"></i>
                                Payment Methods
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="flex items-center p-4 border border-gray-200 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="payment_methods[]" value="stripe" <?= in_array('stripe', $settings['payment_methods'] ?? []) ? 'checked' : '' ?> class="mr-3">
                                    <div>
                                        <div class="font-medium text-charcoal">Stripe</div>
                                        <div class="text-sm text-charcoal-400">Credit/Debit Cards</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border border-gray-200 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="payment_methods[]" value="paypal" <?= in_array('paypal', $settings['payment_methods'] ?? []) ? 'checked' : '' ?> class="mr-3">
                                    <div>
                                        <div class="font-medium text-charcoal">PayPal</div>
                                        <div class="text-sm text-charcoal-400">PayPal Account</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border border-gray-200 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="payment_methods[]" value="bank_transfer" <?= in_array('bank_transfer', $settings['payment_methods'] ?? []) ? 'checked' : '' ?> class="mr-3">
                                    <div>
                                        <div class="font-medium text-charcoal">Bank Transfer</div>
                                        <div class="text-sm text-charcoal-400">Direct Bank Transfer</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="bg-white border border-gray-200 overflow-hidden">
                        <div class="bg-charcoal text-white px-6 py-4">
                            <h3 class="text-lg font-semibold flex items-center">
                                <i class="bi bi-tools mr-3 text-folly"></i>
                                System Settings
                            </h3>
                        </div>
                        <div class="p-6">
                            <label class="flex items-center p-4 border border-gray-200 hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? false) ? 'checked' : '' ?> class="mr-3">
                                <div>
                                    <div class="font-medium text-charcoal">Maintenance Mode</div>
                                    <div class="text-sm text-charcoal-400">Enable to show maintenance page to visitors</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="px-8 py-3 bg-folly text-white hover:bg-folly-600 transition-colors font-medium">
                            <i class="bi bi-check-circle mr-2"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .touch-manipulation {
            touch-action: manipulation;
        }
        
        .scale-hover:hover {
            transform: scale(1.02);
        }
        
        @media (max-width: 768px) {
            .mobile-form-stack {
                display: block;
            }
            .mobile-form-stack > div {
                width: 100% !important;
                margin-bottom: 1rem;
            }
        }
    </style>

    <script>
        let currencyIndex = <?= count($settings['currencies']) ?>;

        function addCurrency() {
            const container = document.getElementById('currencyContainer');
            const newCurrency = document.createElement('div');
            newCurrency.className = 'bg-gray-50 border border-gray-200 p-4 currency-item';
            newCurrency.innerHTML = `
                <div class="flex justify-between items-start mb-4">
                    <h4 class="font-medium text-charcoal">Currency #${currencyIndex + 1}</h4>
                    <button type="button" onclick="removeCurrency(this)" class="text-red-600 hover:text-red-800">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-charcoal-600 mb-2">Code</label>
                        <input type="text" name="currencies[${currencyIndex}][code]" class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-charcoal-600 mb-2">Symbol</label>
                        <input type="text" name="currencies[${currencyIndex}][symbol]" class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-charcoal-600 mb-2">Name</label>
                        <input type="text" name="currencies[${currencyIndex}][name]" class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" required>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center">
                            <input type="radio" name="default_currency" value="${currencyIndex}" class="mr-2">
                            <span class="text-sm font-medium text-charcoal-600">Default</span>
                        </label>
                    </div>
                </div>
                <input type="hidden" name="currencies[${currencyIndex}][default]" value="0" class="default-input">
            `;
            container.appendChild(newCurrency);
            currencyIndex++;
        }

        function removeCurrency(button) {
            const currencyItem = button.closest('.currency-item');
            currencyItem.remove();
            
            // Update currency numbers
            const items = document.querySelectorAll('.currency-item');
            items.forEach((item, index) => {
                const title = item.querySelector('h4');
                title.textContent = `Currency #${index + 1}`;
            });
        }

        // Handle default currency radio buttons
        document.addEventListener('change', function(e) {
            if (e.target.name === 'default_currency') {
                // Reset all default inputs
                document.querySelectorAll('.default-input').forEach(input => {
                    input.value = '0';
                });
                // Set the selected one
                const selectedIndex = e.target.value;
                const selectedInput = document.querySelector(`input[name="currencies[${selectedIndex}][default]"]`);
                if (selectedInput) {
                    selectedInput.value = '1';
                }
            }
        });

        // Mobile menu functions
        function openMobileMenu() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                
                // Add touch event listeners for mobile
                sidebar.addEventListener('touchstart', handleTouchStart, { passive: true });
                sidebar.addEventListener('touchmove', handleTouchMove, { passive: true });
                sidebar.addEventListener('touchend', handleTouchEnd, { passive: true });
            }
        }
        
        function closeMobileMenu() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
                
                // Remove touch event listeners
                sidebar.removeEventListener('touchstart', handleTouchStart);
                sidebar.removeEventListener('touchmove', handleTouchMove);
                sidebar.removeEventListener('touchend', handleTouchEnd);
            }
        }
        
        // Touch handling for mobile menu
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        function handleTouchStart(e) {
            startX = e.touches[0].clientX;
            isDragging = true;
        }
        
        function handleTouchMove(e) {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
            const diffX = currentX - startX;
            
            // If swiping left (negative diff) and significant distance
            if (diffX < -50) {
                closeMobileMenu();
            }
        }
        
        function handleTouchEnd() {
            isDragging = false;
            startX = 0;
            currentX = 0;
        }
        
        // Enhanced mobile menu controls
        document.addEventListener('DOMContentLoaded', function() {
            // Close menu on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMobileMenu();
                }
            });
            
            // Close menu when window is resized to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) { // lg breakpoint
                    closeMobileMenu();
                }
            });
            
            // Add touch-friendly hover effects for mobile devices
            if ('ontouchstart' in window) {
                const buttons = document.querySelectorAll('.touch-manipulation');
                buttons.forEach(button => {
                    button.addEventListener('touchstart', function() {
                        this.style.transform = 'scale(0.98)';
                    }, { passive: true });
                    
                    button.addEventListener('touchend', function() {
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }, { passive: true });
                });
            }
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 5000);
            });
        });
    </script>
</body>
</html> 