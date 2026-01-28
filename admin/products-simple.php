<?php
session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/auth.php');
    exit;
}

// Helper function
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Load data
$products = array();
$categories = array();

if (file_exists('../data/products.json')) {
    $products_json = file_get_contents('../data/products.json');
    $products = json_decode($products_json, true);
    if (!$products) $products = array();
}

if (file_exists('../data/categories.json')) {
    $categories_json = file_get_contents('../data/categories.json');
    $categories = json_decode($categories_json, true);
    if (!$categories) $categories = array();
}

// Handle form submissions
$message = '';
$message_type = '';

// CSRF token setup
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (empty($postedToken) || !hash_equals($_SESSION['admin_csrf_token'], $postedToken)) {
        http_response_code(403);
        $message = 'Invalid CSRF token.';
        $message_type = 'error';
    } else {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $max_id = 0;
                foreach ($products as $product) {
                    if ($product['id'] > $max_id) {
                        $max_id = $product['id'];
                    }
                }
                
                $new_product = array(
                    'id' => $max_id + 1,
                    'name' => $_POST['name'],
                    'slug' => generateSlug($_POST['name']),
                    'price' => (float)$_POST['price'],
                    'category_id' => (int)$_POST['category_id'],
                    'description' => $_POST['description'],
                    'image' => $_POST['image'] ? $_POST['image'] : 'products/placeholder.jpg',
                    'stock' => (int)$_POST['stock'],
                    'featured' => isset($_POST['featured']),
                    'active' => isset($_POST['active'])
                );
                $products[] = $new_product;
                $message = 'Product added successfully!';
                $message_type = 'success';
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                for ($i = 0; $i < count($products); $i++) {
                    if ($products[$i]['id'] === $id) {
                        $products[$i]['name'] = $_POST['name'];
                        $products[$i]['slug'] = generateSlug($_POST['name']);
                        $products[$i]['price'] = (float)$_POST['price'];
                        $products[$i]['category_id'] = (int)$_POST['category_id'];
                        $products[$i]['description'] = $_POST['description'];
                        $products[$i]['image'] = $_POST['image'] ? $_POST['image'] : $products[$i]['image'];
                        $products[$i]['stock'] = (int)$_POST['stock'];
                        $products[$i]['featured'] = isset($_POST['featured']);
                        $products[$i]['active'] = isset($_POST['active']);
                        break;
                    }
                }
                $message = 'Product updated successfully!';
                $message_type = 'success';
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $new_products = array();
                foreach ($products as $product) {
                    if ($product['id'] !== $id) {
                        $new_products[] = $product;
                    }
                }
                $products = $new_products;
                $message = 'Product deleted successfully!';
                $message_type = 'success';
                break;
        }
        
        // Save changes
        file_put_contents('../data/products.json', json_encode($products, JSON_PRETTY_PRINT));
    }
}

// Handle GET actions
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_products = array();
    foreach ($products as $product) {
        if ($product['id'] !== $id) {
            $new_products[] = $product;
        }
    }
    $products = $new_products;
    file_put_contents('../data/products.json', json_encode($products, JSON_PRETTY_PRINT));
    $message = 'Product deleted successfully!';
    $message_type = 'success';
}

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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .content-area {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="bi bi-shield-check"></i> Admin Panel
                    </h4>
                    <div class="text-white-50 mb-4">
                        Welcome, <?php echo htmlspecialchars($_SESSION['admin_user']); ?>
                    </div>
                </div>
                
<?php require_once __DIR__ . '/includes/admin_functions.php'; ?>
                <nav class="nav flex-column px-3">
                    <a class="nav-link" href="<?= getAdminUrl('index.php') ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="<?= getAdminUrl('products-simple.php') ?>">
                        <i class="bi bi-box-seam me-2"></i> Products
                    </a>
                    <a class="nav-link" href="<?= getAdminUrl('categories.php') ?>">
                        <i class="bi bi-tags me-2"></i> Categories
                    </a>
                    <a class="nav-link" href="<?= getAdminUrl('orders.php') ?>">
                        <i class="bi bi-receipt me-2"></i> Orders
                    </a>
                    <a class="nav-link" href="<?= getAdminUrl('contacts.php') ?>">
                        <i class="bi bi-envelope me-2"></i> Contacts
                    </a>
                    <a class="nav-link" href="<?= getAdminUrl('settings.php') ?>">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                    <hr class="text-white-50">
                    <a class="nav-link" href="<?= getMainSiteUrl() ?>" target="_blank">
                        <i class="bi bi-house me-2"></i> View Site
                    </a>
                    <a class="nav-link" href="<?= getAdminUrl('auth.php?logout=1') ?>">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content-area">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">Products Management</h1>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Product
                        </button>
                    </div>

                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Products Table -->
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Image</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                No products found
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <img src="../assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     class="product-image"
                                                     onerror="this.src='../assets/images/general/placeholder-small.jpg'">
                                            </td>
                                            <td>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product['slug']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $category_name = 'Unknown';
                                                foreach ($categories as $category) {
                                                    if ($category['id'] == $product['category_id']) {
                                                        $category_name = $category['name'];
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($category_name); ?></span>
                                            </td>
                                            <td>£<?php echo number_format($product['price'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $product['stock'] <= 10 ? 'bg-danger' : 'bg-success'; ?>">
                                                    <?php echo $product['stock']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge <?php echo $product['active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo $product['active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                    <?php if ($product['featured']): ?>
                                                    <span class="badge bg-warning">Featured</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?action=edit&id=<?php echo $product['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $product['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this product?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                    <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                    <?php if ($edit_product): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" name="name" class="form-control" required
                                           value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $edit_product && $edit_product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price (£) *</label>
                                    <input type="number" name="price" class="form-control" step="0.01" min="0" required
                                           value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity *</label>
                                    <input type="number" name="stock" class="form-control" min="0" required
                                           value="<?php echo $edit_product ? $edit_product['stock'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image Path</label>
                            <input type="text" name="image" class="form-control" 
                                   placeholder="products/image.jpg"
                                   value="<?php echo $edit_product ? htmlspecialchars($edit_product['image']) : ''; ?>">
                            <div class="form-text">Relative to assets/images/ directory</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="active" class="form-check-input" 
                                           <?php echo !$edit_product || $edit_product['active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Active</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="featured" class="form-check-input"
                                           <?php echo $edit_product && $edit_product['featured'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Featured</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show modal if editing
        <?php if ($edit_product): ?>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('productModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html> 