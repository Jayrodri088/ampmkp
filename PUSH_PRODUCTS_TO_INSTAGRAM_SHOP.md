# Push Products to Meta Commerce & Instagram Shop

## What Does What

| Piece | What it does | Does it push products? |
|-------|----------------|------------------------|
| **Meta Pixel** (in header) | Tracks page views, add to cart, purchase for ads/analytics | **No** |
| **Conversions API** (meta-integration.php) | Same events, sent from your server (backup + better matching) | **No** |
| **Product feed** (`api/facebook-feed.php`) | Exposes your products as a URL Meta can fetch | **Yes** – this is what pushes products |

**To get products into Meta Commerce and Instagram Shop you use only the product feed.**  
Pixel and Conversions API help with ads and measurement; they do not add products to a catalog.

---

## Flow: Your Project → Meta Commerce → Instagram Shop

```
Your project (Angel Marketplace)
         │
         │  Products live in: data/products.json
         │
         ▼
  api/facebook-feed.php
  (reads products, outputs TSV feed)
         │
         │  You give Meta this URL:
         │  https://yourdomain.com/api/facebook-feed.php
         │
         ▼
  Meta Commerce Manager
  (fetches feed on a schedule, imports products into a catalog)
         │
         ▼
  Instagram Shop
  (catalog connected to Instagram → products show in Shop)
```

So: **editing products in your project (or admin) updates `data/products.json` → the feed URL returns that data → Meta pulls it → catalog and Instagram Shop stay in sync.**

---

## What You Need Before Starting

1. **Live site on HTTPS**  
   Meta can only fetch a feed from a **public URL** (e.g. `https://angelmarketplace.org/api/facebook-feed.php`).  
   `http://localhost/...` will not work for Commerce Manager.

2. **Meta Business Suite**  
   [business.facebook.com](https://business.facebook.com) – same account as your Pixel (858816100329251).

3. **Facebook Page**  
   Linked to that Business account.

4. **Instagram Business Account**  
   Connected to that Facebook Page.

---

## Step 1: Test the Product Feed Locally

You can test the feed **before** going live.

1. **Run the feed script**
   - Local: `http://localhost/ampmkp/api/facebook-feed.php`
   - Or on your live site: `https://yourdomain.com/api/facebook-feed.php`

2. **Check the output**
   - You should see **tab-separated text** (TSV).
   - First line = column headers: `id`, `title`, `description`, `availability`, `condition`, `price`, `link`, `image_link`, `brand`, `mpn`, `google_product_category`.
   - Following lines = one row per **active** product from `data/products.json`.

3. **Fix common issues**
   - **No products / empty:** Ensure `data/products.json` has items and they are active.
   - **Broken image links:** Feed uses `getBaseUrl()` for `link` and `image_link`. On live site, base URL must be correct (e.g. `https://yourdomain.com`). Check `.htaccess` and `getBasePath()` if links are wrong.

Once this looks correct locally, use the **same URL on your live site** in Step 2.

---

## Step 2: Create a Catalog in Commerce Manager

1. Go to [business.facebook.com/commerce](https://business.facebook.com/commerce).
2. Click **Create catalog** (or **Add catalog**).
3. Choose **E-commerce**.
4. Name it (e.g. “Angel Marketplace Catalog”).
5. Create the catalog.

---

## Step 3: Add Your Product Feed as Data Source

This is the step that **pushes** your project’s products into Meta.

1. Open your new catalog in Commerce Manager.
2. Go to **Data sources** (or **Catalog** → **Data sources**).
3. Click **Add data source** → **Data feed** (or **Scheduled fetch**).
4. **Feed URL:**  
   Use your **live** feed URL, for example:
   - `https://angelmarketplace.org/api/facebook-feed.php`  
   (Replace with your real domain; do **not** use `localhost`.)
5. **Schedule:**  
   Choose **Hourly** or **Daily** so Meta re-fetches and updates products.
6. Save / create the feed.

Meta will fetch the URL on the schedule. After the first run (can take a few minutes), products from your feed should appear in the catalog.

---

## Step 4: Check Products in the Catalog

1. In Commerce Manager, open your catalog.
2. Go to **Products** (or **Items**).
3. You should see products that were in the feed.
4. If you see **errors** (e.g. “Invalid image” or “Missing required field”):
   - Fix the feed/output (e.g. image URLs must be **HTTPS** and reachable by Meta).
   - Trigger a re-sync or wait for the next scheduled fetch.

Your project “pushes” products only in the sense that **this feed URL** is the source of truth; Meta pulls from it. Changing `data/products.json` (via site or admin) will be reflected after the next fetch.

---

## Step 5: Turn On Instagram Shopping

1. In Commerce Manager, go to **Commerce account** (or **Settings**).
2. Find **Instagram Shopping** (or **Sales channels** → Instagram).
3. Start setup and select your **Instagram Business** account.
4. Accept the merchant/seller agreement if prompted.
5. Submit for review.  
   Meta may take **1–3 business days** to approve.

Once approved:

- Products from your catalog (fed by `api/facebook-feed.php`) will be available for **Instagram Shop**.
- You can tag products in posts/stories and use the Shop tab.

---

## Step 6: Keep Products in Sync

- **Automatic:** Commerce Manager re-fetches your feed URL on the schedule you chose (hourly/daily). New or updated products in `data/products.json` will appear after the next fetch.
- **Manual:** In Commerce Manager you can trigger a sync/refresh for the feed if needed.

No extra step is required in your code beyond keeping `api/facebook-feed.php` working and the feed URL publicly accessible.

---

## How Pixel & Conversions API Fit In

- **Pixel (858816100329251)** and **Conversions API** do **not** push products.  
  They send **events** (PageView, ViewContent, AddToCart, Purchase) so Meta can:
  - Measure conversions
  - Optimize ads
  - Build audiences

- **Product feed** is the only part that **pushes products** from your project into Meta Commerce and Instagram Shop.

So:

- **To push products to Meta Commerce / Instagram Shop:**  
  Use the feed URL in Commerce Manager (Steps 2–4) and connect Instagram Shopping (Step 5).

- **To improve ad performance and tracking:**  
  Keep using the Pixel and Conversions API as already set up.

---

## Quick Checklist

- [ ] Feed URL works in browser: `https://yourdomain.com/api/facebook-feed.php` (TSV with products).
- [ ] Catalog created in Commerce Manager.
- [ ] Data source added: **Data feed** with your feed URL and schedule (hourly/daily).
- [ ] Products visible in catalog; fix any feed errors (images, required fields).
- [ ] Instagram Shopping set up and submitted for review.
- [ ] After approval, products from your project (via feed) appear in Instagram Shop.

---

## Troubleshooting

| Problem | What to check |
|--------|----------------|
| Feed URL returns 404 | Correct path and server config; feed must be under `/api/facebook-feed.php` and publicly reachable. |
| Feed empty | `data/products.json` has active products; no PHP errors (check error log). |
| Meta says “Invalid URL” | Use **HTTPS** and a **public** URL (no localhost). |
| Products not in catalog | Wait for first sync (can take ~5–15 min); check Data source status and any error report in Commerce Manager. |
| Image errors in catalog | All `image_link` values in feed must be **HTTPS** and load in a browser. |
| Instagram Shop not approved | Ensure site has clear policies (e.g. returns, contact); follow Meta’s checklist for Shopping. |

---

**Summary:**  
Products are pushed from your project to Meta Commerce and Instagram Shop **only** via the **product feed URL** (`api/facebook-feed.php`) added as a **Data feed** in Commerce Manager. Pixel and Conversions API support tracking and ads; they do not add products to the catalog.
