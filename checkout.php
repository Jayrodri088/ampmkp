<?php
$page_title = 'Checkout';
$page_description = 'Complete your order at Angel Marketplace. Secure checkout process with multiple payment options.';

require_once 'includes/functions.php';

// Start session to access persisted currency
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if cart is empty
$cart = getCart();
if (empty($cart)) {
    header('Location: ' . getBaseUrl('cart.php'));
    exit;
}

// Get cart details and check for multiple currencies
$cartItems = [];
$availableCurrencies = [];
$settings = getSettings();
$shippingSettings = getShippingSettings();

// Use persisted currency from session, or fall back to default
$selectedCurrency = $_SESSION['selected_currency'] ?? $settings['currency_code'] ?? 'GBP';

// Build available currencies from settings
foreach ($settings['currencies'] as $curr) {
    $availableCurrencies[] = $curr['code'];
}

// Validate selected currency against available currencies
if (!in_array($selectedCurrency, $availableCurrencies)) {
    $selectedCurrency = $settings['currency_code'] ?? 'GBP';
}

foreach ($cart as $item) {
    $product = getProductById($item['product_id']);
    if ($product) {
        $cartItem = [
            'product' => $product,
            'quantity' => $item['quantity']
        ];
        
        // Add size if available
        if (isset($item['size']) && !empty($item['size'])) {
            $cartItem['size'] = $item['size'];
        }
        
        // Add color if available
        if (isset($item['color']) && !empty($item['color'])) {
            $cartItem['color'] = $item['color'];
        }
        
        $cartItems[] = $cartItem;
    }
}

// Calculate totals based on selected currency
$subtotal = 0;
foreach ($cartItems as $index => $item) {
    $product = $item['product'];
    $price = 0;
    
    if (isset($product['prices'][$selectedCurrency])) {
        $price = $product['prices'][$selectedCurrency];
    } elseif (isset($product['price'])) {
        $price = $product['price']; // Fallback to legacy price
    }
    
    $cartItems[$index]['unit_price'] = $price;
    $cartItems[$index]['item_total'] = $price * $item['quantity'];
    $subtotal += $cartItems[$index]['item_total'];
}

// Determine desired shipping method (allow override by user when enabled)
$selectedMethod = $_POST['shipping_method'] ?? $_GET['shipping_method'] ?? $_SESSION['shipping_method'] ?? getDefaultShippingMethod($shippingSettings);
$selectedMethod = validateShippingMethod($selectedMethod, $shippingSettings);
$_SESSION['shipping_method'] = $selectedMethod;

// Calculate shipping cost using helper
$shippingCost = computeShippingCost($subtotal, $selectedCurrency, $selectedMethod, $shippingSettings);
$total = $subtotal + $shippingCost;

// Calculate delivery cost separately for display in shipping method options
$deliveryCost = computeShippingCost($subtotal, $selectedCurrency, 'delivery', $shippingSettings);

// Define payment methods based on currency
function getPaymentMethodsForCurrency($currency) {
    $paymentMethods = [];
    
    switch (strtoupper($currency)) {
        case 'USD':
        case 'EUR':
        case 'GBP':
            $paymentMethods = ['stripe', 'paypal', 'bank_transfer'];
            break;
        case 'NGN':
        case 'NAIRA':
            $paymentMethods = ['stripe', 'bank_transfer'];
            break;
        case 'ESP':
        case 'ESPEES':
            $paymentMethods = ['espees'];
            break;
        default:
            $paymentMethods = ['bank_transfer'];
            break;
    }
    
    return $paymentMethods;
}

$availablePaymentMethods = getPaymentMethodsForCurrency($selectedCurrency);

// Generate form token to prevent duplicate submissions
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
}

