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

// Stripe Configuration
class StripeConfig {
    // Test API Keys
    const TEST_SECRET_KEY = 'sk_test_your_test_secret_key_here';
    const TEST_PUBLISHABLE_KEY = 'pk_test_your_test_publishable_key_here';
    
    // Live API Keys (set these in production)
    const LIVE_SECRET_KEY = '';
    const LIVE_PUBLISHABLE_KEY = '';
    
    // Environment (set to 'live' in production)
    const ENVIRONMENT = 'test';
    
    public static function getSecretKey() {
        return self::ENVIRONMENT === 'live' ? self::LIVE_SECRET_KEY : self::TEST_SECRET_KEY;
    }
    
    public static function getPublishableKey() {
        return self::ENVIRONMENT === 'live' ? self::LIVE_PUBLISHABLE_KEY : self::TEST_PUBLISHABLE_KEY;
    }
    
    public static function isLive() {
        return self::ENVIRONMENT === 'live';
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