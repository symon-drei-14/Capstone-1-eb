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
          <div class="date-range-filter-container">
    <div class="date-input-wrapper">
        <input type="date" id="startDate" onchange="applyDateFilter()">
        <label for="startDate">From</label>
    </div>
    <div class="date-input-wrapper">
        <input type="date" id="endDate" onchange="applyDateFilter()">
        <label for="endDate">To</label>
    </div>
    <button class="clear-date-filter" onclick="clearDateFilter()">
        <i class="fas fa-times"></i> Clear
    </button>
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
    <label for="maintenanceTypeId">Maintenance Type:</label>
    <select id="maintenanceTypeId" name="maintenanceTypeId" required onchange="checkMaintenanceType()">
        <option value="">Select Maintenance Type</option>
        <option value="1">Preventive Maintenance</option>
        <option value="2">Emergency Maintenance</option>
    </select>
</div>
                
                <div class="form-group">
                    <label for="truckId">Truck ID:</label>
                    <select id="truckId" name="truckId" required>
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
    <div class="form-group">
        <label>Maintenance Purpose(s):</label>
        <div class="checkbox-grid" id="maintenancePurposes">
        
            <!-- Checkboxes will be populated by JavaScript -->
        </div>
            <input type="hidden" id="remarks" name="remarks">
        <div class="other-purpose" style="display: none;">
            <label for="otherPurposeText">Specify other purpose:</label>
            <textarea id="otherPurposeText" name="otherPurposeText" rows="2" placeholder="Enter specific maintenance purpose"></textarea>
        </div>
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
                    <label for="supplierId">Supplier:</label>
                        <select id="supplierId" name="supplierId" required>
                        </select>
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
        <div class="modal-header">
            <h3>Maintenance History</h3>
            <span class="close" onclick="closeHistoryModal()">&times;</span>
            </div>
                <div class="history-list" id="historyList"></div>
 
                <div class="modal-footer">
                <button type="button"  onclick="closeHistoryModal()">Close</button>
             </div>
            </div>
           
            </div>
        </div>
        
        <!-- Maintenance Reminders Modal -->
        <div id="remindersModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeRemindersModal()">&times;</span>
                <h2>Maintenance Reminders</h2>
                <div class="reminders-list" id="remindersList">
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
            let rowsPerPage = 5;
            let searchTimeout;
            let startDateFilter = null;
            let endDateFilter = null;
            let maintenanceData = [];
            
        function getLocalDate() {
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(now - offset)).toISOString().slice(0, 10);
        return localISOTime;
        }

 
