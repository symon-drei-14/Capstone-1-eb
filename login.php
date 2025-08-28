<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mansar Logistics - Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="include/css/login.css">
</head>
<body>
    <div class="bg-element bg-1"></div>
    <div class="bg-element bg-2"></div>
    <div class="bg-element bg-3"></div>
    <div class="bg-element bg-4"></div>
    
    <div class="login-box">
        <div class="login-left">
            <div class="left-content">
                <div class="logo">
                    <i class="fas fa-shipping-fast logo-icon"></i>
                    <span class="logo-text">Mansar Logistics</span>
                </div>
                <h1>Admin Portal Access</h1>
                <p>Manage your logistics operations efficiently with our comprehensive admin dashboard.</p>
                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> Track shipments in real-time</li>
                    <li><i class="fas fa-check-circle"></i> Manage Fleet chuvaness</li>
                    <li><i class="fas fa-check-circle"></i> Monitor fleet and delivery personnelity</li>
                    <li><i class="fas fa-check-circle"></i> View detailed reports and analytics</li>
                </ul>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h2>Admin Login</h2>
                <p>Enter your credentials to access the dashboard</p>
            </div>
            
            <form class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="password-toggle" id="passwordToggle">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                
                
                <button type="submit" class="login-button" id="loginButton">
                    Sign In <i class="fas fa-arrow-right"></i>
                </button>
                
                <div class="error-message" id="errorMessage"></div>
                <div class="success-message" id="successMessage"></div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordInput = document.getElementById('password');
            
            // Toggle password visibility
            passwordToggle.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    passwordToggle.innerHTML = '<i class="far fa-eye-slash"></i>';
                } else {
                    passwordInput.type = 'password';
                    passwordToggle.innerHTML = '<i class="far fa-eye"></i>';
                }
            });
            
            // Form submission
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                
                // Reset messages
                errorMessage.style.display = 'none';
                successMessage.style.display = 'none';
                
                // Basic validation
                if (!username || !password) {
                    showError('Please enter both username and password');
                    return;
                }
                
                if (username.length > 50 || password.length > 255) {
                    showError('Input too long');
                    return;
                }
                
                // Create form data to send
                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);
                formData.append('action', 'login');
                
                // Add CSRF token in a real implementation
                formData.append('csrf_token', '<?php echo $_SESSION['csrf_token'] ?? ''; ?>');
                
                
                loginButton.innerHTML = 'Signing In... <i class="fas fa-spinner fa-spin"></i>';
                loginButton.disabled = true;
                
                // Send login request to server
                fetch('include/handlers/login_process.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin' // Include cookies
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showSuccess('Login successful! Redirecting...');
                        // Redirect to dashboard on successful login
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 1000);
                    } else {
                        // Display error message (already sanitized by server)
                        showError(data.message || 'Login failed. Please check your credentials.');
                        loginButton.innerHTML = 'Sign In <i class="fas fa-arrow-right"></i>';
                        loginButton.disabled = false;
                    }
                })
                .catch(error => {
                    showError('An error occurred. Please try again later.');
                    console.error('Error:', error);
                    loginButton.innerHTML = 'Sign In <i class="fas fa-arrow-right"></i>';
                    loginButton.disabled = false;
                });
            });
            
            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
            }
            
            function showSuccess(message) {
                successMessage.textContent = message;
                successMessage.style.display = 'block';
            }
        });
    </script>

</body>

     
</html>