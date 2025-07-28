<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/login.php?message=Metode tidak diizinkan&type=error');
    exit;
}

try {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header('Location: ../admin/login.php?message=Email dan password harus diisi&type=error');
        exit;
    }

    // Query untuk mencari admin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_login_time'] = time();
        
        // Update last login time
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$admin['id']]);
        
        header('Location: ../admin/dashboard.php?message=Selamat datang Admin ' . urlencode($admin['username']) . '!&type=success');
        exit;
    } else {
        // Log failed login attempt
        error_log("Failed admin login attempt for email: " . $email);
        header('Location: ../admin/login.php?message=Email atau password salah, atau Anda bukan admin&type=error');
        exit;
    }
} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    header('Location: ../admin/login.php?message=Terjadi kesalahan sistem. Silakan coba lagi.&type=error');
    exit;
}
?>
