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
    <div class="container mx-auto px-4 py-16 text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Product Not Found</h1>
        <p class="text-gray-600 mb-8">The product you're looking for doesn't exist or has been removed.</p>
        <a href="<?php echo getBaseUrl('shop.php'); ?>" class="bg-folly hover:bg-folly-600 text-white px-6 py-3 rounded-md font-medium">
            Continue Shopping
        </a>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// Get category information using the new helper function
$category = getCategoryById($product['category_id']);

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

// Determine selected currency for display
$selectedCurrency = getSelectedCurrency();

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white border-b border-gray-100 py-3 md:py-4 mt-16 md:mt-20">
    <div class="container mx-auto px-4">
        <nav class="text-xs md:text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-folly hover:text-folly-600 hover:underline">Home</a>
            <span class="text-gray-400">/</span>
            <a href="<?php echo getBaseUrl('shop.php'); ?>" class="text-folly hover:text-folly-600 hover:underline">Shop</a>
            <?php if ($category): ?>
                <span class="text-gray-400">/</span>
                <a href="<?php echo getBaseUrl('category.php?slug=' . $category['slug']); ?>" class="text-folly hover:text-folly-600 hover:underline">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endif; ?>
            <span class="text-gray-400">/</span>
            <span class="text-gray-700 font-medium line-clamp-1"><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>
    </div>
</div>

