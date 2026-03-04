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
$saveMessage = '';
$saveError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');

    if ($action === 'save_profile') {
        $payload = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'countryCode' => sanitizeInput($_POST['country_code'] ?? '+44'),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'city' => sanitizeInput($_POST['city'] ?? ''),
            'postal_code' => sanitizeInput($_POST['postal_code'] ?? ''),
            'country' => sanitizeInput($_POST['country'] ?? ''),
            'profile_id' => sanitizeInput($_POST['profile_id'] ?? ''),
            'profile_label' => sanitizeInput($_POST['profile_label'] ?? ''),
            'set_default_profile' => isset($_POST['set_default_profile']) ? 1 : 0,
        ];

        if (saveCheckoutProfileForEmail($customerEmail, $payload)) {
            $saveMessage = $payload['profile_id'] !== ''
                ? 'Address updated successfully.'
                : 'Address saved. You can now select it during checkout.';
        } else {
            $saveError = 'Could not save your address right now. Please try again.';
        }
    }

    if ($action === 'set_default_profile') {
        $profileId = sanitizeInput($_POST['profile_id'] ?? '');
        if ($profileId !== '' && function_exists('setDefaultCheckoutProfileForEmail') && setDefaultCheckoutProfileForEmail($customerEmail, $profileId)) {
            $saveMessage = 'Default checkout address updated.';
        } else {
            $saveError = 'Could not update default address.';
        }
    }

    if ($action === 'delete_profile') {
        $profileId = sanitizeInput($_POST['profile_id'] ?? '');
        if ($profileId !== '' && function_exists('deleteCheckoutProfileForEmail') && deleteCheckoutProfileForEmail($customerEmail, $profileId)) {
            $saveMessage = 'Address removed.';
        } else {
            $saveError = 'Could not remove this address.';
        }
    }

    $account = getAccountByEmail($customerEmail);
}

$savedCheckoutProfiles = function_exists('getSavedCheckoutProfiles') ? getSavedCheckoutProfiles($customerEmail) : [];
$defaultAddress = getDefaultAddressForAccount($customerEmail) ?? [];
$accountName = trim((string)($account['name'] ?? ''));
$nameParts = $accountName !== '' ? explode(' ', $accountName, 2) : ['', ''];

$editProfileId = sanitizeInput($_GET['edit'] ?? '');
$editingProfile = null;
if ($editProfileId !== '') {
    foreach ($savedCheckoutProfiles as $profile) {
        if (($profile['id'] ?? '') === $editProfileId) {
            $editingProfile = $profile;
            break;
        }
    }
}

$formProfileId = (string)($editingProfile['id'] ?? '');
$formFirstName = (string)($editingProfile['first_name'] ?? ($nameParts[0] ?? ''));
$formLastName = (string)($editingProfile['last_name'] ?? ($nameParts[1] ?? ''));
$formCountryCode = (string)($editingProfile['countryCode'] ?? ($account['country_code'] ?? '+44'));
$formPhone = (string)($editingProfile['phone'] ?? ($account['phone'] ?? ''));
$formAddress = (string)($editingProfile['address'] ?? ($defaultAddress['line1'] ?? ''));
$formCity = (string)($editingProfile['city'] ?? ($defaultAddress['city'] ?? ''));
$formPostal = (string)($editingProfile['postal_code'] ?? ($defaultAddress['postcode'] ?? ''));
$formCountry = (string)($editingProfile['country'] ?? ($defaultAddress['country'] ?? 'GB'));
$formLabel = (string)($editingProfile['label'] ?? '');

$hasAnyDefault = false;
foreach ($savedCheckoutProfiles as $profile) {
    if (!empty($profile['is_default'])) {
        $hasAnyDefault = true;
        break;
    }
}
$defaultChecked = $editingProfile
    ? !empty($editingProfile['is_default'])
    : !$hasAnyDefault;

