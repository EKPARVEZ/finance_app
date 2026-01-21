<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Set headers for Excel file download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="finance_report_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Get date range (default: current month)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get income data for the period
$income_stmt = $pdo->prepare("
    SELECT * FROM income 
    WHERE user_id = ? 
    AND date BETWEEN ? AND ?
    ORDER BY date DESC
");
$income_stmt->execute([$user_id, $start_date, $end_date]);
$income_records = $income_stmt->fetchAll();

// Get total income for the period
$income_total_stmt = $pdo->prepare("
    SELECT SUM(amount) as total FROM income 
    WHERE user_id = ? 
    AND date BETWEEN ? AND ?
");
$income_total_stmt->execute([$user_id, $start_date, $end_date]);
$total_income = $income_total_stmt->fetch()['total'] ?? 0;

// Get expense data for the period
$expense_stmt = $pdo->prepare("
    SELECT * FROM expenses 
    WHERE user_id = ? 
    AND date BETWEEN ? AND ?
    ORDER BY date DESC
");
$expense_stmt->execute([$user_id, $start_date, $end_date]);
$expense_records = $expense_stmt->fetchAll();

// Get total expenses for the period
$expense_total_stmt = $pdo->prepare("
    SELECT SUM(amount) as total FROM expenses 
    WHERE user_id = ? 
    AND date BETWEEN ? AND ?
");
$expense_total_stmt->execute([$user_id, $start_date, $end_date]);
$total_expenses = $expense_total_stmt->fetch()['total'] ?? 0;

// Calculate balance
$balance = $total_income - $total_expenses;

// Get all-time totals for summary
$all_time_income_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM income WHERE user_id = ?");
$all_time_income_stmt->execute([$user_id]);
$all_time_income = $all_time_income_stmt->fetch()['total'] ?? 0;

$all_time_expense_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ?");
$all_time_expense_stmt->execute([$user_id]);
$all_time_expenses = $all_time_expense_stmt->fetch()['total'] ?? 0;

$all_time_balance = $all_time_income - $all_time_expenses;
?>

<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #dee2e6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .income-row {
            background-color: #d4edda;
        }
        .expense-row {
            background-color: #f8d7da;
        }
        .total-row {
            background-color: #cce5ff;
            font-weight: bold;
        }
        .balance-positive {
            color: #28a745;
            font-weight: bold;
        }
        .balance-negative {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Report Header -->
<div class="header">
    <h1>Finance Report</h1>
    <h3>Period: <?php echo date('F d, Y', strtotime($start_date)); ?> to <?php echo date('F d, Y', strtotime($end_date)); ?></h3>
    <p>Generated on: <?php echo date('F d, Y H:i:s'); ?></p>
</div>

<!-- Summary Section -->
<div class="summary">
    <h2>Financial Summary</h2>
    <table>
        <tr>
            <td><strong>Report Period:</strong></td>
            <td><?php echo date('F d, Y', strtotime($start_date)); ?> to <?php echo date('F d, Y', strtotime($end_date)); ?></td>
        </tr>
        <tr>
            <td><strong>Total Income:</strong></td>
            <td class="balance-positive">৳ <?php echo number_format($total_income, 2); ?></td>
        </tr>
        <tr>
            <td><strong>Total Expenses:</strong></td>
            <td class="balance-negative">৳ <?php echo number_format($total_expenses, 2); ?></td>
        </tr>
        <tr>
            <td><strong>Net Balance:</strong></td>
            <td class="<?php echo $balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                ৳ <?php echo number_format($balance, 2); ?>
            </td>
        </tr>
        <tr>
            <td><strong>All-time Income:</strong></td>
            <td>৳ <?php echo number_format($all_time_income, 2); ?></td>
        </tr>
        <tr>
            <td><strong>All-time Expenses:</strong></td>
            <td>৳ <?php echo number_format($all_time_expenses, 2); ?></td>
        </tr>
        <tr>
            <td><strong>All-time Balance:</strong></td>
            <td class="<?php echo $all_time_balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                ৳ <?php echo number_format($all_time_balance, 2); ?>
            </td>
        </tr>
    </table>
</div>

<!-- Income Section -->
<h2>Income Details (<?php echo count($income_records); ?> records)</h2>
<?php if (count($income_records) > 0): ?>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Amount (BDT)</th>
            <th>Added Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($income_records as $income): ?>
        <tr class="income-row">
            <td><?php echo date('d/m/Y', strtotime($income['date'])); ?></td>
            <td><?php echo htmlspecialchars($income['name']); ?></td>
            <td>৳ <?php echo number_format($income['amount'], 2); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($income['created_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="2"><strong>Total Income</strong></td>
            <td><strong>৳ <?php echo number_format($total_income, 2); ?></strong></td>
            <td></td>
        </tr>
    </tbody>
</table>
<?php else: ?>
<p>No income records found for this period.</p>
<?php endif; ?>

<!-- Expense Section -->
<h2>Expense Details (<?php echo count($expense_records); ?> records)</h2>
<?php if (count($expense_records) > 0): ?>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Amount (BDT)</th>
            <th>Added Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($expense_records as $expense): ?>
        <tr class="expense-row">
            <td><?php echo date('d/m/Y', strtotime($expense['date'])); ?></td>
            <td><?php echo htmlspecialchars($expense['description']); ?></td>
            <td>৳ <?php echo number_format($expense['amount'], 2); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($expense['created_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="2"><strong>Total Expenses</strong></td>
            <td><strong>৳ <?php echo number_format($total_expenses, 2); ?></strong></td>
            <td></td>
        </tr>
    </tbody>
</table>
<?php else: ?>
<p>No expense records found for this period.</p>
<?php endif; ?>

<!-- Daily Summary Table -->
<h2>Daily Summary</h2>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Income</th>
            <th>Expenses</th>
            <th>Daily Balance</th>
            <th>Net Balance</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Get daily summary
        $daily_stmt = $pdo->prepare("
            SELECT 
                date,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount END), 0) as daily_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount END), 0) as daily_expense
            FROM (
                SELECT date, amount, 'income' as type FROM income WHERE user_id = ? AND date BETWEEN ? AND ?
                UNION ALL
                SELECT date, amount, 'expense' as type FROM expenses WHERE user_id = ? AND date BETWEEN ? AND ?
            ) as transactions
            GROUP BY date
            ORDER BY date
        ");
        $daily_stmt->execute([$user_id, $start_date, $end_date, $user_id, $start_date, $end_date]);
        $daily_summary = $daily_stmt->fetchAll();
        
        $running_balance = 0;
        foreach ($daily_summary as $day):
            $daily_balance = $day['daily_income'] - $day['daily_expense'];
            $running_balance += $daily_balance;
        ?>
        <tr>
            <td><?php echo date('d/m/Y', strtotime($day['date'])); ?></td>
            <td class="balance-positive">৳ <?php echo number_format($day['daily_income'], 2); ?></td>
            <td class="balance-negative">৳ <?php echo number_format($day['daily_expense'], 2); ?></td>
            <td class="<?php echo $daily_balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                ৳ <?php echo number_format($daily_balance, 2); ?>
            </td>
            <td class="<?php echo $running_balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                ৳ <?php echo number_format($running_balance, 2); ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Category Summary (if you have categories) -->
<h2>Category Summary</h2>
<table>
    <thead>
        <tr>
            <th>Category Type</th>
            <th>Count</th>
            <th>Total Amount (BDT)</th>
            <th>Percentage</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Get income categories summary
        $income_cat_stmt = $pdo->prepare("
            SELECT 
                'Income' as type,
                COUNT(*) as count,
                SUM(amount) as total,
                ROUND((SUM(amount) / ? * 100), 2) as percentage
            FROM income 
            WHERE user_id = ? AND date BETWEEN ? AND ?
            GROUP BY 'type'
        ");
        $income_cat_stmt->execute([$total_income > 0 ? $total_income : 1, $user_id, $start_date, $end_date]);
        $income_summary = $income_cat_stmt->fetch();
        
        if ($income_summary && $income_summary['total'] > 0):
        ?>
        <tr class="income-row">
            <td><strong>Income</strong></td>
            <td><?php echo $income_summary['count']; ?></td>
            <td>৳ <?php echo number_format($income_summary['total'], 2); ?></td>
            <td><?php echo $income_summary['percentage']; ?>%</td>
        </tr>
        <?php endif; ?>
        
        <?php
        // Get expense categories summary (group by first word of description)
        $expense_cat_stmt = $pdo->prepare("
            SELECT 
                CASE 
                    WHEN UPPER(description) LIKE '%FOOD%' OR UPPER(description) LIKE '%MEAL%' OR UPPER(description) LIKE '%GROCERY%' THEN 'Food'
                    WHEN UPPER(description) LIKE '%RENT%' OR UPPER(description) LIKE '%HOUSE%' THEN 'Rent'
                    WHEN UPPER(description) LIKE '%TRANSPORT%' OR UPPER(description) LIKE '%BUS%' OR UPPER(description) LIKE '%CAR%' THEN 'Transport'
                    WHEN UPPER(description) LIKE '%SHOPPING%' OR UPPER(description) LIKE '%CLOTH%' THEN 'Shopping'
                    WHEN UPPER(description) LIKE '%BILL%' OR UPPER(description) LIKE '%ELECTRIC%' OR UPPER(description) LIKE '%WATER%' THEN 'Utilities'
                    WHEN UPPER(description) LIKE '%ENTERTAINMENT%' OR UPPER(description) LIKE '%MOVIE%' OR UPPER(description) LIKE '%GAME%' THEN 'Entertainment'
                    ELSE 'Other'
                END as category,
                COUNT(*) as count,
                SUM(amount) as total,
                ROUND((SUM(amount) / ? * 100), 2) as percentage
            FROM expenses 
            WHERE user_id = ? AND date BETWEEN ? AND ?
            GROUP BY category
            ORDER BY total DESC
        ");
        $expense_cat_stmt->execute([$total_expenses > 0 ? $total_expenses : 1, $user_id, $start_date, $end_date]);
        $expense_categories = $expense_cat_stmt->fetchAll();
        
        foreach ($expense_categories as $category):
        ?>
        <tr class="expense-row">
            <td><?php echo $category['category']; ?></td>
            <td><?php echo $category['count']; ?></td>
            <td>৳ <?php echo number_format($category['total'], 2); ?></td>
            <td><?php echo $category['percentage']; ?>%</td>
        </tr>
        <?php endforeach; ?>
        
        <?php if ($total_expenses > 0): ?>
        <tr class="total-row">
            <td><strong>Total Expenses</strong></td>
            <td><?php echo count($expense_records); ?></td>
            <td><strong>৳ <?php echo number_format($total_expenses, 2); ?></strong></td>
            <td><strong>100%</strong></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Monthly Trends -->
<h2>Monthly Trends (Last 6 Months)</h2>
<table>
    <thead>
        <tr>
            <th>Month</th>
            <th>Income</th>
            <th>Expenses</th>
            <th>Balance</th>
            <th>Savings Rate</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Get monthly trends for last 6 months
        $monthly_stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                DATE_FORMAT(date, '%M %Y') as month_name,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount END), 0) as expense
            FROM (
                SELECT date, amount, 'income' as type FROM income WHERE user_id = ?
                UNION ALL
                SELECT date, amount, 'expense' as type FROM expenses WHERE user_id = ?
            ) as transactions
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month DESC
        ");
        $monthly_stmt->execute([$user_id, $user_id]);
        $monthly_trends = $monthly_stmt->fetchAll();
        
        foreach ($monthly_trends as $month):
            $month_balance = $month['income'] - $month['expense'];
            $savings_rate = $month['income'] > 0 ? round(($month_balance / $month['income']) * 100, 2) : 0;
        ?>
        <tr>
            <td><?php echo $month['month_name']; ?></td>
            <td class="balance-positive">৳ <?php echo number_format($month['income'], 2); ?></td>
            <td class="balance-negative">৳ <?php echo number_format($month['expense'], 2); ?></td>
            <td class="<?php echo $month_balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                ৳ <?php echo number_format($month_balance, 2); ?>
            </td>
            <td class="<?php echo $savings_rate >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
                <?php echo $savings_rate; ?>%
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Footer -->
<div style="margin-top: 50px; padding: 20px; border-top: 2px solid #ddd; text-align: center;">
    <p><strong>Generated by: <?php echo $_SESSION['username']; ?></strong></p>
    <p><em>This is an auto-generated report from Finance Tracker System</em></p>
    <p>For any queries, please contact: finance@tracker.com</p>
</div>

</body>
</html>