$(document).ready(function() {
    loadMaintenanceData();
    fetchTrucksList();
    loadMaintenancePurposes();
    loadSuppliers();
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
        {id: 'maintenanceTypeId', name: 'Maintenance Type'},
        {id: 'truckId', name: 'Truck ID'},
        {id: 'supplierId', name: 'Supplier'},
        {id: 'date', name: 'Date of Inspection'},
        {id: 'status', name: 'Status'}
    ];
    
    // Check each required field
    for (const field of requiredFields) {
        const element = document.getElementById(field.id);
        
        if (!element) {
            console.error(`Element with ID '${field.id}' not found`);
            Swal.fire({
                title: 'Form Error',
                text: `Form field '${field.name}' is missing. Please refresh the page and try again.`,
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        const value = element.value ? element.value.trim() : '';
        
        if (!value || value === '' || value === '0' || value === 'null') {
            Swal.fire({
                title: 'Required Field Missing',
                text: `Please select/enter ${field.name}`,
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            element.focus();
            return false;
        }
    }

    // REMOVED THE OLD REMARKS VALIDATION
    // Validate maintenance purposes instead
    const selectedPurposes = [];
    document.querySelectorAll('input[name="maintenancePurpose"]:checked').forEach(checkbox => {
        if (checkbox.value === "Other") {
            const otherPurpose = document.getElementById('otherPurposeText').value.trim();
            if (otherPurpose) {
                selectedPurposes.push("Other: " + otherPurpose);
            }
        } else {
            selectedPurposes.push(checkbox.value);
        }
    });
    
    if (selectedPurposes.length === 0) {
        Swal.fire({
            title: 'Purpose Required',
            text: 'Please select at least one maintenance purpose',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return false;
    }

    // Validate date for new records
    if (!isEditing) {
        const today = new Date();
        const inspectionDate = new Date(document.getElementById("date").value);
        
        today.setHours(0, 0, 0, 0);
        inspectionDate.setHours(0, 0, 0, 0);

        if (inspectionDate < today) {
            Swal.fire({
                title: 'Invalid Date',
                text: 'Inspection date must be today or in the future',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            document.getElementById("date").focus();
            return false;
        }
    }
    
    return true;
}

function searchMaintenance() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        
        if (!searchTerm) {
            currentPage = 1;
            loadMaintenanceData();
            document.querySelector('.pagination').style.display = 'flex';
            return;
        }

      
        document.querySelector('.table-container').classList.add('loading');
        
        
        fetchAllRecordsForSearch().then(allRecords => {
            performSearch(searchTerm, allRecords);
            document.querySelector('.table-container').classList.remove('loading');
        }).catch(error => {
            console.error("Error fetching records for search:", error);
            document.querySelector('.table-container').classList.remove('loading');
        });
    }, 300);
}

function performSearch(searchTerm, allRecords) {
    const filteredRecords = allRecords.filter(record => {
        return (
            String(record.truckId).toLowerCase().includes(searchTerm) ||
            (record.licensePlate && record.licensePlate.toLowerCase().includes(searchTerm)) ||
            (record.maintenanceDate && formatDate(record.maintenanceDate).toLowerCase().includes(searchTerm)) ||
            (record.remarks && record.remarks.toLowerCase().includes(searchTerm)) ||
            (record.status && record.status.toLowerCase().includes(searchTerm)) ||
            (record.supplierName && record.supplierName.toLowerCase().includes(searchTerm)) ||
            (record.cost && String(record.cost).toLowerCase().includes(searchTerm)) ||
            (record.lastUpdatedBy && record.lastUpdatedBy.toLowerCase().includes(searchTerm)) ||
            (record.editReason && record.editReason.toLowerCase().includes(searchTerm)) ||
            (record.deleteReason && record.deleteReason.toLowerCase().includes(searchTerm))
        );
    });
    
    renderTable(filteredRecords);
    updateShowingInfo(filteredRecords.length, filteredRecords.length);
    document.querySelector('.pagination').style.display = 'none';
}

function loadMaintenanceTypes() {
    fetch('include/handlers/maintenance_handler.php?action=getMaintenanceTypes')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response from getMaintenanceTypes:', text);
                    throw new Error('Server returned non-JSON response for maintenance types');
                });
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success && data.types) {
                const select = document.getElementById('maintenanceTypeId');
                if (select) {
                    select.innerHTML = '<option value="">Select Maintenance Type</option>';
                    data.types.forEach(type => {
                        select.innerHTML += `<option value="${type.maintenance_type_id}">${type.type_name}</option>`;
                    });
                }
            } else {
                console.error('Invalid maintenance types data:', data);
            }
        })
        .catch(error => {
            console.error('Error loading maintenance types:', error);
            const select = document.getElementById('maintenanceTypeId');
            if (select) {
                select.innerHTML = '<option value="">Error loading types</option>';
            }
        });
}

function loadSuppliers() {
    fetch('include/handlers/maintenance_handler.php?action=getSuppliers')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response from getSuppliers:', text);
                    throw new Error('Server returned non-JSON response for suppliers');
                });
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success && data.suppliers) {
                const select = document.getElementById('supplierId');
                if (select) {
                    select.innerHTML = '<option value="">Select Supplier</option>';
                    data.suppliers.forEach(supplier => {
                        select.innerHTML += `<option value="${supplier.supplier_id}">${supplier.name}</option>`;
                    });
                }
            } else {
                console.error('Invalid suppliers data:', data);
            }
        })
        .catch(error => {
            console.error('Error loading suppliers:', error);
            // Show user-friendly error
            const select = document.getElementById('supplierId');
            if (select) {
                select.innerHTML = '<option value="">Error loading suppliers</option>';
            }
        });
}

function testEndpoint() {
    fetch('include/handlers/maintenance_handler.php?action=getCounts')
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            const contentType = response.headers.get('content-type');
            console.log('Content-Type:', contentType);
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.log('Non-JSON response body:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Success:', data);
        })
        .catch(error => {
            console.error('Test error:', error);
        });
}

