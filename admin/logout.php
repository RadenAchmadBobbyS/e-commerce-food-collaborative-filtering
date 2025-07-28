<?php
session_start();

// Clear admin session data
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);
unset($_SESSION['admin_login_time']);

// Redirect to admin login
header('Location: login.php?message=Anda telah berhasil logout dari admin panel&type=success');
exit;
?>
