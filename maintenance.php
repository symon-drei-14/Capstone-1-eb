<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/maintenancestyle.css">

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
                <h2>Preventive Maintenance Scheduling</h2>
                <div class="button-row">
                    <button class="add_sched" onclick="openModal()"> Add Maintenance Schedule</button>
                    <button class="reminder_btn" onclick="checkReminders()"> Maintenance Reminders</button>
                </div>
                <br />


                <div class="table-container">
                    <table id="maintenanceTable">
                        <thead>
                            <tr>
                                <th>Maintenance <br/> ID</th>
                                <th>Truck ID</th>
                                <th>License Plate</th>
                                <th>Date of <br /> Inspection</th>
                                <th>Remarks</th>
                                <th>Status</th>
                                <th>Supplier</th>
                                <th>Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
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

    <!-- Modal -->
    <div id="maintenanceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Add Maintenance Schedule</h2>
            <form>
                <label for="truckId">Truck ID:</label>
                <select id="status" name="status">
                    <option value="Truck 1">Truck 1</option>
                    <option value="Truck 2">Truck 2</option>
                    <option value="Truck 3">Truck 3 </option>
                    <option value="Truck 4">Truck 4</option>
                </select><br><br>


                <label for="date">Date of Inspection:</label>
                <input type="date" id="date" name="date"><br><br>

                <label for="remarks">Remarks:</label>
                <input type="text" id="remarks" name="remarks"><br><br>

                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="Completed">Completed</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Overdue">Overdue</option>
                </select><br><br>

                <label for="cost">Cost:</label>
                <input type="text" id="cost" name="cost"><br><br>

                <button type="submit">Submit</button>
                <button type="button" class="cancelbtn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <div id="historyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeHistoryModal()">&times;</span>
            <h2>Maintenance History</h2>
            <div class="history-list">
                <div class="history-item">
                    <strong>Maintenance ID:</strong> 001<br>
                    <strong>Remarks:</strong> Oil Change<br>
                    <strong>Date of Inspection:</strong> 2023-10-01<br>
                    <strong>Status:</strong> Completed<br>
                    <strong>Supplier:</strong> Supplier A<br>
                    <strong>Cost:</strong> ₱ 100<br>
                    <hr>
                </div>
                <div class="history-item">
                    <strong>Maintenance ID:</strong> 002<br>
                    <strong>Remarks:</strong> Tire Replacement<br>
                    <strong>Date of Inspection:</strong> 2023-10-05<br>
                    <strong>Status:</strong> Pending<br>
                    <strong>Supplier:</strong> Supplier B<br>
                    <strong>Cost:</strong> ₱ 300<br>
                    <hr>
                </div>
                <div class="history-item">
                    <strong>Maintenance ID:</strong> 003<br>
                    <strong>Remarks:</strong> Brake Inspection<br>
                    <strong>Date of Inspection:</strong> 2023-10-10<br>
                    <strong>Status:</strong> In Progress<br>
                    <strong>Supplier:</strong> Supplier C<br>
                    <strong>Cost:</strong> ₱ 150<br>
                    <hr>
                </div>
                <!-- Add more history records here as needed -->
            </div>
        </div>
    </div>

    <script>
     
     function openHistoryModal() {
            document.getElementById("historyModal").style.display = "block";
        }

        // Close the history modal
        function closeHistoryModal() {
            document.getElementById("historyModal").style.display = "none";
        }

        function openModal() {
            document.getElementById("maintenanceModal").style.display = "block";
        }

        // Close the modal
        function closeModal() {
            document.getElementById("maintenanceModal").style.display = "none";
        }
        function confirmDelete(id) {
            const confirmation = confirm("Are you sure you want to delete this maintenance record?");
        }
        let currentPage = 1;
        const rowsPerPage = 5;
        const maintenanceData = [
            { id: "001", truckId: "TRK-1234", licensePlate: "ABC-1234", date: "2023-10-01", remarks: "Oil Change", status: "Completed", supplier: "Supplier A", cost: "₱ 100" },
            { id: "002", truckId: "TRK-5678", licensePlate: "XYZ-5678", date: "2023-10-05", remarks: "Tire Replacement", status: "Pending", supplier: "Supplier B", cost: "₱ 300" },
            { id: "003", truckId: "TRK-9876", licensePlate: "DEF-9876", date: "2023-10-10", remarks: "Brake Inspection", status: "In Progress", supplier: "Supplier C", cost: "₱ 150" },
            { id: "004", truckId: "TRK-6543", licensePlate: "GHI-6543", date: "2023-10-15", remarks: "Transmission Repair", status: "Overdue", supplier: "Supplier D", cost: "₱ 500" },
            { id: "005", truckId: "TRK-3210", licensePlate: "JKL-3210", date: "2023-10-20", remarks: "Oil Change", status: "Completed", supplier: "Supplier E", cost: "₱ 120" },
            { id: "006", truckId: "TRK-1357", licensePlate: "MNO-1357", date: "2023-10-25", remarks: "Tire Replacement", status: "Pending", supplier: "Supplier F", cost: "₱ 250" },
            { id: "007", truckId: "TRK-2468", licensePlate: "PQR-2468", date: "2023-11-01", remarks: "Suspension Check", status: "Completed", supplier: "Supplier G", cost: "₱ 300" },
            { id: "008", truckId: "TRK-3690", licensePlate: "STU-3690", date: "2023-11-05", remarks: "Engine Inspection", status: "In Progress", supplier: "Supplier H", cost: "₱ 400" }
        ];

        function renderTable() {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pageData = maintenanceData.slice(start, end);

            const tableBody = document.querySelector("#maintenanceTable tbody");
            tableBody.innerHTML = ""; // Clear existing rows

            pageData.forEach(row => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${row.id}</td>
                    <td>${row.truckId}</td>
                    <td>${row.licensePlate}</td>
                    <td>${row.date}</td>
                    <td>${row.remarks}</td>
                    <td><span class="status-${row.status.toLowerCase().replace(" ", "-")}">${row.status}</span></td>
                    <td>${row.supplier}</td>
                    <td>${row.cost}</td>
                    <td class="actions">
                        <button class="edit" onclick="openModal()" >Edit</button>
                         <button class="delete" onclick="if(confirm('Are you sure you want to delete this maintenance record?')) {
                    const index = maintenanceData.findIndex(record => record.id === '${row.id}');
                    if (index !== -1) {
                        maintenanceData.splice(index, 1);
                        renderTable(); // Re-render the table after deletion
                    }
                }">Delete</button>
                         <button class="history" onclick="openHistoryModal()">View History</button>
                    </td>
                `;
                tableBody.appendChild(tr);
            });

            document.getElementById("page-info").textContent = `Page ${currentPage}`;
        }

        function changePage(direction) {
            const totalPages = Math.ceil(maintenanceData.length / rowsPerPage);
            currentPage += direction;

            if (currentPage < 1) {
                currentPage = 1;
            } else if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            renderTable();
        }

        // Initial render
        renderTable();
    </script>

</body>
</html>
