<?php
session_start();
include 'dbhandler.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// ======= SPECIAL ADMIN ACCESS ======= //

if ($_POST['username'] === 'admin123' && $_POST['password'] === 'admin123') {
    $_SESSION['admin_id'] = 9999; // Special ID
    $_SESSION['username'] = 'admin123';
    $_SESSION['role'] = 'Full Admin';
    $_SESSION['logged_in'] = true;
    
    $response['success'] = true;
    $response['message'] = 'Login successful (special admin access)';
    echo json_encode($response);
    exit();
}
// ======= END SPECIAL ADMIN ACCESS ======= //

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the action is login
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        
        // Get the username and password from the POST data
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];
        
        // Query the database to find the user with role
        $sql = "SELECT admin_id, username, password, role FROM login_admin WHERE username = '$username'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Password is correct, set session variables
                $_SESSION['admin_id'] = $user['admin_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                $response['success'] = true;
                $response['message'] = 'Login successful';
            } else {
                $response['message'] = 'Invalid password';
            }
        } else {
            $response['message'] = 'Username not found';
        }
    } else {
        $response['message'] = 'Invalid request';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
$conn->close();
?>