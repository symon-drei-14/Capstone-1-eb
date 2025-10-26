<?php
require_once __DIR__ . '/dbhandler.php';
require_once __DIR__ . '/phpmailer_config.php';


date_default_timezone_set('Asia/Manila');

function getNotificationRecipients($conn) {
    $recipients = [];
    $sql = "SELECT admin_email 
            FROM login_admin 
            WHERE (role = 'Full Admin' OR role = 'Fleet Manager') 
              AND is_deleted = FALSE 
              AND admin_email IS NOT NULL 
              AND admin_email != ''";
              
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recipients[] = $row['admin_email'];
        }
    }
    return $recipients;
}

function updateAndGetNotifications($conn) {
    $currentDate = date('Y-m-d');
    
    $updateOverdueSql = "UPDATE maintenance_table m 
                         SET status = 'Overdue' 
                         WHERE status = 'Pending'
                           AND date_mtnce < ?
                           AND NOT EXISTS (
                               SELECT 1 FROM audit_logs_maintenance al 
                               WHERE al.maintenance_id = m.maintenance_id 
                                 AND al.is_deleted = 1
                                 AND al.modified_at = (
                                     SELECT MAX(al2.modified_at)
                                     FROM audit_logs_maintenance al2
                                     WHERE al2.maintenance_id = m.maintenance_id
                                 )
                           )";
    
    $updateStmt = $conn->prepare($updateOverdueSql);
    if ($updateStmt) {
        $updateStmt->bind_param("s", $currentDate);
        $updateStmt->execute();
        $updateStmt->close();
    }

    $sql = "SELECT 
                m.maintenance_id, 
                m.date_mtnce, 
                m.status, 
                m.remarks,
                t.plate_no,
                CASE 
                    WHEN m.maintenance_type_id = 1 THEN 'Preventive Maintenance'
                    WHEN m.maintenance_type_id = 2 THEN 'Emergency Maintenance'
                    ELSE 'Other'
                END AS type_name
            FROM maintenance_table m
            JOIN truck_table t ON m.truck_id = t.truck_id
            LEFT JOIN (
                SELECT al1.maintenance_id, al1.is_deleted
                FROM audit_logs_maintenance al1
                WHERE al1.modified_at = (
                    SELECT MAX(al2.modified_at)
                    FROM audit_logs_maintenance al2
                    WHERE al2.maintenance_id = al1.maintenance_id
                )
            ) latest_audit ON m.maintenance_id = latest_audit.maintenance_id
            WHERE 
                (latest_audit.is_deleted = 0 OR latest_audit.is_deleted IS NULL)
                AND t.is_deleted = 0
                AND (
                    (m.status = 'Pending' AND m.date_mtnce BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY))
                    OR
                    (m.status = 'Overdue')
                )
            ORDER BY m.status DESC, m.date_mtnce ASC";
            
    $result = $conn->query($sql);
    
    $overdue = [];
    $upcoming = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] == 'Overdue') {
                $overdue[] = $row;
            } else if ($row['status'] == 'Pending') {
                $upcoming[] = $row;
            }
        }
    }
    
    return ['overdue' => $overdue, 'upcoming' => $upcoming];
}

function buildEmailBody($overdue, $upcoming) {
    $body = "
    <html>
    <head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { width: 90%; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .status-overdue { color: #D8000C; font-weight: bold; }
        .status-pending { color: #9F6000; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 0.9em; color: #777; }
    </style>
    </head>
    <body>
    <div class='container'>
        <h2>Mansar Logistics Maintenance Notification</h2>
        <p>This is an automated notification for overdue and upcoming truck maintenance schedules.</p>";

    if (!empty($overdue)) {
        $body .= "<h3><span class='status-overdue'>Overdue Maintenance</span></h3>
        <table>
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>Plate No.</th>
                    <th>Maintenance Type</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>";
        foreach ($overdue as $item) {
            $body .= "<tr>
                        <td>" . htmlspecialchars($item['date_mtnce']) . "</td>
                        <td>" . htmlspecialchars($item['plate_no']) . "</td>
                        <td>" . htmlspecialchars($item['type_name']) . "</td>
                        <td><span class='status-overdue'>" . htmlspecialchars($item['status']) . "</span></td>
                        <td>" . htmlspecialchars($item['remarks']) . "</td>
                      </tr>";
        }
        $body .= "</tbody></table>";
    }

    if (!empty($upcoming)) {
        $body .= "<h3><span class='status-pending'>Upcoming Maintenance (Next 7 Days)</span></h3>
        <table>
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>Plate No.</th>
                    <th>Maintenance Type</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>";
        foreach ($upcoming as $item) {
            $body .= "<tr>
                        <td>" . htmlspecialchars($item['date_mtnce']) . "</td>
                        <td>" . htmlspecialchars($item['plate_no']) . "</td>
                        <td>" . htmlspecialchars($item['type_name']) . "</td>
                        <td><span class='status-pending'>" . htmlspecialchars($item['status']) . "</span></td>
                        <td>" . htmlspecialchars($item['remarks']) . "</td>
                      </tr>";
        }
        $body .= "</tbody></table>";
    }

    $body .= "<p class='footer'>Please log in to the system to manage these schedules. This is an auto-generated email, please do not reply.</p>
    </div>
    </body>
    </html>";
    
    return $body;
}

$logMessages = [];
$recipients = getNotificationRecipients($conn);

if (empty($recipients)) {
    $logMessages[] = "[" . date('Y-m-d H:i:s') . "] No valid notification recipients found (Full Admin or Fleet Manager).";
} else {
    $notifications = updateAndGetNotifications($conn);
    $overdue = $notifications['overdue'];
    $upcoming = $notifications['upcoming'];
    
    if (empty($overdue) && empty($upcoming)) {
        $logMessages[] = "[" . date('Y-m-d H:i:s') . "] No maintenance records require notification today.";
    } else {
        $mail = getMailer();
        
        if (!$mail) {
            $logMessages[] = "[" . date('Y-m-d H:i:s') . "] Failed to initialize PHPMailer.";
        } else {
            try {
                foreach ($recipients as $email) {
                    $mail->addAddress($email);
                }
                
                $mail->isHTML(true);
                $mail->Subject = 'Mansar Logistics - Maintenance Notification (' . date('Y-m-d') . ')';
                $mail->Body    = buildEmailBody($overdue, $upcoming);
                
                $mail->send();
                $logMessages[] = "[" . date('Y-m-d H:i:s') . "] Notification email sent successfully to " . count($recipients) . " recipients.";
                
            } catch (Exception $e) {
                $logMessages[] = "[" . date('Y-m-d H:i:s') . "] Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        }
    }
}

$conn->close();

foreach ($logMessages as $message) {
    echo $message . "\n";
    error_log($message);
}

?>