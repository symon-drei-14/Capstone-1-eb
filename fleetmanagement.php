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
                    <button class="add_trip" onclick="openModal()">Assign Trips</button>
                </div>
                <br />

                <div class="table-container">
                    <table id="fleetTable">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Driver Assistant</th>
                                <th>Client</th>
                                <th>Container No.</th>
                                <th>Destination</th>
                                <th>Departure Time</th>
                                <th>Estimated Arrival Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="prev" onclick="changePage(-1)">◄</button> 
                    <span id="page-info">Page 1</span>
                    <button class="next" onclick="changePage(1)">►</button> 
                </div>
            </div>
        </section>
    </div>

    <!-- Modal Structure -->
    <div id="tripModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h2>Assign Trip</h2>
            <form>
                <label for="vehicle">Vehicle</label>
                <select id="vehicle" name="vehicle" required>
                    <option value="Vehicle 1">Vehicle 1</option>
                    <option value="Vehicle 2">Vehicle 2</option>
                    <option value="Vehicle 3">Vehicle 3</option>
                </select>

                <label for="driver">Driver</label>
                <select id="driver" name="driver" required>
                    <option value="Driver 1">Driver 1</option>
                    <option value="Driver 2">Driver 2</option>
                    <option value="Driver 3">Driver 3</option>
                </select>

                <label for="driverAssistant">Driver Assistant</label>
                <select id="driverAssistant" name="driverAssistant" required>
                    <option value="Assistant 1">Assistant 1</option>
                    <option value="Assistant 2">Assistant 2</option>
                    <option value="Assistant 3">Assistant 3</option>
                </select>

                <label for="client">Client</label>
                <select id="client" name="client" required>
                    <option value="Client A">Client A</option>
                    <option value="Client B">Client B</option>
                    <option value="Client C">Client C</option>
                </select>

                <label for="containerNo">Container No.</label>
                <input type="text" id="containerNo" name="containerNo" required>

                <label for="destination">Destination</label>
                <input type="text" id="destination" name="destination" required>

                <label for="departureTime">Departure Time</label>
                <input type="datetime-local" id="departureTime" name="departureTime" required>

                <label for="estimatedArrivalTime">Estimated Arrival Time</label>
                <input type="datetime-local" id="estimatedArrivalTime" name="estimatedArrivalTime" required>

                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>

                <button type="submit">Save</button>
                <button type="button" class="cancelbtn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        // Sample data
        const fleetData = [
            {id: 1, vehicle: 'Truck 101', driver: 'John Smith', driverAssistant: 'Carlos Rodriguez', client: 'ABC Corp', containerNo: 'CONT7890', destination: 'New York', departureTime: '2025-03-30 08:00', estimatedArrivalTime: '2025-03-31 14:00', status: 'In Progress'},
            {id: 2, vehicle: 'Van 202', driver: 'Sarah Johnson', driverAssistant: 'Mike Lee', client: 'XYZ Ltd', containerNo: 'CONT1234', destination: 'Los Angeles', departureTime: '2025-03-29 09:30', estimatedArrivalTime: '2025-03-29 18:30', status: 'Completed'},
            {id: 3, vehicle: 'Truck 303', driver: 'David Wilson', driverAssistant: 'Emma Clark', client: 'DEF Inc', containerNo: 'CONT5678', destination: 'Chicago', departureTime: '2025-03-31 07:00', estimatedArrivalTime: '2025-04-01 12:00', status: 'Pending'},
            {id: 4, vehicle: 'Semi 404', driver: 'Lisa Brown', driverAssistant: 'James Taylor', client: 'GHI LLC', containerNo: 'CONT9012', destination: 'Miami', departureTime: '2025-03-28 06:00', estimatedArrivalTime: '2025-03-30 10:00', status: 'Completed'},
            {id: 5, vehicle: 'Truck 505', driver: 'Michael Davis', driverAssistant: 'Anna White', client: 'JKL Corp', containerNo: 'CONT3456', destination: 'Dallas', departureTime: '2025-04-01 10:00', estimatedArrivalTime: '2025-04-02 16:00', status: 'Pending'},
            {id: 6, vehicle: 'Van 606', driver: 'Emily Black', driverAssistant: 'Ryan Green', client: 'MNO Inc', containerNo: 'CONT7890', destination: 'Houston', departureTime: '2025-03-30 11:00', estimatedArrivalTime: '2025-03-31 08:00', status: 'In Progress'},
            {id: 7, vehicle: 'Truck 707', driver: 'Daniel Gray', driverAssistant: 'Olivia Brown', client: 'PQR Ltd', containerNo: 'CONT2345', destination: 'Boston', departureTime: '2025-03-29 14:00', estimatedArrivalTime: '2025-03-30 19:00', status: 'Completed'},
            {id: 8, vehicle: 'Semi 808', driver: 'Sophia Blue', driverAssistant: 'Noah Adams', client: 'STU Corp', containerNo: 'CONT6789', destination: 'Atlanta', departureTime: '2025-04-02 08:30', estimatedArrivalTime: '2025-04-03 15:30', status: 'Pending'},
            {id: 9, vehicle: 'Truck 909', driver: 'James White', driverAssistant: 'Isabella Johnson', client: 'VWX Ltd', containerNo: 'CONT0123', destination: 'Phoenix', departureTime: '2025-03-31 09:00', estimatedArrivalTime: '2025-04-01 17:00', status: 'In Progress'},
            {id: 10, vehicle: 'Van 1010', driver: 'Olivia Pink', driverAssistant: 'William Moore', client: 'YZA Inc', containerNo: 'CONT4567', destination: 'Seattle', departureTime: '2025-03-30 12:00', estimatedArrivalTime: '2025-03-31 20:00', status: 'Pending'},
        ];

        let currentPage = 1;
        const rowsPerPage = 5;

        function renderTable() {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pageData = fleetData.slice(start, end);

            const tableBody = document.querySelector("#fleetTable tbody");
            tableBody.innerHTML = ""; // Clear existing rows

            pageData.forEach(row => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${row.vehicle}</td>
                    <td>${row.driver}</td>
                    <td>${row.driverAssistant}</td>
                    <td>${row.client}</td>
                    <td>${row.containerNo}</td>
                    <td>${row.destination}</td>
                    <td>${row.departureTime}</td>
                    <td>${row.estimatedArrivalTime}</td>
                    <td><span class="status-${row.status.toLowerCase().replace(" ", "-")}">${row.status}</span></td>
                    <td class="actions">
                        <button class="edit" onclick="openModal()">Edit</button>
                        <button class="delete" onclick="deleteRecord(${row.id})">Delete</button>
                    </td>
                `;
                tableBody.appendChild(tr);
            });

            document.getElementById("page-info").textContent = `Page ${currentPage}`;
        }

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

        function openModal() {
            document.getElementById("tripModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("tripModal").style.display = "none";
        }

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

        // When the page loads, render the table
        window.onload = function() {
            renderTable();
        };
    </script>
</body>
</html>