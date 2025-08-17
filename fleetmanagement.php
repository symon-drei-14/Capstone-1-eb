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
    <link rel="stylesheet" href="include/css/sidenav.css">
    <link rel="stylesheet" href="include/css/loading.css">
    <link rel="stylesheet" href="include/css/fleetmanagement.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  
 
</head>
<body>
    <header class="header">
    <button id="toggleSidebarBtn" class="toggle-sidebar-btn">
        <i class="fa fa-bars"></i>
    </button>
    <div class="logo-container">
 
        <img src="include/img/mansar2.png" alt="Company Name" class="company">
    </div>

    <div class="datetime-container">
        <div id="current-date" class="date-display"></div>
        <div id="current-time" class="time-display"></div>
    </div>

    <div class="profile">
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
            <a href="include/handlers/logout.php" data-no-loading="true">Logout</a>
        </div>
    </div>


 <h3><i class="fa-solid fa-truck"></i>Truck Management</h3>
<div class="stats-container-wrapper">
    <div class="stats-container" id="statsContainer">
                    <div class="stat-card">
                         
                        <div class="stat-icon icon-terminal">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">8</div>
                            <div class="stat-label">In Terminal</div>
                        </div>
                        
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-enroute">
                            <i class="fas fa-truck-moving"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">5</div>
                            <div class="stat-label">Enroute</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-repair">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">2</div>
                            <div class="stat-label">In Repair</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-overdue">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">1</div>
                            <div class="stat-label">Overdue</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-total">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">16</div>
                            <div class="stat-label">Total Trucks</div>
                        </div>
                    </div>
    </div>
</div>
    <div class="main-content4">
            <div class="container">
                <div class="status-filter">
                <div class="button-row">
                    <button class="add_trip" onclick="openTruckModal()">Add a truck</button>
                </div>


    <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search trucks..." oninput="searchTrucks()">
    </div>
        <select id="statusFilter" onchange="filterTrucksByStatus()">
        <option value="" disabled selected>Status Filter</option>
        <option value="all">All Statuses</option>
        <option value="In Terminal">In Terminal</option>
        <option value="Enroute">Enroute</option>
        <option value="In Repair">In Repair</option>
        <option value="Overdue">Overdue</option>
        <option value="deleted">Deleted</option>
    </select>
</div>

<div class="table-controls">
    <div class="table-info" id="showingInfo"></div>
   
    <div class="rows-per-page-container">
        <label for="rowsPerPage">Rows per page:</label>
        <select id="rowsPerPage" onchange="changeRowsPerPage()">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>
</div>

                <div class="table-container">
                    <table id="trucksTable">
                        <thead>
                            <tr>
                                <th onclick="sortTrucks('truck_id')">ID <span id="sortIndicator">↑</span></th>
                                <th>Optimum pride</th>
                                <th>Plate No.</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Last Modified</th>
                                <th>Maintenance History</th>
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
  
    </div>

    <div id="historyModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="document.getElementById('historyModal').style.display='none'">&times;</span>
        <div id="historyModalContent"></div>
    </div>
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
        let rowsPerPage = 5;
        let isEditMode = false;
        let currentSortOrder = 'asc';
 let showDeleted = false;

  function updateDateTime() {
        const now = new Date();
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
        
        document.getElementById('current-time').textContent = now.toLocaleTimeString();
    }

    // Update immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

function toggleDeletedTrucks() {
     clearTimeout(searchTimeout);
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
    clearTimeout(searchTimeout); 
    currentStatusFilter = document.getElementById('statusFilter').value;
    document.getElementById('searchInput').value = '';
    currentTruckPage = 1; 
    fetchTrucks(); 
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
                     fetchTruckCounts();
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
    // Find the truck in our data
    const truck = trucksData.find(t => t.truck_id == truckId);
    
    if (truck) {
        // Check if truck is Enroute
        if (truck.display_status === 'Enroute' || truck.status === 'Enroute') {
            alert("Cannot delete a truck that is currently Enroute. Please change its status first.");
            return;
        }
        
        // If not Enroute, proceed with deletion
        document.getElementById('deleteTruckId').value = truckId;
        document.getElementById('deleteReason').value = '';
        openModal('deleteModal');
    }
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
            currentTruckPage = 1; // Reset to first page
            fetchTrucks().then(() => {
                updateShowingInfo(trucksData);
            });
            fetchTruckCounts();
        }
         else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

