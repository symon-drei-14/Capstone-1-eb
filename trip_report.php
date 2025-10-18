<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess();

require 'include/handlers/dbhandler.php';

$tripId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tripId) {
    header('Location: triplogs.php');
    exit();
}

$sql = "SELECT 
            t.*,
            tr.plate_no as truck_plate_no, 
            tr.capacity as truck_capacity,
            d.name as driver,
            h.name as helper,
            disp.name as dispatcher,
            c.name as client,
            p.name as port,  
            dest.name as destination,
            sl.name as shipping_line,
            cons.name as consignee,
            COALESCE(te.cash_advance, 0) as cash_advance,
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
        LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
        WHERE t.trip_id = ? AND NOT EXISTS (
            SELECT 1 FROM audit_logs_trips al2 
            WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
        )";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tripId);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    header('Location: triplogs.php');
    exit();
}

$expensesSql = "SELECT   et.name as expense_type,
            CONCAT('₱', FORMAT(de.amount, 2)) as amount,
            de.created_at as submitted_time,
            de.receipt_image
        FROM driver_expenses de
        INNER JOIN expense_types et ON de.expense_type_id = et.type_id
        WHERE de.trip_id = ?
        ORDER BY de.created_at DESC";
$expensesStmt = $conn->prepare($expensesSql);
$expensesStmt->bind_param("i", $tripId);
$expensesStmt->execute();
$expensesResult = $expensesStmt->get_result();
$expenses = [];
$totalExpenses = 0;

while ($expense = $expensesResult->fetch_assoc()) {
    $expenses[] = $expense;
    $totalExpenses += floatval(str_replace(['₱', ','], '', $expense['amount']));
}

function formatDateTime($datetimeString) {
    if (!$datetimeString) return 'N/A';
    try {
        $date = new DateTime($datetimeString);
        return $date->format('F j, Y g:i A');
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Report - <?php echo htmlspecialchars($trip['container_no']); ?></title>
    <link rel="stylesheet" href="include/css/tripreport.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.0/css/all.css">

</head>
<body>
    <a href="triplogs.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Trip Logs
    </a>
    
    <button onclick="window.print()" class="print-button">
        <i class="fas fa-print"></i> Print Report
    </button>

    <div class="report-container">
        <div class="report-header">
            <!-- <div class="company-logo">
                <img src="include/img/mansar2.png" alt="Company Logo">
            </div> -->
            <h1 class="report-title">TRIP REPORT</h1>
            <p class="report-subtitle">Trip ID: TR-<?php echo str_pad($trip['trip_id'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>

        <div class="report-content">
            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-truck"></i>
                    Trip Overview
                </h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Trip Date</div>
                        <div class="info-value"><?php echo formatDateTime($trip['trip_date']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Container No.</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['container_no']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Container Size</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['fcl_status']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Plate Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['truck_plate_no']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status <?php echo strtolower(str_replace(' ', '', $trip['status'])); ?>">
                                <?php echo htmlspecialchars($trip['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-user-friends"></i>
                    Employee Details
                </h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Driver</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['driver'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Helper</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['helper'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Dispatcher</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['dispatcher'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-map-marked-alt"></i>
                    Client  & Trip Details
                </h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Client</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['client'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Port</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['port'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Destination</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['destination'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Shipping Line</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['shipping_line'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Consignee</div>
                        <div class="info-value"><?php echo htmlspecialchars($trip['consignee'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h2 class="section-title">
                    <i class="fas fa-wallet"></i>
                    Financial Summary
                </h2>
                
                <h4 style="margin: 0 0 10px; color: #495057;">Initial Funds</h4>
               <table class="expense-table">
    <thead>
        <tr>
            <th>Cash Advance</th>
            <th>Additional Cash</th>
            <th>Total Initial</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>₱<?php echo number_format($trip['cash_advance'], 2); ?></td>
            <td>₱<?php echo number_format($trip['additional_cash_advance'], 2); ?></td>
            <td><strong>₱<?php echo number_format($trip['cash_advance'] + $trip['additional_cash_advance'], 2); ?></strong></td>
        </tr>
    </tbody>
</table>

                <?php if (!empty($expenses)): ?>
                <h4 style="margin: 20px 0 10px; color: #495057;">Breakdown of Researchers</h4>
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Submitted At</th>
                            <th>Receipt</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($expense['expense_type']); ?></td>
                          
                            <td><?php echo formatDateTime($expense['submitted_time']); ?></td>
                            <td><?php echo $expense['receipt_image'] ? 'Yes' : 'No'; ?></td>
                              <td><?php echo htmlspecialchars($expense['amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3"><strong>Total</strong></td>
                            <td><strong>₱<?php echo number_format($totalExpenses, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="margin-top: 15px; color: #6c757d; font-style: italic;">No additional expenses recorded.</p>
                <?php endif; ?>
            </div>


            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; font-size: 12px; color: #6c757d;">
                Report generated on <?php echo date('F j, Y g:i A'); ?><br>
                Mansar Logistics - Nakakasigurado tama ang gamot
            </div>
        </div>
    </div>
</body>
</html>