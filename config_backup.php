<?php
// config_backup.php
// Backup configuration settings

// Database configuration (from your existing config.php)
define('DB_HOST', 'localhost');
define('DB_NAME', 'finance_tracker');
define('DB_USER', 'root');
define('DB_PASS', '');

// Backup configuration
define('BACKUP_DIR', 'backups/');
define('MAX_BACKUPS', 30); // Keep last 30 backups
define('AUTO_BACKUP_HOUR', 2); // Daily backup at 2 AM
define('BACKUP_FILES', true); // Backup PHP files too
define('ENCRYPT_BACKUP', false); // Set to true for encryption
define('ENCRYPTION_KEY', 'your-secret-key-here'); // Change this!

// File types to backup
$backup_file_types = [
    '.php',
    '.css',
    '.js',
    '.html',
    '.json',
    '.sql'
];

// Excluded directories from backup
$excluded_dirs = [
    'backups',
    'node_modules',
    'vendor',
    '.git',
    'temp',
    'cache'
];

// Tables to backup (empty = all tables)
$tables_to_backup = [];
?>