<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess(); // No role needed—logic is handled internally


require_once 'include/handlers/get_driving_drivers.php';
$ongoingCount = getOngoingDeliveriesCount();

$drivingDrivers = getDrivingDrivers();

$alldeliveries = getAllDeliveriesCount();
$alloverduetrucks = getOverdueTrucks();
$allrepairtrucks = getRepairTrucks();

require_once 'include/handlers/dbhandler.php';
$maintenanceQuery = "SELECT licence_plate, remarks, date_mtnce, status 
                    FROM maintenance 
                    WHERE is_deleted = 0 
                    AND status != 'completed'
                    ORDER BY date_mtnce ASC 
                    LIMIT 5";
$maintenanceResult = $conn->query($maintenanceQuery);
$maintenanceRecords = [];
if ($maintenanceResult->num_rows > 0) {
    while($row = $maintenanceResult->fetch_assoc()) {
        $maintenanceRecords[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="include/css/sidenav.css">
    <link rel="stylesheet" href="include/css/loading.css">
    <link rel="stylesheet" href="include/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
 
   
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.css" rel="stylesheet">


<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.js"></script>
<style>
.grid-item.card.statistic {
    display: flex;
    align-items: flex-start; /* Align items to the top */
    padding: 20px;
    border-radius: 10px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: rgba(0, 0, 0, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
}

.grid-item.card.statistic .content,
.grid-item.card.statistic .content2,
.grid-item.card.statistic .content3,
.grid-item.card.statistic .content4 {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
    text-align:center;
}

.grid-item.card.statistic .icon-container,
.grid-item.card.statistic .icon-container2,
.grid-item.card.statistic .icon-container3,
.grid-item.card.statistic .icon-container4 {
    margin-right: 15px;
    display: flex;
    align-items: center;
    margin-top:5px;
}

.grid-item.card.statistic .content h2,
.grid-item.card.statistic .content2 h2,
.grid-item.card.statistic .content3 h2,
.grid-item.card.statistic .content4 h2 {
    color: inherit;
    margin: 0;
    font-size: 45px;
    text-shadow: 0 1px 3px rgba(108, 103, 103, 0.8); 
    text-align:center;
    justify-content:center;
    display:flex;
    width:100%;
    order: 1; 

}

.grid-item.card.statistic .content p,
.grid-item.card.statistic .content2 p,
.grid-item.card.statistic .content3 p,
.grid-item.card.statistic .content4 p {
    color: inherit;
    margin: 10px 0 0 0; 
    text-align:center;
    justify-content:center;
    display:flex; 
    width:100%;
    font-size: 16px;
    text-shadow: 0 1px 3px rgba(111, 108, 108, 0.8);
    order: 2; 
    
}


.grid-item.card.statistic .content-wrapper {
    display: flex;
    align-items: flex-start;
    width: 100%;
}

.on-route {
    background: linear-gradient(to bottom, 
        rgba(27, 123, 19, 0.8) 0%, 
        rgba(69, 137, 63, 0.5) 100%);
    color: white;
    text-shadow: 0 1px 3px rgba(27, 26, 26, 0.8);
}
.on-route .icon-container i {
    color: white;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.error {
    background: linear-gradient(to bottom, 
        rgba(180, 30, 30, 0.9) 0%,  /* Darker red at top */
        rgba(255, 120, 120, 0.5) 100%);
    color: white;
    text-shadow: 0 1px 3px rgba(27, 26, 26, 0.8);
}
.error .icon-container2 i {
    color: white;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.5));
}

.late {
    background: linear-gradient(to bottom, 
        rgba(200, 150, 0, 0.8) 0%,  /* Darker gold at top */
        rgba(235, 224, 190, 1) 100%);
    color: white;
    text-shadow: 0 1px 3px rgba(27, 26, 26, 0.8);
}
.late .icon-container3 i {
    color: rgba(255, 255, 255, 1);
    filter: drop-shadow(0 1px 1px rgba(4, 0, 0, 0.5));
}

.deviated {
    background: linear-gradient(to bottom, 
        rgba(200, 80, 20, 0.8) 0%,  /* Darker orange at top */
        rgba(255, 190, 150, 0.5) 100%);
    color: white;
    text-shadow: 0 1px 3px rgba(27, 26, 26, 0.8);
}
.deviated .icon-container4 i {
    color: white;
    filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.3));
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}



    
    .pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-top: 15px;
}

.pagination-btn {
    padding: 5px 10px;
    background-color: #1b1963;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.grid-item.card.statistic {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-radius: 10px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: rgba(0, 0, 0, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
}


.grid-item.card.statistic:hover {
    transform: scale(1.05); 
   box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 4px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -3px 0px inset;
}


.card-large {
    flex: 3;
    background: #fff;
    padding: 15px;
    border-radius: 15px;
    box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
    margin-top:15px;
     border: 1px solid #ffffff29;
}

.card-large2 {
    flex: 3;
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
}
.card-small {
    flex: 1;
    background: #fff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
}

.fa.fa-user.icon-bg {
    height: 5px; 
    width: 5px; 
    font-size: 24px; 
    color: #B82132; 
    background-color: rgba(184, 33, 50, 0.1); 
    border-radius: 50%; 
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px; 
}

