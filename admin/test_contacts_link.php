<?php
session_start();

// Simple page to test the contacts link
require_once 'includes/admin_functions.php';

echo "<h2>Testing Contacts Link</h2>";
echo "<p>Current session status: " . (isset($_SESSION['admin_logged_in']) ? 'LOGGED IN' : 'NOT LOGGED IN') . "</p>";

$contactsUrl = getAdminUrl('contacts.php');
echo "<p>Generated contacts URL: <strong>$contactsUrl</strong></p>";

echo "<p><a href='$contactsUrl' style='padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 5px;'>Click to Test Contacts Page</a></p>";

echo "<hr>";
echo "<h3>Alternative Links to Test:</h3>";
echo "<p><a href='contacts.php'>Direct link: contacts.php</a></p>";
echo "<p><a href='./contacts.php'>Direct link with ./: ./contacts.php</a></p>";
echo "<p><a href='/ampmkp/admin/contacts.php'>Absolute link: /ampmkp/admin/contacts.php</a></p>";
?>