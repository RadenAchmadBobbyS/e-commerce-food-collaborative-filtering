<?php
// Script untuk setup admin
require '../config/db.php';

try {
    // 1. Periksa dan buat kolom role jika belum ada
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'role'");
    $checkStmt->execute();
    $roleColumn = $checkStmt->fetch();
    
    if (!$roleColumn) {
        echo "Menambahkan kolom role...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user'");
        echo "Kolom role berhasil ditambahkan.\n";
    } else {
        echo "Kolom role sudah ada.\n";
    }
    
    // 2. Set semua user existing menjadi 'user'
    $pdo->exec("UPDATE users SET role = 'user' WHERE role IS NULL");
    echo "Role user existing berhasil di-set.\n";
    
    // 3. Periksa apakah admin sudah ada
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@foodrec.com'");
    $stmt->execute();
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        // Update admin yang ada
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin', username = 'admin' WHERE email = 'admin@foodrec.com'");
        $updateStmt->execute([$hashedPassword]);
        echo "Admin existing berhasil diupdate.\n";
        echo "Admin ID: " . $existingAdmin['id'] . "\n";
    } else {
        // Buat admin baru
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $insertStmt->execute(['admin', 'admin@foodrec.com', $hashedPassword, 'admin']);
        echo "Admin baru berhasil dibuat.\n";
        echo "Admin ID: " . $pdo->lastInsertId() . "\n";
    }
    
    // 4. Verifikasi admin
    $verifyStmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@foodrec.com' AND role = 'admin'");
    $verifyStmt->execute();
    $admin = $verifyStmt->fetch();
    
    if ($admin) {
        echo "\n=== VERIFIKASI ADMIN ===\n";
        echo "ID: " . $admin['id'] . "\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Role: " . $admin['role'] . "\n";
        echo "Password (hashed): " . substr($admin['password'], 0, 30) . "...\n";
        
        // Test password
        if (password_verify('password', $admin['password'])) {
            echo "✅ Password verification: BERHASIL\n";
        } else {
            echo "❌ Password verification: GAGAL\n";
        }
        
        echo "\n=== LOGIN CREDENTIALS ===\n";
        echo "Email: admin@foodrec.com\n";
        echo "Password: password\n";
        echo "\nSetup admin selesai!\n";
    } else {
        echo "❌ Gagal membuat atau menemukan admin!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
