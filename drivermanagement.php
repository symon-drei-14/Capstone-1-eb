<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/drivermanagement.css">
</head>
<body>

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
        <a href="settings.php">Settings</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚪</i>
        <a href="logout.php">Logout</a>
    </div>
</div>

    <div class="main-content3">
        <section class="dashboard">
            <div class="container">
                <h2>Driver Management</h2>
                <div class="button-row">
                    <button class="add_driver" onclick="openModal()">Add Driver</button>
                </div>
                <br />

                <div class="table-container">
                    <table id="driverTable">
                        <thead>
                            <tr>
                                <th>Picture</th>
                                <th>Name</th>
                                <th>License Number</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal Structure -->
    <div id="driverModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h2>Driver Details</h2>
            <form id="driverForm">
                <label for="driverName">Name</label>
                <input type="text" id="driverName" name="driverName" required>

                <label for="licenseNo">License Number</label>
                <input type="text" id="licenseNo" name="licenseNo" required>

                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" required>

                <label for="address">Address</label>
                <input type="text" id="address" name="address" required>

                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                <button type="submit">Save</button>
                <button type="button" class="cancelbtn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById("driverModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("driverModal").style.display = "none";
        }

        // Sample data with placeholder image URLs
        const driverData = [
            {id: 1, name: 'John Doe', licenseNo: 'D12345', phone: '123-456-7890', address: '123 Street, City', status: 'Active', picture: '/img/placeholder.png'},
            {id: 2, name: 'Jane Smith', licenseNo: 'D67890', phone: '234-567-8901', address: '456 Avenue, City', status: 'Inactive', picture: '/img/placeholder.png'},
            {id: 3, name: 'Alex Green', licenseNo: 'D11223', phone: '345-678-9012', address: '789 Road, City', status: 'Active', picture: '/img/placeholder.png'},
            {id: 4, name: 'Lisa White', licenseNo: 'D44556', phone: '456-789-0123', address: '101 Parkway, City', status: 'Active', picture: '/img/placeholder.png'},
            {id: 5, name: 'Michael Brown', licenseNo: 'D78901', phone: '567-890-1234', address: '202 Boulevard, City', status: 'Inactive', picture: '/img/placeholder.png'},
        ];

        function renderTable() {
            const tableBody = document.querySelector("#driverTable tbody");
            tableBody.innerHTML = ""; // Clear existing rows

            driverData.forEach(driver => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td><img src="${driver.picture}" alt="${driver.name}'s Picture" class="driver-picture" /></td>
                    <td>${driver.name}</td>
                    <td>${driver.licenseNo}</td>
                    <td>${driver.phone}</td>
                    <td>${driver.address}</td>
                    <td><span class="status-${driver.status.toLowerCase()}">${driver.status}</span></td>
                    <td class="actions">
                        <button class="edit" onclick="editDriver(${driver.id})">Edit</button>
                        <button class="delete" onclick="deleteDriver(${driver.id})">Delete</button>
                    </td>
                `;
                tableBody.appendChild(tr);
            });
        }

        function editDriver(id) {
            const driver = driverData.find(d => d.id === id);
            if (driver) {
                document.getElementById("driverName").value = driver.name;
                document.getElementById("licenseNo").value = driver.licenseNo;
                document.getElementById("phone").value = driver.phone;
                document.getElementById("address").value = driver.address;
                document.getElementById("status").value = driver.status;

                // Modify the form to handle edit
                const form = document.getElementById("driverForm");
                form.onsubmit = function (e) {
                    e.preventDefault();
                    updateDriver(id);
                    closeModal();
                };

                openModal();
            }
        }

        function updateDriver(id) {
            const driver = driverData.find(d => d.id === id);
            if (driver) {
                driver.name = document.getElementById("driverName").value;
                driver.licenseNo = document.getElementById("licenseNo").value;
                driver.phone = document.getElementById("phone").value;
                driver.address = document.getElementById("address").value;
                driver.status = document.getElementById("status").value;

                renderTable();
            }
        }

        function deleteDriver(id) {
    const confirmDelete = confirm("Are you sure you want to delete this driver?");
    if (confirmDelete) {
        const index = driverData.findIndex(d => d.id === id);
        if (index !== -1) {
            driverData.splice(index, 1);
            renderTable();
        }
    }
}


        // Initial rendering of the table
        renderTable();
    </script>

</body>
</html>
