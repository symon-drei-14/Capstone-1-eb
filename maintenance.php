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
    <title>Maintenance Scheduling</title>
    
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/maintenancestyle.css">
    <!-- Add jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<style>
    body {
    font-family: Arial, sans-serif;
    margin: 100px;
    background-color: rgb(241, 241, 244);
}

table {
    width: 98%;
    border-collapse: collapse;
    margin-top: 20px;

}

th, td {
    padding: 12px;
    text-align: left;
    border-radius: 1px;
    text-align: center;
}

th {
    background-color: #ffffff;
    font-weight: bold;
    position: relative;
    box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px;
    border-bottom: 5px double #d3d1d15c;
    z-index: 1;
    text-align: center;
}

tr:nth-child(even) {
    background-color: #f0eeee9a; /* Light gray for even rows */
}

tr:nth-child(odd) {
    background-color: #ffffff; /* White for odd rows */
}

tr:hover {
    background-color: #e0e0e0; /* Light gray on hover */
}

.actions {
    text-align: center;
}

/* Column Widths */
th:nth-child(1), td:nth-child(1) {
    width: 5%; 
}

th:nth-child(2), td:nth-child(2) {
    width: 7%; 
}

th:nth-child(3), td:nth-child(3) {
    width: 12%; 
}

th:nth-child(4), td:nth-child(4) {
    width: 20%;
}

th:nth-child(5), td:nth-child(5) {
    width: 10%;
}

th:nth-child(6), td:nth-child(6) {
    width: 10%; 
}

th:nth-child(7), td:nth-child(7) {
    width: 10%; 
}

th:nth-child(8), td:nth-child(8) {
    width: 5%; 
}

/* Status Colors */
.status-completed {
    background-color: #4CAF50; /* Green */
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

.status-pending {
    background-color: #FF9800; /* Orange */
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

.status-in-progress {
    background-color: #2196F3; /* Blue */
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

.status-overdue {
    background-color: #F44336; /* Red */
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
}

.actions button {
    padding: 6px 12px;
    font-size: 14px;
    cursor: pointer;
    border: none;
    border-radius: 7px;
    margin: 0 5px;
    transition: background-color 0.3s, color 0.3s;
    width: 100px; 
    white-space: nowrap; 
    margin-bottom: 10px;
}

/* Individual Button Styles */
.actions button.edit {
    background-color: #4CAF50; /* Green */
    color: white;
}

.actions button.delete {
    background-color: #d7584f; /* Red */
    color: white;
}

.actions button.history {
    background-color: #2196F3; /* Orange */
    color: rgb(255, 252, 252);
}

/* Button Hover Effects */
.actions button:hover {
    opacity: 0.5;
}

.main-content3{
    width: 90vw;
    height: 145vh;
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
    overflow-x: hidden;
    overflow-y:hidden;
}

.container{
    padding: 20px;
}

.table-container {
    margin-bottom: 20px;
   
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 20px;
}

.pagination button {
    background-color: #ffffff00; /* Blue */
    color: rgb(0, 0, 0);
    border: none;
    padding: 6px 12px;
    font-size: 18px;
    cursor: pointer;
    border-radius: 10px;
    margin: 0 5px;
}

.pagination .prev, .pagination .next {
    font-size: 18px;
}

.pagination button:hover {
    opacity: 0.7;
}

.page-numbers {
    display: inline-flex;
    gap: 5px;
    align-items: center;
}

.page-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid #ccc;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    font-weight: bold;
    background-color:rgb(255, 255, 255);
    transition: background-color 0.3s, color 0.3s;
}

.page-number:hover {
    background-color:rgba(183, 181, 181, 0.95);
    color: #fff;
}

.page-number.active {
    background-color:rgba(255, 255, 255, 0.82);
    color: black;
    border-color:rgb(26, 97, 12);
    border-width:2px;
}

.add_sched{
    background-color: #4CAF50;
    border:#ffffff;
    color: #ffffff;
    padding: 10px;
    border-radius: 5px;
    font-size: 14px;
    font-weight: bold;
}

.reminder_btn{
    background-color: #e4873f;
    border:#ffffff;
    color: #ffffff;
    padding: 10px;
    border-radius: 5px;
    font-size: 14px;
    font-weight: bold;

}

.modal {
    display: none; 
    position: fixed; 
    z-index: 11000; 
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto; 
    background-color: rgba(0, 0, 0, 0.4); 
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%; 
    max-width: 400px;
    border-radius: 20px;
    box-shadow: rgba(0, 0, 0, 0.25) 0px 54px 55px, rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.12) 0px 4px 6px, rgba(0, 0, 0, 0.17) 0px 12px 13px, rgba(0, 0, 0, 0.09) 0px -3px 5px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
    text-align: center;
}

.modal-footer {
    display: flex;
    justify-content: flex-end; /* Aligns buttons to the right */
    margin-top: 20px; /* Optional: Adds some space above the buttons */
}

.modal-footer button {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    font-size: 16px;
    margin-left: 10px;
}

.modal-footer button:hover {
    opacity: 0.8;


}

form label {
    font-weight: bold;
    margin-bottom: 8px;
}

form input, form select {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
    font-size: 16px;
}


button[type="submit"] {
    background-color: #4CAF50; /* Green */
    color: white;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

button[type="submit"]:hover {
    background-color: #28652b; 
}

.cancelbtn{
    background-color: #d02b2b; /* Green */
    color: white;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.cancelbtn:hover{
    background-color: #a22222; /* Green */
    color: white;
    padding: 12px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}


#historyModal .modal-content {
    width: 70%; 
    max-width: 400px;
    box-shadow: rgba(0, 0, 0, 0.25) 0px 54px 55px, rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.12) 0px 4px 6px, rgba(0, 0, 0, 0.17) 0px 12px 13px, rgba(0, 0, 0, 0.09) 0px -3px 5px;
}

#historyModal .history-list {
    margin-top: 10px;
}

