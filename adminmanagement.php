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

</head>
<style>

    </style>

 <h3><i class="fa-solid fa-user-tie"></i>Admin Management</h3>
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

    <div class="main-content4">
        <section class="content-2">
            <div class="container">
              
               <div class="filter-controls">
                  <div class="button-row">
                    <button class="add_trip" onclick="openAdminModal()">
                    <i class="fas fa-plus"></i> Add Admin
                    </button>
                </div>
    <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="adminSearch" placeholder="Search admins..." onkeyup="searchAdmins()">
    </div>
    <label class="checkbox-container">
        <input type="checkbox" id="showDeletedCheckbox" onchange="toggleDeletedAdmins()">
        <span class="checkmark"></span>
        Show Deleted 
    </label>
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
                <br />
               
                <div class="table-container">
                    <table id="adminsTable">
                        <thead>
                            <tr>
                                <th></th>
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
                <span id="admin-page-info"></span>
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
        
        const deletedAt = admin.deleted_at ? new Date(admin.deleted_at).toLocaleString() : '';
        
        row.innerHTML = `
    <td><i class="fa-solid fa-circle-user"></i></td>
    <td>${highlightText(admin.admin_id)}</td>
    <td>${highlightText(admin.username)}</td>
    <td>${highlightText(admin.role || 'Full Admin')}</td>
    <td>${highlightText(admin.is_deleted ? 'Deleted' : 'Active')}</td>
   <td class="deleted-only">${highlightText(admin.deleted_by || '')}</td>
<td class="deleted-only">${highlightText(deletedAt)}</td>
<td class="deleted-only">${highlightText(admin.delete_reason || '')}</td>
    <td class="actions">
        ${admin.is_deleted ? '' : `<button class="icon-btn edit" onclick="openAdminModal(${admin.admin_id})" data-tooltip="Edit"><i class="fas fa-edit"></i></button>`}
        ${admin.is_deleted ? '' : `<button class="icon-btn delete" onclick="confirmDeleteAdmin(${admin.admin_id})" data-tooltip="Delete"><i class="fas fa-trash-alt"></i></button>`}
        ${admin.is_deleted ? `<button class="icon-btn restore" onclick="restoreAdmin(${admin.admin_id})" data-tooltip="Restore"><i class="fas fa-trash-restore"></i></button>` : ''}
        ${admin.is_deleted ? `<button class="icon-btn full-delete" onclick="fullDeleteAdmin(${admin.admin_id})" data-tooltip="Permanently Delete"><i class="fa-solid fa-ban"></i></button>` : ''}
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
        let rowsPerPage = parseInt(document.getElementById('rowsPerPage').value) || 5;
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
    
    fetchAdminsPaginated(showDeleted);
}

        // Fetch admins with pagination
function fetchAdminsPaginated(showDeleted = false) {
    const searchTerm = document.getElementById('adminSearch').value.toLowerCase();
 const url = `include/handlers/get_admins.php?page=${currentAdminPage}&limit=${rowsPerPage}&show_deleted=${showDeleted}&search=${encodeURIComponent(searchTerm)}`;    
    fetch(url)
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
                

                updateShowingInfo(data.admins.length, data.total);
 
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
                 document.getElementById("showingInfo").textContent = 
                    `Showing ${data.admins.length} of ${data.total} results`;
                document.getElementById("admin-page-info").textContent = 
                    `Showing search results`;
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

              function updateDateTime() {
            const now = new Date();
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
            
            document.getElementById('current-time').textContent = now.toLocaleTimeString();
        }

        // Update immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);
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

function fullDeleteAdmin(adminId) {
    if (confirm('WARNING: This will permanently delete this admin record. Are you absolutely sure?')) {
        fetch('include/handlers/delete_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                admin_id: adminId,
                full_delete: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Admin permanently deleted!');
                fetchAdminsPaginated(document.getElementById('showDeletedCheckbox').checked);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function changeRowsPerPage() {
    rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
    currentAdminPage = 1; 
    fetchAdminsPaginated(document.getElementById('showDeletedCheckbox').checked);
}

function updateShowingInfo(currentCount, total) {
    if (total === 0) {
        document.getElementById('showingInfo').textContent = 'Showing 0 of 0';
        return;
    }
    
    const start = ((currentAdminPage - 1) * rowsPerPage) + 1;
    const end = Math.min(start + currentCount - 1, total);
    document.getElementById('showingInfo').textContent = `Showing ${start} to ${end} of ${total}`;
}

window.onload = function() {
    document.getElementById('showDeletedCheckbox').addEventListener('change', toggleDeletedAdmins);
    
    // Initialize rows per page selector
    const rowsPerPageSelect = document.getElementById('rowsPerPage');
    rowsPerPageSelect.value = rowsPerPage;
    rowsPerPageSelect.addEventListener('change', changeRowsPerPage);
    
    fetchAdminsPaginated();
};
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