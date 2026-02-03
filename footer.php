<?php
// footer.php
?>

<footer class="footer">
    <div class="footer-container">
        <!-- Top Section -->
        <div class="footer-top">
            <div class="footer-brand">
                <div class="footer-logo">
                    <?php if(isset($custom_icons)): ?>
                        <?php echo displayIcon('chart', '32px'); ?>
                    <?php else: ?>
                        <i class="fas fa-chart-line"></i>
                    <?php endif; ?>
                    <span>Finance Tracker</span>
                </div>
                <p class="footer-tagline">
                    Track your finances, achieve your goals
                </p>
                <div class="footer-social">
                    <a href="#" class="social-link" title="Facebook">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" class="social-link" title="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link" title="LinkedIn">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <a href="#" class="social-link" title="GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>

            <div class="footer-links">
                <div class="footer-column">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-menu">
                        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="income.php"><i class="fas fa-money-bill-wave"></i> Add Income</a></li>
                        <li><a href="expenses.php"><i class="fas fa-shopping-cart"></i> Add Expense</a></li>
                        <li><a href="view_income.php"><i class="fas fa-eye"></i> View Income</a></li>
                        <li><a href="view_expenses.php"><i class="fas fa-eye"></i> View Expenses</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                   
                </div>

                <div class="footer-column">
                    <h3 class="footer-title">Contact Us</h3>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>bdtechnology2009@gmail.com</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>+8801912981072</span>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Jashore, Khulna, Bangladesh</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

      
              
        <!-- Bottom Section -->
        <div class="footer-bottom">
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> Finance Tracker. All rights reserved.
                <span class="version">v1.0.0</span>
            </div>
            
            
            
            <div class="developer-info">
                Developed with <i class="fas fa-heart" style="color: #e74c3c;"></i> by E.K Parvez
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" class="back-to-top">
    <i class="fas fa-chevron-up"></i>
</button>

<style>
    /* Footer Styles */
    .footer {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: #ecf0f1;
        padding: 40px 0 20px;
        margin-top: 50px;
        border-top: 4px solid #3498db;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .footer-top {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 40px;
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 1px solid #4a6572;
    }

    .footer-brand {
        display: flex;
        flex-direction: column;
    }

    .footer-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
        font-size: 24px;
        font-weight: bold;
        color: #3498db;
    }

    .footer-logo i {
        font-size: 32px;
    }

    .footer-tagline {
        color: #bdc3c7;
        margin-bottom: 20px;
        font-size: 14px;
        line-height: 1.6;
    }

    .footer-social {
        display: flex;
        gap: 15px;
        margin-top: 10px;
    }

    .social-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: #ecf0f1;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .social-link:hover {
        background: #3498db;
        transform: translateY(-3px);
    }

    .footer-links {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .footer-column {
        display: flex;
        flex-direction: column;
    }

    .footer-title {
        color: #3498db;
        font-size: 18px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
    }

    .footer-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-menu li {
        margin-bottom: 12px;
    }

    .footer-menu a {
        color: #bdc3c7;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: color 0.3s ease;
    }

    .footer-menu a:hover {
        color: #3498db;
        padding-left: 5px;
    }

    .footer-menu a i {
        width: 16px;
        text-align: center;
    }

    .footer-contact {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-contact li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 15px;
        color: #bdc3c7;
        font-size: 14px;
    }

    .footer-contact i {
        color: #3498db;
        margin-top: 2px;
    }

    /* Stats Section */
    .footer-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 40px 0;
        padding: 30px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .stat-item {
        text-align: center;
        padding: 15px;
    }

    .stat-number {
        font-size: 36px;
        font-weight: bold;
        color: #3498db;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 14px;
        color: #bdc3c7;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Bottom Section */
    .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 20px;
        border-top: 1px solid #4a6572;
        flex-wrap: wrap;
        gap: 20px;
    }

    .copyright {
        color: #95a5a6;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .version {
        background: #3498db;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
    }

    .footer-bottom-links {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .footer-bottom-links a {
        color: #95a5a6;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }

    .footer-bottom-links a:hover {
        color: #3498db;
    }

    .separator {
        color: #4a6572;
    }

    .developer-info {
        color: #95a5a6;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Back to Top Button */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .back-to-top:hover {
        background: #2980b9;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .footer-top {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .footer-links {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .footer-links {
            grid-template-columns: 1fr;
        }
        
        .footer-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .footer-bottom {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .footer-stats {
            grid-template-columns: 1fr;
        }
        
        .stat-number {
            font-size: 28px;
        }
    }

    /* Dark/Light Mode Toggle (Optional) */
    .theme-toggle {
        position: fixed;
        bottom: 30px;
        left: 30px;
        width: 50px;
        height: 50px;
        background: #2c3e50;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        z-index: 1000;
    }
</style>

<script>
    // Back to Top Button
    document.addEventListener('DOMContentLoaded', function() {
        const backToTopButton = document.getElementById('backToTop');
        
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'flex';
            } else {
                backToTopButton.style.display = 'none';
            }
        });
        
        // Scroll to top when clicked
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Current year for copyright
        const yearSpan = document.querySelector('.copyright');
        if (yearSpan) {
            yearSpan.innerHTML = yearSpan.innerHTML.replace('2024', new Date().getFullYear());
        }
        
        // Animate stats on scroll
        const statNumbers = document.querySelectorAll('.stat-number');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const stat = entry.target;
                    const finalValue = stat.textContent;
                    const suffix = finalValue.includes('৳') ? '৳' : '';
                    const numValue = parseFloat(finalValue.replace(/[^0-9.]/g, ''));
                    
                    let startValue = 0;
                    const duration = 2000;
                    const startTime = performance.now();
                    
                    function animateNumber(currentTime) {
                        const elapsedTime = currentTime - startTime;
                        const progress = Math.min(elapsedTime / duration, 1);
                        
                        const currentValue = Math.floor(startValue + (numValue - startValue) * progress);
                        stat.textContent = suffix + currentValue.toLocaleString();
                        
                        if (progress < 1) {
                            requestAnimationFrame(animateNumber);
                        } else {
                            stat.textContent = finalValue;
                        }
                    }
                    
                    requestAnimationFrame(animateNumber);
                    observer.unobserve(stat);
                }
            });
        }, { threshold: 0.5 });
        
        statNumbers.forEach(stat => observer.observe(stat));
    });
    
    // Theme Toggle Function (Optional)
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        const themeIcon = document.querySelector('.theme-toggle i');
        if (document.body.classList.contains('dark-mode')) {
            themeIcon.className = 'fas fa-sun';
            localStorage.setItem('theme', 'dark');
        } else {
            themeIcon.className = 'fas fa-moon';
            localStorage.setItem('theme', 'light');
        }
    }
    
    // Load saved theme
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            const themeIcon = document.querySelector('.theme-toggle i');
            if (themeIcon) themeIcon.className = 'fas fa-sun';
        }
    });
</script>

<?php
// If you want to add theme toggle button, add this after the footer:
// <button class="theme-toggle" onclick="toggleTheme()">
//     <i class="fas fa-moon"></i>
// </button>
?>