$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Debug logging
    error_log('=== CHECKOUT POST REQUEST ===');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('shipping_method: ' . ($_POST['shipping_method'] ?? 'NOT SET'));
    error_log('payment_method: ' . ($_POST['payment_method'] ?? 'NOT SET'));
    
    // Check if this is a JSON request or form data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJsonRequest = strpos($contentType, 'application/json') !== false;
    
    if ($isJsonRequest || (isset($_POST['ajax']) && $_POST['ajax'] === '1')) {
        // Handle JSON request from JavaScript
        $requestData = $isJsonRequest ? json_decode(file_get_contents('php://input'), true) : $_POST;
        if ($isJsonRequest && json_last_error() !== JSON_ERROR_NONE) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
            exit;
        }
        
        // Extract customer data from JSON request
        $customerData = [
            'email' => sanitizeInput($requestData['customer']['email'] ?? ''),
            'first_name' => sanitizeInput($requestData['customer']['first_name'] ?? ''),
            'last_name' => sanitizeInput($requestData['customer']['last_name'] ?? ''),
            'phone' => sanitizeInput($requestData['customer']['phone'] ?? ''),
            'countryCode' => sanitizeInput($requestData['customer']['countryCode'] ?? '+44'),
            'address' => sanitizeInput($requestData['shipping']['address'] ?? ''),
            'city' => sanitizeInput($requestData['shipping']['city'] ?? ''),
            'postal_code' => sanitizeInput($requestData['shipping']['postal_code'] ?? ''),
            'country' => sanitizeInput($requestData['shipping']['country'] ?? ''),
            'payment_method' => sanitizeInput($requestData['payment_method'] ?? ''),
            'special_instructions' => sanitizeInput($requestData['special_instructions'] ?? ''),
            'selected_currency' => sanitizeInput($requestData['selected_currency'] ?? $selectedCurrency),
            'bank_name' => sanitizeInput($requestData['bank_name'] ?? ''),
            'account_holder' => sanitizeInput($requestData['account_holder'] ?? ''),
            'paypal_email' => sanitizeInput($requestData['paypal_email'] ?? '')
        ];

        // Quick path: AJAX toggle for shipping method
        if (isset($requestData['ajax']) && $requestData['ajax'] === '1' && isset($requestData['shipping_method'])) {
            $_SESSION['shipping_method'] = validateShippingMethod($requestData['shipping_method'], $shippingSettings);
            $selectedMethod = $_SESSION['shipping_method'];
            // Recompute shipping and totals
            $shippingCost = computeShippingCost($subtotal, $selectedCurrency, $selectedMethod, $shippingSettings);
            $total = $subtotal + $shippingCost;
            if ($selectedMethod === 'pickup') {
                $shippingHtml = 'Pickup';
                $shippingFormatted = 'Pickup';
            } elseif ($shippingCost > 0) {
                $shippingHtml = formatPriceWithCurrency($shippingCost, $selectedCurrency);
                $shippingFormatted = formatPriceWithCurrency($shippingCost, $selectedCurrency);
            } else {
                $shippingHtml = '<span class="text-green-600">Free</span>';
                $shippingFormatted = 'Free';
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'subtotal_formatted' => formatPriceWithCurrency($subtotal, $selectedCurrency),
                'shipping_formatted' => $shippingFormatted,
                'shipping_html' => $shippingHtml,
                'total_formatted' => formatPriceWithCurrency($total, $selectedCurrency),
                'requires_address' => isAddressRequiredForMethod($selectedMethod, $shippingSettings)
            ]);
            exit;
        }
    } else {
        // Handle traditional form submission
        error_log('Traditional form submission detected');
        $submittedToken = $_POST['checkout_token'] ?? '';
        error_log('Submitted token: ' . substr($submittedToken, 0, 10) . '...');
        error_log('Session token: ' . substr($_SESSION['checkout_token'] ?? '', 0, 10) . '...');
        
        if (empty($submittedToken) || !hash_equals($_SESSION['checkout_token'], $submittedToken)) {
            $error = 'Invalid form submission. Please try again.';
            error_log('TOKEN MISMATCH!');
        } else {
            error_log('Token validation passed');
            // Get form data for actual checkout submission
            $customerData = [
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                'phone' => sanitizeInput($_POST['phone'] ?? ''),
                'countryCode' => sanitizeInput($_POST['countryCode'] ?? '+44'),
                'address' => sanitizeInput($_POST['address'] ?? ''),
                'city' => sanitizeInput($_POST['city'] ?? ''),
                'postal_code' => sanitizeInput($_POST['postal_code'] ?? ''),
                'country' => sanitizeInput($_POST['country'] ?? ''),
                'payment_method' => sanitizeInput($_POST['payment_method'] ?? ''),
                    'special_instructions' => sanitizeInput($_POST['special_instructions'] ?? ''),
                    'selected_currency' => sanitizeInput($_POST['selected_currency'] ?? $selectedCurrency),
                    // Optional payment detail fields (e.g., bank transfer payer name)
                    'account_holder' => sanitizeInput($_POST['account_holder'] ?? '')
            ];
            error_log('Customer data collected');
        }
    }
    
    error_log('Before validation check - customerData isset: ' . (isset($customerData) ? 'YES' : 'NO'));
    error_log('Before validation check - error isset: ' . (isset($error) ? 'YES' : 'NO'));
    error_log('Before validation check - error empty: ' . (empty($error) ? 'YES' : 'NO'));
    if (isset($error)) {
        error_log('Error value: "' . $error . '"');
    }
    
    // Continue with validation and processing if we have customer data and no error
    if (isset($customerData) && empty($error)) {
        error_log('Starting validation and processing');
        
        // Determine which fields are required based on shipping method
        $shippingMethod = $_POST['shipping_method'] ?? $_SESSION['shipping_method'] ?? 'delivery';
        $requiresAddress = $shippingMethod !== 'pickup';
        
        error_log('Shipping method: ' . $shippingMethod);
        error_log('Requires address: ' . ($requiresAddress ? 'YES' : 'NO'));
        
        // Build required fields list
        $required_fields = ['email', 'first_name', 'last_name', 'payment_method'];
        
        // Only require address fields for delivery
        if ($requiresAddress) {
            $required_fields = array_merge($required_fields, ['address', 'city', 'postal_code', 'country']);
        }
        
        error_log('Required fields: ' . implode(', ', $required_fields));
        
        $missing_fields = [];
    
        foreach ($required_fields as $field) {
            if (empty($customerData[$field])) {
                $missing_fields[] = ucwords(str_replace('_', ' ', $field));
                error_log('Missing field: ' . $field);
            }
        }
    
        error_log('Missing fields count: ' . count($missing_fields));
    
        if (!empty($missing_fields)) {
            $error = 'Please fill in all required fields to continue.';
            error_log('Validation failed - missing fields');
        } elseif (!validateEmail($customerData['email'])) {
            $error = 'Please enter a valid email address.';
            error_log('Validation failed - invalid email');
        } elseif ($customerData['payment_method'] === 'stripe') {
            // Stripe payments should not be processed here
            $error = 'Please use the Stripe checkout button to complete your payment.';
            error_log('Validation failed - stripe payment attempted');
        } else {
            error_log('Validation passed - creating order');
            // Create order for non-Stripe payment methods
            $orderId = 'AMP' . date('Y') . sprintf('%06d', time() % 1000000);
            
            $orderData = [
                'id' => $orderId,
                'customer' => $customerData,
                'customer_name' => trim(($customerData['first_name'] ?? '') . ' ' . ($customerData['last_name'] ?? '')),
                'customer_email' => $customerData['email'] ?? '',
                'customer_phone' => $customerData['phone'] ?? '',
                'items' => $cartItems,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'shipping_method' => $shippingMethod,
                'total' => $total,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $customerData['payment_method'] ?? 'bank_transfer',
                'created_at' => date('Y-m-d H:i:s'),
                'currency' => $selectedCurrency,
                'shipping_address' => [
                    'line1' => $customerData['address'] ?? '',
                    'city' => $customerData['city'] ?? '',
                    'postcode' => $customerData['postal_code'] ?? '',
                    'country' => $customerData['country'] ?? ''
                ],
                'billing_address' => [
                    'line1' => $customerData['address'] ?? '',
                    'city' => $customerData['city'] ?? '',
                    'postcode' => $customerData['postal_code'] ?? '',
                    'country' => $customerData['country'] ?? ''
                ]
            ];
            
            // Save order
            $orders = readJsonFile('orders.json');
            $orders[] = $orderData;
            
            if (writeJsonFile('orders.json', $orders)) {
                // Send admin notification for pending payment orders
                try {
                    if (function_exists('sendPendingOrderNotificationToAdmin')) {
                        $sent = sendPendingOrderNotificationToAdmin($orderData);
                        if (!$sent && function_exists('logError')) {
                            logError('Pending order admin email failed', ['order_id' => $orderId]);
                        }
                    }
                } catch (Throwable $e) {
                    error_log('Pending order admin email error: ' . $e->getMessage());
                    if (function_exists('logError')) {
                        logError('Pending order admin email exception: ' . $e->getMessage(), ['order_id' => $orderId]);
                    }
                }

                // Send customer order confirmation email
                try {
                    if (function_exists('sendPendingOrderConfirmationToCustomer')) {
                        $sentCustomer = sendPendingOrderConfirmationToCustomer($orderData);
                        if (!$sentCustomer && function_exists('logError')) {
                            logError('Pending order customer email failed', ['order_id' => $orderId]);
                        }
                    }
                } catch (Throwable $e) {
                    error_log('Pending order customer email error: ' . $e->getMessage());
                    if (function_exists('logError')) {
                        logError('Pending order customer email exception: ' . $e->getMessage(), ['order_id' => $orderId]);
                    }
                }

                // Clear cart
                clearCart();
                
                // Regenerate token after successful submission
                $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
                
                if ($isJsonRequest) {
                    // Return JSON response for AJAX requests
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'redirect_url' => getBaseUrl('payment-pending.php?order=' . $orderId),
                        'order_id' => $orderId
                    ]);
                    exit;
                } else {
                    // Redirect to payment pending page for manual payment methods
                    header('Location: ' . getBaseUrl('payment-pending.php?order=' . $orderId));
                    exit;
                }
            } else {
                if ($isJsonRequest) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Sorry, there was an error processing your order. Please try again.']);
                    exit;
                } else {
                    $error = 'Sorry, there was an error processing your order. Please try again.';
                }
            }
        }
    }
    
    // Handle errors for JSON requests
    if (isset($isJsonRequest) && $isJsonRequest && !empty($error)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <a href="<?php echo getBaseUrl('cart.php'); ?>" class="text-gray-500 hover:text-folly transition-colors">Cart</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">Checkout</span>
        </nav>
    </div>
</div>

<!-- Checkout Section -->
<section class="py-12 md:py-20 bg-gradient-to-b from-gray-50 to-white min-h-screen">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <p class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-3">Checkout</p>
            <h1 class="text-3xl md:text-5xl font-bold text-charcoal-900 mb-4 font-display tracking-tight">
                Secure Checkout
            </h1>
            <p class="text-gray-500 max-w-2xl mx-auto">
                Complete your order securely. We accept multiple payment methods for your convenience.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="mb-8 p-4 bg-red-50 border border-red-100 rounded-xl flex items-start gap-3 max-w-4xl mx-auto">
                <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="font-bold text-red-800">Error</h3>
                    <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" id="checkout-form" class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
            <input type="hidden" name="checkout_token" value="<?php echo htmlspecialchars($_SESSION['checkout_token']); ?>">
            
            <!-- Left Column: Customer Info & Shipping -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Customer Information -->
                <div class="checkout-step glass-strong rounded-3xl p-6 md:p-8">
                    <h2 class="text-xl font-bold text-charcoal-900 mb-6 flex items-center gap-3">
                        <span class="step-number w-8 h-8 rounded-full bg-folly/10 text-folly flex items-center justify-center text-sm font-bold">1</span>
                        Customer Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">First Name <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                name="first_name" 
                                value="<?php echo htmlspecialchars($customerData['first_name'] ?? ''); ?>"
                                required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Last Name <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                name="last_name" 
                                value="<?php echo htmlspecialchars($customerData['last_name'] ?? ''); ?>"
                                required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                            >
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Address <span class="text-red-500">*</span></label>
                            <input 
                                type="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($customerData['email'] ?? ''); ?>"
                                required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                            >
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Phone Number</label>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <select id="countryCode" name="countryCode" class="w-full sm:w-32 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none appearance-none cursor-pointer" onchange="updatePhonePlaceholder()">
                                    <option value="+44" data-format="XXXX XXX XXXX" selected>🇬🇧 +44</option>
                                    <option value="+1" data-format="(XXX) XXX-XXXX">🇺🇸 +1</option>
                                    <option value="+1" data-format="(XXX) XXX-XXXX">🇨🇦 +1</option>
                                    <option value="+234" data-format="XXX XXX XXXX">🇳🇬 +234</option>
                                    <option value="+353" data-format="XX XXX XXXX">🇮🇪 +353</option>
                                    <option value="+49" data-format="XXX XXXXXXXX">🇩🇪 +49</option>
                                    <option value="+33" data-format="X XX XX XX XX">🇫🇷 +33</option>
                                    <option value="+39" data-format="XXX XXX XXXX">🇮🇹 +39</option>
                                    <option value="+34" data-format="XXX XXX XXX">🇪🇸 +34</option>
                                    <option value="+31" data-format="X XXXXXXXX">🇳🇱 +31</option>
                                    <option value="+32" data-format="XXX XX XX XX">🇧🇪 +32</option>
                                    <option value="+41" data-format="XX XXX XX XX">🇨🇭 +41</option>
                                    <option value="+43" data-format="XXX XXXXXX">🇦🇹 +43</option>
                                    <option value="+46" data-format="XX XXX XX XX">🇸🇪 +46</option>
                                    <option value="+47" data-format="XXX XX XXX">🇳🇴 +47</option>
                                    <option value="+45" data-format="XX XX XX XX">🇩🇰 +45</option>
                                    <option value="+358" data-format="XX XXX XXXX">🇫🇮 +358</option>
                                    <option value="+48" data-format="XXX XXX XXX">🇵🇱 +48</option>
                                    <option value="+351" data-format="XXX XXX XXX">🇵🇹 +351</option>
                                    <option value="+30" data-format="XXX XXX XXXX">🇬🇷 +30</option>
                                    <option value="+61" data-format="XXX XXX XXX">🇦🇺 +61</option>
                                    <option value="+64" data-format="XX XXX XXXX">🇳🇿 +64</option>
                                    <option value="+27" data-format="XX XXX XXXX">🇿🇦 +27</option>
                                    <option value="+233" data-format="XX XXX XXXX">🇬🇭 +233</option>
                                    <option value="+254" data-format="XXX XXXXXX">🇰🇪 +254</option>
                                    <option value="+256" data-format="XXX XXXXXX">🇺🇬 +256</option>
                                    <option value="+255" data-format="XXX XXX XXX">🇹🇿 +255</option>
                                    <option value="+91" data-format="XXXXX XXXXX">🇮🇳 +91</option>
                                    <option value="+92" data-format="XXX XXXXXXX">🇵🇰 +92</option>
                                    <option value="+880" data-format="XXXX XXXXXX">🇧🇩 +880</option>
                                    <option value="+86" data-format="XXX XXXX XXXX">🇨🇳 +86</option>
                                    <option value="+81" data-format="XX XXXX XXXX">🇯🇵 +81</option>
                                    <option value="+82" data-format="XX XXXX XXXX">🇰🇷 +82</option>
                                    <option value="+65" data-format="XXXX XXXX">🇸🇬 +65</option>
                                    <option value="+60" data-format="XX XXX XXXX">🇲🇾 +60</option>
                                    <option value="+63" data-format="XXX XXX XXXX">🇵🇭 +63</option>
                                    <option value="+66" data-format="XX XXX XXXX">🇹🇭 +66</option>
                                    <option value="+84" data-format="XX XXX XXXX">🇻🇳 +84</option>
                                    <option value="+62" data-format="XXX XXX XXXX">🇮🇩 +62</option>
                                    <option value="+971" data-format="XX XXX XXXX">🇦🇪 +971</option>
                                    <option value="+966" data-format="XX XXX XXXX">🇸🇦 +966</option>
                                    <option value="+974" data-format="XXXX XXXX">🇶🇦 +974</option>
                                    <option value="+973" data-format="XXXX XXXX">🇧🇭 +973</option>
                                    <option value="+968" data-format="XXXX XXXX">🇴🇲 +968</option>
                                    <option value="+965" data-format="XXXX XXXX">🇰🇼 +965</option>
                                    <option value="+972" data-format="XX XXX XXXX">🇮🇱 +972</option>
                                    <option value="+90" data-format="XXX XXX XXXX">🇹🇷 +90</option>
                                    <option value="+20" data-format="XX XXXX XXXX">🇪🇬 +20</option>
                                    <option value="+212" data-format="XX XXX XXXX">🇲🇦 +212</option>
                                    <option value="+55" data-format="XX XXXXX XXXX">🇧🇷 +55</option>
                                    <option value="+52" data-format="XX XXXX XXXX">🇲🇽 +52</option>
                                    <option value="+54" data-format="XX XXXX XXXX">🇦🇷 +54</option>
                                    <option value="+57" data-format="XXX XXX XXXX">🇨🇴 +57</option>
                                    <option value="+56" data-format="X XXXX XXXX">🇨🇱 +56</option>
                                    <option value="+51" data-format="XXX XXX XXX">🇵🇪 +51</option>
                                    <option value="+58" data-format="XXX XXX XXXX">🇻🇪 +58</option>
                                    <option value="+7" data-format="XXX XXX XX XX">🇷🇺 +7</option>
                                    <option value="+380" data-format="XX XXX XXXX">🇺🇦 +380</option>
                                    <option value="+375" data-format="XX XXX XX XX">🇧🇾 +375</option>
                                    <option value="+40" data-format="XXX XXX XXX">🇷🇴 +40</option>
                                    <option value="+36" data-format="XX XXX XXXX">🇭🇺 +36</option>
                                    <option value="+420" data-format="XXX XXX XXX">🇨🇿 +420</option>
                                    <option value="+421" data-format="XXX XXX XXX">🇸🇰 +421</option>
                                    <option value="+385" data-format="XX XXX XXXX">🇭🇷 +385</option>
                                    <option value="+386" data-format="XX XXX XXX">🇸🇮 +386</option>
                                    <option value="+381" data-format="XX XXX XXXX">🇷🇸 +381</option>
                                    <option value="+359" data-format="XX XXX XXXX">🇧🇬 +359</option>
                                    <option value="+370" data-format="XXX XXXXX">🇱🇹 +370</option>
                                    <option value="+371" data-format="XXXX XXXX">🇱🇻 +371</option>
                                    <option value="+372" data-format="XXXX XXXX">🇪🇪 +372</option>
                                </select>
                                <input 
                                    type="tel" 
                                    id="phone"
                                    name="phone" 
                                    value="<?php echo htmlspecialchars($customerData['phone'] ?? ''); ?>"
                                    placeholder="Enter phone number"
                                    class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Method -->
                <?php if (!empty($shippingSettings['allow_method_selection'])): ?>
                <div class="checkout-step glass-strong rounded-3xl p-6 md:p-8">
                    <h2 class="text-xl font-bold text-charcoal-900 mb-6 flex items-center gap-3">
                        <span class="step-number w-8 h-8 rounded-full bg-folly/10 text-folly flex items-center justify-center text-sm font-bold">2</span>
                        Shipping Method
                    </h2>
                    
                    <div class="space-y-4" id="shipping-methods">
                        <?php if (!empty($shippingSettings['enable_delivery'])): ?>
                        <label class="shipping-method-option relative block cursor-pointer group">
                            <input type="radio" name="shipping_method" value="delivery" class="peer sr-only" <?php echo $selectedMethod === 'delivery' ? 'checked' : ''; ?> onchange="onShippingMethodChange('delivery')">
                            <div class="p-4 rounded-xl border-2 border-gray-100 peer-checked:border-folly peer-checked:bg-folly/5 transition-all flex items-center justify-between group-hover:border-folly/50">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 peer-checked:bg-folly peer-checked:text-white transition-colors">
                                        <i class="bi bi-truck text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-charcoal-900">Standard Delivery</h3>
                                        <p class="text-sm text-gray-500">Delivered to your doorstep</p>
                                    </div>
                                </div>
                                <span class="font-bold text-charcoal-900 delivery-cost">
                                    <?php echo $deliveryCost > 0 ? formatPriceWithCurrency($deliveryCost, $selectedCurrency) : '<span class="text-green-600">Free</span>'; ?>
                                </span>
                            </div>
                        </label>
                        <?php endif; ?>

                        <?php if (!empty($shippingSettings['enable_pickup'])): ?>
                        <label class="shipping-method-option relative block cursor-pointer group">
                            <input type="radio" name="shipping_method" value="pickup" class="peer sr-only" <?php echo $selectedMethod === 'pickup' ? 'checked' : ''; ?> onchange="onShippingMethodChange('pickup')">
                            <div class="p-4 rounded-xl border-2 border-gray-100 peer-checked:border-folly peer-checked:bg-folly/5 transition-all flex items-center justify-between group-hover:border-folly/50">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 peer-checked:bg-folly peer-checked:text-white transition-colors">
                                        <i class="bi bi-shop text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-charcoal-900"><?php echo htmlspecialchars($shippingSettings['pickup_label'] ?? 'Store Pickup'); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($shippingSettings['pickup_instructions'] ?? 'Pick up from our store'); ?></p>
                                    </div>
                                </div>
                                <span class="font-bold text-green-600">Free</span>
                            </div>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Shipping Address -->
                <div id="shipping-address-section" class="checkout-step glass-strong rounded-3xl p-6 md:p-8 <?php echo $selectedMethod === 'pickup' ? 'hidden' : ''; ?>">
                    <h2 class="text-xl font-bold text-charcoal-900 mb-6 flex items-center gap-3">
                        <span class="step-number w-8 h-8 rounded-full bg-folly/10 text-folly flex items-center justify-center text-sm font-bold">3</span>
                        Shipping Address
                    </h2>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Street Address <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                id="address"
                                name="address" 
                                value="<?php echo htmlspecialchars($customerData['address'] ?? ''); ?>"
                                <?php echo isAddressRequiredForMethod($selectedMethod, $shippingSettings) ? 'required' : ''; ?>
                                placeholder="123 Main Street, Apt 4B"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                            >
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">City <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    id="city"
                                    name="city" 
                                    value="<?php echo htmlspecialchars($customerData['city'] ?? ''); ?>"
                                    <?php echo isAddressRequiredForMethod($selectedMethod, $shippingSettings) ? 'required' : ''; ?>
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Postal Code <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    id="postal_code"
                                    name="postal_code" 
                                    value="<?php echo htmlspecialchars($customerData['postal_code'] ?? ''); ?>"
                                    <?php echo isAddressRequiredForMethod($selectedMethod, $shippingSettings) ? 'required' : ''; ?>
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Country <span class="text-red-500">*</span></label>
                                <select 
                                    id="country"
                                    name="country" 
                                    <?php echo isAddressRequiredForMethod($selectedMethod, $shippingSettings) ? 'required' : ''; ?>
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none appearance-none cursor-pointer"
                                >
                                    <option value="">Select Country</option>
                                    <option value="US" <?php echo ($customerData['country'] ?? '') === 'US' ? 'selected' : ''; ?>>🇺🇸 United States</option>
                                    <option value="GB" <?php echo ($customerData['country'] ?? '') === 'GB' ? 'selected' : ''; ?>>🇬🇧 United Kingdom</option>
                                    <option value="NG" <?php echo ($customerData['country'] ?? '') === 'NG' ? 'selected' : ''; ?>>🇳🇬 Nigeria</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="checkout-step glass-strong rounded-3xl p-6 md:p-8">
                    <h2 class="text-xl font-bold text-charcoal-900 mb-6 flex items-center gap-3">
                        <span class="step-number w-8 h-8 rounded-full bg-folly/10 text-folly flex items-center justify-center text-sm font-bold"><?php echo $selectedMethod === 'pickup' ? '3' : '4'; ?></span>
                        Payment Method
                    </h2>
                    <input type="hidden" name="selected_currency" value="<?php echo htmlspecialchars($selectedCurrency); ?>">

                    <div class="space-y-4">
                        <?php if (in_array('stripe', $availablePaymentMethods)): ?>
                        <label class="relative block cursor-pointer group">
                            <input type="radio" name="payment_method" value="stripe" class="peer sr-only" <?php echo ($customerData['payment_method'] ?? '') === 'stripe' ? 'checked' : ''; ?> onchange="showPaymentForm('stripe')">
                            <div class="p-4 rounded-xl border-2 border-gray-100 peer-checked:border-folly peer-checked:bg-folly/5 transition-all flex items-center gap-4 group-hover:border-folly/50">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 peer-checked:bg-folly peer-checked:text-white transition-colors">
                                    <i class="bi bi-credit-card text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-charcoal-900">Credit/Debit Card</h3>
                                    <p class="text-sm text-gray-500">Secure payment via Stripe</p>
                                </div>
                            </div>
                        </label>
                        <?php endif; ?>

                        <?php if (in_array('paypal', $availablePaymentMethods)): ?>
                        <label class="relative block cursor-pointer group">
                            <input type="radio" name="payment_method" value="paypal" class="peer sr-only" <?php echo ($customerData['payment_method'] ?? '') === 'paypal' ? 'checked' : ''; ?> onchange="showPaymentForm('paypal')">
                            <div class="p-4 rounded-xl border-2 border-gray-100 peer-checked:border-folly peer-checked:bg-folly/5 transition-all flex items-center gap-4 group-hover:border-folly/50">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 peer-checked:bg-folly peer-checked:text-white transition-colors">
                                    <i class="bi bi-paypal text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-charcoal-900">PayPal</h3>
                                    <p class="text-sm text-gray-500">Pay via PayPal account</p>
                                </div>
                            </div>
                        </label>
                        <?php endif; ?>

                        <?php if (in_array('bank_transfer', $availablePaymentMethods)): ?>
                        <label class="relative block cursor-pointer group">
                            <input type="radio" name="payment_method" value="bank_transfer" class="peer sr-only" <?php echo ($customerData['payment_method'] ?? '') === 'bank_transfer' ? 'checked' : ''; ?> onchange="showPaymentForm('bank_transfer')">
                            <div class="p-4 rounded-xl border-2 border-gray-100 peer-checked:border-folly peer-checked:bg-folly/5 transition-all flex items-center gap-4 group-hover:border-folly/50">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 peer-checked:bg-folly peer-checked:text-white transition-colors">
                                    <i class="bi bi-bank text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-charcoal-900">Bank Transfer</h3>
                                    <p class="text-sm text-gray-500">Direct bank transfer</p>
                                </div>
                            </div>
                        </label>
                        <?php endif; ?>
                        
                        <?php if (in_array('espees', $availablePaymentMethods)): ?>
                        <label class="relative block cursor-pointer group">
                            <input type="radio" name="payment_method" value="espees" class="peer sr-only" <?php echo ($customerData['payment_method'] ?? '') === 'espees' ? 'checked' : ''; ?> onchange="showPaymentForm('espees')">
                            <div class="p-4 rounded-xl border-2 border-gray-100 peer-checked:border-folly peer-checked:bg-folly/5 transition-all flex items-center gap-4 group-hover:border-folly/50">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 peer-checked:bg-folly peer-checked:text-white transition-colors">
                                    <i class="bi bi-wallet2 text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-charcoal-900">Espees</h3>
                                    <p class="text-sm text-gray-500">Pay with Espees wallet</p>
                                </div>
                            </div>
                        </label>
                        <?php endif; ?>
                    </div>

                    <!-- Payment Forms Container -->
                    <div class="mt-8">
                        <!-- Stripe Form -->
                        <div id="stripe-form" class="payment-form hidden">
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
                                <h3 class="font-bold text-charcoal-900 mb-4">Card Details</h3>
                                <div class="space-y-4">
                                    <div id="card-element" class="p-4 bg-white border border-gray-200 rounded-xl"></div>
                                    <div id="card-errors" class="text-red-500 text-sm"></div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Cardholder Name</label>
                                        <input type="text" id="cardholder_name" class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly outline-none">
                                    </div>
                                </div>
                                <div id="payment-processing" class="hidden mt-4 text-folly font-bold flex items-center gap-2">
                                    <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    Processing payment...
                                </div>
                            </div>
                        </div>

                        <!-- PayPal Form -->
                        <div id="paypal-form" class="payment-form hidden">
                            <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100 text-center">
                                <img src="https://www.paypalobjects.com/webstatic/icon/pp24.png" alt="PayPal" class="h-8 mx-auto mb-4">
                                <p class="text-blue-900 mb-4">You will be redirected to PayPal to complete your payment.</p>
                                <a href="http://paypal.me/amp202247" target="_blank" class="inline-flex items-center gap-2 bg-[#0070ba] text-white px-6 py-3 rounded-xl font-bold hover:bg-[#003087] transition-colors">
                                    Pay on PayPal
                                </a>
                                <p class="text-xs text-blue-700 mt-4">After payment, please click "Complete Order" below.</p>
                            </div>
                        </div>

                        <!-- Bank Transfer Form -->
                        <div id="bank_transfer-form" class="payment-form hidden">
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
                                <h3 class="font-bold text-charcoal-900 mb-4">Bank Transfer Details</h3>
                                <?php if (strtoupper($selectedCurrency) === 'NGN' || strtoupper($selectedCurrency) === 'NAIRA'): ?>
                                    <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-2 text-sm">
                                        <div class="flex justify-between"><span class="text-gray-500">Bank:</span> <span class="font-bold text-charcoal-900">Parallex Bank</span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Account Number:</span> <span class="font-bold text-charcoal-900 font-mono">100004476</span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Account Name:</span> <span class="font-bold text-charcoal-900">ANGELMP</span></div>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-2 text-sm">
                                        <div class="flex justify-between"><span class="text-gray-500">Bank:</span> <span class="font-bold text-charcoal-900">Monzo</span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Sort Code:</span> <span class="font-bold text-charcoal-900 font-mono">04-00-04</span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Account Number:</span> <span class="font-bold text-charcoal-900 font-mono">64689014</span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Account Name:</span> <span class="font-bold text-charcoal-900">Angel Marketplace</span></div>
                                    </div>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500 mt-4">Please use your Order ID as the payment reference.</p>
                                <div class="mt-4">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Account Holder Name</label>
                                    <input
                                        type="text"
                                        name="account_holder"
                                        placeholder="Name on the bank account sending payment"
                                        class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                    >
                                    <p class="text-[11px] text-gray-500 mt-1">Helps us match your transfer quickly.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Espees Form -->
                        <div id="espees-form" class="payment-form hidden">
                            <div class="bg-purple-50 p-6 rounded-2xl border border-purple-100 text-center">
                                <h3 class="font-bold text-purple-900 mb-4">Espees Payment</h3>
                                <div class="bg-white p-4 rounded-xl border border-purple-200 inline-block mb-4">
                                    <span class="block text-xs text-purple-500 uppercase font-bold">Payable to</span>
                                    <span class="block text-xl font-mono font-bold text-purple-900">ANGELMP</span>
                                </div>
                                <p class="text-sm text-purple-800">Send exact amount to the username above.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Special Instructions -->
                <div class="checkout-step glass-strong rounded-3xl p-6 md:p-8">
                    <h2 class="text-xl font-bold text-charcoal-900 mb-6 flex items-center gap-3">
                        <span class="step-number w-8 h-8 rounded-full bg-folly/10 text-folly flex items-center justify-center text-sm font-bold"><?php echo $selectedMethod === 'pickup' ? '4' : '5'; ?></span>
                        Special Instructions
                    </h2>
                    <textarea 
                        name="special_instructions" 
                        rows="3" 
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                        placeholder="Any special notes for delivery..."
                    ><?php echo htmlspecialchars($customerData['special_instructions'] ?? ''); ?></textarea>
                </div>

            </div>

            <!-- Right Column: Order Summary -->
            <div class="lg:col-span-1 lg:self-start">
                <div class="glass-strong rounded-3xl p-6 md:p-8 lg:sticky md:sticky top-6 lg:top-8">
                    <h2 class="text-xl font-bold text-charcoal-900 mb-6">Order Summary</h2>
                    
                    <div class="space-y-4 mb-6 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="flex gap-4">
                                <img 
                                    src="<?php echo getAssetUrl('images/' . $item['product']['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($item['product']['name']); ?>"
                                    class="w-16 h-16 rounded-xl object-cover bg-gray-50"
                                    onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                >
                                <div class="flex-1">
                                    <h4 class="font-bold text-charcoal-900 text-sm line-clamp-2"><?php echo htmlspecialchars($item['product']['name']); ?></h4>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Qty: <?php echo $item['quantity']; ?>
                                        <?php if (isset($item['size'])): ?> | <?php echo htmlspecialchars($item['size']); ?><?php endif; ?>
                                    </div>
                                    <div class="text-sm font-bold text-folly mt-1">
                                        <?php echo formatPriceWithCurrency($item['item_total'], $selectedCurrency); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="space-y-3 border-t border-gray-100 pt-6 mb-8">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-bold text-charcoal-900"><?php echo formatPriceWithCurrency($subtotal, $selectedCurrency); ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span id="shipping-display" class="font-bold <?php echo $shippingCost > 0 ? 'text-charcoal-900' : 'text-green-600'; ?>">
                                <?php 
                                if ($selectedMethod === 'pickup') {
                                    echo 'Pickup';
                                } elseif ($shippingCost > 0) {
                                    echo formatPriceWithCurrency($shippingCost, $selectedCurrency);
                                } else {
                                    echo '<span class="text-green-600">Free</span>';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-end pt-4 border-t border-gray-100">
                            <span class="text-lg font-bold text-charcoal-900">Total</span>
                            <span id="total-display" class="text-3xl font-bold text-folly font-display"><?php echo formatPriceWithCurrency($total, $selectedCurrency); ?></span>
                        </div>
                    </div>

                    <button type="submit" id="submit-btn" class="w-full bg-gradient-to-r from-charcoal-900 to-charcoal-800 hover:from-folly hover:to-folly-500 text-white text-center font-semibold py-4 rounded-2xl transition-all duration-300 shadow-lg hover:shadow-folly/25">
                        Complete Order
                    </button>

                    <div class="mt-6 flex items-center justify-center gap-2 text-gray-400 text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Secure Encrypted Checkout
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
function getBasePath() {
    return '<?php echo rtrim(getBaseUrl(), '/'); ?>';
}

function showPaymentForm(paymentMethod) {
    document.querySelectorAll('.payment-form').forEach(el => el.classList.add('hidden'));
    const form = document.getElementById(paymentMethod + '-form');
    if (form) form.classList.remove('hidden');
    
    if (paymentMethod === 'stripe') {
        initializeStripeElements();
    }
}

// Stripe Integration
let stripe, elements, cardElement;

function initializeStripeElements() {
    if (stripe) return;
    
    <?php require_once 'includes/stripe-config.php'; ?>
    stripe = Stripe('<?php echo StripeConfig::getPublishableKey(); ?>');
    elements = stripe.elements();
    
    cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#3B4255',
                fontFamily: 'Poppins, sans-serif',
                '::placeholder': { color: '#9CA3AF' },
            },
        },
    });
    cardElement.mount('#card-element');
    
    cardElement.on('change', ({error}) => {
        const displayError = document.getElementById('card-errors');
        displayError.textContent = error ? error.message : '';
    });
}

