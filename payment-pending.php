<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/mail_config.php';

$orderId = $_GET['order'] ?? '';
$order = null;
$error = '';

if ($orderId) {
    $orders = readJsonFile('orders.json');
    foreach ($orders as $o) {
        if ($o['id'] === $orderId) {
            $order = $o;
            break;
        }
    }
}

if (!$order) {
    header('Location: ' . getBaseUrl());
    exit;
}

$paymentMethod = $order['payment_method'] ?? '';
$currency = $order['currency'] ?? 'GBP';

$pageTitle = 'Payment Pending - Angel Marketplace';
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <a href="<?php echo getBaseUrl('checkout.php'); ?>" class="text-gray-500 hover:text-folly transition-colors">Checkout</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">Payment Pending</span>
        </nav>
    </div>
</div>

<!-- Page Header -->
<section class="bg-charcoal-900 py-12 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10 bg-[url('assets/images/pattern.png')]"></div>
    <div class="absolute top-0 right-0 w-64 h-64 bg-folly rounded-full mix-blend-overlay filter blur-3xl opacity-20"></div>
    
    <div class="container mx-auto px-4 relative z-10 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-500/20 backdrop-blur-sm rounded-full mb-6 border border-yellow-500/30">
            <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4 font-display">Payment Pending</h1>
        <p class="text-gray-400 max-w-xl mx-auto text-lg">
            Your order <span class="text-white font-mono font-bold">#<?php echo htmlspecialchars($orderId); ?></span> has been placed. 
            Please complete your payment to finalize the order.
        </p>
    </div>
</section>