.table-container {
    padding: 10px;
}


table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
padding:20px;
border-radius:15px;
}


th, td {
    border: 1px solid #ddd;
    padding: 15px;
    text-align: center;
    border:none;
    border-bottom: 2px solid rgba(235, 233, 233, 0.88);

}


th {
    background-color: #f8f8f8; 
    padding:10px;
    border-bottom: none;
    border-bottom: 3px double #B82132; 
    padding-bottom: 12px;
    height:30px; 
}

tr:not(:last-child) td {
    border-bottom: 1px solid #f0f0f0; 
}

tr:hover td {
    background-color: rgba(115, 81, 84, 0.07); 
}

tr:first-child th:first-child {
    border-top-left-radius: 15px;
}

tr:first-child th:last-child {
    border-top-right-radius: 15px;
}

tr:last-child td:first-child {
    border-bottom-left-radius: 15px;
}

tr:last-child td:last-child {
    border-bottom-right-radius: 15px;
}

.icon-bg2 {
    display: flex;  /* Enables flexbox */
    align-items: center;  /* Centers vertically */
    justify-content: center;
width: 35px;
height: 22px;
border-radius: 20px;
padding:  5px 10px;
text-align: center;
line-height: 40px;
margin-right: 15px;
   color: #B82132; 
    background-color: rgba(184, 33, 50, 0.1); 
}

.maintenance-section {
    margin-top: 30px;
}

.maintenance-container {
    margin-top: 20px;
    border-radius: 10px;
    overflow: hidden;
}

.maintenance-header {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr;
    padding: 15px 20px;
    background-color: #f8f8f8;
    font-weight: 600;
    color: #555;
    border-bottom: 2px solid #e0e0e0;
}

.maintenance-item {
    display: grid;
    grid-template-columns: 1fr;
    padding: 0;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.maintenance-item:hover {
    background-color: rgba(184, 33, 50, 0.03);
}

.maintenance-details {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr;
    padding: 15px 20px;
    align-items: center;
}

.maintenance-progress {
    height: 4px;
    background-color: #f0f0f0;
    width: 100%;
    border-radius: 2px;
    overflow: hidden;
}

.progress-bar {
    height: 70%;
}   


.status-badge {
    padding: 6px 12px;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    border-radius: 12px;
    font-weight: 600;
}
.pending-badge {
    background-color: rgba(255, 201, 8, 0.2);
    color: #BB9407;
}

.in-progress-badge {
    background-color: rgba(0, 123, 255, 0.2);
    color: #0062cc;
}

.completed-badge {
    background-color: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.overdue-badge {
    background-color: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}


.pending-bar {
    background-color: #FFC107; 
    width: 30%; 
}

.in-progress-bar {
    background-color: #17A2B8; 
    width: 60%; 
}

.completed-bar {
    background-color: #28A745; 
    width: 100%; 
}

.overdue-bar {
    background-color: #DC3545; 
    width: 100%; 
}

.view-all-btn {
    display: block;
    width:20%;
    padding: 12px;
    margin-top: 20px;
    background-color: #B82132;
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
  
}

.view-all-btn:hover {
    background-color: #9a1c2a;
}

/*@media (max-width: 768px) {
    .maintenance-header,
    .maintenance-details {
        grid-template-columns: 1fr 1fr;
    }
    
    .maintenance-header span:nth-child(3),
    .maintenance-details span:nth-child(3) {
        display: none;
    }
    
    .status-badge {
        justify-self: end;
    }
}
    */

.quick-stats {
    background: #f8f9fa;
    padding: 10px 20px;
    display: flex;
    justify-content: space-around;
    margin-top: 50px; 
    border-bottom: 1px solid #e0e0e0;
box-shadow: rgba(67, 71, 85, 0.27) 0px 0px 0.25em, rgba(90, 125, 188, 0.05) 0px 0.25em 1em;
}

.quick-stats span {
    font-size: 0.9rem;
    color: #555;
}

.quick-stats i {
    margin-right: 8px;
    color: #B82132;
}
/* ------------------------------------------------------------------------------------------------------------ */

.pagination-btn:hover:not(:disabled) {
    background-color: #2a277a;
}

.pagination-btn:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
}

.page-info {
    font-size: 14px;
    color: #555;
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



.logo-container {
    display: flex;
    align-content:left;
}

.logo {
    height: 80px;
}



/* .search-container {
    display: flex;
    justify-content: center;
    flex-grow: 1;
    align-items: center;
}

.search-bar {
    padding: 8px;
    width: 200px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
    transition: width 0.3s ease;
}

    .search-bar:focus {
        width: 300px;
    }
*/
   
.profile-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
    
}

.profile-name {
    font-size: 14px;
    font-weight: bold;
    
    
}

/*.sidebar {
    position: fixed;
  
    top: 1rem;
    left: 0;
    width: 80px;
    height: 100%;
    background-color: #edf1ed;
    color: #161616 !important;
    padding: 20px;
    box-sizing: border-box;
    transition: width 0.3s ease;
    overflow-x: hidden;
    overflow-y: hidden;
    z-index: 1100;
    border-right: 2px solid #16161627;
}

    .sidebar:hover {
        width: 300px;
        box-shadow: 100px 0 100px rgba(0, 0, 0, 0.1);
        transition: 0.5s ease;
    }*/



.icon {
    margin-right: 10px;
    color: white !important;
}

.icon2 {
    margin-right: 10px;
    /* font-size: 16px; */
    filter: grayscale(0%); 
    height:1.5em;
}

/* .icon2 {
    margin-right: 10px;
    color: black; 
    opacity: 1; 
    filter: grayscale(100%) brightness(0);
} */

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


.container {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100vh;
}

.main-content {
    margin-top:20px;
    margin-left: 75px; /* Same as the sidebar width */
    transition: margin-left 0.3s ease;
}

.main-content2 {
    margin-top: 40px;
    /*margin-left: 85px;*/ /* Same as the sidebar width */
    margin-left: 200px; /* Adjust based on your sidebar width */
    margin-right: 40px; /* Increased right margin to prevent touching scrollbar */
    width: calc(100% - 320px);
    transition: margin-left 0.3s ease;
    overflow-y: auto;
}

.main-content3 {
    margin-top: 80px;
    margin-left: 100px; /* Reduced left margin from 200px to 100px */
    margin-right: 20px; /* Reduced right margin from 40px to 20px */
    width: calc(100% - 140px); /* Adjusted width calculation based on new margins */
    transition: margin-left 0.3s ease;
    overflow-y: auto;
}

.main-content4 {
    margin-top: 80px;
    margin-left: 100px;
    margin-right: 10px;
    width: calc(100% - 110px);
}

.main-content5 {
    margin-top: 30px;
    margin-left: 100px;
    margin-right: 10px;
    width: calc(100% - 110px);
    
}

/* Toggle Button Styles */
.toggle-sidebar-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    z-index: 1300;
       color: #F6F0F0;
}





