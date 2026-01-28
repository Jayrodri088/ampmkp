# Tasks Progress

- 2025-08-07T00:00:00Z: Currency switching improvements implemented across cart, checkout, API, and product listing/rendering. Added helper `getSelectedCurrency`, injected currency context in `includes/header.php`, updated `api/cart.php` to honor `?currency`, unified JS currency symbols with settings, fixed order currency storage and success page formatting.

- 2025-08-07T00:00:00Z: Hardening and quick wins: added session cookie security and headers in `includes/header.php`; restricted CORS and added origin check to `api/cart.php`; switched remaining price renders to currency-aware helpers; added lazy-loading to product images; added file-locking for JSON writes; improved `sanitizeInput` handling arrays; Stripe checkout now uses selected currency and per-currency shipping.

- 2025-08-07T00:00:00Z: Secured newsletter CORS and origin checks in `api/newsletter.php`. Added CSRF tokens and server-side validation to `admin/products.php` and `admin/settings.php` forms. Maintained admin auth gating.

- 2025-08-07T00:00:00Z: Updated favicon to use site logo in `includes/header.php` for consistent branding across devices (icon and apple-touch-icon).

- 2025-08-08T00:00:00Z: Added responsive `vendors/terms.php` page for Vendor Terms of Service, linked from `vendors/index.php` (step 3). Ensured it uses shared header/footer, mobile-friendly layout, and base URL helpers.
- 2025-08-08T00:05:00Z: Added `vendors/privacy.php` (Privacy Policy 2025) and `vendors/agreement.php` (Vendor Agreement). Updated links in `vendors/index.php` and `vendors/terms.php` to reference these pages. All pages are responsive and use existing base URL helpers and shared header/footer.

- 2025-08-08T00:15:00Z: Wired vendor application form to backend `api/vendor_apply.php` with CSRF, sanitization, server-side validation, and file upload handling to `assets/images/vendors/`. Persisting applications to `data/vendors.json`. Updated `vendors/index.php` submission and success handling. Added admin management page `admin/vendors.php` to review, approve, reject, and delete applications with CSRF protection and responsive UI.
 
 - 2025-08-08T00:35:00Z: Added robust client-side validation to `vendors/index.php` multi-step form: step gating, field-level errors, email/phone/URL checks, category custom-name enforcement, price/stock validation, and image type/size checks (JPG/PNG/GIF/WebP ≤5MB). Errors are surfaced inline without compromising existing responsive/mobile behavior.

- 2025-08-10T00:00:00Z: Removed hardcoded London location from `contact.php` contact information section and cleared `site_address` in `data/settings.json` to avoid displaying a London address anywhere. Ensured responsiveness remains intact across mobile/tablet/desktop. 

- 2025-08-10T00:10:00Z: Added `big-church-festival.php` with a responsive lead capture form (name, email, phone) protected by CSRF and honeypot, persisting to `data/festival_leads.json`, and sending to `admin@angelmarketplace.org` via `sendFestivalLeadEmail`. Created initial `qr.php` endpoint.

- 2025-08-10T00:20:00Z: Redesigned `big-church-festival.php` as a standalone landing page without shared header/footer, removed embedded QR image, and applied a modern, mobile-optimized design. Reworked `qr.php` to mint a one-time code per request that always redirected to a fixed destination via `go.php` (code-based redirect) with mappings in `data/qr_mappings.json`.

- 2025-08-10T00:28:00Z: Removed the QR code system entirely — deleted `qr.php`, `go.php`, and `data/qr_mappings.json`. Confirmed `big-church-festival.php` has no QR usage and remains responsive.

- 2025-08-10T00:32:00Z: Added minimal `qr-festival.php` endpoint that returns a PNG QR for `https://angelmarketplace.org/big-church-festival` using `api.qrserver.com` with a graceful redirect fallback. 

- 2025-08-10T00:40:00Z: Enhanced `big-church-festival.php` with brand header (logo + Angel Marketplace name) and upgraded form UI (icons, gradient CTA, improved inputs) while preserving CSRF and honeypot protections; fully responsive.

- 2025-08-14T00:00:00Z: Fixed admin login POST under URL rewrite. Updated `admin/auth.php` form action to post to the current request path (`$_SERVER['REQUEST_URI']` via `parse_url(..., PHP_URL_PATH)`) to prevent extensionless rewrite from redirecting `auth.php` and dropping POST. CSRF/session logic unchanged; UI remains responsive.

- 2025-08-14T00:10:00Z: Added `admin/.htaccess` to prevent global rewrites from forcing admin to `index.php`. Enabled extensionless admin routes (e.g., `/admin/products` maps to `products.php`) while serving existing files directly. This fixes tabs/links being stuck on the dashboard under production rewrite rules.

- 2025-08-14T00:18:00Z: Made admin navigation URLs base-aware. Updated `admin/partials/nav_links_{desktop,mobile}.php` and two internal links in `admin/index.php` to prepend the current admin base path, ensuring links work when visiting `/admin` without a trailing slash under rewrite rules.

- 2025-08-14T00:45:00Z: Fixed admin redirect loop on localhost. Added `isLocalhost()` and `getAdminAbsoluteUrl($path, $forceHttps)` in `admin/includes/admin_functions.php`. Updated `admin/index.php` and `admin/auth.php` to enforce HTTPS only in non-local environments and to redirect explicitly to HTTPS when needed. Prevents ERR_TOO_MANY_REDIRECTS during local development while keeping secure cookies in production.

