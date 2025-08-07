// Angel Marketplace - Cart Management

// Helper function to construct API URLs properly
function getApiUrl(endpoint) {
    const basePath = getBasePath();
    return basePath ? `${basePath}/${endpoint}` : `/${endpoint}`;
}

// Helper function to construct asset URLs properly
function getAssetUrl(assetPath) {
    const basePath = getBasePath();
    return basePath ? `${basePath}/assets/${assetPath}` : `/assets/${assetPath}`;
}

// Get base path dynamically
function getBasePath() {
    // Try to detect from script tag first
    const scriptTags = document.querySelectorAll('script[src*="assets/js"]');
    if (scriptTags.length > 0) {
        const scriptSrc = scriptTags[0].src;
        const url = new URL(scriptSrc);
        const scriptPath = url.pathname;
        // Remove '/assets/js/cart.js' or similar from the end
        let basePath = scriptPath.replace(/\/assets\/js\/.*$/, '');
        
        // If basePath is empty or just '/', return empty string (root)
        if (!basePath || basePath === '/') {
            return '';
        }
        
        return basePath;
    }
    
    // Fallback: try to detect from current path by looking for known files
    const currentPath = window.location.pathname;
    
    // If we're at root, return empty string
    if (currentPath === '/' || currentPath === '') {
        return '';
    }
    
    // Look for application indicators to find base path
    const pathParts = currentPath.split('/').filter(part => part);
    const appFiles = ['index.php', 'shop.php', 'cart.php', 'checkout.php', 'about.php', 'contact.php'];
    
    // Check if current file is an app file (indicates we're in the app root)
    const currentFile = pathParts[pathParts.length - 1];
    if (appFiles.includes(currentFile)) {
        const basePath = '/' + pathParts.slice(0, -1).join('/');
        return basePath === '/' ? '' : basePath;
    }
    
    // Default fallback
    return '';
}

// Color hex mapping (same as in product.php)
const colorHexMap = {
    'Black': '#000000',
    'White': '#FFFFFF', 
    'Red': '#FF0000',
    'Blue': '#0000FF',
    'Green': '#008000',
    'Yellow': '#FFFF00',
    'Pink': '#FFC0CB',
    'Purple': '#800080',
    'Orange': '#FFA500',
    'Brown': '#8B4513',
    'Gray': '#808080',
    'Navy': '#000080'
};

// Global cart state
let cartState = {
    items: [],
    total: 0,
    count: 0,
    isLoading: false
};

// Initialize cart functionality
document.addEventListener('DOMContentLoaded', function() {
    try {
        initializeCart();
        initializeOptionsModal();
        
        // Only load cart data if cart elements exist
        if (document.getElementById('cart-button') || document.querySelector('.cart-counter')) {
            loadCartData().catch(error => {
                console.warn('Could not load cart data:', error);
            });
        }
        
        // Close mini-cart when clicking outside
        document.addEventListener('click', function(e) {
            const miniCart = document.getElementById('mini-cart');
            const cartButton = document.getElementById('cart-button');
            
            if (miniCart && cartButton && 
                !miniCart.contains(e.target) && 
                !cartButton.contains(e.target)) {
                miniCart.classList.add('hidden');
            }
        });
    } catch (error) {
        console.error('Cart initialization error:', error);
    }
});

// Initialize cart event listeners
function initializeCart() {
    // Override global addToCart function
    window.addToCart = addToCartWithOptionsCheck;
}

