<?php
require_once 'dbhandler.php';

class NotificationService {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function createNotification($driverId, $title, $body, $type = 'system', $tripId = null, $data = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (driver_id, trip_id, title, body, type, data) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $dataJson = $data ? json_encode($data) : null;
        $stmt->bind_param("sissss", $driverId, $tripId, $title, $body, $type, $dataJson);
        
        if ($stmt->execute()) {
            $notificationId = $this->conn->insert_id;
            $stmt->close();
            
            $this->sendPushNotification($driverId, $title, $body, $data);
            
            return $notificationId;
        }
        
        $stmt->close();
        return false;
    }
    
    private function sendPushNotification($driverId, $title, $body, $data = null) {
        $tokens = $this->getDriverFCMTokens($driverId);

        error_log("=== PUSH NOTIFICATION DEBUG ===");
        error_log("Driver ID: $driverId");
        error_log("Title: $title");
        error_log("Body: $body");
        error_log("Tokens found: " . count($tokens));
        error_log("Data: " . json_encode($data));

        if (empty($tokens)) {
            error_log("No FCM tokens found for driver ID: $driverId");
            return false;
        }

        $accessToken = $this->getAccessToken($this->getServiceAccountConfig());

        if (!$accessToken) {
            error_log("Failed to get access token for FCM");
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/mansartrucking1/messages:send";
        $successCount = 0;
        $failCount = 0;

        foreach ($tokens as $token) {
            $message = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "title" => $title,
                        "body" => $body
                    ],
                    "data" => array_merge($data ?: [], [
                        'driver_id' => (string)$driverId,
                        'timestamp' => (string)time()
                    ]),
                    "android" => [
                        "priority" => "high",
                        "notification" => [
                            "channel_id" => "mansar_trucking_channel",
                            "sound" => "default",
                            "click_action" => $data['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK'
                        ]
                    ],
                    "apns" => [
                        "headers" => [
                            "apns-priority" => "10"
                        ],
                        "payload" => [
                            "aps" => [
                                "alert" => [
                                    "title" => $title,
                                    "body" => $body
                                ],
                                "sound" => "default",
                                "badge" => 1
                            ]
                        ]
                    ]
                ]
            ];

            $headers = [
                "Authorization: Bearer $accessToken",
                "Content-Type: application/json"
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200 && !$error) {
                $successCount++;
                error_log("Push notification sent successfully to driver: $driverId, token: " . substr($token, 0, 20) . "...");
            } else {
                $failCount++;
                error_log("Failed to send push notification to driver: $driverId. HTTP Code: $httpCode, cURL Error: $error, Response: $result");

                if ($httpCode === 404 || (strpos($result, 'invalid-token') !== false)) {
                    $this->deactivateToken($token);
                }
            }
        }

        error_log("Notification summary for driver $driverId: $successCount successful, $failCount failed");
        
