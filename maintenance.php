<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess(); // No role needed—logic is handled internally
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Scheduling</title>
    
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/maintenancestyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<style>
.edit-reasons-section {
    display: none;
    margin-top: 20px;
    padding: 15px;

    border-radius: 5px;
    border: 1px solid #ddd;
    margin-bottom:15px;
}



.reasons-container {
    display: flex;
    flex-direction: column;

}


.reason-option {
    display: flex;
    align-items: center;
    background-color: #fff;
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #ddd;

}

.reason-option label {
    display: block;
    cursor: pointer;
    margin: 0;
    font-size: 14px;
    flex-grow: 1;
    word-break: break-word;
    margin-top:10px;
    margin-bottom:-10px;
}


.reason-option input[type="checkbox"] {
    margin-right: 51em;
    flex-shrink: 0;
   position:relative;
   top:-1em;
   

}

.other-reason {
    margin-top: 10px;
    padding-left: 20px;

}   

.other-reason label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.other-reason textarea {
    width: 90%;
    padding: 8px;

    border-radius: 4px;
    resize: vertical;
    min-height: 60px;
}

    .view-remarks-btn {
    background-color: #4b77deff;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 10px;
    margin-top: 5px;
    font-weight:bold;
}



.view-remarks-btn:hover {
    background-color: #45a049;
}

.remarks-modal-content {
    padding: 10px;
    background: white;
    border-radius: 5px;
}

.remarks-modal-content h3 {
    margin-top: 0;
    color: #333;
}

.remarks-modal-content ul {
    padding-left: 0px;
}

.remarks-modal-content li {
    margin-bottom: 8px;
}

.remarks-modal-content button {
    background-color: #9f1515ff;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 15px;
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%; 
    max-width: 500px; 
    border-radius: 20px;
    box-shadow: rgba(0, 0, 0, 0.25) 0px 54px 55px, rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.12) 0px 4px 6px, rgba(0, 0, 0, 0.17) 0px 12px 13px, rgba(0, 0, 0, 0.09) 0px -3px 5px;
    overflow-x: hidden; 
    max-height: 80vh; 
    overflow-y: auto; 

}


.remarks-modal-content button:hover {
    background-color: #45a049;
}

    body{
        margin-top:50px;
        font-family: Arial, sans-serif;
    }
    @media (max-width: 768px) {
        .sidebar {
            display: none;
            position: absolute;
            z-index: 999;
            background-color: #fff;
            width: 250px;
            height: 100%;
            box-shadow: 2px 0 5px rgba(0,0,0,0.2);
        }

        .sidebar.show {
            display: block;
        }
    }

    .toggle-sidebar-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #333;
        z-index: 1300;
    }


    .sidebar {
        position: fixed;
        top: 1rem;
        left: 0;
        width: 300px; 
        height: 100%;
        background-color: #edf1ed;
        color: #161616 !important;
        padding: 20px;
        box-sizing: border-box;
        overflow-x: hidden;
        overflow-y: auto;
        z-index: 1100;
        border-right: 2px solid #16161627;
        transform: translateX(-100%); 
        transition: transform 0.3s ease;
    }


    .sidebar.expanded {
        transform: translateX(0);
    }

    .sidebar.expanded .sidebar-item a,
    .sidebar.expanded .sidebar-item span {
        visibility: visible;
        opacity: 1;
    }
    .main-content3 {
        margin-top: 80px;
        margin-left: 10px; 
        margin-right: 20px; 
        width: calc(100% - 25px); 
        height: auto;
        max-height: 150vh;
        min-height:150vh;
        transition: margin-left 0.3s ease;
        overflow-y: hidden;
    }

    th:nth-child(9), td:nth-child(9) {
        width: 5%; 
    }

    input:invalid, select:invalid {
    border: 1px solid red;
}

.error-message {
    color: red;
    font-size: 12px;
    margin-top: 5px;
}

