# 📊 MAE (Mean Absolute Error) Evaluation System

Sistem evaluasi untuk mengukur akurasi algoritma Collaborative Filtering menggunakan metrik Mean Absolute Error (MAE) dan metrik evaluasi lainnya.

## 🎯 Fitur Evaluasi

### 1. Mean Absolute Error (MAE)

- Mengukur rata-rata kesalahan absolut antara rating prediksi dan rating aktual
- Formula: `MAE = Σ|predicted - actual| / n`
- Range: 0-4 (semakin rendah semakin baik)
- Ideal: MAE < 1.0

### 2. Root Mean Square Error (RMSE)

- Mengukur akar dari rata-rata kuadrat kesalahan
- Lebih sensitif terhadap outlier dibanding MAE
- Formula: `RMSE = √(Σ(predicted - actual)² / n)`
- Range: 0-4 (semakin rendah semakin baik)

### 3. Precision & Recall

- **Precision**: Akurasi rekomendasi yang diberikan
- **Recall**: Seberapa lengkap sistem menemukan item relevan
- **F1-Score**: Harmonic mean dari precision dan recall
- Range: 0-1 (semakin tinggi semakin baik)

## 📁 File Structure

```
functions/
├── evaluation.php          # Core evaluation functions
└── cf.php                 # Collaborative filtering functions

pages/
└── evaluation.php         # Web interface untuk evaluasi

admin/
├── demo_data_generator.php # Generate sample data untuk testing
└── test_mae.php           # Quick test script untuk MAE
```

## 🚀 Cara Penggunaan

### 1. Akses Web Interface

```
http://localhost/e-commerce-food-collaborative-filtering/pages/evaluation.php
```

### 2. Generate Sample Data (Opsional)

```
http://localhost/e-commerce-food-collaborative-filtering/admin/demo_data_generator.php
```

### 3. Quick Test

```
http://localhost/e-commerce-food-collaborative-filtering/admin/test_mae.php
```

## 📊 Interpretasi Hasil

### MAE Score Interpretation

| MAE Score | Kategori  | Keterangan                |
| --------- | --------- | ------------------------- |
| 0.0 - 0.8 | Excellent | Prediksi sangat akurat    |
| 0.8 - 1.2 | Good      | Prediksi cukup akurat     |
| 1.2 - 1.8 | Fair      | Masih dapat diterima      |
| 1.8+      | Poor      | Perlu perbaikan algoritma |

### Coverage Interpretation

| Coverage | Kategori | Keterangan                                   |
| -------- | -------- | -------------------------------------------- |
| 80%+     | High     | Sistem dapat memprediksi sebagian besar data |
| 50-80%   | Medium   | Coverage cukup baik                          |
| <50%     | Low      | Perlu peningkatan algoritma                  |

## 🔧 Technical Details

### Evaluation Process

1. **Data Splitting**: Membagi data rating menjadi training (80%) dan testing (20%)
2. **Model Training**: Menggunakan data training untuk membangun model collaborative filtering
3. **Prediction**: Memprediksi rating untuk data testing
4. **Evaluation**: Menghitung metrik evaluasi (MAE, RMSE, Precision, Recall)

### Algorithm Used

- **Similarity Metric**: Pearson Correlation Coefficient
- **Prediction Method**: Weighted average based on user similarity
- **Fallback Strategy**: User average rating jika tidak ada neighbor yang similar

## 📋 Prerequisites

### Data Requirements

- Minimal 10 rating dalam database
- Minimal 3 user dengan rating
- Minimal 3 produk yang di-rating

### Optimal Conditions

- 50+ rating untuk evaluasi yang stabil
- 10+ user aktif
- 10+ produk dengan rating

## 🔍 Sample Data Generator

Tool untuk generate sample data testing:

### Features

- Membuat test users dengan pattern `testuser1`, `testuser2`, dll.
- Generate rating realistis (bias ke rating tinggi)
- Komentar random untuk beberapa rating
- Dapat di-clear kapan saja

### Usage

```php
// Generate 20 users, 15 products, 8 ratings per user
generateSampleRatings($pdo, 20, 15, 8);

// Clear semua sample data
clearSampleData($pdo);
```

## 🐛 Troubleshooting

### Common Issues

#### "Insufficient data for evaluation"

- **Cause**: Data rating kurang dari 10
- **Solution**: Generate sample data atau tambah rating manual

#### "No predictions could be made"

- **Cause**: Algoritma tidak dapat membuat prediksi
- **Solution**: Pastikan ada overlap rating antar user

#### "No users with sufficient ratings found"

- **Cause**: User tidak memiliki cukup rating (min 5)
- **Solution**: Generate lebih banyak sample data

### Performance Tips

- Gunakan database indexing pada kolom `user_id` dan `product_id` di tabel `ratings`
- Limit jumlah user yang dievaluasi untuk dataset besar
- Cache hasil similarity calculation jika memungkinkan

## 📈 Expected Results

### Baseline Performance

Dengan Pearson Correlation dan data yang cukup:

- **MAE**: 0.8 - 1.2 (good range)
- **Coverage**: 60-80%
- **Precision**: 0.3 - 0.6
- **Recall**: 0.2 - 0.5

### Factors Affecting Performance

- **Data Sparsity**: Semakin sparse, semakin sulit prediksi
- **User Overlap**: Semakin banyak overlap rating, semakin baik
- **Rating Distribution**: Rating yang terdistribusi normal lebih baik

## 🔄 Improvement Opportunities

### Algorithm Enhancements

1. **Matrix Factorization**: SVD, NMF untuk handle sparsity
2. **Deep Learning**: Neural collaborative filtering
3. **Hybrid Methods**: Kombinasi collaborative + content-based
4. **Advanced Similarity**: Cosine similarity, adjusted cosine

### Evaluation Enhancements

1. **Cross-Validation**: K-fold validation untuk hasil yang lebih stabil
2. **Additional Metrics**: NDCG, MRR, Diversity metrics
3. **Temporal Evaluation**: Mempertimbangkan time-based splitting
4. **Statistical Tests**: Significance testing untuk perbandingan algoritma

## 📚 References

- Ricci, F., Rokach, L., & Shapira, B. (2015). Recommender Systems Handbook
- Aggarwal, C. C. (2016). Recommender Systems: The Textbook
- Herlocker, J. L., et al. (2004). Evaluating collaborative filtering recommender systems

## 👨‍💻 Implementation Notes

### Code Quality

- Full error handling dan logging
- Parameter validation
- Memory efficient untuk dataset besar
- Clean, documented code

### Security

- Prepared statements untuk SQL queries
- Input sanitization
- No direct database credentials exposure

### Extensibility

- Modular design untuk mudah extend
- Configuration-based parameters
- Plugin-ready architecture
