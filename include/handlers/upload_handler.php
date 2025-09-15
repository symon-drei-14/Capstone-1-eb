<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $logMessage .= " | Data: " . print_r($data, true);
    }
    error_log($logMessage);
}

logDebug("=== UPLOAD HANDLER START ===");

try {
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Accept");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        logDebug("OPTIONS preflight request");
        http_response_code(200);
        exit(0);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST method allowed for uploads");
    }

    logDebug("Request details", [
        'method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set'
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        logDebug("Session started successfully");
    }

    $action = $_POST['action'] ?? '';
    logDebug("Action received", $action);
    
    if ($action !== 'upload_receipt') {
        throw new Exception("Invalid action: {$action}");
    }

    if (!isset($_FILES['image'])) {
        logDebug("FILES array", $_FILES);
        throw new Exception("No image file found in upload");
    }

    $uploadedFile = $_FILES['image'];
    logDebug("Uploaded file details", $uploadedFile);

    switch ($uploadedFile['error']) {
        case UPLOAD_ERR_OK:
            logDebug("Upload successful, no errors");
            break;
        case UPLOAD_ERR_INI_SIZE:
            throw new Exception("File too large (server ini limit)");
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception("File too large (form limit)");
        case UPLOAD_ERR_PARTIAL:
            throw new Exception("File upload was incomplete");
        case UPLOAD_ERR_NO_FILE:
            throw new Exception("No file was uploaded");
        case UPLOAD_ERR_NO_TMP_DIR:
            throw new Exception("Server error: no temporary directory");
        case UPLOAD_ERR_CANT_WRITE:
            throw new Exception("Server error: cannot write to disk");
        case UPLOAD_ERR_EXTENSION:
            throw new Exception("Server error: upload blocked by extension");
        default:
            throw new Exception("Unknown upload error: " . $uploadedFile['error']);
    }

    $maxSize = 5 * 1024 * 1024;
    if ($uploadedFile['size'] > $maxSize) {
        throw new Exception("File too large: " . formatBytes($uploadedFile['size']) . " (max: " . formatBytes($maxSize) . ")");
    }

    if (!file_exists($uploadedFile['tmp_name'])) {
        throw new Exception("Temporary file not found");
    }

    $allowedTypes = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedType = $finfo->file($uploadedFile['tmp_name']);
    logDebug("MIME type detection", [
        'uploaded_type' => $uploadedFile['type'],
        'detected_type' => $detectedType
    ]);

    if (!in_array($detectedType, $allowedTypes)) {
        throw new Exception("Invalid file type: {$detectedType}. Only images allowed.");
    }

    $uploadDir = '../uploads/receipts/';
    $absoluteUploadDir = realpath(dirname($uploadDir)) . '/' . basename($uploadDir) . '/';
    
    logDebug("Directory setup", [
        'relative_path' => $uploadDir,
        'absolute_path' => $absoluteUploadDir
    ]);

    if (!file_exists($absoluteUploadDir)) {
        logDebug("Creating upload directory");
        if (!mkdir($absoluteUploadDir, 0755, true)) {
            throw new Exception("Failed to create upload directory");
        }
        logDebug("Upload directory created successfully");
    }

    if (!is_writable($absoluteUploadDir)) {
        throw new Exception("Upload directory is not writable: {$absoluteUploadDir}");
    }

    $extension = getFileExtension($detectedType);
    $filename = 'receipt_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
    $filePath = $absoluteUploadDir . $filename;
    
    logDebug("File destination", [
        'filename' => $filename,
        'full_path' => $filePath
    ]);

    if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
        throw new Exception("Failed to save uploaded file");
    }

    if (!file_exists($filePath)) {
        throw new Exception("File was not saved properly");
    }

    $savedFileSize = filesize($filePath);
    logDebug("File saved successfully", [
        'path' => $filePath,
        'size' => formatBytes($savedFileSize)
    ]);

    $imageInfo = @getimagesize($filePath);
    if ($imageInfo === false) {
        unlink($filePath);
        throw new Exception("Invalid image file uploaded");
    }

    logDebug("Image validation successful", [
        'dimensions' => $imageInfo[0] . 'x' . $imageInfo[1],
        'type' => $imageInfo['mime']
    ]);

    $relativePath = 'include/uploads/receipts/' . $filename;
    $publicUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $publicUrl .= "://{$_SERVER['HTTP_HOST']}/Capstone-1-eb/{$relativePath}";

    $response = [
        'success' => true,
        'message' => 'Receipt uploaded successfully',
        'image_path' => $relativePath,
        'public_url' => $publicUrl, 
        'filename' => $filename,
        'file_size' => $savedFileSize,
        'mime_type' => $detectedType,
        'dimensions' => [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ]
    ];

    logDebug("Success response", $response);
    echo json_encode($response);

} catch (Exception $e) {
    logDebug("Exception caught", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'Exception'
    ]);

} catch (Error $e) {
    logDebug("Fatal error caught", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'error_type' => 'Fatal Error'
    ]);

} catch (Throwable $e) {
    logDebug("Throwable caught", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected error: ' . $e->getMessage(),
        'error_type' => 'Throwable'
    ]);
}

logDebug("=== UPLOAD HANDLER END ===");

function formatBytes($size, $precision = 2) {
    if ($size === 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $base = log($size, 1024);
    
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

function getFileExtension($mimeType) {
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png', 
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    return $extensions[$mimeType] ?? 'jpg';
}
?>