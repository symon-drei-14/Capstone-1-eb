<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip logs</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/triplogs.css">

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

    <div class="main-content3">
        <section class="dashboard">
            <div class="container">
                <h2>Trip Logs</h2>
                <div class="button-row">
                <button class="add_triplog" onclick="openModal()"> Add Trip log</button>
                </div>
                <br />

                <div class="table-container">
                    <table id="maintenanceTable">
                        <thead>
                            <tr>
                        <th> Driver</th>
                       <th> Broker/Client</th>
                       <th>Destination</th>
                       <th>Container No.</th>
                       <th>BL REF NO.</th>
                       <th>Status</th>
                       <th>Cash Advance</th>
                       <th>Additional Cash Advance</th>
                       <th>Total Amount</th>
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
<!-- Modal Structure -->
<div id="maintenanceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">×</span>
        <h2> Trip Log</h2>
        <form>
            <label for="driver">Driver</label>
            <select id="driver" name="driver" required>
                <option value="driver 1">driver 1</option>
                <option value="driver 2">driver 2</option>
            </select>


            <label for="broker">Broker</label>
            <select id="broker" name="broker" required>
                <option value="broker 1">broker 1</option>
                <option value="broker 2">broker 2</option>
            </select>


            <label for="destination">Destination</label>
            <input type="text" id="destination" name="destination" required>

            <label for="containerNo">Container No.</label>
            <input type="text" id="containerNo" name="containerNo" required>

            <label for="blRefNo">BL Ref No.</label>
            <input type="text" id="blRefNo" name="blRefNo" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="Completed">Completed</option>
                <option value="Pending">Pending</option>
            </select>

            <label for="cashAdvance">Cash Advance</label>
            <input type="number" id="cashAdvance" name="cashAdvance" required>

            <label for="additionalCashAdvance">Additional Cash Advance</label>
            <input type="number" id="additionalCashAdvance" name="additionalCashAdvance" required>

            <label for="totalAmount">Total Amount</label>
            <input type="number" id="totalAmount" name="totalAmount" required>

            <button type="submit">Save</button>
            <button type="button" class="cancelbtn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

    <script>
function openModal() {
    document.getElementById("maintenanceModal").style.display = "block";
}

// Close the modal
function closeModal() {
    document.getElementById("maintenanceModal").style.display = "none";
}

        // Sample data
        const maintenanceData = [
            {id: 1, driver: 'John Doe', broker: 'ABC Corp', destination: 'NYC', containerNo: 'C123', blRefNo: 'BL123', status: 'Completed', cashAdvance: '₱ 100', additionalCashAdvance: '₱ 50', totalAmount: '₱ 150'},
            {id: 2, driver: 'Jane Smith', broker: 'XYZ Ltd', destination: 'LA', containerNo: 'C124', blRefNo: 'BL124', status: 'Pending', cashAdvance: '₱ 120', additionalCashAdvance: '₱ 60', totalAmount: '₱ 180'},
            {id: 3, driver: 'Alex Green', broker: 'DEF Inc', destination: 'Chicago', containerNo: 'C125', blRefNo: 'BL125', status: 'Completed', cashAdvance: '₱ 110', additionalCashAdvance: '₱ 40', totalAmount: '₱ 150'},
            {id: 4, driver: 'Lisa White', broker: 'GHI LLC', destination: 'Miami', containerNo: 'C126', blRefNo: 'BL126', status: 'Completed', cashAdvance: '₱ 130', additionalCashAdvance: '₱ 70', totalAmount: '₱ 200'},
            {id: 5, driver: 'Michael Brown', broker: 'JKL Corp', destination: 'Dallas', containerNo: 'C127', blRefNo: 'BL127', status: 'Pending', cashAdvance: '₱ 140', additionalCashAdvance: '₱ 80', totalAmount: '₱ 220'},
            {id: 6, driver: 'Emily Black', broker: 'MNO Inc', destination: 'Houston', containerNo: 'C128', blRefNo: 'BL128', status: 'Completed', cashAdvance: '₱ 150', additionalCashAdvance: '₱ 90', totalAmount: '₱ 240'},
            {id: 7, driver: 'David Gray', broker: 'PQR Ltd', destination: 'Boston', containerNo: 'C129', blRefNo: 'BL129', status: 'Completed', cashAdvance: '₱ 160', additionalCashAdvance: '₱ 100', totalAmount: '₱ 260'},
            {id: 8, driver: 'Sophia Blue', broker: 'STU Corp', destination: 'Atlanta', containerNo: 'C130', blRefNo: 'BL130', status: 'Pending', cashAdvance: '₱ 170', additionalCashAdvance: '₱ 110', totalAmount: '₱ 280'},
            {id: 9, driver: 'James White', broker: 'VWX Ltd', destination: 'Phoenix', containerNo: 'C131', blRefNo: 'BL131', status: 'Completed', cashAdvance: '₱ 180', additionalCashAdvance: '₱ 120', totalAmount: '₱ 300'},
            {id: 10, driver: 'Olivia Pink', broker: 'YZA Inc', destination: 'Seattle', containerNo: 'C132', blRefNo: 'BL132', status: 'Pending', cashAdvance: '₱ 190', additionalCashAdvance: '₱ 130', totalAmount: '₱320'},
        ];

        let currentPage = 1;
        const rowsPerPage = 5;

        function renderTable() {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const pageData = maintenanceData.slice(start, end);

            const tableBody = document.querySelector("#maintenanceTable tbody");
            tableBody.innerHTML = ""; // Clear existing rows

            pageData.forEach(row => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${row.driver}</td>
                    <td>${row.broker}</td>
                    <td>${row.destination}</td>
                    <td>${row.containerNo}</td>
                    <td>${row.blRefNo}</td>
                    <td><span class="status-${row.status.toLowerCase().replace(" ", "-")}">${row.status}</span></td>
                    <td>${row.cashAdvance}</td>
                    <td>${row.additionalCashAdvance}</td>
                    <td>${row.totalAmount}</td>
                    <td class="actions">
                        <button class="edit" onclick="openModal()">Edit</button>
                        <button class="delete" onclick="deleteRecord('${row.id}')">Delete</button>
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

        function deleteRecord(id) {
    const confirmDelete = confirm("Are you sure you want to delete this Re4cord?");
    if (confirmDelete) {
        const index = driverData.findIndex(d => d.id === id);
        if (index !== -1) {
            driverData.splice(index, 1);
            renderTable();
        }
    }
}


        renderTable();
    </script>

</body>
</html>
