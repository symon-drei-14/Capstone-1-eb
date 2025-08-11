<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

// Verify session and CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // In a real implementation, you would validate CSRF token here
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
        exit();
    }
}

// Get JSON input safely
$json = file_get_contents('php://input');
if ($json === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$data = json_decode($json, true);
if ($data === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

// Validate and sanitize inputs
$action = isset($data['action']) ? trim($conn->real_escape_string($data['action'])) : '';
$page = isset($data['page']) ? (int)$data['page'] : 1;
$tripId = isset($data['tripId']) ? (int)$data['tripId'] : null;

try {
    switch ($action) {
        case 'get_ongoing_trips':
            // Validate page number
            if ($page < 1) {
                $page = 1;
            }
            
            $limit = 3;
            $offset = ($page - 1) * $limit;
            
            // Get total count using prepared statement
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM assign WHERE status = 'En Route'");
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            $totalPages = ceil($total / $limit);
            
            // Get paginated results with prepared statement
            $stmt = $conn->prepare("
                SELECT * FROM assign 
                WHERE status = 'En Route'
                ORDER BY date DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $trips = [];
            while ($row = $result->fetch_assoc()) {
                // Sanitize output
                $sanitizedRow = array_map(function($value) {
                    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }, $row);
                $trips[] = $sanitizedRow;
            }
            
            echo json_encode([
                'success' => true, 
                'trips' => $trips,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalTrips' => $total
                ]
            ]);
            break;

        case 'get_trip_details':
            if (!$tripId || $tripId < 1) {
                throw new Exception("Invalid Trip ID");
            }
            
            $stmt = $conn->prepare("SELECT * FROM assign WHERE trip_id = ?");
            $stmt->bind_param("i", $tripId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Trip not found");
            }
            
            $trip = $result->fetch_assoc();
            // Sanitize output
            $trip = array_map(function($value) {
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }, $trip);
            
            echo json_encode(['success' => true, 'trip' => $trip]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    // Generic error message for production, detailed for debugging
    $errorMessage = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) 
        ? $e->getMessage() 
        : 'An error occurred';
    echo json_encode(['success' => false, 'message' => $errorMessage]);
} finally {
    if (isset($stmt)) { $stmt->close(); }
    if (isset($countStmt)) { $countStmt->close(); }
    $conn->close();
}
?>