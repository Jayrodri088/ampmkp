<?php
// Debug session and authentication
session_start();

echo "<h2>Session Debug Information</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NOT ACTIVE') . "</p>";
echo "<p><strong>Admin Logged In:</strong> " . (isset($_SESSION['admin_logged_in']) ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Admin User:</strong> " . ($_SESSION['admin_user'] ?? 'NOT SET') . "</p>";
echo "<p><strong>Login Time:</strong> " . (isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'NOT SET') . "</p>";

echo "<h2>Server Information</h2>";
echo "<p><strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "</p>";
echo "<p><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "</p>";
echo "<p><strong>Script Name:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "</p>";

echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Clear Session (for testing)</h2>";
echo "<a href='?clear=1'>Clear Session</a>";

if (isset($_GET['clear'])) {
    session_destroy();
    echo "<p style='color: green;'>Session cleared! <a href='debug_session.php'>Refresh</a></p>";
}
?>