<?php
$page_title = 'Manifestation Journal';
$page_description = 'Discover the Rhapsody of Realities Manifestation Journal for Teenagers & Young Adults. Intentional living, strategic planning, and soul-winning. Become a distributor today!';

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
    $botValidation = $botProtection->validateSubmission('journal_distributor', $_POST);

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($location)) {
        $error = 'Please fill in all required fields.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!$botValidation['valid']) {
        $error = 'Security validation failed. Please try again.';
        $botProtection->logSuspiciousActivity('journal_distributor_form', $_POST, $botValidation['errors']);
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
        $applications = readJsonFile('journal_distributors.json');
        if (!is_array($applications)) {
            $applications = [];
        }
        $applications[] = $applicationData;

        // Save to file
        if (writeJsonFile('journal_distributors.json', $applications)) {
            header('Location: journal.php?success=1');
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
            <span class="text-charcoal-900 font-medium">Manifestation Journal</span>
        </nav>
    </div>
</div>

<!-- Hero Section -->
<section class="relative bg-charcoal-900 py-12 sm:py-16 md:py-24 overflow-hidden">
    <div class="absolute inset-0 bg-[url('<?php echo getAssetUrl('images/journal/amp3.png'); ?>')] bg-cover bg-center opacity-10"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-charcoal-900 via-charcoal-900/95 to-charcoal-900/80"></div>

    <div class="relative container mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
            <!-- Text Content -->
            <div class="text-center lg:text-left">
                <p class="text-folly font-semibold text-sm uppercase tracking-wider mb-3">Rhapsody of Realities</p>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4 font-display">
                    Manifestation Journal
                </h1>
                <p class="text-gray-300 text-base sm:text-lg mb-6 max-w-lg">
                    For Teenagers & Young Adults. A practical tool for intentional living, strategic planning, and accountable soul-winning.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start mb-8">
                    <a href="#become-distributor" class="inline-flex items-center justify-center gap-2 bg-folly hover:bg-folly-600 text-white px-6 py-3 rounded-xl font-bold transition-colors">
                        Become a Distributor
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                    <a href="#about-journal" class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-xl font-bold transition-colors border border-white/20">
                        Learn More
                    </a>
                </div>

                <div class="flex flex-wrap gap-x-6 gap-y-2 justify-center lg:justify-start text-sm text-gray-400">
                    <span>Intentional Living</span>
                    <span>Strategic Planning</span>
                    <span>Soul-Winning</span>
                </div>
            </div>

            <!-- Journal Image -->
            <div class="flex justify-center lg:justify-end">
                <img src="<?php echo getAssetUrl('images/journal/amp2.png'); ?>"
                     alt="Manifestation Journal"
                     class="w-64 sm:w-72 md:w-80 h-auto drop-shadow-2xl">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="about-journal" class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-charcoal-900 mb-4 font-display">Why This Journal?</h2>
            <div class="w-20 h-1 bg-folly mx-auto rounded-full mb-6"></div>
            <p class="text-charcoal-600 text-lg">The Manifestation Journal is designed to help young people live with purpose and achieve their God-given potential.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Feature 1 -->
            <div class="text-center group">
                <div class="w-16 h-16 bg-folly-50 text-folly rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:bg-folly group-hover:text-white transition-all duration-300 shadow-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-charcoal-900 mb-3">Intentional Living</h3>
                <p class="text-charcoal-600">Learn to live each day with purpose and direction, making every moment count for eternity.</p>
            </div>

            <!-- Feature 2 -->
            <div class="text-center group">
                <div class="w-16 h-16 bg-tangerine-50 text-tangerine rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:bg-tangerine group-hover:text-white transition-all duration-300 shadow-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-charcoal-900 mb-3">Strategic Planning</h3>
                <p class="text-charcoal-600">Develop clear goals and actionable plans to achieve your dreams and fulfill your destiny.</p>
            </div>

            <!-- Feature 3 -->
            <div class="text-center group">
                <div class="w-16 h-16 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300 shadow-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-charcoal-900 mb-3">Accountable Soul-Winning</h3>
                <p class="text-charcoal-600">Track your outreach efforts and stay accountable in your mission to win souls for Christ.</p>
            </div>

            <!-- Feature 4 -->
            <div class="text-center group">
                <div class="w-16 h-16 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:bg-green-600 group-hover:text-white transition-all duration-300 shadow-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-charcoal-900 mb-3">The Race for the Last Man</h3>
                <p class="text-charcoal-600">Join the global movement to reach every person with the Gospel before Christ's return.</p>
            </div>
        </div>
    </div>
</section>

<!-- Journal Showcase -->
<section class="py-16 md:py-24 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Image -->
            <div class="order-2 lg:order-1">
                <img src="<?php echo getAssetUrl('images/journal/amp1.jpg'); ?>"
                     alt="Manifestation Journal Promo"
                     class="w-full rounded-3xl shadow-2xl">
            </div>

            <!-- Content -->
            <div class="order-1 lg:order-2 space-y-6">
                <span class="inline-block py-2 px-4 rounded-full bg-folly-50 text-folly text-sm font-bold">
                    Available Now
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-charcoal-900 font-display">
                    Transform Your Life with Purpose
                </h2>
                <p class="text-charcoal-600 text-lg leading-relaxed">
                    The Manifestation Journal is more than just a notebook—it's a comprehensive guide to help teenagers and young adults discover their purpose, set meaningful goals, and make a lasting impact in their world.
                </p>

                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-charcoal-700">Daily devotional prompts and reflections</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-charcoal-700">Goal-setting frameworks and action plans</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-charcoal-700">Soul-winning tracker and accountability tools</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-charcoal-700">Beautiful, vibrant design that inspires creativity</span>
                    </li>
                </ul>

                <div class="pt-4 flex flex-wrap gap-4">
                    <div class="bg-white rounded-xl p-4 shadow-md border border-gray-100">
                        <p class="text-sm text-gray-500 mb-1">Price</p>
                        <p class="text-2xl font-bold text-folly">5 Espees</p>
                        <p class="text-xs text-gray-500">Code: ANGELMP</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- Become a Distributor Section -->
<section id="become-distributor" class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20">
                <!-- Info -->
                <div class="space-y-8">
                    <div>
                        <span class="inline-block py-2 px-4 rounded-full bg-tangerine-50 text-tangerine text-sm font-bold mb-4">
                            Join Our Network
                        </span>
                        <h2 class="text-3xl md:text-4xl font-bold text-charcoal-900 mb-4 font-display">
                            Become a Distributor
                        </h2>
                        <p class="text-charcoal-600 text-lg leading-relaxed">
                            Partner with us to spread the Manifestation Journal to teenagers and young adults in your community. As a distributor, you'll be part of a movement transforming lives.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start gap-4 p-5 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="w-12 h-12 bg-folly-50 rounded-xl flex items-center justify-center flex-shrink-0 text-folly">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-charcoal-900 mb-1">Earn While You Share</h3>
                                <p class="text-charcoal-600 text-sm">Get competitive margins on every journal you distribute in your network.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-5 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="w-12 h-12 bg-tangerine-50 rounded-xl flex items-center justify-center flex-shrink-0 text-tangerine">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-charcoal-900 mb-1">Build Your Community</h3>
                                <p class="text-charcoal-600 text-sm">Connect with like-minded young people and build a network of purpose-driven individuals.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-5 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center flex-shrink-0 text-purple-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-charcoal-900 mb-1">Full Support</h3>
                                <p class="text-charcoal-600 text-sm">Receive training, marketing materials, and ongoing support from our team.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="bg-gradient-to-br from-charcoal-900 to-charcoal-800 rounded-2xl p-6 text-white">
                        <h3 class="font-bold text-lg mb-4">Have Questions?</h3>
                        <div class="space-y-3">
                            <a href="tel:+2347014140224" class="flex items-center gap-3 text-gray-300 hover:text-white transition-colors">
                                <span class="text-lg">🇳🇬</span>
                                <span>+234 701 414 0224</span>
                            </a>
                            <a href="tel:+441708556604" class="flex items-center gap-3 text-gray-300 hover:text-white transition-colors">
                                <span class="text-lg">🇬🇧</span>
                                <span>+44 (0) 1708 556604</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6 md:p-10">
                    <h3 class="text-2xl font-bold text-charcoal-900 mb-6">Apply to Become a Distributor</h3>

                    <?php if ($success): ?>
                        <div class="mb-8 p-6 bg-green-50 border border-green-200 rounded-xl text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h3 class="font-bold text-green-800 text-xl mb-2">Application Submitted!</h3>
                            <p class="text-green-700">Thank you for your interest in becoming a distributor. We'll review your application and get back to you shortly.</p>
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
                    <form method="POST" class="space-y-5">
                        <div>
                            <label for="name" class="block text-sm font-bold text-charcoal-900 mb-2">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                placeholder="Enter your full name">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-bold text-charcoal-900 mb-2">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                placeholder="you@example.com">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-bold text-charcoal-900 mb-2">Phone Number <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                <select id="countryCode" name="countryCode" class="w-32 px-2 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none text-sm">
                                    <option value="+93">🇦🇫 +93</option>
                                    <option value="+355">🇦🇱 +355</option>
                                    <option value="+213">🇩🇿 +213</option>
                                    <option value="+376">🇦🇩 +376</option>
                                    <option value="+244">🇦🇴 +244</option>
                                    <option value="+1264">🇦🇮 +1264</option>
                                    <option value="+1268">🇦🇬 +1268</option>
                                    <option value="+54">🇦🇷 +54</option>
                                    <option value="+374">🇦🇲 +374</option>
                                    <option value="+297">🇦🇼 +297</option>
                                    <option value="+61">🇦🇺 +61</option>
                                    <option value="+43">🇦🇹 +43</option>
                                    <option value="+994">🇦🇿 +994</option>
                                    <option value="+1242">🇧🇸 +1242</option>
                                    <option value="+973">🇧🇭 +973</option>
                                    <option value="+880">🇧🇩 +880</option>
                                    <option value="+1246">🇧🇧 +1246</option>
                                    <option value="+375">🇧🇾 +375</option>
                                    <option value="+32">🇧🇪 +32</option>
                                    <option value="+501">🇧🇿 +501</option>
                                    <option value="+229">🇧🇯 +229</option>
                                    <option value="+1441">🇧🇲 +1441</option>
                                    <option value="+975">🇧🇹 +975</option>
                                    <option value="+591">🇧🇴 +591</option>
                                    <option value="+387">🇧🇦 +387</option>
                                    <option value="+267">🇧🇼 +267</option>
                                    <option value="+55">🇧🇷 +55</option>
                                    <option value="+673">🇧🇳 +673</option>
                                    <option value="+359">🇧🇬 +359</option>
                                    <option value="+226">🇧🇫 +226</option>
                                    <option value="+257">🇧🇮 +257</option>
                                    <option value="+855">🇰🇭 +855</option>
                                    <option value="+237">🇨🇲 +237</option>
                                    <option value="+1">🇨🇦 +1</option>
                                    <option value="+238">🇨🇻 +238</option>
                                    <option value="+1345">🇰🇾 +1345</option>
                                    <option value="+236">🇨🇫 +236</option>
                                    <option value="+235">🇹🇩 +235</option>
                                    <option value="+56">🇨🇱 +56</option>
                                    <option value="+86">🇨🇳 +86</option>
                                    <option value="+57">🇨🇴 +57</option>
                                    <option value="+269">🇰🇲 +269</option>
                                    <option value="+242">🇨🇬 +242</option>
                                    <option value="+243">🇨🇩 +243</option>
                                    <option value="+506">🇨🇷 +506</option>
                                    <option value="+225">🇨🇮 +225</option>
                                    <option value="+385">🇭🇷 +385</option>
                                    <option value="+53">🇨🇺 +53</option>
                                    <option value="+357">🇨🇾 +357</option>
                                    <option value="+420">🇨🇿 +420</option>
                                    <option value="+45">🇩🇰 +45</option>
                                    <option value="+253">🇩🇯 +253</option>
                                    <option value="+1767">🇩🇲 +1767</option>
                                    <option value="+1809">🇩🇴 +1809</option>
                                    <option value="+593">🇪🇨 +593</option>
                                    <option value="+20">🇪🇬 +20</option>
                                    <option value="+503">🇸🇻 +503</option>
                                    <option value="+240">🇬🇶 +240</option>
                                    <option value="+291">🇪🇷 +291</option>
                                    <option value="+372">🇪🇪 +372</option>
                                    <option value="+251">🇪🇹 +251</option>
                                    <option value="+500">🇫🇰 +500</option>
                                    <option value="+298">🇫🇴 +298</option>
                                    <option value="+679">🇫🇯 +679</option>
                                    <option value="+358">🇫🇮 +358</option>
                                    <option value="+33">🇫🇷 +33</option>
                                    <option value="+241">🇬🇦 +241</option>
                                    <option value="+220">🇬🇲 +220</option>
                                    <option value="+995">🇬🇪 +995</option>
                                    <option value="+49">🇩🇪 +49</option>
                                    <option value="+233">🇬🇭 +233</option>
                                    <option value="+350">🇬🇮 +350</option>
                                    <option value="+30">🇬🇷 +30</option>
                                    <option value="+299">🇬🇱 +299</option>
                                    <option value="+1473">🇬🇩 +1473</option>
                                    <option value="+502">🇬🇹 +502</option>
                                    <option value="+224">🇬🇳 +224</option>
                                    <option value="+245">🇬🇼 +245</option>
                                    <option value="+592">🇬🇾 +592</option>
                                    <option value="+509">🇭🇹 +509</option>
                                    <option value="+504">🇭🇳 +504</option>
                                    <option value="+852">🇭🇰 +852</option>
                                    <option value="+36">🇭🇺 +36</option>
                                    <option value="+354">🇮🇸 +354</option>
                                    <option value="+91">🇮🇳 +91</option>
                                    <option value="+62">🇮🇩 +62</option>
                                    <option value="+98">🇮🇷 +98</option>
                                    <option value="+964">🇮🇶 +964</option>
                                    <option value="+353">🇮🇪 +353</option>
                                    <option value="+972">🇮🇱 +972</option>
                                    <option value="+39">🇮🇹 +39</option>
                                    <option value="+1876">🇯🇲 +1876</option>
                                    <option value="+81">🇯🇵 +81</option>
                                    <option value="+962">🇯🇴 +962</option>
                                    <option value="+7">🇰🇿 +7</option>
                                    <option value="+254">🇰🇪 +254</option>
                                    <option value="+686">🇰🇮 +686</option>
                                    <option value="+850">🇰🇵 +850</option>
                                    <option value="+82">🇰🇷 +82</option>
                                    <option value="+965">🇰🇼 +965</option>
                                    <option value="+996">🇰🇬 +996</option>
                                    <option value="+856">🇱🇦 +856</option>
                                    <option value="+371">🇱🇻 +371</option>
                                    <option value="+961">🇱🇧 +961</option>
                                    <option value="+266">🇱🇸 +266</option>
                                    <option value="+231">🇱🇷 +231</option>
                                    <option value="+218">🇱🇾 +218</option>
                                    <option value="+423">🇱🇮 +423</option>
                                    <option value="+370">🇱🇹 +370</option>
                                    <option value="+352">🇱🇺 +352</option>
                                    <option value="+853">🇲🇴 +853</option>
                                    <option value="+389">🇲🇰 +389</option>
                                    <option value="+261">🇲🇬 +261</option>
                                    <option value="+265">🇲🇼 +265</option>
                                    <option value="+60">🇲🇾 +60</option>
                                    <option value="+960">🇲🇻 +960</option>
                                    <option value="+223">🇲🇱 +223</option>
                                    <option value="+356">🇲🇹 +356</option>
                                    <option value="+692">🇲🇭 +692</option>
                                    <option value="+222">🇲🇷 +222</option>
                                    <option value="+230">🇲🇺 +230</option>
                                    <option value="+52">🇲🇽 +52</option>
                                    <option value="+691">🇫🇲 +691</option>
                                    <option value="+373">🇲🇩 +373</option>
                                    <option value="+377">🇲🇨 +377</option>
                                    <option value="+976">🇲🇳 +976</option>
                                    <option value="+382">🇲🇪 +382</option>
                                    <option value="+1664">🇲🇸 +1664</option>
                                    <option value="+212">🇲🇦 +212</option>
                                    <option value="+258">🇲🇿 +258</option>
                                    <option value="+95">🇲🇲 +95</option>
                                    <option value="+264">🇳🇦 +264</option>
                                    <option value="+674">🇳🇷 +674</option>
                                    <option value="+977">🇳🇵 +977</option>
                                    <option value="+31">🇳🇱 +31</option>
                                    <option value="+64">🇳🇿 +64</option>
                                    <option value="+505">🇳🇮 +505</option>
                                    <option value="+227">🇳🇪 +227</option>
                                    <option value="+234">🇳🇬 +234</option>
                                    <option value="+47">🇳🇴 +47</option>
                                    <option value="+968">🇴🇲 +968</option>
                                    <option value="+92">🇵🇰 +92</option>
                                    <option value="+680">🇵🇼 +680</option>
                                    <option value="+970">🇵🇸 +970</option>
                                    <option value="+507">🇵🇦 +507</option>
                                    <option value="+675">🇵🇬 +675</option>
                                    <option value="+595">🇵🇾 +595</option>
                                    <option value="+51">🇵🇪 +51</option>
                                    <option value="+63">🇵🇭 +63</option>
                                    <option value="+48">🇵🇱 +48</option>
                                    <option value="+351">🇵🇹 +351</option>
                                    <option value="+1787">🇵🇷 +1787</option>
                                    <option value="+974">🇶🇦 +974</option>
                                    <option value="+40">🇷🇴 +40</option>
                                    <option value="+7">🇷🇺 +7</option>
                                    <option value="+250">🇷🇼 +250</option>
                                    <option value="+685">🇼🇸 +685</option>
                                    <option value="+378">🇸🇲 +378</option>
                                    <option value="+239">🇸🇹 +239</option>
                                    <option value="+966">🇸🇦 +966</option>
                                    <option value="+221">🇸🇳 +221</option>
                                    <option value="+381">🇷🇸 +381</option>
                                    <option value="+248">🇸🇨 +248</option>
                                    <option value="+232">🇸🇱 +232</option>
                                    <option value="+65">🇸🇬 +65</option>
                                    <option value="+421">🇸🇰 +421</option>
                                    <option value="+386">🇸🇮 +386</option>
                                    <option value="+677">🇸🇧 +677</option>
                                    <option value="+252">🇸🇴 +252</option>
                                    <option value="+27">🇿🇦 +27</option>
                                    <option value="+211">🇸🇸 +211</option>
                                    <option value="+34">🇪🇸 +34</option>
                                    <option value="+94">🇱🇰 +94</option>
                                    <option value="+249">🇸🇩 +249</option>
                                    <option value="+597">🇸🇷 +597</option>
                                    <option value="+268">🇸🇿 +268</option>
                                    <option value="+46">🇸🇪 +46</option>
                                    <option value="+41">🇨🇭 +41</option>
                                    <option value="+963">🇸🇾 +963</option>
                                    <option value="+886">🇹🇼 +886</option>
                                    <option value="+992">🇹🇯 +992</option>
                                    <option value="+255">🇹🇿 +255</option>
                                    <option value="+66">🇹🇭 +66</option>
                                    <option value="+670">🇹🇱 +670</option>
                                    <option value="+228">🇹🇬 +228</option>
                                    <option value="+676">🇹🇴 +676</option>
                                    <option value="+1868">🇹🇹 +1868</option>
                                    <option value="+216">🇹🇳 +216</option>
                                    <option value="+90">🇹🇷 +90</option>
                                    <option value="+993">🇹🇲 +993</option>
                                    <option value="+1649">🇹🇨 +1649</option>
                                    <option value="+688">🇹🇻 +688</option>
                                    <option value="+256">🇺🇬 +256</option>
                                    <option value="+380">🇺🇦 +380</option>
                                    <option value="+971">🇦🇪 +971</option>
                                    <option value="+44" selected>🇬🇧 +44</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+598">🇺🇾 +598</option>
                                    <option value="+998">🇺🇿 +998</option>
                                    <option value="+678">🇻🇺 +678</option>
                                    <option value="+379">🇻🇦 +379</option>
                                    <option value="+58">🇻🇪 +58</option>
                                    <option value="+84">🇻🇳 +84</option>
                                    <option value="+1284">🇻🇬 +1284</option>
                                    <option value="+1340">🇻🇮 +1340</option>
                                    <option value="+967">🇾🇪 +967</option>
                                    <option value="+260">🇿🇲 +260</option>
                                    <option value="+263">🇿🇼 +263</option>
                                </select>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required
                                    class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                    placeholder="Phone number">
                            </div>
                        </div>

                        <div>
                            <label for="location" class="block text-sm font-bold text-charcoal-900 mb-2">Location (City, Country) <span class="text-red-500">*</span></label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                placeholder="e.g., Lagos, Nigeria">
                        </div>

                        <div>
                            <label for="reason" class="block text-sm font-bold text-charcoal-900 mb-2">Why do you want to become a distributor?</label>
                            <textarea id="reason" name="reason" rows="4"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none resize-none"
                                placeholder="Tell us about yourself and your motivation..."><?php echo htmlspecialchars($reason); ?></textarea>
                        </div>

                        <!-- Honeypot & Timing -->
                        <div class="hidden">
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                            <input type="hidden" name="form_start_time" value="<?php echo time(); ?>">
                        </div>

                        <button type="submit" id="distributor-submit-btn"
                            class="w-full bg-folly hover:bg-folly-600 text-white font-bold py-4 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center gap-2">
                            <span>Submit Application</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-gradient-to-r from-folly to-tangerine relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('<?php echo getAssetUrl('images/general/pattern.png'); ?>')] opacity-10"></div>
    <div class="container mx-auto px-4 relative z-10 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6 font-display">Ready to Make an Impact?</h2>
        <p class="text-white/90 text-lg max-w-2xl mx-auto mb-8">Join thousands of young people who are using the Manifestation Journal to transform their lives and reach others for Christ.</p>
        <a href="#become-distributor" class="inline-flex items-center justify-center gap-2 bg-white text-folly px-8 py-4 rounded-xl font-bold hover:bg-gray-50 transition-all shadow-xl hover:shadow-2xl">
            Apply Now
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
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
