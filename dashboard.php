<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get total income
$income_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM income WHERE user_id = ?");
$income_stmt->execute([$user_id]);
$total_income = $income_stmt->fetch()['total'] ?? 0;

// Get total expenses
$expense_stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ?");
$expense_stmt->execute([$user_id]);
$total_expenses = $expense_stmt->fetch()['total'] ?? 0;

// Get balance
$balance = $total_income - $total_expenses;

// Get recent transactions
$recent_stmt = $pdo->prepare("
    (SELECT date, name as description, amount, 'income' as type FROM income WHERE user_id = ? ORDER BY date DESC LIMIT 5)
    UNION ALL
    (SELECT date, description, amount, 'expense' as type FROM expenses WHERE user_id = ? ORDER BY date DESC LIMIT 5)
    ORDER BY date DESC LIMIT 10
");
$recent_stmt->execute([$user_id, $user_id]);
$recent_transactions = $recent_stmt->fetchAll();

// Get monthly data for chart (last 6 months)
$monthly_stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
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
$monthly_data = $monthly_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="nav-brand">
                <i class="fas fa-chart-line"></i>BD Technology Dashboard <span class="bdt-badge">BDT</span>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="income.php"><i class="fas fa-money-bill-wave"></i> Income</a>
                <a href="expenses.php"><i class="fas fa-shopping-cart"></i> Expenses</a>
                <a href="view_income.php"><i class="fas fa-eye"></i> View Income</a>
                <a href="view_expenses.php"><i class="fas fa-eye"></i> View Expenses</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <div class="welcome-section">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Here's your financial overview for <?php echo date('d F Y'); ?></p>
        </div>

        <div class="summary-cards">
            <div class="card income">
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-content">
                    <h3>Total Income</h3>
                    <p class="total-amount">৳<?php echo number_format($total_income, 2); ?></p>
                    <small>All time income</small>
                </div>
            </div>
            
            <div class="card expense">
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-content">
                    <h3>Total Expenses</h3>
                    <p class="total-amount expense-amount">৳<?php echo number_format($total_expenses, 2); ?></p>
                    <small>All time expenses</small>
                </div>
            </div>
            
            <div class="card balance">
                <div class="card-icon">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="card-content">
                    <h3>Current Balance</h3>
                    <p class="total-amount" style="color: <?php echo $balance >= 0 ? '#2ecc71' : '#e74c3c'; ?>">
                        ৳<?php echo number_format($balance, 2); ?>
                    </p>
                    <small>Income - Expenses</small>
                </div>
            </div>
        </div>
		

        <div class="chart-container">
            <h2><i class="fas fa-chart-bar"></i> Monthly Overview</h2>
            <canvas id="monthlyChart"></canvas>
        </div>

        <div class="recent-transactions">
            <h2><i class="fas fa-history"></i> Recent Transactions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Amount (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_transactions)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No transactions yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $transaction): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($transaction['date'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td>
                                <span class="type-<?php echo $transaction['type']; ?>">
                                    <?php echo ucfirst($transaction['type']); ?>
                                </span>
                            </td>
                            <td class="<?php echo $transaction['type']; ?>-amount">
                                ৳<?php echo number_format($transaction['amount'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

       

    <script>
        // Prepare data for chart
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        
        // Reverse to show oldest to newest
        monthlyData.reverse();
        
        const months = monthlyData.map(item => {
            const [year, month] = item.month.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleString('default', { month: 'short', year: '2-digit' });
        });
        
        const incomeData = monthlyData.map(item => parseFloat(item.income) || 0);
        const expenseData = monthlyData.map(item => parseFloat(item.expense) || 0);
        
        // Create chart
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Income (BDT)',
                        data: incomeData,
                        backgroundColor: 'rgba(46, 204, 113, 0.7)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Expenses (BDT)',
                        data: expenseData,
                        backgroundColor: 'rgba(231, 76, 60, 0.7)',
                        borderColor: 'rgba(231, 76, 60, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '৳' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ৳' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Format all currency amounts on page
        document.addEventListener('DOMContentLoaded', function() {
            // You can add additional JavaScript formatting here if needed
        });
    </script>
</body>
</html>