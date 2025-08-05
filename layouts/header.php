<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in for protected pages
$protected_pages = ['rekomendasi.php', 'product.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (in_array($current_page, $protected_pages) && !isset($_SESSION['user_id'])) {
    header('Location: login.php?message=Silakan login terlebih dahulu');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WRKP - Sistem Rekomendasi Cerdas</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            position: relative;
        }
        
        .header-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1050;
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: #e74c3c !important;
            text-decoration: none;
        }
        
        .navbar-brand:hover {
            color: #c0392b !important;
        }
        
        .navbar-nav .nav-link {
            color: #2c3e50 !important;
            font-weight: 500;
            padding: 0.75rem 1rem !important;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 0.25rem;
        }
        
        .navbar-nav .nav-link:hover {
            color: #e74c3c !important;
            background-color: rgba(231, 76, 60, 0.1);
        }
        
        .navbar-nav .nav-link.active {
            color: #e74c3c !important;
            background-color: rgba(231, 76, 60, 0.15);
            font-weight: 600;
        }
        
        .user-dropdown .dropdown-toggle {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border: none;
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .user-dropdown .dropdown-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }
        
        .user-dropdown .dropdown-toggle::after {
            margin-left: 0.5rem;
        }
        
        .user-dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            padding: 0.5rem 0;
            min-width: 200px;
            z-index: 1051;
            position: absolute;
            top: 100%;
            right: 0;
            left: auto;
            margin-top: 2px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            will-change: transform;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            color: #2c3e50;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #e74c3c;
        }
        
        .dropdown-item i {
            margin-right: 0.5rem;
            width: 16px;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
            width: 100vw;
            margin-left: calc(-50vw + 50%);
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="70" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        
        .hero-content {
            display: flex;
            align-items: center;
            min-height: 300px;
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .hero-text {
            flex: 1;
            padding-right: 2rem;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .btn-hero {
            background: white;
            color: #e74c3c;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            color: #c0392b;
        }
        
        .hero-image {
            flex: 1;
            text-align: center;
        }
        
        .food-image-placeholder {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 3rem;
            backdrop-filter: blur(10px);
            display: inline-block;
        }
        
        /* Section Headers */
        .section-header {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .section-subtitle {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        /* Product Cards */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .product-image-container {
            position: relative;
            width: 100%;
            height: 180px;
            overflow: hidden;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .product-content {
            padding: 1rem 1.25rem 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .product-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.4;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: #f8f9fa;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .product-distance {
            color: #7f8c8d;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .product-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            gap: 1rem;
        }
        
        .product-price {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #2c3e50;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .rating-stars {
            color: #f39c12;
        }
        
        .main-content {
            padding-top: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            
            .user-dropdown .dropdown-toggle {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-content {
                flex-direction: column;
                text-align: center;
                padding: 0 1rem;
            }
            
            .hero-text {
                padding-right: 0;
                margin-bottom: 2rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function submitLogout() {
            const form = document.getElementById('logout-form');
            if (form) {
                form.submit();
            }
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg header-navbar">
        <div class="container">
            <a class="navbar-brand" href="home.php">
                <span style="background: #e74c3c; color: white; padding: 0.25rem 0.5rem; border-radius: 50%; margin-right: 0.5rem; font-size: 1.2rem;">🍽️</span>
                WRKP
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'active' : '' ?>" href="home.php">
                            <i class="fas fa-home"></i> Beranda
                        </a>
                    </li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'rekomendasi.php') ? 'active' : '' ?>" href="rekomendasi.php">
                                <i class="fas fa-star"></i> Rekomendasi
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'evaluation.php') ? 'active' : '' ?>" href="evaluation.php">
                                <i class="fas fa-chart-line"></i> Evaluasi MAE
                            </a>
                        </li>
                        
                        <li class="nav-item dropdown user-dropdown">
                            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> Halo, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Pengguna') ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="submitLogout(); return false;">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                            
                            <?php 
                            // Tentukan path logout berdasarkan lokasi file
                            $logout_path = '../process/logout_process.php';
                            ?>
                            <form id="logout-form" action="<?= $logout_path ?>" method="POST" style="display: none;">
                                <!-- Hidden form for logout -->
                            </form>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : '' ?>" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <script>
        // Enhanced dropdown functionality with Bootstrap integration
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdowns
            const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            const dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl, {
                    autoClose: true,
                    boundary: 'viewport'
                });
            });
            
            // Additional dropdown enhancements
            const dropdownMenus = document.querySelectorAll('.dropdown-menu');
            dropdownMenus.forEach(menu => {
                // Prevent dropdown from closing when clicking inside menu items (except logout)
                menu.addEventListener('click', function(e) {
                    if (!e.target.closest('.logout-link')) {
                        e.stopPropagation();
                    }
                });
            });
            
            // Handle logout functionality
            const logoutLinks = document.querySelectorAll('.logout-link');
            logoutLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('logout-form').submit();
                });
            });
        });
    </script>
    
    <main class="main-content">
        <div class="container">