#historyModal .history-item {
    margin-bottom: 15px;
    line-height: 30px;

}

#historyModal .history-item strong {
    display: inline-block;
    width: 150px;
    font-weight: bold;
}

#historyModal .history-item hr {
    margin-top: 10px;
    border: 1px solid #ddd;
}

#historyModal .close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

#historyModal .close:hover,
#historyModal .close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

#dateSortIcon{
    font-size:20px;
    position: relative;

}
.submitbtn{
    background-color:green;
    padding:12px;
    font-size:14px;
    border:none;
    border-radius:5px;
    color:white;
}

</style>
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
                <h2>Preventive Maintenance Scheduling</h2>
                <div class="button-row">
                    <button class="add_sched" onclick="openModal('add')">Add Maintenance Schedule</button>
                    <button class="reminder_btn" onclick="openRemindersModal()">Maintenance Reminders</button>
                </div>
                <br />

                <div class="table-container">
                    <table id="maintenanceTable">
                        <thead>
                            <tr>
                      
                                <th>Truck ID</th>
                                <th>License Plate</th>
                                <th onclick="sortByDate()" style="cursor:pointer;">
                             Date of <br /> Inspection <span id="dateSortIcon">⬍</span>
                            </th>

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
             

                    <div class="pagination">
                    <button class="prev" onclick="changePage(-1)">◄</button> 
                    <div id="page-numbers" class="page-numbers"></div>
                    <button class="next" onclick="changePage(1)">►</button> 
