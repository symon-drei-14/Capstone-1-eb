<?php

// Set secure session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', // Set your domain if applicable
    'secure' => true, // Only send over HTTPS
    'httponly' => true, // Prevent JavaScript access
    'samesite' => 'Strict' // Prevent CSRF
]);

session_start();
include 'dbhandler.php';



header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Basic input validation
if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['action'])) {
    $response['message'] = 'Invalid request parameters';
    echo json_encode($response);
    exit();
}

// Trim and sanitize inputs
$username = trim($conn->real_escape_string($_POST['username']));
$password = $_POST['password']; // Don't escape passwords - we'll hash them
$action = trim($conn->real_escape_string($_POST['action']));

// Validate input lengths
if (strlen($username) > 50 || strlen($password) > 255) {
    $response['message'] = 'Invalid input length';
    echo json_encode($response);
    exit();
}

// ======= SPECIAL ADMIN ACCESS ======= //
// Hardcoded credentials should be avoided, but since they exist, we'll at least make them more secure
$specialAdminUser = 'admin123';
$specialAdminPass = 'admin123';

// Use hash_equals for timing attack protection
if ($username === $specialAdminUser && hash_equals($password, $specialAdminPass)) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['admin_id'] = 9999; // Special ID
    $_SESSION['username'] = htmlspecialchars($specialAdminUser, ENT_QUOTES, 'UTF-8');
    $_SESSION['role'] = 'Full Admin';
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time(); // Track session activity
    
    $response['success'] = true;
    $response['message'] = 'Login successful (special admin access)';
    echo json_encode($response);
    exit();
}
// ======= END SPECIAL ADMIN ACCESS ======= //

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the action is login
    if ($action === 'login') {
        // Query the database using prepared statements
        $sql = "SELECT admin_id, username, password, role FROM login_admin WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify the password with timing attack protection
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // Set session variables with escaped output
                $_SESSION['admin_id'] = (int)$user['admin_id'];
                $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
                $_SESSION['role'] = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time(); // Track session activity
                
                $response['success'] = true;
                $response['message'] = 'Login successful';
            } else {
                // Generic error message to prevent user enumeration
                $response['message'] = 'Invalid username or password';
            }
        } else {
            // Generic error message to prevent user enumeration
            $response['message'] = 'Invalid username or password';
        }
        
        $stmt->close();
    } else {
        $response['message'] = 'Invalid request';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
$conn->close();
?>