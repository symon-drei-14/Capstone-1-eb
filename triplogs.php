<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess(); // No role needed—logic is handled internally
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip logs</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/triplogs.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<style>
    

    .event-item{
        padding:20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    
    /* .event-details-container {
        width: 500px;
        height: auto; 
        padding: 30px;
        background-color: #ffffff;
        border-radius: 8px;
    
        display: relative;
        margin: 50px;
        margin-top: 100px;
        line-height: 30px;
        max-height: 600px;
        overflow-y: auto;
    }
    
            .event-details-container h4 {
                margin-top: 0;
            }
    
            .event-details-container p {
                margin: 5px 0;
            }
    
            .event-list {
                list-style-type: none;
                padding: 0;
            }
    
            .event-list li {
                margin: 10px 0;
            } */
    
        
    
            #calendar {
                width: 600px;
                background-color: #ffffff;
                padding: 30px;
                border-radius: 20px;
                box-shadow: rgba(0, 0, 0, 0.15) 0px 15px 25px, rgba(0, 0, 0, 0.05) 0px 5px 10px;
             height:30rem;
            }
    
            #noEventsMessage {
                display: block;
            }

            /* .events-table {
    width: 100%;
    table-layout: auto;
    border-collapse: collapse;
    margin-top: 10px;
    margin-bottom: 20px;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    word-wrap: break-word;
}

.events-table th, .events-table td {
    padding: 8px 10px;
    text-align: center;
    border-radius: 1px;
    font-size: 14px;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
} */
    
    
            .events-table {
        width: 100%;
       
        border-collapse: collapse;
        margin-top: 10px;
        margin-bottom: 20px;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    
    
    }
    
    .events-table th {
        padding: 12px;
        text-align: center;
        border-radius: 1px;
        word-wrap: break-word;
      
        
    }
    .events-table td{
    
        padding: 15px;
        text-align: center;
        border-radius: 1px;
        word-wrap: break-word;
        font-size: 15px;
    }
    
    .events-table th {
        background-color: #ffffff;
        font-weight: bold;
        position: relative;
        box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px;
        border-bottom: 5px double #d3d1d15c;
        z-index: 1;
      
    
    }
    /* .events-table td{
        font-size: 25px;
    } */
    
    .events-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    
    .events-table tr:nth-child(odd) {
        background-color: #ffffff;
    }
    
    .events-table td {
        color: #333;
    }
    
    .events-table tr:hover {
        background-color: #f1f1f1;
        cursor: pointer;
    }
    
    /* .events-table td {
        font-size: 13px;
        color: #555;
    } */
    
    .events-table td a {
        color: #4CAF50;
        text-decoration: none;
    }
    
    .events-table td a:hover {
        text-decoration: underline;
    }
    
    .events-table .description-cell {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    @media screen and (max-width: 768px) {
        .events-table {
            width: 100%;
            font-size: 12px;
        }
    
        .events-table th, .events-table td {
            padding: 8px 10px;
        }
    }
    
            /* Button styling */
            .toggle-btns {
                display: flex;
                gap: 0px;
                margin-bottom: 20px;
            
            }
    
            .toggle-btn {
                padding: 10px 20px;
                border: none;
                background-color: #e4e4e4;
                cursor: pointer;
                border-radius: 5px;
                font-size: 14px;
    
            }
    
            .toggle-btn.active {
                background-color: #1b1963;
                color: #fff;
            }
            .status {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 5px;
        
    }
    
    .status.Completed {
        background-color: #28a745; /* Green */
        color: white;
    }
    
    .status.Pending {
        background-color: #ffc107; /* Yellow */
        color: black;
    }
    
    .status.Cancelled {
        background-color: #dc3545; /* Red */
        color: white;
    }
    
    /* Pagination controls */
    /* .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    
    .pagination button {
        padding: 5px 10px;
        margin: 0 5px;
        cursor: pointer;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    
    .pagination button:hover {
        background-color: #f0f0f0;
    } */

     /* pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
    }
    
    .pagination button {
        background-color: #ffffff00;
        color: rgb(0, 0, 0);
        border: none;
        padding: 6px 12px;
        font-size: 18px;
        cursor: pointer;
        border-radius: 10px;
        margin: 0 5px;
    }
    
    .pagination .prev, .pagination .next {
        font-size: 18px;
    }
    
    .pagination button:hover {
        opacity: 0.7;
    }
    
    .page-numbers {
        display: inline-flex;
        gap: 5px;
        align-items: center;
    }
    
    .page-number {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 1px solid #ccc;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        font-weight: bold;
        background-color: rgb(255, 255, 255);
        transition: background-color 0.3s, color 0.3s;
    }
    
    .page-number:hover {
        background-color: rgba(183, 181, 181, 0.95);
        color: #fff;
    }
    
    .page-number.active {
        background-color: rgba(255, 255, 255, 0.82);
        color: black;
        border-color: rgb(26, 97, 12);
        border-width: 2px;
    }

    /* pagination */
    
    .fc-day-selected {
        background-color: #d5d5d8 !important;
        color: white !important;
    }
    
    .fc-day:hover {
        background-color: #d5d5d8 ;
        color: white ;
    }
    
    .edit-btn{
        background-color: #28a745   ;
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        border: none;
        margin-bottom: 2px;
        width: 80px;
    }

    .edit-btn2{
        background-color: #28a7a7   ;
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        border: none;
        margin-bottom: 2px;
        width: 80px;
        font-size: 10px;
    }
    
    .delete-btn{
        background-color: #cc4141   ;
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        border: none;
    }
    
    .modal {
        display: none; 
        position: fixed; 
        z-index: 11000; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%;
      
        background-color: rgba(0, 0, 0, 0.5); /* Black with opacity */
    
    }
    
    .modal-content {
        background-color: #fff;
        margin: 1% auto;
        padding: 20px;
        height: 90vh;
        border: 1px solid #888;
        width: 50%;
        max-width: 400px;
        border-radius: 20px;
        box-shadow: rgba(0, 0, 0, 0.25) 0px 54px 55px, rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.12) 0px 4px 6px, rgba(0, 0, 0, 0.17) 0px 12px 13px, rgba(0, 0, 0, 0.09) 0px -3px 5px;
    overflow-y: auto;
    }
    
    
    .close:hover,
    .close:focus {
        color: rgb(255, 255, 255);
        text-decoration: none;
        cursor: pointer;
        background-color: #730707;
    
    }
    
    .modal button {
        background-color:#f44336;
        color: white;
        padding: 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    
    .add-schedule-btn {
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    
    .add-schedule-btn:hover {
        background-color: #45a049;
    }
    
    label {
        font-size: 16px;
        color: #333;
        font-weight: bold;
        margin-bottom: 5px; /* Add space between label and input */
        text-align: left;
        width: 100%; /* Ensure label takes up full width */
    }
    
    input, select {
        padding: 10px;
        font-size: 14px;
        border-radius: 5px;
        border: 1px solid #ccc;
        outline: none;
        transition: border-color 0.3s;
        width: 90%; /* Make inputs fill the container */
        max-width: 400px; /* Limit input width */
        margin-bottom: 10px; /* Add spacing between inputs */
    }
    
    input:focus, select:focus {
        border-color: #4CAF50;
    }
    
    input[type="datetime-local"] {
        padding: 8px;
    }
    
    #editForm button[type="submit"],
    #addScheduleForm button[type="submit"] {
        margin-top: 10px;
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    
    button.close {
        margin-top: 10px;
        background-color: #f44336;
        color: white;
        border-radius: 5px;
        padding: 10px 20px;
        cursor: pointer;
    }
    
    .cancel-btn:hover {
        background-color:rgb(104, 20, 14);
    
    }
    
    
  .event-details-container {
        width: 500px;
        height: auto; 
        padding: 30px;
        background-color: #ffffff;
        border-radius: 8px;
        display: relative;
        margin: 50px;
        margin-top: 100px;
        line-height: 30px;
        max-height: 600px;
        overflow-y: auto;
    }
    
    .event-details-container h4 {
        margin-top: 0;
    }
    
    .event-details-container p {
        margin: 5px 0;
    }
    
    .event-list {
        list-style-type: none;
        padding: 0;
    }
    
    .event-list li {
        margin: 10px 0;
    }

    .event-thumbnail {
        cursor: pointer;
        padding: 10px;
        border: 1px solid #ccc;
         border-radius:  5px 5px 0px 0px;
        margin-bottom: 10px;
    }

    .event-details {
        display: none;
        padding: 10px;
        border-top: 1px solid white;
        border: 1px solid #ccc;
        border-radius: 0px 0px 5px 5px;
        margin-top: -11px;
        margin-bottom:5px;
    }
         .toggle-sidebar-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    margin-left: 1rem;
    color: #333;
}

@media (max-width: 768px) {
    .sidebar {
        display: none;
        position: absolute;
        z-index: 999;
        background-color: #fff;
        width: 250px;
        height: 100%;
        box-shadow: 2px 0 5px rgba(0,0,0,0.2);
    }

    .sidebar.show {
        display: block;
    }
}

.toggle-sidebar-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #333;
    z-index: 1300;
}


