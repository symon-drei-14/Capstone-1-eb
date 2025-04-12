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
    <title>Admin Management</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/fleetmanagement.css">
</head>
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

    <div class="main-content4">
        <section class="content-2">
            <div class="container">
                <div class="button-row">
                    <button class="add_trip" onclick="openAdminModal()">Add Admin</button>
                </div>
                <br />
                <h3>List of Admins</h3>
                <div class="table-container">
                    <table id="adminsTable">
                        <thead>
                            <tr>
                                <th>Admin ID</th>
                                <th>Username</th>
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
                        // Password is not fetched for security reasons
                    } else {
                        alert('Error fetching admin details: ' + data.message);
                        closeModal('adminModal');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Render admins table
        function renderAdminsTable(admins) {
            const tableBody = document.getElementById('adminTableBody');
            tableBody.innerHTML = '';
            
            admins.forEach(admin => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${admin.admin_id}</td>
                    <td>${admin.username}</td>
                    <td class="actions">
                        <button class="edit" onclick="openAdminModal(${admin.admin_id})">Edit</button>
                        <button class="delete" onclick="deleteAdmin(${admin.admin_id})">Delete</button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        // Save admin (create or update)
        function saveAdmin() {
            const adminId = document.getElementById('adminId').value;
            const username = document.getElementById('username').value;
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

        // Delete admin
        function deleteAdmin(adminId) {
            if (confirm('Are you sure you want to delete this admin?')) {
                fetch('include/handlers/delete_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_id: adminId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Admin deleted successfully!');
                        fetchAdminsPaginated();
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

        // Fetch admins with pagination
        function fetchAdminsPaginated() {
            fetch(`include/handlers/get_admins.php?page=${currentAdminPage}&limit=${rowsPerPage}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAdminsTable(data.admins);
                        totalAdmins = data.total;
                        
                        const totalPages = Math.ceil(totalAdmins / rowsPerPage);
                        document.getElementById("admin-page-info").textContent = `Page ${currentAdminPage} of ${totalPages || 1}`;
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
            fetchAdminsPaginated();
        };
    </script>
</body>
</html>