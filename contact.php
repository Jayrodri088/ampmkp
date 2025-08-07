<?php
$page_title = 'Contact Us';
$page_description = 'Get in touch with Angel Marketplace. We\'re here to help with your questions and provide exceptional customer service.';

require_once 'includes/functions.php';
require_once 'includes/mail_config.php';
require_once 'includes/bot_protection.php';

$success = false;
$error = '';

// Initialize form variables
$name = '';
$email = '';
$phone = '';
$message = '';

// Initialize bot protection
$botProtection = new BotProtection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');

    // Bot protection validation
    $botValidation = $botProtection->validateSubmission('contact', $_POST);

    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!$botValidation['valid']) {
        $error = 'Security validation failed. Please try again.';
        // Log suspicious activity
        $botProtection->logSuspiciousActivity('contact_form', $_POST, $botValidation['errors']);
    } else {
        // Save contact form data
        $contactData = [
            'id' => time(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'submitted_at' => date('Y-m-d H:i:s'),
            'status' => 'new'
        ];
        
        // Read existing contacts
        $contacts = readJsonFile('contacts.json');
        $contacts[] = $contactData;
        
        // Save to file
        if (writeJsonFile('contacts.json', $contacts)) {
            // Send email notification
            $emailSent = sendContactEmail($name, $email, $phone, $message);

            if ($emailSent) {
                // Redirect to prevent form resubmission
                header('Location: contact.php?success=1');
                exit;
            } else {
                // Even if email fails, we consider the form submission successful
                // since we've saved the data to the JSON file
                // Redirect to prevent form resubmission
                header('Location: contact.php?success=1');
                exit;
            }
        } else {
            $error = 'Sorry, there was an error sending your message. Please try again.';
        }
    }
}

// Check for success parameter from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
    // Keep form fields empty on success
    $name = '';
    $email = '';
    $phone = '';
    $message = '';
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-3 md:py-4 mt-16 md:mt-20">
    <div class="container mx-auto px-4">
        <nav class="text-xs md:text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium">Contact Us</span>
        </nav>
    </div>
</div>