// Initialize product options (size and color) selection modal
function initializeOptionsModal() {
    // Create product options modal if it doesn't exist
    if (!document.getElementById('product-options-modal')) {
        const modalHTML = `
            <div id="product-options-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="options-modal-content">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900">Select Options</h3>
                            <button onclick="closeOptionsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <img id="options-modal-image" src="" alt="" class="w-20 h-20 object-cover rounded-lg border border-gray-200 mb-3">
                            <h4 id="options-modal-product-name" class="font-semibold text-gray-900 mb-2"></h4>
                            <div id="options-modal-pricing" class="text-sm text-gray-600 mb-4"></div>
                        </div>
                        
                        <!-- Size Selection Section -->
                        <div id="size-selection-section" class="mb-6 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Choose Size:</label>
                            <div id="size-options" class="grid grid-cols-3 gap-2">
                                <!-- Size options will be populated here -->
                            </div>
                            <div id="size-error" class="text-red-600 text-sm mt-2 hidden">Please select a size</div>
                        </div>
                        
                        <!-- Color Selection Section -->
                        <div id="color-selection-section" class="mb-6 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Choose Color:</label>
                            <div id="color-options" class="grid grid-cols-2 gap-2">
                                <!-- Color options will be populated here -->
                            </div>
                            <div id="color-error" class="text-red-600 text-sm mt-2 hidden">Please select a color</div>
                        </div>
                        
                        <div class="flex gap-3">
                            <button onclick="closeOptionsModal()" class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                                Cancel
                            </button>
                            <button onclick="addToCartWithOptions()" id="add-to-cart-options-btn" class="flex-1 px-4 py-2 bg-folly hover:bg-folly-600 text-white rounded-lg font-medium transition-colors">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}

// Global variables for options modal
let currentProductForOptions = null;
let selectedSize = null;
let selectedColor = null;

// Add to cart with size and color check
async function addToCartWithOptionsCheck(productId, quantity = 1, sourceElement = null, options = {}) {
    console.log('addToCartWithOptionsCheck called with productId:', productId, 'options:', options);
    
    // If options are already provided (from product page), use them directly
    if (options.size || options.color) {
        console.log('Options provided, adding directly to cart with options');
        addToCartWithMiniCart(productId, quantity, sourceElement, options);
        return;
    }
    
    try {
        // Get product data
        console.log('Fetching product data...');
        const response = await fetch(getApiUrl(`api/get-product.php?id=${productId}`));
        console.log('Response status:', response.status);
        
        const productData = await response.json();
        console.log('Product data received:', productData);
        
        if (!productData.success) {
            console.log('Product data unsuccessful:', productData.message);
            showCartNotification('Product not found', 'error');
            return;
        }
        
        const product = productData.product;
        console.log('Product:', product);
        console.log('Has sizes:', product.has_sizes, 'Available sizes:', product.available_sizes);
        console.log('Has colors:', product.has_colors, 'Available colors:', product.available_colors);
        
        // Check if product has sizes OR colors (only show modal for non-product pages)
        const hasSizeOptions = product.has_sizes && product.available_sizes && product.available_sizes.length > 0;
        const hasColorOptions = product.has_colors && product.available_colors && product.available_colors.length > 0;
        
        if (hasSizeOptions || hasColorOptions) {
            console.log('Product has options, showing modal');
            // Show options selection modal
            showOptionsModal(product, quantity, sourceElement);
        } else {
            console.log('Product has no options, adding directly to cart');
            // Add directly to cart
            addToCartWithMiniCart(productId, quantity, sourceElement);
        }
    } catch (error) {
        console.error('Error checking product:', error);
        // Fallback to direct add to cart
        console.log('Falling back to direct add to cart');
        addToCartWithMiniCart(productId, quantity, sourceElement);
    }
}

// Show options selection modal
function showOptionsModal(product, quantity = 1, sourceElement = null) {
    currentProductForOptions = { ...product, quantity, sourceElement };
    selectedSize = null;
    selectedColor = null;
    
    // Populate modal content
    const modalImage = document.getElementById('options-modal-image');
    modalImage.src = getAssetUrl(`images/${product.image}`);
    modalImage.alt = product.name;
    modalImage.onerror = function() {
        this.onerror = null; // Prevent infinite loop
        this.src = getAssetUrl('images/general/placeholder.jpg');
    };
    
    document.getElementById('options-modal-product-name').textContent = product.name;
    
    // Show pricing information
    const pricingDiv = document.getElementById('options-modal-pricing');
    if (product.prices && Object.keys(product.prices).length > 1) {
        // Multi-currency pricing
        const priceEntries = Object.entries(product.prices).map(([code, price]) => {
            const currency = getCurrencyByCode(code);
            return `${currency?.symbol || code} ${parseFloat(price).toFixed(2)}`;
        });
        pricingDiv.innerHTML = `<strong>Prices:</strong> ${priceEntries.join(' | ')}`;
    } else {
        // Single currency
        pricingDiv.innerHTML = `<strong>Price:</strong> ${formatProductPrice(product)}`;
    }
    
    // Handle size options
    const sizeSection = document.getElementById('size-selection-section');
    const hasSizeOptions = product.has_sizes && product.available_sizes && product.available_sizes.length > 0;
    
    if (hasSizeOptions) {
        sizeSection.classList.remove('hidden');
        const sizeOptionsDiv = document.getElementById('size-options');
        sizeOptionsDiv.innerHTML = '';
        
        product.available_sizes.forEach(size => {
            const sizeButton = document.createElement('button');
            sizeButton.className = 'size-option px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:border-folly hover:text-folly transition-colors';
            sizeButton.textContent = size;
            sizeButton.onclick = () => selectSize(size, sizeButton);
            sizeOptionsDiv.appendChild(sizeButton);
        });
    } else {
        sizeSection.classList.add('hidden');
    }
    
    // Handle color options
    const colorSection = document.getElementById('color-selection-section');
    const hasColorOptions = product.has_colors && product.available_colors && product.available_colors.length > 0;
    
    if (hasColorOptions) {
        colorSection.classList.remove('hidden');
        const colorOptionsDiv = document.getElementById('color-options');
        colorOptionsDiv.innerHTML = '';
        
        product.available_colors.forEach(color => {
            const hexColor = colorHexMap[color] || '#CCCCCC';
            const colorButton = document.createElement('button');
            colorButton.className = 'color-option px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:border-indigo-500 hover:text-indigo-500 transition-colors flex items-center justify-center gap-2';
            colorButton.innerHTML = `
                <div class="w-4 h-4 rounded-full border border-gray-300" style="background-color: ${hexColor}; ${color === 'White' ? 'border-color: #ccc;' : ''}"></div>
                <span>${color}</span>
            `;
            colorButton.onclick = () => selectColor(color, colorButton);
            colorOptionsDiv.appendChild(colorButton);
        });
    } else {
        colorSection.classList.add('hidden');
    }
    
    // Show modal
    const modal = document.getElementById('product-options-modal');
    const content = document.getElementById('options-modal-content');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Select size
function selectSize(size, buttonElement) {
    selectedSize = size;
    
    // Update UI
    document.querySelectorAll('.size-option').forEach(btn => {
        btn.classList.remove('border-folly', 'text-folly', 'bg-folly-50');
        btn.classList.add('border-gray-300');
    });
    
    buttonElement.classList.remove('border-gray-300');
    buttonElement.classList.add('border-folly', 'text-folly', 'bg-folly-50');
    
    // Hide error
    document.getElementById('size-error').classList.add('hidden');
}

// Select color
function selectColor(color, buttonElement) {
    selectedColor = color;
    
    // Update UI
    document.querySelectorAll('.color-option').forEach(btn => {
        btn.classList.remove('border-indigo-500', 'text-indigo-500', 'bg-indigo-50');
        btn.classList.add('border-gray-300');
    });
    
    buttonElement.classList.remove('border-gray-300');
    buttonElement.classList.add('border-indigo-500', 'text-indigo-500', 'bg-indigo-50');
    
    // Hide error
    document.getElementById('color-error').classList.add('hidden');
}

// Close options modal
function closeOptionsModal() {
    const modal = document.getElementById('product-options-modal');
    const content = document.getElementById('options-modal-content');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
    
    currentProductForOptions = null;
    selectedSize = null;
    selectedColor = null;
}

// Add to cart with selected options
async function addToCartWithOptions() {
    if (!currentProductForOptions) {
        console.error('No product selected');
        return;
    }
    
    const product = currentProductForOptions;
    let hasErrors = false;
    
    // Validate size selection if required
    if (product.has_sizes && product.available_sizes && product.available_sizes.length > 0) {
        if (!selectedSize) {
            document.getElementById('size-error').classList.remove('hidden');
            hasErrors = true;
        }
    }
    
    // Validate color selection if required
    if (product.has_colors && product.available_colors && product.available_colors.length > 0) {
        if (!selectedColor) {
            document.getElementById('color-error').classList.remove('hidden');
            hasErrors = true;
        }
    }
    
    if (hasErrors) {
        return;
    }
    
    // Prepare options
    const options = {};
    if (selectedSize) {
        options.size = selectedSize;
    }
    if (selectedColor) {
        options.color = selectedColor;
    }
    
    // Close modal
    closeOptionsModal();
    
    // Add to cart with options
    addToCartWithMiniCart(product.id, product.quantity, product.sourceElement, options);
}

// Get currency information by code
function getCurrencyByCode(code) {
    const currencies = (window.availableCurrencies || []);
    return currencies.find(currency => currency.code === code);
}

// Format product price (updated to handle multi-currency)
function formatProductPrice(product, currencyCode = null) {
    if (product.prices && Object.keys(product.prices).length > 0) {
        if (currencyCode && product.prices[currencyCode]) {
            const currency = getCurrencyByCode(currencyCode);
            return `${currency?.symbol || currencyCode} ${parseFloat(product.prices[currencyCode]).toFixed(2)}`;
        } else {
            // Return default currency price
            const defaultCurrency = Object.keys(product.prices)[0];
            const currency = getCurrencyByCode(defaultCurrency);
            return `${currency?.symbol || defaultCurrency} ${parseFloat(product.prices[defaultCurrency]).toFixed(2)}`;
        }
    } else if (product.price) {
        // Fallback to legacy price
        return `£${parseFloat(product.price).toFixed(2)}`;
    }
    return '£0.00';
}

// Toggle mini-cart visibility
function toggleMiniCart() {
    const miniCart = document.getElementById('mini-cart');
    if (miniCart) {
        if (miniCart.classList.contains('hidden')) {
            loadCartData().then(() => {
                miniCart.classList.remove('hidden');
            });
        } else {
            miniCart.classList.add('hidden');
        }
    }
}

// Load cart data from API
async function loadCartData() {
    try {
        cartState.isLoading = true;
        const selectedCurrency = window.currentCurrency || 'GBP';
        const response = await fetch(`${getApiUrl('api/cart.php')}?currency=${selectedCurrency}`);
        
        if (!response.ok) {
            throw new Error('Failed to load cart data');
        }
        
        const data = await response.json();
        
        if (data.success) {
            cartState.items = data.cart || [];
            cartState.total = parseFloat(data.total || 0);
            cartState.count = data.count || 0;
            
            updateCartUI();
        } else {
            throw new Error(data.message || 'Failed to load cart data');
        }
    } catch (error) {
        console.error('Error loading cart data:', error);
        showCartNotification('Error loading cart. Please refresh the page.', 'error');
    } finally {
        cartState.isLoading = false;
    }
}

// Update cart counter and UI elements
function updateCartUI() {
    // Update cart counters
    const cartCounters = document.querySelectorAll('.cart-counter');
    cartCounters.forEach(counter => {
        const oldCount = parseInt(counter.textContent) || 0;
        const newCount = cartState.count;
        
        counter.textContent = newCount;
        
        // Always update visibility based on cart count
        if (newCount > 0) {
            counter.style.display = 'flex';
            
            // Add pulse animation if count increased
            if (newCount > oldCount) {
                counter.classList.add('animate-ping');
                setTimeout(() => {
                    counter.classList.remove('animate-ping');
                }, 500);
            }
        } else {
            counter.style.display = 'none';
        }
    });
    
    // Update any cart totals on the page
    const cartTotals = document.querySelectorAll('.cart-total');
    cartTotals.forEach(total => {
        total.textContent = formatPrice(cartState.total);
    });
    
    // Update mini-cart content
    updateMiniCart();
}

// Update mini-cart content
function updateMiniCart() {
    const miniCartItems = document.getElementById('mini-cart-items');
    const miniCartTotal = document.getElementById('mini-cart-total');
    
    if (!miniCartItems || !miniCartTotal) return;
    
    // Get the selected currency from the page
    const selectedCurrency = window.currentCurrency || 'GBP';
    const currencySymbol = getCurrencySymbol(selectedCurrency);
    
    miniCartTotal.textContent = formatPrice(cartState.total, selectedCurrency);
    
    if (cartState.items.length === 0) {
        miniCartItems.innerHTML = `
            <div class="p-6 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"></path>
                </svg>
                <p class="text-sm">Your cart is empty</p>
                <a href="${getBasePath() ? getBasePath() + '/shop.php' : '/shop.php'}" class="text-folly hover:text-folly-600 text-sm font-medium mt-2 inline-block">
                    Continue Shopping
                </a>
            </div>
        `;
        return;
    }
    
    const itemsHTML = cartState.items.map(item => `
        <div class="flex items-center p-3 border-b border-gray-100 last:border-b-0">
            <img 
                src="${getAssetUrl(`images/${item.image}`)}" 
                alt="${escapeHtml(item.name)}"
                class="w-12 h-12 object-cover rounded border border-gray-200 flex-shrink-0"
                onerror="this.onerror=null;this.src='${getAssetUrl('images/general/placeholder.jpg')}'"
            >
            <div class="ml-3 flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-900 truncate">
                    ${escapeHtml(item.name)}
                </h4>
                ${item.size ? `<div class="text-xs text-gray-500 mb-1">Size: ${escapeHtml(item.size)}</div>` : ''}
                ${item.color ? `<div class="text-xs text-gray-500 mb-1">Color: ${escapeHtml(item.color)}</div>` : ''}
                <div class="flex items-center justify-between mt-1">
                    <div class="flex items-center space-x-2">
                        <button 
                            onclick="updateCartItemQuantity(${item.product_id}, ${item.quantity - 1})"
                            class="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition-colors duration-200"
                            ${item.quantity <= 1 ? 'disabled' : ''}
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </button>
                        <span class="text-sm font-medium w-6 text-center">${item.quantity}</span>
                        <button 
                            onclick="updateCartItemQuantity(${item.product_id}, ${item.quantity + 1})"
                            class="w-6 h-6 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition-colors duration-200"
                            ${item.quantity >= item.stock ? 'disabled' : ''}
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-gray-900">${formatPrice(item.item_total)}</div>
                        <button 
                            onclick="removeCartItem(${item.product_id})"
                            class="text-xs text-red-600 hover:text-red-700 mt-1"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    miniCartItems.innerHTML = itemsHTML;
}

// Add item to cart with mini-cart integration
async function addToCartWithMiniCart(productId, quantity = 1, sourceElement = null, options = {}) {
    if (cartState.isLoading) return;
    
    cartState.isLoading = true;
    
    // Show loading state on button
    const button = sourceElement || (window.event && window.event.target);
    let originalText = '';
    if (button) {
        originalText = button.textContent;
        button.textContent = 'Adding...';
        button.disabled = true;
    }
    
    try {
        const requestBody = {
            action: 'add',
            product_id: productId,
            quantity: quantity
        };
        
        // Add options (like size) if provided
        if (options && Object.keys(options).length > 0) {
            requestBody.options = options;
        }
        
        console.log('Sending request to cart API:', requestBody);
        
        const response = await fetch(getApiUrl('api/cart.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody)
        });
        
        console.log('Cart API response status:', response.status);
        
        const data = await response.json();
        console.log('Cart API response data:', data);
        
        if (data.success) {
            // Check if this is a "buy now" action
            if (window.buyNowMode) {
                // Clear the buy now flag
                window.buyNowMode = false;
                
                // Show brief success message
                showCartNotification('Added to cart! Redirecting to checkout...', 'success');
                
                // Reload cart data and redirect to checkout
                await loadCartData();
                
                // Redirect to checkout after a brief delay
                setTimeout(() => {
                    const basePath = getBasePath();
                    window.location.href = basePath ? `${basePath}/checkout.php` : '/checkout.php';
                }, 1000);
                
                return; // Exit early to avoid mini-cart logic
            }
            
            // Normal add to cart behavior
            showCartNotification('Product added to cart!', 'info');
            
            // Reload cart data and show mini-cart
            await loadCartData();
            
            // Auto-open mini-cart for a moment
            const miniCart = document.getElementById('mini-cart');
            if (miniCart) {
                miniCart.classList.remove('hidden');
                
                // Auto-close after 3 seconds unless user interacts
                setTimeout(() => {
                    if (!miniCart.matches(':hover')) {
                        miniCart.classList.add('hidden');
                    }
                }, 3000);
            }
            
        } else {
            showCartNotification(data.message || 'Failed to add product to cart.', 'error');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showCartNotification('An error occurred while adding the product to cart.', 'error');
    } finally {
        cartState.isLoading = false;
        
        // Reset button state
        if (button) {
            button.textContent = originalText;
            button.disabled = false;
        }
    }
}

// Update cart item quantity
async function updateCartItemQuantity(productId, newQuantity) {
    if (cartState.isLoading) return;
    
    cartState.isLoading = true;
    
    try {
        const response = await fetch(getApiUrl('api/cart.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update',
                product_id: productId,
                quantity: newQuantity
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadCartData();
            if (newQuantity === 0) {
                showCartNotification('Item removed from cart', 'info');
            }
        } else {
            showCartNotification(data.message || 'Failed to update cart.', 'error');
        }
    } catch (error) {
        console.error('Error updating cart:', error);
        showCartNotification('An error occurred while updating the cart.', 'error');
    } finally {
        cartState.isLoading = false;
    }
}

// Remove item from cart
async function removeCartItem(productId) {
    if (cartState.isLoading) return;
    
    cartState.isLoading = true;
    
    try {
        const response = await fetch(getApiUrl('api/cart.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove',
                product_id: productId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showCartNotification('Item removed from cart', 'info');
            await loadCartData();
        } else {
            showCartNotification(data.message || 'Failed to remove item.', 'error');
        }
    } catch (error) {
        console.error('Error removing item:', error);
        showCartNotification('An error occurred while removing the item.', 'error');
    } finally {
        cartState.isLoading = false;
    }
}

// Proceed to checkout
function proceedToCheckout() {
    if (cartState.count === 0) {
        showCartNotification('Your cart is empty', 'warning');
        return;
    }
    
    // Redirect to checkout page
    const basePath = getBasePath();
    window.location.href = basePath ? `${basePath}/checkout.php` : '/checkout.php';
}

// Show cart-specific notifications
function showCartNotification(message, type = 'info') {
    // Try to use existing notification system if available
    try {
        if (window.showNotification && typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }
    } catch (e) {
        // Fallback to our custom notification
    }
    
    // Fallback notification system
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full max-w-sm`;
    
    // Set styles based on type
    switch (type) {
        case 'success':
            notification.className += ' bg-green-500 text-white';
            break;
        case 'error':
            notification.className += ' bg-red-500 text-white';
            break;
        case 'warning':
            notification.className += ' bg-orange-500 text-white';
            break;
        default:
            notification.className += ' text-white';
            notification.style.backgroundColor = '#FF0055'; // folly color for info/default
    }
    
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"></path>
            </svg>
            <span>${escapeHtml(message)}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Utility functions
function formatPrice(price, currencyCode = null) {
    // Get the selected currency from the page or use the provided one
    const selectedCurrency = currencyCode || (window.currentCurrency || 'GBP');
    const currencySymbol = getCurrencySymbol(selectedCurrency);
    
    // Format the price with the appropriate currency symbol
    return currencySymbol + parseFloat(price || 0).toFixed(2);
}

// Get currency symbol based on currency code
function getCurrencySymbol(currencyCode) {
    const c = getCurrencyByCode(currencyCode);
    if (c && c.symbol) return c.symbol;
    switch (currencyCode) {
        case 'GBP': return '£';
        case 'USD': return '$';
        case 'EUR': return '€';
        default: return '';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export functions for global use
window.toggleMiniCart = toggleMiniCart;
window.addToCart = addToCartWithOptionsCheck;
window.updateCartItemQuantity = updateCartItemQuantity;
window.removeCartItem = removeCartItem;
window.proceedToCheckout = proceedToCheckout;