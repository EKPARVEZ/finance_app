<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);
    
    $_SESSION['message'] = 'Expense record deleted successfully!';
    $_SESSION['message_type'] = 'success';
    header('Location: view_expenses.php');
    exit();
}

// Get all expense records
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC");
$stmt->execute([$user_id]);
$expense_records = $stmt->fetchAll();

// Get total expenses
$total_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ?");
$total_stmt->execute([$user_id]);
$total_expenses = $total_stmt->fetch()['total'] ?? 0;

// Get total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expenses WHERE user_id = ?");
$count_stmt->execute([$user_id]);
$expense_count = $count_stmt->fetch()['count'];

// Display session messages
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Expenses - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .card.expense {
            border-top: 5px solid #e74c3c;
        }
        
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .card.expense .card-icon {
            color: #e74c3c;
        }
        
        .card-content h3 {
            color: #7f8c8d;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .card-content p {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .card-content small {
            color: #95a5a6;
            font-size: 14px;
        }
        
        /* Table Styles */
        .data-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table thead {
            background-color: #3498db;
            color: white;
        }
        
        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .data-table tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .expense-amount {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .expense-currency {
            color: #e74c3c;
            font-weight: bold;
            margin-right: 2px;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 5px 10px;
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
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        /* No Records Message */
        .no-records {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .no-records i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }
        
        /* Message Styles */
        .message {
            padding: 12px 20px;
            margin: 15px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        /* Filter and Search */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn-filter {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-filter:hover {
            background: #2980b9;
        }
        
        /* Export Button */
        .export-btn {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        
        .export-btn:hover {
            background: #27ae60;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
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
                <a href="income.php"><i class="fas fa-money-bill-wave"></i> Add Income</a>
                <a href="expenses.php"><i class="fas fa-shopping-cart"></i> Add Expense</a>
                <a href="view_income.php"><i class="fas fa-eye"></i> View Income</a>
                <a href="view_expenses.php" class="active"><i class="fas fa-eye"></i> View Expenses</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> Expense Records</h1>
            <p>View and manage all your expense transactions</p>
        </div>

        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="card expense">
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-content">
                    <h3>Total Expenses</h3>
                    <p><span class="expense-currency">৳</span><?php echo number_format($total_expenses, 2); ?></p>
                    <small>All time expenses</small>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="card-content">
                    <h3>Total Transactions</h3>
                    <p><?php echo $expense_count; ?></p>
                    <small>Number of expense records</small>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="card-content">
                    <h3>Current Month</h3>
                    <p><span class="expense-currency">৳</span><?php 
                        $current_month = date('Y-m');
                        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
                        $stmt->execute([$user_id, $current_month]);
                        $month_total = $stmt->fetch()['total'] ?? 0;
                        echo number_format($month_total, 2);
                    ?></p>
                    <small>Expenses for <?php echo date('F Y'); ?></small>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Filter Expenses</h3>
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label for="start_date"><i class="fas fa-calendar-alt"></i> Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_date"><i class="fas fa-calendar-alt"></i> End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Search</label>
                    <input type="text" id="search" name="search" placeholder="Search by description..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
                <a href="view_expenses.php" class="btn-filter" style="background: #95a5a6;">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
        </div>
<div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
    <a href="export_form.php" class="export-btn">
        <i class="fas fa-file-export"></i> Full Report
    </a>
    <a href="export.php?start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-t'); ?>" class="export-btn" style="background: #3498db;">
        <i class="fas fa-download"></i> Export Current Month
    </a>
</div>
        <!-- Expenses Table -->
        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-list"></i> All Expense Records</h2>
               
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount (BDT)</th>
                        <th>Added On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expense_records)): ?>
                        <tr>
                            <td colspan="5" class="no-records">
                                <i class="fas fa-file-invoice"></i>
                                <h3>No Expense Records Found</h3>
                                <p>Start by adding your first expense record</p>
                                <a href="expenses.php" class="btn" style="margin-top: 15px; background: linear-gradient(135deg, #e74c3c, #c0392b);">
                                    <i class="fas fa-plus-circle"></i> Add Expense
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($expense_records as $expense): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($expense['date'])); ?></td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td class="expense-amount"><span class="expense-currency">৳</span><?php echo number_format($expense['amount'], 2); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($expense['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="view_expenses.php?delete_id=<?php echo $expense['id']; ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this expense record?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong class="expense-amount"><span class="expense-currency">৳</span><?php echo number_format($total_expenses, 2); ?></strong></td>
                        <td colspan="2"></td>
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
        
        // Set default date values for filters
        document.addEventListener('DOMContentLoaded', function() {
            var startDate = document.getElementById('start_date');
            var endDate = document.getElementById('end_date');
            
            // Set end date to today
            if (!endDate.value) {
                var today = new Date().toISOString().split('T')[0];
                endDate.value = today;
            }
            
            // Set start date to first day of current month
            if (!startDate.value) {
                var firstDay = new Date();
                firstDay.setDate(1);
                startDate.value = firstDay.toISOString().split('T')[0];
            }
            
            // Confirm delete function
            var deleteLinks = document.querySelectorAll('.btn-delete');
            deleteLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this expense record?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>