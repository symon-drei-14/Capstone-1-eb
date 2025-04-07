<!doctype html>
<html lang="en">
<head>
    <title>Webleb</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="include/login.css" rel="stylesheet">
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <form id="loginForm">
            <div class="user-box">
                <input type="text" id="username" name="Username" required />
                <label>Username</label>
            </div>
            <div class="user-box">
                <input type="password" id="password" name="Password" required />
                <label>Password</label>
            </div>
            <a href="#" id="loginButton">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                Submit
            </a>
            <p id="error-message" style="color: red;"></p>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginButton = document.getElementById('loginButton');
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('error-message');

        loginButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            // Basic validation
            if (!username || !password) {
                errorMessage.textContent = 'Please enter both username and password';
                return;
            }
            
            // Create form data to send
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            formData.append('action', 'login');
            
            // Send login request to server
            fetch('include/handlers/login_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to dashboard on successful login
                    window.location.href = 'dashboard.php';
                } else {
                    // Display error message
                    errorMessage.textContent = data.message || 'Login failed. Please check your credentials.';
                }
            })
            .catch(error => {
                errorMessage.textContent = 'An error occurred. Please try again later.';
                console.error('Error:', error);
            });
        });
    });
    </script>
</body>
</html>