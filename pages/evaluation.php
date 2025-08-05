<?php
session_start();
require '../config/db.php';
require '../functions/evaluation.php';

// Check if user is logged in (optional, can be accessed by guests)
include '../layouts/header.php';

// Handle evaluation request
$evaluationResult = null;
$selectedMetric = 'mae';

if (isset($_GET['evaluate']) || isset($_POST['evaluate'])) {
    $selectedMetric = $_POST['metric'] ?? $_GET['metric'] ?? 'mae';
    
    switch ($selectedMetric) {
        case 'mae':
            $evaluationResult = calculateMAE($pdo);
            break;
        case 'rmse':
            $evaluationResult = calculateRMSE($pdo);
            break;
        case 'precision_recall':
            $evaluationResult = calculatePrecisionRecall($pdo);
            break;
        case 'comprehensive':
            $evaluationResult = comprehensiveEvaluation($pdo);
            break;
        default:
            $evaluationResult = calculateMAE($pdo);
    }
}

// Get system statistics
try {
    $stats = [];
    
    // Total ratings
    $stmt = $pdo->query("SELECT COUNT(*) as total_ratings FROM ratings");
    $stats['total_ratings'] = $stmt->fetch()['total_ratings'];
    
    // Total users with ratings
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as total_users FROM ratings");
    $stats['total_users'] = $stmt->fetch()['total_users'];
    
    // Total products with ratings
    $stmt = $pdo->query("SELECT COUNT(DISTINCT product_id) as total_products FROM ratings");
    $stats['total_products'] = $stmt->fetch()['total_products'];
    
    // Average rating
    $stmt = $pdo->query("SELECT AVG(rating) as avg_rating FROM ratings");
    $stats['avg_rating'] = round($stmt->fetch()['avg_rating'], 2);
    
    // Data sparsity
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $stmt->fetch()['total_users'];
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $totalProducts = $stmt->fetch()['total_products'];
    
    $possibleRatings = $totalUsers * $totalProducts;
    $stats['data_sparsity'] = $possibleRatings > 0 ? 
        round((1 - ($stats['total_ratings'] / $possibleRatings)) * 100, 2) : 0;
    
} catch (Exception $e) {
    $stats = ['error' => 'Could not load statistics: ' . $e->getMessage()];
}
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <!-- Page Header -->
    <div class="evaluation-header" style="text-align: center; margin-bottom: 3rem;">
        <h1 style="color: #2c3e50; margin-bottom: 1rem;">
            <i class="fas fa-chart-line"></i> Evaluasi Sistem Rekomendasi
        </h1>
        <p style="color: #7f8c8d; font-size: 1.1rem;">
            Analisis performa algoritma Collaborative Filtering menggunakan metrik MAE, RMSE, dan lainnya
        </p>
    </div>

    <!-- System Statistics -->
    <div style="background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <h2 style="color: #34495e; margin-bottom: 1.5rem;">
            <i class="fas fa-database"></i> Statistik Sistem
        </h2>
        
        <?php if (!isset($stats['error'])): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 10px;">
                <div style="font-size: 2rem; font-weight: bold; color: #3498db;"><?= number_format($stats['total_ratings']) ?></div>
                <div style="color: #7f8c8d;">Total Rating</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 10px;">
                <div style="font-size: 2rem; font-weight: bold; color: #2ecc71;"><?= $stats['total_users'] ?></div>
                <div style="color: #7f8c8d;">User Aktif</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 10px;">
                <div style="font-size: 2rem; font-weight: bold; color: #e74c3c;"><?= $stats['total_products'] ?></div>
                <div style="color: #7f8c8d;">Produk Dirating</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 10px;">
                <div style="font-size: 2rem; font-weight: bold; color: #f39c12;"><?= $stats['avg_rating'] ?>/5</div>
                <div style="color: #7f8c8d;">Rata-rata Rating</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 10px;">
                <div style="font-size: 2rem; font-weight: bold; color: #9b59b6;"><?= $stats['data_sparsity'] ?>%</div>
                <div style="color: #7f8c8d;">Data Sparsity</div>
            </div>
        </div>
        <?php else: ?>
        <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($stats['error']) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Evaluation Controls -->
    <div style="background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <h2 style="color: #34495e; margin-bottom: 1.5rem;">
            <i class="fas fa-cogs"></i> Jalankan Evaluasi
        </h2>
        
        <form method="POST" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; color: #555; font-weight: 500;">Pilih Metrik:</label>
                <select name="metric" style="padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                    <option value="mae" <?= $selectedMetric === 'mae' ? 'selected' : '' ?>>MAE (Mean Absolute Error)</option>
                    <option value="rmse" <?= $selectedMetric === 'rmse' ? 'selected' : '' ?>>RMSE (Root Mean Square Error)</option>
                    <option value="precision_recall" <?= $selectedMetric === 'precision_recall' ? 'selected' : '' ?>>Precision & Recall</option>
                    <option value="comprehensive" <?= $selectedMetric === 'comprehensive' ? 'selected' : '' ?>>Evaluasi Komprehensif</option>
                </select>
            </div>
            
            <button type="submit" name="evaluate" value="1" 
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 500;">
                <i class="fas fa-play"></i> Jalankan Evaluasi
            </button>
        </form>
    </div>

    <!-- Evaluation Results -->
    <?php if ($evaluationResult): ?>
    <div style="background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <h2 style="color: #34495e; margin-bottom: 1.5rem;">
            <i class="fas fa-chart-bar"></i> Hasil Evaluasi
        </h2>
        
        <?php if ($selectedMetric === 'comprehensive'): ?>
            <!-- Comprehensive Evaluation Results -->
            <div style="display: grid; gap: 2rem;">
                <!-- MAE Results -->
                <?php if (isset($evaluationResult['mae_evaluation'])): ?>
                <div style="border: 2px solid #3498db; border-radius: 10px; padding: 1.5rem;">
                    <h3 style="color: #3498db; margin-bottom: 1rem;">📊 Mean Absolute Error (MAE)</h3>
                    <?php $mae = $evaluationResult['mae_evaluation']; ?>
                    <?php if ($mae['mae'] !== null): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                            <div style="text-align: center; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                <div style="font-size: 1.8rem; font-weight: bold; color: #3498db;"><?= $mae['mae'] ?></div>
                                <div style="color: #7f8c8d;">MAE Score</div>
                            </div>
                            <div style="text-align: center; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                <div style="font-size: 1.8rem; font-weight: bold; color: #2ecc71;"><?= $mae['successful_predictions'] ?></div>
                                <div style="color: #7f8c8d;">Prediksi Sukses</div>
                            </div>
                            <div style="text-align: center; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                <div style="font-size: 1.8rem; font-weight: bold; color: #f39c12;"><?= $mae['prediction_coverage'] ?>%</div>
                                <div style="color: #7f8c8d;">Coverage</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px;">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($mae['error']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- RMSE Results -->
                <?php if (isset($evaluationResult['rmse_evaluation']) && $evaluationResult['rmse_evaluation']['rmse'] !== null): ?>
                <div style="border: 2px solid #e74c3c; border-radius: 10px; padding: 1.5rem;">
                    <h3 style="color: #e74c3c; margin-bottom: 1rem;">📈 Root Mean Square Error (RMSE)</h3>
                    <?php $rmse = $evaluationResult['rmse_evaluation']; ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <div style="text-align: center; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #e74c3c;"><?= $rmse['rmse'] ?></div>
                            <div style="color: #7f8c8d;">RMSE Score</div>
                        </div>
                        <div style="text-align: center; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                            <div style="font-size: 1.8rem; font-weight: bold; color: #9b59b6;"><?= $rmse['mse'] ?></div>
                            <div style="color: #7f8c8d;">MSE Score</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Precision/Recall Results -->
                <?php if (isset($evaluationResult['precision_recall_evaluation'])): ?>
                <div style="border: 2px solid #2ecc71; border-radius: 10px; padding: 1.5rem;">
                    <h3 style="color: #2ecc71; margin-bottom: 1rem;">🎯 Precision & Recall</h3>
                    <?php $pr = $evaluationResult['precision_recall_evaluation']; ?>
                    <?php if ($pr['precision'] !== null): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                            <div style="text-align: center; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                <div style="font-size: 1.8rem; font-weight: bold; color: #2ecc71;"><?= $pr['precision'] ?></div>
                                <div style="color: #7f8c8d;">Precision</div>
                            </div>
                            <div style="text-align: center; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                <div style="font-size: 1.8rem; font-weight: bold; color: #3498db;"><?= $pr['recall'] ?></div>
                                <div style="color: #7f8c8d;">Recall</div>
                            </div>
                            <div style="text-align: center; background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                <div style="font-size: 1.8rem; font-weight: bold; color: #f39c12;"><?= $pr['f1_score'] ?></div>
                                <div style="color: #7f8c8d;">F1-Score</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px;">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($pr['error']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- Individual Metric Results -->
            <?php if (isset($evaluationResult['error']) && $evaluationResult['error']): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 1.5rem; border-radius: 8px;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Error:</strong> <?= htmlspecialchars($evaluationResult['error']) ?>
                </div>
            <?php else: ?>
                <!-- MAE/RMSE Results -->
                <?php if (isset($evaluationResult['mae']) || isset($evaluationResult['rmse'])): ?>
                <div style="display: grid; gap: 2rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php if (isset($evaluationResult['mae'])): ?>
                        <div style="text-align: center; background: #e3f2fd; padding: 1.5rem; border-radius: 10px; border: 2px solid #2196f3;">
                            <div style="font-size: 2.5rem; font-weight: bold; color: #1976d2;"><?= $evaluationResult['mae'] ?></div>
                            <div style="color: #1976d2; font-weight: 500;">MAE Score</div>
                            <small style="color: #666;">Semakin rendah semakin baik</small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($evaluationResult['rmse'])): ?>
                        <div style="text-align: center; background: #ffebee; padding: 1.5rem; border-radius: 10px; border: 2px solid #f44336;">
                            <div style="font-size: 2.5rem; font-weight: bold; color: #d32f2f;"><?= $evaluationResult['rmse'] ?></div>
                            <div style="color: #d32f2f; font-weight: 500;">RMSE Score</div>
                            <small style="color: #666;">Semakin rendah semakin baik</small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($evaluationResult['prediction_coverage'])): ?>
                        <div style="text-align: center; background: #e8f5e8; padding: 1.5rem; border-radius: 10px; border: 2px solid #4caf50;">
                            <div style="font-size: 2.5rem; font-weight: bold; color: #388e3c;"><?= $evaluationResult['prediction_coverage'] ?>%</div>
                            <div style="color: #388e3c; font-weight: 500;">Coverage</div>
                            <small style="color: #666;">Persentase prediksi berhasil</small>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Evaluation Details -->
                    <?php if (isset($evaluationResult['total_ratings'])): ?>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px;">
                        <h3 style="color: #555; margin-bottom: 1rem;">Detail Evaluasi</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                            <div><strong>Total Rating:</strong> <?= number_format($evaluationResult['total_ratings']) ?></div>
                            <div><strong>Data Training:</strong> <?= number_format($evaluationResult['train_size']) ?></div>
                            <div><strong>Data Testing:</strong> <?= number_format($evaluationResult['test_size']) ?></div>
                            <div><strong>Prediksi Sukses:</strong> <?= number_format($evaluationResult['successful_predictions']) ?></div>
                            <?php if (isset($evaluationResult['min_error'])): ?>
                            <div><strong>Error Minimum:</strong> <?= $evaluationResult['min_error'] ?></div>
                            <div><strong>Error Maksimum:</strong> <?= $evaluationResult['max_error'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Precision/Recall Results -->
                <?php if (isset($evaluationResult['precision']) && $evaluationResult['precision'] !== null): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div style="text-align: center; background: #e8f5e8; padding: 1.5rem; border-radius: 10px; border: 2px solid #4caf50;">
                        <div style="font-size: 2.5rem; font-weight: bold; color: #388e3c;"><?= $evaluationResult['precision'] ?></div>
                        <div style="color: #388e3c; font-weight: 500;">Precision</div>
                        <small style="color: #666;">Akurasi rekomendasi relevan</small>
                    </div>
                    <div style="text-align: center; background: #e3f2fd; padding: 1.5rem; border-radius: 10px; border: 2px solid #2196f3;">
                        <div style="font-size: 2.5rem; font-weight: bold; color: #1976d2;"><?= $evaluationResult['recall'] ?></div>
                        <div style="color: #1976d2; font-weight: 500;">Recall</div>
                        <small style="color: #666;">Cakupan item relevan</small>
                    </div>
                    <div style="text-align: center; background: #fff3e0; padding: 1.5rem; border-radius: 10px; border: 2px solid #ff9800;">
                        <div style="font-size: 2.5rem; font-weight: bold; color: #f57c00;"><?= $evaluationResult['f1_score'] ?></div>
                        <div style="color: #f57c00; font-weight: 500;">F1-Score</div>
                        <small style="color: #666;">Harmonic mean P&R</small>
                    </div>
                </div>
                
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-top: 1rem;">
                    <h3 style="color: #555; margin-bottom: 1rem;">Detail Evaluasi</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <div><strong>User Dievaluasi:</strong> <?= $evaluationResult['evaluated_users'] ?></div>
                        <div><strong>Top-N Rekomendasi:</strong> <?= $evaluationResult['top_n'] ?></div>
                        <div><strong>Threshold Relevan:</strong> <?= $evaluationResult['relevance_threshold'] ?></div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Sample Predictions -->
        <?php if (isset($evaluationResult['evaluation_details']) && !empty($evaluationResult['evaluation_details'])): ?>
        <div style="margin-top: 2rem;">
            <h3 style="color: #555; margin-bottom: 1rem;">
                <i class="fas fa-table"></i> Contoh Prediksi (10 teratas)
            </h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: white;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 1rem; border: 1px solid #ddd; text-align: left;">User ID</th>
                            <th style="padding: 1rem; border: 1px solid #ddd; text-align: left;">Product ID</th>
                            <th style="padding: 1rem; border: 1px solid #ddd; text-align: center;">Actual Rating</th>
                            <th style="padding: 1rem; border: 1px solid #ddd; text-align: center;">Predicted Rating</th>
                            <th style="padding: 1rem; border: 1px solid #ddd; text-align: center;">Absolute Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($evaluationResult['evaluation_details'], 0, 10) as $detail): ?>
                        <tr>
                            <td style="padding: 1rem; border: 1px solid #ddd;"><?= $detail['user_id'] ?></td>
                            <td style="padding: 1rem; border: 1px solid #ddd;"><?= $detail['product_id'] ?></td>
                            <td style="padding: 1rem; border: 1px solid #ddd; text-align: center; font-weight: 500;">
                                <span style="color: #2ecc71;"><?= $detail['actual'] ?></span>
                            </td>
                            <td style="padding: 1rem; border: 1px solid #ddd; text-align: center; font-weight: 500;">
                                <span style="color: #3498db;"><?= round($detail['predicted'], 2) ?></span>
                            </td>
                            <td style="padding: 1rem; border: 1px solid #ddd; text-align: center; font-weight: 500;">
                                <span style="color: <?= $detail['error'] <= 1 ? '#27ae60' : ($detail['error'] <= 2 ? '#f39c12' : '#e74c3c') ?>;">
                                    <?= round($detail['error'], 2) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Explanation Section -->
    <div style="background: #f8f9fa; border-radius: 15px; padding: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <h2 style="color: #34495e; margin-bottom: 1.5rem;">
            <i class="fas fa-info-circle"></i> Penjelasan Metrik Evaluasi
        </h2>
        
        <div style="display: grid; gap: 1.5rem;">
            <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #3498db;">
                <h3 style="color: #2980b9; margin-bottom: 0.5rem;">📊 Mean Absolute Error (MAE)</h3>
                <p style="color: #555; line-height: 1.6; margin-bottom: 0;">
                    Mengukur rata-rata kesalahan absolut antara rating prediksi dan rating aktual. 
                    Nilai yang lebih rendah menunjukkan prediksi yang lebih akurat. 
                    <strong>Range: 0-4 (ideal: mendekati 0)</strong>
                </p>
            </div>
            
            <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #e74c3c;">
                <h3 style="color: #c0392b; margin-bottom: 0.5rem;">📈 Root Mean Square Error (RMSE)</h3>
                <p style="color: #555; line-height: 1.6; margin-bottom: 0;">
                    Mengukur akar dari rata-rata kuadrat kesalahan. Lebih sensitif terhadap outlier dibanding MAE. 
                    Nilai yang lebih rendah menunjukkan performa yang lebih baik. 
                    <strong>Range: 0-4 (ideal: mendekati 0)</strong>
                </p>
            </div>
            
            <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #2ecc71;">
                <h3 style="color: #27ae60; margin-bottom: 0.5rem;">🎯 Precision & Recall</h3>
                <p style="color: #555; line-height: 1.6; margin-bottom: 0;">
                    <strong>Precision:</strong> Seberapa akurat rekomendasi yang diberikan (relevan/total yang direkomendasikan). <br>
                    <strong>Recall:</strong> Seberapa lengkap sistem menemukan item relevan (relevan yang ditemukan/total relevan). <br>
                    <strong>F1-Score:</strong> Harmonic mean dari precision dan recall. <strong>Range: 0-1 (ideal: mendekati 1)</strong>
                </p>
            </div>
            
            <div style="background: white; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #f39c12;">
                <h3 style="color: #d68910; margin-bottom: 0.5rem;">📊 Coverage & Data Sparsity</h3>
                <p style="color: #555; line-height: 1.6; margin-bottom: 0;">
                    <strong>Coverage:</strong> Persentase prediksi yang berhasil dibuat dari total data test. <br>
                    <strong>Data Sparsity:</strong> Persentase data yang kosong dalam matriks user-item. 
                    Semakin tinggi sparsity, semakin sulit melakukan prediksi yang akurat.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
    
    table {
        font-size: 0.9rem;
    }
    
    th, td {
        padding: 0.5rem !important;
    }
}

.evaluation-header h1 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>

<?php include '../layouts/footer.php'; ?>
