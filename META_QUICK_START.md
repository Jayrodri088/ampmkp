# Quick Start: Meta Conversions API Implementation

## What Changed?

We're moving from **Pixel-only tracking** to **Dual Tracking** (Meta's 2024-2025 recommendation):

```
OLD (2023):
┌──────────┐
│  PIXEL   │
└──────────┘
  ↓
Facebook Events

NEW (2025):
┌──────────┐  +  ┌──────────────┐
│  PIXEL   │  →  │ CONVERSIONS API │
└──────────┘     └──────────────┘
      ↓                  ↓
  Facebook ←──────────┘
   Events          │
                    ↓
        Instagram Shop
```

## 3 Simple Steps

### Step 1: Update .env (5 minutes)

```env
FACEBOOK_PIXEL_ID=your_15_digit_pixel_id
FACEBOOK_ACCESS_TOKEN=your_long_access_token
FACEBOOK_APP_ID=your_app_id
FACEBOOK_APP_SECRET=your_app_secret
FACEBOOK_PAGE_ID=your_page_id
INSTAGRAM_BUSINESS_ACCOUNT_ID=your_instagram_id
FACEBOOK_CONVERSIONS_TEST_MODE=false
```

### Step 2: Verify Files (2 minutes)

Check these exist:
- `includes/meta-integration.php` ← NEW (Conversions API)
- `includes/header.php` ← Has Pixel code
- `api/facebook-feed.php` ← Product feed
- `.env` ← Updated with Meta config

### Step 3: Add Tracking Code (15 minutes)

**Option A: Minimal (Just Conversions API)**

In `product.php` (add after product data is loaded):
```php
<?php
require_once 'includes/meta-integration.php';
$meta = new MetaIntegration();

// Track product view
$meta->trackViewContent(
    $product['id'],
    $product['name'],
    $price,
    $category['name']
);
?>
```

**Option B: Dual Tracking (Recommended)**

```php
<?php
require_once 'includes/meta-integration.php';
$meta = new MetaIntegration();

// Server-side (Conversions API)
$meta->trackViewContent(
    $product['id'],
    $product['name'],
    $price,
    $category['name']
);

// Client-side (Pixel) - Uses same event ID for deduplication
$eventId = MetaIntegration::generateEventId('ViewContent', [$product['id']]);
?>
<script>
fbq('track', 'ViewContent', {
    eventID: '<?php echo $eventId; ?>'
});
</script>
```

### Step 4: Test (5 minutes)

1. **Test Pixel**: Install [Facebook Pixel Helper](https://chrome.google.com/webstore/detail/facebook-pixel-helper/) and visit your site
2. **Test API**: Create `test-meta.php` and visit it
3. **Check Events Manager**: Go to business.facebook.com/events_manager

### Step 5: Set Up Instagram Shop (30 minutes)

1. Go to https://business.facebook.com/commerce
2. Create E-commerce Catalog
3. Add data source: `https://yourdomain.com/api/facebook-feed.php`
4. Enable Instagram Shopping
5. Wait for approval (1-3 days)

## Where to Add Tracking?

| File | Events to Track |
|-------|-----------------|
| `product.php` | ViewContent |
| `api/cart.php` (add item) | AddToCart |
| `checkout.php` | InitiateCheckout |
| `order-success.php` | Purchase |
| `contact.php` | Contact |
| `api/newsletter.php` | Lead |

## What's Already Done?

✅ Created `includes/meta-integration.php` - Conversions API wrapper
✅ Updated `includes/header.php` - Pixel tracking code
✅ Created `api/facebook-feed.php` - Instagram Shop product feed
✅ Updated `.env.example` - Meta configuration variables
✅ Created comprehensive guide: `META_CONVERSIONS_API_GUIDE_2025.md`

## Next Actions

1. **Fill in .env**: Add your actual Meta credentials
2. **Install dependencies**: Run `composer require facebook/php-business-sdk` (optional, for advanced features)
3. **Add tracking**: Implement event tracking in key files
4. **Test thoroughly**: Verify both Pixel and API are working
5. **Set up Commerce Manager**: Create catalog and connect feed
6. **Enable Instagram Shopping**: Submit for approval

## Quick Debugging

### Events Not Showing?

1. Check `.env` values are correct
2. Verify access token has right permissions
3. Check error logs: `logs/error.log`
4. Use Test Events tool: business.facebook.com/events_manager/tools/test-events
5. Install Pixel Helper extension

### Duplicates in Events Manager?

**Good!** Means deduplication is working. Both Pixel and API send same event with same ID.

**Bad!** Event IDs or names don't match exactly.

### Instagram Shop Not Updating?

1. Visit `https://yourdomain.com/api/facebook-feed.php` directly
2. Check feed has all products
3. In Commerce Manager, check feed status for errors
4. Ensure product images are accessible URLs

## Benefits You'll Get

✅ **Better Tracking**: Pixel + API = More accurate data
✅ **Cookie-Proof**: Server-side tracking works even with ad blockers
✅ **Offline Events**: Track phone orders, CRM data
✅ **Instagram Shop**: Products sync automatically from your JSON database
✅ **Better Ads**: Higher Event Match Quality = Lower cost per conversion
✅ **Future-Proof**: Complies with privacy regulations

## Documentation

- **Quick Start**: This file
- **Full Guide**: `META_CONVERSIONS_API_GUIDE_2025.md`
- **Original Guide**: `META_SHOP_INTEGRATION_GUIDE.md`
- **Meta Docs**: https://developers.facebook.com/docs/marketing-api/conversions-api

## Support

- **Meta Help Center**: https://www.facebook.com/business/help
- **Developers Forum**: https://developers.facebook.com/community
- **Stack Overflow**: Tag questions with `facebook-marketing-api` and `conversions-api`

---

**Time to Complete:** ~1-2 hours  
**Difficulty:** Medium  
**Maintenance:** Low (automatic once set up)

Happy tracking! 🚀
