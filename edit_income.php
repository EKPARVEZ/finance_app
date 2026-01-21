<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get income ID from URL
if (!isset($_GET['id'])) {
    header('Location: income.php');
    exit();
}

$income_id = $_GET['id'];

// Verify this income belongs to the logged-in user
$stmt = $pdo->prepare("SELECT * FROM income WHERE id = ? AND user_id = ?");
$stmt->execute([$income_id, $user_id]);
$income = $stmt->fetch();

if (!$income) {
    header('Location: income.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $name = $_POST['name'];
    $amount = $_POST['amount'];
    
    // Validation
    if (empty($date) || empty($name) || empty($amount)) {
        $message = 'All fields are required!';
        $message_type = 'error';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $message = 'Please enter a valid amount!';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE income SET date = ?, name = ?, amount = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$date, $name, $amount, $income_id, $user_id]);
            
            $message = 'Income updated successfully!';
            $message_type = 'success';
            
            // Update the income data
            $income['date'] = $date;
            $income['name'] = $name;
            $income['amount'] = $amount;
        } catch(PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Income</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="nav-brand">
                <i class="fas fa-chart-line"></i> Finance Tracker
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="income.php"><i class="fas fa-money-bill-wave"></i> Income</a>
                <a href="expenses.php"><i class="fas fa-shopping-cart"></i> Expenses</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><i class="fas fa-edit"></i> Edit Income</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($income['date']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="name"><i class="fas fa-tag"></i> Income Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($income['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="amount"><i class="fas fa-dollar-sign"></i> Amount</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="<?php echo htmlspecialchars($income['amount']); ?>" required>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Update Income
                </button>
                <a href="income.php" class="btn" style="background: #6c757d; margin-left: 10px;">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </form>
        </div>
    </div>

    <script>
        setTimeout(function() {
            var messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                message.style.opacity = '0';
                setTimeout(function() {
                    message.style.display = 'none';
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>