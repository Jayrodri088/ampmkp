<?php
require_once '../includes/stripe-config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'create_payment_intent') {
        // Create PaymentIntent
        
        // Check if cart exists and has items
        $cart = getCart();
        if (empty($cart)) {
            throw new Exception('Cart is empty');
        }

        // Get cart details and calculate totals
        $cartItems = [];
        $cartItemsForMetadata = []; // Simplified version for Stripe metadata
        $subtotal = 0;
        
        foreach ($cart as $item) {
            $product = getProductById($item['product_id']);
            if ($product) {
                $itemTotal = $product['price'] * $item['quantity'];
                
                // Full cart items for order processing
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'item_total' => $itemTotal
                ];
                
                // Simplified cart items for Stripe metadata (under 500 chars)
                $cartItemsForMetadata[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $item['quantity'],
                    'total' => $itemTotal
                ];
                
                $subtotal += $itemTotal;
            }
        }

        if (empty($cartItems)) {
            throw new Exception('No valid items in cart');
        }

        // Get settings
        $settings = getSettings();
        $freeShippingThreshold = $settings['shipping']['free_shipping_threshold'] ?? 0;
        $standardShippingCost = $settings['shipping']['standard_shipping_cost'] ?? 0;
        
        // Calculate shipping cost
        if ($freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold) {
            $shippingCost = 0; // Free shipping
        } else {
            $shippingCost = $standardShippingCost; // Standard shipping cost
        }
        
        $total = $subtotal + $shippingCost;

        // Get customer data if provided
        $customerData = null;
        if (isset($input['customer_data'])) {
            $customerData = $input['customer_data'];
        }

        // Create PaymentIntent
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => round($total * 100), // Convert to cents/pence
            'currency' => strtolower($settings['currency_code']),
            'metadata' => [
                'cart_items' => json_encode($cartItemsForMetadata), // Use simplified version
                'subtotal' => (string)$subtotal,
                'shipping_cost' => (string)$shippingCost,
                'total' => (string)$total,
                'item_count' => (string)count($cartItems)
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
        
        // Store full cart data and customer data separately for order creation
        // We'll retrieve this when confirming the payment
        $paymentData = [
            'cart_items' => $cartItems,
            'customer_data' => $customerData,
            'created_at' => time()
        ];
        
        // Store payment data in a temporary file using payment intent ID
        $tempFilePath = __DIR__ . '/../data/temp_payment_' . $paymentIntent->id . '.json';
        $writeResult = file_put_contents($tempFilePath, json_encode($paymentData));
        
        if ($writeResult === false) {
            throw new Exception('Failed to store payment data');
        }

        echo json_encode([
            'success' => true,
            'client_secret' => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id
        ]);

    } elseif ($action === 'confirm_payment') {
        // Confirm payment and create order
        
        $paymentIntentId = $input['payment_intent_id'] ?? '';
        if (empty($paymentIntentId)) {
            throw new Exception('Payment Intent ID is required');
        }

        // Retrieve the PaymentIntent
        $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
        
        if ($paymentIntent->status !== 'succeeded') {
            throw new Exception('Payment not completed');
        }

        // Read full cart data from temporary file
        $tempPaymentFile = __DIR__ . '/../data/temp_payment_' . $paymentIntentId . '.json';
        
        if (!file_exists($tempPaymentFile)) {
            throw new Exception('Payment data not found. Please try the checkout process again.');
        }
        
        $paymentData = json_decode(file_get_contents($tempPaymentFile), true);
        $cartItems = $paymentData['cart_items'];
        $customerData = $paymentData['customer_data'];
        
        // Get totals from metadata (these are simplified and safe)
        $subtotal = floatval($paymentIntent->metadata->subtotal);
        $shippingCost = floatval($paymentIntent->metadata->shipping_cost);
        $total = floatval($paymentIntent->metadata->total);

        // Create order ID
        $orderId = 'AMP' . date('Y') . sprintf('%06d', time() % 1000000);

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
            'customer_name' => ($customerData['first_name'] ?? '') . ' ' . ($customerData['last_name'] ?? ''),
            'customer_email' => $customerData['email'] ?? '',
            'customer_phone' => $customerData['phone'] ?? '',
            'shipping_address' => [
                'line1' => $customerData['address'] ?? '',
                'line2' => '',
                'city' => $customerData['city'] ?? '',
                'postcode' => $customerData['postal_code'] ?? '',
                'country' => $customerData['country'] ?? ''
            ],
            'billing_address' => [
                'line1' => $customerData['address'] ?? '',
                'line2' => '',
                'city' => $customerData['city'] ?? '',
                'postcode' => $customerData['postal_code'] ?? '',
                'country' => $customerData['country'] ?? ''
            ],
            'items' => $orderItems,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax' => 0,
            'total' => $total,
            'status' => 'processing',
            'payment_method' => 'card',
            'payment_status' => 'completed',
            'date' => date('Y-m-d H:i:s'),
            'notes' => $customerData['special_instructions'] ?? '',
            'stripe_payment_intent' => $paymentIntentId
        ];

        // Save order
        $orders = readJsonFile('orders.json');
        $orders[] = $orderData;
        
        if (writeJsonFile('orders.json', $orders)) {
            // Clear cart after successful payment
            clearCart();
            
            // Clean up temporary payment file
            if (file_exists($tempPaymentFile)) {
                unlink($tempPaymentFile);
            }
            
            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'redirect_url' => getBaseUrl('order-success.php?order=' . $orderId)
            ]);
        } else {
            throw new Exception('Failed to save order');
        }

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 