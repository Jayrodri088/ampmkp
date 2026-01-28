<?php
// Start session with secure settings that work in both development and production
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Use server's default session save path (avoid per-file overrides that break session sharing)

require_once __DIR__ . '/includes/admin_functions.php';
// Only require secure cookies if we're using HTTPS (robust detection)
ini_set('session.cookie_secure', isRequestHttps() ? 1 : 0);

session_start();

// Enforce HTTPS in admin (skip on localhost) to ensure Secure cookies persist
if (!isRequestHttps() && !isLocalhost()) {
    $redirect = getAdminAbsoluteUrl('index.php', true);
    header('Location: ' . $redirect, true, 302);
    echo '<script>window.location.href = ' . json_encode($redirect) . ';</script>';
    exit;
}

// Check if user is authenticated - redirect to separate login page if not
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . getAdminAbsoluteUrl('auth.php'), true, 302);
    echo '<script>window.location.href = ' . json_encode(getAdminUrl('auth.php')) . ';</script>';
    exit;
}

// Check for session timeout (24 hours)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 86400)) {
    session_destroy();
    header('Location: ' . getAdminAbsoluteUrl('auth.php?timeout=1'), true, 302);
    echo '<script>window.location.href = ' . json_encode(getAdminUrl('auth.php?timeout=1')) . ';</script>';
    exit;
}

require_once '../includes/functions.php';

// CSRF token setup
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Load and consolidate contacts data from all sources
function loadConsolidatedContacts() {
    // Load data from all sources
    $contactForms = readJsonFile('contacts.json') ?: [];
    $newsletter = readJsonFile('newsletter.json') ?: [];
    $orders = readJsonFile('orders.json') ?: [];
    
    $consolidatedContacts = [];
    $emailIndex = []; // Track emails to avoid duplicates
    
    // Process contact form submissions
    foreach ($contactForms as $contact) {
        if (!isset($contact['email'])) continue;
        
        $email = strtolower(trim($contact['email']));
        if (empty($email)) continue;
        
        $consolidatedContacts[] = [
            'id' => $contact['id'] ?? uniqid(),
            'name' => $contact['name'] ?? 'N/A',
            'email' => $contact['email'],
            'phone' => $contact['phone'] ?? '',
            'message' => $contact['message'] ?? '',
            'submitted_at' => $contact['submitted_at'] ?? date('Y-m-d H:i:s'),
            'status' => $contact['status'] ?? 'new',
            'updated_at' => $contact['updated_at'] ?? null,
            'sources' => ['contact_form'],
            'source_details' => [
                'contact_form' => [
                    'submitted_at' => $contact['submitted_at'] ?? date('Y-m-d H:i:s'),
                    'status' => $contact['status'] ?? 'new'
                ]
            ]
        ];
        $emailIndex[$email] = count($consolidatedContacts) - 1;
    }
    
    // Process newsletter subscriptions
    foreach ($newsletter as $subscriber) {
        if (!isset($subscriber['email'])) continue;
        
        $email = strtolower(trim($subscriber['email']));
        if (empty($email)) continue;
        
        if (isset($emailIndex[$email])) {
            // Add newsletter source to existing contact
            $index = $emailIndex[$email];
            $consolidatedContacts[$index]['sources'][] = 'newsletter';
            $consolidatedContacts[$index]['source_details']['newsletter'] = [
                'subscribed_at' => $subscriber['subscribed_at'] ?? date('Y-m-d H:i:s'),
                'active' => $subscriber['active'] ?? true
            ];
        } else {
            // Create new contact from newsletter
            $consolidatedContacts[] = [
                'id' => 'newsletter_' . ($subscriber['id'] ?? uniqid()),
                'name' => 'Newsletter Subscriber',
                'email' => $subscriber['email'],
                'phone' => '',
                'message' => 'Newsletter subscription',
                'submitted_at' => $subscriber['subscribed_at'] ?? date('Y-m-d H:i:s'),
                'status' => 'new',
                'updated_at' => null,
                'sources' => ['newsletter'],
                'source_details' => [
                    'newsletter' => [
                        'subscribed_at' => $subscriber['subscribed_at'] ?? date('Y-m-d H:i:s'),
                        'active' => $subscriber['active'] ?? true
                    ]
                ]
            ];
            $emailIndex[$email] = count($consolidatedContacts) - 1;
        }
    }
    
    // Process orders
    foreach ($orders as $order) {
        if (!isset($order['customer_email'])) continue;
        
        $email = strtolower(trim($order['customer_email']));
        if (empty($email)) continue;
        
        $orderData = [
            'ordered_at' => $order['created_at'] ?? $order['order_date'] ?? date('Y-m-d H:i:s'),
            'order_id' => $order['id'] ?? 'N/A',
            'status' => $order['status'] ?? 'pending',
            'total' => $order['total_amount'] ?? $order['total'] ?? 0
        ];
        
        if (isset($emailIndex[$email])) {
            // Add order source to existing contact
            $index = $emailIndex[$email];
            if (!in_array('orders', $consolidatedContacts[$index]['sources'])) {
                $consolidatedContacts[$index]['sources'][] = 'orders';
            }
            
            if (!isset($consolidatedContacts[$index]['source_details']['orders'])) {
                $consolidatedContacts[$index]['source_details']['orders'] = [];
            }
            $consolidatedContacts[$index]['source_details']['orders'][] = $orderData;
            
            // Update name and phone if not available
            if ($consolidatedContacts[$index]['name'] === 'N/A' || $consolidatedContacts[$index]['name'] === 'Newsletter Subscriber') {
                $consolidatedContacts[$index]['name'] = $order['customer_name'] ?? 'Customer';
            }
            if (empty($consolidatedContacts[$index]['phone'])) {
                $consolidatedContacts[$index]['phone'] = $order['customer_phone'] ?? '';
            }
        } else {
            // Create new contact from order
            $consolidatedContacts[] = [
                'id' => 'order_' . ($order['id'] ?? uniqid()),
                'name' => $order['customer_name'] ?? 'Customer',
                'email' => $order['customer_email'],
                'phone' => $order['customer_phone'] ?? '',
                'message' => 'Order placed',
                'submitted_at' => $order['created_at'] ?? $order['order_date'] ?? date('Y-m-d H:i:s'),
                'status' => 'new',
                'updated_at' => null,
                'sources' => ['orders'],
                'source_details' => [
                    'orders' => [$orderData]
                ]
            ];
            $emailIndex[$email] = count($consolidatedContacts) - 1;
        }
    }
    
    return $consolidatedContacts;
}

