<?php
$page_title = 'Privacy Policy (Vendors)';
$page_description = 'Angel Connect – Privacy Policy 2025 for vendors.';

require_once '../includes/functions.php';
include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-gray-50 to-charcoal-50 py-16 md:py-24">
  <div class="container mx-auto px-4">
    <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-2xl border border-gray-100 p-6 sm:p-10">
      <div class="mb-8 text-center">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900">
          Angel Connect – Privacy Policy 2025
        </h1>
        <p class="text-gray-600 mt-3">How we collect, use, and safeguard vendor data.</p>
      </div>

      <div class="prose max-w-none text-gray-800">
        <p>Angel Connect is committed to protecting your personal information. This Privacy Policy outlines how we collect, use, and safeguard vendor data throughout the onboarding process and vendor relationship.</p>

        <h2 class="text-xl font-bold mt-8 mb-3">1. Information We Collect</h2>
        <p>We collect the following types of personal and business information:</p>
        <ul class="list-disc pl-6 space-y-1">
          <li>Full name, email address, phone number, and company name</li>
          <li>Business registration documents and proof of identity</li>
          <li>Bank account and payment details</li>
          <li>Product details, descriptions, and images</li>
          <li>Communication records with our support team or customers</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">2. How We Use Your Information</h2>
        <p>We use your data to:</p>
        <ul class="list-disc pl-6 space-y-1">
          <li>Verify your vendor application and eligibility</li>
          <li>Facilitate product listings, transactions, and payment processing</li>
          <li>Communicate with you about orders, platform updates, and performance</li>
          <li>Improve platform functionality and customer experience</li>
          <li>Send marketing communications (only with your consent)</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">3. Data Sharing & Disclosure</h2>
        <p>We may share information with:</p>
        <ul class="list-disc pl-6 space-y-1">
          <li>Payment processors to enable secure transactions</li>
          <li>Shipping/logistics partners to fulfill orders</li>
          <li>Legal authorities when required by law or to enforce our terms</li>
          <li>Third-party services under strict confidentiality agreements</li>
        </ul>
        <p>We never sell your personal information to advertisers or outside entities.</p>

        <h2 class="text-xl font-bold mt-8 mb-3">4. Data Security</h2>
        <p>Angel Connect uses industry-standard encryption, firewalls, and secure servers to protect your data. Access is restricted to authorized personnel only.</p>

        <h2 class="text-xl font-bold mt-8 mb-3">5. Your Rights & Choices</h2>
        <p>You have the right to:</p>
        <ul class="list-disc pl-6 space-y-1">
          <li>Access, correct, or delete your personal data</li>
          <li>Opt out of marketing emails at any time</li>
          <li>Request data portability or restriction of processing</li>
          <li>File a complaint with the relevant data protection authority</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">6. Data Retention</h2>
        <p>We retain vendor data as long as your account is active or as required to comply with legal obligations. Upon account termination, your data is deleted unless retention is legally necessary.</p>

        <h2 class="text-xl font-bold mt-8 mb-3">7. Cookies & Tracking Technologies</h2>
        <p>Angel Connect uses cookies and similar technologies to enhance site performance and personalize your experience. You can manage cookie preferences in your browser settings.</p>

        <h2 class="text-xl font-bold mt-8 mb-3">8. Updates to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. Changes will be posted on our platform and vendors will be notified via email.</p>

        <p class="text-sm text-gray-500 mt-8">Last updated: <?php echo date('Y-m-d'); ?></p>
      </div>

      <div class="mt-10 flex flex-col sm:flex-row gap-4">
        <a href="<?php echo getBaseUrl('vendors/index.php'); ?>" class="inline-flex items-center justify-center bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
          Back to Vendor Registration
        </a>
        <a href="<?php echo getBaseUrl('vendors/terms.php'); ?>" class="inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-800 px-6 py-3 rounded-xl font-semibold border border-gray-300 hover:border-gray-400 transition-all duration-300">
          View Vendor Terms of Service
        </a>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>