<!-- Contact Header -->
<section class="relative bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-12 md:py-20 overflow-hidden">
    <!-- Background decorative elements -->
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
    <div class="absolute top-10 left-10 w-48 h-48 md:w-72 md:h-72 bg-folly-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    <div class="absolute top-20 right-10 w-48 h-48 md:w-72 md:h-72 bg-tangerine-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    
    <div class="relative container mx-auto px-4">
        <div class="max-w-5xl mx-auto text-center">
            <h1 class="text-2xl md:text-5xl lg:text-7xl font-bold text-gray-900 mb-6 md:mb-8 leading-tight px-4">
                Get In 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly via-tangerine to-charcoal-800">
                    Touch
                </span>
            </h1>
            <div class="w-16 md:w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6 md:mb-8"></div>
            <p class="text-base md:text-xl text-gray-600 mb-6 md:mb-8 leading-relaxed max-w-3xl mx-auto px-4">
                We'd love to hear from you. Send us a message and we'll respond as soon as possible. 
                <span class="font-semibold text-folly">Your questions and feedback matter to us</span>.
            </p>
            
            <!-- Quick Contact Options -->
            <div class="flex flex-wrap justify-center gap-4 mt-8">
                <a href="tel:+447918154909" class="bg-white/70 backdrop-blur-sm hover:bg-white text-folly px-6 py-3 rounded-xl font-semibold transition-all duration-200 shadow-lg hover:shadow-xl border border-gray-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                    </svg>
                    Call Us Now
                </a>
                <a href="mailto:sales@angelmarketplace.org" class="bg-white/70 backdrop-blur-sm hover:bg-white text-charcoal-600 px-6 py-3 rounded-xl font-semibold transition-all duration-200 shadow-lg hover:shadow-xl border border-gray-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                    </svg>
                    Email Us
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="bg-gradient-to-br from-gray-50 to-charcoal-50 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                <!-- Contact Information -->
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-10">Contact Information</h2>
                    
                    <div class="space-y-8">
                        <!-- Location -->
                        <div class="flex items-start group">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-folly-50 rounded-2xl flex items-center justify-center group-hover:bg-folly-100 transition-colors duration-200">
                                    <svg class="w-6 h-6 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Location</h3>
                                <p class="text-gray-600 text-lg">London, United Kingdom</p>
                            </div>
                        </div>
                        
                        <!-- Phone Numbers -->
                        <div class="flex items-start group">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-tangerine-50 rounded-2xl flex items-center justify-center group-hover:bg-tangerine-100 transition-colors duration-200">
                                    <svg class="w-6 h-6 text-tangerine" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Phone Numbers</h3>
                                <div class="space-y-2">
                                    <p class="text-gray-600 text-lg">
                                        <a href="tel:+447918154909" class="hover:text-folly transition-colors duration-200 font-medium">(+44) 07918154909</a>
                                    </p>
                                    <p class="text-gray-600 text-lg">
                                        <a href="tel:+441708556604" class="hover:text-folly transition-colors duration-200 font-medium">(+44) 01708556604</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="flex items-start group">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-charcoal-50 rounded-2xl flex items-center justify-center group-hover:bg-charcoal-100 transition-colors duration-200">
                                    <svg class="w-6 h-6 text-charcoal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Email</h3>
                                <p class="text-gray-600 text-lg">
                                    <a href="mailto:sales@angelmarketplace.org" class="hover:text-folly transition-colors duration-200 font-medium">sales@angelmarketplace.org</a>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Business Hours -->
                        <div class="flex items-start group">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-folly-50 rounded-2xl flex items-center justify-center group-hover:bg-folly-100 transition-colors duration-200">
                                    <svg class="w-6 h-6 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">Business Hours</h3>
                                <div class="space-y-1 text-gray-600 text-lg">
                                    <p><span class="font-medium">Monday - Friday:</span> 9:00 AM - 6:00 PM</p>
                                    <p><span class="font-medium">Saturday - Sunday:</span> Closed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Support Notice -->
                    <div class="mt-12 p-8 bg-gradient-to-br from-tangerine-50 to-tangerine-100 rounded-2xl border border-tangerine-200">
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-tangerine rounded-2xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-tangerine-900 mb-3">24/7 Support Available</h3>
                                <p class="text-tangerine-800 text-lg leading-relaxed">
                                    For urgent inquiries, please call our main line at 
                                    <a href="tel:+447918154909" class="font-bold underline hover:text-folly transition-colors duration-200">+44 791 815 4909</a>. 
                                    We're committed to providing exceptional customer service.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="bg-gradient-to-br from-white to-charcoal-50 rounded-3xl p-8 shadow-xl border border-gray-200">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">Send us a Message</h2>
                    
                    <?php if ($success): ?>
                        <div class="mb-8 p-6 bg-green-50 border border-green-200 rounded-2xl">
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-green-600 rounded-xl flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-green-800 mb-2">Message sent successfully!</h3>
                                    <p class="text-green-700">Thank you for contacting us. We'll get back to you within 24 hours.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="mb-8 p-6 bg-red-50 border border-red-200 rounded-2xl">
                            <div class="flex items-start">
                                <div class="w-8 h-8 bg-red-600 rounded-xl flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-red-800 mb-2">Error</h3>
                                    <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-bold text-gray-700 mb-3">
                                Your Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                required
                                class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 bg-white shadow-sm"
                                placeholder="Enter your full name"
                            >
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-bold text-gray-700 mb-3">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                required
                                class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 bg-white shadow-sm"
                                placeholder="Enter your email address"
                            >
                        </div>
                        
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-bold text-gray-700 mb-3">
                                Phone Number
                            </label>
                            <div class="flex gap-2">
                                <select id="countryCode" name="countryCode"
                                        class="w-32 px-3 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 text-sm bg-white shadow-sm"
                                        onchange="updatePhonePlaceholder()">
                                    <option value="+1" data-country="US" data-format="(XXX) XXX-XXXX">🇺🇸 +1</option>
                                    <option value="+7" data-country="RU" data-format="XXX XXX-XX-XX">🇷🇺 +7</option>
                                    <option value="+20" data-country="EG" data-format="XX XXXX XXXX">🇪🇬 +20</option>
                                    <option value="+27" data-country="ZA" data-format="XX XXX XXXX">🇿🇦 +27</option>
                                    <option value="+30" data-country="GR" data-format="XXX XXX XXXX">🇬🇷 +30</option>
                                    <option value="+31" data-country="NL" data-format="X XXXX XXXX">🇳🇱 +31</option>
                                    <option value="+32" data-country="BE" data-format="XXX XX XX XX">🇧🇪 +32</option>
                                    <option value="+33" data-country="FR" data-format="X XX XX XX XX">🇫🇷 +33</option>
                                    <option value="+34" data-country="ES" data-format="XXX XXX XXX">🇪🇸 +34</option>
                                    <option value="+36" data-country="HU" data-format="XX XXX XXXX">🇭🇺 +36</option>
                                    <option value="+39" data-country="IT" data-format="XXX XXX XXXX">🇮🇹 +39</option>
                                    <option value="+40" data-country="RO" data-format="XXX XXX XXX">🇷🇴 +40</option>
                                    <option value="+41" data-country="CH" data-format="XX XXX XX XX">🇨🇭 +41</option>
                                    <option value="+43" data-country="AT" data-format="XXX XXXXXXX">🇦🇹 +43</option>
                                    <option value="+44" data-country="GB" data-format="XXXX XXX XXXX" selected>🇬🇧 +44</option>
                                    <option value="+45" data-country="DK" data-format="XX XX XX XX">🇩🇰 +45</option>
                                    <option value="+46" data-country="SE" data-format="XX XXX XX XX">🇸🇪 +46</option>
                                    <option value="+47" data-country="NO" data-format="XXX XX XXX">🇳🇴 +47</option>
                                    <option value="+48" data-country="PL" data-format="XXX XXX XXX">🇵🇱 +48</option>
                                    <option value="+49" data-country="DE" data-format="XXX XXXXXXXX">🇩🇪 +49</option>
                                    <option value="+51" data-country="PE" data-format="XXX XXX XXX">🇵🇪 +51</option>
                                    <option value="+52" data-country="MX" data-format="XX XXXX XXXX">🇲🇽 +52</option>
                                    <option value="+53" data-country="CU" data-format="X XXX XXXX">🇨🇺 +53</option>
                                    <option value="+54" data-country="AR" data-format="XX XXXX XXXX">🇦🇷 +54</option>
                                    <option value="+55" data-country="BR" data-format="XX XXXXX XXXX">🇧🇷 +55</option>
                                    <option value="+56" data-country="CL" data-format="X XXXX XXXX">🇨🇱 +56</option>
                                    <option value="+57" data-country="CO" data-format="XXX XXX XXXX">🇨🇴 +57</option>
                                    <option value="+58" data-country="VE" data-format="XXX XXX XXXX">🇻🇪 +58</option>
                                    <option value="+60" data-country="MY" data-format="XX XXXX XXXX">🇲🇾 +60</option>
                                    <option value="+61" data-country="AU" data-format="XXX XXX XXX">🇦🇺 +61</option>
                                    <option value="+62" data-country="ID" data-format="XXX XXXX XXXX">🇮🇩 +62</option>
                                    <option value="+63" data-country="PH" data-format="XXX XXX XXXX">🇵🇭 +63</option>
                                    <option value="+64" data-country="NZ" data-format="XX XXX XXXX">🇳🇿 +64</option>
                                    <option value="+65" data-country="SG" data-format="XXXX XXXX">🇸🇬 +65</option>
                                    <option value="+66" data-country="TH" data-format="XX XXX XXXX">🇹🇭 +66</option>
                                    <option value="+81" data-country="JP" data-format="XX XXXX XXXX">🇯🇵 +81</option>
                                    <option value="+82" data-country="KR" data-format="XX XXXX XXXX">🇰🇷 +82</option>
                                    <option value="+84" data-country="VN" data-format="XX XXXX XXXX">🇻🇳 +84</option>
                                    <option value="+86" data-country="CN" data-format="XXX XXXX XXXX">🇨🇳 +86</option>
                                    <option value="+90" data-country="TR" data-format="XXX XXX XX XX">🇹🇷 +90</option>
                                    <option value="+91" data-country="IN" data-format="XXXXX XXXXX">🇮🇳 +91</option>
                                    <option value="+92" data-country="PK" data-format="XXX XXX XXXX">🇵🇰 +92</option>
                                    <option value="+93" data-country="AF" data-format="XX XXX XXXX">🇦🇫 +93</option>
                                    <option value="+94" data-country="LK" data-format="XX XXX XXXX">🇱🇰 +94</option>
                                    <option value="+95" data-country="MM" data-format="XX XXX XXXX">🇲🇲 +95</option>
                                    <option value="+98" data-country="IR" data-format="XXX XXX XXXX">🇮🇷 +98</option>
                                    <option value="+212" data-country="MA" data-format="XXX XXX XXX">🇲🇦 +212</option>
                                    <option value="+213" data-country="DZ" data-format="XXX XXX XXX">🇩🇿 +213</option>
                                    <option value="+216" data-country="TN" data-format="XX XXX XXX">🇹🇳 +216</option>
                                    <option value="+218" data-country="LY" data-format="XX XXX XXXX">🇱🇾 +218</option>
                                    <option value="+220" data-country="GM" data-format="XXX XXXX">🇬🇲 +220</option>
                                    <option value="+221" data-country="SN" data-format="XX XXX XX XX">🇸🇳 +221</option>
                                    <option value="+222" data-country="MR" data-format="XX XX XX XX">🇲🇷 +222</option>
                                    <option value="+223" data-country="ML" data-format="XX XX XX XX">🇲🇱 +223</option>
                                    <option value="+224" data-country="GN" data-format="XXX XXX XXX">🇬🇳 +224</option>
                                    <option value="+225" data-country="CI" data-format="XX XX XX XX XX">🇨🇮 +225</option>
                                    <option value="+226" data-country="BF" data-format="XX XX XX XX">🇧🇫 +226</option>
                                    <option value="+227" data-country="NE" data-format="XX XX XX XX">🇳🇪 +227</option>
                                    <option value="+228" data-country="TG" data-format="XX XX XX XX">🇹🇬 +228</option>
                                    <option value="+229" data-country="BJ" data-format="XX XX XX XX">🇧🇯 +229</option>
                                    <option value="+230" data-country="MU" data-format="XXXX XXXX">🇲🇺 +230</option>
                                    <option value="+231" data-country="LR" data-format="XX XXX XXXX">🇱🇷 +231</option>
                                    <option value="+232" data-country="SL" data-format="XX XXXXXX">🇸🇱 +232</option>
                                    <option value="+233" data-country="GH" data-format="XX XXX XXXX">🇬🇭 +233</option>
                                    <option value="+234" data-country="NG" data-format="XXX XXX XXXX">🇳🇬 +234</option>
                                    <option value="+235" data-country="TD" data-format="XX XX XX XX">🇹🇩 +235</option>
                                    <option value="+236" data-country="CF" data-format="XX XX XX XX">🇨🇫 +236</option>
                                    <option value="+237" data-country="CM" data-format="X XX XX XX XX">🇨🇲 +237</option>
                                    <option value="+238" data-country="CV" data-format="XXX XX XX">🇨🇻 +238</option>
                                    <option value="+239" data-country="ST" data-format="XXX XXXX">🇸🇹 +239</option>
                                    <option value="+240" data-country="GQ" data-format="XXX XXX XXX">🇬🇶 +240</option>
                                    <option value="+241" data-country="GA" data-format="X XX XX XX">🇬🇦 +241</option>
                                    <option value="+242" data-country="CG" data-format="XX XXX XXXX">🇨🇬 +242</option>
                                    <option value="+243" data-country="CD" data-format="XXX XXX XXX">🇨🇩 +243</option>
                                    <option value="+244" data-country="AO" data-format="XXX XXX XXX">🇦🇴 +244</option>
                                    <option value="+245" data-country="GW" data-format="XXX XXXX">🇬🇼 +245</option>
                                    <option value="+246" data-country="IO" data-format="XXX XXXX">🇮🇴 +246</option>
                                    <option value="+248" data-country="SC" data-format="X XX XX XX">🇸🇨 +248</option>
                                    <option value="+249" data-country="SD" data-format="XX XXX XXXX">🇸🇩 +249</option>
                                    <option value="+250" data-country="RW" data-format="XXX XXX XXX">🇷🇼 +250</option>
                                    <option value="+251" data-country="ET" data-format="XX XXX XXXX">🇪🇹 +251</option>
                                    <option value="+252" data-country="SO" data-format="XX XXX XXXX">🇸🇴 +252</option>
                                    <option value="+253" data-country="DJ" data-format="XX XX XX XX">🇩🇯 +253</option>
                                    <option value="+254" data-country="KE" data-format="XXX XXXXXX">🇰🇪 +254</option>
                                    <option value="+255" data-country="TZ" data-format="XX XXX XXXX">🇹🇿 +255</option>
                                    <option value="+256" data-country="UG" data-format="XXX XXXXXX">🇺🇬 +256</option>
                                    <option value="+257" data-country="BI" data-format="XX XX XX XX">🇧🇮 +257</option>
                                    <option value="+258" data-country="MZ" data-format="XX XXX XXXX">🇲🇿 +258</option>
                                    <option value="+260" data-country="ZM" data-format="XX XXX XXXX">🇿🇲 +260</option>
                                    <option value="+261" data-country="MG" data-format="XX XX XXX XX">🇲🇬 +261</option>
                                    <option value="+262" data-country="RE" data-format="XXX XX XX XX">🇷🇪 +262</option>
                                    <option value="+263" data-country="ZW" data-format="XX XXX XXXX">🇿🇼 +263</option>
                                    <option value="+264" data-country="NA" data-format="XX XXX XXXX">🇳🇦 +264</option>
                                    <option value="+265" data-country="MW" data-format="XXX XX XX XX">🇲🇼 +265</option>
                                    <option value="+266" data-country="LS" data-format="XX XXX XXXX">🇱🇸 +266</option>
                                    <option value="+267" data-country="BW" data-format="XX XXX XXXX">🇧🇼 +267</option>
                                    <option value="+268" data-country="SZ" data-format="XX XX XXXX">🇸🇿 +268</option>
                                    <option value="+269" data-country="KM" data-format="XXX XX XX">🇰🇲 +269</option>
                                    <option value="+290" data-country="SH" data-format="XXXX">🇸🇭 +290</option>
                                    <option value="+291" data-country="ER" data-format="X XXX XXX">🇪🇷 +291</option>
                                    <option value="+297" data-country="AW" data-format="XXX XXXX">🇦🇼 +297</option>
                                    <option value="+298" data-country="FO" data-format="XXX XXX">🇫🇴 +298</option>
                                    <option value="+299" data-country="GL" data-format="XX XX XX">🇬🇱 +299</option>
                                    <option value="+350" data-country="GI" data-format="XXXX XXXX">🇬🇮 +350</option>
                                    <option value="+351" data-country="PT" data-format="XXX XXX XXX">🇵🇹 +351</option>
                                    <option value="+352" data-country="LU" data-format="XXX XXX XXX">🇱🇺 +352</option>
                                    <option value="+353" data-country="IE" data-format="XX XXX XXXX">🇮🇪 +353</option>
                                    <option value="+354" data-country="IS" data-format="XXX XXXX">🇮🇸 +354</option>
                                    <option value="+355" data-country="AL" data-format="XX XXX XXXX">🇦🇱 +355</option>
                                    <option value="+356" data-country="MT" data-format="XXXX XXXX">🇲🇹 +356</option>
                                    <option value="+357" data-country="CY" data-format="XX XXX XXX">🇨🇾 +357</option>
                                    <option value="+358" data-country="FI" data-format="XX XXX XXXX">🇫🇮 +358</option>
                                    <option value="+359" data-country="BG" data-format="XXX XXX XXX">🇧🇬 +359</option>
                                    <option value="+370" data-country="LT" data-format="XXX XXXXX">🇱🇹 +370</option>
                                    <option value="+371" data-country="LV" data-format="XX XXX XXX">🇱🇻 +371</option>
                                    <option value="+372" data-country="EE" data-format="XXXX XXXX">🇪🇪 +372</option>
                                    <option value="+373" data-country="MD" data-format="XX XXX XXX">🇲🇩 +373</option>
                                    <option value="+374" data-country="AM" data-format="XX XXX XXX">🇦🇲 +374</option>
                                    <option value="+375" data-country="BY" data-format="XX XXX XX XX">🇧🇾 +375</option>
                                    <option value="+376" data-country="AD" data-format="XXX XXX">🇦🇩 +376</option>
                                    <option value="+377" data-country="MC" data-format="XX XX XX XX XX">🇲🇨 +377</option>
                                    <option value="+378" data-country="SM" data-format="XXXX XXXXXX">🇸🇲 +378</option>
                                    <option value="+380" data-country="UA" data-format="XX XXX XX XX">🇺🇦 +380</option>
                                    <option value="+381" data-country="RS" data-format="XX XXX XXXX">🇷🇸 +381</option>
                                    <option value="+382" data-country="ME" data-format="XX XXX XXX">🇲🇪 +382</option>
                                    <option value="+383" data-country="XK" data-format="XX XXX XXX">🇽🇰 +383</option>
                                    <option value="+385" data-country="HR" data-format="XX XXX XXXX">🇭🇷 +385</option>
                                    <option value="+386" data-country="SI" data-format="XX XXX XXX">🇸🇮 +386</option>
                                    <option value="+387" data-country="BA" data-format="XX XXX XXX">🇧🇦 +387</option>
                                    <option value="+389" data-country="MK" data-format="XX XXX XXX">🇲🇰 +389</option>
                                    <option value="+420" data-country="CZ" data-format="XXX XXX XXX">🇨🇿 +420</option>
                                    <option value="+421" data-country="SK" data-format="XXX XXX XXX">🇸🇰 +421</option>
                                    <option value="+423" data-country="LI" data-format="XXX XX XX">🇱🇮 +423</option>
                                    <option value="+500" data-country="FK" data-format="XXXXX">🇫🇰 +500</option>
                                    <option value="+501" data-country="BZ" data-format="XXX XXXX">🇧🇿 +501</option>
                                    <option value="+502" data-country="GT" data-format="X XXX XXXX">🇬🇹 +502</option>
                                    <option value="+503" data-country="SV" data-format="XXXX XXXX">🇸🇻 +503</option>
                                    <option value="+504" data-country="HN" data-format="XXXX XXXX">🇭🇳 +504</option>
                                    <option value="+505" data-country="NI" data-format="XXXX XXXX">🇳🇮 +505</option>
                                    <option value="+506" data-country="CR" data-format="XXXX XXXX">🇨🇷 +506</option>
                                    <option value="+507" data-country="PA" data-format="XXXX XXXX">🇵🇦 +507</option>
                                    <option value="+508" data-country="PM" data-format="XX XX XX">🇵🇲 +508</option>
                                    <option value="+509" data-country="HT" data-format="XX XX XXXX">🇭🇹 +509</option>
                                    <option value="+590" data-country="GP" data-format="XXX XX XX XX">🇬🇵 +590</option>
                                    <option value="+591" data-country="BO" data-format="X XXX XXXX">🇧🇴 +591</option>
                                    <option value="+592" data-country="GY" data-format="XXX XXXX">🇬🇾 +592</option>
                                    <option value="+593" data-country="EC" data-format="XX XXX XXXX">🇪🇨 +593</option>
                                    <option value="+594" data-country="GF" data-format="XXX XX XX XX">🇬🇫 +594</option>
                                    <option value="+595" data-country="PY" data-format="XXX XXX XXX">🇵🇾 +595</option>
                                    <option value="+596" data-country="MQ" data-format="XXX XX XX XX">🇲🇶 +596</option>
                                    <option value="+597" data-country="SR" data-format="XXX XXXX">🇸🇷 +597</option>
                                    <option value="+598" data-country="UY" data-format="X XXX XX XX">🇺🇾 +598</option>
                                    <option value="+599" data-country="CW" data-format="X XXX XXXX">🇨🇼 +599</option>
                                    <option value="+670" data-country="TL" data-format="XXX XXXX">🇹🇱 +670</option>
                                    <option value="+672" data-country="NF" data-format="XXX XXX">🇳🇫 +672</option>
                                    <option value="+673" data-country="BN" data-format="XXX XXXX">🇧🇳 +673</option>
                                    <option value="+674" data-country="NR" data-format="XXX XXXX">🇳🇷 +674</option>
                                    <option value="+675" data-country="PG" data-format="XXX XX XXX">🇵🇬 +675</option>
                                    <option value="+676" data-country="TO" data-format="XXXXX">🇹🇴 +676</option>
                                    <option value="+677" data-country="SB" data-format="XXXXX">🇸🇧 +677</option>
                                    <option value="+678" data-country="VU" data-format="XXXXX">🇻🇺 +678</option>
                                    <option value="+679" data-country="FJ" data-format="XXX XXXX">🇫🇯 +679</option>
                                    <option value="+680" data-country="PW" data-format="XXX XXXX">🇵🇼 +680</option>
                                    <option value="+681" data-country="WF" data-format="XX XX XX">🇼🇫 +681</option>
                                    <option value="+682" data-country="CK" data-format="XX XXX">🇨🇰 +682</option>
                                    <option value="+683" data-country="NU" data-format="XXXX">🇳🇺 +683</option>
                                    <option value="+684" data-country="AS" data-format="XXX XXXX">🇦🇸 +684</option>
                                    <option value="+685" data-country="WS" data-format="XX XXXX">🇼🇸 +685</option>
                                    <option value="+686" data-country="KI" data-format="XXXXX">🇰🇮 +686</option>
                                    <option value="+687" data-country="NC" data-format="XX XX XX">🇳🇨 +687</option>
                                    <option value="+688" data-country="TV" data-format="XXXXX">🇹🇻 +688</option>
                                    <option value="+689" data-country="PF" data-format="XX XX XX XX">🇵🇫 +689</option>
                                    <option value="+690" data-country="TK" data-format="XXXX">🇹🇰 +690</option>
                                    <option value="+691" data-country="FM" data-format="XXX XXXX">🇫🇲 +691</option>
                                    <option value="+692" data-country="MH" data-format="XXX XXXX">🇲🇭 +692</option>
                                    <option value="+850" data-country="KP" data-format="XXX XXX XXXX">🇰🇵 +850</option>
                                    <option value="+852" data-country="HK" data-format="XXXX XXXX">🇭🇰 +852</option>
                                    <option value="+853" data-country="MO" data-format="XXXX XXXX">🇲🇴 +853</option>
                                    <option value="+855" data-country="KH" data-format="XX XXX XXXX">🇰🇭 +855</option>
                                    <option value="+856" data-country="LA" data-format="XX XX XXX XXX">🇱🇦 +856</option>
                                    <option value="+880" data-country="BD" data-format="XXXX XXXXXX">🇧🇩 +880</option>
                                    <option value="+886" data-country="TW" data-format="XXX XXX XXX">🇹🇼 +886</option>
                                    <option value="+960" data-country="MV" data-format="XXX XXXX">🇲🇻 +960</option>
                                    <option value="+961" data-country="LB" data-format="XX XXX XXX">🇱🇧 +961</option>
                                    <option value="+962" data-country="JO" data-format="X XXXX XXXX">🇯🇴 +962</option>
                                    <option value="+963" data-country="SY" data-format="XXX XXX XXX">🇸🇾 +963</option>
                                    <option value="+964" data-country="IQ" data-format="XXX XXX XXXX">🇮🇶 +964</option>
                                    <option value="+965" data-country="KW" data-format="XXXX XXXX">🇰🇼 +965</option>
                                    <option value="+966" data-country="SA" data-format="XX XXX XXXX">🇸🇦 +966</option>
                                    <option value="+967" data-country="YE" data-format="XXX XXX XXX">🇾🇪 +967</option>
                                    <option value="+968" data-country="OM" data-format="XXXX XXXX">🇴🇲 +968</option>
                                    <option value="+970" data-country="PS" data-format="XXX XXX XXX">🇵🇸 +970</option>
                                    <option value="+971" data-country="AE" data-format="XX XXX XXXX">🇦🇪 +971</option>
                                    <option value="+972" data-country="IL" data-format="XX XXX XXXX">🇮🇱 +972</option>
                                    <option value="+973" data-country="BH" data-format="XXXX XXXX">🇧🇭 +973</option>
                                    <option value="+974" data-country="QA" data-format="XXXX XXXX">🇶🇦 +974</option>
                                    <option value="+975" data-country="BT" data-format="XX XXX XXX">🇧🇹 +975</option>
                                    <option value="+976" data-country="MN" data-format="XXXX XXXX">🇲🇳 +976</option>
                                    <option value="+977" data-country="NP" data-format="XXX XXX XXXX">🇳🇵 +977</option>
                                    <option value="+992" data-country="TJ" data-format="XX XXX XXXX">🇹🇯 +992</option>
                                    <option value="+993" data-country="TM" data-format="XX XXXXXX">🇹🇲 +993</option>
                                    <option value="+994" data-country="AZ" data-format="XX XXX XX XX">🇦🇿 +994</option>
                                    <option value="+995" data-country="GE" data-format="XXX XXX XXX">🇬🇪 +995</option>
                                    <option value="+996" data-country="KG" data-format="XXX XXX XXX">🇰🇬 +996</option>
                                    <option value="+998" data-country="UZ" data-format="XX XXX XX XX">🇺🇿 +998</option>
                                </select>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone" 
                                    value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                    class="flex-1 px-5 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 bg-white shadow-sm"
                                    placeholder="XXXX XXX XXXX"
                                >
                            </div>
                        </div>
                        
                        <!-- Message -->
                        <div>
                            <label for="message" class="block text-sm font-bold text-gray-700 mb-3">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                id="message" 
                                name="message" 
                                rows="6" 
                                required
                                class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 bg-white shadow-sm resize-none"
                                placeholder="Tell us how we can help you..."
                            ><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>

                        <!-- Honeypot field (hidden from users, should remain empty) -->
                        <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;">
                            <label for="website">Website (leave blank):</label>
                            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <!-- Form timing (hidden field to track form load time) -->
                        <input type="hidden" name="form_start_time" value="<?php echo time(); ?>">

                        <!-- Submit Button -->
                        <div>
                            <button 
                                type="submit" 
                                id="contact-submit-btn"
                                class="w-full bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-3"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <span>Send Message</span>
                                <span class="loading-spinner hidden">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                        
                        <script>
                        // Phone number formatting function
                        function updatePhonePlaceholder() {
                            const countrySelect = document.getElementById('countryCode');
                            const phoneInput = document.getElementById('phone');
                            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
                            const format = selectedOption.getAttribute('data-format');
                            
                            if (format) {
                                phoneInput.placeholder = format;
                            }
                        }
                        
                        document.addEventListener('DOMContentLoaded', function() {
                            // Initialize phone placeholder
                            updatePhonePlaceholder();
                            
                            const contactForm = document.querySelector('form');
                            const submitButton = document.getElementById('contact-submit-btn');
                            const loadingSpinner = submitButton.querySelector('.loading-spinner');
                            const buttonText = submitButton.querySelector('span:not(.loading-spinner)');
                            
                            if (contactForm) {
                                contactForm.addEventListener('submit', function() {
                                    // Show loading state
                                    loadingSpinner.classList.remove('hidden');
                                    buttonText.textContent = 'Sending...';
                                    submitButton.disabled = true;
                                    
                                    // Form will submit normally
                                });
                            }
                        });
                        </script>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.bg-grid-pattern {
    background-image: radial-gradient(circle at 1px 1px, rgba(255, 0, 85, 0.15) 1px, transparent 0);
    background-size: 20px 20px;
}

