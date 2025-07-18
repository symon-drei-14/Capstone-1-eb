<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? '';
$page = $data['page'] ?? 1;

try {
    switch ($action) {
        case 'get_ongoing_trips':
            $limit = 3; // Number of rows per page
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM assign WHERE status = 'En Route'");
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            $totalPages = ceil($total / $limit);
            
            // Get paginated results
            $stmt = $conn->prepare("
                SELECT plate_no, driver, helper, client, destination 
                FROM assign 
                WHERE status = 'En Route'
                ORDER BY date DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $trips = [];
            while ($row = $result->fetch_assoc()) {
                $trips[] = $row;
            }
            
            echo json_encode([
                'success' => true, 
                'trips' => $trips,
                'pagination' => [
                    'currentPage' => (int)$page,
                    'totalPages' => $totalPages,
                    'totalTrips' => $total
                ]
            ]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) { $stmt->close(); }
    if (isset($countStmt)) { $countStmt->close(); }
    $conn->close();
}
?>