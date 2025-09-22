<?php
header("Content-Type: application/json");
require 'dbhandler.php';

// Handle file upload
$adminPic = null;
if (!empty($_FILES['adminProfile']['name'])) {
    if ($_FILES['adminProfile']['error'] !== UPLOAD_ERR_OK) {

        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => "File is too large (server limit).",
            UPLOAD_ERR_FORM_SIZE => "File is too large (form limit).",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded.",
            UPLOAD_ERR_NO_FILE => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload.",
        ];
        $errorCode = $_FILES['adminProfile']['error'];
        $errorMessage = $uploadErrors[$errorCode] ?? "An unknown upload error occurred.";
        echo json_encode(["success" => false, "message" => $errorMessage]);
        exit;
    }


    if ($_FILES['adminProfile']['size'] > 2 * 1024 * 1024) {
        echo json_encode(["success" => false, "message" => "File is too large. Maximum size is 2MB."]);
        exit;
    }

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


$data = $_POST;
if (empty($data)) {
    $data = json_decode(file_get_contents("php://input"), true);
}

if (!isset($data['username']) || !isset($data['password']) || !isset($data['role']) || 
    empty($data['username']) || empty($data['password']) || empty($data['role'])) {
    echo json_encode(["success" => false, "message" => "Username, password and role are required"]);
    exit;
}


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


$hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);


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