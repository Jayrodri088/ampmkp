<?php
// Align session + HTTPS handling with other admin pages
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

require_once __DIR__ . '/includes/admin_functions.php';

// Set cookie Secure flag dynamically; do NOT force redirect so we can diagnose HTTP vs HTTPS
ini_set('session.cookie_secure', isRequestHttps() ? 1 : 0);

session_start();

// Note: Do NOT require authentication here; we want to diagnose sessions even when cookies are missing



echo "<h2>Testing Admin URL Generation</h2>";
echo "<style>body { font-family: Arial, sans-serif; } pre { background: #f5f5f5; padding: 15px; border: 1px solid #ddd; }</style>";
echo "<pre>";

echo "Current SERVER values:\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n"; 
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "dirname(__DIR__): " . dirname(__DIR__) . "\n";
echo "realpath(__DIR__ . '/..') : " . realpath(__DIR__ . '/..') . "\n";

echo "\nAdmin URL Generation:\n";
try {
    echo "getAdminBasePath(): " . getAdminBasePath() . "\n";
    echo "getAdminUrl(): " . getAdminUrl() . "\n";
    echo "getAdminUrl('index.php'): " . getAdminUrl('index.php') . "\n";
    echo "getAdminUrl('products.php'): " . getAdminUrl('products.php') . "\n";
    echo "getMainSiteUrl(): " . getMainSiteUrl() . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h3>Direct Navigation Test Links:</h3>";
echo "<ul>";
echo "<li><a href='" . getAdminUrl('index.php') . "'>Dashboard</a></li>";
echo "<li><a href='" . getAdminUrl('products.php') . "'>Products</a></li>";
echo "<li><a href='" . getAdminUrl('categories.php') . "'>Categories</a></li>";
echo "<li><a href='" . getAdminUrl('orders.php') . "'>Orders</a></li>";
echo "<li><a href='" . getAdminUrl('settings.php') . "'>Settings</a></li>";
echo "</ul>";

echo "<h3>Actual Navigation HTML:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9; max-height: 300px; overflow: auto;'>";
ob_start();
$activePage = 'dashboard';
include __DIR__ . '/partials/nav_links_desktop.php';
$nav_html = ob_get_clean();
echo htmlspecialchars($nav_html);
echo "</div>";

echo "<h3>Rendered Navigation (actual HTML):</h3>";
echo "<div style='border: 1px solid #333; padding: 10px; background: #2a2a2a; color: white; max-height: 300px; overflow: auto;'>";
$activePage = 'dashboard';
include __DIR__ . '/partials/nav_links_desktop.php';
echo "</div>";

echo "<hr>";
echo "<p><strong>Instructions:</strong> Click on the navigation links above to test if they work properly. Check browser console for any errors.</p>";
?>
<?php
// Extended Diagnostics
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/admin_functions.php';

$httpsDetected = isRequestHttps() ? 'YES' : 'NO';
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$server = [
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '(not set)',
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? '(not set)',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '(not set)',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? '(not set)',
    'PHP_SELF' => $_SERVER['PHP_SELF'] ?? '(not set)',
    'HTTPS' => $_SERVER['HTTPS'] ?? '(not set)',
    'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? '(not set)',
    'HTTP_X_FORWARDED_PROTO' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '(not set)',
    'HTTP_X_FORWARDED_SSL' => $_SERVER['HTTP_X_FORWARDED_SSL'] ?? '(not set)',
    'HTTP_X_FORWARDED_HOST' => $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '(not set)',
    'HTTP_X_REAL_IP' => $_SERVER['HTTP_X_REAL_IP'] ?? '(not set)'
];

$cookieParams = session_get_cookie_params();
$sessionInfo = [
    'session_id' => session_id(),
    'name' => session_name(),
    'status' => session_status(),
    'save_path' => session_save_path(),
    'has_cookie' => isset($_COOKIE[session_name()])
];

$adminUrls = [
    'base' => getAdminBasePath(),
    'relative_index' => getAdminUrl('index.php'),
    'relative_auth' => getAdminUrl('auth.php'),
    'absolute_index' => getAdminAbsoluteUrl('index.php'),
    'absolute_auth' => getAdminAbsoluteUrl('auth.php'),
    'main_site' => getMainSiteUrl(),
];

// Write tests
$write = [];
$tmpFile = sys_get_temp_dir() . '/ampmkp_diag_' . uniqid() . '.tmp';
$write['sys_temp_dir'] = @file_put_contents($tmpFile, 'ok') !== false && file_exists($tmpFile);
@unlink($tmpFile);
$dataDir = realpath(__DIR__ . '/../data') ?: '(not found)';
if ($dataDir !== '(not found)') {
    $dataTestFile = $dataDir . '/diag_write_' . uniqid() . '.tmp';
    $write['data_dir'] = @file_put_contents($dataTestFile, 'ok') !== false && file_exists($dataTestFile);
    @unlink($dataTestFile);
} else {
    $write['data_dir'] = false;
}

// Potential issues
$issues = [];
if (!isRequestHttps()) $issues[] = 'Not using HTTPS; Secure cookies will not persist over HTTP.';
if (empty($_COOKIE[session_name()] ?? '')) $issues[] = 'Session cookie missing in request (domain/path/scheme or browser blocking).';
if (!is_writable(session_save_path())) $issues[] = 'Session save path not writable: ' . session_save_path();
if (!$write['sys_temp_dir']) $issues[] = 'Cannot write to system temp dir: ' . sys_get_temp_dir();
if (!$write['data_dir']) $issues[] = 'Cannot write to data dir: ' . $dataDir;
if (!$isLoggedIn) $issues[] = 'Not authenticated on this request; if other admin pages show logged-in, cookie scope/scheme mismatch likely.';
?>
<hr/>
<h2>Diagnostics</h2>
<p>Status: <?= $isLoggedIn ? '<strong style="color:#0a7f3f">Logged In</strong>' : '<strong style="color:#b00020">Not Logged In</strong>' ?> | HTTPS Detected: <strong><?= htmlspecialchars($httpsDetected) ?></strong></p>

<?php if (!empty($issues)): ?>
    <h3>Potential Issues</h3>
    <ul>
        <?php foreach ($issues as $i): ?>
            <li><?= htmlspecialchars($i) ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No obvious issues detected.</p>
<?php endif; ?>

<h3>Server Vars</h3>
<pre><?php print_r($server); ?></pre>

<h3>Session</h3>
<pre><?php print_r($sessionInfo); ?></pre>

<h3>Session Cookie Params</h3>
<pre><?php print_r($cookieParams); ?></pre>

<h3>Cookies</h3>
<pre><?php print_r($_COOKIE); ?></pre>

<h3>Admin URLs</h3>
<pre><?php print_r($adminUrls); ?></pre>

<h3>Write Tests</h3>
<pre><?php print_r($write); ?></pre>