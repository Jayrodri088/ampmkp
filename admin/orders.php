<?php
session_start();

// Simple authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: auth.php');
    exit;
}

require_once '../includes/functions.php';

// Load orders data
$orders = readJsonFile('orders.json');

// Handle order actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $orderId = $_POST['order_id'] ?? '';
                $newStatus = $_POST['status'] ?? '';
                
                if ($orderId && $newStatus) {
                    foreach ($orders as &$order) {
                        if ($order['id'] === $orderId) {
                            $order['status'] = $newStatus;
                            $order['updated_at'] = date('Y-m-d H:i:s');
                            break;
                        }
                    }
                    
                    if (writeJsonFile('orders.json', $orders)) {
                        $message = 'Order status updated successfully';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to update order status';
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'update_payment_status':
                $orderId = $_POST['order_id'] ?? '';
                $newPaymentStatus = $_POST['payment_status'] ?? '';
                
                if ($orderId && $newPaymentStatus) {
                    foreach ($orders as &$order) {
                        if ($order['id'] === $orderId) {
                            $order['payment_status'] = $newPaymentStatus;
                            $order['updated_at'] = date('Y-m-d H:i:s');
                            break;
                        }
                    }
                    
                    if (writeJsonFile('orders.json', $orders)) {
                        $message = 'Payment status updated successfully';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to update payment status';
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'delete_order':
                $orderId = $_POST['order_id'] ?? '';
                
                if ($orderId) {
                    $orders = array_filter($orders, fn($order) => $order['id'] !== $orderId);
                    $orders = array_values($orders); // Re-index array
                    
                    if (writeJsonFile('orders.json', $orders)) {
                        $message = 'Order deleted successfully';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to delete order';
                        $message_type = 'danger';
                    }
                }
                break;
        }
    }
}

// Filtering and sorting
$filter_status = $_GET['status'] ?? 'all';
$filter_payment = $_GET['payment'] ?? 'all';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';

// Apply filters
$filtered_orders = $orders;

if ($filter_status !== 'all') {
    $filtered_orders = array_filter($filtered_orders, fn($order) => ($order['status'] ?? 'pending') === $filter_status);
}

if ($filter_payment !== 'all') {
    $filtered_orders = array_filter($filtered_orders, fn($order) => ($order['payment_status'] ?? 'pending') === $filter_payment);
}

if (!empty($search)) {
    $filtered_orders = array_filter($filtered_orders, function($order) use ($search) {
        $searchTerm = strtolower($search);
        return strpos(strtolower($order['id'] ?? ''), $searchTerm) !== false ||
               strpos(strtolower($order['customer_name'] ?? ''), $searchTerm) !== false ||
               strpos(strtolower($order['customer_email'] ?? ''), $searchTerm) !== false;
    });
}

// Apply sorting
usort($filtered_orders, function($a, $b) use ($sort) {
    switch ($sort) {
        case 'date_asc':
            return strtotime($a['date'] ?? $a['created_at'] ?? '0') <=> strtotime($b['date'] ?? $b['created_at'] ?? '0');
        case 'date_desc':
            return strtotime($b['date'] ?? $b['created_at'] ?? '0') <=> strtotime($a['date'] ?? $a['created_at'] ?? '0');
        case 'amount_asc':
            return ($a['total'] ?? 0) <=> ($b['total'] ?? 0);
        case 'amount_desc':
            return ($b['total'] ?? 0) <=> ($a['total'] ?? 0);
        case 'status':
            return ($a['status'] ?? 'pending') <=> ($b['status'] ?? 'pending');
        default:
            return strtotime($b['date'] ?? $b['created_at'] ?? '0') <=> strtotime($a['date'] ?? $a['created_at'] ?? '0');
    }
});

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$total_orders = count($filtered_orders);
$total_pages = ceil($total_orders / $per_page);
$offset = ($page - 1) * $per_page;
$paged_orders = array_slice($filtered_orders, $offset, $per_page);

// Calculate statistics
$stats = [
    'total' => count($orders),
    'pending' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'pending')),
    'processing' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'processing')),
    'completed' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'completed')),
    'cancelled' => count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'cancelled')),
    'total_revenue' => array_sum(array_map(fn($o) => $o['total'] ?? 0, array_filter($orders, fn($o) => ($o['payment_status'] ?? 'pending') === 'completed')))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Admin</title>
    
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

    <style>
        .touch-manipulation {
            touch-action: manipulation;
        }
        
        .scale-hover:hover {
            transform: scale(1.02);
        }
        
        /* Enhanced mobile touch feedback */
        @media (max-width: 1279px) {
            .mobile-card {
                transition: all 0.2s ease;
            }
            
            .mobile-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            
            .mobile-card:active {
                transform: translateY(0);
            }
        }
        
        /* Better scrollable area styling */
        .scrollable-items {
            scrollbar-width: thin;
            scrollbar-color: #d1d5db #f3f4f6;
        }
        
        .scrollable-items::-webkit-scrollbar {
            width: 4px;
        }
        
        .scrollable-items::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 2px;
        }
        
        .scrollable-items::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }
    </style>
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
                <a href="orders.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-white bg-folly hover:bg-folly-600 transition-colors touch-manipulation">
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
                <a href="ads.php" class="flex items-center px-4 py-3 text-charcoal-200 hover:text-white hover:bg-charcoal-700 transition-colors touch-manipulation">
                    <i class="bi bi-megaphone mr-3 w-5 text-center"></i>
                    Advertisements
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 text-white bg-folly hover:bg-folly-600 transition-colors touch-manipulation">
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
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-charcoal truncate">Orders Management</h1>
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
                <?php if (empty($orders)): ?>
                <div class="bg-blue-50 border border-blue-200 p-4 lg:p-6 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex flex-col lg:flex-row lg:items-start">
                        <div class="flex-1">
                            <h3 class="text-base lg:text-lg font-semibold text-blue-900 mb-2">
                                <i class="bi bi-lightbulb mr-2"></i>No orders yet
                            </h3>
                            <p class="text-blue-800 mb-2 text-sm lg:text-base">Orders will appear here when customers make purchases. This dashboard helps you manage and track all orders.</p>
                            <p class="text-blue-700 text-xs lg:text-sm">
                                <i class="bi bi-info-circle mr-1"></i>
                                Tip: You can update order status, payment status, and view detailed order information.
                            </p>
                        </div>
                        <div class="mt-4 lg:mt-0 lg:ml-6 text-center">
                            <i class="bi bi-receipt text-4xl lg:text-6xl text-blue-400"></i>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 lg:gap-4 mb-4 lg:mb-6">
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Total Orders</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['total'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-receipt text-blue-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Pending</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['pending'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-clock text-yellow-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Processing</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['processing'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-gear text-orange-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Completed</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['completed'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-check-circle text-green-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Cancelled</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['cancelled'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-x-circle text-red-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation xl:col-span-2">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Revenue</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal">£<?= number_format($stats['total_revenue'], 2) ?></p>
                            </div>
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-currency-pound text-purple-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="bg-white border border-gray-200 mb-4 lg:mb-6 rounded-lg">
                    <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <h3 class="text-sm lg:text-base font-semibold text-charcoal">Filter & Search Orders</h3>
                    </div>
                    <div class="p-4 lg:p-6">
                        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 lg:gap-4">
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Order Status</label>
                                <select name="status" class="w-full px-3 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" onchange="this.form.submit()">
                                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                                    <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $filter_status === 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Payment Status</label>
                                <select name="payment" class="w-full px-3 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" onchange="this.form.submit()">
                                    <option value="all" <?= $filter_payment === 'all' ? 'selected' : '' ?>>All Payment</option>
                                    <option value="pending" <?= $filter_payment === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="completed" <?= $filter_payment === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="failed" <?= $filter_payment === 'failed' ? 'selected' : '' ?>>Failed</option>
                                    <option value="refunded" <?= $filter_payment === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Sort By</label>
                                <select name="sort" class="w-full px-3 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" onchange="this.form.submit()">
                                    <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
                                    <option value="amount_desc" <?= $sort === 'amount_desc' ? 'selected' : '' ?>>Amount (High-Low)</option>
                                    <option value="amount_asc" <?= $sort === 'amount_asc' ? 'selected' : '' ?>>Amount (Low-High)</option>
                                    <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Status</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-2">
                                <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Search Orders</label>
                                <div class="flex">
                                    <input type="text" name="search" class="flex-1 px-3 py-2 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" placeholder="Search by order ID, customer name, or email" value="<?= htmlspecialchars($search) ?>">
                                    <button type="submit" class="ml-2 px-3 lg:px-4 py-2 bg-folly text-white hover:bg-folly-600 transition-colors touch-manipulation">
                                        <i class="bi bi-search text-sm lg:text-base"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-white border border-gray-200 rounded-lg lg:rounded-xl">
                    <div class="px-3 sm:px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                            <h3 class="text-sm lg:text-base font-semibold text-charcoal">Orders List</h3>
                            <span class="px-2 lg:px-3 py-1 bg-gray-100 text-charcoal-600 text-xs lg:text-sm font-medium rounded-full self-start sm:self-auto">
                                Showing <?= count($paged_orders) ?> of <?= $total_orders ?> orders
                            </span>
                        </div>
                    </div>
                    
                    <?php if (empty($paged_orders)): ?>
                        <div class="px-3 sm:px-6 py-6 lg:py-8 text-center text-charcoal-400">
                            <i class="bi bi-receipt text-3xl lg:text-4xl mb-3 lg:mb-4 block"></i>
                            <h5 class="text-base lg:text-lg font-medium mb-2">No orders found</h5>
                            <p class="text-sm lg:text-base">No orders match your current filters.</p>
                        </div>
                    <?php else: ?>
                        <!-- Desktop Table View (hidden on mobile/tablet) -->
                        <div class="hidden xl:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Items</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Payment</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <?php foreach ($paged_orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-charcoal">#<?= htmlspecialchars($order['id']) ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-charcoal"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></div>
                                                <div class="text-sm text-charcoal-400"><?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if (isset($order['items']) && is_array($order['items'])): ?>
                                                <div class="text-sm text-charcoal">
                                                    <?= count($order['items']) ?> item(s)
                                                    <div class="text-xs text-charcoal-400 mt-1 max-h-16 overflow-y-auto">
                                                        <?php foreach ($order['items'] as $item): ?>
                                                            <div><?= htmlspecialchars($item['name'] ?? 'Item') ?> (<?= $item['quantity'] ?? 1 ?>x)</div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-charcoal-400">No items</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-charcoal">
                                                £<?= number_format($order['total'] ?? 0, 2) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $status = $order['status'] ?? 'pending';
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'processing' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800'
                                            ];
                                            $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium <?= $statusColor ?> rounded-full">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $paymentStatus = $order['payment_status'] ?? 'pending';
                                            $paymentColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'failed' => 'bg-red-100 text-red-800',
                                                'refunded' => 'bg-gray-100 text-gray-800'
                                            ];
                                            $paymentColor = $paymentColors[$paymentStatus] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium <?= $paymentColor ?> rounded-full">
                                                <?= ucfirst($paymentStatus) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $paymentMethod = $order['payment_method'] ?? 'unknown';
                                            $methodDisplay = '';
                                            $methodIcon = '';
                                            
                                            switch ($paymentMethod) {
                                                case 'card':
                                                    $methodDisplay = 'Stripe';
                                                    $methodIcon = '💳';
                                                    break;
                                                case 'bank_transfer':
                                                    $methodDisplay = 'Bank Transfer';
                                                    $methodIcon = '🏦';
                                                    break;
                                                case 'paypal':
                                                    $methodDisplay = 'PayPal';
                                                    $methodIcon = '💙';
                                                    break;
                                                case 'espees':
                                                    $methodDisplay = 'Espees';
                                                    $methodIcon = '💰';
                                                    break;
                                                default:
                                                    $methodDisplay = 'Unknown';
                                                    $methodIcon = '❓';
                                            }
                                            ?>
                                            <div class="flex items-center">
                                                <span class="mr-1"><?= $methodIcon ?></span>
                                                <span class="text-sm text-charcoal"><?= $methodDisplay ?></span>
                                                <?php if ($paymentMethod === 'card' && isset($order['stripe_payment_intent'])): ?>
                                                    <button onclick="viewStripePayment('<?= htmlspecialchars($order['stripe_payment_intent']) ?>')" 
                                                            class="ml-2 text-blue-600 hover:text-blue-800 text-xs" 
                                                            title="View in Stripe Dashboard">
                                                        <i class="bi bi-external-link"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-charcoal">
                                                <?= date('M j, Y', strtotime($order['date'] ?? $order['created_at'] ?? 'now')) ?>
                                            </div>
                                            <div class="text-xs text-charcoal-400">
                                                <?= date('g:i A', strtotime($order['date'] ?? $order['created_at'] ?? 'now')) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <button type="button" onclick="viewOrder('<?= htmlspecialchars(json_encode($order)) ?>')" class="px-3 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-sm rounded touch-manipulation">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" onclick="editOrder('<?= htmlspecialchars(json_encode($order)) ?>')" class="px-3 py-1 bg-green-100 text-green-700 hover:bg-green-200 transition-colors text-sm rounded touch-manipulation">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this order?')">
                                                    <input type="hidden" name="action" value="delete_order">
                                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                                    <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-sm rounded touch-manipulation">
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

                        <!-- Mobile/Tablet Card View (visible on mobile/tablet) -->
                        <div class="xl:hidden">
                            <div class="divide-y divide-gray-100">
                                <?php foreach ($paged_orders as $order): ?>
                                    <?php
                                    $status = $order['status'] ?? 'pending';
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                    
                                    $paymentStatus = $order['payment_status'] ?? 'pending';
                                    $paymentColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'refunded' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $paymentColor = $paymentColors[$paymentStatus] ?? 'bg-gray-100 text-gray-800';
                                    
                                    $paymentMethod = $order['payment_method'] ?? 'unknown';
                                    $methodDisplay = '';
                                    $methodIcon = '';
                                    
                                    switch ($paymentMethod) {
                                        case 'card':
                                            $methodDisplay = 'Stripe';
                                            $methodIcon = '💳';
                                            break;
                                        case 'bank_transfer':
                                            $methodDisplay = 'Bank Transfer';
                                            $methodIcon = '🏦';
                                            break;
                                        case 'paypal':
                                            $methodDisplay = 'PayPal';
                                            $methodIcon = '💙';
                                            break;
                                        case 'espees':
                                            $methodDisplay = 'Espees';
                                            $methodIcon = '💰';
                                            break;
                                        default:
                                            $methodDisplay = 'Unknown';
                                            $methodIcon = '❓';
                                    }
                                    ?>
                                    <div class="mobile-card p-3 sm:p-4 lg:p-5 hover:bg-gray-50 transition-colors">
                                        <!-- Header Row -->
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="text-sm lg:text-base font-semibold text-charcoal">
                                                        #<?= htmlspecialchars($order['id']) ?>
                                                    </h4>
                                                    <span class="px-2 py-1 text-xs font-medium <?= $statusColor ?> rounded-full">
                                                        <?= ucfirst($status) ?>
                                                    </span>
                                                </div>
                                                <div class="text-xs lg:text-sm text-charcoal-400">
                                                    <?= date('M j, Y \a\t g:i A', strtotime($order['date'] ?? $order['created_at'] ?? 'now')) ?>
                                                </div>
                                            </div>
                                            <div class="text-right sm:ml-4 flex-shrink-0">
                                                <div class="text-base lg:text-lg font-bold text-charcoal">
                                                    £<?= number_format($order['total'] ?? 0, 2) ?>
                                                </div>
                                                <span class="px-2 py-1 text-xs font-medium <?= $paymentColor ?> rounded-full">
                                                    <?= ucfirst($paymentStatus) ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Customer Info -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                            <div>
                                                <div class="text-xs lg:text-sm font-medium text-charcoal-600 mb-1">Customer</div>
                                                <div class="text-sm lg:text-base font-medium text-charcoal">
                                                    <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?>
                                                </div>
                                                <div class="text-xs lg:text-sm text-charcoal-400 truncate">
                                                    <?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-xs lg:text-sm font-medium text-charcoal-600 mb-1">Payment Method</div>
                                                <div class="flex items-center text-sm lg:text-base text-charcoal">
                                                    <span class="mr-2"><?= $methodIcon ?></span>
                                                    <span><?= $methodDisplay ?></span>
                                                    <?php if ($paymentMethod === 'card' && isset($order['stripe_payment_intent'])): ?>
                                                        <button onclick="viewStripePayment('<?= htmlspecialchars($order['stripe_payment_intent']) ?>')" 
                                                                class="ml-2 text-blue-600 hover:text-blue-800 text-xs touch-manipulation" 
                                                                title="View in Stripe Dashboard">
                                                            <i class="bi bi-external-link"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Items Info -->
                                        <div class="mb-3">
                                            <div class="text-xs lg:text-sm font-medium text-charcoal-600 mb-1">Items</div>
                                            <?php if (isset($order['items']) && is_array($order['items'])): ?>
                                                <div class="text-sm lg:text-base text-charcoal">
                                                    <?= count($order['items']) ?> item(s)
                                                </div>
                                                <div class="scrollable-items text-xs lg:text-sm text-charcoal-400 mt-1 space-y-1 max-h-20 overflow-y-auto">
                                                    <?php foreach ($order['items'] as $item): ?>
                                                        <div class="flex justify-between">
                                                            <span><?= htmlspecialchars($item['name'] ?? 'Item') ?></span>
                                                            <span><?= $item['quantity'] ?? 1 ?>x</span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-sm lg:text-base text-charcoal-400">No items</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                                            <button type="button" 
                                                    onclick="viewOrder('<?= htmlspecialchars(json_encode($order)) ?>')" 
                                                    class="flex-1 sm:flex-initial px-3 sm:px-4 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-xs lg:text-sm font-medium rounded touch-manipulation">
                                                <i class="bi bi-eye mr-1"></i>
                                                <span class="hidden sm:inline">View</span>
                                            </button>
                                            <button type="button" 
                                                    onclick="editOrder('<?= htmlspecialchars(json_encode($order)) ?>')" 
                                                    class="flex-1 sm:flex-initial px-3 sm:px-4 py-2 bg-green-100 text-green-700 hover:bg-green-200 transition-colors text-xs lg:text-sm font-medium rounded touch-manipulation">
                                                <i class="bi bi-pencil mr-1"></i>
                                                <span class="hidden sm:inline">Edit</span>
                                            </button>
                                            <form method="POST" class="flex-1 sm:flex-initial" onsubmit="return confirm('Are you sure you want to delete this order?')">
                                                <input type="hidden" name="action" value="delete_order">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                                <button type="submit" 
                                                        class="w-full px-3 sm:px-4 py-2 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-xs lg:text-sm font-medium rounded touch-manipulation">
                                                    <i class="bi bi-trash mr-1"></i>
                                                    <span class="hidden sm:inline">Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                    
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-3 sm:px-4 lg:px-6 py-3 lg:py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-xs lg:text-sm text-charcoal-600 text-center sm:text-left">
                            Showing <?= ($page - 1) * $per_page + 1 ?> to <?= min($page * $per_page, $total_orders) ?> of <?= $total_orders ?> results
                        </div>
                        <div class="flex justify-center sm:justify-end">
                            <div class="flex space-x-1 lg:space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&status=<?= $filter_status ?>&payment=<?= $filter_payment ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" 
                                       class="px-2 lg:px-3 py-2 border border-gray-300 text-xs lg:text-sm text-charcoal hover:bg-gray-50 rounded touch-manipulation">
                                        <i class="bi bi-chevron-left sm:hidden"></i>
                                        <span class="hidden sm:inline">Previous</span>
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Mobile: Show only current page and total -->
                                <div class="sm:hidden flex items-center px-3 py-2 border border-gray-300 text-xs bg-white">
                                    <?= $page ?> / <?= $total_pages ?>
                                </div>
                                
                                <!-- Desktop: Show page numbers -->
                                <div class="hidden sm:flex space-x-1 lg:space-x-2">
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?= $i ?>&status=<?= $filter_status ?>&payment=<?= $filter_payment ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" 
                                           class="px-2 lg:px-3 py-2 border border-gray-300 text-xs lg:text-sm <?= $i === $page ? 'bg-folly text-white border-folly' : 'text-charcoal hover:bg-gray-50' ?> rounded touch-manipulation"><?= $i ?></a>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?>&status=<?= $filter_status ?>&payment=<?= $filter_payment ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" 
                                       class="px-2 lg:px-3 py-2 border border-gray-300 text-xs lg:text-sm text-charcoal hover:bg-gray-50 rounded touch-manipulation">
                                        <i class="bi bi-chevron-right sm:hidden"></i>
                                        <span class="hidden sm:inline">Next</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-2 sm:p-4" onclick="closeViewModal()">
        <div class="bg-white w-full max-w-4xl max-h-screen overflow-y-auto rounded-lg lg:rounded-xl shadow-2xl" onclick="event.stopPropagation()">
            <div class="bg-charcoal text-white px-3 sm:px-4 lg:px-6 py-3 lg:py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-base lg:text-xl font-bold">
                        <i class="bi bi-receipt mr-2 lg:mr-3"></i>
                        <span class="hidden sm:inline">Order Details</span>
                        <span class="sm:hidden">Details</span>
                    </h3>
                    <button type="button" onclick="closeViewModal()" class="text-white hover:text-gray-300 text-xl lg:text-2xl p-1 touch-manipulation">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            <div id="viewModalContent" class="p-3 sm:p-4 lg:p-6">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-2 sm:p-4" onclick="closeEditModal()">
        <div class="bg-white w-full max-w-lg sm:max-w-2xl max-h-screen overflow-y-auto rounded-lg lg:rounded-xl shadow-2xl" onclick="event.stopPropagation()">
            <div class="bg-charcoal text-white px-3 sm:px-4 lg:px-6 py-3 lg:py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-base lg:text-xl font-bold">
                        <i class="bi bi-pencil-square mr-2 lg:mr-3"></i>
                        <span class="hidden sm:inline">Edit Order</span>
                        <span class="sm:hidden">Edit</span>
                    </h3>
                    <button type="button" onclick="closeEditModal()" class="text-white hover:text-gray-300 text-xl lg:text-2xl p-1 touch-manipulation">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            <form id="editForm" method="POST">
                <div class="p-3 sm:p-4 lg:p-6 space-y-4 lg:space-y-6">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="edit_order_id">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
                        <div>
                            <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">Order Status</label>
                            <select name="status" id="edit_status" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly bg-white rounded touch-manipulation">
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">Payment Status</label>
                            <select name="payment_status" id="edit_payment_status" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly bg-white rounded touch-manipulation">
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="px-3 sm:px-4 lg:px-6 py-3 lg:py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                        <button type="button" onclick="closeEditModal()" class="order-2 sm:order-1 px-4 lg:px-6 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal hover:bg-gray-50 transition-colors rounded touch-manipulation">
                            Cancel
                        </button>
                        <button type="submit" class="order-1 sm:order-2 px-4 lg:px-6 py-2 text-sm lg:text-base bg-folly text-white hover:bg-folly-600 transition-colors font-medium rounded touch-manipulation">
                            Update Order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function viewOrder(orderJson) {
            const order = JSON.parse(orderJson);
            const content = document.getElementById('viewModalContent');
            
            let itemsHtml = '';
            if (order.items && Array.isArray(order.items)) {
                itemsHtml = order.items.map(item => `
                    <div class="flex flex-col sm:flex-row sm:justify-between py-2 lg:py-3 border-b border-gray-100 last:border-b-0">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm lg:text-base font-medium text-charcoal truncate">${item.name || 'Item'}</div>
                            <div class="text-xs lg:text-sm text-gray-500 mt-1">Quantity: ${item.quantity || 1}</div>
                        </div>
                        <div class="text-sm lg:text-base font-medium text-charcoal mt-1 sm:mt-0 sm:ml-4 flex-shrink-0">
                            £${((item.price || 0) * (item.quantity || 1)).toFixed(2)}
                        </div>
                    </div>
                `).join('');
                itemsHtml = `<div class="p-3 lg:p-4">${itemsHtml}</div>`;
            }
            
            // Build payment details section
            let paymentDetailsHtml = '';
            if (order.payment_method === 'card' && order.stripe_payment_intent) {
                paymentDetailsHtml = `
                    <div class="mt-4 lg:mt-6">
                        <h4 class="text-sm lg:text-base font-semibold text-charcoal mb-3 lg:mb-4">💳 Payment Details</h4>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 lg:p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:gap-4">
                                <div class="space-y-2">
                                    <div class="text-sm lg:text-base"><strong>Payment Method:</strong> Stripe (Credit Card)</div>
                                    <div class="text-sm lg:text-base"><strong>Payment Intent ID:</strong> 
                                        <code class="bg-gray-100 px-2 py-1 rounded text-xs lg:text-sm break-all">${order.stripe_payment_intent}</code>
                                    </div>
                                    <div class="text-sm lg:text-base"><strong>Payment Status:</strong> 
                                        <span class="px-2 py-1 text-xs rounded-full ${order.payment_status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">${order.payment_status || 'pending'}</span>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <div class="text-sm lg:text-base"><strong>Subtotal:</strong> £${(order.subtotal || 0).toFixed(2)}</div>
                                    <div class="text-sm lg:text-base"><strong>Shipping:</strong> £${(order.shipping_cost || 0).toFixed(2)}</div>
                                    <div class="text-sm lg:text-base"><strong>Total Charged:</strong> <span class="font-bold text-base lg:text-lg">£${(order.total || 0).toFixed(2)}</span></div>
                                </div>
                            </div>
                            <div class="mt-3 lg:mt-4 flex flex-col sm:flex-row gap-2 sm:gap-3">
                                <button onclick="viewStripePayment('${order.stripe_payment_intent}')" 
                                        class="flex-1 sm:flex-initial px-3 lg:px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors text-xs lg:text-sm font-medium touch-manipulation">
                                    <i class="bi bi-receipt mr-1"></i> 
                                    <span class="hidden sm:inline">View Stripe Receipt</span>
                                    <span class="sm:hidden">Stripe Receipt</span>
                                </button>
                                <button onclick="copyPaymentId('${order.stripe_payment_intent}')" 
                                        class="flex-1 sm:flex-initial px-3 lg:px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors text-xs lg:text-sm font-medium touch-manipulation">
                                    <i class="bi bi-clipboard mr-1"></i> 
                                    <span class="hidden sm:inline">Copy Payment ID</span>
                                    <span class="sm:hidden">Copy ID</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            } else if (order.payment_method) {
                paymentDetailsHtml = `
                    <div class="mt-4 lg:mt-6">
                        <h4 class="text-sm lg:text-base font-semibold text-charcoal mb-3 lg:mb-4">💰 Payment Details</h4>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 lg:p-4">
                            <div class="text-sm lg:text-base"><strong>Payment Method:</strong> ${order.payment_method === 'bank_transfer' ? 'Bank Transfer' : order.payment_method === 'paypal' ? 'PayPal' : order.payment_method === 'espees' ? 'Espees' : 'Unknown'}</div>
                            <div class="text-sm lg:text-base mt-2"><strong>Payment Status:</strong> 
                                <span class="px-2 py-1 text-xs rounded-full ${order.payment_status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">${order.payment_status || 'pending'}</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Build shipping address section
            let shippingHtml = '';
            if (order.shipping_address) {
                const addr = order.shipping_address;
                const addressParts = [
                    addr.line1,
                    addr.line2,
                    addr.city,
                    addr.postcode,
                    addr.country
                ].filter(part => part && part.trim());
                
                shippingHtml = `
                    <div class="mt-4 lg:mt-6">
                        <h4 class="text-sm lg:text-base font-semibold text-charcoal mb-3 lg:mb-4">📦 Shipping Address</h4>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 lg:p-4">
                            <div class="text-sm lg:text-base whitespace-pre-line">${addressParts.join('\n')}</div>
                        </div>
                    </div>
                `;
            }

            content.innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                    <div>
                        <h4 class="text-sm lg:text-base font-semibold text-charcoal mb-3 lg:mb-4">📋 Order Information</h4>
                        <div class="space-y-2 lg:space-y-3">
                            <div class="text-sm lg:text-base"><strong>Order ID:</strong> #${order.id}</div>
                            <div class="text-sm lg:text-base"><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full bg-gray-100">${order.status || 'pending'}</span></div>
                            <div class="text-sm lg:text-base"><strong>Payment Status:</strong> <span class="px-2 py-1 text-xs rounded-full bg-gray-100">${order.payment_status || 'pending'}</span></div>
                            <div class="text-sm lg:text-base"><strong>Total:</strong> <span class="font-semibold">£${(order.total || 0).toFixed(2)}</span></div>
                            <div class="text-sm lg:text-base"><strong>Date:</strong> ${new Date(order.date || order.created_at).toLocaleDateString('en-GB', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}</div>
                            ${order.notes ? `<div class="text-sm lg:text-base"><strong>Notes:</strong> ${order.notes}</div>` : ''}
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm lg:text-base font-semibold text-charcoal mb-3 lg:mb-4">👤 Customer Information</h4>
                        <div class="space-y-2 lg:space-y-3">
                            <div class="text-sm lg:text-base"><strong>Name:</strong> ${order.customer_name || 'N/A'}</div>
                            <div class="text-sm lg:text-base"><strong>Email:</strong> <span class="break-all">${order.customer_email || 'N/A'}</span></div>
                            <div class="text-sm lg:text-base"><strong>Phone:</strong> ${order.customer_phone || 'N/A'}</div>
                        </div>
                    </div>
                </div>
                
                ${paymentDetailsHtml}
                ${shippingHtml}
                
                <div class="mt-4 lg:mt-6">
                    <h4 class="text-sm lg:text-base font-semibold text-charcoal mb-3 lg:mb-4">🛍️ Order Items</h4>
                    <div class="border border-gray-200 rounded-lg">
                        ${itemsHtml || '<div class="p-3 lg:p-4 text-center text-gray-500 text-sm lg:text-base">No items found</div>'}
                    </div>
                </div>
            `;
            
            openViewModal();
        }

        function editOrder(orderJson) {
            const order = JSON.parse(orderJson);
            document.getElementById('edit_order_id').value = order.id;
            document.getElementById('edit_status').value = order.status || 'pending';
            document.getElementById('edit_payment_status').value = order.payment_status || 'pending';
            openEditModal();
        }

        function openViewModal() {
            const modal = document.getElementById('viewModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeViewModal() {
            const modal = document.getElementById('viewModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function openEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeViewModal();
                closeEditModal();
            }
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 5000);
            });
        });

        // Stripe payment functions
        function viewStripePayment(paymentIntentId) {
            // Open Stripe dashboard payment page in new tab
            const stripeUrl = `https://dashboard.stripe.com/payments/${paymentIntentId}`;
            window.open(stripeUrl, '_blank');
        }

        function copyPaymentId(paymentIntentId) {
            // Copy payment intent ID to clipboard
            navigator.clipboard.writeText(paymentIntentId).then(function() {
                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
                notification.textContent = 'Payment ID copied to clipboard!';
                document.body.appendChild(notification);
                
                // Remove notification after 3 seconds
                setTimeout(function() {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 3000);
            }).catch(function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = paymentIntentId;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Show success notification
                alert('Payment ID copied to clipboard: ' + paymentIntentId);
            });
        }

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
                    closeViewModal();
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
    </script>
</body>
</html>