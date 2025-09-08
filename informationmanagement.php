<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess(); // No role needed—logic is handled internally
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Information Management</title>
    <link rel="stylesheet" href="include/css/sidenav.css">
    <link rel="stylesheet" href="include/css/loading.css">
    <link rel="stylesheet" href="include/css/informationmanagement.css">
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

<h3 class="title"><i class="fa-solid fa-chart-line"></i>Information Management</h3>

<div class="tabs-container">
    <div class="tabs">
        <button class="tab-btn active" data-tab="dispatchers">Dispatchers</button>
        <button class="tab-btn" data-tab="destinations">Destinations</button>
        <button class="tab-btn" data-tab="clients">Clients</button>
        <button class="tab-btn" data-tab="shipping-lines">Shipping Lines</button>
        <button class="tab-btn" data-tab="consignees">Consignees</button>
    </div>
    
    <!-- Dispatchers Tab -->
    <div class="tab-content active" id="dispatchers-tab">
        <div class="table-controls">
            <button class="add-btn" onclick="openModal('dispatchers')"><i class="fa-solid fa-plus"></i> Add Dispatcher</button>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="dispatchers-search" placeholder="Search dispatchers..." oninput="searchTable('dispatchers')">
            </div>
        </div>
        
        <div class="table-container">
            <table id="dispatchers-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Last Modified By</th>
                        <th>Last Modified At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="dispatchers-table-body"></tbody>
            </table>
        </div>
        
        <div class="pagination">
            <button class="prev" onclick="changePage('dispatchers', -1)">◄</button>
            <span id="dispatchers-page-info">Page 1</span>
            <button class="next" onclick="changePage('dispatchers', 1)">►</button>
        </div>
    </div>
    
    <!-- Destinations Tab -->
    <div class="tab-content" id="destinations-tab">
        <div class="table-controls">
            <button class="add-btn" onclick="openModal('destinations')"><i class="fa-solid fa-plus"></i> Add Destination</button>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="destinations-search" placeholder="Search destinations..." oninput="searchTable('destinations')">
            </div>
        </div>
        
        <div class="table-container">
            <table id="destinations-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Last Modified By</th>
                        <th>Last Modified At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="destinations-table-body"></tbody>
            </table>
        </div>
        
        <div class="pagination">
            <button class="prev" onclick="changePage('destinations', -1)">◄</button>
            <span id="destinations-page-info">Page 1</span>
            <button class="next" onclick="changePage('destinations', 1)">►</button>
        </div>
    </div>
    
    <!-- Clients Tab -->
    <div class="tab-content" id="clients-tab">
        <div class="table-controls">
            <button class="add-btn" onclick="openModal('clients')"><i class="fa-solid fa-plus"></i> Add Client</button>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="clients-search" placeholder="Search clients..." oninput="searchTable('clients')">
            </div>
        </div>
        
        <div class="table-container">
            <table id="clients-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Last Modified By</th>
                        <th>Last Modified At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="clients-table-body"></tbody>
            </table>
        </div>
        
        <div class="pagination">
            <button class="prev" onclick="changePage('clients', -1)">◄</button>
            <span id="clients-page-info">Page 1</span>
            <button class="next" onclick="changePage('clients', 1)">►</button>
        </div>
    </div>
    
    <!-- Shipping Lines Tab -->
    <div class="tab-content" id="shipping-lines-tab">
        <div class="table-controls">
            <button class="add-btn" onclick="openModal('shipping-lines')"><i class="fa-solid fa-plus"></i> Add Shipping Line</button>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="shipping-lines-search" placeholder="Search shipping lines..." oninput="searchTable('shipping-lines')">
            </div>
        </div>
        
        <div class="table-container">
            <table id="shipping-lines-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Last Modified By</th>
                        <th>Last Modified At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="shipping-lines-table-body"></tbody>
            </table>
        </div>
        
        <div class="pagination">
            <button class="prev" onclick="changePage('shipping-lines', -1)">◄</button>
            <span id="shipping-lines-page-info">Page 1</span>
            <button class="next" onclick="changePage('shipping-lines', 1)">►</button>
        </div>
    </div>
    
    <!-- Consignees Tab -->
    <div class="tab-content" id="consignees-tab">
        <div class="table-controls">
            <button class="add-btn" onclick="openModal('consignees')"><i class="fa-solid fa-plus"></i> Add Consignee</button>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="consignees-search" placeholder="Search consignees..." oninput="searchTable('consignees')">
            </div>
        </div>
        
        <div class="table-container">
            <table id="consignees-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Last Modified By</th>
                        <th>Last Modified At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="consignees-table-body"></tbody>
            </table>
        </div>
        
        <div class="pagination">
            <button class="prev" onclick="changePage('consignees', -1)">◄</button>
            <span id="consignees-page-info">Page 1</span>
            <button class="next" onclick="changePage('consignees', 1)">►</button>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit -->
