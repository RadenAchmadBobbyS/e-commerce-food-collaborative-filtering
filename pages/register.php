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

<h2 class="page-title">📝 Daftar Akun Baru</h2>

<div class="form-container">
    <form action="../process/register_process.php" method="POST">
        <div class="form-group">
            <label for="username">👤 Nama Lengkap:</label>
            <input type="text" id="username" name="username" placeholder="Masukkan nama lengkap Anda" required minlength="3">
        </div>
        
        <div class="form-group">
            <label for="email">📧 Email:</label>
            <input type="email" id="email" name="email" placeholder="Masukkan email Anda" required>
        </div>
        
        <div class="form-group">
            <label for="password">🔒 Password:</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password (min. 6 karakter)" required minlength="6">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">🔒 Konfirmasi Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Masukkan ulang password Anda" required>
        </div>
        
        <button type="submit" class="btn">🎯 Daftar Sekarang</button>
    </form>
    
    <div style="text-align: center; margin-top: 1.5rem;">
        <p style="color: #7f8c8d;">Sudah punya akun? 
            <a href="login.php" style="color: #3498db; text-decoration: none; font-weight: 500;">Login disini</a>
        </p>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Password tidak cocok');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include '../layouts/footer.php'; ?>
