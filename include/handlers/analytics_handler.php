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
    // Get cost breakdown by expense type for the current year
    // Using CASE to standardize expense types to match your mobile categories
    $sql = "SELECT 
                CASE 
                    WHEN LOWER(expense_type) IN ('gas', 'fuel', 'gasoline') THEN 'Gas'
                    WHEN LOWER(expense_type) IN ('toll gate', 'toll', 'tollgate') THEN 'Toll Gate'
                    WHEN LOWER(expense_type) IN ('maintenance', 'repair', 'service') THEN 'Maintenance'
                    WHEN LOWER(expense_type) IN ('food', 'meal', 'snack') THEN 'Food'
                    WHEN LOWER(expense_type) IN ('parking', 'park') THEN 'Parking'
                    ELSE 'Other'
                END as expense_category,
                SUM(amount) as total_amount,
                COUNT(*) as count
            FROM expenses 
            WHERE YEAR(created_at) = YEAR(CURDATE())
            GROUP BY expense_category
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
    
    // If no data found, return empty but successful response
    if (empty($data)) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'labels' => [],
            'percentages' => [],
            'total' => 0,
            'message' => 'No expense data found for current year'
        ]);
        return;
    }
    
    // Calculate percentages for legend
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
    // Get monthly cost trends for the current year
    $sql = "SELECT 
                MONTH(created_at) as month,
                MONTHNAME(created_at) as month_name,
                CASE 
                    WHEN LOWER(expense_type) IN ('gas', 'fuel', 'gasoline') THEN 'Gas'
                    WHEN LOWER(expense_type) IN ('toll gate', 'toll', 'tollgate') THEN 'Toll Gate'
                    WHEN LOWER(expense_type) IN ('maintenance', 'repair', 'service') THEN 'Maintenance'
                    WHEN LOWER(expense_type) IN ('food', 'meal', 'snack') THEN 'Food'
                    WHEN LOWER(expense_type) IN ('parking', 'park') THEN 'Parking'
                    ELSE 'Other'
                END as expense_category,
                SUM(amount) as total_amount
            FROM expenses 
            WHERE YEAR(created_at) = YEAR(CURDATE())
            GROUP BY MONTH(created_at), expense_category
            ORDER BY MONTH(created_at), expense_category";
    
    $result = $conn->query($sql);
    
    // Organize data by month and expense type
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
    // Get yearly cost trends for the last 5 years
    $sql = "SELECT 
                YEAR(created_at) as year,
                CASE 
                    WHEN LOWER(expense_type) IN ('gas', 'fuel', 'gasoline') THEN 'Gas'
                    WHEN LOWER(expense_type) IN ('toll gate', 'toll', 'tollgate') THEN 'Toll Gate'
                    WHEN LOWER(expense_type) IN ('maintenance', 'repair', 'service') THEN 'Maintenance'
                    WHEN LOWER(expense_type) IN ('food', 'meal', 'snack') THEN 'Food'
                    WHEN LOWER(expense_type) IN ('parking', 'park') THEN 'Parking'
                    ELSE 'Other'
                END as expense_category,
                SUM(amount) as total_amount
            FROM expenses 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
            GROUP BY YEAR(created_at), expense_category
            ORDER BY YEAR(created_at), expense_category";
    
    $result = $conn->query($sql);
    
    // Organize data by year and expense type
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