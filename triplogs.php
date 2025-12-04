<?php
    require_once __DIR__ . '/include/check_access.php';
    checkAccess(); 


    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Trip logs</title>
        <link rel="stylesheet" href="include/css/sidenav.css">
        <link rel="stylesheet" href="include/css/loading.css">
        <link rel="stylesheet" href="include/css/triplogs.css">


        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.0/css/all.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>
        <style>
        @keyframes continuousPulse {
            0% {
                background-color: #d4edda; 
            }
            15% {
                background-color: #f0fff4; 
            }
            30% {
                background-color: #d4edda;
            }
            45% {
                background-color: #f0fff4;
            }
            60% {
                background-color: #d4edda;
            }
            100% {
                background-color: transparent;
            }
        }

        .highlight-continuous-pulse {
            animation: continuousPulse 10s ease-in-out forwards;
        }
    </style>

    <body>
        <?php
        require 'include/handlers/dbhandler.php';
        require 'include/handlers/triplogstats.php';


    $tripStats = getTripStatistics($conn);
        

    
$sql = "SELECT 
          t.*,
            tr.plate_no as truck_plate_no, 
            tr.capacity as truck_capacity,
            d.name as driver,
            d.driver_id, d.firebase_uid,
            h.name as helper,
            disp.name as dispatcher,
            c.name as client,
            p.name as port,  
            dest.name as destination,
            sl.name as shipping_line,
            cons.name as consignee,
            al.edit_reason as edit_reason,
            al.modified_by as last_modified_by,
            al.modified_at as last_modified_at,
            COALESCE(te.cash_advance, 0) as cash_advance,
            COALESCE(te.additional_cash_advance, 0) as additional_cash_advance,
            tr.plate_no,  
            t.trip_date   
        FROM trips t
        LEFT JOIN truck_table tr ON t.truck_id = tr.truck_id
        LEFT JOIN drivers_table d ON t.driver_id = d.driver_id
        LEFT JOIN helpers h ON t.helper_id = h.helper_id
        LEFT JOIN dispatchers disp ON t.dispatcher_id = disp.dispatcher_id
        LEFT JOIN clients c ON t.client_id = c.client_id
        LEFT JOIN ports p ON t.port_id = p.port_id  
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
    'plateNo' => $row['truck_plate_no'],
    'date' => $row['trip_date'],  
    'driver' => $row['driver'],
    'driver_id' => $row['driver_id'],
    'firebase_uid' => $row['firebase_uid'],
    'helper' => $row['helper'],
    'dispatcher' => $row['dispatcher'],
    'containerNo' => $row['container_no'],
    'client' => $row['client'],
    'port' => $row['port'],
    'destination' => $row['destination'],
    'shippingLine' => $row['shipping_line'],
    'consignee' => $row['consignee'],
    'size' => $row['fcl_status'],
    'cashAdvance' => $row['cash_advance'],
    'additionalCashAdvance' => $row['additional_cash_advance'],
    'status' => $row['status'],
    'modifiedby' => $row['last_modified_by'],
    'modifiedat' => $row['last_modified_at'],
    'truck_plate_no' => $row['truck_plate_no'],
    'truck_capacity' => $row['truck_capacity'],
    'edit_reasons' => $row['edit_reason'],
    'fcl_status' => $row['fcl_status']  
];
    }
}

   
$driverQuery = "SELECT d.driver_id, d.name, t.plate_no as truck_plate_no, t.capacity, d.assigned_truck_id, d.checked_in_at, d.penalty_until 
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
    'truck_plate_no' => $driverRow['truck_plate_no'],
    'assigned_truck_id' => $driverRow['assigned_truck_id'],
    'checked_in_at' => $driverRow['checked_in_at'], 
    'penalty_until' => $driverRow['penalty_until']  
];
        }
    }

        
        $eventsDataJson = json_encode($eventsData);
        $driversDataJson = json_encode($driversData);
        ?>

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

     <div class="profile" onclick="window.location.href='admin_profile.php'" style="cursor: pointer;"> 
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

 <h3 class="title"><i class="fa-solid fa-calendar-days"></i>Trip Management</h3>
  <div class="stats-container-wrapper">
    <div class="stats-container" id="statsContainer">
        <div class="stat-card">
            <div class="stat-icon icon-pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-enroute">
                <i class="fas fa-truck-moving"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['enroute']; ?></div>
                <div class="stat-label">En Route</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-completed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-cancelled">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['cancelled']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-total">
                <i class="fas fa-list-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['total']; ?></div>
                <div class="stat-label">Total Trips</div>
            </div>
        </div>
</div>
</div>
  <div class="main-container">   
        <div class="calendar-container">
            <section class="calendar-section">
                <div class="calendar-controls">
                    <button id="addScheduleBtnTable"> 
                        <i class="fa-solid fa-calendar-plus" style="margin-right:5px;"></i>Add Schedule
                    </button>

                    <button id="expenseSummaryBtn">
    <i class="fa-solid fa-file-invoice-dollar" style="margin-right:5px;"></i>Expense Summary
</button>
                    
                    <div class="status-filter-container">
                        <select id="statusFilter">
                            <option value="" disabled selected>Status Filter</option>
                            <option value="all">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="En Route">En Route</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="deleted">Deleted</option>
                            <option value="today">Trips Today</option> 
                        </select>
                         <div class="date-filter-container">
    <div class="date-input-group">
        <label for="dateFrom">From:</label>
        <input type="date" id="dateFrom" class="date-input">
    </div>
    <div class="date-input-group">
        <label for="dateTo">To:</label>
        <input type="date" id="dateTo" class="date-input">
    </div>
    <button id="resetDateFilter" class="clear-date-btn">
         <i class="fas fa-times"></i> Clear</button>
</div>
                        <div class="search-container">
                            <i class="fa fa-search"></i>
                              <input type="text" id="searchInput" placeholder="Search trips..." onkeyup="searchTrips()">
                        </div>
                    </div>
                    
                    <div class="filter-toggle-container">
                        <div class="toggle-btns">
                            <button id="calendarViewBtn" class="toggle-btn active" data-tooltip="Calendar View"> 
                                <i class="fa fa-calendar"></i>
                            </button>
                            <button id="tableViewBtn" class="toggle-btn" data-tooltip="Table View">  
                                <i class="fa fa-tasks"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div id="calendar"></div>
            </section>
            
          
        </div>
<!-- modal for the scheduled trips in the calendar -->
<div id="eventModal" class="modal">
    <div class="modal-content redesigned">
        <div class="modal-header">
            <div class="header-content">
                <i class="fas fa-box-open header-icon"></i>
                <div>
                    <h3 class="modal-title">Trip Details <span id="modal-trip-id"></span></h3>
                       <span class="summary-value"><span id="modal-status" class="status"></span></span>
                </div>
            </div>
           
            <div class="event-modal-actions">
                <div class="dropdown">
                    <button class="dropdown-btn" data-tooltip="Actions">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-content">
                        <button class="dropdown-item view-location" id="eventModalViewLocationBtn" style="display: none;">
                            <i class="fas fa-map-marker-alt"></i> View Location
                        </button>
                        <button class="dropdown-item edit" id="eventModalEditBtn">
                            <i class="fas fa-edit"></i> Edit Trip
                        </button>
                        <button class="dropdown-item view-expenses">
                            <i class="fas fa-money-bill-wave"></i> View Expenses
                        </button>
                        <button class="dropdown-item view-checklist">
                            <i class="fas fa-clipboard-check"></i> Driver Checklist
                        </button>
                        <a href="#" class="dropdown-item generate-report" target="_blank">
                            <i class="fas fa-file-alt"></i> Generate Report
                        </a>
                        <button class="dropdown-item view-reasons" id="eventModalHistoryBtn">
                            <i class="fas fa-history"></i> Edit Remarks
                        </button>
                        <button class="dropdown-item delete" id="eventModalDeleteBtn">
                            <i class="fas fa-trash-alt"></i> Delete Trip
                        </button>
                        <button class="dropdown-item cancel-trip">
                            <i class="fas fa-ban"></i> Cancel Trip
                        </button>
                    </div>
                </div>
            </div>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>

        <div class="modal-body">
            <div class="trip-summary-section">
                <div class="summary-item route-box-vertical">
                    <div class="route-line-modal-top"></div>
                    <div class="route-points-container">
                        <div class="route-point">
                            <span class="summary-label">Origin (Port)</span>
                            <span class="summary-value" id="modal-origin"></span>
                        </div>
                        <div class="route-point destination">
                            <span class="summary-label">Destination</span>
                            <span class="summary-value" id="modal-destination"></span>
                        </div>
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
                        <i class="fas fa-weight-hanging detail-icon"></i>
                        <div class="detail-text">
                            <span class="detail-label">Container No.</span>
                             <span class="detail-value" id="modal-container-no"></span>
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
                    <h4 class="section-title">Financial Details</h4>
                    <div class="detail-item-row">
                        <div class="detail-item">
                            <i class="fas fa-money-bill-wave detail-icon"></i>
                            <div class="detail-text">
                                <span class="detail-label">Cash Advance</span>
                                <span class="detail-value" id="modal-cash-advance"></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-plus-circle detail-icon"></i>
                            <div class="detail-text">
                                <span class="detail-label">Additional Cash</span>
                                <span class="detail-value" id="modal-additional-cash"></span>
                            </div>
                        </div>

                        
                    </div>
                </div>

                <div class="details-section full-width">
                    <h4 class="section-title">Client & Shipping Details</h4>
                    <div class="detail-item-row">
                        <div class="detail-item">
                            <i class="fas fa-user-tie detail-icon"></i>
                            <div class="detail-text">
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
    </div>
</div>

     <!-- Edit Modal -->
    <div id="editModal" class="modal">
    <div class="modal-content" style="width: 90%; max-width: 700px; max-height: 90vh; overflow-y: scroll; overflow-x:hidden;">
        <div class="modal-header2">
        <span class="close">&times;</span>
        <h2 style="margin-top: 20;">Edit Trip <span id="editModalTripId" style="color: #6c757d; font-weight: normal;"></span></h2>
        </div>
    <form id="editForm">
                <input type="hidden" id="editEventId" name="eventId">
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <fieldset style="flex: 1; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
                    <legend style="font-weight: bold;">Shipment Information</legend>
                      <label for="editEventDate">Date & Time:</label>
                <input type="datetime-local" id="editEventDate" name="editEventDate" required style="width: 100%;">
                <label for="editEventSize">Container Size:</label>
                <select id="editEventSize" name="eventSize" required style="width: 100%;">
                    <option value="" >Select Size</option>
                    <option value="20ft">20ft</option>
                    <option value="40ft">40ft</option>
                    
                </select>

                <label for="editEventPlateNo">Plate No.:</label>
                <input type="text" id="editEventPlateNo" name="eventPlateNo" required style="width: 100%;">

             

                <label for="editEventDriver">Driver:</label>
                <select id="editEventDriver" name="eventDriver" required style="width: 100%;">
                    <option value=""  disabled selected>Select Driver</option>
                </select>

                <label for="editEventHelper">Helper:</label>
                <select id="editEventHelper" name="eventHelper" required style="width: 100%;">
                    <option value="" disabled selected>Select Helper</option>
                </select>

                <label for="editEventFCL">FCL Status:</label>
            <select id="editEventFCL" name="eventFCL" required style="width: 100%;">
                <option value="" disabled selected>Select FCL Status</option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 20px;">
                <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
                    <legend style="font-weight: bold;">Dispatcher & Container Information</legend>
                <label for="editEventDispatcher">Dispatcher:</label>
                <select id="editEventDispatcher" name="eventDispatcher" required style="width: 100%;">
                    <option value=""  disabled selected>Select Dispatcher</option>
                </select>

                <label for="editEventContainerNo">Container No.:</label>
                <input type="text" id="editEventContainerNo" name="eventContainerNo" required style="width: 100%;">
                </legend>
                </fieldset>
                <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
                    <legend style="font-weight: bold;">Client & Destination</legend>
                <label for="editEventClient">Client:</label>
                <select id="editEventClient" name="eventClient" required style="width: 100%;">
                    <option value=""  disabled selected >Select Client</option>
                </select>


                <label for="editEventPort">Port:</label>
                <select id="editEventPort" name="eventPort" required style="width: 100%;">
                    <option value=""  disabled selected>Select Port</option>
                </select>

                <label for="editEventDestination">Destination:</label>
                <select id="editEventDestination" name="eventDestination" required style="width: 100%;">
                    <option value=""  disabled selected>Select Destination</option>
                </select>
             
                <label for="editEventStatus">Status:</label>
                <select id="editEventStatus" name="eventStatus" required style="width: 100%;">
                    <option value="Pending">Pending</option>
                    <option value="En Route">En Route</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                
                </select>
                   </legend>
                </fieldset>
            </div>

            <div style="display: flex; flex-direction: column; gap: 20px;">
                <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
                    <legend style="font-weight: bold;">Shipping Information</legend>
                <label for="editEventShippingLine">Shipping Line:</label>
                <select id="editEventShippingLine" name="eventShippingLine" required style="width: 100%;">
                    <option value=""  disabled selected>Select Shipping Line</option>
                </select>
   
                <label for="editEventConsignee">Consignee:</label>
                <select id="editEventConsignee" name="eventConsignee" required style="width: 100%;">
                    <option value=""  disabled selected>Select Consignee</option>
                </select>
                 </legend>
                </fieldset>
            </div>

            <div style="display: flex; flex-direction: column; gap: 20px;">
    <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
        <legend style="font-weight: bold;">Financial Information</legend>
        <div>
            <label for="editEventCashAdvance">Cash Advance:</label>
            <input type="number" id="editEventCashAdvance" name="eventCashAdvance" 
                   min="2000" step="0.01" placeholder="2000.00" style="width: 100%;">
        </div>
        <div id="editAdditionalCashContainer" style="display: none;">
            <label for="editEventAdditionalCashAdvance">Additional Cash:</label>
            <input type="number" id="editEventAdditionalCashAdvance" name="eventAdditionalCashAdvance" 
                   min="0" step="0.01" placeholder="0.00" style="width: 100%;">
        </div>
        
    </fieldset>
