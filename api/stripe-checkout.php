<?php
require_once '../includes/stripe-config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Check if cart exists and has items
    $cart = getCart();
    if (empty($cart)) {
        throw new Exception('Cart is empty');
    }

    // Get cart details and calculate totals
    $cartItems = [];
    $subtotal = 0;
    
    $selectedCurrency = getSelectedCurrency();
    foreach ($cart as $item) {
        $product = getProductById($item['product_id']);
        if ($product) {
            $unit = getProductPrice($product, $selectedCurrency);
            $itemTotal = $unit * $item['quantity'];
            $cartItems[] = [
                'product' => $product,
                'quantity' => $item['quantity'],
                'item_total' => $itemTotal
            ];
            $subtotal += $itemTotal;
        }
    }

    if (empty($cartItems)) {
        throw new Exception('No valid items in cart');
    }

    // Get settings
    $settings = getSettings();
    $shippingSettings = getShippingSettings();
    if (session_status() == PHP_SESSION_NONE) { session_start(); }
    $selectedMethod = $_SESSION['shipping_method'] ?? getDefaultShippingMethod($shippingSettings);
    $selectedMethod = validateShippingMethod($selectedMethod, $shippingSettings);
    $shippingCost = computeShippingCost($subtotal, $selectedCurrency, $selectedMethod, $shippingSettings);
    
    $total = $subtotal + $shippingCost;

    // Create line items for Stripe
    $lineItems = [];
    
    // Add product items
    foreach ($cartItems as $item) {
        $lineItems[] = [
            'price_data' => [
                'currency' => strtolower($selectedCurrency),
                'product_data' => [
                    'name' => $item['product']['name'],
                    'description' => $item['product']['description'],
                    'images' => [StripeConfig::getDomain() . getAssetUrl('images/' . $item['product']['image'])],
                ],
                'unit_amount' => round($unit * 100), // Convert to cents/pence
            ],
            'quantity' => $item['quantity'],
        ];
    }
    
    // Add shipping as a line item if applicable
    if ($shippingCost > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => strtolower($selectedCurrency),
                'product_data' => [
                    'name' => 'Shipping',
                    'description' => 'Standard shipping',
                ],
                'unit_amount' => round($shippingCost * 100), // Convert to cents/pence
            ],
            'quantity' => 1,
        ];
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Create customer data if provided
    $customerData = null;
    if (isset($input['customer_data'])) {
        $customerData = json_decode($input['customer_data'], true);
    }

    // Create checkout session
    $sessionData = [
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => StripeConfig::getDomain() . getBaseUrl('stripe-success.php?session_id={CHECKOUT_SESSION_ID}'),
        'cancel_url' => StripeConfig::getDomain() . getBaseUrl('stripe-cancel.php'),
        'automatic_tax' => [
            'enabled' => false,
        ],
        'shipping_address_collection' => [
            'allowed_countries' => ['GB', 'US', 'CA', 'AU'], // Add more countries as needed
        ],
        'phone_number_collection' => [
            'enabled' => true,
        ],
        'metadata' => [
            'cart_items' => json_encode($cartItems),
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'total' => $total,
        ],
    ];

    // Add customer email if provided
    if ($customerData && !empty($customerData['email'])) {
        $sessionData['customer_email'] = $customerData['email'];
    }

    $checkoutSession = \Stripe\Checkout\Session::create($sessionData);

    // Return the session URL
    echo json_encode([
        'success' => true,
        'url' => $checkoutSession->url,
        'session_id' => $checkoutSession->id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 