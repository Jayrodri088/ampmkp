# Tasks Progress

- 2025-08-07T00:00:00Z: Currency switching improvements implemented across cart, checkout, API, and product listing/rendering. Added helper `getSelectedCurrency`, injected currency context in `includes/header.php`, updated `api/cart.php` to honor `?currency`, unified JS currency symbols with settings, fixed order currency storage and success page formatting.

- 2025-08-07T00:00:00Z: Hardening and quick wins: added session cookie security and headers in `includes/header.php`; restricted CORS and added origin check to `api/cart.php`; switched remaining price renders to currency-aware helpers; added lazy-loading to product images; added file-locking for JSON writes; improved `sanitizeInput` handling arrays; Stripe checkout now uses selected currency and per-currency shipping.

