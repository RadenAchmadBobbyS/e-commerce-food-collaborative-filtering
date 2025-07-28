# 🍽️ Sistem Rekomendasi Makanan

Aplikasi web untuk rekomendasi makanan berbasis collaborative filtering dengan tampilan modern dan klasik.

## ✨ Fitur Utama

### 🔐 Sistem Autentikasi

- **Registrasi pengguna** dengan validasi lengkap
- **Login/Logout** yang aman dengan session management
- **Proteksi halaman** untuk fitur yang memerlukan login
- **Validasi email** dan password strength

### 🍽️ Manajemen Makanan

- **Daftar makanan** dengan gambar dan deskripsi
- **Rating sistem** (1-5 bintang)
- **Detail produk** dengan informasi lengkap
- **Tampilan rating rata-rata** dan jumlah reviewer

### ⭐ Sistem Rekomendasi

- **Collaborative Filtering** menggunakan algoritma similarity
- **Rekomendasi personal** berdasarkan preferensi user
- **Real-time updates** setelah user memberikan rating

### 🎨 Tampilan Modern

- **Design responsif** untuk desktop dan mobile
- **Tema klasik** dengan gradient dan shadows
- **Animasi smooth** untuk interaksi user
- **Icon emoji** untuk user experience yang menyenangkan

## 🚀 Instalasi

### Prasyarat

- XAMPP/WAMP/LAMP dengan PHP 7.4+
- MySQL 5.7+
- Web browser modern

### Langkah Instalasi

1. **Clone/Download project** ke folder htdocs XAMPP

   ```bash
   cd C:\xampp\htdocs\
   # Letakkan folder rekomendasi_makanan di sini
   ```

2. **Setup Database**

   - Buka phpMyAdmin (http://localhost/phpmyadmin)
   - Import file `db/rekomendasi_makanan.sql`
   - Atau buat database manual dengan tabel:
     - `users` (id, name, email, password, created_at, last_login)
     - `products` (id, name, description, image, created_at)
     - `ratings` (id, user_id, product_id, rating, created_at, updated_at)

3. **Konfigurasi Database**

   - Edit file `config/db.php`
   - Sesuaikan username, password, dan nama database

4. **Akses Aplikasi**
   - Buka browser dan kunjungi: http://localhost/rekomendasi_makanan

## 📁 Struktur Project

```
rekomendasi_makanan/
├── assets/
│   ├── css/
│   │   └── style.css          # Styling modern & responsif
│   └── img/                   # Folder gambar produk
├── config/
│   └── db.php                 # Konfigurasi database
├── db/
│   └── rekomendasi_makanan.sql # Schema database
├── functions/
│   └── cf.php                 # Algoritma collaborative filtering
├── includes/
│   └── auth_check.php         # Helper autentikasi
├── layouts/
│   ├── header.php             # Header dengan navigation
│   └── footer.php             # Footer dengan script JS
├── pages/
│   ├── home.php               # Halaman utama
│   ├── login.php              # Halaman login
│   ├── register.php           # Halaman registrasi
│   ├── product.php            # Detail produk & rating
│   └── rekomendasi.php        # Halaman rekomendasi
├── process/
│   ├── login_process.php      # Proses login
│   ├── register_process.php   # Proses registrasi
│   ├── logout_process.php     # Proses logout
│   └── rating_process.php     # Proses pemberian rating
└── index.php                  # Entry point
```

## 🛡️ Fitur Keamanan

### Proteksi Session

- Session regeneration setiap login
- Automatic logout handling
- Session timeout management

### Validasi Input

- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (htmlspecialchars)
- ✅ CSRF protection (form validation)
- ✅ Password hashing (bcrypt)

### Access Control

- ✅ Login required untuk rating
- ✅ Login required untuk rekomendasi
- ✅ Redirect protection
- ✅ Role-based access (siap untuk admin panel)

## 📱 Responsive Design

### Desktop (1200px+)

- Grid layout untuk produk
- Sidebar navigation
- Full width header

### Tablet (768px - 1199px)

- Adaptive grid layout
- Collapsible navigation
- Touch-friendly buttons

### Mobile (< 768px)

- Single column layout
- Mobile-first navigation
- Optimized forms

## 🎯 Algoritma Rekomendasi

Menggunakan **User-Based Collaborative Filtering**:

1. **Similarity Calculation**: Menghitung kesamaan antar user berdasarkan rating
2. **Neighbor Selection**: Memilih user dengan kesamaan tertinggi
3. **Prediction**: Memprediksi rating untuk item yang belum dirating
4. **Recommendation**: Mengurutkan item berdasarkan prediksi rating

## 🔧 Kustomisasi

### Mengubah Tema

Edit file `assets/css/style.css`:

- Ubah gradient colors di variabel CSS
- Sesuaikan font family
- Modifikasi spacing dan sizing

### Menambah Fitur

1. **Admin Panel**: Tambah role management
2. **Categories**: Implementasi kategori makanan
3. **Reviews**: Tambah sistem review text
4. **Wishlist**: Fitur save produk favorit

## 🐛 Troubleshooting

### Database Connection Error

1. Pastikan MySQL service berjalan
2. Cek kredensial di `config/db.php`
3. Pastikan database sudah dibuat

### Session Issues

1. Cek permission folder session PHP
2. Pastikan cookies enabled di browser
3. Clear browser cache

### Layout Broken

1. Pastikan file CSS ter-load
2. Cek console browser untuk error
3. Validate HTML structure

## 📈 Performance Tips

### Database Optimization

- Add index pada foreign keys
- Optimize query dengan EXPLAIN
- Use connection pooling

### Caching

- Implement Redis/Memcached
- Cache recommendation results
- Use browser caching for assets

### Security Updates

- Regular PHP/MySQL updates
- Monitor error logs
- Implement rate limiting

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 License

Project ini dibuat untuk keperluan edukasi dan pembelajaran.

## 👨‍💻 Developer

Dibuat dengan ❤️ menggunakan PHP, MySQL, dan vanilla JavaScript.

---

**Happy Coding! 🚀**