body{
   background-color:#FCFAEE;
    font-family: 'Open Sans', sans-serif;
    line-height: 1.2;
}

/* Footer Styles */
.site-footer {
    background-color: #B82132;
    color: white;
    padding: 30px 0 0;
    margin-top: 40px;
    position: relative;
    bottom: 0;
    width: 100%;
}


.footer-bottom {
    text-align: center;
    display:block;
    justify-items:center;
    align-items:center;
    padding: 10px 0;

    
}

.footer-bottom p {
    margin: 0;
    color: #ddd;
    font-size: 16px;
    display:block;
    
}

.modal {
    display: none;
    position: fixed;
    z-index: 11000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow-y: auto; /* Allow background scrolling if needed */
    padding: 20px 0; /* Add padding to prevent modal from touching edges */
}

.modal-content {
    background-color: #fff;
    margin: 2% auto; /* Reduced margin for better visibility */
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    max-width: 700px; /* Increased width slightly */
    max-height: 100vh; /* Increased max height */
    overflow-y: auto; /* Make content scrollable */
    position: relative;
    width: 90%; /* Responsive width */
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}
  .datetime-container {
        display: inline-flex;
        flex-direction: row;
        align-items: right;
        justify-content: right;
        margin-left: 45em;
        gap: 20px;  
    }
    
    .date-display {
        font-size: 14px;
        color: #DDDAD0;
        font-weight:bold;   
    }
    
    .time-display {
        font-size: 14px;
        color: #DDDAD0;
          font-weight:bold;   
    }
    
   
  
    .profile {
    display: flex;
    align-items: center;
    position: relative;
    right: 50px;
    
}

#tripDetailsContent {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    max-height: calc(120vh - 120px); /* Account for header and padding */
    overflow-y: auto; /* Make the content area scrollable */
    padding-right: 10px; /* Space for scrollbar */
}

#tripDetailsContent p {
    margin: 8px 0;
    padding: 5px;
    background-color: #f9f9f9;
    border-radius: 4px;
}

#tripDetailsContent strong {
    color: #555;
    display: inline-block;
    min-width: 120px;
}

#tripDetailsContent .status {
    padding: 3px 8px;
    border-radius: 3px;
    font-weight: bold;
}

#tripDetailsContent .status.completed {
    background-color: #28a745;
    color: white;
}

#tripDetailsContent .status.pending {
    background-color: #ffc107;
    color: black;
}

#tripDetailsContent .status.cancelled {
    background-color: #dc3545;
    color: white;
}

#tripDetailsContent .status.enroute {
    background-color: #007bff;
    color: white;
}

.trip-details {
    background-color: #B82132;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: background-color 0.3s;
}

.trip-details:hover {
    background-color: #9a1c2a;
}
.company {
    margin-left:-90px;
    height: 110px;
}

#calendar {
    width: 96%;
    background-color: #ffffff;
    padding: 30px;
    border-radius: 20px;
    box-shadow: rgba(0, 0, 0, 0.15) 0px 15px 25px, rgba(0, 0, 0, 0.05) 0px 5px 10px;
    height:auto;

}

.fc-event {
    max-width: 120px !important;
    border: none !important;
    border-radius: 4px !important;
    padding: 2px 4px !important;
    margin: 1px 0 !important;
    font-size: 0.85em !important;
    line-height: 1.2;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    display: inline-block;
}

.fc-event .fc-content {
    display: flex;
    flex-direction: column;
}

