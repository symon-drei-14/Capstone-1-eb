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
     <div class="header-left">
     <button id="toggleSidebarBtn" class="toggle-sidebar-btn">
         <i class="fa fa-bars"></i>
     </button>
     <div class="logo-container">

         <img src="include/img/mansar2.png" alt="Company Name" class="company">
     </div>
</div>
  <div class="header-right">
     <div class="datetime-container">
         <div id="current-date" class="date-display"></div>
         <div id="current-time" class="time-display"></div>
     </div>

     <div class="profile" onclick="window.location.href='admin_profile.php'" style="cursor: pointer;"> 
     <?php 
    
     if (isset($_SESSION['admin_pic']) && !empty($_SESSION['admin_pic'])) {
        
         echo '<img src="data:image/jpeg;base64,' . $_SESSION['admin_pic'] . '" alt="Admin Profile" class="profile-icon">';
     } else {
        
         echo '<img src="include/img/profile.png" alt="Admin Profile" class="profile-icon">';
     }
     ?>
     <div class="profile-name">
         <?php 
             echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
         ?>
     </div>
</div>
</div>
</header>
 <?php require_once __DIR__ . '/include/sidebar.php'; ?>
     <div id="sidebar-backdrop" class="backdrop"></div>
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
                                     <th>Email</th>
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
         <div class="modal-header">
             <h2 id="modalTitle">Add Admin</h2>
             <span class="close" onclick="closeModal('adminModal')">&times;</span>
         </div>

         <form id="adminForm">
             <input type="hidden" id="adminId" name="adminId">

             <div class="form-group">
                 <label for="adminProfile">Profile Photo (Max 2MB)</label>
                 <input type="file" id="adminProfile" name="adminProfile" accept="image/*">
                 <div id="adminProfilePreview" style="margin-top: 10px;"></div>
             </div>
             
             <div class="form-row">
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
             </div>

             <div class="form-group">
                 <label for="adminEmail">Email *</label>
                 <input type="email" id="adminEmail" name="adminEmail" class="form-control" required placeholder="admin@example.com">
             </div>

            

             <div class="form-row">
                 <div class="form-group">
                     <label for="password" id="passwordLabel">Password *</label>
                     <div class="password-wrapper">
                         <input type="password" id="password" name="password" class="form-control">
                         <i class="fa-regular fa-eye toggle-password"></i>
                     </div>
                     <small id="passwordHelp" style="display: none;">Leave blank to keep current password.</small>
                 </div>
                 <div class="form-group">
                     <label for="confirmPassword">Confirm Password *</label>
                     <div class="password-wrapper">
                         <input type="password" id="confirmPassword" name="confirmPassword" class="form-control">
                         <i class="fa-regular fa-eye toggle-password"></i>
                     </div>
                 </div>
             </div>
         </form>

         <div class="modal-footer">
             <div class="button-group">
                 <button type="button" class="cancel-btn" onclick="closeModal('adminModal')">Cancel</button>
                 <button type="button" class="save-btn" onclick="saveAdmin()">Save</button>
             </div>
         </div>
     </div>