- 2025-08-14T01:10:00Z: Shipping method selection added. Introduced helpers in `includes/functions.php` (`getShippingSettings`, `computeShippingCost`, etc.). Admin can enable pickup, allow user selection, choose default, and set pickup label/instructions in `admin/settings.php`. Updated `checkout.php`, `cart.php`, `api/stripe-payment.php`, and `api/stripe-checkout.php` to honor delivery vs pickup, hide address requirements for pickup, and recalc totals. Fully responsive UI.

- 2025-08-19T00:00:00Z: Post-payment emails added for Stripe card payments. Implemented `sendOrderConfirmationToCustomer` and `sendOrderNotificationToAdmin` in `includes/mail_config.php` with HTML order summary and Stripe receipt/invoice links when available. Hooked into `stripe-success.php` and `api/stripe-payment.php` to send emails after saving the order. Non-blocking with error logging; no impact on success redirect. All email templates remain mobile friendly.

- 2025-08-19T00:10:00Z: Fixed checkout desktop summary not updating totals when switching to Pickup. Updated `updateOrderSummary()` in `checkout.php` to target the Subtotal/Shipping/Total rows reliably and recalc `Shipping` (showing "Pickup"/"Free") and `Total` values dynamically.

- 2025-08-19T00:20:00Z: Refreshed homepage hero in `index.php` (mobile and desktop): preserved brand colors (`folly`, `tangerine`, `charcoal`), added subtle floating decorative elements, improved layout with left content + shopping‑style visuals on desktop (product collage), and tightened CTAs/trust indicators. Fully responsive across breakpoints.

- 2025-08-19T00:24:00Z: Tweaked desktop hero collage in `index.php`: removed white container behind collage images for a cleaner storefront look and made the cart icon a clickable link to each `product.php?slug=...` page.

- 2025-08-19T00:28:00Z: Enhanced mobile hero in `index.php` with a horizontally scrollable mini product shelf (price badges, snap scrolling, hidden scrollbar) to emphasize shopping while keeping text unchanged and colors consistent.

- 2025-08-19T00:32:00Z: Aligned mobile hero text with desktop (title, subheading, underline, tagline) and added trust indicators to match theme. Kept layout optimized for mobile while preserving desktop content parity.

- 2025-08-19T00:35:00Z: Removed the mobile hero product shelf and associated no-scrollbar CSS to keep the mobile hero clean and consistent with desktop theme and text-only focus.
 
- 2025-08-19T00:40:00Z: Fixed localhost redirect loop for admin. Updated `admin/index.php` to skip HTTPS enforcement on localhost and only force HTTPS in non-local environments (uses `isLocalhost()` and `isRequestHttps()`), preventing ERR_TOO_MANY_REDIRECTS during local development.

- 2025-08-19T00:50:00Z: Enriched admin `orders.php` items display with product details. Loads `data/products.json` to map `product_id` → name/image; desktop and mobile views now show item image, proper name, and quantity. No changes to order schema.

- 2025-12-12T00:00:00Z: Homepage performance boost (builds on the 2025-08-19 homepage updates). Added request-level JSON caching in `includes/functions.php`, plus one-pass aggregation for rating stats and category total counts (incl. sub-categories). Updated `index.php` to reuse precomputed maps (no per-item `getProductRatingStats()` calls; no repeated `getTotalProductCountForCategory()` calls inside loops), improving TTFB and overall load speed without changing responsive UI.
- 2025-12-12T00:05:00Z: Order notifications now copied to `sales@angelmarketplace.org`. Added `SALES_EMAIL` in `includes/mail_config.php` and send logic so paid orders, pending/manual orders, and payment confirmations send to both admin and sales inboxes (including fallback mail()).
- 2025-12-12T00:15:00Z: Fixed checkout order payloads to store shipping/billing addresses, currency, and payment method consistently for manual (non-Stripe) orders. Updated `buildOrderEmailHtml` to show payment method and resolved addresses, and switched admin/sales emails to use per-currency formatting. This prevents “Unknown payment method” and ensures emails/admin orders display payment method and addresses.
- 2025-12-12T00:18:00Z: Updated UK bank transfer display in `checkout.php` to show Monzo as the bank and “Angel Marketplace” as the account name (kept existing sort code/account number).
- 2025-12-12T00:23:00Z: Email admin link standardized to absolute `https://angelmarketplace.org/admin/orders.php`. Added `ADMIN_ORDERS_URL` in `includes/mail_config.php` and swapped pending/payment confirmation CTA links to use it.
- 2025-12-12T00:30:00Z: Added “Account Holder Name” field to bank transfer payment form in `checkout.php` and capture it in server-side checkout handling (non-Stripe). This lets customers provide their name for matching transfers.
- 2025-12-12T00:34:00Z: Made checkout order summary sticky on tablet/desktop (`checkout.php`): added `md/lg:sticky` with top offsets and self-start so the summary stays in view while scrolling the form.
- 2025-12-12T00:40:00Z: Fixed missing customer name in admin/payment emails: normalized customer name/email/phone fallbacks in `sendPaymentConfirmationToAdmin` so bank-transfer confirmations no longer show “Unknown”.

- 2025-12-12T00:10:00Z: Admin orders “View Details” modal now shows customer addresses. Added safe address formatting helpers and render both shipping and billing blocks in `admin/orders.php`, pulling from stored order address fields and escaping content to keep the UI responsive and secure.
