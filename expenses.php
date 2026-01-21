<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
$message = '';
$message_type = '';

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
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, date, description, amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $date, $description, $amount]);
            
            $message = 'Expense added successfully!';
            $message_type = 'success';
            
            // Clear form after successful submission
            $_POST = array();
        } catch(PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get expense records for display
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC");
$stmt->execute([$user_id]);
$expense_records = $stmt->fetchAll();

// Calculate total expenses
$total_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ?");
$total_stmt->execute([$user_id]);
$total_expenses = $total_stmt->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Action Button Styles */
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-edit:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        
        /* Message Styles */
        .message {
            padding: 12px 20px;
            margin: 15px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        /* Currency Input */
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
        
        /* Expense Amount Color */
        .expense-amount {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .expense-currency {
            color: #e74c3c;
            font-weight: bold;
            margin-right: 2px;
        }
        
        .total-amount {
            font-size: 1.2em;
            font-weight: bold;
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

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><i class="fas fa-plus-circle"></i> Add New Expense</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" id="date" name="date" value="<?php echo isset($_POST['date']) ? $_POST['date'] : date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-file-alt"></i> Description</label>
                    <input type="text" id="description" name="description" placeholder="Food, Rent, Transport, Shopping, etc." value="<?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="amount"><i class="fas fa-money-bill"></i> Amount (BDT)</label>
                    <div class="currency-input">
                        <span>৳</span>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="0.00" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : ''; ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fas fa-save"></i> Add Expense
                </button>
            </form>
        </div>

        <div class="table-container">
            <h2><i class="fas fa-list"></i> Recent Expenses</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount (BDT)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expense_records)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No expense records found. Add your first expense!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($expense_records as $expense): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($expense['date'])); ?></td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td class="expense-amount"><span class="expense-currency">৳</span><?php echo number_format($expense['amount'], 2); ?></td>
                            <td class="actions">
                                <!-- FIXED: Changed edit_income.php to edit_expense.php -->
                                <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <!-- FIXED: Changed delete_income.php to delete_expense.php -->
                                <a href="delete_expense.php?id=<?php echo $expense['id']; ?>" 
                                   class="btn-action btn-delete" 
                                   onclick="return confirm('Are you sure you want to delete this expense record?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Total Expenses</strong></td>
                        <td><strong class="total-amount" style="color: #e74c3c;">৳<?php echo number_format($total_expenses, 2); ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <script>
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
        
        // Set default date to today
        document.addEventListener('DOMContentLoaded', function() {
            var dateField = document.getElementById('date');
            if (!dateField.value) {
                var today = new Date().toISOString().split('T')[0];
                dateField.value = today;
            }
            
            // Form validation
            var form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                var amount = document.getElementById('amount').value;
                if (parseFloat(amount) <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid amount greater than 0.');
                    return false;
                }
                return true;
            });
        });
    </script>
</body>
</html>