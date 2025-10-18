<?php
header("Content-Type: application/json");
session_start();
date_default_timezone_set('Asia/Manila');
require 'dbhandler.php';


$currentUser = $_SESSION['username'] ?? 'System';


$json = file_get_contents('php://input');
$data = json_decode($json, true);


$action = $data['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';


if (isset($_POST['action']) && ($_POST['action'] === 'addTruck' || $_POST['action'] === 'updateTruck')) {
    $data = array_merge($data ?: [], $_POST);
}

function validatePlateNumber($plateNo) {
    return preg_match("/^[A-Za-z]{2,3}-?\d{3,4}$/", $plateNo);
}



// Function to update truck status based on maintenance and trip logs
function updateTruckStatus($conn, $truckId, $plateNo) {
    $tripStatus = null;
    $tripQuery = $conn->prepare("
           SELECT status 
        FROM trips 
        WHERE truck_id = ? 
        ORDER BY trip_date DESC 
        LIMIT 1
    ");
    $tripQuery->bind_param("i", $truckId);
    $tripQuery->execute();
    $tripResult = $tripQuery->get_result();
    if ($tripResult->num_rows > 0) {
        $tripStatus = $tripResult->fetch_assoc()['status'];
    }

    $maintenanceStatus = null;
    $maintenanceQuery = $conn->prepare("
        SELECT m.status 
        FROM maintenance_table m
        WHERE m.truck_id = ? 
        AND m.status IN ('In Progress', 'Overdue', 'Pending', 'Completed')
        AND NOT EXISTS (
            SELECT 1 FROM audit_logs_maintenance al 
            WHERE al.maintenance_id = m.maintenance_id 
            AND al.is_deleted = 1
            AND al.modified_at = (
                SELECT MAX(al2.modified_at)
                FROM audit_logs_maintenance al2
                WHERE al2.maintenance_id = m.maintenance_id
            )
        )
        ORDER BY 
            CASE m.status 
                WHEN 'In Progress' THEN 1 
                WHEN 'Overdue' THEN 2 
                WHEN 'Pending' THEN 3 
                WHEN 'Completed' THEN 4
            END,
            m.date_mtnce DESC 
        LIMIT 1
    ");
    $maintenanceQuery->bind_param("i", $truckId);
    $maintenanceQuery->execute();
    $maintenanceResult = $maintenanceQuery->get_result();
    if ($maintenanceResult->num_rows > 0) {
        $maintenanceStatus = $maintenanceResult->fetch_assoc()['status'];
    }

    $newStatus = 'In Terminal';

    if ($tripStatus === 'Enroute') {
        $newStatus = 'Enroute';
    } elseif ($maintenanceStatus === 'In Progress') {
        $newStatus = 'In Repair';
    } elseif ($maintenanceStatus === 'Overdue') {
        $newStatus = 'Overdue';
    } elseif ($maintenanceStatus === 'Pending') {
        $newStatus = 'In Terminal';
    } elseif ($maintenanceStatus === 'Completed' || $maintenanceStatus === null) {
        $newStatus = 'In Terminal';
    }

   $updateStmt = $conn->prepare("
    UPDATE truck_table 
    SET status = ?, last_modified_at = NOW() 
    WHERE truck_id = ?
");
    $updateStmt->bind_param("si", $newStatus, $truckId);
    $updateStmt->execute();
}

function updateAllTruckStatuses($conn) {
    $trucksQuery = $conn->query("SELECT truck_id, plate_no FROM truck_table WHERE is_deleted = 0");
    
    while ($truck = $trucksQuery->fetch_assoc()) {
        $truckId = $truck['truck_id'];
        $plateNo = $truck['plate_no'];

        // First check if truck is currently on an En Route trip (excluding soft-deleted trips)
        $tripStatus = null;
        $tripQuery = $conn->prepare("
            SELECT t.status 
            FROM trips t
            WHERE t.truck_id = ? 
            AND t.status = 'En Route'
            AND NOT EXISTS (
                SELECT 1 FROM audit_logs_trips al 
                WHERE al.trip_id = t.trip_id 
                AND al.is_deleted = 1
                AND al.modified_at = (
                    SELECT MAX(al2.modified_at)
                    FROM audit_logs_trips al2
                    WHERE al2.trip_id = t.trip_id
                )
            )
            ORDER BY t.trip_date DESC 
            LIMIT 1
        ");
        $tripQuery->bind_param("i", $truckId);
        $tripQuery->execute();
        $tripResult = $tripQuery->get_result();
        if ($tripResult->num_rows > 0) {
            $tripStatus = $tripResult->fetch_assoc()['status'];
        }

        $maintenanceStatus = null;
        $maintenanceQuery = $conn->prepare("
            SELECT m.status 
            FROM maintenance_table m
            WHERE m.truck_id = ? 
            AND m.status IN ('In Progress', 'Overdue', 'Pending', 'Completed')
            AND NOT EXISTS (
                SELECT 1 FROM audit_logs_maintenance al 
                WHERE al.maintenance_id = m.maintenance_id 
                AND al.is_deleted = 1
                AND al.modified_at = (
                    SELECT MAX(al2.modified_at)
                    FROM audit_logs_maintenance al2
                    WHERE al2.maintenance_id = m.maintenance_id
                )
            )
            ORDER BY 
                CASE m.status 
                    WHEN 'In Progress' THEN 1 
                    WHEN 'Overdue' THEN 2 
                    WHEN 'Pending' THEN 3 
                    WHEN 'Completed' THEN 4
                END,
                m.date_mtnce DESC 
            LIMIT 1
        ");
        $maintenanceQuery->bind_param("i", $truckId);
        $maintenanceQuery->execute();
        $maintenanceResult = $maintenanceQuery->get_result();
        if ($maintenanceResult->num_rows > 0) {
            $maintenanceStatus = $maintenanceResult->fetch_assoc()['status'];
        }

        $newStatus = 'In Terminal';

        // PRIORITIZE TRIP STATUS OVER MAINTENANCE STATUS
        if ($tripStatus === 'En Route') {
            $newStatus = 'Enroute';
        } elseif ($maintenanceStatus === 'In Progress') {
            $newStatus = 'In Repair';
        } elseif ($maintenanceStatus === 'Overdue') {
            $newStatus = 'Overdue';
        } elseif ($maintenanceStatus === 'Pending') {
            $newStatus = 'In Terminal';
        } elseif ($maintenanceStatus === 'Completed' || $maintenanceStatus === null) {
            $newStatus = 'In Terminal';
        }

        // Only update if status has changed
        $updateStmt = $conn->prepare("
            UPDATE truck_table 
            SET status = ?, last_modified_at = NOW() 
            WHERE truck_id = ? AND status <> ?
        ");
        $updateStmt->bind_param("sis", $newStatus, $truckId, $newStatus);
        $updateStmt->execute();
    }
}




try {
    switch ($action) {
 case 'getTrucks':
    updateAllTruckStatuses($conn);
    $stmt = $conn->prepare("SELECT t.truck_id, t.plate_no, t.capacity, 
                          t.status as display_status, t.is_deleted,
                          t.last_modified_by, t.delete_reason,
                          t.last_modified_at, t.truck_pic
                          FROM truck_table t
                          ORDER BY t.truck_id");
    $stmt->execute();
    $result = $stmt->get_result();
    $trucks = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'trucks' => $trucks]);
    break;

         case 'addTruck':
    if (!validatePlateNumber($data['plate_no'])) {
        throw new Exception("Invalid plate number format. Use format like ABC123 or ABC-1234");
    }

    // Handle photo upload
    $truckPic = null;
    if (!empty($_FILES['truck_photo']['name']) && $_FILES['truck_photo']['error'] == UPLOAD_ERR_OK) {
        // Let's check the file type to be safe
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $_FILES['truck_photo']['tmp_name']);
        finfo_close($fileInfo);
        
        if (in_array($mimeType, $allowedTypes)) {
            // File size check (max 2MB)
            if ($_FILES['truck_photo']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Image file is too large. Maximum size is 2MB.");
            }
            
            // Read the image and create a full data URI. This is better for handling different image types.
            $imageContent = file_get_contents($_FILES['truck_photo']['tmp_name']);
            $truckPic = $mimeType . ';base64,' . base64_encode($imageContent);
        } else {
            throw new Exception("Invalid file type. Only JPG, PNG and GIF are allowed.");
        }
    }

    $stmt = $conn->prepare("INSERT INTO truck_table 
                          (plate_no, capacity, status, truck_pic, last_modified_by) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", 
        $data['plate_no'], 
        $data['capacity'], 
        $data['status'],
        $truckPic,
        $currentUser
    );
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

       case 'updateTruck':
    if (!validatePlateNumber($data['plate_no'])) {
        throw new Exception("Invalid plate number format. Use format like ABC123 or ABC-1234");
    }

    $validStatuses = ['In Terminal', 'Enroute', 'In Repair', 'Overdue'];
    if (!in_array($data['status'], $validStatuses)) {
        throw new Exception("Invalid status value");
    }

    // We'll build the query parts dynamically, which is cleaner.
    $photoUpdate = "";
    $types = "ssss"; // Start with types for plate_no, capacity, status, and last_modified_by
    $params = [
        $data['plate_no'], 
        $data['capacity'], 
        $data['status'],
        $currentUser,
    ];

    // Check if a new photo was uploaded
    if (!empty($_FILES['truck_photo']['name']) && $_FILES['truck_photo']['error'] == UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $_FILES['truck_photo']['tmp_name']);
        finfo_close($fileInfo);
        
        if (in_array($mimeType, $allowedTypes)) {
            if ($_FILES['truck_photo']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Image file is too large. Maximum size is 2MB.");
            }
            $imageContent = file_get_contents($_FILES['truck_photo']['tmp_name']);
            $truckPic = $mimeType . ';base64,' . base64_encode($imageContent);

            $photoUpdate = ", truck_pic = ?"; // Add the photo to our SQL query
            $params[] = $truckPic;             // And to our parameters
            $types .= "s";                     // And its type
        } else {
            throw new Exception("Invalid file type. Only JPG, PNG and GIF are allowed.");
        }
    }

    // The truck_id for the WHERE clause always goes last.
    $params[] = $data['truck_id'];
    $types .= "i";

    $stmt = $conn->prepare("UPDATE truck_table 
                             SET plate_no=?, capacity=?, status=?, 
                             last_modified_by=?, last_modified_at=NOW()" . $photoUpdate . "
                             WHERE truck_id=?");
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    // After updating, let's grab the fresh data to send back
    $stmt = $conn->prepare("SELECT t.truck_id, t.plate_no, t.capacity, 
                                  t.status as display_status, t.is_deleted,
                                  t.last_modified_by, t.delete_reason,
                                  t.last_modified_at, t.truck_pic
                           FROM truck_table t WHERE t.truck_id = ?");
    $stmt->bind_param("i", $data['truck_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $updatedTruck = $result->fetch_assoc();

    echo json_encode(['success' => true, 'updatedTruck' => $updatedTruck]);
    break;



  case 'deleteTruck':
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete the truck
        $stmt = $conn->prepare("DELETE FROM truck_table WHERE truck_id=?");
        $stmt->bind_param("i", $data['truck_id']);
        $stmt->execute();
        
        // Get the maximum remaining truck_id
        $maxIdResult = $conn->query("SELECT MAX(truck_id) as max_id FROM truck_table");
        $maxId = $maxIdResult->fetch_assoc()['max_id'];
        
        // Set the auto-increment to max_id + 1
        if ($maxId) {
            $conn->query("ALTER TABLE truck_table AUTO_INCREMENT = " . ($maxId + 1));
        } else {
            // If no trucks left, reset to 1
            $conn->query("ALTER TABLE truck_table AUTO_INCREMENT = 1");
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

    case 'getHistory':
            $truckId = $_GET['truckId'] ?? null;
            if (!$truckId) {
                throw new Exception("Truck ID is required to fetch history.");
            }

            // This query fetches maintenance records for a specific truck,
            // but only if its latest audit log entry is not a soft delete.
            $query = "
                SELECT 
                    m.maintenance_id,
                    m.date_mtnce,
                    m.status,
                    m.cost,
                    m.remarks,
                    mt.maintenance_type_name,
                    s.supplier_name
                FROM 
                    maintenance_table m
                LEFT JOIN 
                    maintenance_types mt ON m.maintenance_type_id = mt.maintenance_type_id
                LEFT JOIN 
                    suppliers s ON m.supplier_id = s.supplier_id
                WHERE 
                    m.truck_id = ?
                AND NOT EXISTS (
                    SELECT 1 FROM audit_logs_maintenance al 
                    WHERE al.maintenance_id = m.maintenance_id 
                    AND al.is_deleted = 1
                    AND al.modified_at = (
                        SELECT MAX(al2.modified_at)
                        FROM audit_logs_maintenance al2
                        WHERE al2.maintenance_id = m.maintenance_id
                    )
                )
                ORDER BY 
                    m.date_mtnce DESC
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $truckId);
            $stmt->execute();
            $result = $stmt->get_result();
            $history = $result->fetch_all(MYSQLI_ASSOC);
            
            // Sending the data back wrapped in a success object, as expected by the frontend.
            echo json_encode(['success' => true, 'history' => $history]);
            break;

    case 'getActiveTrucks':
    $stmt = $conn->prepare("SELECT t.truck_id, t.plate_no, t.capacity, 
                          t.status as display_status
                          FROM truck_table t
                          WHERE t.is_deleted = 0
                          ORDER BY t.truck_id");
    $stmt->execute();
    $result = $stmt->get_result();
    $trucks = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'trucks' => $trucks]);
    break;

    case 'fullDeleteTruck':
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete the truck
        $stmt = $conn->prepare("DELETE FROM truck_table WHERE truck_id=?");
        $stmt->bind_param("i", $data['truck_id']);
        $stmt->execute();
        
        // Get the maximum remaining truck_id
        $maxIdResult = $conn->query("SELECT MAX(truck_id) as max_id FROM truck_table");
        $maxId = $maxIdResult->fetch_assoc()['max_id'];
        
        // Set the auto-increment to max_id + 1
        if ($maxId) {
            $conn->query("ALTER TABLE truck_table AUTO_INCREMENT = " . ($maxId + 1));
        } else {
            // If no trucks left, reset to 1
            $conn->query("ALTER TABLE truck_table AUTO_INCREMENT = 1");
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

case 'softDeleteTruck':
    // First check if truck is Enroute
    $checkStmt = $conn->prepare("SELECT status FROM truck_table WHERE truck_id = ?");
    $checkStmt->bind_param("i", $data['truck_id']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $truckStatus = $checkResult->fetch_assoc()['status'];
    
    if ($truckStatus === 'Enroute') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete a truck that is currently Enroute']);
        break;
    }
    
    $stmt = $conn->prepare("UPDATE truck_table 
                          SET is_deleted=1, status='Deleted', delete_reason=?, 
                          last_modified_by=?, last_modified_at=NOW()
                          WHERE truck_id=?");
    $stmt->bind_param("ssi", 
        $data['delete_reason'],
        $currentUser,
        $data['truck_id']
    );
    $stmt->execute();
    echo json_encode(['success' => true]);
    break;

    case 'getTruckCounts':
    $counts = [];
    
    // Query for each status
    $statuses = ['In Terminal', 'Enroute', 'In Repair', 'Overdue'];
    
    foreach ($statuses as $status) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM truck_table WHERE status = ? AND is_deleted = 0");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $counts[$status] = $result->fetch_assoc()['count'];
    }
    
    // Total trucks (non-deleted)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM truck_table WHERE is_deleted = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $counts['Total'] = $result->fetch_assoc()['count'];
    
    echo json_encode(['success' => true, 'counts' => $counts]);
    break;

    case 'getAvailableTrucks':
    $driverIdBeingEdited = $_GET['driverId'] ?? null;


    $query = "SELECT t.truck_id, t.plate_no
              FROM truck_table t
              WHERE t.is_deleted = 0 AND t.truck_id NOT IN
                  (SELECT d.assigned_truck_id FROM drivers_table d WHERE d.assigned_truck_id IS NOT NULL";
    
    if ($driverIdBeingEdited) {
        $query .= " AND d.driver_id != ?";
    }
    
    $query .= ") ORDER BY t.plate_no ASC";

    $stmt = $conn->prepare($query);

    if ($driverIdBeingEdited) {
        $stmt->bind_param("s", $driverIdBeingEdited);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $trucks = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'trucks' => $trucks]);
    break;

    case 'getPlateByTruckId':
    $stmt = $conn->prepare("SELECT plate_no FROM truck_table WHERE truck_id = ? AND is_deleted = 0");
    $stmt->bind_param("i", $data['truck_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $truck = $result->fetch_assoc();
        echo json_encode(['success' => true, 'plate_no' => $truck['plate_no']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Truck not found']);
    }
    break;

    case 'restoreTruck':
    // First get the truck's previous status before deletion
    $getStmt = $conn->prepare("SELECT status FROM truck_table WHERE truck_id = ?");
    $getStmt->bind_param("i", $data['truck_id']);
    $getStmt->execute();
    $result = $getStmt->get_result();
    $truck = $result->fetch_assoc();
    
    // Determine the status to restore to
    $statusToRestore = 'In Terminal'; // default if no status found
    if ($truck && isset($truck['status'])) {
        // If the status was 'Deleted' (from soft delete), restore to default
        $statusToRestore = ($truck['status'] === 'Deleted') ? 'In Terminal' : $truck['status'];
    }
    
    // Now update the truck
    $stmt = $conn->prepare("UPDATE truck_table 
                          SET is_deleted=0, delete_reason=NULL, status=?,
                          last_modified_by=?, last_modified_at=NOW()
                          WHERE truck_id=?");
    $stmt->bind_param("ssi", 
        $statusToRestore,
        $currentUser,
        $data['truck_id']
    );
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