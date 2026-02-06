<?php
// Prevent caching in admin area
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/auth.php');
    exit;
}

// Include main functions
require_once '../includes/functions.php';
require_once '../includes/admin_helpers.php';

// CSRF token setup
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to render category options for products dropdown
function renderCategoryOptionsForProducts($categories, $selectedId = 0, $level = 0) {
    $html = '';
    foreach ($categories as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
        $selected = ($category['id'] == $selectedId) ? 'selected' : '';
        $html .= '<option value="' . $category['id'] . '" ' . $selected . '>';
        $html .= $indent . htmlspecialchars($category['name']);
        $html .= '</option>';
        
        // Render children
        if (!empty($category['children'])) {
            $html .= renderCategoryOptionsForProducts($category['children'], $selectedId, $level + 1);
        }
    }
    return $html;
}

// Helper function to render category options for filter dropdown
function renderCategoryOptionsForFilter($categories, $selectedId = 0, $level = 0) {
    $html = '';
    foreach ($categories as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
        $prefix = $level === 0 ? 'Category: ' : 'Sub: ';
        $selected = ($category['id'] == $selectedId) ? 'selected' : '';
        $html .= '<option value="' . $category['id'] . '" ' . $selected . '>';
        $html .= $prefix . $indent . htmlspecialchars($category['name']);
        $html .= '</option>';
        
        // Render children
        if (!empty($category['children'])) {
            $html .= renderCategoryOptionsForFilter($category['children'], $selectedId, $level + 1);
        }
    }
    return $html;
}

