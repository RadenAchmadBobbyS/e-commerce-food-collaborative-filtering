CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL
);

-- Membuat tabel categories jika belum ada
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Membuat tabel products jika belum ada
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  product_id INT,
  rating INT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Data dummy
INSERT INTO users (username, email, password) VALUES ('user1', 'user1@example.com', MD5('12345'));

-- Menambahkan data kategori jika belum ada
INSERT INTO categories (id, name) VALUES
    (1, 'Makanan'),
    (3, 'Minuman Dingin'),
    (4, 'Minuman Panas')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Menambahkan data ke tabel products dengan category_id yang sesuai
INSERT INTO products (name, image, description, category_id) VALUES
    ('Nasi Goreng', 'nasigoreng.jpg', 'Makanan khas Indonesia', 1),
    ('Bakso', 'bakso.jpg', 'Bakso sapi kenyal', 1),
    ('Sate Ayam', 'sate.jpg', 'Sate dengan bumbu kacang', 1),
    ('Mie Goreng', 'mie.jpg', 'Mie goreng dengan tambahan sayuran segar', 1),
    ('Es Teh Manis', 'esteh.jpg', 'Teh manis dingin yang menyegarkan', 3),
    ('Es Jeruk', 'esjeruk.jpg', 'Jeruk segar dengan es batu', 3),
    ('Kopi Panas', 'kopi.jpg', 'Kopi hitam panas dengan aroma khas', 4),
    ('Teh Panas', 'teh.jpg', 'Teh panas dengan rasa yang menenangkan', 4);

-- Menambahkan 10 data dummy untuk minuman tanpa kolom id
INSERT INTO products (name, category_id, price, description) VALUES
    ('Es Kopi Susu', 3, 15000, 'Kopi susu dingin dengan gula aren'),
    ('Es Coklat', 3, 12000, 'Coklat dingin yang manis dan menyegarkan'),
    ('Es Lemon Tea', 3, 10000, 'Teh lemon dingin dengan rasa segar'),
    ('Es Soda Gembira', 3, 13000, 'Soda dengan susu kental manis dan sirup'),
    ('Es Alpukat', 3, 17000, 'Jus alpukat dengan tambahan coklat'),
    ('Kopi Latte Panas', 4, 20000, 'Kopi latte panas dengan foam susu'),
    ('Cappuccino Panas', 4, 22000, 'Kopi cappuccino panas dengan taburan coklat'),
    ('Teh Hijau Panas', 4, 15000, 'Teh hijau panas yang menenangkan'),
    ('Wedang Jahe', 4, 12000, 'Minuman jahe panas khas Indonesia'),
    ('Susu Jahe', 4, 14000, 'Susu panas dengan jahe segar');

-- Menambahkan 10 data dummy untuk makanan
INSERT INTO products (name, category_id, price, description) VALUES
    ('Ayam Goreng', 1, 20000, 'Ayam goreng dengan bumbu rempah khas'),
    ('Sate Ayam', 1, 25000, 'Sate ayam dengan bumbu kacang'),
    ('Bakso', 1, 15000, 'Bakso sapi dengan kuah kaldu gurih'),
    ('Soto Ayam', 1, 18000, 'Soto ayam dengan kuah kuning'),
    ('Nasi Uduk', 1, 12000, 'Nasi uduk dengan lauk pauk lengkap'),
    ('Pecel Lele', 1, 17000, 'Lele goreng dengan sambal khas'),
    ('Gado-Gado', 1, 15000, 'Salad sayur dengan bumbu kacang'),
    ('Rendang', 1, 30000, 'Daging sapi dengan bumbu rendang khas Padang'),
    ('Nasi Padang', 1, 25000, 'Nasi dengan lauk khas Padang'),
    ('Mie Ayam', 1, 15000, 'Mie ayam dengan topping ayam kecap');
