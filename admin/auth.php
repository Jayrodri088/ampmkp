<?php
// Dedicated authentication handler
// Start session with secure settings that work in both development and production
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Set session save path to system temp directory (default XAMPP path)
$session_path = sys_get_temp_dir();
if (is_writable($session_path)) {
    session_save_path($session_path);
}

// Only require secure cookies if we're using HTTPS
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

ini_set('session.cookie_secure', $is_https ? 1 : 0);

session_start();



$login_error = '';
$login_success = false;

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    // Hashed password for 'amp@2025' - change this to your desired password hash
    // To generate a new hash, use: password_hash('your_password', PASSWORD_DEFAULT)
    $stored_password_hash = '$2y$12$Tv9s1kNlEIuRxGMOUZctlezrIEy9PTQdcjK6ZaTsa/RBC7SoSDXyS'; // This is 'amp@2025'
    
    $submitted_password = $_POST['admin_password'];
    
    // Debug information (only if debug requested)
    if (isset($_GET['debug'])) {
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
        echo "<h3>Debug Info:</h3>";
        echo "<p>Password submitted: " . (strlen($submitted_password) > 0 ? "***PROVIDED*** (length: " . strlen($submitted_password) . ")" : "NOT PROVIDED") . "</p>";
        echo "<p>Hash verification: " . (password_verify($submitted_password, $stored_password_hash) ? 'VALID' : 'INVALID') . "</p>";
        echo "<p>HTTPS detected: " . ($is_https ? 'YES' : 'NO') . "</p>";
        echo "<p>Session ID: " . session_id() . "</p>";
        echo "<p>Session save path: " . session_save_path() . "</p>";
        echo "<p>Session save path writable: " . (is_writable(session_save_path()) ? 'YES' : 'NO') . "</p>";
        echo "</div>";
    }
    
    if (password_verify($submitted_password, $stored_password_hash)) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = 'Administrator';
        $_SESSION['login_time'] = time();
        
        $login_success = true;
        
        // Redirect to dashboard after successful login
        header('Location: index.php?login=success');
        exit;
    } else {
        $login_error = 'Invalid password. Please check your password and try again.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_start();
    header('Location: auth.php?message=logged_out');
    exit;
}

