<?php
session_start();

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: login.php?message=Akses ditolak. Silakan login sebagai admin&type=error');
    exit;
}

require '../config/db.php';

$message = '';
$type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $target_dir = "../assets/img/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $new_filename = "product_" . $product_id . "_" . time() . "." . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is valid
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        // Check file size (5MB max)
        if ($_FILES["image"]["size"] <= 5000000) {
            // Allow certain file formats
            if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif" || $imageFileType == "webp") {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Update database
                    $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                    if ($stmt->execute([$new_filename, $product_id])) {
                        $message = "Image berhasil diupload dan diupdate!";
                        $type = "success";
                    } else {
                        $message = "Error updating database.";
                        $type = "error";
                    }
                } else {
                    $message = "Error uploading file.";
                    $type = "error";
                }
            } else {
                $message = "Hanya file JPG, JPEG, PNG, GIF & WEBP yang diizinkan.";
                $type = "error";
            }
        } else {
            $message = "File terlalu besar. Maksimal 5MB.";
            $type = "error";
        }
    } else {
        $message = "File bukan image.";
        $type = "error";
    }
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Upload Image Produk</title>
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
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="../pages/home.php"><i class="fas fa-home"></i> Lihat Website</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-header-custom">
                        <h4 class="mb-1" style="font-size: 1.5rem; font-weight: 700;">
                            <i class="fas fa-upload me-2"></i> Upload Image Produk
                        </h4>
                        <p class="mb-0 opacity-90" style="font-size: 1rem;">Kelola gambar produk untuk meningkatkan daya tarik visual</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Upload Form -->
            <div class="col-md-6 mb-4">
                <div class="admin-card h-100">
                    <div class="p-4">
                        <div class="icon-feature mb-3">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h5 class="card-title mb-3">Upload Image Baru</h5>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="product_id" class="form-label fw-semibold">Pilih Produk:</label>
                                <select class="form-select" name="product_id" required style="border-radius: 10px;">
                                    <option value="">-- Pilih Produk --</option>
                                    <?php foreach ($products as $product): ?>
                                        <?php 
                                        $hasImage = $product['image'] && file_exists("../assets/img/" . $product['image']);
                                        $imageStatus = $hasImage ? '(Ada gambar)' : '(Belum ada gambar)';
                                        ?>
                                        <option value="<?= $product['id'] ?>">
                                            <?= htmlspecialchars($product['name']) ?> 
                                            <?= $imageStatus ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="image" class="form-label fw-semibold">Upload Image:</label>
                                <input type="file" class="form-control" name="image" accept="image/*" required style="border-radius: 10px;">
                                <div class="form-text mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Format: JPG, JPEG, PNG, GIF, WEBP. Maksimal 5MB.
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn">
                                    <i class="fas fa-upload me-2"></i> Upload Image
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary" style="border-radius: 15px;">
                                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="col-md-6 mb-4">
                <div class="admin-card h-100">
                    <div class="p-4">
                        <div class="icon-feature mb-3">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h5 class="card-title mb-3">Preview & Info</h5>
                        
                        <div class="bg-light rounded-3 p-4 text-center" style="min-height: 200px; border: 2px dashed #dee2e6;">
                            <i class="fas fa-image fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Preview gambar akan muncul di sini setelah dipilih</p>
                        </div>
                        
                        <div class="mt-3">
                            <h6 class="fw-semibold mb-2">Panduan Upload:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i> Resolusi optimal: 800x600px</li>
                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i> Rasio aspek 4:3 direkomendasikan</li>
                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i> Format terbaik: JPG atau PNG</li>
                                <li class="mb-1"><i class="fas fa-check text-success me-2"></i> Ukuran file maksimal 5MB</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Products Gallery -->
        <div class="row">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-header-custom">
                        <h4 class="mb-1" style="font-size: 1.25rem; font-weight: 700;">
                            <i class="fas fa-images me-2"></i> Gallery Produk
                        </h4>
                        <p class="mb-0 opacity-90">Semua produk dan gambar yang tersedia saat ini</p>
                    </div>
                    <div class="p-4">
                        <div class="row g-4">
                            <?php foreach ($products as $product): ?>
                                <?php 
                                $imageExists = $product['image'] && file_exists("../assets/img/" . $product['image']);
                                ?>
                                <div class="col-md-4 col-lg-3">
                                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                                        <?php if ($imageExists): ?>
                                            <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" 
                                                 class="card-img-top" style="height: 180px; object-fit: cover;" 
                                                 alt="<?= htmlspecialchars($product['name']) ?>">
                                        <?php else: ?>
                                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                                 style="height: 180px;">
                                                <i class="fas fa-image fa-2x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-2 fw-semibold"><?= htmlspecialchars($product['name']) ?></h6>
                                            <p class="card-text small text-muted mb-2">
                                                <i class="fas fa-tag me-1"></i>
                                                Status: <?= $imageExists ? '<span class="text-success">Ada gambar</span>' : '<span class="text-warning">Belum ada gambar</span>' ?>
                                            </p>
                                            <?php if ($product['image'] && !$imageExists): ?>
                                                <p class="card-text small text-danger mb-0">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    File database: <?= htmlspecialchars($product['image']) ?> (tidak ditemukan)
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.querySelector('input[name="image"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.querySelector('.bg-light.rounded-3');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = `
                        <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px; max-width: 100%;" alt="Preview">
                        <p class="text-muted mt-2 mb-0"><small>Preview: ${file.name}</small></p>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.innerHTML = `
                    <i class="fas fa-image fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">Preview gambar akan muncul di sini setelah dipilih</p>
                `;
            }
        });
        
        // Product selection info
        document.querySelector('select[name="product_id"]').addEventListener('change', function(e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            if (selectedOption.value) {
                console.log('Product selected:', selectedOption.text);
            }
        });
    </script>
</body>
</html>
