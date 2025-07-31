<?php
session_start();
require_once '../config/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan'
    ]);
    exit;
}

// Get and validate input
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID produk tidak valid'
    ]);
    exit;
}

if (empty($comment)) {
    echo json_encode([
        'success' => false,
        'message' => 'Komentar tidak boleh kosong'
    ]);
    exit;
}

if (strlen($comment) > 1000) {
    echo json_encode([
        'success' => false,
        'message' => 'Komentar terlalu panjang (maksimal 1000 karakter)'
    ]);
    exit;
}

try {
    // Check if product exists
    $productStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $productStmt->execute([$product_id]);
    
    if (!$productStmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Produk tidak ditemukan'
        ]);
        exit;
    }
    
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO comments (user_id, product_id, comment, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $success = $stmt->execute([
        $_SESSION['user_id'],
        $product_id,
        $comment
    ]);
    
    if ($success) {
        // Get username for response
        $userStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Komentar berhasil ditambahkan',
            'username' => htmlspecialchars($user['username'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan komentar'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Comment Process Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem'
    ]);
}
?>
