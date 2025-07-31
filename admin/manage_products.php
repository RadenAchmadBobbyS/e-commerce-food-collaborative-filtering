<?php
session_start();

// Cek apakah user adalah admin
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: login.php?message=Akses ditolak. Silakan login sebagai admin&type=error');
    exit;
}

require '../config/db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_category':
                $category_name = trim($_POST['category_name']);
                if (!empty($category_name)) {
                    // Check if category already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
                    $stmt->execute([$category_name]);
                    if ($stmt->fetchColumn() == 0) {
                        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                        if ($stmt->execute([$category_name])) {
                            $success_message = "Kategori berhasil ditambahkan!";
                        } else {
                            $error_message = "Gagal menambahkan kategori!";
                        }
                    } else {
                        $error_message = "Kategori sudah ada!";
                    }
                } else {
                    $error_message = "Nama kategori harus diisi!";
                }
                break;
                
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                
                if (!empty($name) && !empty($description) && $price > 0) {
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$name, $description, $price, $category_id ?: null])) {
                        $success_message = "Produk berhasil ditambahkan!";
                    } else {
                        $error_message = "Gagal menambahkan produk!";
                    }
                } else {
                    $error_message = "Nama, deskripsi, dan harga harus diisi dengan benar!";
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                
                if (!empty($name) && !empty($description) && $price > 0) {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ? WHERE id = ?");
                    if ($stmt->execute([$name, $description, $price, $category_id ?: null, $id])) {
                        $success_message = "Produk berhasil diperbarui!";
                    } else {
                        $error_message = "Gagal memperbarui produk!";
                    }
                } else {
                    $error_message = "Nama, deskripsi, dan harga harus diisi dengan benar!";
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $force_delete = isset($_POST['force_delete']) && $_POST['force_delete'] === 'true';
                
                // Check if product has ratings first
                $stmt = $pdo->prepare("SELECT COUNT(*) as rating_count FROM ratings WHERE product_id = ?");
                $stmt->execute([$id]);
                $rating_count = $stmt->fetch()['rating_count'];
                
                if ($rating_count > 0 && !$force_delete) {
                    $error_message = "Produk memiliki {$rating_count} rating. Gunakan opsi 'Hapus Paksa' untuk menghapus produk beserta semua ratingnya.";
                } else {
                    try {
                        $pdo->beginTransaction();
                        
                        // Delete ratings first if any exist
                        if ($rating_count > 0) {
                            $stmt = $pdo->prepare("DELETE FROM ratings WHERE product_id = ?");
                            $stmt->execute([$id]);
                        }
                        
                        // Then delete the product
                        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                        if ($stmt->execute([$id])) {
                            $pdo->commit();
                            if ($rating_count > 0) {
                                $success_message = "Produk dan {$rating_count} rating berhasil dihapus!";
                            } else {
                                $success_message = "Produk berhasil dihapus!";
                            }
                        } else {
                            $pdo->rollback();
                            $error_message = "Gagal menghapus produk!";
                        }
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $error_message = "Terjadi kesalahan saat menghapus produk!";
                    }
                }
                break;
        }
    }
}

// Get all products with rating stats
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name,
           COUNT(r.id) as rating_count,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           CASE WHEN p.image IS NOT NULL AND p.image != '' THEN 'Ada' ELSE 'Tidak ada' END as image_status
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN ratings r ON p.id = r.product_id 
    GROUP BY p.id 
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();