.status-filter-container {
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-filter-container label {
    font-weight: bold;
    color: #333;
}

.status-filter-container select {
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background-color: white;
    cursor: pointer;
}

.status-filter-container select:focus {
    outline: none;
    border-color: #4b77de;
}

/* Remove the deleted label styles and adjust table columns */
#maintenanceTable th:nth-child(8), 
#maintenanceTable td:nth-child(8) {
    width: 15%;
}

#maintenanceTable th:nth-child(9), 
#maintenanceTable td:nth-child(9) {
    width: 15%;
}

.remarks-modal-content h4 {
    margin-top: 15px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.deleted-row {
    background-color: #ffeeee;
}

.deleted-row td {
    opacity: 0.7;
}

.deleted-row .status-pending,
.deleted-row .status-completed,
.deleted-row .status-in-progress,
.deleted-row .status-overdue {
    text-decoration: line-through;
}

.restore {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
}

.restore:hover {
    background-color: #45a049;
}

.full-delete {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
    margin-top: 5px;
    display: inline-block;
}

.full-delete:hover {
    background-color: #c82333;
}

.filter-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 10px 0;
}

.status-filter-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-filter-container label {
    font-weight: bold;
    color: #333;
}

.status-filter-container select {
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background-color: white;
    cursor: pointer;
}

.search-container {
    position: relative;
    display: flex;
    align-items: center;
}

.search-container .fa-search {
    position: absolute;
    left: 10px;
    color: #aaa;
    pointer-events: none;
}

#searchInput {
    padding: 5px 10px 5px 30px;
    border-radius: 4px;
    border: 1px solid #ddd;
    width: 200px;
}

#searchInput:focus {
    outline: none;
    border-color: #4b77de;
}
</style>
<body>

<header class="header">
          <button id="toggleSidebarBtn" class="toggle-sidebar-btn">
  <i class="fa fa-bars"></i>
</button>
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

         <div class="filter-controls">
    <div class="status-filter-container">
        <label for="statusFilter">Show:</label>
        <select id="statusFilter" onchange="filterTableByStatus()">
            <option value="all">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Completed">Completed</option>
            <option value="In Progress">In Progress</option>
            <option value="Overdue">Overdue</option>
            <option value="Deleted">Deleted</option>
        </select>
    </div>
    
    <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search..." onkeyup="searchMaintenance()">
    </div>
</div>
<br />
                <br />

                <div class="table-container">
                    <table id="maintenanceTable">
                        <thead>
                            <tr>
                              <th onclick="sortByTruckId()" style="cursor:pointer;">
    Truck ID <span id="truckIdSortIcon">⬍</span>
</th>
                                <th>License Plate</th>
                                <th onclick="sortByDate()" style="cursor:pointer;">
                             Date of <br /> Inspection <span id="dateSortIcon">⬍</span>
                            </th>
                                <th>Remarks</th>
                                <th>Status</th>
                                <th>Supplier</th>
                                <th>Cost</th>
                                <th>Last Modified</th>
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
                <label for="maintenanceType">Maintenance Type:</label>
<select id="maintenanceType" name="maintenanceType" required>
    <option value="preventive">Preventive Maintenance</option>
    <option value="emergency">Emergency Repair</option>
</select><br><br>

                <label for="truckId">Truck ID:</label>
                <select id="truckId" name="truckId" required>
                    <!-- Will be populated by JavaScript -->
                </select><br><br>
                <label for="licensePlate">License Plate:</label>
                <input type="text" id="licensePlate" name="licensePlate" readonly><br><br>

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
                
