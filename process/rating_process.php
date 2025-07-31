<?php
session_start();
require '../config/db.php';

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
    
    // Basic validation - redirect tanpa pesan error
    if ($product_id <= 0) {
        header("Location: ../pages/home.php");
        exit;
    }
    
    if ($rating <= 0) {
        header("Location: ../pages/product.php?id=$product_id");
        exit;
    }

    // Check if product exists - redirect tanpa pesan error
    $productStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $productStmt->execute([$product_id]);
    if (!$productStmt->fetch()) {
        header("Location: ../pages/home.php");
        exit;
    }

    // Check if user already rated this product
    $existingStmt = $pdo->prepare("SELECT id FROM ratings WHERE user_id = ? AND product_id = ?");
    $existingStmt->execute([$user_id, $product_id]);
    $existingRating = $existingStmt->fetch();

    if ($existingRating) {
        // Update existing rating
        $stmt = $pdo->prepare("UPDATE ratings SET rating = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$rating, $user_id, $product_id]);
        $message = "Rating Anda berhasil diperbarui!";
    } else {
        // Insert new rating
        $stmt = $pdo->prepare("INSERT INTO ratings (user_id, product_id, rating) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $rating]);
        $message = "Terima kasih! Rating Anda berhasil disimpan.";
    }

    header("Location: ../pages/product.php?id=$product_id&message=" . urlencode($message) . "&type=success");

} catch (Exception $e) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    header("Location: ../pages/product.php?id=$product_id");
    exit;
}
?>
