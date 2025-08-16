<?php
    require_once __DIR__ . '/include/check_access.php';
    checkAccess(); // No role needed—logic is handled internally

    require_once 'include/handlers/dbhandler.php';

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Driver Management</title>
        <link rel="stylesheet" href="include/css/sidenav.css">
        <link rel="stylesheet" href="include/css/loading.css">
        <link rel="stylesheet" href="include/css/drivermanagement.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
<h3><i class="fa-solid fa-id-card"></i>Driver Management</h3>
    <div class="main-content3">
        <section class="dashboard">
            <div class="container">
                <div class="button-row">
                    <br>
                    <div class="search-container" style="float: left; margin-top: 10px; margin-left: 20px;">
                        <i class="fas fa-search"></i>
                        <input type="text" id="driverSearch" placeholder="Search drivers..." onkeyup="searchDrivers()">
                    </div>
                    <div style="float: right; margin-top: 10px; margin-right: 20px;">
                        <button class="btn-primary" onclick="openAddDriverModal()">
                            <i class="fas fa-plus"></i> Add New Driver
                        </button>
                    </div>
                </div>
                <br />

                <div class="table-container">
                    <table id="driverTable">
                        <thead>
                            <tr>
                                <th>Profile</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Assigned Truck</th>
                                <th>Total Completed Trips</th>
                                <th>Completed Trips This Month</th>
                                <th>Created At <br> (Y-M-D)</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="driverTableBody">
                            <!-- Table data will be loaded here via JavaScript -->
                        </tbody>
                    </table>
                    
                    <div class="pagination">
                        <button class="prev" id="prevPageBtn">&laquo;</button>
                        <div id="page-numbers" class="page-numbers"></div>
                        <button class="next" id="nextPageBtn">&raquo;</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal Structure (Add/Edit) -->
    <div id="driverModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h2 id="modalTitle">Add Driver</h2>
            <form id="driverForm">
                <input type="hidden" id="driverId" name="driverId">
                <input type="hidden" id="modalMode" name="modalMode" value="add">
                
                <div class="form-group">
                    <label for="driverName">Name *</label>
                    <input type="text" id="driverName" name="driverName" required>
                </div>

                <div class="form-group">
                    <label for="driverEmail">Email *</label>
                    <input type="email" id="driverEmail" name="driverEmail" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <small id="passwordHelp">Leave blank to keep current password</small>
                </div>
                
                <div class="form-group">
                    <label for="assignedTruck">Assigned Truck ID</label>
                    <input type="number" id="assignedTruck" name="assignedTruck" placeholder="Enter truck ID or leave blank">
                </div>

                <button type="submit" id="saveButton" class="btn-primary">
                    <i class="fas fa-save"></i> <span id="saveButtonText">Add Driver</span>
                </button>
                <button type="button" class="cancelbtn" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </form>
        </div>
    </div>

    <script>
        let currentDriverId = null;
        let driversData = [];
        let currentPage = 1;
        let rowsPerPage = 5;

        $(document).ready(function() {
            fetchDrivers();
        });

        function updateDateTime() {
            const now = new Date();
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
            
            document.getElementById('current-time').textContent = now.toLocaleTimeString();
        }

        // Update immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        function openAddDriverModal() {
            document.getElementById("modalTitle").textContent = "Add New Driver";
            document.getElementById("modalMode").value = "add";
            document.getElementById("saveButtonText").textContent = "Add Driver";
            document.getElementById("passwordHelp").style.display = "none";
            document.getElementById("password").required = true;
            
            // Clear all fields
            document.getElementById("driverId").value = "";
            document.getElementById("driverName").value = "";
            document.getElementById("driverEmail").value = "";
            document.getElementById("password").value = "";
            document.getElementById("assignedTruck").value = "";
            
            document.getElementById("driverModal").style.display = "block";
        }

       function fetchDrivers() {
    $.ajax({
        url: 'include/handlers/get_all_drivers.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                driversData = data.drivers;
                // Fetch trip counts for each driver
                fetchTripCounts().then(() => {
                    renderTable();
                });
            } else {
                alert("Error fetching drivers: " + data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            alert("An error occurred while fetching driver data.");
        }
    });
}

