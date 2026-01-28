<?php
/**
 * Stripe Configuration
 * Keep your Stripe API key protected by including it as an environment variable
 * or in a private script that does not publicly expose the source code.
 */

// Check if composer autoload exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    throw new Exception('Stripe PHP SDK not found. Please run: composer install');
}

// Load settings to get currency
require_once __DIR__ . '/functions.php';
$settings = getSettings();

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Only set if not already set
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Stripe Configuration
class StripeConfig {
    
    public static function getSecretKey() {
        $env = $_ENV['STRIPE_ENVIRONMENT'] ?? getenv('STRIPE_ENVIRONMENT') ?? 'test';
        if ($env === 'live') {
            return $_ENV['STRIPE_LIVE_SECRET_KEY'] ?? getenv('STRIPE_LIVE_SECRET_KEY') ?? '';
        }
        return $_ENV['STRIPE_TEST_SECRET_KEY'] ?? getenv('STRIPE_TEST_SECRET_KEY') ?? '';
    }
    
    public static function getPublishableKey() {
        $env = $_ENV['STRIPE_ENVIRONMENT'] ?? getenv('STRIPE_ENVIRONMENT') ?? 'test';
        if ($env === 'live') {
            return $_ENV['STRIPE_LIVE_PUBLISHABLE_KEY'] ?? getenv('STRIPE_LIVE_PUBLISHABLE_KEY') ?? '';
        }
        return $_ENV['STRIPE_TEST_PUBLISHABLE_KEY'] ?? getenv('STRIPE_TEST_PUBLISHABLE_KEY') ?? '';
    }
    
    public static function isLive() {
        $env = $_ENV['STRIPE_ENVIRONMENT'] ?? getenv('STRIPE_ENVIRONMENT') ?? 'test';
        return $env === 'live';
    }
    
    public static function getDomain() {
        // Handle CLI usage
        if (php_sapi_name() === 'cli') {
            return 'http://localhost';
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
    
    public static function init() {
        \Stripe\Stripe::setApiKey(self::getSecretKey());
    }
}

// Initialize Stripe
StripeConfig::init(); 