.sidebar {
    position: fixed;
    top: 1.7rem;
    left: 0;
    width: 300px; 
    height: 100%;
    background-color: #edf1ed;
    color: #161616 !important;
    padding: 20px;
    box-sizing: border-box;
    overflow-x: hidden;
    overflow-y: auto;
    z-index: 1100;
    border-right: 2px solid #16161627;
    transform: translateX(-100%); 
    transition: transform 0.3s ease;
}


.sidebar.expanded {
    transform: translateX(0);
}

.sidebar.expanded .sidebar-item a,
.sidebar.expanded .sidebar-item span {
    visibility: visible;
    opacity: 1;
}
   .main-container{
        background-color: rgb(255, 255, 255);
        margin-left:10px;
        margin-top: 80px;
        padding:10px;
        border-radius:20px;
        box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
          width: calc(100% - 60px); 
    min-height: calc(100vh - 100px); 

        height: auto;

    }
        .calendar-container {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                padding: 20px;
                height:auto;
                    overflow-y: auto; /* Enable scrolling if content overflows */
    
            }
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: rgb(241, 241, 244);
    overflow-y: auto; /* or just remove this; auto is default */
    height: auto; /* allow content to grow */
}


</style>
<body>
    <?php
    require 'include/handlers/dbhandler.php';
    session_start();
    // Fetch trip assignments
  
