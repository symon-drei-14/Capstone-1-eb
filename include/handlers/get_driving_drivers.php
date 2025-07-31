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

function getOngoingDeliveriesCount() {
    global $conn;
    
    $count = 0;
    $query = "SELECT COUNT(*) as count FROM assign WHERE status = 'en route'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $count = $row['count'];
    }
    
    return $count;
}

function getAllDeliveriesCount() {
    global $conn;
    
    $count = 0;
    $query = "SELECT COUNT(*) as count FROM assign WHERE is_deleted = '0' and status ='Pending'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $count = $row['count'];
    }
    
    return $count;
}

function getOverdueTrucks() {
    global $conn;
    
    $count = 0;
    $query = "SELECT COUNT(*) as count FROM truck_table WHERE status = 'Overdue'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $count = $row['count'];
    }
    
    return $count;
}

function getRepairTrucks() {
    global $conn;
    
    $count = 0;
    $query = "SELECT COUNT(*) as count FROM truck_table WHERE status = 'In Repair'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $count = $row['count'];
    }
    
    return $count;
}

?>