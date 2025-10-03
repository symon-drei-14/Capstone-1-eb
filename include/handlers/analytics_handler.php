<?php
header("Content-Type: application/json");
session_start();
require_once 'dbhandler.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

try {
    
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'cost_trends':
            getCostTrends($conn);
            break;
        case 'monthly_trends':
            getMonthlyTrends($conn);
            break;
        case 'yearly_trends':
            getYearlyTrends($conn);
            break;
        case 'get_completed_trip_counts':
            getCompletedTripCounts($conn);
        break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

function getCostTrends($conn) {
    // Get cost breakdown by expense type for the current year from completed trips.
    $sql = "SELECT 
                et.name as expense_category,
                SUM(de.amount) as total_amount
            FROM driver_expenses de
            JOIN expense_types et ON de.expense_type_id = et.type_id
            JOIN trips t ON de.trip_id = t.trip_id
            WHERE t.status = 'Completed'
              AND YEAR(t.trip_date) = YEAR(CURDATE())
            GROUP BY et.name
            ORDER BY total_amount DESC";
    
    $result = $conn->query($sql);
    
    $data = [];
    $labels = [];
    $total = 0;
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $amount = floatval($row['total_amount']);
            $data[] = $amount;
            $labels[] = $row['expense_category'];
            $total += $amount;
        }
    }
    
    // If we didn't find any data, we should let the front-end know.
    if (empty($data)) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'labels' => [],
            'percentages' => [],
            'total' => 0,
            'message' => 'No expense data found for the current year'
        ]);
        return;
    }
    
    // Calculate percentages for the chart's legend
    $percentages = [];
    foreach($data as $amount) {
        $percentages[] = $total > 0 ? round(($amount / $total) * 100, 1) : 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'labels' => $labels,
        'percentages' => $percentages,
        'total' => $total
    ]);
}

function getCompletedTripCounts($conn) {
    // Fetches the number of completed trips for the last 6 months to display on the dashboard chart.
    $sql = "SELECT
                DATE_FORMAT(trip_date, '%b ''%y') AS month_year,
                COUNT(trip_id) AS trip_count
            FROM trips
            WHERE
                status = 'Completed'
                AND trip_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                AND NOT EXISTS (
                    SELECT 1 FROM audit_logs_trips al2
                    WHERE al2.trip_id = trips.trip_id AND al2.is_deleted = 1
                )
            GROUP BY
                DATE_FORMAT(trip_date, '%Y-%m')
            ORDER BY
                DATE_FORMAT(trip_date, '%Y-%m') ASC";

    $result = $conn->query($sql);

    $labels = [];
    $data = [];

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $labels[] = $row['month_year'];
            $data[] = (int)$row['trip_count'];
        }
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'data' => $data
    ]);
}

function getMonthlyTrends($conn) {
    // Get monthly cost trends for the current year from completed trips
    $sql = "SELECT 
                MONTHNAME(t.trip_date) as month_name,
                et.name as expense_category,
                SUM(de.amount) as total_amount
            FROM driver_expenses de
            JOIN expense_types et ON de.expense_type_id = et.type_id
            JOIN trips t ON de.trip_id = t.trip_id
            WHERE t.status = 'Completed'
              AND YEAR(t.trip_date) = YEAR(CURDATE())
            GROUP BY MONTH(t.trip_date), et.name
            ORDER BY MONTH(t.trip_date), et.name";
    
    $result = $conn->query($sql);
    
    // This part organizes the data by month, then by expense type, which is what the chart expects.
    $monthlyData = [];
    $expenseTypes = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $month = $row['month_name'];
            $type = $row['expense_category'];
            $amount = floatval($row['total_amount']);
            
            if(!in_array($type, $expenseTypes)) {
                $expenseTypes[] = $type;
            }
            
            if(!isset($monthlyData[$month])) {
                $monthlyData[$month] = [];
            }
            
            $monthlyData[$month][$type] = $amount;
        }
    }
    
    echo json_encode([
        'success' => true,
        'monthlyData' => $monthlyData,
        'expenseTypes' => $expenseTypes
    ]);
}

function getYearlyTrends($conn) {
    // Get yearly cost trends for the last 5 years from completed trips
    $sql = "SELECT 
                YEAR(t.trip_date) as year,
                et.name as expense_category,
                SUM(de.amount) as total_amount
            FROM driver_expenses de
            JOIN expense_types et ON de.expense_type_id = et.type_id
            JOIN trips t ON de.trip_id = t.trip_id
            WHERE t.status = 'Completed'
              AND t.trip_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
            GROUP BY YEAR(t.trip_date), et.name
            ORDER BY YEAR(t.trip_date), et.name";
    
    $result = $conn->query($sql);
    
    // Organizes the data by year, which is perfect for the yearly chart view.
    $yearlyData = [];
    $expenseTypes = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $year = $row['year'];
            $type = $row['expense_category'];
            $amount = floatval($row['total_amount']);
            
            if(!in_array($type, $expenseTypes)) {
                $expenseTypes[] = $type;
            }
            
            if(!isset($yearlyData[$year])) {
                $yearlyData[$year] = [];
            }
            
            $yearlyData[$year][$type] = $amount;
        }
    }
    
    echo json_encode([
        'success' => true,
        'yearlyData' => $yearlyData,
        'expenseTypes' => $expenseTypes
    ]);
}

$conn->close();
?>