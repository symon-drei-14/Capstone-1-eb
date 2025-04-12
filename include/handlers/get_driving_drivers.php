<?php

require 'dbhandler.php';

/**
 * Get all drivers with pending status
 * @return array Array of drivers and their destinations
 */
function getDrivingDrivers() {
    global $conn;
    
    $drivers = array();
    

    $query = "SELECT driver, destination FROM assign WHERE status = 'pending'";
    $result = $conn->query($query);
    
  
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $drivers[] = $row;
        }
    }
    
    return $drivers;
}
?>