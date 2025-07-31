<?php 
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

include '../layouts/header.php';

// Display messages if any
if (isset($_GET['message'])) {
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
    echo "<div class='message {$message_type}'>" . htmlspecialchars($_GET['message']) . "</div>";
}
?>

<h2 class="page-title">🔐 Login ke Akun Anda</h2>

<div class="form-container">
    <form action="../process/login_process.php" method="POST">
        <div class="form-group">
            <label for="email">📧 Email:</label>
            <input type="email" id="email" name="email" placeholder="Masukkan email Anda" required>
        </div>
        
        <div class="form-group">
            <label for="password">🔒 Password:</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
        </div>
        
        <button type="submit" class="btn">🚀 Login</button>
    </form>
    
    <div style="text-align: center; margin-top: 1.5rem;">
        <p style="color: #7f8c8d;">Belum punya akun? 
            <a href="register.php" style="color: #3498db; text-decoration: none; font-weight: 500;">Daftar disini</a>
        </p>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