<div id="infoModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('infoModal')">&times;</span>
        <h2 id="modalTitle">Add Item</h2>
        <input type="hidden" id="itemId">
        <input type="hidden" id="itemType">
        
        <div class="form-group">
            <label for="itemName">Name</label>
            <input type="text" id="itemName" class="form-control" required>
        </div>
        
        <div class="button-group">
            <button type="button" class="save-btn" onclick="saveItem()">Save</button>
            <button type="button" class="cancel-btn" onclick="closeModal('infoModal')">Cancel</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="width: 40%;">
        <span class="close" onclick="closeModal('deleteModal')">&times;</span>
        <h2>Delete Item</h2>
        <input type="hidden" id="deleteItemId">
        <input type="hidden" id="deleteItemType">
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

<!-- Reason View Modal -->
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
    // Global variables
    let currentTab = 'dispatchers';
    let data = {
        dispatchers: { items: [], currentPage: 1, rowsPerPage: 5, searchTerm: '' },
        destinations: { items: [], currentPage: 1, rowsPerPage: 5, searchTerm: '' },
        clients: { items: [], currentPage: 1, rowsPerPage: 5, searchTerm: '' },
        'shipping-lines': { items: [], currentPage: 1, rowsPerPage: 5, searchTerm: '' },
        consignees: { items: [], currentPage: 1, rowsPerPage: 5, searchTerm: '' }
    };
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        setupTabs();
        fetchAllData();
        updateDateTime();
        setInterval(updateDateTime, 1000);
        
        // Set up sidebar active state
        const currentPage = window.location.pathname.split('/').pop();
        const sidebarLinks = document.querySelectorAll('.sidebar-item a');
        
        sidebarLinks.forEach(link => {
            const linkPage = link.getAttribute('href').split('/').pop();
            if (linkPage === currentPage) {
                link.parentElement.classList.add('active');
                const icon = link.parentElement.querySelector('.icon2');
                if (icon) icon.style.color = 'white';
            }
        });
    });
    
    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
        document.getElementById('current-time').textContent = now.toLocaleTimeString();
    }
    
    function setupTabs() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                
                // Update active button
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Update active content
                tabContents.forEach(content => content.classList.remove('active'));
                document.getElementById(`${tabId}-tab`).classList.add('active');
                
                currentTab = tabId;
            });
        });
    }
    
    function fetchAllData() {
        const types = ['dispatchers', 'destinations', 'clients', 'shipping-lines', 'consignees'];
        types.forEach(type => {
            fetchData(type);
        });
    }
    
    function fetchData(type) {
        fetch(`include/handlers/info_management_handler.php?action=get${capitalizeFirstLetter(type)}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    data[type].items = result.data;
                    renderTable(type);
                } else {
                    console.error(`Error fetching ${type}:`, result.message);
                }
            })
            .catch(error => console.error(`Error fetching ${type}:`, error));
    }
    
    function formatDateTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';
        
        const date = new Date(dateTimeString);
        if (isNaN(date.getTime())) return 'Invalid Date';
        
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }
    
    function renderTable(type) {
        const tableBody = document.getElementById(`${type}-table-body`);
        const pageInfo = document.getElementById(`${type}-page-info`);
        const searchTerm = data[type].searchTerm.toLowerCase();
        
        // Filter items based on search term
        let filteredItems = data[type].items.filter(item => 
            item.name.toLowerCase().includes(searchTerm)
        );
        
        // Calculate pagination
        const totalPages = Math.ceil(filteredItems.length / data[type].rowsPerPage);
        if (data[type].currentPage > totalPages && totalPages > 0) {
            data[type].currentPage = totalPages;
        } else if (totalPages === 0) {
            data[type].currentPage = 1;
        }
        
        const startIndex = (data[type].currentPage - 1) * data[type].rowsPerPage;
        const endIndex = Math.min(startIndex + data[type].rowsPerPage, filteredItems.length);
        const pageItems = filteredItems.slice(startIndex, endIndex);
        
        // Update table
        tableBody.innerHTML = '';
        
        if (pageItems.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="4" style="text-align: center;">No ${type} found</td></tr>`;
        } else {
            pageItems.forEach(item => {
                const tr = document.createElement('tr');
                
                // Highlight search matches
                let nameDisplay = item.name;
                if (searchTerm) {
                    const regex = new RegExp(searchTerm, 'gi');
                    nameDisplay = item.name.replace(regex, match => `<span class="highlight">${match}</span>`);
                }
                
                tr.innerHTML = `
                    <td>${nameDisplay}</td>
                    <td>${item.last_modified_by || 'System'}</td>
                    <td>${formatDateTime(item.last_modified_at)}</td>
                    <td class="actions">
                        <div class="dropdown">
                            <button class="dropdown-btn" data-tooltip="Actions">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-content">
                                ${item.is_deleted == 1 ? `
                                    <button class="dropdown-item restore" data-tooltip="Restore" onclick="restoreItem('${type}', ${item[`${type.slice(0, -1)}_id`]})">
                                        <i class="fas fa-trash-restore"></i> Restore
                                    </button>
                                    <button class="dropdown-item view-reason" data-tooltip="View Reason" onclick="viewDeletionReason('${type}', ${item[`${type.slice(0, -1)}_id`]})">
                                        <i class="fas fa-info-circle"></i> View Reason
                                    </button>
                                    ${window.userRole === 'Full Admin' ? 
                                        `<button class="dropdown-item full-delete" data-tooltip="Permanently Delete" onclick="fullDeleteItem('${type}', ${item[`${type.slice(0, -1)}_id`]})">
                                            <i class="fa-solid fa-ban"></i> Permanent Delete
                                        </button>` : ''}
                                ` : `
                                    <button class="dropdown-item edit" data-tooltip="Edit" onclick="editItem('${type}', ${item[`${type.slice(0, -1)}_id`]})">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="dropdown-item delete" data-tooltip="Delete" onclick="deleteItem('${type}', ${item[`${type.slice(0, -1)}_id`]})">
                                        <i class='fas fa-trash-alt'></i> Delete
                                    </button>
                                `}
                            </div>
                        </div>
                    </td>
                `;
                tableBody.appendChild(tr);
            });
        }
        
        // Update pagination info
        if (pageInfo) {
            pageInfo.textContent = `Page ${data[type].currentPage} of ${totalPages || 1}`;
        }
        
        // Set up dropdown functionality
        setupDropdowns();
    }
    
    function setupDropdowns() {
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
    }
    
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
    
    function searchTable(type) {
        data[type].searchTerm = document.getElementById(`${type}-search`).value;
        data[type].currentPage = 1;
        renderTable(type);
    }
    
    function changePage(type, direction) {
        data[type].currentPage += direction;
        const totalPages = Math.ceil(data[type].items.length / data[type].rowsPerPage);
        
        if (data[type].currentPage < 1) data[type].currentPage = 1;
        if (data[type].currentPage > totalPages) data[type].currentPage = totalPages;
        
        renderTable(type);
    }
    
    function openModal(type, id = null) {
        document.getElementById('itemType').value = type;
        
        if (id) {
            // Edit mode
            document.getElementById('modalTitle').textContent = `Edit ${capitalizeFirstLetter(type.slice(0, -1))}`;
            document.getElementById('itemId').value = id;
            
            const item = data[type].items.find(item => item[`${type.slice(0, -1)}_id`] == id);
            if (item) {
                document.getElementById('itemName').value = item.name;
            }
        } else {
            // Add mode
            document.getElementById('modalTitle').textContent = `Add ${capitalizeFirstLetter(type.slice(0, -1))}`;
            document.getElementById('itemId').value = '';
            document.getElementById('itemName').value = '';
        }
        
        document.getElementById('infoModal').style.display = 'block';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    function saveItem() {
        const type = document.getElementById('itemType').value;
        const id = document.getElementById('itemId').value;
        const name = document.getElementById('itemName').value;
        
        if (!name) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please enter a name'
            });
            return;
        }
        
        const action = id ? 'update' : 'add';
        const formData = new FormData();
        formData.append('action', `${action}${capitalizeFirstLetter(type.slice(0, -1))}`);
        formData.append('id', id);
        formData.append('name', name);
        
        fetch('include/handlers/info_management_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: result.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    closeModal('infoModal');
                    fetchData(type);
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred. Please try again.'
            });
        });
    }
    
    function editItem(type, id) {
        openModal(type, id);
    }
    
    function deleteItem(type, id) {
        document.getElementById('deleteItemId').value = id;
        document.getElementById('deleteItemType').value = type;
        document.getElementById('deleteReason').value = '';
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    function performSoftDelete() {
        const id = document.getElementById('deleteItemId').value;
        const type = document.getElementById('deleteItemType').value;
        const reason = document.getElementById('deleteReason').value;
        
        if (!reason) {
            Swal.fire({
                icon: 'error',
                title: 'Reason Required',
                text: 'Please provide a reason for deletion'
            });
            return;
        }
        
        const formData = new FormData();
        formData.append('action', `softDelete${capitalizeFirstLetter(type.slice(0, -1))}`);
        formData.append('id', id);
        formData.append('reason', reason);
        
        fetch('include/handlers/info_management_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: result.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    closeModal('deleteModal');
                    fetchData(type);
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred. Please try again.'
            });
        });
    }
    
    function viewDeletionReason(type, id) {
        const item = data[type].items.find(item => item[`${type.slice(0, -1)}_id`] == id);
        if (item) {
            document.getElementById('deletionReasonText').textContent = 
                item.delete_reason || 'No reason provided';
            document.getElementById('reasonModal').style.display = 'block';
        }
    }
    
    function restoreItem(type, id) {
        Swal.fire({
            title: 'Restore Item',
            text: 'Are you sure you want to restore this item?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, restore it!',
            cancelButtonText: 'No, keep it',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            customClass: {
                confirmButton: 'btn-confirm',
                cancelButton: 'btn-cancel'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', `restore${capitalizeFirstLetter(type.slice(0, -1))}`);
                formData.append('id', id);
                
                fetch('include/handlers/info_management_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            fetchData(type);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                });
            }
        });
    }
    
    function fullDeleteItem(type, id) {
        Swal.fire({
            title: 'Permanent Deletion',
            html: '<strong>Are you sure you want to PERMANENTLY delete this item?</strong><br><br>This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete permanently!',
            cancelButtonText: 'Cancel',
            backdrop: 'rgba(0,0,0,0.8)',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', `fullDelete${capitalizeFirstLetter(type.slice(0, -1))}`);
                formData.append('id', id);
                
                fetch('include/handlers/info_management_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            fetchData(type);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                });
            }
        });
    }
    
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    document.getElementById('toggleSidebarBtn').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('expanded');
});

