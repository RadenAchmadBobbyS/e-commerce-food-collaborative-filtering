-- Script untuk memastikan database dalam kondisi yang benar

-- 1. Pastikan kolom role ada di tabel users
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user';

-- 2. Pastikan tabel ratings ada dan sesuai struktur
CREATE TABLE IF NOT EXISTS ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  rating INT NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_product (user_id, product_id)
);

-- 3. Pastikan ada admin default
INSERT IGNORE INTO users (username, email, password, role) VALUES 
('admin', 'admin@foodrec.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 4. Update users yang belum punya role
UPDATE users SET role = 'user' WHERE role IS NULL;

-- 5. Pastikan ada data products dengan price
UPDATE products SET price = 15000 WHERE price IS NULL OR price = 0;
