<?php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['driverId'])) {
    echo json_encode(["success" => false, "message" => "Driver ID is required"]);
    exit;
}

$driverId = $data['driverId'];

try {
    // Prepare and execute the delete query for MySQL
    $stmt = $conn->prepare("DELETE FROM drivers_table WHERE driver_id = ?");
    $stmt->bind_param("s", $driverId);
    
    if ($stmt->execute()) {
        // If MySQL deletion successful, also delete from Firebase
        $firebase_deleted = deleteFromFirebase($driverId);
        
        if ($firebase_deleted) {
            echo json_encode(["success" => true, "message" => "Driver deleted successfully from both MySQL and Firebase"]);
        } else {
            echo json_encode(["success" => true, "message" => "Driver deleted from MySQL, but Firebase deletion failed"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error deleting driver from MySQL: " . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

function deleteFromFirebase($driverId) {
    try {
        $firebase_url = "https://mansartrucking1-default-rtdb.asia-southeast1.firebasedatabase.app/drivers/" . $driverId . ".json?auth=Xtnh1Zva11o8FyDEA75gzep6NUeNJLMZiCK6mXB7";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $firebase_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $curl_error = curl_error($ch);
            curl_close($ch);
            error_log("Firebase delete cURL Error: " . $curl_error);
            return false;
        }
        
        curl_close($ch);
        
        // Firebase returns 200 for successful DELETE operations
        if ($http_code === 200) {
            return true;
        } else {
            error_log("Firebase delete failed with HTTP code: " . $http_code . " Response: " . $response);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Firebase deletion failed: " . $e->getMessage());
        return false;
    }
}

$conn->close();
?>