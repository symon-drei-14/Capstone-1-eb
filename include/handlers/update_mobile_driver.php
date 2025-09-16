<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once 'dbhandler.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// All data will come from $_POST and $_FILES now.
if (!isset($_POST['driver_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required field: driver_id']);
    exit;
}

$driver_id = $_POST['driver_id'];
$name = isset($_POST['name']) ? trim($_POST['name']) : null;
$email = isset($_POST['email']) ? trim($_POST['email']) : null;
$contact_no = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : null;
$password = isset($_POST['password']) ? trim($_POST['password']) : null;
$new_driver_pic_base64 = null;

// Handle file upload
if (isset($_FILES['driver_pic']) && $_FILES['driver_pic']['error'] == UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $_FILES['driver_pic']['tmp_name']);
    finfo_close($fileInfo);

    if (in_array($mimeType, $allowedTypes)) {
        $new_driver_pic_base64 = base64_encode(file_get_contents($_FILES['driver_pic']['tmp_name']));
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF are allowed.']);
        exit;
    }
}

try {
    // Check if email already exists for another driver
    if ($email) {
        $check_email = $conn->prepare("SELECT driver_id FROM drivers_table WHERE email = ? AND driver_id != ?");
        $check_email->bind_param("ss", $email, $driver_id);
        $check_email->execute();
        $email_result = $check_email->get_result();

        if ($email_result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists for another driver']);
            exit;
        }
        $check_email->close();
    }

    // Build the update query dynamically
    $updateFields = [];
    $params = [];
    $types = "";

    if ($name !== null) { $updateFields[] = "name = ?"; $params[] = $name; $types .= "s"; }
    if ($email !== null) { $updateFields[] = "email = ?"; $params[] = $email; $types .= "s"; }
    if ($contact_no !== null) { $updateFields[] = "contact_no = ?"; $params[] = $contact_no; $types .= "s"; }
    if ($new_driver_pic_base64 !== null) { $updateFields[] = "driver_pic = ?"; $params[] = $new_driver_pic_base64; $types .= "s"; }
    if ($password !== null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updateFields[] = "password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        exit;
    }

    $params[] = $driver_id;
    $types .= "s";

    $query = "UPDATE drivers_table SET " . implode(", ", $updateFields) . " WHERE driver_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Fetch the updated data to return to the app
            $select_stmt = $conn->prepare("SELECT name, email, contact_no, driver_pic FROM drivers_table WHERE driver_id = ?");
            $select_stmt->bind_param("s", $driver_id);
            $select_stmt->execute();
            $updated_driver_data = $select_stmt->get_result()->fetch_assoc();
            $select_stmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Driver information updated successfully',
                'updated_driver' => $updated_driver_data
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made']);
        }
    } else {
        throw new Exception("Failed to execute update: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Update mobile driver error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating driver: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>