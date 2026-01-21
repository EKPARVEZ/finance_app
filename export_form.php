<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Finance Report - Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .export-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .export-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .export-header i {
            font-size: 3rem;
            color: #2ecc71;
            margin-bottom: 15px;
        }
        
        .export-options {
            margin: 20px 0;
        }
        
        .option-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            cursor: pointer;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .option-card:hover {
            border-color: #3498db;
            background: #e3f2fd;
        }
        
        .option-card.selected {
            border-color: #2ecc71;
            background: #d4edda;
        }
        
        .option-card h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .option-card p {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .date-range {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn-export {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-export:hover {
            background: linear-gradient(135deg, #27ae60, #219653);
            transform: translateY(-2px);
        }
        
        .format-options {
            display: flex;
            gap: 15px;
            margin: 20px 0;
        }
        
        .format-btn {
            flex: 1;
            padding: 10px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .format-btn.selected {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .preview-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            display: none;
        }
        
        .preview-section.active {
            display: block;
        }
        
        .preview-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .preview-item:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .export-container {
                margin: 20px;
                padding: 20px;
            }
            
            .date-range {
                grid-template-columns: 1fr;
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
                <a href="income.php"><i class="fas fa-money-bill-wave"></i> Income</a>
                <a href="expenses.php"><i class="fas fa-shopping-cart"></i> Expenses</a>
                <a href="view_income.php"><i class="fas fa-eye"></i> View Income</a>
                <a href="view_expenses.php"><i class="fas fa-eye"></i> View Expenses</a>
                <a href="export_form.php" class="active"><i class="fas fa-file-export"></i> Export</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <div class="export-container">
            <div class="export-header">
                <i class="fas fa-file-excel"></i>
                <h2>Export Finance Report</h2>
                <p>Generate detailed Excel reports of your financial data</p>
            </div>

            <form id="exportForm" action="export.php" method="GET">
                <div class="export-options">
                    <h3><i class="fas fa-calendar"></i> Select Report Period</h3>
                    
                    <div class="option-card selected" data-period="month">
                        <h4><i class="fas fa-calendar-alt"></i> Current Month</h4>
                        <p>Export data for <?php echo date('F Y'); ?></p>
                    </div>
                    
                    <div class="option-card" data-period="custom">
                        <h4><i class="fas fa-calendar-day"></i> Custom Date Range</h4>
                        <p>Select specific start and end dates</p>
                    </div>
                    
                    <div class="option-card" data-period="year">
                        <h4><i class="fas fa-calendar-check"></i> This Year</h4>
                        <p>Export all data for <?php echo date('Y'); ?></p>
                    </div>
                </div>

                <div id="dateRangeSection" class="date-range" style="display: none;">
                    <div class="form-group">
                        <label for="start_date"><i class="fas fa-calendar-plus"></i> Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date"><i class="fas fa-calendar-minus"></i> End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo date('Y-m-t'); ?>">
                    </div>
                </div>

                <div class="format-options">
                    <h3><i class="fas fa-file"></i> Export Format</h3>
                    <div class="format-btn selected" data-format="excel">
                        <i class="fas fa-file-excel"></i> Excel (XLS)
                    </div>
                    <div class="format-btn" data-format="pdf" style="display: none;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </div>
                </div>

                <div class="preview-section" id="previewSection">
                    <h3><i class="fas fa-eye"></i> Report Preview</h3>
                    <div id="previewContent">
                        <!-- Preview will be loaded here -->
                    </div>
                </div>

                <button type="submit" class="btn-export">
                    <i class="fas fa-download"></i> Generate & Download Report
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const optionCards = document.querySelectorAll('.option-card');
            const dateRangeSection = document.getElementById('dateRangeSection');
            const formatBtns = document.querySelectorAll('.format-btn');
            const previewSection = document.getElementById('previewSection');
            const previewContent = document.getElementById('previewContent');
            
            let selectedPeriod = 'month';
            let selectedFormat = 'excel';
            
            // Handle period selection
            optionCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    optionCards.forEach(c => c.classList.remove('selected'));
                    
                    // Add selected class to clicked card
                    this.classList.add('selected');
                    
                    // Get selected period
                    selectedPeriod = this.dataset.period;
                    
                    // Show/hide date range section
                    if (selectedPeriod === 'custom') {
                        dateRangeSection.style.display = 'grid';
                    } else {
                        dateRangeSection.style.display = 'none';
                        
                        // Set default dates based on period
                        const today = new Date();
                        const startDate = document.getElementById('start_date');
                        const endDate = document.getElementById('end_date');
                        
                        if (selectedPeriod === 'month') {
                            // Current month
                            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                            
                            startDate.value = firstDay.toISOString().split('T')[0];
                            endDate.value = lastDay.toISOString().split('T')[0];
                        } else if (selectedPeriod === 'year') {
                            // Current year
                            const firstDay = new Date(today.getFullYear(), 0, 1);
                            const lastDay = new Date(today.getFullYear(), 11, 31);
                            
                            startDate.value = firstDay.toISOString().split('T')[0];
                            endDate.value = lastDay.toISOString().split('T')[0];
                        }
                    }
                    
                    // Update preview
                    updatePreview();
                });
            });
            
            // Handle format selection
            formatBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove selected class from all buttons
                    formatBtns.forEach(b => b.classList.remove('selected'));
                    
                    // Add selected class to clicked button
                    this.classList.add('selected');
                    
                    // Get selected format
                    selectedFormat = this.dataset.format;
                    
                    // Update form action
                    const form = document.getElementById('exportForm');
                    if (selectedFormat === 'pdf') {
                        form.action = 'export_pdf.php';
                    } else {
                        form.action = 'export.php';
                    }
                });
            });
            
            // Update preview when dates change
            document.getElementById('start_date').addEventListener('change', updatePreview);
            document.getElementById('end_date').addEventListener('change', updatePreview);
            
            // Function to update preview
            function updatePreview() {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                
                if (startDate && endDate) {
                    const start = new Date(startDate);
                    const end = new Date(endDate);
                    
                    // Calculate days between dates
                    const timeDiff = end.getTime() - start.getTime();
                    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                    
                    // Update preview content
                    previewContent.innerHTML = `
                        <div class="preview-item">
                            <span>Report Period:</span>
                            <span>${formatDate(start)} to ${formatDate(end)}</span>
                        </div>
                        <div class="preview-item">
                            <span>Duration:</span>
                            <span>${daysDiff} days</span>
                        </div>
                        <div class="preview-item">
                            <span>Report Includes:</span>
                            <span>Income & Expense Details</span>
                        </div>
                        <div class="preview-item">
                            <span>Format:</span>
                            <span>${selectedFormat === 'excel' ? 'Excel Spreadsheet' : 'PDF Document'}</span>
                        </div>
                    `;
                    
                    // Show preview section
                    previewSection.classList.add('active');
                }
            }
            
            // Helper function to format date
            function formatDate(date) {
                return date.toLocaleDateString('en-US', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            }
            
            // Initialize preview
            updatePreview();
        });
    </script>
</body>
</html>