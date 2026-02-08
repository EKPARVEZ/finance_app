<?php
// auto_backup.php
// Run this via cron job for automatic backups
require_once 'config_backup.php';

// Connect to database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit;
}

// Create backup directory if not exists
if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

// Check if backup should run today
$backup_file = BACKUP_DIR . 'last_backup.txt';
$last_backup_date = file_exists($backup_file) ? file_get_contents($backup_file) : '';

if ($last_backup_date != date('Y-m-d')) {
    // Create daily backup
    try {
        $description = 'Auto backup ' . date('Y-m-d');
        
        // Backup database
        backupDatabase($pdo, $description);
        
        // Backup files every Sunday
        if (date('w') == 0) { // 0 = Sunday
            backupFiles($description);
        }
        
        // Record backup date
        file_put_contents($backup_file, date('Y-m-d'));
        
        // Clean old backups
        cleanupOldBackups();
        
        error_log("Auto backup completed: " . date('Y-m-d H:i:s'));
    } catch (Exception $e) {
        error_log("Auto backup failed: " . $e->getMessage());
    }
}

// Backup functions (same as in backup_database.php)
function backupDatabase($pdo, $description) {
    $tables_to_backup = [];
    
    $backup_name = 'auto_db_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = BACKUP_DIR . $backup_name;
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $output = "-- Auto Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Description: " . $description . "\n\n";
    
    foreach ($tables as $table) {
        $output .= "DROP TABLE IF EXISTS `$table`;\n\n";
        
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $output .= $row[1] . ";\n\n";
        
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $output .= "INSERT INTO `$table` VALUES\n";
            $values = [];
            foreach ($rows as $row) {
                $row_values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    return $pdo->quote($value);
                }, array_values($row));
                $values[] = '(' . implode(', ', $row_values) . ')';
            }
            $output .= implode(",\n", $values) . ";\n\n";
        }
    }
    
    file_put_contents($backup_path, $output);
    
    $info = [
        'filename' => $backup_name,
        'type' => 'database',
        'created_at' => date('Y-m-d H:i:s'),
        'description' => $description,
        'size' => filesize($backup_path),
        'auto' => true
    ];
    
    file_put_contents(BACKUP_DIR . 'info_' . $backup_name . '.json', json_encode($info, JSON_PRETTY_PRINT));
}

function backupFiles($description) {
    $backup_file_types = ['.php', '.css', '.js', '.html', '.json'];
    $excluded_dirs = ['backups', 'node_modules', '.git', 'temp', 'cache'];
    
    $backup_name = 'auto_files_' . date('Y-m-d_H-i-s') . '.zip';
    $backup_path = BACKUP_DIR . $backup_name;
    
    $zip = new ZipArchive();
    if ($zip->open($backup_path, ZipArchive::CREATE) !== TRUE) {
        throw new Exception("Cannot create zip file");
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('.'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(getcwd()) + 1);
            
            $include_file = false;
            foreach ($backup_file_types as $type) {
                if (strpos($filePath, $type) !== false) {
                    $include_file = true;
                    break;
                }
            }
            
            foreach ($excluded_dirs as $excluded_dir) {
                if (strpos($filePath, $excluded_dir) !== false) {
                    $include_file = false;
                    break;
                }
            }
            
            if ($include_file) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    $zip->close();
    
    $info = [
        'filename' => $backup_name,
        'type' => 'files',
        'created_at' => date('Y-m-d H:i:s'),
        'description' => $description,
        'size' => filesize($backup_path),
        'auto' => true
    ];
    
    file_put_contents(BACKUP_DIR . 'info_' . $backup_name . '.json', json_encode($info, JSON_PRETTY_PRINT));
}

function cleanupOldBackups() {
    $backups = [];
    $files = scandir(BACKUP_DIR);
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && strpos($file, 'info_') === false) {
            $file_path = BACKUP_DIR . $file;
            $info_path = BACKUP_DIR . 'info_' . $file . '.json';
            
            if (file_exists($info_path)) {
                $info = json_decode(file_get_contents($info_path), true);
                $backups[] = [
                    'filename' => $file,
                    'created_at' => $info['created_at']
                ];
            }
        }
    }
    
    // Sort by date (oldest first)
    usort($backups, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    
    // Remove old backups
    while (count($backups) > MAX_BACKUPS) {
        $old_backup = array_shift($backups);
        $backup_path = BACKUP_DIR . $old_backup['filename'];
        $info_path = BACKUP_DIR . 'info_' . $old_backup['filename'] . '.json';
        
        if (file_exists($backup_path)) {
            unlink($backup_path);
        }
        if (file_exists($info_path)) {
            unlink($info_path);
        }
    }
}
?>