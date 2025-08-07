<?php
// Simple session test
session_start();

echo "<h2>Session Test</h2>";

if (isset($_POST['test'])) {
    $_SESSION['test_value'] = 'Session is working!';
    header('Location: test_session.php?result=1');
    exit;
}

if (isset($_GET['result'])) {
    if (isset($_SESSION['test_value'])) {
        echo "<p style='color: green; font-weight: bold;'>✅ SESSION WORKING: " . $_SESSION['test_value'] . "</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ SESSION NOT WORKING</p>";
    }
}

echo "<form method='POST'>";
echo "<button type='submit' name='test' value='1'>Test Session</button>";
echo "</form>";

echo "<hr>";
echo "<h3>Session Info:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";
echo "</pre>";

echo "<p><a href='?clear=1'>Clear Session</a></p>";

if (isset($_GET['clear'])) {
    session_destroy();
    echo "<p>Session cleared!</p>";
    echo "<script>setTimeout(() => location.href='test_session.php', 1000);</script>";
}
?> 