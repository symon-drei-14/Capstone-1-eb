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
    .swal2-container {
  z-index: 999999 !important;
}   
    </style>
    <body>
        <?php
        require 'include/handlers/dbhandler.php';
        require 'include/handlers/triplogstats.php';


    $tripStats = getTripStatistics($conn);
        session_start();

    
    $sql = "SELECT a.*, t.plate_no as truck_plate_no, t.capacity as truck_capacity, a.edit_reasons,d.driver_id
            FROM assign a
            LEFT JOIN drivers_table d ON a.driver = d.name
            LEFT JOIN truck_table t ON d.assigned_truck_id = t.truck_id
            WHERE a.is_deleted = 0"; // Add this WHERE clause
    $result = $conn->query($sql);
    $eventsData = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $eventsData[] = [
                'id' => $row['trip_id'],
                'plateNo' => $row['plate_no'],
                'date' => $row['date'],
                'driver' => $row['driver'],
                'driver_id' => $row['driver_id'], // eto yung driver_id
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

    // Fetch drivers with their assigned truck capacity
    $driverQuery = "SELECT d.driver_id, d.name, t.plate_no as truck_plate_no, t.capacity, d.assigned_truck_id 
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
                'assigned_truck_id' => $driverRow['assigned_truck_id']
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

     <!-- Edit Modal -->
     <div id="editModal" class="modal">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: scroll;">
            <span class="close">&times;</span>
            <h3 style="margin-top: 0;">Edit Trip</h3>
            <form id="editForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; overflow: hidden;">
                <input type="hidden" id="editEventId" name="eventId">
                
                <!-- Column 1 -->
                <div style="display: flex; flex-direction: column;">
                    <label for="editEventSize">Shipment Size:</label>
                    <select id="editEventSize" name="eventSize" required style="width: 100%;">
                        <option value="">Select Size</option>
                        <option value="20ft">20ft</option>
                        <option value="40ft">40ft</option>
                        <option value="40ft HC">40ft HC</option>
                        <option value="45ft">45ft</option>
                    </select>

                    <label for="editEventPlateNo">Plate No.:</label>
                    <input type="text" id="editEventPlateNo" name="eventPlateNo" required style="width: 100%;">

                    <label for="editEventDate">Date & Time:</label>
                    <input type="datetime-local" id="editEventDate" name="editEventDate" required style="width: 100%;">

                    <label for="editEventDriver">Driver:</label>
                    <select id="editEventDriver" name="eventDriver" required style="width: 100%;">
                        <option value="">Select Driver</option>
                    </select>

                    <label for="editEventHelper">Helper:</label>
                    <input type="text" id="editEventHelper" name="eventHelper" required style="width: 100%;">
                </div>

                <!-- Column 2 -->
                <div style="display: flex; flex-direction: column;">
                    <label for="editEventDispatcher">Dispatcher:</label>
                    <input type="text" id="editEventDispatcher" name="eventDispatcher" required style="width: 100%;">

                    <label for="editEventContainerNo">Container No.:</label>
                    <input type="text" id="editEventContainerNo" name="eventContainerNo" required style="width: 100%;">

                    <label for="editEventClient">Client:</label>
                    <select id="editEventClient" name="eventClient" required style="width: 100%;">
                        <option value="">Select Client</option>
                        <option value="Maersk">Maersk</option>
                        <option value="MSC">MSC</option>
                        <option value="COSCO">COSCO</option>
                        <option value="CMA CGM">CMA CGM</option>
                        <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                        <option value="Evergreen">Evergreen</option>
                    </select>

                    <label for="editEventDestination">Destination:</label>
                    <select id="editEventDestination" name="eventDestination" required style="width: 100%;">
                        <option value="">Select Destination</option>
                        <option value="Manila Port">Manila Port</option>
                        <option value="Batangas Port">Batangas Port</option>
                        <option value="Subic Port">Subic Port</option>
                        <option value="Cebu Port">Cebu Port</option>
                        <option value="Davao Port">Davao Port</option>
                    </select>

                    <label for="editEventStatus">Status:</label>
                    <select id="editEventStatus" name="eventStatus" required style="width: 100%;">
                        <option value="Pending">Pending</option>
                        <option value="En Route">En Route</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Full width fields -->
                <div style="grid-column: span 2;">
                    <label for="editEventShippingLine">Shipping Line:</label>
                    <select id="editEventShippingLine" name="eventShippingLine" required style="width: 35%;">
                        <option value="">Select Shipping Line</option>
                        <option value="Maersk Line">Maersk Line</option>
                        <option value="Mediterranean Shipping Co.">Mediterranean Shipping Co.</option>
                        <option value="COSCO Shipping">COSCO Shipping</option>
                        <option value="CMA CGM">CMA CGM</option>
                        <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                    </select>
                
                    <label for="editEventConsignee">Consignee:</label>
                    <input type="text" id="editEventConsignee" name="eventConsignee" required style="width: 25%;">
                    <br>
                    <label for="editEventCashAdvance">Cash Advance:</label>
                    <input type="text" id="editEventCashAdvance" name="eventCashAdvance" required style="width: 20%;">
                </div>

                
        <div class="edit-reasons-section" style="grid-column: span 2; margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; border: 1px solid #ddd; width: 100%;">
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
                <label for="reason6" style="margin: 0; cursor: pointer; flex: 1;">Other (please specify below)</label>
                <input type="checkbox" name="editReason" value="Other" id="reason6" style="margin-left: 10px;">
            </div>
            
            <div class="other-reason" style="width: 90%;" id="otherReasonContainer">
                <label for="otherReasonText" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Specify other reason:</label>
                <textarea id="otherReasonText" name="otherReasonText" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; min-height: 80px;"></textarea>
            </div>
        </div>
     </div>

                <!-- Form buttons -->
                <div class="buttons"style="grid-column: span 2; display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; border-top: 1px solid #eee;">
                    <button type="button" id="viewChecklistBtn" class="save-btn" style="background-color: #17a2b8; display: none;">
            View Driver Checklist
        </button>
        <button type="button" id="viewExpensesBtn" class="save-btn" style="padding: 8px 15px; background-color: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; display: none;">Expense Reports</button>
                    <button type="button" class="close-btn cancel-btn" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    
                    <button type="submit" class="save-btn"style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    




    <div id="expensesModal" class="modal">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <span class="close">&times;</span>
            <h3 style="margin-top: 0;">Expense Reports</h3>
            <div id="expensesContent">
                <table class="events-table" style="width: 100%; margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Expense Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTableBody"></tbody>
                </table>
            </div>
            <button type="button" class="close-btn cancel-btn" style="margin-top: 20px;">Close</button>
        </div>
    </div>

        <div id="addScheduleModal" class="modal">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow: hidden;">
            <span class="close">&times;</span>
            <h2 style="margin-top: 0;">Add Schedule</h2>
            <form id="addScheduleForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; overflow: hidden;">
                <!-- Column 1 -->
                <div style="display: flex; flex-direction: column;">
                    <label for="addEventSize">Shipment Size:</label>
                    <select id="addEventSize" name="eventSize" required style="width: 100%;">
                        <option value="">Select Size</option>
                        <option value="20ft">20ft</option>
                        <option value="40ft">40ft</option>
                        <option value="40ft HC">40ft HC</option>
                        <option value="45ft">45ft</option>
                    </select>

                    <label for="addEventPlateNo">Plate No.:</label>
                    <input type="text" id="addEventPlateNo" name="eventPlateNo" required style="width: 100%;">

                    <label for="addEventDate">Date & Time:</label>
                    <input type="datetime-local" id="addEventDate" name="eventDate" required style="width: 100%;">

                    <label for="addEventDriver">Driver:</label>
                    <select id="addEventDriver" name="eventDriver" required style="width: 100%;">
                        <option value="">Select Driver</option>
                    </select>

                    <label for="addEventHelper">Helper:</label>
                    <input type="text" id="addEventHelper" name="eventHelper" required style="width: 100%;">
                </div>

                <!-- Column 2 -->
                <div style="display: flex; flex-direction: column;">
                    <label for="addEventDispatcher">Dispatcher:</label>
                    <input type="text" id="addEventDispatcher" name="eventDispatcher" required style="width: 100%;">

                    <label for="addEventContainerNo">Container No.:</label>
                    <input type="text" id="addEventContainerNo" name="eventContainerNo" required style="width: 100%;">

                    <label for="addEventClient">Client:</label>
                    <select id="addEventClient" name="eventClient" required style="width: 100%;">
                        <option value="">Select Client</option>
                        <option value="Maersk">Maersk</option>
                        <option value="MSC">MSC</option>
                        <option value="COSCO">COSCO</option>
                        <option value="CMA CGM">CMA CGM</option>
                        <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                        <option value="Evergreen">Evergreen</option>
                    </select>

                    <label for="addEventDestination">Destination:</label>
                    <select id="addEventDestination" name="eventDestination" required style="width: 100%;">
                        <option value="">Select Destination</option>
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
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

            <div style="grid-column: span 2; display: flex; gap: 15px; align-items: flex-end;">
        <div style="flex: 1;">
            <label for="addEventShippingLine">Shipping Line:</label>
            <select id="addEventShippingLine" name="eventShippingLine" required style="width: 100%;">
                <option value="">Select Shipping Line</option>
                <option value="Maersk Line">Maersk Line</option>
                <option value="Mediterranean Shipping Co.">Mediterranean Shipping Co.</option>
                <option value="COSCO Shipping">COSCO Shipping</option>
                <option value="CMA CGM">CMA CGM</option>
                <option value="Hapag-Lloyd">Hapag-Lloyd</option>
            </select>
        </div>
        
        <div style="flex: 1;">
            <label for="addEventConsignee">Consignee:</label>
            <input type="text" id="addEventConsignee" name="eventConsignee" required style="width: 100%;">
        </div>
    </div>

    <div style="grid-column: span 2;">
        <label for="addEventCashAdvance">Cash Advance:</label>
        <input type="text" id="addEventCashAdvance" name="eventCashAdvance" required style="width: 100%;">
    </div>

                <!-- Form buttons -->
                <div style="grid-column: span 2; display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                    <button type="button" class="close-btn cancel-btn" style="padding: 5px 10px;">Cancel</button>
                    <button type="submit" class="save-btn" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px;">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <div id="checklistModal" class="modal">
    <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <span class="close">&times;</span>
        <h3 style="margin-top: 0;">Driver Checklist</h3>
        <div id="checklistContent">
            <table class="events-table" style="width: 100%; margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody id="checklistTableBody"></tbody>
            </table>
        </div>
        <button type="button" class="close-btn cancel-btn" style="margin-top: 20px;">Close</button>
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

    
        <div class="filter-row">
        <div class="status-filter-container">
            <select id="statusFilter" onchange="filterTableByStatus()">
                <option value="" disabled selected>Status Filter</option>
    <option value="all">All Statuses</option>
    <option value="Pending">Pending</option>
    <option value="En Route">En Route</option>
    <option value="Completed">Completed</option>
    <option value="Cancelled">Cancelled</option>
    <option value="deleted">Deleted</option>
</select>
    <div class="search-container">
            <i class="fa fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search trips..." onkeyup="searchTrips()">
    </div>
    </div>
    </div>
    <div class="table-controls">
    <div class="table-info" id="showingInfo"></div>
    
     <div class="rows-per-page-container">
        <label for="rowsPerPage">Rows per page:</label>
        <select id="rowsPerPage" onchange="changeRowsPerPage()">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
     </div>
</div>


            <table class="events-table" id="eventsTable"> 
                <thead>
                    <tr>
                        <th>Plate No.</th>
                    <th>
                Date 
                <button id="dateSortBtn" style="background: none; border: none; cursor: pointer;">
                    <i class="fa fa-sort"></i>
                </button>
            </th>
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
                    <button class="prev" id="prevPageBtn">&laquo</button> 
                    <div id="page-numbers" class="page-numbers"></div>
                    <button class="next" id="nextPageBtn">&raquo</button>
                </div>
            </div>
        </div>

    <div id="editReasonsModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close">&times;</span>
            <h3>Edit Remarks</h3>
            <div id="editReasonsContent">
            
            </div>
            <button type="button" class="close-btn cancel-btn" style="margin-top: 20px;">Close</button>
        </div>
    </div>
    </div>



    <script>

         function formatDateTime(datetimeString) {
        if (!datetimeString) return 'N/A';
        const date = new Date(datetimeString);
        return date.toLocaleString(); 
    }
 let currentPage = 1;
let rowsPerPage = 5; 
let totalPages = 1;
let totalItems = 0;
  let currentStatusFilter = 'all';
  let dateSortOrder = 'desc';
let filteredEvents = [];
        
       $(document).ready(function() {
    rowsPerPage = parseInt($('#rowsPerPage').val());
    let now = new Date();
    let formattedNow = now.toISOString().slice(0,16); 
    $('#rowsPerPage').val(rowsPerPage);
    updateTableInfo(totalItems, 0);
    $('#statusFilter').on('change', filterTableByStatus);
    $('#editEventDate').attr('min', formattedNow);
    $('#addEventDate').attr('min', formattedNow); 
    $('#rowsPerPage').on('change', function() {
        rowsPerPage = parseInt($(this).val());
        currentPage = 1;
        renderTable();
    });


            function updateDateTime() {
            const now = new Date();
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
            
            document.getElementById('current-time').textContent = now.toLocaleTimeString();
        }

        // Update immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

    function renderTable() {
    const showDeleted = currentStatusFilter === 'deleted';
    const rowsPerPage = parseInt($('#rowsPerPage').val()); 
    
    let action;
    if (showDeleted) {
        action = 'get_deleted_trips';
    } else {
        action = 'get_active_trips';
    }
    
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ 
            action: action, 
            statusFilter: currentStatusFilter === 'deleted' ? 'all' : currentStatusFilter, 
            sortOrder: dateSortOrder,
            page: currentPage,
            perPage: rowsPerPage
        }),
        success: function(response) {
            if (response.success) {
                $('#eventTableBody').empty();
                
                if (response.trips.length === 0) {
                    $('#eventTableBody').html('<tr><td colspan="16">No trips found</td></tr>');
                } else {
                    renderTripRows(response.trips, showDeleted);
                }
                
                totalItems = response.total;
                totalPages = Math.ceil(totalItems / rowsPerPage);
                updatePagination(totalItems);
                updateTableInfo(totalItems, response.trips.length);
            } else {
                alert('Error: ' + response.message);
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
                    // Show warning modal
                    Swal.fire({
                        title: 'Maintenance Conflict',
                        html: `This truck has scheduled maintenance on <strong>${response.maintenanceDate}</strong>.<br><br>
                            Maintenance Type: <strong>${response.maintenanceType}</strong><br>
                            Remarks: <strong>${response.remarks}</strong>`,
                        icon: 'warning',
                        confirmButtonText: 'Continue Anyway',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        callback(result.isConfirmed);
                    });
                } else {
                    callback(true); // No conflict, proceed
                }
            },
            error: function() {
                console.error('Error checking maintenance');
                callback(true); // On error, proceed anyway
            }
        });
    }


    function renderTripRows(trips, showDeleted) {
        trips.forEach(function(trip) {
            const dateObj = new Date(trip.date);
            const formattedDate = dateObj.toLocaleDateString();
            const formattedTime = moment(dateObj).format('h:mm A');
            
            let statusCell = '';
            let actionCell = '';
            
            if (showDeleted || trip.is_deleted == 1) {
                statusCell = `<td><span class="status cancelled">Deleted</span></td>`;
                actionCell = `
                    <td class="actions">
                        <button class="icon-btn restore" data-tooltip="Restore" data-id="${trip.trip_id}">
                        <i class="fas fa-trash-restore"></i>
                        ${window.userRole === 'Full Admin' ? 
                        `<button class="icon-btn full-delete" data-tooltip="Permanently Delete" data-id="${trip.trip_id}" > 
                            <i class="fa-solid fa-ban"></i>
                        </button>` : ''}
                        </button>
                    </td>
                `;
            } else {
                statusCell = `<td><span class="status ${trip.status.toLowerCase().replace(/\s+/g, '')}">${trip.status}</span></td>`;
                actionCell = `
                    <td class="actions">
                        <button class="icon-btn edit" data-tooltip="Edit" data-id="${trip.trip_id}"><i class="fas fa-edit"></i></button>
                        <button class="icon-btn delete" data-tooltip="Delete" data-id="${trip.trip_id}"><i class="fas fa-trash-alt"></i></button>
                        ${trip.edit_reasons && trip.edit_reasons !== 'null' && trip.edit_reasons !== '' ? 
                        `<button class="icon-btn view-reasons" data-tooltip="View Edit History" data-id="${trip.trip_id}">
                            <i class="fas fa-history"></i>
                        </button>` : ''}
                    </td>
                `;
            }

            const row = `
                 <tr class="${showDeleted || trip.is_deleted == 1 ? 'deleted-row' : ''}">
                    <td>${trip.plate_no || 'N/A'}</td>
                    <td>${formattedDate}</td>
                    <td>${formattedTime}</td>
                    <td>${trip.driver || 'N/A'}</td>
                    <td>${trip.helper || 'N/A'}</td>
                    <td>${trip.dispatcher || 'N/A'}</td>
                    <td>${trip.container_no || 'N/A'}</td>
                    <td>${trip.client || 'N/A'}</td>
                    <td>${trip.destination || 'N/A'}</td>
                    <td>${trip.shippine_line || 'N/A'}</td>
                    <td>${trip.consignee || 'N/A'}</td>
                    <td>${trip.size || 'N/A'}</td>
                    <td>${trip.cash_adv || 'N/A'}</td>
                    ${statusCell}
                    <td>
                        <strong>${trip.last_modified_by || 'System'}</strong><br>
                        ${formatDateTime(trip.last_modified_at)}
                    </td>
                    ${actionCell}
                </tr>
            `;
            $('#eventTableBody').append(row);
        });
    }



            var eventsData = <?php echo $eventsDataJson; ?>;
            var driversData = <?php echo $driversDataJson; ?>;
            



            // Populate driver dropdowns
    function populateDriverDropdowns(selectedSize = '', currentDriver = '') {
        // First get the list of all trucks with their statuses
        $.ajax({
            url: 'include/handlers/truck_handler.php?action=getTrucks',
            type: 'GET',
            async: false, // We need to wait for this response
            success: function(truckResponse) {
                if (truckResponse.success) {
                    // Identify unavailable trucks (In Repair, Overdue, or Deleted)
                    var unavailableTruckIds = truckResponse.trucks
                        .filter(truck => 
                            truck.display_status === 'In Repair' || 
                            truck.display_status === 'Overdue' ||
                            truck.is_deleted == 1
                        )
                        .map(truck => truck.truck_id.toString());
                    
                    var driverOptions = '<option value="">Select Driver</option>';
                    
                    driversData.forEach(function(driver) {
                        // Skip drivers assigned to unavailable trucks
                        if (driver.assigned_truck_id && 
                            unavailableTruckIds.includes(driver.assigned_truck_id.toString())) {
                            return; // skip this driver
                        }
                        
                        // Filter drivers based on selected size if specified
                        if (!selectedSize || !driver.capacity || 
                            (selectedSize.includes('20') && driver.capacity === '20') ||
                            (selectedSize.includes('40') && driver.capacity === '40')) {
                            
                            // Check if this is the current driver being edited
                            var selectedAttr = (driver.name === currentDriver) ? ' selected' : '';
                            
                            // Include truck_plate_no as a data attribute
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
                    
                    // Add a disabled option for unavailable drivers if any were filtered out
                    var unavailableDrivers = driversData.filter(driver => 
                        driver.assigned_truck_id && 
                        unavailableTruckIds.includes(driver.assigned_truck_id.toString())
                    );
                    
                    if (unavailableDrivers.length > 0) {
                        driverOptions += '<optgroup label="Unavailable Drivers">';
                        unavailableDrivers.forEach(function(driver) {
                            var status = truckResponse.trucks.find(t => 
                                t.truck_id.toString() === driver.assigned_truck_id.toString()
                            ).display_status;
                            
                            driverOptions += `
                                <option 
                                    disabled
                                    title="Assigned truck is ${status === 'Deleted' ? 'deleted' : status.toLowerCase()}"
                                >
                                    ${driver.name} (Unavailable)
                                </option>
                            `;
                        });
                        driverOptions += '</optgroup>';
                    }
                    
                    $('#editEventDriver').html(driverOptions);
                    $('#addEventDriver').html(driverOptions);
                } else {
                    console.error('Error fetching truck data:', truckResponse.message);
                   
                    populateAllDrivers(selectedSize, currentDriver);
                }
            },
            error: function() {
                console.error('Error fetching truck data');
                
                populateAllDrivers(selectedSize, currentDriver);
            }
        });
    }

  


    $('#dateSortBtn').on('click', function() {
        dateSortOrder = dateSortOrder === 'desc' ? 'asc' : 'desc';
        renderTable();
    });

    // Fallback function to show all drivers
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
            driver_id: event.driver_id, // eto yung driver_id
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
            truck_capacity: event.truck_capacity,
            edit_reasons: event.edit_reasons 
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
    
    // Next button
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
        
        var statusClass = 'status ' + event.status.toLowerCase().replace(/\s+/g, '');
        element.addClass(statusClass);
    },
    // Add this to automatically select today's date
    viewRender: function(view, element) {
        if (view.name === 'month') {
            // Trigger a click on today's cell
            setTimeout(function() {
                $('.fc-today').trigger('click');
            }, 100);
        }
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

        // Toggle event details on thumbnail click
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
    
    // Highlight today's date
    $('.fc-today').addClass('fc-day-selected');
}, 500);
            
            // View toggle buttons
    $('#calendarViewBtn').on('click', function() {
        $(this).addClass('active');
        $('#tableViewBtn').removeClass('active');
        $('#calendar').show();
        $('#eventDetails').show();
        $('#eventsTable, #eventTableBody, .pagination-container, .filter-row, .table-controls ').hide();
        $('body').removeClass('table-view'); 
        $('#calendar').fullCalendar('render');
    });

    $('#tableViewBtn').on('click', function() {
        $(this).addClass('active');
        $('#calendarViewBtn').removeClass('active');
        $('#calendar').hide();
        $('#eventDetails').hide();
        $('#eventsTable, #eventTableBody, .pagination-container, .filter-row, .rows-per-page-container, .table-controls').show(); 
        $('body').addClass('table-view');
        currentPage = 1;
        renderTable();
    });
            // Edit button click handler
   $(document).on('click', '.icon-btn.edit', function() {
    var eventId = $(this).data('id');
    var event = eventsData.find(function(e) { return e.id == eventId; });
    
    if (event) {
        $('#editEventId').val(event.id);
        $('#editEventPlateNo').val(event.truck_plate_no || event.plateNo);
        
        var eventDate = event.date.includes(':00.') 
            ? event.date.replace(/:00\.\d+Z$/, '') 
            : event.date;
        $('#editEventDate').val(eventDate);

        populateDriverDropdowns(event.size);
        setTimeout(() => {
            $('#editEventDriver').val(event.driver);
        }, 100);
        
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

        // Check for both Completed status AND if driver_id exists
        if (event.status === 'Completed') {
    $('#viewExpensesBtn').show();
} else {
    $('#viewExpensesBtn').hide();
}

// Show checklist button if we have a driver_id and status is not Cancelled
if (event.driver_id && event.status !== 'Cancelled') {
    $('#viewChecklistBtn').show();
} else {
    $('#viewChecklistBtn').hide();
}
       
        
        $('#editModal').show();
    }
});

    $(document).on('click', '#viewExpensesBtn', function() {
        var tripId = $('#editEventId').val();
        
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
                    
                    if (response.expenses.length > 0) {
                        response.expenses.forEach(function(expense) {
                            var row = `
                                <tr>
                                    <td>${expense.expense_type}</td>
                                    <td>${expense.amount}</td>
                                </tr>
                            `;
                            $('#expensesTableBody').append(row);
                        });
                    } else {
                        $('#expensesTableBody').html('<tr><td colspan="2" style="text-align: center;">No expenses recorded for this trip</td></tr>');
                    }
                    
                    $('#expensesModal').show();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                 Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Server error occurred while loading expenses',
                    });
           
            }
        });
    });
            

            $(document).on('click', '.icon-btn.delete', function() {
                var eventId = $(this).data('id');
                $('#deleteEventId').val(eventId);
                $('#deleteConfirmModal').show();
            });
            
        $('#addScheduleForm').on('submit', function(e) {
        e.preventDefault();

        var selectedDriver = $('#addEventDriver').val();
        var driver = driversData.find(d => d.name === selectedDriver);
        var truckPlateNo = driver && driver.truck_plate_no ? driver.truck_plate_no : $('#addEventPlateNo').val();
        var tripDate = $('#addEventDate').val();

        checkMaintenanceConflict(truckPlateNo, tripDate, function(shouldProceed) {
            if (!shouldProceed) {
                return; // User cancelled after seeing maintenance warning
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
        console.log('Raw response:', response);
        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Trip added successfully!',
                timer: 2000,
                showConfirmButton: false,
                willClose: () => {
                    $('#addScheduleModal').hide();
                    location.reload();
                }
            });
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

    $(document).on('click', '.icon-btn.view-reasons', function() {
        var eventId = $(this).data('id');
        var event = eventsData.find(function(e) { return e.id == eventId; });
        
        if (event && event.edit_reasons) {
            try {
                var reasons = JSON.parse(event.edit_reasons);
                var html = '<div style="padding: 10px; background: #f9f9f9; border-radius: 5px; margin-bottom: 10px;">';
                html += '<ul style="list-style-type: none; padding-left: 5px;">';
                
                reasons.forEach(function(reason) {
                    // Format the reason with a bullet point and proper spacing
                    html += '<li style="margin-bottom: 8px; padding-left: 15px; position: relative;">';
                    html += '<span style="position: absolute; left: 0;">•</span> ' + reason;
                    html += '</li>';
                });
                
                html += '</ul>';
                html += '<p style="font-style: italic; margin-top: 10px; color: #666;">';
                html += 'Last modified by: ' + (event.modifiedby || 'System') + '<br>';
                html += 'On: ' + formatDateTime(event.modifiedat);
                html += '</p></div>';
                
                $('#editReasonsContent').html(html);
                $('#editReasonsModal').show();
            } catch (e) {
                console.error('Error parsing edit reasons:', e);
                $('#editReasonsContent').html('<div style="padding: 15px; background: #fff8f8; border: 1px solid #ffdddd;">'+
                    '<p>Error displaying edit history</p></div>');
                $('#editReasonsModal').show();
            }
        } else {
            $('#editReasonsContent').html('<div style="padding: 15px; background: #f5f5f5; border-radius: 5px;">'+
                '<p>No edit remarks recorded for this trip</p></div>');
            $('#editReasonsModal').show();
        }
    });


function validateEditReasons() {
    // Check if at least one reason is selected
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
    
    // Check if "Other" is checked but no reason provided
    if ($('#reason6').is(':checked') && otherReasonText === '') {
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


            // Edit form submit handler
      $('#editForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validate edit reasons
    if ($('input[name="editReason"]:checked').length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Reason Required',
            text: 'Please select at least one reason for editing this trip',
            confirmButtonColor: '#3085d6'
        });
        return; 
    }
    
    // Check if "Other" is selected but textarea is empty
    if ($('#reason6').is(':checked') && $('#otherReasonText').val().trim() === '') {
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

    checkMaintenanceConflict(truckPlateNo, tripDate, function(shouldProceed) {
        if (!shouldProceed) return;

        var editReasons = [];
        $('input[name="editReason"]:checked').each(function() {
            if ($(this).val() === 'Other') {
                editReasons.push('Other: ' + $('#otherReasonText').val().trim());
            } else {
                editReasons.push($(this).val());
            }
        });
        
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
                status: $('#editEventStatus').val(),
                editReasons: editReasons
            }),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Trip has been updated successfully',
                        timer: 2000,
                        showConfirmButton: false,
                        timerProgressBar: true
                    }).then(() => {
                        $('#editModal').hide();
                        location.reload();
                    });
                } else {
                    Swal.fire({  
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update trip'
                    });
                }
            }
        });
    });
});
                
        $('#confirmDeleteBtn').on('click', function() {
        var eventId = $('#deleteEventId').val();
        var deleteReason = $('#deleteReason').val();
        
        if (!deleteReason) {
        Swal.fire({
            icon: 'warning',
            title: 'Required',
            text: 'Please provide a reason for deletion'
        });
        return;
        }
        
       $.ajax({
    url: 'include/handlers/trip_operations.php',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
        action: 'delete',
        id: eventId,
        reason: deleteReason
    }),
    success: function(response) {
        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Trip marked as deleted successfully!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                $('#deleteConfirmModal').hide();
                location.reload();
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
            title: 'Server Error',
            text: 'An error occurred while processing your request'
        });
    }
});
    });
            // Initial render
            renderTable();
        });

        document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('expanded');
        });

        $('#otherReasonText').on('input', function() {
        if ($(this).val().trim() !== '') {
            $('#reason6').prop('checked', true);
        }
    });


    $('#reason6').on('change', function() {
        if (!$(this).is(':checked')) {
            $('#otherReasonText').val('');
        }
    });

    function searchTrips() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const table = document.getElementById('eventsTable');
        const rows = table.getElementsByTagName('tr');
        
        // Skip the header row (index 0)
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            let found = false;
            
            // Check each cell in the row (skip the last one which has actions)
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

