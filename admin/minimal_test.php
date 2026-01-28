<?php
// Minimal test without any includes
session_start();

echo "<!DOCTYPE html><html><head><title>Minimal Test</title></head><body>";
echo "<h1>Minimal Admin Test</h1>";
echo "<p>If you can see this, the admin directory works fine.</p>";
echo "<p>Session status: " . (isset($_SESSION['admin_logged_in']) ? 'LOGGED IN' : 'NOT LOGGED IN') . "</p>";
echo "</body></html>";
?>