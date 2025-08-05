<?php
/**
 * Simple MAE Test Script
 * Quick test untuk memverifikasi bahwa MAE calculation bekerja dengan benar
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../config/db.php';
require '../functions/evaluation.php';

echo "<h1>🧪 MAE Calculation Test</h1>\n";
echo "<p>Testing Mean Absolute Error calculation for collaborative filtering...</p>\n";

try {
    echo "<h2>📊 Running MAE Evaluation...</h2>\n";
    
    $startTime = microtime(true);
    $result = calculateMAE($pdo, 0.2); // 20% test data
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 3);
    
    echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; font-family: monospace;'>";
    
    if ($result['mae'] !== null) {
        echo "<h3 style='color: #28a745;'>✅ MAE Calculation Successful!</h3>";
        echo "<strong>MAE Score:</strong> {$result['mae']}<br>";
        echo "<strong>Execution Time:</strong> {$executionTime} seconds<br>";
        echo "<strong>Total Ratings:</strong> {$result['total_ratings']}<br>";
        echo "<strong>Training Data:</strong> {$result['train_size']}<br>";
        echo "<strong>Test Data:</strong> {$result['test_size']}<br>";
        echo "<strong>Successful Predictions:</strong> {$result['successful_predictions']}<br>";
        echo "<strong>Prediction Coverage:</strong> {$result['prediction_coverage']}%<br>";
        echo "<strong>Average Error:</strong> {$result['average_error']}<br>";
        echo "<strong>Min Error:</strong> {$result['min_error']}<br>";
        echo "<strong>Max Error:</strong> {$result['max_error']}<br>";
        
        // Interpretasi hasil
        echo "<br><h4>📈 Interpretasi Hasil:</h4>";
        if ($result['mae'] <= 0.8) {
            echo "<span style='color: #28a745;'>🎯 Excellent: MAE sangat rendah, prediksi sangat akurat!</span><br>";
        } elseif ($result['mae'] <= 1.2) {
            echo "<span style='color: #ffc107;'>👍 Good: MAE cukup rendah, prediksi cukup akurat</span><br>";
        } elseif ($result['mae'] <= 1.8) {
            echo "<span style='color: #fd7e14;'>⚠️ Fair: MAE sedang, masih dapat diterima</span><br>";
        } else {
            echo "<span style='color: #dc3545;'>❌ Poor: MAE tinggi, perlu perbaikan algoritma</span><br>";
        }
        
        if ($result['prediction_coverage'] >= 80) {
            echo "<span style='color: #28a745;'>📊 Coverage tinggi: Sistem dapat memprediksi sebagian besar test data</span><br>";
        } elseif ($result['prediction_coverage'] >= 50) {
            echo "<span style='color: #ffc107;'>📊 Coverage sedang: Sistem dapat memprediksi setengah dari test data</span><br>";
        } else {
            echo "<span style='color: #dc3545;'>📊 Coverage rendah: Sistem hanya dapat memprediksi sedikit test data</span><br>";
        }
        
    } else {
        echo "<h3 style='color: #dc3545;'>❌ MAE Calculation Failed</h3>";
        echo "<strong>Error:</strong> " . htmlspecialchars($result['error']) . "<br>";
        
        if (isset($result['total_ratings'])) {
            echo "<strong>Total Ratings in DB:</strong> {$result['total_ratings']}<br>";
        }
    }
    
    echo "</div>";
    
    // Test precision/recall jika MAE berhasil
    if ($result['mae'] !== null) {
        echo "<h2>🎯 Running Precision/Recall Test...</h2>\n";
        
        $startTime = microtime(true);
        $prResult = calculatePrecisionRecall($pdo, 10, 4.0);
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 3);
        
        echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; font-family: monospace;'>";
        
        if ($prResult['precision'] !== null) {
            echo "<h3 style='color: #28a745;'>✅ Precision/Recall Calculation Successful!</h3>";
            echo "<strong>Precision:</strong> {$prResult['precision']}<br>";
            echo "<strong>Recall:</strong> {$prResult['recall']}<br>";
            echo "<strong>F1-Score:</strong> {$prResult['f1_score']}<br>";
            echo "<strong>Execution Time:</strong> {$executionTime} seconds<br>";
            echo "<strong>Evaluated Users:</strong> {$prResult['evaluated_users']}<br>";
            echo "<strong>Top-N:</strong> {$prResult['top_n']}<br>";
            echo "<strong>Relevance Threshold:</strong> {$prResult['relevance_threshold']}<br>";
        } else {
            echo "<h3 style='color: #dc3545;'>❌ Precision/Recall Calculation Failed</h3>";
            echo "<strong>Error:</strong> " . htmlspecialchars($prResult['error']) . "<br>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<h3 style='color: #dc3545;'>❌ Test Failed with Exception</h3>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>💡 Next Steps</h2>";
echo "<ul>";
echo "<li><a href='demo_data_generator.php'>Generate more sample data</a> jika data rating kurang</li>";
echo "<li><a href='../pages/evaluation.php'>Go to full evaluation page</a> untuk interface yang lengkap</li>";
echo "<li><a href='../pages/home.php'>Back to main application</a></li>";
echo "</ul>";

// Basic database info
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ratings");
    $totalRatings = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM ratings");
    $totalUsers = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT product_id) as count FROM ratings");
    $totalProducts = $stmt->fetch()['count'];
    
    echo "<hr>";
    echo "<h3>📊 Database Summary</h3>";
    echo "<p><strong>Total Ratings:</strong> " . number_format($totalRatings) . "</p>";
    echo "<p><strong>Active Users:</strong> {$totalUsers}</p>";
    echo "<p><strong>Rated Products:</strong> {$totalProducts}</p>";
    
    if ($totalRatings < 10) {
        echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #ffc107;'>";
        echo "<strong>⚠️ Warning:</strong> Jumlah rating terlalu sedikit untuk evaluasi yang akurat. ";
        echo "Minimal dibutuhkan 10 rating. Silakan <a href='demo_data_generator.php'>generate sample data</a> terlebih dahulu.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p>Could not get database summary: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
