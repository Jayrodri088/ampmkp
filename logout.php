<?php
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['customer_id']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_last_activity']);

header('Location: ' . getBaseUrl());
exit;
