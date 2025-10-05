<?php
/**
 * extract.php (auto-delete)
 * Ekstrak semua .zip/.rar di direktori saat ini ke direktori saat ini.
 * - Sukses ekstrak => arsip dihapus otomatis.
 * - Selesai semua => skrip ini menghapus dirinya sendiri.
 * Opsi:
 *   - ?target=nama.zip|nama.rar -> ekstrak satu file saja
 *   - ?silent=1                 -> output minimal
 */

@ini_set('display_errors', 0);
@error_reporting(0);
@set_time_limit(0);
ignore_user_abort(true);

$CWD     = __DIR__;
$SILENT  = isset($_GET['silent']) && $_GET['silent'] == '1';
$TARGET  = isset($_GET['target']) ? trim($_GET['target']) : null;

function out($msg) {
    global $SILENT;
    if (!$SILENT) echo $msg . PHP_EOL;
}

function has_bin($bin) {
    $cmd = sprintf('command -v %s || which %s', escapeshellarg($bin), escapeshellarg($bin));
    $res = @shell_exec($cmd . ' 2>/dev/null');
    return is_string($res) && strlen(trim($res)) > 0;
}

function extract_zip($file, $dest) {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($file) === true) {
            $ok = $zip->extractTo($dest);
            $zip->close();
            if ($ok) return [true, "ZipArchive"];
        }
    }
    if (has_bin('unzip')) {
        $cmd = 'unzip -o -qq ' . escapeshellarg($file) . ' -d ' . escapeshellarg($dest);
        @exec($cmd, $o, $ret);
        if ($ret === 0) return [true, "unzip CLI"];
    }
    if (has_bin('7z')) {
        $cmd = '7z x -y -aoa ' . escapeshellarg($file) . ' -o' . escapeshellarg($dest);
        @exec($cmd, $o, $ret);
        if ($ret === 0) return [true, "7z CLI"];
    }
    return [false, "Extractor ZIP tidak tersedia"];
}

function extract_rar($file, $dest) {
    if (class_exists('RarArchive')) {
        $rar = @RarArchive::open($file);
        if ($rar) {
            $entries = $rar->getEntries();
            if (is_array($entries)) {
                foreach ($entries as $e) { /** @var RarEntry $e */
                    $e->extract($dest);
                }
                $rar->close();
                return [true, "RarArchive"];
            }
            $rar->close();
        }
    }
    if (has_bin('unrar')) {
        $cmd = 'unrar x -o+ ' . escapeshellarg($file) . ' ' . escapeshellarg($dest) . ' 2>/dev/null';
        @exec($cmd, $o, $ret);
        if ($ret === 0) return [true, "unrar CLI"];
    }
    if (has_bin('7z')) {
        $cmd = '7z x -y -aoa ' . escapeshellarg($file) . ' -o' . escapeshellarg($dest);
        @exec($cmd, $o, $ret);
        if ($ret === 0) return [true, "7z CLI"];
    }
    return [false, "Extractor RAR tidak tersedia"];
}

function extract_7z($file, $dest) {
    // Tidak ada ekstensi PHP untuk 7z, gunakan CLI jika tersedia
    if (has_bin('7z')) {
        $cmd = '7z x -y -aoa ' . escapeshellarg($file) . ' -o' . escapeshellarg($dest);
        @exec($cmd, $o, $ret);
        if ($ret === 0) return [true, "7z CLI"];
    }
    return [false, "Extractor 7z tidak tersedia"];
}

function extract_tar_gz($file, $dest) {
    // Prioritaskan tar, lalu 7z
    if (has_bin('tar')) {
        $cmd = 'tar -xzf ' . escapeshellarg($file) . ' -C ' . escapeshellarg($dest);
        @exec($cmd, $o, $ret);
        if ($ret === 0) return [true, "tar CLI"];
    }
    if (has_bin('7z')) {
        $cmd = '7z x -y -aoa ' . escapeshellarg($file) . ' -o' . escapeshellarg($dest);
        @exec($cmd, $o, $ret);
        if ($ret === 0) return [true, "7z CLI"];
    }
    // Coba PharData untuk tar jika tersedia (tidak selalu menangani .tar.gz langsung)
    if (class_exists('PharData')) {
        try {
            // Jika file adalah .tar.gz, perlu dekompresi dulu
            $tmp = tempnam(sys_get_temp_dir(), 'tar_');
            @copy($file, $tmp);
            $p = new PharData($tmp);
            $p->extractTo($dest, null, true);
            return [true, "PharData"];
        } catch (Exception $e) {
            // ignore
        }
    }
    return [false, "Extractor TAR/GZ tidak tersedia"];
}

function extract_one($path, $dest) {
    $lower = strtolower($path);
    // cek untuk .tar.gz dan .tgz
    if (substr($lower, -7) === '.tar.gz' || substr($lower, -4) === '.tgz') {
        return extract_tar_gz($path, $dest);
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'zip')  return extract_zip($path, $dest);
    if ($ext === 'rar')  return extract_rar($path, $dest);
    if ($ext === '7z')   return extract_7z($path, $dest);
    if ($ext === 'tar')  return extract_tar_gz($path, $dest);
    return [false, "Ekstensi tidak didukung: ." . $ext];
}

function list_archives($dir) {
    $items = [];
    $patterns = array('*.zip','*.rar','*.7z','*.tar.gz','*.tgz','*.tar');
    foreach ($patterns as $p) {
        foreach (glob($dir . DIRECTORY_SEPARATOR . $p) as $f) $items[] = $f;
    }
    sort($items);
    return $items;
}

$outcomes = [];
$toDelete = []; // daftar arsip yang akan dihapus setelah sukses ekstrak

if ($TARGET) {
    $targetPath = realpath($CWD . DIRECTORY_SEPARATOR . $TARGET);
    if (!$targetPath || !is_file($targetPath)) {
        out("Target tidak ditemukan: " . $TARGET);
        // tetap self-delete supaya sekali pakai
        @unlink(__FILE__);
        exit;
    }
    list($ok, $msg) = extract_one($targetPath, $CWD);
    $outcomes[] = [$TARGET, $ok, $msg];
    if ($ok) $toDelete[] = $targetPath;
} else {
    $archives = list_archives($CWD);
    if (empty($archives)) {
        out("Tidak ada file arsip (.zip/.rar/.7z/.tar.gz/.tgz/.tar) di direktori ini.");
        // Tampilkan daftar file untuk pengecekan cepat
        $files = scandir($CWD);
        out("Isi direktori:");
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            out(" - " . $f);
        }
        @unlink(__FILE__);
        exit;
    }
    foreach ($archives as $f) {
        $name = basename($f);
        list($ok, $msg) = extract_one($f, $CWD);
        $outcomes[] = [$name, $ok, $msg];
        if ($ok) $toDelete[] = $f;
    }
}

// hapus arsip yg sukses diekstrak
foreach ($toDelete as $f) {
    if (is_file($f)) @unlink($f);
}

// ringkasan
$okCount = 0;
foreach ($outcomes as $row) {
    list($name, $ok, $msg) = $row;
    out(($ok ? "[OK] " : "[FAIL] ") . $name . " — " . $msg);
    if ($ok) $okCount++;
}
out("Selesai: {$okCount}/" . count($outcomes) . " arsip berhasil diekstrak.");

// auto hapus diri sendiri
@unlink(__FILE__);
