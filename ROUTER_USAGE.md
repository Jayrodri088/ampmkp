# Router System Usage Guide

This guide explains how to use the Router class with your existing `.htaccess` configuration.

## Files Created

1. **`router.php`** - The main Router class
2. **`routes.php`** - Route definitions and configuration
3. **`bootstrap.php`** - Integration helper for existing pages
4. **`ROUTER_USAGE.md`** - This documentation

## How it Works with .htaccess

The router is designed to work **alongside** your existing `.htaccess` rules, not replace them. Here's how they cooperate:

### .htaccess Handles:
- Static pages (index, shop, about, contact, cart, checkout, etc.)
- Product pages (`/product/123`)
- Category pages (`/category/electronics`)
- PHP extension removal
- Static file serving

### Router Handles:
- API endpoints (`/api/*`)
- Admin routes (`/admin/*`)
- Dynamic features (blog, user profiles)
- AJAX endpoints
- Custom application routes

## Basic Usage

### 1. Define Routes

```php
// In routes.php
$router = new Router();

// Simple GET route
$router->get('/api/products', function() {
    // Your code here
});

// Route with parameters
$router->get('/user/{id}', function($id) {
    echo "User ID: " . $id;
});

// POST route
$router->post('/api/cart/add', function() {
    // Handle cart addition
});
```

### 2. Integration Methods

#### Method A: Include in existing files
```php
// At the top of any existing PHP file
require_once 'bootstrap.php';
```

#### Method B: Direct router initialization
```php
// For new pages that should use routing
require_once 'routes.php';
```

#### Method C: Conditional routing
```php
// Check if route should be handled by router
if (shouldUseRouter()) {
    require_once 'routes.php';
} else {
    // Continue with existing page logic
}
```

## Router Class Features

### HTTP Methods
```php
$router->get('/path', $callback);
$router->post('/path', $callback);
$router->put('/path', $callback);
$router->delete('/path', $callback);
$router->any('/path', $callback); // All methods
```

### Route Parameters
```php
// Single parameter
$router->get('/user/{id}', function($id) {
    // $id contains the value from URL
});

// Multiple parameters
$router->get('/category/{category}/product/{id}', function($category, $id) {
    // Both parameters available
});
```

### Middleware
```php
// Global middleware
$router->middleware(function() {
    // Runs before all routes
    return true; // Continue processing
});

// Route-specific middleware
$router->get('/admin/users', function() {
    // Route logic
}, [
    function() {
        // Authentication check
        if (!isLoggedIn()) {
            http_response_code(401);
            echo "Unauthorized";
            return false; // Stop processing
        }
        return true; // Continue
    }
]);
```

### Route Groups
```php
// Group routes with common prefix and middleware
$router->group('/api', [], function($router) {
    $router->get('/users', function() {
        // Handles /api/users
    });
    
    $router->get('/products', function() {
        // Handles /api/products
    });
});

// Group with middleware
$router->group('/admin', [$authMiddleware], function($router) {
    $router->get('/dashboard', function() {
        // Protected route
    });
});
```

### Response Helpers
```php
// JSON response
$router->json(['status' => 'success'], 200);

// Redirect
$router->redirect('/login');

// Get request data
$postData = $router->getPostData();
$jsonData = $router->getJsonData();
$params = $router->getParams();
```

## Example API Implementation

```php
// Product API endpoint
$router->get('/api/products/{id}', function($id) {
    $products = json_decode(file_get_contents('data/products.json'), true);
    $product = array_filter($products, function($p) use ($id) {
        return $p['id'] == $id;
    });
    
    if ($product) {
        header('Content-Type: application/json');
        echo json_encode(array_values($product)[0]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
    }
});

// Cart API
$router->post('/api/cart/add', function() {
    $productId = $_POST['product_id'] ?? null;
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        return;
    }
    
    // Add to cart logic
    session_start();
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
    
    echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
});
```

## Integration with Existing Code

### Updating existing API calls

If you have existing JavaScript that calls `/ampmkp/api/cart.php`, you can now also use:

```javascript
// New router-based API calls
fetch('/ampmkp/api/cart/add', {
    method: 'POST',
    body: formData
});

// Get products
fetch('/ampmkp/api/products')
    .then(response => response.json())
    .then(products => {
        // Handle products
    });
```

### Adding to existing pages

```php
// At the top of an existing page like index.php
require_once 'bootstrap.php';

// Your existing page code continues normally
require_once 'includes/header.php';
// ... rest of page
```

## Error Handling

```php
// Custom 404 handler
$router->setNotFoundHandler(function() {
    include '404.php';
});

// Custom error handler
$router->setErrorHandler(function($exception) {
    error_log("Router Error: " . $exception->getMessage());
    http_response_code(500);
    echo "An error occurred";
});
```

## URL Generation

```php
// Generate URLs for routes
$productUrl = $router->url('/product/{id}', ['id' => 123]);
// Returns: /ampmkp/product/123

$categoryUrl = $router->url('/category/{name}', ['name' => 'electronics']);
// Returns: /ampmkp/category/electronics
```

## Testing Routes

You can test your routes using curl or a tool like Postman:

```bash
# Test API endpoints
curl -X GET http://localhost/ampmkp/api/products
curl -X POST http://localhost/ampmkp/api/cart/add -d "product_id=1&quantity=2"

# Test custom routes
curl -X GET http://localhost/ampmkp/blog/my-post-slug
curl -X GET http://localhost/ampmkp/user/123
```

## Best Practices

1. **Keep .htaccess routes for static pages** - Let your existing product/category pages work as they do
2. **Use router for APIs and dynamic content** - APIs, admin panels, user-generated content
3. **Implement proper middleware** - Authentication, logging, rate limiting
4. **Use route groups** - Organize related routes together
5. **Handle errors gracefully** - Provide meaningful error responses
6. **Log route access** - For debugging and analytics

## Troubleshooting

### Routes not working
1. Check if the route pattern matches your URL
2. Verify the route is defined before `$router->run()`
3. Ensure middleware isn't blocking the route

### Conflicts with .htaccess
1. Make sure router paths don't overlap with .htaccess patterns
2. Use the `isStaticPhpFile()` method to exclude conflicting routes
3. Test both systems independently

### Performance considerations
1. Only include the router where needed using `bootstrap.php`
2. Use route groups to minimize route checking
3. Cache route definitions for high-traffic sites

This router system gives you modern routing capabilities while maintaining full compatibility with your existing AMPMKP application structure. 