<div class="edit-reasons-section">
    <label>Reason for Edit (select all that apply):</label>
    <div class="reasons-container">
        <div class="reason-option">
            <label>
                Changed maintenance schedule as per supplier availability
                <input type="checkbox" name="editReason" value="Changed maintenance schedule as per supplier availability">
            </label>
        </div>
        <div class="reason-option">
            <label>
                Updated maintenance type based on vehicle condition
                <input type="checkbox" name="editReason" value="Updated maintenance type based on vehicle condition">
            </label>
        </div>
        <div class="reason-option">
            <label>
                Adjusted date due to parts availability
                <input type="checkbox" name="editReason" value="Adjusted date due to parts availability">
            </label>
        </div>
        <div class="reason-option">
            <label>
                Updated cost estimate after inspection
                <input type="checkbox" name="editReason" value="Updated cost estimate after inspection">
            </label>
        </div>
        <div class="reason-option">
            <label>
                Changed status based on work progress
                <input type="checkbox" name="editReason" value="Changed status based on work progress">
            </label>
        </div>
        <div class="reason-option">
            <label>
                Updated supplier information
                <input type="checkbox" name="editReason" value="Updated supplier information">
            </label>
        </div>
        <div class="reason-option">
            <label>
                Corrected data entry error
                <input type="checkbox" name="editReason" value="Corrected data entry error">
            </label>
        </div>
        <div class="reason-option">
            <label>
                Other (please specify below)
                <input type="checkbox" name="editReason" value="Other">
            </label>
        </div>
        <div class="other-reason">
            <label for="otherReasonText">Specify other reason:</label>
            <textarea id="otherReasonText" name="otherReasonText" rows="3" placeholder="Enter specific reason for edit"></textarea>
        </div>
    </div>
</div>
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

    <div id="remarksModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div id="remarksModalContent"></div>
    </div>
</div>

    <script>
    
document.getElementById('otherReasonText').addEventListener('input', function() {
    const otherCheckbox = document.querySelector('input[name="editReason"][value="Other"]');
    if (this.value.trim() !== '') {
        otherCheckbox.checked = true;
    }
});


document.querySelector('input[name="editReason"][value="Other"]').addEventListener('change', function() {
    if (!this.checked) {
        document.getElementById('otherReasonText').value = '';
    }
});
        let currentPage = 1;
        let totalPages = 1;
        let currentTruckId = 0;
        let isEditing = false;
        let trucksList = [];
        let sortTruckIdAsc = true; 
        
    function getLocalDate() {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60000; // offset in milliseconds
    const localISOTime = (new Date(now - offset)).toISOString().slice(0, 10);
    return localISOTime;
}

$(document).ready(function() {
    loadMaintenanceData();
    fetchTrucksList();
});

        function validateMaintenanceForm() {
    const requiredFields = [
        {id: 'truckId', name: 'Truck ID'},
        {id: 'date', name: 'Date of Inspection'},
        {id: 'remarks', name: 'Remarks'},
        {id: 'status', name: 'Status'},
        {id: 'maintenanceType', name: 'Maintenance Type'}
    ];
    
    for (const field of requiredFields) {
        const element = document.getElementById(field.id);
        if (!element.value) {
            alert(`Please fill in the ${field.name} field`);
            element.focus();
            return false;
        }
    }
    
    // Additional validation - check if date is in the future for new records
    if (!isEditing) {
        const today = new Date();
        const inspectionDate = new Date(document.getElementById('date').value);
        if (inspectionDate < today) {
            alert("Inspection date must be today or in the future");
            document.getElementById('date').focus();
            return false;
        }
    }
    
    return true;
}


        
      function fetchTrucksList() {
    fetch('include/handlers/truck_handler.php?action=getActiveTrucks') // Changed endpoint
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                trucksList = data.trucks;
                populateTruckDropdown();
            }
        })
        .catch(error => {
            console.error("Error loading trucks:", error);
        });
}
        
        function populateTruckDropdown() {
            const truckDropdown = document.getElementById('truckId');
            truckDropdown.innerHTML = '';
            
            trucksList.forEach(truck => {
                const option = document.createElement('option');
                option.value = truck.truck_id;
                option.textContent = truck.truck_id ;
                option.setAttribute('data-plate-no', truck.plate_no);
                truckDropdown.appendChild(option);
            });
            
            // Add event listener for truck selection change
            truckDropdown.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const plateNo = selectedOption.getAttribute('data-plate-no');
                document.getElementById('licensePlate').value = plateNo || '';
            });
        }

        let currentStatusFilter = 'all';

