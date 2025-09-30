<?php

require_once __DIR__ . '/include/check_access.php';
checkAccess();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <link rel="stylesheet" href="include/css/sidenav.css">
    <link rel="stylesheet" href="include/css/loading.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="include/css/adminmanagement.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

 <h3><i class="fa-solid fa-truck"></i>Admin Management</h3>
 
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

    <div class="main-content4">
        <section class="content-2">
            <div class="container">
                <div class="button-row">
                    <button class="add_trip" onclick="openAdminModal()">
                    <i class="fas fa-plus"></i> Add Admin
                    </button>
                </div>
               <div class="filter-controls">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="adminSearch" placeholder="Search admins..." onkeyup="searchAdmins()">
                    </div>
                    <label class="checkbox-container">
                        <input type="checkbox" id="showDeletedCheckbox" onchange="toggleDeletedAdmins()">
                        <span class="checkmark"></span>
                        Show Deleted Admins
                    </label>
                </div>

                <div id="activeAdminsSection">
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
                    <br />
                    <div class="table-container">
                        <table id="adminsTable">
                            <thead>
                                <tr>
                                     <th>Admin ID</th>
                                    <th>Profile Picture</th>
                                   
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th class="deleted-only">Deleted By</th>
                                    <th class="deleted-only">Deleted At</th>
                                    <th class="deleted-only">Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminTableBody"></tbody>
                        </table>
                    </div>
                    <div class="pagination2" id="adminPagination">
                        </div>
                </div>

                <div id="deletedAdminsSection">
                   
                    <div class="table-controls">
                        <div class="table-info" id="deletedShowingInfo"></div>
                         <div class="rows-per-page-container">
                            <label for="deletedRowsPerPage">Rows per page:</label>
                            <select id="deletedRowsPerPage" onchange="changeRowsPerPage()">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                    </div>
                    <br />
                    <div class="table-container">
                        <table id="deletedAdminsTable">
                           <thead>
                                <tr>
                                    <th>Profile Picture</th>
                                    <th>Username</th>
                                    <th class="deleted-only">Deleted By</th>
                                    <th class="deleted-only">Deleted At</th>
                                    <th class="deleted-only">Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="deletedAdminTableBody"></tbody>
                        </table>
                    </div>
                    <div class="pagination2" id="deletedAdminPagination">
                        </div>
                </div>

            </div>
        </section>
    </div>

    <div id="adminModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('adminModal')">&times;</span>
        <h2 id="modalTitle">Add Admin</h2>
        
        <form id="adminForm">
            <input type="hidden" id="adminId" name="adminId">

            <div class="form-group">
                <label for="adminProfile">Profile Photo (Max 2MB)</label>
                <input type="file" id="adminProfile" name="adminProfile" accept="image/*">
                <div id="adminProfilePreview" style="margin-top: 10px;"></div>
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
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

            <div class="form-group" id="oldPasswordGroup" style="display: none;">
                <label for="oldPassword">Current Password</label>
                <input type="password" id="oldPassword" name="oldPassword" class="form-control">
                <small>Required only if you are setting a new password.</small>
            </div>

            <div class="form-group">
                <label for="password" id="passwordLabel">Password *</label>
                <input type="password" id="password" name="password" class="form-control">
                <small id="passwordHelp" style="display: none;">Leave blank to keep current password.</small>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm Password *</label>
                <input type="password" id="confirmPassword" name="confirmPassword" class="form-control">
            </div>

            <div class="button-group">
                <button type="button" class="save-btn" onclick="saveAdmin()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('adminModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

    <script>
        // State variables for pagination
        let currentAdminPage = 1;
        let activeRowsPerPage = 5;
        let totalAdmins = 0;

        let currentDeletedPage = 1;
        let deletedRowsPerPage = 5;
        let totalDeletedAdmins = 0;

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        function openAdminModal(adminId = null) {
            document.getElementById('adminForm').reset();
            const modalTitle = document.getElementById('modalTitle');
            const passwordHelp = document.getElementById('passwordHelp');
            const oldPasswordGroup = document.getElementById('oldPasswordGroup');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const passwordLabel = document.getElementById('passwordLabel');
            const profilePreview = document.getElementById('adminProfilePreview');
            profilePreview.innerHTML = '';

            if (adminId) {
                modalTitle.textContent = 'Edit Admin';
                passwordHelp.style.display = 'block';
                oldPasswordGroup.style.display = 'block';
                passwordInput.required = false;
                confirmPasswordInput.required = false;
                passwordLabel.textContent = "New Password";
                fetchAdminDetails(adminId);
            } else {
                modalTitle.textContent = 'Add Admin';
                passwordHelp.style.display = 'none';
                oldPasswordGroup.style.display = 'none';
                passwordInput.required = true;
                confirmPasswordInput.required = true;
                passwordLabel.textContent = "Password *";
                profilePreview.innerHTML = '<p>Upload a profile picture </p>';
                document.getElementById('adminProfile').value = '';
            }
            openModal('adminModal');
        }

       function fetchAdminDetails(adminId) {
            fetch(`include/handlers/get_admin.php?id=${adminId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const admin = data.admin;
                        document.getElementById('adminId').value = admin.admin_id;
                        document.getElementById('username').value = admin.username;
                        document.getElementById('role').value = admin.role || 'Full Admin';
                        const profilePreview = document.getElementById('adminProfilePreview');
                        if (admin.admin_pic) {
                            profilePreview.innerHTML = `
                                <div class="current-profile-section">
                                    <h4>Current Profile Picture:</h4>
                                    <div class="large-profile-display">
                                        <img src="data:image/jpeg;base64,${admin.admin_pic}" class="large-profile-preview" alt="Current Admin Photo">
                                    </div>
                                </div>`;
                        } else {
                            profilePreview.innerHTML = `
                                <div class="current-profile-section">
                                    <h4>Current Profile Picture:</h4>
                                    <div class="large-profile-display">
                                        <i class="fa-solid fa-circle-user large-profile-icon"></i>
                                        <p>No profile picture uploaded</p>
                                    </div>
                                </div>`;
                        }
                        document.getElementById('adminProfile').value = '';
                    } else {
                        Swal.fire('Error', `Error fetching admin details: ${data.message}`, 'error');
                        closeModal('adminModal');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function renderAdminsTable(admins, tableBodyId) {
    const tableBody = document.getElementById(tableBodyId);
    tableBody.innerHTML = '';
    const searchTerm = document.getElementById('adminSearch').value;

    const highlightText = (text) => {
        if (!searchTerm.trim() || !text) {
            return text;
        }
        const str = String(text);
        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return str.replace(regex, '<span class="highlight">$1</span>');
    };

    admins.forEach(admin => {
        const row = document.createElement('tr');
        const deletedAt = admin.deleted_at ? new Date(admin.deleted_at).toLocaleString() : '';

        // Added this line to handle potential quotes in the reason
        const deleteReason = (admin.delete_reason || '').replace(/'/g, "\\'").replace(/"/g, '\\"');

        if (tableBodyId === 'deletedAdminTableBody') {
            // Template for the deleted admins table
            row.innerHTML = `
                <td data-label="Profile">
                    ${admin.admin_pic ? 
                        '<img src="data:image/jpeg;base64,' + admin.admin_pic + '" class="admin-photo">' : 
                        '<i class="fa-solid fa-circle-user admin-profile-icon"></i>'
                    }
                </td>
                <td data-label="Username">${highlightText(admin.username)}</td>
                <td class="deleted-only" data-label="Deleted By">${highlightText(admin.deleted_by || '')}</td>
                <td class="deleted-only" data-label="Deleted At">${highlightText(deletedAt)}</td>
                <td class="deleted-only" data-label="Reason">${highlightText(admin.delete_reason || '')}</td>
                <td data-label="Actions" class="actions">
                    <div class="dropdown">
                        <button class="dropdown-btn" data-tooltip="Actions"><i class="fas fa-ellipsis-v"></i></button>
                        <div class="dropdown-content">
                            <button class="dropdown-item restore" onclick="restoreAdmin(${admin.admin_id}, '${deleteReason}')"><i class="fas fa-trash-restore"></i>Restore</button>
                            <button class="dropdown-item full-delete" onclick="fullDeleteAdmin(${admin.admin_id})"><i class="fa-solid fa-ban"></i>Full Delete</button>
                        </div>
                    </div>
                </td>`;
        } else {
            // Original template for the active admins table
            row.innerHTML = `
               <td data-label="Admin ID">${highlightText(admin.admin_id)}</td>
                <td data-label="Profile">
                    ${admin.admin_pic ? 
                        '<img src="data:image/jpeg;base64,' + admin.admin_pic + '" class="admin-photo">' : 
                        '<i class="fa-solid fa-circle-user admin-profile-icon"></i>'
                    }
                </td>
                
                <td data-label="Username">${highlightText(admin.username)}</td>
                <td data-label="Role">${highlightText(admin.role || 'Full Admin')}</td>
                <td data-label="Status">${highlightText(admin.is_deleted ? 'Deleted' : 'Active')}</td>
                <td class="deleted-only" data-label="Deleted By">${highlightText(admin.deleted_by || '')}</td>
                <td class="deleted-only" data-label="Deleted At">${highlightText(deletedAt)}</td>
                <td class="deleted-only" data-label="Reason">${highlightText(admin.delete_reason || '')}</td>
                <td data-label="Actions" class="actions">
                    <div class="dropdown">
                        <button class="dropdown-btn" data-tooltip="Actions"><i class="fas fa-ellipsis-v"></i></button>
                        <div class="dropdown-content">
                            <button class="dropdown-item edit" onclick="openAdminModal(${admin.admin_id})"><i class="fas fa-edit"></i>Edit</button>
                            <button class="dropdown-item delete" onclick="confirmDeleteAdmin(${admin.admin_id})"><i class="fas fa-trash-alt"></i>Delete</button>
                        </div>
                    </div>
                </td>`;
        }
        tableBody.appendChild(row);
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

        function confirmDeleteAdmin(adminId) {
            Swal.fire({
                title: 'Delete Admin?',
                text: "Please provide a reason for deleting this admin.",
                input: 'textarea',
                inputPlaceholder: 'Enter your reason here...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                preConfirm: (reason) => {
                    if (!reason) {
                        Swal.showValidationMessage('A reason is required to delete an admin.')
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteAdmin(adminId, result.value);
                }
            });
        }

        function deleteAdmin(adminId, reason) {
            fetch('include/handlers/delete_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ admin_id: adminId, reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', 'The admin has been moved to the deleted list.', 'success');
                    fetchAdminsPaginated(false); // Refresh active admins list
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function saveAdmin() {
            const adminId = document.getElementById('adminId').value;
            const username = document.getElementById('username').value;
            const role = document.getElementById('role').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const oldPassword = document.getElementById('oldPassword').value;
            const profileInput = document.getElementById('adminProfile');
            
            if (!username || !role) {
                Swal.fire('Validation Error', 'Username and role are required.', 'warning');
                return;
            }

            if (password !== confirmPassword) {
                Swal.fire('Validation Error', 'New passwords do not match.', 'warning');
                return;
            }
            
            if (adminId && password && !oldPassword) {
                Swal.fire('Validation Error', 'To set a new password, you must enter the current password.', 'warning');
                return;
            }
            
            if (!adminId && !password) {
                Swal.fire('Validation Error', 'Password is required for new admins.', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('admin_id', adminId);
            formData.append('username', username);
            formData.append('role', role);
            formData.append('password', password);
            formData.append('old_password', oldPassword);
            
            if (profileInput.files.length > 0) {
                formData.append('adminProfile', profileInput.files[0]);
            }
            
            const url = adminId ? 'include/handlers/update_admin.php' : 'include/handlers/add_admin.php';
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: `Admin has been ${adminId ? 'updated' : 'added'} successfully.`,
                    }).then(() => {
                        closeModal('adminModal');
                        fetchAdminsPaginated(false); // Refresh active admins
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function restoreAdmin(adminId, reason = '') {
    // This is the special flow for accounts locked due to failed logins
    if (reason === 'Failed Login Attempts') {
        Swal.fire({
            title: 'Password Reset Required',
            text: 'This account was locked. You must set a new password to restore it.',
            icon: 'info',
            html: `
                <input type="password" id="swal-password" class="swal2-input" placeholder="New Password" autocomplete="new-password">
                <input type="password" id="swal-confirm-password" class="swal2-input" placeholder="Confirm New Password" autocomplete="new-password">
            `,
            confirmButtonText: 'Restore & Set Password',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            preConfirm: () => {
                const password = Swal.getPopup().querySelector('#swal-password').value;
                const confirmPassword = Swal.getPopup().querySelector('#swal-confirm-password').value;
                if (!password || !confirmPassword) {
                    Swal.showValidationMessage(`Please enter and confirm the new password.`);
                } else if (password !== confirmPassword) {
                    Swal.showValidationMessage(`The passwords do not match.`);
                }
                return { password: password };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Now we send the request to the backend with the new password
                fetch('include/handlers/restore_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_id: adminId,
                        password: result.value.password // Include the new password
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Restored!', 'The admin account has been restored with a new password.', 'success');
                        fetchAdminsPaginated(true); // Refresh the deleted list
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    } else {
        // This is the original restore flow for normally deleted accounts
        Swal.fire({
            title: 'Restore Admin?',
            text: "Are you sure you want to restore this admin?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, restore it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('include/handlers/restore_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_id: adminId }) // No password needed here
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Restored!', 'The admin has been restored successfully.', 'success');
                        fetchAdminsPaginated(true); // Refresh the deleted list
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    }
}

        function fetchAdminsPaginated(isDeletedView = false) {
            const searchTerm = document.getElementById('adminSearch').value;
            const page = isDeletedView ? currentDeletedPage : currentAdminPage;
            const limit = isDeletedView ? deletedRowsPerPage : activeRowsPerPage;
            
            const url = `include/handlers/get_admins.php?page=${page}&limit=${limit}&show_deleted=${isDeletedView}&search=${encodeURIComponent(searchTerm)}`;    
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (isDeletedView) {
                            renderAdminsTable(data.admins, 'deletedAdminTableBody');
                            totalDeletedAdmins = data.total;
                            updateShowingInfo(data.admins.length, totalDeletedAdmins, true);
                            renderPagination('deletedAdminPagination', currentDeletedPage, totalDeletedAdmins, deletedRowsPerPage, goToDeletedAdminPage);
                        } else {
                            renderAdminsTable(data.admins, 'adminTableBody');
                            totalAdmins = data.total;
                            updateShowingInfo(data.admins.length, totalAdmins, false);
                            renderPagination('adminPagination', currentAdminPage, totalAdmins, activeRowsPerPage, goToAdminPage);
                        }
                    } else {
                        Swal.fire('Error', `Error fetching admins: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to fetch admins. Please try again.', 'error');
                });
        }
        
        function goToAdminPage(page) {
            const totalPages = Math.ceil(totalAdmins / activeRowsPerPage);
            if (page >= 1 && page <= totalPages) {
                currentAdminPage = page;
                fetchAdminsPaginated(false);
            }
        }

        function goToDeletedAdminPage(page) {
            const totalPages = Math.ceil(totalDeletedAdmins / deletedRowsPerPage);
            if (page >= 1 && page <= totalPages) {
                currentDeletedPage = page;
                fetchAdminsPaginated(true);
            }
        }

        function renderPagination(containerId, currentPage, totalItems, itemsPerPage, goToPageFunction) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            const totalPages = Math.ceil(totalItems / itemsPerPage);

            if (totalPages <= 1) return;

            // Previous Button
            const prevButton = document.createElement('button');
            prevButton.innerHTML = '◄';
            prevButton.className = 'prev';
            prevButton.disabled = currentPage === 1;
            prevButton.onclick = () => goToPageFunction(currentPage - 1);
            container.appendChild(prevButton);

            // Page Number Buttons
            for (let i = 1; i <= totalPages; i++) {
                const pageButton = document.createElement('button');
                pageButton.textContent = i;
                if (i === currentPage) {
                    pageButton.className = 'active';
                } else {
                    pageButton.className = 'page-number';
                }
                pageButton.onclick = () => goToPageFunction(i);
                container.appendChild(pageButton);
            }

            // Next Button
            const nextButton = document.createElement('button');
            nextButton.innerHTML = '►';
            nextButton.className = 'next';
            nextButton.disabled = currentPage === totalPages;
            nextButton.onclick = () => goToPageFunction(currentPage + 1);
            container.appendChild(nextButton);
        }

        function searchAdmins() {
            const isDeletedView = document.getElementById('showDeletedCheckbox').checked;
            if (isDeletedView) {
                currentDeletedPage = 1;
            } else {
                currentAdminPage = 1;
            }
            fetchAdminsPaginated(isDeletedView);
        }

        function toggleDeletedAdmins() {
            const showDeleted = document.getElementById('showDeletedCheckbox').checked;
            const activeSection = document.getElementById('activeAdminsSection');
            const deletedSection = document.getElementById('deletedAdminsSection');

            if (showDeleted) {
                activeSection.style.display = 'none';
                deletedSection.style.display = 'block';
                fetchAdminsPaginated(true);
            } else {
                activeSection.style.display = 'block';
                deletedSection.style.display = 'none';
                fetchAdminsPaginated(false);
            }
        }
        
        function fullDeleteAdmin(adminId) {
            Swal.fire({
                title: 'PERMANENTLY DELETE?',
                text: "This action cannot be undone!",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete permanently!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('include/handlers/delete_admin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ admin_id: adminId, full_delete: true })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Permanently Deleted!', 'The admin record has been removed.', 'success');
                            fetchAdminsPaginated(true);
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            });
        }

        function changeRowsPerPage() {
            const isDeletedView = document.getElementById('showDeletedCheckbox').checked;
            if (isDeletedView) {
                deletedRowsPerPage = parseInt(document.getElementById('deletedRowsPerPage').value);
                currentDeletedPage = 1;
                fetchAdminsPaginated(true);
            } else {
                activeRowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
                currentAdminPage = 1;
                fetchAdminsPaginated(false);
            }
        }
        
        function updateShowingInfo(currentCount, total, isDeletedView) {
            const page = isDeletedView ? currentDeletedPage : currentAdminPage;
            const limit = isDeletedView ? deletedRowsPerPage : activeRowsPerPage;
            const infoElementId = isDeletedView ? 'deletedShowingInfo' : 'showingInfo';
            const infoElement = document.getElementById(infoElementId);

            if (total === 0) {
                infoElement.textContent = 'Showing 0 of 0';
                return;
            }
            
            const start = ((page - 1) * limit) + 1;
            const end = Math.min(start + currentCount - 1, total);
            infoElement.textContent = `Showing ${start} to ${end} of ${total}`;
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            // Setup listeners
            document.getElementById('showDeletedCheckbox').addEventListener('change', toggleDeletedAdmins);
            document.getElementById('rowsPerPage').addEventListener('change', changeRowsPerPage);
            document.getElementById('deletedRowsPerPage').addEventListener('change', changeRowsPerPage);
            
            // Set initial rows per page values
            document.getElementById('rowsPerPage').value = activeRowsPerPage;
            document.getElementById('deletedRowsPerPage').value = deletedRowsPerPage;

            // Initial fetch for active admins
            fetchAdminsPaginated(false);

            // Other initializations
            const currentPage = window.location.pathname.split('/').pop();
            const sidebarLinks = document.querySelectorAll('.sidebar-item a');
            sidebarLinks.forEach(link => {
                if (link.getAttribute('href').split('/').pop() === currentPage) {
                    link.parentElement.classList.add('active');
                }
            });

            document.getElementById('adminProfile').addEventListener('change', (e) => {
                 handleProfileImageChange(e, document.getElementById('adminProfilePreview'));
            });

            AdminLoading.init();
        });

        function handleProfileImageChange(e, previewElement) {
            const file = e.target.files[0];
            const maxFileSize = 2 * 1024 * 1024; // 2MB

            if (file) {
                if (file.size > maxFileSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Please select an image smaller than 2MB.'
                    });
                    e.target.value = ''; 
                    const existingContent = previewElement.querySelector('.current-profile-section');
                    previewElement.innerHTML = existingContent ? existingContent.outerHTML : '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    const existingContent = previewElement.querySelector('.current-profile-section');
                    let newPreviewHtml = `
                        <div class="new-profile-section">
                            <h4>New Profile Picture:</h4>
                            <div class="large-profile-display">
                                <img src="${event.target.result}" class="large-profile-preview" alt="New Admin Photo">
                            </div>
                        </div>`;
                    
                    previewElement.innerHTML = existingContent ? existingContent.outerHTML + newPreviewHtml : newPreviewHtml;
                };
                reader.readAsDataURL(file);
            } else {
                 const existingContent = previewElement.querySelector('.current-profile-section');
                 previewElement.innerHTML = existingContent ? existingContent.outerHTML : '';
            }
        }
    </script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
            document.getElementById('current-time').textContent = now.toLocaleTimeString();
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);

        const toggleBtn = document.getElementById('toggleSidebarBtn');
        const sidebar = document.querySelector('.sidebar');
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('expanded'));
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('expanded') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('expanded');
            }
        });
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
          this.titleEl = this.loadingEl.querySelector('.loading-title');
          this.messageEl = this.loadingEl.querySelector('.loading-message');
          this.progressBar = this.loadingEl.querySelector('.progress-bar');
          this.progressText = this.loadingEl.querySelector('.progress-text');
          this.setupNavigationInterception();
      },
      show(title = 'Processing Request', message = 'Please wait...') {
          this.titleEl.textContent = title;
          this.messageEl.textContent = message;
          this.loadingEl.style.display = 'flex';
          setTimeout(() => this.loadingEl.classList.add('active'), 50);
      },
      hide() {
          this.loadingEl.classList.remove('active');
          setTimeout(() => this.loadingEl.style.display = 'none', 800);
      },
      updateProgress(percent) {
          this.progressBar.style.width = `${percent}%`;
          this.progressText.textContent = `${percent}%`;
      },
      setupNavigationInterception() {
          document.addEventListener('click', (e) => {
              if (e.target.closest('.swal2-container, .modal, .dropdown')) return;
              const link = e.target.closest('a');
              if (link && !link.hasAttribute('data-no-loading') && link.href && !link.href.startsWith('javascript:') && !link.href.startsWith('#')) {
                  e.preventDefault();
                  const loading = this.startAction('Loading Page', `Preparing ${link.textContent.trim()}...`);
                  let progress = 0;
                  const progressInterval = setInterval(() => {
                      progress += Math.random() * 40;
                      if (progress >= 90) clearInterval(progressInterval);
                      loading.updateProgress(Math.min(progress, 100));
                  }, 300);
                  setTimeout(() => window.location.href = link.href, 2000);
              }
          });
      },
      startAction(actionName, message) {
          this.show(actionName, message);
          return {
              updateProgress: (percent) => this.updateProgress(percent),
              updateMessage: (msg) => this.messageEl.textContent = msg,
              complete: () => {
                  this.updateProgress(100);
                  this.updateMessage('Done!');
                  setTimeout(() => this.hide(), 800);
              }
          };
      }
  };
</script>
<footer class="site-footer">

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
    </div>
</footer>
</body>
</html>