class FinanceTracker {
    constructor() {
        this.baseUrl = 'http://localhost/finance-tracker/backend/api.php';
        this.init();
    }

    init() {
        this.loadDashboard();
        this.setupEventListeners();
    }

    async fetchData(endpoint) {
        try {
            const response = await fetch(`${this.baseUrl}/${endpoint}`);
            return await response.json();
        } catch (error) {
            console.error('Error:', error);
            return null;
        }
    }

    async loadDashboard() {
        if (!document.querySelector('.summary-cards')) return;
        
        const summary = await this.fetchData('summary');
        const income = await this.fetchData('income');
        const expenses = await this.fetchData('expenses');
        
        this.updateSummaryCards(summary);
        this.displayRecentTransactions(income, expenses);
    }

    updateSummaryCards(summary) {
        if (!summary) return;
        
        document.querySelector('.income-total').textContent = 
            `$${summary.total_income || 0}`;
        document.querySelector('.expense-total').textContent = 
            `$${summary.total_expenses || 0}`;
        document.querySelector('.balance-total').textContent = 
            `$${summary.balance || 0}`;
        
        // Update balance color
        const balanceEl = document.querySelector('.balance-total');
        balanceEl.style.color = summary.balance >= 0 ? '#4CAF50' : '#f44336';
    }

    displayRecentTransactions(income, expenses) {
        const container = document.getElementById('recent-transactions');
        if (!container) return;
        
        container.innerHTML = '';
        
        const combined = [
            ...(income || []).map(item => ({...item, type: 'income'})),
            ...(expenses || []).map(item => ({...item, type: 'expense'}))
        ]
        .sort((a, b) => new Date(b.date) - new Date(a.date))
        .slice(0, 10);
        
        combined.forEach(item => {
            const row = document.createElement('tr');
            const amount = parseFloat(item.amount).toFixed(2);
            
            row.innerHTML = `
                <td>${item.date}</td>
                <td>${item.type === 'income' ? item.name : item.description}</td>
                <td>${item.type === 'income' ? 'Income' : 'Expense'}</td>
                <td style="color: ${item.type === 'income' ? '#4CAF50' : '#f44336'}">
                    ${item.type === 'income' ? '+' : '-'}$${amount}
                </td>
            `;
            container.appendChild(row);
        });
    }

    setupEventListeners() {
        // Income form submission
        const incomeForm = document.getElementById('income-form');
        if (incomeForm) {
            incomeForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.addIncome();
            });
        }

        // Expense form submission
        const expenseForm = document.getElementById('expense-form');
        if (expenseForm) {
            expenseForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.addExpense();
            });
        }

        // Load table data
        if (window.location.pathname.includes('income.html')) {
            this.loadIncomeTable();
        } else if (window.location.pathname.includes('expense.html')) {
            this.loadExpenseTable();
        }
    }

    async loadIncomeTable() {
        const income = await this.fetchData('income');
        if (!income) return;
        
        const container = document.getElementById('income-table-body');
        if (!container) return;
        
        container.innerHTML = '';
        let total = 0;
        
        income.forEach(item => {
            total += parseFloat(item.amount);
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.date}</td>
                <td>${item.name}</td>
                <td>$${parseFloat(item.amount).toFixed(2)}</td>
                <td>
                    <button class="delete-btn" onclick="tracker.deleteIncome(${item.id})">
                        Delete
                    </button>
                </td>
            `;
            container.appendChild(row);
        });
        
        document.getElementById('income-total').textContent = `$${total.toFixed(2)}`;
    }

    async loadExpenseTable() {
        const expenses = await this.fetchData('expenses');
        if (!expenses) return;
        
        const container = document.getElementById('expense-table-body');
        if (!container) return;
        
        container.innerHTML = '';
        let total = 0;
        
        expenses.forEach(item => {
            total += parseFloat(item.amount);
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.date}</td>
                <td>${item.description}</td>
                <td>${item.category || '-'}</td>
                <td>$${parseFloat(item.amount).toFixed(2)}</td>
                <td>
                    <button class="delete-btn" onclick="tracker.deleteExpense(${item.id})">
                        Delete
                    </button>
                </td>
            `;
            container.appendChild(row);
        });
        
        document.getElementById('expense-total').textContent = `$${total.toFixed(2)}`;
    }

    async addIncome() {
        const form = document.getElementById('income-form');
        const formData = {
            date: form.date.value,
            name: form.name.value,
            amount: parseFloat(form.amount.value)
        };

        try {
            const response = await fetch(`${this.baseUrl}/income`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Income added successfully!');
                form.reset();
                this.loadIncomeTable();
            } else {
                alert('Error adding income');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async addExpense() {
        const form = document.getElementById('expense-form');
        const formData = {
            date: form.date.value,
            description: form.description.value,
            amount: parseFloat(form.amount.value),
            category: form.category.value
        };

        try {
            const response = await fetch(`${this.baseUrl}/expenses`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Expense added successfully!');
                form.reset();
                this.loadExpenseTable();
            } else {
                alert('Error adding expense');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async deleteIncome(id) {
        if (!confirm('Are you sure you want to delete this income record?')) return;
        
        try {
            const response = await fetch(`${this.baseUrl}/income/${id}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.loadIncomeTable();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async deleteExpense(id) {
        if (!confirm('Are you sure you want to delete this expense?')) return;
        
        try {
            const response = await fetch(`${this.baseUrl}/expenses/${id}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.loadExpenseTable();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
}

// Initialize tracker
const tracker = new FinanceTracker();