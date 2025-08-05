<?php
/**
 * Demo Script untuk Generate Sample Ratings
 * Script ini akan membuat sample data rating untuk menguji evaluasi MAE
 */

require '../config/db.php';

// Set timezone untuk Indonesia
date_default_timezone_set('Asia/Jakarta');

function generateSampleRatings($pdo, $numUsers = 20, $numProducts = 15, $ratingsPerUser = 8) {
    try {
        // Mulai transaksi
        $pdo->beginTransaction();
        
        echo "<h2>🎲 Generating Sample Data untuk Evaluasi MAE</h2>\n";
        
        // 1. Buat sample users jika belum ada
        echo "<h3>👥 Creating Sample Users...</h3>\n";
        for ($i = 1; $i <= $numUsers; $i++) {
            $username = "testuser" . $i;
            $email = "testuser{$i}@example.com";
            $password = password_hash("password123", PASSWORD_DEFAULT);
            
            // Check if user exists
            $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkUser->execute([$username, $email]);
            
            if (!$checkUser->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $password]);
                echo "✅ Created user: {$username}<br>\n";
            } else {
                echo "ℹ️ User already exists: {$username}<br>\n";
            }
        }
        
        // 2. Ambil semua users dan products yang ada
        $users = $pdo->query("SELECT id FROM users LIMIT $numUsers")->fetchAll(PDO::FETCH_COLUMN);
        $products = $pdo->query("SELECT id FROM products LIMIT $numProducts")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($users) || empty($products)) {
            throw new Exception("Tidak ada users atau products yang tersedia!");
        }
        
        echo "<h3>⭐ Generating Random Ratings...</h3>\n";
        $totalRatings = 0;
        
        // 3. Generate ratings untuk setiap user
        foreach ($users as $userId) {
            // Shuffle products dan ambil beberapa untuk di-rating
            $shuffledProducts = $products;
            shuffle($shuffledProducts);
            $productsToRate = array_slice($shuffledProducts, 0, min($ratingsPerUser, count($shuffledProducts)));
            
            foreach ($productsToRate as $productId) {
                // Check if rating already exists
                $checkRating = $pdo->prepare("SELECT id FROM ratings WHERE user_id = ? AND product_id = ?");
                $checkRating->execute([$userId, $productId]);
                
                if (!$checkRating->fetch()) {
                    // Generate realistic rating (bias towards higher ratings)
                    $rating = generateRealisticRating();
                    
                    // Generate random comment (opsional)
                    $comments = [
                        "Makanannya enak banget!",
                        "Rasanya lumayan, tapi bisa lebih baik.",
                        "Sangat memuaskan, akan pesan lagi.",
                        "Biasa aja sih, tidak istimewa.",
                        "Luar biasa! Sangat recommended!",
                        "Kurang sesuai selera saya.",
                        "Porsinya pas dan rasanya mantap.",
                        "Harganya worth it dengan kualitasnya.",
                        "",  // No comment
                        ""   // No comment
                    ];
                    $comment = $comments[array_rand($comments)];
                    
                    $stmt = $pdo->prepare("INSERT INTO ratings (user_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$userId, $productId, $rating, $comment]);
                    $totalRatings++;
                    
                    echo "📝 User {$userId} rated Product {$productId}: {$rating}/5<br>\n";
                }
            }
        }
        
        // Commit transaksi
        $pdo->commit();
        
        echo "<div style='background: #d4edda; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #28a745;'>";
        echo "<h3>✅ Sample Data Generation Complete!</h3>";
        echo "<strong>Total Ratings Generated:</strong> {$totalRatings}<br>";
        echo "<strong>Users:</strong> " . count($users) . "<br>";
        echo "<strong>Products:</strong> " . count($products) . "<br>";
        echo "<strong>Avg Ratings per User:</strong> " . round($totalRatings / count($users), 1) . "<br>";
        echo "</div>";
        
        return [
            'success' => true,
            'total_ratings' => $totalRatings,
            'total_users' => count($users),
            'total_products' => count($products)
        ];
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #dc3545;'>";
        echo "<h3>❌ Error:</h3>";
        echo htmlspecialchars($e->getMessage());
        echo "</div>";
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function generateRealisticRating() {
    // Bias towards higher ratings (more realistic)
    $random = mt_rand(1, 100);
    
    if ($random <= 5) return 1;      // 5% chance
    if ($random <= 15) return 2;     // 10% chance  
    if ($random <= 35) return 3;     // 20% chance
    if ($random <= 70) return 4;     // 35% chance
    return 5;                        // 30% chance
}

function clearSampleData($pdo) {
    try {
        $pdo->beginTransaction();
        
        echo "<h3>🧹 Clearing Sample Data...</h3>\n";
        
        // Delete sample ratings
        $stmt = $pdo->prepare("DELETE FROM ratings WHERE user_id IN (SELECT id FROM users WHERE username LIKE 'testuser%')");
        $stmt->execute();
        $ratingsDeleted = $stmt->rowCount();
        
        // Delete sample users
        $stmt = $pdo->prepare("DELETE FROM users WHERE username LIKE 'testuser%'");
        $stmt->execute();
        $usersDeleted = $stmt->rowCount();
        
        $pdo->commit();
        
        echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #ffc107;'>";
        echo "<h3>🗑️ Sample Data Cleared!</h3>";
        echo "<strong>Ratings Deleted:</strong> {$ratingsDeleted}<br>";
        echo "<strong>Users Deleted:</strong> {$usersDeleted}<br>";
        echo "</div>";
        
        return ['success' => true, 'ratings_deleted' => $ratingsDeleted, 'users_deleted' => $usersDeleted];
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #dc3545;'>";
        echo "<h3>❌ Error:</h3>";
        echo htmlspecialchars($e->getMessage());
        echo "</div>";
        
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Handle requests
$action = $_GET['action'] ?? 'show';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Data Generator - MAE Evaluation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f8f9fa; 
            padding: 2rem 0;
        }
        .container { 
            max-width: 900px; 
        }
        .card { 
            border: none; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            border-radius: 15px; 
        }
        pre { 
            background: #f8f9fa; 
            padding: 1rem; 
            border-radius: 8px; 
            max-height: 400px; 
            overflow-y: auto; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h1 class="mb-0">
                    <i class="fas fa-database"></i> Demo Data Generator untuk Evaluasi MAE
                </h1>
            </div>
            <div class="card-body">
                
                <?php if ($action === 'generate'): ?>
                    <div style="background: white; padding: 1rem; border-radius: 8px; font-family: monospace;">
                        <?php 
                        $numUsers = (int)($_GET['users'] ?? 20);
                        $numProducts = (int)($_GET['products'] ?? 15);
                        $ratingsPerUser = (int)($_GET['ratings'] ?? 8);
                        
                        $result = generateSampleRatings($pdo, $numUsers, $numProducts, $ratingsPerUser); 
                        ?>
                    </div>
                    
                    <div class="mt-3">
                        <a href="?action=show" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <a href="../pages/evaluation.php" class="btn btn-success">
                            <i class="fas fa-chart-line"></i> Test MAE Evaluation
                        </a>
                    </div>
                    
                <?php elseif ($action === 'clear'): ?>
                    <div style="background: white; padding: 1rem; border-radius: 8px; font-family: monospace;">
                        <?php $result = clearSampleData($pdo); ?>
                    </div>
                    
                    <div class="mt-3">
                        <a href="?action=show" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                    
                <?php else: ?>
                    <!-- Show current statistics -->
                    <?php
                    try {
                        $stats = [];
                        $stmt = $pdo->query("SELECT COUNT(*) as total_ratings FROM ratings");
                        $stats['total_ratings'] = $stmt->fetch()['total_ratings'];
                        
                        $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as total_users FROM ratings");
                        $stats['total_users'] = $stmt->fetch()['total_users'];
                        
                        $stmt = $pdo->query("SELECT COUNT(DISTINCT product_id) as total_products FROM ratings");
                        $stats['total_products'] = $stmt->fetch()['total_products'];
                        
                        $stmt = $pdo->query("SELECT COUNT(*) as sample_users FROM users WHERE username LIKE 'testuser%'");
                        $stats['sample_users'] = $stmt->fetch()['sample_users'];
                    } catch (Exception $e) {
                        $stats = ['error' => $e->getMessage()];
                    }
                    ?>
                    
                    <h3 class="text-primary mb-3">
                        <i class="fas fa-info-circle"></i> Current Database Statistics
                    </h3>
                    
                    <?php if (!isset($stats['error'])): ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white text-center">
                                <div class="card-body">
                                    <h2><?= number_format($stats['total_ratings']) ?></h2>
                                    <p>Total Ratings</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white text-center">
                                <div class="card-body">
                                    <h2><?= $stats['total_users'] ?></h2>
                                    <p>Active Users</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white text-center">
                                <div class="card-body">
                                    <h2><?= $stats['total_products'] ?></h2>
                                    <p>Rated Products</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white text-center">
                                <div class="card-body">
                                    <h2><?= $stats['sample_users'] ?></h2>
                                    <p>Sample Users</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <h4><i class="fas fa-lightbulb"></i> Tentang Demo Data Generator</h4>
                        <p>Tool ini akan membuat sample data rating untuk menguji sistem evaluasi MAE. Data yang dibuat meliputi:</p>
                        <ul>
                            <li><strong>Test Users:</strong> User dengan username "testuser1", "testuser2", dst.</li>
                            <li><strong>Random Ratings:</strong> Rating 1-5 dengan distribusi realistis (bias ke rating tinggi)</li>
                            <li><strong>Sample Comments:</strong> Komentar random untuk beberapa rating</li>
                        </ul>
                        <p class="mb-0"><strong>Catatan:</strong> Data ini hanya untuk testing dan dapat dihapus kapan saja.</p>
                    </div>
                    
                    <!-- Generation Form -->
                    <div class="card border-primary">
                        <div class="card-header bg-light">
                            <h4 class="mb-0"><i class="fas fa-plus"></i> Generate Sample Data</h4>
                        </div>
                        <div class="card-body">
                            <form action="" method="GET" class="row g-3">
                                <input type="hidden" name="action" value="generate">
                                
                                <div class="col-md-4">
                                    <label class="form-label">Jumlah Users:</label>
                                    <input type="number" name="users" class="form-control" value="20" min="5" max="100">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Jumlah Products:</label>
                                    <input type="number" name="products" class="form-control" value="15" min="5" max="50">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Ratings per User:</label>
                                    <input type="number" name="ratings" class="form-control" value="8" min="3" max="20">
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-magic"></i> Generate Sample Data
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex gap-2 flex-wrap">
                        <?php if ($stats['sample_users'] > 0): ?>
                        <a href="?action=clear" class="btn btn-warning" 
                           onclick="return confirm('Yakin ingin menghapus semua sample data?')">
                            <i class="fas fa-trash"></i> Clear Sample Data
                        </a>
                        <?php endif; ?>
                        
                        <a href="../pages/evaluation.php" class="btn btn-success">
                            <i class="fas fa-chart-line"></i> Go to MAE Evaluation
                        </a>
                        
                        <a href="../pages/home.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
