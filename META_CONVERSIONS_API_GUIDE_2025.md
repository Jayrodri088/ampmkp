# Meta Conversions API + Pixel Dual Tracking (2024-2025 Implementation)

## Overview

This guide implements **Meta's recommended 2024-2025 approach**: **Dual Tracking System**

```
┌─────────────────────────────────────────────────────────────────┐
│                  META INTEGRATION ARCHITECTURE               │
├─────────────────────────────────────────────────────────────────┤
│                                                           │
│  ┌──────────────┐         ┌───────────────────┐   │
│  │              │         │                   │   │
│  │ META PIXEL   │         │  CONVERSIONS API  │   │
│  │ (Browser)    │         │  (Server-Side)   │   │
│  │              │         │                   │   │
│  └──────────────┘         └───────────────────┘   │
│         │                        │                   │
│         │                        │                   │
│         └────────────┬───────────┘                   │
│                      │                              │
│               ┌──────▼────────────┐                │
│               │                 │                │
│               │  DEDUPLICATION  │                │
│               │  (Event IDs)    │                │
│               │                 │                │
│               └────────────────┘                │
│                      │                              │
│               ┌──────▼────────────┐                │
│               │                 │                │
│               │  EVENTS MANAGER │                │
│               │                 │                │
│               └────────────────┘                │
│                      │                              │
│         ┌──────▼────────────┐                │
│         │                 │                │
│         │  INSTAGRAM SHOP  │                │
│         │                 │                │
│         └────────────────┘                │
│                                          │
└──────────────────────────────────────────────────┘
```

## Why Dual Tracking? (Meta's 2024 Recommendation)

Meta now recommends implementing **both Pixel AND Conversions API** for:

### 1. **Redundancy & Reliability**
- **Pixel**: Captures browser events but fails with:
  - Ad blockers
  - Network issues
  - Page loading errors
  - ITP (Intelligent Tracking Prevention in Safari)
  
- **Conversions API**: Server-side, more reliable
  - Not affected by browser limitations
  - Captures offline events
  - Works even if user leaves page before load completes

### 2. **Deeper Funnel Visibility**
- **Pixel**: Website interactions only
- **Conversions API**: Can track:
  - CRM data
  - Offline sales (phone, in-store)
  - Qualified leads
  - Multi-site customer journeys
  - Backend order confirmations

### 3. **Data Control**
- Choose what data to share
- Add custom parameters (customer LTV, profit margins)
- Comply with privacy regulations
- GDPR-compliant by default

### 4. **Better Attribution**
- Higher Event Match Quality (EMQ) with more customer data
- Accurate cross-device tracking
- Better for ad optimization
- Lower cost per conversion

---

## Architecture Implementation

### Files Created/Modified

1. **`.env`** - Configuration for both Pixel and Conversions API
2. **`includes/meta-integration.php`** - Server-side Conversions API wrapper
3. **`includes/header.php`** - Meta Pixel client-side code
4. **`api/facebook-feed.php`** - Product feed for Instagram Shop
5. **This guide** - Complete implementation instructions

---

## Step 1: Update .env Configuration

Add these variables to your `.env` file:

```env
# ===========================================
# Meta (Facebook/Instagram) Configuration
# ===========================================

# Facebook Pixel ID for client-side tracking
FACEBOOK_PIXEL_ID=123456789012345

# Conversions API Configuration
# Access token with pages_manage_catalog and pages_read_engagement permissions
FACEBOOK_ACCESS_TOKEN=EAAxxxxxxxxxxxxxxx

# Meta App Configuration
FACEBOOK_APP_ID=123456789012345
FACEBOOK_APP_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Testing Mode
# Set to 'true' for development, 'false' for production
FACEBOOK_CONVERSIONS_TEST_MODE=false

# Commerce Manager Configuration
FACEBOOK_PAGE_ID=123456789012345
INSTAGRAM_BUSINESS_ACCOUNT_ID=123456789012345

# Product Feed Settings
FACEBOOK_FEED_CURRENCY=GBP
FACEBOOK_FEED_LANGUAGE=en
```

### How to Get These Values

#### FACEBOOK_PIXEL_ID
1. Go to: https://business.facebook.com/events_manager
2. Click **Connect Data Sources** → **Facebook Pixel**
3. Create new pixel or select existing
4. Copy **Pixel ID** (15-16 digit number)

