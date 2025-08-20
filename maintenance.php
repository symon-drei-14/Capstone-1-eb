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
        
        <link rel="stylesheet" href="include/css/sidenav.css">
        <link rel="stylesheet" href="include/css/loading.css">
        <link rel="stylesheet" href="include/css/maintenancestyle.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
    </head>
<style>
    .swal2-container {
  z-index: 999999 !important;
}   
    </style>
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

        <h3><i class="fa-solid fa-wrench"></i>Preventive Maintenance Scheduling</h3>
        <div class="stats-container-wrapper">
     <div class="stats-cards">
    <div class="stat-card pending">
        <div class="stat-icon icon-pending"><i class="fas fa-clock"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">Pending</div>
        </div>
    </div>
    <div class="stat-card in-progress">
        <div class="stat-icon icon-in-progress"><i class="fas fa-spinner"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">In Progress</div>
        </div>
    </div>
    <div class="stat-card completed">
        <div class="stat-icon icon-completed"><i class="fas fa-check-circle"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">Completed this month</div>
        </div>
    </div>
    <div class="stat-card overdue">
        <div class="stat-icon icon-overdue"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">Overdue</div>
        </div>
    </div>
    <div class="stat-card total">
        <div class="stat-icon icon-total"><i class="fas fa-tools"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">Total</div>
        </div>
    </div>
</div>
</div>
        <div class="main-content3">
            <div class="dashboard-header">
    <div class="header-left">
        <div class="button-row">
            <button class="add_sched" onclick="openModal('add')">Add Maintenance Schedule</button>
            <button class="reminder_btn" onclick="openRemindersModal()">Maintenance Reminders</button>
        </div>
    </div>
    
 
