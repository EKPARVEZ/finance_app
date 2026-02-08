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
    // Check if it's a bulk delete request
    if (isset($_POST['bulk_delete']) && isset($_POST['selected_ids']) && !empty($_POST['selected_ids'])) {
        $selected_ids = $_POST['selected_ids'];
        
        // Ensure selected_ids is an array
        if (is_string($selected_ids)) {
            $selected_ids = explode(',', $selected_ids);
        }
        
        if (empty($selected_ids)) {
            $message = 'Please select at least one expense record to delete!';
            $message_type = 'error';
        } else {
            try {
                // Create placeholders for the prepared statement
                $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
                
                // Prepare and execute delete query
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE id IN ($placeholders) AND user_id = ?");
                
                // Bind parameters: selected_ids + user_id
                $params = array_merge($selected_ids, [$user_id]);
                $stmt->execute($params);
                
                $deleted_count = $stmt->rowCount();
                $message = "Successfully deleted $deleted_count expense record(s)!";
                $message_type = 'success';
                
                // Redirect to refresh the page and avoid form resubmission
                header("Location: expenses.php?message=" . urlencode($message) . "&type=" . $message_type);
                exit();
                
            } catch(PDOException $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } 
    // Handle single expense addition
    else {
        $date = isset($_POST['date']) ? trim($_POST['date']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $amount = isset($_POST['amount']) ? trim($_POST['amount']) : '';
        
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
                
                // Redirect to refresh the page and avoid form resubmission
                header("Location: expenses.php?message=" . urlencode($message) . "&type=" . $message_type);
                exit();
                
            } catch(PDOException $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Check for message in URL (from redirect)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['type'];
}

// Get expense records for display
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC");
$stmt->execute([$user_id]);
$expense_records = $stmt->fetchAll();

// Calculate total expenses
$total_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ?");
$total_stmt->execute([$user_id]);
$total_expenses = $total_stmt->fetch()['total'] ?? 0;

// Set default values for form
$form_date = date('Y-m-d');
$form_description = '';
$form_amount = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management - Finance Tracker</title>
	<link rel="icon" type="image/png" sizes="32x32" href="bd.png">
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
        
        /* Bulk Actions Styles */
        .bulk-actions {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .bulk-actions h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #495057;
        }
        
        .checkbox-group {
            margin: 10px 0;
        }
        
        .bulk-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-bulk {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn-bulk-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-bulk-delete:hover {
            background-color: #c82333;
        }
        
        .btn-select-all {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-select-all:hover {
            background-color: #5a6268;
        }
        
        .btn-deselect-all {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-deselect-all:hover {
            background-color: #138496;
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
        
        /* Table Column Styles */
        .serial-column {
            width: 50px;
            text-align: center;
        }
        
        .checkbox-column {
            width: 50px;
            text-align: center;
        }
        
        .checkbox-column input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        #bulkDeleteForm {
            margin: 0;
            padding: 0;
        }
        
        .selected-count {
            margin-left: 10px;
            color: #6c757d;
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
				<a href="backup_database.php"><i class="fas fa-database"></i> Backup</a>
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
            <form method="POST" action="" id="expenseForm">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" id="date" name="date" value="<?php echo $form_date; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-file-alt"></i> Description</label>
                    <input type="text" id="description" name="description" placeholder="Food, Rent, Transport, Shopping, etc." value="<?php echo $form_description; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="amount"><i class="fas fa-money-bill"></i> Amount (BDT)</label>
                    <div class="currency-input">
                        <span>৳</span>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder="0.00" value="<?php echo $form_amount; ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fas fa-save"></i> Add Expense
                </button>
            </form>
        </div>

        <?php if (!empty($expense_records)): ?>
        <div class="bulk-actions">
            <h3><i class="fas fa-tasks"></i> Bulk Actions 
                <span class="selected-count" id="selectedCount">0 selected</span>
            </h3>
            <form method="POST" action="" id="bulkDeleteForm">
                <input type="hidden" name="bulk_delete" value="1">
                <input type="hidden" name="selected_ids" id="selectedIds" value="">
                
                <div class="checkbox-group">
                    <input type="checkbox" id="select_all">
                    <label for="select_all">Select All Records</label>
                </div>
                
                <div class="bulk-buttons">
                    <button type="button" class="btn-bulk btn-bulk-delete" onclick="performBulkDelete()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button type="button" class="btn-bulk btn-select-all" onclick="selectAllRecords()">
                        <i class="fas fa-check-square"></i> Select All
                    </button>
                    <button type="button" class="btn-bulk btn-deselect-all" onclick="deselectAllRecords()">
                        <i class="fas fa-square"></i> Deselect All
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <h2><i class="fas fa-list"></i> Expense Records (<?php echo count($expense_records); ?> records found)</h2>
            <table>
                <thead>
                    <tr>
                        <th class="serial-column">#</th>
                        <?php if (!empty($expense_records)): ?>
                        <th class="checkbox-column">Select</th>
                        <?php endif; ?>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount (BDT)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="expenseTableBody">
                    <?php if (empty($expense_records)): ?>
                        <tr>
                            <td colspan="<?php echo !empty($expense_records) ? '6' : '5'; ?>" style="text-align: center;">No expense records found. Add your first expense!</td>
                        </tr>
                    <?php else: ?>
                        <?php $serial = 1; ?>
                        <?php foreach ($expense_records as $expense): ?>
                        <tr>
                            <td class="serial-column"><?php echo $serial++; ?></td>
                            <td class="checkbox-column">
                                <input type="checkbox" class="record-checkbox" data-id="<?php echo $expense['id']; ?>">
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($expense['date'])); ?></td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td class="expense-amount"><span class="expense-currency">৳</span><?php echo number_format($expense['amount'], 2); ?></td>
                            <td class="actions">
                                <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
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
                        <td colspan="3"><strong>Total Expenses</strong></td>
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
            
            // Form validation for adding expense
            var form = document.getElementById('expenseForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    var amount = document.getElementById('amount').value;
                    if (parseFloat(amount) <= 0) {
                        e.preventDefault();
                        alert('Please enter a valid amount greater than 0.');
                        return false;
                    }
                    return true;
                });
            }
            
            // Initialize selected count
            updateSelectedCount();
        });
        
        // Function to select all records
        function selectAllRecords() {
            var checkboxes = document.querySelectorAll('.record-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
            document.getElementById('select_all').checked = true;
            updateSelectedCount();
        }
        
        // Function to deselect all records
        function deselectAllRecords() {
            var checkboxes = document.querySelectorAll('.record-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
            document.getElementById('select_all').checked = false;
            updateSelectedCount();
        }
        
        // Function to update selected count
        function updateSelectedCount() {
            var selectedCount = document.querySelectorAll('.record-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = selectedCount + ' selected';
        }
        
        // Function to get selected IDs
        function getSelectedIds() {
            var selectedIds = [];
            var checkboxes = document.querySelectorAll('.record-checkbox:checked');
            checkboxes.forEach(function(checkbox) {
                selectedIds.push(checkbox.getAttribute('data-id'));
            });
            return selectedIds;
        }
        
        // Function to perform bulk delete
        function performBulkDelete() {
            var selectedIds = getSelectedIds();
            
            if (selectedIds.length === 0) {
                alert('Please select at least one record to delete.');
                return false;
            }
            
            if (confirm('Are you sure you want to delete ' + selectedIds.length + ' selected expense record(s)? This action cannot be undone.')) {
                // Set the selected IDs in the hidden field
                document.getElementById('selectedIds').value = selectedIds.join(',');
                
                // Submit the bulk delete form
                document.getElementById('bulkDeleteForm').submit();
            }
        }
        
        // Select all checkbox functionality
        var selectAllCheckbox = document.getElementById('select_all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('.record-checkbox');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateSelectedCount();
            });
        }
        
        // Update selected count when individual checkboxes change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('record-checkbox')) {
                var checkboxes = document.querySelectorAll('.record-checkbox');
                var allChecked = true;
                
                checkboxes.forEach(function(checkbox) {
                    if (!checkbox.checked) {
                        allChecked = false;
                    }
                });
                
                var selectAllCheckbox = document.getElementById('select_all');
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                }
                
                updateSelectedCount();
            }
        });
    </script>
	<?php include 'footer.php'; ?>
</body>
</html>


