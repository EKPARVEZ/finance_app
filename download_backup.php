<?php
// download_backup.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['file'])) {
    die('No file specified');
}

$filename = basename($_GET['file']);
$filepath = 'backups/' . $filename;

// Check if it's in uploads subdirectory
if (strpos($filename, 'uploads/') === 0) {
    $filepath = 'backups/' . $filename;
} elseif (file_exists('backups/uploads/' . $filename)) {
    $filepath = 'backups/uploads/' . $filename;
}

if (!file_exists($filepath)) {
    die('File not found: ' . htmlspecialchars($filepath));
}

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Clear output buffer
ob_clean();
flush();

// Read file and output
readfile($filepath);
exit;
?>