</div>
        <div class="filter-controls">
        <div class="status-filter-container">
            <select id="statusFilter" onchange="filterTableByStatus()">
                <option value="" disabled selected>Status Filter </option>
                <option value="all">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="In Progress">In Progress</option>
                <option value="Overdue">Overdue</option>
                <option value="deleted">Deleted</option>
            </select>
        </div>
        
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search..." onkeyup="searchMaintenance()">
        </div>
        
    
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
    <br/>
    <br/>
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
            
            <div class="form-row">
                <div class="form-group">
                    <label for="maintenanceType">Maintenance Type:</label>
                    <select id="maintenanceType" name="maintenanceType" required>
                        <option value="preventive">Preventive Maintenance</option>
                        <option value="emergency">Emergency Repair</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="truckId">Truck ID:</label>
                    <select id="truckId" name="truckId" required>
                        <!-- Will be populated by JavaScript -->
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="licensePlate">License Plate:</label>
                    <input type="text" id="licensePlate" name="licensePlate" readonly>
                </div>
                
                <div class="form-group">
                    <label for="date">Date of Inspection:</label>
                    <input type="date" id="date" name="date" required>
                </div>
            </div>
            
            <div class="form-group2">
                <label>Remarks:</label>
                <div class="checkbox-grid">
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Change Oil" id="remark-oil">
                        <label for="remark-oil">Change Oil</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Change Tires" id="remark-tires">
                        <label for="remark-tires">Change Tires</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Brake Inspection" id="remark-brake">
                        <label for="remark-brake">Brake Inspection</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Engine Check" id="remark-engine">
                        <label for="remark-engine">Engine Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Transmission Check" id="remark-transmission">
                        <label for="remark-transmission">Transmission Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Electrical System Check" id="remark-electrical">
                        <label for="remark-electrical">Electrical System Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Suspension Check" id="remark-suspension">
                        <label for="remark-suspension">Suspension Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Other" id="remark-other">
                        <label for="remark-other">Other</label>
                    </div>
                </div>
                
                <div class="other-remark">
                    <label for="otherRemarkText">Specify other remark:</label>
                    <textarea id="otherRemarkText" name="otherRemarkText" rows="3" placeholder="Enter specific remark"></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Pending" selected>Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="supplier">Supplier:</label>
                    <input type="text" id="supplier" name="supplier">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cost">Cost:</label>
                    <input type="number" id="cost" name="cost" step="0.01">
                </div>
            </div>
            
            <div class="edit-reasons-section">
                <label>Reason for Edit (select all that apply):</label>
                <div class="checkbox-grid">
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Changed maintenance schedule as per supplier availability" id="reason-supplier">
                        <label for="reason-supplier">Supplier availability</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Updated maintenance type based on vehicle condition" id="reason-condition">
                        <label for="reason-condition">Vehicle condition</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Adjusted date due to parts availability" id="reason-parts">
                        <label for="reason-parts">Parts availability</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Updated cost estimate after inspection" id="reason-cost">
                        <label for="reason-cost">Updated cost</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Changed status based on work progress" id="reason-progress">
                        <label for="reason-progress">Work progress</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Updated supplier information" id="reason-supplier-info">
                        <label for="reason-supplier-info">Supplier info</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Corrected data entry error" id="reason-error">
                        <label for="reason-error">Data correction</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Other" id="reason-other">
                        <label for="reason-other">Other</label>
                    </div>
                </div>
                
                <div class="other-reason">
                    <label for="otherReasonText">Specify other reason:</label>
                    <textarea id="otherReasonText" name="otherReasonText" rows="3" placeholder="Enter specific reason for edit"></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="cancelbtn" onclick="closeModal()">Cancel</button>
                <button type="button" class="submitbtn" onclick="saveMaintenanceRecord()">Submit</button>
            </div>
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
            let rowsPerPage = 5; // Default value
            
        function getLocalDate() {
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000; // offset in milliseconds
        const localISOTime = (new Date(now - offset)).toISOString().slice(0, 10);
        return localISOTime;
        }

 

    $(document).ready(function() {
        loadMaintenanceData();
        fetchTrucksList();
         updateStatsCards();
    });

      function updateDateTime() {
        const now = new Date();
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
        
        document.getElementById('current-time').textContent = now.toLocaleTimeString();
    }

   
    updateDateTime();
    setInterval(updateDateTime, 1000);

            function validateMaintenanceForm() {
    const requiredFields = [
        {id: 'truckId', name: 'Truck ID'},
        {id: 'date', name: 'Date of Inspection'},
        {id: 'status', name: 'Status'},
        {id: 'maintenanceType', name: 'Maintenance Type'}
    ];
    
    for (const field of requiredFields) {
        const element = document.getElementById(field.id);
        if (!element || !element.value) {
            
            alert(`Please fill in the ${field.name} field`);
            if (element) element.focus();
            return false;
        }
    }
    
    // Check at least one remark is selected
    const remarkCheckboxes = document.querySelectorAll('input[name="remarks[]"]:checked');
    if (remarkCheckboxes.length === 0) {
        Swal.fire({
    title: 'Please select at least one maintenance remark.',
    icon: 'info',
    confirmButtonText: 'OK'
});

        return false;
    }
    
    // Additional validation - check if date is in the future for new records
    if (!isEditing) {
        const today = new Date();
        const inspectionDate = new Date(document.getElementById("date").value);
        if (inspectionDate < today) {
            Swal.fire({
            title: 'Inspection date must be today if emergency keneme or in the future kung preventive chuvabels',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
                
            document.getElementById("date").focus();
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
    
   let url = `include/handlers/maintenance_handler.php?action=getRecords&page=${currentPage}&limit=${rowsPerPage}`;
    
    if (currentStatusFilter === 'deleted') {
        url += `&showDeleted=1`;
    } 
    else if (currentStatusFilter !== 'all') {
        url += `&status=${encodeURIComponent(currentStatusFilter)}`;
    }
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(response => {
            renderTable(response.records || []);
            totalPages = response.totalPages || 1;
            currentPage = response.currentPage || 1;
            updatePagination();
             updateShowingInfo(response.totalRecords, response.records.length);
        })
        .catch(error => {
            console.error("Error loading data:", error);
            const tableBody = document.querySelector("#maintenanceTable tbody");
            tableBody.innerHTML = '<tr><td colspan="9" class="text-center">Error loading data</td></tr>';
             document.getElementById('showingInfo').textContent = 'Showing 0 to 0 of 0 entries';
        });
}

        function changeRowsPerPage() {
            const newRowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
            if (!isNaN(newRowsPerPage) && newRowsPerPage > 0) {
                rowsPerPage = newRowsPerPage;
                currentPage = 1; 
                loadMaintenanceData();
               
                document.getElementById('rowsPerPage').value = rowsPerPage;
            }
        }

    window.onload = function() {

        rowsPerPage = parseInt(document.getElementById('rowsPerPage').value) || 5;
        document.getElementById('rowsPerPage').value = rowsPerPage;
        

        document.getElementById('showDeletedCheckbox').addEventListener('change', toggleDeletedRecords);
        loadMaintenanceData();
        updateStatsCards();
    };

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
                    <button class="icon-btn restore" data-tooltip="Restore" onclick="restoreMaintenance(${row.maintenance_id})">
                       <i class="fas fa-trash-restore"></i>
                    </button>
                    ${window.userRole === 'Full Admin' ? 
                    `<button class="icon-btn full-delete" data-tooltip="Permanently Delete" onclick="fullDeleteMaintenance(${row.maintenance_id})">
                        <i class="fa-solid fa-ban"></i>
                    </button>` : ''}
                    <button class="icon-btn history" data-tooltip="View History" onclick="openHistoryModal(${row.truck_id})">
                        <i class="fas fa-history"></i>
                    </button>
                `
                : `
                    <button class="icon-btn edit" data-tooltip="Edit" onclick="openEditModal(${row.maintenance_id}, ${row.truck_id}, '${row.licence_plate || ''}', '${row.date_mtnce}', '${row.remarks}', '${row.status}', '${row.supplier || ''}', ${row.cost}, '${row.maintenance_type || 'preventive'}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="icon-btn delete" data-tooltip="Delete" onclick="deleteMaintenance(${row.maintenance_id})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                    <button class="icon-btn history" data-tooltip="View History" onclick="openHistoryModal(${row.truck_id})">
                        <i class="fas fa-history"></i>
                    </button>
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
    Swal.fire({
        title: 'Permanent Deletion',
        html: '<strong>Are you sure you want to PERMANENTLY delete this maintenance record?</strong><br><br>This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete permanently!',
        cancelButtonText: 'Cancel',
        backdrop: 'rgba(0,0,0,0.7)',
        allowOutsideClick: false,
        customClass: {
            container: 'swal2-top-container',
            popup: 'swal2-delete-popup'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting Record',
                html: 'Please wait while we permanently remove this record...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`include/handlers/maintenance_handler.php?action=fullDelete&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            title: 'Success',
                            text: 'Maintenance record has been permanently deleted!',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            loadMaintenanceData();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || "Failed to delete record",
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error("Error deleting record:", error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to delete maintenance record. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
        }
    });
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
    const paginationContainer = document.querySelector(".pagination");
    paginationContainer.innerHTML = "";


    const prevButton = document.createElement("button");
    prevButton.classList.add("prev");
    prevButton.innerHTML = '&laquo;';
    prevButton.onclick = () => changePage(-1);
    prevButton.disabled = currentPage === 1;
    paginationContainer.appendChild(prevButton);


    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    // Adjust if we're at the end
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // Always show page 1
    if (startPage > 1) {
        const firstPageButton = document.createElement("button");
        firstPageButton.textContent = "1";
        firstPageButton.onclick = () => {
            currentPage = 1;
            loadMaintenanceData();
        };
        if (currentPage === 1) {
            firstPageButton.classList.add("active");
        }
        paginationContainer.appendChild(firstPageButton);

        if (startPage > 2) {
            const ellipsis = document.createElement("span");
            ellipsis.textContent = "...";
            ellipsis.classList.add("ellipsis");
            paginationContainer.appendChild(ellipsis);
        }
    }

    // Create numbered page buttons
    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement("button");
        pageButton.textContent = i;
        if (i === currentPage) {
            pageButton.classList.add("active");
        }
        pageButton.onclick = () => {
            currentPage = i;
            loadMaintenanceData();
        };
        paginationContainer.appendChild(pageButton);
    }

    // Always show last page if needed
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement("span");
            ellipsis.textContent = "...";
            ellipsis.classList.add("ellipsis");
            paginationContainer.appendChild(ellipsis);
        }

        const lastPageButton = document.createElement("button");
        lastPageButton.textContent = totalPages;
        lastPageButton.onclick = () => {
            currentPage = totalPages;
            loadMaintenanceData();
        };
        if (currentPage === totalPages) {
            lastPageButton.classList.add("active");
        }
        paginationContainer.appendChild(lastPageButton);
    }

    // Create Next button
    const nextButton = document.createElement("button");
    nextButton.classList.add("next");
    nextButton.innerHTML = '&raquo;';
    nextButton.onclick = () => changePage(1);
    nextButton.disabled = currentPage === totalPages;
    paginationContainer.appendChild(nextButton);
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
    document.getElementById("status").value = status;
    document.getElementById("supplier").value = supplier;
    document.getElementById("cost").value = cost;
    document.getElementById("maintenanceType").value = maintenanceType || 'preventive';
    
    // Parse the remarks and set checkboxes
    if (remarks) {
        try {
            // Try to parse as JSON first
            let remarksArray;
            try {
                remarksArray = JSON.parse(remarks);
            } catch (e) {
                // If not JSON, treat as comma-separated string
                remarksArray = remarks.split(',').map(item => item.trim());
            }
            
            // Uncheck all checkboxes first
            document.querySelectorAll('input[name="remarks[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check the appropriate checkboxes
            remarksArray.forEach(remark => {
                // Check if remark starts with "Other:"
                if (typeof remark === 'string' && remark.startsWith("Other:")) {
                    document.querySelector('input[name="remarks[]"][value="Other"]').checked = true;
                    document.getElementById('otherRemarkText').value = remark.replace("Other:", "").trim();
                } else {
                    // Try to find exact match first
                    const exactMatch = document.querySelector(`input[name="remarks[]"][value="${remark}"]`);
                    if (exactMatch) {
                        exactMatch.checked = true;
                    } else {
                        // If no exact match, check if it's one of our standard options
                        const standardRemarks = [
                            "Change Oil", "Change Tires", "Brake Inspection", 
                            "Engine Check", "Transmission Check", 
                            "Electrical System Check", "Suspension Check"
                        ];
                        
                        if (standardRemarks.includes(remark)) {
                            document.querySelector(`input[name="remarks[]"][value="${remark}"]`).checked = true;
                        } else {
                            // If not a standard option, put in Other
                            document.querySelector('input[name="remarks[]"][value="Other"]').checked = true;
                            document.getElementById('otherRemarkText').value = remark;
                        }
                    }
                }
            });
        } catch (e) {
            console.error("Error parsing remarks:", e);
            // Fallback: if parsing fails, treat as single value
            const checkbox = document.querySelector(`input[name="remarks[]"][value="${remarks}"]`);
            if (checkbox) {
                checkbox.checked = true;
            } else if (remarks) {
                document.querySelector('input[name="remarks[]"][value="Other"]').checked = true;
                document.getElementById('otherRemarkText').value = remarks;
            }
        }
    } else {
        // Reset if no remarks
        document.querySelectorAll('input[name="remarks[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('otherRemarkText').value = '';
    }
    
    document.getElementById("status").disabled = false;
    document.querySelector('.edit-reasons-section').style.display = 'block';
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
            swalInstance.close();
            Swal.fire({
                title: 'Edit Reason Required',
                text: 'Please select at least one reason for editing this record.',
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return;
        }
        }
        
        // Collect remarks
        let remarks = [];
        const remarkCheckboxes = document.querySelectorAll('input[name="remarks[]"]:checked');
        remarkCheckboxes.forEach(checkbox => {
            if (checkbox.value === "Other") {
                const otherRemark = document.getElementById('otherRemarkText').value.trim();
                if (otherRemark) {
                    remarks.push("Other: " + otherRemark);
                }
            } else {
                remarks.push(checkbox.value);
            }
        });
        
        if (remarks.length === 0) {
            Swal.fire({
            title: 'Please select at least one maintenance remark.',
            icon: 'info',
            confirmButtonText: 'OK'
        });
    
            return;
        }
        
        const form = document.getElementById("maintenanceForm");
        const maintenanceId = document.getElementById("maintenanceId").value;
        const action = isEditing ? 'edit' : 'add';
        
        const formData = {
            maintenanceId: maintenanceId ? parseInt(maintenanceId) : null,
            truckId: parseInt(document.getElementById("truckId").value),
            licensePlate: document.getElementById("licensePlate").value,
            date: document.getElementById("date").value,
            remarks: JSON.stringify(remarks), 
            status: document.getElementById("status").value,
            supplier: document.getElementById("supplier").value,
            cost: parseFloat(document.getElementById("cost").value || 0),
            maintenanceType: document.getElementById("maintenanceType").value,
            editReasons: editReasons
        };
        
        $.ajax({
        url: 'include/handlers/maintenance_handler.php?action=' + (isEditing ? 'edit' : 'add'),
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            Swal.close();
            if (response.success) {
                Swal.fire({
                    title: 'Success',
                    text: isEditing ? 'Maintenance record updated successfully!' : 'Maintenance record added successfully!',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    closeModal();
                    loadMaintenanceData();
                    updateStatsCards();
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'An unknown error occurred',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn-confirm-error'
                    }
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            Swal.fire({
                title: 'Error',
                html: 'Failed to save maintenance record.<br><br>Error details: ' + error,
                icon: 'error',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn-confirm-error'
                }
            });
            console.error("Error saving record:", error);
        }
    });
}
    document.getElementById('otherRemarkText').addEventListener('input', function() {
        const otherCheckbox = document.querySelector('input[name="remarks[]"][value="Other"]');
        if (this.value.trim() !== '') {
            otherCheckbox.checked = true;
        }
    });
    

    document.querySelector('input[name="remarks[]"][value="Other"]').addEventListener('change', function() {
        if (!this.checked) {
            document.getElementById('otherRemarkText').value = '';
        }
    });

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
                updateShowingInfo(filteredRecords.length, filteredRecords.length);
                // Hide pagination during search
                document.querySelector('.pagination').style.display = 'none';
            })
            .catch(error => {
                console.error("Error searching records:", error);
                Swal.fire({
                    title: 'Failed to search maintenance records.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
        
            });
    }
            
    function deleteMaintenance(id) {
        if (!confirm("Are you sure you want to delete this maintenance record?")) {
            return;
        }
        
        const deleteReason = prompt("Please enter the reason for deleting this record:");
        if (deleteReason === null) return; // User cancelled
        if (deleteReason.trim() === "") {
            Swal.fire({
                title: 'You must provide a reason for deletion.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
    
            return;
        }
        
        $.ajax({
            url: `include/handlers/maintenance_handler.php?action=delete&id=${id}&reason=${encodeURIComponent(deleteReason)}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    loadMaintenanceData();
                    updateStatsCards();
                    Swal.fire({
                        title: 'Maintenance record deleted successfully!',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert("Error: " + (response.message || "Unknown error"));
                }
            },
            error: function(xhr, status, error) {
                console.error("Error deleting record: " + error);
                Swal.fire({
                    title: 'Failed to delete maintenance record',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            
            }
        });
    }

    function updateStatsCards() {
    fetch('include/handlers/maintenance_handler.php?action=getCounts')
        .then(response => response.json())
        .then(data => {
            document.querySelector('.stat-card.total .value').textContent = data.total || 0;
            document.querySelector('.stat-card.pending .value').textContent = data.pending || 0;
            document.querySelector('.stat-card.in-progress .value').textContent = data.in_progress || 0;
            document.querySelector('.stat-card.completed .value').textContent = data.completed_this_month || 0;
            document.querySelector('.stat-card.overdue .value').textContent = data.overdue || 0;
        })
        .catch(error => {
            console.error("Error loading stats:", error);
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
                        Swal.fire({
                            title: 'Failed to load maintenance history.',
                            icon: 'info',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
            
            function closeHistoryModal() {
                document.getElementById("historyModal").style.display = "none";
            }
            
            function openRemindersModal() {
    const modal = document.getElementById("remindersModal");
    const list = document.getElementById("remindersList");
    
    // Show loading state
    list.innerHTML = "<p>Loading reminders...</p>";
    modal.style.display = "block";
    
    fetch('include/handlers/maintenance_handler.php?action=getReminders')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.reminders.length === 0) {
                list.innerHTML = "<p>No upcoming maintenance reminders.</p>";
                return;
            }
            
            // Use document fragment for better performance
            const fragment = document.createDocumentFragment();
            
            data.reminders.forEach(item => {
                const daysRemaining = parseInt(item.days_remaining);
                let statusClass, daysText;
                
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
                fragment.appendChild(reminderItem);
            });
            
            list.innerHTML = ""; // Clear loading message
            list.appendChild(fragment);
        })
        .catch(error => {
            console.error("Error loading reminders:", error);
            list.innerHTML = "<p>Failed to load reminders. Please try again.</p>";
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
    Swal.fire({
        title: 'Restore Maintenance Record',
        html: 'Are you sure you want to restore this maintenance record?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, restore it',
        cancelButtonText: 'No, cancel',
        allowOutsideClick: false,
        customClass: {
            container: 'swal2-top-container',
            popup: 'swal2-restore-popup'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Restoring...',
                html: 'Please wait while we restore the record',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: `include/handlers/maintenance_handler.php?action=restore&id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Maintenance record restored successfully',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            willClose: () => {
                                loadMaintenanceData();
                                updateStatsCards();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Failed to restore record',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn-confirm-error'
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    Swal.fire({
                        title: 'Error',
                        html: `Failed to restore maintenance record<br><br>${error}`,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'btn-confirm-error'
                        }
                    });
                    console.error("Error restoring record:", error);
                }
            });
        }
    });
}
    function toggleDeletedRecords() {
        loadMaintenanceData();
    }
        </script>
        <script>
        document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('expanded');
        });

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


function updateShowingInfo(totalRecords, currentPageRecordsCount) {
    const showingInfo = document.getElementById('showingInfo');
    
    if (!totalRecords || totalRecords === 0) {
        showingInfo.textContent = 'Showing 0 to 0 of 0 entries';
        return;
    }
    
    const start = ((currentPage - 1) * rowsPerPage) + 1;
    const end = start + currentPageRecordsCount - 1;
    
    showingInfo.textContent = `Showing ${start} to ${end} of ${totalRecords} entries`;
}
    </script>

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