</div>

            <div class="edit-reasons-section" style="grid-column: span 2; margin-top: 15px; padding: 15px; border-radius: 5px; border: 1px solid #ddd; width: 95%;">
                <h4 style="margin-top: 0; margin-bottom: 15px; color: #333;">Reason for Edit</h4>
                <p style="margin-top: 0; margin-bottom: 10px; color: #666;">Select all that apply:</p>
                
                <div class="reasons-container" style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
                    <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                        <label for="reason1" style="margin: 0; cursor: pointer; flex: 1;">Changed schedule as per client request</label>
                        <input type="checkbox" name="editReason" value="Changed schedule as per client request" id="reason1" style="margin-left: 10px;">
                    </div>
                    
                    <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                        <label for="reason2" style="margin: 0; cursor: pointer; flex: 1;">Updated driver assignment due to availability</label>
                        <input type="checkbox" name="editReason" value="Updated driver assignment due to availability" id="reason2" style="margin-left: 10px;">
                    </div>
                    
                    <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                        <label for="reason3" style="margin: 0; cursor: pointer; flex: 1;">Modified vehicle assignment for capacity requirements</label>
                        <input type="checkbox" name="editReason" value="Modified vehicle assignment for capacity requirements" id="reason3" style="margin-left: 10px;">
                    </div>
                    
                    <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                        <label for="reason4" style="margin: 0; cursor: pointer; flex: 1;">Adjusted destination based on new instructions</label>
                        <input type="checkbox" name="editReason" value="Adjusted destination based on new instructions" id="reason4" style="margin-left: 10px;">
                    </div>
                    
                    <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                        <label for="reason5" style="margin: 0; cursor: pointer; flex: 1;">Updated container details for accuracy</label>
                        <input type="checkbox" name="editReason" value="Updated container details for accuracy" id="reason5" style="margin-left: 10px;">
                    </div>
                    
                    <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                        <label for="reason6" style="margin: 0; cursor: pointer; flex: 1;">Updated expense information</label>
                        <input type="checkbox" name="editReason" value="Updated expense information" id="reason6" style="margin-left: 10px;">
                    </div>
                    
                    <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                        <label for="reason7" style="margin: 0; cursor: pointer; flex: 1;">Other (please specify below)</label>
                        <input type="checkbox" name="editReason" value="Other" id="reason7" style="margin-left: 10px;">
                    </div>
                    
                    <div class="other-reason" style="width: 90%;" id="otherReasonContainer">
                        <label for="otherReasonText" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Specify other reason:</label>
                        <textarea id="otherReasonText" name="otherReasonText" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; min-height: 80px;"></textarea>
                    </div>
                </div>
            </div>

            <div class="buttons" style="grid-column: span 2; display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; border-top: 1px solid #eee;">
             
                <button type="button" class="close-btn cancel-btn" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit" class="save-btn" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Changes</button>
            </div>
        </form>
    </div>
</div>
 <div id="expensesModal" class="modal">
        <div class="expensemodal-content">
            <div class="modal-header">
                <span class="expenseclose" onclick="closeModal()">&times;</span>
                <h3 class="expensemodal-header">Trip Expense Report</h3>

            </div>
            
            <div class="modal-body">

                <div class="info-section">
                    <h4 class="section-title">
                        <div class="section-icon"><i class="fas fa-route"></i></div>
                        Trip Information
                    </h4>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Plate Number</div>
                            <div class="detail-value" id="expensePlateNo"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Trip Date</div>
                            <div class="detail-value" id="expenseTripDate"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Container No</div>
                            <div class="detail-value" id="expenseContainerNo"></div>
                        </div>
                         <div class="detail-item">
                            <div class="detail-label">Driver</div>
                            <div class="detail-value" id="expenseDriverName"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Helper</div>
                            <div class="detail-value" id="expenseHelperName"></div>
                        </div>
                         <div class="detail-item">
                            <div class="detail-label">Destination</div>
                            <div class="detail-value" id="expenseDestination"></div>
                        </div>
                    </div>
                </div>

                

                <div class="funds-card">
                    <div class="funds-header">
                        <div class="funds-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h4 class="funds-title">Initial Funds</h4>
                    </div>
                    <div class="funds-item">
                        <strong>Cash Advance:</strong> 
                        <span id="expenseCashAdvance"></span>
                    </div>
                    <div class="funds-item">
                        <strong>Additional Cash:</strong> 
                        <span id="expenseAdditionalCash"></span>
                    </div>
                    
                    <div class="funds-total">
                        <strong>Total Initial Funds:</strong> 
                        <span id="totalInitialFunds"></span>
                    </div>
                </div>

                <div class="info-section">
                    <h4 class="section-title">
                        <div class="section-icon"><i class="fas fa-receipt"></i></div>
                        Breakdown of Expenses
                    </h4>
                    
                    <div class="expense-table-container">
                        <table class="expense-table">
                            <thead>
                                <tr>
                                    <th>Expense Type</th>
                                    <th>Amount</th>
                                    <th>Submitted Time</th>
                                </tr>
                            </thead>
                            <tbody id="expensesTableBody">
                                <tr>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                          <div class="total-amount">
                        <span class="total-label">Total Expenses</span>
                        <span class="total-value" id="totalExpensesAmount">₱15,500.00</span>
                    </div>
                    </div>
                </div>
                
      
           
                  
                    <button type="button" class="expense-close-btn" onclick="closeModal()">
                        <i class="fas fa-check-circle"></i> Close Report
                    </button>
              
            </div>
        </div>

        <div id="receiptModal" class="modal">
    <span class="close receipt-close">&times;</span>
    <img class="modal-content" id="receiptImageSrc" style="max-width: 60%; max-height: 80%; object-fit: contain;">
</div>
    </div>

<div id="addScheduleModal" class="modal">
    <!-- etong css gamit ng add modal -->
    <div class="modal-content" style="width: 100%; max-width: 700px; max-height: 90vh; overflow-y: auto; overflow-x:hidden;">
        <div class="modal-header2">
        <span class="close">&times;</span>
        <h2 style="margin-top:20;">Add Schedule</h2>
    </div>
        <form id="addScheduleForm">           
              <div style="display: flex; flex-direction: column; gap: 20px;">
                <fieldset style="flex: 1; border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
                    <legend style="font-weight: bold;">Shipment Information</legend>

                      <label for="addEventDate">Date & Time:</label>
                <input type="datetime-local" id="addEventDate" name="eventDate" required style="width: 100%;">
                <label for="addEventSize">Shipment Size:</label>

                <select id="addEventSize" name="eventSize" required style="width: 100%;">
                    <option value=""  disabled selected>Select Size</option>
                    <option value="20ft">20ft</option>
                    <option value="40ft">40ft</option>
                    
                </select>
                <label for="addEventDriver">Driver:</label>
                <select id="addEventDriver" name="eventDriver" required style="width: 100%;">
                    <option value="">Select Driver</option>
                </select>

                <label for="addEventPlateNo">Plate No.:</label>
                <input type="text" id="addEventPlateNo" name="eventPlateNo" required style="width: 100%;" disabled>

              

                

                <label for="addEventHelper">Helper:</label>
                <select id="addEventHelper" name="eventHelper" required style="width: 100%;">
                    <option value="">Select Helper</option>
                </select>

                <label for="addEventFCL">FCL Status:</label>
                <select id="addEventFCL" name="eventFCL" required style="width: 100%;">
            <option value="" disabled selected>Select FCL Status</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
                </select>
            </div>

            <!-- Column 2 -->
               <div style="display: flex; flex-direction: column; gap: 20px;">
                <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
                    <legend style="font-weight: bold;">Dispatcher & Container Information</legend>
                <label for="addEventDispatcher">Dispatcher:</label>
                <select id="addEventDispatcher" name="eventDispatcher" required style="width: 100%;">
                    <option value="">Select Dispatcher</option>
                </select>

                <label for="addEventContainerNo">Container No.:</label>
                <input type="text" id="addEventContainerNo" name="eventContainerNo" required style="width: 100%;">

                
</legend>
</fieldset>
  <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
                    <legend style="font-weight: bold;">Client & Destination</legend>
<label for="addEventClient">Client:</label>
                <select id="addEventClient" name="eventClient" required style="width: 100%;">
                    <option value="" disabled selected >Select Client</option>
                    <option value="Maersk">Maersk</option>
                    <option value="MSC">MSC</option>
                    <option value="COSCO">COSCO</option>
                    <option value="CMA CGM">CMA CGM</option>
                    <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                    <option value="Evergreen">Evergreen</option>
                </select>


                <label for="addEventPort">Port:</label>
                <select id="addEventPort" name="eventPort" required style="width: 100%;">
                    <option value="" disabled selected>Select Port</option>
                </select>


                <label for="addEventDestination">Destination:</label>
                <select id="addEventDestination" name="eventDestination" required style="width: 100%;">
                    <option value="" disabled selected>Select Destination</option>
                    <option value="Manila Port">Manila Port</option>
                    <option value="Batangas Port">Batangas Port</option>
                    <option value="Subic Port">Subic Port</option>
                    <option value="Cebu Port">Cebu Port</option>
                    <option value="Davao Port">Davao Port</option>
                </select>

                <label for="addEventStatus">Status:</label>
                <select id="addEventStatus" name="eventStatus" required style="width: 100%;">
                    <option value="Pending">Pending</option>
                    <option value="En Route">En Route</option>
                    <option value="Completed">Completed</option>
                    
                </select>
                </legend>
</fieldset>
            </div>

    
     
                  <div style="display: flex; flex-direction: column; gap: 20px;">
                <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
                    <legend style="font-weight: bold;">Shipping Information</legend>
                    <label for="addEventShippingLine">Shipping Line:</label>
                    <select id="addEventShippingLine" name="eventShippingLine" required style="width: 100%;">
                        <option value="" disabled selected>Select Shipping Line</option>
                        <option value="Maersk Line">Maersk Line</option>
                        <option value="Mediterranean Shipping Co.">Mediterranean Shipping Co.</option>
                        <option value="COSCO Shipping">COSCO Shipping</option>
                        <option value="CMA CGM">CMA CGM</option>
                        <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                    </select>
         
                    <label for="addEventConsignee">Consignee:</label>
                    <select id="addEventConsignee" name="eventConsignee" required style="width: 100%;">
                        <option value="">Select Consignee</option>
                    </select>
 </legend>
</fieldset>
      </div>

            <!-- Expense Fields Section -->
           <div style="display: flex; flex-direction: column; gap: 20px;">
    <fieldset style="border: 1px solid #ccc; padding: 15px; border-radius: 5px;">
        <legend style="font-weight: bold;">Financial Information</legend>
        <div>
            <label for="addEventCashAdvance">Cash Advance:</label>
            <input type="number" id="addEventCashAdvance" name="eventCashAdvance" 
                min="2000" step="0.01" placeholder="2000.00" value="2000" style="width: 100%;">
        </div>
        
    </fieldset>
</div>

            <!-- Form buttons -->
               <div class="buttons" style="grid-column: span 2; display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; border-top: 1px solid #eee ; ">
                <button type="button" class="close-btn cancel-btn" style="padding: 5px 10px;">Cancel</button>
                <button type="submit" class="save-btn" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px;">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

  <div id="checklistModal" class="modal">
    <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        
        <div class="modal-header">
            <h3><i class="fas fa-clipboard-check"></i> Driver Checklist</h3>
            <span class="close">&times;</span>
        </div>

        <div id="checklistContent">
            <table class="events-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody id="checklistTableBody"></tbody>
            </table>
        </div>
        
        <div class="modal-footer">
             <button type="button" class="close-btn cancel-btn">Close</button>
        </div>

    </div>
</div>
        
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content2">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete this trip?</p>
            <input type="hidden" id="deleteEventId">
            <label for="deleteReason">Reason for deletion:</label>
            <textarea id="deleteReason" rows="4" style="width: 100%; margin: 10px 0;"></textarea>
            <button id="confirmDeleteBtn">Yes, Delete</button>
            <button type="button" class="close-btn cancel-btn">Cancel</button>
        </div>
    </div>

    
        
    <div class="table-controls">
    <div class="table-info" id="showingInfo"></div>
    
     <div class="rows-per-page-container">
         <label for="rowsPerPage" class="rowlabel">Rows per page:</label>

        <select id="rowsPerPage" onchange="changeRowsPerPage()">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
     </div>
</div>

<div class="table-responsive-wrapper">
<table class="events-table" id="eventsTable"> 
    <thead>
        <tr>
            <th>Plate Number</th> 
            <th>Trip Date</th>
            <th>Driver</th>
            <th>Helper</th>
            <th>Dispatcher</th>
            <th>Container No.</th>
            <th>Client</th>
            <th>Port (Origin)</th>
            <th>Destination</th>
            <th>Shipping Line</th>
            <th>Consignee</th>
            <th>Container Size</th>
            <th>FCL</th>
            <th>Cash Advance</th>
            <th>Additional Cash</th>
            <th>Status</th>
            <th>Last Modified</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="eventTableBody"></tbody>
</table>
</div>
            <div class="pagination-container">
                <div class="pagination">
                    <button class="prev" id="prevPageBtn">&laquo</button> 
                    <div id="page-numbers" class="page-numbers"></div>
                    <button class="next" id="nextPageBtn">&raquo</button>
                </div>
            </div>
        </div>

    <div id="editReasonsModal" class="modal">
        <div class="modal-content" style="max-width: 600px;  overflow:hidden;">
            <div class="modal-header2">
            <span class="close">&times;</span>
            <h3>Edit Remarks</h3>
            </div>
            <div id="editReasonsContent" style="padding:20px;">
            
            </div>
            <div class="modalfooter" style="display:flex; justify-self:flex-end; padding:10px;">
            <button type="button" class="close-btn cancel-btn">Close</button>
            </div>
        </div>
    </div>
    </div>


        
       <div id="expenseSummaryModal" class="modal">
        
    <div class="modal-content" style=" max-width: 450px; ">
         <div class="modal-header">
            
        <span class="close">&times;</span>
            <h3 style="margin-top: 0; font-size: 20px" > <i class="fa-solid fa-file"></i>  Generate Expense Summary</h3>
        </div>
        <form id="expenseSummaryForm" action="expense_summary.php" method="GET" target="_blank">
            <label for="summaryType" style="display:block; margin-bottom:10px;">Report Type:</label>
            <select id="summaryType" name="type" required style="width: 100%; padding: 8px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #ccc;">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>

            <div id="date-picker-container">
                </div>

            <div class="buttons" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                <button type="button" class="close-btn cancel-btn">Cancel</button>
                <button type="submit" class="Generate-btn">Generate Report</button>
            </div>
        </form>
    </div>
</div>

    <script>
function formatDateTime(datetimeString) {
    if (!datetimeString) return 'N/A';
    
    const date = new Date(datetimeString);
    
   
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[date.getMonth()];
    
    
    const day = date.getDate();
    
    
    const year = date.getFullYear();
    
    
    let hours = date.getHours();
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; 
    
  return `<span class="date">${month} ${day}, ${year}</span><br> <span class="time">${hours}:${minutes} ${ampm}</span>`;
}

function formatDateTimeSingleLine(datetimeString) {
    if (!datetimeString) return 'N/A';
    
    const date = new Date(datetimeString);
    
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[date.getMonth()];
    const day = date.getDate();
    const year = date.getFullYear();
    
    let hours = date.getHours();
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; 
    
    // Returns a single line, e.g., "Jan 5, 2025 at 10:30 AM"
    return `${month} ${day}, ${year} at ${hours}:${minutes} ${ampm}`;
}
 let currentPage = 1;
