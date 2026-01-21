<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get expense ID from URL
if (!isset($_GET['id'])) {
    header('Location: expenses.php');
    exit();
}

$expense_id = $_GET['id'];

// Verify this expense belongs to the logged-in user
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
$stmt->execute([$expense_id, $user_id]);
$expense = $stmt->fetch();

if (!$expense) {
    header('Location: expenses.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    
    // Validation
    if (empty($date) || empty($description) || empty($amount)) {
        $message = 'All fields are required!';
        $message_type = 'error';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $message = 'Please enter a valid amount!';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE expenses SET date = ?, description = ?, amount = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$date, $description, $amount, $expense_id, $user_id]);
            
            $message = 'Expense updated successfully!';
            $message_type = 'success';
            
            // Update the expense data for display
            $expense['date'] = $date;
            $expense['description'] = $description;
            $expense['amount'] = $amount;
            
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
    <title>Edit Expense - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .message {
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 5px;
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
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .currency-input {
            position: relative;
        }
        
        .currency-input span {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
            color: #666;
            font-size: 16px;
        }
        
        .currency-input input {
            padding-left: 30px !important;
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
                <a href="expenses.php" class="active"><i class="fas fa-shopping-cart"></i> Expenses</a>
                <a href="view_income.php"><i class="fas fa-eye"></i> View Income</a>
                <a href="view_expenses.php"><i class="fas fa-eye"></i> View Expenses</a>
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
            <h2><i class="fas fa-edit"></i> Edit Expense</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($expense['date']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-file-alt"></i> Description</label>
                    <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($expense['description']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="amount"><i class="fas fa-money-bill"></i> Amount (BDT)</label>
                    <div class="currency-input">
                        <span>৳</span>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="<?php echo htmlspecialchars($expense['amount']); ?>" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                        <i class="fas fa-save"></i> Update Expense
                    </button>
                    <a href="expenses.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set default date to today if empty
        document.addEventListener('DOMContentLoaded', function() {
            var dateField = document.getElementById('date');
            if (!dateField.value) {
                var today = new Date().toISOString().split('T')[0];
                dateField.value = today;
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
        });
    </script>
</body>
</html>