$sql = "SELECT a.*, t.plate_no as truck_plate_no, t.capacity as truck_capacity
        FROM assign a
        LEFT JOIN drivers_table d ON a.driver = d.name
        LEFT JOIN truck_table t ON d.assigned_truck_id = t.truck_id";
$result = $conn->query($sql);
$eventsData = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $eventsData[] = [
            'id' => $row['trip_id'],
            'plateNo' => $row['plate_no'],
            'date' => $row['date'],
            'driver' => $row['driver'],
            'helper' => $row['helper'],
             'dispatcher' => $row['dispatcher'],
            'containerNo' => $row['container_no'],
            'client' => $row['client'],
            'destination' => $row['destination'],
            'shippingLine' => $row['shippine_line'],
            'consignee' => $row['consignee'],
            'size' => $row['size'],
            'cashAdvance' => $row['cash_adv'],
            'status' => $row['status'],
            'modifiedby' => $row['last_modified_by'],
            'modifiedat' => $row['last_modified_at'],
            'truck_plate_no' => $row['truck_plate_no'],
            'truck_capacity' => $row['truck_capacity']
        ];
    }
}

// Fetch drivers with their assigned truck capacity
$driverQuery = "SELECT d.driver_id, d.name, t.plate_no as truck_plate_no, t.capacity 
                FROM drivers_table d
                LEFT JOIN truck_table t ON d.assigned_truck_id = t.truck_id";
$driverResult = $conn->query($driverQuery);
$driversData = [];

if ($driverResult->num_rows > 0) {
    while($driverRow = $driverResult->fetch_assoc()) {
        $driversData[] = [
            'id' => $driverRow['driver_id'],
            'name' => $driverRow['name'],
            'capacity' => $driverRow['capacity'],
            'truck_plate_no' => $driverRow['truck_plate_no'] // Add this line
        ];
    }
}
    
    $eventsDataJson = json_encode($eventsData);
    $driversDataJson = json_encode($driversData);
    ?>

<header class="header">
    <button id="toggleSidebarBtn" class="toggle-sidebar-btn">
  <i class="fa fa-bars"></i>
</button>
        <div class="logo-container">
            <img src="include/img/logo.png" alt="Company Logo" class="logo">
            <img src="include/img/mansar.png" alt="Company Name" class="company">
        </div>

        <div class="profile">
            <i class="icon">✉</i>
            <img src="include/img/profile.png" alt="Admin Profile" class="profile-icon">
            <div class="profile-name">
        <?php 
        echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User ';
        ?>
    </div>
        </div>
    </header>

<div class="sidebar">
    <div class="sidebar-item">
        <i class="icon2">🏠</i>
        <a href="dashboard.php">Home</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚗</i>
        <a href="drivermanagement.php">Driver Management</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚛</i>
        <a href="fleetmanagement.php">Fleet Management</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">📋</i>
        <a href="triplogs.php">Trip Management</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">📍</i>
        <a href="tracking.php">Tracking</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🔧</i>
        <a href="maintenance.php">Maintenance Scheduling</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">📈</i>
        <a href="fleetperformance.php">Fleet Performance Analytics</a>
    </div>
    <hr>
    <div class="sidebar-item">
        <i class="icon2">⚙️</i>
        <a href="adminmanagement.php">Admin Management</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚪</i>
        <a href="include/handlers/logout.php">Logout</a>
    </div>