<!-- Product Details -->
<section class="relative bg-gradient-to-br from-charcoal-50 via-white to-tangerine-50 py-12 md:py-20 overflow-hidden">
    <!-- Background decorative elements -->
    <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-16">
            <!-- Product Image -->
            <div class="space-y-4 md:space-y-6">
                <!-- Main Product Image -->
                <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl overflow-hidden shadow-xl border border-gray-200">
                    <img 
                        loading="lazy"
                        src="<?php echo getAssetUrl('images/' . $product['image']); ?>" 
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                        id="main-product-image"
                        onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                    >
                </div>
                
                <!-- Product Image Thumbnails (for future carousel implementation) -->
                <?php if (isset($product['images']) && is_array($product['images']) && count($product['images']) > 1): ?>
                <div class="grid grid-cols-4 gap-3">
                    <?php foreach (array_slice($product['images'], 0, 4) as $index => $additionalImage): ?>
                        <div class="product-thumbnail aspect-square bg-gray-100 rounded-lg overflow-hidden border-2 border-transparent hover:border-folly transition-colors cursor-pointer">
                            <img 
                                loading="lazy"
                                src="<?php echo getAssetUrl('images/' . $additionalImage); ?>" 
                                alt="<?php echo htmlspecialchars($product['name']); ?> - View <?php echo $index + 1; ?>"
                                class="w-full h-full object-cover"
                                onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="space-y-4 md:space-y-8">
                <?php if ($category): ?>
                    <div class="inline-block bg-folly-50 text-folly-800 text-xs md:text-sm font-bold px-3 md:px-4 py-2 rounded-full">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </div>
                <?php endif; ?>
                
                <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>
                
                <div class="bg-gradient-to-r from-folly to-folly-600 text-white px-4 md:px-6 py-3 md:py-4 rounded-2xl inline-block shadow-lg">
                    <span class="text-2xl md:text-3xl font-bold"><?php echo formatProductPrice($product, $selectedCurrency); ?></span>
                    <?php 
                    $settings = getSettings();
                    $productPrice = getProductPrice($product, $selectedCurrency);
                    $currencySettings = $settings['shipping']['costs'][$selectedCurrency] ?? [];
                    $shippingCost = $currencySettings['standard'] ?? ($settings['shipping']['standard_shipping_cost'] ?? 0);
                    $freeShippingThreshold = $currencySettings['free_threshold'] ?? ($settings['shipping']['free_shipping_threshold'] ?? 0);
                    if ($freeShippingThreshold > 0 && $productPrice >= $freeShippingThreshold) {
                        echo '<span class="text-folly-100 text-xs md:text-sm ml-2">Free shipping</span>';
                    } elseif ($shippingCost > 0) {
                        echo '<span class="text-folly-100 text-xs md:text-sm ml-2">+ ' . formatPriceWithCurrency($shippingCost, $selectedCurrency) . ' shipping</span>';
                    } else {
                        echo '<span class="text-folly-100 text-xs md:text-sm ml-2">Free shipping</span>';
                    }
                    ?>
                </div>
                
                <div class="bg-white/80 backdrop-blur-sm p-4 md:p-6 rounded-2xl shadow-lg border border-gray-200">
                    <p class="text-gray-700 text-base md:text-lg leading-relaxed">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </p>
                </div>
                
                <!-- Stock Status -->
                <div class="bg-white/80 backdrop-blur-sm p-3 md:p-4 rounded-2xl shadow-lg border border-gray-200">
                    <?php if ($product['stock'] > 0): ?>
                        <div class="flex items-center text-green-600">
                            <div class="w-6 h-6 md:w-8 md:h-8 bg-green-100 rounded-xl flex items-center justify-center mr-2 md:mr-3">
                                <svg class="w-4 h-4 md:w-5 md:h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold text-base md:text-lg">In Stock</span>
                                <?php if ($product['stock'] <= 5): ?>
                                    <span class="block text-orange-600 text-xs md:text-sm font-medium">
                                        Only <?php echo $product['stock']; ?> left - Order soon!
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center text-red-600">
                            <div class="w-6 h-6 md:w-8 md:h-8 bg-red-100 rounded-xl flex items-center justify-center mr-2 md:mr-3">
                                <svg class="w-4 h-4 md:w-5 md:h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <span class="font-bold text-base md:text-lg">Out of Stock</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Add to Cart -->
                <div class="bg-white/80 backdrop-blur-sm p-4 md:p-6 rounded-2xl shadow-lg border border-gray-200 space-y-4 md:space-y-6">
                    <!-- Size Selection -->
                    <?php if (isset($product['has_sizes']) && $product['has_sizes'] && !empty($product['available_sizes'])): ?>
                    <div>
                        <label class="block text-base md:text-lg font-bold text-gray-700 mb-3">Size:</label>
                        <div class="grid grid-cols-3 md:grid-cols-4 gap-2 md:gap-3">
                            <?php foreach ($product['available_sizes'] as $size): ?>
                                <label class="size-option cursor-pointer">
                                    <input type="radio" name="size" value="<?php echo htmlspecialchars($size); ?>" class="hidden size-radio" required>
                                    <div class="px-3 md:px-4 py-2 md:py-3 border-2 border-gray-300 rounded-xl text-center font-semibold text-sm md:text-base transition-all duration-200 hover:border-folly hover:bg-folly-50">
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
                        <label class="block text-base md:text-lg font-bold text-gray-700 mb-3">Color:</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 md:gap-3">
                            <?php 
                            $color_hex_map = [
                                'Black' => '#000000', 'White' => '#FFFFFF', 'Red' => '#FF0000', 
                                'Blue' => '#0000FF', 'Green' => '#008000', 'Yellow' => '#FFFF00',
                                'Pink' => '#FFC0CB', 'Purple' => '#800080', 'Orange' => '#FFA500',
                                'Brown' => '#8B4513', 'Gray' => '#808080', 'Navy' => '#000080'
                            ];
                            foreach ($product['available_colors'] as $color): 
                                $hex_color = $color_hex_map[$color] ?? '#CCCCCC';
                            ?>
                                <label class="color-option cursor-pointer">
                                    <input type="radio" name="color" value="<?php echo htmlspecialchars($color); ?>" class="hidden color-radio" required>
                                    <div class="px-3 md:px-4 py-2 md:py-3 border-2 border-gray-300 rounded-xl text-center font-semibold text-sm md:text-base transition-all duration-200 hover:border-indigo-500 hover:bg-indigo-50 flex items-center justify-center gap-2">
                                        <div class="w-4 h-4 rounded-full border border-gray-300" style="background-color: <?php echo $hex_color; ?>; <?php echo $color === 'White' ? 'border-color: #ccc;' : ''; ?>"></div>
                                        <?php echo htmlspecialchars($color); ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center space-x-3 md:space-x-4">
                        <label for="quantity" class="text-base md:text-lg font-bold text-gray-700">Quantity:</label>
                        <input 
                            type="number" 
                            id="quantity" 
                            min="1" 
                            max="<?php echo $product['stock']; ?>" 
                            value="1" 
                            class="w-20 md:w-24 px-3 md:px-4 py-2 md:py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly text-center font-semibold text-sm md:text-base"
                            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                        >
                    </div>
                    
                    <div class="flex flex-col space-y-3">
                        <button 
                            onclick="addToCartProduct(<?php echo $product['id']; ?>)"
                            class="w-full bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed text-white px-6 md:px-8 py-3 md:py-4 rounded-xl font-bold text-base md:text-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-2 md:gap-3 touch-manipulation"
                            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                        >
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l-2.5 5m12.5 0H9"></path>
                            </svg>
                            <?php echo $product['stock'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                        </button>
                        <button 
                            onclick="buyNowProduct(<?php echo $product['id']; ?>)"
                            class="w-full bg-gray-100 hover:bg-gray-200 disabled:bg-gray-300 disabled:cursor-not-allowed text-gray-800 px-6 md:px-8 py-3 md:py-4 rounded-xl font-semibold text-base md:text-lg transition-all duration-200 transform hover:scale-105 border-2 border-gray-300 hover:border-gray-400 flex items-center justify-center gap-2 md:gap-3 touch-manipulation"
                            <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>
                        >
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Buy Now
                        </button>
                    </div>
                </div>
                
                <!-- Product Features -->
                <?php if (isset($product['features']) && !empty($product['features'])): ?>
                <div class="bg-white/80 backdrop-blur-sm p-4 md:p-6 rounded-2xl shadow-lg border border-gray-200">
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 md:mb-6">Product Features</h3>
                    
                    <!-- Dynamic features from admin panel -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                        <?php 
                        $colorClasses = ['folly', 'charcoal', 'tangerine'];
                        $colorIndex = 0;
                        foreach ($product['features'] as $feature): 
                            $colorClass = $colorClasses[$colorIndex % count($colorClasses)];
                            $colorIndex++;
                        ?>
                            <div class="flex items-start bg-<?php echo $colorClass; ?>-50 p-4 rounded-xl">
                                <div class="w-8 h-8 bg-<?php echo $colorClass; ?>-100 rounded-xl flex items-center justify-center mr-3 flex-shrink-0 mt-0.5">
                                    <svg class="w-5 h-5 text-<?php echo $colorClass === 'charcoal' ? 'charcoal-600' : $colorClass; ?>" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($feature['name']); ?></div>
                                    <div class="text-sm text-gray-700"><?php echo htmlspecialchars($feature['value']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Ratings and Reviews Section -->
<section class="bg-white py-12 md:py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <?php 
            $ratingStats = getProductRatingStats($product['id']);
            $productRatings = getProductRatings($product['id']);
            
            // Handle rating submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
                $rating = (int)$_POST['rating'];
                $review = $_POST['review'];
                $reviewerName = $_POST['reviewer_name'];
                $reviewerEmail = $_POST['reviewer_email'];
                
                if ($rating >= 1 && $rating <= 5 && !empty($review) && !empty($reviewerName) && validateEmail($reviewerEmail)) {
                    $newRating = addRating($product['id'], $rating, $review, $reviewerName, $reviewerEmail);
                    if ($newRating) {
                        // Refresh stats and ratings after adding new review
                        $ratingStats = getProductRatingStats($product['id']);
                        $productRatings = getProductRatings($product['id']);
                        $reviewSuccess = true;
                    } else {
                        $reviewError = "Failed to submit review. Please try again.";
                    }
                } else {
                    $reviewError = "Please fill all fields correctly and provide a valid rating.";
                }
            }
            ?>
            
            <div class="text-center mb-8 md:mb-16">
                <h2 class="text-2xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 md:mb-6">Customer Reviews</h2>
                <div class="w-16 md:w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-4 md:mb-6"></div>
                
                <!-- Rating Summary -->
                <div class="flex flex-col lg:flex-row items-center justify-center gap-6 md:gap-8 mb-6 md:mb-8">
                    <div class="text-center">
                        <div class="text-4xl md:text-6xl font-bold text-gray-900 mb-2"><?php echo $ratingStats['average']; ?></div>
                        <div class="mb-3">
                            <?php echo renderStars($ratingStats['average'], 5, 'w-5 h-5 md:w-6 md:h-6'); ?>
                        </div>
                        <p class="text-gray-600 text-sm md:text-base">Based on <?php echo $ratingStats['count']; ?> review<?php echo $ratingStats['count'] != 1 ? 's' : ''; ?></p>
                    </div>
                    
                    <?php if ($ratingStats['count'] > 0): ?>
                        <div class="w-full max-w-md">
                            <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                                <div class="flex items-center mb-2">
                                    <span class="text-sm w-3"><?php echo $star; ?></span>
                                    <svg class="w-4 h-4 mx-2 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full mx-2">
                                        <div class="h-2 bg-yellow-400 rounded-full" style="width: <?php echo $ratingStats['count'] > 0 ? ($ratingStats['distribution'][$star] / $ratingStats['count']) * 100 : 0; ?>%"></div>
                                    </div>
                                    <span class="text-sm text-gray-600 w-8 text-right"><?php echo $ratingStats['distribution'][$star]; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid lg:grid-cols-2 gap-12">
                <!-- Reviews List -->
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Reviews</h3>
                    
                    <?php if (empty($productRatings)): ?>
                        <div class="bg-gray-50 rounded-2xl p-8 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <p class="text-gray-600 text-lg">No reviews yet. Be the first to review this product!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach (array_reverse($productRatings) as $review): ?>
                                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <div class="flex items-center mb-2">
                                                <?php echo renderStars($review['rating'], 5, 'w-4 h-4'); ?>
                                                <span class="ml-2 font-semibold text-gray-900"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                                                <?php if ($review['verified_purchase']): ?>
                                                    <span class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Verified Purchase</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-600"><?php echo date('F j, Y', strtotime($review['date'])); ?></p>
                                        </div>
                                    </div>
                                    <p class="text-gray-800 leading-relaxed"><?php echo htmlspecialchars($review['review']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Review Form -->
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Write a Review</h3>
                    
                    <?php if (isset($reviewSuccess)): ?>
                        <div class="bg-green-50 border border-green-200 rounded-2xl p-6 mb-6">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-green-800 font-semibold">Thank you for your review! It has been submitted successfully.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($reviewError)): ?>
                        <div class="bg-red-50 border border-red-200 rounded-2xl p-6 mb-6">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-red-800 font-semibold"><?php echo $reviewError; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="bg-gray-50 rounded-2xl p-6 border border-gray-200" data-validate>
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-3">Your Rating *</label>
                            <div class="flex items-center space-x-2" id="rating-stars">
                                <input type="hidden" name="rating" id="rating-input" required>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" class="rating-star text-gray-300 hover:text-yellow-400 transition-colors duration-200" data-rating="<?php echo $i; ?>">
                                        <svg class="w-8 h-8 fill-current" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    </button>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="review" class="block text-sm font-bold text-gray-700 mb-3">Your Review *</label>
                            <textarea 
                                name="review" 
                                id="review" 
                                rows="5" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200 resize-none" 
                                placeholder="Share your experience with this product..."
                                required
                                minlength="10"
                            ></textarea>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="reviewer_name" class="block text-sm font-bold text-gray-700 mb-3">Your Name *</label>
                                <input 
                                    type="text" 
                                    name="reviewer_name" 
                                    id="reviewer_name" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200" 
                                    placeholder="Enter your name"
                                    required
                                >
                            </div>
                            <div>
                                <label for="reviewer_email" class="block text-sm font-bold text-gray-700 mb-3">Your Email *</label>
                                <input 
                                    type="email" 
                                    name="reviewer_email" 
                                    id="reviewer_email" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-all duration-200" 
                                    placeholder="Enter your email"
                                    required
                                >
                            </div>
                        </div>
                        
                        <button 
                            type="submit" 
                            name="submit_review"
                            class="w-full bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                        >
                            Submit Review
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<?php if (!empty($relatedProducts)): ?>
<section class="bg-gradient-to-br from-charcoal-50 to-tangerine-50 py-20">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">You Might Also Like</h2>
            <div class="w-24 h-1 bg-gradient-to-r from-folly to-tangerine mx-auto rounded-full mb-6"></div>
            <p class="text-gray-600 text-lg">Discover more products from the same category</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
                <div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden border border-gray-100 transform hover:-translate-y-2">
                    <!-- Background gradient overlay -->
                    <div class="absolute inset-0 bg-gradient-to-br from-folly/5 to-tangerine/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <div class="aspect-w-1 aspect-h-1 bg-gradient-to-br from-gray-100 to-gray-200 rounded-t-2xl overflow-hidden">
                        <img 
                            src="<?php echo getAssetUrl('images/' . $relatedProduct['image']); ?>" 
                            alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                            class="w-full h-48 object-cover transition-transform duration-500 group-hover:scale-110"
                            onerror="this.onerror=null;this.src='<?php echo getAssetUrl('images/general/placeholder.jpg'); ?>'"
                        >
                        <!-- Image overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <div class="relative p-6">
                        <h3 class="font-bold text-gray-900 mb-3 text-lg group-hover:text-folly transition-colors duration-300 line-clamp-2">
                            <?php echo htmlspecialchars($relatedProduct['name']); ?>
                        </h3>
                        
                        <!-- Rating stars (real data) -->
                        <div class="flex items-center mb-4">
                            <?php 
                            $relatedRatingStats = getProductRatingStats($relatedProduct['id']);
                            echo renderStars($relatedRatingStats['average'], 5, 'w-3 h-3');
                            ?>
                            <span class="ml-2 text-xs text-gray-500">
                                <?php if ($relatedRatingStats['count'] > 0): ?>
                                    (<?php echo $relatedRatingStats['average']; ?>)
                                <?php else: ?>
                                    (No reviews)
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-gray-900">
                                <?php echo formatProductPrice($relatedProduct, $selectedCurrency); ?>
                            </span>
                            <a 
                                href="<?php echo getBaseUrl('product.php?slug=' . $relatedProduct['slug']); ?>" 
                                class="bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                            >
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function addToCartProduct(productId) {
    const quantity = document.getElementById('quantity').value;
    
    // Get selected size and color
    const selectedSize = document.querySelector('input[name="size"]:checked');
    const selectedColor = document.querySelector('input[name="color"]:checked');
    
    // Check if size is required but not selected
    const hasSizes = document.querySelector('input[name="size"]');
    if (hasSizes && !selectedSize) {
        alert('Please select a size');
        return;
    }
    
    // Check if color is required but not selected
    const hasColors = document.querySelector('input[name="color"]');
    if (hasColors && !selectedColor) {
        alert('Please select a color');
        return;
    }
    
    // Prepare options object
    const options = {};
    if (selectedSize) {
        options.size = selectedSize.value;
    }
    if (selectedColor) {
        options.color = selectedColor.value;
    }
    
    // Find the button for loading state
    const button = document.querySelector(`button[onclick*="addToCartProduct(${productId})"]`);
    
    // Show loading state
    if (button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 md:w-5 md:h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4m9-9h-4M3 12H1"></path></svg> Adding...';
        button.disabled = true;
    }
    
    // Add to cart directly via API (bypass modal for product page)
    fetch('<?php echo getBaseUrl("api/cart.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: parseInt(quantity),
            options: options
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showSuccessMessage('Product added to cart successfully!');
            
            // Update cart counter if global function exists
            if (window.updateCartCounter) {
                window.updateCartCounter();
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to add to cart'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding to cart');
    })
    .finally(() => {
        // Reset button state
        if (button) {
            button.innerHTML = '<svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l-2.5 5m12.5 0H9"></path></svg> Add to Cart';
            button.disabled = false;
        }
    });
}

function showSuccessMessage(message) {
    // Create success notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            ${message}
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

function buyNowProduct(productId) {
    const quantity = document.getElementById('quantity').value;
    
    // Get selected size and color
    const selectedSize = document.querySelector('input[name="size"]:checked');
    const selectedColor = document.querySelector('input[name="color"]:checked');
    
    // Check if size is required but not selected
    const hasSizes = document.querySelector('input[name="size"]');
    if (hasSizes && !selectedSize) {
        alert('Please select a size');
        return;
    }
    
    // Check if color is required but not selected
    const hasColors = document.querySelector('input[name="color"]');
    if (hasColors && !selectedColor) {
        alert('Please select a color');
        return;
    }
    
    // Prepare options object
    const options = {};
    if (selectedSize) {
        options.size = selectedSize.value;
    }
    if (selectedColor) {
        options.color = selectedColor.value;
    }
    
    // Find the button for loading state
    const button = document.querySelector(`button[onclick*="buyNowProduct(${productId})"]`);
    
    // Show loading state
    if (button) {
        button.innerHTML = '<svg class="w-4 h-4 md:w-5 md:h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4m9-9h-4M3 12H1"></path></svg> Processing...';
        button.disabled = true;
    }
    
    // Add to cart then redirect to checkout
    fetch('<?php echo getBaseUrl("api/cart.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: parseInt(quantity),
            options: options
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to checkout
            window.location.href = '<?php echo getBaseUrl("checkout.php"); ?>';
        } else {
            alert('Error: ' + (data.message || 'Failed to add to cart'));
            // Reset button state on error
            if (button) {
                button.innerHTML = '<svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Buy Now';
                button.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your order');
        // Reset button state on error
        if (button) {
            button.innerHTML = '<svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> Buy Now';
            button.disabled = false;
        }
    });
}

// Product image carousel functionality
function changeMainImage(imageSrc, event) {
    const mainImage = document.getElementById('main-product-image');
    if (mainImage) {
        mainImage.src = imageSrc;
        
        // Update all thumbnails to remove active state
        document.querySelectorAll('.product-thumbnail').forEach(thumb => {
            thumb.classList.remove('border-folly');
            thumb.classList.add('border-transparent');
        });
        
        // Add active state to clicked thumbnail
        if (event && event.target) {
            const thumbnail = event.target.closest('.product-thumbnail');
            if (thumbnail) {
                thumbnail.classList.add('border-folly');
                thumbnail.classList.remove('border-transparent');
            }
        }
    }
}

// Size and Color selection functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize product image carousel
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    let currentImageIndex = 0;
    let autoScrollInterval;
    
    if (thumbnails.length > 0) {
        // Set the first thumbnail as active by default
        updateActiveThumbnail(0);
        
        // Add click event listeners to all thumbnails
        thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', function(e) {
                // Stop auto-scrolling when user interacts
                stopAutoScroll();
                
                // Update active thumbnail and main image
                currentImageIndex = index;
                updateActiveThumbnail(index);
                
                // Get the image source from the clicked thumbnail
                const img = this.querySelector('img');
                if (img) {
                    document.getElementById('main-product-image').src = img.src;
                }
            });
        });
        
        // Start auto-scrolling
        startAutoScroll();
        
        // Pause auto-scroll on hover of the main image container
        const mainImageEl = document.getElementById('main-product-image');
        const imageContainer = mainImageEl ? mainImageEl.closest('.aspect-square') : null;
        if (imageContainer) {
            imageContainer.addEventListener('mouseenter', stopAutoScroll);
            imageContainer.addEventListener('mouseleave', startAutoScroll);
        }
    }
    
    function updateActiveThumbnail(index) {
        // Remove active class from all thumbnails
        thumbnails.forEach(thumb => {
            thumb.classList.remove('border-folly');
            thumb.classList.add('border-transparent');
        });
        
        // Add active class to the selected thumbnail
        if (thumbnails[index]) {
            thumbnails[index].classList.add('border-folly');
            thumbnails[index].classList.remove('border-transparent');
        }
    }
    
    function startAutoScroll() {
        // Clear any existing interval
        stopAutoScroll();
        
        // Start new interval (change image every 5 seconds)
        autoScrollInterval = setInterval(() => {
            if (!thumbnails.length) return;
            
            currentImageIndex = (currentImageIndex + 1) % thumbnails.length;
            updateActiveThumbnail(currentImageIndex);
            
            // Update main image
            const thumbnailImg = thumbnails[currentImageIndex].querySelector('img');
            if (thumbnailImg) {
                document.getElementById('main-product-image').src = thumbnailImg.src;
            }
        }, 5000);
    }
    
    function stopAutoScroll() {
        if (autoScrollInterval) {
            clearInterval(autoScrollInterval);
            autoScrollInterval = null;
        }
    }
    
    // Handle size option selection
    const sizeOptions = document.querySelectorAll('.size-option');
    sizeOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove active class from all size options
            sizeOptions.forEach(opt => {
                opt.querySelector('div').classList.remove('border-folly', 'bg-folly-100', 'text-folly-800');
                opt.querySelector('div').classList.add('border-gray-300');
            });
            
            // Add active class to selected option
            const optionDiv = this.querySelector('div');
            optionDiv.classList.remove('border-gray-300');
            optionDiv.classList.add('border-folly', 'bg-folly-100', 'text-folly-800');
            
            // Check the radio button
            this.querySelector('input').checked = true;
        });
    });
    
    // Handle color option selection
    const colorOptions = document.querySelectorAll('.color-option');
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove active class from all color options
            colorOptions.forEach(opt => {
                opt.querySelector('div').classList.remove('border-indigo-500', 'bg-indigo-100', 'text-indigo-800');
                opt.querySelector('div').classList.add('border-gray-300');
            });
            
            // Add active class to selected option
            const optionDiv = this.querySelector('div');
            optionDiv.classList.remove('border-gray-300');
            optionDiv.classList.add('border-indigo-500', 'bg-indigo-100', 'text-indigo-800');
            
            // Check the radio button
            this.querySelector('input').checked = true;
        });
    });
});

