<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php?message=Metode tidak diizinkan&type=error');
    exit;
}

try {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header('Location: ../pages/login.php?message=Email dan password harus diisi&type=error');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['login_time'] = time();
        
        // Update last login time
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        header('Location: ../pages/home.php?message=Selamat datang, ' . urlencode($user['username']) . '!&type=success');
        exit;
    } else {
        // Log failed login attempt
        error_log("Failed login attempt for email: " . $email);
        header('Location: ../pages/login.php?message=Email atau password salah&type=error');
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    header('Location: ../pages/login.php?message=Terjadi kesalahan sistem. Silakan coba lagi.&type=error');
}

exit;
?>