let rowsPerPage = 5; 
let totalPages = 1;
let totalItems = 0;
  let currentStatusFilter = 'all';
  let dateSortOrder = 'desc';
let filteredEvents = [];
let currentDateFrom = '';
let currentDateTo = '';
let highlightTripId = null; 


      $(document).ready(function() {
        $.ajaxSetup({ cache: false });
    rowsPerPage = parseInt($('#rowsPerPage').val());
    let now = new Date();
    let formattedNow = now.toISOString().slice(0, 16);
    $('#rowsPerPage').val(rowsPerPage);
    updateTableInfo(totalItems, 0);
    $('#statusFilter').on('change', filterTableByStatus);
    
    $('#addEventDate').attr('min', formattedNow);
    $('#rowsPerPage').on('change', function() {
        rowsPerPage = parseInt($(this).val());
        currentPage = 1;
        renderTable();
    });

  
   $('#editEventStatus').on('change', function() {
        var newStatus = $(this).val();
        const originalData = $('#editForm').data('originalData');  
        if (newStatus === 'En Route' || newStatus === 'Completed') {
        $('#editAdditionalCashContainer').show();
            if (originalData) {
                $('#editEventAdditionalCashAdvance').val(originalData.additionalCashAdvance || 0);
            }
        } else {
        $('#editAdditionalCashContainer').hide();
        }
    });

    
    $('#dateFrom, #dateTo').on('change', filterTableByDateRange);

        $('#resetDateFilter').on('click', function() {
            $('#dateFrom').val('');
            $('#dateTo').val('');
            currentPage = 1;
            renderTable();
        });

        
        let searchTimeout;
        $('#searchInput').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1; 
                renderTable();
            }, 400); 
        });

    
function filterTableByDateRange() {
    const dateFrom = $('#dateFrom').val();
    const dateTo = $('#dateTo').val();
    
    if (dateFrom && dateTo && dateFrom > dateTo) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Date Range',
            text: 'Start date cannot be later than end date'
        });
        return;
    }
    currentDateFrom = dateFrom;
    currentDateTo = dateTo;
    
    
    currentPage = 1;
    
    
    renderTable();
}


            function updateDateTime() {
            const now = new Date();
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
            
            document.getElementById('current-time').textContent = now.toLocaleTimeString();
        }


        updateDateTime();
        setInterval(updateDateTime, 1000);
function renderTable() {
    const showDeleted = currentStatusFilter === 'deleted';
    const showToday = currentStatusFilter === 'today';
    const rowsPerPage = parseInt($('#rowsPerPage').val());
    const searchTerm = $('#searchInput').val();
    let action;
    if (showDeleted) {
        action = 'get_deleted_trips';
    } else if (showToday) {
        action = 'get_trips_today';
    } else {
        action = 'get_active_trips';
    }
    
    
    const dateFrom = $('#dateFrom').val();
    const dateTo = $('#dateTo').val();
    
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ 
            action: action, 
            statusFilter: showToday ? 'all' : (currentStatusFilter === 'deleted' ? 'all' : currentStatusFilter), 
            sortOrder: dateSortOrder,
            page: currentPage,
            perPage: rowsPerPage,
            dateFrom: dateFrom,
             dateTo: dateTo,
                searchTerm: searchTerm,
                _cacheBuster: new Date().getTime() 
        }),
        success: function(response) {
            if (response.success) {
                $('#eventTableBody').empty();
                
                if (response.trips.length === 0) {
                    $('#eventTableBody').html('<tr><td colspan="18">No trips found</td></tr>');
                } else {
                    renderTripRows(response.trips, showDeleted);
                }
                
                totalItems = response.total;
                totalPages = Math.ceil(totalItems / rowsPerPage);
                updatePagination(totalItems);
                updateTableInfo(totalItems, response.trips.length);
                  if (highlightTripId) {
                    const rowToHighlight = $(`tr[data-trip-id='${highlightTripId}']`);
                    if (rowToHighlight.length) {
                        rowToHighlight[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        rowToHighlight.addClass('highlight-continuous-pulse');
                    }
                     highlightTripId = null; 
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to load trips',
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server error occurred while loading trips',
            });
        }
    });
}
    


  function checkMaintenanceConflict(plateNo, tripDate, callback) {
    $.ajax({
        url: 'include/handlers/maintenance_handler.php',
        type: 'GET',
        data: {
            action: 'checkMaintenance',
            plateNo: plateNo,
            tripDate: tripDate
        },
        success: function(response) {
            if (response.success && response.hasConflict) {
                
                Swal.fire({
                    title: 'Maintenance Conflict',
                    html: `This truck has a scheduled maintenance on <strong>${response.maintenanceDate}</strong>.<br><br>
                        <strong>Type:</strong> ${response.maintenanceType}<br>
                        <strong>Remarks:</strong> ${response.remarks}<br><br>
                        Trips cannot be scheduled within one week before, or any time after, a pending maintenance. The maintenance must be marked as 'Completed' before new trips can be scheduled.`,
                    icon: 'warning',
                    confirmButtonText: 'OK',
                    showCancelButton: false
                }).then((result) => {
                    callback(false); 
                });
            } else {
                callback(true); 
            }
        },
        error: function() {
            console.error('Error checking maintenance');
            
            Swal.fire({
                title: 'Warning',
                text: 'Could not verify maintenance schedule. Please proceed with caution.',
                icon: 'warning',
                confirmButtonText: 'Continue Anyway',
                showCancelButton: true,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                callback(result.isConfirmed);
            });
        }
    });
}

function highlightText(text, searchTerm) {
    if (!searchTerm || !text) {
        return text;
    }
    const escapedSearchTerm = searchTerm.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    const regex = new RegExp(escapedSearchTerm, 'gi');
    return text.toString().replace(regex, (match) => `<span class="highlight">${match}</span>`);
}
function renderTripRows(trips, showDeleted) {
  const searchTerm = $('#searchInput').val(); 
    trips.forEach(function(trip) {
        let statusCell = '';
        let actionCell = '';
        
        if (showDeleted || trip.is_deleted == 1) {
            statusCell = `<td data-label="Status"><span class="status cancelled">Deleted</span></td>`;
            actionCell = `
               <td data-label="Actions" class="actions">
                    <div class="dropdown">
                        <button class="dropdown-btn" data-tooltip="Actions">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-content">
                            <button class="dropdown-item restore" data-id="${trip.trip_id}">
                                <i class="fas fa-trash-restore"></i> Restore
                            </button>
                            <button class="dropdown-item full-delete" data-id="${trip.trip_id}">
                                <i class="fa-solid fa-ban"></i> Permanent Delete
                            </button>
                            <button type="button" id="viewChecklistBtn" class=" dropdown-item full-delete" style="background-color: #17a2b8; display: none;">
                                    View Driver Checklist
                            </button>
                        </div>
                    </div>
                </td>
            `;
        } else {
            statusCell = `<td data-label="Status"><span class="status ${trip.status.toLowerCase().replace(/\s+/g, '')}">${trip.status}</span></td>`;
            actionCell = `
             <td data-label="Actions" class="actions">
                <div class="dropdown">
                    <button class="dropdown-btn" data-tooltip="Actions">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-content">
                        ${trip.status === 'En Route' ? 
                        `<a href="tracking.php?driver_id=${trip.firebase_uid}" target="_blank" class="dropdown-item view-location">
                            <i class="fas fa-map-marker-alt"></i> View Location
                        </a>` : ''}
                        <button class="dropdown-item edit" data-id="${trip.trip_id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="dropdown-item view-expenses" data-id="${trip.trip_id}">
                            <i class="fas fa-money-bill-wave"></i> View Expenses
                        </button>
                        <button class="dropdown-item view-checklist" data-id="${trip.trip_id}" data-driver-id="${trip.driver_id}">
                            <i class="fas fa-clipboard-check"></i> Driver Checklist
                        </button>
                       <a href="trip_report.php?id=${trip.trip_id}" target="_blank" class="dropdown-item Full-report">
                            <i class="fas fa-file-alt"></i> Generate Report
                        </a>
                        ${trip.edit_reason && trip.edit_reason !== 'null' && trip.edit_reason !== '' ? 
                        `<button class="dropdown-item view-reasons" data-id="${trip.trip_id}">
                            <i class="fas fa-history"></i> Edit Remarks
                        </button>` : ''}
                        <button class="dropdown-item delete" data-id="${trip.trip_id}">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                         <button class="dropdown-item cancel-trip" onclick="cancelTrip(${trip.trip_id})">
                            <i class="fas fa-ban"></i> Cancel Trip
                        </button>
                    </div>
                </div>
            </td>
            `;
        }
        const row = `
             <tr data-trip-id="${trip.trip_id}" class="${showDeleted || trip.is_deleted == 1 ? 'deleted-row' : ''}">
                <td data-label="Plate Number">${highlightText(trip.plate_no || 'N/A', searchTerm)}</td>
                <td data-label="Trip Date">${formatDateTime(trip.trip_date)}</td>
                <td data-label="Driver">${highlightText(trip.driver || 'N/A', searchTerm)}</td>
                <td data-label="Helper">${highlightText(trip.helper || 'N/A', searchTerm)}</td>
                <td data-label="Dispatcher">${highlightText(trip.dispatcher || 'N/A', searchTerm)}</td>
                <td data-label="Container No.">${highlightText(trip.container_no || 'N/A', searchTerm)}</td>
                <td data-label="Client">${highlightText(trip.client || 'N/A', searchTerm)}</td>
                <td data-label="Port (Origin)">${highlightText(trip.port || 'N/A', searchTerm)}</td>
                <td data-label="Destination">${highlightText(trip.destination || 'N/A', searchTerm)}</td>
                <td data-label="Shipping Line">${highlightText(trip.shipping_line || 'N/A', searchTerm)}</td>
                <td data-label="Consignee">${highlightText(trip.consignee || 'N/A', searchTerm)}</td>
                <td data-label="Container Size">${highlightText(trip.truck_capacity ? trip.truck_capacity + 'ft' : 'N/A', searchTerm)}</td>
                <td data-label="FCL">${highlightText(trip.fcl_status || 'N/A', searchTerm)}</td>
                <td data-label="Cash Advance">₱${parseFloat(trip.cash_advance || 0).toFixed(2)}</td>
                <td data-label="Additional Cash">₱${parseFloat(trip.additional_cash_advance || 0).toFixed(2)}</td>
                ${statusCell}
                <td data-label="Last Modified" data-raw-date="${trip.last_modified_at || trip.created_at}">${formatDateTime(trip.last_modified_at || trip.created_at)}
                    ${trip.last_modified_by ? `<br> <strong>${highlightText(trip.last_modified_by, searchTerm)}</strong></small>` : ''}
                </td>
                ${actionCell}
            </tr>
        `;
        $('#eventTableBody').append(row);
    });
}

$(document).on('click', '.dropdown-btn', function(e) {
    e.stopPropagation();
    $('.dropdown-content').not($(this).siblings('.dropdown-content')).removeClass('show');
    $(this).siblings('.dropdown-content').toggleClass('show');
});
$(document).on('click', function(e) {
    if (!$(e.target).closest('.dropdown').length) {
        $('.dropdown-content').removeClass('show');
    }
});

            var eventsData = <?php echo $eventsDataJson; ?>;
            var driversData = <?php echo $driversDataJson; ?>;



function populateDropdowns(action, responseKey, targetSelectors, defaultText) {
     if ($('#editEventHelper option').length <= 1 || $('#addEventHelper option').length <= 1) {
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: action }),
        success: function(response) {
            if (response.success && response[responseKey]) {
                var options = `<option value="" disabled selected>${defaultText}</option>`;
                response[responseKey].forEach(function(item) {
                    options += `<option value="${item.name}">${item.name}</option>`;
                });
                $(targetSelectors).html(options);
            }
        },
        error: function() {
            console.error(`Error fetching ${responseKey}`);
        }
    });
    }
}


function populateHelperDropdowns() {
    populateDropdowns('get_helpers', 'helpers', '#editEventHelper, #addEventHelper', 'Select Helper');
}

function populateClientDropdowns() {
    populateDropdowns('get_clients', 'clients', '#editEventClient, #addEventClient', 'Select Client');
}

function populateDestinationDropdowns() {
    populateDropdowns('get_destinations', 'destinations', '#editEventDestination, #addEventDestination', 'Select Destination');
}

function populateShippingLineDropdowns() {
    populateDropdowns('get_shipping_lines', 'shipping_lines', '#editEventShippingLine, #addEventShippingLine', 'Select Shipping Line');
}

function populateDispatcherDropdowns() {
    populateDropdowns('get_dispatchers', 'dispatchers', '#editEventDispatcher, #addEventDispatcher', 'Select Dispatcher');
}

function populateConsigneeDropdowns() {
    populateDropdowns('get_consignees', 'consignees', '#editEventConsignee, #addEventConsignee', 'Select Consignee');
}


$(document).ready(function() {
    populateHelperDropdowns();
    populateDispatcherDropdowns();
    populateConsigneeDropdowns();
    populateClientDropdowns();
    populatePortDropdowns();
    populateDestinationDropdowns();
    populateShippingLineDropdowns();

});

$('#addScheduleBtnTable').on('click', function() {
    resetAddScheduleForm();
    populateDriverDropdowns();
    populateHelperDropdowns();        
    populateDispatcherDropdowns();
    populateConsigneeDropdowns();
    populatePortDropdowns();
    populateClientDropdowns();
    populateDestinationDropdowns();
    populateShippingLineDropdowns();
    $('#addScheduleModal').show();
});

$(document).on('click', '.icon-btn.edit', function() {
 var eventId = $(this).data('id');
    var eventData = eventsData.find(function(e) { return e.id == eventId; });

    if (eventData) {
      
        populateHelperDropdowns();
        populateDispatcherDropdowns();
        populateConsigneeDropdowns();
        populateClientDropdowns();
        populatePortDropdowns();
        populateDestinationDropdowns();
        populateShippingLineDropdowns();

    setTimeout(() => {
        $('#editEventHelper').val(event.helper);
        $('#editEventDispatcher').val(event.dispatcher || '');
        $('#editEventConsignee').val(event.consignee);
        $('#editEventClient').val(event.client);
         $('#editEventPort').val(event.port || '');
        $('#editEventDestination').val(event.destination);
        $('#editEventShippingLine').val(event.shipping_line);
    }, 100);
    
    $('#editModal').show();
        } else {
        console.error("Could not find event data for ID:", eventId);
    }
});
            

// expense summary things
$('#expenseSummaryBtn').on('click', function() {
   
    $('#summaryType').val('daily').trigger('change');
    $('#expenseSummaryModal').show();
});