// Rating system functionality
document.addEventListener('DOMContentLoaded', function() {
    const ratingStars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('rating-input');
    let selectedRating = 0;
    
    ratingStars.forEach((star, index) => {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.rating);
            ratingInput.value = selectedRating;
            updateStarDisplay();
        });
        
        star.addEventListener('mouseenter', function() {
            const hoverRating = parseInt(this.dataset.rating);
            highlightStars(hoverRating);
        });
    });
    
    // Reset to selected rating when mouse leaves the rating area
    document.getElementById('rating-stars').addEventListener('mouseleave', function() {
        updateStarDisplay();
    });
    
    function highlightStars(rating) {
        ratingStars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }
    
    function updateStarDisplay() {
        highlightStars(selectedRating);
    }
});
</script>

<style>
.bg-grid-pattern {
    background-image: radial-gradient(circle at 1px 1px, rgba(255, 0, 85, 0.15) 1px, transparent 0);
    background-size: 20px 20px;
}

@keyframes fade-in-down {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}



.animation-delay-200 {
    animation-delay: 0.2s;
}

.animation-delay-400 {
    animation-delay: 0.4s;
}

.animation-delay-600 {
    animation-delay: 0.6s;
}

.animation-delay-800 {
    animation-delay: 0.8s;
}

/* Product thumbnail styles for carousel */
.product-thumbnail {
    transition: all 0.3s ease;
}

.product-thumbnail:hover {
    transform: scale(1.05);
}

.product-thumbnail.active {
    border-color: #FF0055 !important;
    box-shadow: 0 4px 12px rgba(255, 0, 85, 0.3);
}
</style>

<?php include 'includes/footer.php'; ?>
