<?php
$page_title = 'Vendor Registration';
$page_description = 'Join Angel Marketplace as a vendor. Register your business and start selling your products.';

require_once '../includes/functions.php';
include '../includes/header.php';
?>

<!-- Vendor Registration Stepper Form -->
<section class="bg-gradient-to-br from-gray-50 to-charcoal-50 py-20 md:py-32 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="text-center mb-12">
                <h1 class="text-3xl md:text-5xl font-bold text-gray-900 mb-4">
                    Become a <span class="text-transparent bg-clip-text bg-gradient-to-r from-folly to-tangerine">Vendor</span>
                </h1>
                <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6"></div>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Join our marketplace and reach thousands of customers. Complete the registration process in just a few simple steps.
                </p>
            </div>

            <!-- Stepper Progress -->
            <div class="mb-12">
                <div class="flex items-center justify-between">
                    <div class="flex items-center flex-1">
                        <div class="step-item active" data-step="1">
                            <div class="step-circle">
                                <span class="step-number">1</span>
                                <svg class="step-check hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="step-label">Business Info</div>
                        </div>
                        <div class="step-line"></div>
                    </div>
                    
                    <div class="flex items-center flex-1">
                        <div class="step-item" data-step="2">
                            <div class="step-circle">
                                <span class="step-number">2</span>
                                <svg class="step-check hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="step-label">Products & Services</div>
                        </div>
                        <div class="step-line"></div>
                    </div>
                    
                    <div class="flex items-center flex-1">
                        <div class="step-item" data-step="3">
                            <div class="step-circle">
                                <span class="step-number">3</span>
                                <svg class="step-check hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="step-label">Verification</div>
                        </div>
                        <div class="step-line"></div>
                    </div>
                    
                    <div class="step-item" data-step="4">
                        <div class="step-circle">
                            <span class="step-number">4</span>
                            <svg class="step-check hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="step-label">Complete</div>
                    </div>
                </div>
            </div>

            <!-- Form Container -->
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">
                <form id="vendorForm" class="p-8 md:p-12" method="POST" action="<?php echo getBaseUrl('api/vendor_apply.php'); ?>" enctype="multipart/form-data">
                    <?php
                    if (session_status() === PHP_SESSION_NONE) { session_start(); }
                    if (!isset($_SESSION['public_csrf_token'])) { $_SESSION['public_csrf_token'] = bin2hex(random_bytes(32)); }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['public_csrf_token']); ?>">

                    <?php if (!empty($_SESSION['vendor_form_errors'])): ?>
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl p-4">
                            <div class="font-semibold mb-2">Please fix the following:</div>
                            <ul class="list-disc pl-5 space-y-1 text-sm">
                                <?php foreach ($_SESSION['vendor_form_errors'] as $err): ?>
                                    <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; unset($_SESSION['vendor_form_errors']); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Step 1: Business Information -->
                    <div class="form-step active" data-step="1">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 bg-gradient-to-r from-folly to-folly-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Business Information</h2>
                            <p class="text-gray-600">Tell us about your business and what makes it unique</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="businessName" class="block text-sm font-semibold text-gray-900 mb-3">Business Name *</label>
                                <input type="text" id="businessName" name="businessName" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                       placeholder="Enter your business name">
                                <div class="error-message hidden text-folly text-sm mt-1"></div>
                            </div>

                            <div class="form-group">
                                <label for="businessType" class="block text-sm font-semibold text-gray-900 mb-3">Business Type *</label>
                                <select id="businessType" name="businessType" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900">
                                    <option value="">Select business type</option>
                                    <option value="retail">Retail</option>
                                    <option value="wholesale">Wholesale</option>
                                    <option value="manufacturer">Manufacturer</option>
                                    <option value="service">Service Provider</option>
                                    <option value="other">Other</option>
                                </select>
                                <div class="error-message hidden text-folly text-sm mt-1"></div>
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="businessDescription" class="block text-sm font-semibold text-gray-900 mb-3">Business Description *</label>
                                <textarea id="businessDescription" name="businessDescription" rows="4" required
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500 resize-none"
                                          placeholder="Describe your business, what you sell, and what makes you unique"></textarea>
                                <div class="error-message hidden text-folly text-sm mt-1"></div>
                            </div>

                            <div class="form-group">
                                <label for="contactName" class="block text-sm font-semibold text-gray-900 mb-3">Contact Person *</label>
                                <input type="text" id="contactName" name="contactName" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                       placeholder="Full name of primary contact">
                                <div class="error-message hidden text-folly text-sm mt-1"></div>
                            </div>

                            <div class="form-group">
                                <label for="contactEmail" class="block text-sm font-semibold text-gray-900 mb-3">Email Address *</label>
                                <input type="email" id="contactEmail" name="contactEmail" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                       placeholder="business@example.com">
                                <div class="error-message hidden text-folly text-sm mt-1"></div>
                            </div>

                            <div class="form-group">
                                <label for="contactPhone" class="block text-sm font-semibold text-gray-900 mb-3">Phone Number *</label>
                                <div class="flex gap-2">
                                    <select id="countryCode" name="countryCode" required
                                            class="w-32 px-3 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 text-sm"
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
                                    <input type="tel" id="contactPhone" name="contactPhone" required
                                           class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                           placeholder="XXXX XXX XXXX">
                                </div>
                                <div class="error-message hidden text-folly text-sm mt-1"></div>
                            </div>

                            <div class="form-group">
                                <label for="businessWebsite" class="block text-sm font-semibold text-gray-900 mb-3">Website (Optional)</label>
                                <input type="url" id="businessWebsite" name="businessWebsite"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                       placeholder="https://yourwebsite.com">
                                <div class="error-message hidden text-folly text-sm mt-1"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Products & Services -->
                    <div class="form-step hidden" data-step="2">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 bg-gradient-to-r from-tangerine to-tangerine-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Add Your Products & Services</h2>
                            <p class="text-gray-600">Upload your actual products with images, descriptions, and pricing</p>
                        </div>

                        <!-- Products Container -->
                        <div id="productsContainer" class="space-y-6">
                            <!-- Initial Product Form -->
                            <div class="product-item bg-gradient-to-r from-gray-50 to-charcoal-50 rounded-2xl p-6 border border-gray-200">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                        <span class="w-8 h-8 bg-folly text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                                        Product #1
                                    </h3>
                                    <button type="button" onclick="removeProduct(this)" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-lg transition-colors duration-200" style="display: none;">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Product Image Upload -->
                                    <div class="form-group">
                                        <label class="block text-sm font-semibold text-gray-900 mb-3">Product Images *</label>
                                        <div class="image-upload-area border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-folly transition-colors duration-200 cursor-pointer">
                                            <input type="file" name="productImages[]" multiple accept="image/*" class="hidden" onchange="handleImageUpload(this)">
                                            <div class="upload-placeholder">
                                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <p class="text-gray-600 mb-2">Click to upload product images</p>
                                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB each</p>
                                            </div>
                                            <div class="image-preview-container hidden grid grid-cols-2 gap-3 mt-4"></div>
                                        </div>
                                    </div>

                                    <!-- Product Details -->
                                    <div class="space-y-4">
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Product Name *</label>
                                            <input type="text" name="productNames[]" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                                   placeholder="Enter product name">
                                        </div>

                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Category *</label>
                                            <select name="productCategories[]" required onchange="handleCategoryChange(this)"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900">
                                                <option value="">Select category</option>
                                                <option value="apparel">Apparel</option>
                                                <option value="footwear">Footwear</option>
                                                <option value="accessories">Accessories</option>
                                                <option value="household">Household Items</option>
                                                <option value="gifts">Gifts & Souvenirs</option>
                                                <option value="kiddies">Kids Items</option>
                                                <option value="other">Other</option>
                                            </select>
                                            <div class="custom-category-field hidden mt-3">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Custom Category Name *</label>
                                                <input type="text" name="customCategories[]" 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                                       placeholder="Enter your custom category name">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="form-group">
                                                <label class="block text-sm font-semibold text-gray-900 mb-2">Price (£) *</label>
                                                <input type="number" name="productPrices[]" step="0.01" min="0" required onchange="updateSummary()"
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                                       placeholder="0.00">
                                            </div>
                                            <div class="form-group">
                                                <label class="block text-sm font-semibold text-gray-900 mb-2">Stock Quantity *</label>
                                                <input type="number" name="productStock[]" min="0" required
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                                       placeholder="0">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Product Description -->
                                    <div class="form-group md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-900 mb-2">Product Description *</label>
                                        <textarea name="productDescriptions[]" rows="4" required
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500 resize-none"
                                                  placeholder="Describe your product in detail - features, benefits, materials, size, etc."></textarea>
                                    </div>

                                    <!-- Product Variants/Options -->
                                    <div class="form-group md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-900 mb-2">Product Variants (Optional)</label>
                                        <div class="variant-container space-y-3">
                                            <div class="flex gap-3">
                                                <input type="text" name="variantTypes[]" placeholder="e.g., Size, Color"
                                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-folly focus:border-folly text-sm">
                                                <input type="text" name="variantValues[]" placeholder="e.g., S, M, L, XL"
                                                       class="flex-2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-folly focus:border-folly text-sm">
                                                <button type="button" onclick="addVariant(this)" class="px-3 py-2 bg-tangerine hover:bg-tangerine-600 text-white rounded-lg text-sm font-medium transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">Add variants like sizes, colors, or styles. Separate multiple values with commas.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add More Products Button -->
                        <div class="text-center mt-6">
                            <button type="button" onclick="addProduct()" class="inline-flex items-center gap-2 bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Another Product
                            </button>
                        </div>

                        <!-- Summary -->
                        <div class="bg-white rounded-2xl p-6 border border-gray-200 mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Summary</h3>
                            <div id="productSummary" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-folly-50 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-folly" id="totalProducts">1</div>
                                    <div class="text-sm text-gray-600">Total Products</div>
                                </div>
                                <div class="bg-tangerine-50 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-tangerine" id="totalCategories">0</div>
                                    <div class="text-sm text-gray-600">Categories</div>
                                </div>
                                <div class="bg-charcoal-50 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-charcoal" id="totalValue">£0.00</div>
                                    <div class="text-sm text-gray-600">Total Value</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Verification -->
                    <div class="form-step hidden" data-step="3">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 bg-gradient-to-r from-charcoal to-charcoal-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Verification & Terms</h2>
                            <p class="text-gray-600">Final step to complete your vendor registration</p>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-gray-50 rounded-2xl p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Business Verification</h3>
                                <p class="text-gray-600 mb-4">To ensure the safety and trust of our marketplace, we require basic verification of your business.</p>
                                
                                <div class="form-group">
                                    <label for="businessLicense" class="block text-sm font-semibold text-gray-900 mb-3">Business License/Registration Number (Optional)</label>
                                    <input type="text" id="businessLicense" name="businessLicense"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                           placeholder="Enter your business license or registration number">
                                    <div class="error-message hidden text-folly text-sm mt-1"></div>
                                </div>

                                <div class="form-group">
                                    <label for="taxId" class="block text-sm font-semibold text-gray-900 mb-3">Tax ID/VAT Number (Optional)</label>
                                    <input type="text" id="taxId" name="taxId"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                           placeholder="Enter your tax identification number">
                                    <div class="error-message hidden text-folly text-sm mt-1"></div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <input type="checkbox" id="termsAccepted" name="termsAccepted" required
                                           class="mt-1 h-4 w-4 text-folly border-gray-300 rounded focus:ring-folly">
                                    <label for="termsAccepted" class="ml-3 text-sm text-gray-900">
                                        I agree to the <a href="<?php echo getBaseUrl('vendors/terms.php'); ?>" class="text-folly hover:text-folly-600 font-semibold">Terms of Service</a> and <a href="<?php echo getBaseUrl('vendors/agreement.php'); ?>" class="text-folly hover:text-folly-600 font-semibold">Vendor Agreement</a> *
                                    </label>
                                </div>

                                <div class="flex items-start">
                                    <input type="checkbox" id="privacyAccepted" name="privacyAccepted" required
                                           class="mt-1 h-4 w-4 text-folly border-gray-300 rounded focus:ring-folly">
                                    <label for="privacyAccepted" class="ml-3 text-sm text-gray-900">
                                        I agree to the <a href="<?php echo getBaseUrl('vendors/privacy.php'); ?>" class="text-folly hover:text-folly-600 font-semibold">Privacy Policy</a> *
                                    </label>
                                </div>

                                <div class="flex items-start">
                                    <input type="checkbox" id="marketingConsent" name="marketingConsent"
                                           class="mt-1 h-4 w-4 text-folly border-gray-300 rounded focus:ring-folly">
                                    <label for="marketingConsent" class="ml-3 text-sm text-gray-900">
                                        I would like to receive updates and marketing communications about vendor opportunities
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Complete -->
                    <div class="form-step hidden" data-step="4">
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gradient-to-r from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Application Submitted!</h2>
                            <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
                                Thank you for your interest in becoming a vendor at Angel Marketplace. We've received your application and will review it within 2-3 business days.
                            </p>
                            
                            <div class="bg-gradient-to-r from-folly-50 to-tangerine-50 rounded-2xl p-6 mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">What happens next?</h3>
                                <div class="text-left max-w-md mx-auto space-y-2">
                                    <div class="flex items-center text-gray-700">
                                        <div class="w-2 h-2 bg-folly rounded-full mr-3"></div>
                                        <span class="text-sm">We'll review your application within 2-3 business days</span>
                                    </div>
                                    <div class="flex items-center text-gray-700">
                                        <div class="w-2 h-2 bg-folly rounded-full mr-3"></div>
                                        <span class="text-sm">You'll receive an email with next steps</span>
                                    </div>
                                    <div class="flex items-center text-gray-700">
                                        <div class="w-2 h-2 bg-folly rounded-full mr-3"></div>
                                        <span class="text-sm">Once approved, you can start listing products</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                <a href="<?php echo getBaseUrl(); ?>" class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                    Return to Home
                                </a>
                                <button type="button" onclick="resetForm()" class="bg-white hover:bg-gray-50 text-gray-800 px-8 py-3 rounded-xl font-semibold border border-gray-300 hover:border-gray-400 transition-all duration-300">
                                    Submit Another Application
                                </button>
                                <a href="<?php echo getBaseUrl('vendors/terms.php'); ?>" class="bg-white hover:bg-gray-50 text-gray-800 px-8 py-3 rounded-xl font-semibold border border-gray-300 hover:border-gray-400 transition-all duration-300">
                                    View Terms / Privacy / Agreement
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between mt-12 pt-8 border-t border-gray-200">
                        <button type="button" id="prevBtn" onclick="previousStep()" 
                                class="hidden bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-xl font-semibold transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Previous
                        </button>
                        
                        <div class="ml-auto">
                            <button type="button" id="nextBtn" onclick="nextStep()" 
                                    class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center gap-2">
                                Next
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Custom Styles -->
<style>
/* Stepper Styles */
.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.step-circle {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background-color: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    border: 3px solid transparent;
}

