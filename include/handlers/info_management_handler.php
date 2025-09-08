<?php
header("Content-Type: application/json");
session_start();
require 'dbhandler.php';

$currentUser = $_SESSION['username'] ?? 'System';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // Dispatchers
        case 'getDispatchers':
            $stmt = $conn->prepare("SELECT dispatcher_id, name, is_deleted, delete_reason, last_modified_by, last_modified_at FROM dispatchers ORDER BY name");
            $stmt->execute();
            $result = $stmt->get_result();
            $dispatchers = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $dispatchers]);
            break;
            
        case 'addDispatcher':
            $name = $_POST['name'] ?? '';
            if (empty($name)) throw new Exception("Name is required");
            
            $stmt = $conn->prepare("INSERT INTO dispatchers (name, last_modified_by) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $currentUser);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Dispatcher added successfully']);
            break;
            
        case 'updateDispatcher':
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            if (empty($id) || empty($name)) throw new Exception("ID and name are required");
            
            $stmt = $conn->prepare("UPDATE dispatchers SET name = ?, last_modified_by = ?, last_modified_at = NOW() WHERE dispatcher_id = ?");
            $stmt->bind_param("ssi", $name, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Dispatcher updated successfully']);
            break;
            
        case 'softDeleteDispatcher':
            $id = $_POST['id'] ?? '';
            $reason = $_POST['reason'] ?? '';
            if (empty($id) || empty($reason)) throw new Exception("ID and reason are required");
            
            $stmt = $conn->prepare("UPDATE dispatchers SET is_deleted = 1, delete_reason = ?, last_modified_by = ?, last_modified_at = NOW() WHERE dispatcher_id = ?");
            $stmt->bind_param("ssi", $reason, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Dispatcher deleted successfully']);
            break;
            
        case 'restoreDispatcher':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("UPDATE dispatchers SET is_deleted = 0, delete_reason = NULL, last_modified_by = ?, last_modified_at = NOW() WHERE dispatcher_id = ?");
            $stmt->bind_param("si", $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Dispatcher restored successfully']);
            break;
            
        case 'fullDeleteDispatcher':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("DELETE FROM dispatchers WHERE dispatcher_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Dispatcher permanently deleted']);
            break;
            
        // Destinations
        case 'getDestinations':
            $stmt = $conn->prepare("SELECT destination_id, name, is_deleted, delete_reason, last_modified_by, last_modified_at FROM destinations ORDER BY name");
            $stmt->execute();
            $result = $stmt->get_result();
            $destinations = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $destinations]);
            break;
            
        case 'addDestination':
            $name = $_POST['name'] ?? '';
            if (empty($name)) throw new Exception("Name is required");
            
            $stmt = $conn->prepare("INSERT INTO destinations (name, last_modified_by) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $currentUser);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Destination added successfully']);
            break;
            
        case 'updateDestination':
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            if (empty($id) || empty($name)) throw new Exception("ID and name are required");
            
            $stmt = $conn->prepare("UPDATE destinations SET name = ?, last_modified_by = ?, last_modified_at = NOW() WHERE destination_id = ?");
            $stmt->bind_param("ssi", $name, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Destination updated successfully']);
            break;
            
        case 'softDeleteDestination':
            $id = $_POST['id'] ?? '';
            $reason = $_POST['reason'] ?? '';
            if (empty($id) || empty($reason)) throw new Exception("ID and reason are required");
            
            $stmt = $conn->prepare("UPDATE destinations SET is_deleted = 1, delete_reason = ?, last_modified_by = ?, last_modified_at = NOW() WHERE destination_id = ?");
            $stmt->bind_param("ssi", $reason, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Destination deleted successfully']);
            break;
            
        case 'restoreDestination':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("UPDATE destinations SET is_deleted = 0, delete_reason = NULL, last_modified_by = ?, last_modified_at = NOW() WHERE destination_id = ?");
            $stmt->bind_param("si", $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Destination restored successfully']);
            break;
            
        case 'fullDeleteDestination':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("DELETE FROM destinations WHERE destination_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Destination permanently deleted']);
            break;
            
        // Clients
        case 'getClients':
            $stmt = $conn->prepare("SELECT client_id, name, is_deleted, delete_reason, last_modified_by, last_modified_at FROM clients ORDER BY name");
            $stmt->execute();
            $result = $stmt->get_result();
            $clients = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $clients]);
            break;
            
        case 'addClient':
            $name = $_POST['name'] ?? '';
            if (empty($name)) throw new Exception("Name is required");
            
            $stmt = $conn->prepare("INSERT INTO clients (name, last_modified_by) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $currentUser);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Client added successfully']);
            break;
            
        case 'updateClient':
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            if (empty($id) || empty($name)) throw new Exception("ID and name are required");
            
            $stmt = $conn->prepare("UPDATE clients SET name = ?, last_modified_by = ?, last_modified_at = NOW() WHERE client_id = ?");
            $stmt->bind_param("ssi", $name, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Client updated successfully']);
            break;
            
        case 'softDeleteClient':
            $id = $_POST['id'] ?? '';
            $reason = $_POST['reason'] ?? '';
            if (empty($id) || empty($reason)) throw new Exception("ID and reason are required");
            
            $stmt = $conn->prepare("UPDATE clients SET is_deleted = 1, delete_reason = ?, last_modified_by = ?, last_modified_at = NOW() WHERE client_id = ?");
            $stmt->bind_param("ssi", $reason, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Client deleted successfully']);
            break;
            
        case 'restoreClient':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("UPDATE clients SET is_deleted = 0, delete_reason = NULL, last_modified_by = ?, last_modified_at = NOW() WHERE client_id = ?");
            $stmt->bind_param("si", $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Client restored successfully']);
            break;
            
        case 'fullDeleteClient':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Client permanently deleted']);
            break;
            
        // Shipping Lines
        case 'getShipping-lines':
            $stmt = $conn->prepare("SELECT shipping_line_id, name, is_deleted, delete_reason, last_modified_by, last_modified_at FROM shipping_lines ORDER BY name");
            $stmt->execute();
            $result = $stmt->get_result();
            $shippingLines = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $shippingLines]);
            break;
            
        case 'addShippingLine':
            $name = $_POST['name'] ?? '';
            if (empty($name)) throw new Exception("Name is required");
            
            $stmt = $conn->prepare("INSERT INTO shipping_lines (name, last_modified_by) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $currentUser);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Shipping line added successfully']);
            break;
            
        case 'updateShippingLine':
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            if (empty($id) || empty($name)) throw new Exception("ID and name are required");
            
            $stmt = $conn->prepare("UPDATE shipping_lines SET name = ?, last_modified_by = ?, last_modified_at = NOW() WHERE shipping_line_id = ?");
            $stmt->bind_param("ssi", $name, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Shipping line updated successfully']);
            break;
            
        case 'softDeleteShippingLine':
            $id = $_POST['id'] ?? '';
            $reason = $_POST['reason'] ?? '';
            if (empty($id) || empty($reason)) throw new Exception("ID and reason are required");
            
            $stmt = $conn->prepare("UPDATE shipping_lines SET is_deleted = 1, delete_reason = ?, last_modified_by = ?, last_modified_at = NOW() WHERE shipping_line_id = ?");
            $stmt->bind_param("ssi", $reason, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Shipping line deleted successfully']);
            break;
            
        case 'restoreShippingLine':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("UPDATE shipping_lines SET is_deleted = 0, delete_reason = NULL, last_modified_by = ?, last_modified_at = NOW() WHERE shipping_line_id = ?");
            $stmt->bind_param("si", $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Shipping line restored successfully']);
            break;
            
        case 'fullDeleteShippingLine':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("DELETE FROM shipping_lines WHERE shipping_line_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Shipping line permanently deleted']);
            break;
            
        // Consignees
        case 'getConsignees':
            $stmt = $conn->prepare("SELECT consignee_id, name, is_deleted, delete_reason, last_modified_by, last_modified_at FROM consignees ORDER BY name");
            $stmt->execute();
            $result = $stmt->get_result();
            $consignees = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $consignees]);
            break;
            
        case 'addConsignee':
            $name = $_POST['name'] ?? '';
            if (empty($name)) throw new Exception("Name is required");
            
            $stmt = $conn->prepare("INSERT INTO consignees (name, last_modified_by) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $currentUser);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Consignee added successfully']);
            break;
            
        case 'updateConsignee':
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            if (empty($id) || empty($name)) throw new Exception("ID and name are required");
            
            $stmt = $conn->prepare("UPDATE consignees SET name = ?, last_modified_by = ?, last_modified_at = NOW() WHERE consignee_id = ?");
            $stmt->bind_param("ssi", $name, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Consignee updated successfully']);
            break;
            
        case 'softDeleteConsignee':
            $id = $_POST['id'] ?? '';
            $reason = $_POST['reason'] ?? '';
            if (empty($id) || empty($reason)) throw new Exception("ID and reason are required");
            
            $stmt = $conn->prepare("UPDATE consignees SET is_deleted = 1, delete_reason = ?, last_modified_by = ?, last_modified_at = NOW() WHERE consignee_id = ?");
            $stmt->bind_param("ssi", $reason, $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Consignee deleted successfully']);
            break;
            
        case 'restoreConsignee':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("UPDATE consignees SET is_deleted = 0, delete_reason = NULL, last_modified_by = ?, last_modified_at = NOW() WHERE consignee_id = ?");
            $stmt->bind_param("si", $currentUser, $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Consignee restored successfully']);
            break;
            
        case 'fullDeleteConsignee':
            $id = $_POST['id'] ?? '';
            if (empty($id)) throw new Exception("ID is required");
            
            $stmt = $conn->prepare("DELETE FROM consignees WHERE consignee_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Consignee permanently deleted']);
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