<!-- Payment Content -->
<section class="bg-gradient-to-b from-gray-50 to-white py-12 md:py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content (Instructions) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Action Required Alert -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.232 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-yellow-800 mb-1">Action Required</h3>
                            <p class="text-sm text-yellow-700 leading-relaxed">
                                Your order is currently on hold. We've reserved your items, but we need to confirm your payment before shipping. Please follow the instructions below.
                            </p>
                        </div>
                    </div>

                    <!-- Payment Method Specific Instructions -->
                    <?php if ($paymentMethod === 'paypal'): ?>
                        <div class="glass-strong rounded-2xl shadow-soft overflow-hidden">
                            <div class="p-6 md:p-8 border-b border-gray-100">
                                <div class="flex items-center gap-4 mb-6">
                                    <img src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-200px.png" alt="PayPal" class="h-8 w-auto">
                                    <h2 class="text-xl font-bold text-charcoal-900 font-display">PayPal Instructions</h2>
                                </div>
                                
                                <div class="space-y-6">
                                    <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
                                        <ol class="space-y-4">
                                            <li class="flex gap-4">
                                                <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-sm">1</span>
                                                <div>
                                                    <p class="font-medium text-charcoal-900">Click the payment button below</p>
                                                    <p class="text-sm text-gray-600 mt-1">You'll be redirected to our secure PayPal payment page.</p>
                                                </div>
                                            </li>
                                            <li class="flex gap-4">
                                                <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-sm">2</span>
                                                <div>
                                                    <p class="font-medium text-charcoal-900">Enter the exact amount</p>
                                                    <p class="text-sm text-gray-600 mt-1">Please send exactly <strong class="text-charcoal-900"><?php echo formatPrice($order['total'], $currency); ?></strong>.</p>
                                                </div>
                                            </li>
                                            <li class="flex gap-4">
                                                <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-sm">3</span>
                                                <div>
                                                    <p class="font-medium text-charcoal-900">Add your Order ID</p>
                                                    <p class="text-sm text-gray-600 mt-1">Include <strong class="font-mono text-charcoal-900"><?php echo htmlspecialchars($orderId); ?></strong> in the payment notes.</p>
                                                </div>
                                            </li>
                                        </ol>
                                    </div>
                                    
                                    <div class="flex flex-col sm:flex-row gap-4 pt-2">
                                        <a href="http://paypal.me/amp202247" target="_blank" class="flex-1 inline-flex items-center justify-center bg-[#0070BA] hover:bg-[#005ea6] text-white px-6 py-4 rounded-xl font-bold transition-all shadow-lg hover:shadow-blue-900/20 group">
                                            <span>Pay with PayPal</span>
                                            <svg class="w-5 h-5 ml-2 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                        <button onclick="confirmPayment()" class="flex-1 inline-flex items-center justify-center bg-white border-2 border-green-500 text-green-600 hover:bg-green-50 px-6 py-4 rounded-xl font-bold transition-all">
                                            I've Sent Payment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($paymentMethod === 'bank_transfer'): ?>
                        <div class="glass-strong rounded-2xl shadow-soft overflow-hidden">
                            <div class="p-6 md:p-8 border-b border-gray-100">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-charcoal-900">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    </div>
                                    <h2 class="text-xl font-bold text-charcoal-900 font-display">Bank Transfer Details</h2>
                                </div>
                                
                                <div class="space-y-6">
                                    <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Account Name</p>
                                                <p class="font-medium text-charcoal-900 select-all">Angel Marketplace Ltd</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Bank Name</p>
                                                <p class="font-medium text-charcoal-900 select-all">Example Bank</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Account Number</p>
                                                <p class="font-mono font-medium text-charcoal-900 select-all text-lg">12345678</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Sort Code</p>
                                                <p class="font-mono font-medium text-charcoal-900 select-all text-lg">12-34-56</p>
                                            </div>
                                            <div class="md:col-span-2 pt-4 border-t border-gray-200">
                                                <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Payment Reference (Important)</p>
                                                <p class="font-mono font-bold text-folly select-all text-xl"><?php echo htmlspecialchars($orderId); ?></p>
                                                <p class="text-xs text-gray-500 mt-1">Please use this reference so we can identify your payment.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button onclick="confirmPayment()" class="w-full inline-flex items-center justify-center bg-folly hover:bg-folly-600 text-white px-6 py-4 rounded-xl font-bold transition-all shadow-lg hover:shadow-folly/30">
                                        I've Completed The Transfer
                                    </button>
                                </div>
                            </div>
                        </div>
                    
                    <?php else: ?>
                        <div class="glass-strong rounded-2xl shadow-soft p-8 text-center">
                            <h2 class="text-xl font-bold text-charcoal-900 mb-4 font-display">Payment Instructions</h2>
                            <p class="text-gray-600 mb-6">Please contact our support team for payment instructions for this method.</p>
                            <button onclick="confirmPayment()" class="inline-flex items-center justify-center bg-folly hover:bg-folly-600 text-white px-8 py-3 rounded-xl font-bold transition-all">
                                Confirm Payment
                            </button>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Sidebar (Order Summary) -->
                <div class="lg:col-span-1">
                    <div class="glass-strong rounded-2xl shadow-soft p-6 sticky top-24">
                        <h3 class="text-lg font-bold text-charcoal-900 mb-4 font-display border-b border-gray-100 pb-4">Order Summary</h3>
                        
                        <div class="space-y-4 mb-6 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                            <?php foreach ($order['items'] as $item): ?>
                            <?php
                            $productName = $item['product_name'] ?? $item['product']['name'] ?? 'Product';
                            $quantity = $item['quantity'] ?? 1;
                            $itemTotal = $item['subtotal'] ?? $item['item_total'] ?? 0;
                            ?>
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-charcoal-900 line-clamp-2"><?php echo htmlspecialchars($productName); ?></p>
                                    <p class="text-xs text-gray-500 mt-0.5">Qty: <?php echo $quantity; ?></p>
                                </div>
                                <span class="text-sm font-semibold text-charcoal-900"><?php echo formatPrice($itemTotal, $currency); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="space-y-3 border-t border-gray-100 pt-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium text-charcoal-900"><?php echo formatPrice($order['subtotal'], $currency); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium text-charcoal-900"><?php echo formatPrice($order['shipping_cost'], $currency); ?></span>
                            </div>
                            <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-100 mt-2">
                                <span class="text-charcoal-900">Total</span>
                                <span class="text-folly"><?php echo formatPrice($order['total'], $currency); ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-6 bg-gray-50 rounded-xl p-4 text-center">
                            <p class="text-xs text-gray-500 mb-2">Need help with payment?</p>
                            <a href="<?php echo getBaseUrl('contact.php'); ?>" class="text-sm font-bold text-folly hover:text-folly-700">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<script>
