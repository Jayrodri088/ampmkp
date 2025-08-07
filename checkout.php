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

// Calculate shipping costs based on settings and selected currency
$currencySettings = $settings['shipping']['costs'][$selectedCurrency] ?? [];
$freeShippingThreshold = $currencySettings['free_threshold'] ?? $settings['shipping']['free_shipping_threshold'] ?? 0;
$standardShippingCost = $currencySettings['standard'] ?? $settings['shipping']['standard_shipping_cost'] ?? 0;

// Calculate shipping cost
if ($freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold) {
    $shippingCost = 0; // Free shipping
} else {
    $shippingCost = $standardShippingCost; // Standard shipping cost
}

$total = $subtotal + $shippingCost;

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
    
    // Check if this is a JSON request or form data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJsonRequest = strpos($contentType, 'application/json') !== false;
    
    if ($isJsonRequest) {
        // Handle JSON request from JavaScript
        $jsonInput = file_get_contents('php://input');
        $requestData = json_decode($jsonInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
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
    } else {
        // Handle traditional form submission
        $submittedToken = $_POST['checkout_token'] ?? '';
        if (empty($submittedToken) || !hash_equals($_SESSION['checkout_token'], $submittedToken)) {
            $error = 'Invalid form submission. Please try again.';
        } else {
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
                'selected_currency' => sanitizeInput($_POST['selected_currency'] ?? $selectedCurrency)
            ];
        }
    }
    
    // Continue with validation and processing if we have customer data
    if (isset($customerData) && !isset($error)) {
        
        // Validation
        $required_fields = ['email', 'first_name', 'last_name', 'address', 'city', 'postal_code', 'country', 'payment_method'];
        $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($customerData[$field])) {
            $missing_fields[] = ucwords(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $error = 'Please fill in all required fields to continue.';
    } elseif (!validateEmail($customerData['email'])) {
        $error = 'Please enter a valid email address.';
    } elseif ($customerData['payment_method'] === 'stripe') {
        // Stripe payments should not be processed here
        $error = 'Please use the Stripe checkout button to complete your payment.';
    } else {
        // Create order for non-Stripe payment methods
        $orderId = 'AMP' . date('Y') . sprintf('%06d', time() % 1000000);
        
            $orderData = [
            'id' => $orderId,
            'customer' => $customerData,
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'status' => 'pending',
            'payment_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
                'currency' => $selectedCurrency
        ];
        
        // Save order
        $orders = readJsonFile('orders.json');
        $orders[] = $orderData;
        
        if (writeJsonFile('orders.json', $orders)) {
            // Clear cart
            clearCart();
            
            // Regenerate token after successful submission
            $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
            
            if ($isJsonRequest) {
                // Return JSON response for AJAX requests
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'redirect_url' => getBaseUrl('order-success.php?order=' . $orderId),
                    'order_id' => $orderId
                ]);
                exit;
            } else {
                // Redirect to success page for form submissions
                header('Location: ' . getBaseUrl('order-success.php?order=' . $orderId));
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
<div class="bg-white border-b border-gray-100 py-3 md:py-4 mt-16 md:mt-20">
    <div class="container mx-auto px-4">
        <nav class="text-xs md:text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <a href="<?php echo getBaseUrl('cart.php'); ?>" class="text-folly hover:text-folly-600 hover:underline">Cart</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium">Checkout</span>
        </nav>
    </div>
</div>

<!-- Checkout -->
<section class="bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-8 md:py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-8 md:mb-12">
                <h1 class="text-3xl md:text-5xl lg:text-6xl font-bold text-gray-900 mb-4 md:mb-6">
                    Secure 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                        Checkout
                    </span>
                </h1>
                <div class="w-16 md:w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-4 md:mb-6"></div>
                <p class="text-base md:text-xl text-gray-600 px-4">Complete your order with our secure checkout process</p>
            </div>
            
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Error</h3>
                            <p class="mt-1 text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                <!-- Checkout Form -->
                <div class="lg:col-span-2 order-3 lg:order-1">
                    <form method="POST" class="space-y-8">
                        <input type="hidden" name="checkout_token" value="<?php echo htmlspecialchars($_SESSION['checkout_token']); ?>">
                        
                        <!-- Customer Information -->
                        <div class="bg-white/80 backdrop-blur-sm p-4 md:p-8 rounded-2xl shadow-xl border border-gray-200 mb-6 md:mb-8">
                            <h2 class="text-lg md:text-2xl font-bold text-gray-900 mb-4 md:mb-8"><i class="bi bi-person-fill text-folly mr-2"></i>Customer Information</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                <div>
                                    <label for="first_name" class="block text-xs md:text-sm font-medium text-gray-700 mb-2">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="first_name" 
                                        name="first_name" 
                                        value="<?php echo htmlspecialchars($customerData['first_name'] ?? ''); ?>"
                                        required
                                        class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                    >
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-xs md:text-sm font-medium text-gray-700 mb-2">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="last_name" 
                                        name="last_name" 
                                        value="<?php echo htmlspecialchars($customerData['last_name'] ?? ''); ?>"
                                        required
                                        class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                    >
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-xs md:text-sm font-medium text-gray-700 mb-2">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        value="<?php echo htmlspecialchars($customerData['email'] ?? ''); ?>"
                                        required
                                        class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                    >
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-xs md:text-sm font-medium text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <div class="flex gap-2">
                                        <select id="countryCode" name="countryCode"
                                                class="w-32 px-3 py-2 md:py-3 text-xs md:text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                                onchange="updatePhonePlaceholder()">
                                            <option value="+1" data-country="US" data-format="(XXX) XXX-XXXX">🇺🇸 +1</option>
                                            <option value="+44" data-country="GB" data-format="XXXX XXX XXXX" selected>🇬🇧 +44</option>
                                            <option value="+7" data-country="RU" data-format="XXX XXX-XX-XX">🇷🇺 +7</option>
                                            <option value="+20" data-country="EG" data-format="XX XXXX XXXX">🇪🇬 +20</option>
                                            <option value="+27" data-country="ZA" data-format="XX XXX XXXX">🇿🇦 +27</option>
                                            <option value="+30" data-country="GR" data-format="XXX XXX XXXX">🇬🇷 +30</option>
                                            <option value="+31" data-country="NL" data-format="X XXXX XXXX">🇳🇱 +31</option>
                                            <option value="+32" data-country="BE" data-format="XXX XX XX XX">🇧🇪 +32</option>
                                            <option value="+33" data-country="FR" data-format="X XX XX XX XX">🇫🇷 +33</option>
                                            <option value="+34" data-country="ES" data-format="XXX XXX XXX">🇪🇸 +34</option>
                                            <option value="+36" data-country="HU" data-format="XX XXX XXXX">🇭🇺 +36</option>
                                            <option value="+39" data-country="IT" data-format="XXX XXX XXXX">🇮🇹 +39</option>
                                            <option value="+40" data-country="RO" data-format="XXX XXX XXX">🇷🇴 +40</option>
                                            <option value="+41" data-country="CH" data-format="XX XXX XX XX">🇨🇭 +41</option>
                                            <option value="+43" data-country="AT" data-format="XXX XXXXXXX">🇦🇹 +43</option>
                                            <option value="+45" data-country="DK" data-format="XX XX XX XX">🇩🇰 +45</option>
                                            <option value="+46" data-country="SE" data-format="XX XXX XX XX">🇸🇪 +46</option>
                                            <option value="+47" data-country="NO" data-format="XXX XX XXX">🇳🇴 +47</option>
                                            <option value="+48" data-country="PL" data-format="XXX XXX XXX">🇵🇱 +48</option>
                                            <option value="+49" data-country="DE" data-format="XXX XXXXXXXX">🇩🇪 +49</option>
                                            <option value="+51" data-country="PE" data-format="XXX XXX XXX">🇵🇪 +51</option>
                                            <option value="+52" data-country="MX" data-format="XX XXXX XXXX">🇲🇽 +52</option>
                                            <option value="+53" data-country="CU" data-format="X XXX XXXX">🇨🇺 +53</option>
                                            <option value="+54" data-country="AR" data-format="XX XXXX XXXX">🇦🇷 +54</option>
                                            <option value="+55" data-country="BR" data-format="XX XXXXX XXXX">🇧🇷 +55</option>
                                            <option value="+56" data-country="CL" data-format="X XXXX XXXX">🇨🇱 +56</option>
                                            <option value="+57" data-country="CO" data-format="XXX XXX XXXX">🇨🇴 +57</option>
                                            <option value="+58" data-country="VE" data-format="XXX XXX XXXX">🇻🇪 +58</option>
                                            <option value="+60" data-country="MY" data-format="XX XXXX XXXX">🇲🇾 +60</option>
                                            <option value="+61" data-country="AU" data-format="XXX XXX XXX">🇦🇺 +61</option>
                                            <option value="+62" data-country="ID" data-format="XXX XXXX XXXX">🇮🇩 +62</option>
                                            <option value="+63" data-country="PH" data-format="XXX XXX XXXX">🇵🇭 +63</option>
                                            <option value="+64" data-country="NZ" data-format="XX XXX XXXX">🇳🇿 +64</option>
                                            <option value="+65" data-country="SG" data-format="XXXX XXXX">🇸🇬 +65</option>
                                            <option value="+66" data-country="TH" data-format="XX XXX XXXX">🇹🇭 +66</option>
                                            <option value="+81" data-country="JP" data-format="XX XXXX XXXX">🇯🇵 +81</option>
                                            <option value="+82" data-country="KR" data-format="XX XXXX XXXX">🇰🇷 +82</option>
                                            <option value="+84" data-country="VN" data-format="XX XXXX XXXX">🇻🇳 +84</option>
                                            <option value="+86" data-country="CN" data-format="XXX XXXX XXXX">🇨🇳 +86</option>
                                            <option value="+90" data-country="TR" data-format="XXX XXX XX XX">🇹🇷 +90</option>
                                            <option value="+91" data-country="IN" data-format="XXXXX XXXXX">🇮🇳 +91</option>
                                            <option value="+92" data-country="PK" data-format="XXX XXX XXXX">🇵🇰 +92</option>
                                            <option value="+93" data-country="AF" data-format="XX XXX XXXX">🇦🇫 +93</option>
                                            <option value="+94" data-country="LK" data-format="XX XXX XXXX">🇱🇰 +94</option>
                                            <option value="+95" data-country="MM" data-format="XX XXX XXXX">🇲🇲 +95</option>
                                            <option value="+98" data-country="IR" data-format="XXX XXX XXXX">🇮🇷 +98</option>
                                            <option value="+212" data-country="MA" data-format="XXX XXX XXX">🇲🇦 +212</option>
                                            <option value="+213" data-country="DZ" data-format="XXX XXX XXX">🇩🇿 +213</option>
                                            <option value="+216" data-country="TN" data-format="XX XXX XXX">🇹🇳 +216</option>
                                            <option value="+218" data-country="LY" data-format="XX XXX XXXX">🇱🇾 +218</option>
                                            <option value="+220" data-country="GM" data-format="XXX XXXX">🇬🇲 +220</option>
                                            <option value="+221" data-country="SN" data-format="XX XXX XX XX">🇸🇳 +221</option>
                                            <option value="+222" data-country="MR" data-format="XX XX XX XX">🇲🇷 +222</option>
                                            <option value="+223" data-country="ML" data-format="XX XX XX XX">🇲🇱 +223</option>
                                            <option value="+224" data-country="GN" data-format="XXX XXX XXX">🇬🇳 +224</option>
                                            <option value="+225" data-country="CI" data-format="XX XX XX XX XX">🇨🇮 +225</option>
                                            <option value="+226" data-country="BF" data-format="XX XX XX XX">🇧🇫 +226</option>
                                            <option value="+227" data-country="NE" data-format="XX XX XX XX">🇳🇪 +227</option>
                                            <option value="+228" data-country="TG" data-format="XX XX XX XX">🇹🇬 +228</option>
                                            <option value="+229" data-country="BJ" data-format="XX XX XX XX">🇧🇯 +229</option>
                                            <option value="+230" data-country="MU" data-format="XXXX XXXX">🇲🇺 +230</option>
                                            <option value="+231" data-country="LR" data-format="XX XXX XXXX">🇱🇷 +231</option>
                                            <option value="+232" data-country="SL" data-format="XX XXXXXX">🇸🇱 +232</option>
                                            <option value="+233" data-country="GH" data-format="XX XXX XXXX">🇬🇭 +233</option>
                                            <option value="+234" data-country="NG" data-format="XXX XXX XXXX">🇳🇬 +234</option>
                                            <option value="+235" data-country="TD" data-format="XX XX XX XX">🇹🇩 +235</option>
                                            <option value="+236" data-country="CF" data-format="XX XX XX XX">🇨🇫 +236</option>
                                            <option value="+237" data-country="CM" data-format="X XX XX XX XX">🇨🇲 +237</option>
                                            <option value="+238" data-country="CV" data-format="XXX XX XX">🇨🇻 +238</option>
                                            <option value="+239" data-country="ST" data-format="XXX XXXX">🇸🇹 +239</option>
                                            <option value="+240" data-country="GQ" data-format="XXX XXX XXX">🇬🇶 +240</option>
                                            <option value="+241" data-country="GA" data-format="X XX XX XX">🇬🇦 +241</option>
                                            <option value="+242" data-country="CG" data-format="XX XXX XXXX">🇨🇬 +242</option>
                                            <option value="+243" data-country="CD" data-format="XXX XXX XXX">🇨🇩 +243</option>
                                            <option value="+244" data-country="AO" data-format="XXX XXX XXX">🇦🇴 +244</option>
                                            <option value="+245" data-country="GW" data-format="XXX XXXX">🇬🇼 +245</option>
                                            <option value="+248" data-country="SC" data-format="X XX XX XX">🇸🇨 +248</option>
                                            <option value="+249" data-country="SD" data-format="XX XXX XXXX">🇸🇩 +249</option>
                                            <option value="+250" data-country="RW" data-format="XXX XXX XXX">🇷🇼 +250</option>
                                            <option value="+251" data-country="ET" data-format="XX XXX XXXX">🇪🇹 +251</option>
                                            <option value="+252" data-country="SO" data-format="XX XXX XXXX">🇸🇴 +252</option>
                                            <option value="+253" data-country="DJ" data-format="XX XX XX XX">🇩🇯 +253</option>
                                            <option value="+254" data-country="KE" data-format="XXX XXXXXX">🇰🇪 +254</option>
                                            <option value="+255" data-country="TZ" data-format="XX XXX XXXX">🇹🇿 +255</option>
                                            <option value="+256" data-country="UG" data-format="XXX XXXXXX">🇺🇬 +256</option>
                                            <option value="+257" data-country="BI" data-format="XX XX XX XX">🇧🇮 +257</option>
                                            <option value="+258" data-country="MZ" data-format="XX XXX XXXX">🇲🇿 +258</option>
                                            <option value="+260" data-country="ZM" data-format="XX XXX XXXX">🇿🇲 +260</option>
                                            <option value="+261" data-country="MG" data-format="XX XX XXX XX">🇲🇬 +261</option>
                                            <option value="+262" data-country="RE" data-format="XXX XX XX XX">🇷🇪 +262</option>
                                            <option value="+263" data-country="ZW" data-format="XX XXX XXXX">🇿🇼 +263</option>
                                            <option value="+264" data-country="NA" data-format="XX XXX XXXX">🇳🇦 +264</option>
                                            <option value="+265" data-country="MW" data-format="XXX XX XX XX">🇲🇼 +265</option>
                                            <option value="+266" data-country="LS" data-format="XX XXX XXXX">🇱🇸 +266</option>
                                            <option value="+267" data-country="BW" data-format="XX XXX XXXX">🇧🇼 +267</option>
                                            <option value="+268" data-country="SZ" data-format="XX XX XXXX">🇸🇿 +268</option>
                                            <option value="+269" data-country="KM" data-format="XXX XX XX">🇰🇲 +269</option>
                                            <option value="+350" data-country="GI" data-format="XXXX XXXX">🇬🇮 +350</option>
                                            <option value="+351" data-country="PT" data-format="XXX XXX XXX">🇵🇹 +351</option>
                                            <option value="+352" data-country="LU" data-format="XXX XXX XXX">🇱🇺 +352</option>
                                            <option value="+353" data-country="IE" data-format="XX XXX XXXX">🇮🇪 +353</option>
                                            <option value="+354" data-country="IS" data-format="XXX XXXX">🇮🇸 +354</option>
                                            <option value="+355" data-country="AL" data-format="XX XXX XXXX">🇦🇱 +355</option>
                                            <option value="+356" data-country="MT" data-format="XXXX XXXX">🇲🇹 +356</option>
                                            <option value="+357" data-country="CY" data-format="XX XXX XXX">🇨🇾 +357</option>
                                            <option value="+358" data-country="FI" data-format="XX XXX XXXX">🇫🇮 +358</option>
                                            <option value="+359" data-country="BG" data-format="XXX XXX XXX">🇧🇬 +359</option>
                                            <option value="+370" data-country="LT" data-format="XXX XXXXX">🇱🇹 +370</option>
                                            <option value="+371" data-country="LV" data-format="XX XXX XXX">🇱🇻 +371</option>
                                            <option value="+372" data-country="EE" data-format="XXXX XXXX">🇪🇪 +372</option>
                                            <option value="+373" data-country="MD" data-format="XX XXX XXX">🇲🇩 +373</option>
                                            <option value="+374" data-country="AM" data-format="XX XXX XXX">🇦🇲 +374</option>
                                            <option value="+375" data-country="BY" data-format="XX XXX XX XX">🇧🇾 +375</option>
                                            <option value="+376" data-country="AD" data-format="XXX XXX">🇦🇩 +376</option>
                                            <option value="+377" data-country="MC" data-format="XX XX XX XX XX">🇲🇨 +377</option>
                                            <option value="+378" data-country="SM" data-format="XXXX XXXXXX">🇸🇲 +378</option>
                                            <option value="+380" data-country="UA" data-format="XX XXX XX XX">🇺🇦 +380</option>
                                            <option value="+381" data-country="RS" data-format="XX XXX XXXX">🇷🇸 +381</option>
                                            <option value="+382" data-country="ME" data-format="XX XXX XXX">🇲🇪 +382</option>
                                            <option value="+385" data-country="HR" data-format="XX XXX XXXX">🇭🇷 +385</option>
                                            <option value="+386" data-country="SI" data-format="XX XXX XXX">🇸🇮 +386</option>
                                            <option value="+387" data-country="BA" data-format="XX XXX XXX">🇧🇦 +387</option>
                                            <option value="+389" data-country="MK" data-format="XX XXX XXX">🇲🇰 +389</option>
                                            <option value="+420" data-country="CZ" data-format="XXX XXX XXX">🇨🇿 +420</option>
                                            <option value="+421" data-country="SK" data-format="XXX XXX XXX">🇸🇰 +421</option>
                                            <option value="+423" data-country="LI" data-format="XXX XX XX">🇱🇮 +423</option>
                                        </select>
                                        <input 
                                            type="tel" 
                                            id="phone" 
                                            name="phone" 
                                            placeholder="Enter phone number"
                                            value="<?php echo htmlspecialchars($customerData['phone'] ?? ''); ?>"
                                            class="flex-1 px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Shipping Address -->
                        <div class="bg-white/80 backdrop-blur-sm p-4 md:p-8 rounded-2xl shadow-xl border border-gray-200 mb-6 md:mb-8">
                            <h2 class="text-lg md:text-2xl font-bold text-gray-900 mb-4 md:mb-8"><i class="bi bi-truck text-folly mr-2"></i>Shipping Address</h2>
                            
                            <div class="space-y-4 md:space-y-6">
                                <div>
                                    <label for="address" class="block text-xs md:text-sm font-medium text-gray-700 mb-2">
                                        Street Address <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="address" 
                                        name="address" 
                                        value="<?php echo htmlspecialchars($customerData['address'] ?? ''); ?>"
                                        required
                                        class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                        placeholder="123 Main Street, Apt 4B"
                                    >
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="city" class="block text-xs md:text-sm font-medium text-gray-700 mb-2">
                                            City <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="city" 
                                            name="city" 
                                            value="<?php echo htmlspecialchars($customerData['city'] ?? ''); ?>"
                                            required
                                            class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                        >
                                    </div>
                                    
                                    <div>
                                        <label for="postal_code" class="block text-xs md:text-sm font-medium text-gray-700 mb-2">
                                            Postal Code <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="postal_code" 
                                            name="postal_code" 
                                            value="<?php echo htmlspecialchars($customerData['postal_code'] ?? ''); ?>"
                                            required
                                            class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                        >
                                    </div>
                                    
                                    <div>
                                        <label for="country" class="block text-xs md:text-sm font-medium text-gray-700 mb-2">
                                            Country <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="country" 
                                            name="country" 
                                            required
                                            class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                        >
                                            <option value="">Select Country</option>
                                            <?php
                                            // Enhanced countries array with flags (similar to vendors dropdown)
                                            $countries = [
                                                ["name" => "United States", "code" => "US", "flag" => "🇺🇸"],
                                                ["name" => "United Kingdom", "code" => "GB", "flag" => "🇬🇧"],
                                                ["name" => "Canada", "code" => "CA", "flag" => "🇨🇦"],
                                                ["name" => "Australia", "code" => "AU", "flag" => "🇦🇺"],
                                                ["name" => "Germany", "code" => "DE", "flag" => "🇩🇪"],
                                                ["name" => "France", "code" => "FR", "flag" => "🇫🇷"],
                                                ["name" => "Netherlands", "code" => "NL", "flag" => "🇳🇱"],
                                                ["name" => "Spain", "code" => "ES", "flag" => "🇪🇸"],
                                                ["name" => "Italy", "code" => "IT", "flag" => "🇮🇹"],
                                                ["name" => "Japan", "code" => "JP", "flag" => "🇯🇵"],
                                                ["name" => "South Korea", "code" => "KR", "flag" => "🇰🇷"],
                                                ["name" => "China", "code" => "CN", "flag" => "🇨🇳"],
                                                ["name" => "India", "code" => "IN", "flag" => "🇮🇳"],
                                                ["name" => "Brazil", "code" => "BR", "flag" => "🇧🇷"],
                                                ["name" => "Mexico", "code" => "MX", "flag" => "🇲🇽"],
                                                ["name" => "Argentina", "code" => "AR", "flag" => "🇦🇷"],
                                                ["name" => "Chile", "code" => "CL", "flag" => "🇨🇱"],
                                                ["name" => "Colombia", "code" => "CO", "flag" => "🇨🇴"],
                                                ["name" => "Peru", "code" => "PE", "flag" => "🇵🇪"],
                                                ["name" => "Venezuela", "code" => "VE", "flag" => "🇻🇪"],
                                                ["name" => "Nigeria", "code" => "NG", "flag" => "🇳🇬"],
                                                ["name" => "South Africa", "code" => "ZA", "flag" => "🇿🇦"],
                                                ["name" => "Egypt", "code" => "EG", "flag" => "🇪🇬"],
                                                ["name" => "Morocco", "code" => "MA", "flag" => "🇲🇦"],
                                                ["name" => "Kenya", "code" => "KE", "flag" => "🇰🇪"],
                                                ["name" => "Ghana", "code" => "GH", "flag" => "🇬🇭"],
                                                ["name" => "Ethiopia", "code" => "ET", "flag" => "🇪🇹"],
                                                ["name" => "Tanzania", "code" => "TZ", "flag" => "🇹🇿"],
                                                ["name" => "Uganda", "code" => "UG", "flag" => "🇺🇬"],
                                                ["name" => "Russia", "code" => "RU", "flag" => "🇷🇺"],
                                                ["name" => "Poland", "code" => "PL", "flag" => "🇵🇱"],
                                                ["name" => "Czech Republic", "code" => "CZ", "flag" => "🇨🇿"],
                                                ["name" => "Hungary", "code" => "HU", "flag" => "🇭🇺"],
                                                ["name" => "Romania", "code" => "RO", "flag" => "🇷🇴"],
                                                ["name" => "Bulgaria", "code" => "BG", "flag" => "🇧🇬"],
                                                ["name" => "Croatia", "code" => "HR", "flag" => "🇭🇷"],
                                                ["name" => "Serbia", "code" => "RS", "flag" => "🇷🇸"],
                                                ["name" => "Ukraine", "code" => "UA", "flag" => "🇺🇦"],
                                                ["name" => "Turkey", "code" => "TR", "flag" => "🇹🇷"],
                                                ["name" => "Greece", "code" => "GR", "flag" => "🇬🇷"],
                                                ["name" => "Portugal", "code" => "PT", "flag" => "🇵🇹"],
                                                ["name" => "Belgium", "code" => "BE", "flag" => "🇧🇪"],
                                                ["name" => "Switzerland", "code" => "CH", "flag" => "🇨🇭"],
                                                ["name" => "Austria", "code" => "AT", "flag" => "🇦🇹"],
                                                ["name" => "Sweden", "code" => "SE", "flag" => "🇸🇪"],
                                                ["name" => "Norway", "code" => "NO", "flag" => "🇳🇴"],
                                                ["name" => "Denmark", "code" => "DK", "flag" => "🇩🇰"],
                                                ["name" => "Finland", "code" => "FI", "flag" => "🇫🇮"],
                                                ["name" => "Iceland", "code" => "IS", "flag" => "🇮🇸"],
                                                ["name" => "Ireland", "code" => "IE", "flag" => "🇮🇪"],
                                                ["name" => "Luxembourg", "code" => "LU", "flag" => "🇱🇺"],
                                                ["name" => "Malta", "code" => "MT", "flag" => "🇲🇹"],
                                                ["name" => "Cyprus", "code" => "CY", "flag" => "🇨🇾"],
                                                ["name" => "Estonia", "code" => "EE", "flag" => "🇪🇪"],
                                                ["name" => "Latvia", "code" => "LV", "flag" => "🇱🇻"],
                                                ["name" => "Lithuania", "code" => "LT", "flag" => "🇱🇹"],
                                                ["name" => "Slovakia", "code" => "SK", "flag" => "🇸🇰"],
                                                ["name" => "Slovenia", "code" => "SI", "flag" => "🇸🇮"],
                                                ["name" => "Thailand", "code" => "TH", "flag" => "🇹🇭"],
                                                ["name" => "Vietnam", "code" => "VN", "flag" => "🇻🇳"],
                                                ["name" => "Singapore", "code" => "SG", "flag" => "🇸🇬"],
                                                ["name" => "Malaysia", "code" => "MY", "flag" => "🇲🇾"],
                                                ["name" => "Indonesia", "code" => "ID", "flag" => "🇮🇩"],
                                                ["name" => "Philippines", "code" => "PH", "flag" => "🇵🇭"],
                                                ["name" => "Taiwan", "code" => "TW", "flag" => "🇹🇼"],
                                                ["name" => "Hong Kong", "code" => "HK", "flag" => "🇭🇰"],
                                                ["name" => "New Zealand", "code" => "NZ", "flag" => "🇳🇿"],
                                                ["name" => "Israel", "code" => "IL", "flag" => "🇮🇱"],
                                                ["name" => "Saudi Arabia", "code" => "SA", "flag" => "🇸🇦"],
                                                ["name" => "United Arab Emirates", "code" => "AE", "flag" => "🇦🇪"],
                                                ["name" => "Kuwait", "code" => "KW", "flag" => "🇰🇼"],
                                                ["name" => "Qatar", "code" => "QA", "flag" => "🇶🇦"],
                                                ["name" => "Bahrain", "code" => "BH", "flag" => "🇧🇭"],
                                                ["name" => "Oman", "code" => "OM", "flag" => "🇴🇲"],
                                                ["name" => "Jordan", "code" => "JO", "flag" => "🇯🇴"],
                                                ["name" => "Lebanon", "code" => "LB", "flag" => "🇱🇧"],
                                                ["name" => "Iran", "code" => "IR", "flag" => "🇮🇷"],
                                                ["name" => "Iraq", "code" => "IQ", "flag" => "🇮🇶"],
                                                ["name" => "Afghanistan", "code" => "AF", "flag" => "🇦🇫"],
                                                ["name" => "Pakistan", "code" => "PK", "flag" => "🇵🇰"],
                                                ["name" => "Bangladesh", "code" => "BD", "flag" => "🇧🇩"],
                                                ["name" => "Sri Lanka", "code" => "LK", "flag" => "🇱🇰"],
                                                ["name" => "Myanmar", "code" => "MM", "flag" => "🇲🇲"],
                                                ["name" => "Nepal", "code" => "NP", "flag" => "🇳🇵"],
                                                ["name" => "Bhutan", "code" => "BT", "flag" => "🇧🇹"],
                                                ["name" => "Maldives", "code" => "MV", "flag" => "🇲🇻"],
                                                ["name" => "Kazakhstan", "code" => "KZ", "flag" => "🇰🇿"],
                                                ["name" => "Uzbekistan", "code" => "UZ", "flag" => "🇺🇿"],
                                                ["name" => "Kyrgyzstan", "code" => "KG", "flag" => "🇰🇬"],
                                                ["name" => "Tajikistan", "code" => "TJ", "flag" => "🇹🇯"],
                                                ["name" => "Turkmenistan", "code" => "TM", "flag" => "🇹🇲"],
                                                ["name" => "Mongolia", "code" => "MN", "flag" => "🇲🇳"],
                                                ["name" => "North Korea", "code" => "KP", "flag" => "🇰🇵"],
                                                ["name" => "Algeria", "code" => "DZ", "flag" => "🇩🇿"],
                                                ["name" => "Tunisia", "code" => "TN", "flag" => "🇹🇳"],
                                                ["name" => "Libya", "code" => "LY", "flag" => "🇱🇾"],
                                                ["name" => "Sudan", "code" => "SD", "flag" => "🇸🇩"],
                                                ["name" => "Chad", "code" => "TD", "flag" => "🇹🇩"],
                                                ["name" => "Niger", "code" => "NE", "flag" => "🇳🇪"],
                                                ["name" => "Mali", "code" => "ML", "flag" => "🇲🇱"],
                                                ["name" => "Burkina Faso", "code" => "BF", "flag" => "🇧🇫"],
                                                ["name" => "Senegal", "code" => "SN", "flag" => "🇸🇳"],
                                                ["name" => "Gambia", "code" => "GM", "flag" => "🇬🇲"],
                                                ["name" => "Guinea", "code" => "GN", "flag" => "🇬🇳"],
                                                ["name" => "Sierra Leone", "code" => "SL", "flag" => "🇸🇱"],
                                                ["name" => "Liberia", "code" => "LR", "flag" => "🇱🇷"],
                                                ["name" => "Ivory Coast", "code" => "CI", "flag" => "🇨🇮"],
                                                ["name" => "Togo", "code" => "TG", "flag" => "🇹🇬"],
                                                ["name" => "Benin", "code" => "BJ", "flag" => "🇧🇯"],
                                                ["name" => "Cameroon", "code" => "CM", "flag" => "🇨🇲"],
                                                ["name" => "Central African Republic", "code" => "CF", "flag" => "🇨🇫"],
                                                ["name" => "Equatorial Guinea", "code" => "GQ", "flag" => "🇬🇶"],
                                                ["name" => "Gabon", "code" => "GA", "flag" => "🇬🇦"],
                                                ["name" => "Congo", "code" => "CG", "flag" => "🇨🇬"],
                                                ["name" => "Democratic Republic of Congo", "code" => "CD", "flag" => "🇨🇩"],
                                                ["name" => "Angola", "code" => "AO", "flag" => "🇦🇴"],
                                                ["name" => "Zambia", "code" => "ZM", "flag" => "🇿🇲"],
                                                ["name" => "Zimbabwe", "code" => "ZW", "flag" => "🇿🇼"],
                                                ["name" => "Botswana", "code" => "BW", "flag" => "🇧🇼"],
                                                ["name" => "Namibia", "code" => "NA", "flag" => "🇳🇦"],
                                                ["name" => "Lesotho", "code" => "LS", "flag" => "🇱🇸"],
                                                ["name" => "Eswatini", "code" => "SZ", "flag" => "🇸🇿"],
                                                ["name" => "Mozambique", "code" => "MZ", "flag" => "🇲🇿"],
                                                ["name" => "Madagascar", "code" => "MG", "flag" => "🇲🇬"],
                                                ["name" => "Mauritius", "code" => "MU", "flag" => "🇲🇺"],
                                                ["name" => "Seychelles", "code" => "SC", "flag" => "🇸🇨"],
                                                ["name" => "Comoros", "code" => "KM", "flag" => "🇰🇲"],
                                                ["name" => "Malawi", "code" => "MW", "flag" => "🇲🇼"],
                                                ["name" => "Rwanda", "code" => "RW", "flag" => "🇷🇼"],
                                                ["name" => "Burundi", "code" => "BI", "flag" => "🇧🇮"],
                                                ["name" => "Djibouti", "code" => "DJ", "flag" => "🇩🇯"],
                                                ["name" => "Somalia", "code" => "SO", "flag" => "🇸🇴"],
                                                ["name" => "Eritrea", "code" => "ER", "flag" => "🇪🇷"],
                                                ["name" => "South Sudan", "code" => "SS", "flag" => "🇸🇸"],
                                                ["name" => "Uruguay", "code" => "UY", "flag" => "🇺🇾"],
                                                ["name" => "Paraguay", "code" => "PY", "flag" => "🇵🇾"],
                                                ["name" => "Bolivia", "code" => "BO", "flag" => "🇧🇴"],
                                                ["name" => "Ecuador", "code" => "EC", "flag" => "🇪🇨"],
                                                ["name" => "Guyana", "code" => "GY", "flag" => "🇬🇾"],
                                                ["name" => "Suriname", "code" => "SR", "flag" => "🇸🇷"],
                                                ["name" => "Other", "code" => "OTHER", "flag" => "🌍"]
                                            ];
                                            
                                            // Sort countries alphabetically by name (except 'Other' which stays at the end)
                                            usort($countries, function($a, $b) {
                                                if ($a['code'] === 'OTHER') return 1;
                                                if ($b['code'] === 'OTHER') return -1;
                                                return strcmp($a['name'], $b['name']);
                                            });
                                            
                                            foreach ($countries as $country) {
                                                $selected = ($customerData['country'] ?? '') === $country['code'] ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($country['code']) . '" ' . $selected . '>' . 
                                                     htmlspecialchars($country['flag'] . ' ' . $country['name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        


                        <!-- Payment Method -->
                        <div class="bg-white/80 backdrop-blur-sm p-4 md:p-8 rounded-2xl shadow-xl border border-gray-200 mb-6 md:mb-8">
                            <h2 class="text-lg md:text-2xl font-bold text-gray-900 mb-4 md:mb-8"><i class="bi bi-credit-card text-folly mr-2"></i>Payment Method</h2>
                            <input type="hidden" name="selected_currency" value="<?php echo htmlspecialchars($selectedCurrency); ?>">
                            
                            <div class="space-y-3 md:space-y-4">
                                <?php if (in_array('stripe', $availablePaymentMethods)): ?>
                                <label class="relative flex items-center p-3 md:p-4 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-folly-300 touch-manipulation">
                                    <input 
                                        type="radio" 
                                        name="payment_method" 
                                        value="stripe" 
                                        class="sr-only payment-radio"
                                        <?php echo ($customerData['payment_method'] ?? '') === 'stripe' ? 'checked' : ''; ?>
                                        onchange="showPaymentForm('stripe')"
                                    >
                                    <div class="payment-option w-full">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-folly mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                            <div>
                                                <h3 class="font-semibold text-gray-900 text-sm md:text-base">Credit/Debit Card</h3>
                                                <p class="text-xs md:text-sm text-gray-600">Pay securely with Stripe (<?php echo strtoupper($selectedCurrency); ?>)</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                <?php endif; ?>
                                
                                <?php if (in_array('paypal', $availablePaymentMethods)): ?>
                                <label class="relative flex items-center p-3 md:p-4 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-folly-300 touch-manipulation">
                                    <input 
                                        type="radio" 
                                        name="payment_method" 
                                        value="paypal" 
                                        class="sr-only payment-radio"
                                        <?php echo ($customerData['payment_method'] ?? '') === 'paypal' ? 'checked' : ''; ?>
                                        onchange="showPaymentForm('paypal')"
                                    >
                                    <div class="payment-option w-full">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-folly mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            <div>
                                                <h3 class="font-semibold text-gray-900 text-sm md:text-base">PayPal</h3>
                                                <p class="text-xs md:text-sm text-gray-600">Pay with PayPal (<?php echo strtoupper($selectedCurrency); ?>)</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                <?php endif; ?>
                                
                                <?php if (in_array('bank_transfer', $availablePaymentMethods)): ?>
                                <label class="relative flex items-center p-3 md:p-4 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-folly-300 touch-manipulation">
                                    <input 
                                        type="radio" 
                                        name="payment_method" 
                                        value="bank_transfer" 
                                        class="sr-only payment-radio"
                                        <?php echo ($customerData['payment_method'] ?? '') === 'bank_transfer' ? 'checked' : ''; ?>
                                        onchange="showPaymentForm('bank_transfer')"
                                    >
                                    <div class="payment-option w-full">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-folly mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4m0 0v-5a2 2 0 012-2h2a2 2 0 012 2v5"></path>
                                            </svg>
                                            <div>
                                                <h3 class="font-semibold text-gray-900 text-sm md:text-base">Bank Transfer</h3>
                                                <p class="text-xs md:text-sm text-gray-600"><?php echo strtoupper($selectedCurrency) === 'NGN' ? 'Nigerian Bank Transfer' : 'International Bank Transfer'; ?> (<?php echo strtoupper($selectedCurrency); ?>)</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                <?php endif; ?>
                                
                                <?php if (in_array('espees', $availablePaymentMethods)): ?>
                                <label class="relative flex items-center p-3 md:p-4 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-folly-300 touch-manipulation">
                                    <input 
                                        type="radio" 
                                        name="payment_method" 
                                        value="espees" 
                                        class="sr-only payment-radio"
                                        <?php echo ($customerData['payment_method'] ?? '') === 'espees' ? 'checked' : ''; ?>
                                        onchange="showPaymentForm('espees')"
                                    >
                                    <div class="payment-option w-full">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-folly mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                            <div>
                                                <h3 class="font-semibold text-gray-900 text-sm md:text-base">Espees Payment</h3>
                                                <p class="text-xs md:text-sm text-gray-600">Pay with Espees currency</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Payment Forms -->
                            <div class="mt-6">
                                <!-- Credit Card Form with Stripe Elements -->
                                <div id="stripe-form" class="payment-form hidden">
                                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Card Details</h3>
                                        
                                        <!-- Stripe Elements will be mounted here -->
                                        <div class="grid grid-cols-1 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Card Information <span class="text-red-500">*</span>
                                                </label>
                                                <div id="card-element" class="p-4 border border-gray-300 rounded-xl bg-white">
                                                    <!-- Stripe Elements will create form elements here -->
                                                </div>
                                                <div id="card-errors" class="text-red-600 text-sm mt-2" role="alert"></div>
                                            </div>
                                            
                                            <div>
                                                <label for="cardholder_name" class="block text-sm font-medium text-gray-700 mb-2">
                                                    Cardholder Name <span class="text-red-500">*</span>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    id="cardholder_name" 
                                                    name="cardholder_name" 
                                                    placeholder="Name as it appears on card"
                                                    required
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200"
                                                >
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 p-3 bg-folly-50 rounded-lg border border-folly-200">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-folly mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                </svg>
                                                <span class="text-sm text-folly-700">
                                                    <strong class="text-folly-800">Powered by Stripe</strong> - Your payment information is secure and encrypted
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Payment processing status -->
                                        <div id="payment-processing" class="hidden mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-blue-500 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                <span class="text-blue-700">Processing your payment...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- PayPal Form -->
                                <div id="paypal-form" class="payment-form hidden">
                                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">PayPal Payment</h3>
                                        <div class="text-center py-8">
                                            <!-- PayPal Direct Link -->
                                            <div class="bg-blue-50 p-4 rounded-lg">
                                                <h4 class="font-semibold text-gray-900 mb-3 flex items-center justify-center">
                                                    <img src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-200px.png" alt="PayPal" class="h-6 w-auto">
                                                    PayPal Direct Link
                                                </h4>
                                                <div class="space-y-2 text-sm">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">PayPal:</span>
                                                        <a href="http://paypal.me/amp202247" target="_blank" class="font-medium text-blue-600 hover:text-blue-800 underline">
                                                            paypal.me/amp202247
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                

                                <!-- Bank Transfer Form -->
                                <div id="bank_transfer-form" class="payment-form hidden">
                                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Bank Transfer Details</h3>
                                        <div class="space-y-4">
                                            
                                            <?php if (strtoupper($selectedCurrency) === 'NGN' || strtoupper($selectedCurrency) === 'NAIRA'): ?>
                                                <!-- Nigerian Account -->
                                                <div class="bg-gray-50 p-4 rounded-lg">
                                                                                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                        <i class="bi bi-flag text-green-600 mr-2"></i> Nigerian Account
                                    </h4>
                                                    <div class="space-y-2 text-sm">
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Bank:</span>
                                                            <span class="font-medium">Parallex Bank</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Account Number:</span>
                                                            <span class="font-medium font-mono">100004476</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Account Name:</span>
                                                            <span class="font-medium">ANGELMP</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- UK Account for International Currencies -->
                                                <div class="bg-gray-50 p-4 rounded-lg">
                                                                                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                        <i class="bi bi-flag text-blue-600 mr-2"></i> UK Account
                                    </h4>
                                                    <div class="space-y-2 text-sm">
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Sort Code:</span>
                                                            <span class="font-medium font-mono">04-00-04</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Account Number:</span>
                                                            <span class="font-medium font-mono">64689014</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Account Name:</span>
                                                            <span class="font-medium">ANGELMP</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                                                <div class="flex">
                                                    <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.664-.833-2.464 0L5.232 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                    <div>
                                                        <h4 class="text-sm font-medium text-yellow-800">Important:</h4>
                                                        <div class="mt-1 text-sm text-yellow-700">
                                                            <ul class="list-disc list-inside space-y-1">
                                                                <li>Please include your order number <strong><?php echo 'AMP' . date('Y') . sprintf('%06d', time() % 1000000); ?></strong> in the transfer reference</li>
                                                                <li>Your order will be processed after payment confirmation (1-3 business days)</li>
                                                                <li>Keep your transfer receipt for your records</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Espees Payment Form -->
                                <div id="espees-form" class="payment-form hidden">
                                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <svg class="w-6 h-6 text-folly mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                            Espees Payment
                                        </h3>
                                        
                                        <div class="text-center py-6">
                                            <div class="bg-gradient-to-r from-folly-100 to-purple-100 p-6 rounded-lg mb-6">
                                                <div class="text-center">
                                                    <div class="flex items-center justify-center mb-4">
                                                        <img src="<?php echo getAssetUrl('images/general/logo.png'); ?>" alt="Espees Logo" class="w-12 h-12 mr-3" onerror="this.style.display='none'">
                                                        <h4 class="text-xl font-bold text-folly-800">ESPEES PAYMENT</h4>
                                                    </div>
                                                    
                                                    <div class="bg-white p-4 rounded-lg border-2 border-folly-300 mb-4">
                                                        <h5 class="text-lg font-bold text-folly-800 mb-2">ESPEES PAYABLE TO:</h5>
                                                        <div class="text-2xl font-mono font-bold text-folly-900">
                                                            ANGELMP
                                                        </div>
                                                    </div>
                                                    
                                                    <p class="text-sm text-folly-700">
                                                        Send <strong><?php echo formatPriceWithCurrency($total, $selectedCurrency); ?></strong> to ANGELMP using your Espees wallet
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                                                <div class="flex">
                                                    <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <div>
                                                        <h4 class="text-sm font-medium text-blue-800">Payment Instructions:</h4>
                                                        <div class="mt-1 text-sm text-blue-700">
                                                            <ol class="list-decimal list-inside space-y-1">
                                                                <li>Open your Espees wallet application</li>
                                                                <li>Send payment to: <strong>ANGELMP</strong></li>
                                                                <li>Amount: <strong><?php echo formatPriceWithCurrency($total, $selectedCurrency); ?></strong></li>
                                                                <li>Include your order reference in the transaction notes</li>
                                                                <li>Click "Complete Order" below after sending payment</li>
                                                            </ol>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Special Instructions -->
                        <div class="bg-white/80 backdrop-blur-sm p-4 md:p-6 rounded-2xl shadow-xl border border-gray-200">
                            <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-4 md:mb-6"><i class="bi bi-pencil-square text-folly mr-2"></i>Special Instructions</h2>
                            <textarea 
                                name="special_instructions" 
                                rows="3" 
                                class="w-full px-3 md:px-4 py-2 md:py-3 text-sm md:text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 touch-manipulation"
                                placeholder="Any special delivery instructions or notes for your order..."
                            ><?php echo htmlspecialchars($customerData['special_instructions'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex flex-col sm:flex-row gap-3 md:gap-4 mt-6 md:mt-8">
                            <a 
                                href="<?php echo getBaseUrl('cart.php'); ?>" 
                                class="flex items-center justify-center px-4 md:px-6 py-3 md:py-4 bg-white border-2 border-gray-300 hover:border-folly hover:bg-folly-50 text-gray-700 hover:text-folly-700 rounded-xl font-semibold text-sm md:text-lg transition-all duration-200 transform hover:scale-105 min-w-0 sm:w-auto touch-manipulation"
                            >
                                <svg class="w-4 md:w-5 h-4 md:h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                <span class="whitespace-nowrap">Back to Cart</span>
                            </a>
                            <button 
                                type="submit" 
                                id="submit-btn"
                                class="flex-1 bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-6 md:px-8 py-3 md:py-4 rounded-xl font-bold text-sm md:text-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center min-w-0 touch-manipulation"
                            >
                                <svg class="w-4 md:w-5 h-4 md:h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <span class="whitespace-nowrap">Complete Order</span>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Mobile Order Summary (appears first on mobile) -->
                <div class="lg:hidden order-1 bg-white/80 backdrop-blur-sm p-4 rounded-2xl shadow-xl border border-gray-200 mb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold text-gray-900">Order Summary</h2>
                        <?php if (count($availableCurrencies) > 1): ?>
                        <a href="cart.php" class="flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-folly-500">
                            <svg class="h-3 w-3 text-gray-500 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Change (<?php echo $selectedCurrency; ?>)
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mobile Items List -->
                    <div class="space-y-3 mb-4">
                        <?php foreach ($cartItems as $index => $item): ?>
                            <div class="flex items-center text-sm" data-item-index="<?php echo $index; ?>" data-product-id="<?php echo $item['product']['id']; ?>">
                                <img 
                                    src="<?php echo getAssetUrl('images/' . $item['product']['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($item['product']['name']); ?>"
                                    class="w-10 h-10 object-cover rounded border border-gray-200"
                                    onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                >
                                <div class="ml-3 flex-1">
                                    <h4 class="font-medium text-gray-900 line-clamp-1">
                                        <?php echo htmlspecialchars($item['product']['name']); ?>
                                    </h4>
                                    <div class="text-xs text-gray-500">
                                        Qty: <?php echo $item['quantity']; ?>
                                        <?php if (isset($item['size']) && !empty($item['size'])): ?>
                                            | Size: <?php echo htmlspecialchars($item['size']); ?>
                                        <?php endif; ?>
                                        <?php if (isset($item['color']) && !empty($item['color'])): ?>
                                            | Color: <?php echo htmlspecialchars($item['color']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-sm font-semibold text-gray-900">
                                    <?php echo formatPriceWithCurrency($item['item_total'], $selectedCurrency); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Mobile Totals -->
                    <div class="border-t border-gray-200 pt-3 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Subtotal:</span>
                            <span id="mobile-subtotal"><?php echo formatPriceWithCurrency($subtotal, $selectedCurrency); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Shipping:</span>
                            <span id="mobile-shipping">
                                <?php if ($shippingCost > 0): ?>
                                    <?php echo formatPriceWithCurrency($shippingCost, $selectedCurrency); ?>
                                <?php else: ?>
                                    <span class="text-green-600">Free</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between text-base font-bold border-t border-gray-200 pt-2">
                            <span>Total:</span>
                            <span id="mobile-total"><?php echo formatPriceWithCurrency($total, $selectedCurrency); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Desktop Order Summary -->
                <div class="hidden lg:block lg:col-span-1 order-2">
                    <div class="bg-white/80 backdrop-blur-sm p-8 rounded-2xl shadow-xl border border-gray-200 sticky top-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-8">Order Summary</h2>
                        

                        
                        <!-- Items -->
                        <div class="space-y-4 mb-6">
                            <?php foreach ($cartItems as $index => $item): ?>
                                <div class="flex items-center" data-item-index="<?php echo $index; ?>" data-product-id="<?php echo $item['product']['id']; ?>">
                                    <img 
                                        src="<?php echo getAssetUrl('images/' . $item['product']['image']); ?>" 
                                        alt="<?php echo htmlspecialchars($item['product']['name']); ?>"
                                        class="w-12 h-12 object-cover rounded border border-gray-200"
                                        onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                                    >
                                    <div class="ml-3 flex-1">
                                        <h4 class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($item['product']['name']); ?>
                                        </h4>
                                        <?php if (isset($item['size']) && !empty($item['size'])): ?>
                                            <div class="text-xs text-gray-500 mb-1" data-size="<?php echo htmlspecialchars($item['size']); ?>">Size: <?php echo htmlspecialchars($item['size']); ?></div>
                                        <?php endif; ?>
                                        <?php if (isset($item['color']) && !empty($item['color'])): ?>
                                            <div class="text-xs text-gray-500 mb-1" data-color="<?php echo htmlspecialchars($item['color']); ?>">Color: <?php echo htmlspecialchars($item['color']); ?></div>
                                        <?php endif; ?>
                                        
                                        <!-- Multi-currency pricing display -->
                                        <?php if (isset($item['product']['prices']) && is_array($item['product']['prices']) && count($item['product']['prices']) > 1): ?>
                                            <div class="text-xs text-gray-600 mb-1">
                                                <?php 
                                                $priceEntries = [];
                                                foreach ($item['product']['prices'] as $currencyCode => $price) {
                                                    $currency = null;
                                                    foreach ($settings['currencies'] as $curr) {
                                                        if ($curr['code'] === $currencyCode) {
                                                            $currency = $curr;
                                                            break;
                                                        }
                                                    }
                                                    $symbol = $currency ? $currency['symbol'] : $currencyCode;
                                                    $priceEntries[] = $symbol . number_format($price, 2);
                                                }
                                                echo 'Prices: ' . implode(' | ', $priceEntries);
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <p class="text-sm text-gray-600">
                                            Qty: <?php echo $item['quantity']; ?> × <?php echo formatPriceWithCurrency($item['unit_price'], $selectedCurrency); ?>
                                        </p>
                                    </div>
                                    <div class="text-sm font-semibold text-gray-900">
                                        <?php echo formatPriceWithCurrency($item['item_total'], $selectedCurrency); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Totals -->
                        <!-- Change Currency Button (only show if multiple currencies available) -->
                        <?php if (count($availableCurrencies) > 1): ?>
                        <div class="mb-4">
                            <a href="cart.php" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-folly-500">
                                <svg class="h-5 w-5 text-gray-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                Change Currency (<?php echo $selectedCurrency; ?>)
                            </a>
                        </div>
                        <?php endif; ?>

                        <div class="border-t border-gray-200 pt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="text-gray-900"><?php echo formatPriceWithCurrency($subtotal, $selectedCurrency); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Shipping</span>
                                <span class="text-gray-900">
                                    <?php if ($shippingCost > 0): ?>
                                        <?php echo formatPriceWithCurrency($shippingCost, $selectedCurrency); ?>
                                    <?php else: ?>
                                        <span class="text-green-600">Free</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($freeShippingThreshold > 0 && $subtotal < $freeShippingThreshold): ?>
                                <div class="text-xs text-folly-600">
                                    Add <?php echo formatPriceWithCurrency($freeShippingThreshold - $subtotal, $selectedCurrency); ?> more for free shipping
                                </div>
                            <?php endif; ?>
                            <div class="border-t border-gray-200 pt-2">
                                <div class="flex justify-between text-lg font-semibold">
                                    <span class="text-gray-900">Total</span>
                                    <span class="text-gray-900"><?php echo formatPriceWithCurrency($total, $selectedCurrency); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Notice -->
                        <div class="mt-6 p-4 bg-folly-50 border border-folly-200 rounded-lg">
                            <div class="flex">
                                <svg class="w-5 h-5 text-folly mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-folly-800">Secure Checkout</h3>
                                    <p class="mt-1 text-xs text-folly-700">Your payment information is secure and encrypted.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.payment-option {
    transition: all 0.2s;
}

input[type="radio"]:checked + .payment-option {
    background-color: #fef2f2;
}

input[type="radio"]:checked + .payment-option .flex {
    border-left: 4px solid #FF0055;
    padding-left: 12px;
}

.payment-form {
    transition: all 0.3s ease-in-out;
    opacity: 0;
    max-height: 0;
    overflow: hidden;
}

.payment-form.active {
    opacity: 1;
    max-height: 1000px;
}

.payment-form.hidden {
    display: none;
}
</style>

<script>
// Get base path dynamically
function getBasePath() {
    // Use PHP-generated base URL for consistency
    return '<?php echo rtrim(getBaseUrl(), '/'); ?>';
}

// Payment form management
function showPaymentForm(paymentMethod) {
    // Hide all payment forms
    const forms = document.querySelectorAll('.payment-form');
    forms.forEach(form => {
        form.classList.remove('active');
        form.classList.add('hidden');
    });
    
    // Show selected payment form
    const selectedForm = document.getElementById(paymentMethod + '-form');
    if (selectedForm) {
        selectedForm.classList.remove('hidden');
        setTimeout(() => {
            selectedForm.classList.add('active');
        }, 10);
    }
    
    // Initialize Stripe Elements when stripe form is shown
    if (paymentMethod === 'stripe') {
        setTimeout(() => {
            initializeStripeElements();
        }, 100);
    }
    
    // Update form validation requirements
    updateFormValidation(paymentMethod);
}

// Stripe Elements variables
let stripe;
let elements;
let cardElement;
let paymentIntentClientSecret;

// Initialize Stripe Elements
function initializeStripeElements() {
    // Get publishable key from PHP
    <?php require_once 'includes/stripe-config.php'; ?>
    stripe = Stripe('<?php echo StripeConfig::getPublishableKey(); ?>');
    elements = stripe.elements();
    
    // Create card element
    cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#424770',
                '::placeholder': {
                    color: '#aab7c4',
                },
                fontFamily: 'Coves, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            },
            invalid: {
                color: '#9e2146',
            },
        },
    });
    
    // Mount the card element
    cardElement.mount('#card-element');
    
    // Handle errors
    cardElement.on('change', ({error}) => {
        const displayError = document.getElementById('card-errors');
        if (error) {
            displayError.textContent = error.message;
        } else {
            displayError.textContent = '';
        }
    });
}

// Create PaymentIntent when card form is shown
async function createPaymentIntent() {
    // Collect customer data with null checks
    const customerData = {
        email: document.getElementById('email')?.value || '',
        first_name: document.getElementById('first_name')?.value || '',
        last_name: document.getElementById('last_name')?.value || '',
        phone: document.getElementById('phone')?.value || '',
        countryCode: document.getElementById('countryCode')?.value || '+44',
        address: document.getElementById('address')?.value || '',
        city: document.getElementById('city')?.value || '',
        postal_code: document.getElementById('postal_code')?.value || '',
        country: document.getElementById('country')?.value || '',
        special_instructions: document.querySelector('textarea[name="special_instructions"]')?.value || ''
    };
    
    try {
        const response = await fetch(`${getBasePath()}/api/stripe-payment.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'create_payment_intent',
                customer_data: customerData
            })
        });
        
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Invalid JSON response:', responseText);
            throw new Error('Server returned invalid response. Please try again.');
        }
        if (data.success) {
            paymentIntentClientSecret = data.client_secret;
            return data;
        } else {
            throw new Error(data.error || 'Failed to create payment intent');
        }
    } catch (error) {
        console.error('Error creating payment intent:', error);
        throw error;
    }
}

// Process Stripe payment
async function processStripePayment() {
    if (!cardElement || !paymentIntentClientSecret) {
        throw new Error('Payment not properly initialized');
    }
    
    const cardholderName = document.getElementById('cardholder_name').value;
    if (!cardholderName.trim()) {
        throw new Error('Please enter the cardholder name');
    }
    
    // Show processing state
    document.getElementById('payment-processing').classList.remove('hidden');
    
    // Confirm payment with Stripe
    const {error, paymentIntent} = await stripe.confirmCardPayment(paymentIntentClientSecret, {
        payment_method: {
            card: cardElement,
            billing_details: {
                name: cardholderName,
                email: document.getElementById('email')?.value || '',
                address: {
                    line1: document.getElementById('address')?.value || '',
                    city: document.getElementById('city')?.value || '',
                    postal_code: document.getElementById('postal_code')?.value || '',
                    country: document.getElementById('country')?.value || '',
                }
            }
        }
    });
    
    // Hide processing state
    document.getElementById('payment-processing').classList.add('hidden');
    
    if (error) {
        throw new Error(error.message);
    }
    
    if (paymentIntent.status === 'succeeded') {
        // Payment succeeded, create order
        const response = await fetch(`${getBasePath()}/api/stripe-payment.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'confirm_payment',
                payment_intent_id: paymentIntent.id
            })
        });
        
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Invalid JSON response:', responseText);
            throw new Error('Server returned invalid response. Please try again.');
        }
        if (data.success) {
            // Redirect to success page
            window.location.href = data.redirect_url;
        } else {
            throw new Error(data.error || 'Failed to create order');
        }
    } else {
        throw new Error('Payment was not completed');
    }
}

// Update form validation based on payment method
function updateFormValidation(paymentMethod) {
    // Remove required attribute from all payment fields
    const allPaymentInputs = document.querySelectorAll('.payment-form input');
    allPaymentInputs.forEach(input => {
        input.removeAttribute('required');
    });
    
    // Add required attribute to active payment form fields
    if (paymentMethod === 'stripe') {
        // Only cardholder name is a manual input with Stripe Elements
        const cardholderNameInput = document.getElementById('cardholder_name');
        if (cardholderNameInput) {
            cardholderNameInput.setAttribute('required', '');
        }
    }
}

// Form submission tracker
let isSubmitting = false;

// Update phone placeholder based on selected country code
function updatePhonePlaceholder() {
    const countryCodeSelect = document.getElementById('countryCode');
    const phoneInput = document.getElementById('phone');
    const selectedOption = countryCodeSelect.options[countryCodeSelect.selectedIndex];
    const format = selectedOption.getAttribute('data-format');
    
    if (format && phoneInput) {
        // Convert format from XXX XXX XXXX to placeholder text
        const placeholder = format.replace(/X/g, '0');
        phoneInput.placeholder = `${placeholder}`;
    }
}

// Form submission validation and handling
async function submitCheckoutForm() {
    // Prevent double submission
    if (isSubmitting) {
        return;
    }
    
    const form = document.querySelector('form[method="POST"]');
    const formData = new FormData(form);
    const paymentMethod = formData.get('payment_method');
    
    const submitButton = document.getElementById('submit-btn');
    const originalText = submitButton.innerHTML;
    
    // Set submission state
    isSubmitting = true;
    
    // Show loading state
    submitButton.innerHTML = '<svg class="w-5 h-5 animate-spin mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Processing...';
    submitButton.disabled = true;
    
    try {
        // Validate required fields
        const requiredFields = ['first_name', 'last_name', 'email', 'address', 'city', 'postal_code', 'country'];
        for (const field of requiredFields) {
            const value = formData.get(field);
            if (!value || value.trim() === '') {
                throw new Error(`Please fill in the ${field.replace('_', ' ')} field`);
            }
        }
        
        // Handle Stripe card payments
        if (paymentMethod === 'stripe') {
            // Create PaymentIntent first
            await createPaymentIntent();
            // Then process the payment
            await processStripePayment();
            return; // processStripePayment handles the redirect
        }
        
        // Handle other payment methods (existing logic)
        const orderData = {
            customer: {
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                countryCode: formData.get('countryCode')
            },
            shipping: {
                address: formData.get('address'),
                city: formData.get('city'),
                postal_code: formData.get('postal_code'),
                country: formData.get('country')
            },
            payment_method: paymentMethod,
            special_instructions: formData.get('special_instructions') || '',
            bank_name: formData.get('bank_name') || '',
            account_holder: formData.get('account_holder') || '',
            paypal_email: formData.get('paypal_email') || ''
        };
        
        const response = await fetch(`${getBasePath()}/checkout.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });
        
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Invalid JSON response:', responseText);
            throw new Error('Server returned invalid response. Please try again.');
        }
        
        if (result.success) {
            window.location.href = result.redirect_url;
        } else {
            throw new Error(result.error || 'Failed to process order');
        }
        
    } catch (error) {
        console.error('Checkout error:', error);
        
        // Hide payment processing indicator
        const processingEl = document.getElementById('payment-processing');
        if (processingEl) {
            processingEl.classList.add('hidden');
        }
        
        let errorMessage = error.message;
        
        // Clean up error messages
        if (errorMessage.includes('test mode') && errorMessage.includes('non test card')) {
            errorMessage = 'Card payment failed. Please check your card details and try again.';
        }
        
        // Use SweetAlert if available, otherwise use native alert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Payment Error',
                text: errorMessage,
                confirmButtonColor: '#FF0055'
            });
        } else {
            // Fallback to native alert
            alert('Payment Error: ' + errorMessage);
        }
    } finally {
        // Restore button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
        isSubmitting = false;
    }
}







// Update Order Summary with new currency data
function updateOrderSummary(data) {
    // Update mobile order summary
    const mobileSubtotal = document.getElementById('mobile-subtotal');
    const mobileShipping = document.getElementById('mobile-shipping');
    const mobileTotal = document.getElementById('mobile-total');
    
    if (mobileSubtotal) {
        mobileSubtotal.textContent = data.subtotal;
    }
    
    if (mobileShipping) {
        if (data.shipping === 'Free') {
            mobileShipping.innerHTML = '<span class="text-green-600">Free</span>';
        } else {
            mobileShipping.textContent = data.shipping;
        }
    }
    
    if (mobileTotal) {
        mobileTotal.textContent = data.total;
    }
    
    // Update desktop order summary - use a more targeted approach
    // Find the desktop order summary container (hidden on mobile, shown on lg screens)
    const desktopOrderSummary = document.querySelector('.hidden.lg\\:block .bg-white\\/80');
    
    if (desktopOrderSummary) {
        // Find all price display elements within desktop order summary
        const priceElements = desktopOrderSummary.querySelectorAll('.flex.justify-between');
        
        priceElements.forEach(element => {
            const label = element.querySelector('.text-gray-600');
            const value = element.querySelector('.text-gray-900');
            
            if (label && value) {
                const labelText = label.textContent.toLowerCase();
                
                if (labelText.includes('subtotal')) {
                    value.textContent = data.subtotal;
                } else if (labelText.includes('shipping')) {
                    if (data.shipping === 'Free') {
                        value.innerHTML = '<span class="text-green-600">Free</span>';
                    } else {
                        value.textContent = data.shipping;
                    }
                } else if (labelText.includes('total')) {
                    value.textContent = data.total;
                }
            }
        });
    }
    
    // Update cart items prices in both mobile and desktop views
    if (data.cart_items && Array.isArray(data.cart_items)) {
        const cartItemElements = document.querySelectorAll('.flex.items-center');
        
        cartItemElements.forEach((itemElement, index) => {
            if (data.cart_items[index]) {
                // Look for price elements within this cart item
                const priceElements = itemElement.querySelectorAll('.text-sm.font-semibold, .font-semibold');
                priceElements.forEach(priceEl => {
                    // Check if this contains a price (has currency symbols or price format)
                    if (priceEl.textContent.match(/[\£\$\€\₦]/)) {
                        priceEl.textContent = data.cart_items[index].item_total;
                    }
                });
                
                // Update quantity x unit price text
                const qtyPriceElements = itemElement.querySelectorAll('.text-sm.text-gray-600');
                qtyPriceElements.forEach(qtyEl => {
                    if (qtyEl.textContent.includes('Qty:')) {
                        qtyEl.innerHTML = `Qty: ${data.cart_items[index].quantity} × ${data.cart_items[index].unit_price}`;
                    }
                });
            }
        });
    }
}

// Update Payment Methods based on currency
function updatePaymentMethods(paymentMethods, currency) {
    const paymentContainer = document.querySelector('.space-y-3.md\\:space-y-4');
    if (!paymentContainer) {
        console.error('Payment container not found');
        return;
    }
    
    // Clear existing payment methods
    const existingMethods = paymentContainer.querySelectorAll('label');
    existingMethods.forEach(method => method.remove());
    
    // Add new payment methods
    paymentMethods.forEach(method => {
        const methodHTML = createPaymentMethodHTML(method, currency);
        paymentContainer.insertAdjacentHTML('beforeend', methodHTML);
    });
    
    // Update existing payment forms container
    let formsContainer = paymentContainer.parentNode.querySelector('.mt-6');
    if (formsContainer) {
        // Clear existing forms
        const existingForms = formsContainer.querySelectorAll('.payment-form');
        existingForms.forEach(form => form.remove());
        
        // Add payment forms for each method
        paymentMethods.forEach(method => {
            const formHTML = createPaymentFormHTML(method, currency);
            formsContainer.insertAdjacentHTML('beforeend', formHTML);
        });
    }
    
    // Re-initialize payment method selection
    setTimeout(() => {
        const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
        if (paymentRadios.length > 0) {
            paymentRadios[0].checked = true;
            showPaymentForm(paymentRadios[0].value);
        }
    }, 100);
}

// Create HTML for payment forms
function createPaymentFormHTML(method, currency) {
    switch (method) {
        case 'stripe':
            return `
                <div id="stripe-form" class="payment-form hidden">
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Card Details</h3>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Card Information <span class="text-red-500">*</span>
                                </label>
                                <div id="card-element" class="p-4 border border-gray-300 rounded-xl bg-white">
                                    <!-- Stripe Elements will create form elements here -->
                                </div>
                                <div id="card-errors" class="text-red-600 text-sm mt-2" role="alert"></div>
                            </div>
                            
                            <div>
                                <label for="cardholder_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Cardholder Name <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="cardholder_name" 
                                    name="cardholder_name" 
                                    placeholder="Name as it appears on card"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200"
                                >
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3 bg-folly-50 rounded-lg border border-folly-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-folly mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                <span class="text-sm text-folly-700">
                                    <strong class="text-folly-800">Powered by Stripe</strong> - Your payment information is secure and encrypted
                                </span>
                            </div>
                        </div>
                        
                        <div id="payment-processing" class="hidden mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-500 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span class="text-blue-700">Processing your payment...</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        case 'paypal':
            return `
                <div id="paypal-form" class="payment-form hidden">
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">PayPal Payment</h3>
                        <div class="text-center py-8">
                            <div class="mb-4">
                                <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_111x69.jpg" alt="PayPal Logo" class="h-16 mx-auto">
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Pay with PayPal</h4>
                            <p class="text-gray-600 mb-6">You'll be redirected to PayPal to complete your payment securely.</p>
                            <div class="bg-folly-50 p-4 rounded-lg">
                                <p class="text-sm text-folly-700">
                                    <strong>Note:</strong> After clicking "Complete Order", you'll be taken to PayPal where you can log in and complete your payment.
                                </p>
                            </div>
                            
                            <!-- PayPal Direct Link -->
                            <div class="bg-blue-50 p-4 rounded-lg mt-4">
                                <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                    <img src="https://www.paypalobjects.com/webstatic/icon/pp24.png" alt="PayPal Icon" class="w-6 h-6 mr-2">
                                    PayPal Direct Link
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">PayPal:</span>
                                        <a href="http://paypal.me/amp202247" target="_blank" class="font-medium text-blue-600 hover:text-blue-800 underline">
                                            paypal.me/amp202247
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- PayPal Direct Link -->
                            <div class="bg-blue-50 p-4 rounded-lg mt-4">
                                <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                    <img src="https://www.paypalobjects.com/webstatic/icon/pp24.png" alt="PayPal Icon" class="w-6 h-6 mr-2">
                                    PayPal Direct Link
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">PayPal:</span>
                                        <a href="http://paypal.me/amp202247" target="_blank" class="font-medium text-blue-600 hover:text-blue-800 underline">
                                            paypal.me/amp202247
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        case 'bank_transfer':
            return `
                <div id="bank_transfer-form" class="payment-form hidden">
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Bank Transfer Details</h3>
                        <div class="space-y-4">
                            ${currency.toUpperCase() === 'NGN' || currency.toUpperCase() === 'NAIRA' ? `
                                <!-- Nigerian Account -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                        <i class="bi bi-flag text-green-600 mr-2"></i> Nigerian Account
                                    </h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Bank:</span>
                                            <span class="font-medium">Parallex Bank</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Account Number:</span>
                                            <span class="font-medium font-mono">100004476</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Account Name:</span>
                                            <span class="font-medium">ANGELMP</span>
                                        </div>
                                    </div>
                                </div>
                            ` : `
                                <!-- UK Account for International Currencies -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                        <i class="bi bi-flag text-blue-600 mr-2"></i> UK Account
                                    </h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Sort Code:</span>
                                            <span class="font-medium font-mono">04-00-04</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Account Number:</span>
                                            <span class="font-medium font-mono">64689014</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Account Name:</span>
                                            <span class="font-medium">ANGELMP</span>
                                        </div>
                                    </div>
                                </div>
                                

                            `}
                            
                            <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                                <div class="flex">
                                    <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.664-.833-2.464 0L5.232 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <div>
                                        <h4 class="text-sm font-medium text-yellow-800">Important:</h4>
                                        <div class="mt-1 text-sm text-yellow-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>Please include your order reference in the transfer notes</li>
                                                <li>Your order will be processed after payment confirmation (1-3 business days)</li>
                                                <li>Keep your transfer receipt for your records</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        case 'espees':
            return `
                <div id="espees-form" class="payment-form hidden">
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-6 h-6 text-folly mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            Espees Payment
                        </h3>
                        
                        <div class="text-center py-6">
                            <div class="bg-gradient-to-r from-folly-100 to-purple-100 p-6 rounded-lg mb-6">
                                <div class="text-center">
                                    <div class="flex items-center justify-center mb-4">
                                        <h4 class="text-xl font-bold text-folly-800">ESPEES PAYMENT</h4>
                                    </div>
                                    
                                    <div class="bg-white p-4 rounded-lg border-2 border-folly-300 mb-4">
                                        <h5 class="text-lg font-bold text-folly-800 mb-2">ESPEES PAYABLE TO:</h5>
                                        <div class="text-2xl font-mono font-bold text-folly-900">
                                            ANGELMP
                                        </div>
                                    </div>
                                    
                                    <p class="text-sm text-folly-700">
                                        Send the exact amount to ANGELMP using your Espees wallet
                                    </p>
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                                <div class="flex">
                                    <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-800">Payment Instructions:</h4>
                                        <div class="mt-1 text-sm text-blue-700">
                                            <ol class="list-decimal list-inside space-y-1">
                                                <li>Open your Espees wallet application</li>
                                                <li>Send payment to: <strong>ANGELMP</strong></li>
                                                <li>Send the exact order amount</li>
                                                <li>Include your order reference in the transaction notes</li>
                                                <li>Click "Complete Order" below after sending payment</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        default:
            return '';
    }
}

// Create HTML for payment method
function createPaymentMethodHTML(method, currency) {
    const methodConfig = {
        stripe: {
            icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            title: 'Credit/Debit Card',
            description: `Pay securely with Stripe (${currency.toUpperCase()})`
        },
        paypal: {
            icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
            title: 'PayPal',
            description: `Pay with PayPal (${currency.toUpperCase()})`
        },
        bank_transfer: {
            icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4m0 0v-5a2 2 0 012-2h2a2 2 0 012 2v5',
            title: 'Bank Transfer',
            description: currency.toUpperCase() === 'NGN' ? 'Nigerian Bank Transfer (NGN)' : `International Bank Transfer (${currency.toUpperCase()})`
        },
        espees: {
            icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
            title: 'Espees Payment',
            description: 'Pay with Espees currency'
        }
    };
    
    const config = methodConfig[method] || methodConfig.bank_transfer;
    
    return `
        <label class="relative flex items-center p-4 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-folly-300">
            <input 
                type="radio" 
                name="payment_method" 
                value="${method}" 
                class="sr-only payment-radio"
                onchange="showPaymentForm('${method}')"
            >
            <div class="payment-option w-full">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-folly mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${config.icon}"></path>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-gray-900">${config.title}</h3>
                        <p class="text-sm text-gray-600">${config.description} (${currency.toUpperCase()})</p>
                    </div>
                </div>
            </div>
        </label>
    `;
}

// Form initialization and event handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="POST"]');
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    
    // Initialize with first checked payment method
    paymentRadios.forEach(radio => {
        if (radio.checked) {
            showPaymentForm(radio.value);
        }
    });
    
    // If no payment method is selected, select the first available one
    if (!document.querySelector('input[name="payment_method"]:checked') && paymentRadios.length > 0) {
        paymentRadios[0].checked = true;
        showPaymentForm(paymentRadios[0].value);
    }
    
    // Initialize phone placeholder
    updatePhonePlaceholder();
    
    // Handle form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Always prevent default form submission
            submitCheckoutForm(); // Use our custom handler
        });
    }
    

});


</script>

<?php include 'includes/footer.php'; ?>