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
            $limit = 3;
            $offset = ($page - 1) * $limit;
            
            // Get total count using trips table
            $countStmt = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM trips t
                WHERE t.status = 'En Route'
                AND NOT EXISTS (
                    SELECT 1 FROM audit_logs_trips al2 
                    WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
                )
            ");
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            $totalPages = ceil($total / $limit);
            
            // Get paginated results using trips table - MODIFIED TO INCLUDE truck_pic
            $stmt = $conn->prepare("
                SELECT 
                    t.trip_id,
                    t.container_no,
                    t.trip_date as date,
                    t.status,
                    tr.plate_no, 
                    tr.truck_pic,
                    tr.capacity,
                    d.name as driver,
                    h.name as helper,
                    c.name as client,
                    dest.name as destination,
                    al.modified_by as last_modified_by,
                    al.modified_at as last_modified_at
                FROM trips t
                LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
                LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
                LEFT JOIN helpers h ON t.helper_id = h.helper_id
                LEFT JOIN clients c ON t.client_id = c.client_id
                LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
                LEFT JOIN audit_logs_trips al ON t.trip_id = al.trip_id AND al.is_deleted = 0
                WHERE t.status = 'En Route'
                AND NOT EXISTS (
                    SELECT 1 FROM audit_logs_trips al2 
                    WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
                )
                ORDER BY t.trip_date DESC
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

        case 'get_trip_details':
            $tripId = $data['tripId'] ?? null;
            if (!$tripId) {
                throw new Exception("Trip ID is required");
            }

        
            $stmt = $conn->prepare("
                SELECT 
                    t.trip_id,
                    t.container_no,
                    t.trip_date as date,
                    t.status,
                    tr.plate_no, 
                    tr.capacity as size,
                    d.name as driver,
                    d.driver_id,
                    h.name as helper,
                    disp.name as dispatcher,
                    c.name as client,
                    p.name as port, 
                    dest.name as destination,
                    sl.name as shipping_line,
                    cons.name as consignee,
                    al.modified_by as last_modified_by,
                    al.modified_at as last_modified_at,
                    al.edit_reason as edit_reasons,
                    COALESCE(te.cash_advance, 0) as cash_adv,
                    COALESCE(te.additional_cash_advance, 0) as additional_cash_advance
                FROM trips t
                LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
                LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
                LEFT JOIN helpers h ON t.helper_id = h.helper_id
                LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
                LEFT JOIN clients c ON t.client_id = c.client_id
                LEFT JOIN ports p ON t.port_id = p.port_id 
                LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
                LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
                LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
                LEFT JOIN audit_logs_trips al ON t.trip_id = al.trip_id AND al.is_deleted = 0
                LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
                WHERE t.trip_id = ?
                AND NOT EXISTS (
                    SELECT 1 FROM audit_logs_trips al2 
                    WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
                )
            ");
            $stmt->bind_param("i", $tripId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Trip not found");
            }

            $trip = $result->fetch_assoc();

            $formattedTrip = [
                'trip_id' => $trip['trip_id'],
                'plate_no' => $trip['plate_no'],
                'date' => $trip['date'],
                'driver' => $trip['driver'],
                'helper' => $trip['helper'],
                'dispatcher' => $trip['dispatcher'],
                'container_no' => $trip['container_no'],
                'client' => $trip['client'],
                'port' => $trip['port'], 
                'destination' => $trip['destination'],
                'shipping_line' => $trip['shipping_line'],
                'consignee' => $trip['consignee'],
                'size' => $trip['size'],
                'cash_adv' => $trip['cash_adv'],
                'status' => $trip['status'],
                'last_modified_by' => $trip['last_modified_by'],
                'last_modified_at' => $trip['last_modified_at'],
                'edit_reasons' => $trip['edit_reasons'],
                'additional_cash_advance' => $trip['additional_cash_advance']
            ];

            echo json_encode(['success' => true, 'trip' => $formattedTrip]);
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