<?php
require_once 'includes/functions.php';

// Get product slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . getBaseUrl('shop.php'));
    exit;
}

// Get product details
$product = getProductBySlug($slug);

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    $page_title = 'Product Not Found';
    include 'includes/header.php';
    ?>
    <div class="container mx-auto px-4 py-24 text-center">
        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <h1 class="text-3xl font-bold text-charcoal-900 mb-4">Product Not Found</h1>
        <p class="text-gray-600 mb-8 max-w-md mx-auto">The product you're looking for doesn't exist or has been removed. It might have been discontinued or the link is incorrect.</p>
        <a href="<?php echo getBaseUrl('shop.php'); ?>" class="inline-flex items-center justify-center px-8 py-3 text-base font-bold text-white transition-all duration-200 bg-folly border border-transparent rounded-xl hover:bg-folly-600 shadow-lg hover:shadow-xl">
            Continue Shopping
        </a>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// Get category information using the new helper function
$category = getCategoryById($product['category_id']);

// Meta Dual Tracking: shared event ID for Pixel + Conversions API deduplication
$price = getProductPrice($product);
require_once 'includes/meta-integration.php';
$meta = new MetaIntegration();
$viewContentEventId = MetaIntegration::generateEventId('ViewContent', [$product['id']]);
$meta->trackViewContent(
    $product['id'],
    $product['name'],
    $price,
    $category ? $category['name'] : '',
    [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ],
    $viewContentEventId
);

// Get related products (same category, excluding current product)
$relatedProducts = array_filter(
    getProducts($product['category_id'], null, 8),
    function($p) use ($product) {
        return $p['id'] !== $product['id'];
    }
);
$relatedProducts = array_slice($relatedProducts, 0, 4);

$page_title = $product['name'];
$page_description = $product['description'];

include 'includes/header.php';
?>

<!-- Meta Pixel: ViewContent (same eventID as Conversions API for deduplication) -->
<script>
if (typeof fbq === 'function') {
    fbq('track', 'ViewContent', { eventID: '<?php echo htmlspecialchars($viewContentEventId, ENT_QUOTES, 'UTF-8'); ?>' });
}
</script>

<!-- Breadcrumb -->
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-gray-500 hover:text-folly transition-colors">Shop</a>
            <?php if ($category): ?>
                <span class="text-gray-300">/</span>
                <a href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" class="text-gray-500 hover:text-folly transition-colors">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endif; ?>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium line-clamp-1"><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>
    </div>
</div>

