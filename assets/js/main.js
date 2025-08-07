// Angel Marketplace - Main JavaScript
// Version: 1.0
// Fixed and optimized version

// Get base path dynamically
function getBasePath() {
    // Try to detect from script tag first
    const scriptTags = document.querySelectorAll('script[src*="assets/js"]');
    if (scriptTags.length > 0) {
        const scriptSrc = scriptTags[0].src;
        const url = new URL(scriptSrc);
        const scriptPath = url.pathname;
        // Remove '/assets/js/main.js' or similar from the end
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

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    try {
        initializeApp();
    } catch (error) {
        console.error('Application initialization error:', error);
    }
});

// Main initialization function
function initializeApp() {
    initializeScrollToTop();
    initializeFormValidation();
    initializeImageLazyLoading();
    initializeSearch();
    updateCartCounter();
    
    // Initialize Alpine.js components if available
    if (typeof Alpine !== 'undefined') {
        Alpine.start();
    }
    
    console.log('Angel Marketplace initialized successfully');
}

// Scroll to Top Button
function initializeScrollToTop() {
    // Create scroll to top button
    const scrollButton = document.createElement('button');
    scrollButton.innerHTML = '↑';
    scrollButton.className = 'fixed bottom-6 right-6 text-white w-12 h-12 rounded-full shadow-lg transition-all duration-200 opacity-0 pointer-events-none z-50';
    scrollButton.style.backgroundColor = '#FF0055'; // folly color
    scrollButton.style.setProperty('--hover-bg', '#CC0044'); // folly-600 color
    scrollButton.addEventListener('mouseover', () => {
        scrollButton.style.backgroundColor = '#CC0044';
    });
    scrollButton.addEventListener('mouseout', () => {
        scrollButton.style.backgroundColor = '#FF0055';
    });
    scrollButton.setAttribute('aria-label', 'Scroll to top');
    scrollButton.setAttribute('type', 'button');
    document.body.appendChild(scrollButton);
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', throttle(function() {
        if (window.pageYOffset > 300) {
            scrollButton.classList.remove('opacity-0', 'pointer-events-none');
        } else {
            scrollButton.classList.add('opacity-0', 'pointer-events-none');
        }
    }, 100));
    
    // Scroll to top when clicked
    scrollButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                validateField(input);
            });
            
            // Clear errors on input
            input.addEventListener('input', function() {
                clearFieldError(input);
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(function(input) {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    let isValid = true;
    let message = '';
    
    // Clear previous error
    clearFieldError(field);
    
    // Required validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required.';
    }
    
    // Email validation
    if (type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        message = 'Please enter a valid email address.';
    }
    
    // Phone validation
    if (type === 'tel' && value && !isValidPhone(value)) {
        isValid = false;
        message = 'Please enter a valid phone number.';
    }
    
    // Minimum length validation
    if (field.hasAttribute('minlength') && value.length < parseInt(field.getAttribute('minlength'))) {
        isValid = false;
        message = `Minimum ${field.getAttribute('minlength')} characters required.`;
    }
    
    if (!isValid) {
        showFieldError(field, message);
    }
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

function showFieldError(field, message) {
    field.classList.add('border-red-500', 'error');
    
    // Remove existing error
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-500 text-sm mt-1 form-error';
    errorDiv.textContent = message;
    errorDiv.setAttribute('data-error-for', field.name || field.id);
    
    // Insert after the field
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

function clearFieldError(field) {
    field.classList.remove('border-red-500', 'error');
    
    const errorDiv = field.parentNode.querySelector(`[data-error-for="${field.name || field.id}"]`);
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Image Lazy Loading with Error Handling
function initializeImageLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    loadImage(img);
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(function(img) {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for browsers that don't support IntersectionObserver
        images.forEach(function(img) {
            loadImage(img);
        });
    }
}

function loadImage(img) {
    img.src = img.dataset.src;
    img.classList.remove('lazy');
    img.classList.add('loaded');
    
    // Remove data-src attribute
    img.removeAttribute('data-src');
}

// Cart Functionality (Basic - Extended by cart.js)
function updateCartCounter() {
            const basePath = getBasePath();
        const apiUrl = basePath ? `${basePath}/api/cart.php` : '/api/cart.php';
        fetch(apiUrl, {
        method: 'GET'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const cartCounters = document.querySelectorAll('.cart-counter');
            cartCounters.forEach(function(counter) {
                if (data.count > 0) {
                    counter.textContent = data.count;
                    counter.style.display = 'flex';
                } else {
                    counter.style.display = 'none';
                }
            });
        }
    })
    .catch(error => {
        console.warn('Could not update cart counter:', error);
    });
}

// Search functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('input[type="search"], input[name="q"]');
    
    searchInputs.forEach(function(input) {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    showSearchSuggestions(query, this);
                }, 300);
            } else {
                hideSearchSuggestions();
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !e.target.closest('.search-suggestions')) {
                hideSearchSuggestions();
            }
        });
        
        // Handle keyboard navigation
        input.addEventListener('keydown', function(e) {
            const suggestions = document.querySelector('.search-suggestions');
            if (!suggestions) return;
            
            const items = suggestions.querySelectorAll('.search-suggestion');
            const activeItem = suggestions.querySelector('.search-suggestion.active');
            let activeIndex = Array.from(items).indexOf(activeItem);
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, items.length - 1);
                    updateActiveSuggestion(items, activeIndex);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                    updateActiveSuggestion(items, activeIndex);
                    break;
                case 'Enter':
                    if (activeItem) {
                        e.preventDefault();
                        activeItem.click();
                    }
                    break;
                case 'Escape':
                    hideSearchSuggestions();
                    break;
            }
        });
    });
}