const currentPage = window.location.pathname.split('/').pop();
    const sidebarLinks = document.querySelectorAll('.sidebar-item a');
    
    sidebarLinks.forEach(link => {
        const linkPage = link.getAttribute('href').split('/').pop();
        if (linkPage === currentPage) {
            link.parentElement.classList.add('active');
            const icon = link.parentElement.querySelector('.icon2');
            if (icon) icon.style.color = 'white';
        }
    });
</script>

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
    if (!this.loadingEl) return;
    this.titleEl.textContent = title;
    this.messageEl.textContent = message;
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
    if (this.progressBar) this.progressBar.style.width = `${percent}%`;
    if (this.progressText) this.progressText.textContent = `${Math.round(percent)}%`;
  },
  
  setupNavigationInterception() {
    document.addEventListener('click', (e) => {
      if (e.target.closest('.swal2-container, .swal2-popup, .swal2-modal, .modal, .modal-content, .fc-event, #calendar')) return;
      
      const link = e.target.closest('a');
      if (link && !link.hasAttribute('data-no-loading') && link.href && 
          !link.href.startsWith('javascript:') && !link.href.startsWith('#') && 
          !link.href.startsWith('mailto:') && !link.href.startsWith('tel:')) {
        
        try {
          const linkUrl = new URL(link.href);
          const currentUrl = new URL(window.location.href);
          if (linkUrl.origin !== currentUrl.origin) return;
          if (linkUrl.pathname === currentUrl.pathname) return;
        } catch (e) { return; }
        
        e.preventDefault();
        sessionStorage.setItem('showAdminLoading', 'true');
        
        const loading = this.startAction('Loading Page', `Preparing ${link.textContent.trim() || 'page'}...`);
        let progress = 0;
        const progressInterval = setInterval(() => {
          progress += Math.random() * 15 + 8;
          if (progress >= 85) {
            clearInterval(progressInterval);
            progress = 90;
          }
          loading.updateProgress(Math.min(progress, 90));
        }, 150);
        
        setTimeout(() => {
          loading.updateProgress(100);
          setTimeout(() => {
            window.location.href = link.href;
          }, 300);
        }, 1200);
      }
    });

    document.addEventListener('submit', (e) => {
      if (e.target.closest('.swal2-container, .swal2-popup, .modal')) return;
      const form = e.target;
      if (form.method && form.method.toLowerCase() === 'post' && form.action) {
        const loading = this.startAction('Submitting Form', 'Processing your data...');
        setTimeout(() => loading.complete(), 2000);
      }
    });
    
    window.addEventListener('popstate', () => {
      this.show('Loading Page', 'Loading previous page...');
      setTimeout(() => this.hide(), 800);
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
  
  showManual: function(title, message) { this.show(title, message); },
  hideManual: function() { this.hide(); },
  setProgress: function(percent) { this.updateProgress(percent); }
};

document.addEventListener('DOMContentLoaded', () => {
  AdminLoading.init();
  const loadingGif = document.querySelector('.loading-gif');
  if (loadingGif) loadingGif.style.transition = 'opacity 0.7s ease 0.3s';
  
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) setTimeout(() => AdminLoading.hideManual(), 500);
  });
});
</script>

<footer class="site-footer">
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
    </div>
</footer>
</body>
</html>