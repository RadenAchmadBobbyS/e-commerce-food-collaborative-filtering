<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/home.php?message=Metode tidak diizinkan&type=error');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php?message=Silakan login terlebih dahulu&type=error');
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);

    // Validation
    if (empty($product_id) || empty($rating)) {
        header("Location: ../pages/product.php?id=$product_id&message=Data tidak lengkap&type=error");
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        header("Location: ../pages/product.php?id=$product_id&message=Rating harus antara 1-5&type=error");
        exit;
    }

    if (!is_numeric($product_id) || !is_numeric($rating)) {
        error_log("Invalid data: product_id=$product_id, rating=$rating");
        header("Location: ../pages/product.php?id=$product_id&message=Data tidak valid&type=error");
        exit;
    }

    // Check if product exists
    $productStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $productStmt->execute([$product_id]);
    if (!$productStmt->fetch()) {
        error_log("Product not found: product_id=$product_id");
        header('Location: ../pages/home.php?message=Produk tidak ditemukan&type=error');
        exit;
    }

    // Check if user exists
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    if (!$userStmt->fetch()) {
        error_log("User not found: user_id=$user_id");
        header('Location: ../pages/home.php?message=Pengguna tidak ditemukan&type=error');
        exit;
    }

    // Check if user already rated this product
    $existingStmt = $pdo->prepare("SELECT id FROM ratings WHERE user_id = ? AND product_id = ?");
    $existingStmt->execute([$user_id, $product_id]);
    $existingRating = $existingStmt->fetch();

    // Debugging tambahan
    error_log("Debugging Rating: user_id=$user_id, product_id=$product_id, rating=$rating");

    if ($existingRating) {
        // Update existing rating
        $stmt = $pdo->prepare("UPDATE ratings SET rating = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$rating, $user_id, $product_id]);
        error_log("Rating updated: user_id=$user_id, product_id=$product_id, rating=$rating");
        $message = "Rating Anda berhasil diperbarui!";
    } else {
        // Insert new rating
        $stmt = $pdo->prepare("INSERT INTO ratings (user_id, product_id, rating) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $rating]);
        error_log("Rating inserted: user_id=$user_id, product_id=$product_id, rating=$rating");
        $message = "Terima kasih! Rating Anda berhasil disimpan.";
    }

    header("Location: ../pages/product.php?id=$product_id&message=" . urlencode($message) . "&type=success");

} catch (Exception $e) {
    error_log("Rating error: " . $e->getMessage());
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    // Menampilkan error langsung di halaman untuk debugging sementara
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
?>
