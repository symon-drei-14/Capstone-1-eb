<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

// Get current username from session
$currentUser = $_SESSION['username'] ?? 'System';

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $stmt = $conn->prepare("INSERT INTO assign 
                (plate_no, date, driver, helper, container_no, client, 
                destination, shippine_line, consignee, size, cash_adv, status,
                last_modified_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssss",
                $data['plateNo'],
                $data['date'],
                $data['driver'],
                $data['helper'],
                $data['containerNo'],
                $data['client'],
                $data['destination'],
                $data['shippingLine'],
                $data['consignee'],
                $data['size'],
                $data['cashAdvance'],
                $data['status'],
                $currentUser  
            );
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'edit':
            $stmt = $conn->prepare("UPDATE assign SET 
                plate_no=?, date=?, driver=?, helper=?, container_no=?, client=?, 
                destination=?, shippine_line=?, consignee=?, size=?, cash_adv=?, status=?,
                last_modified_by=?
                WHERE trip_id=?");
            $stmt->bind_param("sssssssssssssi",
                $data['plateNo'],
                $data['date'],
                $data['driver'],
                $data['helper'],
                $data['containerNo'],
                $data['client'],
                $data['destination'],
                $data['shippingLine'],
                $data['consignee'],
                $data['size'],
                $data['cashAdvance'],
                $data['status'],
                $currentUser,  
                $data['id']
            );
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM assign WHERE trip_id=?");
            $stmt->bind_param("i", $data['id']);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>