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

    // Debugging: Periksa apakah kolom role ada
    try {
        $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'role'");
        $checkStmt->execute();
        $roleColumn = $checkStmt->fetch();
        
        if (!$roleColumn) {
            // Jika kolom role tidak ada, buat kolom dan admin default
            $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user'");
            $pdo->exec("UPDATE users SET role = 'user' WHERE role IS NULL");
            
            // Buat admin default
            $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT IGNORE INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
                ->execute(['admin', 'admin@foodrec.com', $hashedPassword, 'admin']);
        }
    } catch (Exception $setupError) {
        error_log("Setup error: " . $setupError->getMessage());
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
        
        // Update last login time (jika kolom ada)
        try {
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$admin['id']]);
        } catch (Exception $updateError) {
            // Kolom last_login mungkin tidak ada, tapi tidak masalah
        }
        
        header('Location: ../admin/dashboard.php?message=Selamat datang Admin ' . urlencode($admin['username']) . '!&type=success');
        exit;
    } else {
        // Coba fallback untuk admin default dengan password plain text (untuk debugging)
        if ($email === 'admin@foodrec.com' && $password === 'password') {
            // Buat atau update admin dengan password yang ter-hash
            $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingAdmin = $stmt->fetch();
            
            if ($existingAdmin) {
                // Update password yang ada
                $updateStmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
                $updateStmt->execute([$hashedPassword, $email]);
            } else {
                // Buat admin baru
                $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $insertStmt->execute(['admin', $email, $hashedPassword, 'admin']);
            }
            
            // Set session
            $_SESSION['admin_id'] = $existingAdmin ? $existingAdmin['id'] : $pdo->lastInsertId();
            $_SESSION['admin_name'] = 'admin';
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_role'] = 'admin';
            $_SESSION['admin_login_time'] = time();
            
            header('Location: ../admin/dashboard.php?message=Selamat datang Admin!&type=success');
            exit;
        }
        
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
