<?php
// Prevent caching in admin area
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: auth.php');
    exit;
}

// Include main functions
require_once '../includes/functions.php';

// Helper function to check if a category is descendant of another
function isDescendantOf($categoryId, $potentialAncestorId, $categories) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryId) {
            $parentId = $cat['parent_id'] ?? 0;
            if ($parentId == $potentialAncestorId) {
                return true;
            } elseif ($parentId > 0) {
                return isDescendantOf($parentId, $potentialAncestorId, $categories);
            }
        }
    }
    return false;
}

// Load data
$categories = json_decode(file_get_contents('../data/categories.json'), true) ?? [];
$products = json_decode(file_get_contents('../data/products.json'), true) ?? [];

// Add parent_id to existing categories if not present
$updated = false;
foreach ($categories as &$category) {
    if (!isset($category['parent_id'])) {
        $category['parent_id'] = 0; // Root category
        $updated = true;
    }
}
if ($updated) {
    file_put_contents('../data/categories.json', json_encode($categories, JSON_PRETTY_PRINT));
}

// Handle form submissions
$message = '';
$message_type = '';

// Handle redirect messages (after form submission)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $message_type = $_GET['type'];
} elseif (isset($_GET['success'])) {
    // Handle our new success redirects
    switch ($_GET['success']) {
        case 'added':
            $message = 'Category added successfully!';
            $message_type = 'success';
            break;
        case 'updated':
            $message = 'Category updated successfully!';
            $message_type = 'success';
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Handle image upload
                $image_path = 'categories/default.jpg'; // Default
                
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../assets/images/categories/';
                    
                    // Create directory if it doesn't exist with proper permissions
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            $message = 'Failed to create upload directory. Category added with default image.';
                            $message_type = 'warning';
                        }
                    }
                    
                    // Check if directory is writable
                    if (!is_writable($upload_dir)) {
                        $message = 'Upload directory is not writable. Please check file permissions. Category added with default image.';
                        $message_type = 'warning';
                    } else {
                        $file_info = pathinfo($_FILES['image']['name']);
                        $extension = strtolower($file_info['extension']);
                        
                        // Validate file type
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (in_array($extension, $allowed_types)) {
                            // Validate file size (max 5MB)
                            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                                $message = 'Image file is too large (max 5MB). Category added with default image.';
                                $message_type = 'warning';
                            } else {
                                // Generate unique filename
                                $filename = generateSlug($_POST['name']) . '_' . time() . '.' . $extension;
                                $upload_path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                                    // Set proper permissions on uploaded file
                                    chmod($upload_path, 0644);
                                    $image_path = 'categories/' . $filename;
                                } else {
                                    $error = error_get_last();
                                    $message = 'Failed to upload image. ' . ($error ? $error['message'] : 'Unknown error.') . ' Category added with default image.';
                                    $message_type = 'warning';
                                }
                            }
                        } else {
                            $message = 'Invalid image format. Only JPG, PNG, GIF, and WebP are allowed. Category added with default image.';
                            $message_type = 'warning';
                        }
                    }
                } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    // Handle upload errors
                    $upload_errors = [
                        UPLOAD_ERR_INI_SIZE => 'File is too large (exceeds server limit)',
                        UPLOAD_ERR_FORM_SIZE => 'File is too large (exceeds form limit)', 
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    ];
                    $error_msg = $upload_errors[$_FILES['image']['error']] ?? 'Unknown upload error';
                    $message = 'Upload failed: ' . $error_msg . '. Category added with default image.';
                    $message_type = 'warning';
                } elseif (!empty($_POST['image_path'])) {
                    // Use manual path if provided
                    $image_path = $_POST['image_path'];
                }
                
                $max_id = 0;
                if (!empty($categories)) {
                    $max_id = max(array_column($categories, 'id'));
                }
                
                $new_category = [
                    'id' => $max_id + 1,
                    'name' => $_POST['name'],
                    'slug' => generateSlug($_POST['name']),
                    'description' => $_POST['description'],
                    'image' => $image_path,
                    'parent_id' => (int)$_POST['parent_id'],
                    'active' => isset($_POST['active']),
                    'featured' => isset($_POST['featured'])
                ];
                $categories[] = $new_category;
                if (empty($message)) {
                    // Save data first
                    file_put_contents('../data/categories.json', json_encode($categories, JSON_PRETTY_PRINT));
                    
                    // Redirect to prevent duplicate submission
                    header('Location: categories.php?success=added&cache=' . time());
                    exit;
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $image_path = null;
                
                // Handle image upload for edit
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../assets/images/categories/';
                    
                    // Create directory if it doesn't exist with proper permissions
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            $message = 'Failed to create upload directory. Category updated without new image.';
                            $message_type = 'warning';
                        }
                    }
                    
                    // Check if directory is writable
                    if (!is_writable($upload_dir)) {
                        $message = 'Upload directory is not writable. Please check file permissions. Category updated without new image.';
                        $message_type = 'warning';
                    } else {
                        $file_info = pathinfo($_FILES['image']['name']);
                        $extension = strtolower($file_info['extension']);
                        
                        // Validate file type
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (in_array($extension, $allowed_types)) {
                            // Validate file size (max 5MB)
                            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                                $message = 'Image file is too large (max 5MB). Category updated without new image.';
                                $message_type = 'warning';
                            } else {
                                // Generate unique filename
                                $filename = generateSlug($_POST['name']) . '_' . time() . '.' . $extension;
                                $upload_path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                                    // Set proper permissions on uploaded file
                                    chmod($upload_path, 0644);
                                    $image_path = 'categories/' . $filename;
                                } else {
                                    $error = error_get_last();
                                    $message = 'Failed to upload image. ' . ($error ? $error['message'] : 'Unknown error.') . ' Category updated without new image.';
                                    $message_type = 'warning';
                                }
                            }
                        } else {
                            $message = 'Invalid image format. Only JPG, PNG, GIF, and WebP are allowed. Category updated without new image.';
                            $message_type = 'warning';
                        }
                    }
                } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    // Handle upload errors
                    $upload_errors = [
                        UPLOAD_ERR_INI_SIZE => 'File is too large (exceeds server limit)',
                        UPLOAD_ERR_FORM_SIZE => 'File is too large (exceeds form limit)', 
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    ];
                    $error_msg = $upload_errors[$_FILES['image']['error']] ?? 'Unknown upload error';
                    $message = 'Upload failed: ' . $error_msg . '. Category updated without new image.';
                    $message_type = 'warning';
                } elseif (!empty($_POST['image_path'])) {
                    // Use manual path if provided
                    $image_path = $_POST['image_path'];
                }
                
                foreach ($categories as &$category) {
                    if ($category['id'] === $id) {
                        // Delete old image if new one is uploaded
                        if ($image_path && $category['image'] !== 'categories/default.jpg') {
                            $old_image_path = '../assets/images/' . $category['image'];
                            if (file_exists($old_image_path)) {
                                unlink($old_image_path);
                            }
                        }
                        
                        $category['name'] = $_POST['name'];
                        $category['slug'] = generateSlug($_POST['name']);
                        $category['description'] = $_POST['description'];
                        if ($image_path) {
                            $category['image'] = $image_path;
                        }
                        $category['parent_id'] = (int)$_POST['parent_id'];
                        $category['active'] = isset($_POST['active']);
                        $category['featured'] = isset($_POST['featured']);
                        break;
                    }
                }
                if (empty($message)) {
                    // Save data first
                    file_put_contents('../data/categories.json', json_encode($categories, JSON_PRETTY_PRINT));
                    
                    // Redirect to prevent duplicate submission
                    header('Location: categories.php?success=updated&cache=' . time());
                    exit;
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Check if category has products
                $products_in_category = array_filter($products, fn($p) => $p['category_id'] == $id);
                
                // Check if category has sub-categories
                $sub_categories = array_filter($categories, fn($c) => ($c['parent_id'] ?? 0) == $id);
                
                if (!empty($products_in_category)) {
                    $message = 'Cannot delete category with existing products! Please move or delete products first.';
                    $message_type = 'danger';
                } elseif (!empty($sub_categories)) {
                    $message = 'Cannot delete category with sub-categories! Please move or delete sub-categories first.';
                    $message_type = 'danger';
                } else {
                    // Find the category to delete its image
                    $category_to_delete = null;
                    foreach ($categories as $category) {
                        if ($category['id'] === $id) {
                            $category_to_delete = $category;
                            break;
                        }
                    }
                    
                    // Delete the image file if it's not the default
                    if ($category_to_delete && $category_to_delete['image'] !== 'categories/default.jpg') {
                        $image_path = '../assets/images/' . $category_to_delete['image'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    
                    $categories = array_filter($categories, fn($c) => $c['id'] !== $id);
                    $categories = array_values($categories);
                    $message = 'Category deleted successfully!';
                    $message_type = 'success';
                }
                break;
        }
        
        // Save changes and redirect
        if ($message_type === 'success') {
            file_put_contents('../data/categories.json', json_encode($categories, JSON_PRETTY_PRINT));
            header('Location: categories.php?message=' . urlencode($message) . '&type=' . $message_type);
            exit;
        } else {
            // If there's an error, redirect with error message
            header('Location: categories.php?message=' . urlencode($message) . '&type=' . $message_type);
            exit;
        }
    }
}