.fc-event .fc-time {
    font-weight: bold;
    margin-right: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.fc-event .fc-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

.fc-day-selected {
    background-color: #d5d5d8 !important;
    color: white !important;
}

.fc-day:hover {
    background-color: #d5d5d8;
    color: white;
}

.status.completed {
    background-color: #28a745;
    color: white;
}

.status.pending {
    background-color: #ffc107;
    color: black;
}

.status.cancelled {
    background-color: #dc3545;
    color: white;
}

.status.enroute, 
.status.en-route {
    background-color: #007bff;
    color: white;
}

  .calendar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        padding: 10px;
        background-color: #ffffffff;
        border-radius: 8px;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .legend-color {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 4px;
    }
    
    .legend-color.pending {
        background-color: #ffc107; 
    }
    
    .legend-color.enroute {
        background-color: #007bff; 
    }
    
    .legend-color.completed {
        background-color: #28a745; 
    }
    
    .legend-color.cancelled {
        background-color: #dc3545; 
    }
    
    .legend-label {
        font-size: 14px;
        color: #333;
    }

    .quick-actions-bar {
    display: flex;
    gap: 10px;
    margin: 15px 0;
    padding: 10px 0;
    border-bottom: 1px solid #d5d2d2ff;
}

.quick-action-btn {
    padding: 10px 15px;
    background-color:#FCFAEE;
    color: black;
    border: none;
    border-radius: 10px;
    font-size:14px;
    cursor: pointer;
    transition: background 0.3s;
    border: 1px solid #00000027;
    box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
}

.quick-action-btn:hover {
    background: #ff7777da;
}



</style>
<?php
require_once __DIR__ . '/include/check_access.php';
require 'include/handlers/dbhandler.php';

// Fetch trip data
$sql = "SELECT a.*, t.plate_no as truck_plate_no, t.capacity as truck_capacity, a.edit_reasons, d.driver_id
        FROM assign a
        LEFT JOIN drivers_table d ON a.driver = d.name
        LEFT JOIN truck_table t ON d.assigned_truck_id = t.truck_id
        WHERE a.is_deleted = 0";
$result = $conn->query($sql);
$eventsData = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $eventsData[] = [
            'id' => $row['trip_id'],
            'plateNo' => $row['plate_no'],
            'date' => $row['date'],
            'driver' => $row['driver'],
            'driver_id' => $row['driver_id'],
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
            'truck_capacity' => $row['truck_capacity'],
            'edit_reasons' => $row['edit_reasons'] 
        ];
    }
}

$eventsDataJson = json_encode($eventsData);
?>
</head>

<body>
<header class="header">
    <button id="toggleSidebarBtn" class="toggle-sidebar-btn">
        <i class="fa fa-bars"></i>
    </button>
    <div class="logo-container">
       
        <img src="include/img/mansar2.png" alt="Company Name" class="company">
    </div>

    <div class="datetime-container">
        <div id="current-date" class="date-display"></div>
        <div id="current-time" class="time-display"></div>
    </div>

    <div class="profile">
        <img src="include/img/profile.png" alt="Admin Profile" class="profile-icon">
        <div class="profile-name">
            <?php 
                echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
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
     <a href="include/handlers/logout.php" data-no-loading="true">Logout</a>
    </div>
</div>

 <!-- <div class="quick-stats">
    <span><i class="fas fa-truck"></i> 42 Active Vehicles</span>
    <span><i class="fas fa-user"></i> 18 Drivers On Duty</span>
    <span><i class="fas fa-map-marker-alt"></i> 7 Deliveries Today</span>
</div>  -->
<div class="quick-actions-bar">
    <button class="quick-action-btn">
        <i class="fas fa-plus"></i> New Delivery
    </button>
    <button class="quick-action-btn">
        <i class="fas fa-truck"></i> Track Fleet
    </button>
    <button class="quick-action-btn">
        <i class="fas fa-calendar-alt"></i> Schedule Maintenance
    </button>
</div>

<div class="dashboard-grid">
    
 <div class="grid-item card statistic on-route">
    <div class="icon-container">
        <i class="fa fa-truck"></i>
    </div>
    <div class="content">
        <h2><?php echo htmlspecialchars($ongoingCount); ?></h2>
        <p>On Going Deliveries</p>
    </div>
</div>

<div class="grid-item card statistic error">
    <div class="icon-container2">
        <i class="fa fa-wrench"></i>
    </div>
    <div class="content2">
        <h2><?php echo htmlspecialchars($allrepairtrucks); ?></h2>
        <p>Under Repair Trucks</p>
    </div>
</div>

<div class="grid-item card statistic late">
    <div class="icon-container3">
        <i class="fa fa-hourglass-end"></i>
    </div>
    <div class="content3">
        <h2><?php echo htmlspecialchars($alldeliveries); ?></h2>
        <p>Scheduled Deliveries</p>
    </div>
</div>

<div class="grid-item card statistic deviated">
    <div class="icon-container4">
        <i class="fa fa-cogs"></i>
    </div>
    <div class="content4">
        <h2><?php echo htmlspecialchars($alloverduetrucks); ?></h2>
        <p>Unchecked Vehicles</p>
    </div>
</div>
</div>

