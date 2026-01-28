<?php
// Keep your Stripe API key protected by including it as an environment variable
// or in a private script that does not publicly expose the source code.

// Load from main project .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Get the appropriate Stripe secret key based on environment
$stripeEnv = $_ENV['STRIPE_ENVIRONMENT'] ?? 'test';
$stripeSecretKey = ($stripeEnv === 'live') 
    ? ($_ENV['STRIPE_LIVE_SECRET_KEY'] ?? '')
    : ($_ENV['STRIPE_TEST_SECRET_KEY'] ?? '');