async function onShippingMethodChange(method) {
    const form = document.getElementById('checkout-form');
    const formData = new FormData(form);
    formData.append('ajax', '1');
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            // Update shipping cost display in order summary
            const shippingDisplay = document.getElementById('shipping-display');
            if (shippingDisplay) {
                shippingDisplay.innerHTML = data.shipping_html;
                // Update class for color based on shipping type
                if (data.shipping_formatted === 'Free') {
                    shippingDisplay.className = 'font-bold text-green-600';
                } else if (data.shipping_formatted === 'Pickup') {
                    shippingDisplay.className = 'font-bold text-charcoal-900';
                } else {
                    shippingDisplay.className = 'font-bold text-charcoal-900';
                }
            }
            
            // Update total display
            const totalDisplay = document.getElementById('total-display');
            if (totalDisplay) {
                totalDisplay.textContent = data.total_formatted;
            }
            
            // Show/hide shipping address section
            const addressSection = document.getElementById('shipping-address-section');
            if (addressSection) {
                if (data.requires_address) {
                    addressSection.classList.remove('hidden');
                    // Make address fields required
                    ['address', 'city', 'postal_code', 'country'].forEach(id => {
                        const field = document.getElementById(id);
                        if (field) field.required = true;
                    });
                } else {
                    addressSection.classList.add('hidden');
                    // Remove required from address fields
                    ['address', 'city', 'postal_code', 'country'].forEach(id => {
                        const field = document.getElementById(id);
                        if (field) field.required = false;
                    });
                }
            }
            
            // Update step numbers dynamically
            updateStepNumbers();
        }
    } catch (error) {
        console.error('Error updating shipping method:', error);
    }
}