<!-- On going vehicles -->

<div class="card-large">
    <div class="table-container">
        <h3>Ongoing Vehicles</h3>
        <table>
            <tr>
                <th></th>
                <th>Plate No.</th>
                <th>Driver</th>
                <th>Helper</th>
                <th>Client</th>
                <th>Delivery Address</th>
                 <th>Date of Departure</th>
                <th>Actions</th>
            </tr>
           
        </table>
    </div>
</div>

<div class="dashboard-section">
    <div class="card-large2">
        <h3>Shipment Statistics</h3>
        <p>Total deliveries: 23.8k</p>
        <div id="shipmentStatisticsChart"></div>
    </div>
    <div class="card-small">
        <h3>Active Drivers</h3>
        <?php
      
        if (count($drivingDrivers) > 0) {
           
            foreach ($drivingDrivers as $driver) {
                echo '<div class="performance">
                        <i class="fa fa-user icon-bg"></i>
                        <p>' . htmlspecialchars($driver['driver']) . ' - Destination: ' . htmlspecialchars($driver['destination']) . '</p>
                      </div>';
            }
        } else {
           
            echo '<div class="performance">
                    <i class="fa fa-info-circle icon-bg"></i>
                    <p>No active drivers currently on duty</p>
                  </div>';
        }
        ?>
    </div>
</div>
<section class="maintenance-section">
    <div class="card-large">
        <h3>Maintenance keneve Status</h3>
        <div class="maintenance-container">
            <div class="maintenance-header">
                <span class="header-vehicle">License Plate</span>
                <span class="header-service">Service Type</span>
                <span class="header-date">Due Date</span>
                <span class="header-status">Status</span>
            </div>
            
            <?php if (!empty($maintenanceRecords)): ?>
              <?php foreach ($maintenanceRecords as $record): 
   
    $statusClass = strtolower(str_replace(' ', '-', $record['status']));
    $badgeClass = $statusClass . '-badge';
    $barClass = $statusClass . '-bar';
    
   
    $dueDate = date('M j, Y', strtotime($record['date_mtnce']));
    $today = new DateTime();
    $dueDateTime = new DateTime($record['date_mtnce']);
    $interval = $today->diff($dueDateTime);
    $daysDifference = $interval->format('%r%a');
    
  
    if ($daysDifference == 0) {
        $dateString = 'Today';
               }elseif ($daysDifference == 1) {
                $dateString = 'Tomorrow';
               } elseif ($daysDifference > 1 && $daysDifference <= 7) {
                $dateString = 'In ' . $daysDifference . ' days';
               } elseif ($daysDifference < 0) {
               $dateString = abs($daysDifference) . ' days overdue';
               } else {
              $dateString = $dueDate;
             }
            ?>
        <div class="maintenance-item">
         <div class="maintenance-details">
             <span class="vehicle"><?php echo htmlspecialchars($record['licence_plate']); ?></span>
             <span class="service"><?php echo htmlspecialchars($record['remarks']); ?></span>
             <span class="date"><?php echo $dateString; ?></span>
              <span class="status-badge <?php echo $badgeClass; ?>">
            <?php echo htmlspecialchars($record['status']); ?>
             </span>
             </div>
             <div class="maintenance-progress">
                <div class="progress-bar <?php echo $barClass; ?>"></div>
             </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
                <div class="maintenance-item">
                    <div class="maintenance-details" style="grid-template-columns: 1fr; text-align: center;">
                        <span>No maintenance records found</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <button class="view-all-btn" onclick="window.location.href='maintenance.php'">View All Maintenance Records</button>
    </div>
</section>

<section class="calendar-section">
    <div class="card-large">
        <h3>Event Calendar</h3>
        
        <div class="calendar-legend">
            <div class="legend-item">
                <span class="legend-color pending"></span>
                <span class="legend-label">Pending</span>
            </div>
            <div class="legend-item">
                <span class="legend-color enroute"></span>
                <span class="legend-label">En Route</span>
            </div>
            <div class="legend-item">
                <span class="legend-color completed"></span>
                <span class="legend-label">Completed</span>
            </div>
            <div class="legend-item">
                <span class="legend-color cancelled"></span>
                <span class="legend-label">Cancelled</span>
            </div>
        </div>
        
        <div id="calendar"></div>
    </div>
</section>