</div>
            </div>
        </section>
        </div>
    </div>

    <!-- Add/Edit Maintenance Modal -->
    <div id="maintenanceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Maintenance Schedule</h2>
            <form id="maintenanceForm">
                <input type="hidden" id="maintenanceId" name="maintenanceId">
                
                <label for="truckId">Truck ID:</label>
                <select id="truckId" name="truckId" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                </select><br><br>
                <label for="licensePlate">License Plate:</label>
                <input type="text" id="licensePlate" name="licensePlate" ><br><br>

                <label for="date">Date of Inspection:</label>
                <input type="date" id="date" name="date" required><br><br>

                <label for="remarks">Remarks:</label>
                <input type="text" id="remarks" name="remarks" required><br><br>

                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="Completed">Completed</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Overdue">Overdue</option>
                </select><br><br>
                
                <label for="supplier">Supplier:</label>
                <input type="text" id="supplier" name="supplier"><br><br>

                <label for="cost">Cost:</label>
                <input type="number" id="cost" name="cost" step="0.01"><br><br>

                <button type="button" class="submitbtn" onclick="saveMaintenanceRecord()">Submit</button>
                <button type="button" class="cancelbtn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Maintenance History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeHistoryModal()">&times;</span>
            <h2>Maintenance History</h2>
            <div class="history-list" id="historyList">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Maintenance Reminders Modal -->
    <div id="remindersModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRemindersModal()">&times;</span>
            <h2>Maintenance Reminders</h2>
            <div class="reminders-list" id="remindersList">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let currentTruckId = 0;
        let isEditing = false;
        
        // Load data when page loads
        $(document).ready(function() {
            loadMaintenanceData();
        });
        
        // Load maintenance records
       // Load maintenance records
function loadMaintenanceData() {
    fetch('include/handlers/maintenance_handler.php?action=getRecords&page=' + currentPage)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(response => {
            console.log("Response data:", response); // Add this for debugging
            renderTable(response.records || []);
            totalPages = response.totalPages || 1;
            currentPage = response.currentPage || 1;
            updatePagination();
        })
        .catch(error => {
            console.error("Error loading data:", error);
            alert("Failed to load maintenance records: " + error.message);
            // Show empty state in the table
            const tableBody = document.querySelector("#maintenanceTable tbody");
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center">Error loading data. Please try again.</td></tr>';
        });
}

