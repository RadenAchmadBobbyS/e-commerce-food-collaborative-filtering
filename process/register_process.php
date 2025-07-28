<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/register.php?message=Metode tidak diizinkan&type=error');
    exit;
}

try {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        header('Location: ../pages/register.php?message=Semua field harus diisi&type=error');
        exit;
    }

    if (strlen($username) < 3) {
        header('Location: ../pages/register.php?message=Nama harus minimal 3 karakter&type=error');
        exit;
    }

    if (strlen($password) < 6) {
        header('Location: ../pages/register.php?message=Password harus minimal 6 karakter&type=error');
        exit;
    }

    if ($password !== $confirm_password) {
        header('Location: ../pages/register.php?message=Konfirmasi password tidak cocok&type=error');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../pages/register.php?message=Format email tidak valid&type=error');
        exit;
    }

    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        header('Location: ../pages/register.php?message=Email sudah terdaftar&type=error');
        exit;
    }

    // Check if username already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$username]);
    if ($checkStmt->fetch()) {
        header('Location: ../pages/register.php?message=Nama pengguna sudah digunakan&type=error');
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);
        header('Location: ../pages/login.php?message=Pendaftaran berhasil! Silakan login.&type=success');
    } catch (PDOException $e) {
        error_log("Database error during registration: " . $e->getMessage());
        header('Location: ../pages/register.php?message=Terjadi kesalahan sistem. Silakan coba lagi.&type=error');
        exit;
    }
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    header('Location: ../pages/register.php?message=Terjadi kesalahan sistem. Silakan coba lagi.&type=error');
}
exit;
?>