console.log('To test your maintenance handler, run: testEndpoint()');

function loadSuppliers() {
    fetch('include/handlers/maintenance_handler.php?action=getSuppliers')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('supplierId');
            select.innerHTML = '<option value="">Select Supplier</option>';
            data.suppliers.forEach(supplier => {
                select.innerHTML += `<option value="${supplier.supplier_id}">${supplier.name}</option>`;
            });
        });
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
    maintenanceData = [];
    loadMaintenanceData();

    document.getElementById('searchInput').value = '';
}

    
function loadMaintenanceData() {
    let url = `include/handlers/maintenance_handler.php?action=getRecords&page=${currentPage}&limit=${rowsPerPage}`;
    
    if (currentStatusFilter === 'deleted') {
        url += `&showDeleted=1`;
    } 
    
    else if (currentStatusFilter !== 'all') {
        url += `&status=${encodeURIComponent(currentStatusFilter)}`;
    }

      if (startDateFilter) {
        url += `&startDate=${encodeURIComponent(startDateFilter)}`;
    }
    if (endDateFilter) {
        url += `&endDate=${encodeURIComponent(endDateFilter)}`;
    }
      url += `&_=${new Date().getTime()}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(response => {
            maintenanceData = response.records || [];
            
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

function loadAllMaintenanceData() {
    let url = `include/handlers/maintenance_handler.php?action=getRecords&page=1&limit=1000`;
    
    if (currentStatusFilter === 'deleted') {
        url += `&showDeleted=1`;
    } 
    else if (currentStatusFilter !== 'all') {
        url += `&status=${encodeURIComponent(currentStatusFilter)}`;
    }
    
    return fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(response => {
            maintenanceData = response.records || [];
            return maintenanceData;
        });
}

function changeRowsPerPage() {
    const newRowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
    if (!isNaN(newRowsPerPage) && newRowsPerPage > 0) {
        rowsPerPage = newRowsPerPage;
        currentPage = 1; 
        loadMaintenanceData();
    }
}

window.onload = function() {
    rowsPerPage = parseInt(document.getElementById('rowsPerPage').value) || 5;
    document.getElementById('rowsPerPage').value = rowsPerPage;
    loadMaintenanceData();
    updateStatsCards();
};

 function fetchAllRecordsForSearch() {
    let url = `include/handlers/maintenance_handler.php?action=getAllRecordsForSearch`;
    
    if (currentStatusFilter !== 'all') {
        url += `&status=${encodeURIComponent(currentStatusFilter)}`;
    }
    
    if (currentStatusFilter === 'deleted') {
        url += `&showDeleted=1`;
    }
    if (startDateFilter) {
        url += `&startDate=${encodeURIComponent(startDateFilter)}`;
    }
    if (endDateFilter) {
        url += `&endDate=${encodeURIComponent(endDateFilter)}`;
    }
    
    return fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                return data.records;
            } else {
                throw new Error(data.message || 'Failed to fetch records for search');
            }
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
        if (row.isDeleted) {
            tr.classList.add('deleted-row');
        }   
        
        const actionsCell = row.isDeleted 
            ? `
             <div class="dropdown">
                        <button class="dropdown-btn" data-tooltip="Actions">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-content">
                <button class="dropdown-item restore" data-tooltip="Restore" onclick="restoreMaintenance(${row.maintenanceId})">
                   <i class="fas fa-trash-restore"></i> Restore
                </button>
             
                <button class="dropdown-item history" data-tooltip="View History" onclick="openHistoryModal(${row.truckId})">
                    <i class="fas fa-history"></i> History
                </button>
                   ${window.userRole === 'Full Admin' ? 
                `<button class="dropdown-item full-delete" data-tooltip="Permanently Delete" onclick="fullDeleteMaintenance(${row.maintenanceId})">
                    <i class="fa-solid fa-ban"></i> Full Delete
                </button>` : ''}
            
            </div>
            </div>    `
        : `  <div class="dropdown">
                <button class="dropdown-btn" data-tooltip="Actions">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-content">
                <button class="dropdown-item edit" data-tooltip="Edit" onclick="openEditModal(${row.maintenanceId}, ${row.truckId}, '${row.licensePlate || ''}', '${row.maintenanceDate}', '${row.remarks || ''}', '${row.status}', ${row.supplierId || 'null'}, ${row.cost || 0}, ${row.maintenanceTypeId || 'null'})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="dropdown-item history" data-tooltip="View History" onclick="openHistoryModal(${row.truckId})">
                    <i class="fas fa-history"></i> History
                </button>
                <button class="dropdown-item delete" data-tooltip="Delete" onclick="deleteMaintenance(${row.maintenanceId})">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
                </div>
                </div>
            `;
        
        tr.innerHTML = `
            <td>${row.truckId}</td>
            <td>${row.licensePlate || 'N/A'}</td>
            <td>${formatDate(row.maintenanceDate)}</td>
            <td>${row.remarks || 'N/A'}</td>
            <td><span class="status-${row.status.toLowerCase().replace(" ", "-")}">${row.status}</span></td>
            <td>${row.supplierName || 'N/A'}</td>
            <td>₱ ${parseFloat(row.cost || 0).toFixed(2)}</td>
            <td>
                <strong>${row.lastUpdatedBy || 'System'}</strong><br>
                ${formatDateTime(row.lastUpdatedAt)}<br>
                ${(row.editReason || row.deleteReason) ? 
                    `<button class="view-remarks-btn" 
                        data-reasons='${JSON.stringify({
                            editReasons: row.editReason ? [row.editReason] : null,
                            deleteReason: row.deleteReason
                        })}'>View Remarks</button>` : ''
                }
            </td>
            <td class="actions">${actionsCell}</td>
        `;
        tableBody.appendChild(tr);
    });

    // Add event listeners for view remarks buttons
    document.querySelectorAll('.view-remarks-btn').forEach(button => {
        button.addEventListener('click', function() {
            showEditRemarks(this.getAttribute('data-reasons'));
        });
    });
}


$(document).on('click', '.dropdown-btn', function(e) {
    e.stopPropagation();
    $('.dropdown-content').not($(this).siblings('.dropdown-content')).removeClass('show');
    $(this).siblings('.dropdown-content').toggleClass('show');
});
$(document).on('click', function(e) {
    if (!$(e.target).closest('.dropdown').length) {
        $('.dropdown-content').removeClass('show');
    }
});

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
            
            return `${date.toLocaleDateString(undefined, dateOptions)} at ${date.toLocaleTimeString(undefined, timeOptions)}`;
        }
        
            function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    
    // Format date in word form: "Month Day, Year"
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    
    return date.toLocaleDateString('en-US', options);
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

function openEditModal(id, truckId, licensePlate, date, remarks, status, supplierId, cost, maintenanceTypeId) {
    isEditing = true;
    document.getElementById("modalTitle").textContent = "Edit Maintenance Schedule";
    document.getElementById("maintenanceId").value = id;
    document.getElementById("truckId").value = truckId;
    document.getElementById("licensePlate").value = licensePlate || '';
    document.getElementById("date").value = date;
    document.getElementById("remarks").value = remarks || '';
    document.getElementById("status").value = status;
    document.getElementById("cost").value = cost;

    document.getElementById("maintenanceTypeId").value = maintenanceTypeId;
    document.getElementById("supplierId").value = supplierId;

    document.getElementById("status").disabled = false;
    populateMaintenancePurposes(remarks);

    document.querySelector('.edit-reasons-section').style.display = 'block';
    document.querySelectorAll('input[name="editReason"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('otherReasonText').value = '';

    document.getElementById("maintenanceModal").style.display = "block";
}

function populateMaintenancePurposes(remarks) {
    if (!remarks) return;
    
    // Reset all checkboxes
    document.querySelectorAll('input[name="maintenancePurpose"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('otherPurposeText').value = '';
    document.querySelector('.other-purpose').style.display = 'none';
    
    // Split remarks by commas to get individual purposes
    const purposes = remarks.split(',').map(p => p.trim());
    
    purposes.forEach(purpose => {
        // Check if it's an "Other" purpose
        if (purpose.startsWith('Other:')) {
            const otherText = purpose.replace('Other:', '').trim();
            document.getElementById('purpose-other').checked = true;
            document.getElementById('otherPurposeText').value = otherText;
            document.querySelector('.other-purpose').style.display = 'block';
        } else {
            // Find and check the matching checkbox
            const checkboxes = document.querySelectorAll('input[name="maintenancePurpose"]');
            for (let checkbox of checkboxes) {
                if (checkbox.value === purpose) {
                    checkbox.checked = true;
                    break;
                }
            }
        }
    });
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
         document.querySelectorAll('input[name="maintenancePurpose"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('otherPurposeText').value = '';
    document.querySelector('.other-purpose').style.display = 'none';
    }

    
            
function saveMaintenanceRecord() {
    if (!validateMaintenanceForm()) {
        return;
    }

    // Collect selected maintenance purposes
    const selectedPurposes = [];
    document.querySelectorAll('input[name="maintenancePurpose"]:checked').forEach(checkbox => {
        if (checkbox.value === "Other") {
            const otherPurpose = document.getElementById('otherPurposeText').value.trim();
            if (otherPurpose) {
                selectedPurposes.push("Other: " + otherPurpose);
            }
        } else {
            selectedPurposes.push(checkbox.value);
        }
    });
    
    // Validate that at least one purpose is selected
    if (selectedPurposes.length === 0) {
        Swal.fire({
            title: 'Purpose Required',
            text: 'Please select at least one maintenance purpose',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Join purposes into a string for the remarks field
    const remarks = selectedPurposes.join(', ');
    document.getElementById('remarks').value = remarks;

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
    
    const maintenanceId = document.getElementById("maintenanceId").value;

    const formData = {
        truckId: parseInt(document.getElementById("truckId").value),
        maintenanceTypeId: parseInt(document.getElementById("maintenanceTypeId").value),
        supplierId: parseInt(document.getElementById("supplierId").value),
        date: document.getElementById("date").value,
        remarks: document.getElementById("remarks").value.trim(),
        status: document.getElementById("status").value,
        cost: parseFloat(document.getElementById("cost").value || 0),
        editReasons: editReasons
    };

    if (isEditing && maintenanceId) {
        formData.maintenanceId = parseInt(maintenanceId);
    }

    Swal.fire({
        title: isEditing ? 'Updating Record' : 'Adding Record',
        html: 'Please wait while we process your request...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const action = isEditing ? 'edit' : 'add';
    
    fetch(`include/handlers/maintenance_handler.php?action=${action}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response. Check console for details.');
            });
        }
        return response.json();
    })
    .then(data => {
        Swal.close();
        
        if (data.success) {
            Swal.fire({
                title: 'Success!',
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
                text: data.message || 'An unknown error occurred',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Save error:', error);
        
        let errorMessage = 'Failed to save maintenance record.';
        if (error.message.includes('non-JSON response')) {
            errorMessage += ' The server may be experiencing issues. Please check the browser console and try again.';
        } else {
            errorMessage += ' Error: ' + error.message;
        }
        
        Swal.fire({
            title: 'Error',
            html: errorMessage,
            icon: 'error',
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
                                 let statusClass = 'status-pending';
                                    if (item.status.toLowerCase().includes('complete')) statusClass = 'status-completed';
                                    if (item.status.toLowerCase().includes('progress')) statusClass = 'status-in-progress';
                                    if (item.status.toLowerCase().includes('pending')) statusClass = 'status-pending';
                                    if (item.status.toLowerCase().includes('overdue')) statusClass = 'status-overdue';
                                const historyItem = document.createElement("div");
                                   const supplierName = item.supplier || item.supplier_name || item.supplierName || 'N/A';
                                historyItem.className = "history-item";
                            historyItem.innerHTML = `
                            <li class="history-item">
                                <div class="history-header">
                                  <span class="history-date"> ${formatDate(item.date_mtnce)}</span>
                                   <span class="history-status ${statusClass}"> ${item.status}</span>
                                </div>
                                <div class="history-details">   
                                 <div class="history-detail">
                                    <span class="detail-label">Supplier</span>
                                    <span class="detail-value"> ${supplierName}</span>
                                    </div>
                                <div class="history-detail">
                                    <span class="detail-label">Cost</span>
                                     <span class="detail-value">₱${parseFloat(item.cost).toFixed(2)}</span>
                                </div>
                                  <div class="history-detail">
                                    <span class="detail-label">Remarks</span>
                                    <span class="detail-value"> ${item.remarks || 'No remarks provided'} </span>
                                    </div>
                                </div>
                                 <div class="history-remarks">
                                  <div class="history-detail">
                                    <span class="detail-label">Last Modified By:</span>
                                    <span class="detail-value">  ${item.last_modified_by} on ${formatDateTime(item.last_modified_at)} </span>
                                </div>
                                    </div>
                            </div>
                            
                            </li>
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
                                // Reset to first page and clear any filters
                                currentPage = 1;
                                document.getElementById('searchInput').value = '';
                                document.getElementById('statusFilter').value = 'all';
                                currentStatusFilter = 'all';
                                
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

function loadMaintenancePurposes() {
    fetch('include/handlers/maintenance_handler.php?action=getMaintenanceTypes')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.types) {
                const container = document.getElementById('maintenancePurposes');
                container.innerHTML = '';
                
                data.types.forEach(type => {
                    const checkboxId = `purpose-${type.maintenance_type_id}`;
                    const checkboxItem = document.createElement('div');
                    checkboxItem.className = 'checkbox-item';
                    checkboxItem.innerHTML = `
                        <input type="checkbox" name="maintenancePurpose" value="${type.type_name}" 
                               id="${checkboxId}" onchange="toggleOtherPurpose()">
                        <label for="${checkboxId}">${type.type_name}</label>
                    `;
                    container.appendChild(checkboxItem);
                });
                
                // Add the "Other" option
                const otherItem = document.createElement('div');
                otherItem.className = 'checkbox-item';
                otherItem.innerHTML = `
                    <input type="checkbox" name="maintenancePurpose" value="Other" 
                           id="purpose-other" onchange="toggleOtherPurpose()">
                    <label for="purpose-other">Other</label>
                `;
                container.appendChild(otherItem);
            }
        })
        .catch(error => {
            console.error('Error loading maintenance purposes:', error);
        });
}

// Function to toggle the other purpose textarea
function toggleOtherPurpose() {
    const otherCheckbox = document.getElementById('purpose-other');
    const otherPurposeSection = document.querySelector('.other-purpose');
    
    if (otherCheckbox.checked) {
        otherPurposeSection.style.display = 'block';
    } else {
        otherPurposeSection.style.display = 'none';
        document.getElementById('otherPurposeText').value = '';
    }
}

// Function to check maintenance type and validate date for preventive maintenance
function checkMaintenanceType() {
    const maintenanceType = document.getElementById('maintenanceTypeId').value;
    const dateInput = document.getElementById('date');
    const truckId = document.getElementById('truckId').value;
    
    if (maintenanceType === '1' && truckId) { // Preventive Maintenance
        // Check if date is at least 6 months after last preventive maintenance
        fetch(`include/handlers/maintenance_handler.php?action=checkPreventiveDate&truckId=${truckId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.lastDate) {
                    const lastDate = new Date(data.lastDate);
                    const minDate = new Date(lastDate);
                    minDate.setMonth(minDate.getMonth() + 6);
                    
                    // Set the minimum date for the date input
                    dateInput.min = minDate.toISOString().split('T')[0];
                    
                    // Show warning if selected date is before minimum date
                    if (new Date(dateInput.value) < minDate) {
                        dateInput.value = minDate.toISOString().split('T')[0];
                        Swal.fire({
                            title: 'Date Adjusted',
                            text: `Preventive maintenance must be at least 6 months after the last one (${formatDate(data.lastDate)}). Date has been adjusted accordingly.`,
                            icon: 'info',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error checking preventive maintenance date:', error);
            });
    } else {
        // Remove restriction for emergency maintenance
        dateInput.removeAttribute('min');
    }
}
function applyDateFilter() {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (startDateInput.value && endDateInput.value) {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate > endDate) {
            Swal.fire({
                title: 'Invalid Date Range',
                text: 'Start date cannot be after end date',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            
            startDateInput.value = endDateInput.value;
            endDateInput.value = startDateInput.value;
            return;
        }
    }
    
    startDateFilter = startDateInput.value;
    endDateFilter = endDateInput.value;

    currentPage = 1;
    loadMaintenanceData();
}

function clearDateFilter() {
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    startDateFilter = null;
    endDateFilter = null;

    currentPage = 1;
    loadMaintenanceData();
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