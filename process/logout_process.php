<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Redirect to home page with success message
header('Location: ../pages/home.php?message=Anda telah berhasil logout');
exit;
?>
