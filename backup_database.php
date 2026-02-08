<?php
// backup_database.php - With Upload Feature
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Simple access control
$allowed_users = [1];
if (!in_array($user_id, $allowed_users)) {
    die('<div style="padding: 50px; text-align: center; font-family: Arial, sans-serif;">
        <h2 style="color: #e74c3c;">Access Restricted</h2>
        <p>You do not have permission to access the backup system.</p>
        <a href="dashboard.php" style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px;">
            Return to Dashboard
        </a>
    </div>');
}

// Configuration
define('BACKUP_DIR', 'backups/');
define('UPLOAD_DIR', 'backups/uploads/');
define('MAX_BACKUPS', 30);
define('MAX_UPLOAD_SIZE', 100 * 1024 * 1024); // 100MB

// Create directories
if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$message = '';
$message_type = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['backup_file'])) {
    try {
        $uploaded_file = uploadBackupFile($_FILES['backup_file']);
        $message = 'Backup file uploaded successfully: ' . basename($uploaded_file);
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Upload failed: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle create backup request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['backup_type'])) {
    $backup_type = $_POST['backup_type'] ?? 'full';
    $description = $_POST['description'] ?? '';
    
    try {
        if ($backup_type == 'database') {
            $backup_file = backupDatabase($description);
            $message = 'Database backup created successfully: ' . basename($backup_file);
            $message_type = 'success';
        } elseif ($backup_type == 'files') {
            $backup_file = backupFiles($description);
            $message = 'Files backup created successfully: ' . basename($backup_file);
            $message_type = 'success';
        } elseif ($backup_type == 'full') {
            $backup_file_db = backupDatabase($description . ' (database)');
            $backup_file_files = backupFiles($description . ' (files)');
            
            // Create manifest
            $manifest = [
                'type' => 'full',
                'created_at' => date('Y-m-d H:i:s'),
                'description' => $description,
                'database_backup' => basename($backup_file_db),
                'files_backup' => basename($backup_file_files)
            ];
            
            $manifest_name = 'full_backup_' . date('Y-m-d_H-i-s') . '.json';
            file_put_contents(BACKUP_DIR . $manifest_name, json_encode($manifest, JSON_PRETTY_PRINT));
            
            $message = 'Full backup created successfully!';
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = 'Backup failed: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle restore request
if (isset($_GET['restore'])) {
    $backup_file = $_GET['restore'];
    try {
        restoreBackup($backup_file);
        $message = 'Backup restored successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Restore failed: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $backup_file = $_GET['delete'];
    try {
        deleteBackup($backup_file);
        $message = 'Backup deleted successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Delete failed: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle upload restore request
if (isset($_GET['restore_upload'])) {
    $uploaded_file = $_GET['restore_upload'];
    try {
        restoreUploadedBackup($uploaded_file);
        $message = 'Uploaded backup restored successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Restore failed: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle upload delete request
if (isset($_GET['delete_upload'])) {
    $uploaded_file = $_GET['delete_upload'];
    try {
        deleteUploadedBackup($uploaded_file);
        $message = 'Uploaded backup deleted successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Delete failed: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Function to upload backup file
function uploadBackupFile($file) {
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }
    
    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('File size exceeds maximum limit of ' . formatBytes(MAX_UPLOAD_SIZE));
    }
    
    // Check file type
    $allowed_extensions = ['sql', 'zip', 'json', 'gz', 'tar'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Invalid file type. Allowed: ' . implode(', ', $allowed_extensions));
    }
    
    // Generate unique filename
    $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $original_name);
    $new_filename = 'uploaded_' . $safe_name . '_' . date('Y-m-d_H-i-s') . '.' . $file_extension;
    $destination = UPLOAD_DIR . $new_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Create info file for uploaded backup
    $info = [
        'filename' => $new_filename,
        'original_name' => $file['name'],
        'type' => 'uploaded',
        'uploaded_at' => date('Y-m-d H:i:s'),
        'size' => filesize($destination),
        'extension' => $file_extension,
        'description' => 'Uploaded backup file'
    ];
    
    file_put_contents(UPLOAD_DIR . 'info_' . $new_filename . '.json', json_encode($info, JSON_PRETTY_PRINT));
    
    return $destination;
}

// Function to restore uploaded backup
function restoreUploadedBackup($filename) {
    $filepath = UPLOAD_DIR . $filename;
    
    if (!file_exists($filepath)) {
        throw new Exception("Uploaded backup file not found");
    }
    
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if ($extension == 'sql') {
        restoreDatabase($filepath);
    } elseif ($extension == 'zip') {
        restoreFiles($filepath);
    } elseif ($extension == 'gz') {
        restoreGzipFile($filepath);
    } else {
        throw new Exception("Unsupported file type: .$extension");
    }
}

// Function to restore gzipped file
function restoreGzipFile($filepath) {
    // Extract .gz file
    $output_path = str_replace('.gz', '', $filepath);
    
    // Open files
    $gz_file = gzopen($filepath, 'rb');
    $output_file = fopen($output_path, 'wb');
    
    if (!$gz_file || !$output_file) {
        throw new Exception("Failed to open gzip file");
    }
    
    // Copy data
    while (!gzeof($gz_file)) {
        fwrite($output_file, gzread($gz_file, 4096));
    }
    
    // Close files
    gzclose($gz_file);
    fclose($output_file);
    
    // Check what type of file we extracted
    $extension = strtolower(pathinfo($output_path, PATHINFO_EXTENSION));
    
    if ($extension == 'sql') {
        restoreDatabase($output_path);
    } elseif ($extension == 'zip') {
        restoreFiles($output_path);
    }
    
    // Clean up extracted file
    unlink($output_path);
}

// Function to backup database
function backupDatabase($description = '') {
    global $pdo;
    
    $backup_name = 'backup_db_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = BACKUP_DIR . $backup_name;
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $output = "-- Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Description: " . $description . "\n\n";
    
    foreach ($tables as $table) {
        // Drop table if exists
        $output .= "DROP TABLE IF EXISTS `$table`;\n\n";
        
        // Create table structure
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $output .= $row[1] . ";\n\n";
        
        // Get table data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $output .= "INSERT INTO `$table` VALUES\n";
            $values = [];
            foreach ($rows as $row) {
                $row_values = array_map(function($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $pdo->quote($value);
                }, array_values($row));
                $values[] = '(' . implode(', ', $row_values) . ')';
            }
            $output .= implode(",\n", $values) . ";\n\n";
        }
    }
    
    // Save to file
    file_put_contents($backup_path, $output);
    
    // Create backup info file
    $info = [
        'filename' => $backup_name,
        'type' => 'database',
        'created_at' => date('Y-m-d H:i:s'),
        'description' => $description,
        'size' => filesize($backup_path),
        'tables' => $tables
    ];
    
    file_put_contents(BACKUP_DIR . 'info_' . $backup_name . '.json', json_encode($info, JSON_PRETTY_PRINT));
    
    return $backup_path;
}

// Function to backup files
function backupFiles($description = '') {
    $backup_file_types = ['.php', '.css', '.js', '.html', '.json', '.sql'];
    $excluded_dirs = ['backups', 'node_modules', 'vendor', '.git', 'temp', 'cache'];
    
    $backup_name = 'backup_files_' . date('Y-m-d_H-i-s') . '.zip';
    $backup_path = BACKUP_DIR . $backup_name;
    
    // Check if ZipArchive is available
    if (!class_exists('ZipArchive')) {
        throw new Exception("ZipArchive class not available. Please enable zip extension.");
    }
    
    // Create zip file
    $zip = new ZipArchive();
    if ($zip->open($backup_path, ZipArchive::CREATE) !== TRUE) {
        throw new Exception("Cannot create zip file");
    }
    
    // Get all files recursively
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('.'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    $file_count = 0;
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(getcwd()) + 1);
            
            // Check if file type should be backed up
            $include_file = false;
            foreach ($backup_file_types as $type) {
                if (str_ends_with($filePath, $type)) {
                    $include_file = true;
                    break;
                }
            }
            
            // Check if directory is excluded
            $exclude = false;
            foreach ($excluded_dirs as $excluded_dir) {
                if (strpos($filePath, $excluded_dir) !== false) {
                    $exclude = true;
                    break;
                }
            }
            
            if ($include_file && !$exclude) {
                $zip->addFile($filePath, $relativePath);
                $file_count++;
            }
        }
    }
    
    $zip->close();
    
    // Create backup info file
    $info = [
        'filename' => $backup_name,
        'type' => 'files',
        'created_at' => date('Y-m-d H:i:s'),
        'description' => $description,
        'size' => filesize($backup_path),
        'file_count' => $file_count
    ];
    
    file_put_contents(BACKUP_DIR . 'info_' . $backup_name . '.json', json_encode($info, JSON_PRETTY_PRINT));
    
    return $backup_path;
}

// Function to restore backup
function restoreBackup($backup_file) {
    $backup_path = BACKUP_DIR . $backup_file;
    
    if (!file_exists($backup_path)) {
        throw new Exception("Backup file not found");
    }
    
    // Check backup type
    if (pathinfo($backup_file, PATHINFO_EXTENSION) == 'sql') {
        restoreDatabase($backup_path);
    } elseif (pathinfo($backup_file, PATHINFO_EXTENSION) == 'zip') {
        restoreFiles($backup_path);
    }
}

// Function to restore database
function restoreDatabase($backup_file) {
    global $pdo;
    
    // Read SQL file
    $sql = file_get_contents($backup_file);
    
    // Split SQL by semicolon
    $queries = explode(';', $sql);
    
    // Execute each query
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $pdo->exec($query);
            } catch (PDOException $e) {
                // Log error but continue
                error_log("SQL Error: " . $e->getMessage());
            }
        }
    }
}

// Function to restore files
function restoreFiles($backup_file) {
    $zip = new ZipArchive();
    if ($zip->open($backup_file) !== TRUE) {
        throw new Exception("Cannot open zip file");
    }
    
    // Extract to current directory
    $zip->extractTo('.');
    $zip->close();
}

// Function to delete backup
function deleteBackup($backup_file) {
    $backup_path = BACKUP_DIR . $backup_file;
    $info_path = BACKUP_DIR . 'info_' . $backup_file . '.json';
    
    if (file_exists($backup_path)) {
        unlink($backup_path);
    }
    
    if (file_exists($info_path)) {
        unlink($info_path);
    }
}

// Function to delete uploaded backup
function deleteUploadedBackup($filename) {
    $filepath = UPLOAD_DIR . $filename;
    $info_path = UPLOAD_DIR . 'info_' . $filename . '.json';
    
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    
    if (file_exists($info_path)) {
        unlink($info_path);
    }
}

// Function to get backup list
function getBackupList() {
    $backups = [];
    
    if (!is_dir(BACKUP_DIR)) {
        return $backups;
    }
    
    $files = scandir(BACKUP_DIR);
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && !str_starts_with($file, 'info_') && !str_starts_with($file, 'full_backup_')) {
            $file_path = BACKUP_DIR . $file;
            $info_path = BACKUP_DIR . 'info_' . $file . '.json';
            
            if (file_exists($info_path)) {
                $info = json_decode(file_get_contents($info_path), true);
                $backups[] = [
                    'filename' => $file,
                    'info' => $info,
                    'size' => filesize($file_path),
                    'modified' => date('Y-m-d H:i:s', filemtime($file_path))
                ];
            }
        }
    }
    
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return strtotime($b['info']['created_at']) - strtotime($a['info']['created_at']);
    });
    
    return $backups;
}

