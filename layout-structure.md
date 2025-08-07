# Angel Marketplace - Layout Structure

## Global Layout Components

### Header
- **Brand Logo**: "The Angel Market Place"
- **Primary Navigation**:
  - All Categories (Dropdown)
  - Search Bar ("Search for products")
  - Cart Icon
  - User Account Icon
- **Support Bar**:
  - 24/7 SUPPORT
  - Phone: +44 791 815 4909
- **Main Navigation**:
  - Browse Categories
  - Shop
  - About Us
  - Schemes
  - Contact Us

### Footer
- **Copyright**: © 2024 Angel Marketplace. All Rights Reserved.
- **Quick Links**: Home, Shop, About Us, Contact Us
- **Categories**: Apparels, Gift Items, Household Items, Other Services, Kids, Angel Hampers
- **Contact Info**: 
  - Phone: +44 791 815 4909
  - Email: info@angelmarketplace.org
- **Social Media**: Facebook, Twitter, Instagram, YouTube, LinkedIn
- **Payment Methods**: Visa, Mastercard, American Express, PayPal, Stripe

## Page Layouts

### 1. Homepage Layout
```
Header
├── Hero Section
│   ├── Headline: "Your Shopping Journey Begins Here Welcome to Angel Marketplace"
│   ├── Description: Brand story and mission
│   ├── CTA Buttons: "Shop Now", "Let's talk"
│   └── Hero Image: New Year Message Apparel
├── Trending Products Section
│   ├── Section Title: "What's trending now"
│   └── Product Grid (25 products)
├── Product Categories Section
│   └── Category Cards: Apparels, Gift Items, Household Items, Other Services, Kids, Angel Hampers
├── Most Popular Products Section
│   ├── Section Title: "Most popular products"
│   └── Product Grid (25 products)
└── CTA Section
    ├── Headline: "Sell. Hire. Connect"
    └── Sub-headline: "The only merchandise store you'll ever need"
Footer
```

### 2. About Us Page Layout
```
Header
├── Hero Section
│   ├── Headline: "Your Journey Starts at Angel Marketplace"
│   └── Statistics: "317+ Items Sale"
├── Main Content
│   ├── Section Title: "A unique Experience at Angel Marketplace"
│   ├── Brand Story & Mission
│   └── CTA: "Questions? Our experts will help find the gear that's right for you"
│       └── Button: "Get In Touch"
Footer
```

### 3. Schemes Page Layout
```
Header
├── Prime Affiliate Scheme Section
│   ├── Title & Description
│   ├── Program Highlights
│   │   ├── High Commission Rates
│   │   ├── Comprehensive Affiliate Dashboard
│   │   ├── Exclusive Marketing Assets
│   │   └── Dedicated Support
│   └── Getting Started Steps
│       ├── Sign Up
│       ├── Promote Products
│       └── Earn on Every Sale
├── Prime Vendor Scheme Section
│   ├── Title & Description
│   ├── Key Benefits
│   │   ├── Extended Market Reach
│   │   ├── Enhanced Vendor Dashboard
│   │   ├── Promotional Support
│   │   ├── Priority Vendor Assistance
│   │   └── Flexible Payment Options
│   └── How It Works
│       ├── Join the Program
│       ├── List Your Products
│       └── Sell and Grow
Footer
```

### 4. Contact Us Page Layout
```
Header
├── Contact Information Section
│   ├── Location: London, UK
│   ├── Phone Numbers:
│   │   ├── (+44) 07918154909
│   │   └── (+44) 01708556604
│   └── Email: sales@angelmarketplace.org
├── Contact Form
│   ├── Your Name (Input)
│   ├── Email Address (Input)
│   ├── Phone Number (Input)
│   ├── Message (Textarea)
│   └── Send Message (Button)
Footer
```

### 5. Shop List Page Layout
```
Header
├── Sidebar Filters
│   ├── Search Product (Input)
│   ├── Price Range Slider
│   ├── Color Options (Swatches)
│   ├── Size Options (Buttons)
│   └── Category List
│       ├── Dresses
│       ├── Top & Blouses
│       ├── Boots
│       ├── Jewelry
│       └── Makeup
├── Main Product Area
│   ├── Results Info: "Showing X-Y of Z Results"
│   ├── Sort Options: Latest, Products
│   └── Product Grid/List
│       ├── Product Images
│       ├── Product Names
│       ├── Prices
│       ├── Reviews
│       └── Add to Cart Buttons
Footer
```

### 6. Shop with Category Page Layout
```
Header
├── Category Banner/Breadcrumb
├── Sidebar Filters (Same as Shop List)
├── Main Product Area (Same as Shop List)
Footer
```

## Product Categories Structure

### Main Categories
1. **Apparels**
   - Sweatshirts (Loveworld, Rhapsody Of Realities, Reachout World)
   - Hoodies (Grace, Iexcel, Grace Everywhere)
   - T-shirts (Grace, Iexcel, Affirmation Tees)
   - Shirts (Male/Female Loveworld)
   - Jackets (Varsity, Premium, Hslhs, Formula 1)
   - Gym Wear
   - Tracksuits
   - Kiddies Apparel

2. **Gift Items**
   - Ball Point Pens
   - Vacuum Flasks
   - Tap 2 Read Products (Bracelet, Standard, Clap On Bracelet)

3. **Household Items**
4. **Other Services**
5. **Kids**
6. **Angel Hampers**

## Sample Product Data Structure
```
Product Example:
- Name: "Sweatshirts-Loveworld"
- Price: £16.00
- Category: Apparels
- Image: Product image
- Description: Product details
- Variants: Size, Color options
- Stock status
- Reviews/Ratings
```

## Responsive Design Considerations
- Mobile-first approach
- Collapsible navigation for mobile
- Responsive product grids
- Touch-friendly interface elements
- Optimized images for different screen sizes

## Interactive Elements
- Dropdown menus
- Product carousels/sliders
- Filter toggles
- Search functionality
- Shopping cart updates
- User account management
- Contact form validation