$(document).on('click', '.icon-btn.full-delete', function(e) {
    e.stopPropagation(); // Prevent event bubbling to parent elements
    
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
                        // Update stats cards with the returned data
                        $('.stat-value').eq(0).text(response.stats.pending);
                        $('.stat-value').eq(1).text(response.stats.enroute);
                        $('.stat-value').eq(2).text(response.stats.completed);
                        $('.stat-value').eq(3).text(response.stats.cancelled);
                        $('.stat-value').eq(4).text(response.stats.total);

                        // Remove the row from the table
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
                                <td>${trip.client || 'N/A'}</td>
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

  $(document).on('click', '.icon-btn.restore', function() {
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
                        // Update stats cards with the returned data
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
                        renderTable(); // Refresh the table
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


    document.getElementById('reason6').addEventListener('change', function() {
        const otherReasonContainer = document.getElementById('otherReasonContainer');
        otherReasonContainer.style.display = this.checked ? 'block' : 'none';
        
        
        if (!this.checked) {
            document.getElementById('otherReasonText').value = '';
        }
    });


    document.getElementById('otherReasonText').addEventListener('input', function() {
        if (this.value.trim() !== '') {
            document.getElementById('reason6').checked = true;
            document.getElementById('otherReasonContainer').style.display = 'block';
        }
    });


    document.querySelector('form').addEventListener('submit', function(e) {
        const otherCheckbox = document.getElementById('reason6');
        const otherReasonText = document.getElementById('otherReasonText').value.trim();
        
       if (otherCheckbox.checked && otherReasonText === '') {
        e.preventDefault();
        
        // Use SweetAlert instead of basic alert
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please specify the other reason for editing this trip',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6',
            didClose: () => {
                // Focus on the textarea after alert is closed
                document.getElementById('otherReasonText').focus();
            }
        });
        
        return false;
    }
});

$('#viewChecklistBtn').on('click', function() {
    var tripId = $('#editEventId').val();
    
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

// Show/hide the checklist button based on trip status
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
    setInterval(updateStats, 300000);
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
    <footer class="site-footer">

        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
        </div>
    </footer>
    </body>
    </html>