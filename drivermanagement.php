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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

    <div class="main-content3">
        <section class="dashboard">
            <div class="container">
                <h2>Driver Management</h2>
                <div class="button-row">
                    <!-- <button class="add_driver" onclick="openModal('add')">Add Driver</button> -->
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
                        <tbody id="driverTableBody">
                            <!-- Table data will be loaded here via JavaScript -->
                        </tbody>
                    </table>
                    
                    <div class="pagination">
    <button class="prev" id="prevPageBtn">◄</button>
    <div id="page-numbers" class="page-numbers"></div>
    <button class="next" id="nextPageBtn">►</button>
</div>
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
        
        // Pagination variables
        let driversData = [];
        let currentPage = 1;
        let rowsPerPage = 5;

        // Fetch all drivers when page loads
        $(document).ready(function() {
            fetchDrivers();
        });

        function fetchDrivers() {
            $.ajax({
                url: 'include/handlers/get_all_drivers.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        driversData = data.drivers;
                        renderTable();
                    } else {
                        alert("Error fetching drivers: " + data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert("An error occurred while fetching driver data.");
                }
            });
        }

        function renderTable() {
            $('#driverTableBody').empty();
            var startIndex = (currentPage - 1) * rowsPerPage;
            var endIndex = startIndex + rowsPerPage;
            var pageData = driversData.slice(startIndex, Math.min(endIndex, driversData.length));
            
            if (pageData.length > 0) {
                pageData.forEach(function(driver) {
                    var row = "<tr>" +
                        "<td>" + driver.driver_id + "</td>" +
                        "<td>" + driver.name + "</td>" +
                        "<td>" + driver.email + "</td>" +
                        "<td>" + (driver.assigned_truck_id || 'None') + "</td>" +
                        "<td>" + driver.created_at + "</td>" +
                        "<td>" + (driver.last_login || 'Never') + "</td>" +
                        "<td class='actions'>" +
                        "<button class='edit' onclick='editDriver(\"" + driver.driver_id + "\")'>Edit</button>" +
                        "<button class='delete' onclick='deleteDriver(\"" + driver.driver_id + "\")'>Delete</button>" +
                        "</td>" +
                        "</tr>";
                    $('#driverTableBody').append(row);
                });
            } else {
                $('#driverTableBody').append("<tr><td colspan='7'>No drivers found</td></tr>");
            }
            
            updatePagination();
        }
        
        let totalPages = 0;

function updatePagination() {
    totalPages = Math.ceil(driversData.length / rowsPerPage);
    
    // Clear previous page numbers
    $('#page-numbers').empty();
    
    // Add page numbers
    for (var i = 1; i <= totalPages; i++) {
        var pageNumClass = i === currentPage ? 'page-number active' : 'page-number';
        $('#page-numbers').append(`<div class="${pageNumClass}" data-page="${i}">${i}</div>`);
    
    pageNumberElement.on('click', function() {
            var page = parseInt($(this).text());
            goToPage(page);
        });
        
        $('#page-numbers').append(pageNumberElement);
    }
    // Enable/disable prev/next buttons
    $('#prevPageBtn').prop('disabled', currentPage === 1);
    $('#nextPageBtn').prop('disabled', currentPage === totalPages || totalPages === 0);
}


function goToPage(page) {
    currentPage = page;
    renderTable();
    $('.page-number').removeClass('active');
    $(`.page-number:contains(${page})`).addClass('active');
}

function changePage(step) {
    var newPage = currentPage + step;
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        renderTable();
    }
}

// Update event handlers for page navigation
$('#prevPageBtn').on('click', function() {
    changePage(-1);
});

$('#nextPageBtn').on('click', function() {
    changePage(1);
});

$(document).on('click', '.page-number', function() {
    var page = parseInt($(this).data('page'));
    goToPage(page);
});

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
            $.ajax({
                url: 'include/handlers/get_driver.php?id=' + driverId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
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
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert("An error occurred while fetching driver data.");
                }
            });
        }

        function deleteDriver(driverId) {
            if (confirm("Are you sure you want to delete this driver?")) {
                // Send AJAX request to delete the driver
                $.ajax({
                    url: 'include/handlers/delete_driver.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ driverId: driverId }),
                    success: function(data) {
                        if (data.success) {
                            alert("Driver deleted successfully.");
                            // Refresh the data without reloading the page
                            fetchDrivers();
                        } else {
                            alert("Error deleting driver: " + data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert("An error occurred while deleting the driver.");
                    }
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
            $.ajax({
                url: 'include/handlers/save_driver.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(data) {
                    if (data.success) {
                        alert(modalMode === 'add' ? "Driver added successfully." : "Driver updated successfully.");
                        // Refresh the data without reloading the page
                        fetchDrivers();
                        closeModal();
                    } else {
                        alert("Error: " + data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert("An error occurred while saving the driver data.");
                }
            });
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