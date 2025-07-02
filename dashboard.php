<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
   
    header("Location: login.php");
    exit();
}


require_once 'include/handlers/get_driving_drivers.php';


$drivingDrivers = getDrivingDrivers();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
 
   
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.css" rel="stylesheet">


<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.js"></script>
<style>
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

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    background-color: #f4f4f4;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: fixed;
    width: 100%;
    max-height: 40px;
    top: 0;
    left: 0;
    z-index: 1200;

}

.logo-container {
    display: flex;
    align-content:left;
}

.logo {
    height: 80px;
}

.company {
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
.profile {
    display: flex;
    align-items: center;
    position: relative;
    right: 70px;
}

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


.sidebar-item {
    position: relative;
    display: flex;
    align-items: center;
    padding: 10px;
    margin-top: 1.7rem;
    cursor: pointer;
    opacity: 1;
    transition: background-color 0.3s ease, border-color 0.3s ease;
    border: 2px solid transparent;
    box-sizing: border-box;
}

    .sidebar-item:hover {
        background-color: #ffffff;
        border-color: #161616ac;
        width: 300px;
        border-top-left-radius: 40px;
        border-bottom-left-radius: 40px;
    }

.icon {
    margin-right: 10px;
    color: black !important;
}

.icon2 {
    margin-right: 10px;
    /* font-size: 16px; */
    opacity: 0.7; 
    filter: grayscale(70%); 
}

/* .icon2 {
    margin-right: 10px;
    color: black; 
    opacity: 1; 
    filter: grayscale(100%) brightness(0);
} */

.sidebar-item span {
    position: absolute;
    left: 60px;
    visibility: hidden;
    opacity: 0;

}

.sidebar:hover .sidebar-item span {
    visibility: visible;
    opacity: 1;
}

.sidebar-item a:visited,
.sidebar-item a:hover,
.sidebar-item a:active {
    text-decoration: none !important; 
    color: inherit !important; /* Ensure color remains the same */
}

.sidebar-item a {
    position: absolute;
    left: 60px;
    visibility: hidden;
    opacity: 0;
    text-decoration: none !important; /* Force remove underline */
    color: inherit !important; /* Force inherit text color */
}

.sidebar:hover .sidebar-item a {
    visibility: visible;
    opacity: 1;
    text-decoration: none; /* Ensures no underline on hover */
    color: inherit; /* Keeps the same color on hover */
}

.sidebar:hover .logo-container-small {
    display: none;
}

.sidebar:hover .logo-container {
    display: block;
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


</style>

</head>

<body>
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
        <a href="include/handlers/logout.php">Logout</a>
    </div>
</div>


<div class="dashboard-grid">
    <div class="grid-item card statistic on-route">
        <div class="content">
            <p>On Going Deliveries</p>
            <h2>42,420</h2>
            <p class="trend">+18.2% than last week</p>
        </div>
        <div class="icon-container">
            <i class="fa fa-truck"></i>
        </div>
    </div>
    <div class="grid-item card statistic error">
        <div class="content2">
            <p>Damaged Vehicles</p>
            <h2>890</h2>
            <p class="trend">-8.7% than last week</p>
        </div>
        <div class="icon-container2">
            <i class="fa fa-wrench"></i>
        </div>
    </div>
    <div class="grid-item card statistic late">
        <div class="content3">
            <p>Late Deliveries</p>
            <h2>13,890</h2>
            <p class="trend">-2.5% than last week</p>
        </div>
        <div class="icon-container3">
            <i class="fa fa-hourglass-end"></i>
        </div>
    </div>
    
    <div class="grid-item card statistic deviated">
        <div class="content4">
            <p>Unchecked Vehicles</p>
            <h2>27,711</h2>
            <p class="trend">+4.3% than last week</p>
        </div>
        <div class="icon-container4">
            <i class="fa fa-cogs"></i>
        </div>
    </div>
</div>



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
                <th>Actions</th>
            </tr>
            <tr>
                <td><i class="fa fa-automobile icon-bg2"></i></td>
                <td>678 QRS</td>
                <td>Jennnifer Mikaela</td>
                <td>Iggy Iglaea</td>
                <td>Mastabushi</td>
                <td>Los Angeles</td>
                <td><button class="location">View Location</button></td>
                   
                </td>
            </tr>
            <tr>
                <td><i class="fa fa-automobile icon-bg2"></i></td>
                <td>345 lmj</td>
                <td>Ranielle</td>
                <td>Vinz Aguilar</td>
                <td>Toyota</td>
                <td>Divisoria</td>
                <td><button class="location">View Location</button></td>
            </tr>
            <tr>
                <td><i class="fa fa-automobile icon-bg2"></i></td>
                <td>123 ABC</td>
                <td>Mario Luigi</td>
                <td>Kirby Yoshi</td>
                <td>Mastabushi</td>
                <td>Paranque</td>
                <td><button class="location">View Location</button></td>
            </tr>
        </table>
    </div>
</div>
<div class="dashboard-section">
    <div class="card-large">
        <h3>Shipment Statistics</h3>
        <p>Total deliveries: 23.8k</p>
        <div id="shipmentStatisticsChart"></div> <!-- Added for line chart -->
    </div>
    <div class="card-small">
        <h3>Active Drivers</h3>
        <?php
        // Check if there are any drivers with pending status
        if (count($drivingDrivers) > 0) {
            // Loop through each driving driver and display them
            foreach ($drivingDrivers as $driver) {
                echo '<div class="performance">
                        <i class="fa fa-user icon-bg"></i>
                        <p>' . htmlspecialchars($driver['driver']) . ' - Destination: ' . htmlspecialchars($driver['destination']) . '</p>
                      </div>';
            }
        } else {
            // Display a message if no driving drivers are found
            echo '<div class="performance">
                    <i class="fa fa-info-circle icon-bg"></i>
                    <p>No active drivers currently on duty</p>
                  </div>';
        }
        ?>
    </div>
</div>


<section class="calendar-section">
    <div class="card-large">
        <h3>Event Calendar</h3>
        <div id="calendar"></div> 
</section>

<script>
    // Shipment Statistics Line Chart
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

    $(document).ready(function() {
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',    // Buttons for navigation
                center: 'title',            // Title in the center
                right: 'agendaWeek'         // Only show agenda week view
            },
            defaultView: 'agendaWeek',      // Start with week view
            views: {
                agendaWeek: {
                    titleFormat: 'MM-DD-YYYY',  // Display only the week title
                }
            },
            // Custom day render to highlight today's date
            dayRender: function(date, cell) {
                var today = moment().format('MM-DD-YYYY');
                if (date.format('YYYY-MM-DD') === today) {
                    // Apply a gray background for today's date
                    cell.css("background-color", "#e0e0e0");
                    cell.addClass("highlight-today");
                }
            },
            events: [
                {
                    title: 'Ongoing Delivery',
                    start: '2025-04-06T10:00:00',
                    end: '2025-04-06T12:00:00',
                    color: '#457B3D', // Custom color for event
                    description: 'Delivering goods to clients'
                },
                {
                    title: 'Damaged Vehicle',
                    start: '2025-04-07T14:00:00',
                    color: '#D91F19', // Custom color for event
                    description: 'Vehicle needs repair'
                },
                {
                    title: 'Late Delivery',
                    start: '2025-04-08T16:00:00',
                    color: '#BB9407', // Custom color for event
                    description: 'Delivery delayed due to traffic'
                },
                {
                    title: 'Vehicle Maintenance',
                    start: '2025-04-09T09:00:00',
                    color: '#E67931', // Custom color for event
                    description: 'Routine maintenance'
                },
                {
                    title: 'New Event',
                    start: '2025-04-10T10:00:00',
                    end: '2025-04-10T12:00:00',
                    color: '#0077FF',
                    description: 'Client delivery on time'
                },
                {
                    title: 'Package Delivery',
                    start: '2025-04-11T14:00:00',
                    end: '2025-04-11T16:00:00',
                    color: '#FF5733',
                    description: 'Package delivery scheduled for today'
                },
                {
                    title: 'Routine Check-up',
                    start: '2025-04-06T08:00:00',
                    end: '2025-04-06T09:00:00',
                    color: '#F1C40F',
                    description: 'Routine vehicle check-up in the morning'
                },
                {
                    title: 'Urgent Delivery',
                    start: '2025-04-07T11:00:00',
                    color: '#E74C3C',
                    description: 'Urgent delivery to client A'
                },
                {
                    title: 'Driver Rest',
                    start: '2025-04-09T18:00:00',
                    end: '2025-04-09T20:00:00',
                    color: '#2ECC71',
                    description: 'Rest period for driver'
                },
                {
                    title: 'Heavy Traffic Expected',
                    start: '2025-04-10T08:00:00',
                    color: '#9B59B6',
                    description: 'Expect delays due to roadwork'
                },
                {
                    title: 'New Vehicle Inspection',
                    start: '2025-04-11T10:00:00',
                    end: '2025-04-11T11:00:00',
                    color: '#FF8C00',
                    description: 'Inspect new vehicle before use'
                }
            ]
        });
    });
  
 
</script>

<script>
    document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('expanded');
    });
</script>


</body>
</html>