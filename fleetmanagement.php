<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Management</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/fleetmanagement.css">
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
            <a asp-area="" asp-controller="Home" asp-action="DriverManagement">Driver Management</a>
        </div>
        <div class="sidebar-item">
            <i class="icon2">🚛</i>
            <span class="text">Fleet Management</span>
        </div>
        <div class="sidebar-item">
            <i class="icon2">📋</i>
            <a asp-area="" asp-controller="Home" asp-action="TripLogs">Trip Logs</a>
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

    <div class="main-content3">
        <section class="dashboard">
            <div class="container">
                <h2>Fleet Management</h2>
                <div class="button-row">
                    <button class="add_trip" onclick="openTripModal()">Assign Trips</button>
                </div>
                <br />
                <h3>Assigned Trips</h3>
                <div class="table-container">
                    <table id="fleetTable">
                        <thead>
                            <tr>
                                <th>Truck ID</th>
                                <th>Driver</th>
                                <th>Driver Assistant</th>
                                <th>Client</th>
                                <th>Container No.</th>
                                <th>Destination</th>
                                <th>Departure Time</th>
                                <th>Estimated Arrival Time</th>
                                <th>Alloted Budget</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="pagination">
                <button class="prev" onclick="changePage(-1)">◄</button> 
                <span id="fleet-page-info">Page 1</span>
                <button class="next" onclick="changePage(1)">►</button> 
            </div>
        </section>
    </div>

    <div class="main-content4">
        <section class="content-2">
            <div class="container">
                <div class="button-row">
                    <button class="add_trip" onclick="openTruckModal()">Add a truck</button>
                </div>
                <br />
                <h3>List of Trucks</h3>
                <div class="table-container">
                    <table id="trucksTable">
                        <thead>
                            <tr>
                                <th>Truck ID</th>
                                <th>Driver</th>
                                <th>Driver Assistant</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="pagination2">
                <button class="prev" onclick="changeTruckPage(-1)">◄</button> 
                <span id="truck-page-info">Page 1</span>
                <button class="next" onclick="changeTruckPage(1)">►</button> 
            </div>
        </section>
    </div>

    <!-- Modal for Assign Trip -->
    <div id="tripModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('tripModal')">&times;</span>
            <h2>Trip Log</h2>
            
            <div class="form-group">
                <label for="vehicle">Truck ID</label>
                <select id="vehicle" name="vehicle" class="form-control" required>
                    <option value="T-001">T-001</option>
                    <option value="T-002">T-002</option>
                    <option value="T-003">T-003</option>
                    <option value="T-004">T-004</option>
                </select>
            </div>

            <div class="form-group">
                <label for="driver">Driver</label>
                <select id="driver" name="driver" class="form-control" required>
                    <option value="driver 1">driver 1</option>
                    <option value="driver 2">driver 2</option>
                    <option value="driver 3">driver 3</option>
                </select>
            </div>

            <div class="form-group">
                <label for="driverAssistant">Driver Assistant</label>
                <select id="driverAssistant" name="driverAssistant" class="form-control" required>
                    <option value="assistant 1">assistant 1</option>
                    <option value="assistant 2">assistant 2</option>
                    <option value="assistant 3">assistant 3</option>
                </select>
            </div>

            <div class="form-group">
                <label for="client">Client</label>
                <select id="client" name="client" class="form-control" required>
                    <option value="client 1">client 1</option>
                    <option value="client 2">client 2</option>
                    <option value="client 3">client 3</option>
                </select>
            </div>

            <div class="form-group">
                <label for="containerNo">Container No.</label>
                <input type="text" id="containerNo" name="containerNo" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="destination">Destination</label>
                <input type="text" id="destination" name="destination" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="departureTime">Departure Time</label>
                <input type="datetime-local" id="departureTime" name="departureTime" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="estimatedArrivalTime">Estimated Arrival Time</label>
                <input type="datetime-local" id="estimatedArrivalTime" name="estimatedArrivalTime" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="allotedBudget">Alloted Budget</label>
                <input type="text" id="allotedBudget" name="allotedBudget" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>

            <div class="button-group">
                <button type="button" class="save-btn" onclick="saveTrip()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('tripModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Modal for Add Truck -->
    <div id="truckModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('truckModal')">&times;</span>
            <h2>Add Truck</h2>
            
            <div class="form-group">
                <label for="truckId">Truck ID</label>
                <input type="text" id="truckId" name="truckId" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="truckDriver">Driver</label>
                <select id="truckDriver" name="truckDriver" class="form-control" required>
                    <option value="driver 1">driver 1</option>
                    <option value="driver 2">driver 2</option>
                    <option value="driver 3">driver 3</option>
                </select>
            </div>

            <div class="form-group">
                <label for="truckDriverAssistant">Driver Assistant</label>
                <select id="truckDriverAssistant" name="truckDriverAssistant" class="form-control" required>
                    <option value="assistant 1">assistant 1</option>
                    <option value="assistant 2">assistant 2</option>
                    <option value="assistant 3">assistant 3</option>
                </select>
            </div>

            <div class="form-group">
                <label for="truckStatus">Status</label>
                <select id="truckStatus" name="truckStatus" class="form-control" required>
                    <option value="Available">Available</option>
                    <option value="On Trip">On Trip</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
            </div>

            <div class="button-group">
                <button type="button" class="save-btn" onclick="saveTruck()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('truckModal')">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        function openTripModal() {
            openModal('tripModal');
        }

        function openTruckModal() {
            openModal('truckModal');
        }

        // Save functions
        function saveTrip() {
            // Get form values
            const vehicle = document.getElementById('vehicle').value;
            const driver = document.getElementById('driver').value;
            const driverAssistant = document.getElementById('driverAssistant').value;
            const client = document.getElementById('client').value;
            const containerNo = document.getElementById('containerNo').value;
            const destination = document.getElementById('destination').value;
            const departureTime = document.getElementById('departureTime').value;
            const estimatedArrivalTime = document.getElementById('estimatedArrivalTime').value;
            const allotedBudget = document.getElementById('allotedBudget').value;
            const status = document.getElementById('status').value;
            
            // Create new trip object
            const newTrip = {
                id: fleetData.length + 1,
                truckid: vehicle,
                driver: driver,
                driverAssistant: driverAssistant,
                client: client,
                containerNo: containerNo,
                destination: destination,
                departureTime: departureTime,
                estimatedArrivalTime: estimatedArrivalTime,
                allotedBudget: allotedBudget,
                status: status
            };
            
            // Add to fleet data
            fleetData.push(newTrip);
            
            // Re-render table
            renderTable();
            
            // Close modal and show success message
            alert("Trip saved successfully!");
            closeModal('tripModal');
        }

        function saveTruck() {
            // Get form values
            const truckId = document.getElementById('truckId').value;
            const driver = document.getElementById('truckDriver').value;
            const driverAssistant = document.getElementById('truckDriverAssistant').value;
            const status = document.getElementById('truckStatus').value;
            
            // Create new truck object
            const newTruck = {
                id: trucksData.length + 1,
                truckId: truckId,
                driver: driver,
                driverAssistant: driverAssistant,
                status: status
            };
            
            // Add to trucks data
            trucksData.push(newTruck);
            
            // Re-render trucks table
            renderTrucksTable();
            
            // Close modal and show success message
            alert("Truck added successfully!");
            closeModal('truckModal');
        }

        // Fleet data
        const fleetData = [
            {id: 1, truckid: 'T-001', driver: 'Glen Diana', driverAssistant: 'Carlos Rodriguez', client: 'ABC Corp', containerNo: 'CONT7890', destination: 'New York', departureTime: '2025-03-30 08:00', estimatedArrivalTime: '2025-03-31 14:00', allotedBudget: 'Php 10,000', status: 'In Progress'},
            {id: 2, truckid: 'T-002', driver: 'Sarah Johnson', driverAssistant: 'Mike Lee', client: 'XYZ Ltd', containerNo: 'CONT1234', destination: 'Los Angeles', departureTime: '2025-03-29 09:30', estimatedArrivalTime: '2025-03-29 18:30', allotedBudget: 'Php 10,000', status: 'Completed'},
            {id: 3, truckid: 'T-003', driver: 'David Wilson', driverAssistant: 'Emma Clark', client: 'DEF Inc', containerNo: 'CONT5678', destination: 'Chicago', departureTime: '2025-03-31 07:00', estimatedArrivalTime: '2025-04-01 12:00', allotedBudget: 'Php 10,000', status: 'Pending'},
            {id: 4, truckid: 'T-004', driver: 'Lisa Brown', driverAssistant: 'James Taylor', client: 'GHI LLC', containerNo: 'CONT9012', destination: 'Miami', departureTime: '2025-03-28 06:00', estimatedArrivalTime: '2025-03-30 10:00', allotedBudget: 'Php 10,000', status: 'Completed'},
            {id: 5, truckid: 'T-005', driver: 'Michael Davis', driverAssistant: 'Anna White', client: 'JKL Corp', containerNo: 'CONT3456', destination: 'Dallas', departureTime: '2025-04-01 10:00', estimatedArrivalTime: '2025-04-02 16:00', allotedBudget: 'Php 10,000', status: 'Pending'},
            {id: 6, truckid: 'T-006', driver: 'Emily Black', driverAssistant: 'Ryan Green', client: 'MNO Inc', containerNo: 'CONT7890', destination: 'Houston', departureTime: '2025-03-30 11:00', estimatedArrivalTime: '2025-03-31 08:00', allotedBudget: 'Php 10,000', status: 'In Progress'},
            {id: 7, truckid: 'T-007', driver: 'Daniel Gray', driverAssistant: 'Olivia Brown', client: 'PQR Ltd', containerNo: 'CONT2345', destination: 'Boston', departureTime: '2025-03-29 14:00', estimatedArrivalTime: '2025-03-30 19:00', allotedBudget: 'Php 10,000', status: 'Completed'},
            {id: 8, truckid: 'T-008', driver: 'Sophia Blue', driverAssistant: 'Noah Adams', client: 'STU Corp', containerNo: 'CONT6789', destination: 'Atlanta', departureTime: '2025-04-02 08:30', estimatedArrivalTime: '2025-04-03 15:30', allotedBudget: 'Php 10,000', status: 'Pending'},
            {id: 9, truckid: 'T-009', driver: 'James White', driverAssistant: 'Isabella Johnson', client: 'VWX Ltd', containerNo: 'CONT0123', destination: 'Phoenix', departureTime: '2025-03-31 09:00', estimatedArrivalTime: '2025-04-01 17:00', allotedBudget: 'Php 10,000', status: 'In Progress'},
            {id: 10, truckid: 'T-010', driver: 'Olivia Pink', driverAssistant: 'William Moore', client: 'YZA Inc', containerNo: 'CONT4567', destination: 'Seattle', departureTime: '2025-03-30 12:00', estimatedArrivalTime: '2025-03-31 20:00', allotedBudget: 'Php 10,000', status: 'Pending'},
        ];

        // Trucks data
        const trucksData = [
            {id: 1, truckId: 'T-001', driver: 'Glen Diana', driverAssistant: 'Carlos Rodriguez', status: 'Available'},
            {id: 2, truckId: 'T-002', driver: 'Sarah Johnson', driverAssistant: 'Mike Lee', status: 'On Trip'},
            {id: 3, truckId: 'T-003', driver: 'David Wilson', driverAssistant: 'Emma Clark', status: 'Maintenance'},
            {id: 4, truckId: 'T-004', driver: 'Lisa Brown', driverAssistant: 'James Taylor', status: 'Available'},
            {id: 5, truckId: 'T-005', driver: 'Michael Davis', driverAssistant: 'Anna White', status: 'On Trip'},
            {id: 6, truckId: 'T-006', driver: 'Emily Black', driverAssistant: 'Ryan Green', status: 'Available'},
            {id: 7, truckId: 'T-007', driver: 'Daniel Gray', driverAssistant: 'Olivia Brown', status: 'Maintenance'},
            {id: 8, truckId: 'T-008', driver: 'Sophia Blue', driverAssistant: 'Noah Adams', status: 'On Trip'},
            {id: 9, truckId: 'T-009', driver: 'James White', driverAssistant: 'Isabella Johnson', status: 'Available'},
            {id: 10, truckId: 'T-010', driver: 'Olivia Pink', driverAssistant: 'William Moore', status: 'On Trip'},
        ];

        // Pagination variables
        let currentPage = 1;
        let currentTruckPage = 1;
        const rowsPerPage = 5;

        // Table rendering functions
        function renderTable() {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pageData = fleetData.slice(start, end);

            const tableBody = document.querySelector("#fleetTable tbody");
            tableBody.innerHTML = ""; // Clear existing rows

            pageData.forEach(row => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${row.truckid}</td>
                    <td>${row.driver}</td>
                    <td>${row.driverAssistant}</td>
                    <td>${row.client}</td>
                    <td>${row.containerNo}</td>
                    <td>${row.destination}</td>
                    <td>${row.departureTime}</td>
                    <td>${row.estimatedArrivalTime}</td>
                    <td>${row.allotedBudget}</td>
                    <td><span class="status-${row.status.toLowerCase().replace(/\s+/g, "-")}">${row.status}</span></td>
                    <td class="actions">
                        <button class="edit" onclick="editTrip(${row.id})">Edit</button>
                        <button class="delete" onclick="deleteRecord(${row.id})">Delete</button>
                    </td>
                `;
                tableBody.appendChild(tr);
            });

            document.getElementById("fleet-page-info").textContent = `Page ${currentPage}`;
        }

        function renderTrucksTable() {
            const start = (currentTruckPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pageData = trucksData.slice(start, end);

            const tableBody = document.querySelector("#trucksTable tbody");
            if (!tableBody) {
                console.error("Trucks table body not found");
                return;
            }
            
            tableBody.innerHTML = ""; // Clear existing rows

            pageData.forEach(truck => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${truck.truckId}</td>
                    <td>${truck.driver}</td>
                    <td>${truck.driverAssistant}</td>
                    <td><span class="status-${truck.status.toLowerCase().replace(/\s+/g, "-")}">${truck.status}</span></td>
                    <td class="actions">
                        <button class="edit" onclick="editTruck(${truck.id})">Edit</button>
                        <button class="delete" onclick="deleteTruck(${truck.id})">Delete</button>
                    </td>
                `;
                tableBody.appendChild(tr);
            });

            document.getElementById("truck-page-info").textContent = `Page ${currentTruckPage}`;
        }

        // Pagination functions
        function changePage(direction) {
            const totalPages = Math.ceil(fleetData.length / rowsPerPage);
            currentPage += direction;

            if (currentPage < 1) {
                currentPage = 1;
            } else if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            renderTable();
        }

        function changeTruckPage(direction) {
            const totalPages = Math.ceil(trucksData.length / rowsPerPage);
            currentTruckPage += direction;

            if (currentTruckPage < 1) {
                currentTruckPage = 1;
            } else if (currentTruckPage > totalPages) {
                currentTruckPage = totalPages;
            }

            renderTrucksTable();
        }

        // Record management functions
        function deleteRecord(id) {
            const confirmDelete = confirm("Are you sure you want to delete this record?");
            if (confirmDelete) {
                const index = fleetData.findIndex(d => d.id === id);
                if (index !== -1) {
                    fleetData.splice(index, 1);
                    renderTable();
                }
            }
        }

        function deleteTruck(id) {
            const confirmDelete = confirm("Are you sure you want to delete this truck?");
            if (confirmDelete) {
                const index = trucksData.findIndex(t => t.id === id);
                if (index !== -1) {
                    trucksData.splice(index, 1);
                    renderTrucksTable();
                }
            }
        }

        function editTrip(id) {
            // Find the trip
            const trip = fleetData.find(t => t.id === id);
            if (trip) {
                // Open the modal
                openTripModal();
                
                // Populate the form fields
                document.getElementById('vehicle').value = trip.truckid;
                document.getElementById('driver').value = trip.driver;
                document.getElementById('driverAssistant').value = trip.driverAssistant;
                document.getElementById('client').value = trip.client;
                document.getElementById('containerNo').value = trip.containerNo;
                document.getElementById('destination').value = trip.destination;
                document.getElementById('departureTime').value = trip.departureTime.replace(' ', 'T');
                document.getElementById('estimatedArrivalTime').value = trip.estimatedArrivalTime.replace(' ', 'T');
                document.getElementById('allotedBudget').value = trip.allotedBudget;
                document.getElementById('status').value = trip.status;
            }
        }

        function editTruck(id) {
            // Find the truck
            const truck = trucksData.find(t => t.id === id);
            if (truck) {
                // Open the modal
                openTruckModal();
                
                // Populate the form fields
                document.getElementById('truckId').value = truck.truckId;
                document.getElementById('truckDriver').value = truck.driver;
                document.getElementById('truckDriverAssistant').value = truck.driverAssistant;
                document.getElementById('truckStatus').value = truck.status;
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        };

        // Initialize tables when page loads
        window.onload = function() {
            renderTable();
            renderTrucksTable();
        };
    </script>
</body>
</html>