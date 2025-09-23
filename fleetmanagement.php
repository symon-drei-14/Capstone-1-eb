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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
 
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
            <a href="informationmanagement.php">Information Management</a>
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
                    <button class="add_trip" onclick="openTruckModal()"> <i class="fa-solid fa-plus"></i> Add truck</button>
                      <!-- <button class="print_btn" type="button"><i class="fas fa-print"></i> Print</button> -->
                </div>


    <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search trucks table..." oninput="searchTrucks()">
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
                                <th>Truck Photo</th>
                                <th>Plate No.</th>
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
  
    </div>

    <div id="historyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Maintenance History</h3>
            <span class="close">&times;</span>
        </div>
        <div id="historyModalContent">
        
        </div>
        <div class="modal-footer">
            <button>Close</button>
        </div>
    </div>
</div>

    <div id="truckModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('truckModal')">&times;</span>
            <h2 id="modalTitle">Add Truck</h2>
            <input type="hidden" id="truckIdHidden">

            <div id="truckPhotoPreview" class="truck-photo-preview"></div>

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

        <div class="form-group">
            <label for="truckPhoto">Truck Photo (Max 2MB)</label>
            <input type="file" id="truckPhoto" class="custom-file-btn" name="truckPhoto" accept="image/*" onchange="previewTruckPhoto(this)">
            <small>Supported formats: JPG, PNG, GIF</small>
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
    
    // Reset photo preview and file input
    resetTruckPhotoPreview();
    
    if (editMode) {
        document.getElementById('modalTitle').textContent = 'Edit Truck';
        const truck = trucksData.find(t => t.truck_id == truckId);
        if (truck) {
            document.getElementById('truckIdHidden').value = truck.truck_id;
            document.getElementById('plateNo').value = truck.plate_no;
            document.getElementById('capacity').value = truck.capacity;
            document.getElementById('status').value = truck.status || truck.display_status;
            
            // Display existing truck photo if available
            const preview = document.getElementById('truckPhotoPreview');
            if (truck.truck_pic) {
                const img = document.createElement('img');
                img.src = 'data:image/jpeg;base64,' + truck.truck_pic;
                img.className = 'truck-preview-image';
                preview.appendChild(img);
                
                // Add a note that this is the current photo
                const note = document.createElement('p');
                note.textContent = 'Current photo';
                note.style.fontSize = '12px';
                note.style.marginTop = '5px';
                note.style.color = '#666';
                preview.appendChild(note);
            }
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
    resetTruckPhotoPreview();
    isEditMode = false;
}

        function validatePlateNumber(plateNo) {
            const plateRegex = /^[A-Za-z]{2,3}-?\d{3,4}$/;
            if (!plateRegex.test(plateNo)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Plate Number',
                    text: 'Please use format like ABC123 or ABC-1234'
                });
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
    const formData = new FormData();
    const truckId = document.getElementById('truckIdHidden').value;
    const plateNo = document.getElementById('plateNo').value;
    const capacity = document.getElementById('capacity').value;
    const status = document.getElementById('status').value;
    const photoFile = document.getElementById('truckPhoto').files[0];
    
    // Add basic truck data
    formData.append('truck_id', truckId);
    formData.append('plate_no', plateNo);
    formData.append('capacity', capacity);
    formData.append('status', status);
    formData.append('action', isEditMode ? 'updateTruck' : 'addTruck');
    
    // Add photo if selected
    if (photoFile) {
        formData.append('truck_photo', photoFile);
    }
    
    fetch('include/handlers/truck_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: isEditMode ? 'Truck Updated' : 'Truck Added',
                text: isEditMode ? 'Truck updated successfully!' : 'Truck added successfully!',
                timer: 2000,
                showConfirmButton: false
            });
            closeModal('truckModal');
            fetchTrucks();
            fetchTruckCounts();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
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
           Swal.fire({
            icon: 'warning',
            title: 'Cannot Delete Truck',
            text: 'Cannot delete a truck that is currently Enroute. Please change its status first.'
        });
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
        Swal.fire({
        icon: 'error',
        title: 'Reason Required',
        text: 'Please provide a reason for deletion'
    });
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
              Swal.fire({
            icon: 'error',
            title: 'Soft Delete',
            text: 'Truck has been deleted successfully!'
        });
            
            closeModal('deleteModal');
            currentTruckPage = 1; 
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
            <td data-label="ID">${highlightMatches(truck.truck_id)}</td>
            <td data-label="Truck Photo">
                <div class='truck-image-container'>
                    ${truck.truck_pic ? 
                        '<img src="data:image/jpeg;base64,' + truck.truck_pic + '" alt="Truck ' + truck.plate_no + '" class="truck-image" title="Plate: ' + truck.plate_no + '\nCapacity: ' + truck.capacity + '">' :
                        '<img src="include/img/truck' + (truck.capacity == 20 ? '1' : '2') + '.png" alt="Truck ' + truck.plate_no + '" class="truck-image" title="Plate: ' + truck.plate_no + '\nCapacity: ' + truck.capacity + '">'
                    }
                </div>
            </td>
            <td data-label="Plate No.">${highlightMatches(truck.plate_no)}</td>
            <td data-label="Capacity">${highlightMatches(truck.capacity)}</td>
            <td data-label="Status"><span class="status-${statusClass}">${highlightMatches(statusText)}</span></td>
            <td data-label="Last Modified"> <strong><span class="modifby">${highlightMatches(truck.last_modified_by)}</span></strong><br>${formatDateTime(truck.last_modified_at)}</td>
            <td data-label="Actions" class="actions">
                <div class="dropdown">
                    <button class="dropdown-btn" data-tooltip="Actions">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-content">
                        ${truck.is_deleted == 1 ? `
                            <button class="dropdown-item restore" data-tooltip="Restore" onclick="restoreTruck(${truck.truck_id})">
                                <i class="fas fa-trash-restore"></i> Restore
                            </button>
                           
                            <button class="dropdown-item view-reason" data-tooltip="View Reason" onclick="viewDeletionReason(${truck.truck_id})">
                                <i class="fas fa-info-circle"></i> View Reason
                            </button>
                             ${window.userRole === 'Full Admin' ? 
                                `<button class="dropdown-item full-delete" data-tooltip="Permanently Delete" onclick="fullDeleteTruck(${truck.truck_id})">
                                    <i class="fa-solid fa-ban"></i> Permanent Delete
                                </button>` : ''}
                        ` : `
                            <button class="dropdown-item edit" data-tooltip="Edit" onclick="openTruckModal(true, ${truck.truck_id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="dropdown-item history" data-tooltip="View History" onclick="viewMaintenanceHistory(${truck.truck_id})">
                                <i class="fas fa-history"></i> Maintenance History
                            </button>
                            <button class="dropdown-item delete" data-tooltip="Delete" onclick="deleteTruck(${truck.truck_id})">
                                <i class='fas fa-trash-alt'></i> Delete
                            </button>
                        `}
                    </div>
                </div>
            </td>
            `;
        tableBody.appendChild(tr);
    });
    
    updatePagination(filteredTrucks.length);
    updateShowingInfo(filteredTrucks);
    
    const pageInfoElement = document.getElementById("truck-page-info");
    if (pageInfoElement) {
        const totalPages = Math.ceil(filteredTrucks.length / rowsPerPage);
        pageInfoElement.textContent = `Page ${currentTruckPage} of ${totalPages}`;
    }
}
   document.addEventListener('DOMContentLoaded', function() {
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    closeAllDropdowns();
                }
            });
            
            // Toggle dropdowns when clicking the button
            document.addEventListener('click', function(e) {
                if (e.target.closest('.dropdown-btn')) {
                    const dropdown = e.target.closest('.dropdown');
                    const dropdownContent = dropdown.querySelector('.dropdown-content');
                    
                    // Close all other dropdowns
                    closeAllDropdownsExcept(dropdownContent);
                    
                    // Toggle this dropdown
                    dropdownContent.classList.toggle('show');
                    e.stopPropagation();
                }
            });
            
            // Handle dropdown item clicks
            document.addEventListener('click', function(e) {
                if (e.target.closest('.dropdown-item')) {
                    // Close the dropdown
                    const dropdownContent = e.target.closest('.dropdown-content');
                    if (dropdownContent) {
                        dropdownContent.classList.remove('show');
                    }
                }
            });
        });
        
        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
        
        function closeAllDropdownsExcept(exceptDropdown) {
            document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                if (dropdown !== exceptDropdown) {
                    dropdown.classList.remove('show');
                }
            });
        }

// Add this new function for full delete
function fullDeleteTruck(truckId) {
    Swal.fire({
        title: 'Permanent Deletion',
        html: '<strong>Are you sure you want to PERMANENTLY delete this truck?</strong><br><br>This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete permanently!',
        cancelButtonText: 'Cancel',
        backdrop: 'rgba(0,0,0,0.8)',
        allowOutsideClick: false,
        customClass: {
            container: 'swal2-top-container'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Deleting Truck',
                html: 'Permanently removing truck from system...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

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
                    Swal.fire({
                        icon: 'success',
                        title: 'Permanently Deleted',
                        text: 'Truck has been permanently removed from the system',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        fetchTrucks();
                        fetchTruckCounts();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Deletion Failed',
                        text: data.message || 'Could not delete truck',
                        footer: 'Please try again or contact support'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to connect to server',
                    footer: 'Check your internet connection'
                });
            });
        }
    });
}

function viewMaintenanceHistory(truckId) {
    
    fetch(`include/handlers/truck_handler.php?action=getHistory&truckId=${truckId}`)
        .then(response => response.json())
        .then(data => {
            console.log('API Response:', data);

            let historyRecords = [];
            // Check if the history data is in the expected 'history' property
            if (data.success && data.history) {
                historyRecords = data.history;
            } else if (Array.isArray(data)) {
                // Fallback for older API response format, just in case
                historyRecords = data;
            }
            
            if (historyRecords.length > 0) {
                let historyHTML = '<ul class="history-list">';
                
                historyRecords.forEach(item => {
                    // Determine status class based on status text
                    let statusClass = 'status-pending';
                    if (item.status.toLowerCase().includes('complete')) statusClass = 'status-completed';
                    if (item.status.toLowerCase().includes('progress')) statusClass = 'status-in-progress';
                    if (item.status.toLowerCase().includes('pending')) statusClass = 'status-pending';
                    if (item.status.toLowerCase().includes('overdue')) statusClass = 'status-overdue';
                    
                    // Use the correct field names from your SQL query
                    const maintenanceDate = item.date_mtnce || item.last_modified_at;
                    const maintenanceType = item.maintenance_type_name || 'N/A';
                    const supplierName = item.supplier_name || 'N/A';
                    const cost = item.cost ? `₱${parseFloat(item.cost).toLocaleString()}` : 'N/A';
                    
                    historyHTML += `
                        <li class="history-item">
                            <div class="history-header">
                                <span class="history-date">${formatDateTime(maintenanceDate)}</span>
                                <span class="history-status ${statusClass}">${item.status}</span>
                            </div>
                            <div class="history-details">
                                <div class="history-detail">
                                    <span class="detail-label">Type</span>
                                    <span class="detail-value">${maintenanceType}</span>
                                </div>
                                <div class="history-detail">
                                    <span class="detail-label">Supplier</span>
                                    <span class="detail-value">${supplierName}</span>
                                </div>
                                <div class="history-detail">
                                    <span class="detail-label">Cost</span>
                                    <span class="detail-value">${cost}</span>
                                </div>
                            </div>
                            <div class="history-remarks">
                                <div class="history-detail">
                                    <span class="detail-label">Remarks</span>
                                    <span class="detail-value">${item.remarks || 'No remarks provided'}</span>
                                </div>
                            </div>
                        </li>
                    `;
                });
                
                historyHTML += '</ul>';
                
                document.getElementById('historyModalContent').innerHTML = historyHTML;
            } else {
                document.getElementById('historyModalContent').innerHTML = 
                    `<div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No maintenance history found for this truck.</p>
                    </div>`;
            }
            
            // Show the modal
            document.getElementById('historyModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error loading maintenance history:', error);
            document.getElementById('historyModalContent').innerHTML = 
                `<div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Error loading maintenance history. Please try again.</p>
                </div>`;
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
            const dateOptions = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            
            const timeOptions = {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            };
            
           return `<span class="date">${date.toLocaleDateString(undefined, dateOptions)}</span>  <span class="time">${date.toLocaleTimeString(undefined, timeOptions)}</span>`;
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

        

        function setupModalEventListeners() {
    // History Modal close events
    const historyModal = document.getElementById('historyModal');
    const historyCloseBtn = historyModal.querySelector('.close');
    const historyCloseButton = historyModal.querySelector('.modal-footer button');
    
    // Close button (X) event
    if (historyCloseBtn) {
        historyCloseBtn.addEventListener('click', function() {
            closeHistoryModal();
        });
    }
    
    // Footer close button event
    if (historyCloseButton) {
        historyCloseButton.addEventListener('click', function() {
            closeHistoryModal();
        });
    }
    
    // Click outside modal to close
    historyModal.addEventListener('click', function(e) {
        if (e.target === historyModal) {
            closeHistoryModal();
        }
    });
    
    // ESC key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (historyModal.style.display === 'block') {
                closeHistoryModal();
            }
            
            if (document.getElementById('truckModal').style.display === 'block') {
                closeModal('truckModal');
            }
            if (document.getElementById('deleteModal').style.display === 'block') {
                closeModal('deleteModal');
            }
            if (document.getElementById('reasonModal').style.display === 'block') {
                closeModal('reasonModal');
            }
        }
    });
}


function closeHistoryModal() {
    const historyModal = document.getElementById('historyModal');
    historyModal.style.display = 'none';
    
    // Clear the modal content
    document.getElementById('historyModalContent').innerHTML = '';
}


        document.addEventListener('DOMContentLoaded', function() {
            fetchTrucks();
            fetchTruckCounts();

               setupModalEventListeners();

               const toggleBtn = document.getElementById('toggleSidebarBtn');
const sidebar = document.querySelector('.sidebar');

    document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('expanded');
    });

    document.addEventListener('click', function (e) {
    if (
        sidebar.classList.contains('expanded') &&
        !sidebar.contains(e.target) && 
        !toggleBtn.contains(e.target) 
    ) {
        sidebar.classList.remove('expanded');
    }
});
        });


       function restoreTruck(truckId) {
    Swal.fire({
        title: 'Restore Truck',
        text: 'Are you sure you want to restore this truck?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, restore it!',
        cancelButtonText: 'No, keep it',
        confirmButtonColor: '#28a745', 
        cancelButtonColor: '#6c757d',  // Gray for cancel
        customClass: {
            confirmButton: 'btn-confirm',
            cancelButton: 'btn-cancel',
            popup: 'swal-restore-popup' 
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Restoring Truck',
                html: 'Please wait while we restore the truck...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

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
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Truck has been restored successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        fetchTrucks();
                        fetchTruckCounts();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to restore truck'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while restoring the truck'
                });
            });
        }
    });
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


function previewTruckPhoto(input) {
    const preview = document.getElementById('truckPhotoPreview');
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        // Check file size (max 2MB)
        if (input.files[0].size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please select an image smaller than 2MB.'
            });
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'truck-preview-image';
            preview.appendChild(img);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function resetTruckPhotoPreview() {
    const preview = document.getElementById('truckPhotoPreview');
    preview.innerHTML = '';
    document.getElementById('truckPhoto').value = '';
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
    
    // Show loading immediately if coming from another page
    // this.checkForIncomingNavigation();
    this.setupNavigationInterception();
  },
  
  checkForIncomingNavigation() {
    // Check if we're coming from another page in the same site
    const referrer = document.referrer;
    const currentDomain = window.location.origin;
    
    // Also check sessionStorage for loading state
    const shouldShowLoading = sessionStorage.getItem('showAdminLoading');
    
    if ((referrer && referrer.startsWith(currentDomain)) || shouldShowLoading) {
      // Clear the flag
      sessionStorage.removeItem('showAdminLoading');
      
      // Show loading animation for incoming navigation
      this.show('Loading Page', 'Loading content...');
      
      // Simulate realistic loading progress
      let progress = 0;
      const progressInterval = setInterval(() => {
        progress += Math.random() * 25 + 10;
        this.updateProgress(Math.min(progress, 100));
        
        if (progress >= 100) {
          clearInterval(progressInterval);
          setTimeout(() => {
            this.hide();
          }, 600);
        }
      }, 180);
    }
  },
  
  show(title = 'Processing Request', message = 'Please wait while we complete this action...') {
    if (!this.loadingEl) return;
    
    this.titleEl.textContent = title;
    this.messageEl.textContent = message;
    
    // Reset progress
    this.updateProgress(0);
    
    this.loadingEl.style.display = 'flex';
    setTimeout(() => {
      this.loadingEl.classList.add('active');
    }, 50);
  },
  
  hide() {
    if (!this.loadingEl) return;
    
    this.loadingEl.classList.remove('active');
    setTimeout(() => {
      this.loadingEl.style.display = 'none';
    }, 800);
  },
  
  updateProgress(percent) {
    if (this.progressBar) {
      this.progressBar.style.width = `${percent}%`;
    }
    if (this.progressText) {
      this.progressText.textContent = `${Math.round(percent)}%`;
    }
  },
  
  setupNavigationInterception() {
    document.addEventListener('click', (e) => {
      // Skip if click is inside SweetAlert modal, regular modals, or calendar
      if (e.target.closest('.swal2-container, .swal2-popup, .swal2-modal, .modal, .modal-content, .fc-event, #calendar')) {
        return;
      }
      
      const link = e.target.closest('a');
      if (link && !link.hasAttribute('data-no-loading') && 
          link.href && !link.href.startsWith('javascript:') &&
          !link.href.startsWith('#') && !link.href.startsWith('mailto:') &&
          !link.href.startsWith('tel:')) {
        
        // Only intercept internal links
        try {
          const linkUrl = new URL(link.href);
          const currentUrl = new URL(window.location.href);
          
          if (linkUrl.origin !== currentUrl.origin) {
            return; // Let external links work normally
          }
          
          // Skip if it's the same page
          if (linkUrl.pathname === currentUrl.pathname) {
            return;
          }
          
        } catch (e) {
          return; // Invalid URL, let it work normally
        }
        
        e.preventDefault();
        
        // Set flag for next page
        sessionStorage.setItem('showAdminLoading', 'true');
        
        const loading = this.startAction(
          'Loading Page', 
          `Preparing ${link.textContent.trim() || 'page'}...`
        );
        
        let progress = 0;
        const progressInterval = setInterval(() => {
          progress += Math.random() * 15 + 8;
          if (progress >= 85) {
            clearInterval(progressInterval);
            progress = 90; // Stop at 90% until page actually loads
          }
          loading.updateProgress(Math.min(progress, 90));
        }, 150);
        
        // Minimum delay to show animation
        const minLoadTime = 1200;
        
        setTimeout(() => {
          // Complete the progress bar
          loading.updateProgress(100);
          setTimeout(() => {
            window.location.href = link.href;
          }, 300);
        }, minLoadTime);
      }
    });

    // Handle form submissions
    document.addEventListener('submit', (e) => {
      // Skip if form is inside SweetAlert or modal
      if (e.target.closest('.swal2-container, .swal2-popup, .modal')) {
        return;
      }
      
      // Only show loading for forms that will cause page navigation
      const form = e.target;
      if (form.method && form.method.toLowerCase() === 'post' && form.action) {
        const loading = this.startAction(
          'Submitting Form', 
          'Processing your data...'
        );
        
        setTimeout(() => {
          loading.complete();
        }, 2000);
      }
    });
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', () => {
      this.show('Loading Page', 'Loading previous page...');
      setTimeout(() => {
        this.hide();
      }, 800);
    });
  },
  
  startAction(actionName, message) {
    this.show(actionName, message);
    return {
      updateProgress: (percent) => this.updateProgress(percent),
      updateMessage: (message) => {
        if (this.messageEl) {
          this.messageEl.textContent = message;
          this.messageEl.style.opacity = 0;
          setTimeout(() => {
            this.messageEl.style.opacity = 1;
            this.messageEl.style.transition = 'opacity 0.5s ease';
          }, 50);
        }
      },
      complete: () => {
        this.updateProgress(100);
        this.updateMessage('Done!');
        setTimeout(() => this.hide(), 800);
      }
    };
  },
  
  // Public methods for manual control
  showManual: function(title, message) {
    this.show(title, message);
  },
  
  hideManual: function() {
    this.hide();
  },
  
  setProgress: function(percent) {
    this.updateProgress(percent);
  }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  AdminLoading.init();
  
  // Add smooth transition to the GIF
  const loadingGif = document.querySelector('.loading-gif');
  if (loadingGif) {
    loadingGif.style.transition = 'opacity 0.7s ease 0.3s';
  }
  
  // Hide loading on page show (handles browser back button)
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      // Page was loaded from cache (back/forward button)
      setTimeout(() => {
        AdminLoading.hideManual();
      }, 500);
    }
  });
});

// Handle page unload
// window.addEventListener('beforeunload', () => {
//   // Set flag that we're navigating
//   sessionStorage.setItem('showAdminLoading', 'true');
// });

// Export for global access (optional)
window.AdminLoading = AdminLoading;
</script>
<footer class="site-footer">

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
    </div>
</footer>
</body>
</html>