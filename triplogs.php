<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Not logged in, redirect to login page
    header("Location: login.php");
    exit();
}
// User is logged in, continue with the page
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
</style>
<body>
    <?php


    // Include database connection
    require 'include/handlers/dbhandler.php';
    
    // Fetch all trips from the database
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
    // Convert to JSON for JavaScript use
    $eventsDataJson = json_encode($eventsData);
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
        
                <label for="editEventDate">Date:</label><br>
                <input type="date" id="editEventDate" name="eventDate" required><br><br>
        
                <label for="editEventDriver">Driver:</label><br>
                <input type="text" id="editEventDriver" name="eventDriver" required><br><br>
        
                <label for="editEventHelper">Helper:</label><br>
                <input type="text" id="editEventHelper" name="eventHelper" required><br><br>
        
                <label for="editEventContainerNo">Container No.:</label><br>
                <input type="text" id="editEventContainerNo" name="eventContainerNo" required><br><br>
        
                <label for="editEventClient">Client:</label><br>
                <input type="text" id="editEventClient" name="eventClient" required><br><br>
        
                <label for="editEventDestination">Destination:</label><br>
                <input type="text" id="editEventDestination" name="eventDestination" required><br><br>
        
                <label for="editEventShippingLine">Shipping Line:</label><br>
                <input type="text" id="editEventShippingLine" name="eventShippingLine" required><br><br>
        
                <label for="editEventConsignee">Consignee:</label><br>
                <input type="text" id="editEventConsignee" name="eventConsignee" required><br><br>
        
                <label for="editEventSize">Size:</label><br>
                <input type="text" id="editEventSize" name="eventSize" required><br><br>
        
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
        
                <label for="addEventDate">Date:</label><br>
                <input type="date" id="addEventDate" name="eventDate" required><br><br>
        
                <label for="addEventDriver">Driver:</label><br>
                <input type="text" id="addEventDriver" name="eventDriver" required><br><br>
        
                <label for="addEventHelper">Helper:</label><br>
                <input type="text" id="addEventHelper" name="eventHelper" required><br><br>
        
                <label for="addEventContainerNo">Container No.:</label><br>
                <input type="text" id="addEventContainerNo" name="eventContainerNo" required><br><br>
        
                <label for="addEventClient">Client:</label><br>
                <input type="text" id="addEventClient" name="eventClient" required><br><br>
        
                <label for="addEventDestination">Destination:</label><br>
                <input type="text" id="addEventDestination" name="eventDestination" required><br><br>
        
                <label for="addEventShippingLine">Shipping Line:</label><br>
                <input type="text" id="addEventShippingLine" name="eventShippingLine" required><br><br>
        
                <label for="addEventConsignee">Consignee:</label><br>
                <input type="text" id="addEventConsignee" name="eventConsignee" required><br><br>
        
                <label for="addEventSize">Size:</label><br>
                <input type="text" id="addEventSize" name="eventSize" required><br><br>
        
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
        <!-- <div class="pagination-container">
            <button class="pagination-btn" id="prevPageBtn">Previous</button>
            <span id="pageInfo"></span>
            <button class="pagination-btn" id="nextPageBtn">Next</button>
        </div> -->
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
            // Load events data from PHP
            var eventsData = <?php echo $eventsDataJson; ?>;
            
            // Format the data for fullCalendar
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

            // Modal handling
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

            // // Pagination variables
            // var currentPage = 1;
            // var rowsPerPage = 5;
            // Variables for pagination