function filterTableByStatus() {
    currentStatusFilter = document.getElementById('statusFilter').value;
    currentPage = 1; 
    loadMaintenanceData();
}

   
function loadMaintenanceData() {
    let url = `include/handlers/maintenance_handler.php?action=getRecords&page=${currentPage}`;
    
    if (currentStatusFilter !== 'all') {
        url += `&status=${encodeURIComponent(currentStatusFilter)}`;
    }
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(response => {
            renderTable(response.records || []);
            totalPages = response.totalPages || 1;
            currentPage = response.currentPage || 1;
            updatePagination();
        })
        .catch(error => {
            console.error("Error loading data:", error);
            alert("Failed to load maintenance records: " + error.message);
            const tableBody = document.querySelector("#maintenanceTable tbody");
            tableBody.innerHTML = '<tr><td colspan="9" class="text-center">Error loading data. Please try again.</td></tr>';
        });
}

function fetchAllRecordsForSearch() {
    let url = `include/handlers/maintenance_handler.php?action=getAllRecordsForSearch`;
    
    if (currentStatusFilter !== 'all') {
        url += `&status=${encodeURIComponent(currentStatusFilter)}`;
    }
    
    return fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        });
}

function renderTable(data) {
    const tableBody = document.querySelector("#maintenanceTable tbody");
    tableBody.innerHTML = ""; 
    
    if (data.length === 0) {
        const tr = document.createElement("tr");
        tr.innerHTML = '<td colspan="9" class="text-center">No maintenance records found</td>';
        tableBody.appendChild(tr);
        return;
    }

    data.forEach(row => {
        const tr = document.createElement("tr");
        tr.setAttribute('data-status', row.status);
        if (row.is_deleted) {
            tr.classList.add('deleted-row');
        }   
        const actionsCell = row.is_deleted 
            ? `
                <button class="restore" onclick="restoreMaintenance(${row.maintenance_id})">Restore</button>
                ${window.userRole === 'Full Admin' ? 
                  `<button class="full-delete" onclick="fullDeleteMaintenance(${row.maintenance_id})">Full Delete</button>` : ''}
                <button class="history" onclick="openHistoryModal(${row.truck_id})">View History</button>
              `
            : `
                <button class="edit" onclick="openEditModal(${row.maintenance_id}, ${row.truck_id}, '${row.licence_plate || ''}', '${row.date_mtnce}', '${row.remarks}', '${row.status}', '${row.supplier || ''}', ${row.cost}, '${row.maintenance_type || 'preventive'}')">Edit</button>
                <button class="delete" onclick="deleteMaintenance(${row.maintenance_id})">Delete</button>
                <button class="history" onclick="openHistoryModal(${row.truck_id})">View History</button>
              `;
        
        tr.innerHTML = `
            <td>${row.truck_id}</td>
            <td>${row.licence_plate || 'N/A'}</td>
            <td>${formatDate(row.date_mtnce)}</td>
            <td>${row.remarks}</td>
            <td><span class="status-${row.status.toLowerCase().replace(" ", "-")}">${row.status}</span></td>
            <td>${row.supplier || 'N/A'}</td>
            <td>₱ ${parseFloat(row.cost).toFixed(2)}</td>
            <td>
                <strong>${row.last_modified_by}</strong><br>
                ${formatDateTime(row.last_modified_at)}<br>
                ${(row.edit_reasons || row.delete_reason) ? 
                  `<button class="view-remarks-btn" 
                    data-reasons='${JSON.stringify({
                        editReasons: row.edit_reasons ? JSON.parse(row.edit_reasons) : null,
                        deleteReason: row.delete_reason
                    })}'>View Remarks</button>` : ''}
            </td>
            <td class="actions">
                ${actionsCell}
            </td>
        `;
        tableBody.appendChild(tr);
    });

    document.querySelectorAll('.view-remarks-btn').forEach(button => {
        button.addEventListener('click', function() {
            showEditRemarks(this.getAttribute('data-reasons'));
        });
    });
}

// Add this new function for full delete
function fullDeleteMaintenance(id) {
    if (confirm("Are you sure you want to PERMANENTLY delete this maintenance record? This cannot be undone!")) {
        fetch(`include/handlers/maintenance_handler.php?action=fullDelete&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Maintenance record permanently deleted!");
                loadMaintenanceData();
            } else {
                alert("Error: " + (data.message || "Unknown error"));
            }
        })
        .catch(error => {
            console.error("Error deleting record: " + error);
            alert("Failed to delete maintenance record.");
        });
    }
}


        function formatDateTime(datetimeString) {
            if (!datetimeString) return 'N/A';
            const date = new Date(datetimeString);
            return date.toLocaleString(); 
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
        
        // Use the new getLocalDate() function here
        document.getElementById("date").value = getLocalDate();
        document.getElementById("date").setAttribute("min", getLocalDate());
        
        document.getElementById("status").value = "Pending";
        document.getElementById("status").disabled = true;
        
        // Hide edit reasons section for add mode
        document.querySelector('.edit-reasons-section').style.display = 'none';
    }
}