</div>
<div class="main-container">
    <div class="calendar-container">
        <section class="calendar-section">
            <h3>Trip Management</h3>
            <div class="toggle-btns">
                <button id="calendarViewBtn" class="toggle-btn active"> <i class="fa fa-calendar"> Calendar</i></button>
                <button id="tableViewBtn" class="toggle-btn">  <i class="fa fa-tasks"> Table</i></button>
            
            </div>
            <button id="addScheduleBtnTable" class="toggle-btn">Add Schedule</button>
    
            <div id="calendar"></div>
        </section>
        
        <section class="event-details-container" id="eventDetails">
            <h4>Event Details</h4>
            <p id="noEventsMessage" style="display: none;">No scheduled trips for this date</p>
            <ul id="eventList" class="event-list"></ul>
        </section>
    </div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Edit Event</h3>
        <form id="editForm">
            <input type="hidden" id="editEventId" name="eventId">
            
  <label for="editEventSize">Shipment Size:</label><br>
            <select id="editEventSize" name="eventSize" required>
                <option value="">Select Size</option>
                <option value="20ft">20ft</option>
                <option value="40ft">40ft</option>
                <option value="40ft HC">40ft HC</option>
                <option value="45ft">45ft</option>
            </select><br><br>

            <label for="editEventPlateNo">Plate No.:</label><br>
            <input type="text" id="editEventPlateNo" name="eventPlateNo" required><br><br>
    
            <label for="editEventDate">Date & Time:</label><br>
            <input type="datetime-local" id="editEventDate" name="editEventDate" required><br><br>

            <label for="editEventDriver">Driver:</label><br>
            <select id="editEventDriver" name="eventDriver" required>
                <option value="">Select Driver</option>
                <!-- Drivers will be populated by JavaScript -->
            </select><br><br>
    
            <label for="editEventHelper">Helper:</label><br>
            <input type="text" id="editEventHelper" name="eventHelper" required><br><br>

            <label for="editEventDispatcher">Dispatcher:</label><br>
<input type="text" id="editEventDispatcher" name="eventDispatcher" required><br><br>


    
            <label for="editEventContainerNo">Container No.:</label><br>
            <select id="editEventContainerNo" name="eventContainerNo" required>
                <option value="">Select Container No.</option>
                <option value="TCLU1234567">TCLU1234567</option>
                <option value="TEMU9876543">TEMU9876543</option>
                <option value="SEGU5678912">SEGU5678912</option>
                <option value="CMAU3456789">CMAU3456789</option>
                <option value="APHU8765432">APHU8765432</option>
            </select><br><br>
    
            <label for="editEventClient">Client:</label><br>
            <select id="editEventClient" name="eventClient" required>
                <option value="">Select Client</option>
                <option value="Maersk">Maersk</option>
                <option value="MSC">MSC</option>
                <option value="COSCO">COSCO</option>
                <option value="CMA CGM">CMA CGM</option>
                <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                <option value="Evergreen">Evergreen</option>
            </select><br><br>
    
            <label for="editEventDestination">Destination:</label><br>
            <select id="editEventDestination" name="eventDestination" required>
                <option value="">Select Destination</option>
                <option value="Manila Port">Manila Port</option>
                <option value="Batangas Port">Batangas Port</option>
                <option value="Subic Port">Subic Port</option>
                <option value="Cebu Port">Cebu Port</option>
                <option value="Davao Port">Davao Port</option>
            </select><br><br>
    
            <label for="editEventShippingLine">Shipping Line:</label><br>
            <select id="editEventShippingLine" name="eventShippingLine" required>
                <option value="">Select Shipping Line</option>
                <option value="Maersk Line">Maersk Line</option>
                <option value="Mediterranean Shipping Co.">Mediterranean Shipping Co.</option>
                <option value="COSCO Shipping">COSCO Shipping</option>
                <option value="CMA CGM">CMA CGM</option>
                <option value="Hapag-Lloyd">Hapag-Lloyd</option>
            </select><br><br>
    
            <label for="editEventConsignee">Consignee:</label><br>
            <input type="text" id="editEventConsignee" name="eventConsignee" required><br><br>
    
          
    
            <label for="editEventCashAdvance">Cash Advance:</label><br>
            <input type="text" id="editEventCashAdvance" name="eventCashAdvance" required><br><br>
    
            <label for="editEventStatus">Status:</label><br>
            <select id="editEventStatus" name="eventStatus" required>
                <option value="Completed">Completed</option>
                <option value="Pending">Pending</option>
                <option value="Cancelled">Cancelled</option>
                  <option value="En Route">En Route</option>
            </select><br><br>
    
            <button type="submit">Save Changes</button>
            <button type="button" class="close-btn cancel-btn">Cancel</button>
        </form>
    </div>
</div>

    <div id="addScheduleModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add Schedule</h2>
        <form id="addScheduleForm">

  <label for="editEventSize">Shipment Size:</label><br>
            <select id="editEventSize" name="eventSize" required>
                <option value="">Select Size</option>
                <option value="20ft">20ft</option>
                <option value="40ft">40ft</option>
                <option value="40ft HC">40ft HC</option>
                <option value="45ft">45ft</option>
            </select><br><br>

            <label for="addEventPlateNo">Plate No.:</label><br>
            <input type="text" id="addEventPlateNo" name="eventPlateNo" required><br><br>
    
            <label for="addEventDate">Date & Time:</label><br>
            <input type="datetime-local" id="addEventDate" name="eventDate" required><br><br>

            <label for="addEventDriver">Driver:</label><br>
            <select id="addEventDriver" name="eventDriver" required>
                <option value="">Select Driver</option>
                <!-- Drivers will be populated by JavaScript -->
            </select><br><br>
    
            <label for="addEventHelper">Helper:</label><br>
            <input type="text" id="addEventHelper" name="eventHelper" required><br><br>

            <label for="addEventDispatcher">Dispatcher:</label><br>
