<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess();

require 'include/handlers/dbhandler.php';

// --- Determine Date Range and Report Title from URL parameters ---
$reportType = $_GET['type'] ?? 'daily';
$startDate = '';
$endDate = '';
$reportTitle = 'Maintenance Expense Summary';
$subtitle = '';

try {
    switch ($reportType) {
        case 'weekly':
            $week = $_GET['week'] ?? '';
            if ($week) {
                $year = substr($week, 0, 4);
                $weekNum = substr($week, 6, 2);
                $dto = new DateTime();
                $dto->setISODate((int)$year, (int)$weekNum);
                $startDate = $dto->format('Y-m-d');
                $subtitle = "From " . $dto->format('M j, Y');
                $dto->modify('+6 days');
                $endDate = $dto->format('Y-m-d');
                $subtitle .= " to " . $dto->format('M j, Y');
                $reportTitle = "Weekly Maintenance Expense Summary";
            }
            break;
        case 'monthly':
            $month = $_GET['month'] ?? '';
            if ($month) {
                $startDate = date('Y-m-01', strtotime($month));
                $endDate = date('Y-m-t', strtotime($month));
                $reportTitle = "Monthly Maintenance Expense Summary";
                $subtitle = date('F Y', strtotime($month));
            }
            break;
        case 'yearly':
            $year = $_GET['year'] ?? '';
            if ($year) {
                $startDate = $year . '-01-01';
                $endDate = $year . '-12-31';
                $reportTitle = "Yearly Maintenance Expense Summary";
                $subtitle = "For the Year " . $year;
            }
            break;
        case 'daily':
        default:
            $date = $_GET['date'] ?? date('Y-m-d');
            $startDate = $endDate = $date;
            $reportTitle = "Daily Maintenance Expense Summary";
            $subtitle = "For " . date('F j, Y', strtotime($date));
            break;
    }
} catch (Exception $e) {
    die("Error processing date: " . $e->getMessage());
}

if (empty($startDate) || empty($endDate)) {
    die("Invalid date range selected. Please go back and try again.");
}

// --- Fetch Maintenance Data and Expenses ---
$maintenanceRecords = [];
$grandTotalCost = 0;

// Get all non-deleted maintenance records within the date range
$maintSql = "SELECT m.maintenance_id, m.date_mtnce, m.remarks, m.cost, t.plate_no, s.name as supplier_name
             FROM maintenance_table m
             LEFT JOIN truck_table t ON m.truck_id = t.truck_id
             LEFT JOIN suppliers s ON m.supplier_id = s.supplier_id
             WHERE DATE(m.date_mtnce) BETWEEN ? AND ?
             AND NOT EXISTS (
                 SELECT 1 FROM audit_logs_maintenance alm 
                 WHERE alm.maintenance_id = m.maintenance_id AND alm.is_deleted = 1
             )
             ORDER BY m.date_mtnce ASC";

$maintStmt = $conn->prepare($maintSql);
$maintStmt->bind_param("ss", $startDate, $endDate);
$maintStmt->execute();
$maintResult = $maintStmt->get_result();

$maintenanceIds = [];
while ($row = $maintResult->fetch_assoc()) {
    $maintenanceRecords[$row['maintenance_id']] = $row;
    $maintenanceRecords[$row['maintenance_id']]['expenses'] = []; // Prepare to hold expenses
    $maintenanceIds[] = $row['maintenance_id'];
    // The main 'cost' is the sum of detailed expenses, so we'll calculate it below.
}
$maintStmt->close();

if (!empty($maintenanceIds)) {
    $placeholders = implode(',', array_fill(0, count($maintenanceIds), '?'));
    $types = str_repeat('i', count($maintenanceIds));

    // Fetch all associated expenses for the collected maintenance IDs
    $expenseSql = "SELECT maintenance_id, expense_type, amount 
                   FROM maintenance_expenses 
                   WHERE maintenance_id IN ($placeholders)";
    
    $expenseStmt = $conn->prepare($expenseSql);
    $expenseStmt->bind_param($types, ...$maintenanceIds);
    $expenseStmt->execute();
    $expenseResult = $expenseStmt->get_result();
    
    while ($row = $expenseResult->fetch_assoc()) {
        if (isset($maintenanceRecords[$row['maintenance_id']])) {
            $maintenanceRecords[$row['maintenance_id']]['expenses'][] = $row;
            $grandTotalCost += floatval($row['amount']);
        }
    }
    $expenseStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($reportTitle); ?></title>
   
    <link rel="stylesheet" href="include/css/expense_summary.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.0/css/all.css">
</head>
<body>
    
    <a href="maintenance.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Maintenance</a>
    <button onclick="window.print()" class="print-button"><i class="fas fa-print"></i> Print Summary</button>

    <div class="report-container">
        <div class="report-header">
            <h1 class="report-title"><?php echo htmlspecialchars($reportTitle); ?></h1>
            <p class="report-subtitle"><?php echo htmlspecialchars($subtitle); ?></p>
        </div>

        <div class="report-content">
            <div class="info-section">
                <h2 class="section-title"><i class="fas fa-coins"></i> Total Summary for the Period</h2>
                <table class="summary-table">
                     <tr class="grand-total">
                        <td>GRAND TOTAL FOR MAINTENANCE:</td>
                        <td>₱<?php echo number_format($grandTotalCost, 2); ?></td>
                    </tr>
                </table>
            </div>

            <div class="info-section">
                <h2 class="section-title"><i class="fas fa-list-ul"></i> Detailed Breakdown per Maintenance Record</h2>
                <?php if (empty($maintenanceRecords)): ?>
                    <p style="text-align:center; color: #6c757d; margin-top: 20px;">No maintenance expenses were found for this period.</p>
                <?php else: ?>
                    <?php foreach ($maintenanceRecords as $id => $record): ?>
                        <div class="trip-card"> <!-- Reusing 'trip-card' class for styling -->
                            <div class="trip-card-header">
                                <h3>Maintenance ID: MT-<?php echo str_pad($id, 6, '0', STR_PAD_LEFT); ?></h3>
                                <p>
                                    <strong>Date:</strong> <?php echo date('M j, Y', strtotime($record['date_mtnce'])); ?> | 
                                    <strong>Plate No:</strong> <?php echo htmlspecialchars($record['plate_no']); ?> | 
                                    <strong>Supplier:</strong> <?php echo htmlspecialchars($record['supplier_name']); ?>
                                </p>
                            </div>
                            <?php 
                                $recordTotal = 0;
                                if (!empty($record['expenses'])): 
                            ?>
                                <table class="expense-table">
                                    <thead>
                                        <tr>
                                            <th>Expense Item</th>
                                            <th class="amount">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($record['expenses'] as $expense): 
                                            $recordTotal += floatval($expense['amount']);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($expense['expense_type']); ?></td>
                                            <td class="amount">₱<?php echo number_format($expense['amount'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="trip-total"> <!-- Reusing style -->
                                            <th>Total for this Record</th>
                                            <th class="amount">₱<?php echo number_format($recordTotal, 2); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            <?php else: ?>
                                <p class="no-expenses-row">No detailed expenses recorded for this maintenance.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="report-footer">
            Report generated on <?php echo date('F j, Y g:i A'); ?>
        </div>
    </div>
</body>
</html>