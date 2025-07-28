<?php 
include '../layouts/header.php'; 
require '../config/db.php'; 
require '../functions/cf.php';

// This page is already protected by header.php
// Display messages if any
if (isset($_GET['message'])) {
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
    echo "<div class='message {$message_type}'>" . htmlspecialchars($_GET['message']) . "</div>";
}

try {
    $rekomendasi = getRecommendations($pdo, $_SESSION['user_id']);
    
    // Menampilkan dropdown kategori
    $stmtCategories = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmtCategories->fetchAll();
    ?>
    <form method="GET" class="category-filter">
        <label for="category">Filter berdasarkan kategori:</label>
        <select name="category" id="category" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>" <?= isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php
    
    // Memodifikasi query rekomendasi berdasarkan kategori
    $categoryFilter = isset($_GET['category']) && is_numeric($_GET['category']) ? intval($_GET['category']) : null;
    if ($categoryFilter) {
        $rekomendasi = array_filter($rekomendasi, function($pid) use ($pdo, $categoryFilter) {
            $stmt = $pdo->prepare("SELECT category_id FROM products WHERE id = ?");
            $stmt->execute([$pid]);
            $product = $stmt->fetch();
            return $product && $product['category_id'] == $categoryFilter;
        });
    }
    
    // Menambahkan Bootstrap untuk memperbaiki tampilan
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <div class="container mt-5">
        <h2 class="text-center mb-4">⭐ Rekomendasi Makanan untuk Anda</h2>

        <?php if (empty($rekomendasi)): ?>
            <div class="alert alert-info text-center">
                <h4>🤔 Belum Cukup Data</h4>
                <p>Kami belum memiliki cukup data untuk memberikan rekomendasi yang akurat untuk Anda.</p>
                <p><strong>💡 Tips:</strong> Berikan rating pada beberapa makanan terlebih dahulu agar kami dapat memahami preferensi Anda!</p>
                <a href="home.php" class="btn btn-primary mt-3">🍽️ Jelajahi Makanan</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($rekomendasi as $index => $pid): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT p.*, COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_ratings 
                                         FROM products p 
                                         LEFT JOIN ratings r ON p.id = r.product_id 
                                         WHERE p.id = ? 
                                         GROUP BY p.id");
                    $stmt->execute([$pid]);
                    $product = $stmt->fetch();
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($product['image']) && file_exists("../assets/img/{$product['image']}")): ?>
                                <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php else: ?>
                                <img src="../assets/img/no-image.jpg" class="card-img-top" alt="No Image">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text">Harga: Rp<?= number_format($product['price'], 0, ',', '.') ?></p>
                                <p class="card-text">Rating: 
                                    <?php 
                                    $stars = '';
                                    for ($i = 1; $i <= 5; $i++) {
                                        $stars .= ($i <= round($product['avg_rating'])) ? '⭐' : '☆';
                                    }
                                    echo $stars;
                                    ?>
                                    (<?= $product['total_ratings'] > 0 ? $product['total_ratings'] : 0 ?> total rating)
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php
} catch (Exception $e) {
    echo "<div class='message error'>Terjadi kesalahan: " . htmlspecialchars($e->getMessage()) . "</div>";
}

include '../layouts/footer.php';
?>