let searchTimeout;

function searchTrucks() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        
        if (!searchTerm) {
             currentStatusFilter = 'all';
            document.getElementById('statusFilter').value = 'all';
             fetchTrucks();
            return;
        }
    
    // Filter trucks based on search term
    const filteredTrucks = trucksData.filter(truck => {
        return (
            truck.truck_id.toString().includes(searchTerm) ||
            truck.plate_no.toLowerCase().includes(searchTerm) ||
            truck.capacity.toString().includes(searchTerm) ||
            (truck.display_status && truck.display_status.toLowerCase().includes(searchTerm)) ||
            (truck.status && truck.status.toLowerCase().includes(searchTerm)) ||
            (truck.last_modified_by && truck.last_modified_by.toLowerCase().includes(searchTerm)) ||
            (truck.delete_reason && truck.delete_reason.toLowerCase().includes(searchTerm))
        );
    });
    
    const originalTrucks = trucksData;
    trucksData = filteredTrucks;
    currentTruckPage = 1; 
    renderTrucksTable(searchTerm);
    trucksData = originalTrucks; a
}, 300);
}

function renderTrucksTable() {
    const start = (currentTruckPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    let filteredTrucks = [...trucksData];
    
    if (searchTerm) {
        filteredTrucks = filteredTrucks.filter(truck => {
            return (
                truck.truck_id.toString().includes(searchTerm) ||
                truck.plate_no.toLowerCase().includes(searchTerm) ||
                truck.capacity.toString().includes(searchTerm) ||
                (truck.display_status && truck.display_status.toLowerCase().includes(searchTerm)) ||
                (truck.status && truck.status.toLowerCase().includes(searchTerm)) ||
                (truck.last_modified_by && truck.last_modified_by.toLowerCase().includes(searchTerm)) ||
                (truck.delete_reason && truck.delete_reason.toLowerCase().includes(searchTerm))
            );
        });
    }
    
   // Replace the showDeleted checkbox logic with status filter logic
    if (currentStatusFilter !== 'all' && currentStatusFilter !== 'deleted') {
        filteredTrucks = filteredTrucks.filter(truck => 
            (truck.display_status === currentStatusFilter || 
             truck.status === currentStatusFilter) &&
            truck.is_deleted == 0
        );
    } else if (currentStatusFilter === 'deleted') {
        filteredTrucks = filteredTrucks.filter(truck => truck.is_deleted == 1);
    } else {
        // Show all non-deleted when 'all' is selected
        filteredTrucks = filteredTrucks.filter(truck => truck.is_deleted == 0);
    }

    const pageData = filteredTrucks.slice(start, Math.min(end, filteredTrucks.length));
    const tableBody = document.getElementById("trucksTableBody");
    tableBody.innerHTML = "";

     if (filteredTrucks.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="8" style="text-align: center;">No trucks found matching your search</td></tr>`;
        updatePagination(0);
        updateShowingInfo(filteredTrucks);
        return;
    }
     const highlightMatches = (text) => {
        if (!searchTerm || !text) return text;
        const str = text.toString();
        const regex = new RegExp(searchTerm, 'gi');
        return str.replace(regex, match => `<span class="highlight">${match}</span>`);
    };
    
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
       <td>${highlightMatches(truck.truck_id)}</td>
            <td>
                <div class="truck-image-container">
                    <img src="include/img/truck${truck.capacity == 20 ? '1' : '2'}.png" 
                         alt="Truck ${truck.plate_no}" 
                         class="truck-image"
                         title="Plate: ${truck.plate_no}\nCapacity: ${truck.capacity}">
                </div>
            </td>
            <td>${highlightMatches(truck.plate_no)}</td>
            <td>${highlightMatches(truck.capacity)}</td>
            <td><span class="status-${statusClass}">${highlightMatches(statusText)}</span></td>
            <td>${highlightMatches(truck.last_modified_by)}<br>${formatDateTime(truck.last_modified_at)}</td>
            <td>
                <button class="icon-btn history" data-tooltip="View History" onclick="viewMaintenanceHistory(${truck.truck_id})">
                    <i class="fas fa-history"></i>
                </button>
            </td>
            <td class="actions">
                ${truck.is_deleted == 1 ? `
                    <button class="icon-btn restore" data-tooltip="Restore" onclick="restoreTruck(${truck.truck_id})">
                      <i class="fas fa-trash-restore"></i>
                    </button>
                    ${window.userRole === 'Full Admin' ? 
                      `<button class="icon-btn full-delete" data-tooltip="Permanently Delete" onclick="fullDeleteTruck(${truck.truck_id})">
                              <i class="fa-solid fa-ban"></i>
                      </button>` : ''}
                    <button class="icon-btn view-reason" data-tooltip="View Reason" onclick="viewDeletionReason(${truck.truck_id})">
                        <i class="fas fa-info-circle"></i>
                    </button>
                ` : `
                    <button class="icon-btn edit" data-tooltip="Edit" onclick="openTruckModal(true, ${truck.truck_id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="icon-btn delete" data-tooltip="Delete" onclick="deleteTruck(${truck.truck_id})">
                        <i class='fas fa-trash-alt'></i>
                    </button>
                `}
            </td>
        `;
        tableBody.appendChild(tr);
    });
    updatePagination(filteredTrucks.length);
    updateShowingInfo(filteredTrucks);
    document.getElementById("truck-page-info").textContent = 
        `Page ${currentTruckPage} of ${Math.ceil(filteredTrucks.length / rowsPerPage)}`;
    
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
                  fetchTruckCounts();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}


