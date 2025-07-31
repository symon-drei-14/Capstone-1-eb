    <?php
    function getTripStatistics($conn) {
        $stats = [
            'pending' => 0,
            'enroute' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'total' => 0
        ];

        $query = "SELECT status, COUNT(*) as count FROM assign WHERE is_deleted = 0 GROUP BY status";
        $result = $conn->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $status = strtolower(str_replace(' ', '', $row['status']));
                if (array_key_exists($status, $stats)) {
                    $stats[$status] = $row['count'];
                }
            }
        }

        // Get total count
        $totalQuery = "SELECT COUNT(*) as total FROM assign WHERE is_deleted = 0";
        $totalResult = $conn->query($totalQuery);
        if ($totalResult) {
            $totalRow = $totalResult->fetch_assoc();
            $stats['total'] = $totalRow['total'];
        }

        return $stats;
    }
    ?>