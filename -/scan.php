<?php
set_time_limit(0);
error_reporting(0);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

$path = getcwd();
if(isset($_POST['dir'])){ 
    $path = $_POST['dir'];
}

// ====== DELETE FILE ======
if(isset($_GET['delete'])){
    $target = $_GET['delete'];
    if(is_file($target)){
        if(@unlink($target)){
            $delete_msg = "<div class='hit green'>[OK] File berhasil dihapus: ".htmlspecialchars($target)."</div>";
        } else {
            $delete_msg = "<div class='hit danger'>[ERR] Gagal menghapus file: ".htmlspecialchars($target)."</div>";
        }
    }
}

// ====== BACKDOOR DETECTION ======
function get_detection_pattern() {
    $rx = "#(exec\s*\(|system\s*\(|passthru\s*\(|shell_exec\s*\(|popen\s*\(|proc_open\s*\(|pcntl_exec\s*\(|eval\s*\(|assert\s*\(|" .
          "call_user_func\s*\(|call_user_func_array\s*\(|create_function\s*\(|include\s*\(|require\s*\(|" .
          "include_once\s*\(|require_once\s*\(|dl\s*\(|base64_decode\s*\(|gzinflate\s*\(|gzuncompress\s*\(|gzdecode\s*\(|" .
          "bzdecompress\s*\(|str_rot13\s*\(|strrev\s*\(|chr\s*\(|ord\s*\(|pack\s*\(|unpack\s*\(|hex2bin\s*\(|bin2hex\s*\(|" .
          "preg_replace\s*\(.*?\/[a-zA-Z]*e[a-zA-Z]*[\'\"]" .
          "|preg_filter\s*\(.*?\/[a-zA-Z]*e[a-zA-Z]*[\'\"]" .
          "|mb_ereg_replace\s*\(.*?\".*?\".*?\".*?e.*?\"" .
          "|mb_eregi_replace\s*\(.*?\".*?\".*?\".*?e.*?\"" .
          "|file_put_contents\s*\(|file_get_contents\s*\(|fopen\s*\(|fwrite\s*\(|fputs\s*\(|move_uploaded_file\s*\(|copy\s*\(|rename\s*\(|unlink\s*\(|chmod\s*\(|chown\s*\(|" .
          "fsockopen\s*\(|pfsockopen\s*\(|curl_exec\s*\(|curl_multi_exec\s*\(|stream_socket_client\s*\(|" .
          "\$\_(GET|POST|REQUEST|COOKIE)\s*\[[^\]]+\]\s*\()#is";
    return $rx;
}

function highlight_bad_things($content) {
    $escaped = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $hp = get_detection_pattern();
    return preg_replace($hp, '<mark>$0</mark>', $escaped);
}

// ====== SCAN BACKDOOR ======
function scanBackdoor($current_dir){
    $max_size = 5 * 1024 * 1024; // 5MB max
    $extPattern = '/^(ph|sh)/i'; // ph* dan sh*
    if(!is_readable($current_dir)) return;
    $dir_location = @scandir($current_dir);
    if($dir_location === false) return;
    foreach ($dir_location as $file) {
        if($file === "." || $file === "..") continue;
        $file_location = rtrim($current_dir,'/').'/'.$file;
        if(is_file($file_location) && filesize($file_location) <= $max_size){
            $ext = pathinfo($file_location, PATHINFO_EXTENSION);
            if(preg_match($extPattern, $ext)){
                checkBackdoor($file_location);
            }
        } elseif(is_dir($file_location)){
            scanBackdoor($file_location);
        }
    }
}

function checkBackdoor($file_location){
    static $detPattern = null;
    if ($detPattern === null) $detPattern = get_detection_pattern();
    $contents = @file_get_contents($file_location);
    if($contents === false) return;
    if(preg_match($detPattern, $contents)){
        echo "<div class='hit'>[+] Suspect file → <span class='danger'>".htmlspecialchars($file_location)."</span></div>";
        echo "<div class='row'><a class='button danger' href='?menu=backdoor&delete=".urlencode($file_location)."' onclick='return confirm(\"Yakin hapus file ini?\")'>Delete</a></div>";
        echo "<details class='codewrap'><summary>Lihat isi file</summary>";
        echo "<pre class='codeblock'>".highlight_bad_things($contents)."</pre>";
        echo "</details>";
    }
}

// ====== SCAN HTACCESS ======
function scanHtaccess($current_dir){
    if(!is_readable($current_dir)) return;
    $dir_location = @scandir($current_dir);
    if($dir_location === false) return;
    foreach ($dir_location as $file) {
        if($file === "." || $file === "..") continue;
        $file_location = rtrim($current_dir,'/').'/'.$file;
        if(is_file($file_location) && strtolower($file) === '.htaccess'){
            $contents = @file_get_contents($file_location);
            if($contents !== false){
                $chmod = substr(sprintf('%o', fileperms($file_location)), -4);
                echo "<div class='hit'>[.] .htaccess ditemukan: <span class='green'>".htmlspecialchars($file_location)." | chmod: {$chmod}</span></div>";
                echo "<details class='codewrap'><summary>Lihat isi .htaccess</summary>";
                echo "<pre class='codeblock'>".htmlspecialchars($contents)."</pre>";
                echo "</details>";
            }
        } elseif(is_dir($file_location)){
            scanHtaccess($file_location);
        }
    }
}

