<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Not logged in, redirect to login page
    header("Location: login.php");
    exit();
}
require_once 'include/handlers/dbhandler.php';
// User is logged in, continue with the page
?>
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
<header class="header">
        <div class="logo-container">
            <img src="include/img/logo.png" alt="Company Logo" class="logo">
            <img src="include/img/mansar.png" alt="Company Name" class="company">
        </div>

        <div class="profile">
            <i class="icon">✉</i>
            <img src="include/img/profile.png" alt="Admin Profile" class="profile-icon">
            <div class="profile-name">Jesus Christ</div>
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
        <a href="settings.php">Admin Management</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚪</i>
        <a href="include/handlers/logout.php">Logout</a>
    </div>
</div>

    <div class="main-content3">
        <section class="dashboard">
            <div class="container">
                <h2>Driver Management</h2>
                <div class="button-row">
                    <button class="add_driver" onclick="openModal('add')">Add Driver</button>
                </div>
                <br />

                <div class="table-container">
                    <table id="driverTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Assigned Truck</th>
                                <th>Created At</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch driver data from the database
                            $query = "SELECT driver_id, name, email, assigned_truck_id, created_at, last_login FROM drivers_table";
                            $result = $conn->query($query);
                            
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['driver_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                    echo "<td>" . (empty($row['assigned_truck_id']) ? 'None' : htmlspecialchars($row['assigned_truck_id'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                    echo "<td>" . (empty($row['last_login']) ? 'Never' : htmlspecialchars($row['last_login'])) . "</td>";
                                    echo "<td class='actions'>";
                                    echo "<button class='edit' onclick='editDriver(\"" . $row['driver_id'] . "\")'>Edit</button>";
                                    echo "<button class='delete' onclick='deleteDriver(\"" . $row['driver_id'] . "\")'>Delete</button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No drivers found</td></tr>";
                            }
                            ?>
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
        <h2 id="modalTitle">Driver Details</h2>
        <form id="driverForm">
            <input type="hidden" id="driverId" name="driverId">
            
            <label for="driverName">Name</label>
            <input type="text" id="driverName" name="driverName" required>

            <label for="driverEmail">Email</label>
            <input type="email" id="driverEmail" name="driverEmail" required>
            
            <label for="firebaseUid">Firebase UID</label>
            <input type="text" id="firebaseUid" name="firebaseUid">
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password">
            
            <label for="assignedTruck">Assigned Truck ID</label>
            <input type="text" id="assignedTruck" name="assignedTruck">
            
            <label for="lastLogin">Last Login</label>
            <input type="text" id="lastLogin" name="lastLogin" placeholder="YYYY-MM-DD HH:MM:SS">

            <button type="submit" id="saveButton">Save</button>
            <button type="button" class="cancelbtn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

    <script>
        let currentDriverId = null;
        let modalMode = 'add';

        function openModal(mode, driverId = null) {
            modalMode = mode;
            currentDriverId = driverId;
            
            if (mode === 'add') {
                document.getElementById("modalTitle").textContent = "Add New Driver";
                document.getElementById("driverForm").reset();
                document.getElementById("driverId").value = "";
            } else {
                document.getElementById("modalTitle").textContent = "Edit Driver";
                // The data will be populated in the editDriver function
            }
            
            document.getElementById("driverModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("driverModal").style.display = "none";
        }

        function editDriver(driverId) {
            // Fetch driver data using AJAX
            fetch('include/handlers/get_driver.php?id=' + driverId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const driver = data.driver;
                        
                        // Populate the form with driver data
                        document.getElementById("driverId").value = driver.driver_id;
                        document.getElementById("driverName").value = driver.name;
                        document.getElementById("driverEmail").value = driver.email;
                        document.getElementById("firebaseUid").value = driver.firebase_uid || '';
                        // Don't pre-fill password for security reasons
                        document.getElementById("password").value = '';
                        document.getElementById("assignedTruck").value = driver.assigned_truck_id || '';
                        document.getElementById("lastLogin").value = driver.last_login === 'NULL' ? '' : (driver.last_login || '');
                        
                        // Open the modal in edit mode
                        openModal('edit', driverId);
                    } else {
                        alert("Error fetching driver data: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred while fetching driver data.");
                });
        }

        function deleteDriver(driverId) {
            if (confirm("Are you sure you want to delete this driver?")) {
                // Send AJAX request to delete the driver
                fetch('include/handlers/delete_driver.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ driverId: driverId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Driver deleted successfully.");
                        // Reload the page to refresh the table
                        location.reload();
                    } else {
                        alert("Error deleting driver: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred while deleting the driver.");
                });
            }
        }

        // Event listener for the form submission
        document.getElementById("driverForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            const formData = {
                driverId: document.getElementById("driverId").value,
                name: document.getElementById("driverName").value,
                email: document.getElementById("driverEmail").value,
                firebaseUid: document.getElementById("firebaseUid").value,
                password: document.getElementById("password").value,
                assignedTruck: document.getElementById("assignedTruck").value,
                lastLogin: document.getElementById("lastLogin").value,
                mode: modalMode
            };
            
            // Send AJAX request to save the driver
            fetch('include/handlers/save_driver.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(modalMode === 'add' ? "Driver added successfully." : "Driver updated successfully.");
                    // Reload the page to refresh the table
                    location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("An error occurred while saving the driver data.");
            });
            
            closeModal();
        });

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            const modal = document.getElementById("driverModal");
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>