<script>
  


    var shipmentOptions = {
        series: [{
            name: 'Income',
            type: 'column',
            data: [1.4, 2, 2.5, 1.5, 2.5, 2.8, 3.8, 4.6]
        }, {
            name: 'Cashflow',
            type: 'column',
            data: [1.1, 3, 3.1, 4, 4.1, 4.9, 6.5, 8.5]
        }, {
            name: 'Revenue',
            type: 'line',
            data: [20, 29, 37, 36, 44, 45, 50, 58]
        }],
        chart: {
            height: 350,
            type: 'line',
            stacked: false
        },
        xaxis: {
            categories: [2009, 2010, 2011, 2012, 2013, 2014, 2015, 2016],
        },
        yaxis: [{
            seriesName: 'Income',
            axisTicks: { show: true },
            axisBorder: { show: true, color: '#008FFB' },
            labels: { style: { colors: '#008FFB' } },
            title: { text: "Income (thousand crores)", style: { color: '#008FFB' } }
        }, {
            seriesName: 'Cashflow',
            opposite: true,
            axisTicks: { show: true },
            axisBorder: { show: true, color: '#00E396' },
            labels: { style: { colors: '#00E396' } },
            title: { text: "Operating Cashflow (thousand crores)", style: { color: '#00E396' } }
        }, {
            seriesName: 'Revenue',
            opposite: true,
            axisTicks: { show: true },
            axisBorder: { show: true, color: '#FEB019' },
            labels: { style: { colors: '#FEB019' } },
            title: { text: "Revenue (thousand crores)", style: { color: '#FEB019' } }
        }],
        tooltip: { fixed: { enabled: true, position: 'topLeft', offsetY: 30, offsetX: 60 } },
        legend: { horizontalAlign: 'left', offsetX: 40 }
    };


    var shipmentChart = new ApexCharts(document.querySelector("#shipmentStatisticsChart"), shipmentOptions);
    shipmentChart.render();


    var vehicleOptions = {
        series: [{
            name: 'On the way',
            data: [39.7]
        }, {
            name: 'Unloading',
            data: [28.3]
        }, {
            name: 'Loading',
            data: [17.4]
        }, {
            name: 'Waiting',
            data: [14.6]
        }],
        chart: {
            height: 350,
            type: 'pie'
        },
        labels: ['On the way', 'Unloading', 'Loading', 'Waiting']
    };


    
     var calendarEvents = <?php echo $eventsDataJson; ?>;
    
    
    var formattedEvents = calendarEvents.map(function(event) {
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
            modifiedat: event.modifiedat
        };
    });

  
   $(document).ready(function() {
    $('#calendar').fullCalendar({
        header: { 
            left: 'prev,next today', 
            center: 'title', 
            right: 'month,agendaWeek,agendaDay' 
        },
        events: formattedEvents,
        timeFormat: 'h:mm A', 
        displayEventTime: true, 
        displayEventEnd: false, 
        eventRender: function(event, element) {
            element.find('.fc-title').css({
                'white-space': 'normal',
                'overflow': 'visible'
            });
            
            element.find('.fc-title').html(event.client + ' - ' + event.destination);
            
            var statusClass = 'status ' + event.status.toLowerCase().replace(/\s+/g, '');
            element.addClass(statusClass);
        },
        dayClick: function(date, jsEvent, view) {
            var clickedDay = $(this);
            $('.fc-day').removeClass('fc-day-selected');
            clickedDay.addClass('fc-day-selected');
        },
        eventClick: function(calEvent, jsEvent, view) {
           
            var dateObj = new Date(calEvent.start);
            var formattedDate = dateObj.toLocaleString();
            
          
            var modifiedDate = calEvent.modifiedat ? new Date(calEvent.modifiedat).toLocaleString() : 'N/A';
            
          
            $('#td-plate').text(calEvent.plateNo || 'N/A');
            $('#td-date').text(formattedDate);
            $('#td-driver').text(calEvent.driver || 'N/A');
            $('#td-helper').text(calEvent.helper || 'N/A');
            $('#td-dispatcher').text(calEvent.dispatcher || 'N/A');
            $('#td-container').text(calEvent.containerNo || 'N/A');
            $('#td-client').text(calEvent.client || 'N/A');
            $('#td-destination').text(calEvent.destination || 'N/A');
            $('#td-shipping').text(calEvent.shippingLine || 'N/A');
            $('#td-consignee').text(calEvent.consignee || 'N/A');
            $('#td-size').text(calEvent.size || 'N/A');
            $('#td-cashadvance').text(calEvent.cashAdvance || 'N/A');
            $('#td-status').text(calEvent.status || 'N/A')
                          .removeClass()
                          .addClass('status ' + (calEvent.status ? calEvent.status.toLowerCase().replace(/\s+/g, '') : ''));
            $('#td-modifiedby').text(calEvent.modifiedby || 'System');
            $('#td-modifiedat').text(modifiedDate);
            
           
            $('#tripDetailsModal').show();
            
            
            return false;
        }
    });
});
 
</script>


<script>
    document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('expanded');
    });
</script>

<script>

     function updateDateTime() {
        const now = new Date();
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
        
        document.getElementById('current-time').textContent = now.toLocaleTimeString();
    }

    // Update immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);
$(document).ready(function() {
    let currentPage = 1;
    
    // Function to load trips for a specific page
   function loadTrips(page) {
    $.ajax({
        url: 'include/handlers/dashboard_handler.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_ongoing_trips',
            page: page
        }),
        success: function(response) {
            if (response.success) {
                $('.table-container table tr:not(:first)').remove();
                
                if (response.trips.length > 0) {
                    response.trips.forEach(function(trip) {
                        var dateObj = new Date(trip.date);
                        var formattedDate = dateObj.toLocaleDateString();
                        var formattedTime = dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        var row = `
                            <tr>
                                <td><i class="fa fa-automobile icon-bg2"></i></td>
                                <td>${trip.plate_no}</td>
                                <td>${trip.driver}</td>
                                <td>${trip.helper}</td>
                                <td>${trip.client}</td>
                                <td>${trip.destination}</td>
                                <td>${formattedDate} ${formattedTime}</td>
                                <td><button class="trip-details" data-id="${trip.trip_id}">View Trip Details</button></td>
                            </tr>
                        `;
                        $('.table-container table').append(row);
                    });
                } else {
                    $('.table-container table').append(`
                        <tr>
                            <td colspan="8" style="text-align: center;">No ongoing trips found</td>
                        </tr>
                    `);
                }
                
                updatePaginationControls(response.pagination);
                currentPage = page;
            }
        },
        error: function() {
            console.error('Error fetching ongoing trips');
        }
    });
}

