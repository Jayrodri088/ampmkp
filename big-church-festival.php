<?php
$page_title = 'Big Church Festival';
$page_description = 'Join our Big Church Festival community — leave your details and we\'ll be in touch.';

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mail_config.php';

// CSRF token setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = false;
$error = '';

$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        // Honeypot
        if (!empty($_POST['website'])) {
            // bot detected; pretend success
            $success = true;
        } else {
            // Sanitize inputs
            $name = sanitizeInput($_POST['name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $phone = sanitizeInput($_POST['phone'] ?? '');

            // Basic validation
            if ($name === '' || $email === '' || $phone === '') {
                $error = 'Name, email, and phone are required.';
            } elseif (!validateEmail($email)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Persist to JSON for audit and backup
                $leads = readJsonFile('festival_leads.json');
                $leads[] = [
                    'id' => time(),
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'source' => 'big-church-festival',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'submitted_at' => date('Y-m-d H:i:s')
                ];
                writeJsonFile('festival_leads.json', $leads);

                // Email to dedicated inbox
                $sent = sendFestivalLeadEmail($name, $email, $phone);
                if ($sent) {
                    // PRG: redirect to avoid resubmission on refresh (session flag, no query param)
                    $_SESSION['festival_success'] = true;
                    header('Location: ' . getBaseUrl('big-church-festival.php'));
                    exit;
                } else {
                    $error = 'We could not send your details right now. Please try again shortly.';
                }
            }
        }
    }
}

// Handle success view after PRG redirect (session-based)
if (!empty($_SESSION['festival_success'])) {
    $success = true;
    $name = $email = $phone = '';
    unset($_SESSION['festival_success']);
    // Rotate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($page_title); ?> - Angel Marketplace</title>
  <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { folly: '#FF0055', charcoal: '#3B4255', tangerine: '#F5884B' } } } }
  </script>
  <style>
    * { font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; }
    .aurora {
      background: radial-gradient(1200px 600px at -10% -10%, rgba(255,0,85,0.15), transparent 50%),
                  radial-gradient(1200px 600px at 110% -10%, rgba(245,136,75,0.15), transparent 50%),
                  radial-gradient(1200px 600px at 50% 120%, rgba(59,66,85,0.12), transparent 50%);
    }
    .glass { backdrop-filter: blur(12px); background: rgba(255,255,255,0.85); }
    .blob { position: fixed; border-radius: 50%; filter: blur(40px); opacity: .3; pointer-events: none; }
    .blob-1 { width: 420px; height: 420px; left: -120px; top: -120px; background: radial-gradient(circle at 30% 30%, #FF0055, transparent 60%); animation: float1 12s ease-in-out infinite alternate; }
    .blob-2 { width: 520px; height: 520px; right: -160px; bottom: -160px; background: radial-gradient(circle at 70% 70%, #F5884B, transparent 60%); animation: float2 14s ease-in-out infinite alternate; }
    @keyframes float1 { from { transform: translate(0,0) rotate(0deg);} to { transform: translate(20px, -10px) rotate(10deg);} }
    @keyframes float2 { from { transform: translate(0,0) rotate(0deg);} to { transform: translate(-20px, 10px) rotate(-10deg);} }
    
  </style>
</head>
<body class="min-h-screen aurora flex items-center justify-center p-4 md:p-8">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  
  <div class="w-full max-w-2xl mx-auto">
    <div class="p-[2px] rounded-3xl bg-gradient-to-r from-folly to-tangerine shadow-2xl">
      <div class="glass rounded-3xl border border-white/40 p-8 md:p-10">
      <div class="mb-6 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Big Church Festival</h1>
        <p class="text-gray-600 mt-2">Leave your details and we'll reach out with updates and opportunities.</p>
      </div>

      <?php /* success modal defined at end of body */ ?>
      <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" class="space-y-6" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div style="position:absolute;left:-9999px;opacity:0;pointer-events:none;">
          <label for="website">Website</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-800 mb-2">Full Name *</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A7 7 0 1118.88 7.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <input type="text" name="name" required value="<?php echo htmlspecialchars($name); ?>" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-shadow shadow-sm hover:shadow" placeholder="Full name (e.g., Mary Johnson)">
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-800 mb-2">Email Address *</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-shadow shadow-sm hover:shadow" placeholder="Email address (e.g., mary@domain.com)">
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-800 mb-2">Phone Number</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-folly" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </span>
            <input type="tel" name="phone" required value="<?php echo htmlspecialchars($phone); ?>" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-folly focus:border-folly transition-shadow shadow-sm hover:shadow" placeholder="Phone number">
          </div>
        </div>

        <div class="pt-2 space-y-3">
          <button id="festival-submit" type="submit" class="w-full md:w-auto bg-gradient-to-r from-folly to-tangerine hover:from-folly/90 hover:to-tangerine/90 text-white font-semibold px-6 py-3 rounded-xl transition-[colors,transform] shadow-lg hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2"><span class="btn-text">Submit</span><svg class="btn-spinner hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></button>
          <p class="text-xs text-gray-500">We respect your privacy. Your details are securely processed and never shared.</p>
        </div>
      </form>
      </div>
    </div>
    
  </div>

  <script>
    (function(){
      const form = document.querySelector('#festival-form') || document.querySelector('form');
      const btn = document.getElementById('festival-submit');
      if (form && btn) {
        form.addEventListener('submit', function(){
          const text = btn.querySelector('.btn-text');
          const spinner = btn.querySelector('.btn-spinner');
          if (spinner) spinner.classList.remove('hidden');
          if (text) text.textContent = 'Sending...';
          btn.disabled = true;
        });
      }
    })();
  </script>

  <?php if (!empty($success)): ?>
    <div id="success-modal" class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 md:p-8 text-center mx-auto">
        <div class="mx-auto mb-4 h-12 w-12 md:h-14 md:w-14 rounded-full bg-green-100 flex items-center justify-center">
          <svg class="h-6 w-6 md:h-8 md:w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-2">Thank you!</h3>
        <p class="text-sm md:text-base text-gray-600 mb-6">Your details have been received. We’ll be in touch shortly.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <button id="modal-close" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-charcoal text-white hover:bg-charcoal/90 transition-colors text-sm md:text-base font-medium">Close</button>
          <button id="modal-new" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-folly to-tangerine text-white hover:from-folly/90 hover:to-tangerine/90 transition-colors text-sm md:text-base font-medium">Submit another response</button>
        </div>
      </div>
    </div>
    <script>
      (function(){
        const modal = document.getElementById('success-modal');
        const closeBtn = document.getElementById('modal-close');
        const newBtn = document.getElementById('modal-new');
        function reloadFresh(){ window.location.href = window.location.pathname; }
        if (closeBtn) closeBtn.addEventListener('click', reloadFresh);
        if (newBtn) newBtn.addEventListener('click', reloadFresh);
      })();
    </script>
  <?php endif; ?>
</body>
</html>