$('#summaryType').on('change', function() {
    const type = $(this).val();
    const container = $('#date-picker-container');
    container.empty(); 
    const today = new Date().toISOString();

    let inputHtml = '';
    const commonStyle = 'width: 100%; padding: 8px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;';

    if (type === 'daily') {
        inputHtml = `
            <label for="summaryDate" style="display:block; margin-bottom:10px;">Select Date:</label>
            <input type="date" id="summaryDate" name="date" required value="${today.slice(0, 10)}" style="${commonStyle}">
        `;
    } else if (type === 'weekly') {
        inputHtml = `
            <label for="summaryWeek" style="display:block; margin-bottom:10px;">Select Week:</label>
            <input type="week" id="summaryWeek" name="week" required style="${commonStyle}">
        `;
    } else if (type === 'monthly') {
        inputHtml = `
            <label for="summaryMonth" style="display:block; margin-bottom:10px;">Select Month:</label>
            <input type="month" id="summaryMonth" name="month" required value="${today.slice(0, 7)}" style="${commonStyle}">
        `;
    } else if (type === 'yearly') {
        inputHtml = `
            <label for="summaryYear" style="display:block; margin-bottom:10px;">Select Year:</label>
            <input type="number" id="summaryYear" name="year" required value="${today.slice(0, 4)}" placeholder="YYYY" min="2020" max="2099" style="${commonStyle}">
        `;
    }
    container.html(inputHtml);
});

   $('#expenseSummaryForm').on('submit', function(e) {
    e.preventDefault();

    const form = $(this);
   
    const reportUrl = form.attr('action') + '?' + form.serialize();

   
    if (form[0].checkValidity()) {
        $('#expenseSummaryModal').hide();

        const loading = AdminLoading.startAction(
            'Generating Report',
            'Preparing your expense summary...'
        );

        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15 + 8;
            if (progress >= 85) {
                clearInterval(progressInterval);
                progress = 90;
            }
            loading.updateProgress(Math.min(progress, 90));
        }, 150);

        const minLoadTime = 1200;

        setTimeout(() => {
            loading.updateProgress(100);
            setTimeout(() => {
                window.location.href = reportUrl;
            }, 300);
        }, minLoadTime);

    } else {
        Swal.fire({
            icon: 'warning',
            title: 'No Date Selected',
            text: 'Please select a date, week, month, or year to generate the summary.'
        });
    }
});


            
 function populateDriverDropdowns(selectedSize = '', currentDriver = '', callback) { 
    $.ajax({
        url: 'include/handlers/truck_handler.php?action=getTrucks',
        type: 'GET',
        success: function(truckResponse) {
            if (truckResponse.success) {
                var unavailableTruckIds = truckResponse.trucks
                    .filter(truck => truck.display_status === 'In Repair' || truck.display_status === 'Overdue' || truck.is_deleted == 1)
                    .map(truck => truck.truck_id.toString());

                let checkedInOptions = '';
                let notCheckedInOptions = '';
                let unavailableDriverOptions = '<optgroup label="❌ Unavailable Drivers">';
                
                let unavailableCount = 0;
                const now = new Date();

                driversData.forEach(function(driver) {
                    const isCurrentTripDriver = (driver.name === currentDriver);
                    let isSelectable = true;
                    let unavailabilityReason = '';

                    if (!isCurrentTripDriver) {
                        if (driver.assigned_truck_id && unavailableTruckIds.includes(driver.assigned_truck_id.toString())) {
                            var truck = truckResponse.trucks.find(t => t.truck_id.toString() === driver.assigned_truck_id.toString());
                            isSelectable = false;
                            unavailabilityReason = `(Truck ${truck ? truck.display_status : 'Unavailable'})`;
                        }
                        if (isSelectable && driver.penalty_until) {
                            const penaltyTime = new Date(driver.penalty_until);
                            if (now < penaltyTime) {
                                isSelectable = false;
                                unavailabilityReason = `(Penalized until ${penaltyTime.toLocaleTimeString()})`;
                            }
                        }
                    }

                    var optionContent = `${driver.name}`;
                    if (driver.truck_plate_no) optionContent += ` (${driver.truck_plate_no})`;
                    if (driver.capacity) optionContent += ` [${driver.capacity}ft]`;

                    let capacityMatch = !selectedSize || !driver.capacity ||
                        (selectedSize.includes('20') && driver.capacity === '20') ||
                        (selectedSize.includes('40') && driver.capacity === '40');

                    if (isCurrentTripDriver || (isSelectable && capacityMatch)) {

                        let isCheckedIn = false;
                        if (driver.checked_in_at) {
                            const checkedInTime = new Date(driver.checked_in_at);
                            const expiryTime = new Date(checkedInTime.getTime() + 16 * 60 * 60 * 1000);
                            if (now < expiryTime) {
                                isCheckedIn = true;
                            }
                        }
                        
                        var selectedAttr = isCurrentTripDriver ? ' selected' : '';
                        const optionHtml = `
                            <option
                                value="${driver.name}"
                                data-plate-no="${driver.truck_plate_no || ''}"
                                data-driver-id="${driver.id || ''}"
                                ${selectedAttr}
                            >
                                ${optionContent}
                            </option>`;
                        
                        if (isCheckedIn) {
                            checkedInOptions += optionHtml;
                        } else {
                            notCheckedInOptions += optionHtml;
                        }

                    } else {
                        unavailableCount++;
                        unavailableDriverOptions += `
                            <option value="${driver.name}" disabled title="Reason: ${unavailabilityReason.replace(/[()]/g, '') || 'Capacity mismatch'}">
                                ${optionContent} ${unavailabilityReason || '(Capacity Mismatch)'}
                            </option>`;
                    }
                });

                
                let finalHtml = '<option value="" disabled selected>Select Driver</option>';

                if (checkedInOptions) {
                    finalHtml += '<optgroup label="✅ Checked-In & Available">' + checkedInOptions + '</optgroup>';
                }
                if (notCheckedInOptions) {
                    finalHtml += '<optgroup label="➖ Not Checked-In (Available)">' + notCheckedInOptions + '</optgroup>';
                }
                if (unavailableCount > 0) {
                    finalHtml += unavailableDriverOptions + '</optgroup>';
                }

                $('#editEventDriver').html(finalHtml);
                $('#addEventDriver').html(finalHtml);

                if (callback) callback(); 

            } else {
                console.error('Error fetching truck data:', truckResponse.message);
                populateAllDrivers(selectedSize, currentDriver); 
                if (callback) callback(); 
            }
        },
        error: function() {
            console.error('AJAX error fetching truck data');
            populateAllDrivers(selectedSize, currentDriver); 
            if (callback) callback(); 
        }
    });
}


    function selectNextDriver(capacity) {
    
    const capacityValue = capacity.replace('ft', '').trim();
    
    if (!capacityValue) {
        $('#addEventDriver').val('');
        $('#addEventPlateNo').val('');
        return;
    }

    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_next_driver',
            capacity: capacityValue
        }),
        success: function(response) {
            if (response.success && response.driver) {
                const driver = response.driver;
                
                $('#addEventDriver').val(driver.name);
                $('#addEventPlateNo').val(driver.plate_no);
                
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: `Driver ${driver.name} automatically assigned.`,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true
                });
            } else {
                $('#addEventDriver').val('');
                $('#addEventPlateNo').val('');
                Swal.fire({
                    icon: 'warning',
                    title: 'No Available Drivers',
                    text: response.message || `No available drivers were found for a ${capacity} shipment.`
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not fetch the next available driver.'
            });
        }
    });
}
  


    $('#dateSortBtn').on('click', function() {
        dateSortOrder = dateSortOrder === 'desc' ? 'asc' : 'desc';
        renderTable();
    });

    
    function populateAllDrivers(selectedSize = '', currentDriver = '') {
        var driverOptions = '<option value="">Select Driver</option>';
        driversData.forEach(function(driver) {
            if (!selectedSize || !driver.capacity || 
                (selectedSize.includes('20') && driver.capacity === '20') ||
                (selectedSize.includes('40') && driver.capacity === '40')) {
                
                var selectedAttr = (driver.name === currentDriver) ? ' selected' : '';
                driverOptions += `
                    <option 
                        value="${driver.name}" 
                        data-plate-no="${driver.truck_plate_no || ''}"
                        data-driver-id="${driver.id || ''}"
                        ${selectedAttr}
                    >
                        ${driver.name}
                        ${driver.truck_plate_no ? ` (${driver.truck_plate_no})` : ''}
                        ${driver.capacity ? ` [${driver.capacity}ft]` : ''}
                    </option>
                `;
            }
        });
        $('#editEventDriver').html(driverOptions);
        $('#addEventDriver').html(driverOptions);
    }

    $(document).on('change', '#addEventDriver, #editEventDriver', function() {
            var selectedOption = $(this).find('option:selected');
            var plateNo = selectedOption.data('plate-no');
            
            
            var isAddForm = $(this).attr('id') === 'addEventDriver';
            var plateNoField = isAddForm ? '#addEventPlateNo' : '#editEventPlateNo';
            
            $(plateNoField).val(plateNo || '');
        });

   
 $('#addEventSize, #editEventSize').on('change', function() {
        var selectedSize = $(this).val();
        var isAddForm = $(this).attr('id') === 'addEventSize';
        
        if(isAddForm) {
            
            populateDriverDropdowns(selectedSize, '', function() {
                selectNextDriver(selectedSize);
            });
        } else {
           
            populateDriverDropdowns(selectedSize); 
        }
    });


    $('#viewDeletedBtn').on('click', function() {
        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            data: JSON.stringify({ action: 'get_deleted_trips' }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('#deletedTripsBody').empty();
                    response.trips.forEach(function(trip) {
                        var row = `
                            <tr>
                                <td>${trip.plate_no}</td>
                                <td>${formatDateTime(trip.date)}</td>
                                <td>${trip.driver}</td>
                                <td>${trip.destination}</td>
                                <td>${trip.last_modified_by}</td>
                                <td>${formatDateTime(trip.last_modified_at)}</td>
                                <td>${trip.delete_reason || 'No reason provided'}</td>
                            </tr>
                        `;
                        $('#deletedTripsBody').append(row);
                    });
                    $('#deletedTripsModal').show();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                 Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Server error occurred',
                    });
            
            }
        });
    });


    $(document).on('change', '#addEventDriver, #editEventDriver', function() {
        var selectedDriverName = $(this).val();
        var driver = driversData.find(function(d) { 
            return d.name === selectedDriverName; 
        });
        
        if (driver && driver.truck_plate_no) {
            
            var isAddForm = $(this).attr('id') === 'addEventDriver';
            var plateNoField = isAddForm ? '#addEventPlateNo' : '#editEventPlateNo';
            
            $(plateNoField).val(driver.truck_plate_no);
        } else {
            
            var isAddForm = $(this).attr('id') === 'addEventDriver';
            var plateNoField = isAddForm ? '#addEventPlateNo' : '#editEventPlateNo';
            $(plateNoField).val('');
        }
    });
            
           
              var calendarEvents = eventsData.map(function(event) {
    return {
        id: event.id,
        title: event.client + ' - ' + event.destination,
        start: event.date,
        plateNo: event.plateNo,
        driver: event.driver,
        driver_id: event.driver_id,
        firebase_uid: event.firebase_uid,
        helper: event.helper,
        dispatcher: event.dispatcher,
        containerNo: event.containerNo,
         port: event.port,
        client: event.client,
        destination: event.destination,
        shippingLine: event.shippingLine,
        consignee: event.consignee,
        size: event.size,
         cashAdvance: event.cashAdvance || event.cash_advance,
        additionalCashAdvance: event.additionalCashAdvance || event.additional_cash_advance, 
        status: event.status,
        modifiedby: event.modifiedby,
        modifiedat: event.modifiedat,
        truck_plate_no: event.truck_plate_no,
        truck_capacity: event.truck_capacity,
        edit_reasons: event.edit_reasons,
         fcl_status: event.fcl_status 
    };
});

 function resetAddScheduleForm() {
    $('#addEventPlateNo').val('');
    $('#addEventDate').val('');
    $('#addEventDriver').val('').trigger('change');
    $('#addEventHelper').val('');
     $('#addEventDispatcher').val('');
    $('#addEventContainerNo').val('');
    $('#addEventClient').val('');
    $('#addEventPort').val('');
    $('#addEventDestination').val('');
    $('#addEventShippingLine').val('');
    $('#addEventConsignee').val('');
    $('#addEventSize').val('');
    $('#addEventFCL').val('');
    $('#addEventCashAdvance').val('2000');
    $('#addEventStatus').val('Pending');
}

$(document).on('click', '.close, .close-btn.cancel-btn', function() {

    const modalId = $(this).closest('.modal').attr('id');
    if (modalId) {
        closeModal(modalId);
    }
});

   
   $(window).on('click', function(event) {
        if ($(event.target).hasClass('modal')) {
            
              closeModal(event.target.id);
            if ($(event.target).is('#editModal')) {
                 $('#editEventDriver').prop('disabled', false);
                 $('#editEventSize').prop('disabled', false);
            }
    
            if ($(event.target).is('#addScheduleModal')) {
                resetAddScheduleForm();
            }
        }
    });
            
        $('#addScheduleBtnTable').on('click', function() {
        resetAddScheduleForm(); 
        populateDriverDropdowns(); 
        $('#addScheduleModal').show();
    });

       function filterTableByStatus() {
    currentStatusFilter = document.getElementById('statusFilter').value;
    currentPage = 1; 
    renderTable();
}

        function updatePagination(totalItems) {
    const pageNumbers = $('#page-numbers');
    pageNumbers.empty();
    

    $('#prevPageBtn').prop('disabled', currentPage === 1);
    
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    

    if (startPage > 1) {
        const firstPageBtn = $('<button>')
            .text('1')
            .addClass('page-number')
            .attr('data-page', 1);
        if (currentPage === 1) firstPageBtn.addClass('active');
        firstPageBtn.on('click', function() {
            goToPage(parseInt($(this).attr('data-page')));
        });
        pageNumbers.append(firstPageBtn);
        
        if (startPage > 2) {
            pageNumbers.append('<span class="ellipsis">...</span>');
        }
    }
    

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = $('<button>')
            .text(i)
            .addClass('page-number')
            .attr('data-page', i);
        if (i === currentPage) pageBtn.addClass('active');
        pageBtn.on('click', function() {
            goToPage(parseInt($(this).attr('data-page')));
        });
        pageNumbers.append(pageBtn);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            pageNumbers.append('<span class="ellipsis">...</span>');
        }
        
        const lastPageBtn = $('<button>')
            .text(totalPages)
            .addClass('page-number')
            .attr('data-page', totalPages);
        if (currentPage === totalPages) lastPageBtn.addClass('active');
        lastPageBtn.on('click', function() {
            goToPage(parseInt($(this).attr('data-page')));
        });
        pageNumbers.append(lastPageBtn);
    }
    
 
    $('#nextPageBtn').prop('disabled', currentPage === totalPages);
}

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
                if (currentPage > 1) {
                goToPage(currentPage - 1);
              }
            });

            $('#nextPageBtn').on('click', function() {
                 if (currentPage < totalPages) {
                goToPage(currentPage + 1);
                 }
            });

          
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
        
        var statusClass = 'status ' + event.status.toLowerCase().replace(/\s+/g, '');
        element.addClass(statusClass);
    },
    viewRender: function(view, element) {
        if (view.name === 'month') {
            setTimeout(function() {
                $('.fc-today').trigger('click');
            }, 100);
        }
    },
   
