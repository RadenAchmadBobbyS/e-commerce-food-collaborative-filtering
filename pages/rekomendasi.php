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
            $whereClause = '';
            $params = [];
            
            if (!empty($rekomendasi)) {
                $placeholders = str_repeat('?,', count($rekomendasi));
                $placeholders = rtrim($placeholders, ','); // Remove trailing comma
                $whereClause = 'WHERE p.id NOT IN (' . $placeholders . ')';
                $params = $rekomendasi;
            }
            
            $limitValue = 12 - count($rekomendasi);
            if ($limitValue > 0) {
                // Don't bind LIMIT value - use it directly in the SQL  
                $sql = "
                    SELECT p.id 
                    FROM products p 
                    LEFT JOIN ratings r ON p.id = r.product_id 
                    $whereClause
                    GROUP BY p.id 
                    ORDER BY AVG(r.rating) DESC, COUNT(r.id) DESC 
                    LIMIT " . intval($limitValue);
                
                $additionalStmt = $pdo->prepare($sql);
                $additionalStmt->execute($params);
                $additional = $additionalStmt->fetchAll(PDO::FETCH_COLUMN);
                $rekomendasi = array_merge($rekomendasi, $additional);
            }
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
    
    // Jika ada filter kategori spesifik, prioritaskan menampilkan SEMUA produk dari kategori tersebut
    if ($categoryFilter) {
        // Ambil semua produk dari kategori
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = ? 
            ORDER BY p.id ASC
        ");
        $stmt->execute([$categoryFilter]);
        $categoryProducts = $stmt->fetchAll();
        
        // Tambahkan informasi rating untuk setiap produk
        foreach ($categoryProducts as &$product) {
            $ratingStmt = $pdo->prepare("
                SELECT COUNT(*) as total_ratings, COALESCE(AVG(rating), 0) as avg_rating 
                FROM ratings 
                WHERE product_id = ?
            ");
            $ratingStmt->execute([$product['id']]);
            $ratingData = $ratingStmt->fetch();
            $product['total_ratings'] = (int)$ratingData['total_ratings'];
            $product['avg_rating'] = (float)$ratingData['avg_rating'];
        }
        
        // Urutkan berdasarkan rating: produk dengan rating terbanyak dulu, lalu rata-rata tertinggi
        usort($categoryProducts, function($a, $b) {
            if ($a['total_ratings'] != $b['total_ratings']) {
                return $b['total_ratings'] - $a['total_ratings']; // Descending by total ratings
            }
            if (abs($a['avg_rating'] - $b['avg_rating']) > 0.01) {
                return $b['avg_rating'] <=> $a['avg_rating']; // Descending by avg rating
            }
            return $a['id'] - $b['id']; // Ascending by ID as tiebreaker
        });
        
        if (!empty($categoryProducts)) {
            $catName = $categoryProducts[0]['category_name'] ?: 'Tanpa Kategori';
            $recommendationsByCategory[$catName] = $categoryProducts;
        } else {
            // Jika tidak ada produk dari kategori yang dipilih, ambil nama kategori dari database
            $catStmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
            $catStmt->execute([$categoryFilter]);
            $catData = $catStmt->fetch();
            if ($catData) {
                $recommendationsByCategory[$catData['name']] = [];
            }
        }
    } else {
        // Jika tidak ada filter kategori, gunakan rekomendasi dari algoritma terlebih dahulu
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
                $catName = $product['category_name'] ?: 'Tanpa Kategori';
                if (!isset($recommendationsByCategory[$catName])) {
                    $recommendationsByCategory[$catName] = [];
                }
                $recommendationsByCategory[$catName][] = $product;
            }
        }
    }
    
    // Jika tidak ada kategori yang terisi atau terlalu sedikit, tambahkan produk dari setiap kategori
    $minProductsNeeded = $categoryFilter ? 1 : 6; // Jika ada filter kategori, minimal 1, jika tidak minimal 6
    
    // Untuk mode "Semua Kategori", pastikan setiap kategori memiliki representasi yang memadai
    if (!$categoryFilter) {
        // Periksa setiap kategori dan pastikan ada minimal 3-4 produk per kategori jika tersedia
        $categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        $allCategories = $categoriesStmt->fetchAll();
        
        foreach ($allCategories as $category) {
            $catName = $category['name'];
            $currentCount = isset($recommendationsByCategory[$catName]) ? count($recommendationsByCategory[$catName]) : 0;
            
            // Untuk mode "Semua Kategori", target minimal 4 produk per kategori (jika tersedia)
            $targetPerCategory = 4;
            
            if ($currentCount < $targetPerCategory) {
                $existingIds = isset($recommendationsByCategory[$catName]) ? 
                    array_column($recommendationsByCategory[$catName], 'id') : [];
                
                $excludeClause = '';
                $params = [$category['id']];
                
                if (!empty($existingIds)) {
                    $placeholders = str_repeat('?,', count($existingIds));
                    $placeholders = rtrim($placeholders, ',');
                    $excludeClause = ' AND p.id NOT IN (' . $placeholders . ')';
                    $params = array_merge($params, $existingIds);
                }
                
                $limitValue = $targetPerCategory - $currentCount;
                if ($limitValue > 0) {
                    try {
                        $sql = "
                            SELECT p.*, c.name as category_name, c.id as category_id,
                                   COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_ratings 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id
                            LEFT JOIN ratings r ON p.id = r.product_id 
                            WHERE p.category_id = ?" . $excludeClause . "
                            GROUP BY p.id 
                            ORDER BY COUNT(r.id) DESC, AVG(r.rating) DESC, p.id ASC
                            LIMIT " . intval($limitValue);
                        
                        $additionalStmt = $pdo->prepare($sql);
                        $additionalStmt->execute($params);
                        $additionalProducts = $additionalStmt->fetchAll();
                        
                        if (!isset($recommendationsByCategory[$catName])) {
                            $recommendationsByCategory[$catName] = [];
                        }
                        
                        $recommendationsByCategory[$catName] = array_merge(
                            $recommendationsByCategory[$catName], 
                            $additionalProducts
                        );
                    } catch (PDOException $e) {
                        error_log("Category Query Error for {$catName}: " . $e->getMessage());
                    }
                }
            }
        }
    } else {
        // Kondisi original untuk filter kategori spesifik
        if (empty($recommendationsByCategory)) {
            $categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
            $allCategories = $categoriesStmt->fetchAll();
            
            foreach ($allCategories as $category) {
                if ($category['id'] != $categoryFilter) {
                    continue;
                }
                
                $catName = $category['name'];
                $currentCount = isset($recommendationsByCategory[$catName]) ? count($recommendationsByCategory[$catName]) : 0;
                
                // Tentukan batas produk berdasarkan filter kategori
                $maxProductsPerCategory = 50; // Untuk filter kategori spesifik, tampilkan lebih banyak
                $additionalLimit = 50; // Limit tambahan produk
                
                // Tambahkan produk dari kategori ini jika belum ada atau masih sedikit
                if ($currentCount < $maxProductsPerCategory) {
                    $existingIds = isset($recommendationsByCategory[$catName]) ? 
                        array_column($recommendationsByCategory[$catName], 'id') : [];
                    
                    $excludeClause = '';
                    $params = [$category['id']];
                    
                    if (!empty($existingIds)) {
                        $placeholders = str_repeat('?,', count($existingIds));
                        $placeholders = rtrim($placeholders, ','); // Remove trailing comma
                        $excludeClause = ' AND p.id NOT IN (' . $placeholders . ')';
                        $params = array_merge($params, $existingIds);
                    }
                    
                    // Add limit parameter to params array
                    $limitValue = $additionalLimit - $currentCount;
                    if ($limitValue > 0) {
                        // Don't bind LIMIT value - use it directly in the SQL
                        try {
                            $sql = "
                                SELECT p.*, c.name as category_name, c.id as category_id,
                                       COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_ratings 
                                FROM products p 
                                LEFT JOIN categories c ON p.category_id = c.id
                                LEFT JOIN ratings r ON p.id = r.product_id 
                                WHERE p.category_id = ?" . $excludeClause . "
                                GROUP BY p.id 
                                ORDER BY AVG(r.rating) DESC, COUNT(r.id) DESC, p.id ASC
                                LIMIT " . intval($limitValue);
                            
                            $additionalStmt = $pdo->prepare($sql);
                            $additionalStmt->execute($params);
                            $additionalProducts = $additionalStmt->fetchAll();
                            
                            if (!isset($recommendationsByCategory[$catName])) {
                                $recommendationsByCategory[$catName] = [];
                            }
                            
                            $recommendationsByCategory[$catName] = array_merge(
                                $recommendationsByCategory[$catName], 
                                $additionalProducts
                            );
                        } catch (PDOException $e) {
                            error_log("Category Query Error for {$catName}: " . $e->getMessage());
                            error_log("SQL: " . $sql);
                            error_log("Params: " . print_r($params, true));
                            // Continue without this category's additional products
                        }
                    }
                }
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
                        <?php 
                        // Menentukan ikon berdasarkan nama kategori
                        $categoryIcon = 'fas fa-utensils'; // Default untuk makanan
                        $categoryLower = strtolower($categoryName);
                        
                        if (strpos($categoryLower, 'minuman') !== false) {
                            if (strpos($categoryLower, 'panas') !== false) {
                                $categoryIcon = 'fas fa-mug-hot'; // Ikon untuk minuman panas
                            } elseif (strpos($categoryLower, 'dingin') !== false) {
                                $categoryIcon = 'fas fa-glass-water'; // Ikon untuk minuman dingin
                            } else {
                                $categoryIcon = 'fas fa-glass-whiskey'; // Ikon umum minuman
                            }
                        }
                        ?>
                        <i class="<?= $categoryIcon ?> text-danger"></i> <?= htmlspecialchars($categoryName) ?>
                    </h3>
                    <p class="category-subtitle"><?= count($products) ?> produk rekomendasi</p>
                </div>
                
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php if (!empty($product['image']) && file_exists("../assets/img/{$product['image']}")): ?>
                                <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); height: 160px;">
                                    <?php 
                                    // Menentukan ikon placeholder berdasarkan kategori
                                    $placeholderIcon = 'fas fa-utensils fa-2x text-muted'; // Default untuk makanan
                                    $productCategoryLower = strtolower($product['category_name']);
                                    
                                    if (strpos($productCategoryLower, 'minuman') !== false) {
                                        if (strpos($productCategoryLower, 'panas') !== false) {
                                            $placeholderIcon = 'fas fa-mug-hot fa-2x text-muted'; // Ikon untuk minuman panas
                                        } elseif (strpos($productCategoryLower, 'dingin') !== false) {
                                            $placeholderIcon = 'fas fa-glass-water fa-2x text-muted'; // Ikon untuk minuman dingin
                                        } else {
                                            $placeholderIcon = 'fas fa-glass-whiskey fa-2x text-muted'; // Ikon umum minuman
                                        }
                                    }
                                    ?>
                                    <i class="<?= $placeholderIcon ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Rating Badge -->
                            <?php if ($product['total_ratings'] > 0): ?>
                                <div class="rating-badge">
                                    <i class="fas fa-star"></i>
                                    <span><?= number_format($product['avg_rating'], 1) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-content">
                                <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                
                                <div class="product-meta">
                                    <div class="product-rating">
                                        <?php if ($product['total_ratings'] > 0): ?>
                                            <i class="fas fa-star"></i>
                                            <span><?= number_format($product['avg_rating'], 1) ?></span>
                                        <?php else: ?>
                                            <i class="fas fa-star text-muted"></i>
                                            <span class="text-muted">Baru</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-category">
                                        <?php 
                                        // Menentukan ikon kategori untuk meta
                                        $metaCategoryIcon = 'fas fa-tag'; // Default
                                        $metaCategoryLower = strtolower($product['category_name']);
                                        
                                        if (strpos($metaCategoryLower, 'minuman') !== false) {
                                            if (strpos($metaCategoryLower, 'panas') !== false) {
                                                $metaCategoryIcon = 'fas fa-mug-hot'; // Ikon untuk minuman panas
                                            } elseif (strpos($metaCategoryLower, 'dingin') !== false) {
                                                $metaCategoryIcon = 'fas fa-glass-water'; // Ikon untuk minuman dingin
                                            } else {
                                                $metaCategoryIcon = 'fas fa-glass-whiskey'; // Ikon umum minuman
                                            }
                                        } else {
                                            $metaCategoryIcon = 'fas fa-utensils'; // Ikon untuk makanan
                                        }
                                        ?>
                                        <i class="<?= $metaCategoryIcon ?> text-primary"></i>
                                        <span><?= htmlspecialchars($product['category_name']) ?></span>
                                    </div>
                                </div>
                                
                                <div class="product-footer">
                                    <div class="product-price">
                                        <i class="fas fa-money-bill text-success"></i>
                                        <span>Rp<?= number_format($product['price'] ?? 15000, 0, ',', '.') ?></span>
                                    </div>
                                    
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="product.php?id=<?= $product['id'] ?>" class="btn-rating">
                                            <i class="fas fa-star"></i> Rating & Ulasan
                                        </a>
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
        <?php endforeach; ?>
    <?php endif; ?>

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
    
    /* Products Grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-top: 2rem;
        width: 100%;
        max-width: 100%;
    }
    
    /* Product Card - Ukuran lebih kecil dan compact */
    .product-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: none;
        position: relative;
        max-width: 280px;
        margin: 0 auto;
    }
    
    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    
    .product-image {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
    }
    
    .rating-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(0, 0, 0, 0.8);
        color: #ffc107;
        padding: 0.3rem 0.6rem;
        border-radius: 15px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-weight: 600;
        z-index: 2;
    }
    
    .rating-badge i {
        color: #ffc107;
        font-size: 0.7rem;
    }
    
    .card-content {
        padding: 1rem;
    }
    
    .product-title {
        color: #2c3e50;
        margin-bottom: 0.4rem;
        font-size: 1.1rem;
        font-weight: 600;
        line-height: 1.3;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    .product-description {
        color: #6c757d;
        margin-bottom: 0.8rem;
        line-height: 1.4;
        font-size: 0.85rem;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    .product-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .product-rating {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: #495057;
    }
    
    .product-rating i {
        color: #ffc107;
        font-size: 0.75rem;
    }
    
    .product-category {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 500;
    }
    
    .product-category i {
        font-size: 0.7rem;
    }
    
    .product-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: auto;
        gap: 0.8rem;
    }
    
    .product-price {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 1rem;
        font-weight: 700;
        color: #28a745;
    }
    
    .product-price i {
        color: #28a745;
        font-size: 0.9rem;
    }
    
    .btn-rating {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        border: none;
        padding: 0.5rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(231, 76, 60, 0.3);
    }
    
    .btn-rating:hover {
        background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
        transform: translateY(-1px);
        color: white;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
    }
    
    .btn-rating i {
        font-size: 0.7rem;
    }
    
    /* Badge styling for "Baru" items */
    .product-rating .text-muted {
        color: #adb5bd !important;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.8rem;
        }
        
        .category-title {
            font-size: 1.3rem;
        }
        
        .product-card {
            max-width: 100%;
        }
        
        .product-footer {
            flex-direction: column;
            align-items: stretch;
            gap: 0.6rem;
        }
        
        .btn-rating {
            width: 100%;
            justify-content: center;
            padding: 0.6rem;
            font-size: 0.8rem;
        }
    }
    
    @media (max-width: 576px) {
        .products-grid {
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
        }
        
        .card-content {
            padding: 0.8rem;
        }
        
        .product-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.4rem;
        }
        
        .product-image {
            height: 140px;
        }
        
        .product-title {
            font-size: 1rem;
        }
        
        .product-description {
            font-size: 0.8rem;
            -webkit-line-clamp: 1;
        }
    }
    
    @media (max-width: 480px) {
        .products-grid {
            grid-template-columns: 1fr;
            gap: 0.8rem;
        }
        
        .product-card {
            margin: 0;
            max-width: 100%;
        }
        
        .card-content {
            padding: 1rem;
        }
        
        .category-title {
            font-size: 1.2rem;
        }
        
        .product-title {
            font-size: 1.1rem;
        }
        
        .btn-rating {
            font-size: 0.8rem;
            padding: 0.7rem;
        }
        
        .product-image {
            height: 160px;
        }
    }
</style>

<?php
include '../layouts/footer.php';
?>
