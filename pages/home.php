<?php 
include '../layouts/header.php'; 
require '../config/db.php'; 

// Display messages if any
if (isset($_GET['message'])) {
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
    echo "<div class='alert alert-{$message_type} alert-dismissible fade show' role='alert'>
            " . htmlspecialchars($_GET['message']) . "
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="hero-content">
        <div class="hero-text">
            <h1 class="hero-title">Rekomendasi Makanan Terbaik</h1>
            <p class="hero-subtitle">Temukan berbagai pilihan makanan lezat yang telah dipilih khusus untuk Anda</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="hero-cta">
                    <a href="login.php" class="btn btn-hero">Mulai Rekomendasi</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="hero-image">
            <div class="food-image-placeholder">
                <i class="fas fa-utensils fa-5x"></i>
            </div>
        </div>
    </div>
</div>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="home.php">Beranda</a></li>
        <li class="breadcrumb-item active">Daftar Makanan</li>
    </ol>
</nav>

<!-- Section Title -->
<div class="section-header">
    <h2 class="section-title">
        <i class="fas fa-fire text-danger"></i> Pilihan Terpopuler
    </h2>
    <p class="section-subtitle">Nikmati cita rasa terbaik yang telah dipilih khusus untuk Anda</p>
</div>

<?php
// Menampilkan dropdown kategori
$stmtCategories = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmtCategories->fetchAll();
?>

<!-- Filter Section -->
<div class="filter-section mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <form method="GET" class="category-filter">
                <label for="categoryFilter" class="form-label">Filter Kategori:</label>
                <select class="form-select" id="categoryFilter" name="category" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <div class="view-info">
                <span class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    <?php if (isset($_GET['category']) && !empty($_GET['category'])): ?>
                        Menampilkan kategori tertentu
                    <?php else: ?>
                        Menampilkan semua kategori
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<?php
// Query untuk mendapatkan produk dengan rating
$categoryFilter = isset($_GET['category']) && is_numeric($_GET['category']) ? intval($_GET['category']) : null;

if ($categoryFilter) {
    // Jika ada filter kategori, tampilkan hanya kategori tersebut
    $query = "SELECT p.*, c.name as category_name, COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_ratings 
              FROM products p 
              LEFT JOIN ratings r ON p.id = r.product_id 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.category_id = :category_id
              GROUP BY p.id 
              ORDER BY avg_rating DESC, p.name ASC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':category_id', $categoryFilter, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Ambil nama kategori yang difilter
    $categoryStmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $categoryStmt->execute([$categoryFilter]);
    $filteredCategoryName = $categoryStmt->fetchColumn();
?>

<!-- Single Category Section (Filtered) -->
<div class="category-section">
    <div class="category-header">
        <h3 class="category-title">
            <i class="fas fa-utensils text-danger"></i> <?= htmlspecialchars($filteredCategoryName) ?>
        </h3>
        <p class="category-subtitle"><?= count($products) ?> produk tersedia</p>
    </div>
    
    <div class="products-grid" id="productsGrid">
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
                            <span>Rp<?= number_format($product['price'], 0, ',', '.') ?></span>
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" action="../process/rating_process.php" class="rating-form">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="btn-rating">
                                    <i class="fas fa-star"></i> Rating
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn-rating">
                                <i class="fas fa-sign-in-alt"></i> Login untuk Rating
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-search fa-4x text-muted"></i>
        </div>
        <h3>Tidak ada produk ditemukan</h3>
        <p class="text-muted">Tidak ada produk dalam kategori ini</p>
    </div>
<?php endif; ?>

<?php } else { 
    // Jika tidak ada filter, tampilkan semua kategori dengan section terpisah
    $categoriesQuery = "SELECT * FROM categories ORDER BY name ASC";
    $categoriesStmt = $pdo->query($categoriesQuery);
    $allCategories = $categoriesStmt->fetchAll();
    
    foreach ($allCategories as $category):
        // Query produk untuk setiap kategori
        $query = "SELECT p.*, c.name as category_name, COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_ratings 
                  FROM products p 
                  LEFT JOIN ratings r ON p.id = r.product_id 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id = :category_id
                  GROUP BY p.id 
                  ORDER BY avg_rating DESC, p.name ASC";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':category_id', $category['id'], PDO::PARAM_INT);
        $stmt->execute();
        $categoryProducts = $stmt->fetchAll();
        
        if (empty($categoryProducts)) continue; // Skip kategori tanpa produk
?>

<!-- Category Section -->
<div class="category-section">
    <div class="category-header">
        <h3 class="category-title">
            <i class="fas fa-utensils text-danger"></i> <?= htmlspecialchars($category['name']) ?>
        </h3>
        <p class="category-subtitle"><?= count($categoryProducts) ?> produk tersedia</p>
    </div>
    
    <div class="products-grid">
        <?php foreach ($categoryProducts as $product): ?>
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
                            <span>Rp<?= number_format($product['price'], 0, ',', '.') ?></span>
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="POST" action="../process/rating_process.php" class="rating-form">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="btn-rating">
                                    <i class="fas fa-star"></i> Rating
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn-rating">
                                <i class="fas fa-sign-in-alt"></i> Login untuk Rating
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php 
    endforeach;
} ?>

<style>
    /* Category Section Styling */
    .category-section {
        margin-bottom: 3rem;
    }
    
    .category-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .category-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .category-subtitle {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin: 0;
    }
    
    /* Additional Styles for GoFood-like appearance */
    .product-image-container {
        position: relative;
        overflow: hidden;
        background: #f8f9fa;
    }
    
    .rating-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(0, 0, 0, 0.75);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-weight: 500;
        z-index: 2;
    }
    
    .product-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: auto;
        gap: 1rem;
        padding-top: 0.5rem;
        border-top: 1px solid #f1f3f4;
    }
    
    .btn-rating {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
    }
    
    .btn-rating:hover {
        background: #c0392b;
        transform: translateY(-1px);
        color: white;
        text-decoration: none;
    }
    
    .rating-form {
        margin: 0;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 15px;
        margin: 2rem 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .empty-icon {
        margin-bottom: 1.5rem;
    }
    
    .view-toggle .btn {
        border-color: #dee2e6;
        padding: 0.5rem 0.75rem;
    }
    
    .view-toggle .btn.active {
        background-color: #e74c3c;
        border-color: #e74c3c;
        color: white;
    }
    
    .view-toggle .btn:hover {
        border-color: #e74c3c;
        color: #e74c3c;
    }
    
    .view-toggle .btn.active:hover {
        background-color: #c0392b;
        border-color: #c0392b;
        color: white;
    }
    
    /* Ensure consistent card heights */
    .products-grid .product-card {
        min-height: 350px;
    }
    
    /* Icon styling */
    .product-price i {
        color: #27ae60;
    }
    
    .product-category {
        color: #7f8c8d;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        background: #f8f9fa;
        padding: 0.3rem 0.6rem;
        border-radius: 20px;
        font-weight: 500;
    }
    
    .product-category i {
        color: #e74c3c;
    }
    
    /* Badge styling for "Baru" items */
    .product-rating .text-muted {
        color: #95a5a6 !important;
    }
</style>

<script>
// Add smooth scroll animation
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.product-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
    
    // Smooth scroll to category sections
    const categoryTitles = document.querySelectorAll('.category-title');
    categoryTitles.forEach(title => {
        title.style.cursor = 'pointer';
        title.addEventListener('click', function() {
            this.parentElement.parentElement.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        });
    });
});
</script>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeIn 0.6s ease forwards;
}
</style>

<?php include '../layouts/footer.php'; ?>