eventClick: function(event, jsEvent, view) {
    var formattedDate = moment(event.start).format('MMMM D, YYYY h:mm A');
    var modifiedDate = event.modifiedat ? moment(event.modifiedat).format('MMMM D, YYYY h:mm A') : 'N/A';
    
     $('#eventModal').data('currentEvent', event);

     $('#modal-trip-id').text(`#${String(event.id).padStart(4, '0')}`);
    $('#modal-container-no').text(event.containerNo || 'N/A');
    $('#modal-status').text(event.status || 'N/A')
        .removeClass()
        .addClass('status ' + (event.status ? event.status.toLowerCase().replace(/\s+/g, '-') : ''));

    $('#modal-origin').text(event.port || 'N/A');
    $('#modal-destination').text(event.destination || 'N/A');
    $('#modal-plate-no').text(event.plateNo || 'N/A');
    $('#modal-date').text(formattedDate);
    $('#modal-size').text(event.truck_capacity ? event.truck_capacity + 'ft' : (event.size || 'N/A'));
    $('#modal-cash-advance').text('₱' + (parseFloat(event.cashAdvance || 0)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#modal-additional-cash').text('₱' + (parseFloat(event.additionalCashAdvance || 0)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    

    $('#modal-driver').text(event.driver || 'N/A');
    $('#modal-helper').text(event.helper || 'N/A');
    $('#modal-dispatcher').text(event.dispatcher || 'N/A');
    $('#modal-client-name').text(event.client || 'N/A');
    $('#modal-shipping-line').text(event.shippingLine || 'N/A');
    $('#modal-consignee').text(event.consignee || 'N/A');
    $('#modal-modified-by').text(event.modifiedby || 'System');
    $('#modal-modified-at').text(modifiedDate);
  
    $('#eventModal').data('eventId', event.id);
    $('#eventModal .edit').data('id', event.id);
    $('#eventModal .delete').data('id', event.id);
    $('#eventModal .view-expenses').data('id', event.id);
    $('#eventModal .view-checklist').data('id', event.id).data('driver-id', event.driver_id);
    $('#eventModal .view-reasons').data('id', event.id);
    $('#eventModal .generate-report').attr('href', `trip_report.php?id=${event.id}`);
    $('#eventModal .cancel-trip').attr('data-id', event.id);
    

    if (event.status === 'En Route') {
        $('#eventModalViewLocationBtn').show();
        $('#eventModalViewLocationBtn').off('click').on('click', function() {
            window.location.href = `tracking.php?driver_id=${event.firebase_uid}`;
        });
    } else {
        $('#eventModalViewLocationBtn').hide();
    }

      if (event.edit_reasons && event.edit_reasons !== 'null' && event.edit_reasons !== '') {
        $('#eventModalHistoryBtn').show();
    } else {
        $('#eventModalHistoryBtn').hide();
    }

    if (event.status === 'Pending') {
        $('.icon-btn.view-expenses').hide();
    } else {
        $('.icon-btn.view-expenses').show();
    }




   
    const statusElement = $('#eventModalStatus');
    statusElement.text(event.status || 'N/A');
    statusElement.removeClass().addClass('status ' + (event.status ? event.status.toLowerCase().replace(/\s+/g, '') : ''));
    
   
    $('#eventModal').data('eventId', event.id);
    
    
  
           
            $('#eventModal').show();

   
   
$('#eventModalHistoryBtn').off('click').on('click', function() {
    var eventId = $('#eventModal').data('eventId');
    var eventData = eventsData.find(function(e) { return e.id == eventId; });

    var html = '';

    if (eventData && eventData.edit_reasons) {
        try {
            var log = JSON.parse(eventData.edit_reasons);

            if (log.user_reason || log.automatic_changes) {
                
                if (log.user_reason && log.user_reason.length > 0) {
                    html += '<h5 style="margin-top:0; margin-bottom:8px; color:#333;">User Reason(s)</h5>';
                    html += '<ul style="margin-top:0; padding-left:20px; color:#555;">';
                    log.user_reason.forEach(function(reason) {
                        html += `<li style="margin-bottom:5px;">${reason}</li>`;
                    });
                    html += '</ul>';
                }

                if (log.automatic_changes && log.automatic_changes.length > 0) {
                    html += '<h5 style="margin-top:15px; margin-bottom:8px; color:#333;">Field Changes Detected</h5>';
                    html += '<ul style="margin-top:0; padding-left:20px; color:#555; font-family: monospace; font-size: 1.1em;">';
                    log.automatic_changes.forEach(function(change) {
                        let formattedChange = change.replace(/ to '(.*?)'$/, " to <strong style='color:#006400;'>'$1'</strong>");
                        html += `<li style="margin-bottom:5px;">${formattedChange}</li>`;
                    });
                    html += '</ul>';
                }
                
            } else {
                html += '<h5 style="margin-top:0; margin-bottom:8px; color:#333;">Edit Reason(s)</h5>';
                html += '<ul style="margin-top:0; padding-left:20px; color:#555;">';
                log.forEach(function(reason) {
                    if (reason !== "Trip created" && reason !== '"Trip created"') {
                         html += `<li style="margin-bottom:5px;">${reason}</li>`;
                    }
                });
                html += '</ul>';
            }

        } catch (e) {
            html += '<h5 style="margin-top:0; margin-bottom:8px; color:#333;">Edit Reason</h5>';
            html += '<p style="margin-top:0; color:#555;">' + eventData.edit_reasons + '</p>';
        }

        html += '<p style="font-style:italic; margin-top:20px; padding-top:10px; border-top:1px solid #eee; color:#666; font-size:0.9em;">';
        html += 'Last modified by: <strong>' + (eventData.modifiedby || 'System') + '</strong><br>';
        html += ' ' + formatDateTimeSingleLine(eventData.modifiedat);
        html += '</p>';
        
        $('#editReasonsContent').html(html);

    } else {

        $('#editReasonsContent').html('<div style="padding: 15px; background: #f5f5f5; border-radius: 5px;">'+
            '<p>No edit remarks recorded for this trip</p></div>');
    }

    $('#eventModal').hide();
    $('#editReasonsModal').show();
});
    

$('#eventModal .view-expenses').off('click').on('click', function(e){
e.stopPropagation();
 var tripId = $(this).data('id');

    
    var tripData = eventsData.find(function(trip) {
        return trip.id == tripId;
    });

    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_expenses',
            tripId: tripId
        }),
        success: function(response) {
            if (response.success) {
                $('#expensesTableBody').empty();

                if (tripData) {
                    $('#expensePlateNo').text(tripData.plateNo || tripData.truck_plate_no || 'N/A');
                    $('#expenseContainerNo').text(tripData.containerNo || 'N/A');
                    $('#expenseContainerSize').text(tripData.truck_capacity ? tripData.truck_capacity + 'ft' : tripData.size || 'N/A');

                    if (tripData.date || tripData.trip_date) {
                        const tripDate = new Date(tripData.date || tripData.trip_date);
                        const formattedDate = tripDate.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        $('#expenseTripDate').text(formattedDate);
                    } else {
                        $('#expenseTripDate').text('N/A');
                    }

                    $('#expenseDriverName').text(tripData.driver || 'N/A');
                    $('#expenseHelperName').text(tripData.helper || 'N/A');
                    $('#expenseDestination').text(tripData.destination || 'N/A');
                }

                const cashAdvance = parseFloat(response.cashAdvance || tripData.cashAdvance || 0);
                const additionalCash = parseFloat(response.additionalCashAdvance || tripData.additionalCashAdvance || 0);
                
                const totalInitialFunds = cashAdvance + additionalCash;

                $('#expenseCashAdvance').text('₱' + cashAdvance.toFixed(2));
                $('#expenseAdditionalCash').text('₱' + additionalCash.toFixed(2));
                
                $('#totalInitialFunds').text('₱' + totalInitialFunds.toFixed(2));

                let totalExpenses = 0;
                if (response.expenses && response.expenses.length > 0) {
                    response.expenses.forEach(function(expense) {
                        const amount = parseFloat(expense.amount.replace('₱', '').replace(',', ''));
                        totalExpenses += amount;
                        const receiptAttr = expense.receipt_image ? `data-receipt="${expense.receipt_image}"` : '';
                        const clickableClass = expense.receipt_image ? 'clickable-expense' : '';
                        const submittedTime = expense.submitted_time ? formatDateTime(expense.submitted_time) : 'N/A';

                        var row = `<tr class="${clickableClass}" ${receiptAttr}>
                                    <td>${expense.expense_type}</td>
                                    <td>${expense.amount}</td>
                                    <td>${submittedTime}</td>
                                </tr>`;
                        $('#expensesTableBody').append(row);
                    });
                } else {
                    $('#expensesTableBody').html('<tr><td colspan="3" style="text-align: center;">No additional expenses recorded</td></tr>');
                }

                $('#totalExpensesAmount').text('₱' + totalExpenses.toFixed(2));
                $('#expensesModal').show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to load expenses'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server error occurred while loading expenses'
            });
        }
    });
});


$('#eventModal .view-checklist').off('click').on('click', function(e) {
    e.stopPropagation();
    var tripId = $(this).data('id'); 
    var driverId = $(this).data('driver-id'); 

    
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_checklist',
            trip_id: tripId
        }),
        success: function(response) {
            if (response.success && response.checklist) {
                $('#checklistTableBody').empty();
                
                var checklist = response.checklist;
                var rows = [
                    { question: 'No body fatigue?', response: checklist.no_fatigue ? 'Yes' : 'No' },
                    { question: 'Did not take illegal drugs?', response: checklist.no_drugs ? 'Yes' : 'No' },
                    { question: 'No mental distractions?', response: checklist.no_distractions ? 'Yes' : 'No' },
                    { question: 'No body illness?', response: checklist.no_illness ? 'Yes' : 'No' },
                    { question: 'Fit to work?', response: checklist.fit_to_work ? 'Yes' : 'No' },
                    { question: 'Alcohol test reading', response: checklist.alcohol_test },
                    { question: 'Hours of sleep', response: checklist.hours_sleep },
                    { question: 'Submitted at', response: formatDateTime(checklist.submitted_at) }
                ];
                
                rows.forEach(function(row) {
                $('#checklistTableBody').append(`
                    <tr>
                        <td data-label="Question"><span>${row.question}</span></td>
                        <td data-label="Response"><span>${row.response}</span></td>
                    </tr>
                `);
            });
                
                $('#checklistModal').show();
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'No Data',
                    text: 'No checklist data found for this trip'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Server Error',
                text: 'Failed to load checklist data'
            });
        }
    });
});
    $('#eventModal').show();
    

    return false;
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
                var hasEditReasons = event.edit_reasons && event.edit_reasons !== 'null' && event.edit_reasons !== '';
                var viewRemarksButton = hasEditReasons ? 
                    `<button class="edit-btn2 view-reasons-btn" data-id="${event.id}" style="margin-top: 10px;">View Remarks</button>` : 
                    '';
                
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
                        <td> <p><strong>Status:</strong> <span class="status ${event.status.toLowerCase().replace(/\s+/g, '')}">${event.status}</span></p></td> 
                        <p><strong>Cash Advance:</strong> ${event.cashAdvance}</p>
                        <p><strong>Last modified by: </strong>${event.modifiedby}<br>
                        <strong>Last Modified at: </strong>${formatDateTime(event.modifiedat)}</p>
                        <div class="event-actions" style="margin-top: 15px;">
                            <button class="icon-btn edit" data-tooltip="Edit" data-id="${event.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="icon-btn delete" data-tooltip="Delete" data-id="${event.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                            ${hasEditReasons ? 
                                `<button class="icon-btn view-reasons" data-tooltip="View Edit History" data-id="${event.id}">
                                    <i class="fas fa-history"></i>
                                </button>` : ''}
                        </div>
                    </div>
                `;
                $('#eventList').append(eventThumbnail);
            });
        } else {
            $('#noEventsMessage').show();
        }

        
        $('.event-thumbnail').on('click', function() {
            $(this).next('.event-details').toggle();
        });
    }
});

setTimeout(function() {
    var today = moment().startOf('day');
    var eventsToday = $('#calendar').fullCalendar('clientEvents', function(event) {
        return moment(event.start).isSame(today, 'day');
    });
    
    var formattedDate = today.format('MMMM D, YYYY');
    $('#eventDetails h4').text('Event Details - ' + formattedDate);
    
    $('#eventList').empty();
    $('#noEventsMessage').hide();
    
    if (eventsToday.length > 0) {
        eventsToday.forEach(function(event) {
            var hasEditReasons = event.edit_reasons && event.edit_reasons !== 'null' && event.edit_reasons !== '';
            var viewRemarksButton = hasEditReasons ? 
                `<button class="edit-btn2 view-reasons-btn" data-id="${event.id}" style="margin-top: 10px;">View Remarks</button>` : 
                '';
            
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
                    <td> <p><strong>Status:</strong> <span class="status ${event.status.toLowerCase().replace(/\s+/g, '')}">${event.status}</span></p></td> 
                    <p><strong>Cash Advance:</strong> ${event.cashAdvance}</p>
                    <p><strong>Last modified by: </strong>${event.modifiedby}<br>
                    <strong>Last Modified at: </strong>${formatDateTime(event.modifiedat)}</p>
                    <div class="event-actions" style="margin-top: 15px;">
                        <button class="icon-btn edit" data-tooltip="Edit" data-id="${event.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="icon-btn delete" data-tooltip="Delete" data-id="${event.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                        ${hasEditReasons ? 
                            `<button class="icon-btn view-reasons" data-tooltip="View Edit History" data-id="${event.id}">
                                <i class="fas fa-history"></i>
                            </button>` : ''}
                    </div>
                </div>
            `;
            $('#eventList').append(eventThumbnail);
        });
    } else {
        $('#noEventsMessage').show();
    }
    
    
    $('.fc-today').addClass('fc-day-selected');
}, 500);
            
            
  $('#calendarViewBtn').on('click', function() {
            $(this).addClass('active');
            $('#tableViewBtn').removeClass('active');
            $('#eventsTable, #eventTableBody, .pagination-container, .table-controls, .status-filter-container').slideUp(400);
            $('#calendar').slideDown(400);
            $('body').removeClass('table-view'); 
            $('#calendar').fullCalendar('render');
        });

    $('#tableViewBtn').on('click', function() {
            $(this).addClass('active');
            $('#calendarViewBtn').removeClass('active');
            $('#calendar').slideUp(400);
            $('#eventsTable, #eventTableBody, .pagination-container, .rows-per-page-container, .table-controls, .status-filter-container').slideDown(400); 
            $('body').addClass('table-view');
            currentPage = 1;
            renderTable();
        });
  $(document).ready(function() {
            if ($('#calendarViewBtn').hasClass('active')) {
                $('.status-filter-container, rows-per-page-container, .table-controls').hide();
            }
        });
            
