<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
include 'dbhandler.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'login') {
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $response['message'] = 'Username and password are required.';
    echo json_encode($response);
    exit();
}

// Prepare to fetch user data
$sql = "SELECT admin_id, username, password, role, failed_attempts, last_failed_attempt FROM login_admin WHERE username = ? AND is_deleted = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $now = time();

    // Verify the password first
    if (password_verify($password, $user['password'])) {
        // --- CORRECT PASSWORD LOGIC ---
        
        // Check if the account is currently in a cooldown period before allowing login
        if ($user['failed_attempts'] >= 6 && $user['last_failed_attempt']) {
            $cooldown_end = strtotime($user['last_failed_attempt']) + (5 * 60); // 5 minutes
            if ($now < $cooldown_end) {
                $timeLeft = $cooldown_end - $now;
                $response['message'] = "Too many attempts. Please try again in " . ceil($timeLeft / 60) . " minute(s).";
                echo json_encode($response);
                exit();
            }
        } elseif ($user['failed_attempts'] >= 3 && $user['last_failed_attempt']) {
            $cooldown_end = strtotime($user['last_failed_attempt']) + (3 * 60); // 3 minutes
            if ($now < $cooldown_end) {
                $timeLeft = $cooldown_end - $now;
                $response['message'] = "Too many attempts. Please try again in " . ceil($timeLeft / 60) . " minute(s).";
                echo json_encode($response);
                exit();
            }
        }

        // If not in cooldown or cooldown has passed, reset attempts and log in
        $resetStmt = $conn->prepare("UPDATE login_admin SET failed_attempts = 0, last_failed_attempt = NULL WHERE admin_id = ?");
        $resetStmt->bind_param("i", $user['admin_id']);
        $resetStmt->execute();
        $resetStmt->close();

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$user['admin_id'];
        $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
        $_SESSION['role'] = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();

        $response['success'] = true;
        $response['message'] = 'Login successful';

    } else {
        // --- INCORRECT PASSWORD LOGIC ---
        
        // Increment the failed attempts counter
        $new_attempts = $user['failed_attempts'] + 1;
        
        // Update the database with the new attempt count and timestamp
        $failStmt = $conn->prepare("UPDATE login_admin SET failed_attempts = ?, last_failed_attempt = NOW() WHERE admin_id = ?");
        $failStmt->bind_param("ii", $new_attempts, $user['admin_id']);
        $failStmt->execute();
        $failStmt->close();

        // Now check if the new attempt count triggers a lockout
        if ($new_attempts >= 9) {
            $lockStmt = $conn->prepare("UPDATE login_admin SET is_deleted = TRUE, deleted_at = NOW(), deleted_by = 0, delete_reason = 'Failed Login Attempts', failed_attempts = 0, last_failed_attempt = NULL WHERE admin_id = ?");
            $lockStmt->bind_param("i", $user['admin_id']);
            $lockStmt->execute();
            $lockStmt->close();
            $response['message'] = 'Your account has been locked due to too many failed login attempts.';
        } else {
             // Just give the standard invalid credentials message
            $response['message'] = 'Invalid username or password';
        }
    }
} else {
    // Generic error to prevent username enumeration
    $response['message'] = 'Invalid username or password';
}

$stmt->close();
echo json_encode($response);
$conn->close();
?>