<!-- Product Details -->
<section class="relative bg-white py-12 md:py-20 overflow-hidden">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 xl:gap-20">
            <!-- Product Image Gallery -->
            <div class="space-y-6">
                <!-- Main Product Image -->
                <div class="relative aspect-square bg-gray-50 rounded-3xl overflow-hidden border border-gray-100 group">
                    <!-- Badges -->
                    <?php if (isset($product['is_new']) && $product['is_new']): ?>
                    <span class="absolute top-6 left-6 bg-green-500 text-white text-sm font-bold px-3 py-1.5 rounded-full z-10 shadow-sm">NEW</span>
                    <?php endif; ?>
                    <?php if (isset($product['discount_percent']) && $product['discount_percent'] > 0): ?>
                    <span class="absolute top-6 right-6 bg-folly text-white text-sm font-bold px-3 py-1.5 rounded-full z-10 shadow-sm">-<?php echo $product['discount_percent']; ?>%</span>
                    <?php endif; ?>

                    <img 
                        src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 cursor-zoom-in"
                        id="main-product-image"
                        onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                    >
                </div>
                
                <!-- Thumbnails -->
                <?php if (isset($product['images']) && is_array($product['images']) && count($product['images']) > 0): ?>
                <div class="grid grid-cols-5 gap-4">
                    <!-- Main image thumbnail -->
                    <button 
                        onclick="changeMainImage('<?php echo getAssetUrl('images/' . $product['image']); ?>', this)"
                        class="product-thumbnail aspect-square rounded-xl overflow-hidden border-2 border-folly p-0.5 transition-all hover:opacity-100 focus:outline-none"
                    >
                        <img 
                            src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                            alt="Main View"
                            class="w-full h-full object-cover rounded-lg"
                        >
                    </button>
                    
                    <!-- Additional images -->
                    <?php foreach (array_slice($product['images'], 0, 4) as $index => $additionalImage): ?>
                        <button 
                            onclick="changeMainImage('<?php echo getAssetUrl('images/' . $additionalImage); ?>', this)"
                            class="product-thumbnail aspect-square rounded-xl overflow-hidden border-2 border-transparent hover:border-gray-300 p-0.5 transition-all opacity-70 hover:opacity-100 focus:outline-none"
                        >
                            <img 
                                src="<?php echo getAssetUrl('images/' . $additionalImage); ?>" 
                                alt="View <?php echo $index + 1; ?>"
                                class="w-full h-full object-cover rounded-lg"
                            >
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="flex flex-col">
                <?php if ($category): ?>
                    <a href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" class="text-folly font-bold text-sm uppercase tracking-wider mb-3 hover:underline">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endif; ?>
                
                <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-charcoal-900 leading-tight mb-4 font-display">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>
                
                <!-- Rating -->
                <div class="flex items-center mb-6">
                    <?php 
                    $ratingStats = getProductRatingStats($product['id']);
                    echo renderStars($ratingStats['average'], 5, 'w-5 h-5');
                    ?>
                    <a href="#reviews" class="ml-3 text-sm font-medium text-gray-500 hover:text-folly transition-colors">
                        <?php echo $ratingStats['count']; ?> Review<?php echo $ratingStats['count'] != 1 ? 's' : ''; ?>
                    </a>
                </div>
                
                <!-- Price -->
                <div class="flex items-end gap-4 mb-8">
                    <span class="text-4xl font-bold text-charcoal-900"><?php echo formatProductPrice($product); ?></span>
                    <?php if (isset($product['old_price']) && $product['old_price'] > $product['price']): ?>
                        <span class="text-xl text-gray-400 line-through mb-1.5"><?php echo formatPrice($product['old_price']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="prose prose-gray text-gray-600 mb-8 leading-relaxed">
                    <?php echo htmlspecialchars($product['description']); ?>
                </div>
                
                <div class="w-full h-px bg-gray-100 mb-8"></div>
                
                <!-- Product Options Form -->
                <div class="space-y-6 mb-8">
                    <!-- Size Selection -->
                    <?php if (isset($product['has_sizes']) && $product['has_sizes'] && !empty($product['available_sizes'])): ?>
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <label class="font-bold text-charcoal-900">Select Size</label>
                            <button type="button" class="text-xs font-bold text-folly hover:underline">Size Guide</button>
                        </div>
                        <div class="flex flex-wrap gap-2 md:gap-3">
                            <?php foreach ($product['available_sizes'] as $size): ?>
                                <label class="cursor-pointer group flex-1 sm:flex-none">
                                    <input type="radio" name="size" value="<?php echo htmlspecialchars($size); ?>" class="peer sr-only">
                                    <div class="px-3 py-2 md:px-4 md:py-3 border border-gray-200 rounded-xl text-center min-w-[2.5rem] md:min-w-[3rem] text-sm md:text-base font-semibold text-charcoal-600 peer-checked:border-folly peer-checked:bg-folly peer-checked:text-white peer-checked:shadow-md transition-all hover:border-folly/50 w-full">
                                        <?php echo htmlspecialchars($size); ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Color Selection -->
                    <?php if (isset($product['has_colors']) && $product['has_colors'] && !empty($product['available_colors'])): ?>
                    <div>
                        <label class="block font-bold text-charcoal-900 mb-3">Select Color</label>
                        <div class="flex flex-wrap gap-2 md:gap-3">
                            <?php 
                            $color_hex_map = [
                                'Black' => '#000000', 'White' => '#FFFFFF', 'Red' => '#EF4444', 
                                'Blue' => '#3B82F6', 'Green' => '#10B981', 'Yellow' => '#F59E0B',
                                'Pink' => '#EC4899', 'Purple' => '#8B5CF6', 'Orange' => '#F97316',
                                'Brown' => '#78350F', 'Gray' => '#6B7280', 'Navy' => '#1E3A8A'
                            ];
                            foreach ($product['available_colors'] as $color): 
                                $hex_color = $color_hex_map[$color] ?? '#CCCCCC';
                            ?>
                                <label class="cursor-pointer group relative">
                                    <input type="radio" name="color" value="<?php echo htmlspecialchars($color); ?>" class="peer sr-only">
                                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full border border-gray-200 shadow-sm flex items-center justify-center peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-folly transition-all" style="background-color: <?php echo $hex_color; ?>">
                                        <span class="sr-only"><?php echo htmlspecialchars($color); ?></span>
                                    </div>
                                    <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 bg-charcoal-900 text-white text-[10px] md:text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10 hidden md:block"><?php echo htmlspecialchars($color); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quantity -->
                    <div>
                        <label class="block font-bold text-charcoal-900 mb-3">Quantity</label>
                        <div class="inline-flex items-center border border-gray-200 rounded-xl p-1">
                            <button onclick="decrementQuantity()" class="w-10 h-10 flex items-center justify-center text-charcoal-500 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                            </button>
                            <input 
                                type="number" 
                                id="quantity" 
                                min="1" 
                                max="<?php echo $product['stock']; ?>" 
                                value="1" 
                                class="w-12 text-center border-none focus:ring-0 font-bold text-charcoal-900 p-0"
                                readonly
                            >
                            <button onclick="incrementQuantity(<?php echo $product['stock']; ?>)" class="w-10 h-10 flex items-center justify-center text-charcoal-500 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 mb-8">
                    <button 
                        onclick="addToCartProduct(<?php echo $product['id']; ?>)"
                        class="flex-1 bg-gradient-to-r from-charcoal-900 to-charcoal-800 hover:from-folly hover:to-folly-500 text-white px-8 py-4 rounded-2xl font-semibold text-lg transition-all duration-300 shadow-lg hover:shadow-folly/25 flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
                        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"></path></svg>
                        <?php echo $product['stock'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                    </button>
                    <button 
                        onclick="buyNowProduct(<?php echo $product['id']; ?>)"
                        class="flex-1 glass text-charcoal-900 px-8 py-4 rounded-2xl font-semibold text-lg transition-all duration-300 hover:bg-white/90 flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
                        <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                    >
                        Buy Now
                    </button>
                </div>
                
                <!-- Trust Badges -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="glass flex items-center gap-3 p-4 rounded-2xl">
                        <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center text-green-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="font-bold text-charcoal-900 text-sm">Secure Payment</p>
                            <p class="text-xs text-gray-500">100% Protected</p>
                        </div>
                    </div>
                    <div class="glass flex items-center gap-3 p-4 rounded-2xl">
                        <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        </div>
                        <div>
                            <p class="font-bold text-charcoal-900 text-sm">Fast Shipping</p>
                            <p class="text-xs text-gray-500">Global Delivery</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Features / Details Tabs -->
        <div class="mt-20">
            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 mb-8 overflow-x-auto">
                <button class="px-8 py-4 text-charcoal-900 font-bold border-b-2 border-folly transition-colors whitespace-nowrap">
                    Description
                </button>
            </div>
            
            <!-- Tab Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <div class="lg:col-span-2 prose prose-lg prose-gray text-gray-600">
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    
                    <?php if (isset($product['features']) && !empty($product['features'])): ?>
                    <h3 class="text-charcoal-900 font-bold mt-8 mb-4">Key Features</h3>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 list-none pl-0">
                        <?php foreach ($product['features'] as $feature): ?>
                        <li class="flex items-start pl-0">
                            <svg class="w-5 h-5 text-folly mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span><strong class="text-charcoal-900"><?php echo htmlspecialchars($feature['name']); ?>:</strong> <?php echo htmlspecialchars($feature['value']); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                
                <div class="glass rounded-2xl p-8 h-fit">
                    <h3 class="text-xl font-bold text-charcoal-900 mb-4">Why Shop With Us?</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center text-gray-600">
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-folly mr-3 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            24/7 Customer Support
                        </li>
                        <li class="flex items-center text-gray-600">
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-folly mr-3 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            </div>
                            Secure Payment Processing
                        </li>
                        <li class="flex items-center text-gray-600">
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-folly mr-3 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"></path></svg>
                            </div>
                            Easy Returns Policy
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reviews Section -->
<section id="reviews" class="bg-gradient-to-b from-gray-50 to-white py-12 md:py-20 border-t border-gray-100">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl font-bold text-charcoal-900 mb-8 text-center">Customer Reviews</h2>
            
            <!-- Review Stats -->
            <div class="glass-strong rounded-2xl p-8 mb-12 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="text-center md:text-left">
                    <div class="text-5xl font-bold text-charcoal-900 mb-2"><?php echo $ratingStats['average']; ?></div>
                    <div class="flex items-center justify-center md:justify-start mb-2">
                        <?php echo renderStars($ratingStats['average'], 5, 'w-6 h-6'); ?>
                    </div>
                    <p class="text-gray-500">Based on <?php echo $ratingStats['count']; ?> reviews</p>
                </div>
                
                <div class="flex-1 w-full max-w-md">
                    <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                        <div class="flex items-center mb-2">
                            <span class="text-sm font-bold text-gray-500 w-6"><?php echo $star; ?></span>
                            <svg class="w-4 h-4 text-yellow-400 mx-2" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-400 rounded-full" style="width: <?php echo $ratingStats['count'] > 0 ? ($ratingStats['distribution'][$star] / $ratingStats['count']) * 100 : 0; ?>%"></div>
                            </div>
                            <span class="text-sm text-gray-400 w-10 text-right"><?php echo $ratingStats['distribution'][$star]; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center">
                    <button onclick="document.getElementById('review-form').scrollIntoView({behavior: 'smooth'})" class="bg-charcoal-900 hover:bg-folly text-white px-6 py-3 rounded-xl font-bold transition-colors shadow-lg">
                        Write a Review
                    </button>
                </div>
            </div>
            
            <!-- Reviews List -->
            <?php 
            $productRatings = getProductRatings($product['id']);
            if (empty($productRatings)): 
            ?>
                <div class="text-center py-12">
                    <p class="text-gray-500 text-lg">No reviews yet. Be the first to share your thoughts!</p>
                </div>
            <?php else: ?>
                <div class="space-y-6 mb-12">
                    <?php foreach (array_reverse($productRatings) as $review): ?>
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-charcoal-600 font-bold text-lg mr-3">
                                        <?php echo strtoupper(substr($review['reviewer_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-charcoal-900"><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                        <div class="flex items-center text-xs text-gray-500">
                                            <span><?php echo date('F j, Y', strtotime($review['date'])); ?></span>
                                            <?php if ($review['verified_purchase']): ?>
                                                <span class="mx-2">•</span>
                                                <span class="text-green-600 font-medium flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    Verified Purchase
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex">
                                    <?php echo renderStars($review['rating'], 5, 'w-4 h-4'); ?>
                                </div>
                            </div>
                            <p class="text-gray-600 leading-relaxed"><?php echo htmlspecialchars($review['review']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Review Form -->
            <div id="review-form" class="glass-strong rounded-2xl p-8">
                <h3 class="text-2xl font-bold text-charcoal-900 mb-6">Leave a Review</h3>
                <form method="POST" action="">
                    <div class="mb-6">
                        <label class="block font-bold text-charcoal-900 mb-2">Rating</label>
                        <div class="flex items-center gap-2 group">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" class="sr-only peer" required>
                                    <svg class="w-8 h-8 text-gray-300 peer-checked:text-yellow-400 hover:text-yellow-400 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block font-bold text-charcoal-900 mb-2">Name</label>
                            <input type="text" name="reviewer_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all">
                        </div>
                        <div>
                            <label class="block font-bold text-charcoal-900 mb-2">Email</label>
                            <input type="email" name="reviewer_email" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block font-bold text-charcoal-900 mb-2">Review</label>
                        <textarea name="review" rows="4" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-folly focus:border-folly transition-all"></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review" class="w-full bg-folly hover:bg-folly-600 text-white font-bold py-4 rounded-xl transition-colors shadow-lg">
                        Submit Review
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<?php if (!empty($relatedProducts)): ?>
<section class="py-12 md:py-20 bg-white border-t border-gray-100">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-charcoal-900 mb-12 text-center">You Might Also Like</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($relatedProducts as $index => $product): 
                $isLcp = $index < 4; // Eager load first 4 products
            ?>
                <div class="group bg-white rounded-2xl border border-gray-100 hover:border-gray-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col h-full">
                    <div class="relative aspect-[4/5] overflow-hidden rounded-t-2xl bg-gray-100">
                        <!-- Loading Skeleton -->
                        <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>

                        <img src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             <?php if (!$isLcp): ?>
                             loading="lazy"
                             class="absolute inset-0 w-full h-full object-cover transition-all duration-300 group-hover:scale-110 opacity-0 z-10"
                             onload="this.classList.remove('opacity-0')"
                             onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>';this.classList.remove('opacity-0')"
                             <?php else: ?>
                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-110 z-10"
                             onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                             <?php endif; ?>>
                        
                        <div class="absolute inset-x-0 bottom-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 flex gap-2">
                            <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>" class="flex-1 bg-white text-charcoal-900 font-semibold py-3 rounded-xl hover:bg-folly hover:text-white transition-colors shadow-lg text-sm flex items-center justify-center">
                                View Details
                            </a>
                        </div>
                    </div>
                    
                    <div class="p-5 flex flex-col flex-grow">
                        <h3 class="text-lg font-bold text-charcoal-900 mb-2 line-clamp-2 group-hover:text-folly transition-colors">
                            <a href="<?php echo getBaseUrl('product.php?slug=' . $product['slug']); ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        
                        <div class="mt-auto flex items-center justify-between">
                            <span class="text-xl font-bold text-charcoal-900"><?php echo formatProductPrice($product); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function incrementQuantity(max) {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value);
    if (currentValue < max) {
        input.value = currentValue + 1;
    }
}

function decrementQuantity() {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value);
    if (currentValue > 1) {
        input.value = currentValue - 1;
    }
}

function changeMainImage(src, thumbnail) {
    const mainImage = document.getElementById('main-product-image');
    mainImage.style.opacity = '0';
    setTimeout(() => {
        mainImage.src = src;
        mainImage.style.opacity = '1';
    }, 200);
    
    // Update thumbnails
    document.querySelectorAll('.product-thumbnail').forEach(thumb => {
        thumb.classList.remove('border-folly', 'opacity-100');
        thumb.classList.add('border-transparent', 'opacity-70');
    });
    thumbnail.classList.remove('border-transparent', 'opacity-70');
    thumbnail.classList.add('border-folly', 'opacity-100');
}

function addToCartProduct(productId) {
    const quantity = document.getElementById('quantity').value;
    const selectedSize = document.querySelector('input[name="size"]:checked');
    const selectedColor = document.querySelector('input[name="color"]:checked');
    
    // Validation
    if (document.querySelector('input[name="size"]') && !selectedSize) {
        Swal.fire({
            icon: 'warning',
            title: 'Please select a size',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }
    
    if (document.querySelector('input[name="color"]') && !selectedColor) {
        Swal.fire({
            icon: 'warning',
            title: 'Please select a color',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }
    
    const options = {
        size: selectedSize ? selectedSize.value : null,
        color: selectedColor ? selectedColor.value : null
    };
    
    // Add to cart logic (using existing API)
    fetch('<?php echo getBaseUrl("api/cart.php"); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: parseInt(quantity),
            options: options
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Added to Cart!',
                text: <?php echo json_encode($product['name'] . ' has been added to your cart.'); ?>,
                showCancelButton: true,
                confirmButtonText: 'View Cart',
                cancelButtonText: 'Continue Shopping',
                confirmButtonColor: '#FF0055',
                cancelButtonColor: '#3B4255'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo getBaseUrl("cart.php"); ?>';
                }
            });
            
            // Update cart count if available
            if (window.updateCartCount) window.updateCartCount(data.cart_count);
            
            // Update mini cart if available
             if (typeof updateMiniCart === 'function') {
                updateMiniCart();
            }
            
            // Update cart counter badge
            const cartCounters = document.querySelectorAll('.cart-counter');
            cartCounters.forEach(counter => {
                counter.textContent = data.cart_count;
                counter.style.display = data.cart_count > 0 ? 'flex' : 'none';
            });
            
        } else {
            Swal.fire('Error', data.message || 'Failed to add to cart', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Something went wrong', 'error');
    });
}

function buyNowProduct(productId) {
    addToCartProduct(productId);
    // The redirect happens in the success callback of addToCartProduct if we modify it, 
    // but for "Buy Now" we usually want immediate redirect. 
    // For simplicity, we'll let the user choose in the modal or we could override the behavior.
    // Ideally, we'd pass a flag to addToCartProduct to auto-redirect.
}
</script>

<?php include 'includes/footer.php'; ?>
