# Meta Commerce & Instagram Shop – Integration Check and Testing

## If you see "Products: 0" and "No pixel events received" (Pixel data source)

Your catalog may have only a **Pixel** data source. The Pixel as a catalog source only adds products when your **product pages** have **microdata** (Schema.org/Open Graph). Your site uses a **Data Feed** instead, so you must add a **Data feed** data source:

1. On the **Data sources** page, click the green **+ Add** button.
2. In the "Add products via" menu, choose **Data file** (the upload/arrow icon – "Add multiple products using a pre-filled Excel or CSV file").
3. On the next screen, look for an option to **use a URL** or **schedule a fetch** (e.g. "Link to your feed", "Fetch from URL", "Scheduled upload"). Do **not** only upload a one-off file.
4. **Feed URL:** `https://angelmarketplace.org/api/facebook-feed.php` (your live feed; HTTPS). Meta accepts TSV from this URL.
5. Set **schedule** (Hourly or Daily) and save.
6. Wait for the first sync (often 5–15 minutes). **Products** should then show a number > 0.

If the Data file flow only offers "Upload a file" with no URL option, check the same data source’s **Settings** after adding it; some accounts allow adding a **schedule** with a feed URL there ([About scheduled data feed uploads](https://www.facebook.com/business/help/2284463181837648)).

The Pixel source can stay; it won’t add products without microdata. The **Data feed** is what fills your catalog from `api/facebook-feed.php`.

---

## Is the integration correct and optimal?

**Yes.** Your project uses the **Data Feed** method, which is one of Meta’s two supported ways to get products into a catalog and Instagram Shop. It matches current Meta Commerce documentation.

### Two ways Meta can get products (current options)

| Method | What it is | Your project |
|--------|------------|--------------|
| **1. Data Feed (scheduled fetch)** | You give Meta a URL (TSV/CSV/XML). Meta fetches it on a schedule and imports products. | **✓ You use this** – `api/facebook-feed.php` |
| **2. Pixel + Microdata** | Meta Pixel on your site + product pages with microdata (Schema.org/Open Graph). Catalog updates when the pixel fires on those pages. | Not used (optional add-on below) |

References: [Ways to add products to your catalog](https://www.facebook.com/business/help/384041892421495), [Commerce Platform Catalog](https://developers.facebook.com/docs/commerce-platform/catalog/overview/).

### About the Microdata Debug Tool

- **URL:** [Catalog Microdata Debug Tool](https://business.facebook.com/ads/microdata/debug)
- **Purpose:** Validates **microdata on a webpage** (Schema.org/Open Graph product markup).
- **Use it with:** A **product page URL**, e.g. `https://angelmarketplace.org/product.php?slug=your-product`.
- **Do not use it with:** Your feed URL (`https://angelmarketplace.org/api/facebook-feed.php`). That URL returns **raw TSV**, not HTML with microdata, so the Microdata Debug Tool does not apply to it.

So: the Microdata Debug Tool is for the **Pixel + Microdata** method (product pages). Your integration is **Data Feed**; testing for that is below.

---

## Feed format vs Meta requirements

Your feed matches Meta’s current requirements for Shops/Dynamic Ads:

- **Required:** id, title, description, availability, condition, price, link, image_link, and at least one of brand/mpn/gtin.  
  Your feed has: id, title, description, availability, condition, price, link, image_link, brand, mpn, google_product_category. ✓
- **Formats:** TSV is supported (tab-delimited, first line = headers). ✓
- **URLs:** `link` and `image_link` must be **absolute** (e.g. `https://angelmarketplace.org/...`). The feed now uses `SITE_BASE_URL` from `.env` so live feeds output full URLs.

Reference: [Catalog fields](https://developers.facebook.com/docs/commerce-platform/catalog/fields/).

---

## What was improved

1. **Absolute URLs in feed**  
   Meta needs full URLs for `link` and `image_link`. The feed now:
   - Uses **SITE_BASE_URL** from `.env` when set (e.g. `https://angelmarketplace.org`).
   - Falls back to request host + path when the feed is called in a browser (e.g. for local testing).

2. **.env**  
   Add (or update) in `.env` for **live**:
   ```env
   SITE_BASE_URL=https://angelmarketplace.org
   ```
   Use your real domain and HTTPS. This is what Meta will see in the feed.

---

## How to test the integration

### 1. Test feed output (local or live)

- **URL:**  
  - Local: `http://localhost/ampmkp/api/facebook-feed.php`  
  - Live: `https://angelmarketplace.org/api/facebook-feed.php`
- **Check:**
  - First line is headers: `id`, `title`, `description`, `availability`, `condition`, `price`, `link`, `image_link`, `brand`, `mpn`, `google_product_category`.
  - Next lines are one row per active product, tab-separated.
  - **link** and **image_link** on live must start with `https://angelmarketplace.org/` (or your domain) when `SITE_BASE_URL` is set.
- **If links are still relative:** Set `SITE_BASE_URL` in `.env` and run the feed again (Meta’s crawler will use the same env when it fetches).

### 2. Test in Commerce Manager (live only)

1. Go to [Commerce Manager](https://business.facebook.com/commerce).
2. Create or open your **E-commerce** catalog.
3. **Data sources** → **Add data source** → **Data feed** (scheduled fetch).
4. **Feed URL:** `https://angelmarketplace.org/api/facebook-feed.php` (HTTPS, no localhost).
5. Set **schedule** (e.g. Hourly or Daily) and save.
6. Wait for the first sync (often 5–15 minutes).
7. In the catalog, open **Products** / **Items** and confirm products appear.
8. Open **Diagnostics** (or the feed’s status) and fix any reported errors (e.g. invalid image URL, missing field).

This is the **only** place that validates that Meta can fetch and ingest your feed; the Microdata Debug Tool does not do this.

### 3. Test Instagram Shop (after catalog is OK)

1. In Commerce Manager: **Commerce account** → **Instagram Shopping** (or Sales channels).
2. Connect your Instagram Business account and complete any merchant agreement.
3. Submit for review; approval often takes 1–3 business days.
4. After approval, products from the same catalog (fed by your feed) appear in Instagram Shop.

### 4. Optional: Microdata Debug Tool (only if you add microdata)

- Use only when you have **product pages** with microdata (Schema.org `Product` or Open Graph product tags).
- In the tool, enter a **product page** URL, e.g. `https://angelmarketplace.org/product.php?slug=your-product`.
- Do **not** enter the feed URL; it will not work there.

---

## Products in catalog but not on Facebook Page or Instagram Shop

If products show in the **catalog** but not on your **Facebook Page** or **Instagram Shop**, use this.

### 1. Issue report (blocking) vs Recommendations (non-blocking)

- **Issue report** tab: These are **blocking**. “Image link is missing” and “Add required product attribute (link)” mean Meta **cannot** use those items in Shops or ads until fixed.
- **Recommendations** tab: These **do not block** items from Shops/ads; they’re suggestions only.

Fix everything in the **Issue report** first.

### 2. Fix feed so link and image_link are never missing

Meta needs **absolute URLs** (e.g. `https://angelmarketplace.org/...`) for `link` and `image_link`. If the feed was fetched when `SITE_BASE_URL` was not set, Meta may have received relative URLs and reported them as “missing”.

**Do this:**

1. In your **.env** (on the server that serves the feed), set:
   ```env
   SITE_BASE_URL=https://angelmarketplace.org
   ```
   Use your real live domain and **HTTPS**. No trailing slash.

2. The feed script now outputs product rows **only** when the base URL is absolute, and uses safe image paths and valid Google product categories.

3. In Commerce Manager, trigger a **re-sync** of your data feed (or wait for the next scheduled fetch). After the next successful sync, re-download the issue report. Blocking issues for “link” and “image link” should be gone once the feed serves absolute URLs.

### 3. Connect the catalog to Facebook Page and Instagram

Products in the catalog do not show in Shops until the catalog is **connected**:

- **Facebook Page:** In Commerce Manager / catalog settings, connect the catalog to your Facebook Page so the Page can show a Shop.
- **Instagram Shop:** Commerce Manager → **Commerce account** (or **Sales channels**) → **Instagram Shopping** → connect your Instagram Business account to the **same** catalog and complete review. Until Instagram Shopping is approved, products will not appear in Instagram Shop.

### 4. Out of stock

Items marked “Out of stock” in the feed will not appear in dynamic ads or Shop. That’s expected. Optional: exclude out-of-stock products from the feed if you don’t want them in the catalog at all.

### Checklist for “products in catalog but not in Shop”

- [ ] **.env** has `SITE_BASE_URL=https://yourdomain.com` (HTTPS, no trailing slash).
- [ ] Re-sync the data feed in Commerce Manager.
- [ ] Issue report has **no blocking** issues for link / image_link / required attributes.
- [ ] Catalog is **connected** to your Facebook Page (for Facebook Shop).
- [ ] **Instagram Shopping** is set up and **approved** for the same catalog (for Instagram Shop).

---

## “Shop location catalogues require a Page with a store Pages structure”

If Commerce Manager shows: **“Shop location catalogues require a Page with a store Pages structure. Go to Shop locations, set up store Pages and then refresh this page to continue”**, your Facebook Page (e.g. Angels Marketplace) has no **Store locations** yet. Meta needs at least one store/location linked to the Page before you can use shop-location catalogues.

### Fix: Set up Store locations

1. In Meta, go to **Commerce Manager** (or **Meta Business Suite**) → **Store locations** (or **Shop locations**).
2. Select your Page (e.g. Angels Marketplace).
3. **Add or update your stores** using one of these:
   - **Upload multiple stores** – spreadsheet (good for many locations).
   - **Connect via API** – use the [Page Locations Graph API](https://developers.facebook.com/docs/graph-api/reference/v24.0/page/locations) to add locations programmatically (see below).
   - **Connect a Page** – link an existing Facebook Page that already represents a store/location.

4. After at least one store is added, go back to the catalogue step and **refresh the page**; the “store Pages structure” error should clear.

### Adding a store via API

The [Page Locations API](https://developers.facebook.com/docs/graph-api/reference/v24.0/page/locations) lets you:

- **GET** `/{page-id}/locations` – list locations for the Page.
- **POST** `/{page-id}/locations` – add a location. You can either:
  - **Add an existing location Page:** send `main_page_id`, `store_number`, `location_page_id`.
  - **Create a new location:** send `main_page_id`, `store_number`, plus address details: `location` (street, city, country; state/zip for USA), `place_topics`, `phone`, `latitude`, `longitude`.

You need a **Page access token** with permission to manage the Page (e.g. `pages_manage_metadata`). Use the same `FACEBOOK_PAGE_ID` and a token that has Page scope.

This project includes a helper script to add one store via the API: see **Adding a store with the helper script** below.

### Adding a store with the helper script

A small API helper is provided so you can add a store from your server using your existing `.env`:

- **Script:** `api/facebook-store-location.php`
- **Requirements:** In `.env`, set `FACEBOOK_PAGE_ID` and `FACEBOOK_ACCESS_TOKEN`. The token must be a **Page access token** (not only a User token) so it can manage the Page’s locations.
- **Usage:**  
  - **GET** (no body): returns short instructions and, if configured, lists existing locations.  
  - **POST** with JSON body: creates one new store. Example body:
    ```json
    {
      "store_number": "1",
      "street": "123 High Street",
      "city": "London",
      "country": "United Kingdom",
      "zip": "SW1A 1AA",
      "phone": "+44 20 7946 0958",
      "latitude": "51.5074",
      "longitude": "-0.1278"
    }
    ```
  For US addresses you must also send `state`. `place_topics` can be omitted in the helper (the script can send a default); see Meta’s docs for valid IDs.

After adding at least one store via the UI or the API, return to Commerce Manager and refresh the catalogue step.

---

## Quick checklist

- [ ] `.env` has `SITE_BASE_URL=https://angelmarketplace.org` (or your live domain) for the feed.
- [ ] Opening the feed URL in a browser shows TSV with headers and product rows.
- [ ] In the feed output, `link` and `image_link` are full `https://...` URLs on live.
- [ ] In Commerce Manager, the feed URL is added as a **Data feed** with a schedule.
- [ ] Catalog **Products/Items** show your products after the first sync.
- [ ] Catalog **Diagnostics** (or feed status) have no blocking errors.
- [ ] Instagram Shopping is connected to the same catalog and approved.

---

## Summary

- **Integration type:** Data Feed (scheduled fetch of `api/facebook-feed.php`) – correct and current.
- **Microdata Debug Tool:** For product **pages** with microdata, not for the feed URL; your feed is not validated there.
- **Testing:** (1) Feed URL in browser → correct TSV and absolute URLs; (2) Commerce Manager → add feed, check Products and Diagnostics; (3) Instagram Shop → connect catalog and get approval.
- **Improvement made:** Feed now outputs absolute `link` and `image_link` using `SITE_BASE_URL` so the catalog stays valid on live.