$contacts = loadConsolidatedContacts();

// Handle contact actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (empty($postedToken) || !hash_equals($_SESSION['admin_csrf_token'], $postedToken)) {
        http_response_code(403);
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_status':
                    $contactId = $_POST['contact_id'] ?? '';
                    $newStatus = $_POST['status'] ?? '';
                    
                    if ($contactId && $newStatus) {
                        // Note: Status updates only work for contact form submissions
                        // For consolidated view, we only update the original contact form data
                        $contactForms = readJsonFile('contacts.json') ?: [];
                        $updated = false;
                        
                        foreach ($contactForms as &$contact) {
                            if ($contact['id'] == $contactId) {
                                $contact['status'] = $newStatus;
                                $contact['updated_at'] = date('Y-m-d H:i:s');
                                $updated = true;
                                break;
                            }
                        }
                        
                        if ($updated && writeJsonFile('contacts.json', $contactForms)) {
                            $message = 'Contact status updated successfully';
                            $message_type = 'success';
                        } else {
                            $message = 'Status updates only available for contact form submissions';
                            $message_type = 'warning';
                        }
                    }
                    break;
                    
                case 'delete_contact':
                    $contactId = $_POST['contact_id'] ?? '';
                    
                    if ($contactId) {
                        // Note: Deletes only work for contact form submissions
                        $contactForms = readJsonFile('contacts.json') ?: [];
                        $originalCount = count($contactForms);
                        $contactForms = array_filter($contactForms, fn($contact) => $contact['id'] != $contactId);
                        $contactForms = array_values($contactForms); // Re-index array
                        
                        if (count($contactForms) < $originalCount && writeJsonFile('contacts.json', $contactForms)) {
                            $message = 'Contact deleted successfully';
                            $message_type = 'success';
                        } else {
                            $message = 'Delete only available for contact form submissions';
                            $message_type = 'warning';
                        }
                    }
                    break;
                    
                case 'bulk_delete':
                    $contactIds = $_POST['contact_ids'] ?? [];
                    
                    if (!empty($contactIds) && is_array($contactIds)) {
                        $contactForms = readJsonFile('contacts.json') ?: [];
                        $originalCount = count($contactForms);
                        $contactForms = array_filter($contactForms, fn($contact) => !in_array($contact['id'], $contactIds));
                        $contactForms = array_values($contactForms); // Re-index array
                        $deletedCount = $originalCount - count($contactForms);
                        
                        if ($deletedCount > 0 && writeJsonFile('contacts.json', $contactForms)) {
                            $message = $deletedCount . ' contact(s) deleted successfully';
                            $message_type = 'success';
                        } else {
                            $message = 'Bulk delete only available for contact form submissions';
                            $message_type = 'warning';
                        }
                    }
                    break;
                    
                case 'bulk_status_update':
                    $contactIds = $_POST['contact_ids'] ?? [];
                    $newStatus = $_POST['bulk_status'] ?? '';
                    
                    if (!empty($contactIds) && is_array($contactIds) && $newStatus) {
                        $contactForms = readJsonFile('contacts.json') ?: [];
                        $updatedCount = 0;
                        
                        foreach ($contactForms as &$contact) {
                            if (in_array($contact['id'], $contactIds)) {
                                $contact['status'] = $newStatus;
                                $contact['updated_at'] = date('Y-m-d H:i:s');
                                $updatedCount++;
                            }
                        }
                        
                        if ($updatedCount > 0 && writeJsonFile('contacts.json', $contactForms)) {
                            $message = $updatedCount . ' contact(s) status updated successfully';
                            $message_type = 'success';
                        } else {
                            $message = 'Bulk status update only available for contact form submissions';
                            $message_type = 'warning';
                        }
                    }
                    break;
            }
        }
    }
}

