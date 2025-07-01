<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

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

    .event-item {
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .event-thumbnail {
        cursor: pointer;
        padding: 10px;
        background-color: #f5f5f5;
    }

    .event-details {
        padding: 10px;
        border-top: 1px solid #ccc;
        background-color: #fff;
    }

    .status {
        padding: 3px 8px;
        border-radius: 3px;
        font-weight: bold;
    }

    .status.completed {
        background-color: #d4edda;
        color: #155724;
    }

    .status.pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .status.cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 50%;
        border-radius: 5px;
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

    button {
        padding: 8px 15px;
        margin: 5px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    button.edit-btn {
        background-color: #4CAF50;
        color: white;
    }

    button.delete-btn {
        background-color: #f44336;
        color: white;
    }

    button.close-btn {
        background-color: #ccc;
    }

    button.cancel-btn {
        background-color: #6c757d;
        color: white;
    }

    button:hover {
        opacity: 0.8;
    }

    input, select {
        width: 100%;
        padding: 8px;
        margin: 5px 0;
        display: inline-block;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .toggle-btns {
        margin-bottom: 15px;
    }

    .toggle-btn {
        padding: 8px 15px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
    }

    .toggle-btn.active {
        background-color: #0056b3;
    }

    .events-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .events-table th, .events-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    .events-table th {
        background-color: #f2f2f2;
    }

    .events-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .pagination {
        display: flex;
        align-items: center;
    }

    .page-numbers {
        display: flex;
        margin: 0 10px;
    }

    .page-number {
        padding: 5px 10px;
        margin: 0 2px;
        cursor: pointer;
        border: 1px solid #ddd;
        border-radius: 3px;
    }

    .page-number.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .prev, .next {
        padding: 5px 10px;
        cursor: pointer;
        border: 1px solid #ddd;
        background-color: #f8f9fa;
    }

    .prev:hover, .next:hover {
        background-color: #e9ecef;
    }

    .prev:disabled, .next:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }
</style>
<body>
    <?php
    require 'include/handlers/dbhandler.php';
    
    // Fetch trip assignments
    $sql = "SELECT * FROM assign";
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
                'containerNo' => $row['container_no'],
                'client' => $row['client'],
                'destination' => $row['destination'],
                'shippingLine' => $row['shippine_line'],
                'consignee' => $row['consignee'],
                'size' => $row['size'],
                'cashAdvance' => $row['cash_adv'],
                'status' => $row['status']
            ];
        }
    }
    
    // Fetch drivers from drivers_table
    $driverQuery = "SELECT driver_id, name FROM drivers_table";
    $driverResult = $conn->query($driverQuery);
    $driversData = [];
    
    if ($driverResult->num_rows > 0) {
        while($driverRow = $driverResult->fetch_assoc()) {
            $driversData[] = [
                'id' => $driverRow['driver_id'],
                'name' => $driverRow['name']
            ];
        }
    }
    
    $eventsDataJson = json_encode($eventsData);
    $driversDataJson = json_encode($driversData);
    ?>

