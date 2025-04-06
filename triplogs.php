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
<body>


    <header class="header">
        <div class="logo-container">
            <img src="/img/logo.png" alt="Company Logo" class="logo">
            <img src="/img/mansar.png" alt="Company Name" class="company">
        </div>

        <div class="search-container">
            <input type="text" placeholder="Search..." class="search-bar">
        </div>

        <div class="profile">
            <i class="icon">✉</i>
            <img src="/img/profile.png" alt="Admin Profile" class="profile-icon">
            <div class="profile-name">Jesus Christ</div>
        </div>
    </header>

    <div class="sidebar">

    <div class="sidebar-item">
        <i class="icon2">🏠</i>
        <a asp-area="" asp-controller="Home" asp-action="LandingPage">Home</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚗</i>
        <span class="text">Driver Management</span>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚛</i>
        <a asp-area="" asp-controller="Home" asp-action="FleetManagement">Fleet Management</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">📋</i>
        <a asp-area="" asp-controller="Home" asp-action="TripLogs">Trip Management</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">📍</i>
        <span class="text">Tracking</span>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🔧</i>
        <a asp-area="" asp-controller="Home" asp-action="PreventiveMaintenance" class="text">Maintenance Scheduling</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2"> 📈  </i>
        <span class="text">Fleet Performance Analytics</span>
    </div>
    <hr>
    <div class="sidebar-item">
        <i class="icon2"> ⚙️ </i>
        <span class="text">Settings</span>
    </div>
    <div class="sidebar-item">
        <i class="icon2"> 🚪 </i>
        <a asp-area="" asp-controller="Home" asp-action="Login" class="text">Logout</a>
    </div>
