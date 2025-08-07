<?php
// Debug login script to identify authentication issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Login Test</h2>";

// Check PHP session configuration
echo "<h3>PHP Session Configuration:</h3>";
echo "<pre>";
echo "Session save path: " . session_save_path() . "\n";
echo "Session name: " . session_name() . "\n";
echo "Session module: " . session_module_name() . "\n";
echo "Session status before start: " . session_status() . "\n";
echo "</pre>";

// Start session with minimal settings first
session_start();

echo "<h3>Session Information After Start:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session status: " . session_status() . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";
echo "</pre>";

// Test password verification
$test_password = 'amp@2025';
$stored_hash = '$2y$12$Tv9s1kNlEIuRxGMOUZctlezrIEy9PTQdcjK6ZaTsa/RBC7SoSDXyS';

echo "<h3>Password Test:</h3>";
echo "<pre>";
echo "Test password: " . $test_password . "\n";
echo "Stored hash: " . $stored_hash . "\n";
echo "Verification result: " . (password_verify($test_password, $stored_hash) ? 'VALID' : 'INVALID') . "\n";
echo "</pre>";

// Check server environment
echo "<h3>Server Environment:</h3>";
echo "<pre>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "\n";
echo "SERVER_PORT: " . ($_SERVER['SERVER_PORT'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "\n";
echo "</pre>";

// Test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Form Submission Test:</h3>";
    echo "<pre>";
    echo "POST data received: " . print_r($_POST, true) . "\n";
    
    if (isset($_POST['admin_password'])) {
        $submitted_password = $_POST['admin_password'];
        echo "Password submitted: " . $submitted_password . "\n";
        echo "Password length: " . strlen($submitted_password) . "\n";
        echo "Password verification: " . (password_verify($submitted_password, $stored_hash) ? 'VALID' : 'INVALID') . "\n";
        
        if (password_verify($submitted_password, $stored_hash)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = 'Administrator';
            $_SESSION['login_time'] = time();
            echo "Session variables set successfully!\n";
            echo "Current session: " . print_r($_SESSION, true) . "\n";
            
            // Try redirect
            echo "Attempting redirect...\n";
            header('Location: debug_login.php?success=1');
            exit;
        } else {
            echo "PASSWORD VERIFICATION FAILED!\n";
        }
    }
    echo "</pre>";
}

// Check for success
if (isset($_GET['success'])) {
    echo "<h3 style='color: green;'>SUCCESS! Login worked and redirect successful!</h3>";
    echo "<pre>";
    echo "Session after redirect: " . print_r($_SESSION, true) . "\n";
    echo "</pre>";
}

// Check if already logged in
if (isset($_SESSION['admin_logged_in'])) {
    echo "<h3 style='color: green;'>Already logged in!</h3>";
    echo "<pre>";
    echo "Session data: " . print_r($_SESSION, true) . "\n";
    echo "</pre>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
        h3 { color: #333; margin-top: 30px; }
    </style>
</head>
<body>
    <h3>Test Login Form:</h3>
    <form method="POST">
        <p>
            <label>Password:</label><br>
            <input type="password" name="admin_password" value="" placeholder="Enter: amp@2025">
        </p>
        <p>
            <button type="submit">Test Login</button>
        </p>
    </form>
    
    <hr>
    <p><a href="index.php">Go to Admin Index</a></p>
    <p><a href="?clear=1">Clear Session</a></p>
    
    <?php
    // Clear session option
    if (isset($_GET['clear'])) {
        session_destroy();
        echo "<p style='color: red;'>Session cleared!</p>";
        echo "<script>setTimeout(() => location.reload(), 1000);</script>";
    }
    ?>
</body>
</html> 