// Handle GET actions
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if category has products
    $products_in_category = array_filter($products, fn($p) => $p['category_id'] == $id);
    
    // Check if category has sub-categories
    $sub_categories = array_filter($categories, fn($c) => ($c['parent_id'] ?? 0) == $id);
    
    if (!empty($products_in_category)) {
        // Redirect with error message
        header('Location: categories.php?message=' . urlencode('Cannot delete category with existing products! Please move or delete products first.') . '&type=danger');
        exit;
    } elseif (!empty($sub_categories)) {
        // Redirect with error message
        header('Location: categories.php?message=' . urlencode('Cannot delete category with sub-categories! Please move or delete sub-categories first.') . '&type=danger');
        exit;
    } else {
        // Find the category to delete its image
        $category_to_delete = null;
        foreach ($categories as $category) {
            if ($category['id'] === $id) {
                $category_to_delete = $category;
                break;
            }
        }
        
        // Delete the image file if it's not the default
        if ($category_to_delete && $category_to_delete['image'] !== 'categories/default.jpg') {
            $image_path = '../assets/images/' . $category_to_delete['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $categories = array_filter($categories, fn($c) => $c['id'] !== $id);
        $categories = array_values($categories);
        file_put_contents('../data/categories.json', json_encode($categories, JSON_PRETTY_PRINT));
        
        // Redirect to prevent form resubmission
        header('Location: categories.php?message=' . urlencode('Category deleted successfully!') . '&type=success');
        exit;
    }
}

// Get category for editing
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $edit_category = array_filter($categories, fn($c) => $c['id'] === $edit_id);
    $edit_category = $edit_category ? array_values($edit_category)[0] : null;
}

