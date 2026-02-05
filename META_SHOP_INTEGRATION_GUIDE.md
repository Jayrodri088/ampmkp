# Meta Commerce Manager & Instagram Shop Integration Guide

## Overview

This guide will help you integrate Angel Marketplace with Meta Commerce Manager to enable Instagram Shopping and sync products from your e-commerce site.

## What You'll Need

1. **Meta Business Suite Account** - Free at business.facebook.com
2. **Facebook Page** - Must have an active Facebook page for your business
3. **Instagram Business Account** - Connected to your Facebook Page
4. **Facebook Pixel ID** - For tracking user behavior
5. **Facebook App** - For Commerce Manager access

---

## Step 1: Set Up Meta Business Suite

### Create Your Meta Business Suite Account

1. Go to https://business.facebook.com
2. Sign up with your Facebook account
3. Create a Business Manager account
4. Add your existing Facebook Page or create a new one

### Connect Instagram to Facebook

1. Go to your Facebook Page
2. Click Settings → Instagram
3. Connect your Instagram Business account
4. Make sure both accounts are linked

---

## Step 2: Create Facebook Pixel

### Get Your Pixel ID

1. Go to Meta Business Suite
2. Navigate to **Events Manager** (from the left menu)
3. Click **Connect Data Sources** → **Facebook Pixel**
4. Click **Create a Pixel**
5. Name it "Angel Marketplace Pixel"
6. Copy your **Pixel ID** (it will be like `123456789012345`)

### Add Pixel to Your Website

Your pixel is already added to `includes/header.php`. Now configure it:

1. Open your `.env` file
2. Add your Pixel ID:

```env
FACEBOOK_PIXEL_ID=123456789012345
```

3. Test your pixel:
   - Install Facebook Pixel Helper browser extension
   - Visit your website
   - Check that the Pixel Helper shows the pixel is active

---

## Step 3: Create Facebook App for Commerce Manager

### Create the App

1. Go to https://developers.facebook.com/apps
2. Click **Create App**
3. Choose **Business** type
4. Name it "Angel Marketplace Commerce"
5. Create the App

### Configure the App

1. In App Dashboard, go to **Settings** → **Basic**
2. Copy the **App ID** and **App Secret**
3. Add to your `.env`:

```env
FACEBOOK_APP_ID=your_app_id_here
FACEBOOK_APP_SECRET=your_app_secret_here
```

### Get Access Token

1. Go to **Tools** → **Graph API Explorer**
2. Select your App
3. Get Token: Select **Page Access Token**
4. Select your permissions: `pages_manage_catalog`, `pages_read_engagement`
5. Click **Generate Access Token**
6. Copy the token and add to `.env`:

```env
FACEBOOK_ACCESS_TOKEN=your_access_token_here
```

---

## Step 4: Set Up Commerce Manager

### Create Catalog

1. In Meta Business Suite, go to **Commerce Manager**
2. Click **Add Catalog** → **E-commerce**
3. Name it "Angel Marketplace Catalog"
4. Click **Create Catalog**

### Add Data Source

You'll use the **Data Feed** method we've created:

1. In your catalog, go to **Data Sources**
2. Click **Add Data Source** → **Data Feed**
3. Choose **Scheduled Fetch**
4. Enter feed URL: `https://yourdomain.com/api/facebook-feed.php`
5. Set upload frequency: **Daily** or **Hourly**
6. Click **Create Feed**

### Configure Product Feed

Your `api/facebook-feed.php` generates a properly formatted TSV feed that includes:
- Product ID
- Title
- Description
- Availability (in stock/out of stock)
- Condition (new)
- Price with currency
- Product URL
- Image URL
- Brand (from settings)
- MPN (unique identifier)
- Google Product Category

The feed automatically:
- Filters inactive products
- Uses multi-currency pricing
- Maps categories to Google Product Categories
- Generates proper image URLs

---

## Step 5: Enable Instagram Shopping

### Requirements Checklist

Before enabling Instagram Shopping, ensure:

- ✅ You have a Facebook Business account
- ✅ You have an Instagram Business account
- ✅ Your Facebook Page is connected to Instagram
- ✅ Your catalog has products with:
  - High-quality images (minimum 500x500px, recommended 1080x1080px)
  - Accurate descriptions
  - Proper pricing
  - Stock information
- ✅ You're in a supported country/region

