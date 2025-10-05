<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess(); // Making sure the user is logged in

require 'include/handlers/dbhandler.php';

// Get the date from the URL, defaulting to today if it's not set
$summaryDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// -- Step 1: Fetch all trips for the selected date --
$tripsSql = "
    SELECT 
        t.trip_id,
        tr.plate_no,
        d.name as driver_name,
        COALESCE(te.cash_advance, 0) as cash_advance,
        COALESCE(te.additional_cash_advance, 0) as additional_cash_advance,
        COALESCE(te.diesel, 0) as diesel
    FROM trips t
    LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
    LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
    LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
    WHERE DATE(t.trip_date) = ? 
    AND NOT EXISTS (
        SELECT 1 FROM audit_logs_trips al2 
        WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
    )
    ORDER BY t.trip_date ASC
";

$stmt = $conn->prepare($tripsSql);
$stmt->bind_param("s", $summaryDate);
$stmt->execute();
$tripsResult = $stmt->get_result();

$trips = [];
$tripIds = [];
while ($row = $tripsResult->fetch_assoc()) {
    $trips[$row['trip_id']] = $row;
    $tripIds[] = $row['trip_id'];
}
$stmt->close();

// -- Step 2: Fetch all driver-submitted expenses for those trips --
$driverExpenses = [];
if (!empty($tripIds)) {
    $placeholders = implode(',', array_fill(0, count($tripIds), '?'));
    $types = str_repeat('i', count($tripIds));
    
    // We're changing this to a LEFT JOIN to make sure we get all expenses,
    // even if an expense type was somehow deleted. COALESCE helps show a default name.
    $expensesSql = "
        SELECT 
            de.trip_id,
            COALESCE(et.name, 'Unknown Type') as expense_type,
            de.amount
        FROM driver_expenses de
        LEFT JOIN expense_types et ON de.expense_type_id = et.type_id
        WHERE de.trip_id IN ($placeholders)
        ORDER BY de.trip_id, et.name
    ";
    
    $stmt = $conn->prepare($expensesSql);
    $stmt->bind_param($types, ...$tripIds);
    $stmt->execute();
    $expensesResult = $stmt->get_result();
    
    while ($row = $expensesResult->fetch_assoc()) {
        $driverExpenses[] = $row;
    }
    $stmt->close();
}

// -- Step 3: Calculate the totals for the summary --
$totalCashAdvance = 0;
$totalAdditionalCash = 0;
$totalDiesel = 0;
$totalDriverExpenses = 0;

foreach ($trips as $trip) {
    $totalCashAdvance += $trip['cash_advance'];
    $totalAdditionalCash += $trip['additional_cash_advance'];
    $totalDiesel += $trip['diesel'];
}

foreach ($driverExpenses as $expense) {
    $totalDriverExpenses += $expense['amount'];
}


$grandTotal = $totalCashAdvance + $totalAdditionalCash + $totalDiesel;


$remainingBalance = ($totalCashAdvance + $totalAdditionalCash) - $totalDriverExpenses;

function formatDateTimeForReport($datetimeString) {
    if (!$datetimeString) return 'N/A';
    try {
        $date = new DateTime($datetimeString);
        return $date->format('F j, Y');
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
    <title>Daily Expense Summary - <?php echo htmlspecialchars($summaryDate); ?></title>
    <link rel="stylesheet" href="include/css/expense_summary.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.0/css/all.css">
</head>
<body>
    <a href="triplogs.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Trip Logs</a>
    <button onclick="window.print()" class="print-button"><i class="fas fa-print"></i> Print Summary</button>

    <div class="report-container">
        <div class="report-header">
            <h1 class="report-title">Daily Expense Summary</h1>
            <p class="report-subtitle">Date: <?php echo formatDateTimeForReport($summaryDate); ?></p>
        </div>

        <div class="report-content">
          <div class="info-section">
    <h2 class="section-title"><i class="fas fa-coins"></i> Total Summary for the Day</h2>
    <table class="summary-table">
        <tr>
            <td>Total Cash Advance:</td>
            <td>₱<?php echo number_format($totalCashAdvance, 2); ?></td>
        </tr>
        <tr>
            <td>Total Additional Cash:</td>
            <td>₱<?php echo number_format($totalAdditionalCash, 2); ?></td>
        </tr>
        <tr>
            <td>Total Diesel Expenses:</td>
            <td>₱<?php echo number_format($totalDiesel, 2); ?></td>
        </tr>
         <tr>
            <td>Total Driver-Submitted Expenses:</td>
            <td>₱<?php echo number_format($totalDriverExpenses, 2); ?></td>
        </tr>
        <tr class="remaining-balance">
            <td>Remaining Balance (Cash on Hand):</td>
            <td>₱<?php echo number_format($remainingBalance, 2); ?></td>
        </tr>
        <tr class="grand-total">
            <td>GRAND TOTAL DISBURSED:</td>
            <td>₱<?php echo number_format($grandTotal, 2); ?></td>
        </tr>
    </table>
</div>

         <div class="info-section">
    <h2 class="section-title"><i class="fas fa-list-ul"></i> Detailed Breakdown per Trip</h2>
    <?php if (empty($trips)): ?>
        <p style="text-align:center; color: #6c757d; margin-top: 20px;">No trips with expenses were found for this date.</p>
    <?php else: ?>
        <?php foreach ($trips as $tripId => $trip): 
            // Filter driver expenses for the current trip
            $tripDriverExpenses = array_filter($driverExpenses, function($exp) use ($tripId) {
                return $exp['trip_id'] == $tripId;
            });

            // Calculate totals for this specific trip
            $tripDriverExpensesTotal = 0;
            foreach ($tripDriverExpenses as $expense) {
                $tripDriverExpensesTotal += $expense['amount'];
            }
            $tripCashGiven = $trip['cash_advance'] + $trip['additional_cash_advance'];
            $tripRemainingBalance = $tripCashGiven - $tripDriverExpensesTotal;
            $tripTotal = $tripCashGiven + $trip['diesel'];
        ?>
            <div class="trip-card">
                <div class="trip-card-header">
                    <h3>Trip ID: TR-<?php echo str_pad($tripId, 6, '0', STR_PAD_LEFT); ?></h3>
                    <p>
                        <strong>Plate No:</strong> <?php echo htmlspecialchars($trip['plate_no']); ?> | 
                        <strong>Driver:</strong> <?php echo htmlspecialchars($trip['driver_name']); ?>
                    </p>
                </div>
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Expense Type</th>
                            <th class="amount">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Cash Advance</td>
                            <td class="amount">₱<?php echo number_format($trip['cash_advance'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Additional Cash</td>
                            <td class="amount">₱<?php echo number_format($trip['additional_cash_advance'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Diesel</td>
                            <td class="amount">₱<?php echo number_format($trip['diesel'], 2); ?></td>
                        </tr>
                        <?php if (empty($tripDriverExpenses)): ?>
                            <tr>
                                <td colspan="2" class="no-expenses-row">No driver-submitted expenses for this trip.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tripDriverExpenses as $expense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($expense['expense_type']); ?> (Driver Submitted)</td>
                                <td class="amount">₱<?php echo number_format($expense['amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="trip-balance">
                            <th>Remaining Balance</th>
                            <th class="amount">₱<?php echo number_format($tripRemainingBalance, 2); ?></th>
                        </tr>
                        <tr class="trip-total">
                            <th>Total Disbursed for Trip</th>
                            <th class="amount">₱<?php echo number_format($tripTotal, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
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