#### FACEBOOK_ACCESS_TOKEN
1. Go to: https://developers.facebook.com/tools/explorer
2. Select your app
3. Select **Page Access Token**
4. Grant permissions: `pages_manage_catalog`, `pages_read_engagement`
5. Copy the token

#### FACEBOOK_APP_ID & SECRET
1. Go to: https://developers.facebook.com/apps
2. Select your app
3. Go to **Settings** → **Basic**
4. Copy **App ID** and **App Secret**

#### FACEBOOK_PAGE_ID
1. Go to: https://business.facebook.com/settings
2. Click **Accounts** → **Pages**
3. Find your business page
4. Copy **Page ID** from URL or hover over page name

---

## Step 2: Verify Installation

### Check File Structure

```
ampmkp/
├── .env                                    ← Update this
├── includes/
│   ├── header.php                          ← Pixel added
│   └── meta-integration.php                 ← New file (Conversions API)
└── api/
    └── facebook-feed.php                     ← New file (product feed)
```

### Test Pixel (Client-Side)

1. Install [Facebook Pixel Helper](https://chrome.google.com/webstore/detail/facebook-pixel-helper/)
2. Visit your website
3. Check that Pixel Helper shows:
   ```
   ✓ Pixel is active
   ✓ PageView event detected
   ```

### Test Conversions API (Server-Side)

Create a test file `test-meta-api.php`:

```php
<?php
require_once 'includes/meta-integration.php';

$meta = new MetaIntegration();

// Test Lead event
$result = $meta->trackLead('test', [
    'email' => 'test@example.com',
    'first_name' => 'Test',
    'last_name' => 'User'
]);

echo '<pre>';
print_r($result);
echo '</pre>';
?>
```

Visit: `https://yourdomain.com/test-meta-api.php`

Expected output:
```
Array
(
    [success] => 1
    [http_code] => 200
    [response] => Array
        (
            [fbtrace_id] => xxxxx
            [events_received] => 1
            [messages] => Array()
        )
)
```

---

## Step 3: Implement Event Tracking

### Product Page View (product.php)

```php
<?php
require_once 'includes/meta-integration.php';

// Get product data
$product = getProductById($productId);
$category = getCategoryById($product['category_id']);
$currency = getSelectedCurrency();
$price = getProductPrice($product, $currency);

// Generate deduplication ID (same for Pixel + Conversions API)
$eventId = MetaIntegration::generateEventId('ViewContent', [$productId]);

// 1. Track with Meta Pixel (Client-Side) - Already in header.php
?>
<script>
// This fires automatically when page loads (already in header.php)
</script>

<?php
// 2. Track with Conversions API (Server-Side)
$meta = new MetaIntegration();

$userData = [
    'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '',
    'first_name' => isset($_SESSION['user_firstname']) ? $_SESSION['user_firstname'] : '',
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
];

$meta->trackViewContent(
    $product['id'],
    $product['name'],
    $price,
    $category['name'],
    $userData
);
?>
```

### Add to Cart (api/cart.php)

```php
<?php
require_once '../includes/meta-integration.php';

$meta = new MetaIntegration();

// After successful cart addition
if ($success) {
    $userData = [
        'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '',
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // Server-side tracking
    $meta->trackAddToCart(
        $productId,
        $productName,
        $quantity,
        $price,
        $category,
        $userData
    );

    // Client-side Pixel event (already handles this with same eventId)
    ?>
    <script>
    fbq('track', 'AddToCart', {
        content_ids: ['<?php echo $eventId; ?>'],
        content_name: '<?php echo $productName; ?>',
        content_type: 'product',
        value: <?php echo $price * $quantity; ?>,
        currency: '<?php echo $currency; ?>',
        eventID: '<?php echo $eventId; ?>'
    });
    </script>
    <?php
}
?>
```

### Checkout Initiation (checkout.php)

```php
<?php
require_once 'includes/meta-integration.php';

$meta = new MetaIntegration();

$cart = getCart();
$cartItems = [];
$totalValue = 0;

foreach ($cart as $item) {
    $product = getProductById($item['product_id']);
    $category = getCategoryById($product['category_id']);
    $price = getProductPrice($product);
    
    $cartItems[] = [
        'product_id' => $product['id'],
        'name' => $product['name'],
        'quantity' => $item['quantity'],
        'price' => $price,
        'category' => $category['name']
    ];
    
    $totalValue += $price * $item['quantity'];
}

$userData = [
    'email' => $_POST['email'] ?? '',
    'first_name' => $_POST['first_name'] ?? '',
    'last_name' => $_POST['last_name'] ?? '',
    'city' => $_POST['city'] ?? '',
    'state' => $_POST['state'] ?? '',
    'country' => $_POST['country'] ?? '',
    'zip' => $_POST['zip'] ?? '',
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
];

// Server-side tracking
$meta->trackInitiateCheckout($cartItems, $totalValue, $userData);

// Client-side Pixel event
$eventId = MetaIntegration::generateEventId('InitiateCheckout', [session_id()]);
?>
<script>
fbq('track', 'InitiateCheckout', {
    content_ids: [<?php echo json_encode(array_column($cartItems, 'product_id')); ?>],
    content_type: 'product',
    value: <?php echo $totalValue; ?>,
    currency: '<?php echo getSelectedCurrency(); ?>',
    num_items: <?php echo count($cartItems); ?>,
    eventID: '<?php echo $eventId; ?>'
});
</script>
```

### Purchase Completion (order-success.php)

```php
<?php
require_once 'includes/meta-integration.php';

$meta = new MetaIntegration();

// Get order data
$orderId = $_GET['order_id'] ?? $_SESSION['last_order_id'];
$order = getOrderById($orderId);

$userData = [
    'email' => $order['customer_email'],
    'first_name' => $order['customer_firstname'],
    'last_name' => $order['customer_lastname'],
    'city' => $order['customer_city'],
    'state' => $order['customer_state'],
    'country' => $order['customer_country'],
    'zip' => $order['customer_zip'],
    'ip' => $order['customer_ip'],
    'user_agent' => $order['user_agent']
];

// Server-side tracking
$meta->trackPurchase(
    $order['id'],
    $order['items'],
    $order['total'],
    $userData
);

// Client-side Pixel event
$eventId = MetaIntegration::generateEventId('Purchase', [$orderId]);
?>
<script>
fbq('track', 'Purchase', {
    content_ids: [<?php echo json_encode(array_column($order['items'], 'product_id')); ?>],
    content_type: 'product',
    value: <?php echo $order['total']; ?>,
    currency: '<?php echo $order['currency']; ?>,
    num_items: <?php echo count($order['items']); ?>,
    order_id: '<?php echo $order['id']; ?>',
    eventID: '<?php echo $eventId; ?>'
});
</script>
```

### Contact Form (contact.php)

```php
<?php
require_once 'includes/meta-integration.php';

$meta = new MetaIntegration();

// After form submission
if ($formSubmitted) {
    $userData = [
        'email' => $_POST['email'],
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // Server-side tracking
    $meta->trackContact($userData);

    // Client-side Pixel event
    $eventId = MetaIntegration::generateEventId('Contact', [session_id()]);
    ?>
    <script>
    fbq('track', 'Contact', {
        eventID: '<?php echo $eventId; ?>'
    });
    </script>
    <?php
}
?>
```

### Newsletter Signup (api/newsletter.php)

```php
<?php
require_once '../includes/meta-integration.php';

$meta = new MetaIntegration();

// After successful subscription
if ($success) {
    $userData = [
        'email' => $email,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // Server-side tracking
    $meta->trackLead('newsletter_subscription', $userData);

    // Client-side Pixel event
    $eventId = MetaIntegration::generateEventId('Lead', ['newsletter', $email]);
    ?>
    <script>
    fbq('track', 'Lead', {
        content_name: 'Newsletter Subscription',
        content_category: 'newsletter',
        eventID: '<?php echo $eventId; ?>'
    });
    </script>
    <?php
}
?>
```

---

## Step 4: Deduplication Explained

### How It Works

Both Pixel and Conversions API send the **same event** with the **same ID**:

```
Timeline: User clicks "Add to Cart"
│
├─ T=0s: Pixel fires (client-side)
│   eventID: "abc123..."
│   Event received by Meta ✓
│
├─ T=0.1s: Conversions API fires (server-side)
│   event_id: "abc123..."
│   Meta detects duplicate ✗
│   Keeps Pixel event (first one received)
│
└─ Result: Event counted ONCE ✓
```

### Event ID Generation

Both use **same method** to generate ID:

```php
// In meta-integration.php (Conversions API)
public static function generateEventId($eventName, $context = []) {
    $contextString = implode('_', array_merge([$eventName], $context));
    return md5($contextString . microtime(true));
}

// In JavaScript (Pixel)
const eventId = md5('ViewContent_' + productId + '_' + Date.now());
fbq('track', 'ViewContent', {
    eventID: eventId
});
```

### Deduplication Rules (Meta 2024)

1. **48-hour window**: Events within 48 hours with matching IDs are duplicates
2. **Event Name**: Must match exactly (`ViewContent` = `ViewContent`)
3. **Event ID**: Must be identical string
4. **Priority**: If within 5 minutes, Pixel event wins
5. **After 5 minutes**: First event received wins

### When Deduplication Isn't Needed

- If you send different events through different channels
- Example: `ViewContent` via Pixel, `Purchase` via Conversions API only
- No risk of double-counting

---

## Step 5: Set Up Commerce Manager (Instagram Shop)

### Create Catalog

1. Go to: https://business.facebook.com/commerce
2. Click **Add Catalog**
3. Choose **E-commerce Catalog**
4. Name it: "Angel Marketplace Catalog"
5. Click **Create Catalog**

### Connect Product Feed

1. In your new catalog, go to **Data Sources**
2. Click **Add Data Source**
3. Choose **Scheduled Fetch**
4. Enter feed URL: `https://yourdomain.com/api/facebook-feed.php`
5. Set update frequency: **Hourly** (for stock changes)
6. Click **Create Feed**
7. Wait for initial fetch (5-15 minutes)
8. Review products in catalog

### Enable Instagram Shopping

**Requirements:**
- ✅ Business Manager account
- ✅ Facebook Page connected to Instagram
- ✅ Catalog with products
- ✅ HTTPS website
- ✅ Contact information on website
- ✅ Return policy
- ✅ Terms of service
- ✅ Privacy policy

**Steps:**
1. In Commerce Manager, go to **Commerce Accounts**
2. Select your commerce account
3. Click **Start Set Up** next to Instagram Shopping
4. Select your Instagram Business account
5. Review merchant agreement
6. Click **Submit for Review**
7. Wait for approval (1-3 business days)

---

## Step 6: Monitor & Optimize

### Check Event Match Quality (EMQ)

1. Go to: https://business.facebook.com/events_manager
2. Click on your Pixel
3. View **Event Match Quality** tab
4. Each event has score 0-10

**Improving EMQ:**
- **0-3**: Poor - Add more customer data (email, phone, name)
- **4-6**: Good - Add location data (city, state, zip)
- **7-10**: Excellent - Full customer information

### Use Test Events Tool

1. Go to: https://business.facebook.com/events_manager/tools/test-events
2. Select your Pixel/App
3. Add test event:
```json
{
  "event_name": "ViewContent",
  "event_time": 1234567890,
  "event_id": "test123",
  "user_data": {
    "em": "a4c7f1b8e3a5b5e6c7c8f9a8d5b6e7a8f9c8f5a6b7e8",
    "fn": "a4c7f1b8e3a5b5e6c7c8f9a8d5b6e7a8f9c8f5a6b7e8",
    "ln": "b8e3a5b5e6c7c8f9a8d5b6e7a8f9c8f5a6b7e8"
  },
  "custom_data": {
    "contents": [{
      "id": "123",
      "quantity": 1,
      "item_price": 29.99,
      "title": "Test Product"
    }],
    "value": 29.99,
    "currency": "GBP"
  },
  "action_source": "website"
}
```
4. Click **Send Test Event**
5. Verify it appears in Events Manager

### View Attribution

1. Go to: https://business.facebook.com/attribution
2. View conversion attribution data
3. Analyze which channels drive conversions
4. Optimize ad spend accordingly

---

## Advanced Features

### Batch Events (More Efficient)

Instead of sending events one-by-one, batch them:

```php
<?php
$meta = new MetaIntegration();

$events = [];

// Collect multiple events
foreach ($recentOrders as $order) {
    $events[] = [
        'event_name' => 'Purchase',
        'event_time' => strtotime($order['created_at']),
        'event_id' => MetaIntegration::generateEventId('Purchase', [$order['id']]),
        'user_data' => [
            'em' => hash('sha256', strtolower($order['customer_email'])),
            'fn' => hash('sha256', strtolower($order['customer_firstname'])),
            'ln' => hash('sha256', strtolower($order['customer_lastname']))
        ],
        'custom_data' => [
            'value' => $order['total'],
            'currency' => 'GBP',
            'order_id' => $order['id']
        ],
        'action_source' => 'website'
    ];
}

// Send all at once
$result = $meta->sendBatchEvents($events);

error_log('Sent ' . count($events) . ' events in batch');
?>
```

**Benefits:**
- Fewer API calls
- Faster processing
- Better for high-volume sites

### Offline Events

Track in-store or phone orders:

```php
<?php
$meta = new MetaIntegration();

// Offline phone order
$meta->trackPurchase($orderId, $items, $total, [
    'email' => $customerEmail,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'phone' => $phone // Phone is key for offline events
]);

// Use different action_source
// Note: You'll need to modify trackPurchase to accept action_source parameter
```

---

## Security & Privacy

### Data Hashing (Required by Meta)

The Conversions API automatically hashes:
- Email addresses (SHA256)
- Phone numbers (SHA256)
- First names (SHA256)
- Last names (SHA256)

**Not hashed:**
- IP addresses (for security)
- User agents (for analytics)
- `fbp` and `fbc` (first-party cookies)

### GDPR Compliance

1. **Inform users**: Add to privacy policy
   > "We use Meta Conversions API to measure advertising effectiveness"

2. **Consent**: Only track if user consents
```php
<?php
if (isset($_COOKIE['marketing_consent']) && $_COOKIE['marketing_consent'] === 'true') {
    $meta->trackEvent(...);
}
?>
```

3. **Right to deletion**: Provide opt-out in privacy policy

---

## Troubleshooting

### Events Not Appearing

**Check:**
1. Verify Pixel ID in `.env` matches Events Manager
2. Check access token has correct permissions
3. Verify test mode is `false` in production
4. Check server error logs: `logs/error.log`
5. Use Facebook Pixel Helper extension

### Deduplication Not Working

**Check:**
1. Event IDs match exactly (case-sensitive)
2. Event names match exactly
3. Events sent within 48 hours
4. Use Test Events tool to verify

### High Event Drop Rate

**Causes:**
- Missing customer data (add email, phone)
- Invalid parameters (use Payload Helper)
- Rate limiting (batch events)
- Access token expired

**Solutions:**
- Add more user data
- Verify parameters with [Payload Helper](https://developers.facebook.com/docs/marketing-api/conversions-api/payload-helper)
- Use batch events
- Refresh access token

---

## Migration from Pixel-Only

If you currently only use Pixel:

1. **Install Conversions API** (this guide)
2. **Keep Pixel running** (don't remove)
3. **Test dual tracking** (use Test Events tool)
4. **Monitor for duplicates** (should be none if deduplication works)
5. **Gradually add customer data** (improve EMQ)

**No need to**:
- Remove existing Pixel code
- Change event tracking logic
- Modify ad campaigns

---

## Performance Best Practices

### Send Events in Real-Time

```php
<?php
// BAD: Delay sending
register_shutdown_function(function() {
    $meta->trackPurchase(...); // Fires after page load
});

// GOOD: Send immediately
$meta->trackPurchase(...); // Fires as soon as data is available
?>
```

### Use Event Match Quality Optimization

**High EMQ (7-10):**
- Email + Phone + Name + Location
- Best for ad optimization

**Medium EMQ (4-6):**
- Email + Name
- Good for measurement

**Low EMQ (0-3):**
- Email only
- Basic tracking, poor optimization

### Avoid Rate Limiting

```php
<?php
// BAD: 100 individual requests
foreach ($orders as $order) {
    $meta->trackPurchase(...); // 100 API calls
}

// GOOD: 1 batch request
$events = [];
foreach ($orders as $order) {
    $events[] = buildEvent($order);
}
$meta->sendBatchEvents($events); // 1 API call
?>
```

---

## Resources

### Official Documentation
- [Conversions API](https://developers.facebook.com/docs/marketing-api/conversions-api)
- [Best Practices](https://developers.facebook.com/docs/marketing-api/conversions-api/best-practices)
- [Payload Helper](https://developers.facebook.com/docs/marketing-api/conversions-api/payload-helper)
- [Deduplication](https://developers.facebook.com/docs/marketing-api/conversions-api/deduplicate-pixel-and-server-events)
- [End-to-End Implementation](https://developers.facebook.com/docs/marketing-api/conversions-api/guides/end-to-end-implementation)

### Tools
- [Test Events Tool](https://business.facebook.com/events_manager/tools/test-events)
- [Events Manager](https://business.facebook.com/events_manager)
- [Commerce Manager](https://business.facebook.com/commerce)
- [Ads Manager](https://business.facebook.com/adsmanager)

### Browser Extensions
- [Facebook Pixel Helper](https://chrome.google.com/webstore/detail/facebook-pixel-helper/)
- [Meta Pixel Debugger](https://developers.facebook.com/tools/debug/)

---

## Summary Checklist

### Pre-Implementation
- [ ] Created Meta Business Manager account
- [ ] Created Facebook Page
- [ ] Connected Instagram Business account
- [ ] Created Facebook App
- [ ] Generated Pixel ID
- [ ] Generated Access Token
- [ ] Installed Composer dependencies

### Implementation
- [ ] Updated `.env` with all Meta variables
- [ ] Created `includes/meta-integration.php`
- [ ] Updated `includes/header.php` with Pixel
- [ ] Created `api/facebook-feed.php`
- [ ] Added event tracking to product pages
- [ ] Added event tracking to cart
- [ ] Added event tracking to checkout
- [ ] Added event tracking to order success
- [ ] Added event tracking to contact forms

### Testing
- [ ] Pixel verified with Pixel Helper
- [ ] Conversions API test successful
- [ ] Deduplication verified (no duplicates)
- [ ] Product feed accessible
- [ ] Events appearing in Events Manager
- [ ] EMQ scores acceptable (>4)

### Launch
- [ ] Set up Commerce Manager catalog
- [ ] Connected product feed
- [ ] Submitted Instagram Shopping application
- [ ] Instagram Shopping approved
- [ ] Created dynamic ads (optional)
- [ ] Set up feed automation (cron job)
- [ ] Monitoring dashboard set up

---

## Quick Reference

### Event Types

| Event Name | Use Case | When to Track |
|------------|-------------|---------------|
| `ViewContent` | Product page view | On product.php |
| `AddToCart` | Add to cart | After cart addition |
| `InitiateCheckout` | Start checkout | On checkout.php |
| `Purchase` | Order complete | On order-success.php |
| `Lead` | Form submission | Contact, newsletter, vendor application |
| `Contact` | Contact form | On contact submission |

### Required Parameters

**For All Events:**
- `event_name`
- `event_time`
- `event_id` (for deduplication)
- `action_source`
- `user_data` (at least one parameter)

**For Website Events:**
- `event_source_url`

**For Product Events:**
- `custom_data.contents`
- `custom_data.value`
- `custom_data.currency`

### Customer Data Parameters (for EMQ)

| Parameter | Type | Hashed? | EMQ Impact |
|-----------|------|----------|-------------|
| `em` (email) | SHA256 | ✓ High |
| `ph` (phone) | SHA256 | ✓ High |
| `fn` (first name) | SHA256 | ✓ Medium |
| `ln` (last name) | SHA256 | ✓ Medium |
| `ct` (city) | No | ✓ Low |
| `st` (state) | No | ✓ Low |
| `zp` (zip) | No | ✓ Low |
| `country` (country code) | No | ✓ Low |
| `client_ip_address` | No | ✓ Medium |
| `client_user_agent` | No | ✓ Low |
| `fbc` / `fbp` | No | ✓ Medium |

---

**Need Help?**

1. Check error logs: `logs/error.log`
2. Use Test Events tool: business.facebook.com/events_manager/tools/test-events
3. Verify parameters with Payload Helper
4. Review Meta documentation: developers.facebook.com/docs/marketing-api/conversions-api

**Last Updated:** January 2025  
**Meta API Version:** v19.0  
**PHP Version:** 7.4+
