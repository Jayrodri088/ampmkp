<?php
$page_title = 'Seraph Toothpaste';
$page_description = 'Discover Seraph Oral - the revolutionary natural toothpaste. Become a distributor and join our network of partners bringing premium oral care to communities worldwide.';

require_once 'includes/functions.php';
require_once 'includes/bot_protection.php';

$success = false;
$error = '';

// Initialize form variables
$name = '';
$email = '';
$phone = '';
$location = '';
$reason = '';

// Initialize bot protection
$botProtection = new BotProtection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $countryCode = sanitizeInput($_POST['countryCode'] ?? '+44');
    $location = sanitizeInput($_POST['location'] ?? '');
    $reason = sanitizeInput($_POST['reason'] ?? '');

    // Combine country code with phone
    $fullPhone = $countryCode . ' ' . $phone;

    // Bot protection validation
    $botValidation = $botProtection->validateSubmission('seraph_distributor', $_POST);

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($location)) {
        $error = 'Please fill in all required fields.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!$botValidation['valid']) {
        $error = 'Security validation failed. Please try again.';
        $botProtection->logSuspiciousActivity('seraph_distributor_form', $_POST, $botValidation['errors']);
    } else {
        // Save distributor application data
        $applicationData = [
            'id' => time(),
            'name' => $name,
            'email' => $email,
            'phone' => $fullPhone,
            'location' => $location,
            'reason' => $reason,
            'submitted_at' => date('Y-m-d H:i:s'),
            'status' => 'new'
        ];

        // Read existing applications
        $applications = readJsonFile('seraph_distributors.json');
        if (!is_array($applications)) {
            $applications = [];
        }
        $applications[] = $applicationData;

        // Save to file
        if (writeJsonFile('seraph_distributors.json', $applications)) {
            header('Location: seraph.php?success=1');
            exit;
        } else {
            $error = 'Sorry, there was an error submitting your application. Please try again.';
        }
    }
}

// Check for success parameter from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
    $name = '';
    $email = '';
    $phone = '';
    $location = '';
    $reason = '';
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">Seraph Toothpaste</span>
        </nav>
    </div>
</div>