function viewMaintenanceHistory(truckId) {
    fetch(`include/handlers/maintenance_handler.php?action=getHistory&truckId=${truckId}`)
        .then(response => response.json())
        .then(data => {
            if (data.history && data.history.length > 0) {
                let historyHTML = '<div class="history-modal-content"><h3>Maintenance History</h3><ul>';
                
                data.history.forEach(item => {
                    if (item.status === 'Completed') {
                        historyHTML += `
                            <li>
                                <strong>Date Completed:</strong> ${formatDateTime(item.last_modified_at)}<br>
                                <strong>Remarks:</strong> ${item.remarks}<br>
                                <hr>
                            </li>
                        `;
                    }
                });
                
                historyHTML += '</ul><button onclick="document.getElementById(\'historyModal\').style.display=\'none\'">Close</button></div>';
                
                document.getElementById('historyModalContent').innerHTML = historyHTML;
                document.getElementById('historyModal').style.display = 'block';
            } else {
                document.getElementById('historyModalContent').innerHTML = 
                    '<div class="history-modal-content"><p>No completed maintenance history found for this truck.</p></div>';
                document.getElementById('historyModal').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading maintenance history:', error);
            document.getElementById('historyModalContent').innerHTML = 
                '<div class="history-modal-content"><p>Error loading maintenance history.</p></div>';
            document.getElementById('historyModal').style.display = 'block';
        });
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


function changeTruckPage(direction) {
    
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
   
    let filteredTrucks = [...trucksData];
  
    if (searchTerm) {
        filteredTrucks = filteredTrucks.filter(truck => {
            return (
                truck.truck_id.toString().includes(searchTerm) ||
                truck.plate_no.toLowerCase().includes(searchTerm) ||
                truck.capacity.toString().includes(searchTerm) ||
                (truck.display_status && truck.display_status.toLowerCase().includes(searchTerm)) ||
                (truck.status && truck.status.toLowerCase().includes(searchTerm)) ||
                (truck.last_modified_by && truck.last_modified_by.toLowerCase().includes(searchTerm)) ||
                (truck.delete_reason && truck.delete_reason.toLowerCase().includes(searchTerm))
                
            );
            const totalPages = Math.ceil(filteredTrucks.length / rowsPerPage);
    currentTruckPage += direction;
        });
    }
    
    if (currentStatusFilter !== 'all') {
        if (currentStatusFilter === 'deleted') {
            filteredTrucks = filteredTrucks.filter(truck => truck.is_deleted == 1);
        } else {
            filteredTrucks = filteredTrucks.filter(truck => 
                (truck.display_status === currentStatusFilter || 
                 truck.status === currentStatusFilter) &&
                truck.is_deleted == 0
            );
        }
    } else {
       
        if (!showDeleted) {
            filteredTrucks = filteredTrucks.filter(truck => truck.is_deleted == 0);
        }
    }
    
    // Calculate pagination
    const totalPages = Math.ceil(filteredTrucks.length / rowsPerPage);
    currentTruckPage += direction;
    
    // Ensure we stay within bounds
    if (currentTruckPage < 1) currentTruckPage = 1;
    if (currentTruckPage > totalPages) currentTruckPage = totalPages;
    
    renderTrucksTable();
}

        function formatDateTime(datetimeString) {
            if (!datetimeString) return 'N/A';
            const date = new Date(datetimeString);
            return date.toLocaleString(); // This will format based on user's locale
        }

  function fetchTruckCounts() {
    fetch('include/handlers/truck_handler.php?action=getTruckCounts')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsCards(data.counts);
            }
        })
        .catch(error => console.error('Error fetching truck counts:', error));
}