$(document).on('click', '.dropdown-item.edit', function() {
    var eventId = $(this).data('id');
    
    
    var event = eventsData.find(function(e) { return e.id == eventId; });
    
    if (event) {
       
        populateEditModal(event);
    } else {
       
        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'get_trip_by_id',
                id: eventId
            }),
            success: function(response) {
                if (response.success && response.trip) {
                   
                    eventsData.push(response.trip);
                    populateEditModal(response.trip);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to load trip data'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Server error occurred while loading trip data'
                });
            }
        });
    }
});

function populateEditModal(event) {
    
    $('#editForm input[name="editReason"]').prop('checked', false);
    $('#otherReasonText').val('');
    $('#otherReasonContainer').hide();

    
    const tripIdFormatted = `#${String(event.id).padStart(4, '0')}`;
    $('#editModalTripId').text(tripIdFormatted);

    $('#editEventId').val(event.id);
    $('#editEventPlateNo').val(event.truck_plate_no || event.plateNo);
    
    var eventDate = event.date || event.trip_date;
    if (eventDate) {
     eventDate = eventDate.replace(' ', 'T').substring(0, 16);
    }
    $('#editEventDate').val(eventDate);

    const currentSize = event.truck_capacity ? event.truck_capacity + 'ft' : event.size;
    
    populateHelperDropdowns();
    populateDispatcherDropdowns();
    populateConsigneeDropdowns();
    populateClientDropdowns();
    
    populatePortDropdowns(event.port); 
    populateDestinationDropdowns();
    populateShippingLineDropdowns();

   
    $('#editEventContainerNo').val(event.containerNo);
    $('#editEventSize').val(event.truck_capacity ? event.truck_capacity + 'ft' : event.size);
    $('#editEventFCL').val(event.fcl_status || event.size);
    $('#editEventCashAdvance').val(event.cashAdvance);
    $('#editEventAdditionalCashAdvance').val(event.additionalCashAdvance);
    
    $('#editEventStatus').val(event.status);

  
    if (event.status === 'En Route' || event.status === 'Completed') {
        $('#editAdditionalCashContainer').show();
    } else {
        $('#editAdditionalCashContainer').hide();
    }

    populateDriverDropdowns(currentSize, event.driver, function() {
        $('#editEventDriver').val(event.driver);
    });

   
    setTimeout(() => {
        $('#editEventHelper').val(event.helper);
        $('#editEventDispatcher').val(event.dispatcher || '');
        $('#editEventConsignee').val(event.consignee);
        $('#editEventClient').val(event.client);
        
        
        
        $('#editEventDestination').val(event.destination);
        $('#editEventShippingLine').val(event.shippingLine);
    }, 200); 

    
    if (event.status === 'Cancelled') {
        $('#editEventStatus option[value="Cancelled"]').show(); 
        $('#editForm').find(':input:not(.close-btn, .cancel-btn)').prop('disabled', true); 
        $('#editForm').find('.save-btn').hide(); 
    } else if (event.status === 'En Route') {
        $('#editForm').find(':input').prop('disabled', true);
        
        $('#editEventStatus').prop('disabled', false);
        $('#editEventAdditionalCashAdvance').prop('disabled', false);
        $('.edit-reasons-section').find(':input').prop('disabled', false);
        $('#editForm .save-btn').prop('disabled', false).show();
        $('#editForm .cancel-btn').prop('disabled', false);
        
        $('#editEventStatus option[value="Cancelled"]').hide(); 
    } else {
        $('#editForm').find(':input').prop('disabled', false);
        $('#editForm').find('.save-btn').show();
        $('#editEventStatus option[value="Cancelled"]').hide(); 
        $('#editEventPlateNo').prop('disabled', true);
    }

   
    if (event.status === 'Completed') {
        $('#viewExpensesBtn').show();
    } else {
        $('#viewExpensesBtn').hide();
    }

    if (event.driver_id && event.status !== 'Cancelled') {
        $('#viewChecklistBtn').show();
    } else {
        $('#viewChecklistBtn').hide();
    }
    
    const originalData = {
        date: eventDate,
        driver: event.driver,
        plateNo: event.truck_plate_no || event.plateNo,
        helper: event.helper,
        dispatcher: event.dispatcher || '',
        containerNo: event.containerNo,
        client: event.client,
        port: event.port,
        destination: event.destination,
        shippingLine: event.shippingLine,
        consignee: event.consignee,
        size: event.truck_capacity ? event.truck_capacity + 'ft' : event.size,
        fclStatus: event.fcl_status || event.size,
        cashAdvance: event.cashAdvance,
        additionalCashAdvance: event.additionalCashAdvance,
        status: event.status
    };

    
    $('#editForm').data('originalData', originalData);
    $('#editModal').show();
}

$(document).on('click', '.dropdown-item.view-expenses', function() {
    var tripId = $(this).data('id');
    
    
    var tripData = eventsData.find(function(trip) { 
        return trip.id == tripId; 
    });
    
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_expenses',
            tripId: tripId
        }),
        success: function(response) {
            if (response.success) {

                $('#expensesTableBody').empty();
                

                if (tripData) {

                    $('#expensePlateNo').text(tripData.plateNo || tripData.truck_plate_no || 'N/A');
                    $('#expenseContainerNo').text(tripData.containerNo || 'N/A');
                    $('#expenseContainerSize').text(tripData.truck_capacity ? tripData.truck_capacity + 'ft' : tripData.size || 'N/A');
                

                    if (tripData.date || tripData.trip_date) {
                        const tripDate = new Date(tripData.date || tripData.trip_date);
                        const formattedDate = tripDate.toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        $('#expenseTripDate').text(formattedDate);
                    } else {
                        $('#expenseTripDate').text('N/A');
                    }
                    
                    $('#expenseDriverName').text(tripData.driver || 'N/A');
                    $('#expenseHelperName').text(tripData.helper || 'N/A');
                    $('#expenseDestination').text(tripData.destination || 'N/A');
               
                }
                
                const cashAdvance = parseFloat(response.cashAdvance || tripData.cashAdvance || 0);
                const additionalCash = parseFloat(response.additionalCashAdvance || tripData.additionalCashAdvance || 0);
           
                const totalInitialFunds = cashAdvance + additionalCash;
                
                $('#expenseCashAdvance').text('₱' + cashAdvance.toFixed(2));
                $('#expenseAdditionalCash').text('₱' + additionalCash.toFixed(2));
               
                $('#totalInitialFunds').text('₱' + totalInitialFunds.toFixed(2));
                
                let totalExpenses = 0;

if (response.expenses && response.expenses.length > 0) {
    response.expenses.forEach(function(expense) {
        const amount = parseFloat(expense.amount.replace('₱', '').replace(',', ''));
        totalExpenses += amount;

        
        const receiptAttr = expense.receipt_image ? `data-receipt="${expense.receipt_image}"` : '';
        const clickableClass = expense.receipt_image ? 'clickable-expense' : '';

        
        const submittedTime = expense.submitted_time ? formatDateTime(expense.submitted_time) : 'N/A';

        var row = `
            <tr class="${clickableClass}" ${receiptAttr}>
                <td>${expense.expense_type}</td>
                <td>${expense.amount}</td>
                <td>${submittedTime}</td>
            </tr>
        `;
        $('#expensesTableBody').append(row);
    });
} else {
    $('#expensesTableBody').html('<tr><td colspan="3" style="text-align: center;">No additional expenses recorded</td></tr>');
}
                
                $('#totalExpensesAmount').text('₱' + totalExpenses.toFixed(2));
                
                $('#expensesModal').show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to load expenses'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server error occurred while loading expenses'
            });
        }
    });
});

            

            
$('#addScheduleForm').on('submit', function(e) {
    e.preventDefault();

    var selectedDriver = $('#addEventDriver').val();
    var driver = driversData.find(d => d.name === selectedDriver);
    var truckPlateNo = driver && driver.truck_plate_no ? driver.truck_plate_no : $('#addEventPlateNo').val();
    var tripDate = $('#addEventDate').val();

    checkMaintenanceConflict(truckPlateNo, tripDate, function(shouldProceed) {
        if (!shouldProceed) {
            return;
        }
    
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
        port: $('#addEventPort').val(),
        containerNo: $('#addEventContainerNo').val(),
        client: $('#addEventClient').val(),
        destination: $('#addEventDestination').val(),
        shippingLine: $('#addEventShippingLine').val(),
        consignee: $('#addEventConsignee').val(),
        size: $('#addEventSize').val(),
        fcl_status: $('#addEventFCL').val(),
        cashAdvance: $('#addEventCashAdvance').val(),
        additionalCashAdvance: $('#addEventAdditionalCashAdvance').val(),
        status: $('#addEventStatus').val()
    }),
        success: function(response) {
            console.log('Raw response:', response);
            if (response.success) {
                $('#addScheduleModal').hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Trip added successfully!',
                    timer: 1500,
                    showConfirmButton: false
                });
                 highlightTripId = response.trip_id; 
                setTimeout(() => {
                    if ($('#calendarViewBtn').hasClass('active')) {
                        $('#tableViewBtn').click();
                    } else {
                        currentPage = 1;
                        renderTable();
                        updateStats();
                        refreshCalendarEvents();

                    }
                }, 300);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: `Failed to add trip:<br><br>${response.message || 'Unknown error occurred'}`,
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        error: function(xhr, status, error) {
            console.log('XHR:', xhr);
            console.log('Status:', status);
            console.log('Error:', error);
            console.log('Response Text:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server error occurred. Check console for details.',
            });
        }
    });
    });
});

$(document).on('click', '#eventsTable .dropdown-item.view-reasons', function() {
    var eventId = $(this).data('id');
    var eventData = eventsData.find(function(e) { return e.id == eventId; });
    if (!eventData) {
        var $row = $(this).closest('tr');
        eventData = {
            edit_reasons: null, 
            modifiedby: $row.find('td[data-label="Last Modified"] strong').text() || 'System',
            modifiedat: $row.find('td[data-label="Last Modified"]').data('raw-date')
        };
    }
    
    var html = '';

    if (eventData && eventData.edit_reasons) {
        try {
            var log = JSON.parse(eventData.edit_reasons);

            if (log.user_reason || log.automatic_changes) {
                
                if (log.user_reason && log.user_reason.length > 0) {
                    html += '<h5 style="margin-top:0; margin-bottom:8px; color:#333;">User Reason(s)</h5>';
                    html += '<ul style="margin-top:0; padding-left:20px; color:#555;">';
                    log.user_reason.forEach(function(reason) {
                        html += `<li style="margin-bottom:5px;">${reason}</li>`;
                    });
                    html += '</ul>';
                }

                if (log.automatic_changes && log.automatic_changes.length > 0) {
                    html += '<h5 style="margin-top:15px; margin-bottom:8px; color:#333;">Field Changes Detected</h5>';
                    html += '<ul style="margin-top:0; padding-left:20px; color:#555; font-family: monospace; font-size: 1.1em;">';
                    log.automatic_changes.forEach(function(change) {
                        let formattedChange = change.replace(/ to '(.*?)'$/, " to <strong style='color:#006400;'>'$1'</strong>");
                        html += `<li style="margin-bottom:5px;">${formattedChange}</li>`;
                    });
                    html += '</ul>';
                }
                
            } else {
                html += '<h5 style="margin-top:0; margin-bottom:8px; color:#333;">Edit Reason(s)</h5>';
                html += '<ul style="margin-top:0; padding-left:20px; color:#555;">';
                log.forEach(function(reason) {
                    if (reason !== "Trip created" && reason !== '"Trip created"') {
                         html += `<li style="margin-bottom:5px;">${reason}</li>`;
                    }
                });
                html += '</ul>';
            }

        } catch (e) {
            html += '<h5 style="margin-top:0; margin-bottom:8px; color:#333;">Edit Reason</h5>';
            html += '<p style="margin-top:0; color:#555;">' + eventData.edit_reasons + '</p>';
        }

        html += '<p style="font-style:italic; margin-top:20px; padding-top:10px; border-top:1px solid #eee; color:#666; font-size: 0.9em;">';
        html += 'Last modified by: <strong>' + (eventData.modifiedby || eventData.last_modified_by || 'System') + '</strong><br>' ;
        html += ' ' + formatDateTimeSingleLine(eventData.modifiedat || eventData.last_modified_at);
        html += '</p>';
        
        $('#editReasonsContent').html(html);

    } else {
        $('#editReasonsContent').html('<div style="padding: 15px; background: #f5f5f5; border-radius: 5px;">'+
            '<p>No edit remarks recorded for this trip</p></div>');
    }
    
    $('#editReasonsModal').show();
});


function validateEditReasons() {
   
    const checkedReasons = $('input[name="editReason"]:checked').length;
    const otherReasonText = $('#otherReasonText').val().trim();
    
    if (checkedReasons === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select at least one reason for editing this trip',
            confirmButtonColor: '#3085d6',
        });
        return false;
    }
    
  

    if ($('#reason7').is(':checked') && $('#otherReasonText').val().trim() === '') {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please specify the "Other" reason',
            confirmButtonColor: '#3085d6',
        });
        $('#otherReasonText').focus();
        return false;
    }
    
    return true;
}


