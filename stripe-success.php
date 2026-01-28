<?php
$page_title = 'Payment Successful';
$page_description = 'Thank you for your payment! Your order has been successfully processed.';

require_once 'includes/stripe-config.php';
require_once 'includes/functions.php';
require_once 'includes/mail_config.php';

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

        // Attempt to collect Stripe receipt/invoice URLs and send emails
        try {
            $receiptUrl = '';
            $invoiceUrl = '';

            // Prefer session invoice if available
            if (!empty($session->invoice)) {
                $invoice = \Stripe\Invoice::retrieve($session->invoice);
                if (!empty($invoice->hosted_invoice_url)) { $invoiceUrl = $invoice->hosted_invoice_url; }
                elseif (!empty($invoice->invoice_pdf)) { $invoiceUrl = $invoice->invoice_pdf; }
            }

            // Fetch latest charge for receipt URL
            if (!empty($session->payment_intent)) {
                $pi = \Stripe\PaymentIntent::retrieve([
                    'id' => $session->payment_intent,
                    'expand' => ['latest_charge']
                ]);
                if (!empty($pi->latest_charge) && !empty($pi->latest_charge->receipt_url)) {
                    $receiptUrl = $pi->latest_charge->receipt_url;
                }
                // If invoice not found above, try via charge->invoice
                if (empty($invoiceUrl) && !empty($pi->latest_charge) && !empty($pi->latest_charge->invoice)) {
                    $invoice = \Stripe\Invoice::retrieve($pi->latest_charge->invoice);
                    if (!empty($invoice->hosted_invoice_url)) { $invoiceUrl = $invoice->hosted_invoice_url; }
                    elseif (!empty($invoice->invoice_pdf)) { $invoiceUrl = $invoice->invoice_pdf; }
                }
            }

            // Send emails (customer + admin)
            @sendOrderConfirmationToCustomer($orderData, $receiptUrl, $invoiceUrl);
            @sendOrderNotificationToAdmin($orderData, $receiptUrl, $invoiceUrl);
        } catch (Exception $mailErr) {
            // Log but don't block redirect
            if (function_exists('logError')) { logError('Order email send failed: ' . $mailErr->getMessage()); }
        }
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
<section class="bg-gray-50 py-16 md:py-24">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto text-center">
            <!-- Error Icon -->
            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-8 animate-pulse">
                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.232 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <!-- Error Message -->
            <h1 class="text-3xl md:text-4xl font-bold text-charcoal-900 mb-4 font-display">Payment Processing Error</h1>
            <div class="bg-white rounded-2xl shadow-soft border border-red-100 p-8 mb-10">
                <p class="text-lg text-gray-600 mb-2">We encountered an issue while processing your payment.</p>
                <p class="text-red-500 font-medium bg-red-50 py-2 px-4 rounded-lg inline-block">
                    <?php echo htmlspecialchars($error ?? 'Unknown error occurred.'); ?>
                </p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo getBaseUrl('cart.php'); ?>" class="px-8 py-4 bg-folly text-white rounded-xl font-bold hover:bg-folly-600 transition-all shadow-lg hover:shadow-folly/30 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l-2.5 5m12.5 0H9"></path></svg>
                    Return to Cart
                </a>
                <a href="<?php echo getBaseUrl('contact.php'); ?>" class="px-8 py-4 bg-white text-charcoal-900 border border-gray-200 rounded-xl font-bold hover:bg-gray-50 transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>