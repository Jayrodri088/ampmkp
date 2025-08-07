<?php

class Router
{
    private $routes = [];
    private $middleware = [];
    private $notFoundCallback;
    private $errorCallback;
    
    /**
     * Add a GET route
     */
    public function get($pattern, $callback, $middleware = [])
    {
        $this->addRoute('GET', $pattern, $callback, $middleware);
    }
    
    /**
     * Add a POST route
     */
    public function post($pattern, $callback, $middleware = [])
    {
        $this->addRoute('POST', $pattern, $callback, $middleware);
    }
    
    /**
     * Add a PUT route
     */
    public function put($pattern, $callback, $middleware = [])
    {
        $this->addRoute('PUT', $pattern, $callback, $middleware);
    }
    
    /**
     * Add a DELETE route
     */
    public function delete($pattern, $callback, $middleware = [])
    {
        $this->addRoute('DELETE', $pattern, $callback, $middleware);
    }
    
    /**
     * Add a route for any HTTP method
     */
    public function any($pattern, $callback, $middleware = [])
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        foreach ($methods as $method) {
            $this->addRoute($method, $pattern, $callback, $middleware);
        }
    }
    
    /**
     * Add a route
     */
    private function addRoute($method, $pattern, $callback, $middleware = [])
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback,
            'middleware' => $middleware
        ];
    }
    
    /**
     * Add global middleware
     */
    public function middleware($callback)
    {
        $this->middleware[] = $callback;
    }
    
    /**
     * Set 404 handler
     */
    public function setNotFoundHandler($callback)
    {
        $this->notFoundCallback = $callback;
    }
    
    /**
     * Set error handler
     */
    public function setErrorHandler($callback)
    {
        $this->errorCallback = $callback;
    }
    
    /**
     * Run the router
     */
    public function run()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = $this->getUri();
            
            // Execute global middleware
            foreach ($this->middleware as $middleware) {
                $result = $this->executeCallback($middleware, []);
                if ($result === false) {
                    return;
                }
            }
            
            // Find matching route
            foreach ($this->routes as $route) {
                if ($route['method'] === $method || $route['method'] === 'ANY') {
                    $params = $this->matchRoute($route['pattern'], $uri);
                    if ($params !== false) {
                        // Execute route middleware
                        foreach ($route['middleware'] as $middleware) {
                            $result = $this->executeCallback($middleware, $params);
                            if ($result === false) {
                                return;
                            }
                        }
                        
                        // Execute route callback
                        $this->executeCallback($route['callback'], $params);
                        return;
                    }
                }
            }
            
            // No route found - check if it's a static PHP file that .htaccess should handle
            if ($this->isStaticPhpFile($uri)) {
                return; // Let .htaccess handle it
            }
            
            // 404 handler
            if ($this->notFoundCallback) {
                $this->executeCallback($this->notFoundCallback, []);
            } else {
                $this->defaultNotFound();
            }
            
        } catch (Exception $e) {
            if ($this->errorCallback) {
                $this->executeCallback($this->errorCallback, [$e]);
            } else {
                $this->defaultError($e);
            }
        }
    }
    
    /**
     * Get the current URI
     */
    private function getUri()
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove the base path if present
        $basePath = $this->getBasePath();
        if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Ensure URI starts with /
        if (empty($uri) || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        
                return rtrim($uri, '/') ?: '/';
    }

    /**
     * Get dynamic base path
     */
    private function getBasePath()
    {
        // Get the document root and current script directory
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $currentDir = __DIR__;
        
        // Calculate the relative path from document root
        $relativePath = str_replace($documentRoot, '', $currentDir);
        
        // Ensure it starts with / and normalize slashes
        $basePath = '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
        
        // Remove trailing slash if not root
        if ($basePath !== '/') {
            $basePath = rtrim($basePath, '/');
        }
        
        return $basePath;
    }

    /**
     * Match a route pattern against a URI
     */
    private function matchRoute($pattern, $uri)
    {
        // Convert pattern to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }
        
        return false;
    }
    
    /**
     * Execute a callback
     */
    private function executeCallback($callback, $params = [])
    {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        } elseif (is_string($callback)) {
            // Handle controller@method syntax
            if (strpos($callback, '@') !== false) {
                list($controller, $method) = explode('@', $callback);
                if (class_exists($controller)) {
                    $instance = new $controller();
                    if (method_exists($instance, $method)) {
                        return call_user_func_array([$instance, $method], $params);
                    }
                }
            } else {
                // Include file
                if (file_exists($callback)) {
                    include $callback;
                    return true;
                }
            }
        }
        
        throw new Exception("Invalid callback: " . print_r($callback, true));
    }
    
    /**
     * Check if URI points to a static PHP file that .htaccess should handle
     */
    private function isStaticPhpFile($uri)
    {
        $staticFiles = [
            '/index', '/', 
            '/shop', '/about', '/contact', 
            '/cart', '/checkout', '/order-success',
            '/search', '/debug_diagnostics'
        ];
        
        // Check if it's a static file or matches .htaccess patterns
        if (in_array($uri, $staticFiles)) {
            return true;
        }
        
        // Check for product pattern that .htaccess handles
        if (preg_match('/^\/product\/\d+$/', $uri)) {
            return true;
        }
        
        // Check for category pattern that .htaccess handles
        if (preg_match('/^\/category\/[^\/]+$/', $uri)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Default 404 handler
     */
    private function defaultNotFound()
    {
        http_response_code(404);
        if (file_exists('404.php')) {
            include '404.php';
        } else {
            echo '404 - Page Not Found';
        }
    }
    
    /**
     * Default error handler
     */
    private function defaultError($exception)
    {
        http_response_code(500);
        echo 'Error: ' . $exception->getMessage();
    }
    
    /**
     * Generate URL for a named route
     */
    public function url($pattern, $params = [])
    {
        $url = $pattern;
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        
        // Add base path
        $basePath = $this->getBasePath();
        return $basePath . $url;
    }
    
    /**
     * Redirect to URL
     */
    public function redirect($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Get current route parameters
     */
    public function getParams()
    {
        return $_GET;
    }
    
    /**
     * Get POST data
     */
    public function getPostData()
    {
        return $_POST;
    }
    
    /**
     * Get JSON input data
     */
    public function getJsonData()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
    
    /**
     * Send JSON response
     */
    public function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Group routes with common prefix and middleware
     */
    public function group($prefix, $middleware, $callback)
    {
        $originalRoutes = $this->routes;
        $this->routes = [];
        
        // Execute the callback to register routes
        $callback($this);
        
        // Prefix all new routes and add middleware
        $newRoutes = $this->routes;
        foreach ($newRoutes as &$route) {
            $route['pattern'] = rtrim($prefix, '/') . $route['pattern'];
            $route['middleware'] = array_merge($middleware, $route['middleware']);
        }
        
        // Merge back with original routes
        $this->routes = array_merge($originalRoutes, $newRoutes);
    }
} 