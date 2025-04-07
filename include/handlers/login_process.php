<?php
session_start();
include 'dbhandler.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the action is login
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        
        // Get the username and password from the POST data
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];
        
        // Query the database to find the user
        $sql = "SELECT admin_id, username, password FROM login_admin WHERE username = '$username'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify the password
            // Note: In a real app, you should use password_hash() and password_verify()
            // This assumes passwords in your DB are stored as plain text based on your structure
            if ($password === $user['password']) {
                // Password is correct, set session variables
                $_SESSION['admin_id'] = $user['admin_id'];
                $_SESSION['username'] = $user['username'];
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