var currentPage = 1;
var rowsPerPage = 5;
var totalPages = 0;
            // Render table and update pagination
            // function renderTable() {
            //     $('#eventTableBody').empty();
            //     var startIndex = (currentPage - 1) * rowsPerPage;
            //     var endIndex = startIndex + rowsPerPage;
            //     var pageData = eventsData.slice(startIndex, Math.min(endIndex, eventsData.length));
                
            //     pageData.forEach(function(event) {
            //         var row = `<tr>
            //             <td>${event.plateNo}</td>
            //             <td>${event.date}</td>
            //             <td>${event.driver}</td>
            //             <td>${event.helper}</td>
            //             <td>${event.containerNo}</td>
            //             <td>${event.client}</td>
            //             <td>${event.destination}</td>
            //             <td>${event.shippingLine}</td>
            //             <td>${event.consignee}</td>
            //             <td>${event.size}</td>
            //             <td>${event.cashAdvance}</td>
            //             <td><span class="status ${event.status.toLowerCase()}">${event.status}</span></td>
            //             <td>
            //                 <button class="edit-btn" data-id="${event.id}">Edit</button>
            //                 <button class="delete-btn" data-id="${event.id}">Delete</button>
            //             </td>
            //         </tr>`;
            //         $('#eventTableBody').append(row);
            //     });
                
            //     updatePagination();
            // }
            function renderTable() {
    $('#eventTableBody').empty();
    var startIndex = (currentPage - 1) * rowsPerPage;
    var endIndex = startIndex + rowsPerPage;
    var pageData = eventsData.slice(startIndex, Math.min(endIndex, eventsData.length));
    
    pageData.forEach(function(event) {
        var row = `<tr>
            <td>${event.plateNo}</td>
            <td>${event.date}</td>
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

            // function updatePagination() {
            //     var totalPages = Math.ceil(eventsData.length / rowsPerPage);
            //     $('#pageInfo').text(`Page ${currentPage} of ${totalPages}`);
                
            //     $('#prevPageBtn').prop('disabled', currentPage === 1);
            //     $('#nextPageBtn').prop('disabled', currentPage === totalPages || totalPages === 0);
            // }
            
            // $('#prevPageBtn').on('click', function() {
            //     if (currentPage > 1) {
            //         currentPage--;
            //         renderTable();
            //     }
            // });
            
            // $('#nextPageBtn').on('click', function() {
            //     var totalPages = Math.ceil(eventsData.length / rowsPerPage);
            //     if (currentPage < totalPages) {
            //         currentPage++;
            //         renderTable();
            //     }
            // });
            
// Update pagination function
function updatePagination() {
    totalPages = Math.ceil(eventsData.length / rowsPerPage);
    
    // Clear previous page numbers
    $('#page-numbers').empty();
    
    // Add page numbers
    for (var i = 1; i <= totalPages; i++) {
        var pageNumClass = i === currentPage ? 'page-number active' : 'page-number';
        $('#page-numbers').append(`<div class="${pageNumClass}" onclick="goToPage(${i})">${i}</div>`);
    }
    
    // Enable/disable prev/next buttons
    $('#prevPageBtn').prop('disabled', currentPage === 1);
    $('#nextPageBtn').prop('disabled', currentPage === totalPages || totalPages === 0);
}

// Function to go to specific page
function goToPage(page) {
    currentPage = page;
    renderTable();
    updatePagination();
}

// Change page function (for prev/next buttons)
function changePage(step) {
    var newPage = currentPage + step;
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        renderTable();
        updatePagination();
    }
}

// Update event handlers for prev/next buttons
$('#prevPageBtn').on('click', function() {
    changePage(-1);
});

$('#nextPageBtn').on('click', function() {
    changePage(1);
});

            // Initialize Calendar
            $('#calendar').fullCalendar({
                header: { 
                    left: 'prev,next today', 
                    center: 'title', 
                    right: 'month,agendaWeek,agendaDay' 
                },
                events: calendarEvents,
                eventRender: function(event, element) {
                    element.find('.fc-title').css({
            'white-space': 'normal',
            'overflow': 'visible'
        });
                    // Customize event rendering
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
    
    // Update the heading to include the clicked date
    var formattedDate = moment(date).format('MMMM D, YYYY');
    $('#eventDetails h4').text('Event Details - ' + formattedDate);
    
    $('#eventList').empty();
    $('#noEventsMessage').hide();
    
    if (eventsOnDay.length > 0) {
        eventsOnDay.forEach(function(event) {
            var eventDetailsHtml = `
                <li class="event-item">
                    <p><strong>Plate No:</strong> ${event.plateNo}</p>
                    <p><strong>Date:</strong> ${moment(event.start).format('MMMM D, YYYY')}</p>
                    <p><strong>Driver:</strong> ${event.driver}</p>
                    <p><strong>Helper:</strong> ${event.helper}</p>
                    <p><strong>Client:</strong> ${event.client}</p>
                    <p><strong>Destination:</strong> ${event.destination}</p>
                    <p><strong>Container No.:</strong> ${event.containerNo}</p>
                    <p><strong>Status:</strong> <span class="status ${event.status.toLowerCase()}">${event.status}</span></p>
                    <p><strong>Cash Advance:</strong> ${event.cashAdvance}</p>
                    <div class="event-actions">
                        <button class="edit-btn" data-id="${event.id}">Edit</button>
                        <button class="delete-btn" data-id="${event.id}">Delete</button>
                    </div>
                </li>
            `;
            $('#eventList').append(eventDetailsHtml);
        });
    } else {
        $('#noEventsMessage').show();
    }
}
            });
            
            // Toggle between Calendar and Table views
            $('#calendarViewBtn').on('click', function() {
                $(this).addClass('active');
                $('#tableViewBtn').removeClass('active');
                $('#calendar').show();
                $('#tableView').hide();
                $('#eventDetails').show();
                
                // Refresh calendar
                $('#calendar').fullCalendar('render');
            });
            
            $('#tableViewBtn').on('click', function() {
                $(this).addClass('active');
                $('#calendarViewBtn').removeClass('active');
                $('#calendar').hide();
                $('#tableView').show();
                $('#eventDetails').hide();
                
                // Reset to first page
                currentPage = 1;
                renderTable();
            });
            
            // Edit event handler (for both table and calendar view)
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
            
            // Delete event handler (for both table and calendar view)
            $(document).on('click', '.delete-btn', function() {
                var eventId = $(this).data('id');
                $('#deleteEventId').val(eventId);
                $('#deleteConfirmModal').show();
            });
            
            // Form submissions
            
            // Add new schedule
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
                            location.reload(); // Reload to refresh data
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Server error occurred');
                    }
                });
            });
            
            // Edit schedule
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
                            location.reload(); // Reload to refresh data
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Server error occurred');
                    }
                });
            });
            
            // Delete confirmation
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
                            location.reload(); // Reload to refresh data
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