function openEditModal(id, truckId, licensePlate, date, remarks, status, supplier, cost, maintenanceType) {
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
    document.getElementById("maintenanceType").value = maintenanceType || 'preventive';
    
    // Enable the status dropdown for editing
    document.getElementById("status").disabled = false;
    
    // Show edit reasons section for edit mode
    document.querySelector('.edit-reasons-section').style.display = 'block';
    
    // Reset checkboxes and textarea
    document.querySelectorAll('input[name="editReason"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('otherReasonText').value = '';
    
    document.getElementById("maintenanceModal").style.display = "block";
}
function showEditRemarks(reasonsJson) {
    try {
        const reasons = JSON.parse(reasonsJson);
        let html = '<div class="remarks-modal-content"><h3>Record Details</h3>';
        
        if (reasons.editReasons && reasons.editReasons.length > 0) {
            html += '<h4>Edit Reasons:</h4><ul>';
            reasons.editReasons.forEach(reason => {
                html += `<li>${reason}</li>`;
            });
            html += '</ul>';
        }
        
        if (reasons.deleteReason) {
            html += '<h4>Delete Reason:</h4>';
            html += `<p>${reasons.deleteReason}</p>`;
        }
        
        html += '<button onclick="document.getElementById(\'remarksModal\').style.display=\'none\'">Close</button></div>';
        
        document.getElementById('remarksModalContent').innerHTML = html;
        document.getElementById('remarksModal').style.display = 'block';
    } catch (e) {
        console.error('Error parsing remarks:', e);
        document.getElementById('remarksModalContent').innerHTML = 
            '<div class="remarks-modal-content"><p>Error displaying remarks</p></div>';
        document.getElementById('remarksModal').style.display = 'block';
    }
}     
     function closeModal() {
    document.getElementById("maintenanceModal").style.display = "none";
    // Always enable the status dropdown when closing the modal
    document.getElementById("status").disabled = false;
    // Hide edit reasons section when closing
    document.querySelector('.edit-reasons-section').style.display = 'none';
}
        
    function saveMaintenanceRecord() {
    // First validate the form
    if (!validateMaintenanceForm()) {
        return;
    }

    let editReasons = [];
    if (isEditing) {
        const checkboxes = document.querySelectorAll('input[name="editReason"]:checked');
        checkboxes.forEach(checkbox => {
            if (checkbox.value === "Other") {
                const otherReason = document.getElementById('otherReasonText').value.trim();
                if (otherReason) {
                    editReasons.push("Other: " + otherReason);
                }
            } else {
                editReasons.push(checkbox.value);
            }
        });
        
        if (editReasons.length === 0) {
            alert("Please select at least one reason for editing this record.");
            return;
        }
    }
    
    const form = document.getElementById("maintenanceForm");
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
        cost: parseFloat(document.getElementById("cost").value || 0),
        maintenanceType: document.getElementById("maintenanceType").value,
        editReasons: editReasons // Add this line to include edit reasons
    };
    
    console.log("Submitting form data:", formData);
    
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
            alert("Failed to save maintenance record. Please check console for details.");
        }
    });
}