// Filtering and sorting
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'date_desc';

// Apply filters
$filtered_contacts = $contacts;

if ($filter_status !== 'all') {
    $filtered_contacts = array_filter($filtered_contacts, fn($contact) => ($contact['status'] ?? 'new') === $filter_status);
}

if (!empty($search)) {
    $filtered_contacts = array_filter($filtered_contacts, function($contact) use ($search) {
        $searchTerm = strtolower($search);
        return strpos(strtolower($contact['id'] ?? ''), $searchTerm) !== false ||
               strpos(strtolower($contact['name'] ?? ''), $searchTerm) !== false ||
               strpos(strtolower($contact['email'] ?? ''), $searchTerm) !== false ||
               strpos(strtolower($contact['message'] ?? ''), $searchTerm) !== false;
    });
}

// Apply sorting
usort($filtered_contacts, function($a, $b) use ($sort) {
    switch ($sort) {
        case 'date_asc':
            return strtotime($a['submitted_at'] ?? '0') <=> strtotime($b['submitted_at'] ?? '0');
        case 'date_desc':
            return strtotime($b['submitted_at'] ?? '0') <=> strtotime($a['submitted_at'] ?? '0');
        case 'name_asc':
            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        case 'name_desc':
            return strcasecmp($b['name'] ?? '', $a['name'] ?? '');
        case 'status':
            return ($a['status'] ?? 'new') <=> ($b['status'] ?? 'new');
        default:
            return strtotime($b['submitted_at'] ?? '0') <=> strtotime($a['submitted_at'] ?? '0');
    }
});

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$total_contacts = count($filtered_contacts);
$total_pages = ceil($total_contacts / $per_page);
$offset = ($page - 1) * $per_page;
$paged_contacts = array_slice($filtered_contacts, $offset, $per_page);

