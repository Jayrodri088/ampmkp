<?php
$page_title = 'Vendor Agreement';
$page_description = 'Angel Connect – Vendor Agreement for marketplace vendors.';

require_once '../includes/functions.php';
include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-gray-50 to-charcoal-50 py-16 md:py-24">
  <div class="container mx-auto px-4">
    <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-2xl border border-gray-100 p-6 sm:p-10">
      <div class="mb-8 text-center">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900">
          Angel Connect – Vendor Agreement
        </h1>
        <p class="text-gray-600 mt-3">Contractual terms for selling on Angel Connect.</p>
      </div>

      <div class="prose max-w-none text-gray-800">
        <p>This Vendor Agreement ("Agreement") is made between Angel Connect ("Platform") and the Vendor ("You") who wishes to offer products or services through the platform. By completing registration and accepting this Agreement, you agree to the terms outlined below:</p>

        <h2 class="text-xl font-bold mt-8 mb-3">1. Engagement & Scope</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>You are authorized to list, market, and sell products on Angel Connect, subject to compliance with this Agreement.</li>
          <li>Angel Connect operates as a facilitator between you and customers. We do not take ownership of your inventory.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">2. Vendor Responsibilities</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Ensure all listings are accurate, including product descriptions, pricing, and availability.</li>
          <li>Fulfill customer orders reliably and provide timely support.</li>
          <li>Comply with all applicable laws and Angel Connect’s vendor guidelines.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">3. Pricing & Profit Model</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Vendor registration and product listing are completely free.</li>
          <li>Angel Connect will add a markup to your listed prices as its profit margin. This markup will be visible to customers but separate from your base price.</li>
          <li>You agree to the markup policy and pricing transparency.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">4. Payments</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Angel Connect collects customer payments through its secure platform.</li>
          <li>Vendors will be paid monthly, based on the total sales amount excluding the markup retained by Angel Connect.</li>
          <li>You must provide accurate banking and payment details to receive disbursements.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">5. Branding & Content</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>You retain ownership of all content and branding materials.</li>
          <li>By joining Angel Connect, you grant us permission to use your product images and branding solely for promotion and operational purposes.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">6. Performance Standards</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Vendors are expected to maintain high-quality offerings and reliable fulfillment.</li>
          <li>Repeated customer complaints, unresolved disputes, or violations may result in suspension.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">7. Termination</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Angel Connect may suspend or terminate accounts for breaches of this Agreement or misconduct.</li>
          <li>Vendors may withdraw from the platform by providing 7 days’ notice and settling any outstanding responsibilities.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">8. Limitation of Liability</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>Angel Connect is not liable for product issues, customer complaints, or business losses incurred by vendors.</li>
          <li>Vendors are responsible for delivering on their commitments and resolving disputes.</li>
        </ul>

        <h2 class="text-xl font-bold mt-8 mb-3">9. Amendments</h2>
        <ul class="list-disc pl-6 space-y-1">
          <li>This Agreement may be updated occasionally. Vendors will be notified of changes, and continued use signifies acceptance of the new terms.</li>
        </ul>

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