<input type="text" id="addEventDispatcher" name="eventDispatcher" required><br><br>
    
            <label for="addEventContainerNo">Container No.:</label><br>
            <select id="addEventContainerNo" name="eventContainerNo" required>
                <option value="">Select Container No.</option>
                <option value="TCLU1234567">TCLU1234567</option>
                <option value="TEMU9876543">TEMU9876543</option>
                <option value="SEGU5678912">SEGU5678912</option>
                <option value="CMAU3456789">CMAU3456789</option>
                <option value="APHU8765432">APHU8765432</option>
            </select><br><br>
    
            <label for="addEventClient">Client:</label><br>
            <select id="addEventClient" name="eventClient" required>
                <option value="">Select Client</option>
                <option value="Maersk">Maersk</option>
                <option value="MSC">MSC</option>
                <option value="COSCO">COSCO</option>
                <option value="CMA CGM">CMA CGM</option>
                <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                <option value="Evergreen">Evergreen</option>
            </select><br><br>
    
            <label for="addEventDestination">Destination:</label><br>
            <select id="addEventDestination" name="eventDestination" required>
                <option value="">Select Destination</option>
                <option value="Manila Port">Manila Port</option>
                <option value="Batangas Port">Batangas Port</option>
                <option value="Subic Port">Subic Port</option>
                <option value="Cebu Port">Cebu Port</option>
                <option value="Davao Port">Davao Port</option>
            </select><br><br>
    
            <label for="addEventShippingLine">Shipping Line:</label><br>
            <select id="addEventShippingLine" name="eventShippingLine" required>
                <option value="">Select Shipping Line</option>
                <option value="Maersk Line">Maersk Line</option>
                <option value="Mediterranean Shipping Co.">Mediterranean Shipping Co.</option>
                <option value="COSCO Shipping">COSCO Shipping</option>
                <option value="CMA CGM">CMA CGM</option>
                <option value="Hapag-Lloyd">Hapag-Lloyd</option>
            </select><br><br>
    
            <label for="addEventConsignee">Consignee:</label><br>
            <input type="text" id="addEventConsignee" name="eventConsignee" required><br><br>
    
      
    
            <label for="addEventCashAdvance">Cash Advance:</label><br>
            <input type="text" id="addEventCashAdvance" name="eventCashAdvance" required><br><br>
    
            <label for="addEventStatus">Status:</label><br>
            <select id="addEventStatus" name="eventStatus" required>
                <option value="Completed">Completed</option>
                <option value="Pending">Pending</option>
                <option value="Cancelled">Cancelled</option>
                  <option value="En Route">En Route</option>
            </select><br><br>
    
            <!-- Save and Cancel buttons -->
            <button type="submit">Save Schedule</button>
            <button type="button" class="close-btn cancel-btn">Cancel</button>
        </form>
    </div>
</div>
    
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete this trip?</p>
            <input type="hidden" id="deleteEventId">
            <button id="confirmDeleteBtn">Yes, Delete</button>
            <button type="button" class="close-btn cancel-btn">Cancel</button>
        </div>
    </div>

    <div id="tableView" style="display: none;">
        <h3>Event Table</h3>

        <table class="events-table" id="eventsTable"> 
            <thead>
                <tr>
                    <th>Plate No.</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Driver</th>
                    <th>Helper</th>
                      <th>Dispatcher</th>
                    <th>Container No.</th>
                    <th>Client</th>
                    <th>Destination</th>
                    <th>Shipping Line</th>
                    <th>Consignee</th>
                    <th>Size</th>
                    <th>Cash Advance</th>
                    <th>Status</th>
                    <th>Last Modified</th>
                        
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="eventTableBody"></tbody>
        </table>
        <div class="pagination-container">
            <div class="pagination">
                <button class="prev" id="prevPageBtn">◄</button> 
                <div id="page-numbers" class="page-numbers"></div>
                <button class="next" id="nextPageBtn">►</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Set minimum date to current date/time
        let now = new Date();
        let formattedNow = now.toISOString().slice(0,16); 
        $('#editEventDate').attr('min', formattedNow);
        $('#addEventDate').attr('min', formattedNow); 
        
        // Get events data
        var eventsData = <?php echo $eventsDataJson; ?>;
        var driversData = <?php echo $driversDataJson; ?>;
        
        // Populate driver dropdowns
   function populateDriverDropdowns(selectedSize = '') {
    var driverOptions = '<option value="">Select Driver</option>';
    
    driversData.forEach(function(driver) {
        // Filter drivers based on selected size
        if (!selectedSize || !driver.capacity || 
            (selectedSize.includes('20') && driver.capacity === '20') ||
            (selectedSize.includes('40') && driver.capacity === '40')) {
            // Include truck_plate_no as a data attribute
            driverOptions += `<option value="${driver.name}" data-plate-no="${driver.truck_plate_no || ''}">${driver.name}</option>`;
        }
    });
    
    $('#editEventDriver').html(driverOptions);
    $('#addEventDriver').html(driverOptions);
}

 $(document).on('change', '#addEventDriver, #editEventDriver', function() {
        var selectedOption = $(this).find('option:selected');
        var plateNo = selectedOption.data('plate-no');
        
        // Determine which form we're in (add or edit)
        var isAddForm = $(this).attr('id') === 'addEventDriver';
        var plateNoField = isAddForm ? '#addEventPlateNo' : '#editEventPlateNo';
        
        $(plateNoField).val(plateNo || '');
    });

