<?php
// Test file to check for redirects
echo "Admin directory is working properly.<br>";
echo "Current URL: " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// Check if there are any redirects configured
if (function_exists('apache_get_modules')) {
    echo "Apache Modules: " . implode(', ', apache_get_modules()) . "<br>";
}

// Check for any .htaccess redirects
echo "<h2>Testing Admin Functions</h2>";
require_once 'includes/admin_functions.php';

echo "getAdminUrl('contacts.php'): " . getAdminUrl('contacts.php') . "<br>";
echo "getAdminAbsoluteUrl('contacts.php'): " . getAdminAbsoluteUrl('contacts.php') . "<br>";
?>