// Count products in each category
$category_product_counts = [];
foreach ($categories as $category) {
    $category_product_counts[$category['id']] = count(array_filter($products, fn($p) => $p['category_id'] == $category['id']));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Admin (<?= date('H:i:s') ?>)</title>
    
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
                <a href="categories.php" class="flex items-center px-3 lg:px-4 py-2 lg:py-3 text-white bg-folly hover:bg-folly-600 transition-colors touch-manipulation">
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
                <a href="categories.php" class="flex items-center px-4 py-3 text-white bg-folly hover:bg-folly-600 transition-colors touch-manipulation">
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
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-charcoal truncate">Categories Management</h1>
                    </div>
                    <button type="button" onclick="openCategoryModal()" class="px-3 sm:px-4 lg:px-6 py-2 lg:py-3 bg-folly text-white hover:bg-folly-600 transition-colors font-medium text-sm lg:text-base touch-manipulation scale-hover whitespace-nowrap">
                        <i class="bi bi-plus mr-1 lg:mr-2"></i>
                        <span class="hidden sm:inline">Add </span>Category
                    </button>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="flex-1 p-3 sm:p-4 lg:p-6 overflow-auto">
                <!-- Quick Start Guide for Empty State -->
                <?php if (empty($categories)): ?>
                <div class="bg-blue-50 border border-blue-200 p-4 lg:p-6 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex flex-col lg:flex-row lg:items-start">
                        <div class="flex-1">
                            <h3 class="text-base lg:text-lg font-semibold text-blue-900 mb-2">
                                <i class="bi bi-lightbulb mr-2"></i>Welcome! Let's add your first category
                            </h3>
                            <p class="text-blue-800 mb-2 text-sm lg:text-base">Categories help organize your products. Start by creating your first product category.</p>
                            <p class="text-blue-700 text-xs lg:text-sm">
                                <i class="bi bi-info-circle mr-1"></i>
                                Tip: Popular categories include "Apparel", "Footwear", "Accessories", etc.
                            </p>
                        </div>
                        <div class="mt-4 lg:mt-0 lg:ml-6 text-center">
                            <i class="bi bi-tags text-4xl lg:text-6xl text-blue-400"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages -->
                <?php if ($message): ?>
                <div class="<?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : ($message_type === 'warning' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 'bg-red-50 border-red-200 text-red-800') ?> border p-3 lg:p-4 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="bi bi-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'x-circle') ?> mr-2 flex-shrink-0"></i>
                        <span class="text-sm lg:text-base"><?= htmlspecialchars($message) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Statistics Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-4 lg:mb-6">
                    <div class="bg-white border border-gray-200 p-4 sm:p-5 lg:p-6 rounded-lg touch-manipulation scale-hover transition-transform">
                        <div class="flex items-center">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs sm:text-sm font-medium text-charcoal-600 truncate">Total Categories</h3>
                                <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-charcoal mt-1"><?= count($categories) ?></p>
                            </div>
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0 ml-3">
                                <i class="bi bi-tags text-lg sm:text-xl lg:text-2xl text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-4 sm:p-5 lg:p-6 rounded-lg touch-manipulation scale-hover transition-transform">
                        <div class="flex items-center">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs sm:text-sm font-medium text-charcoal-600 truncate">Root Categories</h3>
                                <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-charcoal mt-1"><?= count(array_filter($categories, fn($c) => ($c['parent_id'] ?? 0) == 0)) ?></p>
                            </div>
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 ml-3">
                                <i class="bi bi-diagram-3 text-lg sm:text-xl lg:text-2xl text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-4 sm:p-5 lg:p-6 rounded-lg touch-manipulation scale-hover transition-transform">
                        <div class="flex items-center">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs sm:text-sm font-medium text-charcoal-600 truncate">Sub Categories</h3>
                                <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-charcoal mt-1"><?= count(array_filter($categories, fn($c) => ($c['parent_id'] ?? 0) > 0)) ?></p>
                            </div>
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0 ml-3">
                                <i class="bi bi-arrow-down-right text-lg sm:text-xl lg:text-2xl text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 p-4 sm:p-5 lg:p-6 rounded-lg touch-manipulation scale-hover transition-transform">
                        <div class="flex items-center">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs sm:text-sm font-medium text-charcoal-600 truncate">Active Categories</h3>
                                <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-charcoal mt-1"><?= count(array_filter($categories, fn($c) => $c['active'])) ?></p>
                            </div>
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 ml-3">
                                <i class="bi bi-check-circle text-lg sm:text-xl lg:text-2xl text-green-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categories Hierarchy -->
                <div class="bg-white border border-gray-200 mb-4 lg:mb-6 rounded-lg">
                    <div class="px-4 sm:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-charcoal text-base lg:text-lg">Category Hierarchy</h3>
                    </div>
                    <div class="p-4 sm:p-6">
                        <?php 
                        $hierarchy = getCategoryHierarchy(true);
                        if (empty($hierarchy)): 
                        ?>
                        <div class="text-center text-charcoal-400 py-6 lg:py-8">
                            <i class="bi bi-tags text-3xl lg:text-4xl mb-3 lg:mb-4 block"></i>
                            <p class="text-sm lg:text-base">No categories found</p>
                        </div>
                        <?php else: ?>
                        <!-- Desktop View -->
                        <div class="hidden lg:block space-y-2">
                            <?php echo renderCategoryTree($hierarchy, $category_product_counts); ?>
                        </div>
                        
                        <!-- Mobile View -->
                        <div class="lg:hidden space-y-3">
                            <?php echo renderMobileCategoryTree($hierarchy, $category_product_counts); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                function renderCategoryTree($categories, $productCounts, $level = 0) {
                    $html = '';
                    foreach ($categories as $category) {
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                        $hasChildren = !empty($category['children']);
                        $productCount = $productCounts[$category['id']] ?? 0;
                        
                        $html .= '<div class="flex items-center justify-between py-2 px-3 ' . ($level > 0 ? 'ml-' . ($level * 4) : '') . ' border-l-2 ' . ($level === 0 ? 'border-folly' : 'border-gray-300') . '">';
                        
                        // Category info
                        $html .= '<div class="flex items-center flex-1">';
                        if ($level > 0) {
                            $html .= '<i class="bi bi-arrow-return-right text-charcoal-400 mr-2"></i>';
                        }
                        $html .= '<img src="../assets/images/' . htmlspecialchars($category['image']) . '" 
                                       alt="' . htmlspecialchars($category['name']) . '" 
                                       class="w-8 h-8 object-cover rounded mr-3"
                                       onerror="this.src=\'../assets/images/general/placeholder-small.jpg\'">';
                        $html .= '<div>';
                        $html .= '<h4 class="font-medium text-charcoal">' . htmlspecialchars($category['name']) . '</h4>';
                        $html .= '<p class="text-xs text-charcoal-400">' . getCategoryPath($category['id']) . '</p>';
                        $html .= '</div>';
                        $html .= '</div>';
                        
                        // Stats and actions
                        $html .= '<div class="flex items-center space-x-2">';
                        $html .= '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">' . $productCount . ' Products</span>';
                        if ($hasChildren) {
                            $subCount = count($category['children']);
                            $html .= '<span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded">' . $subCount . ' Sub' . ($subCount === 1 ? '' : 's') . '</span>';
                        }
                        $html .= '<span class="px-2 py-1 text-xs font-medium ' . ($category['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . ' rounded">';
                        $html .= $category['active'] ? 'Active' : 'Inactive';
                        $html .= '</span>';
                        if ($category['featured'] ?? false) {
                            $html .= '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded">Featured</span>';
                        }
                        
                        // Action buttons
                        $html .= '<div class="flex space-x-1">';
                        $html .= '<a href="?action=edit&id=' . $category['id'] . '" class="px-2 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-xs"><i class="bi bi-pencil"></i></a>';
                        $html .= '<a href="products.php?filter=' . $category['id'] . '" class="px-2 py-1 bg-green-100 text-green-700 hover:bg-green-200 transition-colors text-xs"><i class="bi bi-eye"></i></a>';
                        $html .= '<a href="?action=delete&id=' . $category['id'] . '" class="px-2 py-1 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-xs" onclick="return confirm(\'Are you sure you want to delete this category?\')"><i class="bi bi-trash"></i></a>';
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= '</div>';
                        
                        // Render children
                        if ($hasChildren) {
                            $html .= renderCategoryTree($category['children'], $productCounts, $level + 1);
                        }
                    }
                    return $html;
                }

                // Mobile optimized category tree
                function renderMobileCategoryTree($categories, $productCounts, $level = 0) {
                    $html = '';
                    foreach ($categories as $category) {
                        $hasChildren = !empty($category['children']);
                        $productCount = $productCounts[$category['id']] ?? 0;
                        
                        $html .= '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 ' . ($level > 0 ? 'ml-' . ($level * 4) : '') . '">';
                        
                        // Category header
                        $html .= '<div class="flex items-start space-x-3 mb-3">';
                        $html .= '<img src="../assets/images/' . htmlspecialchars($category['image']) . '" 
                                       alt="' . htmlspecialchars($category['name']) . '" 
                                       class="w-12 h-12 object-cover rounded-lg flex-shrink-0"
                                       onerror="this.src=\'../assets/images/general/placeholder-small.jpg\'">';
                        
                        $html .= '<div class="flex-1 min-w-0">';
                        $html .= '<div class="flex items-center justify-between">';
                        $html .= '<h4 class="font-medium text-charcoal text-sm truncate">' . htmlspecialchars($category['name']) . '</h4>';
                        if ($level > 0) {
                            $html .= '<i class="bi bi-arrow-return-right text-charcoal-400 text-sm ml-2"></i>';
                        }
                        $html .= '</div>';
                        
                        // Description
                        if (!empty($category['description'])) {
                            $description = htmlspecialchars($category['description']);
                            if (strlen($description) > 60) {
                                $description = substr($description, 0, 60) . '...';
                            }
                            $html .= '<p class="text-xs text-charcoal-400 mt-1">' . $description . '</p>';
                        }
                        
                        // Stats badges
                        $html .= '<div class="flex flex-wrap gap-1 mt-2">';
                        $html .= '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">' . $productCount . ' Products</span>';
                        if ($hasChildren) {
                            $subCount = count($category['children']);
                            $html .= '<span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded">' . $subCount . ' Sub' . ($subCount === 1 ? '' : 's') . '</span>';
                        }
                        $html .= '<span class="px-2 py-1 text-xs font-medium ' . ($category['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . ' rounded">';
                        $html .= $category['active'] ? 'Active' : 'Inactive';
                        $html .= '</span>';
                        if ($category['featured'] ?? false) {
                            $html .= '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded">Featured</span>';
                        }
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= '</div>';
                        
                        // Action buttons
                        $html .= '<div class="flex space-x-2 pt-3 border-t border-gray-200">';
                        $html .= '<a href="?action=edit&id=' . $category['id'] . '" class="flex-1 px-3 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-sm font-medium rounded-lg text-center touch-manipulation">';
                        $html .= '<i class="bi bi-pencil mr-1"></i>Edit';
                        $html .= '</a>';
                        $html .= '<a href="products.php?filter=' . $category['id'] . '" class="flex-1 px-3 py-2 bg-green-100 text-green-700 hover:bg-green-200 transition-colors text-sm font-medium rounded-lg text-center touch-manipulation">';
                        $html .= '<i class="bi bi-eye mr-1"></i>View';
                        $html .= '</a>';
                        $html .= '<a href="?action=delete&id=' . $category['id'] . '" class="px-3 py-2 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-sm font-medium rounded-lg touch-manipulation" onclick="return confirm(\'Are you sure you want to delete this category?\')">';
                        $html .= '<i class="bi bi-trash"></i>';
                        $html .= '</a>';
                        $html .= '</div>';
                        $html .= '</div>';
                        
                        // Render children
                        if ($hasChildren) {
                            $html .= renderMobileCategoryTree($category['children'], $productCounts, $level + 1);
                        }
                    }
                    return $html;
                }

                // Function to render category selection hierarchy
                function renderCategorySelection($categories, $selectedParentId = 0, $editId = 0, $level = 0) {
                    $html = '';
                    foreach ($categories as $category) {
                        // Don't allow selecting self or descendants as parent
                        if ($editId && ($category['id'] == $editId || isDescendantOf($category['id'], $editId, $categories))) {
                            // Skip this category and its children
                            continue;
                        }
                        
                        $hasChildren = !empty($category['children']);
                        $isSelected = ($category['id'] == $selectedParentId);
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                        
                        $html .= '<div class="border border-gray-200 rounded-lg mb-2 hover:bg-gray-50 transition-colors">';
                        $html .= '<label class="flex items-center p-3 cursor-pointer touch-manipulation">';
                        $html .= '<input type="radio" name="parent_id" value="' . $category['id'] . '" class="mr-3 w-4 h-4 flex-shrink-0" ' . ($isSelected ? 'checked' : '') . '>';
                        
                        // Indentation for hierarchy
                        if ($level > 0) {
                            $html .= '<div class="flex items-center mr-2 flex-shrink-0">';
                            for ($i = 0; $i < $level; $i++) {
                                $html .= '<div class="w-3 sm:w-4 h-px bg-gray-300 mr-1"></div>';
                            }
                            $html .= '<i class="bi bi-arrow-return-right text-gray-400 text-xs sm:text-sm"></i>';
                            $html .= '</div>';
                        }
                        
                        // Category image
                        $html .= '<img src="../assets/images/' . htmlspecialchars($category['image']) . '" 
                                       alt="' . htmlspecialchars($category['name']) . '" 
                                       class="w-8 h-8 sm:w-10 sm:h-10 object-cover rounded mr-3 flex-shrink-0"
                                       onerror="this.src=\'../assets/images/general/placeholder-small.jpg\'">';
                        
                        // Category info
                        $html .= '<div class="flex-1 min-w-0">';
                        $html .= '<div class="font-medium text-charcoal text-sm sm:text-base truncate">' . htmlspecialchars($category['name']) . '</div>';
                        $html .= '<div class="text-xs text-gray-500 truncate">';
                        if ($category['description']) {
                            $description = htmlspecialchars($category['description']);
                            $maxLength = $level > 0 ? 30 : 40; // Shorter description for sub-categories
                            if (strlen($description) > $maxLength) {
                                $html .= substr($description, 0, $maxLength) . '...';
                            } else {
                                $html .= $description;
                            }
                        } else {
                            $html .= 'No description';
                        }
                        $html .= '</div>';
                        $html .= '</div>';
                        
                        // Status badges - responsive layout
                        $html .= '<div class="flex flex-col sm:flex-row sm:items-center sm:space-x-2 space-y-1 sm:space-y-0 flex-shrink-0 ml-2">';
                        if ($hasChildren) {
                            $subCount = count($category['children']);
                            $html .= '<span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full text-center">' . $subCount . ' sub' . ($subCount === 1 ? '' : 's') . '</span>';
                        }
                        $html .= '<span class="px-2 py-1 text-xs rounded-full text-center ' . ($category['active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600') . '">';
                        $html .= $category['active'] ? 'Active' : 'Inactive';
                        $html .= '</span>';
                        $html .= '</div>';
                        
                        $html .= '</label>';
                        $html .= '</div>';
                        
                        // Render children
                        if ($hasChildren) {
                            $html .= renderCategorySelection($category['children'], $selectedParentId, $editId, $level + 1);
                        }
                    }
                    return $html;
                }
                ?>


            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-2 sm:p-4" onclick="closeCategoryModal()">
        <div class="bg-white w-full max-w-2xl max-h-screen overflow-y-auto rounded-lg shadow-2xl" onclick="event.stopPropagation()">
            <div class="bg-charcoal text-white px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg sm:text-xl font-bold">
                        <i class="bi bi-<?= $edit_category ? 'pencil-square' : 'plus-circle' ?> mr-2 lg:mr-3"></i>
                        <span class="hidden sm:inline"><?= $edit_category ? 'Edit Category' : 'Add New Category' ?></span>
                        <span class="sm:hidden"><?= $edit_category ? 'Edit' : 'Add' ?></span>
                    </h3>
                    <button type="button" onclick="closeCategoryModal()" class="text-white hover:text-gray-300 text-xl sm:text-2xl p-1 touch-manipulation">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $edit_category ? 'edit' : 'add' ?>">
                <?php if ($edit_category): ?>
                <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
                <?php endif; ?>
                
                <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-charcoal mb-2">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required
                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly rounded-lg text-sm sm:text-base touch-manipulation"
                               placeholder="Enter category name"
                               value="<?= $edit_category ? htmlspecialchars($edit_category['name']) : '' ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-charcoal mb-3">
                            Parent Category
                        </label>
                        
                        <!-- Root Category Option -->
                        <div class="border border-gray-200 rounded-lg mb-2 hover:bg-gray-50 transition-colors">
                            <label class="flex items-center p-3 cursor-pointer touch-manipulation">
                                <input type="radio" name="parent_id" value="0" class="mr-3 w-4 h-4" <?= (!$edit_category || ($edit_category['parent_id'] ?? 0) == 0) ? 'checked' : '' ?>>
                                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-folly rounded mr-3 flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-house text-white text-sm sm:text-lg"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-charcoal text-sm sm:text-base">Root Category</div>
                                    <div class="text-xs text-gray-500">No parent - top-level category</div>
                                </div>
                                <span class="px-2 py-1 bg-folly-100 text-folly-700 text-xs rounded-full flex-shrink-0">Root</span>
                            </label>
                        </div>
                        
                        <?php if (!empty($categories)): ?>
                        <!-- Category Hierarchy -->
                        <div class="max-h-48 sm:max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-2 bg-gray-50">
                            <div class="text-xs text-gray-600 mb-2 px-2">Select an existing category as parent:</div>
                            <?php 
                            $editId = $edit_category ? $edit_category['id'] : 0;
                            $selectedParentId = $edit_category ? ($edit_category['parent_id'] ?? 0) : 0;
                            $hierarchy = getCategoryHierarchy(true);
                            echo renderCategorySelection($hierarchy, $selectedParentId, $editId, 0);
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <p class="text-xs text-charcoal-400 mt-3">
                            <i class="bi bi-info-circle mr-1"></i>
                            Choose a parent category to create a sub-category, or select "Root Category" for a top-level category
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-charcoal mb-2">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly rounded-lg text-sm sm:text-base touch-manipulation"
                                  placeholder="Describe your category..."><?= $edit_category ? htmlspecialchars($edit_category['description']) : '' ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-charcoal mb-2">Category Image</label>
                        <input type="file" name="image" id="imageUpload" accept="image/*"
                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly rounded-lg text-sm sm:text-base touch-manipulation">
                        <p class="text-xs text-charcoal-400 mt-2">Upload JPG, PNG, GIF, or WebP. Max file size: 5MB</p>
                        <?php if ($edit_category && $edit_category['image']): ?>
                        <div class="mt-3">
                            <p class="text-sm text-charcoal-600 mb-2">Current image:</p>
                            <img src="../assets/images/<?= htmlspecialchars($edit_category['image']) ?>" 
                                 alt="Current category image" 
                                 class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-lg border border-gray-200"
                                 onerror="this.src='../assets/images/general/placeholder-small.jpg'">
                        </div>
                        <?php endif; ?>
                        <div id="imagePreview" class="mt-3 hidden">
                            <p class="text-sm text-charcoal-600 mb-2">New image preview:</p>
                            <img id="previewImg" src="" alt="Preview" class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-lg border border-gray-200">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-charcoal mb-2">Manual Image Path (Optional)</label>
                        <input type="text" name="image_path"
                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly rounded-lg text-sm sm:text-base touch-manipulation"
                               placeholder="categories/image.jpg"
                               value="<?= $edit_category ? htmlspecialchars($edit_category['image']) : '' ?>">
                        <p class="text-xs text-charcoal-400 mt-2">Manual path relative to assets/images/ directory</p>
                    </div>
                    
                    <div class="space-y-3 sm:space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="active" value="1" id="activeCheckbox"
                                   class="mr-3 w-4 h-4 touch-manipulation" <?= !$edit_category || $edit_category['active'] ? 'checked' : '' ?>>
                            <label for="activeCheckbox" class="text-sm font-medium text-charcoal touch-manipulation">Category is Active</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="featured" value="1" id="featuredCheckbox"
                                   class="mr-3 w-4 h-4 touch-manipulation" <?= $edit_category && ($edit_category['featured'] ?? false) ? 'checked' : '' ?>>
                            <label for="featuredCheckbox" class="text-sm font-medium text-charcoal touch-manipulation">Feature on Homepage</label>
                            <div class="ml-2 group relative">
                                <i class="bi bi-info-circle text-gray-400 cursor-help text-sm touch-manipulation"></i>
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                    Featured categories appear on the homepage
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0">
                        <p class="text-charcoal-400 text-xs sm:text-sm text-center sm:text-left">All required fields (*) must be filled</p>
                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                            <button type="button" onclick="closeCategoryModal()" class="px-4 sm:px-6 py-2 sm:py-2 border border-gray-300 bg-white text-charcoal hover:bg-gray-50 transition-colors rounded-lg text-sm sm:text-base font-medium touch-manipulation">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 sm:px-6 py-2 sm:py-2 bg-folly text-white hover:bg-folly-600 transition-colors font-medium rounded-lg text-sm sm:text-base touch-manipulation">
                                <?= $edit_category ? 'Update Category' : 'Create Category' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mobile menu functions
        function openMobileMenu() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileMenuOverlay');
            if (sidebar && overlay) {
                overlay.classList.remove('hidden');
                sidebar.classList.remove('-translate-x-full');
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
        
        // Auto-close mobile menu on window resize to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) { // lg breakpoint
                closeMobileMenu();
            }
        });
        
        // Close mobile menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMobileMenu();
                closeCategoryModal();
            }
        });
    
        // Modal control functions
        function openCategoryModal() {
            const modal = document.getElementById('categoryModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeCategoryModal() {
            const modal = document.getElementById('categoryModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = '';
                
                // Reset form if it's not an edit
                const form = modal.querySelector('form');
                if (form && !<?php echo $edit_category ? 'true' : 'false'; ?>) {
                    form.reset();
                    const preview = document.getElementById('imagePreview');
                    if (preview) preview.classList.add('hidden');
                }
            }
        }

        // Auto-open modal if editing
        <?php if ($edit_category): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                openCategoryModal();
            }, 100);
        });
        <?php endif; ?>
        
        // Image preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('imageUpload');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Validate file size (5MB limit)
                        if (file.size > 5 * 1024 * 1024) {
                            alert('File size must be less than 5MB');
                            this.value = '';
                            return;
                        }
                        
                        // Validate file type
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        if (!allowedTypes.includes(file.type)) {
                            alert('Please select a valid image file (JPG, PNG, GIF, WebP)');
                            this.value = '';
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('previewImg').src = e.target.result;
                            document.getElementById('imagePreview').classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                        
                        // Clear manual path if file is selected
                        document.querySelector('input[name="image_path"]').value = '';
                    } else {
                        document.getElementById('imagePreview').classList.add('hidden');
                    }
                });
            }
        });
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCategoryModal();
            }
        });
    </script>
</body>
</html>