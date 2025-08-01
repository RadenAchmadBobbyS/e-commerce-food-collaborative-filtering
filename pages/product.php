<?php 
session_start();
include '../layouts/header.php'; 
require '../config/db.php';

// DEBUG INFO - TEMPORARY (hapus setelah testing)
if (isset($_GET['debug'])) {
    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 12px;'>";
    echo "<strong>DEBUG INFO:</strong><br>";
    echo "Session Status: " . (isset($_SESSION['user_id']) ? "✅ LOGGED IN (ID: {$_SESSION['user_id']})" : "❌ NOT LOGGED IN") . "<br>";
    echo "Session Data: " . print_r($_SESSION, true) . "<br>";
    echo "</div>";
}

// This page is already protected by header.php
// Display messages if any
if (isset($_GET['message'])) {
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
    $icon = $message_type === 'success' ? '✅' : ($message_type === 'error' ? '❌' : 'ℹ️');
    echo "<div class='notification notification-{$message_type}' id='notification'>
            <span class='notification-icon'>{$icon}</span>
            <span class='notification-text'>" . htmlspecialchars($_GET['message']) . "</span>
            <button type='button' class='notification-close' onclick='hideNotification()'>×</button>
          </div>";
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

    // Get user's current rating and comment if logged in
    $userRating = null;
    if (isset($_SESSION['user_id'])) {
        $userRatingStmt = $pdo->prepare("SELECT rating, comment FROM ratings WHERE user_id = ? AND product_id = ?");
        $userRatingStmt->execute([$_SESSION['user_id'], $id]);
        $userRating = $userRatingStmt->fetch();
    }

    // Get all ratings and comments for this product
    $ratingsStmt = $pdo->prepare("SELECT r.rating, r.comment, r.created_at, u.username as user_name 
                                 FROM ratings r 
                                 JOIN users u ON r.user_id = u.id 
                                 WHERE r.product_id = ? 
                                 ORDER BY r.created_at DESC");
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

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="home.php">Beranda</a></li>
        <li class="breadcrumb-item active">Detail Produk</li>
    </ol>
</nav>

<!-- Simple Product Layout -->
<div class="product-detail-container" style="max-width: 1000px; margin: 0 auto; padding: 20px;">
    <!-- Product Details -->
    <div class="product-detail" style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin-bottom: 2rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
            <div>
                <?php if (!empty($product['image']) && file_exists("../assets/img/{$product['image']}")): ?>
                    <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="product-image" style="width: 100%; max-width: 400px; height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 1rem;">
                <?php else: ?>
                    <div style="width: 100%; max-width: 400px; height: 300px; background: #f8f9fa; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #666; margin-bottom: 1rem;">
                        <i class="fas fa-utensils" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.4;"></i>
                        <p style="margin: 0;">Gambar tidak tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div>
                <h1 style="font-size: 2rem; color: #333; margin-bottom: 1rem;"><?= htmlspecialchars($product['name']) ?></h1>
                
                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1rem;">
                    <span style="font-size: 1.5rem;"><?= $stars ?></span>
                    <span style="font-size: 1.2rem; font-weight: bold; color: #333;"><?= $rating_display ?></span>
                    <span style="color: #666;">(<?= $product['total_ratings'] ?> rating)</span>
                </div>
                
                <div style="color: #666; line-height: 1.6; margin-bottom: 1rem;">
                    <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>
                
                <?php if (isset($product['price']) && $product['price'] > 0): ?>
                <div style="font-size: 1.5rem; font-weight: bold; color: #333; margin-bottom: 1rem;">
                    Harga: Rp <?= number_format($product['price'], 0, ',', '.') ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Rating & Review Section -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="rating-form" style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin-bottom: 2rem;">
        <h3 style="color: #333; margin-bottom: 1rem;">Berikan Rating & Ulasan</h3>
        
        <?php if ($userRating): ?>
            <div style="background: #d4edda; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #28a745; color: #155724;">
                <strong>✅ Anda sudah memberikan rating: <?= $userRating['rating'] ?>/5</strong>
                <?php if (!empty($userRating['comment'])): ?>
                    <br><em>"<?= htmlspecialchars($userRating['comment']) ?>"</em>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form id="ratingForm" method="POST" action="../process/rating_process.php">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500;">Rating Anda:</label>
                <div class="star-rating" style="display: flex; gap: 0.5rem; margin: 1rem 0;">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" <?= ($userRating && $userRating['rating'] == $i) ? 'checked' : '' ?> style="display: none;">
                        <label for="star<?= $i ?>" style="font-size: 2rem; color: #ddd; cursor: pointer; transition: color 0.2s ease;">
                            <i class="fas fa-star"></i>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="comment" style="display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500;">Ulasan (opsional):</label>
                <textarea id="comment" name="comment" 
                          placeholder="Bagikan pengalaman Anda tentang makanan ini..."
                          style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; resize: vertical;"
                          maxlength="500"><?= $userRating ? htmlspecialchars($userRating['comment'] ?? '') : '' ?></textarea>
                <div style="text-align: right; margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                    <span id="charCount">0</span>/500 karakter
                </div>
            </div>
            
            <button type="submit" style="background: #007bff; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer; font-size: 1rem;">
                <i class="fas fa-paper-plane"></i>
                <?= $userRating ? 'Perbarui' : 'Kirim' ?> Rating & Ulasan
            </button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div style="text-align: center; margin-bottom: 2rem;">
        <div style="background: #fff3cd; border-radius: 10px; padding: 2rem; border-left: 4px solid #ffc107;">
            <i class="fas fa-sign-in-alt" style="font-size: 2rem; color: #856404; margin-bottom: 1rem;"></i>
            <h3 style="color: #856404; margin-bottom: 1rem;">Login untuk Memberikan Rating</h3>
            <p style="color: #856404; margin-bottom: 1.5rem;">Silakan login terlebih dahulu untuk memberikan rating dan ulasan</p>
            <a href="login.php" style="background: #007bff; color: white; padding: 0.75rem 1.5rem; border-radius: 5px; text-decoration: none; font-weight: 500;">Login Sekarang</a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Reviews Section -->
    <div style="background: white; border-radius: 10px; padding: 2rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <h3 style="color: #333; margin-bottom: 1.5rem;">Ulasan Pengguna</h3>
        
        <?php if (!empty($allRatings)): ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($allRatings as $rating): ?>
                <div style="border-bottom: 1px solid #eee; padding: 1rem 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 30px; height: 30px; background: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                <?= strtoupper(substr($rating['user_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #333;"><?= htmlspecialchars($rating['user_name']) ?></div>
                                <div style="color: #ffc107;"><?= str_repeat('⭐', $rating['rating']) ?></div>
                            </div>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">
                            <?= date('d M Y', strtotime($rating['created_at'])) ?>
                        </div>
                    </div>
                    <?php if (!empty($rating['comment'])): ?>
                    <div style="color: #666; line-height: 1.5;">
                        <?= nl2br(htmlspecialchars($rating['comment'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; color: #666;">
                <i class="fas fa-comments" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Belum ada ulasan untuk produk ini</p>
                <small>Jadilah yang pertama memberikan ulasan!</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple star rating hover effect
    const starLabels = document.querySelectorAll('.star-rating label');
    starLabels.forEach((label, index) => {
        label.addEventListener('mouseover', function() {
            // Highlight stars on hover
            for (let i = starLabels.length - 1; i >= index; i--) {
                starLabels[i].style.color = '#ffc107';
            }
        });
        
        label.addEventListener('mouseout', function() {
            // Reset colors based on checked state
            const checkedInput = document.querySelector('.star-rating input[type="radio"]:checked');
            const checkedIndex = checkedInput ? Array.from(starLabels).indexOf(checkedInput.nextElementSibling) : -1;
            
            starLabels.forEach((lbl, i) => {
                lbl.style.color = i >= checkedIndex && checkedIndex >= 0 ? '#ffc107' : '#ddd';
            });
        });
        
        label.addEventListener('click', function() {
            // Update colors when clicked
            const inputValue = this.previousElementSibling.value;
            starLabels.forEach((lbl, i) => {
                lbl.style.color = (starLabels.length - i) <= inputValue ? '#ffc107' : '#ddd';
            });
        });
    });
    
    // Initialize star colors based on checked input
    const checkedInput = document.querySelector('.star-rating input[type="radio"]:checked');
    if (checkedInput) {
        const checkedValue = checkedInput.value;
        starLabels.forEach((label, i) => {
            label.style.color = (starLabels.length - i) <= checkedValue ? '#ffc107' : '#ddd';
        });
    }
    
    // Character counter for comment textarea
    const commentTextarea = document.getElementById('comment');
    const charCount = document.getElementById('charCount');
    
    if (commentTextarea && charCount) {
        charCount.textContent = commentTextarea.value.length;
        
        commentTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
    
    // Form submission with loading state
    const ratingForm = document.getElementById('ratingForm');
    if (ratingForm) {
        ratingForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            }
        });
    }
    
    // Auto-hide notification after 4 seconds
    const notification = document.getElementById('notification');
    if (notification) {
        setTimeout(function() {
            hideNotification();
        }, 4000);
    }
});

// Function to hide notification
function hideNotification() {
    const notification = document.getElementById('notification');
    if (notification) {
        notification.style.animation = 'slideOut 0.3s ease-in forwards';
        setTimeout(function() {
            notification.remove();
        }, 300);
    }
}
</script>

<?php include '../layouts/footer.php'; ?>
