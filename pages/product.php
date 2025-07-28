<?php 
include '../layouts/header.php'; 
require '../config/db.php';

// This page is already protected by header.php
// Display messages if any
if (isset($_GET['message'])) {
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
    echo "<div class='message {$message_type}'>" . htmlspecialchars($_GET['message']) . "</div>";
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: home.php?message=Produk tidak ditemukan&type=error');
    exit;
}

$id = intval($_GET['id']);

try {
    // Get product details with average rating
    $stmt = $pdo->prepare("SELECT p.*, COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_ratings 
                          FROM products p 
                          LEFT JOIN ratings r ON p.id = r.product_id 
                          WHERE p.id = ? 
                          GROUP BY p.id");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: home.php?message=Produk tidak ditemukan&type=error');
        exit;
    }

    // Get user's current rating if logged in
    $userRating = null;
    if (isset($_SESSION['user_id'])) {
        $userRatingStmt = $pdo->prepare("SELECT rating FROM ratings WHERE user_id = ? AND product_id = ?");
        $userRatingStmt->execute([$_SESSION['user_id'], $id]);
        $userRating = $userRatingStmt->fetch();
    }

    // Get all ratings for this product
    $ratingsStmt = $pdo->prepare("SELECT r.rating, u.username as user_name 
                                 FROM ratings r 
                                 JOIN users u ON r.user_id = u.id 
                                 WHERE r.product_id = ? 
                                 ORDER BY r.rating DESC");
    $ratingsStmt->execute([$id]);
    $allRatings = $ratingsStmt->fetchAll();

} catch (Exception $e) {
    echo "<div class='message error'>Terjadi kesalahan: " . htmlspecialchars($e->getMessage()) . "</div>";
    include '../layouts/footer.php';
    exit;
}

$rating_display = $product['avg_rating'] > 0 ? number_format($product['avg_rating'], 1) : 'Belum ada rating';
$stars = '';
for ($i = 1; $i <= 5; $i++) {
    $stars .= ($i <= round($product['avg_rating'])) ? '⭐' : '☆';
}
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-bottom: 2rem;" class="product-detail">
        <div>
            <?php if (!empty($product['image']) && file_exists("../assets/img/{$product['image']}")): ?>
                <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     style="width: 100%; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <?php else: ?>
                <div style="width: 100%; height: 300px; background: #f0f0f0; border-radius: 15px; display: flex; align-items: center; justify-content: center; color: #666;">
                    Tidak ada gambar
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <h1 style="color: #2c3e50; margin-bottom: 1rem;"><?= htmlspecialchars($product['name']) ?></h1>
            <p style="color: #7f8c8d; line-height: 1.6; margin-bottom: 1.5rem; font-size: 1.1rem;">
                <?= htmlspecialchars($product['description']) ?>
            </p>
            
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem;">
                <h3 style="margin-bottom: 1rem; color: #2c3e50;">📊 Rating & Ulasan</h3>
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div style="font-size: 1.5rem;"><?= $stars ?></div>
                    <div>
                        <strong style="font-size: 1.2rem; color: #2c3e50;"><?= $rating_display ?>/5.0</strong>
                        <br>
                        <small style="color: #7f8c8d;"><?= $product['total_ratings'] ?> total rating</small>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div style="background: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1rem; color: #2c3e50;">
                        <?= $userRating ? '✏️ Ubah Rating Anda' : '⭐ Berikan Rating' ?>
                    </h3>
                    
                    <?php if ($userRating): ?>
                        <div style="background: #e8f5e8; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; color: #27ae60;">
                            ✅ Anda sudah memberikan rating: <strong><?= $userRating['rating'] ?>/5</strong>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="../process/rating_process.php" id="ratingForm">
                        <input type="hidden" name="product_id" value="<?= $id ?>">
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2c3e50;">
                                Pilih Rating:
                            </label>
                            <div class="rating-select" style="display: flex; gap: 0.5rem;">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <label style="cursor: pointer; padding: 0.5rem; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;">
                                        <input type="radio" name="rating" value="<?= $i ?>" 
                                               <?= ($userRating && $userRating['rating'] == $i) ? 'checked' : '' ?>
                                               style="display: none;">
                                        <span style="font-size: 1.2rem;">⭐</span>
                                        <span><?= $i ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn">
                            <?= $userRating ? '🔄 Update Rating' : '🚀 Kirim Rating' ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="message info">
                    <p>🔐 <a href="login.php" style="color: #3498db;">Login</a> untuk memberikan rating pada makanan ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- All Ratings Section -->
    <?php if (!empty($allRatings)): ?>
        <div style="background: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 1.5rem; color: #2c3e50;">💬 Semua Rating (<?= count($allRatings) ?>)</h3>
            
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($allRatings as $rating): ?>
                    <div style="border-bottom: 1px solid #eee; padding: 1rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: #2c3e50;">
                                <?= htmlspecialchars($rating['user_name']) ?>
                            </strong>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo ($i <= $rating['rating']) ? '⭐' : '☆';
                            }
                            ?>
                            <span style="color: #7f8c8d;">(<?= $rating['rating'] ?>/5)</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.rating-select input[type="radio"]:checked + span + span,
.rating-select input[type="radio"]:checked + span {
    color: #f39c12;
}

.rating-select label:hover {
    border-color: #3498db;
    background-color: #f8f9fa;
}

.rating-select input[type="radio"]:checked ~ label,
.rating-select label:has(input[type="radio"]:checked) {
    border-color: #f39c12;
    background-color: #fff8e1;
}

@media (max-width: 768px) {
    .product-detail {
        grid-template-columns: 1fr !important;
    }
    
    .rating-select {
        flex-direction: column !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingLabels = document.querySelectorAll('.rating-select label');
    
    ratingLabels.forEach(label => {
        label.addEventListener('click', function() {
            // Remove active class from all labels
            ratingLabels.forEach(l => l.classList.remove('active'));
            // Add active class to clicked label
            this.classList.add('active');
        });
    });
});
</script>

<?php include '../layouts/footer.php'; ?>