// Add event listener for size dropdown changes
$('#addEventSize, #editEventSize').on('change', function() {
    var selectedSize = $(this).val();
    var isAddForm = $(this).attr('id') === 'addEventSize';
    populateDriverDropdowns(selectedSize, isAddForm);
});

$(document).on('change', '#addEventDriver, #editEventDriver', function() {
    var selectedDriverName = $(this).val();
    var driver = driversData.find(function(d) { 
        return d.name === selectedDriverName; 
    });
    
    if (driver && driver.truck_plate_no) {
        // Determine which form we're in (add or edit)
        var isAddForm = $(this).attr('id') === 'addEventDriver';
        var plateNoField = isAddForm ? '#addEventPlateNo' : '#editEventPlateNo';
        
        $(plateNoField).val(driver.truck_plate_no);
    } else {
        // Clear the plate number if driver has no assigned truck
        var isAddForm = $(this).attr('id') === 'addEventDriver';
        var plateNoField = isAddForm ? '#addEventPlateNo' : '#editEventPlateNo';
        $(plateNoField).val('');
    }
});
        
        // Format events for calendar
       var calendarEvents = eventsData.map(function(event) {
    return {
        id: event.id,
        title: event.client + ' - ' + event.destination,
        start: event.date,
        plateNo: event.plateNo,
        driver: event.driver,
        helper: event.helper,
            dispatcher: event.dispatcher,
        containerNo: event.containerNo,
        client: event.client,
        destination: event.destination,
        shippingLine: event.shippingLine,
        consignee: event.consignee,
        size: event.size,
        cashAdvance: event.cashAdvance,
        status: event.status,
        modifiedby: event.modifiedby,
        modifiedat: event.modifiedat,
        truck_plate_no: event.truck_plate_no,
        truck_capacity: event.truck_capacity
    };
});

function resetAddScheduleForm() {
    $('#addEventPlateNo').val('');
    $('#addEventDate').val('');
    $('#addEventDriver').val('').trigger('change');
    $('#addEventHelper').val('');
    $('#addEventContainerNo').val('');
    $('#addEventClient').val('');
    $('#addEventDestination').val('');
    $('#addEventShippingLine').val('');
    $('#addEventConsignee').val('');
    $('#addEventSize').val('');
    $('#addEventCashAdvance').val('');
    $('#addEventStatus').val('Pending'); // Set default status
}
        // Close modal handlers
      $('.close, .close-btn.cancel-btn').on('click', function() {
    $('.modal').hide();
    if ($(this).closest('#addScheduleModal').length) {
        resetAddScheduleForm();
    }
});