function fetchTripDetails(tripId) {
    return $.ajax({
        url: 'include/handlers/dashboard_handler.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_trip_details',
            tripId: tripId
        })
        
    });
}
    
    // Function to update pagination controls
    function updatePaginationControls(pagination) {
        // Remove existing pagination controls if they exist
        $('.pagination-controls').remove();
        
        // Create new pagination controls
        var controls = `
            <div class="pagination-controls" style="margin-top: 10px; text-align: center;">
                <button class="pagination-btn prev" ${pagination.currentPage <= 1 ? 'disabled' : ''}>
                    <i class="fa fa-chevron-left"></i> Previous
                </button>
                <span class="page-info">
                    Page ${pagination.currentPage} of ${pagination.totalPages}
                </span>
                <button class="pagination-btn next" ${pagination.currentPage >= pagination.totalPages ? 'disabled' : ''}>
                    Next <i class="fa fa-chevron-right"></i>
                </button>
            </div>
        `;
        
        $('.table-container').append(controls);
    }
    
    // Initial load
    loadTrips(currentPage);
    
    // Pagination button click handlers
    $(document).on('click', '.pagination-btn.prev', function() {
        if (currentPage > 1) {
            loadTrips(currentPage - 1);
        }
    });
    
    $(document).on('click', '.pagination-btn.next', function() {
        loadTrips(currentPage + 1);
    });


$(document).on('click', '.trip-details', function() {
    var tripId = $(this).data('id');
    
    fetchTripDetails(tripId).then(function(response) {
        if (response.success) {
            var trip = response.trip;
            var dateObj = new Date(trip.date);
            var modifiedDateObj = new Date(trip.last_modified_at);
            
            $('#td-plate').text(trip.plate_no || 'N/A');
            $('#td-date').text(dateObj.toLocaleString());
            $('#td-driver').text(trip.driver || 'N/A');
            $('#td-helper').text(trip.helper || 'N/A');
            $('#td-dispatcher').text(trip.dispatcher || 'N/A');
            $('#td-container').text(trip.container_no || 'N/A');
            $('#td-client').text(trip.client || 'N/A');
            $('#td-destination').text(trip.destination || 'N/A');
            $('#td-shipping').text(trip.shippine_line || 'N/A');
            $('#td-consignee').text(trip.consignee || 'N/A');
            $('#td-size').text(trip.size || 'N/A');
            $('#td-cashadvance').text(trip.cash_adv || 'N/A');
            $('#td-status').text(trip.status || 'N/A').removeClass().addClass('status ' + (trip.status ? trip.status.toLowerCase().replace(/\s+/g, '') : ''));
            $('#td-modifiedby').text(trip.last_modified_by || 'System');
            $('#td-modifiedat').text(modifiedDateObj.toLocaleString());
            
            $('#tripDetailsModal').show();
        } else {
            alert('Error loading trip details: ' + response.message);
        }
    }).catch(function(error) {
        console.error('Error:', error);
        alert('Failed to load trip details');
    });
});

$('.close').on('click', function() {
    $('#tripDetailsModal').hide();
});

$(window).on('click', function(event) {
    if ($(event.target).hasClass('modal')) {
        $('#tripDetailsModal').hide();
    }
});

});

$(document).on('mouseenter', '.maintenance-item', function() {
    $(this).css('transform', 'scale(1.02)');
}).on('mouseleave', '.maintenance-item', function() {
    $(this).css('transform', 'scale(1)');
});

// Smooth scroll for anchor links
$('a[href*="#"]').on('click', function(e) {
    e.preventDefault();
    $('html, body').animate(
        { scrollTop: $($(this).attr('href')).offset().top - 20 },
        500
    );
});



document.addEventListener('DOMContentLoaded', function() {
    // Get current page filename
    const currentPage = window.location.pathname.split('/').pop();
    
    // Find all sidebar links
    const sidebarLinks = document.querySelectorAll('.sidebar-item a');
    
    // Check each link
    sidebarLinks.forEach(link => {
        const linkPage = link.getAttribute('href').split('/').pop();
        
        // If this link matches current page, add active class
        if (linkPage === currentPage) {
            link.parentElement.classList.add('active');
            
            // Also highlight the icon
            const icon = link.parentElement.querySelector('.icon2');
            if (icon) {
                icon.style.color = 'white';
            }
        }
    });
});

</script>



<footer class="site-footer">

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
    </div>
</footer>

