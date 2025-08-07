/**
 * Newsletter Subscription Functionality
 * Handles newsletter subscription form submissions and confirmations
 */

// Newsletter subscription functionality
async function subscribeNewsletter(event) {
    event.preventDefault();
    
    const emailInput = document.getElementById('newsletter-email');
    const submitButton = document.getElementById('newsletter-submit');
    const email = emailInput.value.trim();
    
    // Basic email validation
    if (!email || !isValidEmail(email)) {
        showNotification('Please enter a valid email address', 'error');
        return false;
    }
    
    // Show loading state
    const buttonText = submitButton.querySelector('span:not(.loading-spinner)') || submitButton;
    const loadingSpinner = submitButton.querySelector('.loading-spinner');
    const originalText = buttonText.textContent;
    
    // Update button state
    if (loadingSpinner) {
        loadingSpinner.classList.remove('hidden');
    }
    buttonText.textContent = 'Subscribing...';
    submitButton.disabled = true;
    
    try {
        // Get base URL dynamically
        const basePath = typeof getBasePath === 'function' ? getBasePath() : '';
        const apiUrl = `${basePath}/api/newsletter.php`;
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'subscribe',
                email: email
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Clear the form
            emailInput.value = '';
            
            // Show success popup
            showNewsletterPopup();
            
            // Log confirmation email status
            if (data.confirmation_email_sent === false) {
                console.warn('Newsletter subscription successful, but confirmation email could not be sent.');
            }
        } else {
            showNotification(data.message || 'Failed to subscribe. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Newsletter subscription error:', error);
        showNotification('An error occurred. Please try again later.', 'error');
    } finally {
        // Reset button state
        buttonText.textContent = originalText;
        if (loadingSpinner) {
            loadingSpinner.classList.add('hidden');
        }
        submitButton.disabled = false;
    }
    
    return false;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showNewsletterPopup() {
    const popup = document.getElementById('newsletter-popup');
    const content = document.getElementById('newsletter-popup-content');
    
    if (!popup || !content) {
        console.error('Newsletter popup elements not found');
        return;
    }
    
    popup.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeNewsletterPopup() {
    const popup = document.getElementById('newsletter-popup');
    const content = document.getElementById('newsletter-popup-content');
    
    if (!popup || !content) return;
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        popup.classList.add('hidden');
    }, 300);
}

// Initialize newsletter functionality
document.addEventListener('DOMContentLoaded', function() {
    // Set up newsletter form submission
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', subscribeNewsletter);
    }
    
    // Set up popup close functionality
    const popup = document.getElementById('newsletter-popup');
    if (popup) {
        // Close popup when clicking outside
        popup.addEventListener('click', function(e) {
            if (e.target === this) {
                closeNewsletterPopup();
            }
        });
        
        // Close button functionality
        const closeButton = document.querySelector('#newsletter-popup button');
        if (closeButton) {
            closeButton.addEventListener('click', closeNewsletterPopup);
        }
    }
});

// Expose functions to global scope for inline event handlers
window.subscribeNewsletter = subscribeNewsletter;
window.showNewsletterPopup = showNewsletterPopup;
window.closeNewsletterPopup = closeNewsletterPopup;