// Function to get uploaded files list
function getUploadedFilesList() {
    $uploads = [];
    
    if (!is_dir(UPLOAD_DIR)) {
        return $uploads;
    }
    
    $files = scandir(UPLOAD_DIR);
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && !str_starts_with($file, 'info_')) {
            $file_path = UPLOAD_DIR . $file;
            $info_path = UPLOAD_DIR . 'info_' . $file . '.json';
            
            if (file_exists($info_path)) {
                $info = json_decode(file_get_contents($info_path), true);
                $uploads[] = [
                    'filename' => $file,
                    'info' => $info,
                    'size' => filesize($file_path),
                    'modified' => date('Y-m-d H:i:s', filemtime($file_path))
                ];
            }
        }
    }
    
    // Sort by date (newest first)
    usort($uploads, function($a, $b) {
        return strtotime($b['info']['uploaded_at']) - strtotime($a['info']['uploaded_at']);
    });
    
    return $uploads;
}

// Get backup and upload lists
$backups = getBackupList();
$uploads = getUploadedFilesList();

// Helper functions
function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'sql': return 'database';
        case 'zip': return 'file-archive';
        case 'json': return 'file-code';
        case 'gz': return 'file-zipper';
        case 'tar': return 'file-zipper';
        default: return 'file';
    }
}

