# Stripe Payment Integration Setup

This document explains how to set up Stripe payments for Angel Marketplace.

## Features Implemented

✅ **Complete Stripe Integration**
- Stripe Checkout sessions with product line items
- Automatic shipping calculation
- Customer data collection
- Order creation and management
- Success and cancel page handling
- Secure payment processing

✅ **Payment Flow**
1. Customer fills out shipping information
2. Selects "Stripe Checkout" payment method
3. Clicks "Pay with Stripe" button
4. Redirected to Stripe hosted checkout
5. Completes payment securely on Stripe
6. Redirected back to success page
7. Order automatically created and cart cleared

## Setup Instructions

### 1. Get Stripe API Keys

1. Create a Stripe account at [stripe.com](https://stripe.com)
2. Go to **Developers** → **API Keys**
3. Copy your **Publishable Key** (starts with `pk_test_` for test mode)
4. Copy your **Secret Key** (starts with `sk_test_` for test mode)

### 2. Configure API Keys

Edit `includes/stripe-config.php` and replace the test keys:

```php
// Test API Keys (replace with your actual keys)
const TEST_SECRET_KEY = 'sk_test_YOUR_SECRET_KEY_HERE';
const TEST_PUBLISHABLE_KEY = 'pk_test_YOUR_PUBLISHABLE_KEY_HERE';
```

### 3. Environment Configuration

For production, update the environment:

```php
// Environment (set to 'live' in production)
const ENVIRONMENT = 'live';

// Live API Keys (set these in production)
const LIVE_SECRET_KEY = 'sk_live_YOUR_LIVE_SECRET_KEY';
const LIVE_PUBLISHABLE_KEY = 'pk_live_YOUR_LIVE_PUBLISHABLE_KEY';
```

### 4. Test the Integration

1. Add items to cart
2. Go to checkout
3. Fill in customer information
4. Select "Stripe Checkout" payment method
5. Click "Pay with Stripe"
6. Use test card numbers from Stripe documentation:
   - **Success**: 4242424242424242
   - **Decline**: 4000000000000002
   - **3D Secure**: 4000002500003155

## File Structure

```
├── includes/stripe-config.php          # Stripe configuration
├── api/stripe-checkout.php             # Creates checkout sessions
├── stripe-success.php                  # Success page handler
├── stripe-cancel.php                   # Cancel page handler
├── checkout.php                        # Updated with Stripe option
└── vendor/                             # Stripe PHP SDK
```

## Security Notes

- ✅ API keys are properly separated (test/live)
- ✅ Secret keys are server-side only
- ✅ Customer data validation
- ✅ Secure checkout session creation
- ✅ Order verification on success page

## Currency Configuration

The system uses the currency settings from `data/settings.json`:
- **Currency Code**: GBP (British Pounds)
- **Currency Symbol**: £

## Shipping Integration

- Free shipping threshold: £50.00
- Standard shipping cost: £5.00
- Shipping is automatically added as a line item in Stripe

## Webhook Support (Optional)

For production, consider setting up Stripe webhooks to handle:
- Payment confirmations
- Failed payments
- Refunds
- Subscription events

Webhook endpoint: `/ampmkp/api/stripe-webhook.php` (not yet implemented)

## Testing Checklist

- [ ] API keys configured
- [ ] Composer dependencies installed (`composer install`)
- [ ] Cart functionality working
- [ ] Customer form validation
- [ ] Stripe checkout redirection
- [ ] Success page order creation
- [ ] Cancel page functionality
- [ ] Order saved to JSON database
- [ ] Cart cleared after successful payment

## Support

For Stripe-specific issues:
- [Stripe Documentation](https://stripe.com/docs)
- [Stripe Testing Guide](https://stripe.com/docs/testing)
- [Stripe Checkout Guide](https://stripe.com/docs/checkout)

For integration issues, check:
1. API keys are correct
2. Composer dependencies installed
3. Cart has items
4. Customer information is complete
5. Server logs for PHP errors 