</div>

     <script>
        
         let currentAdminPage = 1;
         let activeRowsPerPage = 5;
         let totalAdmins = 0;

         let currentDeletedPage = 1;
         let deletedRowsPerPage = 5;
         let totalDeletedAdmins = 0;

       
         function openModal(modalId) {
             document.getElementById(modalId).style.display = "block";
         }
         
         function closeModal(modalId) {
             const modalToClose = document.getElementById(modalId);
             if (!modalToClose) return;

             modalToClose.classList.add('closing');

             setTimeout(() => {
                 modalToClose.style.display = 'none';
                 modalToClose.classList.remove('closing');
             }, 300); 
         }

         window.onclick = function(event) {
             if (event.target.classList.contains('modal')) {
                 closeModal(event.target.id);
             }
         };

          function openAdminModal(adminId = null) {
             document.getElementById('adminForm').reset();
             const modalTitle = document.getElementById('modalTitle');
             const passwordHelp = document.getElementById('passwordHelp');
             const passwordInput = document.getElementById('password');
             const confirmPasswordInput = document.getElementById('confirmPassword');
             const passwordLabel = document.getElementById('passwordLabel');
             const profilePreview = document.getElementById('adminProfilePreview');
             profilePreview.innerHTML = '';

             if (adminId) {
            
                 modalTitle.textContent = 'Edit Admin';
                 passwordHelp.style.display = 'block';
              
                 passwordInput.required = false;
                 confirmPasswordInput.required = false;
                 passwordLabel.textContent = "New Password";
                 fetchAdminDetails(adminId);
             } else {
                 
                 modalTitle.textContent = 'Add Admin';
                 passwordHelp.style.display = 'none';
                 passwordInput.required = true;
                 confirmPasswordInput.required = true;
                 passwordLabel.textContent = "Password *";
                 profilePreview.innerHTML = '<p>Upload a profile picture </p>';
                 document.getElementById('adminProfile').value = '';
                 
                
                 document.getElementById('adminId').value = ''; 
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
                          document.getElementById('adminEmail').value = admin.admin_email || '';
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
             const deleteReason = (admin.delete_reason || '').replace(/'/g, "\\'").replace(/"/g, '\\"');

             if (tableBodyId === 'deletedAdminTableBody') {
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
                     <td data-label="Email">${highlightText(admin.admin_email || '')}</td>
                     
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
                     fetchAdminsPaginated(false); 
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
        const adminEmail = document.getElementById('adminEmail').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        const profileInput = document.getElementById('adminProfile');


        if (!username || !role || !adminEmail) {
            Swal.fire('Validation Error', 'Username, role, and email are required.', 'warning');
            return;
        }
        if (password !== confirmPassword) {
            Swal.fire('Validation Error', 'New passwords do not match.', 'warning');
            return;
        }
        
        
        if (!adminId && !password) {
            Swal.fire('Validation Error', 'Password is required for new admins.', 'warning');
            return;
        }

        if (password && password.length < 8) {
             Swal.fire('Validation Error', 'Password must be at least 8 characters long.', 'warning');
             return;
        }
        
        const saveButton = document.querySelector('#adminModal .save-btn');
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const formData = new FormData();
        formData.append('admin_id', adminId);
        formData.append('username', username);
        formData.append('role', role);
        formData.append('admin_email', adminEmail);
        formData.append('password', password);
        
        
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
                    
                    let successMessage = `Admin has been ${adminId ? 'updated' : 'added'} successfully.`;
                    
                    if (data.message_type === 'password_notified') {
                        successMessage = "Password updated! A security notification has been sent to the admin's email.";
                    } else if (data.message_type === 'email_notified') {
                        successMessage = "Email updated! A security notification has been sent to the admin's *old* email address.";
                    } else if (data.message_type === 'password_and_email_notified') {
                        successMessage = "Password and Email updated! Security notifications have been sent to the respective email addresses.";
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: successMessage,
                    }).then(() => {
                        closeModal('adminModal');
                        fetchAdminsPaginated(false);
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
                const saveButton = document.querySelector('#adminModal .save-btn');
                saveButton.disabled = false;
                saveButton.innerHTML = 'Save';

            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Request Failed', 'An error occurred while saving the admin.', 'error');
                const saveButton = document.querySelector('#adminModal .save-btn');
                saveButton.disabled = false;
                saveButton.innerHTML = 'Save';
            });
    }

   

       function restoreAdmin(adminId, reason = '') {
    const mandatoryResetReasons = ['Failed Login Attempts', 'Too many OTP attempts'];

    if (mandatoryResetReasons.includes(reason)) {
        
        Swal.fire({
            title: 'Security Reset Required',
            text: 'This account was locked for security reasons. You must set a new password and email to restore it.',
            icon: 'info',
            html: `
                <input type="password" id="swal-password" class="swal2-input" placeholder="New Password" autocomplete="new-password">
                <input type="password" id="swal-confirm-password" class="swal2-input" placeholder="Confirm New Password" autocomplete="new-password">
                <input type="email" id="swal-email" class="swal2-input" placeholder="New Email Address" required>
            `,
            confirmButtonText: 'Restore & Reset Credentials',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            preConfirm: () => {
                const password = Swal.getPopup().querySelector('#swal-password').value;
                const confirmPassword = Swal.getPopup().querySelector('#swal-confirm-password').value;
                const email = Swal.getPopup().querySelector('#swal-email').value;
                
                if (!password || !confirmPassword || !email) {
                    Swal.showValidationMessage(`All fields are required.`);
                    return false;
                } else if (password !== confirmPassword) {
                    Swal.showValidationMessage(`The passwords do not match.`);
                    return false;
                }
                
              
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                     Swal.showValidationMessage(`Please enter a valid email address.`);
                     return false;
                }

                return { password: password, admin_email: email };
            }
        }).then((result) => {
            if (result.isConfirmed) {
               
                fetch('include/handlers/restore_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admin_id: adminId,
                        password: result.value.password,
                        admin_email: result.value.admin_email 
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Restored!', 'The admin account has been restored with new credentials.', 'success');
                        fetchAdminsPaginated(true); 
                    } else {
                        
                        Swal.fire('Error!', data.message, 'error');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    } else {
        
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
                    body: JSON.stringify({ admin_id: adminId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Restored!', 'The admin has been restored successfully.', 'success');
                        fetchAdminsPaginated(true); 
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

             
             const prevButton = document.createElement('button');
             prevButton.innerHTML = '&laquo;';
             prevButton.className = 'prev';
             prevButton.disabled = currentPage === 1;
             prevButton.onclick = () => goToPageFunction(currentPage - 1);
             container.appendChild(prevButton);

          
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

             
             const nextButton = document.createElement('button');
             nextButton.innerHTML = '&raquo;';
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
             
             document.getElementById('showDeletedCheckbox').addEventListener('change', toggleDeletedAdmins);
             document.getElementById('rowsPerPage').addEventListener('change', changeRowsPerPage);
             document.getElementById('deletedRowsPerPage').addEventListener('change', changeRowsPerPage);
             
             
             document.getElementById('rowsPerPage').value = activeRowsPerPage;
             document.getElementById('deletedRowsPerPage').value = deletedRowsPerPage;

             
             fetchAdminsPaginated(false);

             
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
     

     const passwordToggles = document.querySelectorAll('.toggle-password');
     passwordToggles.forEach(toggle => {
         toggle.addEventListener('click', function () {
             const passwordInput = this.previousElementSibling;
             const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
             passwordInput.setAttribute('type', type);
             this.classList.toggle('fa-eye');
             this.classList.toggle('fa-eye-slash');
         });
     });

             AdminLoading.init();
         });

         function handleProfileImageChange(e, previewElement) {
            const file = e.target.files[0];
            const maxFileSize = 2 * 1024 * 1024; 

            if (file) {
                if (file.size > maxFileSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Please select an image smaller than 2MB.'
                    });
                    e.target.value = ''; 
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {  
                    const newPreviewHtml = `
                        <div class="current-profile-section">
                            <h4>New Profile Preview:</h4>
                            <div class="large-profile-display">
                                <img src="${event.target.result}" class="large-profile-preview" alt="New Admin Photo">
                            </div>
                        </div>`;
                    
                    previewElement.innerHTML = newPreviewHtml;
                };
                reader.readAsDataURL(file);
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

        document.addEventListener('DOMContentLoaded', function () {
         const toggleBtn = document.getElementById('toggleSidebarBtn');
         const sidebar = document.querySelector('.sidebar');
         const backdrop = document.getElementById('sidebar-backdrop'); 

         const openSidebar = () => {
             sidebar.classList.add('expanded');
             backdrop.classList.add('show');
            document.body.classList.add('no-scroll');
         };


         const closeSidebar = () => {
             sidebar.classList.remove('expanded');
             backdrop.classList.remove('show');
             document.body.classList.remove('no-scroll');
         };


         toggleBtn.addEventListener('click', function (e) {
             e.stopPropagation(); 
             if (sidebar.classList.contains('expanded')) {
                 closeSidebar();
             } else {
                 openSidebar();
             }
         });

         backdrop.addEventListener('click', function () {
             closeSidebar();
         });

         document.addEventListener('click', function (e) {
             if (
                 sidebar.classList.contains('expanded') &&
                 !sidebar.contains(e.target) && 
                 !toggleBtn.contains(e.target)
             ) {
                 closeSidebar();
             }
         });
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