.step-item.active .step-circle {
    background: linear-gradient(135deg, #FF0055, #FF0055);
    color: white;
    border-color: #FF0055;
    box-shadow: 0 0 0 4px rgba(255, 0, 85, 0.1);
}

.step-item.completed .step-circle {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-color: #10b981;
}

.step-item.completed .step-number {
    display: none;
}

.step-item.completed .step-check {
    display: block;
}

.step-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-align: center;
    margin-top: 0.5rem;
}

.step-item.active .step-label {
    color: #FF0055;
}

.step-item.completed .step-label {
    color: #10b981;
}

.step-line {
    height: 2px;
    background-color: #e5e7eb;
    flex: 1;
    margin: 0 1rem;
    margin-top: -1.5rem;
    position: relative;
    z-index: -1;
    transition: background-color 0.3s ease;
}

.step-item.completed + .flex .step-line {
    background: linear-gradient(90deg, #10b981, #059669);
}

/* Form Styles */
.form-step {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-checkbox:checked {
    background-color: #FF0055;
    border-color: #FF0055;
}

.form-checkbox:checked:hover {
    background-color: #FF0055;
    border-color: #FF0055;
}

.form-checkbox:focus {
    ring-color: rgba(255, 0, 85, 0.5);
}

/* Error States */
.form-group.error input,
.form-group.error select,
.form-group.error textarea {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-message {
    animation: slideDown 0.3s ease-in-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .step-item {
        flex-direction: row;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .step-circle {
        margin-bottom: 0;
        margin-right: 0.75rem;
        width: 2.5rem;
        height: 2.5rem;
    }
    
    .step-label {
        margin-top: 0;
        text-align: left;
        font-size: 0.875rem;
    }
    
    .flex.items-center.justify-between {
        flex-direction: column;
        align-items: stretch;
    }
    
    .step-line {
        display: none;
    }
}
</style>

<!-- JavaScript -->
<script>
let currentStep = 1;
const totalSteps = 4;

// ---------- Validation Utilities ----------
function getFormGroup(element) {
    return element.closest('.form-group') || element.parentElement;
}

function ensureErrorContainer(formGroup) {
    let container = formGroup.querySelector('.error-message');
    if (!container) {
        container = document.createElement('div');
        container.className = 'error-message text-folly text-sm mt-1';
        formGroup.appendChild(container);
    }
    return container;
}

function setError(element, message) {
    const formGroup = getFormGroup(element);
    if (!formGroup) return;
    formGroup.classList.add('error');
    const container = ensureErrorContainer(formGroup);
    container.textContent = message || 'This field is required';
    container.classList.remove('hidden');
    element.setAttribute('aria-invalid', 'true');
}

function clearError(element) {
    const formGroup = getFormGroup(element);
    if (!formGroup) return;
    formGroup.classList.remove('error');
    const container = formGroup.querySelector('.error-message');
    if (container) container.classList.add('hidden');
    element.removeAttribute('aria-invalid');
}

function scrollToFirstError() {
    const firstError = document.querySelector('.form-group.error');
    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function nextStep() {
    if (!validateCurrentStep()) {
        scrollToFirstError();
        return;
    }
    if (currentStep === 3) {
        submitForm();
    } else if (currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
    }
}

function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('active');
    });
    
    // Show current step
    const currentStepEl = document.querySelector(`.form-step[data-step="${step}"]`);
    if (currentStepEl) {
        currentStepEl.classList.remove('hidden');
        currentStepEl.classList.add('active');
    }
    
    // Update stepper
    updateStepper(step);
    
    // Update navigation buttons
    updateNavigation(step);
}

function updateStepper(step) {
    document.querySelectorAll('.step-item').forEach((el, index) => {
        const stepNum = index + 1;
        
        el.classList.remove('active', 'completed');
        
        if (stepNum < step) {
            el.classList.add('completed');
        } else if (stepNum === step) {
            el.classList.add('active');
        }
    });
}

function updateNavigation(step) {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    // Show/hide previous button
    if (step === 1) {
        prevBtn.classList.add('hidden');
    } else {
        prevBtn.classList.remove('hidden');
    }
    
    // Update next button text
    if (step === 3) {
        nextBtn.innerHTML = `
            Submit Application
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        `;
    } else if (step === 4) {
        nextBtn.style.display = 'none';
    } else {
        nextBtn.innerHTML = `
            Next
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        `;
    }
}

function validateCurrentStep() {
    let valid = true;

    // Step 1: Business info
    if (currentStep === 1) {
        const businessName = document.getElementById('businessName');
        const businessType = document.getElementById('businessType');
        const businessDescription = document.getElementById('businessDescription');
        const contactName = document.getElementById('contactName');
        const contactEmail = document.getElementById('contactEmail');
        const contactPhone = document.getElementById('contactPhone');
        const businessWebsite = document.getElementById('businessWebsite');

        // Name
        if (!businessName.value.trim() || businessName.value.trim().length < 2) {
            setError(businessName, 'Please enter a valid business name (min 2 characters).');
            valid = false;
        } else {
            clearError(businessName);
        }

        // Type
        if (!businessType.value) {
            setError(businessType, 'Please select a business type.');
            valid = false;
        } else {
            clearError(businessType);
        }

        // Description
        if (!businessDescription.value.trim() || businessDescription.value.trim().length < 20) {
            setError(businessDescription, 'Please provide a description (min 20 characters).');
            valid = false;
        } else {
            clearError(businessDescription);
        }

        // Contact name
        if (!contactName.value.trim() || contactName.value.trim().length < 2) {
            setError(contactName, 'Please enter the contact person name.');
            valid = false;
        } else {
            clearError(contactName);
        }

        // Email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i;
        if (!emailRegex.test(contactEmail.value.trim())) {
            setError(contactEmail, 'Please enter a valid email address.');
            valid = false;
        } else {
            clearError(contactEmail);
        }

        // Phone
        const phoneDigits = contactPhone.value.replace(/[^0-9]/g, '');
        if (phoneDigits.length < 7) {
            setError(contactPhone, 'Please enter a valid phone number.');
            valid = false;
        } else {
            clearError(contactPhone);
        }

        // Website (optional)
        if (businessWebsite.value.trim()) {
            try {
                const u = new URL(businessWebsite.value.trim());
                if (!['http:', 'https:'].includes(u.protocol)) throw new Error('Invalid');
                clearError(businessWebsite);
            } catch {
                setError(businessWebsite, 'Please enter a valid URL (http or https).');
                valid = false;
            }
        } else {
            clearError(businessWebsite);
        }
    }

    // Step 2: Products
    if (currentStep === 2) {
        const productItems = document.querySelectorAll('.product-item');
        if (productItems.length === 0) return false;

        productItems.forEach((item) => {
            const nameInput = item.querySelector('input[name="productNames[]"]');
            const categorySelect = item.querySelector('select[name="productCategories[]"]');
            const customCategory = item.querySelector('input[name="customCategories[]"]');
            const priceInput = item.querySelector('input[name="productPrices[]"]');
            const stockInput = item.querySelector('input[name="productStock[]"]');
            const descriptionInput = item.querySelector('textarea[name="productDescriptions[]"]');
            const fileInput = item.querySelector('.image-upload-area input[type="file"]');

            // Name
            if (!nameInput.value.trim() || nameInput.value.trim().length < 2) {
                setError(nameInput, 'Enter a valid product name.');
                valid = false;
            } else {
                clearError(nameInput);
            }

            // Category
            if (!categorySelect.value) {
                setError(categorySelect, 'Select a category.');
                valid = false;
            } else if (categorySelect.value === 'other') {
                if (!customCategory || !customCategory.value.trim()) {
                    setError(categorySelect, 'Provide a custom category name.');
                    valid = false;
                } else {
                    clearError(categorySelect);
                }
            } else {
                clearError(categorySelect);
            }

            // Price
            const price = parseFloat(priceInput.value);
            if (isNaN(price) || price < 0) {
                setError(priceInput, 'Enter a valid price (>= 0).');
                valid = false;
            } else {
                clearError(priceInput);
            }

            // Stock
            const stock = parseInt(stockInput.value, 10);
            if (isNaN(stock) || stock < 0) {
                setError(stockInput, 'Enter a valid stock (>= 0).');
                valid = false;
            } else {
                clearError(stockInput);
            }

            // Description
            if (!descriptionInput.value.trim() || descriptionInput.value.trim().length < 10) {
                setError(descriptionInput, 'Add a brief description (min 10 characters).');
                valid = false;
            } else {
                clearError(descriptionInput);
            }

            // Images
            const files = (fileInput && fileInput.files) ? Array.from(fileInput.files) : [];
            if (files.length === 0) {
                setError(fileInput, 'Please upload at least one product image.');
                valid = false;
            } else {
                const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                const maxSize = 5 * 1024 * 1024;
                const invalid = files.find(f => !allowed.includes(f.type) || f.size > maxSize);
                if (invalid) {
                    const reason = !allowed.includes(invalid.type) ? 'Unsupported file type' : 'Image too large (max 5MB)';
                    setError(fileInput, reason + '.');
                    valid = false;
                } else {
                    clearError(fileInput);
                }
            }
        });
    }

    // Step 3: Terms
    if (currentStep === 3) {
        const terms = document.getElementById('termsAccepted');
        const privacy = document.getElementById('privacyAccepted');
        if (!terms.checked) {
            setError(terms, 'You must accept the Terms of Service.');
            valid = false;
        } else {
            clearError(terms);
        }
        if (!privacy.checked) {
            setError(privacy, 'You must accept the Privacy Policy.');
            valid = false;
        } else {
            clearError(privacy);
        }
    }

    return valid;
}

function submitForm() {
    const nextBtn = document.getElementById('nextBtn');
    nextBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Submitting...
    `;
    nextBtn.disabled = true;

    // Submit the actual form to backend
    document.getElementById('vendorForm').submit();
}

function resetForm() {
    // Reset form
    document.getElementById('vendorForm').reset();
    
    // Reset to first step
    currentStep = 1;
    showStep(currentStep);
    
    // Reset button
    const nextBtn = document.getElementById('nextBtn');
    nextBtn.disabled = false;
    nextBtn.style.display = 'flex';
}

// Phone number formatting function
function updatePhonePlaceholder() {
    const countrySelect = document.getElementById('countryCode');
    const phoneInput = document.getElementById('contactPhone');
    const selectedOption = countrySelect.options[countrySelect.selectedIndex];
    const format = selectedOption.getAttribute('data-format');
    
    if (format) {
        phoneInput.placeholder = format;
    }
}

// Category change handler
function handleCategoryChange(selectElement) {
    const customCategoryField = selectElement.parentNode.querySelector('.custom-category-field');
    const customInput = customCategoryField.querySelector('input[name="customCategories[]"]');
    
    if (selectElement.value === 'other') {
        // Show custom category field
        customCategoryField.classList.remove('hidden');
        customInput.required = true;
        
        // Add smooth slide-down animation
        customCategoryField.style.maxHeight = '0px';
        customCategoryField.style.overflow = 'hidden';
        customCategoryField.style.transition = 'max-height 0.3s ease-in-out';
        
        setTimeout(() => {
            customCategoryField.style.maxHeight = '200px';
        }, 10);
        
        // Focus on the custom input
        setTimeout(() => {
            customInput.focus();
        }, 300);
    } else {
        // Hide custom category field
        customCategoryField.style.maxHeight = '0px';
        customInput.required = false;
        customInput.value = '';
        
        setTimeout(() => {
            customCategoryField.classList.add('hidden');
            customCategoryField.style.maxHeight = '';
            customCategoryField.style.overflow = '';
            customCategoryField.style.transition = '';
        }, 300);
    }
    
    // Update summary after category change
    updateSummary();
}

// Product Management Functions
let productCounter = 1;

function addProduct() {
    productCounter++;
    const container = document.getElementById('productsContainer');
    
    const productHTML = `
        <div class="product-item bg-gradient-to-r from-gray-50 to-charcoal-50 rounded-2xl p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 bg-folly text-white rounded-full flex items-center justify-center text-sm font-bold">${productCounter}</span>
                    Product #${productCounter}
                </h3>
                <button type="button" onclick="removeProduct(this)" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Product Image Upload -->
                <div class="form-group">
                    <label class="block text-sm font-semibold text-gray-900 mb-3">Product Images *</label>
                    <div class="image-upload-area border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-folly transition-colors duration-200 cursor-pointer">
                        <input type="file" name="productImages[]" multiple accept="image/*" class="hidden" onchange="handleImageUpload(this)">
                        <div class="upload-placeholder">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-gray-600 mb-2">Click to upload product images</p>
                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB each</p>
                        </div>
                        <div class="image-preview-container hidden grid grid-cols-2 gap-3 mt-4"></div>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Product Name *</label>
                        <input type="text" name="productNames[]" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                               placeholder="Enter product name">
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Category *</label>
                        <select name="productCategories[]" required onchange="handleCategoryChange(this)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900">
                            <option value="">Select category</option>
                            <option value="apparel">Apparel</option>
                            <option value="footwear">Footwear</option>
                            <option value="accessories">Accessories</option>
                            <option value="household">Household Items</option>
                            <option value="gifts">Gifts & Souvenirs</option>
                            <option value="kiddies">Kids Items</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="custom-category-field hidden mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Custom Category Name *</label>
                            <input type="text" name="customCategories[]" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                   placeholder="Enter your custom category name">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group">
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Price (£) *</label>
                            <input type="number" name="productPrices[]" step="0.01" min="0" required onchange="updateSummary()"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                   placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Stock Quantity *</label>
                            <input type="number" name="productStock[]" min="0" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500"
                                   placeholder="0">
                        </div>
                    </div>
                </div>

                <!-- Product Description -->
                <div class="form-group md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Product Description *</label>
                    <textarea name="productDescriptions[]" rows="4" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 text-gray-900 placeholder-gray-500 resize-none"
                              placeholder="Describe your product in detail - features, benefits, materials, size, etc."></textarea>
                </div>

                <!-- Product Variants/Options -->
                <div class="form-group md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Product Variants (Optional)</label>
                    <div class="variant-container space-y-3">
                        <div class="flex gap-3">
                            <input type="text" name="variantTypes[]" placeholder="e.g., Size, Color"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-folly focus:border-folly text-sm">
                            <input type="text" name="variantValues[]" placeholder="e.g., S, M, L, XL"
                                   class="flex-2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-folly focus:border-folly text-sm">
                            <button type="button" onclick="addVariant(this)" class="px-3 py-2 bg-tangerine hover:bg-tangerine-600 text-white rounded-lg text-sm font-medium transition-colors duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Add variants like sizes, colors, or styles. Separate multiple values with commas.</p>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', productHTML);
    updateSummary();
    
    // Show remove buttons if more than 1 product
    if (productCounter > 1) {
        document.querySelectorAll('.product-item button[onclick="removeProduct(this)"]').forEach(btn => {
            btn.style.display = 'block';
        });
    }
    
    // Scroll to new product
    setTimeout(() => {
        const newProduct = container.lastElementChild;
        newProduct.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
}

function removeProduct(button) {
    const productItem = button.closest('.product-item');
    productItem.remove();
    
    // Update product numbers
    const products = document.querySelectorAll('.product-item');
    products.forEach((product, index) => {
        const numberSpan = product.querySelector('span');
        const title = product.querySelector('h3');
        numberSpan.textContent = index + 1;
        title.innerHTML = `<span class="w-8 h-8 bg-folly text-white rounded-full flex items-center justify-center text-sm font-bold">${index + 1}</span> Product #${index + 1}`;
    });
    
    productCounter = products.length;
    updateSummary();
    
    // Hide remove buttons if only 1 product left
    if (productCounter === 1) {
        document.querySelectorAll('.product-item button[onclick="removeProduct(this)"]').forEach(btn => {
            btn.style.display = 'none';
        });
    }
}

function handleImageUpload(input) {
    const uploadArea = input.closest('.image-upload-area');
    const placeholder = uploadArea.querySelector('.upload-placeholder');
    const previewContainer = uploadArea.querySelector('.image-preview-container');
    const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (input.files && input.files.length > 0) {
        placeholder.classList.add('hidden');
        previewContainer.classList.remove('hidden');
        previewContainer.innerHTML = '';
        
        const files = Array.from(input.files);
        const validFiles = files.filter(f => allowed.includes(f.type) && f.size <= maxSize);

        if (validFiles.length !== files.length) {
            setError(input, 'Invalid image(s): only JPG, PNG, GIF, WebP up to 5MB each.');
        } else {
            clearError(input);
        }

        validFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imagePreview = document.createElement('div');
                imagePreview.className = 'relative group';
                imagePreview.innerHTML = `
                    <img src="${e.target.result}" alt="Product ${index + 1}" class="w-full h-24 object-cover rounded-lg border border-gray-200">
                    <button type="button" onclick="removeImage(this, ${index})" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-200 hover:bg-red-600">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                `;
                previewContainer.appendChild(imagePreview);
            };
            reader.readAsDataURL(file);
        });
    }
}

function removeImage(button, index) {
    const imagePreview = button.closest('.relative');
    const uploadArea = button.closest('.image-upload-area');
    const input = uploadArea.querySelector('input[type="file"]');
    const previewContainer = uploadArea.querySelector('.image-preview-container');
    const placeholder = uploadArea.querySelector('.upload-placeholder');
    
    imagePreview.remove();
    
    // If no images left, show placeholder
    if (previewContainer.children.length === 0) {
        placeholder.classList.remove('hidden');
        previewContainer.classList.add('hidden');
        input.value = '';
    }
}

function addVariant(button) {
    const container = button.closest('.variant-container');
    const newVariant = document.createElement('div');
    newVariant.className = 'flex gap-3';
    newVariant.innerHTML = `
        <input type="text" name="variantTypes[]" placeholder="e.g., Material, Style"
               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-folly focus:border-folly text-sm">
        <input type="text" name="variantValues[]" placeholder="e.g., Cotton, Polyester"
               class="flex-2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-folly focus:border-folly text-sm">
        <button type="button" onclick="removeVariant(this)" class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition-colors duration-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;
    container.appendChild(newVariant);
}

function removeVariant(button) {
    button.closest('.flex').remove();
}

function updateSummary() {
    const products = document.querySelectorAll('.product-item');
    const categories = new Set();
    let totalValue = 0;
    
    products.forEach(product => {
        const categorySelect = product.querySelector('select[name="productCategories[]"]');
        const customCategoryInput = product.querySelector('input[name="customCategories[]"]');
        const price = parseFloat(product.querySelector('input[name="productPrices[]"]').value) || 0;
        
        let category = categorySelect.value;
        
        // Use custom category if "other" is selected and custom category is provided
        if (category === 'other' && customCategoryInput && customCategoryInput.value.trim()) {
            category = customCategoryInput.value.trim().toLowerCase();
        }
        
        if (category && category !== 'other') {
            categories.add(category);
        }
        
        totalValue += price;
    });
    
    document.getElementById('totalProducts').textContent = products.length;
    document.getElementById('totalCategories').textContent = categories.size;
    document.getElementById('totalValue').textContent = `£${totalValue.toFixed(2)}`;
}

// Make image upload areas clickable
document.addEventListener('click', function(e) {
    if (e.target.closest('.image-upload-area')) {
        const input = e.target.closest('.image-upload-area').querySelector('input[type="file"]');
        if (input) {
            input.click();
        }
    }
});

// Initialize form
document.addEventListener('DOMContentLoaded', function() {
    showStep(1);
    updateSummary();
    updatePhonePlaceholder(); // Initialize phone placeholder
    
    // If redirected with success, show completion step
    const params = new URLSearchParams(window.location.search);
    if (params.has('success')) {
        currentStep = 4;
        showStep(currentStep);
    }
    if (params.has('error')) {
        // Optionally surface errors saved in session via server-side include in PHP
    }
    
    // Add event listeners for summary updates
    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name="productCategories[]"]') || 
            e.target.matches('input[name="productPrices[]"]') ||
            e.target.matches('input[name="customCategories[]"]')) {
            updateSummary();
        }
    });
    
    // Also listen for input events on custom category fields for real-time updates
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name="customCategories[]"]')) {
            updateSummary();
        }
    });

    // Live clear errors on input/change
    document.addEventListener('input', function(e) {
        const el = e.target;
        if (el.closest('.form-group')) {
            clearError(el);
        }
    });
    document.addEventListener('change', function(e) {
        const el = e.target;
        if (el.type === 'file') {
            // Revalidate images on change
            handleImageUpload(el);
        }
        if (el.closest('.form-group')) {
            clearError(el);
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>