<div id="tripDetailsModal" class="modal">
    <div class="modal-content" style="max-width: 600px; max-height: 80vh; overflow-y: auto;">
        <span class="close">&times;</span>
        <h3>Trip Details</h3>
        <div id="tripDetailsContent" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <p><strong>Plate No:</strong> <span id="td-plate"></span></p>
            <p><strong>Date:</strong> <span id="td-date"></span></p>
            <p><strong>Driver:</strong> <span id="td-driver"></span></p>
            <p><strong>Helper:</strong> <span id="td-helper"></span></p>
            <p><strong>Dispatcher:</strong> <span id="td-dispatcher"></span></p>
            <p><strong>Container No:</strong> <span id="td-container"></span></p>
            <p><strong>Client:</strong> <span id="td-client"></span></p>
            <p><strong>Destination:</strong> <span id="td-destination"></span></p>
            <p><strong>Shipping Line:</strong> <span id="td-shipping"></span></p>
            <p><strong>Consignee:</strong> <span id="td-consignee"></span></p>
            <p><strong>Size:</strong> <span id="td-size"></span></p>
            <p><strong>Cash Advance:</strong> <span id="td-cashadvance"></span></p>
            <p><strong>Status:</strong> <span id="td-status" class="status"></span></p>
            <p><strong>Last Modified By:</strong> <span id="td-modifiedby"></span></p>
            <p><strong>Last Modified At:</strong> <span id="td-modifiedat"></span></p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="include/js/logout-confirm.js"></script>

<div id="admin-loading" class="admin-loading">
  <div class="admin-loading-container">
    <div class="loading-gif-container">
      <img src="include/img/loading.gif" alt="Loading..." class="loading-gif">
    </div>
    <div class="admin-loading-content">
      <h4 class="loading-title">Loading Page</h4>
      <p class="loading-message">Redirecting to another page...</p>
      <div class="loading-progress">
        <div class="progress-bar"></div>
        <span class="progress-text">0%</span>
      </div>
    </div>
  </div>
</div>
<script>

  const AdminLoading = {
  init() {
    this.loadingEl = document.getElementById('admin-loading');
    this.titleEl = document.querySelector('.loading-title');
    this.messageEl = document.querySelector('.loading-message');
    this.progressBar = document.querySelector('.progress-bar');
    this.progressText = document.querySelector('.progress-text');
    
    this.setupNavigationInterception();
  },
  
  show(title = 'Processing Request', message = 'Please wait while we complete this action...') {
    this.titleEl.textContent = title;
    this.messageEl.textContent = message;
    
    // Start the sequence with longer delays
    this.loadingEl.style.display = 'flex';
    setTimeout(() => {
      this.loadingEl.classList.add('active');
    }, 50);
  },
  
  hide() {
    // Longer fade out
    this.loadingEl.classList.remove('active');
    setTimeout(() => {
      this.loadingEl.style.display = 'none';
    }, 800); 
  },
  
  updateProgress(percent) {
    this.progressBar.style.width = `${percent}%`;
    this.progressText.textContent = `${percent}%`;
  },
  
   setupNavigationInterception() {
  document.addEventListener('click', (e) => {
    // Skip if click is inside SweetAlert modal
    if (e.target.closest('.swal2-container, .swal2-popup, .swal2-modal')) {
      return;
    }
    
    // Skip if click is on any modal element
    if (e.target.closest('.modal, .modal-content')) {
      return;
    }
    
    const link = e.target.closest('a');
    if (link && !link.hasAttribute('data-no-loading') && 
        link.href && !link.href.startsWith('javascript:') &&
        !link.href.startsWith('#')) {
      e.preventDefault();
      
      const loading = this.startAction(
        'Loading Page', 
        `Preparing ${link.textContent.trim()}...`
      );
      
      let progress = 0;
      const progressInterval = setInterval(() => {
        progress += Math.random() * 40; 
        if (progress >= 90) clearInterval(progressInterval);
        loading.updateProgress(Math.min(progress, 100));
      }, 300); 
      
      const minLoadTime = 2000;
      const startTime = Date.now();
      
      setTimeout(() => {
        window.location.href = link.href;
      }, minLoadTime);
    }
  });

  document.addEventListener('submit', (e) => {
    // Skip if form is inside SweetAlert or modal
    if (e.target.closest('.swal2-container, .swal2-popup, .modal')) {
      return;
    }
    
    const loading = this.startAction(
      'Submitting Form', 
      'Processing your data...'
    );
    
    setTimeout(() => {
      loading.complete();
    }, 1500);
  });
}
    

  
  startAction(actionName, message) {
    this.show(actionName, message);
    return {
      updateProgress: (percent) => this.updateProgress(percent),
      updateMessage: (message) => {
        this.messageEl.textContent = message;
        this.messageEl.style.opacity = 0;
        setTimeout(() => {
          this.messageEl.style.opacity = 1;
          this.messageEl.style.transition = 'opacity 0.5s ease';
        }, 50);
      },
      complete: () => {

        this.updateProgress(100);
        this.updateMessage('Done!');
        setTimeout(() => this.hide(), 800);
      }
    };
  }
};

document.addEventListener('DOMContentLoaded', () => {
  AdminLoading.init();
  
  // Add smooth transition to the GIF
  const loadingGif = document.querySelector('.loading-gif');
  if (loadingGif) {
    loadingGif.style.transition = 'opacity 0.7s ease 0.3s';
  }
});
</script>
</body>
</html>