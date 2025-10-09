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
$maintenanceQuery = "
    SELECT 
        t.plate_no, 
        mt.type_name AS service_type, 
        m.remarks, 
        m.date_mtnce, 
        m.status
    FROM maintenance_table m
    INNER JOIN truck_table t 
        ON m.truck_id = t.truck_id
    INNER JOIN maintenance_types mt 
        ON m.maintenance_type_id = mt.maintenance_type_id
    WHERE m.status != 'Completed'
      AND m.maintenance_id NOT IN (
          SELECT maintenance_id 
          FROM audit_logs_maintenance 
          WHERE is_deleted = 1
      )
    ORDER BY m.date_mtnce ASC
    LIMIT 5
";

$maintenanceResult = $conn->query($maintenanceQuery);

$maintenanceRecords = [];
if ($maintenanceResult && $maintenanceResult->num_rows > 0) {
    while ($row = $maintenanceResult->fetch_assoc()) {
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

<?php

require 'include/handlers/dbhandler.php';

// Fetch trip data
$sql = "SELECT 
            t.trip_id,
            t.container_no as container_no,
            t.trip_date as date,
            t.status,
            tr.plate_no as plate_no,
            tr.capacity as size,
            tr.truck_pic,
            d.name as driver,
            d.driver_id,
            h.name as helper,
            disp.name as dispatcher,
            c.name as client,
            p.name as port, /* ADD THIS LINE */
            dest.name as destination,
            sl.name as shipping_line,
            cons.name as consignee,
            al.modified_by as last_modified_by,
            al.modified_at as last_modified_at,
            al.edit_reason as edit_reasons,
            COALESCE(te.cash_advance, 0) as cash_adv
        FROM trips t
        LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
        LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
        LEFT JOIN helpers h ON t.helper_id = h.helper_id
        LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
        LEFT JOIN clients c ON t.client_id = c.client_id
        LEFT JOIN ports p ON t.port_id = p.port_id /* ADD THIS LINE */
        LEFT JOIN destinations dest ON t.destination_id = dest.destination_id
        LEFT JOIN shipping_lines sl ON t.shipping_line_id = sl.shipping_line_id
        LEFT JOIN consignees cons ON t.consignee_id = cons.consignee_id
        LEFT JOIN audit_logs_trips al ON t.trip_id = al.trip_id AND al.is_deleted = 0
        LEFT JOIN trip_expenses te ON t.trip_id = te.trip_id
        WHERE NOT EXISTS (
            SELECT 1 FROM audit_logs_trips al2 
            WHERE al2.trip_id = t.trip_id AND al2.is_deleted = 1
        )";

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
            'port' => $row['port'],
            'destination' => $row['destination'],
            'shippingLine' => $row['shipping_line'],
            'consignee' => $row['consignee'],
            'size' => $row['size'],
            'cashAdvance' => $row['cash_adv'],
            'status' => $row['status'],
            'modifiedby' => $row['last_modified_by'],
            'modifiedat' => $row['last_modified_at'],
            'truck_plate_no' => $row['plate_no'],
            'truck_pic' => $row['truck_pic'], 
            'edit_reasons' => $row['edit_reasons']
        ];
    }
}

$eventsDataJson = json_encode($eventsData);
?>
</head>

<body>
<header class="header">
    <div class="header-left">
    <button id="toggleSidebarBtn" class="toggle-sidebar-btn">
        <i class="fa fa-bars"></i>
    </button>
    <div class="logo-container">

        <img src="include/img/mansar2.png" alt="Company Name" class="company">
    </div>
</div>
  <div class="header-right">
    <div class="datetime-container">
        <div id="current-date" class="date-display"></div>
        <div id="current-time" class="time-display"></div>
    </div>

   <div class="profile">
    <?php 
   
    if (isset($_SESSION['admin_pic']) && !empty($_SESSION['admin_pic'])) {
        
        echo '<img src="data:image/jpeg;base64,' . $_SESSION['admin_pic'] . '" alt="Admin Profile" class="profile-icon">';
    } else {
        
        echo '<img src="include/img/profile.png" alt="Admin Profile" class="profile-icon">';
    }
    ?>
    <div class="profile-name">
        <?php 
            echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
        ?>
    </div>
</div>
</div>
</header>

<?php require_once __DIR__ . '/include/sidebar.php'; ?>

<div id="sidebar-backdrop" class="backdrop"></div>
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

