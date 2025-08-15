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
    <link rel="stylesheet" href="include/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.css" rel="stylesheet">


<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.js"></script>
<style>

 .shipments-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .shipment-row {
        display: flex;
        flex-wrap: wrap;
        gap: 45px;
        margin-bottom: 20px;
    }

    .shipment-card {
        flex: 1 1 calc(33.333% - 20px);
        max-width: 350px;
        height:430px;
        background-color: white;
        border-radius: 8px;
        box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px;
        padding: 10px;
        contain:content;
        white-space:wrap;
        flex-shrink:0.8
    }



    .no-shipments {
        text-align: center;
        padding: 40px;
        background-color: #f9f9f9;
        border-radius: 8px;
        color: #666;
    }

.shipment-header {
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
    margin-bottom: 15px;
}


    .shipment-number {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .info-label {
        font-weight: bold;
        color: #555;
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
    }
    .info-value {
        font-weight:bolder;
        color: #333;
        font-size: 20px;
    }


    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .shipment-card {
            flex: 1 1 calc(50% - 20px);
        }
    }

    @media (max-width: 768px) {
        .shipment-card {
            flex: 1 1 100%;
        }
    }

.shipment-content {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.left-section {
    flex: 2;
    position: relative;
    padding-left: 30px;

}
.right-section {
    flex: 1;
}

.info-label {

    color: #8d8b8bff;
    display: block;
    margin-bottom: 5px;
     font-size: 14px;


}

.plate-number {
    font-size: 20px;
    font-weight: 700;
    color: #333;
}


.divider {
    border-top: 1px dashed #ccc;
    margin: 15px 0;
    width: 300%;
}

.route-container {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    position: relative;
}


.route-section {
    flex: 1;
    position: relative;
    padding-right: 30px;

}
.client-section {
    flex: 1;
    padding-left: 30px;

}

.route-visual {
    position: relative;
    height: 40px;
    margin: 10px 0;
}



.info-section {
    background-color: #ffffffff;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 10px;
       flex-shrink:1.2;
}

.vertical-route {
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    transform: translateX(-50%);
    z-index: 1;
}


.route-line {
        width: 2px;
    flex-grow: 1;
    background: linear-gradient(to bottom, #4CAF50, #2196F3);
    z-index: 1;
    margin: 0 auto;
}
.pointer {
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 12px solid #4CAF50;
    margin-bottom: -1px;
    z-index: 2;
     position: absolute;
    top: 0;

}

.pin {
    width: 12px;
    height: 12px;
    background-color: #2196F3;
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.3);
    margin-top: -1px;
    z-index: 2;
      position: absolute;
    bottom: 0;

}


.departure {
    margin-bottom: 15px;
}

.destination {
    margin-top: 15px;

}



.platenum{
  margin-top:40px;
}
.truck-image {
    width: 150px;
    flex-shrink: 0;
    margin-right:20px
}
.header-columns {
    display: flex;
    width: 100%;
    justify-content: space-between;
    margin-top: 5px;
}

.header-column {
    flex: 1;
    padding: 0 10px;
}


.status {
    font-size: 14px;
    padding: 4px 10px;
    border-radius: 20px;
    display: inline-block;
    margin-top:10px;
}

.horizontal-route-container {
    display: flex;
    flex-direction: column;
    height: 200px;
    position: relative;
    margin: 20px 0;
}

.horizontal-top-section {
    display: flex;
    justify-content: space-between;
    align-content:center;
}

.horizontal-bottom-section {
    margin-top: 30px;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.line {
    position: relative;
    height: 2px;
    background: linear-gradient(to right, #4CAF50, #2196F3);
    margin: 10px 0;
    display: flex;
    align-items: center;
   margin-bottom:20px;
}

.horizontal-pointer {
    width: 0;
    height: 0;
    border-top: 8px solid transparent;
    border-bottom: 8px solid transparent;
    border-left: 12px solid #4CAF50;
    position: absolute;
    left: 0;
    transform: translateY(20%);
}

.horizontal-pin {
    width: 12px;
    height: 12px;
    background-color: #2196F3;
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.3);
    position: absolute;
    right: 0;
    transform: translateY(-220%);
}


.shipment-row.single-card {
    justify-content: center;
}

.shipment-row.single-card .shipment-card {
    flex: 1 1 100%;
    max-width: 80%;
    height: auto;
    min-height: 40px;
}
.info-section-client,
.info-section-platenum,
.info-section-driver,
.info-section-helper {
    flex: 1;
    min-width: 120px;
    padding: 5px;
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

<!-- <div class="card-large">
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
</div> -->
<div class="card-large">
    <div class="shipments-container">
    <?php

    $enrouteTrips = array_filter($eventsData, function($trip) {
        return strtolower($trip['status']) === 'en route';
    });

    if (!empty($enrouteTrips)):
        $isSingleCard = count($enrouteTrips) === 1;
        $singleCardClass = $isSingleCard ? 'single-card' : '';

        $chunkSize = $isSingleCard ? 1 : 3;
        $chunkedTrips = array_chunk($enrouteTrips, $chunkSize);

        foreach ($chunkedTrips as $tripRow): ?>
            <div class="shipment-row <?php echo $singleCardClass; ?>">
                <?php foreach ($tripRow as $trip):
                    $departureDate = date('d-m-y g:ia', strtotime($trip['date']));
                    $statusClass = strtolower(str_replace(' ', '-', $trip['status']));
                ?>
                <div class="shipment-card">
                    <div class="shipment-header">
                        <img src="include/img/truck.png" alt="Truck" class="truck-image">
                        <div class="header-details">
                            <div class="plate-column">
                                <span class="info-label">Container Number</span>
                                <span class="plate-number"><?php echo htmlspecialchars($trip['containerNo']); ?></span>
                            </div>
                            <div class="status-column">
                                <span class="status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($trip['status']); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($isSingleCard): ?>
                        <!-- Horizontal layout for single card -->
                        <div class="horizontal-route-container">
                            <div class="horizontal-top-section">
                                <div class="info-section departure">
                                    <span class="info-label">Departure</span>
                                    <span class="info-value"><?php echo $departureDate; ?></span>
                                </div>
                                <div class="info-section destination">
                                    <span class="info-label">Destination</span>
                                    <span class="info-value"><?php echo htmlspecialchars($trip['destination']); ?></span>
                                </div>
                            </div>

                            <div class="horizontal-route-line">
                                <div class="horizontal-pointer"></div>
                                <div class="line"></div>
                                <div class="horizontal-pin"></div>
                            </div>

                            <div class="horizontal-bottom-section">
                                <div class="info-section-client">
                                    <span class="info-label">Client</span>
                                    <span class="info-value"><?php echo htmlspecialchars($trip['client']); ?></span>
                                    </div>
                                <div class="info-section-platenum">
                                    <span class="info-label">Plate Number</span>
                                    <span class="info-value"><?php echo htmlspecialchars($trip['plateNo']); ?></span>
                                    </div>
                                <div class="info-section-driver">
                                    <span class="info-label">Driver</span>
                                    <span class="info-value"><?php echo htmlspecialchars($trip['driver']); ?></span>
                                    </div>
                                <div class="info-section-helper">
                                    <span class="info-label">Helper</span>
                                    <span class="info-value"><?php echo htmlspecialchars($trip['helper']); ?></span>
                                </div>

                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Vertical layout for multiple cards -->
                        <div class="route-container">
                            <div class="vertical-route">
                                <div class="pointer"></div>
                                <div class="route-line"></div>
                                <div class="pin"></div>
                            </div>

                            <div class="route-section">
                                <div class="info-section departure">
                                    <span class="info-label">Departure</span>
                                    <span class="info-value"><?php echo $departureDate; ?></span>
                                </div>
                                <div class="info-section destination">
                                    <span class="info-label">Destination</span>
                                    <span class="info-value"><?php echo htmlspecialchars($trip['destination']); ?></span>
                                </div>
                            </div>

                            <div class="client-section">
                                <div class="info-section client">
                                    <span class="info-label">Client</span>
                                    <span class="info-value"><?php echo htmlspecialchars($trip['client']); ?></span>
                            </div>
                            <div class="info-section platenum">
                                    <span class="info-label">Plate Number</span>
                                    <span class="info-value"><?php echo htmlspecialchars($trip['plateNo']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="info-grid">
                            <div class="info-section">
                                <span class="info-label">Driver</span>
                                <span class="info-value"><?php echo htmlspecialchars($trip['driver']); ?></span>
                            </div>
                            <div class="info-section">
                                <span class="info-label">Helper</span>
                                <span class="info-value"><?php echo htmlspecialchars($trip['helper']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach;
    else: ?>
        <div class="no-shipments">
            <p>There are currently no active shipments</p>
        </div>
    <?php endif; ?>
</div>
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