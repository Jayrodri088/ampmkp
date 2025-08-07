<?php

require_once 'router.php';

// Create router instance
$router = new Router();

// Set custom 404 handler
$router->setNotFoundHandler(function() {
    include '404.php';
});

// Set custom error handler
$router->setErrorHandler(function($exception) {
    error_log("Router Error: " . $exception->getMessage());
    http_response_code(500);
    echo "An error occurred. Please try again later.";
});

// Global middleware for logging
$router->middleware(function() {
    error_log("Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
    return true; // Continue processing
});

// API Routes Group
$router->group('/api', [], function($router) {
    
    // Cart API endpoints
    $router->get('/cart', function() {
        include 'api/cart.php';
    });
    
    $router->post('/cart/add', function() {
        $_GET['action'] = 'add';
        include 'api/cart.php';
    });
    
    $router->post('/cart/update', function() {
        $_GET['action'] = 'update';
        include 'api/cart.php';
    });
    
    $router->post('/cart/remove', function() {
        $_GET['action'] = 'remove';
        include 'api/cart.php';
    });
    
    $router->delete('/cart/clear', function() {
        $_GET['action'] = 'clear';
        include 'api/cart.php';
    });
    
    // Products API
    $router->get('/products', function() {
        header('Content-Type: application/json');
        $products = json_decode(file_get_contents('data/products.json'), true);
        echo json_encode($products);
    });
    
    $router->get('/products/{id}', function($id) {
        header('Content-Type: application/json');
        $products = json_decode(file_get_contents('data/products.json'), true);
        $product = array_filter($products, function($p) use ($id) {
            return $p['id'] == $id;
        });
        
        if ($product) {
            echo json_encode(array_values($product)[0]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
        }
    });
    
    // Categories API
    $router->get('/categories', function() {
        header('Content-Type: application/json');
        $categories = json_decode(file_get_contents('data/categories.json'), true);
        echo json_encode($categories);
    });
    
    // Search API
    $router->get('/search', function() {
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Search query required']);
            return;
        }
        
        $_GET['search'] = $query;
        include 'search.php';
    });
    
});

// Admin Routes Group (with authentication middleware)
$router->group('/admin', [
    function() {
        // Simple authentication check
        session_start();
        if (!isset($_SESSION['admin_logged_in'])) {
            http_response_code(401);
            echo "Unauthorized";
            return false; // Stop processing
        }
        return true; // Continue processing
    }
], function($router) {
    
    $router->get('/dashboard', function() {
        echo "<h1>Admin Dashboard</h1>";
        echo "<p>Welcome to the admin panel!</p>";
    });
    
    $router->get('/products', function() {
        echo "<h1>Manage Products</h1>";
        // Include admin products management page
    });
    
    $router->get('/orders', function() {
        echo "<h1>Manage Orders</h1>";
        // Include admin orders management page
    });
    
});

// Custom dynamic routes (these won't conflict with .htaccess)
$router->get('/blog', function() {
    echo "<h1>Blog</h1><p>Blog listing page</p>";
});

$router->get('/blog/{slug}', function($slug) {
    echo "<h1>Blog Post: " . htmlspecialchars($slug) . "</h1>";
    echo "<p>This is a blog post about " . htmlspecialchars($slug) . "</p>";
});

$router->get('/user/{id}', function($id) {
    echo "<h1>User Profile</h1>";
    echo "<p>Showing profile for user ID: " . htmlspecialchars($id) . "</p>";
});

$router->get('/special-offers', function() {
    echo "<h1>Special Offers</h1>";
    echo "<p>Check out our amazing deals!</p>";
});

// AJAX endpoints
$router->post('/ajax/newsletter-signup', function() {
    require_once 'includes/bot_protection.php';
    require_once 'includes/functions.php';
    require_once 'includes/mail_config.php';
    
    $botProtection = new BotProtection();
    $email = sanitizeInput($_POST['email'] ?? '');
    
    header('Content-Type: application/json');
    
    // Bot protection validation
    $botValidation = $botProtection->validateSubmission('newsletter', $_POST);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    if (!$botValidation['valid']) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Security validation failed. Please try again later.']);
        // Log suspicious activity
        $botProtection->logSuspiciousActivity('newsletter_signup', $_POST, $botValidation['errors']);
        return;
    }
    
    // Check if email already exists
    $newsletters = readJsonFile('newsletter.json');
    foreach ($newsletters as $subscriber) {
        if ($subscriber['email'] === $email) {
            echo json_encode(['success' => false, 'message' => 'Email already subscribed']);
            return;
        }
    }
    
    // Save email to newsletter list
    $newsletterData = [
        'id' => count($newsletters) + 1,
        'email' => $email,
        'subscribed_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'active' => true
    ];
    
    $newsletters[] = $newsletterData;
    
    if (writeJsonFile('newsletter.json', $newsletters)) {
        // Send confirmation email
        $emailSent = sendNewsletterConfirmation($email);
        echo json_encode(['success' => true, 'message' => 'Thank you for subscribing!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save subscription']);
    }
});

$router->post('/ajax/contact-form', function() {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if ($name && $email && $message) {
        // Save contact form submission
        $contacts = json_decode(file_get_contents('data/contacts.json'), true) ?? [];
        $contacts[] = [
            'id' => uniqid(),
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'date' => date('Y-m-d H:i:s')
        ];
        file_put_contents('data/contacts.json', json_encode($contacts, JSON_PRETTY_PRINT));
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
    } else {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
    }
});

// Catch-all route for SPA-like behavior (should be last)
$router->get('/app/{path}', function($path) {
    // For single-page application routes
    echo "<h1>SPA Route: " . htmlspecialchars($path) . "</h1>";
    echo "<p>This could load a JavaScript application</p>";
});

// Run the router
$router->run(); 