### Enable Instagram Shopping

1. In Commerce Manager, go to **Commerce Accounts**
2. Select your commerce account
3. Click **Start Set Up** next to Instagram Shopping
4. Select your Instagram Business account
5. Review and agree to the Merchant Agreement
6. Submit your account for review

**Note:** Review typically takes 1-3 business days. Facebook will verify:
- Your business legitimacy
- Product images and descriptions
- Website functionality
- Customer service capabilities

---

## Step 6: Test Your Integration

### Test Product Feed

1. Visit `https://yourdomain.com/api/facebook-feed.php`
2. You should see a TSV (tab-separated values) file with your products
3. Check that:
   - All active products are listed
   - Prices are correct
   - Image URLs are accessible
   - Categories are properly mapped

### Test Facebook Pixel Events

1. Install Facebook Pixel Helper browser extension
2. Visit product pages - should see `ViewContent` event
3. Add to cart - should see `AddToCart` event
4. Start checkout - should see `InitiateCheckout` event
5. Complete purchase - should see `Purchase` event

### Test Instagram Shop

1. Open Instagram app
2. Go to your profile
3. Tap **View Shop** (should appear after approval)
4. Verify products are displayed
5. Test clicking products - should redirect to your website

---

## Step 7: Configure Feed Currency

Edit your `.env` file to set the correct currency for Instagram:

```env
FACEBOOK_FEED_CURRENCY=GBP
```

Supported currencies: USD, GBP, EUR, NGN, etc.

---

## Step 8: Optional - Add Custom Event Tracking

### Add Event Tracking to Product Pages

In `product.php`, add this JavaScript after the product is loaded:

```javascript
<script>
// Track ViewContent event
fbq('track', 'ViewContent', {
    content_ids: ['<?php echo $product['id']; ?>'],
    content_name: '<?php echo htmlspecialchars($product['name']); ?>',
    content_category: '<?php echo htmlspecialchars($categoryMap[$product['category_id']]['name'] ?? ''); ?>',
    value: <?php echo getProductPrice($product); ?>,
    currency: '<?php echo $selectedCurrency; ?>'
});
</script>
```

### Add Event Tracking to Cart

In `api/cart.php`, after successful add to cart:

```javascript
fbq('track', 'AddToCart', {
    content_ids: [productId],
    content_name: productName,
    value: price,
    currency: currentCurrency
});
```

### Add Event Tracking to Checkout

In `order-success.php`, after successful order:

```javascript
fbq('track', 'Purchase', {
    content_ids: productIds,
    value: totalAmount,
    currency: currency,
    transaction_id: orderId
});
```

---

## Step 9: Schedule Feed Updates

To keep your Instagram Shop synced, set up automatic feed updates:

### Using Cron Job (Recommended)

1. Access your server's cron scheduler
2. Add a job to fetch the feed hourly:

```bash
0 * * * * wget -O /dev/null https://yourdomain.com/api/facebook-feed.php
```

### Using Webhook (Advanced)

You can create a webhook to update Facebook when products change in your admin panel.

---

## Step 10: Monitor and Optimize

### View Product Sync Status

1. In Commerce Manager, go to **Commerce Manager** → **Catalogs**
2. Click on your catalog
3. View **Product Status** to see:
   - Successfully synced products
   - Products with errors
   - Out of stock items

### View Pixel Events

1. Go to **Events Manager**
2. Click on your Pixel
3. View **Test Events** to see real-time events
4. View **Overview** to see aggregate event data

### Create Dynamic Ads

Once pixel data is collected, you can:

1. Go to **Ads Manager**
2. Click **Create Ad**
3. Choose **Catalog Sales** objective
4. Select your catalog
5. Create dynamic product ads for Facebook and Instagram

---

## Troubleshooting

### Products Not Showing in Instagram Shop

**Problem:** Products aren't appearing in Instagram

**Solutions:**
1. Check Commerce Manager for sync errors
2. Verify feed URL is accessible: `https://yourdomain.com/api/facebook-feed.php`
3. Ensure product images are at least 500x500px
4. Check that products are marked "active" in your admin panel
5. Verify Instagram Shopping approval status

### Pixel Not Tracking Events

**Problem:** Events not appearing in Events Manager

**Solutions:**
1. Verify Pixel ID in `.env` matches Events Manager
2. Check browser console for errors
3. Use Facebook Pixel Helper to see tracking status
4. Verify `.env` file exists and is readable
5. Clear browser cache and test again