// Render table with maintenance data
function renderTable(data) {
    const tableBody = document.querySelector("#maintenanceTable tbody");
    tableBody.innerHTML = ""; // Clear existing rows
    
    if (data.length === 0) {
        const tr = document.createElement("tr");
        tr.innerHTML = '<td colspan="8" class="text-center">No maintenance records found</td>';
        tableBody.appendChild(tr);
        return;
    }

    data.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${row.truck_id}</td>
            <td>${row.licence_plate || 'N/A'}</td>
            <td>${formatDate(row.date_mtnce)}</td>
            <td>${row.remarks}</td>
            <td><span class="status-${row.status.toLowerCase().replace(" ", "-")}">${row.status}</span></td>
            <td>${row.supplier || 'N/A'}</td>
            <td>₱ ${parseFloat(row.cost).toFixed(2)}</td>
            <td class="actions">
                <button class="edit" onclick="openEditModal(${row.maintenance_id}, ${row.truck_id}, '${row.licence_plate || ''}', '${row.date_mtnce}', '${row.remarks}', '${row.status}', '${row.supplier || ''}', ${row.cost})">Edit</button>
                <button class="delete" onclick="deleteMaintenance(${row.maintenance_id})">Delete</button>
                <button class="history" onclick="openHistoryModal(${row.truck_id})">View History</button>
            </td>
        `;
        tableBody.appendChild(tr);
    });
}
        // Format date for display
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toISOString().split('T')[0];
        }
        
        // Update pagination display
        function updatePagination() {
    const pageNumbersContainer = document.getElementById("page-numbers");
    pageNumbersContainer.innerHTML = "";

    const createPageButton = (page) => {
        const pageBtn = document.createElement("div");
        pageBtn.classList.add("page-number");
        if (page === currentPage) {
            pageBtn.classList.add("active");
        }
        pageBtn.textContent = page;
        pageBtn.onclick = () => {
            if (page !== currentPage) {
                currentPage = page;
                loadMaintenanceData();
            }
        };
        pageNumbersContainer.appendChild(pageBtn);
    };

    const addEllipsis = () => {
        const ellipsis = document.createElement("div");
        ellipsis.classList.add("page-number");
        ellipsis.textContent = "...";
        ellipsis.style.pointerEvents = "none";
        pageNumbersContainer.appendChild(ellipsis);
    };

    // Always show first page
    if (totalPages <= 7) {
        // Show all pages if small number
        for (let i = 1; i <= totalPages; i++) {
            createPageButton(i);
        }
    } else {
        // Show first page
        createPageButton(1);

        if (currentPage > 4) {
            addEllipsis();
        }

        // Calculate range of middle buttons
        let startPage = Math.max(2, currentPage - 1);
        let endPage = Math.min(totalPages - 1, currentPage + 1);

        for (let i = startPage; i <= endPage; i++) {
            createPageButton(i);
        }

        if (currentPage < totalPages - 3) {
            addEllipsis();
        }

        // Show last page
        createPageButton(totalPages);
    }
}
        
        // Change page
        function changePage(direction) {
            const newPage = currentPage + direction;
            
            if (newPage < 1 || newPage > totalPages) {
                return;
            }
            
            currentPage = newPage;
            loadMaintenanceData();
        }
        
        // Open modal for add/edit
        function openModal(mode) {
            document.getElementById("maintenanceModal").style.display = "block";
            
            if (mode === 'add') {
                isEditing = false;
                document.getElementById("modalTitle").textContent = "Add Maintenance Schedule";
                document.getElementById("maintenanceForm").reset();
                document.getElementById("maintenanceId").value = "";
                // Set today's date as default
                const today = new Date().toISOString().split('T')[0];
                document.getElementById("date").value = today;
            }
        }
        
        // Open edit modal with data
        function openEditModal(id, truckId, licensePlate, date, remarks, status, supplier, cost) {
    isEditing = true;
    document.getElementById("modalTitle").textContent = "Edit Maintenance Schedule";
    document.getElementById("maintenanceId").value = id;
    document.getElementById("truckId").value = truckId;
    document.getElementById("licensePlate").value = licensePlate || '';
    document.getElementById("date").value = date;
    document.getElementById("remarks").value = remarks;
    document.getElementById("status").value = status;
    document.getElementById("supplier").value = supplier;
    document.getElementById("cost").value = cost;
    
    document.getElementById("maintenanceModal").style.display = "block";
}
        // Close the modal
        function closeModal() {
            document.getElementById("maintenanceModal").style.display = "none";
        }
        
        // Save maintenance record (add or update)
        function saveMaintenanceRecord() {
    const form = document.getElementById("maintenanceForm");
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const maintenanceId = document.getElementById("maintenanceId").value;
    const action = isEditing ? 'edit' : 'add';
    
    const formData = {
        maintenanceId: maintenanceId ? parseInt(maintenanceId) : null,
        truckId: parseInt(document.getElementById("truckId").value),
        licensePlate: document.getElementById("licensePlate").value,
        date: document.getElementById("date").value,
        remarks: document.getElementById("remarks").value,
        status: document.getElementById("status").value,
        supplier: document.getElementById("supplier").value,
        cost: parseFloat(document.getElementById("cost").value || 0)
    };
    
    $.ajax({
        url: 'include/handlers/maintenance_handler.php?action=' + action,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                closeModal();
                loadMaintenanceData();
                alert(isEditing ? "Maintenance record updated successfully!" : "Maintenance record added successfully!");
            } else {
                alert("Error: " + (response.message || "Unknown error"));
            }
        },
        error: function(xhr, status, error) {
            console.error("Error saving record: " + error);
            alert("Failed to save maintenance record.");
        }
    });
}
        
        // Delete maintenance record
        function deleteMaintenance(id) {
            if (!confirm("Are you sure you want to delete this maintenance record?")) {
                return;
            }
            
            $.ajax({
                url: 'include/handlers/maintenance_handler.php?action=delete&id=' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadMaintenanceData();
                        alert("Maintenance record deleted successfully!");
                    } else {
                        alert("Error: " + (response.message || "Unknown error"));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error deleting record: " + error);
                    alert("Failed to delete maintenance record.");
                }
            });
        }
        
        // Open maintenance history modal
        function openHistoryModal(truckId) {
            currentTruckId = truckId;
            
            $.ajax({
                url: 'include/handlers/maintenance_handler.php?action=getHistory&truckId=' + truckId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    const historyList = document.getElementById("historyList");
                    historyList.innerHTML = ""; // Clear existing items
                    
                    if (response.history.length === 0) {
                        historyList.innerHTML = "<p>No maintenance history found for this truck.</p>";
                    } else {
                        response.history.forEach(item => {
                            const historyItem = document.createElement("div");
                            historyItem.className = "history-item";
                            historyItem.innerHTML = `
                                <strong>Date of Inspection:</strong> ${formatDate(item.date_mtnce)}<br>
                                <strong>Remarks:</strong> ${item.remarks}<br>
                                <strong>Status:</strong> ${item.status}<br>
                                <strong>Supplier:</strong> ${item.supplier || 'N/A'}<br>
                                <strong>Cost:</strong> ₱ ${parseFloat(item.cost).toFixed(2)}<br>
                                <hr>
                            `;
                            historyList.appendChild(historyItem);
                        });
                    }
                    
                    document.getElementById("historyModal").style.display = "block";
                },
                error: function(xhr, status, error) {
                    console.error("Error loading history: " + error);
                    alert("Failed to load maintenance history.");
                }
            });
        }
        
        // Close history modal
        function closeHistoryModal() {
            document.getElementById("historyModal").style.display = "none";
        }
        
        // Open reminders modal
        function openRemindersModal() {
            $.ajax({
                url: 'include/handlers/maintenance_handler.php?action=getReminders',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    const remindersList = document.getElementById("remindersList");
                    remindersList.innerHTML = ""; // Clear existing items
                    
                    if (response.reminders.length === 0) {
                        remindersList.innerHTML = "<p>No upcoming maintenance reminders.</p>";
                    } else {
                        response.reminders.forEach(item => {
                            const daysRemaining = parseInt(item.days_remaining);
                            let statusClass = '';
                            let daysText = '';
                            
                            if (daysRemaining < 0) {
                                statusClass = 'overdue';
                                daysText = `<span class="overdue">OVERDUE by ${Math.abs(daysRemaining)} days</span>`;
                            } else if (daysRemaining === 0) {
                                statusClass = 'due-today';
                                daysText = `<span class="due-today">DUE TODAY</span>`;
                            } else {
                                statusClass = 'upcoming';
                                daysText = `<span class="upcoming">Due in ${daysRemaining} days</span>`;
                            }
                            
                            const reminderItem = document.createElement("div");
                            reminderItem.className = `reminder-item ${statusClass}`;
                            reminderItem.innerHTML = `
                                <strong>Truck:</strong> ${item.truck_id} (${item.licence_plate || 'N/A'})<br>
                                <strong>Maintenance:</strong> ${item.remarks}<br>
                                <strong>Due Date:</strong> ${formatDate(item.date_mtnce)} - ${daysText}<br>
                                <strong>Status:</strong> ${item.status}<br>
                                <hr>
                            `;
                            remindersList.appendChild(reminderItem);
                        });
                    }
                    
                    document.getElementById("remindersModal").style.display = "block";
                },
                error: function(xhr, status, error) {
                    console.error("Error loading reminders: " + error);
                    alert("Failed to load maintenance reminders.");
                }
            });
        }
        
        // Close reminders modal
        function closeRemindersModal() {
            document.getElementById("remindersModal").style.display = "none";
        }

        let sortDateAsc = true; // default sorting order

function sortByDate() {
    const tableBody = document.querySelector("#maintenanceTable tbody");
    const rows = Array.from(tableBody.querySelectorAll("tr"));

    const sortedRows = rows.sort((a, b) => {
        const dateA = new Date(a.children[2].textContent.trim());
        const dateB = new Date(b.children[2].textContent.trim());

        return sortDateAsc ? dateA - dateB : dateB - dateA;
    });

    // Toggle sort direction
    sortDateAsc = !sortDateAsc;

    // Update icon
    const icon = document.getElementById("dateSortIcon");
    icon.textContent = sortDateAsc ? '⬆' : '⬇';

    // Replace rows with sorted ones
    tableBody.innerHTML = '';
    sortedRows.forEach(row => tableBody.appendChild(row));
}

    </script>

</body>
</html>