// Get categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - WRKP Admin</title>
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

    <div class="container my-5">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="admin-card">
                    <div class="card-header-custom">
                        <h4 class="mb-0">
                            <i class="fas fa-utensils"></i> Manajemen Produk
                        </h4>
                        <small class="opacity-75">Kelola semua produk makanan dalam sistem</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add Product Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="admin-card">
                    <div class="p-4">
                        <h5 class="mb-3"><i class="fas fa-plus"></i> Tambah Produk Baru</h5>
                        <form method="POST" id="addProductForm">
                            <input type="hidden" name="action" value="add">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="price" class="form-label">Harga</label>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="category_id" class="form-label">Kategori</label>
                                    <div class="input-group">
                                        <select class="form-control" id="category_id" name="category_id">
                                            <option value="">Pilih Kategori</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-secondary" onclick="openAddCategoryModal()" title="Tambah Kategori Baru">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn">
                                        <i class="fas fa-plus me-2"></i> Tambah Produk
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products List -->
        <div class="row">
            <div class="col-12">
                <div class="admin-card">
                    <div class="p-4">
                        <h5 class="mb-3"><i class="fas fa-list"></i> Daftar Produk (<?= count($products) ?> produk)</h5>
                        
                        <?php if (empty($products)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada produk yang ditambahkan</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Produk</th>
                                            <th>Kategori</th>
                                            <th>Harga</th>
                                            <th>Rating</th>
                                            <th>Gambar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><?= $product['id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($product['name']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($product['category_name'])): ?>
                                                        <span class="badge bg-info"><?= htmlspecialchars($product['category_name']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>Rp <?= number_format($product['price'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($product['rating_count'] > 0): ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-star"></i> <?= number_format($product['avg_rating'], 1) ?>
                                                            (<?= $product['rating_count'] ?>)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Belum ada rating</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($product['image_status'] === 'Ada'): ?>
                                                        <span class="badge bg-success">Ada gambar</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Tidak ada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm" onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>', <?= $product['rating_count'] ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editProductForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editName" class="form-label">Nama Produk</label>
                                <input type="text" class="form-control" id="editName" name="name" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="editPrice" class="form-label">Harga</label>
                                <input type="number" class="form-control" id="editPrice" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="editCategoryId" class="form-label">Kategori</label>
                                <select class="form-control" id="editCategoryId" name="category_id">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="editDescription" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="editDescription" name="description" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn">
                            <i class="fas fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage"></p>
                    <div id="forceDeleteOption" style="display: none;">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Peringatan:</strong> Menghapus produk akan menghapus semua rating yang terkait!
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmForceDelete">
                            <label class="form-check-label" for="confirmForceDelete">
                                Saya memahami bahwa semua rating akan ikut terhapus
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <div id="deleteButtons">
                        <form method="POST" style="display: inline;" id="normalDeleteForm">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" id="deleteId">
                            <button type="submit" class="btn btn-danger" id="normalDeleteBtn">
                                <i class="fas fa-trash me-2"></i> Hapus
                            </button>
                        </form>
                        <form method="POST" style="display: none;" id="forceDeleteForm">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" id="forceDeleteId">
                            <input type="hidden" name="force_delete" value="true">
                            <button type="submit" class="btn btn-danger" id="forceDeleteBtn" disabled>
                                <i class="fas fa-exclamation-triangle me-2"></i> Hapus Paksa
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="addCategoryForm">
                    <input type="hidden" name="action" value="add_category">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" id="categoryName" name="category_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn">
                            <i class="fas fa-save me-2"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openAddCategoryModal() {
            new bootstrap.Modal(document.getElementById('addCategoryModal')).show();
        }
        
        function editProduct(product) {
            document.getElementById('editId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editCategoryId').value = product.category_id || '';
            document.getElementById('editDescription').value = product.description;
            
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        }

        function deleteProduct(id, name, ratingCount) {
            document.getElementById('deleteId').value = id;
            document.getElementById('forceDeleteId').value = id;
            
            if (ratingCount > 0) {
                document.getElementById('deleteMessage').innerHTML = 
                    `<strong>Produk "${name}" memiliki ${ratingCount} rating</strong><br>` +
                    `Anda dapat menghapus produk beserta semua ratingnya dengan menggunakan opsi "Hapus Paksa".`;
                
                // Show force delete option
                document.getElementById('forceDeleteOption').style.display = 'block';
                document.getElementById('normalDeleteForm').style.display = 'none';
                document.getElementById('forceDeleteForm').style.display = 'inline';
                
                // Reset checkbox
                document.getElementById('confirmForceDelete').checked = false;
                document.getElementById('forceDeleteBtn').disabled = true;
            } else {
                document.getElementById('deleteMessage').innerHTML = 
                    `Apakah Anda yakin ingin menghapus produk <strong>"${name}"</strong>?<br>` +
                    `<small class="text-muted">Tindakan ini tidak dapat dibatalkan.</small>`;
                
                // Hide force delete option
                document.getElementById('forceDeleteOption').style.display = 'none';
                document.getElementById('normalDeleteForm').style.display = 'inline';
                document.getElementById('forceDeleteForm').style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
        }

        // Handle force delete checkbox
        document.getElementById('confirmForceDelete').addEventListener('change', function() {
            document.getElementById('forceDeleteBtn').disabled = !this.checked;
        });

        // Form validation
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const description = document.getElementById('description').value.trim();
            
            if (!name || !description || price <= 0) {
                e.preventDefault();
                alert('Mohon isi semua field dengan benar!');
            }
        });

        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            const name = document.getElementById('editName').value.trim();
            const price = parseFloat(document.getElementById('editPrice').value);
            const description = document.getElementById('editDescription').value.trim();
            
            if (!name || !description || price <= 0) {
                e.preventDefault();
                alert('Mohon isi semua field dengan benar!');
            }
        });
    </script>
</body>
</html>