// Function to render hierarchical category selection for products
function renderProductCategorySelection($categories, $selectedId = 0, $level = 0) {
    $html = '';
    foreach ($categories as $category) {
        $hasChildren = !empty($category['children']);
        $isSelected = ($category['id'] == $selectedId);
        
        $html .= '<div class="border border-gray-200 rounded-lg mb-2 hover:bg-gray-50 transition-colors">';
        $html .= '<label class="flex items-center p-3 cursor-pointer">';
        $html .= '<input type="radio" name="category_id" value="' . $category['id'] . '" class="mr-3" ' . ($isSelected ? 'checked' : '') . ' required>';
        
        // Indentation for hierarchy
        if ($level > 0) {
            $html .= '<div class="flex items-center mr-2">';
            for ($i = 0; $i < $level; $i++) {
                $html .= '<div class="w-4 border-l border-gray-300 mr-2"></div>';
            }
            $html .= '<i class="bi bi-arrow-return-right text-gray-400 mr-2"></i>';
            $html .= '</div>';
        }
        
        // Category image
        $html .= '<img src="../assets/images/' . htmlspecialchars($category['image']) . '" 
                       alt="' . htmlspecialchars($category['name']) . '" 
                       class="w-10 h-10 object-cover rounded mr-3"
                       onerror="this.src=\'../assets/images/general/placeholder.jpg\'">';
        
        // Category info
        $html .= '<div class="flex-1">';
        $html .= '<div class="font-medium text-charcoal">' . htmlspecialchars($category['name']) . '</div>';
        $html .= '<div class="text-sm text-charcoal-400">';
        if ($category['description']) {
            $html .= htmlspecialchars(substr($category['description'], 0, 50));
            if (strlen($category['description']) > 50) $html .= '...';
        } else {
            $html .= 'No description';
        }
        $html .= '</div>';
        $html .= '</div>';
        
        // Status badges
        $html .= '<div class="flex items-center space-x-2">';
        if ($hasChildren) {
            $subCount = count($category['children']);
            $html .= '<span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">' . $subCount . ' sub' . ($subCount === 1 ? '' : 's') . '</span>';
        }
        $html .= '<span class="px-2 py-1 text-xs rounded-full ' . ($category['active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600') . '">';
        $html .= $category['active'] ? 'Active' : 'Inactive';
        $html .= '</span>';
        $html .= '</div>';
        
        $html .= '</label>';
        $html .= '</div>';
        
        // Render children
        if ($hasChildren) {
            $html .= renderProductCategorySelection($category['children'], $selectedId, $level + 1);
        }
    }
    return $html;
}

// Load data
$products = adminGetAllProducts();
$categories = adminGetAllCategories();
$settings = getSettings();

// Handle form submissions
$message = '';
$message_type = '';

// Handle redirect messages (after form submission)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = $_GET['message'];
    $message_type = $_GET['type'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    $postedToken = $_POST['csrf_token'] ?? '';
    if (empty($postedToken) || !hash_equals($_SESSION['admin_csrf_token'], $postedToken)) {
        http_response_code(403);
        $message = 'Invalid CSRF token.';
        $message_type = 'error';
    } else {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Handle main image upload
                $image_path = 'products/placeholder.jpg'; // Default
                
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../assets/images/products/';
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_info = pathinfo($_FILES['image']['name']);
                    $extension = strtolower($file_info['extension']);
                    
                    // Validate file type
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($extension, $allowed_types)) {
                        // Generate unique filename
                        $filename = generateSlug($_POST['name']) . '_main_' . time() . '.' . $extension;
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            $image_path = 'products/' . $filename;
                        } else {
                            $message = 'Failed to upload main image. Product added with placeholder image.';
                            $message_type = 'warning';
                        }
                    } else {
                        $message = 'Invalid image format. Only JPG, PNG, GIF, and WebP are allowed. Product added with placeholder image.';
                        $message_type = 'warning';
                    }
                } elseif (!empty($_POST['image_path'])) {
                    // Use manual path if provided
                    $image_path = $_POST['image_path'];
                }
                
                // Handle multiple additional images upload
                $additional_images = array();
                if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['tmp_name'])) {
                    $upload_dir = '../assets/images/products/';
                    foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK && !empty($tmp_name)) {
                            $file_info = pathinfo($_FILES['additional_images']['name'][$key]);
                            $extension = strtolower($file_info['extension']);
                            // Validate file type
                            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            if (in_array($extension, $allowed_types)) {
                                // Generate unique filename
                                $filename = generateSlug($_POST['name']) . '_' . ($key + 1) . '_' . time() . '.' . $extension;
                                $upload_path = $upload_dir . $filename;
                                if (move_uploaded_file($tmp_name, $upload_path)) {
                                    $additional_images[] = 'products/' . $filename;
                                }
                            }
                        }
                    }
                }
                
                $max_id = 0;
                foreach ($products as $product) {
                    if ($product['id'] > $max_id) {
                        $max_id = $product['id'];
                    }
                }
                
                // Handle multi-currency pricing
                $prices = array();
                $default_price = 0;
                if (isset($_POST['prices']) && is_array($_POST['prices'])) {
                    foreach ($_POST['prices'] as $currency => $price) {
                        if (!empty($price) && is_numeric($price)) {
                            $prices[$currency] = (float)$price;
                            // Set default price for backward compatibility
                            if (isset($settings['currencies'])) {
                                foreach ($settings['currencies'] as $curr) {
                                    if ($curr['code'] === $currency && $curr['default']) {
                                        $default_price = (float)$price;
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Fallback to single price if no multi-currency prices set
                if (empty($prices) && !empty($_POST['price'])) {
                    $default_price = (float)$_POST['price'];
                    $default_currency = $settings['currency_code'] ?? 'GBP';
                    $prices[$default_currency] = $default_price;
                }
                
                // Handle sizes
                $has_sizes = isset($_POST['has_sizes']) && $_POST['has_sizes'] === '1';
                $available_sizes = array();
                if ($has_sizes && isset($_POST['available_sizes']) && is_array($_POST['available_sizes'])) {
                    $available_sizes = $_POST['available_sizes'];
                }
                
                // Handle colors
                $has_colors = isset($_POST['has_colors']) && $_POST['has_colors'] === '1';
                $available_colors = array();
                if ($has_colors && isset($_POST['available_colors']) && is_array($_POST['available_colors'])) {
                    $available_colors = $_POST['available_colors'];
                }
                
                // Handle features
                $features = array();
                if (isset($_POST['features']) && is_array($_POST['features'])) {
                    foreach ($_POST['features'] as $feature) {
                        if (!empty($feature['name']) && !empty($feature['value'])) {
                            $features[] = array(
                                'name' => sanitizeInput($feature['name']),
                                'value' => sanitizeInput($feature['value'])
                            );
                        }
                    }
                }
                
                $new_product = array(
                    'id' => $max_id + 1,
                    'name' => $_POST['name'],
                    'slug' => generateSlug($_POST['name']),
                    'price' => $default_price, // Keep for backward compatibility
                    'prices' => $prices,
                    'category_id' => (int)$_POST['category_id'],
                    'description' => $_POST['description'],
                    'image' => $image_path,
                    'images' => $additional_images, // Additional images array
                    'stock' => (int)$_POST['stock'],
                    'featured' => isset($_POST['featured']),
                    'active' => isset($_POST['active']),
                    'has_sizes' => $has_sizes,
                    'available_sizes' => $available_sizes,
                    'has_colors' => $has_colors,
                    'available_colors' => $available_colors,
                    'features' => $features
                );
                $products[] = $new_product;
                
                if (!$message) {
                    $message = 'Product added successfully!';
                    $message_type = 'success';
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                foreach ($products as &$product) {
                    if ($product['id'] === $id) {
                        // Handle image upload for edit
                        $image_path = $product['image']; // Keep existing image by default
                        
                        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                            $upload_dir = '../assets/images/products/';
                            
                            // Create directory if it doesn't exist
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $file_info = pathinfo($_FILES['image']['name']);
                            $extension = strtolower($file_info['extension']);
                            
                            // Validate file type
                            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            if (in_array($extension, $allowed_types)) {
                                // Generate unique filename
                                $filename = generateSlug($_POST['name']) . '_main_' . time() . '.' . $extension;
                                $upload_path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                                    // Delete old image if it's not a placeholder
                                    if ($product['image'] !== 'products/placeholder.jpg' && file_exists('../assets/images/' . $product['image'])) {
                                        unlink('../assets/images/' . $product['image']);
                                    }
                                    $image_path = 'products/' . $filename;
                                } else {
                                    $message = 'Failed to upload new image. Product updated with existing image.';
                                    $message_type = 'warning';
                                }
                            } else {
                                $message = 'Invalid image format. Only JPG, PNG, GIF, and WebP are allowed. Product updated with existing image.';
                                $message_type = 'warning';
                            }
                        } elseif (!empty($_POST['image_path']) && $_POST['image_path'] !== $product['image']) {
                            // Use manual path if provided and different
                            $image_path = $_POST['image_path'];
                        }
                        
                        // Handle additional images
                        $additional_images = isset($product['images']) ? $product['images'] : array();
                        
                        // Handle new additional images upload
                        if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['tmp_name'])) {
                            $upload_dir = '../assets/images/products/';
                            
                            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                                if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK && !empty($tmp_name)) {
                                    $file_info = pathinfo($_FILES['additional_images']['name'][$key]);
                                    $extension = strtolower($file_info['extension']);
                                    
                                    // Validate file type
                                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                    if (in_array($extension, $allowed_types)) {
                                        // Generate unique filename
                                        $filename = generateSlug($_POST['name']) . '_' . (count($additional_images) + $key + 1) . '_' . time() . '.' . $extension;
                                        $upload_path = $upload_dir . $filename;
                                        
                                        if (move_uploaded_file($tmp_name, $upload_path)) {
                                            $additional_images[] = 'products/' . $filename;
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Handle image deletion
                        if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                            foreach ($_POST['delete_images'] as $image_to_delete) {
                                // Remove from additional_images array
                                $additional_images = array_filter($additional_images, function($img) use ($image_to_delete) {
                                    return $img !== $image_to_delete;
                                });
                                
                                // Delete physical file
                                if (file_exists('../assets/images/' . $image_to_delete)) {
                                    unlink('../assets/images/' . $image_to_delete);
                                }
                            }
                            // Reindex array
                            $additional_images = array_values($additional_images);
                        }
                        
                        // Handle multi-currency pricing
                        $prices = isset($product['prices']) ? $product['prices'] : array();
                        $default_price = $product['price'] ?? 0;
                        
                        if (isset($_POST['prices']) && is_array($_POST['prices'])) {
                            $prices = array();
                            foreach ($_POST['prices'] as $currency => $price) {
                                if (!empty($price) && is_numeric($price)) {
                                    $prices[$currency] = (float)$price;
                                    // Set default price for backward compatibility
                                    if (isset($settings['currencies'])) {
                                        foreach ($settings['currencies'] as $curr) {
                                            if ($curr['code'] === $currency && $curr['default']) {
                                                $default_price = (float)$price;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Fallback to single price if no multi-currency prices set
                        if (empty($prices) && !empty($_POST['price'])) {
                            $default_price = (float)$_POST['price'];
                            $default_currency = $settings['currency_code'] ?? 'GBP';
                            $prices[$default_currency] = $default_price;
                        }
                        
                        // Handle sizes
                        $has_sizes = isset($_POST['has_sizes']) && $_POST['has_sizes'] === '1';
                        $available_sizes = array();
                        if ($has_sizes && isset($_POST['available_sizes']) && is_array($_POST['available_sizes'])) {
                            $available_sizes = $_POST['available_sizes'];
                        }
                        
                        // Handle colors
                        $has_colors = isset($_POST['has_colors']) && $_POST['has_colors'] === '1';
                        $available_colors = array();
                        if ($has_colors && isset($_POST['available_colors']) && is_array($_POST['available_colors'])) {
                            $available_colors = $_POST['available_colors'];
                        }
                        
                        // Handle features
                        $features = array();
                        if (isset($_POST['features']) && is_array($_POST['features'])) {
                            foreach ($_POST['features'] as $feature) {
                                if (!empty($feature['name']) && !empty($feature['value'])) {
                                    $features[] = array(
                                        'name' => sanitizeInput($feature['name']),
                                        'value' => sanitizeInput($feature['value'])
                                    );
                                }
                            }
                        }
                        
                        $product['name'] = $_POST['name'];
                        $product['slug'] = generateSlug($_POST['name']);
                        $product['price'] = $default_price; // Keep for backward compatibility
                        $product['prices'] = $prices;
                        $product['category_id'] = (int)$_POST['category_id'];
                        $product['description'] = $_POST['description'];
                        $product['image'] = $image_path;
                        $product['images'] = $additional_images; // Additional images array
                        $product['stock'] = (int)$_POST['stock'];
                        $product['featured'] = isset($_POST['featured']);
                        $product['active'] = isset($_POST['active']);
                        $product['has_sizes'] = $has_sizes;
                        $product['available_sizes'] = $available_sizes;
                        $product['has_colors'] = $has_colors;
                        $product['available_colors'] = $available_colors;
                        $product['features'] = $features;
                        break;
                    }
                }
                
                if (!$message) {
                    $message = 'Product updated successfully!';
                    $message_type = 'success';
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $new_products = array();
                foreach ($products as $product) {
                    if ($product['id'] !== $id) {
                        $new_products[] = $product;
                    } else {
                        // Delete associated image files
                        if ($product['image'] !== 'products/placeholder.jpg' && file_exists('../assets/images/' . $product['image'])) {
                            unlink('../assets/images/' . $product['image']);
                        }
                        
                        // Delete additional images
                        if (isset($product['images']) && is_array($product['images'])) {
                            foreach ($product['images'] as $image_path) {
                                if (file_exists('../assets/images/' . $image_path)) {
                                    unlink('../assets/images/' . $image_path);
                                }
                            }
                        }
                    }
                }
                $products = $new_products;
                $message = 'Product deleted successfully!';
                $message_type = 'success';
                break;
                
            case 'bulk_action':
                $selected_ids = isset($_POST['selected_products']) ? $_POST['selected_products'] : array();
                $bulk_action = $_POST['bulk_action'];
                
                if (!empty($selected_ids)) {
                    foreach ($products as &$product) {
                        if (in_array($product['id'], $selected_ids)) {
                            switch ($bulk_action) {
                                case 'activate':
                                    $product['active'] = true;
                                    break;
                                case 'deactivate':
                                    $product['active'] = false;
                                    break;
                                case 'feature':
                                    $product['featured'] = true;
                                    break;
                                case 'unfeature':
                                    $product['featured'] = false;
                                    break;
                                case 'delete':
                                    // Mark for deletion
                                    $product['_delete'] = true;
                                    break;
                            }
                        }
                    }
                    
                    if ($bulk_action === 'delete') {
                        $delete_products = array();
                        foreach ($products as $p) {
                            if (!isset($p['_delete'])) {
                                $delete_products[] = $p;
                            }
                        }
                        $products = $delete_products;
                    }
                    
                    $message = 'Bulk action completed successfully!';
                    $message_type = 'success';
                }
                break;
        }
        
        // Save changes
        $save_result = file_put_contents('../data/products.json', json_encode($products, JSON_PRETTY_PRINT));
        
        if ($save_result === false) {
            $message = 'Failed to save changes to products file!';
            $message_type = 'error';
        }
        
        // Redirect to prevent form resubmission
        header('Location: products.php?message=' . urlencode($message) . '&type=' . $message_type);
        exit;
    }
    // End CSRF else block
}
}

// Handle GET actions (like individual delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_products = array();
    foreach ($products as $product) {
        if ($product['id'] !== $id) {
            $new_products[] = $product;
        } else {
            // Delete associated image files
            if ($product['image'] !== 'products/placeholder.jpg' && file_exists('../assets/images/' . $product['image'])) {
                unlink('../assets/images/' . $product['image']);
            }
            
            // Delete additional images
            if (isset($product['images']) && is_array($product['images'])) {
                foreach ($product['images'] as $image_path) {
                    if (file_exists('../assets/images/' . $image_path)) {
                        unlink('../assets/images/' . $image_path);
                    }
                }
            }
        }
    }
    $products = $new_products;
    
    // Attempt to save the updated products list
    $save_result = file_put_contents('../data/products.json', json_encode($products, JSON_PRETTY_PRINT));
    
    if ($save_result === false) {
        // Redirect with error message if save failed
        header('Location: products.php?message=' . urlencode('Failed to delete product - could not save changes!') . '&type=error');
        exit;
    }
    
    // Redirect to prevent form resubmission
    header('Location: products.php?message=' . urlencode('Product deleted successfully!') . '&type=success');
    exit;
}

// Filtering and sorting
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Apply filters
$filtered_products = $products;

if ($filter !== 'all') {
    switch ($filter) {
        case 'active':
            $filtered_products = array();
            foreach ($products as $p) {
                if ($p['active']) $filtered_products[] = $p;
            }
            break;
        case 'inactive':
            $filtered_products = array();
            foreach ($products as $p) {
                if (!$p['active']) $filtered_products[] = $p;
            }
            break;
        case 'featured':
            $filtered_products = array();
            foreach ($products as $p) {
                if ($p['featured']) $filtered_products[] = $p;
            }
            break;
        case 'low_stock':
            $filtered_products = array();
            foreach ($products as $p) {
                if ($p['stock'] <= 10) $filtered_products[] = $p;
            }
            break;
        default:
            if (is_numeric($filter)) {
                $filtered_products = array();
                foreach ($products as $p) {
                    if ($p['category_id'] == $filter) $filtered_products[] = $p;
                }
            }
    }
}

// Apply search
if ($search) {
    $search_results = array();
    foreach ($filtered_products as $p) {
        if (stripos($p['name'], $search) !== false || 
            stripos($p['description'], $search) !== false ||
            stripos($p['slug'], $search) !== false) {
            $search_results[] = $p;
        }
    }
    $filtered_products = $search_results;
}

// Apply sorting
usort($filtered_products, function($a, $b) use ($sort) {
    switch ($sort) {
        case 'name':
            return strcasecmp($a['name'], $b['name']);
        case 'price_asc':
            return $a['price'] <=> $b['price'];
        case 'price_desc':
            return $b['price'] <=> $a['price'];
        case 'stock_asc':
            return $a['stock'] <=> $b['stock'];
        case 'stock_desc':
            return $b['stock'] <=> $a['stock'];
        case 'newest':
            return $b['id'] <=> $a['id'];
        case 'oldest':
            return $a['id'] <=> $b['id'];
        default:
            return 0;
    }
});

// Get product for editing
$edit_product = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    foreach ($products as $product) {
        if ($product['id'] === $edit_id) {
            $edit_product = $product;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Admin</title>
    
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
            
            .mobile-table-card .table-row {
                display: block;
            }
            
            .mobile-table-card .table-cell {
                display: block;
                padding: 0.5rem 0;
                border-bottom: 1px solid #f3f4f6;
            }
            
            .mobile-table-card .table-cell:last-child {
                border-bottom: none;
            }
            
            .mobile-table-card .cell-label {
                font-weight: 600;
                color: #374151;
                display: block;
                margin-bottom: 0.25rem;
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
                <?php $activePage = 'products'; include __DIR__ . '/partials/nav_links_desktop.php'; ?>
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
                <?php $activePage = 'products'; include __DIR__ . '/partials/nav_links_mobile.php'; ?>
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
                        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-charcoal truncate">Products Management</h1>
                    </div>
                    <button type="button" onclick="openProductModal()" class="px-3 sm:px-4 lg:px-6 py-2 lg:py-3 bg-folly text-white hover:bg-folly-600 transition-colors font-medium text-sm lg:text-base touch-manipulation scale-hover whitespace-nowrap">
                        <i class="bi bi-plus mr-1 lg:mr-2"></i>
                        <span class="hidden sm:inline">Add </span>Product
                    </button>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="flex-1 p-3 sm:p-4 lg:p-6 overflow-auto">
                <!-- Quick Start Guide for Empty State -->
                <?php if (empty($products)): ?>
                <div class="bg-blue-50 border border-blue-200 p-4 lg:p-6 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex flex-col lg:flex-row lg:items-start">
                        <div class="flex-1">
                            <h3 class="text-base lg:text-lg font-semibold text-blue-900 mb-2">
                                <i class="bi bi-lightbulb mr-2"></i>Welcome! Let's add your first product
                            </h3>
                            <p class="text-blue-800 mb-2 text-sm lg:text-base">Getting started is easy! Click the "Add Product" button above to create your first product listing.</p>
                            <p class="text-blue-700 text-xs lg:text-sm">
                                <i class="bi bi-info-circle mr-1"></i>
                                Tip: Make sure you have at least one category created before adding products.
                            </p>
                        </div>
                        <div class="mt-4 lg:mt-0 lg:ml-6 text-center">
                            <i class="bi bi-box-seam text-4xl lg:text-6xl text-blue-400"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages -->
                <?php if ($message): ?>
                <div class="<?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?> border p-3 lg:p-4 mb-4 lg:mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="bi bi-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> mr-2 flex-shrink-0"></i>
                        <span class="text-sm lg:text-base"><?= htmlspecialchars($message) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filters and Search -->
                <div class="bg-white border border-gray-200 mb-4 lg:mb-6 rounded-lg">
                    <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-charcoal text-sm lg:text-base">Filter & Search Products</h3>
                    </div>
                    <div class="p-4 lg:p-6">
                        <form method="GET" class="space-y-4">
                            <!-- Mobile: Stacked layout, Desktop: Grid layout -->
                            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                                <div class="lg:col-span-1">
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Filter by Status</label>
                                    <select name="filter" class="w-full px-3 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" onchange="this.form.submit()">
                                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Products</option>
                                        <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="featured" <?= $filter === 'featured' ? 'selected' : '' ?>>Featured</option>
                                        <option value="low_stock" <?= $filter === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                                        <?php 
                                        $hierarchy = getCategoryHierarchy(true);
                                        echo renderCategoryOptionsForFilter($hierarchy, $filter, 0);
                                        ?>
                                    </select>
                                </div>
                                <div class="lg:col-span-1">
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Sort by</label>
                                    <select name="sort" class="w-full px-3 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" onchange="this.form.submit()">
                                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
                                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low-High)</option>
                                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High-Low)</option>
                                        <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>Stock (Low-High)</option>
                                        <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>Stock (High-Low)</option>
                                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                    </select>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="block text-xs lg:text-sm font-medium text-charcoal-600 mb-2">Search Products</label>
                                    <input type="text" name="search" class="w-full px-3 py-2 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="w-full px-4 py-2 bg-folly text-white hover:bg-folly-600 transition-colors font-medium text-sm lg:text-base touch-manipulation scale-hover">
                                        <i class="bi bi-search mr-1 lg:mr-2"></i>Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <form method="POST" id="bulkForm">
                    <input type="hidden" name="action" value="bulk_action">
                    <div class="bg-white border border-gray-200 mb-4 lg:mb-6 rounded-lg">
                        <div class="px-4 lg:px-6 py-3 lg:py-4">
                            <div class="flex flex-col space-y-3 lg:space-y-0 lg:flex-row lg:items-center lg:justify-between lg:space-x-4">
                                <div class="flex flex-col space-y-3 lg:space-y-0 lg:flex-row lg:items-center lg:space-x-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="selectAll" class="mr-2 touch-manipulation">
                                        <label for="selectAll" class="text-xs lg:text-sm font-medium text-charcoal">Select All</label>
                                    </div>
                                    <select name="bulk_action" class="px-3 py-2 text-sm lg:text-base border border-gray-300 bg-white text-charcoal focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation">
                                        <option value="">Bulk Actions</option>
                                        <option value="activate">Activate Selected</option>
                                        <option value="deactivate">Deactivate Selected</option>
                                        <option value="feature">Mark as Featured</option>
                                        <option value="unfeature">Remove from Featured</option>
                                        <option value="delete">Delete Selected</option>
                                    </select>
                                </div>
                                <button type="submit" class="w-full lg:w-auto px-4 py-2 bg-charcoal text-white hover:bg-charcoal-700 transition-colors text-sm lg:text-base touch-manipulation scale-hover" 
                                        onclick="return confirm('Are you sure you want to perform this action?')">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="bg-white border border-gray-200 mb-4 lg:mb-6 rounded-lg">
                        <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold text-charcoal text-sm lg:text-base">Products List</h3>
                                <span class="px-2 lg:px-3 py-1 bg-gray-100 text-charcoal-600 text-xs lg:text-sm font-medium rounded">
                                    <?= count($filtered_products) ?> of <?= count($products) ?> products
                                </span>
                            </div>
                        </div>

                        <?php if (empty($filtered_products)): ?>
                        <div class="px-4 lg:px-6 py-8 text-center text-charcoal-400">
                            <i class="bi bi-box-seam text-4xl mb-4 block"></i>
                            <p class="text-sm lg:text-base">No products found</p>
                        </div>
                        <?php else: ?>
                        
                        <!-- Desktop Table View -->
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left">
                                            <input type="checkbox" id="selectAllHeader" class="rounded touch-manipulation">
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Image</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Product Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Stock</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <?php foreach ($filtered_products as $product): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input type="checkbox" name="selected_products[]" 
                                                   value="<?= $product['id'] ?>" class="rounded product-checkbox touch-manipulation">
                                        </td>
                                        <td class="px-6 py-4">
                                            <img src="../assets/images/<?= htmlspecialchars($product['image']) ?>" 
                                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                                 class="w-12 h-12 object-cover rounded"
                                                 onerror="this.src='../assets/images/general/placeholder.jpg'">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-charcoal"><?= htmlspecialchars($product['name']) ?></div>
                                                <div class="text-sm text-charcoal-400"><?= htmlspecialchars($product['slug']) ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                            $category = array_filter($categories, fn($c) => $c['id'] == $product['category_id']);
                                            if ($category) {
                                                $categoryPath = getCategoryPath($product['category_id']);
                                                echo '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-charcoal rounded">' . htmlspecialchars($categoryPath) . '</span>';
                                            } else {
                                                echo '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">Unknown</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-charcoal">
                                                <?php 
                                                // Display price - check if multi-currency
                                                if (isset($product['prices']) && is_array($product['prices'])) {
                                                    $default_currency = $settings['currency_code'] ?? 'GBP';
                                                    $display_price = $product['prices'][$default_currency] ?? $product['price'] ?? 0;
                                                    echo $settings['currency_symbol'] ?? '£';
                                                    echo number_format($display_price, 2);
                                                    if (count($product['prices']) > 1) {
                                                        echo '<div class="text-xs text-charcoal-400">(' . count($product['prices']) . ' currencies)</div>';
                                                    }
                                                } else {
                                                    echo $settings['currency_symbol'] ?? '£';
                                                    echo number_format($product['price'] ?? 0, 2);
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded <?= $product['stock'] <= 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                                <?= $product['stock'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <span class="px-2 py-1 text-xs font-medium rounded <?= $product['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                    <?= $product['active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                                <?php if ($product['featured']): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded block">Featured</span>
                                                <?php endif; ?>
                                                <?php if (isset($product['has_sizes']) && $product['has_sizes']): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded block" title="Has sizes: <?= implode(', ', $product['available_sizes'] ?? []) ?>">
                                                    Sizes
                                                </span>
                                                <?php endif; ?>
                                                <?php if (isset($product['has_colors']) && $product['has_colors']): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-indigo-100 text-indigo-800 rounded block" title="Has colors: <?= implode(', ', $product['available_colors'] ?? []) ?>">
                                                    Colors
                                                </span>
                                                <?php endif; ?>
                                                <?php if (isset($product['features']) && !empty($product['features'])): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded block" title="<?= count($product['features']) ?> features">
                                                    <?= count($product['features']) ?> Features
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <a href="?action=edit&id=<?= $product['id'] ?>" 
                                                   class="px-3 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-sm rounded touch-manipulation"
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="px-3 py-1 bg-green-100 text-green-700 hover:bg-green-200 transition-colors text-sm rounded touch-manipulation"
                                                        onclick="viewProduct(<?= htmlspecialchars(json_encode($product)) ?>)"
                                                        title="View">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <a href="?action=delete&id=<?= $product['id'] ?>" 
                                                   class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-sm rounded touch-manipulation"
                                                   onclick="return confirm('Are you sure you want to delete \'<?= addslashes($product['name']) ?>\'?\n\nThis action cannot be undone and will also delete all associated images.')"
                                                   title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="lg:hidden space-y-3 p-3">
                            <?php foreach ($filtered_products as $product): ?>
                            <div class="border border-gray-200 rounded-lg p-4 bg-white mobile-table-card">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <input type="checkbox" name="selected_products[]" 
                                               value="<?= $product['id'] ?>" class="product-checkbox touch-manipulation mt-1">
                                    </div>
                                    <div class="flex-shrink-0">
                                        <img src="../assets/images/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="w-16 h-16 object-cover rounded"
                                             onerror="this.src='../assets/images/general/placeholder.jpg'">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-sm font-semibold text-charcoal truncate"><?= htmlspecialchars($product['name']) ?></h4>
                                                <p class="text-xs text-charcoal-400 truncate"><?= htmlspecialchars($product['slug']) ?></p>
                                            </div>
                                            <div class="flex space-x-1 ml-2">
                                                <a href="?action=edit&id=<?= $product['id'] ?>" 
                                                   class="p-2 bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors text-sm rounded touch-manipulation"
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="p-2 bg-green-100 text-green-700 hover:bg-green-200 transition-colors text-sm rounded touch-manipulation"
                                                        onclick="viewProduct(<?= htmlspecialchars(json_encode($product)) ?>)"
                                                        title="View">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <a href="?action=delete&id=<?= $product['id'] ?>" 
                                                   class="p-2 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-sm rounded touch-manipulation"
                                                   onclick="return confirm('Are you sure you want to delete \'<?= addslashes($product['name']) ?>\'?\n\nThis action cannot be undone and will also delete all associated images.')"
                                                   title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3 space-y-2">
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-charcoal-600">Category:</span>
                                                <?php 
                                                $category = array_filter($categories, fn($c) => $c['id'] == $product['category_id']);
                                                if ($category) {
                                                    $categoryPath = getCategoryPath($product['category_id']);
                                                    echo '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-charcoal rounded">' . htmlspecialchars($categoryPath) . '</span>';
                                                } else {
                                                    echo '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">Unknown</span>';
                                                }
                                                ?>
                                            </div>
                                            
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-charcoal-600">Price:</span>
                                                <span class="text-sm font-semibold text-charcoal">
                                                    <?php 
                                                    // Display price - check if multi-currency
                                                    if (isset($product['prices']) && is_array($product['prices'])) {
                                                        $default_currency = $settings['currency_code'] ?? 'GBP';
                                                        $display_price = $product['prices'][$default_currency] ?? $product['price'] ?? 0;
                                                        echo $settings['currency_symbol'] ?? '£';
                                                        echo number_format($display_price, 2);
                                                        if (count($product['prices']) > 1) {
                                                            echo ' <span class="text-xs text-charcoal-400">(' . count($product['prices']) . ' currencies)</span>';
                                                        }
                                                    } else {
                                                        echo $settings['currency_symbol'] ?? '£';
                                                        echo number_format($product['price'] ?? 0, 2);
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs text-charcoal-600">Stock:</span>
                                                <span class="px-2 py-1 text-xs font-medium rounded <?= $product['stock'] <= 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                                    <?= $product['stock'] ?>
                                                </span>
                                            </div>
                                            
                                            <div class="flex justify-between items-start">
                                                <span class="text-xs text-charcoal-600">Status:</span>
                                                <div class="space-y-1 text-right">
                                                    <span class="px-2 py-1 text-xs font-medium rounded <?= $product['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                        <?= $product['active'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                    <?php if ($product['featured']): ?>
                                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded block">Featured</span>
                                                    <?php endif; ?>
                                                    <?php if (isset($product['has_sizes']) && $product['has_sizes']): ?>
                                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded block">Sizes</span>
                                                    <?php endif; ?>
                                                    <?php if (isset($product['has_colors']) && $product['has_colors']): ?>
                                                    <span class="px-2 py-1 text-xs font-medium bg-indigo-100 text-indigo-800 rounded block">Colors</span>
                                                    <?php endif; ?>
                                                    <?php if (isset($product['features']) && !empty($product['features'])): ?>
                                                    <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded block"><?= count($product['features']) ?> Features</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-2 lg:p-4" onclick="closeProductModal()">
        <div class="bg-white w-full max-w-4xl max-h-screen overflow-y-auto rounded-lg shadow-2xl" onclick="event.stopPropagation()">
                <div class="bg-charcoal text-white px-4 lg:px-6 py-3 lg:py-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg lg:text-xl font-bold">
                            <i class="bi bi-<?= $edit_product ? 'pencil-square' : 'plus-circle' ?> mr-2 lg:mr-3"></i>
                            <?= $edit_product ? 'Edit Product' : 'Add New Product' ?>
                        </h3>
                        <button type="button" onclick="closeProductModal()" class="text-white hover:text-gray-300 text-xl lg:text-2xl p-1 touch-manipulation">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
                
                <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                    <div class="flex flex-col space-y-2 lg:space-y-0 lg:flex-row lg:justify-between lg:items-center">
                        <span class="px-3 py-1 bg-folly text-white text-xs lg:text-sm font-medium rounded">Step 1 of 1</span>
                        <span class="text-charcoal-400 text-xs lg:text-sm">Fill in the details below to create your product</span>
                    </div>
                </div>
<form method="POST" id="productForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                    <input type="hidden" name="action" value="<?= $edit_product ? 'edit' : 'add' ?>">
                    <?php if ($edit_product): ?>
                    <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
                    <?php endif; ?>
                    
                <div class="p-4 lg:p-6">
                    <!-- Essential Product Fields -->
                    <div class="space-y-4 lg:space-y-6">
                        <!-- Product Name -->
                        <div>
                            <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">
                                Product Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" required
                                   class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation"
                                   placeholder="Enter product name"
                                   value="<?= $edit_product ? htmlspecialchars($edit_product['name']) : '' ?>">
                        </div>
                        
                        <!-- Category Selection -->
                        <div>
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <div class="max-h-64 overflow-y-auto border border-gray-300 rounded-lg p-2">
                                <?php 
                                $hierarchy = getCategoryHierarchy(true);
                                if (empty($hierarchy)): 
                                ?>
                                <div class="text-center text-charcoal-400 py-8">
                                    <i class="bi bi-tags text-2xl mb-2 block"></i>
                                    No categories available. Please create categories first.
                                </div>
                                <?php else: ?>
                                <?php echo renderProductCategorySelection($hierarchy, $edit_product ? $edit_product['category_id'] : 0, 0); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Price and Stock -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">
                                    Price (£) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="price" step="0.01" min="0" required
                                       class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation"
                                       placeholder="0.00"
                                       value="<?= $edit_product ? $edit_product['price'] : '' ?>">
                            </div>
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">
                                    Stock Quantity <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="stock" min="0" required
                                       class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation"
                                       placeholder="0"
                                       value="<?= $edit_product ? $edit_product['stock'] : '' ?>">
                            </div>
                        </div>
                        
                        <!-- Main Product Image -->
                        <div>
                            <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">
                                Main Product Image
                            </label>
                            <input type="file" name="image" id="imageUpload" accept="image/*"
                                   class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation">
                            <?php if ($edit_product && $edit_product['image']): ?>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600">Current image:</p>
                                <img src="../assets/images/<?php echo $edit_product['image']; ?>" 
                                     alt="Current product image" 
                                     class="mt-1 h-20 w-20 object-cover rounded"
                                     onerror="this.src='../assets/images/general/placeholder.jpg'">
                            </div>
                            <?php endif; ?>
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <p class="text-sm text-gray-600">Preview:</p>
                                <img id="previewImg" src="" alt="Preview" class="mt-1 h-20 w-20 object-cover rounded">
                            </div>
                        </div>
                        
                        <!-- Additional Product Images -->
                        <div>
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Additional Images (optional)
                            </label>
                            <input type="file" name="additional_images[]" id="additionalImagesUpload" accept="image/*" multiple
                                   class="w-full px-4 py-3 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly">
                            <p class="text-xs text-gray-500 mt-1">You can select multiple images at once. These will appear in the product gallery.</p>
                            
                            <?php if ($edit_product && isset($edit_product['images']) && !empty($edit_product['images'])): ?>
                            <div class="mt-3">
                                <p class="text-sm text-gray-600 mb-2">Current additional images:</p>
                                <div class="grid grid-cols-4 gap-2" id="currentImages">
                                    <?php foreach ($edit_product['images'] as $index => $image_path): ?>
                                    <div class="relative image-item" data-image="<?php echo htmlspecialchars($image_path); ?>">
                                        <img src="../assets/images/<?php echo $image_path; ?>" 
                                             alt="Additional image <?php echo $index + 1; ?>" 
                                             class="h-16 w-16 object-cover rounded border"
                                             onerror="this.src='../assets/images/general/placeholder.jpg'">
                                        <button type="button" onclick="markImageForDeletion(this)" 
                                                class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs hover:bg-red-600 flex items-center justify-center"
                                                title="Mark for deletion">×</button>
                                        <input type="hidden" name="delete_images[]" value="" disabled>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Click the × button to mark images for deletion when you save.</p>
                            </div>
                            <?php endif; ?>
                            
                            <div id="additionalImagesPreview" class="mt-2 hidden">
                                <p class="text-sm text-gray-600 mb-2">New images preview:</p>
                                <div class="grid grid-cols-4 gap-2" id="previewGrid"></div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div>
                            <label class="block text-xs lg:text-sm font-medium text-charcoal mb-2">
                                Description
                            </label>
                            <textarea name="description" rows="3"
                                      class="w-full px-3 lg:px-4 py-2 lg:py-3 text-sm lg:text-base border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly touch-manipulation resize-y"
                                      placeholder="Describe your product..."><?= $edit_product ? htmlspecialchars($edit_product['description']) : '' ?></textarea>
                        </div>
                        
                        <!-- Multi-Currency Pricing -->
                        <div>
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Multi-Currency Pricing
                            </label>
                            <div class="space-y-3">
                                <?php 
                                $currencies = $settings['currencies'] ?? [['code' => 'GBP', 'symbol' => '£', 'default' => true]];
                                $product_prices = $edit_product['prices'] ?? [];
                                foreach ($currencies as $currency): 
                                ?>
                                <div class="flex items-center space-x-3">
                                    <span class="w-12 text-sm font-medium text-charcoal"><?= htmlspecialchars($currency['code']) ?></span>
                                    <span class="w-8 text-center text-charcoal"><?= htmlspecialchars($currency['symbol']) ?></span>
                                    <input type="number" 
                                           name="prices[<?= $currency['code'] ?>]" 
                                           step="0.01" 
                                           min="0"
                                           class="flex-1 px-4 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly"
                                           placeholder="0.00"
                                           value="<?= isset($product_prices[$currency['code']]) ? $product_prices[$currency['code']] : '' ?>">
                                    <?php if ($currency['default']): ?>
                                    <span class="px-2 py-1 bg-folly-50 text-folly text-xs font-medium">Default</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="text-xs text-charcoal-400 mt-2">Set prices in different currencies. Leave empty to use default conversion.</p>
                        </div>
                        
                        <!-- Sizes Section -->
                        <div>
                            <div class="flex items-center space-x-3 mb-3">
                                <input type="checkbox" 
                                       name="has_sizes" 
                                       value="1" 
                                       id="hasSizes"
                                       class="mr-2" 
                                       <?= $edit_product && $edit_product['has_sizes'] ? 'checked' : '' ?>>
                                <label for="hasSizes" class="text-sm font-medium text-charcoal">This product has different sizes</label>
                            </div>
                            
                            <div id="sizesSection" class="<?= !($edit_product && $edit_product['has_sizes']) ? 'hidden' : '' ?>">
                                <label class="block text-sm font-medium text-charcoal mb-2">Available Sizes</label>
                                
                                <!-- Common Sizes -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-charcoal-600 mb-2">Common Sizes</label>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <?php 
                                        $common_sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'One Size'];
                                        $product_sizes = $edit_product['available_sizes'] ?? [];
                                        foreach ($common_sizes as $size): 
                                        ?>
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="available_sizes[]" 
                                                   value="<?= $size ?>"
                                                   class="mr-2"
                                                   <?= in_array($size, $product_sizes) ? 'checked' : '' ?>>
                                            <span class="text-sm text-charcoal"><?= $size ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Custom Sizes -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-charcoal-600 mb-2">Custom Sizes</label>
                                    <div class="space-y-2">
                                        <input type="text" 
                                               id="customSizeInput" 
                                               placeholder="Enter custom size (e.g., 32, 34, 36 or Small/Medium, Large/Extra Large)"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-folly focus:border-transparent">
                                        <button type="button" 
                                                id="addCustomSize" 
                                                class="px-4 py-2 bg-folly text-white rounded-md hover:bg-folly-dark transition-colors">
                                            Add Custom Size
                                        </button>
                                    </div>
                                    
                                    <!-- Display custom sizes -->
                                    <div id="customSizesList" class="mt-3">
                                        <?php 
                                        // Display existing custom sizes that aren't in common sizes
                                        $custom_sizes = array_diff($product_sizes, $common_sizes);
                                        foreach ($custom_sizes as $custom_size): 
                                        ?>
                                        <div class="inline-flex items-center bg-gray-100 rounded-full px-3 py-1 text-sm mr-2 mb-2">
                                            <span><?= htmlspecialchars($custom_size) ?></span>
                                            <input type="hidden" name="available_sizes[]" value="<?= htmlspecialchars($custom_size) ?>">
                                            <button type="button" class="ml-2 text-red-500 hover:text-red-700" onclick="removeCustomSize(this)">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Colors Section -->
                        <div>
                            <div class="flex items-center space-x-3 mb-3">
                                <input type="checkbox" 
                                       name="has_colors" 
                                       value="1" 
                                       id="hasColors"
                                       class="mr-2" 
                                       <?= $edit_product && $edit_product['has_colors'] ? 'checked' : '' ?>>
                                <label for="hasColors" class="text-sm font-medium text-charcoal">This product has different colors</label>
                            </div>
                            
                            <div id="colorsSection" class="<?= !($edit_product && $edit_product['has_colors']) ? 'hidden' : '' ?>">
                                <label class="block text-sm font-medium text-charcoal mb-2">Available Colors</label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    <?php 
                                    $common_colors = [
                                        ['name' => 'Black', 'hex' => '#000000'],
                                        ['name' => 'White', 'hex' => '#FFFFFF'],
                                        ['name' => 'Red', 'hex' => '#FF0000'],
                                        ['name' => 'Blue', 'hex' => '#0000FF'],
                                        ['name' => 'Green', 'hex' => '#008000'],
                                        ['name' => 'Yellow', 'hex' => '#FFFF00'],
                                        ['name' => 'Pink', 'hex' => '#FFC0CB'],
                                        ['name' => 'Purple', 'hex' => '#800080'],
                                        ['name' => 'Orange', 'hex' => '#FFA500'],
                                        ['name' => 'Brown', 'hex' => '#8B4513'],
                                        ['name' => 'Gray', 'hex' => '#808080'],
                                        ['name' => 'Navy', 'hex' => '#000080']
                                    ];
                                    $product_colors = $edit_product['available_colors'] ?? [];
                                    foreach ($common_colors as $color): 
                                    ?>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="available_colors[]" 
                                               value="<?= $color['name'] ?>"
                                               class="mr-2"
                                               <?= in_array($color['name'], $product_colors) ? 'checked' : '' ?>>
                                        <div class="w-4 h-4 border border-gray-300 mr-2" style="background-color: <?= $color['hex'] ?>; <?= $color['name'] === 'White' ? 'border-color: #ccc;' : '' ?>"></div>
                                        <span class="text-sm text-charcoal"><?= $color['name'] ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Features -->
                        <div>
                            <label class="block text-sm font-medium text-charcoal mb-2">
                                Product Features
                            </label>
                            <div id="featuresContainer">
                                <?php 
                                $product_features = $edit_product['features'] ?? [['name' => '', 'value' => '']];
                                foreach ($product_features as $index => $feature): 
                                ?>
                                <div class="feature-row grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
                                    <div class="md:col-span-4">
                                        <input type="text" 
                                               name="features[<?= $index ?>][name]" 
                                               class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" 
                                               placeholder="Feature name (e.g., Material)"
                                               value="<?= htmlspecialchars($feature['name']) ?>">
                                    </div>
                                    <div class="md:col-span-6">
                                        <input type="text" 
                                               name="features[<?= $index ?>][value]" 
                                               class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" 
                                               placeholder="Feature value (e.g., 100% Cotton)"
                                               value="<?= htmlspecialchars($feature['value']) ?>">
                                    </div>
                                    <div class="md:col-span-2">
                                        <button type="button" 
                                                class="px-3 py-2 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-sm w-full"
                                                onclick="removeFeature(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" 
                                    onclick="addFeature()" 
                                    class="px-4 py-2 bg-gray-100 text-charcoal hover:bg-gray-200 transition-colors text-sm">
                                <i class="bi bi-plus-circle mr-2"></i>Add Feature
                            </button>
                        </div>
                        
                        <!-- Status -->
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="active" value="1" 
                                       class="mr-2" <?= !$edit_product || $edit_product['active'] ? 'checked' : '' ?>>
                                <span class="text-sm font-medium text-charcoal">Product is Active</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="featured" value="1" 
                                       class="mr-2" <?= $edit_product && $edit_product['featured'] ? 'checked' : '' ?>>
                                <span class="text-sm font-medium text-charcoal">Featured Product</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="px-4 lg:px-6 py-3 lg:py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex flex-col space-y-3 lg:space-y-0 lg:flex-row lg:justify-between lg:items-center">
                            <p class="text-charcoal-400 text-xs lg:text-sm">All required fields (*) must be filled</p>
                            <div class="flex flex-col space-y-2 lg:space-y-0 lg:flex-row lg:space-x-3">
                                <button type="button" onclick="closeProductModal()" class="w-full lg:w-auto px-4 lg:px-6 py-2 border border-gray-300 bg-white text-charcoal hover:bg-gray-50 transition-colors touch-manipulation">
                                    Cancel
                                </button>
                                <button type="submit" class="w-full lg:w-auto px-4 lg:px-6 py-2 bg-folly text-white hover:bg-folly-600 transition-colors font-medium touch-manipulation">
                                    <?= $edit_product ? 'Update Product' : 'Create Product' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Product Modal -->
    <div id="viewProductModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-2 sm:p-4" onclick="closeViewProductModal()">
        <div class="bg-white w-full max-w-2xl max-h-screen overflow-y-auto rounded-lg shadow-2xl" onclick="event.stopPropagation()">
            <div class="bg-charcoal text-white px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg sm:text-xl font-bold">
                        <i class="bi bi-eye mr-2"></i>
                        Product Details
                    </h3>
                    <button type="button" onclick="closeViewProductModal()" class="text-white hover:text-gray-300 text-xl sm:text-2xl p-1 touch-manipulation">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            <div class="p-4 sm:p-6" id="viewProductContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
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
        
        // Modal control functions - Define first
        function openProductModal() {
            const modal = document.getElementById('productModal');
            if (modal) {
                modal.classList.remove('hidden');
                // Ensure the modal is visible and properly styled
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            } else {
                console.error('Product modal not found');
            }
        }
        
        function closeProductModal() {
            const modal = document.getElementById('productModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = ''; // Restore scrolling
                
                // Reset form if it's not an edit
                const form = document.getElementById('productForm');
                if (form && !<?php echo $edit_product ? 'true' : 'false'; ?>) {
                    form.reset();
                    // Clear any preview images
                    const preview = document.getElementById('imagePreview');
                    if (preview) preview.style.display = 'none';
                }
            }
        }

        // Auto-open modal if editing - moved here to ensure functions are defined
        <?php if ($edit_product): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                openProductModal();
            }, 100); // Small delay to ensure DOM is fully ready
        });
        <?php else: ?>
        <?php endif; ?>
        
        // Sizes toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const hasSizesCheckbox = document.getElementById('hasSizes');
            const sizesSection = document.getElementById('sizesSection');
            
            if (hasSizesCheckbox && sizesSection) {
                hasSizesCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        sizesSection.classList.remove('hidden');
                    } else {
                        sizesSection.classList.add('hidden');
                        // Uncheck all size checkboxes when disabled
                        const sizeCheckboxes = sizesSection.querySelectorAll('input[type="checkbox"]');
                        sizeCheckboxes.forEach(cb => cb.checked = false);
                    }
                });
            }
            
            // Colors toggle functionality
            const hasColorsCheckbox = document.getElementById('hasColors');
            const colorsSection = document.getElementById('colorsSection');
            
            if (hasColorsCheckbox && colorsSection) {
                hasColorsCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        colorsSection.classList.remove('hidden');
                    } else {
                        colorsSection.classList.add('hidden');
                        // Uncheck all color checkboxes when disabled
                        const colorCheckboxes = colorsSection.querySelectorAll('input[type="checkbox"]');
                        colorCheckboxes.forEach(cb => cb.checked = false);
                    }
                });
            }
        });
        
        // Features management
        let featureIndex = <?= count($edit_product['features'] ?? [['name' => '', 'value' => '']]) ?>;

        function addFeature() {
            const container = document.getElementById('featuresContainer');
            const newRow = document.createElement('div');
            newRow.className = 'feature-row grid grid-cols-1 md:grid-cols-12 gap-3 mb-3';
            newRow.innerHTML = `
                <div class="md:col-span-4">
                    <input type="text" 
                           name="features[${featureIndex}][name]" 
                           class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" 
                           placeholder="Feature name (e.g., Material)">
                </div>
                <div class="md:col-span-6">
                    <input type="text" 
                           name="features[${featureIndex}][value]" 
                           class="w-full px-3 py-2 border border-gray-300 focus:border-folly focus:ring-1 focus:ring-folly" 
                           placeholder="Feature value (e.g., 100% Cotton)">
                </div>
                <div class="md:col-span-2">
                    <button type="button" 
                            class="px-3 py-2 bg-red-100 text-red-700 hover:bg-red-200 transition-colors text-sm w-full"
                            onclick="removeFeature(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            featureIndex++;
        }

        function removeFeature(button) {
            const row = button.closest('.feature-row');
            const container = document.getElementById('featuresContainer');
            
            // Don't remove the last row - just clear it
            if (container.children.length === 1) {
                const inputs = row.querySelectorAll('input');
                inputs.forEach(input => input.value = '');
            } else {
                row.remove();
            }
        }
        
        // Enhanced form validation and user experience
        document.addEventListener('DOMContentLoaded', function() {
            // Add real-time validation feedback
            const form = document.getElementById('productForm');
            const requiredFields = form.querySelectorAll('[required]');
            
            // Add visual feedback for required fields
            requiredFields.forEach(field => {
                field.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.classList.add('is-invalid');
                        this.style.borderColor = '#dc3545';
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        this.style.borderColor = '#198754';
                    }
                });
                
                field.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        this.style.borderColor = '#198754';
                    }
                });
            });
            
            // Enhanced form submission
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const submitBtn = form.querySelector('button[type="submit"]');
                
                // Check all required fields
                requiredFields.forEach(field => {
                    if (field.value.trim() === '') {
                        field.classList.add('is-invalid');
                        field.style.borderColor = '#dc3545';
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    showToast('Please fill in all required fields', 'error');
                    return;
                }
                
                // Show loading state
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving...';
                submitBtn.disabled = true;
            });
            
            // Show toast notifications
            function showToast(message, type = 'info') {
                const toastContainer = document.getElementById('toastContainer') || createToastContainer();
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : 'primary'} border-0`;
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-${type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                toastContainer.appendChild(toast);
                
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', function() {
                    toast.remove();
                });
            }
            
            function createToastContainer() {
                const container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
                return container;
            }
        });
        
        // Bulk selection
        document.addEventListener('DOMContentLoaded', function() {
            // Bulk selection functionality
            const selectAllCheckbox = document.getElementById('selectAll');
            const selectAllHeaderCheckbox = document.getElementById('selectAllHeader');
            
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.product-checkbox');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            }

            if (selectAllHeaderCheckbox) {
                selectAllHeaderCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.product-checkbox');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            }
        });

        // Function to mark images for deletion
        function markImageForDeletion(button) {
            const imageItem = button.closest('.image-item');
            const hiddenInput = imageItem.querySelector('input[type="hidden"]');
            const imagePath = imageItem.dataset.image;
            
            if (imageItem.classList.contains('marked-for-deletion')) {
                // Unmark for deletion
                imageItem.classList.remove('marked-for-deletion');
                imageItem.style.opacity = '1';
                button.textContent = '×';
                button.className = 'absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs hover:bg-red-600 flex items-center justify-center';
                hiddenInput.value = '';
                hiddenInput.disabled = true;
            } else {
                // Mark for deletion
                imageItem.classList.add('marked-for-deletion');
                imageItem.style.opacity = '0.5';
                button.textContent = '✓';
                button.className = 'absolute -top-1 -right-1 bg-green-500 text-white rounded-full w-5 h-5 text-xs hover:bg-green-600 flex items-center justify-center';
                hiddenInput.value = imagePath;
                hiddenInput.disabled = false;
            }
        }
        
        // View Product Modal functions
        function viewProduct(product) {
            const modal = document.getElementById('viewProductModal');
            const content = document.getElementById('viewProductContent');
            
            if (!modal || !content) return;
            
            // Build the product details HTML
            let html = `
                <div class="space-y-4">
                    <div class="flex items-start space-x-4">
                        <img src="../assets/images/${product.image}" 
                             alt="${product.name}" 
                             class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-lg border"
                             onerror="this.src='../assets/images/general/placeholder.jpg'">
                        <div class="flex-1">
                            <h4 class="text-lg font-semibold text-charcoal">${product.name}</h4>
                            <p class="text-sm text-charcoal-400">${product.slug}</p>
                            <div class="mt-2 space-x-2">
                                <span class="px-2 py-1 text-xs font-medium rounded ${product.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                    ${product.active ? 'Active' : 'Inactive'}
                                </span>
                                ${product.featured ? '<span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded">Featured</span>' : ''}
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-charcoal-600 mb-1">Price</label>
                            <p class="text-lg font-semibold text-charcoal">£${parseFloat(product.price || 0).toFixed(2)}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-charcoal-600 mb-1">Stock</label>
                            <p class="text-lg font-semibold ${product.stock <= 10 ? 'text-red-600' : 'text-green-600'}">${product.stock}</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-charcoal-600 mb-1">Description</label>
                        <p class="text-sm text-charcoal bg-gray-50 p-3 rounded">${product.description || 'No description available'}</p>
                    </div>
            `;
            
            // Add sizes if available
            if (product.has_sizes && product.available_sizes && product.available_sizes.length > 0) {
                html += `
                    <div>
                        <label class="block text-xs font-medium text-charcoal-600 mb-1">Available Sizes</label>
                        <div class="flex flex-wrap gap-1">
                            ${product.available_sizes.map(size => `<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">${size}</span>`).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Add colors if available
            if (product.has_colors && product.available_colors && product.available_colors.length > 0) {
                html += `
                    <div>
                        <label class="block text-xs font-medium text-charcoal-600 mb-1">Available Colors</label>
                        <div class="flex flex-wrap gap-1">
                            ${product.available_colors.map(color => `<span class="px-2 py-1 text-xs bg-indigo-100 text-indigo-800 rounded">${color}</span>`).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Add features if available
            if (product.features && product.features.length > 0) {
                html += `
                    <div>
                        <label class="block text-xs font-medium text-charcoal-600 mb-1">Features</label>
                        <div class="space-y-1">
                            ${product.features.map(feature => `
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-charcoal">${feature.name}:</span>
                                    <span class="text-charcoal-600">${feature.value}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Add additional images if available
            if (product.images && product.images.length > 0) {
                html += `
                    <div>
                        <label class="block text-xs font-medium text-charcoal-600 mb-1">Additional Images</label>
                        <div class="grid grid-cols-4 gap-2">
                            ${product.images.map(image => `
                                <img src="../assets/images/${image}" 
                                     alt="Additional image" 
                                     class="w-full h-16 object-cover rounded border"
                                     onerror="this.src='../assets/images/general/placeholder.jpg'">
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            html += '</div>';
            
            content.innerHTML = html;
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeViewProductModal() {
            const modal = document.getElementById('viewProductModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
        
        // Make functions globally available
        window.markImageForDeletion = markImageForDeletion;
        window.viewProduct = viewProduct;
        window.closeViewProductModal = closeViewProductModal;
        
        // Custom size management functions
        function addCustomSize() {
            const input = document.getElementById('customSizeInput');
            const sizesList = document.getElementById('customSizesList');
            const sizeValue = input.value.trim();
            
            if (sizeValue === '') {
                alert('Please enter a size value');
                return;
            }
            
            // Check if size already exists
            const existingSizes = Array.from(sizesList.querySelectorAll('input[type="hidden"]')).map(input => input.value);
            const commonSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'One Size'];
            const allExistingSizes = [...existingSizes, ...commonSizes.filter(size => 
                document.querySelector(`input[name="available_sizes[]"][value="${size}"]:checked`)
            )];
            
            if (allExistingSizes.includes(sizeValue)) {
                alert('This size already exists');
                return;
            }
            
            // Create new size element
            const sizeElement = document.createElement('div');
            sizeElement.className = 'inline-flex items-center bg-gray-100 rounded-full px-3 py-1 text-sm mr-2 mb-2';
            sizeElement.innerHTML = `
                <span>${sizeValue}</span>
                <input type="hidden" name="available_sizes[]" value="${sizeValue}">
                <button type="button" class="ml-2 text-red-500 hover:text-red-700" onclick="removeCustomSize(this)">
                    <i class="bi bi-x"></i>
                </button>
            `;
            
            sizesList.appendChild(sizeElement);
            input.value = '';
        }
        
        function removeCustomSize(button) {
            button.closest('div').remove();
        }
        
        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const addButton = document.getElementById('addCustomSize');
            const input = document.getElementById('customSizeInput');
            
            if (addButton) {
                addButton.addEventListener('click', addCustomSize);
            }
            
            if (input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addCustomSize();
                    }
                });
            }
        });
        
        // Make custom size functions globally available
        window.addCustomSize = addCustomSize;
        window.removeCustomSize = removeCustomSize;
    </script>
</body>
</html>