function formatBytes($bytes, $decimals = 2) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $dm = $decimals < 0 ? 0 : $decimals;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return number_format($bytes / pow($k, $i), $dm) . ' ' . $sizes[$i];
}

// Calculate total sizes
$total_backup_size = 0;
$total_upload_size = 0;

foreach ($backups as $backup) {
    $total_backup_size += $backup['size'];
}
foreach ($uploads as $upload) {
    $total_upload_size += $upload['size'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .backup-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .backup-header {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }
        
        .backup-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .backup-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
            max-width: 800px;
        }
        
        .backup-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-top: 5px solid;
        }
        
        .section-card.create {
            border-color: #2ecc71;
        }
        
        .section-card.upload {
            border-color: #3498db;
        }
        
        .section-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-card h2 i {
            font-size: 24px;
        }
        
        .backup-options {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .backup-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid;
        }
        
        .backup-card.database {
            border-left-color: #2ecc71;
        }
        
        .backup-card.files {
            border-left-color: #3498db;
        }
        
        .backup-card.full {
            border-left-color: #e74c3c;
        }
        
        .backup-card h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .upload-form {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease;
        }
        
        .upload-form:hover {
            border-color: #3498db;
            background: #e9f7fe;
        }
        
        .upload-form.drag-over {
            border-color: #2ecc71;
            background: #e8f8ef;
        }
        
        .file-input-wrapper {
            position: relative;
            margin-bottom: 15px;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-input-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            background: white;
            border-radius: 8px;
            border: 2px dashed #bdc3c7;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            border-color: #3498db;
            background: #f0f8ff;
        }
        
        .file-input-label i {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .file-input-label span {
            font-size: 16px;
            color: #7f8c8d;
            text-align: center;
        }
        
        .file-info {
            font-size: 12px;
            color: #95a5a6;
            text-align: center;
            margin-top: 10px;
        }
        
        .file-preview {
            margin-top: 15px;
            padding: 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            display: none;
        }
        
        .backup-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card.backups .stat-number { color: #2ecc71; }
        .stat-card.uploads .stat-number { color: #3498db; }
        .stat-card.size .stat-number { color: #e74c3c; }
        .stat-card.remaining .stat-number { color: #f39c12; }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 5px;
            margin-bottom: 25px;
        }
        
        .tab {
            flex: 1;
            text-align: center;
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            background: white;
            color: #3498db;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .backup-table, .upload-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .backup-table th, .upload-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
        }
        
        .backup-table td, .upload-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .backup-table tr:hover, .upload-table tr:hover {
            background: #f8f9fa;
        }
        
        .actions-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        
        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d68910;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        
        .btn-upload {
            background: #9b59b6;
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            width: 100%;
            justify-content: center;
        }
        
        .btn-upload:hover {
            background: #8e44ad;
        }
        
        .file-type {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .type-database { background: #d4edda; color: #155724; }
        .type-files { background: #d1ecf1; color: #0c5460; }
        .type-full { background: #f8d7da; color: #721c24; }
        .type-uploaded { background: #e2d9f3; color: #542c85; }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 64px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .no-data h3 {
            margin: 0 0 15px 0;
            color: #495057;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .message {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 1200px) {
            .backup-sections {
                grid-template-columns: 1fr;
            }
            
            .backup-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .backup-stats {
                grid-template-columns: 1fr;
            }
            
            .backup-table, .upload-table {
                display: block;
                overflow-x: auto;
            }
            
            .actions-cell {
                flex-direction: column;
            }
            
            .stat-number {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="nav-brand">
                <i class="fas fa-chart-line"></i> Finance Tracker (BDT)
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="income.php"><i class="fas fa-money-bill-wave"></i> Income</a>
                <a href="expenses.php"><i class="fas fa-shopping-cart"></i> Expenses</a>
                <a href="view_income.php"><i class="fas fa-eye"></i> View Income</a>
                <a href="view_expenses.php"><i class="fas fa-eye"></i> View Expenses</a>
                <a href="backup_database.php" class="active"><i class="fas fa-database"></i> Backup</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <div class="backup-container">
            <div class="backup-header">
                <h1><i class="fas fa-database"></i> Complete Backup & Restore System</h1>
                <p>Create backups, upload existing backups, and restore your system. Protect your financial data with regular backups.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Backup Stats -->
            <div class="backup-stats">
                <div class="stat-card backups">
                    <div class="stat-number"><?php echo count($backups); ?></div>
                    <div class="stat-label">System Backups</div>
                </div>
                <div class="stat-card uploads">
                    <div class="stat-number"><?php echo count($uploads); ?></div>
                    <div class="stat-label">Uploaded Backups</div>
                </div>
                <div class="stat-card size">
                    <div class="stat-number"><?php echo formatBytes($total_backup_size + $total_upload_size); ?></div>
                    <div class="stat-label">Total Size</div>
                </div>
                <div class="stat-card remaining">
                    <div class="stat-number"><?php echo MAX_BACKUPS - count($backups); ?></div>
                    <div class="stat-label">Backups Remaining</div>
                </div>
            </div>

            <!-- Create & Upload Sections -->
            <div class="backup-sections">
                <!-- Create Backup Section -->
                <div class="section-card create">
                    <h2><i class="fas fa-plus-circle"></i> Create New Backup</h2>
                    
                    <div class="backup-options">
                        <div class="backup-card database">
                            <h3><i class="fas fa-database"></i> Database Backup</h3>
                            <p>Backup only the database (SQL format). Includes all financial records.</p>
                            <form method="POST">
                                <input type="hidden" name="backup_type" value="database">
                                <div class="form-group">
                                    <label for="db_description"><i class="fas fa-comment"></i> Description</label>
                                    <input type="text" id="db_description" name="description" placeholder="e.g., Pre-update backup">
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-download"></i> Create Database Backup
                                </button>
                            </form>
                        </div>

                        <div class="backup-card files">
                            <h3><i class="fas fa-file-code"></i> Files Backup</h3>
                            <p>Backup all PHP, CSS, JS files. Excludes large directories.</p>
                            <form method="POST">
                                <input type="hidden" name="backup_type" value="files">
                                <div class="form-group">
                                    <label for="files_description"><i class="fas fa-comment"></i> Description</label>
                                    <input type="text" id="files_description" name="description" placeholder="e.g., Code backup before changes">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Create Files Backup
                                </button>
                            </form>
                        </div>

                        <div class="backup-card full">
                            <h3><i class="fas fa-server"></i> Full System Backup</h3>
                            <p>Complete backup of database and files. For migration or disaster recovery.</p>
                            <form method="POST">
                                <input type="hidden" name="backup_type" value="full">
                                <div class="form-group">
                                    <label for="full_description"><i class="fas fa-comment"></i> Description</label>
                                    <input type="text" id="full_description" name="description" placeholder="e.g., Monthly full backup">
                                </div>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-download"></i> Create Full Backup
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Upload Backup Section -->
                <div class="section-card upload">
                    <h2><i class="fas fa-cloud-upload-alt"></i> Upload Backup File</h2>
                    
                    <div class="upload-form" id="uploadForm">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="file-input-wrapper">
                                <input type="file" id="backup_file" name="backup_file" accept=".sql,.zip,.json,.gz,.tar" required>
                                <label for="backup_file" class="file-input-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to browse or drag & drop backup files here</span>
                                    <span class="file-info">Maximum file size: <?php echo formatBytes(MAX_UPLOAD_SIZE); ?><br>
                                    Supported formats: .sql, .zip, .json, .gz, .tar</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label for="upload_description"><i class="fas fa-comment"></i> Description (optional)</label>
                                <input type="text" id="upload_description" name="description" placeholder="e.g., Backup from old server">
                            </div>
                            
                            <button type="submit" class="btn btn-upload">
                                <i class="fas fa-upload"></i> Upload Backup File
                            </button>
                        </form>
                        
                        <div class="file-preview" id="filePreview">
                            <strong>Selected File:</strong> <span id="fileName"></span><br>
                            <strong>Size:</strong> <span id="fileSize"></span><br>
                            <strong>Type:</strong> <span id="fileType"></span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h4><i class="fas fa-info-circle"></i> Upload Instructions:</h4>
                        <ul style="margin: 10px 0 0 20px; color: #6c757d;">
                            <li>Upload SQL files to restore database only</li>
                            <li>Upload ZIP files to restore system files</li>
                            <li>Upload compressed (.gz) files are automatically extracted</li>
                            <li>Make sure backup files are from the same application version</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Backup Management Tabs -->
            <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-top: 30px;">
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('system')">
                        <i class="fas fa-database"></i> System Backups
                    </div>
                    <div class="tab" onclick="switchTab('uploaded')">
                        <i class="fas fa-cloud-upload-alt"></i> Uploaded Backups
                    </div>
                </div>
                
                <!-- System Backups Tab -->
                <div id="systemTab" class="tab-content active">
                    <h3><i class="fas fa-history"></i> System Backup History</h3>
                    <p>Backups created by this system. Old backups are automatically deleted after <?php echo MAX_BACKUPS; ?> backups.</p>
                    
                    <?php if (empty($backups)): ?>
                        <div class="no-data">
                            <i class="fas fa-database"></i>
                            <h3>No System Backups Found</h3>
                            <p>Create your first backup to secure your data.</p>
                        </div>
                    <?php else: ?>
                        <table class="backup-table">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Date Created</th>
                                    <th>Size</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-file-<?php echo getFileIcon($backup['filename']); ?>"></i>
                                        <?php echo htmlspecialchars($backup['filename']); ?>
                                    </td>
                                    <td>
                                        <span class="file-type type-<?php echo $backup['info']['type']; ?>">
                                            <?php echo ucfirst($backup['info']['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d M Y H:i', strtotime($backup['info']['created_at'])); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-hdd"></i>
                                        <?php echo formatBytes($backup['size']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($backup['info']['description']); ?>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="download_backup.php?file=<?php echo urlencode($backup['filename']); ?>" 
                                           class="btn btn-info" 
                                           title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        
                                        <a href="backup_database.php?restore=<?php echo urlencode($backup['filename']); ?>" 
                                           class="btn btn-warning" 
                                           title="Restore"
                                           onclick="return confirmRestore('system')">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                        
                                        <a href="backup_database.php?delete=<?php echo urlencode($backup['filename']); ?>" 
                                           class="btn btn-danger" 
                                           title="Delete"
                                           onclick="return confirmDelete('system')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Uploaded Backups Tab -->
                <div id="uploadedTab" class="tab-content">
                    <h3><i class="fas fa-cloud-upload-alt"></i> Uploaded Backup Files</h3>
                    <p>Backup files uploaded from your computer or other sources.</p>
                    
                    <?php if (empty($uploads)): ?>
                        <div class="no-data">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h3>No Uploaded Backups Found</h3>
                            <p>Upload a backup file to restore from external sources.</p>
                        </div>
                    <?php else: ?>
                        <table class="upload-table">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Original Name</th>
                                    <th>Type</th>
                                    <th>Uploaded</th>
                                    <th>Size</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uploads as $upload): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-file-<?php echo getFileIcon($upload['filename']); ?>"></i>
                                        <?php echo htmlspecialchars($upload['filename']); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-file"></i>
                                        <?php echo htmlspecialchars($upload['info']['original_name']); ?>
                                    </td>
                                    <td>
                                        <span class="file-type type-uploaded">
                                            .<?php echo $upload['info']['extension']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d M Y H:i', strtotime($upload['info']['uploaded_at'])); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-hdd"></i>
                                        <?php echo formatBytes($upload['size']); ?>
                                    </td>
                                    <td class="actions-cell">
                                        <a href="download_backup.php?file=uploads/<?php echo urlencode($upload['filename']); ?>" 
                                           class="btn btn-info" 
                                           title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        
                                        <a href="backup_database.php?restore_upload=<?php echo urlencode($upload['filename']); ?>" 
                                           class="btn btn-warning" 
                                           title="Restore"
                                           onclick="return confirmRestore('uploaded')">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                        
                                        <a href="backup_database.php?delete_upload=<?php echo urlencode($upload['filename']); ?>" 
                                           class="btn btn-danger" 
                                           title="Delete"
                                           onclick="return confirmDelete('uploaded')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Update tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Activate selected tab
            event.target.classList.add('active');
            document.getElementById(tabName + 'Tab').classList.add('active');
        }
        
        // File upload preview
        const fileInput = document.getElementById('backup_file');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const fileType = document.getElementById('fileType');
        const uploadForm = document.getElementById('uploadForm');
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                fileName.textContent = file.name;
                fileSize.textContent = formatBytes(file.size);
                fileType.textContent = file.type || 'Unknown';
                filePreview.style.display = 'block';
            }
        });
        
        // Drag and drop functionality
        uploadForm.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        uploadForm.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });
        
        uploadForm.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        });
        
        // Format bytes for display
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        
        // Confirmation dialogs
        function confirmRestore(type) {
            const message = type === 'uploaded' 
                ? '⚠️ Restore uploaded backup?\n\nThis will overwrite existing data with uploaded backup.'
                : '⚠️ Restore system backup?\n\nThis will overwrite existing data.';
            return confirm(message);
        }
        
        function confirmDelete(type) {
            const message = type === 'uploaded'
                ? '🗑️ Delete uploaded backup?\n\nThis action cannot be undone.'
                : '🗑️ Delete system backup?\n\nThis action cannot be undone.';
            return confirm(message);
        }
        
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            var messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                message.style.opacity = '0';
                setTimeout(function() {
                    message.style.display = 'none';
                }, 500);
            });
        }, 5000);
        
        // Form submission loading states
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        if (this.querySelector('input[type="file"]')) {
                            // Upload form
                            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                        } else {
                            // Backup form
                            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Backup...';
                        }
                        submitBtn.disabled = true;
                    }
                });
            });
        });
    </script>
</body>
</html>