$('#editForm').on('submit', function(e) {
    e.preventDefault();
    
    const formatLogDateTime = (isoString) => {
        if (!isoString || isoString.length < 16) return 'N/A';
        try {
            const date = new Date(isoString);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        } catch (e) { return isoString; }
    };

    if ($('input[name="editReason"]:checked').length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Reason Required',
            text: 'Please select at least one reason for editing this trip',
            confirmButtonColor: '#3085d6'
        });
        return; 
    }
    
    if ($('#reason7').is(':checked') && $('#otherReasonText').val().trim() === '') {
        Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please specify the "Other" reason for editing this trip',
            confirmButtonColor: '#3085d6'
        });
        $('#otherReasonText').focus();
        return;
    }

    var selectedDriver = $('#editEventDriver').val();
    var driver = driversData.find(d => d.name === selectedDriver);
    var truckPlateNo = driver && driver.truck_plate_no ? driver.truck_plate_no : $('#editEventPlateNo').val();
    var tripDate = $('#editEventDate').val();

    const originalData = $(this).data('originalData');

    const newData = {
        date: $('#editEventDate').val(),
        driver: selectedDriver,
        plateNo: truckPlateNo,
        helper: $('#editEventHelper').val(),
        dispatcher: $('#editEventDispatcher').val() || '',
        containerNo: $('#editEventContainerNo').val(),
        client: $('#editEventClient').val(),
        port: $('#editEventPort').val(),
        destination: $('#editEventDestination').val(),
        shippingLine: $('#editEventShippingLine').val(),
        consignee: $('#editEventConsignee').val(),
        size: $('#editEventSize').val(),
        fclStatus: $('#editEventFCL').val(),
        cashAdvance: $('#editEventCashAdvance').val() || "0",
        additionalCashAdvance: $('#editEventAdditionalCashAdvance').val() || "0",
        status: $('#editEventStatus').val()
    };

    let detectedChanges = [];
    
    const isDifferent = (a, b) => {
        const valA = a === null || typeof a === 'undefined' ? "" : String(a);
        const valB = b === null || typeof b === 'undefined' ? "" : String(b);
        return valA !== valB;
    };

    if (isDifferent(originalData.date, newData.date)) {
        const oldDate = formatLogDateTime(originalData.date);
        const newDate = formatLogDateTime(newData.date);
        detectedChanges.push(`Date changed from '${oldDate}' to '${newDate}'`);
    }

    if (isDifferent(originalData.driver, newData.driver)) {
        detectedChanges.push(`Driver changed from '${originalData.driver || 'N/A'}' to '${newData.driver}'`);
    }
    if (isDifferent(originalData.plateNo, newData.plateNo)) {
        detectedChanges.push(`Plate No changed from '${originalData.plateNo || 'N/A'}' to '${newData.plateNo}'`);
    }
    if (isDifferent(originalData.helper, newData.helper)) {
        detectedChanges.push(`Helper changed from '${originalData.helper || 'N/A'}' to '${newData.helper || 'N/A'}'`);
    }
    if (isDifferent(originalData.dispatcher, newData.dispatcher)) {
        detectedChanges.push(`Dispatcher changed from '${originalData.dispatcher || 'N/A'}' to '${newData.dispatcher || 'N/A'}'`);
    }
    if (isDifferent(originalData.containerNo, newData.containerNo)) {
        detectedChanges.push(`Container No changed from '${originalData.containerNo || 'N/A'}' to '${newData.containerNo}'`);
    }
    if (isDifferent(originalData.client, newData.client)) {
        detectedChanges.push(`Client changed from '${originalData.client || 'N/A'}' to '${newData.client}'`);
    }
    if (isDifferent(originalData.port, newData.port)) {
        detectedChanges.push(`Port changed from '${originalData.port || 'N/A'}' to '${newData.port}'`);
    }
    if (isDifferent(originalData.destination, newData.destination)) {
        detectedChanges.push(`Destination changed from '${originalData.destination || 'N/A'}' to '${newData.destination}'`);
    }
    if (isDifferent(originalData.shippingLine, newData.shippingLine)) {
        detectedChanges.push(`Shipping Line changed from '${originalData.shippingLine || 'N/A'}' to '${newData.shippingLine || 'N/A'}'`);
    }
    if (isDifferent(originalData.consignee, newData.consignee)) {
        detectedChanges.push(`Consignee changed from '${originalData.consignee || 'N/A'}' to '${newData.consignee || 'N/A'}'`);
    }
    if (isDifferent(originalData.size, newData.size)) {
        detectedChanges.push(`Size changed from '${originalData.size || 'N/A'}' to '${newData.size}'`);
    }
    if (isDifferent(originalData.fclStatus, newData.fclStatus)) {
        detectedChanges.push(`FCL Status changed from '${originalData.fclStatus || 'N/A'}' to '${newData.fclStatus}'`);
    }
    if (parseFloat(originalData.cashAdvance || 0) !== parseFloat(newData.cashAdvance || 0)) {
        detectedChanges.push(`Cash Advance changed from '₱${originalData.cashAdvance || 0}' to '₱${newData.cashAdvance}'`);
    }
    if (parseFloat(originalData.additionalCashAdvance || 0) !== parseFloat(newData.additionalCashAdvance || 0)) {
        detectedChanges.push(`Add. Cash changed from '₱${originalData.additionalCashAdvance || 0}' to '₱${newData.additionalCashAdvance}'`);
    }
    if (isDifferent(originalData.status, newData.status)) {
        detectedChanges.push(`Status changed from '${originalData.status}' to '${newData.status}'`);
    }
    
    var userReasons = [];
    $('input[name="editReason"]:checked').each(function() {
        if ($(this).val() === 'Other') {
            userReasons.push('Other: ' + $('#otherReasonText').val().trim());
        } else {
            userReasons.push($(this).val());
        }
    });

    const finalLogEntry = {
        user_reason: userReasons,
        automatic_changes: detectedChanges.length > 0 ? detectedChanges : ["No data fields were changed"]
    };

    checkMaintenanceConflict(truckPlateNo, tripDate, function(shouldProceed) {
        if (!shouldProceed) return;

        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
            action: 'edit',
            id: $('#editEventId').val(),
            plateNo: truckPlateNo,
            date: newData.date,
            driver: newData.driver,
            helper: newData.helper,
            dispatcher: newData.dispatcher,
            containerNo: newData.containerNo,
            client: newData.client,
            port: newData.port,
            destination: newData.destination,
            shippingLine: newData.shippingLine,
            consignee: newData.consignee,
            size: newData.size,
            fclStatus: newData.fclStatus,
            cashAdvance: newData.cashAdvance,
            additionalCashAdvance: newData.additionalCashAdvance,
            status: newData.status,
            editReasons: JSON.stringify(finalLogEntry)
            }),
            success: function(response) {
                if (response.success) {
                    const editedTripId = $('#editEventId').val();

                    const eventIndex = eventsData.findIndex(e => e.id == editedTripId);
                    if (eventIndex !== -1) {
                        const currentUsername = $('.profile-name').text().trim() || 'User';
                        const newTimestamp = new Date().toISOString();
                        Object.assign(eventsData[eventIndex], {
                            date: newData.date,
                            trip_date: newData.date,
                            driver: newData.driver,
                            helper: newData.helper,
                            dispatcher: newData.dispatcher,
                            containerNo: newData.containerNo,
                            client: newData.client,
                            port: newData.port,
                            destination: newData.destination,
                            shippingLine: newData.shippingLine,
                            consignee: newData.consignee,
                            fcl_status: newData.fclStatus,
                            cashAdvance: newData.cashAdvance,
                            additionalCashAdvance: newData.additionalCashAdvance,
                            status: newData.status,
                            plateNo: truckPlateNo,
                            truck_plate_no: truckPlateNo,
                            truck_capacity: newData.size.replace('ft', ''),
                            size: newData.size,
                            edit_reasons: JSON.stringify(finalLogEntry), 
                            modifiedat: newTimestamp,
                            modifiedby: currentUsername
                        });
                    }

                    $('#editModal').hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Trip has been updated successfully',
                        timer: 2000,
                        showConfirmButton: false,
                        timerProgressBar: true
                    });
                    
                    updateStats();
                    refreshCalendarEvents(); 
                    renderTable(); 
                    updateEventModalDetails();

                    if ($('#tableViewBtn').hasClass('active')) {
                        highlightTripId = editedTripId;
                        renderTable(); 
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update trip'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'AJAX Error',
                    text: 'A server error occurred.'
                });
            }
        });
    });
});
                
            
            renderTable();
        });


function deleteTrip(tripId, rowElement) {
    Swal.fire({
        title: 'Reason for Deletion',
        input: 'textarea',
        inputLabel: 'Please provide a reason for deleting this trip.',
        inputPlaceholder: 'Type your reason here...',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        inputValidator: (value) => {
            if (!value) {
                return 'You need to provide a reason!';
            }
        }
    }).then((reasonResult) => {
        if (reasonResult.isConfirmed && reasonResult.value) {
            const deleteReason = reasonResult.value;

            Swal.fire({
                title: 'Are you absolutely sure?',
                text: "This trip will be marked as deleted. You can restore it later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((confirmResult) => {
                if (confirmResult.isConfirmed) {
                    $.ajax({
                        url: 'include/handlers/trip_operations.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            action: 'delete',
                            id: tripId,
                            reason: deleteReason
                        }),
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Trip has been marked as deleted.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                const rowsOnPage = $('#eventTableBody tr').length;
                                if (rowsOnPage === 1 && currentPage > 1) {
                                    currentPage--; 
                                }

                                if (rowElement) {
                                    rowElement.fadeOut(500, function() {
                                        $(this).remove();

                                        
                                        $.ajax({
                                            url: 'include/handlers/trip_operations.php',
                                            type: 'POST',
                                            contentType: 'application/json',
                                            data: JSON.stringify({
                                                action: 'fetchNextRow',
                                                page: currentPage,
                                                perPage: rowsPerPage,
                                                statusFilter: currentStatusFilter,
                                                sortOrder: dateSortOrder,
                                                dateFrom: $('#dateFrom').val(),
                                                dateTo: $('#dateTo').val(),
                                                searchTerm: $('#searchInput').val()
                                            }),
                                            success: function(nextRowResponse) {
                                                if (nextRowResponse.success && nextRowResponse.rowHtml) {
                                                    $('#eventTableBody').append(nextRowResponse.rowHtml);
                                                } else {
                                                    renderTable(); 
                                                }
                                            },
                                            error: function() {
                                                renderTable(); 
                                            }
                                        });
                                    });
                                } else {
                                    renderTable();
                                }

                                updateStats();

                                if (!$('#tableViewBtn').hasClass('active')) {
                                    refreshCalendarEvents();
                                }

                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Failed to delete trip'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Server error occurred during deletion'
                            });
                        }
                    });
                }
            });
        }
    });
}



$(document).on('click', '.dropdown-item.delete', function() {
    var eventId = $(this).data('id');
   
    var rowElement = $(this).closest('tr');
    deleteTrip(eventId, rowElement); 
});


$('#eventModalDeleteBtn').on('click', '.dropdown-item.delete', function() {
    var event = $('#eventModal').data('currentEvent');
    var tripId = event ? event.id : $('#eventModal').data('eventId');
    $('#eventModal').hide();
    deleteTrip(tripId);
});

   document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('toggleSidebarBtn');
        const sidebar = document.querySelector('.sidebar');
        const backdrop = document.getElementById('sidebar-backdrop'); 

        const openSidebar = () => {
            sidebar.classList.add('expanded');
            backdrop.classList.add('show');
            document.body.classList.add('no-scroll');
        };


        const closeSidebar = () => {
            sidebar.classList.remove('expanded');
            backdrop.classList.remove('show');
            document.body.classList.remove('no-scroll');
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


       $('#otherReasonText').on('input', function() {
if ($(this).val().trim() !== '') {
$('#reason7').prop('checked', true); 
}
});


   $('#reason7').on('change', function() {
if (!$(this).is(':checked')) {
$('#otherReasonText').val('');
}
});

function formatCurrency(amount) {
    return '₱' + parseFloat(amount || 0).toFixed(2);
}

function populatePortDropdowns(currentPortValue = null) {
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'get_ports' }),
        success: function(response) {
            if (response.success && response.ports) {
                var options = '<option value="" disabled selected>Select Port</option>';
                response.ports.forEach(function(port) {
                    
                    options += `<option value="${port.name}">${port.name}</option>`;
                });
                
                $('#editEventPort, #addEventPort').html(options);
                
                
                if (currentPortValue) {
                  
                    $('#editEventPort').val(currentPortValue.trim());
                }
            }
        },
        error: function() {
            console.error('Error fetching ports');
        }
    });
}

    function searchTrips() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const table = document.getElementById('eventsTable');
        const rows = table.getElementsByTagName('tr');
        
      
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            let found = false;
            
            
            for (let j = 0; j < row.cells.length - 1; j++) {
                const cell = row.cells[j];
                if (cell.textContent.toLowerCase().includes(searchTerm)) {
                    found = true;
                    break;
                }
            }
            
            if (found || searchTerm === '') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }

   function closeModal(modalId) {
    let modalToClose = null;

    if (modalId) {
        modalToClose = document.getElementById(modalId);
    } else if (event && event.target) {

        modalToClose = event.target.closest('.modal');
    }
    if (!modalToClose) return; 

    modalToClose.classList.add('closing');
    setTimeout(() => {

        modalToClose.style.display = 'none';
        modalToClose.classList.remove('closing');

        if (modalToClose.id === 'addScheduleModal') {
            resetAddScheduleForm();
        }
        if (modalToClose.id === 'editModal') {
            $('#editEventDriver').prop('disabled', false);
            $('#editEventSize').prop('disabled', false);
        }
        if (modalToClose.id === 'receiptModal') {
            $('#expensesModal .expensemodal-content').removeClass('shifted');
        }

    }, 300); 
}

