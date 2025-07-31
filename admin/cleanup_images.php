<?php
// Script untuk membersihkan database dari file gambar yang tidak ada
require '../config/db.php';

echo "=== CLEANUP IMAGE DATABASE ===\n";

try {
    // Ambil semua produk yang memiliki nama file gambar
    $stmt = $pdo->query("SELECT id, name, image FROM products WHERE image IS NOT NULL AND image != ''");
    $products = $stmt->fetchAll();
    
    $cleanedCount = 0;
    
    foreach ($products as $product) {
        $imagePath = "../assets/img/" . $product['image'];
        
        if (!file_exists($imagePath)) {
            echo "❌ File tidak ditemukan: " . $product['image'] . " untuk produk: " . $product['name'] . "\n";
            
            // Set image menjadi NULL di database
            $updateStmt = $pdo->prepare("UPDATE products SET image = NULL WHERE id = ?");
            $updateStmt->execute([$product['id']]);
            
            echo "✅ Database dibersihkan untuk produk ID: " . $product['id'] . "\n";
            $cleanedCount++;
        } else {
            echo "✅ File OK: " . $product['image'] . " untuk produk: " . $product['name'] . "\n";
        }
    }
    
    echo "\n=== HASIL CLEANUP ===\n";
    echo "Total produk dicek: " . count($products) . "\n";
    echo "Database dibersihkan: " . $cleanedCount . " produk\n";
    echo "Cleanup selesai!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