@keyframes fade-in-down {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}



.animation-delay-200 {
    animation-delay: 0.2s;
}

.animation-delay-400 {
    animation-delay: 0.4s;
}

.animation-delay-600 {
    animation-delay: 0.6s;
}

.animation-delay-800 {
    animation-delay: 0.8s;
}


</style>

<!-- FAQ Section -->
<section class="bg-gradient-to-br from-charcoal-50 to-tangerine-50 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">Frequently Asked Questions</h2>
                <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6"></div>
                <p class="text-gray-600 text-lg">Quick answers to common questions about our products and services</p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-start mb-4">
                        <div class="w-8 h-8 bg-folly-50 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-folly" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">How long does shipping take?</h3>
                    </div>
                    <p class="text-gray-600 leading-relaxed">Standard shipping typically takes 3-7 business days within the UK. International shipping may take 10-14 business days.</p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-start mb-4">
                        <div class="w-8 h-8 bg-tangerine-50 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-tangerine" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">What is your return policy?</h3>
                    </div>
                    <p class="text-gray-600 leading-relaxed">We offer a 30-day return policy for unused items in original condition. Custom or personalized items may not be returnable.</p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-start mb-4">
                        <div class="w-8 h-8 bg-charcoal-50 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-charcoal-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Do you offer bulk discounts?</h3>
                    </div>
                    <p class="text-gray-600 leading-relaxed">Yes! We offer special pricing for bulk orders and church groups. Contact us for a custom quote based on your specific needs.</p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-start mb-4">
                        <div class="w-8 h-8 bg-folly-50 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-folly" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Can I track my order?</h3>
                    </div>
                    <p class="text-gray-600 leading-relaxed">Absolutely! Once your order ships, you'll receive a tracking number via email to monitor your package's progress.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>