function updateStepNumbers() {
    // Get all checkout steps and renumber visible ones
    const steps = document.querySelectorAll('.checkout-step');
    let stepNum = 1;
    
    steps.forEach(step => {
        const stepSpan = step.querySelector('.step-number');
        if (!stepSpan) return;
        
        // Skip hidden sections
        if (step.classList.contains('hidden')) {
            return;
        }
        
        stepSpan.textContent = stepNum;
        stepNum++;
    });
}

function updatePhonePlaceholder() {
    const select = document.getElementById('countryCode');
    const input = document.getElementById('phone');
    const format = select.options[select.selectedIndex].getAttribute('data-format');
    input.placeholder = format.replace(/X/g, '0');
}

// Form Submission
document.getElementById('checkout-form').addEventListener('submit', async function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
    
    if (paymentMethod === 'stripe') {
        e.preventDefault();
        const submitBtn = document.getElementById('submit-btn');
        const processingMsg = document.getElementById('payment-processing');
        
        submitBtn.disabled = true;
        processingMsg.classList.remove('hidden');
        
        try {
            // Create Payment Intent
            // Note: This requires a backend endpoint to create the intent
            const customerData = {
                email: document.querySelector('input[name="email"]').value,
                name: document.getElementById('cardholder_name').value
            };
            
            const response = await fetch(`${getBasePath()}/api/stripe-payment.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_payment_intent',
                    customer_data: customerData
                })
            });
            
            const data = await response.json();
            
            if (data.error) throw new Error(data.error);
            
            const { error, paymentIntent } = await stripe.confirmCardPayment(data.client_secret, {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: customerData.name,
                        email: customerData.email
                    }
                }
            });
            
            if (error) throw error;
            
            if (paymentIntent.status === 'succeeded') {
                // Confirm payment on backend
                const confirmResponse = await fetch(`${getBasePath()}/api/stripe-payment.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'confirm_payment',
                        payment_intent_id: paymentIntent.id
                    })
                });
                
                const confirmData = await confirmResponse.json();
                if (confirmData.success) {
                    window.location.href = confirmData.redirect_url;
                } else {
                    throw new Error(confirmData.error);
                }
            }
            
        } catch (error) {
            console.error('Payment Error:', error);
            const displayError = document.getElementById('card-errors');
            displayError.textContent = error.message;
            submitBtn.disabled = false;
            processingMsg.classList.add('hidden');
        }
    }
    // For other methods, let the form submit naturally
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    const checkedPayment = document.querySelector('input[name="payment_method"]:checked');
    if (checkedPayment) showPaymentForm(checkedPayment.value);
    updatePhonePlaceholder();
});
</script>

<?php include 'includes/footer.php'; ?>