$countryCodeOptions = [
    '+44' => 'UK (+44)',
    '+1' => 'US/CA (+1)',
    '+234' => 'Nigeria (+234)',
];
$countryOptions = [
    'GB' => 'United Kingdom',
    'US' => 'United States',
    'CA' => 'Canada',
    'NG' => 'Nigeria',
];

include __DIR__ . '/../includes/header.php';
?>

<div class="bg-white/70 backdrop-blur border-b border-white/40 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center gap-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">My account</span>
        </nav>
    </div>
</div>

<section class="relative py-10 md:py-14 bg-gradient-to-b from-gray-50 via-white to-gray-50">
    <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-r from-folly/10 via-transparent to-folly/5 pointer-events-none"></div>

    <div class="container mx-auto px-4 max-w-6xl relative">
        <?php if ($saveMessage): ?>
            <div class="mb-6 p-4 rounded-2xl border border-green-200 bg-green-50 text-green-700 text-sm font-medium">
                <?php echo htmlspecialchars($saveMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($saveError): ?>
            <div class="mb-6 p-4 rounded-2xl border border-red-200 bg-red-50 text-red-700 text-sm font-medium">
                <?php echo htmlspecialchars($saveError); ?>
            </div>
        <?php endif; ?>

        <div class="mb-8 p-6 md:p-8 rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="min-w-0">
                    <p class="text-xs uppercase tracking-[0.2em] text-folly font-semibold mb-2">Account overview</p>
                    <h1 class="text-3xl md:text-4xl font-display font-bold text-charcoal-900 leading-tight">My account</h1>
                    <div class="mt-3 text-sm text-gray-600 space-y-1">
                        <p><span class="font-semibold text-charcoal-900">Email:</span> <?php echo htmlspecialchars($customerEmail); ?></p>
                        <?php if ($accountName !== ''): ?>
                            <p><span class="font-semibold text-charcoal-900">Name:</span> <?php echo htmlspecialchars($accountName); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <a href="<?php echo getBaseUrl('account/orders.php'); ?>" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-folly text-white font-semibold hover:bg-folly-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"></path></svg>
                        My orders
                    </a>
                    <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center justify-center px-5 py-3 rounded-xl border border-gray-200 text-charcoal-700 font-semibold hover:bg-gray-50 transition-colors">
                        Continue shopping
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <div class="lg:col-span-2 order-2 lg:order-1">
                <div class="rounded-3xl border border-gray-200 bg-white p-5 md:p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3 mb-5">
                        <h2 class="text-lg font-bold text-charcoal-900">Saved addresses</h2>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 font-semibold"><?php echo count($savedCheckoutProfiles); ?></span>
                    </div>

                    <?php if (!empty($savedCheckoutProfiles)): ?>
                        <div class="space-y-4">
                            <?php foreach ($savedCheckoutProfiles as $profile): ?>
                                <?php
                                    $profileId = (string)($profile['id'] ?? '');
                                    $isDefault = !empty($profile['is_default']);
                                    $isEditing = ($profileId !== '' && $profileId === $editProfileId);
                                ?>
                                <article class="rounded-2xl border <?php echo $isEditing ? 'border-folly/60 bg-folly/5' : 'border-gray-200 bg-gray-50/70'; ?> p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold text-charcoal-900 leading-tight"><?php echo htmlspecialchars((string)($profile['label'] ?? 'Saved address')); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                                                <?php echo htmlspecialchars(trim((string)($profile['address'] ?? ''))); ?><br>
                                                <?php echo htmlspecialchars(trim((string)($profile['city'] ?? ''))); ?>, <?php echo htmlspecialchars(trim((string)($profile['postal_code'] ?? ''))); ?><br>
                                                <?php echo htmlspecialchars(trim((string)($countryOptions[(string)($profile['country'] ?? '')] ?? (string)($profile['country'] ?? '')))); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-2"><?php echo htmlspecialchars((string)($profile['countryCode'] ?? '')); ?> <?php echo htmlspecialchars((string)($profile['phone'] ?? '')); ?></p>
                                        </div>
                                        <?php if ($isDefault): ?>
                                            <span class="shrink-0 text-[11px] px-2.5 py-1 rounded-full bg-folly/10 text-folly font-semibold">Default</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="<?php echo getBaseUrl('account/index.php?edit=' . urlencode($profileId) . '#address-form'); ?>" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-charcoal-700 bg-white hover:bg-gray-50 transition-colors">Edit</a>

                                        <?php if (!$isDefault): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="set_default_profile">
                                                <input type="hidden" name="profile_id" value="<?php echo htmlspecialchars($profileId); ?>">
                                                <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-charcoal-700 bg-white hover:bg-gray-50 transition-colors">Set default</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this saved address?');">
                                            <input type="hidden" name="action" value="delete_profile">
                                            <input type="hidden" name="profile_id" value="<?php echo htmlspecialchars($profileId); ?>">
                                            <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-red-200 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-colors">Delete</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-5 text-sm text-gray-600">
                            No saved checkout addresses yet. Add your first address using the form.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-3 order-1 lg:order-2" id="address-form">
                <div class="rounded-3xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                        <h2 class="text-xl font-bold text-charcoal-900"><?php echo $editingProfile ? 'Edit address' : 'Add new address'; ?></h2>
                        <?php if ($editingProfile): ?>
                            <a href="<?php echo getBaseUrl('account/'); ?>" class="text-sm font-semibold text-folly hover:text-folly-600">Cancel editing</a>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="save_profile">
                        <input type="hidden" name="profile_id" value="<?php echo htmlspecialchars($formProfileId); ?>">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-charcoal-700 mb-2">First name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($formFirstName); ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-folly/30 focus:border-folly transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-charcoal-700 mb-2">Last name</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($formLastName); ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-folly/30 focus:border-folly transition-all">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-charcoal-700 mb-2">Country code</label>
                                <select name="country_code" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-folly/30 focus:border-folly transition-all">
                                    <?php foreach ($countryCodeOptions as $code => $label): ?>
                                        <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $formCountryCode === $code ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-charcoal-700 mb-2">Phone</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($formPhone); ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-folly/30 focus:border-folly transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-charcoal-700 mb-2">Address label</label>
                            <input type="text" name="profile_label" value="<?php echo htmlspecialchars($formLabel); ?>" placeholder="Home, Office, Family house" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-folly/30 focus:border-folly transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-charcoal-700 mb-2">Street address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($formAddress); ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-folly/30 focus:border-folly transition-all">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-charcoal-700 mb-2">City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($formCity); ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-folly/30 focus:border-folly transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-charcoal-700 mb-2">Postal code</label>
                                <input type="text" name="postal_code" value="<?php echo htmlspecialchars($formPostal); ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-folly/30 focus:border-folly transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-charcoal-700 mb-2">Country</label>
                                <select name="country" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-folly/30 focus:border-folly transition-all">
                                    <?php foreach ($countryOptions as $code => $label): ?>
                                        <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $formCountry === $code ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm text-charcoal-700">
                            <input type="checkbox" name="set_default_profile" value="1" <?php echo $defaultChecked ? 'checked' : ''; ?> class="rounded border-gray-300 text-folly focus:ring-folly/30">
                            Set as default checkout address
                        </label>

                        <div class="pt-2 flex flex-col sm:flex-row gap-3">
                            <button type="submit" class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-charcoal-900 text-white font-semibold hover:bg-folly transition-colors">
                                <?php echo $editingProfile ? 'Update address' : 'Save address'; ?>
                            </button>
                            <?php if ($editingProfile): ?>
                                <a href="<?php echo getBaseUrl('account/'); ?>" class="inline-flex items-center justify-center px-6 py-3 rounded-xl border border-gray-200 text-charcoal-700 font-semibold hover:bg-gray-50 transition-colors">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
