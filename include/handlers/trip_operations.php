<?php
header("Content-Type: application/json");
require 'dbhandler.php';

// Get the request data
$data = json_decode(file_get_contents("php://input"));

// Check if data is valid
if (!isset($data->action)) {
    echo json_encode(["success" => false, "message" => "No action specified"]);
    exit;
}

switch ($data->action) {
    case 'add':
        // Validate required fields
        if (!isset($data->plateNo, $data->date, $data->driver, $data->helper, $data->containerNo)) {
            echo json_encode(["success" => false, "message" => "Incomplete data"]);
            exit;
        }
        
        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO assign (plate_no, date, driver, helper, container_no, client, destination, shippine_line, consignee, size, cash_adv, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Bind parameters
        $stmt->bind_param("ssssssssssss", 
            $data->plateNo, 
            $data->date, 
            $data->driver, 
            $data->helper, 
            $data->containerNo, 
            $data->client, 
            $data->destination, 
            $data->shippingLine, 
            $data->consignee, 
            $data->size, 
            $data->cashAdvance, 
            $data->status
        );
        
        // Execute the query
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Trip added successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        
        $stmt->close();
        break;
        
    case 'edit':
        // Validate required fields
        if (!isset($data->id, $data->plateNo, $data->date, $data->driver, $data->helper, $data->containerNo)) {
            echo json_encode(["success" => false, "message" => "Incomplete data"]);
            exit;
        }
        
        // Prepare the SQL statement
        $stmt = $conn->prepare("UPDATE assign SET plate_no = ?, date = ?, driver = ?, helper = ?, container_no = ?, 
                              client = ?, destination = ?, shippine_line = ?, consignee = ?, size = ?, 
                              cash_adv = ?, status = ? WHERE trip_id = ?");
        
        // Bind parameters
        $stmt->bind_param("ssssssssssssi", 
            $data->plateNo, 
            $data->date, 
            $data->driver, 
            $data->helper, 
            $data->containerNo, 
            $data->client, 
            $data->destination, 
            $data->shippingLine, 
            $data->consignee, 
            $data->size, 
            $data->cashAdvance, 
            $data->status,
            $data->id
        );
        
        // Execute the query
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Trip updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        
        $stmt->close();
        break;
        
    case 'delete':
        // Validate the ID
        if (!isset($data->id)) {
            echo json_encode(["success" => false, "message" => "No ID specified"]);
            exit;
        }
        
        // Prepare the SQL statement
        $stmt = $conn->prepare("DELETE FROM assign WHERE trip_id = ?");
        
        // Bind parameter
        $stmt->bind_param("i", $data->id);
        
        // Execute the query
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Trip deleted successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
        }
        
        $stmt->close();
        break;
        
    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        break;
}

$conn->close();
?>