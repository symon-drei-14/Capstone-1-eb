<?php
header("Content-Type: application/json");
require 'dbhandler.php';

// Handle file upload
$adminPic = null;
if (!empty($_FILES['adminProfile']['name']) && $_FILES['adminProfile']['error'] == UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $_FILES['adminProfile']['tmp_name']);
    finfo_close($fileInfo);
    
    if (in_array($mimeType, $allowedTypes)) {
        $adminPic = base64_encode(file_get_contents($_FILES['adminProfile']['tmp_name']));
    } else {
        echo json_encode(["success" => false, "message" => "Invalid file type. Only JPG, PNG and GIF are allowed."]);
        exit;
    }
}

// Get the POST data
$data = $_POST;
if (empty($data)) {
    $data = json_decode(file_get_contents("php://input"), true);
}

if (!isset($data['username']) || !isset($data['password']) || !isset($data['role']) || 
    empty($data['username']) || empty($data['password']) || empty($data['role'])) {
    echo json_encode(["success" => false, "message" => "Username, password and role are required"]);
    exit;
}

// Check if username already exists
$checkStmt = $conn->prepare("SELECT admin_id FROM login_admin WHERE username = ?");
$checkStmt->bind_param("s", $data['username']);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already exists"]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// Hash the password before storing
$hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

// Add new admin with hashed password and profile picture
$stmt = $conn->prepare("INSERT INTO login_admin (username, password, role, admin_pic) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $data['username'], $hashedPassword, $data['role'], $adminPic);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>