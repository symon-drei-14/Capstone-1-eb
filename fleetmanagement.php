<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Management</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/fleetmanagement.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body{
            font-family: Arial, sans-serif;
        }
        h3{
            font-family: Arial, sans-serif;
            margin-top:-10px;
            margin-bottom:40px;
            font-size:1.5rem;
            text-transform:uppercase;
        }
        .toggle-sidebar-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            margin-left: 1rem;
            color: #333;
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

        .sidebar {
            position: fixed;
            top: 1.7rem;
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

        .main-content4 {
            margin-top: 80px;
            margin-left: 10px;
            margin-right: 10px;
            width: calc(100% - 110px);
            width: 96vw;
            height: 105vh;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        .status-enroute {
            background-color: #007bff; /* Blue */
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .status-pending {
            background-color: #ffc107; /* Yellow */
            color: black;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .status-in-repair {
            background-color: #ef9e2eff; /* Red */
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .status-in-terminal {
            background-color: #28a745; /* Green */
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .status-en-route {
    background-color: #007bff; /* Blue */
    color: white;
     padding: 3px 8px;
            border-radius: 4px;
}
        
        .status-overdue {
            background-color: #c60909ff; /* Gray */
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            box-sizing: border-box;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1200;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 5px;
        }
        .status-filter {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-filter label {
    font-weight: bold;
}

.status-filter select {
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background-color: white;
    cursor: pointer;
}

th[onclick] {
    cursor: pointer;
    user-select: none;
}

th[onclick]:hover {
    background-color: #f5f5f5;
}

#sortIndicator {
    margin-left: 5px;
    font-size: 1.2em;
}

.status-deleted {
    background-color: #6c757d; /* Gray */
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
}

.show-deleted-filter {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.show-deleted-filter label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.show-deleted-filter input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.restore {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
}

.restore:hover {
    background-color: #218838;
}

.view-reason {
    background-color: #17a2b8;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.view-reason:hover {
    background-color: #138496;
}

.full-delete {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
}

.full-delete:hover {
    background-color: #c82333;
}

    </style>
</head>
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
                <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
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

    <div class="main-content4">
        <section class="content-2">
            <div class="container">
                <div class="button-row">
                    <button class="add_trip" onclick="openTruckModal()">Add a truck</button>
                </div>
                <br>
                <h3>List of Trucks</h3>
                <div class="status-filter">
    <label for="statusFilter">Filter by Status:</label>
    <select id="statusFilter" onchange="filterTrucksByStatus()">
        <option value="all">All Statuses</option>
        <option value="In Terminal">In Terminal</option>
        <option value="Enroute">Enroute</option>
        <option value="In Repair">In Repair</option>
        <option value="Overdue">Overdue</option>

    </select>
</div>
<div class="show-deleted-filter">
    <label>
        <input type="checkbox" id="showDeleted" onchange="toggleDeletedTrucks()">
        Show Deleted Trucks
    </label>
</div>
                <div class="table-container">
                    <table id="trucksTable">
                        <thead>
                            <tr>
                                <th onclick="sortTrucks('truck_id')">ID <span id="sortIndicator">↑</span></th>
                                <th>Plate Number</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Last Modified</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="trucksTableBody"></tbody>
                    </table>
                </div>
                <div class="pagination2">
                    <button class="prev" onclick="changeTruckPage(-1)">◄</button>
                    <span id="truck-page-info">Page 1</span>
                    <button class="next" onclick="changeTruckPage(1)">►</button>
                </div>
            </div>
        </section>
    </div>

    <div id="truckModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('truckModal')">&times;</span>
            <h2 id="modalTitle">Add Truck</h2>
            <input type="hidden" id="truckIdHidden">
            <div class="form-group">
                <label for="plateNo">Plate Number (Format: ABC123 or ABC-1234)</label>
                <input type="text" id="plateNo" name="plateNo" 
                       pattern="[A-Za-z]{2,3}-?\d{3,4}"
                       title="2-3 letters followed by 3-4 numbers"
                       class="form-control" required>
            </div>
            <div class="form-group">
                <label for="capacity">Capacity</label>
                <select id="capacity" name="capacity" class="form-control" required>
                    <option value="20">20</option>
                    <option value="40">40</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="In Terminal">In Terminal</option>
                    <option value="Enroute">Enroute</option>
                    <option value="In Repair">In Repair</option>
                    <option value="Overdue">Overdue</option>
                    
                </select>
            </div>
            <div class="button-group">
                <button type="button" class="save-btn" onclick="validateAndSaveTruck()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('truckModal')">Cancel</button>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal">
    <div class="modal-content" style="width: 40%;">
        <span class="close" onclick="closeModal('deleteModal')">&times;</span>
        <h2>Delete Truck</h2>
        <input type="hidden" id="deleteTruckId">
        <div class="form-group">
            <label for="deleteReason">Reason for deletion:</label>
            <textarea id="deleteReason" name="deleteReason" class="form-control" rows="4" required></textarea>
        </div>
        <div class="button-group">
            <button type="button" class="save-btn" onclick="performSoftDelete()">Confirm Delete</button>
            <button type="button" class="cancel-btn" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
    </div>
</div>

<div id="reasonModal" class="modal">
    <div class="modal-content" style="width: 40%;">
        <span class="close" onclick="closeModal('reasonModal')">&times;</span>
        <h2>Deletion Reason</h2>
        <div class="form-group">
            <p id="deletionReasonText" style="padding: 15px; background-color: #f5f5f5; border-radius: 5px;"></p>
        </div>
        <div class="button-group">
            <button type="button" class="cancel-btn" onclick="closeModal('reasonModal')">Close</button>
        </div>
    </div>
</div>

    <script>
        let currentStatusFilter = 'all';
        let trucksData = [];
        let currentTruckPage = 1;
        const rowsPerPage = 4;
        let isEditMode = false;
        let currentSortOrder = 'asc';
 let showDeleted = false;

function toggleDeletedTrucks() {
    showDeleted = document.getElementById('showDeleted').checked;
    // Reset to first page when toggling
    currentTruckPage = 1;
    renderTrucksTable();
}

function viewDeletionReason(truckId) {
    // Find the truck in our data
    const truck = trucksData.find(t => t.truck_id == truckId);
    if (truck) {
        document.getElementById('deletionReasonText').textContent = 
            truck.delete_reason || 'No reason provided';
        openModal('reasonModal');
    }
}


     function filterTrucksByStatus() {
    currentStatusFilter = document.getElementById('statusFilter').value;
    currentTruckPage = 1; // Reset to first page when filtering
    fetchTrucks(); // This will fetch fresh data with the new filter
}

function fetchTrucks() {
    const statusFilter = document.getElementById('statusFilter').value;
    fetch(`include/handlers/truck_handler.php?action=getTrucks&status=${statusFilter}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                trucksData = data.trucks;
                renderTrucksTable();
               
            } else {
                alert('Error fetching trucks: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
}
       
        
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
            resetForm();
        }

        function openTruckModal(editMode = false, truckId = null) {
    isEditMode = editMode;
    if (editMode) {
        document.getElementById('modalTitle').textContent = 'Edit Truck';
        const truck = trucksData.find(t => t.truck_id == truckId);
        if (truck) {
            document.getElementById('truckIdHidden').value = truck.truck_id;
            document.getElementById('plateNo').value = truck.plate_no;
            document.getElementById('capacity').value = truck.capacity;
            document.getElementById('status').value = truck.status || truck.display_status;
        }
    } else {
        document.getElementById('modalTitle').textContent = 'Add Truck';
    }
    openModal('truckModal');
}
        function resetForm() {
            document.getElementById('truckIdHidden').value = '';
            document.getElementById('plateNo').value = '';
            document.getElementById('capacity').value = '20';
            document.getElementById('status').value = 'In Terminal';
            isEditMode = false;
        }

        function validatePlateNumber(plateNo) {
            const plateRegex = /^[A-Za-z]{2,3}-?\d{3,4}$/;
            if (!plateRegex.test(plateNo)) {
                alert("Invalid plate number format. Please use format like ABC123 or ABC-1234");
                return false;
            }
            return true;
        }

        function validateAndSaveTruck() {
            const plateNo = document.getElementById('plateNo').value;
            if (!validatePlateNumber(plateNo)) return;
            saveTruck();
        }

        function saveTruck() {
            const truckData = {
                truck_id: document.getElementById('truckIdHidden').value,
                plate_no: document.getElementById('plateNo').value,
                capacity: document.getElementById('capacity').value,
                status: document.getElementById('status').value,
                action: isEditMode ? 'updateTruck' : 'addTruck'
            };

            fetch('include/handlers/truck_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(truckData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(isEditMode ? 'Truck updated successfully!' : 'Truck added successfully!');
                    closeModal('truckModal');
                    fetchTrucks();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please check console for details.');
            });
        }

       function deleteTruck(truckId) {
    document.getElementById('deleteTruckId').value = truckId;
    document.getElementById('deleteReason').value = '';
    openModal('deleteModal');
}
     

function performSoftDelete() {
    const truckId = document.getElementById('deleteTruckId').value;
    const deleteReason = document.getElementById('deleteReason').value;
    
    if (!deleteReason) {
        alert("Please provide a reason for deletion");
        return;
    }

    fetch('include/handlers/truck_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'softDeleteTruck', 
            truck_id: truckId,
            delete_reason: deleteReason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Truck has been deleted successfully!');
            closeModal('deleteModal');
            fetchTrucks();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function renderTrucksTable() {
    const start = (currentTruckPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    
    // Filter trucks based on the showDeleted flag
    let filteredTrucks = trucksData.filter(truck => {
        return showDeleted ? truck.is_deleted == 1 : truck.is_deleted == 0;
    });

    // Apply status filter only when not showing deleted trucks
    if (!showDeleted && currentStatusFilter !== 'all') {
        filteredTrucks = filteredTrucks.filter(truck => 
            truck.display_status === currentStatusFilter || 
            truck.status === currentStatusFilter
        );
    }

    const pageData = filteredTrucks.slice(start, Math.min(end, filteredTrucks.length));
    
    const tableBody = document.getElementById("trucksTableBody");
    tableBody.innerHTML = "";
    
    pageData.forEach(truck => {
        const tr = document.createElement("tr");
        
        let statusClass, statusText;
        if (truck.is_deleted == 1) {
            statusClass = 'deleted';
            statusText = 'Deleted';
        } else {
            statusClass = truck.display_status.toLowerCase().replace(/\s+/g, "-");
            statusText = truck.display_status;
        }
        
        tr.innerHTML = `
        <td>${truck.truck_id}</td>
        <td>${truck.plate_no}</td>
        <td>${truck.capacity}</td>
        <td><span class="status-${statusClass}">${statusText}</span></td>
        <td>${truck.last_modified_by}<br>${formatDateTime(truck.last_modified_at)}</td>
        <td class="actions">
            ${truck.is_deleted == 1 ? `
                <button class="restore" onclick="restoreTruck(${truck.truck_id})">Restore</button>
                ${window.userRole === 'Full Admin' ? 
                  `<button class="full-delete" onclick="fullDeleteTruck(${truck.truck_id})">Full Delete</button>` : ''}
                <button class="view-reason" onclick="viewDeletionReason(${truck.truck_id})">View Reason</button>
            ` : `
                <button class="edit" onclick="openTruckModal(true, ${truck.truck_id})">Edit</button>
                <button class="delete" onclick="deleteTruck(${truck.truck_id})">Delete</button>
            `}
        </td>
    `;
        tableBody.appendChild(tr);
    });
    
    document.getElementById("truck-page-info").textContent = `Page ${currentTruckPage} of ${Math.ceil(filteredTrucks.length / rowsPerPage)}`;
}

// Add this new function for full delete
function fullDeleteTruck(truckId) {
    if (confirm("Are you sure you want to PERMANENTLY delete this truck? This cannot be undone!")) {
        fetch('include/handlers/truck_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'fullDeleteTruck', 
                truck_id: truckId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Truck has been permanently deleted!');
                fetchTrucks();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function restoreTruck(truckId) {
    if (confirm("Are you sure you want to restore this truck?")) {
        fetch('include/handlers/truck_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'restoreTruck', 
                truck_id: truckId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Truck has been restored successfully!');
                fetchTrucks();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}


function sortTrucks(sortBy) {
    // Toggle sort order if clicking the same column
    if (sortBy === 'truck_id') {
        currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
    }
    
    trucksData.sort((a, b) => {
        if (sortBy === 'truck_id') {
            return currentSortOrder === 'asc' 
                ? a.truck_id - b.truck_id 
                : b.truck_id - a.truck_id;
        }
        return 0;
    });
    
    // Update sort indicator
    document.getElementById('sortIndicator').textContent = 
        currentSortOrder === 'asc' ? '↑' : '↓';
    
    // Reset to first page and render
    currentTruckPage = 1;
    renderTrucksTable();
}

// Update changeTruckPage function to work with filtered data
function changeTruckPage(direction) {
    let filteredTrucks = trucksData;
    if (currentStatusFilter !== 'all') {
        if (currentStatusFilter === 'deleted') {
            filteredTrucks = trucksData.filter(truck => truck.is_deleted == 1);
        } else {
            filteredTrucks = trucksData.filter(truck => 
                (truck.display_status === currentStatusFilter || 
                 truck.status === currentStatusFilter) &&
                truck.is_deleted == 0
            );
        }
    } else {
        filteredTrucks = trucksData.filter(truck => truck.is_deleted == 0);
    }
    
    const totalPages = Math.ceil(filteredTrucks.length / rowsPerPage);
    currentTruckPage += direction;
    if (currentTruckPage < 1) currentTruckPage = 1;
    if (currentTruckPage > totalPages) currentTruckPage = totalPages;
    renderTrucksTable();
}

        function formatDateTime(datetimeString) {
            if (!datetimeString) return 'N/A';
            const date = new Date(datetimeString);
            return date.toLocaleString(); // This will format based on user's locale
        }

  

        function fetchTrucks() {
            fetch('include/handlers/truck_handler.php?action=getTrucks')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        trucksData = data.trucks;
                        renderTrucksTable();
                        
                       
                    } else {
                        alert('Error fetching trucks: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            fetchTrucks();
            document.getElementById('toggleSidebarBtn').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('expanded');
            });
        });


        function restoreTruck(truckId) {
    if (confirm("Are you sure you want to restore this truck?")) {
        fetch('include/handlers/truck_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'restoreTruck', 
                truck_id: truckId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Truck has been restored successfully!');
                fetchTrucks();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
    </script>
</body>
</html>