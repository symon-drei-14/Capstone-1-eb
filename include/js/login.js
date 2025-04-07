document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');
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
        
        // Create form data
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
        // Send login request
        fetch('include/login_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect on successful login
                window.location.href = 'dashboard.php';
            } else {
                // Display error message
                errorMessage.textContent = data.message || 'Login failed. Please try again.';
            }
        })
        .catch(error => {
            errorMessage.textContent = 'An error occurred. Please try again later.';
            console.error('Error:', error);
        });
    });
});