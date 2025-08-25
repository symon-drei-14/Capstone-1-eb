<?php
function getTripStatistics($conn) {
    $stats = [
        'pending' => 0,
        'enroute' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'total' => 0
    ];

    $query = "SELECT 
                t.status,
                COUNT(*) as count 
              FROM trips t
              WHERE NOT EXISTS (
                  SELECT 1 FROM audit_logs_trips al 
                  WHERE al.trip_id = t.trip_id AND al.is_deleted = 1
              )
              GROUP BY t.status";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = strtolower($row['status']);
            $count = (int)$row['count'];
            
            switch ($status) {
                case 'pending':
                    $stats['pending'] = $count;
                    break;
                case 'en route':
                    $stats['enroute'] = $count;
                    break;
                case 'completed':
                    $stats['completed'] = $count;
                    break;
                case 'cancelled':
                    $stats['cancelled'] = $count;
                    break;
            }
            
            $stats['total'] += $count;
        }
    }
    
    return $stats;
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once 'dbhandler.php';
    
    header('Content-Type: application/json');
    
    try {
        $stats = getTripStatistics($conn);
        echo json_encode($stats);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
    $conn->close();
}
?>