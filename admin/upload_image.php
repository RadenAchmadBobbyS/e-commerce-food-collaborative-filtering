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
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="admin-card">
                    <div class="card-header bg-danger text-white">
                        <h3><i class="fas fa-upload"></i> Upload Image Produk</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?>" role="alert">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="product_id" class="form-label">Pilih Produk:</label>
                                <select class="form-select" name="product_id" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= $product['id'] ?>">
                                            <?= htmlspecialchars($product['name']) ?> 
                                            (Current: <?= $product['image'] ?: 'No image' ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Upload Image:</label>
                                <input type="file" class="form-control" name="image" accept="image/*" required>
                                <div class="form-text">Format: JPG, JPEG, PNG, GIF, WEBP. Maksimal 5MB.</div>
                            </div>

                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-upload"></i> Upload Image
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Display current products with images -->
                <div class="admin-card mt-4">
                    <div class="card-header bg-info text-white">
                        <h4><i class="fas fa-images"></i> Produk & Image Saat Ini</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($products as $product): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <?php if ($product['image'] && file_exists("../assets/img/" . $product['image'])): ?>
                                            <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" 
                                                 class="card-img-top" style="height: 200px; object-fit: cover;" 
                                                 alt="<?= htmlspecialchars($product['name']) ?>">
                                        <?php else: ?>
                                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                                 style="height: 200px;">
                                                <span class="text-muted">No Image</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($product['name']) ?></h6>
                                            <small class="text-muted">
                                                Image: <?= $product['image'] ?: 'Tidak ada' ?>
                                            </small>
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
</body>
</html>