function updateStatsCards(counts) {
    document.querySelector('.stat-card:nth-child(1) .stat-value').textContent = counts['In Terminal'] || '0';
    document.querySelector('.stat-card:nth-child(2) .stat-value').textContent = counts['Enroute'] || '0';
    document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = counts['In Repair'] || '0';
    document.querySelector('.stat-card:nth-child(4) .stat-value').textContent = counts['Overdue'] || '0';
    document.querySelector('.stat-card:nth-child(5) .stat-value').textContent = counts['Total'] || '0';
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
            fetchTruckCounts();
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
                fetchTruckCounts();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Get current page filename
    const currentPage = window.location.pathname.split('/').pop();
    
    // Find all sidebar links
    const sidebarLinks = document.querySelectorAll('.sidebar-item a');
    
    // Check each link
    sidebarLinks.forEach(link => {
        const linkPage = link.getAttribute('href').split('/').pop();
        
        // If this link matches current page, add active class
        if (linkPage === currentPage) {
            link.parentElement.classList.add('active');
            
            // Also highlight the icon
            const icon = link.parentElement.querySelector('.icon2');
            if (icon) {
                icon.style.color = 'white';
            }
        }
    });
});

function changeRowsPerPage() {
    rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
    currentTruckPage = 1;
    renderTrucksTable();
}

function updatePagination(totalItems) {
    const totalPages = Math.ceil(totalItems / rowsPerPage);
    const paginationContainers = document.querySelectorAll('.pagination1, .pagination2');
    
    paginationContainers.forEach(paginationContainer => {
        paginationContainer.innerHTML = '';
        
        const prevButton = document.createElement('button');
        prevButton.innerHTML = '&laquo;';
        prevButton.classList.add('nav-btn');
        prevButton.onclick = () => changeTruckPage(-1);
        prevButton.disabled = currentTruckPage === 1;
        paginationContainer.appendChild(prevButton);

        const maxVisiblePages = 5; 
        let startPage = Math.max(1, currentTruckPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        if (startPage > 1) {
            const firstPageButton = document.createElement('button');
            firstPageButton.textContent = '1';
            firstPageButton.onclick = () => {
                currentTruckPage = 1;
                renderTrucksTable();
            };
            if (currentTruckPage === 1) {
                firstPageButton.classList.add('active');
            }
            paginationContainer.appendChild(firstPageButton);
            
            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.classList.add('ellipsis');
                paginationContainer.appendChild(ellipsis);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            if (i === currentTruckPage) {
                pageButton.classList.add('active');
            }
            pageButton.onclick = () => {
                currentTruckPage = i;
                renderTrucksTable();
            };
            paginationContainer.appendChild(pageButton);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.classList.add('ellipsis');
                paginationContainer.appendChild(ellipsis);
            }
            
            const lastPageButton = document.createElement('button');
            lastPageButton.textContent = totalPages;
            lastPageButton.onclick = () => {
                currentTruckPage = totalPages;
                renderTrucksTable();
            };
            if (currentTruckPage === totalPages) {
                lastPageButton.classList.add('active');
            }
            paginationContainer.appendChild(lastPageButton);
        }

        const nextButton = document.createElement('button');
        nextButton.innerHTML = '&raquo;';
        nextButton.classList.add('nav-btn');
        nextButton.onclick = () => changeTruckPage(1);
        nextButton.disabled = currentTruckPage === totalPages;
        paginationContainer.appendChild(nextButton);
    });
}

function updateShowingInfo(filteredTrucks) {
    if (!filteredTrucks || filteredTrucks.length === 0) {
        document.getElementById('showingInfo').textContent = 'Showing 0 to 0 of 0 entries';
        return;
    }
    
    const start = (currentTruckPage - 1) * rowsPerPage + 1;
    const end = Math.min(currentTruckPage * rowsPerPage, filteredTrucks.length);
    const total = filteredTrucks.length;
    
    document.getElementById('showingInfo').textContent = 
        `Showing ${start} to ${end} of ${total} entries`;
}
    </script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="include/js/logout-confirm.js"></script>
