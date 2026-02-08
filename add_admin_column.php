<?php
// add_admin_column.php
// Run this script once to add admin column to users table

require_once 'config.php';

try {
    // Check if is_admin column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Add is_admin column
        $sql = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0";
        $pdo->exec($sql);
        echo "is_admin column added successfully!\n";
        
        // Get current user and make them admin
        session_start();
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $sql = "UPDATE users SET is_admin = 1 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            echo "User ID $user_id has been made admin!\n";
        } else {
            // Make first user admin
            $sql = "UPDATE users SET is_admin = 1 WHERE id = (SELECT MIN(id) FROM users)";
            $pdo->exec($sql);
            echo "First user has been made admin!\n";
        }
    } else {
        echo "is_admin column already exists!\n";
    }
    
    echo "Setup completed. You can now access the backup system.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>