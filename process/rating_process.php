<?php
session_start();
require '../config/db.php';

// Set content type for faster response
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/home.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Quick validation - redirect immediately if invalid
    if ($product_id <= 0 || $rating <= 0 || $rating > 5) {
        header("Location: ../pages/home.php");
        exit;
    }

    // Validate comment length if provided (quick check)
    if (!empty($comment) && strlen($comment) > 1000) {
        header("Location: ../pages/product.php?id=$product_id&message=" . urlencode("Ulasan terlalu panjang (maksimal 1000 karakter)") . "&type=error");
        exit;
    }

    // Use prepared statement with optimized query
    // Check if user already rated this product in one query
    $checkStmt = $pdo->prepare("SELECT id FROM ratings WHERE user_id = ? AND product_id = ? LIMIT 1");
    $checkStmt->execute([$user_id, $product_id]);
    $existingRating = $checkStmt->fetch();

    if ($existingRating) {
        // Update existing rating and comment - optimized query
        $stmt = $pdo->prepare("UPDATE ratings SET rating = ?, comment = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$rating, $comment, $user_id, $product_id]);
        $message = "Rating dan ulasan berhasil diperbarui!";
    } else {
        // Insert new rating with comment - simplified query
        $stmt = $pdo->prepare("INSERT INTO ratings (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $rating, $comment]);
        $message = "Rating dan ulasan berhasil disimpan!";
    }

    // Immediate redirect for faster response
    header("Location: ../pages/product.php?id=$product_id&message=" . urlencode($message) . "&type=success");
    exit;

} catch (Exception $e) {
    // Simplified error handling
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if ($product_id > 0) {
        header("Location: ../pages/product.php?id=$product_id&message=" . urlencode("Terjadi kesalahan, silakan coba lagi") . "&type=error");
    } else {
        header("Location: ../pages/home.php");
    }
    exit;
}
?>
