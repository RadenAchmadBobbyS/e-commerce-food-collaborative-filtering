<?php
/**
 * Authentication check helper
 * Include this file to protect pages that require login
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin($redirect_to = 'login.php') {
    if (!isset($_SESSION['user_id'])) {
        $current_page = basename($_SERVER['PHP_SELF']);
        header("Location: $redirect_to?message=Silakan login untuk mengakses halaman ini&type=info&redirect=" . urlencode($current_page));
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

function redirectIfLoggedIn($redirect_to = 'home.php') {
    if (isLoggedIn()) {
        header("Location: $redirect_to");
        exit;
    }
}
?>
