<?php
// setup_cron.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['is_admin'] != 1) {
    die('Access denied. Admin privileges required.');
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'enable_auto_backup') {
        // Create cron job entry
        $cron_command = "0 2 * * * php " . realpath('auto_backup.php') . " >> " . realpath('backups/cron.log') . " 2>&1";
        $message = "Add this to your crontab: <code>" . htmlspecialchars($cron_command) . "</code>";
        $message_type = 'success';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Setup - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cron-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .cron-instructions {
            background: #f8f9fa;
            border-left: 5px solid #3498db;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .cron-code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .btn-cron {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 10px 0;
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
                <a href="backup_database.php"><i class="fas fa-database"></i> Backup</a>
                <a href="setup_cron.php" class="active"><i class="fas fa-clock"></i> Cron Setup</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <div class="cron-container">
            <h1><i class="fas fa-clock"></i> Automatic Backup Setup</h1>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="cron-instructions">
                <h3><i class="fas fa-info-circle"></i> Instructions for Setting Up Automatic Backups</h3>
                
                <h4>For Linux/Unix (cPanel):</h4>
                <ol>
                    <li>Login to your cPanel</li>
                    <li>Go to "Cron Jobs"</li>
                    <li>Add new cron job with these settings:
                        <div class="cron-code">
                            Minute: 0<br>
                            Hour: 2<br>
                            Day: *<br>
                            Month: *<br>
                            Weekday: *<br>
                            Command: php <?php echo realpath('auto_backup.php'); ?>
                        </div>
                    </li>
                </ol>
                
                <h4>For Windows (Task Scheduler):</h4>
                <ol>
                    <li>Open Task Scheduler</li>
                    <li>Create Basic Task</li>
                    <li>Set trigger to "Daily" at 2:00 AM</li>
                    <li>Action: Start a program</li>
                    <li>Program: php.exe</li>
                    <li>Arguments: <?php echo realpath('auto_backup.php'); ?></li>
                </ol>
                
                <h4>Manual Setup via SSH:</h4>
                <div class="cron-code">
                    crontab -e<br>
                    # Add this line to run daily at 2 AM<br>
                    0 2 * * * php <?php echo realpath('auto_backup.php'); ?> >> <?php echo realpath('backups/cron.log'); ?> 2>&1
                </div>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="enable_auto_backup">
                <button type="submit" class="btn-cron">
                    <i class="fas fa-play"></i> Generate Cron Command
                </button>
            </form>
        </div>
    </div>
</body>
</html>