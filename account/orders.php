<?php
$page_title = 'My orders';
$page_description = 'View your order history at Angel Marketplace.';

require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isCustomerLoggedIn()) {
    header('Location: ' . getBaseUrl('login.php') . '?redirect=account/orders.php');
    exit;
}

$customerEmail = getLoggedInCustomerEmail();
$orders = getOrdersForCustomer($customerEmail);
$settings = getSettings();
$defaultCurrency = $settings['currency_code'] ?? 'GBP';

include __DIR__ . '/../includes/header.php';
?>

<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <a href="<?php echo getBaseUrl('account/'); ?>" class="text-gray-500 hover:text-folly transition-colors">My account</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">My orders</span>
        </nav>
    </div>
</div>

<section class="py-12 md:py-16 bg-gradient-to-b from-gray-50 to-white min-h-screen">
    <div class="container mx-auto px-4">
        <h1 class="text-2xl md:text-3xl font-bold text-charcoal-900 mb-8 font-display">My orders</h1>

        <?php if (empty($orders)): ?>
            <div class="glass-strong rounded-2xl shadow-soft p-8 md:p-12 text-center">
                <p class="text-gray-600 mb-6">You haven't placed any orders yet.</p>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-folly text-white font-bold rounded-xl hover:bg-folly-600 transition-colors">
                    Start shopping
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6 max-w-4xl">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $orderId = $order['id'] ?? '';
                    $orderDate = $order['date'] ?? $order['created_at'] ?? '';
                    $status = $order['status'] ?? 'pending';
                    $total = $order['total'] ?? 0;
                    $currency = $order['currency'] ?? $order['currency_code'] ?? $defaultCurrency;
                    $itemCount = 0;
                    if (!empty($order['items']) && is_array($order['items'])) {
                        foreach ($order['items'] as $it) {
                            $itemCount += (int)($it['quantity'] ?? 1);
                        }
                    }
                    ?>
                    <div class="glass-strong rounded-2xl shadow-soft overflow-hidden">
                        <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h2 class="font-bold text-charcoal-900 font-mono">#<?php echo htmlspecialchars($orderId); ?></h2>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?php echo date('F j, Y', strtotime($orderDate)); ?>
                                    &middot; <?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?>
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    <?php
                                    if ($status === 'completed' || $status === 'shipped') echo 'bg-green-100 text-green-800';
                                    elseif ($status === 'processing') echo 'bg-blue-100 text-blue-800';
                                    elseif ($status === 'cancelled' || $status === 'refunded') echo 'bg-gray-100 text-gray-800';
                                    else echo 'bg-amber-100 text-amber-800';
                                    ?>
                                ">
                                    <?php echo htmlspecialchars(ucfirst($status)); ?>
                                </span>
                                <span class="font-bold text-charcoal-900"><?php echo formatPriceWithCurrency($total, $currency); ?></span>
                                <a href="<?php echo getBaseUrl('order-success.php?order=' . urlencode($orderId)); ?>" class="px-4 py-2 bg-folly text-white text-sm font-semibold rounded-lg hover:bg-folly-600 transition-colors">
                                    View order
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
