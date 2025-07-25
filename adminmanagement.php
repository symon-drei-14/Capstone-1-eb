<?php
session_start();
require_once __DIR__ . '/include/check_access.php';
checkAccess();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <link rel="stylesheet" href="include/sidenav.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="include/fleetmanagement.css">
</head>
<style>
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

.main-content4 {
    margin-top: 30px;
    margin-left: 50px;
    margin-right: 10px;
    width: calc(100% - 100px);
}
.deleted-row {
    background-color: #f9f9f9ff;
    color: #5f5f5fff;
}



.filter-controls {
    margin-top: 10px;

    padding: 10px;

    border-radius: 4px;
}

button.restore {
    background-color: #4CAF50;
    color: white;
}

button.restore:hover {
    background-color: #45a049;
}

.deleted-only {
    display: none;
}

.show-deleted .deleted-only {
    display: table-cell;
}

.show-deleted .non-deleted-row {
    display: none;
}

.deleted-row {
    background-color: #ffffffff;
 
}

/* .deleted-row td {
    opacity: 0.9;
} */

    .search-container {
    position: relative;
    display: inline-block;
}

.search-container input {
    padding: 8px 10px 8px 30px; 
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.search-container .fa-search {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.highlight {
    background-color: yellow;
    font-weight: bold;
    padding: 0 2px;
    border-radius: 2px;
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

    <div class="main-content4">
        <section class="content-2">
            <div class="container">
                <div class="button-row">
                    <button class="add_trip" onclick="openAdminModal()">Add Admin</button>
                </div>
                <div class="filter-controls">
    <label>
        <input type="checkbox" id="showDeletedCheckbox" onchange="toggleDeletedAdmins()">
        Show Deleted Admins
    </label>

     <div class="search-container" style="float: left; margin-top: 10px;">
          <i class="fas fa-search"></i>
        <input type="text" id="adminSearch" placeholder="Search admins..." onkeyup="searchAdmins()">
      
    </div>
</div>

                <br />
                <h3>List of Admins</h3>
                <div class="table-container">
                    <table id="adminsTable">
                        <thead>
                            <tr>
                                <th>Admin ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="deleted-only">Deleted By</th>
                                <th class="deleted-only">Deleted At</th>
                                <th class="deleted-only">Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="adminTableBody">
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="pagination2">
                <button class="prev" onclick="changeAdminPage(-1)">◄</button>
                <span id="admin-page-info">Page 1</span>
                <button class="next" onclick="changeAdminPage(1)">►</button>
            </div>
        </section>
    </div>

    <!-- Modal for Add/Edit Admin -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('adminModal')">&times;</span>
            <h2 id="modalTitle">Add Admin</h2>
           
            <input type="hidden" id="adminId" name="adminId">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="Full Admin">Full Admin</option>
                    <option value="Operations Manager">Operations Manager</option>
                    <option value="Fleet Manager">Fleet Manager</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password" id="passwordLabel">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small id="passwordHelp" style="display: none; color: #666;">Leave blank to keep current password</small>
            </div>

            <div class="button-group">
                <button type="button" class="save-btn" onclick="saveAdmin()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('adminModal')">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        function openAdminModal(adminId = null) {
            // Reset form
            document.getElementById('adminId').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('role').value = 'Full Admin';
            document.getElementById('passwordHelp').style.display = 'none';
            document.getElementById('password').required = true;
            
            if (adminId) {
                // Edit mode
                document.getElementById('modalTitle').textContent = 'Edit Admin';
                document.getElementById('passwordHelp').style.display = 'block';
                document.getElementById('password').required = false;
                fetchAdminDetails(adminId);
            } else {
                // Add mode
                document.getElementById('modalTitle').textContent = 'Add Admin';
            }
            
            openModal('adminModal');
        }

        // Fetch and display admins from the database
        function fetchAdmins() {
            fetch('include/handlers/get_admins.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAdminsTable(data.admins);
                    } else {
                        alert('Error fetching admins: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Fetch single admin details for editing
        function fetchAdminDetails(adminId) {
            fetch(`include/handlers/get_admin.php?id=${adminId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('adminId').value = data.admin.admin_id;
                        document.getElementById('username').value = data.admin.username;
                        document.getElementById('role').value = data.admin.role || 'Full Admin';
                        // Password is not fetched for security reasons
                    } else {
                        alert('Error fetching admin details: ' + data.message);
                        closeModal('adminModal');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Render admins table
function renderAdminsTable(admins, isSearchResult = false) {
    const tableBody = document.getElementById('adminTableBody');
    tableBody.innerHTML = '';
    
    const showDeleted = document.getElementById('showDeletedCheckbox').checked;
    const searchTerm = document.getElementById('adminSearch').value.toLowerCase();
    
    // Function to highlight matching text
    const highlightText = (text) => {
        if (!searchTerm || !text) return text;
        
        const str = String(text);
        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return str.replace(regex, '<span class="highlight">$1</span>');
    };
    
    admins.forEach(admin => {
        const row = document.createElement('tr');
        
        if (admin.is_deleted) {
            row.classList.add('deleted-row');
        } else {
            row.classList.add('non-deleted-row');
            if (showDeleted) {
                row.style.display = 'none';
            }
        }
        
        // Format deleted_at date if it exists
        const deletedAt = admin.deleted_at ? new Date(admin.deleted_at).toLocaleString() : '';
        
        row.innerHTML = `
            <td>${highlightText(admin.admin_id)}</td>
            <td>${highlightText(admin.username)}</td>
            <td>${highlightText(admin.role || 'Full Admin')}</td>
            <td>${highlightText(admin.is_deleted ? 'Deleted' : 'Active')}</td>
            <td class="deleted-only">${highlightText(admin.deleted_by || '')}</td>
            <td class="deleted-only">${highlightText(deletedAt)}</td>
            <td class="deleted-only">${highlightText(admin.delete_reason || '')}</td>
            <td class="actions">
                ${admin.is_deleted ? '' : `<button class="edit" onclick="openAdminModal(${admin.admin_id})">Edit</button>`}
                ${admin.is_deleted ? '' : `<button class="delete" onclick="confirmDeleteAdmin(${admin.admin_id})">Delete</button>`}
                ${admin.is_deleted ? `<button class="restore" onclick="restoreAdmin(${admin.admin_id})">Restore</button>` : ''}
            </td>
        `;
        tableBody.appendChild(row);
    });
    
    if (!isSearchResult) {
        const totalPages = Math.ceil(totalAdmins / rowsPerPage);
        document.getElementById("admin-page-info").textContent = `Page ${currentAdminPage} of ${totalPages || 1}`;
    }
}


function confirmDeleteAdmin(adminId) {
    const reason = prompt('Please enter the reason for deleting this admin:');
    if (reason === null) return; 
    
    if (reason.trim() === '') {
        alert('Please provide a reason for deletion');
        return;
    }
    
    if (confirm('Are you sure you want to delete this admin?')) {
        deleteAdmin(adminId, reason);
    }
}
        // Save admin (create or update)
        function saveAdmin() {
            const adminId = document.getElementById('adminId').value;
            const username = document.getElementById('username').value;
            const role = document.getElementById('role').value;
            const password = document.getElementById('password').value;
            
            if (!username) {
                alert('Username is required');
                return;
            }
            
            if (!adminId && !password) {
                alert('Password is required for new admins');
                return;
            }
            
            const adminData = {
                admin_id: adminId,
                username: username,
                role: role,
                password: password
            };
            
            const url = adminId ? 'include/handlers/update_admin.php' : 'include/handlers/add_admin.php';
            
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(adminData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(adminId ? 'Admin updated successfully!' : 'Admin added successfully!');
                    closeModal('adminModal');
                    fetchAdminsPaginated();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

      function deleteAdmin(adminId, reason) {
    fetch('include/handlers/delete_admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            admin_id: adminId,
            delete_reason: reason 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Admin deleted successfully!');
            fetchAdminsPaginated(document.getElementById('showDeletedCheckbox').checked);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Add restore function
function restoreAdmin(adminId) {
    if (confirm('Are you sure you want to restore this admin?')) {
        fetch('include/handlers/restore_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_id: adminId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Admin restored successfully!');
                fetchAdminsPaginated(document.getElementById('showDeletedCheckbox').checked);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}


        // Pagination variables
        let currentAdminPage = 1;
        const rowsPerPage = 5;
        let totalAdmins = 0;

        // Change admin page
        function changeAdminPage(direction) {
            const totalPages = Math.ceil(totalAdmins / rowsPerPage);
            currentAdminPage += direction;

            if (currentAdminPage < 1) {
                currentAdminPage = 1;
            } else if (currentAdminPage > totalPages) {
                currentAdminPage = totalPages;
            }

            fetchAdminsPaginated();
        }

   function toggleDeletedAdmins() {
    const showDeleted = document.getElementById('showDeletedCheckbox').checked;
    const table = document.getElementById('adminsTable');
    
    fetchAdminsPaginated(showDeleted);
    
    if (showDeleted) {
        table.classList.add('show-deleted');
    } else {
        table.classList.remove('show-deleted');
    }
}

        // Fetch admins with pagination
function fetchAdminsPaginated(showDeleted = false) {
    fetch(`include/handlers/get_admins.php?page=${currentAdminPage}&limit=${rowsPerPage}&show_deleted=${showDeleted}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderAdminsTable(data.admins);
                totalAdmins = data.total;
                
                const totalPages = Math.ceil(totalAdmins / rowsPerPage);
                document.getElementById("admin-page-info").textContent = `Page ${currentAdminPage} of ${totalPages || 1}`;
                
                // Update the checkbox state to match the current view
                document.getElementById('showDeletedCheckbox').checked = showDeleted;
            } else {
                alert('Error fetching admins: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to fetch admins. Please try again.');
        });
}
function searchAdmins() {
    const searchTerm = document.getElementById('adminSearch').value.toLowerCase();
    const showDeleted = document.getElementById('showDeletedCheckbox').checked;
    
    if (searchTerm === '') {

          document.querySelectorAll('.highlight').forEach(el => {
            el.outerHTML = el.innerHTML;
        });
       
        fetchAdminsPaginated(showDeleted);
        return;
    }

    fetch('include/handlers/get_admins.php?show_deleted=' + showDeleted)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const filteredAdmins = data.admins.filter(admin => {
                    return (
                        String(admin.admin_id).toLowerCase().includes(searchTerm) ||
                        String(admin.username).toLowerCase().includes(searchTerm) ||
                        String(admin.role || 'Full Admin').toLowerCase().includes(searchTerm) ||
                        String(admin.is_deleted ? 'Deleted' : 'Active').toLowerCase().includes(searchTerm) ||
                        String(admin.deleted_by || '').toLowerCase().includes(searchTerm) ||
                        String(admin.delete_reason || '').toLowerCase().includes(searchTerm) ||
                        (admin.deleted_at && new Date(admin.deleted_at).toLocaleString().toLowerCase().includes(searchTerm))
        )});
                
                renderAdminsTable(filteredAdmins, true);
                
                // Update pagination info to show filtered results
                document.getElementById("admin-page-info").textContent = 
                    `Showing ${filteredAdmins.length} result(s)`;
            } else {
                alert('Error fetching admins: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
}
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        };

        // Initialize when page loads
        window.onload = function() {
            // Add this to your window.onload or DOMContentLoaded
document.getElementById('showDeletedCheckbox').addEventListener('change', toggleDeletedAdmins);
            fetchAdminsPaginated();
        };

        

        
    </script>

     <script>
    document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('expanded');
    });
</script>
</body>
</html>