        return $successCount > 0;
    }


    private function getAccessToken($serviceAccount) {
        $jwtHeader = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $jwtClaim = $this->base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $serviceAccount['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600
        ]));

        $jwt = $jwtHeader . '.' . $jwtClaim;

        openssl_sign($jwt, $signature, openssl_pkey_get_private($serviceAccount['private_key']), 'sha256');
        $jwtSigned = $jwt . '.' . $this->base64UrlEncode($signature);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $serviceAccount['token_uri']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwtSigned
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        $tokenData = json_decode($response, true);

        if (!isset($tokenData['access_token'])) {
            error_log("Failed to retrieve access token. Response: $response");
        }

        return $tokenData['access_token'] ?? null;
    }


    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }


    private function getServiceAccountConfig() {
        $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
        
        if (!file_exists($serviceAccountPath)) {
            error_log("Service account JSON file not found at: $serviceAccountPath");
            return null;
        }
        
        $serviceAccountJson = file_get_contents($serviceAccountPath);
        $serviceAccount = json_decode($serviceAccountJson, true);
        
        if (!$serviceAccount) {
            error_log("Failed to parse service account JSON");
            return null;
        }
        
        return $serviceAccount;
    }
    

    private function getDriverFCMTokens($driverId) {
        $stmt = $this->conn->prepare("
            SELECT fcm_token FROM fcm_tokens 
            WHERE driver_id = ? AND is_active = 1
        ");
        $stmt->bind_param("s", $driverId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tokens = [];
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row['fcm_token'];
        }
        
        $stmt->close();
        return $tokens;
    }


    private function deactivateToken($token) {
        $stmt = $this->conn->prepare("UPDATE fcm_tokens SET is_active = 0 WHERE fcm_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
        
        error_log("Deactivated invalid FCM token: " . substr($token, 0, 20) . "...");
    }
    

    public function registerFCMToken($driverId, $token, $deviceType = 'android') {
        $deactivateStmt = $this->conn->prepare("
            UPDATE fcm_tokens SET is_active = 0 WHERE driver_id = ?
        ");
        $deactivateStmt->bind_param("s", $driverId);
        $deactivateStmt->execute();
        $deactivateStmt->close();
        

        $stmt = $this->conn->prepare("
            INSERT INTO fcm_tokens (driver_id, fcm_token, device_type, is_active) 
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                is_active = 1, 
                device_type = VALUES(device_type),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->bind_param("sss", $driverId, $token, $deviceType);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    

    public function getDriverNotifications($driverId, $limit = 50, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                n.*,
                t.container_no,
                t.trip_date,
                dest.name as destination,
                c.name as client
            FROM notifications n
            LEFT JOIN trips t ON n.trip_id = t.trip_id
            LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
            LEFT JOIN clients c ON t.client_id = c.client_id
            WHERE n.driver_id = ?
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("sii", $driverId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        $stmt->close();
        return $notifications;
    }
    

    public function markAsRead($notificationId, $driverId) {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND driver_id = ?
        ");
        $stmt->bind_param("is", $notificationId, $driverId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    

    public function getUnreadCount($driverId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as unread_count 
            FROM notifications 
            WHERE driver_id = ? AND is_read = 0
        ");
        $stmt->bind_param("s", $driverId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['unread_count'];
        $stmt->close();
        
        return $count;
    }


    public function sendTripAssignedNotification($driverId, $tripData) {
        $title = "New Trip Assigned";
        $body = "Trip to {$tripData['destination']} on {$tripData['formatted_date']}";
        
        if (isset($tripData['container_no']) && !empty($tripData['container_no'])) {
            $body .= " - Container: {$tripData['container_no']}";
        }
        
        $data = [
            'type' => 'trip_assigned',
            'trip_id' => (string)$tripData['trip_id'],
            'destination' => $tripData['destination'] ?? '',
            'client' => $tripData['client'] ?? '',
            'trip_date' => $tripData['trip_date'] ?? '',
            'formatted_date' => $tripData['formatted_date'] ?? '',
            'container_no' => $tripData['container_no'] ?? '',
            'plate_no' => $tripData['plate_no'] ?? '',
            'port' => $tripData['port'] ?? '',
            'shipping_line' => $tripData['shipping_line'] ?? '',
            'click_action' => 'TRIP_DETAILS',
            'sound' => 'default'
        ];
        
        $notificationId = $this->createNotification(
            $driverId, 
            $title, 
            $body, 
            'trip_assigned', 
            $tripData['trip_id'], 
            $data
        );
        
        error_log("Trip assigned notification created with ID: $notificationId for driver: $driverId, trip: {$tripData['trip_id']}");
        
        return $notificationId !== false;
    }

    public function sendTripUpdatedNotification($driverId, $tripData) {
        $title = "Trip Updated";
        $body = "Your trip to {$tripData['destination']} has been updated";
        
        if (isset($tripData['formatted_date'])) {
            $body .= " - Scheduled for {$tripData['formatted_date']}";
        }
        
        $data = [
            'type' => 'trip_updated',
            'trip_id' => (string)$tripData['trip_id'],
            'destination' => $tripData['destination'] ?? '',
            'client' => $tripData['client'] ?? '',
            'trip_date' => $tripData['trip_date'] ?? '',
            'formatted_date' => $tripData['formatted_date'] ?? '',
            'container_no' => $tripData['container_no'] ?? '',
            'plate_no' => $tripData['plate_no'] ?? '',
            'port' => $tripData['port'] ?? '',
            'shipping_line' => $tripData['shipping_line'] ?? '',
            'status' => $tripData['status'] ?? '',
            'click_action' => 'TRIP_DETAILS',
            'sound' => 'default'
        ];
        
        $notificationId = $this->createNotification(
            $driverId, 
            $title, 
            $body, 
            'trip_updated', 
            $tripData['trip_id'], 
            $data
        );
        
        error_log("Trip updated notification created with ID: $notificationId for driver: $driverId, trip: {$tripData['trip_id']}");
        
        return $notificationId !== false;
    }

    public function sendTripCancelledNotification($driverId, $tripData) {
        $title = "Trip Cancelled";
        $body = "Your trip to {$tripData['destination']} has been cancelled";
        
        if (isset($tripData['formatted_date'])) {
            $body .= " (was scheduled for {$tripData['formatted_date']})";
        }
        
        $data = [
            'type' => 'trip_cancelled',
            'trip_id' => (string)$tripData['trip_id'],
            'destination' => $tripData['destination'] ?? '',
            'client' => $tripData['client'] ?? '',
            'trip_date' => $tripData['trip_date'] ?? '',
            'formatted_date' => $tripData['formatted_date'] ?? '',
            'container_no' => $tripData['container_no'] ?? '',
            'click_action' => 'TRIP_LIST',
            'sound' => 'default'
        ];
        
        $notificationId = $this->createNotification(
            $driverId, 
            $title, 
            $body, 
            'trip_cancelled', 
            $tripData['trip_id'], 
            $data
        );
        
        error_log("Trip cancelled notification created with ID: $notificationId for driver: $driverId, trip: {$tripData['trip_id']}");
        
        return $notificationId !== false;
    }


    public function sendTripStatusChangeNotification($driverId, $tripData, $oldStatus, $newStatus) {
        $title = "Trip Status Updated";
        $body = "Trip to {$tripData['destination']} status changed from {$oldStatus} to {$newStatus}";
        
        $data = [
            'type' => 'trip_status_change',
            'trip_id' => (string)$tripData['trip_id'],
            'destination' => $tripData['destination'] ?? '',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'click_action' => 'TRIP_DETAILS',
            'sound' => 'default'
        ];
        
        return $this->createNotification(
            $driverId, 
            $title, 
            $body, 
            'trip_status_change', 
            $tripData['trip_id'], 
            $data
        );
    }

    public function sendMaintenanceNotification($driverId, $data) {
        $title = $data['title'] ?? 'Maintenance Alert';
        $body = $data['body'] ?? 'New maintenance schedule added for your truck.';
        
        $payload = [
            'type' => 'maintenance',
            'maintenance_id' => (string)($data['maintenance_id'] ?? ''),
            'truck_id' => (string)($data['truck_id'] ?? ''),
            'click_action' => 'MAINTENANCE_SCREEN',
            'sound' => 'default'
        ];

        $notificationId = $this->createNotification(
            $driverId, 
            $title, 
            $body, 
            'maintenance',
            null,
            $payload
        );
        
        error_log("Maintenance notification created with ID: $notificationId for driver: $driverId");
        
        return $notificationId !== false;
    }

    public function sendTestNotification($driverId, $message = "Test notification from Mansar Trucking") {
        $title = "Test Notification";
        $body = $message;
        
        $data = [
            'type' => 'test',
            'timestamp' => date('Y-m-d H:i:s'),
            'click_action' => 'MAIN_ACTIVITY'
        ];
        
        $result = $this->createNotification($driverId, $title, $body, 'test', null, $data);
        
        error_log("Test notification sent to driver $driverId: " . ($result ? 'success' : 'failed'));
        
        return $result !== false;
    }


    public function sendMaintenanceUpdateNotification($driverId, $data) {
        $status = $data['status'] ?? 'Updated';
        $title = "Maintenance Update";
        
        if ($status === 'In Progress') {
            $body = "Maintenance has started on your truck.";
        } elseif ($status === 'Completed') {
            $body = "Maintenance is complete. Your truck is ready.";
        } else {
            $body = "Maintenance status updated to: $status";
        }
        
        $payload = [
            'type' => 'maintenance_update',
            'maintenance_id' => (string)($data['maintenance_id'] ?? ''),
            'status' => $status,
            'click_action' => 'MAINTENANCE_SCREEN',
            'sound' => 'default'
        ];

        return $this->createNotification($driverId, $title, $body, 'maintenance_update', null, $payload);
    }
}