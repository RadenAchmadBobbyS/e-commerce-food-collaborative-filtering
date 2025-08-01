<?php
session_start();
require '../config/db.php';

// Redirect ke login jika belum login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?message=' . urlencode('Silakan login untuk memberikan rating') . '&redirect=rating.php');
    exit;
}

// Get all products untuk rating
try {
    $stmt = $pdo->prepare("SELECT p.*, COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_ratings,
                          ur.rating as user_rating, ur.comment as user_comment
                          FROM products p 
                          LEFT JOIN ratings r ON p.id = r.product_id 
                          LEFT JOIN ratings ur ON p.id = ur.product_id AND ur.user_id = ?
                          GROUP BY p.id 
                          ORDER BY p.name");
    $stmt->execute([$_SESSION['user_id']]);
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

include '../layouts/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; color: #2c3e50; margin-bottom: 2rem;">⭐ Halaman Rating & Ulasan</h1>
    
    <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
        <h3 style="color: #27ae60; margin-bottom: 1rem;">👋 Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!</h3>
        <p style="color: #27ae60;">Berikan rating dan ulasan untuk makanan yang sudah Anda coba</p>
    </div>

    <!-- Products Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <?php foreach ($products as $product): ?>
            <?php 
            $stars = '';
            for ($i = 1; $i <= 5; $i++) {
                $stars .= ($i <= round($product['avg_rating'])) ? '⭐' : '☆';
            }
            
            $userStars = '';
            if ($product['user_rating']) {
                for ($i = 1; $i <= 5; $i++) {
                    $userStars .= ($i <= $product['user_rating']) ? '⭐' : '☆';
                }
            }
            ?>
            
            <div style="background: #fff; border-radius: 15px; padding: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s;" 
                 onmouseover="this.style.transform='translateY(-5px)'" 
                 onmouseout="this.style.transform='translateY(0)'">
                
                <!-- Product Image -->
                <div style="text-align: center; margin-bottom: 1rem;">
                    <?php if (!empty($product['image']) && file_exists("../assets/img/{$product['image']}")): ?>
                        <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
                    <?php else: ?>
                        <div style="width: 100%; height: 200px; background: #f0f0f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #666;">
                            🍽️ No Image
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <h3 style="color: #2c3e50; margin-bottom: 0.5rem; text-align: center;">
                    <?= htmlspecialchars($product['name']) ?>
                </h3>
                
                <!-- Average Rating -->
                <div style="text-align: center; margin-bottom: 1rem; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 1.2rem; margin-bottom: 5px;"><?= $stars ?></div>
                    <small style="color: #7f8c8d;">
                        Rata-rata: <?= $product['avg_rating'] > 0 ? number_format($product['avg_rating'], 1) : '0' ?>/5.0
                        (<?= $product['total_ratings'] ?> rating)
                    </small>
                </div>

                <!-- User's Rating Status -->
                <?php if ($product['user_rating']): ?>
                    <div style="background: #e8f5e8; padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                        <div style="font-weight: 500; color: #27ae60; margin-bottom: 5px;">
                            ✅ Rating Anda: <?= $userStars ?> (<?= $product['user_rating'] ?>/5)
                        </div>
                        <?php if (!empty($product['user_comment'])): ?>
                            <small style="color: #27ae60;">
                                "<?= htmlspecialchars($product['user_comment']) ?>"
                            </small>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="background: #fff3cd; padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                        <div style="color: #856404;">⏳ Belum ada rating</div>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div style="text-align: center;">
                    <a href="product.php?id=<?= $product['id'] ?>" 
                       style="background: #3498db; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; margin: 5px;">
                        <?= $product['user_rating'] ? '✏️ Edit Rating' : '⭐ Beri Rating' ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Stats -->
    <div style="background: #f8f9fa; padding: 2rem; border-radius: 15px; margin-top: 2rem; text-align: center;">
        <h3 style="color: #2c3e50; margin-bottom: 1rem;">📊 Statistik Rating Anda</h3>
        <?php 
        $userRatingsCount = 0;
        $totalProducts = count($products);
        foreach ($products as $product) {
            if ($product['user_rating']) $userRatingsCount++;
        }
        ?>
        <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
            <div>
                <div style="font-size: 2rem; font-weight: bold; color: #3498db;"><?= $userRatingsCount ?></div>
                <div style="color: #7f8c8d;">Rating Diberikan</div>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: bold; color: #e74c3c;"><?= $totalProducts - $userRatingsCount ?></div>
                <div style="color: #7f8c8d;">Belum Di-rating</div>
            </div>
            <div>
                <div style="font-size: 2rem; font-weight: bold; color: #27ae60;"><?= $totalProducts ?></div>
                <div style="color: #7f8c8d;">Total Produk</div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include '../layouts/footer.php'; ?>
