<?php
session_start();

// Simple authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: auth.php');
    exit;
}

require_once '../includes/functions.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = addAd($_POST, $_FILES);
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'danger';
                }
                break;
                
            case 'edit':
                $result = updateAd($_POST['id'], $_POST, $_FILES);
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $result = deleteAd($_POST['id']);
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'danger';
                }
                break;
                
            case 'toggle_status':
                $result = toggleAdStatus($_POST['id']);
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get all ads
$ads = getAds();

// Get all products for linking
$products = getProducts();

// Get all categories for linking
$categories = getCategories();

// Calculate statistics
$stats = [
    'total' => count($ads),
    'active' => count(array_filter($ads, fn($ad) => $ad['active'])),
    'inactive' => count(array_filter($ads, fn($ad) => !$ad['active']))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisements Management - Admin</title>
    
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
                <a href="ads.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-white bg-folly hover:bg-folly-600 transition-colors touch-manipulation">
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
                <a href="settings.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
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
                <a href="ads.php" class="flex items-center px-4 py-3 text-white bg-folly hover:bg-folly-600 transition-colors touch-manipulation">
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
                <a href="settings.php" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
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
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-charcoal truncate">Advertisements Management</h1>
                    </div>
                    <div class="hidden sm:block text-charcoal-400 text-xs lg:text-sm">
                        <i class="bi bi-calendar3 mr-1 lg:mr-2"></i>
                        <span class="hidden lg:inline"><?= date('l, F j, Y') ?></span>
                        <span class="lg:hidden"><?= date('M j, Y') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="flex-1 p-3 sm:p-4 lg:p-6 overflow-auto">
                <!-- Quick Start Guide for Empty State -->
                <?php if (empty($ads)): ?>
                <div class="bg-blue-50 border border-blue-200 p-4 lg:p-6 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex flex-col lg:flex-row lg:items-start">
                        <div class="flex-1">
                            <h3 class="text-base lg:text-lg font-semibold text-blue-900 mb-2">
                                <i class="bi bi-lightbulb mr-2"></i>Create your first advertisement
                            </h3>
                            <p class="text-blue-800 mb-2 text-sm lg:text-base">Advertisements help promote your products on the homepage. You can link them to specific products, categories, search results, or custom URLs.</p>
                            <p class="text-blue-700 text-xs lg:text-sm">
                                <i class="bi bi-info-circle mr-1"></i>
                                Tip: Use high-quality images and choose appropriate destinations to drive conversions.
                            </p>
                        </div>
                        <div class="mt-4 lg:mt-0 lg:ml-6 text-center">
                            <i class="bi bi-megaphone text-4xl lg:text-6xl text-blue-400"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages -->
                <?php if ($message): ?>
                <div class="<?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?> border p-3 lg:p-4 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="bi bi-<?= $message_type === 'success' ? 'check-circle' : 'x-circle' ?> mr-2 flex-shrink-0"></i>
                        <span class="text-sm lg:text-base"><?= htmlspecialchars($message) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Statistics Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6 mb-4 lg:mb-6">
                    <div class="bg-white border border-gray-200 p-4 lg:p-6 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs lg:text-sm font-medium text-charcoal-600">Total Ads</h3>
                                <p class="text-xl lg:text-2xl font-bold text-charcoal mt-1"><?= $stats['total'] ?></p>
                                <p class="text-xs text-charcoal-400">All advertisements</p>
                            </div>
                            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-megaphone text-lg lg:text-2xl text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-4 lg:p-6 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs lg:text-sm font-medium text-charcoal-600">Active Ads</h3>
                                <p class="text-xl lg:text-2xl font-bold text-charcoal mt-1"><?= $stats['active'] ?></p>
                                <p class="text-xs text-charcoal-400">Currently showing</p>
                            </div>
                            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-eye text-lg lg:text-2xl text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-4 lg:p-6 rounded-lg hover:shadow-md transition-shadow touch-manipulation sm:col-span-2 lg:col-span-1">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs lg:text-sm font-medium text-charcoal-600">Inactive Ads</h3>
                                <p class="text-xl lg:text-2xl font-bold text-charcoal mt-1"><?= $stats['inactive'] ?></p>
                                <p class="text-xs text-charcoal-400">Not displayed</p>
                            </div>
                            <div class="w-10 h-10 lg:w-12 lg:h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-eye-slash text-lg lg:text-2xl text-yellow-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add New Ad Form -->
                <div class="bg-white border border-gray-200 mb-4 lg:mb-6 rounded-lg">
                    <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-sm lg:text-base font-semibold text-charcoal flex items-center">
                                <i class="bi bi-plus-circle text-folly mr-2"></i>Add New Advertisement
                            </h3>
                            <button type="button" onclick="toggleAddForm()" class="px-3 lg:px-4 py-2 bg-gray-100 text-charcoal hover:bg-gray-200 transition-colors text-xs lg:text-sm touch-manipulation">
                                <i class="bi bi-chevron-down" id="toggleIcon"></i> <span class="hidden sm:inline">Toggle Form</span>
                            </button>
                        </div>
                    </div>
                    <div id="addAdForm" class="p-4 lg:p-6">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6 mb-4 lg:mb-6">
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">
                                        Title <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="title" required
                                           class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation"
                                           placeholder="Enter advertisement title">
                                </div>
                                
                                <div>
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">
                                        Destination Type <span class="text-red-500">*</span>
                                    </label>
                                    <select name="destination_type" id="add_destination_type" required onchange="handleDestinationChange('add')"
                                            class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly bg-white touch-manipulation">
                                        <option value="">Select destination type</option>
                                        <option value="product">Specific Product</option>
                                        <option value="category">Category Page</option>
                                        <option value="search">Search Results</option>
                                        <option value="custom">Custom URL</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Destination Fields (conditionally shown) -->
                            <div class="mb-4 lg:mb-6">
                                <!-- Product Selection -->
                                <div id="add_product_field" class="destination-field" style="display: none;">
                                    <label class="block text-sm font-medium text-charcoal mb-2">
                                        Select Product <span class="text-red-500">*</span>
                                    </label>
                                    <select name="product_id" 
                                            class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly bg-white">
                                        <option value="">Choose a product</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Category Selection -->
                                <div id="add_category_field" class="destination-field" style="display: none;">
                                    <label class="block text-sm font-medium text-charcoal mb-2">
                                        Select Category <span class="text-red-500">*</span>
                                    </label>
                                    <select name="category_id" 
                                            class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly bg-white">
                                        <option value="">Choose a category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Search Query -->
                                <div id="add_search_field" class="destination-field" style="display: none;">
                                    <label class="block text-sm font-medium text-charcoal mb-2">
                                        Search Query <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="search_query" 
                                           class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly"
                                           placeholder="Enter search terms (e.g., 'hoodies', 'grace collection')">
                                    <p class="text-xs text-charcoal-400 mt-2">Users will be taken to search results for this query</p>
                                </div>

                                <!-- Custom URL -->
                                <div id="add_custom_field" class="destination-field" style="display: none;">
                                    <label class="block text-sm font-medium text-charcoal mb-2">
                                        Custom URL <span class="text-red-500">*</span>
                                    </label>
                                    <input type="url" name="custom_url" 
                                           class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly"
                                           placeholder="https://example.com/custom-page">
                                    <p class="text-xs text-charcoal-400 mt-2">Enter a full URL including http:// or https://</p>
                                </div>
                            </div>
                            
                            <div class="mb-4 lg:mb-6">
                                <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">
                                    Advertisement Image <span class="text-red-500">*</span>
                                </label>
                                <input type="file" name="image" id="addImageUpload" accept="image/*" required
                                       class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation">
                                <p class="text-xs text-charcoal-400 mt-2">Upload JPG, PNG, GIF, or WebP. Max file size: 5MB</p>
                                <div id="addImagePreview" class="mt-3 hidden">
                                    <p class="text-xs lg:text-sm text-charcoal-600 mb-2">Preview:</p>
                                    <img id="addPreviewImg" src="" alt="Preview" class="w-24 lg:w-32 h-16 lg:h-20 object-cover rounded-lg border border-gray-200">
                                </div>
                            </div>
                            
                            <div class="mb-4 lg:mb-6">
                                <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">Description (Optional)</label>
                                <textarea name="description" rows="3"
                                          class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation resize-y"
                                          placeholder="Brief description of the advertisement"></textarea>
                            </div>
                            
                            <div class="mb-4 lg:mb-6">
                                <div class="flex items-center">
                                    <input type="checkbox" name="active" value="1" checked id="activeCheckbox"
                                           class="mr-3 touch-manipulation">
                                    <label for="activeCheckbox" class="text-xs lg:text-sm font-medium text-charcoal">Active (show on homepage)</label>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="w-full sm:w-auto px-4 lg:px-6 py-2 lg:py-3 bg-folly text-white hover:bg-folly-600 transition-colors font-medium text-sm lg:text-base touch-manipulation">
                                    <i class="bi bi-plus-circle mr-2"></i>Add Advertisement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Current Advertisements -->
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-sm lg:text-base font-semibold text-charcoal flex items-center">
                                <i class="bi bi-megaphone text-folly mr-2"></i>Current Advertisements
                            </h3>
                            <span class="px-2 lg:px-3 py-1 bg-gray-100 text-charcoal-600 text-xs lg:text-sm font-medium rounded-full"><?= count($ads) ?> total</span>
                        </div>
                    </div>
                    
                    <?php if (empty($ads)): ?>
                        <div class="px-4 lg:px-6 py-8 text-center text-charcoal-400">
                            <i class="bi bi-megaphone text-4xl mb-4 block"></i>
                            <h5 class="text-base lg:text-lg font-medium mb-2">No advertisements</h5>
                            <p class="text-sm lg:text-base">Get started by creating your first advertisement above.</p>
                        </div>
                    <?php else: ?>
                    
                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Preview</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Destination</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <?php foreach ($ads as $ad): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <img class="w-20 h-12 object-cover rounded-lg border border-gray-200" 
                                                 src="<?php echo getAssetUrl('images/ads/' . $ad['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($ad['title']); ?>" 
                                                 onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-charcoal"><?php echo htmlspecialchars($ad['title']); ?></div>
                                                <?php if (!empty($ad['description'])): ?>
                                                    <div class="text-sm text-charcoal-400"><?php echo htmlspecialchars($ad['description']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                            // Handle both old and new ad formats
                                            $destinationType = $ad['destination_type'] ?? 'product';
                                            $destinationInfo = getDestinationInfo($ad);
                                            ?>
                                            <div>
                                                <div class="text-sm font-medium text-charcoal flex items-center">
                                                    <?php echo $destinationInfo['icon']; ?>
                                                    <span class="ml-2"><?php echo $destinationInfo['label']; ?></span>
                                                </div>
                                                <div class="text-sm text-charcoal-400"><?php echo $destinationInfo['description']; ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-medium <?php echo $ad['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> rounded-full">
                                                <i class="bi bi-<?php echo $ad['active'] ? 'eye' : 'eye-slash'; ?> mr-1"></i>
                                                <?php echo $ad['active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <!-- Toggle Status -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
                                                    <button type="submit" class="px-3 py-1 text-sm <?php echo $ad['active'] ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200'; ?> transition-colors">
                                                        <i class="bi bi-<?php echo $ad['active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- Edit Button -->
                                                <button type="button" class="px-3 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-sm"
                                                        onclick="editAd(<?php echo htmlspecialchars(json_encode($ad)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <!-- Delete Button -->
                                                <form method="POST" class="inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this advertisement?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
                                                    <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="lg:hidden space-y-3 p-3">
                            <?php foreach ($ads as $ad): ?>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <img class="w-16 h-12 object-cover rounded border border-gray-200" 
                                             src="<?php echo getAssetUrl('images/ads/' . $ad['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($ad['title']); ?>" 
                                             onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-sm font-semibold text-charcoal truncate"><?php echo htmlspecialchars($ad['title']); ?></h4>
                                                <?php if (!empty($ad['description'])): ?>
                                                    <p class="text-xs text-charcoal-400 truncate"><?php echo htmlspecialchars($ad['description']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex space-x-1 ml-2">
                                                <!-- Toggle Status -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
                                                    <button type="submit" class="p-2 text-xs <?php echo $ad['active'] ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200'; ?> transition-colors rounded touch-manipulation">
                                                        <i class="bi bi-<?php echo $ad['active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- Edit Button -->
                                                <button type="button" class="p-2 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-xs rounded touch-manipulation"
                                                        onclick="editAd(<?php echo htmlspecialchars(json_encode($ad)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <!-- Delete Button -->
                                                <form method="POST" class="inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this advertisement?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
                                                    <button type="submit" class="p-2 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-xs rounded touch-manipulation">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 space-y-2">
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-charcoal-600">Destination:</span>
                                                <?php 
                                                $destinationType = $ad['destination_type'] ?? 'product';
                                                $destinationInfo = getDestinationInfo($ad);
                                                ?>
                                                <div class="text-xs text-charcoal">
                                                    <?php echo $destinationInfo['icon']; ?>
                                                    <span class="ml-1"><?php echo $destinationInfo['label']; ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-charcoal-600">Status:</span>
                                                <span class="px-2 py-1 text-xs font-medium <?php echo $ad['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?> rounded-full">
                                                    <i class="bi bi-<?php echo $ad['active'] ? 'eye' : 'eye-slash'; ?> mr-1"></i>
                                                    <?php echo $ad['active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                            
                                            <?php if (!empty($destinationInfo['description'])): ?>
                                            <div class="text-xs text-charcoal-400 truncate">
                                                <?php echo $destinationInfo['description']; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-2 lg:p-4" onclick="closeEditModal()">
        <div class="bg-white w-full max-w-4xl max-h-screen overflow-y-auto rounded-lg shadow-2xl" onclick="event.stopPropagation()">
            <div class="bg-charcoal text-white px-4 lg:px-6 py-3 lg:py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg lg:text-xl font-bold">
                        <i class="bi bi-pencil-square mr-2 lg:mr-3"></i>Edit Advertisement
                    </h3>
                    <button type="button" onclick="closeEditModal()" class="text-white hover:text-gray-300 text-xl lg:text-2xl p-1 touch-manipulation">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="p-4 lg:p-6 space-y-4 lg:space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="edit_title" required
                                   class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Destination Type <span class="text-red-500">*</span>
                            </label>
                            <select name="destination_type" id="edit_destination_type" required onchange="handleDestinationChange('edit')"
                                    class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly bg-white">
                                <option value="">Select destination type</option>
                                <option value="product">Specific Product</option>
                                <option value="category">Category Page</option>
                                <option value="search">Search Results</option>
                                <option value="custom">Custom URL</option>
                            </select>
                        </div>
                    </div>

                    <!-- Edit Destination Fields -->
                    <div>
                        <!-- Product Selection -->
                        <div id="edit_product_field" class="destination-field" style="display: none;">
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Select Product <span class="text-red-500">*</span>
                            </label>
                            <select name="product_id" id="edit_product_id"
                                    class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly bg-white">
                                <option value="">Choose a product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Category Selection -->
                        <div id="edit_category_field" class="destination-field" style="display: none;">
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Select Category <span class="text-red-500">*</span>
                            </label>
                            <select name="category_id" id="edit_category_id"
                                    class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly bg-white">
                                <option value="">Choose a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Search Query -->
                        <div id="edit_search_field" class="destination-field" style="display: none;">
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Search Query <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="search_query" id="edit_search_query"
                                   class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly"
                                   placeholder="Enter search terms">
                            <p class="text-xs text-charcoal-400 mt-2">Users will be taken to search results for this query</p>
                        </div>

                        <!-- Custom URL -->
                        <div id="edit_custom_field" class="destination-field" style="display: none;">
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Custom URL <span class="text-red-500">*</span>
                            </label>
                            <input type="url" name="custom_url" id="edit_custom_url"
                                   class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly"
                                   placeholder="https://example.com/custom-page">
                            <p class="text-xs text-charcoal-400 mt-2">Enter a full URL including http:// or https://</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-charcoal mb-2">Advertisement Image</label>
                        <input type="file" name="image" id="edit_image" accept="image/*"
                               class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly">
                        <p class="text-xs text-charcoal-400 mt-2">Leave blank to keep current image</p>
                        <div id="current_image_preview" class="mt-3"></div>
                        <div id="editImagePreview" class="mt-3 hidden">
                            <p class="text-sm text-charcoal-600 mb-2">New image preview:</p>
                            <img id="editPreviewImg" src="" alt="Preview" class="w-32 h-20 object-cover rounded-lg border border-gray-200">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-charcoal mb-2">Description (Optional)</label>
                        <textarea name="description" id="edit_description" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly"></textarea>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="active" id="edit_active" value="1" class="mr-3">
                        <label for="edit_active" class="text-sm font-medium text-charcoal">Active (show on homepage)</label>
                    </div>
                </div>
                
                <div class="px-4 lg:px-6 py-3 lg:py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex flex-col space-y-3 lg:space-y-0 lg:flex-row lg:justify-between lg:items-center">
                        <p class="text-charcoal-400 text-xs lg:text-sm">All required fields (*) must be filled</p>
                        <div class="flex flex-col space-y-2 lg:space-y-0 lg:flex-row lg:space-x-3">
                            <button type="button" onclick="closeEditModal()" class="w-full lg:w-auto px-4 lg:px-6 py-2 border border-gray-300 bg-white text-charcoal hover:bg-gray-50 transition-colors touch-manipulation">
                                Cancel
                            </button>
                            <button type="submit" class="w-full lg:w-auto px-4 lg:px-6 py-2 bg-folly text-white hover:bg-folly-600 transition-colors font-medium touch-manipulation">
                                Update Advertisement
                            </button>
                        </div>
                    </div>
                </div>
            </form>
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
            .mobile-table-card {
                display: block;
                border: 1px solid #e5e7eb;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
                background: white;
                padding: 1rem;
            }
        }
    </style>

    <script>
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
                    closeEditModal();
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
        });
        
        // Destination type handling
        function handleDestinationChange(formType) {
            const destinationType = document.getElementById(formType + '_destination_type').value;
            const fields = document.querySelectorAll('#' + formType + 'AdForm .destination-field, #editModal .destination-field');
            
            // Hide all destination fields
            fields.forEach(field => {
                field.style.display = 'none';
                // Clear required attributes
                const inputs = field.querySelectorAll('input, select');
                inputs.forEach(input => input.removeAttribute('required'));
            });
            
            // Show appropriate field and set required
            if (destinationType) {
                const targetField = document.getElementById(formType + '_' + destinationType + '_field');
                if (targetField) {
                    targetField.style.display = 'block';
                    const inputs = targetField.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        if (input.name && input.name !== 'description') {
                            input.setAttribute('required', 'required');
                        }
                    });
                }
            }
        }

        // Modal functions
        function editAd(ad) {
            document.getElementById('edit_id').value = ad.id;
            document.getElementById('edit_title').value = ad.title;
            document.getElementById('edit_description').value = ad.description || '';
            document.getElementById('edit_active').checked = ad.active == 1;
            
            // Handle destination type - check for both old and new format
            const destinationType = ad.destination_type || 'product';
            document.getElementById('edit_destination_type').value = destinationType;
            
            // Clear all destination fields first
            document.getElementById('edit_product_id').value = '';
            document.getElementById('edit_category_id').value = '';
            document.getElementById('edit_search_query').value = '';
            document.getElementById('edit_custom_url').value = '';
            
            // Set appropriate field value based on destination type
            switch (destinationType) {
                case 'product':
                    document.getElementById('edit_product_id').value = ad.product_id || '';
                    break;
                case 'category':
                    document.getElementById('edit_category_id').value = ad.category_id || '';
                    break;
                case 'search':
                    document.getElementById('edit_search_query').value = ad.search_query || '';
                    break;
                case 'custom':
                    document.getElementById('edit_custom_url').value = ad.custom_url || '';
                    break;
            }
            
            // Show appropriate field
            handleDestinationChange('edit');
            
            // Show current image
            const imagePreview = document.getElementById('current_image_preview');
            imagePreview.innerHTML = `
                <div class="text-center">
                    <img src="<?php echo getAssetUrl('images/ads/'); ?>${ad.image}" 
                         alt="Current image" 
                         class="w-32 h-20 object-cover rounded-lg border border-gray-200 mx-auto"
                         onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'">
                    <p class="text-charcoal-600 text-sm mt-2">Current image</p>
                </div>
            `;
            
            openEditModal();
        }

        function openEditModal() {
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = '';
                
                // Clear preview
                const preview = document.getElementById('editImagePreview');
                if (preview) preview.classList.add('hidden');
            }
        }

        // Toggle add form
        function toggleAddForm() {
            const form = document.getElementById('addAdForm');
            const icon = document.getElementById('toggleIcon');
            
            if (form.style.display === 'none') {
                form.style.display = 'block';
                icon.className = 'bi bi-chevron-down';
            } else {
                form.style.display = 'none';
                icon.className = 'bi bi-chevron-up';
            }
        }

        // Image preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add form image preview
            const addImageInput = document.getElementById('addImageUpload');
            if (addImageInput) {
                addImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('addPreviewImg').src = e.target.result;
                            document.getElementById('addImagePreview').classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        document.getElementById('addImagePreview').classList.add('hidden');
                    }
                });
            }

            // Edit form image preview
            const editImageInput = document.getElementById('edit_image');
            if (editImageInput) {
                editImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('editPreviewImg').src = e.target.result;
                            document.getElementById('editImagePreview').classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        document.getElementById('editImagePreview').classList.add('hidden');
                    }
                });
            }

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 5000);
            });
        });
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>
</html> 