<div class="card-large">
    <div class="shipments-container">
        <div class="card-header">
            <i class="fas fa-route header-icon"></i>
            <h4 class="shipment-title">Ongoing Trips <span class="otw-trips"><?php echo htmlspecialchars($ongoingCount); ?> </span></h4>
        </div>
    <?php

    $enrouteTrips = array_filter($eventsData, function($trip) {
        return strtolower($trip['status']) === 'en route';
    });

  if (!empty($enrouteTrips)):
    $tripCount = count($enrouteTrips);
    $specialLayoutClass = '';
    
    // Determine special layout classes
    if ($tripCount === 1) {
        $specialLayoutClass = 'single-card';
    } elseif ($tripCount === 2) {
        $specialLayoutClass = 'two-cards';
    }
    
    $chunkSize = ($tripCount === 1) ? 1 : 3;
    $chunkedTrips = array_chunk($enrouteTrips, $chunkSize);

        foreach ($chunkedTrips as $tripRow): ?>
        <div class="shipment-row <?php echo $specialLayoutClass; ?>">
         <?php foreach ($tripRow as $trip):
    $departureDate = date('d-m-y g:ia', strtotime($trip['date']));
    $statusClass = strtolower(str_replace(' ', '-', $trip['status']));
?>
    <div class="shipment-card" data-trip-id="<?php echo htmlspecialchars($trip['id']); ?>">
        
        <?php if ($tripCount === 1): ?>
            <div class="shipment-header-single">
                <img src="include/img/truck2.png" alt="Truck <?php echo htmlspecialchars($trip['plateNo']); ?>" class="truck-image">
                <div class="details-single">
                    <span class="container-info">
                        <span class="info-label">Container No:</span>
                        <span class="plate-number"><?php echo htmlspecialchars($trip['containerNo']); ?></span>
                    </span>
                    <span class="status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($trip['status']); ?></span>
                </div>
            </div>

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
            <div class="shipment-header">
                <img src="include/img/truck2.png" alt="Truck <?php echo htmlspecialchars($trip['plateNo']); ?>" class="truck-image">
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
 
    <div class="card-large">
        <div class="card-header">
            <i class="fa-solid fa-screwdriver-wrench header-icon"></i>
        <h3>Maintenance Status</h3>
    </div>
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
             <span class="vehicle"><?php echo htmlspecialchars($record['plate_no']); ?></span>
<span class="service"><?php echo htmlspecialchars($record['service_type']); ?></span>

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

        <button class="view-all-btn" onclick="window.location.href='maintenance.php'">View All Maintenance</button>

        
    </div>
    <?php

        // if (count($drivingDrivers) > 0) {

        //     foreach ($drivingDrivers as $driver) {
        //         echo '<div class="performance">
        //                     <i class="fa fa-user icon-bg"></i>
        //                     <p>' . htmlspecialchars($driver['driver']) . ' - Destination: ' . htmlspecialchars($driver['destination']) . '</p>
        //                 </div>';
        //     }
        // } else {

        //     echo '<div class="performance">
        //                 <i class="fa fa-info-circle icon-bg"></i>
        //                 <p>No active drivers currently on duty</p>
        //             </div>';
        // }
        ?>
<div class="card1">
    <div class="card-header">
        <i class="fas fa-chart-bar header-icon"></i>
        <h3>Maintenance Frequency</h3>
    </div>
    <div id="maintenance"></div>
</div>
    
</div>
<section class="analytics-section">

<div class="card3">
    <div class="card-header">
        <i class="fas fa-dollar-sign header-icon"></i>
        <h3>Cost Trends</h3>
    </div>
    <div id="costtrendchart"></div>
</div>
    <div class="card2">
    <div class="card-header">
        <i class="fas fa-truck-loading header-icon"></i>
        <h3>Number of Trips</h3>
    </div>
    <div id="tripnumber"></div>
</div>
    
            
</section>


<section class="calendar-section">
     <div class="card-header-calendar">
        
   

        <div class="calendar-legend">
            <i class="fas fa-calendar-alt header-icon"></i>
        <h3>Event Calendar</h3>
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
 </div>
        <div id="calendar"></div>
    </div>
</section>


<script>


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
            port: event.port,
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
    var formattedDate = dateObj.toLocaleString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' 
    });
    var modifiedDate = calEvent.modifiedat ? new Date(calEvent.modifiedat).toLocaleString() : 'N/A';

    $('#modal-container-no').text(calEvent.containerNo || 'N/A');

    $('#modal-status').text(calEvent.status || 'N/A')
        .removeClass()
        .addClass('status ' + (calEvent.status ? calEvent.status.toLowerCase().replace(/\s+/g, '-') : ''));
        $('#modal-origin').text(calEvent.port || 'N/A'); 
    $('#modal-client-name').text(calEvent.client || 'N/A');
    $('#modal-destination').text(calEvent.destination || 'N/A');
    
    $('#modal-plate-no').text(calEvent.plateNo || 'N/A');
    $('#modal-date').text(formattedDate);
    $('#modal-size').text(calEvent.size || 'N/A');
    $('#modal-cash-advance').text('₱ ' + (parseFloat(calEvent.cashAdvance) || 0).toLocaleString());
    
    $('#modal-driver').text(calEvent.driver || 'N/A');
    $('#modal-helper').text(calEvent.helper || 'N/A');
    $('#modal-dispatcher').text(calEvent.dispatcher || 'N/A');
    
    $('#modal-shipping-line').text(calEvent.shippingLine || 'N/A');
    $('#modal-consignee').text(calEvent.consignee || 'N/A');

    $('#modal-modified-by').text(calEvent.modifiedby || 'System');
    $('#modal-modified-at').text(modifiedDate);

    var trackingUrl = `tracking.php?trip_id=${calEvent.id}`;
    $('#track-delivery-btn').attr('href', trackingUrl);
    
    $('#tripDetailsModal').show();

    return false;
}
    });
});

