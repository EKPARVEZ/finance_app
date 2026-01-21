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
            $stmt = $pdo->prepare("INSERT INTO income (user_id, date, name, amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $date, $name, $amount]);
            
            $message = 'Income added successfully!';
            $message_type = 'success';
            
            // Clear form after successful submission
            $_POST = array();
        } catch(PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get income records for display
$stmt = $pdo->prepare("SELECT * FROM income WHERE user_id = ? ORDER BY date DESC");
$stmt->execute([$user_id]);
$income_records = $stmt->fetchAll();

// Calculate total income
$total_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM income WHERE user_id = ?");
$total_stmt->execute([$user_id]);
$total_income = $total_stmt->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Management - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .message {
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 5px;
            display: none;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-edit:hover {
            background-color: #e0a800;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .currency-symbol {
            color: #2ecc71;
            font-weight: bold;
            margin-right: 2px;
        }
        
        .total-amount {
            color: #2ecc71;
            font-weight: bold;
            font-size: 1.2em;
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
                <a href="income.php" class="active"><i class="fas fa-money-bill-wave"></i> Income</a>
                <a href="expenses.php"><i class="fas fa-shopping-cart"></i> Expenses</a>
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
            <h2><i class="fas fa-plus-circle"></i> Add New Income</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" id="date" name="date" value="<?php echo isset($_POST['date']) ? $_POST['date'] : date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="name"><i class="fas fa-tag"></i> Income Name</label>
                    <input type="text" id="name" name="name" placeholder="Salary, Freelance, Business, etc." value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="amount"><i class="fas fa-money-bill"></i> Amount (BDT)</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #666; font-weight: bold;">৳</span>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="0.00" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : ''; ?>" required style="padding-left: 30px;">
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Add Income
                </button>
            </form>
        </div>

        <div class="table-container">
            <h2><i class="fas fa-list"></i> Recent Income</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Amount (BDT)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($income_records)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No income records found. Add your first income!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($income_records as $income): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($income['date'])); ?></td>
                            <td><?php echo htmlspecialchars($income['name']); ?></td>
                            <td class="income-amount"><span class="currency-symbol">৳</span><?php echo number_format($income['amount'], 2); ?></td>
                            <td class="actions">
                                <a href="edit_income.php?id=<?php echo $income['id']; ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_income.php?id=<?php echo $income['id']; ?>" 
                                   class="btn-action btn-delete" 
                                   onclick="return confirm('Are you sure you want to delete this income record?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Total Income</strong></td>
                        <td><strong class="total-amount">৳<?php echo number_format($total_income, 2); ?></strong></td>
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