<header class="header">
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
    
            <label for="editEventSize">Size:</label><br>
            <select id="editEventSize" name="eventSize" required>
                <option value="">Select Size</option>
                <option value="20ft">20ft</option>
                <option value="40ft">40ft</option>
                <option value="40ft HC">40ft HC</option>
                <option value="45ft">45ft</option>
            </select><br><br>
    
            <label for="editEventCashAdvance">Cash Advance:</label><br>
            <input type="text" id="editEventCashAdvance" name="eventCashAdvance" required><br><br>
    
            <label for="editEventStatus">Status:</label><br>
            <select id="editEventStatus" name="eventStatus" required>
                <option value="Completed">Completed</option>
                <option value="Pending">Pending</option>
                <option value="Cancelled">Cancelled</option>
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
    
            <label for="addEventSize">Size:</label><br>
            <select id="addEventSize" name="eventSize" required>
                <option value="">Select Size</option>
                <option value="20ft">20ft</option>
                <option value="40ft">40ft</option>
                <option value="40ft HC">40ft HC</option>
                <option value="45ft">45ft</option>
            </select><br><br>
    
            <label for="addEventCashAdvance">Cash Advance:</label><br>
            <input type="text" id="addEventCashAdvance" name="eventCashAdvance" required><br><br>
    
            <label for="addEventStatus">Status:</label><br>
            <select id="addEventStatus" name="eventStatus" required>
                <option value="Completed">Completed</option>
                <option value="Pending">Pending</option>
                <option value="Cancelled">Cancelled</option>
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
                    <th>Container No.</th>
                    <th>Client</th>
                    <th>Destination</th>
                    <th>Shipping Line</th>
                    <th>Consignee</th>
                    <th>Size</th>
                    <th>Cash Advance</th>
                    <th>Status</th>
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
        function populateDriverDropdowns() {
            var driverOptions = '<option value="">Select Driver</option>';
            
            driversData.forEach(function(driver) {
                driverOptions += `<option value="${driver.name}">${driver.name}</option>`;
            });
            
            $('#editEventDriver').html(driverOptions);
            $('#addEventDriver').html(driverOptions);
        }
        
        // Call this function to initialize dropdowns
        populateDriverDropdowns();
        
        // Format events for calendar
        var calendarEvents = eventsData.map(function(event) {
            return {
                id: event.id,
                title: event.client + ' - ' + event.destination,
                start: event.date,
                plateNo: event.plateNo,
                driver: event.driver,
                helper: event.helper,
                containerNo: event.containerNo,
                client: event.client,
                destination: event.destination,
                shippingLine: event.shippingLine,
                consignee: event.consignee,
                size: event.size,
                cashAdvance: event.cashAdvance,
                status: event.status
            };
        });

        // Close modal handlers
        $('.close, .close-btn').on('click', function() {
            $('.modal').hide();
        });
        
        $(window).on('click', function(event) {
            if ($(event.target).hasClass('modal')) {
                $('.modal').hide();
            }
        });
        
        $('#addScheduleBtnTable').on('click', function() {
            $('#addScheduleModal').show();
        });

        // Table pagination variables
        var currentPage = 1;
        var rowsPerPage = 5;
        var totalPages = 0;
        
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
                    <td>${event.containerNo}</td>
                    <td>${event.client}</td>
                    <td>${event.destination}</td>
                    <td>${event.shippingLine}</td>
                    <td>${event.consignee}</td>
                    <td>${event.size}</td>
                    <td>${event.cashAdvance}</td>
                    <td><span class="status ${event.status.toLowerCase()}">${event.status}</span></td>
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
                        var eventItem = `
                            <li class="event-item">
                                <div class="event-thumbnail">
                                    <strong>Date:</strong> ${moment(event.start).format('MMMM D, YYYY')}<br>
                                    <strong>Plate No:</strong> ${event.plateNo}<br>
                                    <strong>Destination:</strong> ${event.destination}
                                </div>
                                <div class="event-details" style="display: none;">
                                    <p><strong>Driver:</strong> ${event.driver}</p>
                                    <p><strong>Helper:</strong> ${event.helper}</p>
                                    <p><strong>Client:</strong> ${event.client}</p>
                                    <p><strong>Container No.:</strong> ${event.containerNo}</p>
                                    <p><strong>Status:</strong> <span class="status ${event.status.toLowerCase()}">${event.status}</span></p>
                                    <p><strong>Cash Advance:</strong> ${event.cashAdvance}</p>
                                    <div class="event-actions">
                                        <button class="edit-btn" data-id="${event.id}">Edit</button>
                                        <button class="delete-btn" data-id="${event.id}">Delete</button>
                                    </div>
                                </div>
                            </li>
                        `;
                        $('#eventList').append(eventItem);
                    });
                } else {
                    $('#noEventsMessage').show();
                }

                // Toggle event details on thumbnail click
                $('.event-thumbnail').on('click', function() {
                    $(this).next('.event-details').toggle();
                    $(this).parent('.event-item').toggleClass('expanded');
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
                $('#editEventPlateNo').val(event.plateNo);
                $('#editEventDate').val(event.date);
                $('#editEventDriver').val(event.driver);
                $('#editEventHelper').val(event.helper);
                $('#editEventContainerNo').val(event.containerNo);
                $('#editEventClient').val(event.client);
                $('#editEventDestination').val(event.destination);
                $('#editEventShippingLine').val(event.shippingLine);
                $('#editEventConsignee').val(event.consignee);
                $('#editEventSize').val(event.size);
                $('#editEventCashAdvance').val(event.cashAdvance);
                $('#editEventStatus').val(event.status);
                
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
            
            $.ajax({
                url: 'include/handlers/trip_operations.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'add',
                    plateNo: $('#addEventPlateNo').val(),
                    date: $('#addEventDate').val(),
                    driver: $('#addEventDriver').val(),
                    helper: $('#addEventHelper').val(),
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
            
            $.ajax({
                url: 'include/handlers/trip_operations.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'edit',
                    id: $('#editEventId').val(),
                    plateNo: $('#editEventPlateNo').val(),
                    date: $('#editEventDate').val(),
                    driver: $('#editEventDriver').val(),
                    helper: $('#editEventHelper').val(),
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
</body>
</html>