<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'dbhandler.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['driver_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Driver ID is required']);
    exit;
}

$driver_id = $data['driver_id'];

try {
    $stmt = $conn->prepare("SELECT name, email, contact_no, driver_pic FROM drivers_table WHERE driver_id = ?");
    $stmt->bind_param("s", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $driver = $result->fetch_assoc();
        echo json_encode(['success' => true, 'driver' => $driver]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Driver not found']);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching driver data: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>