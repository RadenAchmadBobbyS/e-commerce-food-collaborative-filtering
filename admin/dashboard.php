<?php
session_start();

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: login.php?message=Akses ditolak. Silakan login sebagai admin&type=error');
    exit;
}

require '../config/db.php';

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch()['total_products'];

$stmt = $pdo->query("SELECT COUNT(*) as total_ratings FROM ratings");
$total_ratings = $stmt->fetch()['total_ratings'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FoodRec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .admin-navbar {
            background: rgba(231, 76, 60, 0.95) !important;
            backdrop-filter: blur(10px);
        }
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .stats-card {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .stats-card.users {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        .stats-card.products {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        .stats-card.ratings {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
    </style>
</head>
<body>
    <!-- Admin Navbar -->
    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container">
            <a class="navbar-brand text-white" href="dashboard.php">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin_name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../pages/home.php"><i class="fas fa-home"></i> Lihat Website</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-<?= $_GET['type'] ?? 'info' ?>" role="alert">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card users">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3><?= $total_users ?></h3>
                            <p class="mb-0">Total Users</p>
                        </div>
                        <i class="fas fa-users fa-3x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card products">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3><?= $total_products ?></h3>
                            <p class="mb-0">Total Produk</p>
                        </div>
                        <i class="fas fa-utensils fa-3x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card ratings">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3><?= $total_ratings ?></h3>
                            <p class="mb-0">Total Rating</p>
                        </div>
                        <i class="fas fa-star fa-3x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Menu -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="admin-card p-4">
                    <h4><i class="fas fa-images text-primary"></i> Manajemen Image</h4>
                    <p class="text-muted">Upload dan kelola gambar produk</p>
                    <a href="upload_image.php" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Image
                    </a>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="admin-card p-4">
                    <h4><i class="fas fa-utensils text-success"></i> Manajemen Produk</h4>
                    <p class="text-muted">Tambah, edit, dan hapus produk</p>
                    <a href="manage_products.php" class="btn btn-success">
                        <i class="fas fa-cog"></i> Kelola Produk
                    </a>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="admin-card p-4">
                    <h4><i class="fas fa-users text-info"></i> Manajemen User</h4>
                    <p class="text-muted">Lihat dan kelola data pengguna</p>
                    <a href="manage_users.php" class="btn btn-info">
                        <i class="fas fa-users-cog"></i> Kelola User
                    </a>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="admin-card p-4">
                    <h4><i class="fas fa-chart-bar text-warning"></i> Laporan</h4>
                    <p class="text-muted">Lihat statistik dan laporan sistem</p>
                    <a href="reports.php" class="btn btn-warning">
                        <i class="fas fa-chart-line"></i> Lihat Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