$(document).on('click', '.dropdown-item.full-delete', function(e) {
    e.stopPropagation(); 
    
    const tripId = $(this).data('id');
    const $row = $(this).closest('tr');

    Swal.fire({
        title: 'Permanently Delete Trip?',
        text: "This action cannot be undone! The trip will be permanently deleted from the database.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete permanently!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'include/handlers/trip_operations.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'full_delete',
                    id: tripId
                }),
                success: function(response) {
                    if (response.success) {
                       
                        $('.stat-value').eq(0).text(response.stats.pending);
                        $('.stat-value').eq(1).text(response.stats.enroute);
                        $('.stat-value').eq(2).text(response.stats.completed);
                        $('.stat-value').eq(3).text(response.stats.cancelled);
                        $('.stat-value').eq(4).text(response.stats.total);

                        
                        $row.remove();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'The trip has been permanently deleted.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to delete trip'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Server error occurred'
                    });
                }
            });
        }
    });
});

    function loadDeletedTrips() {
        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            data: JSON.stringify({ action: 'get_deleted_trips' }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('#deletedTripsBody').empty();
                    response.trips.forEach(function(trip) {
                        var row = `
                            <tr>
                                <td>${trip.plate_no || 'N/A'}</td>
                                <td>${formatDateTime(trip.date)}</td>
                                <td>${trip.driver || 'N/A'}</td>
                                <td>${trip.helper || 'N/A'}</td>
                                <td>${trip.dispatcher || 'N/A'}</td>
                                <td>${trip.container_no || 'N/A'}</td>
                               <td>${trip.client || 'N/A'}${trip.port ? ' - ' + trip.port : ''}</td>
                                <td>${trip.destination || 'N/A'}</td>
                                <td>${trip.shippine_line || 'N/A'}</td>
                                <td>${trip.consignee || 'N/A'}</td>
                                <td>${trip.size || 'N/A'}</td>
                                <td>${trip.cash_adv || 'N/A'}</td>
                                <td><span class="status ${trip.status ? trip.status.toLowerCase().replace(/\s+/g, '') : ''}">${trip.status || 'N/A'}</span></td>
                                <td>${trip.last_modified_by || 'System'}</td>
                                <td>${formatDateTime(trip.last_modified_at)}</td>
                                <td>${trip.delete_reason || 'No reason provided'}</td>
                                <td><button class="restore-btn" data-id="${trip.trip_id}">Restore</button></td>
                            </tr>
                        `;
                        $('#deletedTripsBody').append(row);
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Server error occurred while loading deleted trips');
            }
        });
    }

  $(document).on('click', '.dropdown-item.restore', function() {
    const tripId = $(this).data('id');
    const $row = $(this).closest('tr');

    Swal.fire({
        title: 'Restore Trip?',
        text: "Are you sure you want to restore this trip?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, restore it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'include/handlers/trip_operations.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'restore',
                    id: tripId
                }),
                success: function(response) {
                    if (response.success) {
                        
                        $('.stat-value').eq(0).text(response.stats.pending);
                        $('.stat-value').eq(1).text(response.stats.enroute);
                        $('.stat-value').eq(2).text(response.stats.completed);
                        $('.stat-value').eq(3).text(response.stats.cancelled);
                        $('.stat-value').eq(4).text(response.stats.total);

                        $row.remove();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Trip restored successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        renderTable(); 
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to restore trip'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Server error occurred while restoring trip'
                    });
                }
            });
        }
    });
});
function updateEventModalDetails() {
   
    const newValues = {
        date: $('#editEventDate').val(),
        driver: $('#editEventDriver').val(),
        helper: $('#editEventHelper').val(),
        dispatcher: $('#editEventDispatcher').val(),
        containerNo: $('#editEventContainerNo').val(),
        client: $('#editEventClient').val(),
        port: $('#editEventPort').val(),
        destination: $('#editEventDestination').val(),
        shippingLine: $('#editEventShippingLine').val(),
        consignee: $('#editEventConsignee').val(),
        cashAdvance: $('#editEventCashAdvance').val(),
        additionalCashAdvance: $('#editEventAdditionalCashAdvance').val(),
        status: $('#editEventStatus').val(),
        plateNo: $('#editEventPlateNo').val(),
        size: $('#editEventSize').val() 
    };


    $('#modal-status').text(newValues.status)
        .removeClass()
        .addClass('status ' + newValues.status.toLowerCase().replace(/\s+/g, '-'));
    
    $('#modal-origin').text(newValues.port || 'N/A');
    $('#modal-destination').text(newValues.destination || 'N/A');
    $('#modal-plate-no').text(newValues.plateNo || 'N/A');
    $('#modal-date').html(formatDateTime(newValues.date));
    $('#modal-size').text(newValues.size || 'N/A');
    $('#modal-container-no').text(newValues.containerNo || 'N/A');
    
    $('#modal-driver').text(newValues.driver || 'N/A');
    $('#modal-helper').text(newValues.helper || 'N/A');
    $('#modal-dispatcher').text(newValues.dispatcher || 'N/A');
    
    $('#modal-cash-advance').text('₱' + (parseFloat(newValues.cashAdvance || 0)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#modal-additional-cash').text('₱' + (parseFloat(newValues.additionalCashAdvance || 0)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    

    $('#modal-client-name').text(newValues.client || 'N/A');
    $('#modal-shipping-line').text(newValues.shippingLine || 'N/A');
    $('#modal-consignee').text(newValues.consignee || 'N/A');

   
    var currentEvent = $('#eventModal').data('currentEvent');
    if (currentEvent) {
        Object.assign(currentEvent, newValues); 
        $('#eventModal').data('currentEvent', currentEvent);
    }
}
    document.addEventListener('DOMContentLoaded', function() {
        
        const currentPage = window.location.pathname.split('/').pop();
        
        
        const sidebarLinks = document.querySelectorAll('.sidebar-item a');
        
        
        sidebarLinks.forEach(link => {
            const linkPage = link.getAttribute('href').split('/').pop();
            
           
            if (linkPage === currentPage) {
                link.parentElement.classList.add('active');
                
                
                const icon = link.parentElement.querySelector('.icon2');
                if (icon) {
                    icon.style.color = 'white';
                }
            }
        });
    });


    document.getElementById('reason7').addEventListener('change', function() {
        const otherReasonContainer = document.getElementById('otherReasonContainer');
        otherReasonContainer.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            document.getElementById('otherReasonText').value = '';
        }
    });


    document.getElementById('otherReasonText').addEventListener('input', function() {
        if (this.value.trim() !== '') {
            document.getElementById('reason7').checked = true;
            document.getElementById('otherReasonContainer').style.display = 'block';
        }
    });


    document.querySelector('form').addEventListener('submit', function(e) {
        const otherCheckbox = document.getElementById('reason7');
        const otherReasonText = document.getElementById('otherReasonText').value.trim();
        
       if (otherCheckbox.checked && otherReasonText === '') {
        e.preventDefault();
        
        
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please specify the other reason for editing this trip',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6',
            didClose: () => {
                
                document.getElementById('otherReasonText').focus();
            }
        });
        
        return false;
    }
});

$(document).on('click', '.dropdown-item.view-checklist', function() {
    var tripId = $(this).data('id');
    var driverId = $(this).data('driver-id');
    
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_checklist',
            trip_id: tripId
        }),
        success: function(response) {
            if (response.success && response.checklist) {
                $('#checklistTableBody').empty();
                
                var checklist = response.checklist;
                var rows = [
                    { question: 'No body fatigue?', response: checklist.no_fatigue ? 'Yes' : 'No' },
                    { question: 'Did not take illegal drugs?', response: checklist.no_drugs ? 'Yes' : 'No' },
                    { question: 'No mental distractions?', response: checklist.no_distractions ? 'Yes' : 'No' },
                    { question: 'No body illness?', response: checklist.no_illness ? 'Yes' : 'No' },
                    { question: 'Fit to work?', response: checklist.fit_to_work ? 'Yes' : 'No' },
                    { question: 'Alcohol test reading', response: checklist.alcohol_test },
                    { question: 'Hours of sleep', response: checklist.hours_sleep },
                    { question: 'Submitted at', response: formatDateTime(checklist.submitted_at) }
                ];
                
                rows.forEach(function(row) {
                    $('#checklistTableBody').append(`
                        <tr>
                            <td>${row.question}</td>
                            <td>${row.response}</td>
                        </tr>
                    `);
                });
                
                $('#checklistModal').show();
            } else {
                Swal.fire({
                icon: 'info',
                title: 'No Data',
                text: 'No checklist data found for this trip'
                });
               
            }
        },
        error: function() {
            Swal.fire({
            icon: 'error',
            title: 'Server Error',
            text: 'Failed to load checklist data'
            });
        }
    });
});


$('#editEventStatus').on('change', function() {
    if ($(this).val() !== 'Cancelled') {
        $('#viewChecklistBtn').show();
    } else {
        $('#viewChecklistBtn').hide();
    }
});

    function updateStats() {
        $.ajax({
            url: 'include/handlers/triplogstats.php',
            type: 'GET',
            dataType: 'json',
            success: function(stats) {
                $('.stat-value').eq(0).text(stats.pending);
                $('.stat-value').eq(1).text(stats.enroute);
                $('.stat-value').eq(2).text(stats.completed);
                $('.stat-value').eq(3).text(stats.cancelled);
                $('.stat-value').eq(4).text(stats.total);
            }
        });
    }

    updateStats();

    console.log("Filtering by:", currentStatusFilter, "Found:", filteredEvents.length, "events");


function goToPage(page) {
    currentPage = page;
    renderTable();
}

function changeRowsPerPage() {
    rowsPerPage = parseInt($('#rowsPerPage').val());
    currentPage = 1;
    renderTable();
}

function updateTableInfo(totalItems, currentItemsCount) {
    const tableInfo = $('.table-info');
    
    if (!totalItems || totalItems === 0) {
        tableInfo.text('No entries found');
        return;
    }

 const start = ((currentPage - 1) * rowsPerPage) + 1;
    const end = start + currentItemsCount - 1;

    tableInfo.text(`Showing ${start} to ${end} of ${totalItems} entries`);
 
}   



$(document).on('click', '.clickable-expense', function() {
    const receiptUrl = $(this).data('receipt');
    if (receiptUrl) {
        $('#expensesModal .expensemodal-content').addClass('shifted');

        $('#receiptImageSrc').attr('src', receiptUrl);
        $('#receiptModal').css('display', 'flex');
    }
});

function closeReceiptModal() {
    $('#receiptModal').hide();
    $('#expensesModal .expensemodal-content').removeClass('shifted');
}

$('.receipt-close').on('click', function(e) {
    e.stopPropagation(); 
    closeReceiptModal();
});

$('#receiptModal').on('click', function(e) {

    if (e.target === this) {
        closeReceiptModal();
    }
});

function cancelTrip(tripId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will change the trip's status to 'Cancelled'.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, cancel it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'include/handlers/trip_operations.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'cancel_trip',
                    id: tripId
                }),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: 'The trip has been successfully cancelled.',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        updateStats();
                        
                        
                        const $row = $(`tr[data-trip-id="${tripId}"]`);
                        if ($row.length) {
                           
                            const $statusCell = $row.find('td:contains("Pending"), td:contains("En Route"), td:contains("Completed")').filter(function() {
                                return $(this).find('.status').length > 0;
                            });
                            
                            if ($statusCell.length) {
                                $statusCell.html('<span class="status cancelled">Cancelled</span>');
                            }
                            
                            
                            const eventIndex = eventsData.findIndex(e => e.id == tripId);
                            if (eventIndex !== -1) {
                                eventsData[eventIndex].status = 'Cancelled';
                            }
                        }
                        
                        if ($('#calendarViewBtn').hasClass('active')) {
                            refreshCalendarEvents();
                        }
                    } else {
                        Swal.fire('Error', response.message || 'Failed to cancel trip.', 'error');
                    }
                }
            });
        }
    });
}

$(document).on('click', '#eventModal .cancel-trip', function() {
    var tripId = $(this).data('id');
    if (tripId) {
        $('#eventModal').hide(); 
        cancelTrip(tripId);
    }
});

$(document).on('click', '.dropdown-item.cancel-trip', function() {
    var tripId = $(this).data('id');
    if (tripId) {
        cancelTrip(tripId);
    }
});

function refreshCalendarEvents() {
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_active_trips',
            perPage: 9999, 
            page: 1,
            _cacheBuster: new Date().getTime()
        }),
        success: function(response) {
            if (response.success) {

                var newCalendarEvents = response.trips.map(function(trip) {
                    return {
                        id: trip.trip_id,
                        title: trip.client + ' - ' + trip.destination,
                        start: trip.trip_date,
                        plateNo: trip.plate_no,
                        driver: trip.driver,
                        driver_id: trip.driver_id,
                        helper: trip.helper,
                        dispatcher: trip.dispatcher,
                        containerNo: trip.container_no,
                        port: trip.port,
                        client: trip.client,
                        destination: trip.destination,
                        shippingLine: trip.shipping_line,
                        consignee: trip.consignee,
                        size: trip.fcl_status,
                        cashAdvance: trip.cash_advance,
                        additionalCashAdvance: trip.additional_cash_advance,
                        status: trip.status,
                        modifiedby: trip.last_modified_by,
                        modifiedat: trip.last_modified_at,
                        truck_plate_no: trip.plate_no,
                        truck_capacity: trip.truck_capacity,
                        edit_reasons: trip.edit_reason,
                        fcl_status: trip.fcl_status
                    };
                });

                 eventsData = response.trips.map(trip => {
                     return {
                        id: trip.trip_id,
                        plateNo: trip.plate_no,
                        date: trip.trip_date,
                        driver: trip.driver,
                        driver_id: trip.driver_id,
                        helper: trip.helper,
                        dispatcher: trip.dispatcher,
                        containerNo: trip.container_no,
                        client: trip.client,
                        port: trip.port,
                        destination: trip.destination,
                        shippingLine: trip.shipping_line,
                        consignee: trip.consignee,
                        size: trip.fcl_status,
                        cashAdvance: trip.cash_advance,
                        additionalCashAdvance: trip.additional_cash_advance,
                        status: trip.status,
                        modifiedby: trip.last_modified_by,
                        modifiedat: trip.last_modified_at,
                        truck_plate_no: trip.plate_no,
                        truck_capacity: trip.truck_capacity,
                        edit_reasons: trip.edit_reason,
                        fcl_status: trip.fcl_status
                    };
                });

                $('#calendar').fullCalendar('removeEvents');
                $('#calendar').fullCalendar('addEventSource', newCalendarEvents);
            }
        },
        error: function() {
            Swal.fire('Error', 'Could not refresh calendar data.', 'error');
        }
    });
}
    </script>

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
  
  checkForIncomingNavigation() {
    
    const referrer = document.referrer;
    const currentDomain = window.location.origin;
    
    
    const shouldShowLoading = sessionStorage.getItem('showAdminLoading');
    
    if ((referrer && referrer.startsWith(currentDomain)) || shouldShowLoading) {
      
      sessionStorage.removeItem('showAdminLoading');
      
      
      this.show('Loading Page', 'Loading content...');
      
      
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
      
      if (e.target.closest('.swal2-container, .swal2-popup, .swal2-modal, .modal, .modal-content, .fc-event, #calendar')) {
        return;
      }
      
      const link = e.target.closest('a');
      if (link && !link.hasAttribute('data-no-loading') && 
          link.href && !link.href.startsWith('javascript:') &&
          !link.href.startsWith('#') && !link.href.startsWith('mailto:') &&
          !link.href.startsWith('tel:')) {
        
       
        try {
          const linkUrl = new URL(link.href);
          const currentUrl = new URL(window.location.href);
          
          if (linkUrl.origin !== currentUrl.origin) {
            return; 
          }
          
          
          if (linkUrl.pathname === currentUrl.pathname) {
            return;
          }
          
        } catch (e) {
          return; 
        }
        
        e.preventDefault();
        
       
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
            progress = 90; 
          }
          loading.updateProgress(Math.min(progress, 90));
        }, 150);
        
       
        const minLoadTime = 1200;
        
        setTimeout(() => {
          
          loading.updateProgress(100);
          setTimeout(() => {
            window.location.href = link.href;
          }, 300);
        }, minLoadTime);
      }
    });

   
    document.addEventListener('submit', (e) => {
      
      if (e.target.closest('.swal2-container, .swal2-popup, .modal')) {
        return;
      }
      

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


document.addEventListener('DOMContentLoaded', () => {
  AdminLoading.init();

  const loadingGif = document.querySelector('.loading-gif');
  if (loadingGif) {
    loadingGif.style.transition = 'opacity 0.7s ease 0.3s';
  }
  

  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
    
      setTimeout(() => {
        AdminLoading.hideManual();
      }, 500);
    }
  });
});


window.AdminLoading = AdminLoading;

</script>
    <footer class="site-footer">

        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
        </div>
    </footer>
    </body>
    </html> 