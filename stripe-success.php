<?php
$page_title = 'Payment Successful';
$page_description = 'Thank you for your payment! Your order has been successfully processed.';

require_once 'includes/stripe-config.php';
require_once 'includes/functions.php';

// Get session ID from URL
$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    header('Location: ' . getBaseUrl());
    exit;
}

try {
    // Retrieve the checkout session
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    
    if ($session->payment_status !== 'paid') {
        throw new Exception('Payment not completed');
    }

    // Get customer details from session
    $customer = null;
    if ($session->customer_details) {
        $customer = $session->customer_details;
    }

    // Parse cart items from metadata
    $cartItems = json_decode($session->metadata->cart_items, true);
    $subtotal = floatval($session->metadata->subtotal);
    $shippingCost = floatval($session->metadata->shipping_cost);
    $total = floatval($session->metadata->total);

    // Create order ID
    $orderId = 'AMP' . date('Y') . sprintf('%06d', time() % 1000000);

    // Prepare customer data to match existing order structure
    $customerName = $customer->name ?? '';
    $nameParts = explode(' ', $customerName, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';

    // Convert cart items to match existing order structure
    $orderItems = [];
    foreach ($cartItems as $item) {
        $orderItems[] = [
            'product_id' => $item['product']['id'],
            'product_name' => $item['product']['name'],
            'quantity' => $item['quantity'],
            'price' => $item['product']['price'],
            'subtotal' => $item['item_total']
        ];
    }

    // Create order data matching existing structure
    $orderData = [
        'id' => $orderId,
        'customer_name' => $customerName,
        'customer_email' => $customer->email ?? '',
        'customer_phone' => $customer->phone ?? '',
        'shipping_address' => [
            'line1' => $customer->address->line1 ?? '',
            'line2' => $customer->address->line2 ?? '',
            'city' => $customer->address->city ?? '',
            'postcode' => $customer->address->postal_code ?? '',
            'country' => $customer->address->country ?? ''
        ],
        'billing_address' => [
            'line1' => $customer->address->line1 ?? '',
            'line2' => $customer->address->line2 ?? '',
            'city' => $customer->address->city ?? '',
            'postcode' => $customer->address->postal_code ?? '',
            'country' => $customer->address->country ?? ''
        ],
        'items' => $orderItems,
        'subtotal' => $subtotal,
        'shipping_cost' => $shippingCost,
        'tax' => 0,
        'total' => $total,
        'status' => 'processing',
        'payment_method' => 'stripe',
        'payment_status' => 'completed',
        'date' => date('Y-m-d H:i:s'),
        'notes' => '',
        'stripe_session_id' => $sessionId,
        'stripe_payment_intent' => $session->payment_intent
    ];

    // Save order
    $orders = readJsonFile('orders.json');
    $orders[] = $orderData;
    
    if (writeJsonFile('orders.json', $orders)) {
        // Clear cart after successful payment
        clearCart();
        
        // Store order for display
        $order = $orderData;
        $success = true;
    } else {
        throw new Exception('Failed to save order');
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    $success = false;
}

// Redirect to regular order success page if no error
if (isset($success) && $success) {
    header('Location: ' . getBaseUrl('order-success.php?order=' . $orderId));
    exit;
}

include 'includes/header.php';
?>

<!-- Error Page if something went wrong -->
<section class="bg-white py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Error Icon -->
            <div class="text-red-500 mb-8">
                <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <!-- Error Message -->
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Payment Processing Error</h1>
            <p class="text-xl text-gray-600 mb-8">
                <?php echo htmlspecialchars($error ?? 'There was an issue processing your payment.'); ?>
            </p>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo getBaseUrl('cart.php'); ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-md font-medium transition-colors duration-200">
                    Return to Cart
                </a>
                <a href="<?php echo getBaseUrl('contact.php'); ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-8 py-3 rounded-md font-medium transition-colors duration-200">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 