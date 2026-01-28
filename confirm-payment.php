<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/mail_config.php';

$orderId = $_GET['order'] ?? '';
$order = null;
$error = '';

if (!$orderId) {
    header('Location: ' . getBaseUrl());
    exit;
}

$orders = readJsonFile('orders.json');
$orderIndex = -1;

foreach ($orders as $index => $o) {
    if ($o['id'] === $orderId) {
        $order = $o;
        $orderIndex = $index;
        break;
    }
}

if (!$order) {
    header('Location: ' . getBaseUrl());
    exit;
}

$orders[$orderIndex]['payment_confirmed_by_customer'] = true;
$orders[$orderIndex]['payment_confirmed_at'] = date('Y-m-d H:i:s');
$orders[$orderIndex]['updated_at'] = date('Y-m-d H:i:s');

// Save payment details from the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orders[$orderIndex]['payment_details'] = [
        'transaction_id' => sanitizeInput($_POST['transaction_id'] ?? ''),
        'payment_email' => sanitizeInput($_POST['payment_email'] ?? ''),
        'bank_name' => sanitizeInput($_POST['bank_name'] ?? ''),
        'account_holder' => sanitizeInput($_POST['account_holder'] ?? ''),
        'transfer_date' => sanitizeInput($_POST['transfer_date'] ?? ''),
        'amount_sent' => sanitizeInput($_POST['amount_sent'] ?? ''),
        'payment_notes' => sanitizeInput($_POST['payment_notes'] ?? ''),
        'submitted_at' => date('Y-m-d H:i:s')
    ];
}

if (writeJsonFile('orders.json', $orders)) {
    $order = $orders[$orderIndex];
    
    try {
        if (function_exists('sendPaymentConfirmationToAdmin')) {
            @sendPaymentConfirmationToAdmin($order);
        }
    } catch (Throwable $e) {
        error_log('Payment confirmation email error: ' . $e->getMessage());
    }
}

$pageTitle = 'Payment Confirmation - Angel Marketplace';
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">Payment Confirmation</span>
        </nav>
    </div>
</div>

<!-- Success Hero -->
<section class="bg-charcoal-900 py-16 md:py-24 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20 bg-[url('assets/images/pattern.png')]"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-green-500 rounded-full mix-blend-overlay filter blur-3xl opacity-20 animate-pulse"></div>
    
    <div class="container mx-auto px-4 relative z-10 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-500 rounded-full mb-8 shadow-lg shadow-green-500/30 animate-bounce-slow">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        
        <h1 class="text-3xl md:text-5xl font-bold text-white mb-6 font-display tracking-tight">
            Payment Confirmation Received
        </h1>
        <p class="text-xl text-gray-300 max-w-2xl mx-auto leading-relaxed font-light">
            Thank you for confirming your payment! We will verify the details and process your order shortly.
        </p>
    </div>
</section>

<!-- Content -->
<section class="bg-gray-50 py-12 md:py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto">
            
            <!-- Next Steps -->
            <div class="bg-white rounded-2xl shadow-soft border border-gray-100 p-8 mb-8">
                <h2 class="text-xl font-bold text-charcoal-900 mb-6 font-display flex items-center gap-3">
                    <span class="w-8 h-8 bg-folly text-white rounded-full flex items-center justify-center text-sm">!</span>
                    What Happens Next?
                </h2>
                
                <div class="space-y-6 relative">
                    <!-- Vertical Line -->
                    <div class="absolute left-4 top-4 bottom-4 w-0.5 bg-gray-100"></div>
                    
                    <div class="relative flex gap-6">
                        <div class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center flex-shrink-0 z-10 border-4 border-white shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-charcoal-900 mb-1">Confirmation Received</h3>
                            <p class="text-sm text-gray-500">Our team has been notified of your payment details.</p>
                        </div>
                    </div>
                    
                    <div class="relative flex gap-6">
                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0 z-10 border-4 border-white shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-charcoal-900 mb-1">Verification</h3>
                            <p class="text-sm text-gray-500">We will verify your payment within 24 hours (usually much faster).</p>
                        </div>
                    </div>
                    
                    <div class="relative flex gap-6">
                        <div class="w-8 h-8 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center flex-shrink-0 z-10 border-4 border-white shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 mb-1">Processing & Shipping</h3>
                            <p class="text-sm text-gray-500">Once verified, your order will be processed and shipped immediately.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Details Summary -->
            <div class="bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-bold text-charcoal-900 font-display">Order Summary</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Order Reference</p>
                        <p class="font-mono text-xl font-bold text-folly"><?php echo htmlspecialchars($orderId); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Total Amount</p>
                        <p class="font-bold text-xl text-charcoal-900"><?php echo formatPrice($order['total'], $order['currency'] ?? 'GBP'); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Payment Method</p>
                        <p class="font-medium text-charcoal-900 capitalize"><?php echo str_replace('_', ' ', $order['payment_method'] ?? ''); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Status</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Awaiting Verification
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="px-8 py-4 bg-folly text-white rounded-xl font-bold hover:bg-folly-600 transition-all shadow-lg hover:shadow-folly/30 flex items-center justify-center gap-2">
                    <span>Continue Shopping</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
                <a href="mailto:<?php echo ADMIN_EMAIL; ?>" class="px-8 py-4 bg-white text-charcoal-900 border border-gray-200 rounded-xl font-bold hover:bg-gray-50 transition-all flex items-center justify-center gap-2">
                    <span>Contact Support</span>
                </a>
            </div>
            
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