function updateActiveSuggestion(items, activeIndex) {
    items.forEach((item, index) => {
        item.classList.toggle('active', index === activeIndex);
    });
}

function showSearchSuggestions(query, inputElement) {
    // Hide existing suggestions
    hideSearchSuggestions();
    
    // Create suggestions container
    const suggestions = document.createElement('div');
    suggestions.className = 'search-suggestions absolute top-full left-0 right-0 bg-white border border-gray-200 border-t-0 rounded-b-md shadow-lg max-h-64 overflow-y-auto z-50';
    
    // Position relative to input
    const inputContainer = inputElement.closest('.relative') || inputElement.parentNode;
    inputContainer.style.position = 'relative';
    inputContainer.appendChild(suggestions);
    
    // Add loading state
    suggestions.innerHTML = '<div class="p-3 text-gray-500 text-sm">Searching...</div>';
    
    // Mock search suggestions (replace with actual API call)
    setTimeout(() => {
        const mockResults = [
            'T-shirts - Grace',
            'Hoodies - Grace',
            'Sweatshirts-Loveworld',
            'Premium Jackets'
        ].filter(item => item.toLowerCase().includes(query.toLowerCase()));
        
        if (mockResults.length > 0) {
            suggestions.innerHTML = mockResults.map(result => 
                `<div class="search-suggestion p-3 cursor-pointer hover:bg-gray-50 text-sm" data-query="${result}">
                    <span class="font-medium">${result}</span>
                </div>`
            ).join('');
            
            // Add click handlers
            suggestions.querySelectorAll('.search-suggestion').forEach(item => {
                item.addEventListener('click', function() {
                    const query = this.dataset.query;
                    inputElement.value = query;
                    hideSearchSuggestions();
                    
                    // Trigger search
                    const form = inputElement.closest('form');
                    if (form) {
                        form.submit();
                    }
                });
            });
        } else {
            suggestions.innerHTML = '<div class="p-3 text-gray-500 text-sm">No suggestions found</div>';
        }
    }, 500);
}

function hideSearchSuggestions() {
    const suggestions = document.querySelector('.search-suggestions');
    if (suggestions) {
        suggestions.remove();
    }
}

// Simple notification system
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 max-w-sm transform translate-x-full transition-transform duration-300`;
    
    // Set styles based on type
    switch (type) {
        case 'success':
            notification.classList.add('bg-green-500', 'text-white');
            break;
        case 'error':
            notification.classList.add('bg-red-500', 'text-white');
            break;
        case 'warning':
            notification.classList.add('text-white');
            notification.style.backgroundColor = '#F5884B'; // tangerine color
            break;
        default:
            notification.classList.add('text-white');
            notification.style.backgroundColor = '#FF0055'; // folly color for info/default
    }
    
    notification.innerHTML = `
        <div class="flex items-center">
            <span>${escapeHtml(message)}</span>
            <button class="ml-3 text-white hover:text-gray-200" onclick="this.parentNode.parentNode.remove()" aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto-remove after duration
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, duration);
    
    return notification;
}

// Utility Functions
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Error handling for images
function handleImageError(img) {
    if (!img.dataset.fallbackAttempted) {
        img.dataset.fallbackAttempted = 'true';
        img.src = getBasePath() + '/assets/images/general/placeholder.jpg';
        img.alt = 'Image not available';
    }
}

// Add global error handler for images
document.addEventListener('error', function(e) {
    if (e.target.tagName === 'IMG') {
        handleImageError(e.target);
    }
}, true);

// Performance optimization: Load non-critical features after page load
window.addEventListener('load', function() {
    // Track page views (if analytics are configured)
    if (window.gtag) {
        gtag('config', 'GA_MEASUREMENT_ID', {
            page_title: document.title,
            page_location: window.location.href
        });
    }
    
    // Initialize any additional features
    console.log('Angel Marketplace fully loaded');
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden - pause any animations or timers
        console.log('Page hidden');
    } else {
        // Page is visible - resume functionality
        console.log('Page visible');
        updateCartCounter(); // Refresh cart when page becomes visible
    }
});

// Expose functions to global scope for inline event handlers
window.showNotification = showNotification;
window.updateCartCounter = updateCartCounter;
window.handleImageError = handleImageError;

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        showNotification,
        updateCartCounter,
        validateForm,
        escapeHtml
    };
}