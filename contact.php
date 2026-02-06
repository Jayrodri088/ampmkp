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
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">Contact Us</span>
        </nav>
    </div>
</div>

<!-- Hero Section -->
<section class="relative bg-charcoal-900 py-12 sm:py-16 md:py-28 overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1557426272-fc759fdf7a8d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80')] bg-cover bg-center opacity-10"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-charcoal-900/50 to-charcoal-900"></div>
    
    <div class="relative container mx-auto px-4 text-center">
        <span class="inline-block py-1.5 px-4 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-xs font-semibold tracking-[0.2em] uppercase mb-4 sm:mb-6">
            We're Here to Help
        </span>
        <h1 class="text-3xl sm:text-4xl md:text-6xl font-bold text-white mb-4 sm:mb-6 font-display tracking-tight">
            Get in Touch
        </h1>
        <p class="text-base sm:text-lg md:text-xl text-gray-300 mb-6 sm:mb-10 max-w-2xl mx-auto leading-relaxed px-2">
            Have a question about our products, shipping, or custom orders? We'd love to hear from you.
        </p>
        
        <div class="flex flex-col sm:flex-row justify-center gap-3 sm:gap-4 px-4 sm:px-0">
            <a href="tel:+447918154909" class="inline-flex items-center justify-center gap-2 glass text-charcoal-900 px-5 sm:px-6 py-3 rounded-xl font-bold hover:bg-white/90 transition-colors text-sm sm:text-base">
                <svg class="w-5 h-5 text-folly" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path></svg>
                Call Us Now
            </a>
            <a href="mailto:sales@angelmarketplace.org" class="inline-flex items-center justify-center gap-2 bg-white/10 backdrop-blur-md text-white px-5 sm:px-6 py-3 rounded-xl font-bold hover:bg-white/20 transition-colors border border-white/20 text-sm sm:text-base">
                <svg class="w-5 h-5 text-folly" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg>
                Email Us
            </a>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="py-10 sm:py-16 md:py-20 bg-gradient-to-b from-gray-50 to-white -mt-6 sm:-mt-10 relative z-10 rounded-t-[1.5rem] sm:rounded-t-[2.5rem]">
    <div class="container mx-auto px-3 sm:px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-20">
                <!-- Contact Info -->
                <div class="space-y-6 sm:space-y-10 overflow-hidden">
                    <div>
                        <span class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-3 block">Reach Out</span>
                        <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-charcoal-900 mb-3 sm:mb-4 md:mb-6 tracking-tight">Contact Information</h2>
                        <p class="text-gray-600 text-sm sm:text-base md:text-lg leading-relaxed">
                            Reach out to us through any of these channels. We're committed to providing you with the best support possible.
                        </p>
                    </div>
                    
                    <div class="space-y-4 sm:space-y-6">
                        <!-- Phone -->
                        <div class="flex items-start p-4 sm:p-6 glass rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-folly/10 rounded-xl flex items-center justify-center flex-shrink-0 text-folly mr-3 sm:mr-5">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base sm:text-lg font-bold text-charcoal-900 mb-1">Phone Support</h3>
                                <p class="text-gray-500 text-xs sm:text-sm mb-2">Mon-Fri from 9am to 6pm</p>
                                <div class="space-y-1">
                                    <a href="tel:+447918154909" class="block text-base sm:text-lg font-medium text-charcoal-900 hover:text-folly transition-colors">+44 791 815 4909</a>
                                    <a href="tel:+441708556604" class="block text-base sm:text-lg font-medium text-charcoal-900 hover:text-folly transition-colors">+44 170 855 6604</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="flex items-start p-4 sm:p-6 glass rounded-2xl hover:shadow-lg transition-shadow">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-tangerine/10 rounded-xl flex items-center justify-center flex-shrink-0 text-tangerine mr-3 sm:mr-5">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base sm:text-lg font-bold text-charcoal-900 mb-1">Email Us</h3>
                                <p class="text-gray-500 text-xs sm:text-sm mb-2">We'll get back to you within 24 hours</p>
                                <a href="mailto:sales@angelmarketplace.org" class="block text-sm sm:text-lg font-medium text-charcoal-900 hover:text-folly transition-colors truncate">sales@angelmarketplace.org</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Support Box -->
                    <div class="bg-gradient-to-br from-charcoal-900 to-charcoal-800 rounded-2xl p-5 sm:p-8 text-white shadow-xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-folly rounded-full mix-blend-overlay filter blur-3xl opacity-20"></div>
                        <h3 class="text-lg sm:text-xl font-bold mb-2 sm:mb-3">Need Immediate Assistance?</h3>
                        <p class="text-gray-300 text-sm sm:text-base mb-4 sm:mb-6">Our support team is available to help you with any urgent inquiries regarding your orders.</p>
                        <a href="tel:+447918154909" class="inline-flex items-center text-folly-300 font-bold hover:text-white transition-colors">
                            Call Support Line <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </a>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="glass-strong rounded-2xl sm:rounded-3xl shadow-xl p-4 sm:p-6 md:p-10">
                    <span class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-2 block">Message Us</span>
                    <h2 class="text-xl sm:text-2xl font-bold text-charcoal-900 mb-4 sm:mb-6 tracking-tight">Send us a Message</h2>
                    
                    <?php if ($success): ?>
                        <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
                            <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <h3 class="font-bold text-green-800">Message Sent!</h3>
                                <p class="text-green-700 text-sm mt-1">Thank you for contacting us. We'll get back to you shortly.</p>
                            </div>
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
                    
                    <form method="POST" class="space-y-4 sm:space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-bold text-charcoal-900 mb-2">Your Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                placeholder="John Doe">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-bold text-charcoal-900 mb-2">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                placeholder="john@example.com">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-bold text-charcoal-900 mb-2">Phone Number</label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <select id="countryCode" name="countryCode" class="w-full sm:w-32 px-3 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none text-sm" onchange="updatePhonePlaceholder()">
                                    <option value="+44" data-format="XXXX XXX XXXX" selected>🇬🇧 +44</option>
                                    <option value="+1" data-format="(XXX) XXX-XXXX">🇺🇸 +1</option>
                                    <option value="+1" data-format="(XXX) XXX-XXXX">🇨🇦 +1</option>
                                    <option value="+234" data-format="XXX XXX XXXX">🇳🇬 +234</option>
                                    <option value="+353" data-format="XX XXX XXXX">🇮🇪 +353</option>
                                    <option value="+49" data-format="XXX XXXXXXXX">🇩🇪 +49</option>
                                    <option value="+33" data-format="X XX XX XX XX">🇫🇷 +33</option>
                                    <option value="+39" data-format="XXX XXX XXXX">🇮🇹 +39</option>
                                    <option value="+34" data-format="XXX XXX XXX">🇪🇸 +34</option>
                                    <option value="+31" data-format="X XXXXXXXX">🇳🇱 +31</option>
                                    <option value="+32" data-format="XXX XX XX XX">🇧🇪 +32</option>
                                    <option value="+41" data-format="XX XXX XX XX">🇨🇭 +41</option>
                                    <option value="+43" data-format="XXX XXXXXX">🇦🇹 +43</option>
                                    <option value="+46" data-format="XX XXX XX XX">🇸🇪 +46</option>
                                    <option value="+47" data-format="XXX XX XXX">🇳🇴 +47</option>
                                    <option value="+45" data-format="XX XX XX XX">🇩🇰 +45</option>
                                    <option value="+358" data-format="XX XXX XXXX">🇫🇮 +358</option>
                                    <option value="+48" data-format="XXX XXX XXX">🇵🇱 +48</option>
                                    <option value="+351" data-format="XXX XXX XXX">🇵🇹 +351</option>
                                    <option value="+30" data-format="XXX XXX XXXX">🇬🇷 +30</option>
                                    <option value="+61" data-format="XXX XXX XXX">🇦🇺 +61</option>
                                    <option value="+64" data-format="XX XXX XXXX">🇳🇿 +64</option>
                                    <option value="+27" data-format="XX XXX XXXX">🇿🇦 +27</option>
                                    <option value="+233" data-format="XX XXX XXXX">🇬🇭 +233</option>
                                    <option value="+254" data-format="XXX XXXXXX">🇰🇪 +254</option>
                                    <option value="+256" data-format="XXX XXXXXX">🇺🇬 +256</option>
                                    <option value="+255" data-format="XXX XXX XXX">🇹🇿 +255</option>
                                    <option value="+91" data-format="XXXXX XXXXX">🇮🇳 +91</option>
                                    <option value="+92" data-format="XXX XXXXXXX">🇵🇰 +92</option>
                                    <option value="+880" data-format="XXXX XXXXXX">🇧🇩 +880</option>
                                    <option value="+86" data-format="XXX XXXX XXXX">🇨🇳 +86</option>
                                    <option value="+81" data-format="XX XXXX XXXX">🇯🇵 +81</option>
                                    <option value="+82" data-format="XX XXXX XXXX">🇰🇷 +82</option>
                                    <option value="+65" data-format="XXXX XXXX">🇸🇬 +65</option>
                                    <option value="+60" data-format="XX XXX XXXX">🇲🇾 +60</option>
                                    <option value="+63" data-format="XXX XXX XXXX">🇵🇭 +63</option>
                                    <option value="+66" data-format="XX XXX XXXX">🇹🇭 +66</option>
                                    <option value="+84" data-format="XX XXX XXXX">🇻🇳 +84</option>
                                    <option value="+62" data-format="XXX XXX XXXX">🇮🇩 +62</option>
                                    <option value="+971" data-format="XX XXX XXXX">🇦🇪 +971</option>
                                    <option value="+966" data-format="XX XXX XXXX">🇸🇦 +966</option>
                                    <option value="+974" data-format="XXXX XXXX">🇶🇦 +974</option>
                                    <option value="+973" data-format="XXXX XXXX">🇧🇭 +973</option>
                                    <option value="+968" data-format="XXXX XXXX">🇴🇲 +968</option>
                                    <option value="+965" data-format="XXXX XXXX">🇰🇼 +965</option>
                                    <option value="+972" data-format="XX XXX XXXX">🇮🇱 +972</option>
                                    <option value="+90" data-format="XXX XXX XXXX">🇹🇷 +90</option>
                                    <option value="+20" data-format="XX XXXX XXXX">🇪🇬 +20</option>
                                    <option value="+212" data-format="XX XXX XXXX">🇲🇦 +212</option>
                                    <option value="+55" data-format="XX XXXXX XXXX">🇧🇷 +55</option>
                                    <option value="+52" data-format="XX XXXX XXXX">🇲🇽 +52</option>
                                    <option value="+54" data-format="XX XXXX XXXX">🇦🇷 +54</option>
                                    <option value="+57" data-format="XXX XXX XXXX">🇨🇴 +57</option>
                                    <option value="+56" data-format="X XXXX XXXX">🇨🇱 +56</option>
                                    <option value="+51" data-format="XXX XXX XXX">🇵🇪 +51</option>
                                    <option value="+58" data-format="XXX XXX XXXX">🇻🇪 +58</option>
                                    <option value="+7" data-format="XXX XXX XX XX">🇷🇺 +7</option>
                                    <option value="+380" data-format="XX XXX XXXX">🇺🇦 +380</option>
                                    <option value="+375" data-format="XX XXX XX XX">🇧🇾 +375</option>
                                    <option value="+40" data-format="XXX XXX XXX">🇷🇴 +40</option>
                                    <option value="+36" data-format="XX XXX XXXX">🇭🇺 +36</option>
                                    <option value="+420" data-format="XXX XXX XXX">🇨🇿 +420</option>
                                    <option value="+421" data-format="XXX XXX XXX">🇸🇰 +421</option>
                                    <option value="+385" data-format="XX XXX XXXX">🇭🇷 +385</option>
                                    <option value="+386" data-format="XX XXX XXX">🇸🇮 +386</option>
                                    <option value="+381" data-format="XX XXX XXXX">🇷🇸 +381</option>
                                    <option value="+359" data-format="XX XXX XXXX">🇧🇬 +359</option>
                                    <option value="+370" data-format="XXX XXXXX">🇱🇹 +370</option>
                                    <option value="+371" data-format="XXXX XXXX">🇱🇻 +371</option>
                                    <option value="+372" data-format="XXXX XXXX">🇪🇪 +372</option>
                                </select>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                    class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none"
                                    placeholder="7912 345 678">
                            </div>
                        </div>
                        
                        <div>
                            <label for="message" class="block text-sm font-bold text-charcoal-900 mb-2">Message <span class="text-red-500">*</span></label>
                            <textarea id="message" name="message" rows="5" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all outline-none resize-none"
                                placeholder="How can we help you?"><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Honeypot & Timing -->
                        <div class="hidden">
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                            <input type="hidden" name="form_start_time" value="<?php echo time(); ?>">
                        </div>
                        
                        <button type="submit" id="contact-submit-btn"
                            class="w-full bg-gradient-to-r from-folly to-folly-500 hover:from-folly-600 hover:to-folly text-white font-bold py-4 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center gap-2">
                            <span>Send Message</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-10 sm:py-16 md:py-20 bg-gradient-to-b from-white to-gray-50">
    <div class="container mx-auto px-3 sm:px-4">
        <div class="text-center mb-8 sm:mb-12 md:mb-16">
            <span class="text-xs font-semibold tracking-[0.2em] uppercase text-folly mb-3 block">FAQ</span>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-charcoal-900 mb-3 sm:mb-4 tracking-tight">Frequently Asked Questions</h2>
            <p class="text-gray-500 text-sm sm:text-base md:text-lg">Quick answers to common questions</p>
        </div>
        
        <div class="grid md:grid-cols-2 gap-4 sm:gap-6 md:gap-8 max-w-4xl mx-auto">
            <div class="glass p-4 sm:p-6 md:p-8 rounded-xl sm:rounded-2xl">
                <h3 class="text-base sm:text-lg md:text-xl font-bold text-charcoal-900 mb-2 sm:mb-3">How long does shipping take?</h3>
                <p class="text-gray-600 text-sm sm:text-base">Standard shipping typically takes 3-7 business days within the UK. International shipping may take 10-14 business days.</p>
            </div>
            <div class="glass p-4 sm:p-6 md:p-8 rounded-xl sm:rounded-2xl">
                <h3 class="text-base sm:text-lg md:text-xl font-bold text-charcoal-900 mb-2 sm:mb-3">What is your return policy?</h3>
                <p class="text-gray-600 text-sm sm:text-base">We offer a 30-day return policy for unused items in original condition. Custom or personalized items may not be returnable.</p>
            </div>
            <div class="glass p-4 sm:p-6 md:p-8 rounded-xl sm:rounded-2xl">
                <h3 class="text-base sm:text-lg md:text-xl font-bold text-charcoal-900 mb-2 sm:mb-3">Do you offer bulk discounts?</h3>
                <p class="text-gray-600 text-sm sm:text-base">Yes! We offer special pricing for bulk orders and church groups. Contact us for a custom quote.</p>
            </div>
            <div class="glass p-4 sm:p-6 md:p-8 rounded-xl sm:rounded-2xl">
                <h3 class="text-base sm:text-lg md:text-xl font-bold text-charcoal-900 mb-2 sm:mb-3">Can I track my order?</h3>
                <p class="text-gray-600 text-sm sm:text-base">Absolutely! Once your order ships, you'll receive a tracking number via email to monitor your package's progress.</p>
            </div>
        </div>
    </div>
</section>

<script>
function updatePhonePlaceholder() {
    const countrySelect = document.getElementById('countryCode');
    const phoneInput = document.getElementById('phone');
    const selectedOption = countrySelect.options[countrySelect.selectedIndex];
    const format = selectedOption.getAttribute('data-format');
    if (format) phoneInput.placeholder = format;
}

document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('contact-submit-btn');
    const form = btn.closest('form');
    
    form.addEventListener('submit', function() {
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Sending...';
    });
});
</script>

<?php include 'includes/footer.php'; ?>