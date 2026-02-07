<?php
$page_title = 'Sign in';
$page_description = 'Sign in to your Angel Marketplace account with your email.';

require_once 'includes/functions.php';

// If already logged in, redirect to home
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isCustomerLoggedIn()) {
    header('Location: ' . getBaseUrl());
    exit;
}

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$redirectUrl = $redirect ? getBaseUrl($redirect) : getBaseUrl();

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-white/60 backdrop-blur-xl border-b border-white/30 py-4">
    <div class="container mx-auto px-4">
        <nav class="text-sm flex items-center space-x-2">
            <a href="<?php echo getBaseUrl(); ?>" class="text-gray-500 hover:text-folly transition-colors">Home</a>
            <span class="text-gray-300">/</span>
            <span class="text-charcoal-900 font-medium">Sign in</span>
        </nav>
    </div>
</div>

<section class="py-12 sm:py-16 md:py-20 bg-gradient-to-b from-gray-50 to-white">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto">
            <div class="glass-strong rounded-2xl shadow-xl p-6 sm:p-8 md:p-10">
                <h1 class="text-2xl sm:text-3xl font-bold text-charcoal-900 mb-2 font-display">Sign in</h1>
                <p class="text-gray-600 text-sm sm:text-base mb-6">No password needed. We'll send a one-time code to your email.</p>

                <!-- Step 1: Email -->
                <div id="step-email">
                    <form id="form-email" class="space-y-4">
                        <div>
                            <label for="login-email" class="block text-sm font-medium text-charcoal-700 mb-1">Email address</label>
                            <input type="email" id="login-email" name="email" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly/30 focus:border-folly font-sans"
                                   placeholder="you@example.com" autocomplete="email">
                            <p id="email-error" class="mt-1 text-sm text-red-600 hidden"></p>
                        </div>
                        <button type="submit" id="btn-send-code" class="w-full py-3.5 px-6 bg-gradient-to-r from-folly to-folly-500 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-folly/25 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                            <span id="btn-send-text">Send code</span>
                        </button>
                    </form>
                </div>

                <!-- Step 2: Code -->
                <div id="step-code" class="hidden">
                    <p class="text-sm text-gray-600 mb-4">We sent a 6-digit code to <strong id="code-email-display"></strong>. Enter it below.</p>
                    <form id="form-code" class="space-y-4">
                        <input type="hidden" id="code-email" name="email">
                        <div>
                            <label for="login-code" class="block text-sm font-medium text-charcoal-700 mb-1">Code</label>
                            <input type="text" id="login-code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly/30 focus:border-folly font-sans text-center text-lg tracking-widest"
                                   placeholder="000000" autocomplete="one-time-code">
                            <p id="code-error" class="mt-1 text-sm text-red-600 hidden"></p>
                        </div>
                        <button type="submit" id="btn-verify" class="w-full py-3.5 px-6 bg-gradient-to-r from-folly to-folly-500 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-folly/25 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                            <span id="btn-verify-text">Sign in</span>
                        </button>
                    </form>
                    <p class="mt-4 text-sm text-gray-500">
                        <button type="button" id="back-to-email" class="text-folly hover:underline">Use a different email</button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    const apiUrl = <?php echo json_encode(getBaseUrl('api/customer-auth.php')); ?>;
    const redirectUrl = <?php echo json_encode($redirectUrl); ?>;

    const stepEmail = document.getElementById('step-email');
    const stepCode = document.getElementById('step-code');
    const formEmail = document.getElementById('form-email');
    const formCode = document.getElementById('form-code');
    const loginEmail = document.getElementById('login-email');
    const codeEmail = document.getElementById('code-email');
    const codeEmailDisplay = document.getElementById('code-email-display');
    const loginCode = document.getElementById('login-code');
    const emailError = document.getElementById('email-error');
    const codeError = document.getElementById('code-error');
    const btnSendCode = document.getElementById('btn-send-code');
    const btnSendText = document.getElementById('btn-send-text');
    const btnVerify = document.getElementById('btn-verify');
    const btnVerifyText = document.getElementById('btn-verify-text');
    const backToEmail = document.getElementById('back-to-email');

    function showEmailError(msg) {
        emailError.textContent = msg || '';
        emailError.classList.toggle('hidden', !msg);
    }
    function showCodeError(msg) {
        codeError.textContent = msg || '';
        codeError.classList.toggle('hidden', !msg);
    }

    formEmail.addEventListener('submit', async function(e) {
        e.preventDefault();
        showEmailError('');
        const email = loginEmail.value.trim();
        if (!email) {
            showEmailError('Please enter your email.');
            return;
        }
        btnSendCode.disabled = true;
        btnSendText.textContent = 'Sending...';
        try {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'request_code', email: email })
            });
            let data = {};
            try {
                const text = await res.text();
                data = text ? JSON.parse(text) : {};
            } catch (parseErr) {
                showEmailError('Invalid response from server. Please try again.');
                btnSendCode.disabled = false;
                btnSendText.textContent = 'Send code';
                return;
            }
            if (data.success) {
                codeEmail.value = email;
                codeEmailDisplay.textContent = email;
                stepEmail.classList.add('hidden');
                stepCode.classList.remove('hidden');
                loginCode.value = '';
                showCodeError('');
                loginCode.focus();
            } else {
                showEmailError(data.message || (res.ok ? 'Something went wrong. Try again.' : 'Request failed. Please try again.'));
            }
        } catch (err) {
            showEmailError('Network error. Please try again.');
        }
        btnSendCode.disabled = false;
        btnSendText.textContent = 'Send code';
    });

    formCode.addEventListener('submit', async function(e) {
        e.preventDefault();
        showCodeError('');
        const email = codeEmail.value.trim();
        const code = loginCode.value.trim().replace(/\D/g, '').slice(0, 6);
        if (!email || code.length !== 6) {
            showCodeError('Please enter the 6-digit code.');
            return;
        }
        btnVerify.disabled = true;
        btnVerifyText.textContent = 'Signing in...';
        try {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'verify_code', email: email, code: code }),
                credentials: 'same-origin'
            });
            const data = await res.json().catch(function() { return {}; });
            if (data.success) {
                window.location.href = redirectUrl;
                return;
            }
            showCodeError(data.message || 'Invalid or expired code. Request a new one.');
        } catch (err) {
            showCodeError('Network error. Please try again.');
        }
        btnVerify.disabled = false;
        btnVerifyText.textContent = 'Sign in';
    });

    backToEmail.addEventListener('click', function() {
        stepCode.classList.add('hidden');
        stepEmail.classList.remove('hidden');
        showEmailError('');
        showCodeError('');
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