function searchMaintenance() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    if (searchTerm.trim() === '') {
        // If search is empty, reload normal paginated data
        currentPage = 1;
        loadMaintenanceData();
        return;
    }
    
    // Fetch all records for searching
    fetchAllRecordsForSearch()
        .then(data => {
            const filteredRecords = data.records.filter(record => {
                return (
                    String(record.truck_id).toLowerCase().includes(searchTerm) ||
                    (record.licence_plate && record.licence_plate.toLowerCase().includes(searchTerm)) ||
                    (record.date_mtnce && formatDate(record.date_mtnce).toLowerCase().includes(searchTerm)) ||
                    (record.remarks && record.remarks.toLowerCase().includes(searchTerm)) ||
                    (record.status && record.status.toLowerCase().includes(searchTerm)) ||
                    (record.supplier && record.supplier.toLowerCase().includes(searchTerm)) ||
                    (record.cost && String(record.cost).toLowerCase().includes(searchTerm))
                );
            });
            
            renderTable(filteredRecords);
            // Hide pagination during search
            document.querySelector('.pagination').style.display = 'none';
        })
        .catch(error => {
            console.error("Error searching records:", error);
            alert("Failed to search maintenance records.");
        });
}
        
function deleteMaintenance(id) {
    if (!confirm("Are you sure you want to delete this maintenance record?")) {
        return;
    }
    
    const deleteReason = prompt("Please enter the reason for deleting this record:");
    if (deleteReason === null) return; // User cancelled
    if (deleteReason.trim() === "") {
        alert("You must provide a reason for deletion.");
        return;
    }
    
    $.ajax({
        url: `include/handlers/maintenance_handler.php?action=delete&id=${id}&reason=${encodeURIComponent(deleteReason)}`,
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
                                <strong>Last Modified By:</strong> ${item.last_modified_by} on ${formatDateTime(item.last_modified_at)}<br>
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

        function sortByTruckId() {
    const tableBody = document.querySelector("#maintenanceTable tbody");
    const rows = Array.from(tableBody.querySelectorAll("tr"));

    const sortedRows = rows.sort((a, b) => {
        const truckIdA = parseInt(a.children[0].textContent.trim());
        const truckIdB = parseInt(b.children[0].textContent.trim());

        return sortTruckIdAsc ? truckIdA - truckIdB : truckIdB - truckIdA;
    });

    sortTruckIdAsc = !sortTruckIdAsc;

    const icon = document.getElementById("truckIdSortIcon");
    icon.textContent = sortTruckIdAsc ? '⬆' : '⬇';

    tableBody.innerHTML = '';
    sortedRows.forEach(row => tableBody.appendChild(row));
}

        function restoreMaintenance(id) {
    if (!confirm("Are you sure you want to restore this maintenance record?")) {
        return;
    }
    
    $.ajax({
        url: `include/handlers/maintenance_handler.php?action=restore&id=${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                loadMaintenanceData();
                alert("Maintenance record restored successfully!");
            } else {
                alert("Error: " + (response.message || "Unknown error"));
            }
        },
        error: function(xhr, status, error) {
            console.error("Error restoring record: " + error);
            alert("Failed to restore maintenance record.");
        }
    });
}
    </script>
      <script>
    document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('expanded');
    });
</script>
</body>
</html>