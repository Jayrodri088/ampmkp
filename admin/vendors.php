<?php
session_start();

// Simple authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/auth.php');
    exit;
}

require_once '../includes/functions.php';

// CSRF token setup
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = '';

// Handle actions: approve, reject, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (empty($postedToken) || !hash_equals($_SESSION['admin_csrf_token'], $postedToken)) {
        http_response_code(403);
        $message = 'Invalid CSRF token.';
        $message_type = 'danger';
    } else {
        $vendors = readJsonFile('vendors.json');
        $id = (int)($_POST['id'] ?? 0);
        $index = -1;
        foreach ($vendors as $i => $v) {
            if ((int)$v['id'] === $id) { $index = $i; break; }
        }
        if ($index === -1) {
            $message = 'Vendor not found.';
            $message_type = 'danger';
        } else {
            $action = $_POST['action'] ?? '';
            if ($action === 'approve') {
                $vendors[$index]['status'] = 'approved';
                $vendors[$index]['updated_at'] = date('Y-m-d H:i:s');
                writeJsonFile('vendors.json', $vendors);
                $message = 'Vendor application approved.';
                $message_type = 'success';
            } elseif ($action === 'reject') {
                $vendors[$index]['status'] = 'rejected';
                $vendors[$index]['updated_at'] = date('Y-m-d H:i:s');
                $vendors[$index]['rejection_reason'] = sanitizeInput($_POST['reason'] ?? '');
                writeJsonFile('vendors.json', $vendors);
                $message = 'Vendor application rejected.';
                $message_type = 'success';
            } elseif ($action === 'delete') {
                array_splice($vendors, $index, 1);
                writeJsonFile('vendors.json', $vendors);
                $message = 'Vendor application deleted.';
                $message_type = 'success';
            }
        }
    }
}

// Load vendors
$vendors = readJsonFile('vendors.json');

