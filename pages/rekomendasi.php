<?php 
session_start();
include '../layouts/header.php'; 
require '../config/db.php'; 
require '../functions/cf.php';

// This page is already protected by header.php
// Display messages if any
if (isset($_GET['message'])) {
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
    echo "<div class='alert alert-{$message_type} alert-dismissible fade show' role='alert'>
            " . htmlspecialchars($_GET['message']) . "
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="home.php">Beranda</a></li>
        <li class="breadcrumb-item active">Rekomendasi</li>
    </ol>
</nav>

<!-- Section Title -->
<div class="section-header">
    <h2 class="section-title">
        <i class="fas fa-star text-danger"></i> Rekomendasi Khusus Untuk Anda
    </h2>
    <p class="section-subtitle">Berdasarkan rating dan preferensi Anda sebelumnya</p>
</div>

<?php
try {
    // Pastikan user_id valid
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        throw new Exception("User ID tidak valid");
    }
    
    $rekomendasi = getRecommendations($pdo, $_SESSION['user_id']);
    $recommendation_type = 'personal'; // Default jenis rekomendasi
    
    // Jika tidak ada rekomendasi personal, tampilkan produk dengan rating tertinggi
    if (empty($rekomendasi)) {
        $fallbackStmt = $pdo->query("
            SELECT p.id 
            FROM products p 
            LEFT JOIN ratings r ON p.id = r.product_id 
            GROUP BY p.id 
            HAVING COUNT(r.id) > 0 
            ORDER BY AVG(r.rating) DESC, COUNT(r.id) DESC 
            LIMIT 10
        ");
        $rekomendasi = $fallbackStmt->fetchAll(PDO::FETCH_COLUMN);
        $recommendation_type = 'popular'; // Berdasarkan popularitas
        
        // Jika masih tidak ada (tidak ada produk yang dirating), tampilkan semua produk
        if (empty($rekomendasi)) {
            $allProductsStmt = $pdo->query("SELECT id FROM products ORDER BY id ASC LIMIT 20");
            $rekomendasi = $allProductsStmt->fetchAll(PDO::FETCH_COLUMN);
            $recommendation_type = 'general'; // Rekomendasi umum
        }
    } else {
        // Jika rekomendasi personal terlalu sedikit, tambahkan produk populer
        if (count($rekomendasi) < 8) {
            $additionalStmt = $pdo->prepare("
                SELECT p.id 
                FROM products p 
                LEFT JOIN ratings r ON p.id = r.product_id 
                WHERE p.id NOT IN (" . str_repeat('?,', count($rekomendasi) - 1) . "?)
                GROUP BY p.id 
                ORDER BY AVG(r.rating) DESC, COUNT(r.id) DESC 
                LIMIT ?
            ");
            $params = array_merge($rekomendasi, [12 - count($rekomendasi)]);
            $additionalStmt->execute($params);
            $additional = $additionalStmt->fetchAll(PDO::FETCH_COLUMN);
            $rekomendasi = array_merge($rekomendasi, $additional);
        }
    }
} catch (Exception $e) {
    error_log("Recommendation Error: " . $e->getMessage());
    // Fallback: tampilkan semua produk
    $allProductsStmt = $pdo->query("SELECT id FROM products ORDER BY id ASC LIMIT 20");
    $rekomendasi = $allProductsStmt->fetchAll(PDO::FETCH_COLUMN);
    $recommendation_type = 'general'; // Rekomendasi umum
}
    
    // Menampilkan dropdown kategori
    $stmtCategories = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmtCategories->fetchAll();
    ?>
    
    <!-- Filter Section -->
    <div class="filter-section mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <form method="GET" class="category-filter">
                    <label for="category" class="form-label">Filter Kategori:</label>
                    <select class="form-select" id="category" name="category" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
    <?php
    
    // Kelompokkan rekomendasi berdasarkan kategori
    $categoryFilter = isset($_GET['category']) && is_numeric($_GET['category']) ? intval($_GET['category']) : null;
    
    // Organize recommendations by category
    $recommendationsByCategory = [];
    
    // Jika ada rekomendasi dari algoritma, gunakan itu terlebih dahulu
    foreach ($rekomendasi as $pid) {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.id as category_id,
                              COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_ratings 
                              FROM products p 
                              LEFT JOIN categories c ON p.category_id = c.id
                              LEFT JOIN ratings r ON p.id = r.product_id 
                              WHERE p.id = ? 
                              GROUP BY p.id");
        $stmt->execute([$pid]);
        $product = $stmt->fetch();
        
        if ($product) {
            if ($categoryFilter && $product['category_id'] != $categoryFilter) {
                continue; // Skip if not matching filter
            }
            
            $catName = $product['category_name'] ?: 'Tanpa Kategori';
            if (!isset($recommendationsByCategory[$catName])) {
                $recommendationsByCategory[$catName] = [];
            }
            $recommendationsByCategory[$catName][] = $product;
        }
    }
    
    // Jika tidak ada kategori yang terisi atau terlalu sedikit, tambahkan produk dari setiap kategori
    if (empty($recommendationsByCategory) || array_sum(array_map('count', $recommendationsByCategory)) < 6) {
        $categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        $allCategories = $categoriesStmt->fetchAll();
        
        foreach ($allCategories as $category) {
            if ($categoryFilter && $category['id'] != $categoryFilter) {
                continue;
            }
            
            $catName = $category['name'];
            $currentCount = isset($recommendationsByCategory[$catName]) ? count($recommendationsByCategory[$catName]) : 0;
            
            // Tambahkan produk dari kategori ini jika belum ada atau masih sedikit
            if ($currentCount < 3) {
                $existingIds = isset($recommendationsByCategory[$catName]) ? 
                    array_column($recommendationsByCategory[$catName], 'id') : [];
                
                $excludeClause = '';
                $params = [$category['id']];
                
                if (!empty($existingIds)) {
                    $excludeClause = ' AND p.id NOT IN (' . str_repeat('?,', count($existingIds) - 1) . '?)';
                    $params = array_merge($params, $existingIds);
                }
                
                $additionalStmt = $pdo->prepare("
                    SELECT p.*, c.name as category_name, c.id as category_id,
                           COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_ratings 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN ratings r ON p.id = r.product_id 
                    WHERE p.category_id = ? $excludeClause
                    GROUP BY p.id 
                    ORDER BY AVG(r.rating) DESC, COUNT(r.id) DESC, p.id ASC
                    LIMIT ?
                ");
                
                $params[] = 4 - $currentCount; // Tambahkan maksimal 4 produk per kategori
                $additionalStmt->execute($params);
                $additionalProducts = $additionalStmt->fetchAll();
                
                if (!isset($recommendationsByCategory[$catName])) {
                    $recommendationsByCategory[$catName] = [];
                }
                
                $recommendationsByCategory[$catName] = array_merge(
                    $recommendationsByCategory[$catName], 
                    $additionalProducts
                );
            }
        }
    }
    ?>

    <!-- Recommendation Type Info -->
    <?php if (!empty($recommendationsByCategory)): ?>
        <div class="row mb-3">
            <div class="col-12">
                <?php if ($recommendation_type === 'personal'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-user-check me-2"></i><strong>Rekomendasi Personal</strong> - Berdasarkan preferensi dan rating Anda sebelumnya
                    </div>
                <?php elseif ($recommendation_type === 'popular'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-star me-2"></i><strong>Produk Terpopuler</strong> - Berikan rating pada beberapa produk untuk mendapatkan rekomendasi personal
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-utensils me-2"></i><strong>Semua Produk</strong> - Mulai berikan rating untuk mendapatkan rekomendasi yang lebih personal
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Products by Category -->
    <?php if (empty($recommendationsByCategory)): ?>
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>Tidak ada produk yang tersedia.</strong><br>
            <small>Hubungi administrator untuk menambahkan produk.</small>
        </div>
    <?php else: ?>
        <?php foreach ($recommendationsByCategory as $categoryName => $products): ?>
            <!-- Category Section -->
            <div class="category-section">
                <div class="category-header">
                    <h3 class="category-title">
                        <i class="fas fa-utensils text-danger"></i> <?= htmlspecialchars($categoryName) ?>
                    </h3>
                    <p class="category-subtitle"><?= count($products) ?> produk rekomendasi</p>
                </div>
                
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image-container">
                                <?php if (!empty($product['image']) && file_exists("../assets/img/{$product['image']}")): ?>
                                    <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                        <i class="fas fa-utensils fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Rating Badge -->
                                <?php if ($product['total_ratings'] > 0): ?>
                                    <div class="rating-badge">
                                        <i class="fas fa-star rating-stars"></i>
                                        <span><?= number_format($product['avg_rating'], 1) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-content">
                                <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                
                                <div class="product-meta">
                                    <div class="product-rating">
                                        <?php if ($product['total_ratings'] > 0): ?>
                                            <i class="fas fa-star rating-stars"></i>
                                            <span><?= number_format($product['avg_rating'], 1) ?></span>
                                        <?php else: ?>
                                            <i class="fas fa-star text-muted"></i>
                                            <span class="text-muted">Baru</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-category">
                                        <i class="fas fa-tag"></i> <?= htmlspecialchars($product['category_name']) ?>
                                    </div>
                                </div>
                                
                                <div class="product-footer">
                                    <div class="product-price">
                                        <i class="fas fa-tag"></i>
                                        <span>Rp<?= number_format($product['price'] ?? 15000, 0, ',', '.') ?></span>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <form method="POST" action="../process/rating_process.php" class="rating-form">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                <button type="submit" class="btn-rating">
                                                    <i class="fas fa-star"></i> Rating
                                                </button>
                                            </form>
                                            <button class="btn-comment" onclick="toggleComment(<?= $product['id'] ?>)">
                                                <i class="fas fa-comment"></i> Ulasan
                                            </button>
                                        <?php else: ?>
                                            <a href="login.php" class="btn-rating">
                                                <i class="fas fa-sign-in-alt"></i> Login untuk Rating
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Comment Section -->
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <div class="comment-section" id="comment-<?= $product['id'] ?>" style="display: none;">
                                        <form class="comment-form" onsubmit="submitComment(event, <?= $product['id'] ?>)">
                                            <textarea class="comment-input" placeholder="Tulis ulasan Anda..." required></textarea>
                                            <button type="submit" class="btn-submit-comment">
                                                <i class="fas fa-paper-plane"></i> Kirim Ulasan
                                            </button>
                                        </form>
                                        
                                        <!-- Existing Comments -->
                                        <div class="comments-list" id="comments-<?= $product['id'] ?>">
                                            <?php
                                            $commentsStmt = $pdo->prepare("
                                                SELECT c.*, u.username 
                                                FROM comments c 
                                                JOIN users u ON c.user_id = u.id 
                                                WHERE c.product_id = ? 
                                                ORDER BY c.created_at DESC 
                                                LIMIT 5
                                            ");
                                            $commentsStmt->execute([$product['id']]);
                                            $comments = $commentsStmt->fetchAll();
                                            
                                            foreach ($comments as $comment): ?>
                                                <div class="comment-item">
                                                    <div class="comment-header">
                                                        <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                                        <small class="text-muted"><?= date('d M Y H:i', strtotime($comment['created_at'])) ?></small>
                                                    </div>
                                                    <div class="comment-text">
                                                        <?= htmlspecialchars($comment['comment']) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<style>
.btn-rating {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
    margin-bottom: 0.5rem;
}

.btn-rating:hover {
    background: #c0392b;
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
}

.rating-form {
    margin: 0;
    display: inline-flex;
    align-items: center;
}

/* Product Footer Responsive */
.product-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.product-price {
    font-size: 1.1rem;
    font-weight: 600;
    color: #dc3545;
    flex-shrink: 0;
}

.product-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: center;
}

.product-actions > * {
    align-self: center;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .product-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .product-actions {
        width: 100%;
        justify-content: flex-start;
        gap: 0.5rem;
    }
    
    .btn-rating, .btn-comment {
        font-size: 0.8rem;
        padding: 0.5rem 1rem;
        flex: 1;
        justify-content: center;
        min-width: 120px;
    }
}

@media (max-width: 576px) {
    .product-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-rating, .btn-comment {
        width: 100%;
        justify-content: center;
        margin-bottom: 0.25rem;
    }
}

/* Category Section Styling */
.category-section {
    margin-bottom: 3rem;
}

.category-header {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dc3545;
}

/* Override products-grid for recommendation page */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
    align-items: start; /* Key fix: prevent cards from stretching */
}

/* Ensure product cards maintain their position */
.product-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
    position: relative; /* Important for stable positioning */
    height: fit-content; /* Prevent unnecessary stretching */
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Comment section with smooth animation */
.comment-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
    transition: all 0.3s ease;
    overflow: hidden;
}

.category-title {
    color: #2c3e50;
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.category-subtitle {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
}

/* Comment Section */
.comment-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.comment-form {
    margin-bottom: 1rem;
}

.comment-input {
    width: 100%;
    min-height: 80px;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    resize: vertical;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.comment-input:focus {
    outline: none;
    border-color: #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2);
}

.btn-comment {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
    margin-bottom: 0.5rem;
}

.btn-comment:hover {
    background: linear-gradient(135deg, #138496 0%, #0c5460 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
    color: white;
}

.btn-submit-comment {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-submit-comment:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}

.comments-list {
    max-height: 300px;
    overflow-y: auto;
}

.comment-item {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.comment-text {
    color: #495057;
    font-size: 0.9rem;
    line-height: 1.4;
}
</style>

<script>
// Toggle comment section with smooth animation
function toggleComment(productId) {
    const commentSection = document.getElementById('comment-' + productId);
    
    if (commentSection.style.display === 'none' || commentSection.style.display === '') {
        // Show with slide down effect
        commentSection.style.display = 'block';
        commentSection.style.opacity = '0';
        commentSection.style.maxHeight = '0';
        commentSection.style.overflow = 'hidden';
        
        // Force reflow
        commentSection.offsetHeight;
        
        // Animate in
        commentSection.style.transition = 'all 0.3s ease';
        commentSection.style.opacity = '1';
        commentSection.style.maxHeight = '500px';
        
        // Clean up after animation
        setTimeout(() => {
            commentSection.style.maxHeight = 'none';
            commentSection.style.overflow = 'visible';
        }, 300);
    } else {
        // Hide with slide up effect
        commentSection.style.transition = 'all 0.3s ease';
        commentSection.style.opacity = '0';
        commentSection.style.maxHeight = '0';
        commentSection.style.overflow = 'hidden';
        
        setTimeout(() => {
            commentSection.style.display = 'none';
        }, 300);
    }
}

// Submit comment
function submitComment(event, productId) {
    event.preventDefault();
    
    const form = event.target;
    const commentText = form.querySelector('.comment-input').value.trim();
    
    if (!commentText) {
        alert('Mohon tulis ulasan Anda');
        return;
    }
    
    // Create FormData
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('comment', commentText);
    
    // Send to server
    fetch('../process/comment_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear the form
            form.querySelector('.comment-input').value = '';
            
            // Add new comment to the list
            const commentsList = document.getElementById('comments-' + productId);
            const newComment = document.createElement('div');
            newComment.className = 'comment-item';
            newComment.innerHTML = `
                <div class="comment-header">
                    <strong>${data.username}</strong>
                    <small class="text-muted">Baru saja</small>
                </div>
                <div class="comment-text">${commentText}</div>
            `;
            commentsList.insertBefore(newComment, commentsList.firstChild);
            
            alert('Ulasan berhasil ditambahkan!');
        } else {
            alert('Gagal menambahkan ulasan: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengirim ulasan');
    });
}
</script>

<?php
include '../layouts/footer.php';
?>