function confirmPayment() {
    const paymentMethod = '<?php echo htmlspecialchars($paymentMethod); ?>';
    
    // Determine what information to collect based on payment method
    let inputHtml = '';
    
    if (paymentMethod === 'paypal') {
        inputHtml = `
            <div class="text-left space-y-4 mt-4">
                <div>
                    <label class="block text-sm font-bold text-charcoal-700 mb-1">PayPal Transaction ID <span class="text-folly">*</span></label>
                    <input type="text" id="transaction_id" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-transparent" placeholder="e.g., 1AB23456CD789012E" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-charcoal-700 mb-1">PayPal Email Used</label>
                    <input type="email" id="payment_email" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-transparent" placeholder="your.email@example.com">
                </div>
                <div>
                    <label class="block text-sm font-bold text-charcoal-700 mb-1">Amount Sent</label>
                    <input type="text" id="amount_sent" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl bg-gray-50 text-gray-500" value="<?php echo formatPrice($order['total'], $currency); ?>" readonly>
                </div>
            </div>
        `;
    } else if (paymentMethod === 'bank_transfer') {
        inputHtml = `
            <div class="text-left space-y-4 mt-4">
                <div>
                    <label class="block text-sm font-bold text-charcoal-700 mb-1">Bank Transaction Reference <span class="text-folly">*</span></label>
                    <input type="text" id="transaction_id" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-transparent" placeholder="e.g., REF123456789" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-charcoal-700 mb-1">Bank Name</label>
                        <input type="text" id="bank_name" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-transparent" placeholder="Your bank">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-charcoal-700 mb-1">Account Name</label>
                        <input type="text" id="account_holder" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-transparent" placeholder="Account holder">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-charcoal-700 mb-1">Transfer Date</label>
                    <input type="date" id="transfer_date" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-transparent" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
        `;
    } else {
        inputHtml = `
            <div class="text-left space-y-4 mt-4">
                <div>
                    <label class="block text-sm font-bold text-charcoal-700 mb-1">Payment Reference <span class="text-folly">*</span></label>
                    <input type="text" id="transaction_id" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-transparent" placeholder="Enter your transaction reference" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-charcoal-700 mb-1">Notes (Optional)</label>
                    <textarea id="payment_notes" class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-transparent" placeholder="Any additional information..." rows="2"></textarea>
                </div>
            </div>
        `;
    }
    
    Swal.fire({
        title: 'Confirm Payment',
        html: `
            <p class="text-gray-600 mb-4 text-sm">Please provide your payment details so we can verify your transaction.</p>
            ${inputHtml}
        `,
        showCancelButton: true,
        confirmButtonColor: '#FF0055',
        cancelButtonColor: '#9CA3AF',
        confirmButtonText: 'Submit Confirmation',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        width: '600px',
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl px-6 py-3 font-bold',
            cancelButton: 'rounded-xl px-6 py-3 font-bold'
        },
        preConfirm: () => {
            const transactionId = document.getElementById('transaction_id')?.value;
            
            if (!transactionId || transactionId.trim() === '') {
                Swal.showValidationMessage('Please enter a transaction reference/ID');
                return false;
            }
            
            return {
                transaction_id: transactionId,
                payment_email: document.getElementById('payment_email')?.value || '',
                bank_name: document.getElementById('bank_name')?.value || '',
                account_holder: document.getElementById('account_holder')?.value || '',
                transfer_date: document.getElementById('transfer_date')?.value || '',
                amount_sent: document.getElementById('amount_sent')?.value || '',
                payment_notes: document.getElementById('payment_notes')?.value || ''
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const paymentDetails = result.value;
            
            Swal.fire({
                title: 'Processing...',
                text: 'Recording your payment details',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo getBaseUrl('confirm-payment.php?order=' . urlencode($orderId)); ?>';
            
            for (const [key, value] of Object.entries(paymentDetails)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
