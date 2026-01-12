<?php
session_start();

// Start output buffering to prevent headers already sent errors
ob_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle rename connection
if (isset($_POST['rename_connection'])) {
    $old_name = $_POST['old_name'];
    $new_name = trim($_POST['new_name']);
    
    if ($old_name && $new_name && isset($_SESSION['db_connections'][$old_name])) {
        // Make sure new name is unique
        if ($new_name !== $old_name && isset($_SESSION['db_connections'][$new_name])) {
            $counter = 1;
            while (isset($_SESSION['db_connections'][$new_name])) {
                $new_name = trim($_POST['new_name']) . ' (' . $counter . ')';
                $counter++;
            }
        }
        
        $_SESSION['db_connections'][$new_name] = $_SESSION['db_connections'][$old_name];
        unset($_SESSION['db_connections'][$old_name]);
        
        if ($_SESSION['active_connection'] === $old_name) {
            $_SESSION['active_connection'] = $new_name;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle delete connection
if (isset($_POST['delete_connection'])) {
    $connection_name = $_POST['connection_name'];
    
    if ($connection_name && isset($_SESSION['db_connections'][$connection_name])) {
        unset($_SESSION['db_connections'][$connection_name]);
        
        // Jika koneksi yang dihapus adalah koneksi aktif
        if ($_SESSION['active_connection'] === $connection_name) {
            // Cek apakah ada koneksi lain yang tersedia
            if (!empty($_SESSION['db_connections'])) {
                // Ambil koneksi pertama yang tersedia sebagai koneksi aktif baru
                $_SESSION['active_connection'] = key($_SESSION['db_connections']);
            } else {
                $_SESSION['active_connection'] = null;
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle database login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_login'])) {
    $connection_name = trim($_POST['connection_name']) ?: 'Default';
    $db_config = [
        'host' => trim($_POST['host']),
        'username' => trim($_POST['username']),
        'password' => trim($_POST['password']),
        'database' => trim($_POST['database']),
    ];
    
    // Test connection
    try {
        $conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->close();
        
        // Make sure connection name is unique
        $original_name = $connection_name;
        $counter = 1;
        while (isset($_SESSION['db_connections'][$connection_name])) {
            $connection_name = $original_name . ' (' . $counter . ')';
            $counter++;
        }
        
        // Save to session
        $_SESSION['db_connections'][$connection_name] = $db_config;
        $_SESSION['active_connection'] = $connection_name;
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $login_error = $e->getMessage();
    }
}

// Handle switch connection
if (isset($_GET['switch_connection']) && isset($_SESSION['db_connections'][$_GET['switch_connection']])) {
    $_SESSION['active_connection'] = $_GET['switch_connection'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle export to CSV - PERBAIKAN DI SINI
if (isset($_GET['action']) && $_GET['action'] === 'export_csv' && isset($_GET['db']) && isset($_GET['table'])) {
    $db = $_GET['db'];
    $table = $_GET['table'];
    
    try {
        $conn->select_db($db);
        
        // Get column names
        $result = $conn->query("DESCRIBE `" . $conn->real_escape_string($table) . "`");
        if (!$result) {
            throw new Exception("Failed to get table structure: " . $conn->error);
        }
        
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Get data
        $result = $conn->query("SELECT * FROM `" . $conn->real_escape_string($table) . "`");
        if (!$result) {
            throw new Exception("Failed to get table data: " . $conn->error);
        }
        
        // Clean any output that might have been sent
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Set headers to force download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $table . '_export_' . date('Y-m-d_H-i-s') . '.csv"');
        
        // Create CSV content manually instead of using fputcsv
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        
        // Add column headers
        $csv .= '"' . implode('","', array_map('addslashes', $columns)) . "\"\n";
        
        // Add data rows
        while ($row = $result->fetch_assoc()) {
            $csv_row = [];
            foreach ($row as $value) {
                // Escape double quotes and handle newlines
                $csv_row[] = '"' . str_replace('"', '""', $value) . '"';
            }
            $csv .= implode(',', $csv_row) . "\n";
        }
        
        // Output CSV content
        echo $csv;
        
        // Stop execution
        exit;
    } catch (Exception $e) {
        // If there's an error, redirect back with error message
        $_SESSION['export_error'] = $e->getMessage();
        header("Location: ?db=" . urlencode($db) . "&table=" . urlencode($table) . "&action=browse");
        exit;
    }
}

// Initialize connections
if (!isset($_SESSION['db_connections'])) {
    $_SESSION['db_connections'] = [];
}

// Get active connection
$active_connection = isset($_SESSION['active_connection']) ? $_SESSION['active_connection'] : null;
$config = null;

if ($active_connection && isset($_SESSION['db_connections'][$active_connection])) {
    $config = $_SESSION['db_connections'][$active_connection];
}

// If no active connection, show login form
if (!$config) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Mini Admin</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            :root {
                /* Modern Dark Theme */
                --bg-primary: #0f172a;
                --bg-secondary: #1e293b;
                --bg-card: #334155;
                --bg-input: #475569;
                --text-primary: #ffffff;
                --text-secondary: #e2e8f0;
                --accent: #3b82f6;
                --accent-hover: #2563eb;
                --success: #10b981;
                --error: #ef4444;
                --border: #475569;
                --radius: 12px;
                --transition: all 0.2s ease;
            }
            
            body {
                font-family: 'Inter', sans-serif;
                background: var(--bg-primary);
                color: var(--text-primary);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .login-container {
                width: 420px;
                background: var(--bg-card);
                border-radius: var(--radius);
                padding: 30px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
                border: 1px solid var(--border);
            }
            
            .login-header {
                text-align: center;
                margin-bottom: 30px;
            }
            
            .login-header h1 {
                font-size: 28px;
                font-weight: 700;
                color: var(--accent);
                margin-bottom: 8px;
            }
            
            .login-header p {
                color: var(--text-secondary);
                font-size: 14px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: var(--text-primary);
                font-size: 14px;
            }
            
            .form-control {
                width: 100%;
                padding: 12px;
                background: var(--bg-input);
                border: 1px solid var(--border);
                border-radius: 8px;
                color: var(--text-primary);
                font-size: 14px;
                transition: var(--transition);
            }
            
            .form-control:focus {
                outline: none;
                border-color: var(--accent);
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            }
            
            .btn-login {
                width: 100%;
                padding: 12px;
                background: var(--accent);
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                transition: var(--transition);
            }
            
            .btn-login:hover {
                background: var(--accent-hover);
                transform: translateY(-1px);
            }
            
            .error {
                background: rgba(239, 68, 68, 0.1);
                color: var(--error);
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 14px;
                border: 1px solid rgba(239, 68, 68, 0.2);
            }
            
            .server-info {
                background: var(--bg-secondary);
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 20px;
                font-size: 13px;
                color: var(--text-secondary);
                border: 1px solid var(--border);
            }
            
            .server-info div {
                display: flex;
                align-items: center;
                margin-bottom: 8px;
            }
            
            .server-info div:last-child {
                margin-bottom: 0;
            }
            
            .server-info i {
                margin-right: 10px;
                color: var(--accent);
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1>Mini Admin</h1>
                <p>Database Management System</p>
            </div>
            
            <?php if (isset($login_error)) { ?>
                <div class="error">
                    <?php echo $login_error; ?>
                </div>
            <?php } ?>
            
            <div class="server-info">
                <div><i class="fas fa-server"></i> <span>Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></span></div>
                <div><i class="fas fa-code"></i> <span>PHP: <?php echo phpversion(); ?></span></div>
                <div><i class="fas fa-plug"></i> <span>MySQLi: <?php echo class_exists('mysqli') ? 'Enabled' : 'Disabled'; ?></span></div>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label class="form-label" for="connection_name">Connection Name</label>
                    <input type="text" class="form-control" id="connection_name" name="connection_name" value="Default" placeholder="e.g., Production, Development">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="host">Host</label>
                    <input type="text" class="form-control" id="host" name="host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="root" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="database">Database (optional)</label>
                    <input type="text" class="form-control" id="database" name="database" placeholder="Leave empty to show all databases">
                </div>
                
                <button type="submit" name="db_login" class="btn-login">
                    Connect to Database
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Connect to database
try {
    $conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create database
        if (isset($_POST['create_db'])) {
            $db_name = trim($_POST['db_name']);
            if (empty($db_name)) {
                throw new Exception("Database name cannot be empty");
            }
            if (!$conn->query("CREATE DATABASE `" . $conn->real_escape_string($db_name) . "`")) {
                throw new Exception("Failed to create database: " . $conn->error);
            }
            header("Location: ?db=" . urlencode($db_name));
            exit;
        }
        
        // Drop database
        if (isset($_POST['drop_db'])) {
            $db_name = trim($_POST['db_name']);
            if (empty($db_name)) {
                throw new Exception("Database name cannot be empty");
            }
            if (!$conn->query("DROP DATABASE IF EXISTS `" . $conn->real_escape_string($db_name) . "`")) {
                throw new Exception("Failed to drop database: " . $conn->error);
            }
            header("Location: ?");
            exit;
        }
        
        // Create table
        if (isset($_POST['create_table'])) {
            $db_name = trim($_POST['db_name']);
            $table_name = trim($_POST['table_name']);
            $columns = $_POST['columns'];
            
            if (empty($db_name) || empty($table_name)) {
                throw new Exception("Database and table name cannot be empty");
            }
            
            $conn->select_db($db_name);
            
            $sql = "CREATE TABLE `" . $conn->real_escape_string($table_name) . "` (";
            foreach ($columns as $col) {
                if (empty($col['name'])) continue;
                $sql .= "`" . $conn->real_escape_string($col['name']) . "` " . $col['type'] . " " . $col['null'] . " " . $col['key'] . ", ";
            }
            $sql = rtrim($sql, ', ') . ")";
            
            if (!$conn->query($sql)) {
                throw new Exception("Failed to create table: " . $conn->error);
            }
            
            header("Location: ?db=" . urlencode($db_name) . "&table=" . urlencode($table_name));
            exit;
        }
        
        // Drop table
        if (isset($_POST['drop_table'])) {
            $db_name = trim($_POST['db_name']);
            $table_name = trim($_POST['table_name']);
            
            if (empty($db_name) || empty($table_name)) {
                throw new Exception("Database and table name cannot be empty");
            }
            
            $conn->select_db($db_name);
            if (!$conn->query("DROP TABLE IF EXISTS `" . $conn->real_escape_string($table_name) . "`")) {
                throw new Exception("Failed to drop table: " . $conn->error);
            }
            
            header("Location: ?db=" . urlencode($db_name));
            exit;
        }
        
        // Insert data
        if (isset($_POST['insert_data'])) {
            $db_name = trim($_POST['db_name']);
            $table_name = trim($_POST['table_name']);
            $data = $_POST['data'];
            
            if (empty($db_name) || empty($table_name) || empty($data)) {
                throw new Exception("Missing required parameters");
            }
            
            $conn->select_db($db_name);
            
            // Get table structure to check which columns allow NULL
            $result = $conn->query("DESCRIBE `" . $conn->real_escape_string($table_name) . "`");
            $null_columns = [];
            
            while ($row = $result->fetch_assoc()) {
                if ($row['Null'] === 'YES') {
                    $null_columns[] = $row['Field'];
                }
            }
            
            // Process data with hashing
            foreach ($data as $field => $value) {
                // Check if this is a password field that needs hashing
                if (isset($_POST['hash_type'][$field]) && $_POST['hash_type'][$field] !== '') {
                    // Only hash if value is not empty
                    if (!empty($value)) {
                        $hash_type = $_POST['hash_type'][$field];
                        switch ($hash_type) {
                            case 'md5':
                                $data[$field] = md5($value);
                                break;
                            case 'sha1':
                                $data[$field] = sha1($value);
                                break;
                            case 'sha256':
                                $data[$field] = hash('sha256', $value);
                                break;
                            case 'sha512':
                                $data[$field] = hash('sha512', $value);
                                break;
                            case 'bcrypt':
                                $data[$field] = password_hash($value, PASSWORD_BCRYPT);
                                break;
                            case 'argon2':
                                $data[$field] = password_hash($value, PASSWORD_ARGON2ID);
                                break;
                            case 'wordpress':
                                $data[$field] = md5($value . 'a unique salt');
                                break;
                            case 'joomla':
                                $data[$field] = md5($value . md5($value));
                                break;
                        }
                    }
                }
            }
            
            // Prepare columns and values for INSERT
            $columns = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                // Skip empty values for columns that allow NULL
                if ($value === '' && in_array($field, $null_columns)) {
                    continue;
                }
                
                $columns[] = $field;
                $values[] = $value === '' ? 'NULL' : "'" . $conn->real_escape_string($value) . "'";
            }
            
            // If no columns to insert, throw exception
            if (empty($columns)) {
                throw new Exception("No data to insert");
            }
            
            $sql = "INSERT INTO `" . $conn->real_escape_string($table_name) . "` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $values) . ")";
            
            if (!$conn->query($sql)) {
                throw new Exception("Failed to insert data: " . $conn->error);
            }
            
            header("Location: ?db=" . urlencode($db_name) . "&table=" . urlencode($table_name) . "&action=browse");
            exit;
        }
        
        // Update data
        if (isset($_POST['update_data'])) {
            $db_name = trim($_POST['db_name']);
            $table_name = trim($_POST['table_name']);
            $id = $_POST['id'];
            $data = $_POST['data'];
            
            if (empty($db_name) || empty($table_name) || empty($id) || empty($data)) {
                throw new Exception("Missing required parameters");
            }
            
            $conn->select_db($db_name);
            
            // Get primary key
            $result = $conn->query("SHOW KEYS FROM `" . $conn->real_escape_string($table_name) . "` WHERE Key_name = 'PRIMARY'");
            if (!$result || $result->num_rows === 0) {
                throw new Exception("No primary key found in table");
            }
            $row = $result->fetch_assoc();
            $primaryKey = $row['Column_name'];
            
            // Get table structure
            $result = $conn->query("DESCRIBE `" . $conn->real_escape_string($table_name) . "`");
            $null_columns = [];
            
            while ($row = $result->fetch_assoc()) {
                if ($row['Null'] === 'YES') {
                    $null_columns[] = $row['Field'];
                }
            }
            
            // Build UPDATE query
            $set = [];
            foreach ($data as $col => $val) {
                // Skip empty values for columns that allow NULL
                if ($val === '' && in_array($col, $null_columns)) {
                    continue;
                }
                
                // Handle password hashing if needed
                if (isset($_POST['hash_type'][$col]) && $_POST['hash_type'][$col] !== '' && !empty($val)) {
                    $hash_type = $_POST['hash_type'][$col];
                    switch ($hash_type) {
                        case 'md5':
                            $val = md5($val);
                            break;
                        case 'sha1':
                            $val = sha1($val);
                            break;
                        case 'sha256':
                            $val = hash('sha256', $val);
                            break;
                        case 'sha512':
                            $val = hash('sha512', $val);
                            break;
                        case 'bcrypt':
                            $val = password_hash($val, PASSWORD_BCRYPT);
                            break;
                        case 'argon2':
                            $val = password_hash($val, PASSWORD_ARGON2ID);
                            break;
                        case 'wordpress':
                            $val = md5($val . 'a unique salt');
                            break;
                        case 'joomla':
                            $val = md5($val . md5($val));
                            break;
                    }
                }
                
                // Handle NULL values
                if ($val === '') {
                    $set[] = "`" . $conn->real_escape_string($col) . "` = NULL";
                } else {
                    $set[] = "`" . $conn->real_escape_string($col) . "` = '" . $conn->real_escape_string($val) . "'";
                }
            }
            
            // If no columns to update, throw exception
            if (empty($set)) {
                throw new Exception("No data to update");
            }
            
            $sql = "UPDATE `" . $conn->real_escape_string($table_name) . "` SET " . implode(", ", $set) . " WHERE `" . $conn->real_escape_string($primaryKey) . "` = '" . $conn->real_escape_string($id) . "'";
            
            if (!$conn->query($sql)) {
                throw new Exception("Failed to update data: " . $conn->error);
            }
            
            header("Location: ?db=" . urlencode($db_name) . "&table=" . urlencode($table_name) . "&action=browse");
            exit;
        }
        
        // Delete data
        if (isset($_POST['delete_data'])) {
            $db_name = trim($_POST['db_name']);
            $table_name = trim($_POST['table_name']);
            $id = $_POST['id'];
            
            if (empty($db_name) || empty($table_name) || empty($id)) {
                throw new Exception("Missing required parameters");
            }
            
            $conn->select_db($db_name);
            
            // Get primary key
            $result = $conn->query("SHOW KEYS FROM `" . $conn->real_escape_string($table_name) . "` WHERE Key_name = 'PRIMARY'");
            if (!$result || $result->num_rows === 0) {
                throw new Exception("No primary key found in table");
            }
            $row = $result->fetch_assoc();
            $primaryKey = $row['Column_name'];
            
            $sql = "DELETE FROM `" . $conn->real_escape_string($table_name) . "` WHERE `" . $conn->real_escape_string($primaryKey) . "` = '" . $conn->real_escape_string($id) . "'";
            
            if (!$conn->query($sql)) {
                throw new Exception("Failed to delete data: " . $conn->error);
            }
            
            header("Location: ?db=" . urlencode($db_name) . "&table=" . urlencode($table_name) . "&action=browse");
            exit;
        }
        
        // Execute query
        if (isset($_POST['execute_query'])) {
            $db_name = trim($_POST['db_name']);
            $query = trim($_POST['query']);
            
            if (empty($db_name) || empty($query)) {
                throw new Exception("Database name and query cannot be empty");
            }
            
            $conn->select_db($db_name);
            
            if ($conn->multi_query($query)) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
            } else {
                throw new Exception("Failed to execute query: " . $conn->error);
            }
            
            header("Location: ?db=" . urlencode($db_name));
            exit;
        }
        
    } catch (Exception $e) {
        $action_error = $e->getMessage();
    }
}

// Get parameters
$db = isset($_GET['db']) ? $_GET['db'] : '';
$table = isset($_GET['table']) ? $_GET['table'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Set active database
if ($db) {
    $conn->select_db($db);
}

// HTML with modern UI
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mini Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Dark mode */
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #334155;
            --bg-input: #475569;
            --bg-hover: #475569;
            --text-primary: #ffffff;
            --text-secondary: #e2e8f0;
            --text-muted: #cbd5e1;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --accent-light: #60a5fa;
            --success: #10b981;
            --success-light: #34d399;
            --error: #ef4444;
            --error-light: #f87171;
            --warning: #f59e0b;
            --info: #3b82f6;
            --border: #334155;
            --shadow: rgba(0, 0, 0, 0.1);
            --radius: 8px;
            --radius-sm: 6px;
            --transition: all 0.2s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.5;
        }
        
        /* Header */
        .header {
            background: var(--bg-card);
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .header-logo {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
            margin-right: 16px;
        }
        
        .header-breadcrumb {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* Advanced Browser-like Tabs */
        .tabs-container {
            position: relative;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
        }
        
        .tabs-nav {
            display: flex;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .tabs-nav::-webkit-scrollbar {
            display: none;
        }
        
        .tab {
            position: relative;
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-bottom: none;
            cursor: pointer;
            transition: var(--transition);
            min-width: 150px;
            max-width: 250px;
        }
        
        .tab:not(:last-child) {
            margin-right: 4px;
        }
        
        .tab:hover {
            background: var(--bg-hover);
        }
        
        .tab.active {
            background: var(--bg-primary);
            color: var(--accent);
            border-bottom: 2px solid var(--accent);
        }
        
        .tab-content {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 500;
            font-size: 14px;
        }
        
        .tab-actions {
            display: flex;
            align-items: center;
            margin-left: 8px;
        }
        
        .tab-action {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
        }
        
        .tab-action:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        
        .tab-action.close:hover {
            background: rgba(239, 68, 68, 0.2);
            color: var(--error);
        }
        
        .add-tab {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-secondary);
            width: 40px;
        }
        
        .add-tab:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
        
        /* Layout */
        .app-container {
            display: flex;
            height: calc(100vh - 120px);
        }
        
        .sidebar {
            width: 220px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        /* Sidebar */
        .sidebar-section {
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            margin-bottom: 4px;
            font-size: 14px;
        }
        
        .sidebar-item:hover {
            background: var(--bg-hover);
        }
        
        .sidebar-item.active {
            background: var(--accent);
            color: white;
        }
        
        .sidebar-item i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
            font-size: 14px;
        }
        
        .sidebar-item-text {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Cards */
        .card {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn:hover {
            background: var(--accent-hover);
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-danger {
            background: var(--error);
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-info {
            background: var(--info);
        }
        
        .btn-info:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: var(--bg-input);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: var(--bg-hover);
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
        }
        
        .btn-icon {
            padding: 6px;
            border-radius: 50%;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Advanced Form Controls */
        .form-field-group {
            margin-bottom: 16px;
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        
        .form-field-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .form-field-title {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-field-type {
            font-size: 12px;
            color: var(--text-secondary);
            background: var(--bg-input);
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .form-field-input {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            transition: var(--transition);
        }
        
        .form-field-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .form-field-options {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        
        .form-field-option {
            flex: 1;
        }
        
        .form-field-option label {
            display: block;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }
        
        .form-field-option select,
        .form-field-option input {
            width: 100%;
            padding: 6px 8px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 12px;
        }
        
        .password-field-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
        }
        
        .password-toggle:hover {
            color: var(--text-primary);
        }
        
        .hash-selector {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        
        .hash-selector select {
            flex: 1;
            padding: 6px 8px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 12px;
        }
        
        /* Tables */
        .table-container {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .table th {
            background: var(--bg-secondary);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .table td {
            font-size: 13px;
        }
        
        .table tbody tr:hover {
            background: var(--bg-hover);
        }
        
        .table-actions {
            display: flex;
            gap: 6px;
        }
        
        /* Alerts */
        .alert {
            padding: 12px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: var(--bg-card);
            margin: 5% auto;
            padding: 0;
            border-radius: var(--radius);
            width: 500px;
            max-width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 16px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
        }
        
        .close:hover {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -220px;
                height: 100vh;
                z-index: 100;
                box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            }
            
            .sidebar.open {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
        
        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius-sm);
        }
        
        .mobile-menu-btn:hover {
            background: var(--bg-hover);
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--text-muted);
        }
        
        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        
        .empty-state p {
            margin-bottom: 20px;
        }
        
        /* Light mode */
        body.light-mode {
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-card: #ffffff;
            --bg-input: #f1f5f9;
            --bg-hover: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --accent-light: #60a5fa;
            --success: #10b981;
            --success-light: #34d399;
            --error: #ef4444;
            --error-light: #f87171;
            --warning: #f59e0b;
            --info: #3b82f6;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-logo">Mini Admin</div>
            <div class="header-breadcrumb">
                <?php if ($db && $table) { ?>
                    <span><?php echo $db; ?> / <?php echo $table; ?></span>
                <?php } elseif ($db) { ?>
                    <span><?php echo $db; ?></span>
                <?php } else { ?>
                    <span>Dashboard</span>
                <?php } ?>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn btn-sm btn-icon" onclick="toggleTheme()" title="Toggle Theme">
                <i class="fas fa-moon" id="theme-icon"></i>
            </button>
            <a href="?logout" class="btn btn-sm btn-danger">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
    
    <!-- Advanced Browser-like Tabs -->
    <div class="tabs-container">
        <div class="tabs-nav" id="tabsNav">
            <?php foreach ($_SESSION['db_connections'] as $name => $conn_config) { ?>
                <div class="tab <?php echo $name === $active_connection ? 'active' : ''; ?>" data-name="<?php echo $name; ?>">
                    <div class="tab-content"><?php echo $name; ?></div>
                    <div class="tab-actions">
                        <button class="tab-action" onclick="switchConnection('<?php echo $name; ?>')" title="Switch">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="tab-action" onclick="showRenameModal('<?php echo $name; ?>')" title="Rename">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="tab-action close" onclick="confirmDeleteConnection('<?php echo $name; ?>')" title="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php } ?>
            <div class="add-tab" onclick="showAddConnectionModal()" title="Add Connection">
                <i class="fas fa-plus"></i>
            </div>
        </div>
    </div>
    
    <div class="app-container">
        <nav class="sidebar" id="sidebar">
            <?php if ($conn && !$conn->connect_error) { ?>
                <div class="sidebar-section">
                    <div class="sidebar-title">Databases</div>
                    <?php
                    $databases = [];
                    $result = $conn->query("SHOW DATABASES");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $databases[] = $row['Database'];
                        }
                    }
                    
                    foreach ($databases as $database) {
                        if (in_array($database, ['information_schema', 'performance_schema', 'mysql', 'sys'])) continue;
                        ?>
                        <a href="?db=<?php echo urlencode($database); ?>" class="sidebar-item <?php echo $db === $database ? 'active' : ''; ?>">
                            <i class="fas fa-database"></i> 
                            <span class="sidebar-item-text"><?php echo $database; ?></span>
                        </a>
                        <?php
                    }
                    ?>
                    <a href="?action=create_db" class="sidebar-item">
                        <i class="fas fa-plus"></i> Create Database
                    </a>
                </div>
                
                <?php if ($db) { ?>
                    <div class="sidebar-section">
                        <div class="sidebar-title">Tables</div>
                        <?php
                        $tables = [];
                        $conn->select_db($db);
                        $result = $conn->query("SHOW TABLES");
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                $tables[] = array_values($row)[0];
                            }
                        }
                        
                        foreach ($tables as $table_name) {
                            ?>
                            <a href="?db=<?php echo urlencode($db); ?>&table=<?php echo urlencode($table_name); ?>" class="sidebar-item <?php echo $table === $table_name ? 'active' : ''; ?>">
                                <i class="fas fa-table"></i> 
                                <span class="sidebar-item-text"><?php echo $table_name; ?></span>
                            </a>
                            <?php
                        }
                        ?>
                        <a href="?db=<?php echo urlencode($db); ?>&action=create_table" class="sidebar-item">
                            <i class="fas fa-plus"></i> Create Table
                        </a>
                    </div>
                <?php } ?>
            <?php } ?>
        </nav>
        
        <main class="main-content">
            <?php if (isset($db_error)) { ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $db_error; ?></span>
                </div>
            <?php } ?>
            
            <?php if (isset($action_error)) { ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $action_error; ?></span>
                </div>
            <?php } ?>
            
            <?php if (isset($_SESSION['export_error'])) { ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $_SESSION['export_error']; ?></span>
                </div>
                <?php unset($_SESSION['export_error']); ?>
            <?php } ?>
            
            <?php if ($action === 'create_db') { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Create Database</h2>
                    </div>
                    <form method="post">
                        <div class="form-group">
                            <label class="form-label" for="db_name">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" required>
                        </div>
                        <button type="submit" name="create_db" class="btn btn-success">
                            <i class="fas fa-save"></i> Create Database
                        </button>
                    </form>
                </div>
            <?php } elseif ($action === 'drop_db') { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Drop Database</h2>
                    </div>
                    <form method="post">
                        <div class="form-group">
                            <label class="form-label" for="db_name">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($db); ?>" required>
                        </div>
                        <button type="submit" name="drop_db" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Drop Database
                        </button>
                    </form>
                </div>
            <?php } elseif ($action === 'create_table') { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Create Table</h2>
                    </div>
                    <form method="post">
                        <div class="form-group">
                            <label class="form-label" for="table_name">Table Name</label>
                            <input type="text" class="form-control" id="table_name" name="table_name" required>
                        </div>
                        <input type="hidden" name="db_name" value="<?php echo htmlspecialchars($db); ?>">
                        
                        <div id="columns-container">
                            <div class="form-field-group">
                                <div class="form-field-header">
                                    <div class="form-field-title">Column 1</div>
                                    <div class="form-field-type">
                                        <select name="columns[0][type]">
                                            <option value="INT">INT</option>
                                            <option value="VARCHAR(255)">VARCHAR(255)</option>
                                            <option value="TEXT">TEXT</option>
                                            <option value="DATE">DATE</option>
                                            <option value="DATETIME">DATETIME</option>
                                            <option value="TIMESTAMP">TIMESTAMP</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-field-input">
                                    <input type="text" name="columns[0][name]" placeholder="Column name" required>
                                </div>
                                <div class="form-field-options">
                                    <div class="form-field-option">
                                        <label>NULL</label>
                                        <select name="columns[0][null]">
                                            <option value="NOT NULL">NOT NULL</option>
                                            <option value="NULL">NULL</option>
                                        </select>
                                    </div>
                                    <div class="form-field-option">
                                        <label>Key</label>
                                        <select name="columns[0][key]">
                                            <option value=""></option>
                                            <option value="PRIMARY KEY">PRIMARY KEY</option>
                                            <option value="UNIQUE">UNIQUE</option>
                                            <option value="INDEX">INDEX</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="button" class="btn btn-secondary" onclick="addColumn()">
                                <i class="fas fa-plus"></i> Add Column
                            </button>
                        </div>
                        
                        <button type="submit" name="create_table" class="btn btn-success">
                            <i class="fas fa-save"></i> Create Table
                        </button>
                    </form>
                </div>
            <?php } elseif ($action === 'drop_table') { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Drop Table</h2>
                    </div>
                    <form method="post">
                        <div class="form-group">
                            <label class="form-label" for="table_name">Table Name</label>
                            <input type="text" class="form-control" id="table_name" name="table_name" value="<?php echo htmlspecialchars($table); ?>" required>
                        </div>
                        <input type="hidden" name="db_name" value="<?php echo htmlspecialchars($db); ?>">
                        <button type="submit" name="drop_table" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Drop Table
                        </button>
                    </form>
                </div>
            <?php } elseif ($action === 'insert' && $db && $table) { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Insert Data</h2>
                    </div>
                    <form method="post">
                        <input type="hidden" name="db_name" value="<?php echo htmlspecialchars($db); ?>">
                        <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($table); ?>">
                        
                        <?php
                        $conn->select_db($db);
                        $result = $conn->query("DESCRIBE `" . $conn->real_escape_string($table) . "`");
                        
                        while ($row = $result->fetch_assoc()) {
                            $field = $row['Field'];
                            $type = $row['Type'];
                            $null = $row['Null'] === 'YES';
                            $key = $row['Key'];
                            $default = $row['Default'];
                            $extra = $row['Extra'];
                            
                            // Skip auto_increment fields
                            if (strpos($extra, 'auto_increment') !== false) {
                                continue;
                            }
                            
                            // Determine if this is a password field
                            $isPasswordField = stripos($field, 'password') !== false || stripos($field, 'pwd') !== false || stripos($field, 'pass') !== false;
                            ?>
                            <div class="form-field-group">
                                <div class="form-field-header">
                                    <div class="form-field-title"><?php echo htmlspecialchars($field); ?></div>
                                    <div class="form-field-type"><?php echo htmlspecialchars($type); ?></div>
                                </div>
                                <div class="password-field-group">
                                    <input type="<?php echo $isPasswordField ? 'password' : 'text'; ?>" 
                                           class="form-field-input" 
                                           name="data[<?php echo htmlspecialchars($field); ?>]"
                                           <?php echo !$null && $default === null ? 'required' : ''; ?>
                                           value="<?php echo htmlspecialchars($default ?? ''); ?>"
                                           placeholder="<?php echo $null ? 'Optional' : 'Required'; ?>">
                                    <?php if ($isPasswordField) { ?>
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility(this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php } ?>
                                </div>
                                <?php if ($isPasswordField) { ?>
                                    <div class="hash-selector">
                                        <select name="hash_type[<?php echo htmlspecialchars($field); ?>]">
                                            <option value="">No hashing</option>
                                            <option value="md5">MD5</option>
                                            <option value="sha1">SHA1</option>
                                            <option value="sha256">SHA256</option>
                                            <option value="sha512">SHA512</option>
                                            <option value="bcrypt">BCRYPT</option>
                                            <option value="argon2">Argon2</option>
                                            <option value="wordpress">WordPress</option>
                                            <option value="joomla">Joomla</option>
                                        </select>
                                    </div>
                                <?php } ?>
                            </div>
                            <?php
                        }
                        ?>
                        
                        <button type="submit" name="insert_data" class="btn btn-success">
                            <i class="fas fa-save"></i> Insert Data
                        </button>
                    </form>
                </div>
            <?php } elseif ($action === 'edit' && $db && $table && isset($_GET['id'])) { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Edit Data</h2>
                    </div>
                    <?php
                    $id = $_GET['id'];
                    $conn->select_db($db);
                    
                    // Get primary key
                    $result = $conn->query("SHOW KEYS FROM `" . $conn->real_escape_string($table) . "` WHERE Key_name = 'PRIMARY'");
                    if (!$result || $result->num_rows === 0) {
                        echo '<div class="alert alert-danger">No primary key found in table</div>';
                    } else {
                        $row = $result->fetch_assoc();
                        $primaryKey = $row['Column_name'];
                        
                        // Get current data
                        $result = $conn->query("SELECT * FROM `" . $conn->real_escape_string($table) . "` WHERE `" . $conn->real_escape_string($primaryKey) . "` = '" . $conn->real_escape_string($id) . "'");
                        if (!$result || $result->num_rows === 0) {
                            echo '<div class="alert alert-danger">Record not found</div>';
                        } else {
                            $data = $result->fetch_assoc();
                            ?>
                            <form method="post">
                                <input type="hidden" name="db_name" value="<?php echo htmlspecialchars($db); ?>">
                                <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($table); ?>">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                
                                <?php
                                $result = $conn->query("DESCRIBE `" . $conn->real_escape_string($table) . "`");
                                
                                while ($row = $result->fetch_assoc()) {
                                    $field = $row['Field'];
                                    $type = $row['Type'];
                                    $null = $row['Null'] === 'YES';
                                    $key = $row['Key'];
                                    $extra = $row['Extra'];
                                    
                                    // Skip primary key fields
                                    if ($key === 'PRI') {
                                        continue;
                                    }
                                    
                                    // Determine if this is a password field
                                    $isPasswordField = stripos($field, 'password') !== false || stripos($field, 'pwd') !== false || stripos($field, 'pass') !== false;
                                    ?>
                                    <div class="form-field-group">
                                        <div class="form-field-header">
                                            <div class="form-field-title"><?php echo htmlspecialchars($field); ?></div>
                                            <div class="form-field-type"><?php echo htmlspecialchars($type); ?></div>
                                        </div>
                                        <div class="password-field-group">
                                            <input type="<?php echo $isPasswordField ? 'password' : 'text'; ?>" 
                                                   class="form-field-input" 
                                                   name="data[<?php echo htmlspecialchars($field); ?>]"
                                                   <?php echo !$null ? 'required' : ''; ?>
                                                   value="<?php echo $isPasswordField ? '' : htmlspecialchars($data[$field] ?? ''); ?>"
                                                   placeholder="<?php echo $isPasswordField ? 'Leave empty to keep current password' : ($null ? 'Optional' : 'Required'); ?>">
                                            <?php if ($isPasswordField) { ?>
                                                <button type="button" class="password-toggle" onclick="togglePasswordVisibility(this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php } ?>
                                        </div>
                                        <?php if ($isPasswordField) { ?>
                                            <div class="hash-selector">
                                                <select name="hash_type[<?php echo htmlspecialchars($field); ?>]">
                                                    <option value="">No hashing</option>
                                                    <option value="md5">MD5</option>
                                                    <option value="sha1">SHA1</option>
                                                    <option value="sha256">SHA256</option>
                                                    <option value="sha512">SHA512</option>
                                                    <option value="bcrypt">BCRYPT</option>
                                                    <option value="argon2">Argon2</option>
                                                    <option value="wordpress">WordPress</option>
                                                    <option value="joomla">Joomla</option>
                                                </select>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <?php
                                }
                                ?>
                                
                                <button type="submit" name="update_data" class="btn btn-success">
                                    <i class="fas fa-save"></i> Update Data
                                </button>
                            </form>
                            <?php
                        }
                    }
                    ?>
                </div>
            <?php } elseif ($action === 'sql' && $db) { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Execute SQL Query</h2>
                    </div>
                    <form method="post">
                        <input type="hidden" name="db_name" value="<?php echo htmlspecialchars($db); ?>">
                        <div class="form-group">
                            <label class="form-label" for="query">SQL Query</label>
                            <textarea class="form-control" id="query" name="query" rows="10" required placeholder="SELECT * FROM table_name WHERE condition;"></textarea>
                        </div>
                        <button type="submit" name="execute_query" class="btn btn-success">
                            <i class="fas fa-play"></i> Execute
                        </button>
                    </form>
                </div>
            <?php } elseif ($db && $table && ($action === 'browse' || empty($action))) { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Browse Data</h2>
                        <div>
                            <a href="?db=<?php echo urlencode($db); ?>&table=<?php echo urlencode($table); ?>&action=insert" class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Insert
                            </a>
                            <a href="?db=<?php echo urlencode($db); ?>&table=<?php echo urlencode($table); ?>&action=sql" class="btn btn-sm btn-info">
                                <i class="fas fa-code"></i> SQL
                            </a>
                            <!-- Tombol Export CSV -->
                            <a href="?db=<?php echo urlencode($db); ?>&table=<?php echo urlencode($table); ?>&action=export_csv" class="btn btn-sm btn-secondary">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </a>
                            <a href="?db=<?php echo urlencode($db); ?>&table=<?php echo urlencode($table); ?>&action=drop_table" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> Drop
                            </a>
                        </div>
                    </div>
                    
                    <?php
                    $conn->select_db($db);
                    
                    // Get primary key
                    $result = $conn->query("SHOW KEYS FROM `" . $conn->real_escape_string($table) . "` WHERE Key_name = 'PRIMARY'");
                    $primaryKey = null;
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $primaryKey = $row['Column_name'];
                    }
                    
                    // Pagination
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $limit = 50;
                    $offset = ($page - 1) * $limit;
                    
                    // Get total records
                    $result = $conn->query("SELECT COUNT(*) as total FROM `" . $conn->real_escape_string($table) . "`");
                    $totalRecords = 0;
                    if ($result) {
                        $row = $result->fetch_assoc();
                        $totalRecords = $row['total'];
                    }
                    $totalPages = ceil($totalRecords / $limit);
                    
                    // Get data
                    $result = $conn->query("SELECT * FROM `" . $conn->real_escape_string($table) . "` LIMIT $limit OFFSET $offset");
                    
                    if (!$result) {
                        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($conn->error) . '</div>';
                    } elseif ($result->num_rows === 0) {
                        echo '<div class="empty-state">';
                        echo '<i class="fas fa-inbox"></i>';
                        echo '<h3>No Data</h3>';
                        echo '<p>There are no records in this table.</p>';
                        echo '<a href="?db=' . urlencode($db) . '&table=' . urlencode($table) . '&action=insert" class="btn btn-success">';
                        echo '<i class="fas fa-plus"></i> Insert Data';
                        echo '</a>';
                        echo '</div>';
                    } else {
                        ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <?php
                                        $fields = $result->fetch_fields();
                                        foreach ($fields as $field) {
                                            echo '<th>' . htmlspecialchars($field->name) . '</th>';
                                        }
                                        ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        foreach ($fields as $field) {
                                            $value = $row[$field->name];
                                            if ($value === null) {
                                                echo '<td><em>NULL</em></td>';
                                            } elseif (is_numeric($value)) {
                                                echo '<td>' . htmlspecialchars($value) . '</td>';
                                            } else {
                                                echo '<td>' . htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') . '</td>';
                                            }
                                        }
                                        echo '<td class="table-actions">';
                                        if ($primaryKey) {
                                            echo '<a href="?db=' . urlencode($db) . '&table=' . urlencode($table) . '&action=edit&id=' . urlencode($row[$primaryKey]) . '" class="btn btn-sm btn-info" title="Edit">';
                                            echo '<i class="fas fa-edit"></i>';
                                            echo '</a>';
                                            
                                            echo '<form method="post" style="display: inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this record?\');">';
                                            echo '<input type="hidden" name="db_name" value="' . htmlspecialchars($db) . '">';
                                            echo '<input type="hidden" name="table_name" value="' . htmlspecialchars($table) . '">';
                                            echo '<input type="hidden" name="id" value="' . htmlspecialchars($row[$primaryKey]) . '">';
                                            echo '<button type="submit" name="delete_data" class="btn btn-sm btn-danger" title="Delete">';
                                            echo '<i class="fas fa-trash"></i>';
                                            echo '</button>';
                                            echo '</form>';
                                        }
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($totalPages > 1) { ?>
                            <div class="card-footer" style="padding: 16px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    Showing <?php echo min($offset + 1, $totalRecords); ?> to <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> entries
                                </div>
                                <div class="pagination">
                                    <?php if ($page > 1) { ?>
                                        <a href="?db=<?php echo urlencode($db); ?>&table=<?php echo urlencode($table); ?>&page=<?php echo $page - 1; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php } ?>
                                    
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    if ($startPage > 1) {
                                        echo '<a href="?db=' . urlencode($db) . '&table=' . urlencode($table) . '&page=1" class="btn btn-sm btn-secondary">1</a>';
                                        if ($startPage > 2) {
                                            echo '<span class="btn btn-sm btn-secondary" style="border: none; cursor: default;">...</span>';
                                        }
                                    }
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++) {
                                        echo '<a href="?db=' . urlencode($db) . '&table=' . urlencode($table) . '&page=' . $i . '" class="btn btn-sm ' . ($i === $page ? 'btn-info' : 'btn-secondary') . '">' . $i . '</a>';
                                    }
                                    
                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) {
                                            echo '<span class="btn btn-sm btn-secondary" style="border: none; cursor: default;">...</span>';
                                        }
                                        echo '<a href="?db=' . urlencode($db) . '&table=' . urlencode($table) . '&page=' . $totalPages . '" class="btn btn-sm btn-secondary">' . $totalPages . '</a>';
                                    }
                                    ?>
                                    
                                    <?php if ($page < $totalPages) { ?>
                                        <a href="?db=<?php echo urlencode($db); ?>&table=<?php echo urlencode($table); ?>&page=<?php echo $page + 1; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                        <?php
                    }
                    ?>
                </div>
            <?php } elseif ($db) { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Tables in <?php echo htmlspecialchars($db); ?></h2>
                        <a href="?db=<?php echo urlencode($db); ?>&action=create_table" class="btn btn-sm btn-success">
                            <i class="fas fa-plus"></i> Create Table
                        </a>
                    </div>
                    
                    <?php
                    $conn->select_db($db);
                    $result = $conn->query("SHOW TABLE STATUS");
                    
                    if (!$result) {
                        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($conn->error) . '</div>';
                    } elseif ($result->num_rows === 0) {
                        echo '<div class="empty-state">';
                        echo '<i class="fas fa-table"></i>';
                        echo '<h3>No Tables</h3>';
                        echo '<p>There are no tables in this database.</p>';
                        echo '<a href="?db=' . urlencode($db) . '&action=create_table" class="btn btn-success">';
                        echo '<i class="fas fa-plus"></i> Create Table';
                        echo '</a>';
                        echo '</div>';
                    } else {
                        ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Rows</th>
                                        <th>Size</th>
                                        <th>Collation</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td><a href="?db=' . urlencode($db) . '&table=' . urlencode($row['Name']) . '" class="btn btn-link">' . htmlspecialchars($row['Name']) . '</a></td>';
                                        echo '<td>' . number_format($row['Rows']) . '</td>';
                                        echo '<td>' . formatBytes($row['Data_length'] + $row['Index_length']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['Collation']) . '</td>';
                                        echo '<td class="table-actions">';
                                        echo '<a href="?db=' . urlencode($db) . '&table=' . urlencode($row['Name']) . '&action=browse" class="btn btn-sm btn-info" title="Browse">';
                                        echo '<i class="fas fa-eye"></i>';
                                        echo '</a>';
                                        echo '<a href="?db=' . urlencode($db) . '&table=' . urlencode($row['Name']) . '&action=drop_table" class="btn btn-sm btn-danger" title="Drop" onclick="return confirm(\'Are you sure you want to drop this table?\');">';
                                        echo '<i class="fas fa-trash"></i>';
                                        echo '</a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            <?php } else { ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Databases</h2>
                        <a href="?action=create_db" class="btn btn-sm btn-success">
                            <i class="fas fa-plus"></i> Create Database
                        </a>
                    </div>
                    
                    <?php
                    $databases = [];
                    $result = $conn->query("SHOW DATABASES");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $databases[] = $row['Database'];
                        }
                    }
                    
                    if (empty($databases)) {
                        echo '<div class="empty-state">';
                        echo '<i class="fas fa-database"></i>';
                        echo '<h3>No Databases</h3>';
                        echo '<p>There are no databases available.</p>';
                        echo '<a href="?action=create_db" class="btn btn-success">';
                        echo '<i class="fas fa-plus"></i> Create Database';
                        echo '</a>';
                        echo '</div>';
                    } else {
                        ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($databases as $database) {
                                        if (in_array($database, ['information_schema', 'performance_schema', 'mysql', 'sys'])) continue;
                                        echo '<tr>';
                                        echo '<td><a href="?db=' . urlencode($database) . '" class="btn btn-link">' . htmlspecialchars($database) . '</a></td>';
                                        echo '<td class="table-actions">';
                                        echo '<a href="?db=' . urlencode($database) . '" class="btn btn-sm btn-info" title="Browse">';
                                        echo '<i class="fas fa-eye"></i>';
                                        echo '</a>';
                                        echo '<a href="?db=' . urlencode($database) . '&action=drop_db" class="btn btn-sm btn-danger" title="Drop" onclick="return confirm(\'Are you sure you want to drop this database?\');">';
                                        echo '<i class="fas fa-trash"></i>';
                                        echo '</a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            <?php } ?>
        </main>
    </div>
    
    <!-- Add Connection Modal -->
    <div id="addConnectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Connection</h3>
                <button class="close" onclick="closeModal('addConnectionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <div class="form-group">
                        <label class="form-label" for="modal_connection_name">Connection Name</label>
                        <input type="text" class="form-control" id="modal_connection_name" name="connection_name" value="Default" placeholder="e.g., Production, Development">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="modal_host">Host</label>
                        <input type="text" class="form-control" id="modal_host" name="host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="modal_username">Username</label>
                        <input type="text" class="form-control" id="modal_username" name="username" value="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="modal_password">Password</label>
                        <input type="password" class="form-control" id="modal_password" name="password">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="modal_database">Database (optional)</label>
                        <input type="text" class="form-control" id="modal_database" name="database" placeholder="Leave empty to show all databases">
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addConnectionModal')">Cancel</button>
                        <button type="submit" name="db_login" class="btn btn-success">Connect</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Rename Connection Modal -->
    <div id="renameConnectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Rename Connection</h3>
                <button class="close" onclick="closeModal('renameConnectionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" id="rename_old_name" name="old_name">
                    <div class="form-group">
                        <label class="form-label" for="rename_new_name">New Name</label>
                        <input type="text" class="form-control" id="rename_new_name" name="new_name" required>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('renameConnectionModal')">Cancel</button>
                        <button type="submit" name="rename_connection" class="btn btn-success">Rename</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Connection Confirmation Modal -->
    <div id="deleteConnectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button class="close" onclick="closeModal('deleteConnectionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this connection?</p>
                <form method="post" style="display: inline;">
                    <input type="hidden" id="delete_connection_name" name="connection_name">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteConnectionModal')">Cancel</button>
                        <button type="submit" name="delete_connection" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Theme toggle
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            if (body.classList.contains('light-mode')) {
                body.classList.remove('light-mode');
                themeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.add('light-mode');
                themeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'light');
            }
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                document.body.classList.add('light-mode');
                document.getElementById('theme-icon').className = 'fas fa-sun';
            }
        });
        
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Modal functions
        function showAddConnectionModal() {
            document.getElementById('addConnectionModal').style.display = 'block';
        }
        
        function showRenameModal(connectionName) {
            document.getElementById('rename_old_name').value = connectionName;
            document.getElementById('rename_new_name').value = connectionName;
            document.getElementById('renameConnectionModal').style.display = 'block';
        }
        
        function confirmDeleteConnection(connectionName) {
            document.getElementById('delete_connection_name').value = connectionName;
            document.getElementById('deleteConnectionModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target === modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        }
        
        // Switch connection
        function switchConnection(connectionName) {
            window.location.href = '?switch_connection=' + encodeURIComponent(connectionName);
        }
        
        // Add column for table creation
        let columnCount = 1;
        function addColumn() {
            columnCount++;
            const container = document.getElementById('columns-container');
            const columnHtml = `
                <div class="form-field-group">
                    <div class="form-field-header">
                        <div class="form-field-title">Column ${columnCount}</div>
                        <div class="form-field-type">
                            <select name="columns[${columnCount - 1}][type]">
                                <option value="INT">INT</option>
                                <option value="VARCHAR(255)">VARCHAR(255)</option>
                                <option value="TEXT">TEXT</option>
                                <option value="DATE">DATE</option>
                                <option value="DATETIME">DATETIME</option>
                                <option value="TIMESTAMP">TIMESTAMP</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-field-input">
                        <input type="text" name="columns[${columnCount - 1}][name]" placeholder="Column name" required>
                    </div>
                    <div class="form-field-options">
                        <div class="form-field-option">
                            <label>NULL</label>
                            <select name="columns[${columnCount - 1}][null]">
                                <option value="NOT NULL">NOT NULL</option>
                                <option value="NULL">NULL</option>
                            </select>
                        </div>
                        <div class="form-field-option">
                            <label>Key</label>
                            <select name="columns[${columnCount - 1}][key]">
                                <option value=""></option>
                                <option value="PRIMARY KEY">PRIMARY KEY</option>
                                <option value="UNIQUE">UNIQUE</option>
                                <option value="INDEX">INDEX</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', columnHtml);
        }
        
        // Toggle password visibility
        function togglePasswordVisibility(button) {
            const input = button.previousElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Format bytes function
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>
<?php
// End output buffering and send the output
ob_end_flush();
?>