<?php
// Set secure session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
include 'dbhandler.php';
require 'phpmailer_config.php'; // Needed for sending OTP emails

date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

$action = $_POST['action'];

if ($action === 'login') {
    // --- Step 1: Handle Initial Username/Password Login ---
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $response['message'] = 'Username and password are required.';
        echo json_encode($response);
        exit();
    }

    $sql = "SELECT admin_id, username, password, role, admin_pic, admin_email, failed_attempts, last_failed_attempt FROM login_admin WHERE username = ? AND is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $now = time();

        // --- Keep the cooldowns BEFORE verifying the password ---
        if ($user['failed_attempts'] >= 6 && $user['last_failed_attempt']) {
            $cooldown_end = strtotime($user['last_failed_attempt']) + (5 * 60); // 5-minute cooldown
            if ($now < $cooldown_end) {
                $timeLeft = ceil(($cooldown_end - $now) / 60);
                $response['message'] = "Too many failed attempts. Please try again in {$timeLeft} minute(s).";
                echo json_encode($response);
                $stmt->close(); $conn->close(); exit();
            }
        } elseif ($user['failed_attempts'] >= 3 && $user['last_failed_attempt']) {
            $cooldown_end = strtotime($user['last_failed_attempt']) + (3 * 60); // 3-minute cooldown
            if ($now < $cooldown_end) {
                $timeLeft = ceil(($cooldown_end - $now) / 60);
                $response['message'] = "Too many failed attempts. Please try again in {$timeLeft} minute(s).";
                echo json_encode($response);
                $stmt->close(); $conn->close(); exit();
            }
        }
        
        // Now, verify the password
        if (password_verify($password, $user['password'])) {
            // --- Password is correct, now start the OTP process ---
            $otp = rand(100000, 999999);
            $_SESSION['otp_user_data'] = [
                'user' => $user,
                'otp' => $otp,
                'expiry' => time() + 300 // OTP valid for 5 minutes
            ];

            $mail = getMailer();
            if ($mail && !empty($user['admin_email'])) {
                try {
                    $mail->addAddress($user['admin_email'], $user['username']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Admin Login Verification Code';
                    $mail->Body    = "Hello {$user['username']},<br><br>Your verification code to log in is: <b>{$otp}</b><br>This code will expire in 5 minutes.<br><br>If you did not attempt to log in, please secure your account immediately.<br><br>Thank you.";
                    $mail->send();

                    // Tell the frontend that OTP is now required
                    $response['success'] = true;
                    $response['otp_required'] = true;
                    $response['message'] = 'A verification code has been sent to your registered email.';
                } catch (Exception $e) {
                    $response['message'] = "Could not send OTP email. Please contact support.";
                }
            } else {
                $response['message'] = "Could not send OTP. Your account does not have a registered email or the mailer is not configured.";
            }
        } else {
            // On failure, increment failed attempts (existing logic is good)
            $new_attempts = $user['failed_attempts'] + 1;
            $failStmt = $conn->prepare("UPDATE login_admin SET failed_attempts = ?, last_failed_attempt = NOW() WHERE admin_id = ?");
            $failStmt->bind_param("ii", $new_attempts, $user['admin_id']);
            $failStmt->execute();
            $failStmt->close();

            if ($new_attempts >= 9) {
                $lockStmt = $conn->prepare("UPDATE login_admin SET is_deleted = TRUE, deleted_at = NOW(), deleted_by = 0, delete_reason = 'Failed Login Attempts', failed_attempts = 0, last_failed_attempt = NULL WHERE admin_id = ?");
                $lockStmt->bind_param("i", $user['admin_id']);
                $lockStmt->execute();
                $lockStmt->close();
                $response['message'] = 'Your account has been locked due to too many failed login attempts.';
            } else {
                $response['message'] = 'Invalid username or password';
            }
        }
    } else {
        $response['message'] = 'Invalid username or password';
    }
    $stmt->close();

} elseif ($action === 'verify_otp') {
    // --- Step 2: Handle OTP Verification ---
    $otp_code = $_POST['otp'] ?? '';

    if (empty($otp_code)) {
        $response['message'] = 'OTP code is required.';
    } elseif (!isset($_SESSION['otp_user_data'])) {
        $response['message'] = 'OTP process not started or session expired. Please log in again.';
    } elseif (time() > $_SESSION['otp_user_data']['expiry']) {
        $response['message'] = 'OTP has expired. Please log in again.';
        unset($_SESSION['otp_user_data']);
    } else {
        // Initialize OTP attempts counter if it doesn't exist
        $_SESSION['otp_user_data']['attempts'] = $_SESSION['otp_user_data']['attempts'] ?? 0;
        $max_otp_attempts = 5; // Set the maximum number of OTP attempts

        if ($_SESSION['otp_user_data']['otp'] != $otp_code) {
            // Invalid OTP, increment attempt count
            $_SESSION['otp_user_data']['attempts']++;
            $remaining_attempts = $max_otp_attempts - $_SESSION['otp_user_data']['attempts'];

            if ($_SESSION['otp_user_data']['attempts'] >= $max_otp_attempts) {
                // Too many failed OTP attempts, lock and delete the account
                $user = $_SESSION['otp_user_data']['user'];
                unset($_SESSION['otp_user_data']); // Clean up OTP data

                $lockStmt = $conn->prepare("UPDATE login_admin SET is_deleted = TRUE, deleted_at = NOW(), deleted_by = 0, delete_reason = 'Too many OTP attempts', failed_attempts = 0, last_failed_attempt = NULL WHERE admin_id = ?");
                $lockStmt->bind_param("i", $user['admin_id']);
                $lockStmt->execute();
                $lockStmt->close();
                
                $response['message'] = 'Your account has been locked due to too many failed OTP attempts.';
            } else {
                $response['message'] = "Invalid OTP code. You have {$remaining_attempts} attempts remaining.";
            }
        } else {
            // --- OTP is correct, complete the login ---
            $user = $_SESSION['otp_user_data']['user'];
            unset($_SESSION['otp_user_data']); // Clean up OTP data

            // Reset failed attempts on successful login
            $resetStmt = $conn->prepare("UPDATE login_admin SET failed_attempts = 0, last_failed_attempt = NULL WHERE admin_id = ?");
            $resetStmt->bind_param("i", $user['admin_id']);
            $resetStmt->execute();
            $resetStmt->close();

            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$user['admin_id'];
            $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
            $_SESSION['role'] = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');
            $_SESSION['admin_pic'] = $user['admin_pic'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();

            $response['success'] = true;
            $response['message'] = 'Login successful';
        }
    }
} else {
    $response['message'] = 'Invalid action specified.';
}

echo json_encode($response);
$conn->close();
?>