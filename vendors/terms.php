<?php
$page_title = 'Vendor Terms of Service';
$page_description = 'Angel Connect – Vendor Terms of Service for marketplace vendors.';

require_once '../includes/functions.php';
include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-gray-50 to-charcoal-50 py-16 md:py-24">
  <div class="container mx-auto px-4">
    <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-2xl border border-gray-100 p-6 sm:p-10">
      <div class="mb-8 text-center">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900">
          Angel Connect – Vendor Terms of Service
        </h1>
        <p class="text-gray-600 mt-3">Please read these terms carefully before registering as a vendor.</p>
      </div>

      <div class="prose max-w-none text-gray-800">
        <p>Welcome to Angel Connect. By registering and operating as a vendor on our platform, you agree to comply with the terms outlined below.</p>

        <h2 class="text-xl font-bold mt-8 mb-3">1. Vendor Eligibility</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Vendors must be at least 18 years old and legally authorized to conduct business in their jurisdiction.</li>
          <li>All information provided during registration must be accurate and complete.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">2. Product Listings & Content</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Vendors are responsible for ensuring all listed products are legal, safe, and properly categorized.</li>
          <li>Descriptions and images must be clear, truthful, and not infringe on third-party rights.</li>
          <li>Counterfeit, prohibited, or misleading products are strictly forbidden.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">3. Order Fulfilment & Customer Service</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Vendors must fulfil orders in a timely manner as promised in their listing.</li>
          <li>Prompt, professional communication with customers is required.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">4. Payments & Fees</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Angel Connect will add a mark up fee for administrative cost on any item that vendors sell on the Angel Marketplace platform.</li>
          <li>Vendors agree to receive payments through the platform’s secure payment system.</li>
          <li>Any unpaid fees may result in suspension or termination.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">5. Compliance & Conduct</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Vendors must comply with all applicable laws and Angel Connect guidelines.</li>
          <li>Harassment, manipulation, or abuse of the platform or customers will result in removal.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">6. Intellectual Property</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Vendors retain rights to their own content but grant Angel Connect a license to use it for promotional and operational purposes.</li>
          <li>Uploading copyrighted or trademarked material without permission is prohibited.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">7. Data Use & Privacy</h2>
        <p>Vendor data is handled according to our <a href="<?php echo getBaseUrl('vendors/privacy.php'); ?>" class="text-folly hover:text-folly-600 font-semibold">Privacy Policy</a>. By registering, vendors agree to the collection and use of data for account management and platform enhancement.</p>

        <h2 class="text-xl font-bold mt-8 mb-3">8. Termination</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Angel Connect reserves the right to suspend or terminate vendor accounts for violations of these terms.</li>
          <li>Vendors may terminate their account at any time, subject to outstanding obligations.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">9. Changes to Terms</h2>
        <p>Angel Connect may update these Terms from time to time. Vendors will be notified and continued use constitutes acceptance of changes.</p>

        <p class="text-sm text-gray-500 mt-8">Last updated: <?php echo date('Y-m-d'); ?></p>
      </div>

      <div class="mt-10 flex flex-col sm:flex-row gap-4">
        <a href="<?php echo getBaseUrl('vendors/index.php'); ?>" class="inline-flex items-center justify-center bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300">
          Back to Vendor Registration
        </a>
        <a href="<?php echo getBaseUrl('vendors/agreement.php'); ?>" class="inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-800 px-6 py-3 rounded-xl font-semibold border border-gray-300 hover:border-gray-400 transition-all duration-300">
          View Vendor Agreement
        </a>
        <a href="<?php echo getBaseUrl(); ?>" class="inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-800 px-6 py-3 rounded-xl font-semibold border border-gray-300 hover:border-gray-400 transition-all duration-300">
          Return to Home
        </a>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>