// ====== RENDER ======
$menu = $_GET['menu'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard Scanner</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root {--bg:#1e1e2f; --panel:#27293d; --muted:#9aa4b2; --text:#e6eaf0; --primary:#3b82f6; --primary-2:#2563eb; --danger:#ef4444; --success:#22c55e; --border:#333647; --code-bg:#1b1b2f; --mark:#fff3cd; --mark-text:#111827; --shadow:0 8px 20px rgba(0,0,0,.35);}
*{box-sizing:border-box;}
html,body{height:100%;margin:0;font:14px/1.6 system-ui,sans-serif;color:var(--text);}
body{display:flex;background:var(--bg);}
.sidebar{width:240px;background:var(--panel);border-right:1px solid var(--border);display:flex;flex-direction:column;}
.sidebar h2{padding:20px; margin:0;font-size:18px;text-transform:uppercase;border-bottom:1px solid var(--border);}
.sidebar a{padding:14px 22px;color:var(--text);text-decoration:none;transition:.2s;border-left:4px solid transparent;}
.sidebar a.active, .sidebar a:hover{background:rgba(59,130,246,.1);border-left:4px solid var(--primary);}
.main{flex:1;padding:30px;overflow:auto;}
.card{background:var(--panel);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;margin-bottom:24px;}
.header{padding:20px 24px;border-bottom:1px solid var(--border);}
.title{margin:0;font-size:18px;letter-spacing:.5px;text-transform:uppercase;}
.row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;}
.input{flex:1;padding:10px 12px;background:var(--code-bg);color:var(--text);border:1px solid var(--border);border-radius:10px;outline:none;}
.button{padding:10px 16px;background:var(--primary);color:#fff;border:none;border-radius:10px;cursor:pointer;transition:.2s;text-decoration:none;display:inline-block;}
.button:hover{background:var(--primary-2);}
.button.danger{background:var(--danger);}
.button.danger:hover{background:#dc2626;}
.alert{margin-top:12px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.25);color:#cfe1ff;padding:10px 12px;border-radius:10px;}
.hit{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);padding:10px 12px;border-radius:10px;margin:16px 0 6px;}
details.codewrap{margin:8px 0 18px;border:1px solid var(--border);border-radius:12px;overflow:hidden;}
details.codewrap>summary{cursor:pointer;background:#2c2d42;padding:10px 14px;font-weight:600;user-select:none;list-style:none;}
details.codewrap>summary::-webkit-details-marker{display:none;}
.codeblock{margin:0;padding:14px 16px;background:var(--code-bg);color:#d1d5db;font:12px/1.55 ui-monospace,monospace;border-top:1px solid var(--border);overflow:auto;white-space:pre-wrap;word-break:break-word;}
mark{background:var(--mark);color:var(--mark-text);}
.green{color:var(--success);}
.danger{color:var(--danger);}
</style>
</head>
<body>
<div class="sidebar">
<h2>Scanner Menu</h2>
<a href="?menu=dashboard" class="<?php echo $menu==='dashboard'?'active':'';?>">Dashboard</a>
<a href="?menu=backdoor" class="<?php echo $menu==='backdoor'?'active':'';?>">Scan Backdoor PHP</a>
<a href="?menu=htaccess" class="<?php echo $menu==='htaccess'?'active':'';?>">Scan .htaccess</a>
</div>
<div class="main">
<?php
echo "<div class='card'><div class='header'><h2 class='title'>".ucfirst($menu)."</h2></div><div class='content'>";
echo "<form method='post' class='row'><input class='input' name='dir' value='".htmlspecialchars($path)."'><button class='button' type='submit'>Go</button></form>";
if(isset($delete_msg)) echo $delete_msg;

if($menu==='dashboard'){
    echo "<p>Selamat datang di Dashboard Scanner. Gunakan menu di samping untuk memulai scan.</p>";
} elseif($menu==='backdoor'){
    echo "<div class='alert'>Memulai scan Backdoor PHP/SH di <strong>".htmlspecialchars($path)."</strong></div>";
    scanBackdoor($path);
} elseif($menu==='htaccess'){
    echo "<div class='alert'>Memulai scan .htaccess di <strong>".htmlspecialchars($path)."</strong></div>";
    scanHtaccess($path);
} else {
    echo "<p>Menu tidak ditemukan.</p>";
}
echo "</div></div>";
?>
</
