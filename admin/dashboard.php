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
    <title>Admin Dashboard - WRKP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/admin-style.css" rel="stylesheet">
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
        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-4 fade-in-up">
                <div class="stats-card users">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= $total_users ?></div>
                            <div class="stats-label">Total Users</div>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 20px;">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 fade-in-up">
                <div class="stats-card products">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= $total_products ?></div>
                            <div class="stats-label">Total Produk</div>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 20px;">
                            <i class="fas fa-utensils fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 fade-in-up">
                <div class="stats-card ratings">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= $total_ratings ?></div>
                            <div class="stats-label">Total Rating</div>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 20px;">
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Menu -->
        <div class="row">
            <!-- Header Section -->
            <div class="col-12 mb-4">
                <div class="admin-card">
                    <div class="card-header-custom">
                        <h4 class="mb-0">
                            <i class="fas fa-tools"></i> Panel Administrasi
                        </h4>
                        <small class="opacity-75">Kelola semua aspek sistem rekomendasi makanan</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="admin-card h-100">
                    <div class="p-4 h-100 d-flex flex-column">
                        <div class="icon-feature mb-3">
                            <i class="fas fa-images"></i>
                        </div>
                        <div class="mb-3">
                            <h5 class="card-title mb-2">Manajemen Image</h5>
                            <p class="card-description mb-3">Upload dan kelola gambar produk makanan untuk meningkatkan daya tarik visual dan user experience</p>
                        </div>
                        <div class="mt-auto">
                            <a href="upload_image.php" class="btn w-100">
                                <i class="fas fa-upload me-2"></i> Upload Image
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="admin-card h-100">
                    <div class="p-4 h-100 d-flex flex-column">
                        <div class="icon-feature mb-3">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="mb-3">
                            <h5 class="card-title mb-2">Manajemen Produk</h5>
                            <p class="card-description mb-3">Tambah, edit, dan hapus produk makanan dalam sistem rekomendasi untuk pengalaman optimal</p>
                        </div>
                        <div class="mt-auto">
                            <a href="manage_products.php" class="btn w-100">
                                <i class="fas fa-cog me-2"></i> Kelola Produk
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="admin-card h-100">
                    <div class="p-4 h-100 d-flex flex-column">
                        <div class="icon-feature mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="mb-3">
                            <h5 class="card-title mb-2">Manajemen User</h5>
                            <p class="card-description mb-3">Lihat dan kelola data pengguna yang terdaftar dalam sistem untuk monitoring aktivitas</p>
                        </div>
                        <div class="mt-auto">
                            <a href="manage_users.php" class="btn w-100">
                                <i class="fas fa-users-cog me-2"></i> Kelola User
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="admin-card h-100">
                    <div class="p-4 h-100 d-flex flex-column">
                        <div class="icon-feature mb-3">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="mb-3">
                            <h5 class="card-title mb-2">Laporan & Statistik</h5>
                            <p class="card-description mb-3">Lihat statistik sistem dan laporan performa rekomendasi untuk analisis mendalam</p>
                        </div>
                        <div class="mt-auto">
                            <a href="reports.php" class="btn w-100">
                                <i class="fas fa-chart-line me-2"></i> Lihat Laporan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