<div id="admin-loading" class="admin-loading">
  <div class="admin-loading-container">
    <div class="loading-gif-container">
      <img src="include/img/loading.gif" alt="Loading..." class="loading-gif">
    </div>
    <div class="admin-loading-content">
      <h4 class="loading-title">Loading Page</h4>
      <p class="loading-message">Redirecting to another page...</p>
      <div class="loading-progress">
        <div class="progress-bar"></div>
        <span class="progress-text">0%</span>
      </div>
    </div>
  </div>
</div>

<script>

    
  const AdminLoading = {
  init() {
    this.loadingEl = document.getElementById('admin-loading');
    this.titleEl = document.querySelector('.loading-title');
    this.messageEl = document.querySelector('.loading-message');
    this.progressBar = document.querySelector('.progress-bar');
    this.progressText = document.querySelector('.progress-text');
    
    this.setupNavigationInterception();
  },
  
  show(title = 'Processing Request', message = 'Please wait while we complete this action...') {
    this.titleEl.textContent = title;
    this.messageEl.textContent = message;
    
    // Start the sequence with longer delays
    this.loadingEl.style.display = 'flex';
    setTimeout(() => {
      this.loadingEl.classList.add('active');
    }, 50);
  },
  
  hide() {
    // Longer fade out
    this.loadingEl.classList.remove('active');
    setTimeout(() => {
      this.loadingEl.style.display = 'none';
    }, 800); 
  },
  
  updateProgress(percent) {
    this.progressBar.style.width = `${percent}%`;
    this.progressText.textContent = `${percent}%`;
  },
  
   setupNavigationInterception() {
  document.addEventListener('click', (e) => {
    // Skip if click is inside SweetAlert modal
    if (e.target.closest('.swal2-container, .swal2-popup, .swal2-modal')) {
      return;
    }
    
    // Skip if click is on any modal element
    if (e.target.closest('.modal, .modal-content')) {
      return;
    }
    
    const link = e.target.closest('a');
    if (link && !link.hasAttribute('data-no-loading') && 
        link.href && !link.href.startsWith('javascript:') &&
        !link.href.startsWith('#')) {
      e.preventDefault();
      
      const loading = this.startAction(
        'Loading Page', 
        `Preparing ${link.textContent.trim()}...`
      );
      
      let progress = 0;
      const progressInterval = setInterval(() => {
        progress += Math.random() * 40; 
        if (progress >= 90) clearInterval(progressInterval);
        loading.updateProgress(Math.min(progress, 100));
      }, 300); 
      
      const minLoadTime = 2000;
      const startTime = Date.now();
      
      setTimeout(() => {
        window.location.href = link.href;
      }, minLoadTime);
    }
  });

  document.addEventListener('submit', (e) => {
    // Skip if form is inside SweetAlert or modal
    if (e.target.closest('.swal2-container, .swal2-popup, .modal')) {
      return;
    }
    
    const loading = this.startAction(
      'Submitting Form', 
      'Processing your data...'
    );
    
    setTimeout(() => {
      loading.complete();
    }, 1500);
  });
}
  
  startAction(actionName, message) {
    this.show(actionName, message);
    return {
      updateProgress: (percent) => this.updateProgress(percent),
      updateMessage: (message) => {
        this.messageEl.textContent = message;
        this.messageEl.style.opacity = 0;
        setTimeout(() => {
          this.messageEl.style.opacity = 1;
          this.messageEl.style.transition = 'opacity 0.5s ease';
        }, 50);
      },
      complete: () => {

        this.updateProgress(100);
        this.updateMessage('Done!');
        setTimeout(() => this.hide(), 800);
      }
    };
  }
};

document.addEventListener('DOMContentLoaded', () => {
  AdminLoading.init();
  
  // Add smooth transition to the GIF
  const loadingGif = document.querySelector('.loading-gif');
  if (loadingGif) {
    loadingGif.style.transition = 'opacity 0.7s ease 0.3s';
  }
});
</script>
<footer class="site-footer">

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
    </div>
</footer>
</body>
</html>