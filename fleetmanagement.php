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
                                <th>Plate Number</th>
                                <th>Driver</th>
                                <th>Helper</th>
                                <th>Container No.</th>
                                <th>Client</th>
                                <th>Shipping Line</th>
                                <th>Consignee</th>
                                <th>Size</th>
                                <th>Cash Advance</th>
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
                <label for="plateNumber">Plate Number</label>
                <select id="plateNumber" name="plateNumber" class="form-control" required>
                    <option value="ABC-123">ABC-123</option>
                    <option value="DEF-456">DEF-456</option>
                    <option value="GHI-789">GHI-789</option>
                    <option value="JKL-012">JKL-012</option>
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
                <label for="helper">Helper</label>
                <select id="helper" name="helper" class="form-control" required>
                    <option value="helper 1">helper 1</option>
                    <option value="helper 2">helper 2</option>
                    <option value="helper 3">helper 3</option>
                </select>
            </div>

            <div class="form-group">
                <label for="containerNo">Container No.</label>
                <input type="text" id="containerNo" name="containerNo" class="form-control" required>
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
                <label for="shippingLine">Shipping Line</label>
                <input type="text" id="shippingLine" name="shippingLine" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="consignee">Consignee</label>
                <input type="text" id="consignee" name="consignee" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="size">Size</label>
                <select id="size" name="size" class="form-control" required>
                    <option value="20ft">20ft</option>
                    <option value="40ft">40ft</option>
                    <option value="45ft">45ft</option>
                </select>
            </div>

            <div class="form-group">
                <label for="cashAdvance">Cash Advance</label>
                <input type="text" id="cashAdvance" name="cashAdvance" class="form-control" required>
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
            const plateNumber = document.getElementById('plateNumber').value;
            const driver = document.getElementById('driver').value;
            const helper = document.getElementById('helper').value;
            const containerNo = document.getElementById('containerNo').value;
            const client = document.getElementById('client').value;
            const shippingLine = document.getElementById('shippingLine').value;
            const consignee = document.getElementById('consignee').value;
            const size = document.getElementById('size').value;
            const cashAdvance = document.getElementById('cashAdvance').value;
            const status = document.getElementById('status').value;
           
            // Create new trip object
            const newTrip = {
                id: fleetData.length + 1,
                plateNumber: plateNumber,
                driver: driver,
                helper: helper,
                containerNo: containerNo,
                client: client,
                shippingLine: shippingLine,
                consignee: consignee,
                size: size,
                cashAdvance: cashAdvance,
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
            {id: 1, plateNumber: 'ABC-123', driver: 'Glen Diana', helper: 'Carlos Rodriguez', containerNo: 'CONT7890', client: 'ABC Corp', shippingLine: 'Maersk Line', consignee: 'Global Trading Ltd', size: '40ft', cashAdvance: 'Php 10,000', status: 'In Progress'},
            {id: 2, plateNumber: 'DEF-456', driver: 'Sarah Johnson', helper: 'Mike Lee', containerNo: 'CONT1234', client: 'XYZ Ltd', shippingLine: 'MSC', consignee: 'Retail Solutions Inc', size: '20ft', cashAdvance: 'Php 8,000', status: 'Completed'},
            {id: 3, plateNumber: 'GHI-789', driver: 'David Wilson', helper: 'Emma Clark', containerNo: 'CONT5678', client: 'DEF Inc', shippingLine: 'CMA CGM', consignee: 'Industrial Corp', size: '45ft', cashAdvance: 'Php 12,000', status: 'Pending'},
            {id: 4, plateNumber: 'JKL-012', driver: 'Lisa Brown', helper: 'James Taylor', containerNo: 'CONT9012', client: 'GHI LLC', shippingLine: 'Evergreen', consignee: 'Supply Chain Co', size: '40ft', cashAdvance: 'Php 9,500', status: 'Completed'},
            {id: 5, plateNumber: 'MNO-345', driver: 'Michael Davis', helper: 'Anna White', containerNo: 'CONT3456', client: 'JKL Corp', shippingLine: 'COSCO', consignee: 'Logistics Pro', size: '20ft', cashAdvance: 'Php 7,000', status: 'Pending'},
            {id: 6, plateNumber: 'PQR-678', driver: 'Emily Black', helper: 'Ryan Green', containerNo: 'CONT7890', client: 'MNO Inc', shippingLine: 'Hapag-Lloyd', consignee: 'Import Export Co', size: '45ft', cashAdvance: 'Php 13,000', status: 'In Progress'},
            {id: 7, plateNumber: 'STU-901', driver: 'Daniel Gray', helper: 'Olivia Brown', containerNo: 'CONT2345', client: 'PQR Ltd', shippingLine: 'ONE', consignee: 'Distribution Experts', size: '40ft', cashAdvance: 'Php 11,000', status: 'Completed'},
            {id: 8, plateNumber: 'VWX-234', driver: 'Sophia Blue', helper: 'Noah Adams', containerNo: 'CONT6789', client: 'STU Corp', shippingLine: 'Yang Ming', consignee: 'Worldwide Shipping', size: '20ft', cashAdvance: 'Php 6,500', status: 'Pending'},
            {id: 9, plateNumber: 'YZA-567', driver: 'James White', helper: 'Isabella Johnson', containerNo: 'CONT0123', client: 'VWX Ltd', shippingLine: 'ZIM', consignee: 'Freight Solutions', size: '40ft', cashAdvance: 'Php 10,500', status: 'In Progress'},
            {id: 10, plateNumber: 'BCD-890', driver: 'Olivia Pink', helper: 'William Moore', containerNo: 'CONT4567', client: 'YZA Inc', shippingLine: 'HMM', consignee: 'Global Logistics', size: '45ft', cashAdvance: 'Php 14,000', status: 'Pending'},
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
                    <td>${row.plateNumber}</td>
                    <td>${row.driver}</td>
                    <td>${row.helper}</td>
                    <td>${row.containerNo}</td>
                    <td>${row.client}</td>
                    <td>${row.shippingLine}</td>
                    <td>${row.consignee}</td>
                    <td>${row.size}</td>
                    <td>${row.cashAdvance}</td>
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
                document.getElementById('plateNumber').value = trip.plateNumber;
                document.getElementById('driver').value = trip.driver;
                document.getElementById('helper').value = trip.helper;
                document.getElementById('containerNo').value = trip.containerNo;
                document.getElementById('client').value = trip.client;
                document.getElementById('shippingLine').value = trip.shippingLine;
                document.getElementById('consignee').value = trip.consignee;
                document.getElementById('size').value = trip.size;
                document.getElementById('cashAdvance').value = trip.cashAdvance;
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