function fetchTripCounts() {
    return $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_driver_trip_counts'
        }),
        success: function(response) {
            if (response.success) {
                // Add trip counts to each driver
                driversData.forEach(driver => {
                    const driverStats = response.trip_counts.find(d => d.driver_id == driver.driver_id);
                    driver.total_completed = driverStats ? driverStats.total_completed : 0;
                    driver.monthly_completed = driverStats ? driverStats.monthly_completed : 0;
                });
            }
        }
    });
}

       function renderTable() {
    document.querySelectorAll('.highlight').forEach(el => {
        el.outerHTML = el.innerHTML;
    });
    
    $('#driverTableBody').empty();
    var startIndex = (currentPage - 1) * rowsPerPage;
    var endIndex = startIndex + rowsPerPage;
    var pageData = driversData.slice(startIndex, Math.min(endIndex, driversData.length));
    
    if (pageData.length > 0) {
        pageData.forEach(function(driver) {
            let formattedLastLogin = formatTime(driver.last_login);
            
            var row = "<tr>" +
                "<td><i class='fa-solid fa-circle-user profile-icon'></i></td>" + 
                "<td>" + driver.driver_id + "</td>" +
                "<td>" + driver.name + "</td>" +
                "<td>" + driver.email + "</td>" +
                "<td>" + (driver.assigned_truck_id || 'None') + "</td>" +
                 "<td>" + (driver.total_completed || 0) + "</td>" +
                "<td>" + (driver.monthly_completed || 0) + "</td>" +
                "<td>" + driver.created_at + "</td>" +
                "<td>" + formattedLastLogin + "</td>" +
                "<td class='actions'><div class='actions-container'>" +
                "<button class='action-btn edit-btn' data-tooltip='Edit Driver' onclick='editDriver(\"" + driver.driver_id + "\")'><i class='fas fa-edit'></i></button>" +
                "<button class='action-btn delete-btn' data-tooltip='Delete Driver' onclick='deleteDriver(\"" + driver.driver_id + "\")'><i class='fas fa-trash-alt'></i></button>" +
                "</div></td>" +
                "</tr>";
            $('#driverTableBody').append(row);
        });
    } else {
        $('#driverTableBody').append("<tr><td colspan='10'>No drivers found</td></tr>");
    }
    
    updatePagination();
}

        function formatTime(dateString) {
            if (!dateString || dateString === 'NULL') return 'Never';
            
            const date = new Date(dateString);
            
            const options = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,  
            };
            
            return date.toLocaleString('en-US', options);
        }
        
        function updatePagination() {
            totalPages = Math.ceil(driversData.length / rowsPerPage);
            
            $('#page-numbers').empty();
            
            for (var i = 1; i <= totalPages; i++) {
                var pageNumClass = i === currentPage ? 'page-number active' : 'page-number';
                var pageNumberElement = $(`<div class="${pageNumClass}">${i}</div>`);
                
                pageNumberElement.on('click', function() {
                    var page = parseInt($(this).text());
                    goToPage(page);
                });
                
                $('#page-numbers').append(pageNumberElement);
            }
            
            $('#prevPageBtn').prop('disabled', currentPage === 1);
            $('#nextPageBtn').prop('disabled', currentPage === totalPages || totalPages === 0);
        }

        function goToPage(page) {
            currentPage = page;
            renderTable();
            $('.page-number').removeClass('active');
            $(`.page-number:contains(${page})`).addClass('active');
        }

        function changePage(step) {
            var newPage = currentPage + step;
            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                renderTable();
            }
        }

        $('#prevPageBtn').on('click', function() {
            changePage(-1);
        });

        $('#nextPageBtn').on('click', function() {
            changePage(1);
        });

        $(document).on('click', '.page-number', function() {
            var page = parseInt($(this).data('page'));
            goToPage(page);
        });

        function closeModal() {
            document.getElementById("driverModal").style.display = "none";
        }

    function editDriver(driverId) {
        $.ajax({
            url: 'include/handlers/get_driver.php?id=' + driverId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    const driver = data.driver;
                    
                    document.getElementById("modalTitle").textContent = "Edit Driver";
                    document.getElementById("modalMode").value = "edit";
                    document.getElementById("saveButtonText").textContent = "Save Changes";
                    document.getElementById("passwordHelp").style.display = "block";
                    document.getElementById("password").required = false;
                    
                    document.getElementById("driverId").value = driver.driver_id;
                    document.getElementById("driverName").value = driver.name;
                    document.getElementById("driverEmail").value = driver.email;
                    document.getElementById("password").value = '';
                    document.getElementById("assignedTruck").value = driver.assigned_truck_id || '';
                    
                    document.getElementById("driverModal").style.display = "block";
                } else {
                    alert("Error fetching driver data: " + data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert("An error occurred while fetching driver data.");
            }
        });
    }

        function deleteDriver(driverId) {
            if (confirm("Are you sure you want to delete this driver?")) {
                $.ajax({
                    url: 'include/handlers/delete_driver.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ driverId: driverId }),
                    success: function(data) {
                        if (data.success) {
                            alert("Driver deleted successfully.");
                            fetchDrivers();
                        } else {
                            alert("Error deleting driver: " + data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert("An error occurred while deleting the driver.");
                    }
                });
            }
        }

      document.getElementById("driverForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const mode = document.getElementById("modalMode").value;
        const formData = {
            name: document.getElementById("driverName").value,
            email: document.getElementById("driverEmail").value,
            password: document.getElementById("password").value,
            assigned_truck_id: document.getElementById("assignedTruck").value ? parseInt(document.getElementById("assignedTruck").value) : null
        };
        
        if (mode === 'add') {
            // For adding new driver, use the same structure as register.js
            const driver_id = Date.now().toString();
            formData.driver_id = driver_id;
            formData.firebase_uid = driver_id;
            
            $.ajax({
                url: 'include/handlers/add_driver.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Driver added successfully!'
                        });
                        fetchDrivers();
                        closeModal();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error adding driver'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while adding the driver.'
                    });
                }
            });
        } else {
            // For editing existing driver
            formData.driverId = document.getElementById("driverId").value;
            formData.mode = 'edit';
            
            $.ajax({
                url: 'include/handlers/save_driver.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Driver updated successfully!'
                        });
                        fetchDrivers();
                        closeModal();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error updating driver'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while updating the driver data.'
                    });
                }
            });
        }
    });

        function searchDrivers() {
            const searchTerm = document.getElementById('driverSearch').value.toLowerCase();
            
            if (searchTerm === '') {
                document.querySelectorAll('.highlight').forEach(el => {
                    el.outerHTML = el.innerHTML;
                });
                fetchDrivers();
                return;
            }

            const filteredDrivers = driversData.filter(driver => {
                return (
                    String(driver.driver_id).toLowerCase().includes(searchTerm) ||
                    String(driver.name).toLowerCase().includes(searchTerm) ||
                    String(driver.email).toLowerCase().includes(searchTerm) ||
                    String(driver.assigned_truck_id || 'None').toLowerCase().includes(searchTerm) ||
                    String(driver.created_at).toLowerCase().includes(searchTerm) ||
                    String(formatTime(driver.last_login)).toLowerCase().includes(searchTerm)
                );
            });

            renderFilteredDrivers(filteredDrivers, searchTerm);
        }

        function renderFilteredDrivers(filteredDrivers, searchTerm) {
            $('#driverTableBody').empty();
            
            if (filteredDrivers.length > 0) {
                filteredDrivers.forEach(function(driver) {
                    let formattedLastLogin = formatTime(driver.last_login);
                    
                    const highlightText = (text) => {
                        if (!searchTerm || !text) return text;
                        const str = String(text);
                        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                        return str.replace(regex, '<span class="highlight">$1</span>');
                    };
                    
                    var row = "<tr>" +
                        "<td><i class='fa-solid fa-circle-user profile-icon'></i></td>" + 
                        "<td>" + highlightText(driver.driver_id) + "</td>" +
                        "<td>" + highlightText(driver.name) + "</td>" +
                        "<td>" + highlightText(driver.email) + "</td>" +
                        "<td>" + highlightText(driver.assigned_truck_id || 'None') + "</td>" +
                        "<td>" + highlightText(driver.total_completed || 0) + "</td>" +
                        "<td>" + highlightText(driver.monthly_completed || 0) + "</td>" +
                        "<td>" + highlightText(driver.created_at) + "</td>" +
                        "<td>" + highlightText(formattedLastLogin) + "</td>" +
                        "<td class='actions'>" +
                        "<button class='action-btn edit-btn' data-tooltip='Edit Driver' onclick='editDriver(\"" + driver.driver_id + "\")'><i class='fas fa-edit'></i></button>" +
                        "<button class='action-btn delete-btn' data-tooltip='Delete Driver' onclick='deleteDriver(\"" + driver.driver_id + "\")'><i class='fas fa-trash-alt'></i></button>" +
                        "</td>" +
                        "</tr>";
                    $('#driverTableBody').append(row);
                });
            } else {
                $('#driverTableBody').append("<tr><td colspan='10'>No matching drivers found</td></tr>");
            }
            
            $('#page-numbers').empty();
            $('#page-numbers').append(`<div>Showing ${filteredDrivers.length} result(s)</div>`);
            $('#prevPageBtn').prop('disabled', true);
            $('#nextPageBtn').prop('disabled', true);
        }
        
        // Preview profile image when selected
        document.getElementById('driverProfile') && document.getElementById('driverProfile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profilePreview').innerHTML = 
                        `<img src="${event.target.result}" style="max-width: 100px; max-height: 100px; border-radius: 50%;">`;
                };
                reader.readAsDataURL(file);
            }
        });

        window.onclick = function(event) {
            const modal = document.getElementById("driverModal");
            if (event.target == modal) {
                closeModal();
            }
        }

        
    </script>

    <script>
        document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('expanded');
        });

        document.addEventListener('DOMContentLoaded', function() {

    const currentPage = window.location.pathname.split('/').pop();
    
    
    const sidebarLinks = document.querySelectorAll('.sidebar-item a');
    

    sidebarLinks.forEach(link => {
        const linkPage = link.getAttribute('href').split('/').pop();
        
       
        if (linkPage === currentPage) {
            link.parentElement.classList.add('active');
            const icon = link.parentElement.querySelector('.icon2');
            if (icon) {
                icon.style.color = 'white';
            }
        }
    });
});
    </script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="include/js/logout-confirm.js"></script>

<footer class="site-footer">

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
    </div>
</footer>

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
    </body>
    </html>