// Check if already logged in (and redirect to dashboard)
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Angel Marketplace</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'folly': {
                            DEFAULT: '#FF0055',
                            50: '#ffccdd',
                            100: '#ff99bb',
                            200: '#ff6699',
                            300: '#ff3377',
                            400: '#ff0055',
                            500: '#cc0044',
                            600: '#990033',
                            700: '#660022',
                            800: '#330011',
                            900: '#1a0008'
                        },
                        'charcoal': {
                            DEFAULT: '#3B4255',
                            50: '#f7f8fa',
                            100: '#ebeef3',
                            200: '#d4d7e1',
                            300: '#a8afc3',
                            400: '#7d88a5',
                            500: '#596380',
                            600: '#3b4255',
                            700: '#2f3443',
                            800: '#232733',
                            900: '#171a22'
                        },
                        'tangerine': {
                            DEFAULT: '#F5884B',
                            50: '#fde8db',
                            100: '#fbd0b8',
                            200: '#f9b994',
                            300: '#f7a270',
                            400: '#f5884b',
                            500: '#f16310',
                            600: '#b64a0b',
                            700: '#793107',
                            800: '#3d1904',
                            900: '#1e0c02'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-charcoal-50 via-gray-50 to-tangerine-50 min-h-screen font-sans">
    <!-- Background decorative elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-20 w-72 h-72 bg-folly-200 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
        <div class="absolute top-32 right-20 w-72 h-72 bg-tangerine-200 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-32 w-72 h-72 bg-charcoal-200 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-4000"></div>
    </div>

    <div class="relative min-h-screen flex items-center justify-center p-3 sm:p-4 lg:p-6">
        <div class="w-full max-w-sm sm:max-w-md">
            <!-- Login Card -->
            <div class="bg-white/95 backdrop-blur-lg rounded-xl lg:rounded-2xl shadow-2xl border border-gray-200/50 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-charcoal to-charcoal-700 text-white px-6 lg:px-8 py-5 lg:py-6 text-center">
                    <div class="flex items-center justify-center mb-2 lg:mb-3">
                        <div class="w-10 h-10 lg:w-12 lg:h-12 bg-folly rounded-full flex items-center justify-center">
                            <i class="bi bi-shield-check text-xl lg:text-2xl text-white"></i>
                        </div>
                    </div>
                    <h1 class="text-xl lg:text-2xl font-bold mb-1 lg:mb-2">Admin Login</h1>
                    <p class="text-charcoal-200 text-xs lg:text-sm">Angel Marketplace Administration</p>
                </div>
                
                <!-- Form Body -->
                <div class="p-6 lg:p-8">
                    <!-- Success Message -->
                    <?php if ($login_success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 px-3 lg:px-4 py-2 lg:py-3 rounded-lg lg:rounded-xl mb-4 lg:mb-6">
                        <div class="flex items-center">
                            <i class="bi bi-check-circle mr-2 flex-shrink-0"></i>
                            <span class="text-sm lg:text-base">Login successful!</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Error Message -->
                    <?php if ($login_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 px-3 lg:px-4 py-2 lg:py-3 rounded-lg lg:rounded-xl mb-4 lg:mb-6">
                        <div class="flex items-center">
                            <i class="bi bi-exclamation-triangle mr-2 flex-shrink-0"></i>
                            <span class="text-sm lg:text-base"><?= htmlspecialchars($login_error) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Logout Success Message -->
                    <?php if (isset($_GET['message']) && $_GET['message'] === 'logged_out'): ?>
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-3 lg:px-4 py-2 lg:py-3 rounded-lg lg:rounded-xl mb-4 lg:mb-6">
                        <div class="flex items-center">
                            <i class="bi bi-info-circle mr-2 flex-shrink-0"></i>
                            <span class="text-sm lg:text-base">You have been logged out successfully.</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="auth.php" class="space-y-5 lg:space-y-6">
                        <div>
                            <label for="admin_password" class="block text-xs lg:text-sm font-medium text-charcoal-700 mb-2">
                                <i class="bi bi-key mr-2 text-folly"></i>Admin Password
                            </label>
                            <input type="password" 
                                   class="w-full px-3 lg:px-4 py-3 text-sm lg:text-base border border-gray-300 rounded-lg lg:rounded-xl focus:border-folly focus:ring-2 focus:ring-folly/20 transition-all duration-200 touch-manipulation" 
                                   id="admin_password" 
                                   name="admin_password" 
                                   placeholder="Enter your admin password"
                                   required 
                                   autofocus>
                        </div>
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-folly to-folly-600 hover:from-folly-600 hover:to-folly-700 text-white py-3 px-6 rounded-lg lg:rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-xl touch-manipulation text-sm lg:text-base">
                            <i class="bi bi-box-arrow-in-right mr-2"></i>
                            Login to Admin Panel
                        </button>
                    </form>
                    
                    <!-- Divider -->
                    <div class="relative my-5 lg:my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-xs lg:text-sm">
                            <span class="px-3 lg:px-4 bg-white text-gray-500">or</span>
                        </div>
                    </div>
                    
                    <!-- Back to Site -->
                    <div class="text-center">
                        <a href="../" class="inline-flex items-center text-charcoal-600 hover:text-folly transition-colors duration-200 text-xs lg:text-sm font-medium touch-manipulation py-2 px-1">
                            <i class="bi bi-arrow-left mr-2"></i>
                            Back to Marketplace
                        </a>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-50 px-6 lg:px-8 py-3 lg:py-4 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-gray-500 text-xs">
                            <i class="bi bi-shield-lock mr-1"></i>
                            Secure admin authentication system
                        </p>

                    </div>
                </div>
            </div>
            
            <!-- Additional Info -->
            <div class="text-center mt-4 lg:mt-6">
                <p class="text-charcoal-400 text-xs lg:text-sm">
                    Secure admin access for Angel Marketplace
                </p>
            </div>
        </div>
    </div>

    <style>
        .touch-manipulation {
            touch-action: manipulation;
        }
        
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        
        /* Custom focus styles */
        input:focus {
            outline: none;
        }
        
        /* Button hover effect */
        button:hover {
            box-shadow: 0 10px 25px rgba(255, 0, 85, 0.3);
        }
        
        /* Touch-friendly interactions */
        @media (max-width: 768px) {
            .touch-manipulation:active {
                transform: scale(0.98);
            }
            
            /* Optimize for small screens */
            .bg-gradient-to-br {
                background-attachment: fixed;
            }
        }
        
        /* Enhanced mobile interactions */
        @media (hover: none) and (pointer: coarse) {
            button:hover {
                transform: none;
                box-shadow: 0 4px 15px rgba(255, 0, 85, 0.2);
            }
            
            button:active {
                transform: scale(0.98);
                box-shadow: 0 2px 8px rgba(255, 0, 85, 0.4);
            }
        }
    </style>
    
    <script>
        // Enhanced mobile experience
        document.addEventListener('DOMContentLoaded', function() {
            // Add touch feedback for mobile devices
            if ('ontouchstart' in window) {
                const touchElements = document.querySelectorAll('.touch-manipulation');
                touchElements.forEach(element => {
                    element.addEventListener('touchstart', function() {
                        this.style.transform = 'scale(0.98)';
                    }, { passive: true });
                    
                    element.addEventListener('touchend', function() {
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }, { passive: true });
                });
            }
            
            // Auto-focus password field on larger screens
            if (window.innerWidth >= 768) {
                const passwordInput = document.getElementById('admin_password');
                if (passwordInput) {
                    passwordInput.focus();
                }
            }
            
            // Handle form submission with better UX
            const form = document.querySelector('form');
            const submitButton = document.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function() {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i>Logging in...';
                submitButton.classList.add('opacity-75');
            });
        });
    </script>
</body>
</html> 