// Stats
$stats = [
    'total' => count($vendors),
    'pending' => count(array_filter($vendors, fn($v) => ($v['status'] ?? 'pending') === 'pending')),
    'approved' => count(array_filter($vendors, fn($v) => ($v['status'] ?? '') === 'approved')),
    'rejected' => count(array_filter($vendors, fn($v) => ($v['status'] ?? '') === 'rejected')),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vendors Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">
<div class="flex min-h-screen">
    <!-- Sidebar include -->
    <div class="hidden lg:block w-64 bg-charcoal text-white flex-shrink-0">
        <div class="p-6 border-b border-charcoal-500">
            <h2 class="text-xl font-bold flex items-center">
                <i class="bi bi-shield-check mr-3 text-folly"></i> Admin Panel
            </h2>
            <p class="text-charcoal-200 text-sm mt-2">Welcome, <?= htmlspecialchars($_SESSION['admin_user']) ?></p>
        </div>
        <nav class="p-4 space-y-1">
            <?php $activePage = 'vendors'; include __DIR__ . '/partials/nav_links_desktop.php'; ?>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-xl lg:text-2xl font-bold text-charcoal">Vendors Management</h1>
                <div class="text-charcoal-400 text-sm"><i class="bi bi-calendar3 mr-2"></i><?= date('M j, Y') ?></div>
            </div>
        </div>

        <div class="flex-1 p-4 lg:p-6 overflow-auto">
            <?php if ($message): ?>
            <div class="<?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?> border p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="bi bi-<?= $message_type === 'success' ? 'check-circle' : 'x-circle' ?> mr-2"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white border border-gray-200 p-5 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-sm text-charcoal-600">Total Applications</h3>
                            <p class="text-2xl font-bold text-charcoal mt-1"><?= $stats['total'] ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center"><i class="bi bi-people text-blue-600 text-2xl"></i></div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 p-5 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-sm text-charcoal-600">Pending</h3>
                            <p class="text-2xl font-bold text-charcoal mt-1"><?= $stats['pending'] ?></p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center"><i class="bi bi-hourglass-split text-yellow-600 text-2xl"></i></div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 p-5 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-sm text-charcoal-600">Approved</h3>
                            <p class="text-2xl font-bold text-charcoal mt-1"><?= $stats['approved'] ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center"><i class="bi bi-check2-all text-green-600 text-2xl"></i></div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 p-5 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-sm text-charcoal-600">Rejected</h3>
                            <p class="text-2xl font-bold text-charcoal mt-1"><?= $stats['rejected'] ?></p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center"><i class="bi bi-x-octagon text-red-600 text-2xl"></i></div>
                    </div>
                </div>
            </div>

            <!-- List -->
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-charcoal">Vendor Applications</h3>
                    <span class="px-3 py-1 bg-gray-100 rounded-full text-sm text-charcoal-600"><?= $stats['total'] ?> total</span>
                </div>

                <?php if (empty($vendors)): ?>
                    <div class="px-6 py-10 text-center text-charcoal-400">
                        <i class="bi bi-people text-4xl mb-2 block"></i>
                        <p>No vendor applications yet.</p>
                    </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Vendor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Products</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-charcoal-600 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($vendors as $v): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-charcoal"><?php echo htmlspecialchars($v['business']['name'] ?? ''); ?></div>
                                    <div class="text-sm text-charcoal-400"><?php echo htmlspecialchars($v['business']['type'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-charcoal"><?php echo htmlspecialchars($v['contact']['name'] ?? ''); ?></div>
                                    <div class="text-xs text-charcoal-500 truncate"><?php echo htmlspecialchars(($v['contact']['email'] ?? '') . ' · ' . ($v['contact']['phone'] ?? '')); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-charcoal"><?php echo count($v['products'] ?? []); ?> items</div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php $s = $v['status'] ?? 'pending'; ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $s === 'approved' ? 'bg-green-100 text-green-800' : ($s === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo ucfirst($s); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-charcoal-600"><?php echo htmlspecialchars($v['created_at'] ?? ''); ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <?php if (($v['status'] ?? 'pending') === 'pending'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                                            <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="px-3 py-1 bg-green-100 text-green-700 hover:bg-green-200 text-sm rounded">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                                            <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="reason" value="Admin rejected">
                                            <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 text-sm rounded">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this application?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_csrf_token']) ?>">
                                            <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="px-3 py-1 bg-gray-100 text-charcoal-700 hover:bg-gray-200 text-sm rounded">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <!-- Expandable details row -->
                            <tr class="bg-gray-50/50">
                                <td colspan="6" class="px-6 py-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <h4 class="font-semibold text-sm text-charcoal mb-2">Business</h4>
                                            <p class="text-sm text-charcoal-700"><?php echo nl2br(htmlspecialchars($v['business']['description'] ?? '')); ?></p>
                                            <?php if (!empty($v['business']['website'])): ?>
                                                <p class="text-sm mt-2"><a href="<?php echo htmlspecialchars($v['business']['website']); ?>" target="_blank" class="text-folly hover:text-folly-600">Website</a></p>
                                            <?php endif; ?>
                                            <?php if (!empty($v['business']['license']) || !empty($v['business']['tax_id'])): ?>
                                            <p class="text-xs text-charcoal-500 mt-2">License: <?php echo htmlspecialchars($v['business']['license'] ?? '—'); ?> · Tax ID: <?php echo htmlspecialchars($v['business']['tax_id'] ?? '—'); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-sm text-charcoal mb-2">Products</h4>
                                            <?php if (empty($v['products'])): ?>
                                                <p class="text-sm text-charcoal-500">No products provided.</p>
                                            <?php else: ?>
                                                <ul class="text-sm space-y-1 list-disc pl-5">
                                                    <?php foreach ($v['products'] as $p): ?>
                                                        <li><?php echo htmlspecialchars($p['name']); ?> — <?php echo htmlspecialchars($p['category']); ?> — £<?php echo number_format((float)($p['price'] ?? 0), 2); ?> (<?php echo (int)($p['stock'] ?? 0); ?>)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-sm text-charcoal mb-2">Uploads</h4>
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach (($v['uploads'] ?? []) as $img): ?>
                                                    <img src="<?php echo getAssetUrl('images/' . $img); ?>" alt="Upload" class="w-20 h-14 object-cover rounded border" onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/products/placeholder.jpg'); ?>'">
                                                <?php endforeach; ?>
                                                <?php if (empty($v['uploads'])): ?>
                                                    <p class="text-sm text-charcoal-500">No uploads.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    :root { --folly: #FF0055; --charcoal: #3B4255; }
    .bg-folly { background-color: var(--folly); }
    .bg-charcoal { background-color: var(--charcoal); }
    .text-charcoal { color: var(--charcoal); }
    .text-folly { color: var(--folly); }
</style>

</body>
</html>


