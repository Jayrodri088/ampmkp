<?php
$page_title = 'My account';
$page_description = 'Manage your Angel Marketplace account.';

require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isCustomerLoggedIn()) {
    header('Location: ' . getBaseUrl('login.php') . '?redirect=account/');
    exit;
}

$customerEmail = getLoggedInCustomerEmail();
$account = getAccountByEmail($customerEmail);

include __DIR__ . '/../includes/header.php';
?>

<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">My account</span>
        </nav>
    </div>
</div>

<section class="py-12 md:py-16 bg-gradient-to-b from-gray-50 to-white">
    <div class="container mx-auto px-4 max-w-2xl">
        <div class="glass-strong rounded-2xl shadow-xl p-6 md:p-8">
            <h1 class="text-2xl font-bold text-charcoal-900 mb-6 font-display">My account</h1>
            <div class="space-y-4 mb-8">
                <p class="text-gray-600"><strong>Email:</strong> <?php echo htmlspecialchars($customerEmail); ?></p>
                <?php if (!empty($account['name'])): ?>
                    <p class="text-gray-600"><strong>Name:</strong> <?php echo htmlspecialchars($account['name']); ?></p>
                <?php endif; ?>
            </div>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="<?php echo getBaseUrl('account/orders.php'); ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-folly text-white font-bold rounded-xl hover:bg-folly-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"></path></svg>
                    My orders
                </a>
                <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white border border-gray-200 text-charcoal-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    Continue shopping
                </a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
