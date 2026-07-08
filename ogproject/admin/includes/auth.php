<?php
session_start();

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['created'])) {
    session_regenerate_id(true);
    $_SESSION['created'] = true;
}

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Store current URL for redirect back after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Validate session fingerprint
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (!isset($_SESSION['fingerprint'])) {
    $_SESSION['fingerprint'] = md5($userAgent . $ip);
} elseif ($_SESSION['fingerprint'] !== md5($userAgent . $ip)) {
    // Possible session hijacking
    session_unset();
    session_destroy();
    header('Location: login.php?error=session');
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>