<!-- Hero Section -->
<section class="relative bg-charcoal-900 py-12 sm:py-16 md:py-24 overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://seraph-oral.org/public/assets/images/img1.jpeg')] bg-cover bg-center opacity-10"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-charcoal-900 via-charcoal-900/95 to-charcoal-900/80"></div>

    <div class="relative container mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <!-- Text Content -->
            <div class="text-center lg:text-left">
                <p class="text-folly font-semibold text-sm uppercase tracking-wider mb-3">Seraph Oral Care</p>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4 font-display">
                    Seraph Toothpaste
                </h1>
                <p class="text-gray-200 text-base sm:text-lg mb-6 max-w-lg">
                    Premium natural oral care for the whole family. Experience the power of nature with our revolutionary toothpaste formula.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start mb-8">
                    <a href="#become-distributor" class="inline-flex items-center justify-center gap-2 bg-folly hover:bg-folly-600 text-white px-6 py-3 rounded-xl font-bold transition-colors">
                        Become a Distributor
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                    <a href="#about-seraph" class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-xl font-bold transition-colors border border-white/20">
                        Learn More
                    </a>
                </div>

                <div class="flex flex-wrap gap-x-6 gap-y-2 justify-center lg:justify-start text-sm text-gray-400">
                    <span>Natural Ingredients</span>
                    <span>Fluoride-Free Options</span>
                    <span>Family Friendly</span>
                </div>
            </div>

            <!-- Product Image -->
            <div class="flex justify-center lg:justify-end">
                <img src="https://seraph-oral.org/public/assets/images/img2.jpeg"
                     alt="Seraph Toothpaste"
                     class="w-64 sm:w-72 md:w-80 h-auto rounded-2xl shadow-2xl">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="about-seraph" class="py-10 sm:py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center max-w-3xl mx-auto mb-8 sm:mb-16">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-charcoal-900 mb-3 sm:mb-4 font-display">Why Choose Seraph?</h2>
            <div class="w-16 sm:w-20 h-1 bg-folly mx-auto rounded-full mb-4 sm:mb-6"></div>
            <p class="text-charcoal-600 text-sm sm:text-lg px-2">Seraph Toothpaste is formulated with premium natural ingredients for a healthier, brighter smile.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8">
            <!-- Feature 1 -->
            <div class="text-center group">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-folly-50 text-folly rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-6 group-hover:bg-folly group-hover:text-white transition-all duration-300 shadow-lg">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                </div>
                <h3 class="text-base sm:text-xl font-bold text-charcoal-900 mb-2 sm:mb-3">Natural Ingredients</h3>
                <p class="text-charcoal-600 text-xs sm:text-base">Made with carefully selected natural ingredients that are gentle yet effective.</p>
            </div>

            <!-- Feature 2 -->
            <div class="text-center group">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-tangerine-50 text-tangerine rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-6 group-hover:bg-tangerine group-hover:text-white transition-all duration-300 shadow-lg">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <h3 class="text-base sm:text-xl font-bold text-charcoal-900 mb-2 sm:mb-3">Cavity Protection</h3>
                <p class="text-charcoal-600 text-xs sm:text-base">Advanced formula that helps protect against cavities and tooth decay.</p>
            </div>

            <!-- Feature 3 -->
            <div class="text-center group">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-purple-50 text-purple-600 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-6 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300 shadow-lg">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-base sm:text-xl font-bold text-charcoal-900 mb-2 sm:mb-3">Family Safe</h3>
                <p class="text-charcoal-600 text-xs sm:text-base">Safe for the whole family, including children. No harsh chemicals.</p>
            </div>

            <!-- Feature 4 -->
            <div class="text-center group">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-green-50 text-green-600 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-6 group-hover:bg-green-600 group-hover:text-white transition-all duration-300 shadow-lg">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                </div>
                <h3 class="text-base sm:text-xl font-bold text-charcoal-900 mb-2 sm:mb-3">Fresh Breath</h3>
                <p class="text-charcoal-600 text-xs sm:text-base">Long-lasting fresh breath that keeps you confident all day long.</p>
            </div>
        </div>
    </div>
</section>