// Calculate statistics
$stats = [
    'total' => count($contacts),
    'new' => count(array_filter($contacts, fn($c) => ($c['status'] ?? 'new') === 'new')),
    'in_progress' => count(array_filter($contacts, fn($c) => ($c['status'] ?? 'new') === 'in_progress')),
    'resolved' => count(array_filter($contacts, fn($c) => ($c['status'] ?? 'new') === 'resolved')),
    'today' => count(array_filter($contacts, fn($c) => date('Y-m-d', strtotime($c['submitted_at'] ?? '0')) === date('Y-m-d'))),
    'contact_form' => count(array_filter($contacts, fn($c) => in_array('contact_form', $c['sources'] ?? []))),
    'newsletter' => count(array_filter($contacts, fn($c) => in_array('newsletter', $c['sources'] ?? []))),
    'orders' => count(array_filter($contacts, fn($c) => in_array('orders', $c['sources'] ?? [])))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts Management - Admin</title>
    
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
        .scrollable-message {
            scrollbar-width: thin;
            scrollbar-color: #d1d5db #f3f4f6;
        }
        
        .scrollable-message::-webkit-scrollbar {
            width: 4px;
        }
        
        .scrollable-message::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 2px;
        }
        
        .scrollable-message::-webkit-scrollbar-thumb {
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
            <nav class="p-4 space-y-1">
                <?php $activePage = 'contacts'; include __DIR__ . '/partials/nav_links_desktop.php'; ?>
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
            <nav class="p-2 sm:p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-140px)]">
                <?php $activePage = 'contacts'; include __DIR__ . '/partials/nav_links_mobile.php'; ?>
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
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-charcoal truncate">Contacts Management</h1>
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
                <?php if (empty($contacts)): ?>
                <div class="bg-blue-50 border border-blue-200 p-4 lg:p-6 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex flex-col lg:flex-row lg:items-start">
                        <div class="flex-1">
                            <h3 class="text-base lg:text-lg font-semibold text-blue-900 mb-2">
                                <i class="bi bi-lightbulb mr-2"></i>No contact messages yet
                            </h3>
                            <p class="text-blue-800 mb-2 text-sm lg:text-base">Contact messages will appear here when customers send inquiries through the contact form. This dashboard helps you manage and respond to all customer communications.</p>
                            <p class="text-blue-700 text-xs lg:text-sm">
                                <i class="bi bi-info-circle mr-1"></i>
                                Tip: You can update contact status, view detailed messages, and manage customer inquiries efficiently.
                            </p>
                        </div>
                        <div class="mt-4 lg:mt-0 lg:ml-6 text-center">
                            <i class="bi bi-envelope text-4xl lg:text-6xl text-blue-400"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages -->
                <?php if ($message): ?>
                <div class="<?= 
                    $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 
                    ($message_type === 'warning' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 'bg-red-50 border-red-200 text-red-800') 
                ?> border p-3 lg:p-4 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="bi bi-<?= 
                            $message_type === 'success' ? 'check-circle' : 
                            ($message_type === 'warning' ? 'exclamation-triangle' : 'x-circle') 
                        ?> mr-2 flex-shrink-0"></i>
                        <span class="text-sm lg:text-base"><?= htmlspecialchars($message) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Statistics Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3 lg:gap-4 mb-4 lg:mb-6">
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Total Contacts</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['total'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-envelope text-blue-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">New</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['new'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-exclamation-circle text-red-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">In Progress</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['in_progress'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-clock text-yellow-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Resolved</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['resolved'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-check-circle text-green-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Today</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['today'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-calendar-day text-purple-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Contact Forms</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['contact_form'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-envelope text-blue-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Newsletter</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['newsletter'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-newspaper text-green-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 lg:p-4 rounded-lg hover:shadow-md transition-shadow touch-manipulation">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-xs font-medium text-charcoal-600 uppercase">Orders</h3>
                                <p class="text-lg lg:text-xl font-bold text-charcoal"><?= $stats['orders'] ?></p>
                            </div>
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-receipt text-purple-600 text-sm lg:text-base"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="bg-white border border-gray-200 mb-4 lg:mb-6 rounded-lg">
                    <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <h3 class="text-sm lg:text-base font-semibold text-charcoal">Filter & Search Contacts</h3>
                    </div>
                    <div class="p-4 lg:p-6">
                        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Status</label>
                                <select name="status" class="w-full px-3 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" onchange="this.form.submit()">
                                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                                    <option value="new" <?= $filter_status === 'new' ? 'selected' : '' ?>>New</option>
                                    <option value="in_progress" <?= $filter_status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="resolved" <?= $filter_status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Sort By</label>
                                <select name="sort" class="w-full px-3 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" onchange="this.form.submit()">
                                    <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
                                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                                    <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>Status</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-2">
                                <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Search Contacts</label>
                                <div class="flex">
                                    <input type="text" name="search" class="flex-1 px-3 py-2 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" placeholder="Search by name, email, or message" value="<?= htmlspecialchars($search) ?>">
                                    <button type="submit" class="ml-2 px-3 lg:px-4 py-2 bg-folly text-white hover:bg-folly-600 transition-colors touch-manipulation">
                                        <i class="bi bi-search text-sm lg:text-base"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bulk Actions (only show when there are contacts) -->
                <?php if (!empty($paged_contacts)): ?>
                <div class="bg-white border border-gray-200 mb-4 lg:mb-6 rounded-lg">
                    <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <h3 class="text-sm lg:text-base font-semibold text-charcoal">Bulk Actions</h3>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Selected contacts:</label>
                                <span id="selectedCount" class="text-sm lg:text-base text-charcoal">0 selected</span>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" class="inline" onsubmit="return confirmBulkStatusUpdate()">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                                    <input type="hidden" name="action" value="bulk_status_update">
                                    <input type="hidden" name="contact_ids" id="bulkStatusIds">
                                    <select name="bulk_status" class="px-3 py-2 text-sm border border-gray-300 bg-white text-charcoal focus:border-folly focus:ring-1 focus:ring-folly">
                                        <option value="new">New</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="resolved">Resolved</option>
                                    </select>
                                    <button type="submit" class="px-3 py-2 bg-blue-600 text-white hover:bg-blue-700 transition-colors text-sm rounded touch-manipulation" disabled id="bulkStatusBtn">
                                        Update Status
                                    </button>
                                </form>
                                <form method="POST" class="inline" onsubmit="return confirmBulkDelete()">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                                    <input type="hidden" name="action" value="bulk_delete">
                                    <input type="hidden" name="contact_ids" id="bulkDeleteIds">
                                    <button type="submit" class="px-3 py-2 bg-red-600 text-white hover:bg-red-700 transition-colors text-sm rounded touch-manipulation" disabled id="bulkDeleteBtn">
                                        Delete Selected
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contacts Table -->
                <div class="bg-white border border-gray-200 rounded-lg lg:rounded-xl">
                    <div class="px-3 sm:px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                            <h3 class="text-sm lg:text-base font-semibold text-charcoal">Contact Messages</h3>
                            <div class="flex items-center gap-3">
                                <?php if (!empty($paged_contacts)): ?>
                                <label class="flex items-center text-xs lg:text-sm text-charcoal-600">
                                    <input type="checkbox" id="selectAll" class="mr-2 touch-manipulation" onchange="toggleSelectAll()">
                                    Select All
                                </label>
                                <?php endif; ?>
                                <span class="px-2 lg:px-3 py-1 bg-gray-100 text-charcoal-600 text-xs lg:text-sm font-medium rounded-full self-start sm:self-auto">
                                    Showing <?= count($paged_contacts) ?> of <?= $total_contacts ?> contacts
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($paged_contacts)): ?>
                        <div class="px-3 sm:px-6 py-6 lg:py-8 text-center text-charcoal-400">
                            <i class="bi bi-envelope text-3xl lg:text-4xl mb-3 lg:mb-4 block"></i>
                            <h5 class="text-base lg:text-lg font-medium mb-2">No contacts found</h5>
                            <p class="text-sm lg:text-base">No contact messages match your current filters.</p>
                        </div>
                    <?php else: ?>
                        <!-- Desktop Table View (hidden on mobile/tablet) -->
                        <div class="hidden xl:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">
                                            <input type="checkbox" id="selectAllDesktop" class="touch-manipulation" onchange="toggleSelectAllDesktop()">
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Phone</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Message</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Sources</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <?php foreach ($paged_contacts as $contact): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input type="checkbox" name="contact_checkbox" value="<?= htmlspecialchars($contact['id']) ?>" class="contact-checkbox touch-manipulation" onchange="updateBulkActions()">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-charcoal">
                                                <?= date('M j, Y', strtotime($contact['submitted_at'] ?? 'now')) ?>
                                            </div>
                                            <div class="text-xs text-charcoal-400">
                                                <?= date('g:i A', strtotime($contact['submitted_at'] ?? 'now')) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-charcoal"><?= htmlspecialchars($contact['name'] ?? 'N/A') ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-charcoal"><?= htmlspecialchars($contact['email'] ?? 'N/A') ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-charcoal"><?= htmlspecialchars($contact['phone'] ?: 'N/A') ?></div>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs">
                                            <div class="text-sm text-charcoal scrollable-message max-h-20 overflow-y-auto">
                                                <?= htmlspecialchars(substr($contact['message'] ?? '', 0, 150)) . (strlen($contact['message'] ?? '') > 150 ? '...' : '') ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <?php 
                                                $sources = $contact['sources'] ?? [];
                                                $sourceIcons = [
                                                    'contact_form' => ['icon' => 'bi-envelope', 'color' => 'bg-blue-100 text-blue-800', 'label' => 'Contact'],
                                                    'newsletter' => ['icon' => 'bi-newspaper', 'color' => 'bg-green-100 text-green-800', 'label' => 'Newsletter'],
                                                    'orders' => ['icon' => 'bi-receipt', 'color' => 'bg-purple-100 text-purple-800', 'label' => 'Orders']
                                                ];
                                                foreach ($sources as $source):
                                                    $sourceInfo = $sourceIcons[$source] ?? ['icon' => 'bi-question', 'color' => 'bg-gray-100 text-gray-800', 'label' => ucfirst($source)];
                                                ?>
                                                    <span class="px-2 py-1 text-xs font-medium <?= $sourceInfo['color'] ?> rounded-full flex items-center">
                                                        <i class="bi <?= $sourceInfo['icon'] ?> mr-1"></i>
                                                        <?= $sourceInfo['label'] ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $status = $contact['status'] ?? 'new';
                                            $statusColors = [
                                                'new' => 'bg-red-100 text-red-800',
                                                'in_progress' => 'bg-yellow-100 text-yellow-800',
                                                'resolved' => 'bg-green-100 text-green-800'
                                            ];
                                            $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium <?= $statusColor ?> rounded-full">
                                                <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <button type="button" onclick="viewContact('<?= htmlspecialchars(json_encode($contact)) ?>')" class="px-3 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-sm rounded touch-manipulation">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" onclick="editContact('<?= htmlspecialchars(json_encode($contact)) ?>')" class="px-3 py-1 bg-green-100 text-green-700 hover:bg-green-200 transition-colors text-sm rounded touch-manipulation">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this contact?')">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="delete_contact">
                                                    <input type="hidden" name="contact_id" value="<?= htmlspecialchars($contact['id']) ?>">
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
                                <?php foreach ($paged_contacts as $contact): ?>
                                    <?php
                                    $status = $contact['status'] ?? 'new';
                                    $statusColors = [
                                        'new' => 'bg-red-100 text-red-800',
                                        'in_progress' => 'bg-yellow-100 text-yellow-800',
                                        'resolved' => 'bg-green-100 text-green-800'
                                    ];
                                    $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <div class="mobile-card p-3 sm:p-4 lg:p-5 hover:bg-gray-50 transition-colors">
                                        <!-- Header Row with Checkbox -->
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex items-start gap-3">
                                                <input type="checkbox" name="contact_checkbox" value="<?= htmlspecialchars($contact['id']) ?>" class="contact-checkbox touch-manipulation mt-1" onchange="updateBulkActions()">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h4 class="text-sm lg:text-base font-semibold text-charcoal">
                                                            <?= htmlspecialchars($contact['name'] ?? 'N/A') ?>
                                                        </h4>
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="px-2 py-1 text-xs font-medium <?= $statusColor ?> rounded-full">
                                                                <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                                            </span>
                                                            <?php 
                                                            $sources = $contact['sources'] ?? [];
                                                            $sourceIcons = [
                                                                'contact_form' => ['icon' => 'bi-envelope', 'color' => 'bg-blue-100 text-blue-800'],
                                                                'newsletter' => ['icon' => 'bi-newspaper', 'color' => 'bg-green-100 text-green-800'],
                                                                'orders' => ['icon' => 'bi-receipt', 'color' => 'bg-purple-100 text-purple-800']
                                                            ];
                                                            foreach ($sources as $source):
                                                                $sourceInfo = $sourceIcons[$source] ?? ['icon' => 'bi-question', 'color' => 'bg-gray-100 text-gray-800'];
                                                            ?>
                                                                <span class="px-1 py-1 text-xs <?= $sourceInfo['color'] ?> rounded" title="<?= ucfirst(str_replace('_', ' ', $source)) ?>">
                                                                    <i class="bi <?= $sourceInfo['icon'] ?>"></i>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <div class="text-xs lg:text-sm text-charcoal-400">
                                                        <?= date('M j, Y \a\t g:i A', strtotime($contact['submitted_at'] ?? 'now')) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Contact Info -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                            <div>
                                                <div class="text-xs lg:text-sm font-medium text-charcoal-600 mb-1">Email</div>
                                                <div class="text-sm lg:text-base text-charcoal truncate">
                                                    <?= htmlspecialchars($contact['email'] ?? 'N/A') ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-xs lg:text-sm font-medium text-charcoal-600 mb-1">Phone</div>
                                                <div class="text-sm lg:text-base text-charcoal">
                                                    <?= htmlspecialchars($contact['phone'] ?: 'N/A') ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Message -->
                                        <div class="mb-3">
                                            <div class="text-xs lg:text-sm font-medium text-charcoal-600 mb-1">Message</div>
                                            <div class="scrollable-message text-sm lg:text-base text-charcoal bg-gray-50 p-3 rounded max-h-20 overflow-y-auto">
                                                <?= htmlspecialchars($contact['message'] ?? 'No message') ?>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                                            <button type="button" 
                                                    onclick="viewContact('<?= htmlspecialchars(json_encode($contact)) ?>')" 
                                                    class="flex-1 sm:flex-initial px-3 sm:px-4 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-xs lg:text-sm font-medium rounded touch-manipulation">
                                                <i class="bi bi-eye mr-1"></i>
                                                <span class="hidden sm:inline">View</span>
                                            </button>
                                            <button type="button" 
                                                    onclick="editContact('<?= htmlspecialchars(json_encode($contact)) ?>')" 
                                                    class="flex-1 sm:flex-initial px-3 sm:px-4 py-2 bg-green-100 text-green-700 hover:bg-green-200 transition-colors text-xs lg:text-sm font-medium rounded touch-manipulation">
                                                <i class="bi bi-pencil mr-1"></i>
                                                <span class="hidden sm:inline">Edit</span>
                                            </button>
                                            <form method="POST" class="flex-1 sm:flex-initial" onsubmit="return confirm('Are you sure you want to delete this contact?')">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                                                <input type="hidden" name="action" value="delete_contact">
                                                <input type="hidden" name="contact_id" value="<?= htmlspecialchars($contact['id']) ?>">
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

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="px-3 sm:px-4 lg:px-6 py-3 lg:py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="text-xs lg:text-sm text-charcoal-600 text-center sm:text-left">
                                Showing <?= ($page - 1) * $per_page + 1 ?> to <?= min($page * $per_page, $total_contacts) ?> of <?= $total_contacts ?> results
                            </div>
                            <div class="flex justify-center sm:justify-end">
                                <div class="flex space-x-1 lg:space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?= $page - 1 ?>&status=<?= $filter_status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" 
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
                                            <a href="?page=<?= $i ?>&status=<?= $filter_status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" 
                                               class="px-2 lg:px-3 py-2 border border-gray-300 text-xs lg:text-sm <?= $i === $page ? 'bg-folly text-white border-folly' : 'text-charcoal hover:bg-gray-50' ?> rounded touch-manipulation"><?= $i ?></a>
                                        <?php endfor; ?>
                                    </div>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?= $page + 1 ?>&status=<?= $filter_status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" 
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

    <!-- View Contact Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-2 sm:p-4" onclick="closeViewModal()">
        <div class="bg-white w-full max-w-2xl max-h-screen overflow-y-auto rounded-lg lg:rounded-xl shadow-2xl" onclick="event.stopPropagation()">
            <div class="bg-charcoal text-white px-3 sm:px-4 lg:px-6 py-3 lg:py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-base lg:text-xl font-bold">
                        <i class="bi bi-envelope mr-2 lg:mr-3"></i>
                        <span class="hidden sm:inline">Contact Details</span>
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

    <!-- Edit Contact Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-2 sm:p-4" onclick="closeEditModal()">
        <div class="bg-white w-full max-w-lg max-h-screen overflow-y-auto rounded-lg lg:rounded-xl shadow-2xl" onclick="event.stopPropagation()">
            <div class="bg-charcoal text-white px-3 sm:px-4 lg:px-6 py-3 lg:py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-base lg:text-xl font-bold">
                        <i class="bi bi-pencil-square mr-2 lg:mr-3"></i>
                        <span class="hidden sm:inline">Edit Contact Status</span>
                        <span class="sm:hidden">Edit Status</span>
                    </h3>
                    <button type="button" onclick="closeEditModal()" class="text-white hover:text-gray-300 text-xl lg:text-2xl p-1 touch-manipulation">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            <form id="editForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                <div class="p-3 sm:p-4 lg:p-6 space-y-4 lg:space-y-6">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="contact_id" id="edit_contact_id">
                    
                    <div>
                        <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">Contact Status</label>
                        <select name="status" id="edit_status" class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly bg-white rounded touch-manipulation">
                            <option value="new">New</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </div>
                <div class="px-3 sm:px-4 lg:px-6 py-3 lg:py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                        <button type="button" onclick="closeEditModal()" class="order-2 sm:order-1 px-4 lg:px-6 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal hover:bg-gray-50 transition-colors rounded touch-manipulation">
                            Cancel
                        </button>
                        <button type="submit" class="order-1 sm:order-2 px-4 lg:px-6 py-2 text-sm lg:text-base bg-folly text-white hover:bg-folly-600 transition-colors font-medium rounded touch-manipulation">
                            Update Status
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Basic HTML escape
        function escHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Modal functions
        function viewContact(contactJson) {
            const contact = JSON.parse(contactJson);
            const content = document.getElementById('viewModalContent');
            
            // Generate sources display
            const sourcesHtml = (contact.sources || []).map(source => {
                const sourceInfo = {
                    contact_form: { icon: '✉️', label: 'Contact Form', color: 'bg-blue-100 text-blue-800' },
                    newsletter: { icon: '📰', label: 'Newsletter', color: 'bg-green-100 text-green-800' },
                    orders: { icon: '🧾', label: 'Orders', color: 'bg-purple-100 text-purple-800' }
                }[source] || { icon: '❓', label: source, color: 'bg-gray-100 text-gray-800' };
                
                return \`<span class="px-2 py-1 text-xs font-medium \${sourceInfo.color} rounded-full">\${sourceInfo.icon} \${sourceInfo.label}</span>\`;
            }).join(' ');
            
            // Generate source details
            let sourceDetailsHtml = '';
            if (contact.source_details) {
                sourceDetailsHtml = '<div class="mt-4"><h5 class="text-sm font-semibold text-charcoal mb-2">📊 Source Details</h5><div class="space-y-2 text-xs">';
                
                if (contact.source_details.contact_form) {
                    sourceDetailsHtml += \`<div class="p-2 bg-blue-50 rounded"><strong>Contact Form:</strong> Submitted \${new Date(contact.source_details.contact_form.submitted_at).toLocaleDateString()}</div>\`;
                }
                
                if (contact.source_details.newsletter) {
                    const active = contact.source_details.newsletter.active ? 'Active' : 'Inactive';
                    sourceDetailsHtml += \`<div class="p-2 bg-green-50 rounded"><strong>Newsletter:</strong> Subscribed \${new Date(contact.source_details.newsletter.subscribed_at).toLocaleDateString()} - \${active}</div>\`;
                }
                
                if (contact.source_details.orders && contact.source_details.orders.length > 0) {
                    sourceDetailsHtml += '<div class="p-2 bg-purple-50 rounded"><strong>Orders:</strong>';
                    contact.source_details.orders.forEach(order => {
                        sourceDetailsHtml += \`<div class="ml-2">• Order \${order.order_id} - \${order.status} (\${new Date(order.ordered_at).toLocaleDateString()})</div>\`;
                    });
                    sourceDetailsHtml += '</div>';
                }
                
                sourceDetailsHtml += '</div></div>';
            }

            content.innerHTML = `
                <div class="space-y-4 lg:space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
                        <div>
                            <h4 class="text-sm lg:text-base font-semibold text-charcoal mb-3 lg:mb-4">📋 Contact Information</h4>
                            <div class="space-y-2 lg:space-y-3">
                                <div class="text-sm lg:text-base"><strong>Name:</strong> ${escHtml(contact.name)}</div>
                                <div class="text-sm lg:text-base"><strong>Email:</strong> <span class="break-all">${escHtml(contact.email)}</span></div>
                                <div class="text-sm lg:text-base"><strong>Phone:</strong> ${escHtml(contact.phone || 'N/A')}</div>
                                <div class="text-sm lg:text-base"><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full bg-gray-100">${contact.status ? contact.status.replace('_', ' ') : 'new'}</span></div>
                                <div class="text-sm lg:text-base"><strong>Submitted:</strong> ${new Date(contact.submitted_at).toLocaleDateString('en-GB', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}</div>
                                <div class="text-sm lg:text-base"><strong>Sources:</strong><br><div class="mt-1 flex flex-wrap gap-1">${sourcesHtml}</div></div>
                            </div>
                            ${sourceDetailsHtml}
                        </div>
                        <div>
                            <h4 class="text-sm lg:text-base font-semibold text-charcoal mb-3 lg:mb-4">📞 Quick Actions</h4>
                            <div class="space-y-2">
                                <a href="mailto:${escHtml(contact.email)}" class="block w-full px-3 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-center text-sm rounded touch-manipulation">
                                    <i class="bi bi-envelope mr-1"></i> Send Email
                                </a>
                                ${contact.phone ? `<a href="tel:${escHtml(contact.phone)}" class="block w-full px-3 py-2 bg-green-100 text-green-700 hover:bg-green-200 transition-colors text-center text-sm rounded touch-manipulation">
                                    <i class="bi bi-telephone mr-1"></i> Call Phone
                                </a>` : ''}
                                <button onclick="editContact('${contactJson.replace(/'/g, "\\'")}')" class="block w-full px-3 py-2 bg-yellow-100 text-yellow-700 hover:bg-yellow-200 transition-colors text-center text-sm rounded touch-manipulation">
                                    <i class="bi bi-pencil mr-1"></i> Update Status
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-sm lg:text-base font-semibold text-charcoal mb-3 lg:mb-4">💬 Message</h4>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 lg:p-4">
                            <div class="text-sm lg:text-base text-charcoal whitespace-pre-wrap">${escHtml(contact.message || 'No message')}</div>
                        </div>
                    </div>
                </div>
            `;
            
            openViewModal();
        }

        function editContact(contactJson) {
            const contact = JSON.parse(contactJson);
            document.getElementById('edit_contact_id').value = contact.id;
            document.getElementById('edit_status').value = contact.status || 'new';
            closeViewModal(); // Close view modal if it's open
            openEditModal();
        }

        // Bulk actions
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.contact-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateBulkActions();
        }

        function toggleSelectAllDesktop() {
            const selectAll = document.getElementById('selectAllDesktop');
            const selectAllMobile = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.contact-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            if (selectAllMobile) {
                selectAllMobile.checked = selectAll.checked;
            }
            
            updateBulkActions();
        }

        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.contact-checkbox:checked');
            const selectedCount = document.getElementById('selectedCount');
            const bulkStatusBtn = document.getElementById('bulkStatusBtn');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            
            const count = checkboxes.length;
            
            if (selectedCount) {
                selectedCount.textContent = `${count} selected`;
            }
            
            if (bulkStatusBtn && bulkDeleteBtn) {
                bulkStatusBtn.disabled = count === 0;
                bulkDeleteBtn.disabled = count === 0;
            }
            
            // Update select all checkboxes
            const totalCheckboxes = document.querySelectorAll('.contact-checkbox').length;
            const selectAllCheckboxes = document.querySelectorAll('#selectAll, #selectAllDesktop');
            
            selectAllCheckboxes.forEach(checkbox => {
                if (count === 0) {
                    checkbox.checked = false;
                    checkbox.indeterminate = false;
                } else if (count === totalCheckboxes) {
                    checkbox.checked = true;
                    checkbox.indeterminate = false;
                } else {
                    checkbox.checked = false;
                    checkbox.indeterminate = true;
                }
            });
        }

        function confirmBulkDelete() {
            const checkboxes = document.querySelectorAll('.contact-checkbox:checked');
            const count = checkboxes.length;
            
            if (count === 0) {
                alert('Please select contacts to delete.');
                return false;
            }
            
            const ids = Array.from(checkboxes).map(cb => cb.value);
            document.getElementById('bulkDeleteIds').value = JSON.stringify(ids);
            
            return confirm(`Are you sure you want to delete ${count} contact(s)? This action cannot be undone.`);
        }

        function confirmBulkStatusUpdate() {
            const checkboxes = document.querySelectorAll('.contact-checkbox:checked');
            const count = checkboxes.length;
            
            if (count === 0) {
                alert('Please select contacts to update.');
                return false;
            }
            
            const ids = Array.from(checkboxes).map(cb => cb.value);
            document.getElementById('bulkStatusIds').value = JSON.stringify(ids);
            
            return confirm(`Are you sure you want to update the status of ${count} contact(s)?`);
        }

        // Modal functions
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

        // Mobile menu functions
        function openMobileMenu() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeMobileMenu() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileMenuOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeViewModal();
                closeEditModal();
                closeMobileMenu();
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

            // Initialize bulk actions
            updateBulkActions();

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