</div>

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
            <p id="noEventsMessage">Click a date to show details</p>
            <ul id="eventList" class="event-list"></ul>
        </section>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Event</h3>
            <form id="editForm">
                <label for="eventPlateNo">Plate No.:</label><br>
                <input type="text" id="eventPlateNo" name="eventPlateNo" required><br><br>
        
                <label for="eventDate">Date:</label><br>
                <input type="datetime-local" id="eventDate" name="eventDate" required><br><br>
        
                <label for="eventDriver">Driver:</label><br>
                <input type="text" id="eventDriver" name="eventDriver" required><br><br>
        
                <label for="eventHelper">Helper:</label><br>
                <input type="text" id="eventHelper" name="eventHelper" required><br><br>
        
                <label for="eventContainerNo">Container No.:</label><br>
                <input type="text" id="eventContainerNo" name="eventContainerNo" required><br><br>
        
                <label for="eventClient">Client:</label><br>
                <input type="text" id="eventClient" name="eventClient" required><br><br>
        
                <label for="eventDestination">Destination:</label><br>
                <input type="text" id="eventDestination" name="eventDestination" required><br><br>
        
                <label for="eventShippingLine">Shipping Line:</label><br>
                <input type="text" id="eventShippingLine" name="eventShippingLine" required><br><br>
        
                <label for="eventConsignee">Consignee:</label><br>
                <input type="text" id="eventConsignee" name="eventConsignee" required><br><br>
        
                <label for="eventSize">Size:</label><br>
                <input type="text" id="eventSize" name="eventSize" required><br><br>
        
                <label for="eventCashAdvance">Cash Advance:</label><br>
                <input type="text" id="eventCashAdvance" name="eventCashAdvance" required><br><br>
        
                <label for="eventStatus">Status:</label><br>
                <select id="eventStatus" name="eventStatus" required>
                    <option value="Completed">Completed</option>
                    <option value="Pending">Pending</option>
                    <option value="Cancelled">Cancelled</option>
                </select><br><br>
        
                <button type="submit">Save Changes</button>
                <button type="button" class="close">Cancel</button>
            </form>
        </div>
    </div>
    
    <div id="addScheduleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add Schedule</h2>
            <form id="addScheduleForm">
                <label for="eventPlateNo">Plate No.:</label><br>
                <input type="text" id="eventPlateNo" name="eventPlateNo" required><br><br>
        
                <label for="eventDate">Date:</label><br>
                <input type="datetime-local" id="eventDate" name="eventDate" required><br><br>
        
                <label for="eventDriver">Driver:</label><br>
                <input type="text" id="eventDriver" name="eventDriver" required><br><br>
        
                <label for="eventHelper">Helper:</label><br>
                <input type="text" id="eventHelper" name="eventHelper" required><br><br>
        
                <label for="eventContainerNo">Container No.:</label><br>
                <input type="text" id="eventContainerNo" name="eventContainerNo" required><br><br>
        
                <label for="eventClient">Client:</label><br>
                <input type="text" id="eventClient" name="eventClient" required><br><br>
        
                <label for="eventDestination">Destination:</label><br>
                <input type="text" id="eventDestination" name="eventDestination" required><br><br>
        
                <label for="eventShippingLine">Shipping Line:</label><br>
                <input type="text" id="eventShippingLine" name="eventShippingLine" required><br><br>
        
                <label for="eventConsignee">Consignee:</label><br>
                <input type="text" id="eventConsignee" name="eventConsignee" required><br><br>
        
                <label for="eventSize">Size:</label><br>
                <input type="text" id="eventSize" name="eventSize" required><br><br>
        
                <label for="eventCashAdvance">Cash Advance:</label><br>
                <input type="text" id="eventCashAdvance" name="eventCashAdvance" required><br><br>
        
                <label for="eventStatus">Status:</label><br>
                <select id="eventStatus" name="eventStatus" required>
                    <option value="Completed">Completed</option>
                    <option value="Pending">Pending</option>
                    <option value="Cancelled">Cancelled</option>
                </select><br><br>
        
                <!-- Save and Cancel buttons -->
                <button type="submit">Save Schedule</button>
                <button type="button" class="close">Cancel</button>
            </form>
        </div>
    </div>
    

    <div id="tableView" style="display: none;">
        <h3>Event Table</h3>

        <table class="events-table" id="eventsTable"> 
            <thead>
                <tr>
                    <th>Plate No.</th>
                    <th>Date</th>
                    <th> Driver</th>
                    <th> Helper</th>
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
            <button class="pagination-btn" id="prevPageBtn">Previous</button>
            <button class="pagination-btn" id="nextPageBtn">Next</button>
        </div>
    </div>

    <script>
        
        $(document).ready(function() {
       
            $('#addScheduleBtnCalendar, #addScheduleBtnTable').on('click', function() {
                $('#addScheduleModal').show();
            });
    
   
            $('.close').on('click', function() {
                $('#addScheduleModal').hide();
            });
    

            $(window).on('click', function(event) {
                if ($(event.target).is('#addScheduleModal')) {
                    $('#addScheduleModal').hide();
                }
            });

            
            $('.close').on('click', function() {
                $('#editModal').hide();
            });
    

            $(window).on('click', function(event) {
                if ($(event.target).is('#editModal')) {
                    $('#editModal').hide();
                }
            });
    
            var eventsData = [
                { 
                    plateNo: 'ABC123', date: '2025-04-06', driver: 'John Doe', helper: 'Jane Doe', 
                    containerNo: 'C123', client: 'XYZ Corp', destination: 'NYC', shippingLine: 'ABC Shipping', 
                    consignee: 'John Doe', size: '20ft', cashAdvance: '₱ 100', status: 'Completed'
                },
                { 
                    plateNo: 'ABC124', date: '2025-04-06', driver: 'John Doe', helper: 'Jane Doe', 
                    containerNo: 'C124', client: 'XYZ Corp', destination: 'NYC', shippingLine: 'ABC Shipping', 
                    consignee: 'John Doe', size: '40ft', cashAdvance: '₱ 150', status: 'Pending'
                },
                { 
                    plateNo: 'ABC125', date: '2025-04-12', driver: 'John Doe', helper: 'Jane Doe', 
                    containerNo: 'C125', client: 'XYZ Corp', destination: 'NYC', shippingLine: 'ABC Shipping', 
                    consignee: 'John Doe', size: '40ft', cashAdvance: '₱ 200', status: 'Cancelled'
                },
                { 
                    plateNo: 'ABC126', date: '2025-04-15', driver: 'John Doe', helper: 'Jane Doe', 
                    containerNo: 'C126', client: 'XYZ Corp', destination: 'NYC', shippingLine: 'ABC Shipping', 
                    consignee: 'John Doe', size: '20ft', cashAdvance: '₱ 120', status: 'Pending'
                },
                { 
                    plateNo: 'ABC127', date: '2025-04-20', driver: 'John Doe', helper: 'Jane Doe', 
                    containerNo: 'C127', client: 'XYZ Corp', destination: 'NYC', shippingLine: 'ABC Shipping', 
                    consignee: 'John Doe', size: '20ft', cashAdvance: '₱ 110', status: 'Pending'
                }
            ];
    
            var currentPage = 1;
            var rowsPerPage = 5;
    
            // Render events in table
            function renderTable() {
                $('#eventTableBody').empty();
                var startIndex = (currentPage - 1) * rowsPerPage;
                var endIndex = startIndex + rowsPerPage;
                var pageData = eventsData.slice(startIndex, endIndex);
    
                pageData.forEach(function(event, index) {
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
                         <td> <span class="status ${event.status}">${event.status}</span> </td>
                        <td>
                            <button class="edit-btn" data-index="${startIndex + index}">Edit</button>
                            <button class="delete-btn" data-index="${startIndex + index}">Delete</button>
                        </td>
                    </tr>`;
                    $('#eventTableBody').append(row);
                });
            }
    

    
          
            $(document).on('click', '.edit-btn', function() {
                var index = $(this).data('index');
                var event = eventsData[index];
    
                $('#eventPlateNo').val(event.plateNo);  
                $('#eventDate').val(event.date);  
                $('#eventDriver').val(event.driver);
                $('#eventHelper').val(event.helper);  
                $('#eventContainerNo').val(event.containerNo);
                $('#eventClient').val(event.client);  
                $('#eventDestination').val(event.destination);
                $('#eventShippingLine').val(event.shippingLine);  
                $('#eventConsignee').val(event.consignee);  
                $('#eventSize').val(event.size);  
                $('#eventCashAdvance').val(event.cashAdvance);
                $('#eventStatus').val(event.status);  
    
    
                $('#editModal').show();

                
            });

            $(document).on('click', '.delete-btn', function() {
                var index = $(this).data('index');
                eventsData.splice(index, 1); 
                renderTable();  
                updatePagination();  
            });
    
            // Initialize Calendar
            $('#calendar').fullCalendar({
                header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,agendaDay' },
                events: eventsData,
                dayClick: function(date, jsEvent, view) {
                    var clickedDay = $(this);
                    
                    $('.fc-day').removeClass('fc-day-selected');
                    clickedDay.addClass('fc-day-selected');
    
                    var eventsOnDay = $('#calendar').fullCalendar('clientEvents', function(event) {
                        return moment(event.start).isSame(date, 'day');
                    });
    
                    $('#eventList').empty();
                    $('#noEventsMessage').hide();
    
                    if (eventsOnDay.length > 0) {
                        eventsOnDay.forEach(function(event, index) {
                            var eventDetailsHtml = `
                                <p><strong>Date:</strong> ${moment(event.start).format('MMMM D, YYYY, h:mm A')} to ${moment(event.end).format('MMMM D, YYYY, h:mm A')}</p>
                                <p><strong>Driver:</strong> ${event.driver}</p>
                                <p><strong>Client:</strong> ${event.client}</p>
                                <p><strong>Destination:</strong> ${event.destination}</p>
                                <p><strong>Container No.:</strong> ${event.containerNo}</p>
                                <p><strong>Status:</strong> <span class="status ${event.status}">${event.status}</span></p>
                                <p><strong>Cash Advance:</strong> ${event.cashAdvance}</p>
                                <button class="edit-btn" data-index="' + index + '">Edit</button>
                                <button class="delete-btn" data-index="' + index + '">Delete</button>
                                <hr>
                            `;
                            $('#eventList').append(eventDetailsHtml);
                        });
                        $('#eventDetails').show();
                    } else {
                        $('#noEventsMessage').show();
                    }
                }
            });
    

            $('#calendarViewBtn').on('click', function() {
                $(this).addClass('active');
                $('#tableViewBtn').removeClass('active');
                $('#calendar').show();
                $('#tableView').hide();
                $('#eventDetails').show();
            });
    
            $('#tableViewBtn').on('click', function() {
                $(this).addClass('active');
                $('#calendarViewBtn').removeClass('active');
                $('#calendar').hide();
                $('#tableView').show();
                $('#eventDetails').hide();
                renderTable();
                updatePagination();
            });
    

            renderTable();
            updatePagination();
        });
    </script>
    

</body>
</html>