// Also reset when clicking outside the modal
$(window).on('click', function(event) {
    if ($(event.target).hasClass('modal')) {
        $('.modal').hide();
        if ($(event.target).is('#addScheduleModal')) {
            resetAddScheduleForm();
        }
    }
});
        
       $('#addScheduleBtnTable').on('click', function() {
    resetAddScheduleForm(); // Clear the form first
    populateDriverDropdowns(); // Repopulate drivers
    $('#addScheduleModal').show();
});

        // Table pagination variables
        var currentPage = 1;
        var rowsPerPage = 5;
        var totalPages = 0;
        
        function formatDateTime(datetimeString) {
    if (!datetimeString) return 'N/A';
    const date = new Date(datetimeString);
    return date.toLocaleString(); 
}
        // Render table function   
        function renderTable() {
            $('#eventTableBody').empty();
            var startIndex = (currentPage - 1) * rowsPerPage;
            var endIndex = startIndex + rowsPerPage;
            var pageData = eventsData.slice(startIndex, Math.min(endIndex, eventsData.length));
            
            pageData.forEach(function(event) {
                var dateObj = new Date(event.date);
                var formattedDate = dateObj.toLocaleDateString();
                var formattedTime = moment(dateObj).format('h:mm A');
          
                
               var row = `<tr>
    <td>${event.plateNo}</td>
    <td>${formattedDate}</td>
    <td>${formattedTime}</td>
    <td>${event.driver}</td>
    <td>${event.helper}</td>
        <td>${event.dispatcher || 'N/A'}</td>
    <td>${event.containerNo}</td>
    <td>${event.client}</td>
    <td>${event.destination}</td>
    <td>${event.shippingLine}</td>
    <td>${event.consignee}</td>
    <td>${event.size}</td>
    <td>${event.cashAdvance}</td>
    <td><span class="status ${event.status.toLowerCase()}">${event.status}</span></td>
    <td>${event.modifiedby}<br>${formatDateTime(event.modifiedat)}</td>
    <td>
        <button class="edit-btn" data-id="${event.id}">Edit</button>
        <button class="delete-btn" data-id="${event.id}">Delete</button>
    </td>
</tr>`;
                $('#eventTableBody').append(row);
            });
            
            updatePagination();
        }

        // Update pagination function
        function updatePagination() {
            totalPages = Math.ceil(eventsData.length / rowsPerPage);
            
            $('#page-numbers').empty();
            
            for (var i = 1; i <= totalPages; i++) {
                var pageNumClass = i === currentPage ? 'page-number active' : 'page-number';
                $('#page-numbers').append(`<div class="${pageNumClass}" data-page="${i}">${i}</div>`);
            }
            
            $('#prevPageBtn').prop('disabled', currentPage === 1);
            $('#nextPageBtn').prop('disabled', currentPage === totalPages || totalPages === 0);
        }

        // Event handler for page number clicks
        $(document).on('click', '.page-number', function() {
            var page = $(this).data('page');
            goToPage(page);
        });

        function goToPage(page) {
            currentPage = page;
            renderTable();
        }

        function changePage(step) {
            var newPage = currentPage + step;
            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                renderTable();
            }
        }

        $('#prevPageBtn').on('click', function() {
            changePage(-1);
        });

        $('#nextPageBtn').on('click', function() {
            changePage(1);
        });

        // Initialize calendar
        $('#calendar').fullCalendar({
            header: { 
                left: 'prev,next today', 
                center: 'title', 
                right: 'month,agendaWeek,agendaDay' 
            },
            events: calendarEvents,
            timeFormat: 'h:mm A', 
            displayEventTime: true, 
            displayEventEnd: false, 
            eventRender: function(event, element) {
                element.find('.fc-title').css({
                    'white-space': 'normal',
                    'overflow': 'visible'
                });
                
                element.find('.fc-title').html(event.client + ' - ' + event.destination);
                
                var statusClass = event.status.toLowerCase();
                element.addClass(statusClass);
            },
            dayClick: function(date, jsEvent, view) {
                var clickedDay = $(this);
                
                $('.fc-day').removeClass('fc-day-selected');
                clickedDay.addClass('fc-day-selected');
                
                var eventsOnDay = $('#calendar').fullCalendar('clientEvents', function(event) {
                    return moment(event.start).isSame(date, 'day');
                });
                
                var formattedDate = moment(date).format('MMMM D, YYYY');
                $('#eventDetails h4').text('Event Details - ' + formattedDate);
                
                $('#eventList').empty();
                $('#noEventsMessage').hide();
                
                if (eventsOnDay.length > 0) {
                    eventsOnDay.forEach(function(event) {
                        var eventThumbnail = `
                            <div class="event-thumbnail">
                                <strong>Date:</strong> ${moment(event.start).format('MMMM D, YYYY')}<br>
                                <strong>Plate No:</strong> ${event.plateNo}<br>
                                <strong>Destination:</strong> ${event.destination}
                            </div>
                            <div class="event-details">
                                <p><strong>Driver:</strong> ${event.driver}</p>
                                <p><strong>Helper:</strong> ${event.helper}</p>
                                  <p><strong>Dispatcher:</strong> ${event.dispatcher || 'N/A'}</p>
                                <p><strong>Client:</strong> ${event.client}</p>
                                <p><strong>Container No.:</strong> ${event.containerNo}</p>
                                <p><strong>Status:</strong> <span class="status ${event.status.toLowerCase()}">${event.status}</span></p>
                                <p><strong>Cash Advance:</strong> ${event.cashAdvance}</p>
                                 <p><strong>Last modified by: </strong>${event.modifiedby}<br><strong>Last Modified at: </strong>${formatDateTime(event.modifiedat)}</p>
                                <div class="event-actions">
                                    <button class="edit-btn" data-id="${event.id}">Edit</button>
                                    <button class="delete-btn" data-id="${event.id}">Delete</button>
                                </div>
                            </div>
                        `;
                        $('#eventList').append(eventThumbnail);
                    });
                } else {
                    $('#noEventsMessage').show();
                }

                // Toggle event details on thumbnail click
                $('.event-thumbnail').on('click', function() {
                    $(this).next('.event-details').toggle();
                });
            }
        });
        
        // View toggle buttons
        $('#calendarViewBtn').on('click', function() {
            $(this).addClass('active');
            $('#tableViewBtn').removeClass('active');
            $('#calendar').show();
            $('#tableView').hide();
            $('#eventDetails').show();
            
            $('#calendar').fullCalendar('render');
        });
        
        $('#tableViewBtn').on('click', function() {
            $(this).addClass('active');
            $('#calendarViewBtn').removeClass('active');
            $('#calendar').hide();
            $('#tableView').show();
            $('#eventDetails').hide();
            
            currentPage = 1;
            renderTable();
        });
        
        // Edit button click handler
 $(document).on('click', '.edit-btn', function() {
    var eventId = $(this).data('id');
    var event = eventsData.find(function(e) { return e.id == eventId; });
    
    if (event) {
        $('#editEventId').val(event.id);
        $('#editEventPlateNo').val(event.truck_plate_no || event.plateNo);
        $('#editEventDate').val(event.date);
        $('#editEventDriver').val(event.driver);
        $('#editEventHelper').val(event.helper);
        $('#editEventDispatcher').val(event.dispatcher || '');
        $('#editEventContainerNo').val(event.containerNo);
        $('#editEventClient').val(event.client);
        $('#editEventDestination').val(event.destination);
        $('#editEventShippingLine').val(event.shippingLine);
        $('#editEventConsignee').val(event.consignee);
        $('#editEventSize').val(event.size);
        $('#editEventCashAdvance').val(event.cashAdvance);
        $('#editEventStatus').val(event.status);
        
        // Populate drivers based on selected size
        populateDriverDropdowns(event.size);
        
        $('#editModal').show();
    }
});
        
        // Delete button click handler
        $(document).on('click', '.delete-btn', function() {
            var eventId = $(this).data('id');
            $('#deleteEventId').val(eventId);
            $('#deleteConfirmModal').show();
        });
        
        // Add schedule form submit handler
       $('#addScheduleForm').on('submit', function(e) {
    e.preventDefault();
    
    // Get selected driver to find their assigned truck
    var selectedDriver = $('#addEventDriver').val();
    var driver = driversData.find(d => d.name === selectedDriver);
    var truckPlateNo = driver && driver.truck_plate_no ? driver.truck_plate_no : $('#addEventPlateNo').val();
    
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'add',
            plateNo: truckPlateNo,
            date: $('#addEventDate').val(),
            driver: selectedDriver,
            helper: $('#addEventHelper').val(),
            dispatcher: $('#addEventDispatcher').val(),
            containerNo: $('#addEventContainerNo').val(),
            client: $('#addEventClient').val(),
            destination: $('#addEventDestination').val(),
            shippingLine: $('#addEventShippingLine').val(),
            consignee: $('#addEventConsignee').val(),
            size: $('#addEventSize').val(),
            cashAdvance: $('#addEventCashAdvance').val(),
            status: $('#addEventStatus').val()
        }),
        success: function(response) {
            if (response.success) {
                alert('Trip added successfully!');
                $('#addScheduleModal').hide();
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Server error occurred');
        }
    });
});
        
        // Edit form submit handler
       $('#editForm').on('submit', function(e) {
    e.preventDefault();
    
    // Get selected driver to find their assigned truck
    var selectedDriver = $('#editEventDriver').val();
    var driver = driversData.find(d => d.name === selectedDriver);
    var truckPlateNo = driver && driver.truck_plate_no ? driver.truck_plate_no : $('#editEventPlateNo').val();
    
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'edit',
            id: $('#editEventId').val(),
            plateNo: truckPlateNo,
            date: $('#editEventDate').val(),
            driver: selectedDriver,
            helper: $('#editEventHelper').val(),
            dispatcher: $('#editEventDispatcher').val(),
            containerNo: $('#editEventContainerNo').val(),
            client: $('#editEventClient').val(),
            destination: $('#editEventDestination').val(),
            shippingLine: $('#editEventShippingLine').val(),
            consignee: $('#editEventConsignee').val(),
            size: $('#editEventSize').val(),
            cashAdvance: $('#editEventCashAdvance').val(),
            status: $('#editEventStatus').val()
        }),
        success: function(response) {
            if (response.success) {
                alert('Trip updated successfully!');
                $('#editModal').hide();
                location.reload(); 
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Server error occurred');
        }
    });
});
            
        $('#confirmDeleteBtn').on('click', function() {
            var eventId = $('#deleteEventId').val();
            
            $.ajax({
                url: 'include/handlers/trip_operations.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'delete',
                    id: eventId
                }),
                success: function(response) {
                    if (response.success) {
                        alert('Trip deleted successfully!');
                        $('#deleteConfirmModal').hide();
                        location.reload(); 
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Server error occurred');
                }
            });
        });
        
        // Initial render
        renderTable();
    });
</script>
 <script>
    document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('expanded');
    });
</script>
</body>
</html>

