<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - FoodRec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .admin-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="admin-login-card">
                    <div class="admin-header">
                        <i class="fas fa-shield-alt fa-3x mb-3"></i>
                        <h3>Admin Panel</h3>
                        <p class="mb-0">Silakan login untuk akses admin</p>
                    </div>
                    
                    <div class="p-4">
                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-<?= $_GET['type'] ?? 'info' ?>" role="alert">
                                <?= htmlspecialchars($_GET['message']) ?>
                            </div>
                        <?php endif; ?>

                        <form action="../process/admin_login_process.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email Admin
                                </label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 mb-3">
                                <i class="fas fa-sign-in-alt"></i> Login Admin
                            </button>
                        </form>

                        <div class="text-center">
                            <a href="../pages/home.php" class="text-muted">
                                <i class="fas fa-arrow-left"></i> Kembali ke Home
                            </a>
                        </div>

                        <hr>
                        <div class="text-center">
                            <small class="text-muted">
                                <strong>Demo Admin:</strong><br>
                                Email: admin@foodrec.com<br>
                                Password: password
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
