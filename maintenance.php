<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Scheduling</title>
    
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/maintenancestyle.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<style>

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
                    <option value="Pending" selected>Pending</option>
                    <option value="Completed">Completed</option>
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
        
        $(document).ready(function() {
            loadMaintenanceData();
        });
        
        function loadMaintenanceData() {
            fetch('include/handlers/maintenance_handler.php?action=getRecords&page=' + currentPage)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(response => {
                    console.log("Response data:", response); 
                    renderTable(response.records || []);
                    totalPages = response.totalPages || 1;
                    currentPage = response.currentPage || 1;
                    updatePagination();
                })
                .catch(error => {
                    console.error("Error loading data:", error);
                    alert("Failed to load maintenance records: " + error.message);
                    const tableBody = document.querySelector("#maintenanceTable tbody");
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-center">Error loading data. Please try again.</td></tr>';
                });
        }

        function renderTable(data) {
            const tableBody = document.querySelector("#maintenanceTable tbody");
            tableBody.innerHTML = ""; 
            
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
      
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toISOString().split('T')[0];
        }
        
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

            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) {
                    createPageButton(i);
                }
            } else {
                createPageButton(1);

                if (currentPage > 4) {
                    addEllipsis();
                }

                let startPage = Math.max(2, currentPage - 1);
                let endPage = Math.min(totalPages - 1, currentPage + 1);

                for (let i = startPage; i <= endPage; i++) {
                    createPageButton(i);
                }

                if (currentPage < totalPages - 3) {
                    addEllipsis();
                }

                createPageButton(totalPages);
            }
        }
        
        function changePage(direction) {
            const newPage = currentPage + direction;
            
            if (newPage < 1 || newPage > totalPages) {
                return;
            }
            
            currentPage = newPage;
            loadMaintenanceData();
        }
        
        function openModal(mode) {
            document.getElementById("maintenanceModal").style.display = "block";
            
            if (mode === 'add') {
                isEditing = false;
                document.getElementById("modalTitle").textContent = "Add Maintenance Schedule";
                document.getElementById("maintenanceForm").reset();
                document.getElementById("maintenanceId").value = "";
                
                // Set default values for add mode
                const today = new Date().toISOString().split('T')[0];
                document.getElementById("date").value = today;
                document.getElementById("date").setAttribute("min", today);
                
                // Force status to "Pending" for new records
                document.getElementById("status").value = "Pending";
                document.getElementById("status").disabled = true;
            }
        }
        
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
            
            // Enable the status dropdown for editing
            document.getElementById("status").disabled = false;
            
            document.getElementById("maintenanceModal").style.display = "block";
        }
        
        function closeModal() {
            document.getElementById("maintenanceModal").style.display = "none";
            // Always enable the status dropdown when closing the modal
            document.getElementById("status").disabled = false;
        }
        
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
        
        function openHistoryModal(truckId) {
            currentTruckId = truckId;
            
            $.ajax({
                url: 'include/handlers/maintenance_handler.php?action=getHistory&truckId=' + truckId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    const historyList = document.getElementById("historyList");
                    historyList.innerHTML = ""; 
                    
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
        
        function closeHistoryModal() {
            document.getElementById("historyModal").style.display = "none";
        }
        
        function openRemindersModal() {
            $.ajax({
                url: 'include/handlers/maintenance_handler.php?action=getReminders',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    const remindersList = document.getElementById("remindersList");
                    remindersList.innerHTML = ""; 
                    
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
        
        function closeRemindersModal() {
            document.getElementById("remindersModal").style.display = "none";
        }

        let sortDateAsc = true; 

        function sortByDate() {
            const tableBody = document.querySelector("#maintenanceTable tbody");
            const rows = Array.from(tableBody.querySelectorAll("tr"));

            const sortedRows = rows.sort((a, b) => {
                const dateA = new Date(a.children[2].textContent.trim());
                const dateB = new Date(b.children[2].textContent.trim());

                return sortDateAsc ? dateA - dateB : dateB - dateA;
            });

            sortDateAsc = !sortDateAsc;

            const icon = document.getElementById("dateSortIcon");
            icon.textContent = sortDateAsc ? '⬆' : '⬇';

            tableBody.innerHTML = '';
            sortedRows.forEach(row => tableBody.appendChild(row));
        }
    </script>
</body>
</html>