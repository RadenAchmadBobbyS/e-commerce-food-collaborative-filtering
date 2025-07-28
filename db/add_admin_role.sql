-- Menambahkan kolom role ke tabel users
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';

-- Membuat user admin default
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@foodrec.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password default: password

-- Update user yang sudah ada menjadi user biasa
UPDATE users SET role = 'user' WHERE role IS NULL;