### Feed Upload Fails

**Problem:** Commerce Manager shows feed errors

**Solutions:**
1. Access feed URL directly to see errors
2. Check for special characters in product descriptions
3. Verify all image URLs are accessible
4. Ensure prices are valid numbers
5. Check that TSV format is correct (tabs between fields)

### Instagram Shopping Not Approved

**Problem:** Instagram Shopping review was rejected

**Solutions:**
1. Review rejection reasons in Commerce Manager
2. Ensure website has:
   - Contact information
   - Return policy
   - Terms of service
   - Privacy policy
   - Secure checkout (HTTPS)
3. Improve product descriptions and images
4. Resubmit after addressing issues

---

## Additional Features

### Facebook Messenger Chat Widget

To add customer chat to your website, add this code to `includes/footer.php`:

```html
<!-- Messenger Plugin Code -->
<div id="fb-root"></div>
<script>
  window.fbAsyncInit = function() {
    FB.init({
      xfbml            : true,
      version          : 'v18.0'
    });
  };
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = 'https://connect.facebook.net/en_US/sdk/xfbml.customerchat/xfbml.customerchat.js';
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));
</script>

<div class="fb-customerchat"
  attribution="setup_tool"
  page_id="YOUR_FACEBOOK_PAGE_ID">
</div>
```

Add to `.env`:

```env
FACEBOOK_PAGE_ID=your_page_id_here
```

---

## Configuration Summary

Update your `.env` file with these values:

```env
# Facebook Pixel ID
FACEBOOK_PIXEL_ID=123456789012345

# Meta App Configuration
FACEBOOK_APP_ID=your_app_id_here
FACEBOOK_APP_SECRET=your_app_secret_here
FACEBOOK_ACCESS_TOKEN=your_long_access_token_here

# Facebook/Instagram Configuration
FACEBOOK_PAGE_ID=your_facebook_page_id_here
INSTAGRAM_BUSINESS_ACCOUNT_ID=your_instagram_account_id_here

# Product Feed Settings
FACEBOOK_FEED_CURRENCY=GBP
FACEBOOK_FEED_LANGUAGE=en
```

---

## Files Modified/Created

1. **`.env`** - Added Facebook/Meta configuration variables
2. **`api/facebook-feed.php`** - New file that generates Facebook-compatible product feed
3. **`includes/header.php`** - Added Facebook Pixel tracking code

---

## Next Steps

1. ✅ Set up Meta Business Suite account
2. ✅ Create Facebook Pixel and add to website
3. ✅ Create Facebook App for Commerce Manager
4. ✅ Set up Commerce Manager and create catalog
5. ✅ Connect product feed URL
6. ✅ Enable Instagram Shopping
7. ✅ Test integration
8. ✅ Set up automated feed updates
9. ✅ Monitor and optimize

---

## Support Resources

- **Meta Business Help Center**: https://www.facebook.com/business/help
- **Instagram Shopping Help**: https://help.instagram.com/1159818363404168
- **Facebook Pixel Documentation**: https://developers.facebook.com/docs/meta-pixel
- **Commerce Manager Guide**: https://www.facebook.com/business/help/889059701331525

---

## Important Notes

1. **Product Images**: Must be high quality (min 500x500px) and show the actual product
2. **Product Descriptions**: Be accurate and detailed (under 5000 characters)
3. **Pricing**: Must match between your website and Instagram Shop
4. **Stock**: Out-of-stock items won't appear in Instagram Shop
5. **Approval Time**: Instagram Shopping approval typically takes 1-3 business days
6. **Feed Updates**: Your feed will sync automatically on the schedule you set
7. **Pixel Privacy**: Ensure your privacy policy mentions Facebook tracking

---

## Quick Reference

### Feed URL
```
https://yourdomain.com/api/facebook-feed.php
```

### Test Feed
Visit the feed URL in your browser to verify it's working

### Pixel Helper
Install Facebook Pixel Helper Chrome extension for testing

### Commerce Manager
https://business.facebook.com/commerce

### Events Manager
https://business.facebook.com/events_manager

---

**Need Help?**
- Check Commerce Manager for specific error messages
- Review feed output for formatting issues
- Use Pixel Helper to verify tracking
- Contact Meta Business Support for account issues