<!-- Product Showcase -->
<section class="py-10 sm:py-16 md:py-24 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <!-- Image -->
            <div class="order-2 lg:order-1">
                <img src="https://seraph-oral.org/public/assets/images/img6.jpeg"
                     alt="Seraph Toothpaste Product"
                     class="w-full rounded-2xl sm:rounded-3xl shadow-xl sm:shadow-2xl">
            </div>

            <!-- Content -->
            <div class="order-1 lg:order-2 space-y-4 sm:space-y-6">
                <span class="inline-block py-1.5 sm:py-2 px-3 sm:px-4 rounded-full bg-folly-50 text-folly text-xs sm:text-sm font-bold">
                    Available Now
                </span>
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-charcoal-900 font-display">
                    Experience Premium Oral Care
                </h2>
                <p class="text-charcoal-600 text-sm sm:text-lg leading-relaxed">
                    Seraph Toothpaste combines ancient wisdom with modern science to deliver a toothpaste that not only cleans but also nourishes your teeth and gums.
                </p>

                <ul class="space-y-3 sm:space-y-4">
                    <li class="flex items-start gap-2 sm:gap-3">
                        <div class="w-5 h-5 sm:w-6 sm:h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-charcoal-700 text-sm sm:text-base">Natural whitening agents for a brighter smile</span>
                    </li>
                    <li class="flex items-start gap-2 sm:gap-3">
                        <div class="w-5 h-5 sm:w-6 sm:h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-charcoal-700 text-sm sm:text-base">Herbal extracts for gum health</span>
                    </li>
                    <li class="flex items-start gap-2 sm:gap-3">
                        <div class="w-5 h-5 sm:w-6 sm:h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-charcoal-700 text-sm sm:text-base">No artificial colors or preservatives</span>
                    </li>
                    <li class="flex items-start gap-2 sm:gap-3">
                        <div class="w-5 h-5 sm:w-6 sm:h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-charcoal-700 text-sm sm:text-base">Refreshing mint flavor</span>
                    </li>
                </ul>

                <div class="pt-4">
                    <a href="https://seraph-oral.org" target="_blank" class="inline-flex items-center gap-2 text-folly hover:text-folly-600 font-semibold">
                        Visit seraph-oral.org
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Become a Distributor Section -->
<section id="become-distributor" class="py-10 sm:py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-20">
                <!-- Info -->
                <div class="space-y-6 sm:space-y-8">
                    <div>
                        <span class="inline-block py-1.5 sm:py-2 px-3 sm:px-4 rounded-full bg-tangerine-50 text-tangerine text-xs sm:text-sm font-bold mb-3 sm:mb-4">
                            Join Our Network
                        </span>
                        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-charcoal-900 mb-3 sm:mb-4 font-display">
                            Become a Distributor
                        </h2>
                        <p class="text-charcoal-600 text-sm sm:text-lg leading-relaxed">
                            Partner with Seraph Oral to bring premium natural oral care to your community. As a distributor, you'll be part of a growing network dedicated to better oral health.
                        </p>
                    </div>

                    <div class="space-y-4 sm:space-y-6">
                        <div class="flex items-start gap-3 sm:gap-4 p-4 sm:p-5 bg-gray-50 rounded-xl sm:rounded-2xl border border-gray-100">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-folly-50 rounded-lg sm:rounded-xl flex items-center justify-center flex-shrink-0 text-folly">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-charcoal-900 mb-1 text-sm sm:text-base">Competitive Margins</h3>
                                <p class="text-charcoal-600 text-xs sm:text-sm">Enjoy attractive profit margins on every product you distribute.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 sm:gap-4 p-4 sm:p-5 bg-gray-50 rounded-xl sm:rounded-2xl border border-gray-100">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-tangerine-50 rounded-lg sm:rounded-xl flex items-center justify-center flex-shrink-0 text-tangerine">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-charcoal-900 mb-1 text-sm sm:text-base">Exclusive Territory</h3>
                                <p class="text-charcoal-600 text-xs sm:text-sm">Get exclusive distribution rights in your local area.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 sm:gap-4 p-4 sm:p-5 bg-gray-50 rounded-xl sm:rounded-2xl border border-gray-100">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-50 rounded-lg sm:rounded-xl flex items-center justify-center flex-shrink-0 text-purple-600">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-charcoal-900 mb-1 text-sm sm:text-base">Marketing Support</h3>
                                <p class="text-charcoal-600 text-xs sm:text-sm">Receive marketing materials and ongoing support from our team.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="bg-gradient-to-br from-charcoal-900 to-charcoal-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 text-white">
                        <h3 class="font-bold text-base sm:text-lg mb-3 sm:mb-4">Have Questions?</h3>
                        <div class="space-y-2 sm:space-y-3">
                            <a href="https://seraph-oral.org" target="_blank" class="flex items-center gap-2 sm:gap-3 text-gray-300 hover:text-white transition-colors text-sm sm:text-base">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                <span>seraph-oral.org</span>
                            </a>
                            <a href="mailto:info@seraph-oral.org" class="flex items-center gap-2 sm:gap-3 text-gray-300 hover:text-white transition-colors text-sm sm:text-base">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span>info@seraph-oral.org</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl border border-gray-100 p-5 sm:p-6 md:p-10">
                    <h3 class="text-xl sm:text-2xl font-bold text-charcoal-900 mb-4 sm:mb-6">Apply to Become a Distributor</h3>

                    <?php if ($success): ?>
                        <div class="mb-8 p-6 bg-green-50 border border-green-200 rounded-xl text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h3 class="font-bold text-green-800 text-xl mb-2">Application Submitted!</h3>
                            <p class="text-green-700">Thank you for your interest in becoming a Seraph distributor. We'll review your application and get back to you shortly.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                            <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <h3 class="font-bold text-red-800">Error</h3>
                                <p class="text-red-700 text-sm mt-1"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="POST" class="space-y-4 sm:space-y-5">
                        <div>
                            <label for="name" class="block text-xs sm:text-sm font-bold text-charcoal-900 mb-1.5 sm:mb-2">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required
                                class="w-full px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border border-gray-200 rounded-lg sm:rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none text-sm sm:text-base"
                                placeholder="Enter your full name">
                        </div>

                        <div>
                            <label for="email" class="block text-xs sm:text-sm font-bold text-charcoal-900 mb-1.5 sm:mb-2">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                                class="w-full px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border border-gray-200 rounded-lg sm:rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none text-sm sm:text-base"
                                placeholder="you@example.com">
                        </div>

                        <div>
                            <label for="phone" class="block text-xs sm:text-sm font-bold text-charcoal-900 mb-1.5 sm:mb-2">Phone Number <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                <select id="countryCode" name="countryCode" class="w-20 sm:w-24 px-2 py-2.5 sm:py-3 bg-gray-50 border border-gray-200 rounded-lg sm:rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none text-xs sm:text-sm">
                                    <option value="+44" selected>+44</option>
                                    <option value="+1">+1</option>
                                    <option value="+234">+234</option>
                                    <option value="+91">+91</option>
                                    <option value="+27">+27</option>
                                    <option value="+254">+254</option>
                                    <option value="+233">+233</option>
                                    <option value="+256">+256</option>
                                    <option value="+255">+255</option>
                                    <option value="+260">+260</option>
                                    <option value="+263">+263</option>
                                    <option value="+61">+61</option>
                                    <option value="+49">+49</option>
                                    <option value="+33">+33</option>
                                    <option value="+31">+31</option>
                                    <option value="+39">+39</option>
                                    <option value="+34">+34</option>
                                    <option value="+353">+353</option>
                                    <option value="+86">+86</option>
                                    <option value="+81">+81</option>
                                </select>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required
                                    class="flex-1 min-w-0 px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border border-gray-200 rounded-lg sm:rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none text-sm sm:text-base"
                                    placeholder="Phone number">
                            </div>
                        </div>

                        <div>
                            <label for="location" class="block text-xs sm:text-sm font-bold text-charcoal-900 mb-1.5 sm:mb-2">Location (City, Country) <span class="text-red-500">*</span></label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" required
                                class="w-full px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border border-gray-200 rounded-lg sm:rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none text-sm sm:text-base"
                                placeholder="e.g., London, UK">
                        </div>

                        <div>
                            <label for="reason" class="block text-xs sm:text-sm font-bold text-charcoal-900 mb-1.5 sm:mb-2">Why do you want to become a distributor?</label>
                            <textarea id="reason" name="reason" rows="3"
                                class="w-full px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border border-gray-200 rounded-lg sm:rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none resize-none text-sm sm:text-base"
                                placeholder="Tell us about yourself and your motivation..."><?php echo htmlspecialchars($reason); ?></textarea>
                        </div>

                        <!-- Honeypot & Timing -->
                        <div class="hidden">
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                            <input type="hidden" name="form_start_time" value="<?php echo time(); ?>">
                        </div>

                        <button type="submit" id="distributor-submit-btn"
                            class="w-full bg-folly hover:bg-folly-600 text-white font-bold py-3 sm:py-4 rounded-lg sm:rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center gap-2 text-sm sm:text-base">
                            <span>Submit Application</span>
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-10 sm:py-16 bg-gradient-to-r from-folly to-tangerine relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('<?php echo getAssetUrl('images/general/pattern.png'); ?>')] opacity-10"></div>
    <div class="container mx-auto px-4 relative z-10 text-center">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white mb-4 sm:mb-6 font-display">Ready to Partner with Seraph?</h2>
        <p class="text-white/90 text-sm sm:text-lg max-w-2xl mx-auto mb-6 sm:mb-8 px-2">Join our network of distributors and bring premium natural oral care to your community. Start your journey today!</p>
        <a href="#become-distributor" class="inline-flex items-center justify-center gap-2 bg-white text-folly px-6 sm:px-8 py-3 sm:py-4 rounded-lg sm:rounded-xl font-bold hover:bg-gray-50 transition-all shadow-xl hover:shadow-2xl text-sm sm:text-base">
            Apply Now
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
        </a>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('distributor-submit-btn');
    if (btn) {
        const form = btn.closest('form');
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Submitting...';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