</script>

<script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('toggleSidebarBtn');
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.getElementById('sidebar-backdrop'); 

            const openSidebar = () => {
                sidebar.classList.add('expanded');
                backdrop.classList.add('show');
            };


            const closeSidebar = () => {
                sidebar.classList.remove('expanded');
                backdrop.classList.remove('show');
            };


            toggleBtn.addEventListener('click', function (e) {
                e.stopPropagation(); 
                if (sidebar.classList.contains('expanded')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            backdrop.addEventListener('click', function () {
                closeSidebar();
            });

            document.addEventListener('click', function (e) {
                if (
                    sidebar.classList.contains('expanded') &&
                    !sidebar.contains(e.target) && 
                    !toggleBtn.contains(e.target)
                ) {
                    closeSidebar();
                }
            });
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

                        // Determine truck image - MODIFIED
                        var truckImage = '';
                        if (trip.truck_pic && trip.truck_pic.length > 0) {
                            truckImage = '<img src="data:image/jpeg;base64,' + trip.truck_pic + '" alt="Truck ' + trip.plate_no + '" class="truck-image" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">';
                        } else {
                            truckImage = '<i class="fa fa-automobile icon-bg2"></i>';
                        }

                        var row = `
                            <tr>
                                <td>${truckImage}</td>
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


$('.shipment-card').on('click', function() {
    const tripId = $(this).data('trip-id');

    fetchTripDetails(tripId).then(function(response) {
        if (response.success) {
            var trip = response.trip; 

            var dateObj = new Date(trip.date);
            var formattedDate = dateObj.toLocaleString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });
            var modifiedDate = trip.last_modified_at ? new Date(trip.last_modified_at).toLocaleString() : 'N/A';

            $('#modal-container-no').text(trip.container_no || 'N/A');


            $('#modal-status').text(trip.status || 'N/A')
                .removeClass()
                .addClass('status ' + (trip.status ? trip.status.toLowerCase().replace(/\s+/g, '-') : ''));
             $('#modal-origin').text(trip.port || 'N/A'); 
            $('#modal-client-name').text(trip.client || 'N/A');
            $('#modal-destination').text(trip.destination || 'N/A');

            $('#modal-plate-no').text(trip.plate_no || 'N/A');
            $('#modal-date').text(formattedDate);
            $('#modal-size').text(trip.size || 'N/A');
            $('#modal-cash-advance').text('₱ ' + (parseFloat(trip.cash_adv) || 0).toLocaleString());

            $('#modal-driver').text(trip.driver || 'N/A');
            $('#modal-helper').text(trip.helper || 'N/A');
            $('#modal-dispatcher').text(trip.dispatcher || 'N/A');

            $('#modal-shipping-line').text(trip.shipping_line || 'N/A'); 
            $('#modal-consignee').text(trip.consignee || 'N/A');

            $('#modal-modified-by').text(trip.last_modified_by || 'System');
            $('#modal-modified-at').text(modifiedDate);

            var trackingUrl = `tracking.php?trip_id=${trip.trip_id}`;
            $('#track-delivery-btn').attr('href', trackingUrl);

            // Show the modal
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

// Click outside to close modal
window.addEventListener('click', function(event) {
    const mainModal = document.getElementById('tripDetailsModal');
    const receiptModal = document.getElementById('receiptModal'); // Assuming you have a receipt modal with this ID

    // Prioritize closing receipt modal if it's open
    if (receiptModal && receiptModal.style.display === 'block') {
        if (event.target == receiptModal) {
            closeReceiptModal(); // Assumes you have a closeReceiptModal function
        }
        return; // Stop further processing to keep the main modal open
    }
    
    // If no receipt modal, handle the main modal
    if (mainModal && event.target == mainModal) {
        mainModal.style.display = 'none';
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
    <div class="modal-content redesigned">
        
        <div class="modal-header">
            <div class="header-content">
                <i class="fas fa-box-open header-icon"></i>
                <div>
                    <h3 class="modal-title">Trip Details</h3>
                    <span class="modal-subtitle" id="modal-container-no"></span>
                </div>
            </div>
            <div class="modal-header-status">

                <span class="summary-value"><span id="modal-status" class="status in-transit">En Route</span></span>
            </div>

            <span class="close">&times;</span>
        </div>

        <div class="modal-body">
            
           <div class="trip-summary-section">
    <div class="summary-item route-box">
        <div class="route-point">
            <span class="summary-label">Origin (Port)</span>
            <span class="summary-value" id="modal-origin"></span>
        </div>
        <div class="route-line-modal"></div>
        <div class="route-point destination">
            <span class="summary-label">Destination</span>
            <span class="summary-value" id="modal-destination"></span>
        </div>
    </div>
</div>


            <div class="trip-details-grid-revised">
                <div class="details-section">
                    <h4 class="section-title">Logistics Details</h4>
                    <div class="detail-item">
                        <i class="fas fa-truck detail-icon"></i>
                        <div class="detail-text">
                            <span class="detail-label">Plate No</span>
                            <span class="detail-value" id="modal-plate-no"></span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt detail-icon"></i>
                        <div class="detail-text">
                            <span class="detail-label">Date & Time</span>
                            <span class="detail-value" id="modal-date"></span>
                        </div>
                    </div>
                     <div class="detail-item">
                        <i class="fas fa-weight-hanging detail-icon"></i>
                        <div class="detail-text">
                            <span class="detail-label">Size</span>
                            <span class="detail-value" id="modal-size"></span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-money-bill-wave detail-icon"></i>
                        <div class="detail-text">
                            <span class="detail-label">Cash Advance</span>
                            <span class="detail-value" id="modal-cash-advance"></span>
                        </div>
                    </div>
                </div>

                <div class="details-section">
                    <h4 class="section-title">Personnel</h4>
                    <div class="detail-item">
                        <i class="fas fa-user-tie detail-icon"></i>
                        <div class="detail-text">
                            <span class="detail-label">Driver</span>
                            <span class="detail-value" id="modal-driver"></span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-user-cog detail-icon"></i>
                        <div class="detail-text">
                            <span class="detail-label">Helper</span>
                            <span class="detail-value" id="modal-helper"></span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-headset detail-icon"></i>
                        <div class="detail-text">
                            <span class="detail-label">Dispatcher</span>
                            <span class="detail-value" id="modal-dispatcher"></span>
                        </div>
                    </div>
                </div>

              <div class="details-section full-width">
    <h4 class="section-title">Client & Shipping Details</h4>
    <div class="detail-item-row">
        <div class="detail-item">
            <i class="fas fa-user-tie detail-icon"></i> <div class="detail-text">
                <span class="detail-label">Client</span>
                <span class="detail-value" id="modal-client-name"></span>
            </div>
        </div>

        <div class="detail-item">
            <i class="fas fa-building detail-icon"></i>
            <div class="detail-text">
                <span class="detail-label">Shipping Line</span>
                <span class="detail-value" id="modal-shipping-line"></span>
            </div>
        </div>

        <div class="detail-item">
            <i class="fas fa-dolly detail-icon"></i>
            <div class="detail-text">
                <span class="detail-label">Consignee</span>
                <span class="detail-value" id="modal-consignee"></span>
            </div>
        </div>
    </div> 
</div>

                 <div class="details-section system-info full-width">
                      <h4 class="section-title">System Information</h4>
                      <div class="detail-item-row">
                           <div class="detail-item">
                               <i class="fas fa-user-edit detail-icon"></i>
                               <div class="detail-text">
                                   <span class="detail-label">Last Modified By</span>
                                   <span class="detail-value" id="modal-modified-by"></span>
                               </div>
                           </div>
                           <div class="detail-item">
                               <i class="fas fa-clock detail-icon"></i>
                               <div class="detail-text">
                                   <span class="detail-label">Last Modified At</span>
                                   <span class="detail-value" id="modal-modified-at"></span>
                               </div>
                           </div>
                     </div>
                 </div>
            </div>
</div>
           <div class="modal-footer">
                <a href="#" id="track-delivery-btn" class="btn btn-track" target="_blank">
                    <i class="fas fa-map-marker-alt"></i> Track Delivery
                </a>
            </div>

    </div> </div>
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
    
    // Show loading immediately if coming from another page
    // this.checkForIncomingNavigation();
    this.setupNavigationInterception();
  },
  
  checkForIncomingNavigation() {
    // Check if we're coming from another page in the same site
    const referrer = document.referrer;
    const currentDomain = window.location.origin;
    
    // Also check sessionStorage for loading state
    const shouldShowLoading = sessionStorage.getItem('showAdminLoading');
    
    if ((referrer && referrer.startsWith(currentDomain)) || shouldShowLoading) {
      // Clear the flag
      sessionStorage.removeItem('showAdminLoading');
      
      // Show loading animation for incoming navigation
      this.show('Loading Page', 'Loading content...');
      
      // Simulate realistic loading progress
      let progress = 0;
      const progressInterval = setInterval(() => {
        progress += Math.random() * 25 + 10;
        this.updateProgress(Math.min(progress, 100));
        
        if (progress >= 100) {
          clearInterval(progressInterval);
          setTimeout(() => {
            this.hide();
          }, 600);
        }
      }, 180);
    }
  },
  
  show(title = 'Processing Request', message = 'Please wait while we complete this action...') {
    if (!this.loadingEl) return;
    
    this.titleEl.textContent = title;
    this.messageEl.textContent = message;
    
    // Reset progress
    this.updateProgress(0);
    
    this.loadingEl.style.display = 'flex';
    setTimeout(() => {
      this.loadingEl.classList.add('active');
    }, 50);
  },
  
  hide() {
    if (!this.loadingEl) return;
    
    this.loadingEl.classList.remove('active');
    setTimeout(() => {
      this.loadingEl.style.display = 'none';
    }, 800);
  },
  
  updateProgress(percent) {
    if (this.progressBar) {
      this.progressBar.style.width = `${percent}%`;
    }
    if (this.progressText) {
      this.progressText.textContent = `${Math.round(percent)}%`;
    }
  },
  
  setupNavigationInterception() {
    document.addEventListener('click', (e) => {
      // Skip if click is inside SweetAlert modal, regular modals, or calendar
      if (e.target.closest('.swal2-container, .swal2-popup, .swal2-modal, .modal, .modal-content, .fc-event, #calendar')) {
        return;
      }
      
      const link = e.target.closest('a');
      if (link && !link.hasAttribute('data-no-loading') && 
          link.href && !link.href.startsWith('javascript:') &&
          !link.href.startsWith('#') && !link.href.startsWith('mailto:') &&
          !link.href.startsWith('tel:')) {
        
        // Only intercept internal links
        try {
          const linkUrl = new URL(link.href);
          const currentUrl = new URL(window.location.href);
          
          if (linkUrl.origin !== currentUrl.origin) {
            return; // Let external links work normally
          }
          
          // Skip if it's the same page
          if (linkUrl.pathname === currentUrl.pathname) {
            return;
          }
          
        } catch (e) {
          return; // Invalid URL, let it work normally
        }
        
        e.preventDefault();
        
        // Set flag for next page
        sessionStorage.setItem('showAdminLoading', 'true');
        
        const loading = this.startAction(
          'Loading Page', 
          `Preparing ${link.textContent.trim() || 'page'}...`
        );
        
        let progress = 0;
        const progressInterval = setInterval(() => {
          progress += Math.random() * 15 + 8;
          if (progress >= 85) {
            clearInterval(progressInterval);
            progress = 90; // Stop at 90% until page actually loads
          }
          loading.updateProgress(Math.min(progress, 90));
        }, 150);
        
        // Minimum delay to show animation
        const minLoadTime = 1200;
        
        setTimeout(() => {
          // Complete the progress bar
          loading.updateProgress(100);
          setTimeout(() => {
            window.location.href = link.href;
          }, 300);
        }, minLoadTime);
      }
    });

    // Handle form submissions
    document.addEventListener('submit', (e) => {
      // Skip if form is inside SweetAlert or modal
      if (e.target.closest('.swal2-container, .swal2-popup, .modal')) {
        return;
      }
      
      // Only show loading for forms that will cause page navigation
      const form = e.target;
      if (form.method && form.method.toLowerCase() === 'post' && form.action) {
        const loading = this.startAction(
          'Submitting Form', 
          'Processing your data...'
        );
        
        setTimeout(() => {
          loading.complete();
        }, 2000);
      }
    });
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', () => {
      this.show('Loading Page', 'Loading previous page...');
      setTimeout(() => {
        this.hide();
      }, 800);
    });
  },
  
  startAction(actionName, message) {
    this.show(actionName, message);
    return {
      updateProgress: (percent) => this.updateProgress(percent),
      updateMessage: (message) => {
        if (this.messageEl) {
          this.messageEl.textContent = message;
          this.messageEl.style.opacity = 0;
          setTimeout(() => {
            this.messageEl.style.opacity = 1;
            this.messageEl.style.transition = 'opacity 0.5s ease';
          }, 50);
        }
      },
      complete: () => {
        this.updateProgress(100);
        this.updateMessage('Done!');
        setTimeout(() => this.hide(), 800);
      }
    };
  },
  
  // Public methods for manual control
  showManual: function(title, message) {
    this.show(title, message);
  },
  
  hideManual: function() {
    this.hide();
  },
  
  setProgress: function(percent) {
    this.updateProgress(percent);
  }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  AdminLoading.init();
  
  // Add smooth transition to the GIF
  const loadingGif = document.querySelector('.loading-gif');
  if (loadingGif) {
    loadingGif.style.transition = 'opacity 0.7s ease 0.3s';
  }
  
  // Hide loading on page show (handles browser back button)
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      // Page was loaded from cache (back/forward button)
      setTimeout(() => {
        AdminLoading.hideManual();
      }, 500);
    }
  });
});

// Handle page unload
// window.addEventListener('beforeunload', () => {
//   // Set flag that we're navigating
//   sessionStorage.setItem('showAdminLoading', 'true');
// });

// Export for global access (optional)
window.AdminLoading = AdminLoading;


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
    
// Replace the existing cost trends chart initialization with this code

// Global variables for the cost trends functionality
let costTrendsChart = null;
let currentView = 'current'; // 'current', 'monthly', 'yearly'

// Initialize cost trends chart
function initializeCostTrendsChart() {
    loadCostTrendsData();
    
    // Add view toggle buttons
    addCostTrendsControls();
}

function addCostTrendsControls() {
        const costTrendsCard = document.querySelector('#costtrendchart').closest('.card3');

    const header = costTrendsCard.querySelector('h3');
    
    // Create control buttons
    const controlsDiv = document.createElement('div');
    controlsDiv.style.cssText = `
        display: flex; 
        gap: 10px; 
        margin: 10px 0; 
        flex-wrap: wrap;
    `;
    
    const buttonStyle = `
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: #f8f9fa;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
    `;
    
    const activeButtonStyle = `
        background: #B82132;
        color: white;
        border-color: #B82132;
    `;
    
    const buttons = [
        { id: 'current', text: 'Current Year' },
        { id: 'monthly', text: 'Monthly View' },
        { id: 'yearly', text: 'Yearly View' }
    ];
    
    buttons.forEach(btn => {
        const button = document.createElement('button');
        button.textContent = btn.text;
        button.style.cssText = buttonStyle;
        button.onclick = () => switchCostView(btn.id);
        button.id = `btn-${btn.id}`;
        controlsDiv.appendChild(button);
    });
    
    // Insert controls after the header
    header.insertAdjacentElement('afterend', controlsDiv);
    
    // Set active button
    document.getElementById('btn-current').style.cssText = buttonStyle + activeButtonStyle;
}

function switchCostView(view) {
    currentView = view;
    
    // Update active button
    document.querySelectorAll('[id^="btn-"]').forEach(btn => {
        btn.style.cssText = `
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        `;
    });
    
    document.getElementById(`btn-${view}`).style.cssText = `
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: #B82132;
        color: white;
        border-color: #B82132;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
    `;
    
    // Load appropriate data
    switch(view) {
        case 'current':
            loadCostTrendsData();
            break;
        case 'monthly':
            loadMonthlyTrendsData();
            break;
        case 'yearly':
            loadYearlyTrendsData();
            break;
    }
}

function loadCostTrendsData() {
    fetch('include/handlers/analytics_handler.php?action=cost_trends')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderCostTrendsChart(data, 'donut', 'Cost Distribution - Current Year');
            } else {
                console.error('Error loading cost trends:', data.error);
                // Fallback to original static data if there's an error
                renderDefaultCostChart();
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            renderDefaultCostChart();
        });
}

function loadMonthlyTrendsData() {
    fetch('include/handlers/analytics_handler.php?action=monthly_trends')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderMonthlyChart(data);
            } else {
                console.error('Error loading monthly trends:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

function loadYearlyTrendsData() {
    fetch('include/handlers/analytics_handler.php?action=yearly_trends')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderYearlyChart(data);
            } else {
                console.error('Error loading yearly trends:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

function renderCostTrendsChart(data, chartType = 'donut', title = 'Cost Trends') {
    if(costTrendsChart) {
        costTrendsChart.destroy();
    }
    
    const options = {
        series: data.data,
        chart: {
            width: 580,
            type: chartType,
        },
        labels: data.labels,
        plotOptions: {
            pie: {
                startAngle: -90,
                endAngle: 270
            }
        },
        dataLabels: {
            enabled: false
        },
        fill: {
            type: 'gradient',
        },
        legend: {
            formatter: function (val, opts) {
                let series = opts.w.globals.series;
                let total = series.reduce((a, b) => a + b, 0);
                let value = series[opts.seriesIndex];
                let percent = ((value / total) * 100).toFixed(1);
                return `${val} - ${percent}% (₱${value.toLocaleString()})`;
            }
        },
        tooltip: {
            y: {
                formatter: function (value, opts) {
                    let series = opts.w.globals.series;
                    let total = series.reduce((a, b) => a + b, 0);
                    let percent = ((value / total) * 100).toFixed(1);
                    return `₱${value.toLocaleString()} (${percent}%)`;
                }
            }
        },
        title: {
            text: title
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };
    
    costTrendsChart = new ApexCharts(document.querySelector("#costtrendchart"), options);
    costTrendsChart.render();
}

function renderMonthlyChart(data) {
    if(costTrendsChart) {
        costTrendsChart.destroy();
    }
    
    // Prepare data for line chart
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                    'July', 'August', 'September', 'October', 'November', 'December'];
    
    const series = [];
    const colors = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'];
    
    data.expenseTypes.forEach((type, index) => {
        const monthlyAmounts = months.map(month => {
            return data.monthlyData[month] && data.monthlyData[month][type] 
                ? data.monthlyData[month][type] 
                : 0;
        });
        
        series.push({
            name: ucfirst(type),
            type: 'line',
            data: monthlyAmounts
        });
    });
    
    const options = {
        series: series,
        chart: {
            height: 350,
            type: 'line',
            stacked: false,
            width: 580,
        },
        stroke: {
            width: [2, 2, 2, 2, 2],
            curve: 'smooth'
        },
        plotOptions: {
            bar: {
                columnWidth: '50%'
            }
        },
        fill: {
            opacity: [0.85, 0.25, 1],
            gradient: {
                inverseColors: false,
                shade: 'light',
                type: "vertical",
                opacityFrom: 0.85,
                opacityTo: 0.55,
                stops: [0, 100, 100, 100]
            }
        },
        labels: months,
        markers: {
            size: 0
        },
        xaxis: {
            type: 'category'
        },
        yaxis: {
            title: {
                text: 'Amount (₱)',
            },
            labels: {
                formatter: function (val) {
                    return '₱' + val.toLocaleString();
                }
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y) {
                    if (typeof y !== "undefined") {
                        return "₱" + y.toLocaleString();
                    }
                    return y;
                }
            }
        },
        title: {
            text: 'Monthly Cost Trends - ' + new Date().getFullYear()
        },
        colors: colors
    };
    
    costTrendsChart = new ApexCharts(document.querySelector("#costtrendchart"), options);
    costTrendsChart.render();
}

function renderYearlyChart(data) {
    if(costTrendsChart) {
        costTrendsChart.destroy();
    }
    
    // Get all years from the data
    const years = Object.keys(data.yearlyData).sort();
    
    const series = [];
    const colors = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'];
    
    data.expenseTypes.forEach((type, index) => {
        const yearlyAmounts = years.map(year => {
            return data.yearlyData[year] && data.yearlyData[year][type] 
                ? data.yearlyData[year][type] 
                : 0;
        });
        
        series.push({
            name: ucfirst(type),
            type: 'column',
            data: yearlyAmounts
        });
    });
    
    const options = {
        series: series,
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            width: 580,
        },
        plotOptions: {
            bar: {
                horizontal: false,
                dataLabels: {
                    total: {
                        enabled: true,
                        style: {
                            fontSize: '13px',
                            fontWeight: 900
                        }
                    }
                }
            },
        },
        stroke: {
            width: 1,
            colors: ['#fff']
        },
        title: {
            text: 'Yearly Cost Trends'
        },
        xaxis: {
            categories: years,
            labels: {
                formatter: function (val) {
                    return val;
                }
            }
        },
        yaxis: {
            title: {
                text: 'Amount (₱)'
            },
            labels: {
                formatter: function (val) {
                    return '₱' + val.toLocaleString();
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return '₱' + val.toLocaleString();
                }
            }
        },
        fill: {
            opacity: 1
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            offsetX: 40
        },
        colors: colors
    };
    
    costTrendsChart = new ApexCharts(document.querySelector("#costtrendchart"), options);
    costTrendsChart.render();
}

function renderDefaultCostChart() {
    // Fallback to your original static chart if data loading fails
    const options = {
        series: [44, 55, 41, 17, 15],
        chart: {
            width: 580,
            type: 'donut',
        },
        labels: ['Fuel', 'Toll Gate', 'Food', 'Emergency', 'Others'],
        plotOptions: {
            pie: {
                startAngle: -90,
                endAngle: 270
            }
        },
        dataLabels: {
            enabled: false
        },
        fill: {
            type: 'gradient',
        },
        legend: {
            formatter: function (val, opts) {
                let series = opts.w.globals.series;
                let total = series.reduce((a, b) => a + b, 0);
                let value = series[opts.seriesIndex];
                let percent = ((value / total) * 100).toFixed(1);
                return `${val} - ${percent}%`;
            }
        },
        tooltip: {
            y: {
                formatter: function (value, opts) {
                    let series = opts.w.globals.series;
                    let total = series.reduce((a, b) => a + b, 0);
                    let percent = ((value / total) * 100).toFixed(1);
                    return `${percent}%`;
                }
            }
        },
        title: {
            text: 'Cost Trends (Demo Data)'
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };
    
    if(costTrendsChart) {
        costTrendsChart.destroy();
    }
    
    costTrendsChart = new ApexCharts(document.querySelector("#costtrendchart"), options);
    costTrendsChart.render();
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure ApexCharts is ready
    setTimeout(() => {
        initializeCostTrendsChart();
          initializeMaintenanceFrequencyChart();
          initializeTripCountChart(); 
    }, 500);
});

  
   function initializeTripCountChart() {
    // Grabs data from our analytics handler to show the trip trends.
    fetch('include/handlers/analytics_handler.php?action=get_completed_trip_counts')
        .then(response => response.json())
        .then(apiData => {
            if (apiData.success) {
                var options = {
                    series: [{
                        name: "Completed Trips",
                        data: apiData.data
                    }],
                    chart: {
                        type: 'area',
                        height: 350,
                        zoom: {
                            enabled: false
                        },
                        toolbar: {
                            show: false // A cleaner look for the dashboard
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    xaxis: {
                        categories: apiData.labels,
                    },
                    yaxis: {
                        title: {
                            text: 'Number of Trips'
                        }
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.4,
                            stops: [0, 90, 100]
                        }
                    },
                    tooltip: {
                        x: {
                            format: 'MMM yyyy'
                        },
                    },
                };

                var tripChart = new ApexCharts(document.querySelector("#tripnumber"), options);
                tripChart.render();
            } else {
                console.error('Failed to load trip count data.');
                document.querySelector("#tripnumber").innerHTML = '<p style="text-align:center; padding-top: 20px; color: #888;">Could not load trip data.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching trip count data:', error);
            document.querySelector("#tripnumber").innerHTML = '<p style="text-align:center; padding-top: 20px; color: #888;">Error loading chart data.</p>';
        });
}

    var options4 = {
        series: [{
            name: 'TEAM A',
            type: 'column',
            data: [23, 11, 22, 27, 13, 22, 37, 21, 44, 22, 30]
        }, {
            name: 'TEAM B',
            type: 'area',
            data: [44, 55, 41, 67, 22, 43, 21, 41, 56, 27, 43]
        }, {
            name: 'TEAM C',
            type: 'line',
            data: [30, 25, 36, 30, 45, 35, 64, 52, 59, 36, 39]
        }],
        chart: {
            height: 350,
            type: 'line',
            stacked: false,
            width:1200,
        },
        stroke: {
            width: [0, 2, 5],
            curve: 'smooth'
        },
        fill: {
            opacity: [0.85, 0.25, 1],
            gradient: {
                inverseColors: false,
                shade: 'light',
                type: "vertical",
                opacityFrom: 0.85,
                opacityTo: 0.55,
                stops: [0, 100, 100, 100]
            }
        },
        labels: ['01/01/2003', '02/01/2003', '03/01/2003', '04/01/2003', '05/01/2003', '06/01/2003', '07/01/2003',
            '08/01/2003', '09/01/2003', '10/01/2003', '11/01/2003'
        ],
        markers: {
            size: 0
        },
        xaxis: {
            type: 'datetime'
        },
        yaxis: {
            title: {
                text: 'Points',
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y) {
                    if (typeof y !== "undefined") {
                        return y.toFixed(0) + " points";
                    }
                    return y;
                }
            }
        }
    };

    var operational = new ApexCharts(document.querySelector("#operational"), options4);
    operational.render();

   function initializeMaintenanceFrequencyChart() {
    fetch('include/handlers/maintenance_handler.php?action=getMaintenanceFrequency')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // If we got the data, set up the chart options
                var options = {
                    series: data.series, // using dynamic series data from the server
                    chart: {
                        type: 'bar',
                        height: 350,
                        stacked: true,
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            dataLabels: {
                                total: {
                                    enabled: true,
                                    offsetX: 0,
                                    style: {
                                        fontSize: '13px',
                                        fontWeight: 900
                                    }
                                }
                            }
                        },
                    },
                    stroke: {
                        width: 1,
                        colors: ['#fff']
                    },
                    xaxis: {
                        categories: data.categories, // using dynamic year categories from the server
                        labels: {
                            formatter: function (val) {
                                return val;
                            }
                        }
                    },
                    yaxis: {
                        title: {
                            text: undefined
                        },
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val; // keeps the tooltip simple
                            }
                        }
                    },
                    fill: {
                        opacity: 1
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        offsetX: 40
                    }
                };
                
                // create and render the chart
                var maintenanceChart = new ApexCharts(document.querySelector("#maintenance"), options);
                maintenanceChart.render();
            } else {
                console.error("Failed to load maintenance frequency data:", data.message);
                document.querySelector("#maintenance").innerHTML = '<p style="text-align: center; color: red;">Could not load chart data.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching maintenance frequency data:', error);
            document.querySelector("#maintenance").innerHTML = '<p style="text-align: center; color: red;">Error fetching chart data.</p>';
        });
}


</script>
 


</body>
</html>