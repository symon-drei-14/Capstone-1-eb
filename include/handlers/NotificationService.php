<?php
// NotificationService.php
require_once 'dbhandler.php';

class NotificationService {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Store notification in database
     */
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
            
            // Send push notification
            $this->sendPushNotification($driverId, $title, $body, $data);
            
            return $notificationId;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Send push notification via FCM v1
     */
    private function sendPushNotification($driverId, $title, $body, $data = null) {
        $tokens = $this->getDriverFCMTokens($driverId);

        if (empty($tokens)) {
            return false;
        }

        // Your service account credentials
        $serviceAccount = [
            "type" => "service_account",
            "project_id" => "mansartrucking1",
            "private_key_id" => "f98f9b812b0aa41cff4cfceaca9ba9e6071f6b5f",
            "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC3Gtd26huYLL0a\nBTZcIiMYJd91p1uyNzMX7jzs/7iiwitHQfslU6bccgPejxjB+8Fh26E5KSAG807c\ntnxRfnvjB3N9uNZfP361FkVsOtERc3A3K+Sw5h+qBka510gO2icHY5R6Q1ftIumS\nQFp8tF8ecW++ejNCCH6rU7FCDpH8gMPLKFS/UQ5qp2ZVTmqu/05yUd+YfwH9Axrh\nYN1bfm/XMi3IPGbta5YK7JGJKSGaIqi657PKgkhVm/+/WyXflhIqfJ3wbntrwLGc\n6jJxsj1JL/PwKRcxJDXFMopRqJg7egZXD4eqHNcfKgnzL6FwiAaPPHnox1YyS7kR\nsZeaBa+9AgMBAAECggEAER3uv3ySGM8x3FFZbBJ64mKD+b0r6sSTP6zzQBqFuZ1a\nK16lKi+gPSJhbzhEUboFsW61KyFHj67GFAbxJzMiRK5pIvsY+y113FrZIY18Btwa\nROYTCmBw3FWa3fucjlrhZCTwd998xDvLxvLCIr8/1xo2noFQ8l7V7JE11F2FUyvT\n5D2D/NlUr8FFozrNDmJlKKDRomCfdaOcjxI3lFhk2cWYHNIJtihuXCufDog7SPGw\nsIb9m+/bpzHt89V5mkJe8q5KioptNOevZkrw3SuSngzf2c0NtAEXaoEovqYR/GcQ\nkQ7WetyIC0O73NNiXdnergtTnB5j8py/CUEIvd8PQQKBgQDdsfi7PzWGAMod5KH3\n5+9zGRTSPcI5QE9U6J5GHv+bAlrzdWNbKqkYGgWFl0Jvndd+oijDiF+QsljdlJIo\nJ3dpMvwMRTiGKKC8v2saEQAzRBRss+KaL49PBiJejcjcQ+IHMhUIleUkYkR4AwFC\nOskRrh8JbC6zKxBl1isdalaWXQKBgQDTcDAHYaycfOuetOQtsrbfVpCs3POn0efP\nItOnZ56PNaO7VyqW4MbbmXIkR1dArSG1G8nmzAJEWUxR9X2SEcr/Fb68oaoB6M9B\ncDvK9xBbugo2VL6vRZ4I7EsEA5rYTIq7IhQRUg9WUQPxZSZcprQh/Zd30odLAa/h\nTchfnVEo4QKBgQDWUGUuxsU8POknCs4VNM9DSjzZncBzzhqi75mKGg9pT1aTQqkB\nCfWbihRKd9ZOxpz7G1Ii7GPOIstLsYO1c6m5NgN47TXeY8o3jSjBcyvpY2gHScLG\n4TE96KUzGQfS/4CzChRRT27LxH+CMQ13dBLKl7QDTOS8aeYZPHhDoHgCNQKBgCF9\nB15j7f7rGjaM2AcU4zoEb+2xITZXXKvGDFfbZZWxHTmy2KAFAfoOF7H/SqaHxWr1\n98iCT2mb6yagBz93aft06jzeLhsXUJxAtnezIfglQzDPw1PnZtxq8Ia2O3Q+y0pQ\nX3VO1fcJ5eH571WFYcpwa+kigyMyJTU+KJpcRFqBAoGALrDs+moAGWvcLByMmrRR\nZ4gu9WAmPzn3yeZsTlO2ltOvkk8ltb3RcASrufaDa1XOBgMEaWYD+Ld7hhU/WW2e\nr4pItZy9f+PYXwRb0pl2Ap2f1wle1JWAIFzxx2VMv8LV845pzg52yacHfnBFz9/g\nkdKwfOLKuLXenV5+viqDhoI=\n-----END PRIVATE KEY-----\n",
            "client_email" => "firebase-adminsdk-fbsvc@mansartrucking1.iam.gserviceaccount.com",
            "client_id" => "115468963919081645844",
            "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
            "token_uri" => "https://oauth2.googleapis.com/token"
        ];

        // Get access token
        $accessToken = $this->getAccessToken($serviceAccount);

        if (!$accessToken) {
            error_log("Failed to get access token for FCM");
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$serviceAccount['project_id']}/messages:send";

        foreach ($tokens as $token) {
            $message = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "title" => $title,
                        "body"  => $body
                    ],
                    "data" => $data ?: []
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

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                error_log("Failed to send push notification. HTTP Code: $httpCode, Response: $result");
            } else {
                error_log("Push notification sent successfully to driver: $driverId");
            }
        }

        return true;
    }

    /**
     * Generate OAuth2 access token
     */
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

        // Sign JWT
        openssl_sign($jwt, $signature, openssl_pkey_get_private($serviceAccount['private_key']), 'sha256');
        $jwtSigned = $jwt . '.' . $this->base64UrlEncode($signature);

        // Exchange JWT for access token
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

    /**
     * Base64 URL-safe encoding
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Get FCM tokens for a driver
     */
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
    
    /**
     * Register FCM token for a driver
     */
    public function registerFCMToken($driverId, $token, $deviceType = 'android') {
        // First, deactivate existing tokens for this driver
        $deactivateStmt = $this->conn->prepare("
            UPDATE fcm_tokens SET is_active = 0 WHERE driver_id = ?
        ");
        $deactivateStmt->bind_param("s", $driverId);
        $deactivateStmt->execute();
        $deactivateStmt->close();
        
        // Insert new token
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
    
    /**
     * Get notifications for a driver
     */
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
    
    /**
     * Mark notification as read
     */
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
    
    /**
     * Get unread notification count
     */
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
    
    /**
     * Send trip assignment notification
     */
    public function sendTripAssignedNotification($driverId, $tripData) {
        $title = "New Trip Assigned";
        $body = "You have been assigned a new trip to {$tripData['destination']} on {$tripData['formatted_date']}";
        
        $data = [
            'type' => 'trip_assigned',
            'trip_id' => $tripData['trip_id'],
            'destination' => $tripData['destination'],
            'client' => $tripData['client'],
            'trip_date' => $tripData['trip_date']
        ];
        
        return $this->createNotification(
            $driverId, 
            $title, 
            $body, 
            'trip_assigned', 
            $tripData['trip_id'], 
            $data
        );
    }
    
    /**
     * Send trip updated notification
     */
    public function sendTripUpdatedNotification($driverId, $tripData) {
        $title = "Trip Updated";
        $body = "Your trip to {$tripData['destination']} has been updated";
        
        $data = [
            'type' => 'trip_updated',
            'trip_id' => $tripData['trip_id'],
            'destination' => $tripData['destination'],
            'client' => $tripData['client'],
            'trip_date' => $tripData['trip_date']
        ];
        
        return $this->createNotification(
            $driverId, 
            $title, 
            $body, 
            'trip_updated', 
            $tripData['trip_id'], 
            $data
        );
    }
    
    /**
     * Send trip cancelled notification
     */
    public function sendTripCancelledNotification($driverId, $tripData) {
        $title = "Trip Cancelled";
        $body = "Your trip to {$tripData['destination']} has been cancelled";
        
        $data = [
            'type' => 'trip_cancelled',
            'trip_id' => $tripData['trip_id'],
            'destination' => $tripData['destination'],
            'client' => $tripData['client'],
            'trip_date' => $tripData['trip_date']
        ];
        
        return $this->createNotification(
            $driverId, 
            $title, 
            $body, 
            'trip_cancelled', 
            $tripData['trip_id'], 
            $data
        );
    }
}