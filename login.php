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
                    <li><i class="fas fa-check-circle"></i> Manage Fleet</li>
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
                

               <div class="form-group" id="otpGroup" style="display: none;">
    <label for="otp">Verification Code</label>
    <div class="otp-input-container">
        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
        <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
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
    
    const usernameGroup = document.querySelector('label[for="username"]').parentElement;
    const passwordGroup = document.querySelector('label[for="password"]').parentElement;
    const otpGroup = document.getElementById('otpGroup');
    
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');
    
    const otpInputs = document.querySelectorAll('.otp-input');
    
    let isOtpStage = false;


    passwordToggle.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordToggle.innerHTML = '<i class="far fa-eye-slash"></i>';
        } else {
            passwordInput.type = 'password';
            passwordToggle.innerHTML = '<i class="far fa-eye"></i>';
        }
    });

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                otpInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').trim().slice(0, 6);
            pasteData.split('').forEach((char, i) => {
                if (otpInputs[index + i]) {
                    otpInputs[index + i].value = char;
                }
            });
             // Focus on the last pasted character's input or the final input
             const lastInputIndex = Math.min(index + pasteData.length, otpInputs.length) - 1;
             otpInputs[lastInputIndex].focus();
        });
    });
    
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Reset messages and set button to loading state
        errorMessage.style.display = 'none';
        successMessage.style.display = 'none';
        loginButton.innerHTML = 'Verifying... <i class="fas fa-spinner fa-spin"></i>';
        loginButton.disabled = true;

        const formData = new FormData();
        
        if (isOtpStage) {
            // --- Stage 2: SUBMITTING OTP ---
            const otpValue = Array.from(otpInputs).map(input => input.value).join('');

            if (otpValue.length < 6) {
                showError('Please enter the complete 6-digit code.');
                resetButton();
                return;
            }
            formData.append('action', 'verify_otp');
            formData.append('otp', otpValue);

        } else {
            // --- Stage 1: SUBMITTING USERNAME/PASSWORD ---
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (!username || !password) {
                showError('Please enter both username and password.');
                resetButton();
                return;
            }
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);
        }

        // Send request to the server
        fetch('include/handlers/login_process.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin' 
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.otp_required) {
                    // Transition to OTP stage
                    showSuccess(data.message || 'OTP sent successfully.');
                    isOtpStage = true;
                    usernameGroup.style.display = 'none';
                    passwordGroup.style.display = 'none';
                    otpGroup.style.display = 'block';
                    resetButton(); // Resets button text to "Verify Code"
                    otpInputs[0].focus(); 
                } else {
                    // Login is fully successful
                    showSuccess('Login successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                }
            } else {
                showError(data.message || 'An unknown error occurred.');
                resetButton();
            }
        })
        .catch(error => {
            showError('A network error occurred. Please try again.');
            console.error('Error:', error);
            resetButton();
        });
    });
    function resetButton() {
        if (isOtpStage) {
             loginButton.innerHTML = 'Verify Code <i class="fas fa-check"></i>';
        } else {
             loginButton.innerHTML = 'Sign In <i class="fas fa-arrow-right"></i>';
        }
        loginButton.disabled = false;
    }
    
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