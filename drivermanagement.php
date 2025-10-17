<?php
    require_once __DIR__ . '/include/check_access.php';
    checkAccess(); 

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
                        <button class="addbtn" onclick="openAddDriverModal()">
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
                                <th>Contact No.</th>
                                <th>Assigned Truck</th>
                                <th>Total Completed Trips</th>
                                <th>Completed Trips This Month</th>
                                <th>Created / Modified</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="driverTableBody">
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

    <div id="driverModal" class="modal">
        <div class="modal-content">
            <div class="modalheader">
                <h2 id="modalTitle">Add Driver</h2>
            <span class="close" onclick="closeModal()">×</span>
            
            </div>
    

            <form id="driverForm">
                
                <div class="form-group">
    <label for="driverProfile">Profile Photo  (Max 2MB)</label>
    <input type="file" id="driverProfile" name="driverProfile" accept="image/*">
    <div id="profilePreview" style="margin-top: 10px;"></div>
</div>

                <input type="hidden" id="driverId" name="driverId">
                <input type="hidden" id="modalMode" name="modalMode" value="add">
                
                <div class="form-group">
                    <label for="driverName">Name *</label>
                    <input type="text" id="driverName" name="driverName" required>
                </div>

            
    <div class="form-row">
        <div class="form-group">
            <label for="driverEmail">Email *</label>
            <input type="email" id="driverEmail" name="driverEmail" required>
        </div>

        <div class="form-group">
            <label for="driverContact">Contact Number *</label>
            <input type="tel" id="driverContact" name="driverContact" required>
        </div>
    </div>
    
    
      <small id="passwordHelp">Leave blank to keep current password</small>
<div class="form-row">
    <div class="form-group">
        <label for="password">Password *</label>
        <div class="password-wrapper">
            <input type="password" id="password" name="password" required>
            <i class="fa-regular fa-eye toggle-password"></i>
        </div>
 
    </div>
    
    <div class="form-group">
        <label for="confirmPassword">Confirm Password *</label>
        <div class="password-wrapper">
            <input type="password" id="confirmPassword" name="confirmPassword" required>
            <i class="fa-regular fa-eye toggle-password"></i>
        </div>
    </div>
</div>
                                        
        <div class="form-group">
            <label for="assignedTruck">Assigned Truck</label>
            <select id="assignedTruck" name="assignedTruck">
                <option value="">None</option>
            </select>
        </div>
        <div class= "button-group">
                <button type="submit" id="saveButton" class="btn-primary">
                    <i class="fas fa-save"></i> <span id="saveButtonText">Add Driver</span>
                </button>
                <button type="button" class="cancelbtn" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
</div>
            </form>
        </div>
    </div>

    <script>
        const userRole = "<?php echo $_SESSION['role'] ?? 'User'; ?>";
        let currentDriverId = null;
        let driversData = [];
       let currentPage = 1;
let rowsPerPage = 5;
let totalPages = 0;

        $(document).ready(function() {
            fetchDrivers();
        });

        function updateDateTime() {
            const now = new Date();
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
            
            document.getElementById('current-time').textContent = now.toLocaleTimeString();
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

       function openAddDriverModal() {
    document.getElementById("modalTitle").textContent = "Add New Driver";
    document.getElementById("modalMode").value = "add";
    document.getElementById("saveButtonText").textContent = "Add Driver";
    document.getElementById("passwordHelp").style.display = "none";
    document.getElementById("password").required = true;
    document.getElementById("password").required = true;
    document.getElementById("confirmPassword").required = true;
    
    document.getElementById("driverId").value = "";
    document.getElementById("driverName").value = "";
    document.getElementById("driverEmail").value = "";
    document.getElementById("driverContact").value = "";
    
    document.getElementById("password").value = "";
    document.getElementById("confirmPassword").value = "";
    
    document.getElementById("assignedTruck").value = "";
    document.getElementById("driverProfile").value = ""; 
    document.getElementById("profilePreview").innerHTML = ""; 

    populateAvailableTrucks();
    
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
    
    totalPages = Math.ceil(driversData.length / rowsPerPage);
    var startIndex = (currentPage - 1) * rowsPerPage;
    var endIndex = startIndex + rowsPerPage;
    var pageData = driversData.slice(startIndex, endIndex);
    
    if (pageData.length > 0) {
        pageData.forEach(function(driver) {
            let formattedLastLogin = formatTime(driver.last_login);
            
            let creationInfo;
            if (driver.last_modified_at && driver.last_modified_by) {
                creationInfo = formatDateWithTime(driver.last_modified_at);
                creationInfo += `<br><small style="color: #555;">Modified by: <strong>${driver.last_modified_by}</strong></small>`;
            } else {
                creationInfo = formatDateWithTime(driver.created_at);
            }
            
       var row = "<tr>" +
               "<td data-label='Profile'>" + (driver.driver_pic ? '<img src="data:image/jpeg;base64,' + driver.driver_pic + '" class="driver-photo">' : '<i class="fa-solid fa-circle-user profile-icon"></i>') + "</td>" +
               "<td data-label='ID'>" + driver.driver_id + "</td>" +
               "<td data-label='Name'>" + driver.name + "</td>" +
               "<td data-label='Email'>" + driver.email + "</td>" +
               "<td data-label='Contact No.'>" + (driver.contact_no || 'N/A') + "</td>" +
               "<td data-label='Assigned Truck'>" + (driver.assigned_truck_id || 'None') + "</td>" +
               "<td data-label='Total Trips'>" + (driver.total_completed || 0) + "</td>" +
               "<td data-label='Monthly Trips'>" + (driver.monthly_completed || 0) + "</td>" +
               "<td data-label='Created / Modified'>" + creationInfo + "</td>" +
               "<td data-label='Last Login'>" + formattedLastLogin + "</td>" +
               "<td data-label='Actions' class='actions'>" +
               "<div class='dropdown'>" +
               "<button class='dropdown-btn' data-tooltip='Actions' onclick='toggleDropdown(this)'><i class='fas fa-ellipsis-v'></i></button>" +
               "<div class='dropdown-content'>" +
               "<button class='dropdown-item edit' onclick='editDriver(\"" + driver.driver_id + "\")'><i class='fas fa-edit'></i> Edit Driver</button>" +
               "<button class='dropdown-item delete' onclick='deleteDriver(\"" + driver.driver_id + "\")'><i class='fas fa-trash-alt'></i> Delete Driver</button>" +
               "</div></div></td>" +
               "</tr>";
               $('#driverTableBody').append(row);
           });
       } else {
          $('#driverTableBody').append("<tr><td colspan='11'>No drivers found</td></tr>");
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

function formatDateWithTime(dateString) {
    if (!dateString || dateString === 'NULL') return 'Not set';
    
    const date = new Date(dateString);

   const dateOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    };
    const timeOptions  = {
       
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };
    
    const formattedDate = date.toLocaleDateString('en-US', dateOptions);
    const formattedTime = date.toLocaleTimeString('en-US', timeOptions);

    return `<span class="date">${formattedDate}</span> <span class="time">${formattedTime}</span>`;
}
       
       function updatePagination() {
    totalPages = Math.ceil(driversData.length / rowsPerPage);
    
    $('#page-numbers').empty();

    if (totalPages <= 1) {
        $('#prevPageBtn').prop('disabled', true);
        $('#nextPageBtn').prop('disabled', true);
        return;
    }
    
    if (currentPage > totalPages) {
        currentPage = totalPages;
    }
    if (currentPage < 1) {
        currentPage = 1;
    }

    for (var i = 1; i <= totalPages; i++) {
        var pageNumClass = i === currentPage ? 'page-number active' : 'page-number';
        var pageNumberElement = $('<div class="' + pageNumClass + '" data-page="' + i + '">' + i + '</div>');
        
        $('#page-numbers').append(pageNumberElement);
    }
    
    $('#prevPageBtn').prop('disabled', currentPage === 1);
    $('#nextPageBtn').prop('disabled', currentPage === totalPages);
}
    function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        renderTable();
    }
}

   function changePage(step) {
    var newPage = currentPage + step;
    if (newPage >= 1 && newPage <= totalPages) {
        goToPage(newPage);
    }
}

   $(document).ready(function() {
    $('#prevPageBtn').off('click');
    $('#nextPageBtn').off('click');
    
    $('#prevPageBtn').on('click', function() {
        changePage(-1);
    });

    $('#nextPageBtn').on('click', function() {
        changePage(1);
    });
    
    $(document).off('click', '.page-number');
    $(document).on('click', '.page-number', function() {
        var page = parseInt($(this).data('page'));
        if (!isNaN(page) && page !== currentPage) {
            goToPage(page);
        }
    });
    
    fetchDrivers();
});

       function closeModal() {
    driverModal.classList.add('closing');
    setTimeout(() => {
        driverModal.style.display = 'none';
        driverModal.classList.remove('closing'); 
    }, 300); 
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
                document.getElementById("confirmPassword").required = false;
                
                document.getElementById("driverId").value = driver.driver_id;
                document.getElementById("driverContact").value = driver.contact_no || '';
                document.getElementById("driverName").value = driver.name;
                document.getElementById("driverEmail").value = driver.email;
                
                document.getElementById("password").value = '';
                document.getElementById("confirmPassword").value = '';
                
                populateAvailableTrucks(driver.driver_id, driver.assigned_truck_id);
                
                const profilePreview = document.getElementById('profilePreview');
                if (driver.driver_pic) {
                    profilePreview.innerHTML = `
                        <div class="current-profile-section">
                            <h4>Current Profile Picture:</h4>
                            <div class="large-profile-display">
                                <img src="data:image/jpeg;base64,${driver.driver_pic}"
                                     class="large-profile-preview"
                                     alt="Current Driver Photo">
                            </div>
                        </div>
                    `;
                } else {
                    profilePreview.innerHTML = `
                        <div class="current-profile-section">
                            <h4>Current Profile Picture:</h4>
                            <div class="large-profile-display">
                                <i class="fa-solid fa-circle-user large-profile-icon"></i>
                                <p>No profile picture uploaded</p>
                            </div>
                        </div>
                    `;
                }
                
                document.getElementById("driverProfile").value = '';
                
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
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone. All related data for this driver will be removed.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'include/handlers/delete_driver.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ driverId: driverId }),
                        success: function(data) {
                            if (data.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'The driver has been successfully deleted.',
                                    'success'
                                );
                                fetchDrivers(); 
                            } else {
                                Swal.fire(
                                    'Error!',
                                    data.message || 'Could not delete the driver.',
                                    'error'
                                );
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            Swal.fire(
                                'Error!',
                                'An error occurred while deleting the driver.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

      document.getElementById("driverForm").addEventListener("submit", function(e) {
    e.preventDefault();
    
    if (!validatePassword()) {
        return;
    }
    const saveButton = document.getElementById('saveButton');
    const originalButtonHTML = saveButton.innerHTML; 

    saveButton.disabled = true;
    saveButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Saving...`;
    const mode = document.getElementById("modalMode").value;
    const formData = new FormData();
    
    formData.append("name", document.getElementById("driverName").value);
    formData.append("email", document.getElementById("driverEmail").value);
    formData.append("password", document.getElementById("password").value);
    formData.append("confirmPassword", document.getElementById("confirmPassword").value); 
    formData.append("assigned_truck_id", document.getElementById("assignedTruck").value);
    formData.append("contact_no", document.getElementById("driverContact").value);
    formData.append("mode", mode);
    
    if (mode === 'edit') {
        formData.append("driverId", document.getElementById("driverId").value);
    }
    
    const profileInput = document.getElementById("driverProfile");
    if (profileInput.files.length > 0) {
        formData.append("driverProfile", profileInput.files[0]);
    }
    
    if (mode === 'add') {
        const driver_id = Date.now().toString();
        formData.append("driver_id", driver_id);
        formData.append("firebase_uid", driver_id);
        
        $.ajax({
            url: 'include/handlers/add_driver.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
            },
              complete: function() {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonHTML;
              }
        });
    } else {
        $.ajax({
            url: 'include/handlers/save_driver.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                if (data.success) {
                    let successMessage = 'Driver updated successfully!';
                    
                    if (data.message_type === 'password_notified') {
                        successMessage = "Password updated! A security notification has been sent to the driver's email.";
                    } else if (data.message_type === 'email_notified') {
                        successMessage = "Email updated! A security notification has been sent to the driver's *old* email address.";
                    } else if (data.message_type === 'password_and_email_notified') {
                        successMessage = "Password and Email updated! Security notifications have been sent to the respective email addresses.";
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: successMessage
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
            },
              complete: function() {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonHTML;
              }
        });
    }
});

        function searchDrivers() {
    const searchTerm = document.getElementById('driverSearch').value.toLowerCase();
    
    currentPage = 1;
    
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
        String(driver.contact_no || '').toLowerCase().includes(searchTerm) || 
        String(driver.assigned_truck_id || 'None').toLowerCase().includes(searchTerm) ||
        String(driver.created_at).toLowerCase().includes(searchTerm) ||
        String(driver.last_modified_by || '').toLowerCase().includes(searchTerm) || 
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
            
            let creationInfo;
            if (driver.last_modified_at && driver.last_modified_by) {
                const modifiedDate = formatDateWithTime(driver.last_modified_at);
                const modifiedBy = highlightText(driver.last_modified_by); 
                creationInfo = `${modifiedDate}<br><small style="color: #555;">Modified by: <strong>${modifiedBy}</strong></small>`;
            } else {
                creationInfo = formatDateWithTime(driver.created_at);
            }

              var row = "<tr>" +
               "<td data-label='Profile'>" + (driver.driver_pic ? '<img src="data:image/jpeg;base64,' + driver.driver_pic + '" class="driver-photo">' : '<i class="fa-solid fa-circle-user profile-icon"></i>') + "</td>" +
               "<td data-label='ID'>" + highlightText(driver.driver_id) + "</td>" +
               "<td data-label='Name'>" + highlightText(driver.name) + "</td>" +
               "<td data-label='Email'>" + highlightText(driver.email) + "</td>" +
               "<td data-label='Contact No.'>" + highlightText(driver.contact_no || 'N/A') + "</td>" +
               "<td data-label='Assigned Truck'>" + highlightText(driver.assigned_truck_id || 'None') + "</td>" +
               "<td data-label='Total Trips'>" + highlightText(driver.total_completed || 0) + "</td>" +
               "<td data-label='Monthly Trips'>" + highlightText(driver.monthly_completed || 0) + "</td>" +
               "<td data-label='Created / Modified'>" + creationInfo + "</td>" +
               "<td data-label='Last Login'>" + highlightText(formattedLastLogin) + "</td>" +
               "<td data-label='Actions' class='actions'>" +
               "<div class='dropdown'>" +
               "<button class='dropdown-btn' data-tooltip='Actions' onclick='toggleDropdown(this)'><i class='fas fa-ellipsis-v'></i></button>" +
               "<div class='dropdown-content'>" +
               "<button class='dropdown-item edit' onclick='editDriver(\"" + driver.driver_id + "\")'><i class='fas fa-edit'></i> Edit Driver</button>" +
               "<button class='dropdown-item delete' onclick='deleteDriver(\"" + driver.driver_id + "\")'><i class='fas fa-trash-alt'></i> Delete Driver</button>" +
               "</div></div></td>" +
               "</tr>";
               $('#driverTableBody').append(row);
        });
    } else {
        $('#driverTableBody').append("<tr><td colspan='11'>No drivers found</td></tr>");
    }
    
    $('#page-numbers').empty();
    $('#page-numbers').append('<div class="search-results">Showing ' + filteredDrivers.length + ' result(s)</div>');
    $('#prevPageBtn').prop('disabled', true);
    $('#nextPageBtn').prop('disabled', true);
}
        

    document.getElementById('driverProfile') && document.getElementById('driverProfile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const profilePreview = document.getElementById('profilePreview');
        const maxSize = 2 * 1024 * 1024; 

        if (file) {
   
            if (file.size > maxSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'The selected image exceeds the 2MB size limit. Please choose a smaller file.'
                });
                e.target.value = ''; 


                const mode = document.getElementById("modalMode").value;
                if (mode === 'edit') {
                    const existingContent = profilePreview.querySelector('.current-profile-section');
                    if (existingContent) {
                        profilePreview.innerHTML = existingContent.outerHTML;
                    }
                } else {
                    profilePreview.innerHTML = '';
                }
                return; 
            }

            const reader = new FileReader();
      
            reader.onload = function(event) {

                const newPreviewHtml = `
                    <div class="current-profile-section">
                        <h4>New Profile Preview:</h4>
                        <div class="large-profile-display">
                            <img src="${event.target.result}"
                                class="large-profile-preview"
                                alt="New Driver Photo">
                        </div>
                    </div>
                `;

                profilePreview.innerHTML = newPreviewHtml;
            };
            reader.readAsDataURL(file);
        } else {
            const mode = document.getElementById("modalMode").value;
            if (mode === 'edit') {
                const existingContent = profilePreview.querySelector('.current-profile-section');
                if (existingContent) {
                    profilePreview.innerHTML = existingContent.outerHTML;
                }
            } else {
                profilePreview.innerHTML = '';
            }
        }
    });

        window.onclick = function(event) {
            const modal = document.getElementById("driverModal");
            if (event.target == modal) {
                closeModal();
            }
        }

        
   document.addEventListener('DOMContentLoaded', function() {
           document.addEventListener('click', function(e) {
               if (!e.target.closest('.dropdown')) {
                   closeAllDropdowns();
               }
           });
           
           document.addEventListener('click', function(e) {
               if (e.target.closest('.dropdown-btn')) {
                   const dropdown = e.target.closest('.dropdown');
                   const dropdownContent = dropdown.querySelector('.dropdown-content');
                   
                   closeAllDropdownsExcept(dropdownContent);
                   
                   dropdownContent.classList.toggle('show');
                   e.stopPropagation();
               }
           });
           
           document.addEventListener('click', function(e) {
               if (e.target.closest('.dropdown-item')) {
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
       
    </script>

    <script>
   document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('toggleSidebarBtn');
        const sidebar = document.querySelector('.sidebar');
        const backdrop = document.getElementById('sidebar-backdrop'); 

        const openSidebar = () => {
            sidebar.classList.add('expanded');
            backdrop.classList.add('show');
        };


        const closeSidebar = () => {
            sidebar.classList.remove('expanded');
            backdrop.classList.remove('show');
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

function validatePassword() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const mode = document.getElementById('modalMode').value;
    
    if (password !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Passwords do not match.'
        });
        return false;
    }
    
    return true;
}

  function populateAvailableTrucks(driverId = null, selectedTruckId = null) {
        const truckSelect = document.getElementById("assignedTruck");
        truckSelect.innerHTML = '<option value="">Loading trucks...</option>';
        truckSelect.disabled = true;

        let url = `include/handlers/truck_handler.php?action=getAvailableTrucks`;
        if (driverId) {
            url += `&driverId=${encodeURIComponent(driverId)}`;
        }

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                truckSelect.innerHTML = '<option value="">None</option>';
                if (data.success && data.trucks) {
                    data.trucks.forEach(function(truck) {
                        const option = document.createElement('option');
                        option.value = truck.truck_id;
                        option.textContent = `${truck.plate_no} (ID: ${truck.truck_id})`;
                        truckSelect.appendChild(option);
                    });
                }
                if (selectedTruckId) {
                    truckSelect.value = selectedTruckId;
                }
            },
            error: function() {
                truckSelect.innerHTML = '<option value="">Error loading trucks</option>';
            },
            complete: function() {
                truckSelect.disabled = false;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
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
  
  checkForIncomingNavigation() {
    const referrer = document.referrer;
    const currentDomain = window.location.origin;
    
    const shouldShowLoading = sessionStorage.getItem('showAdminLoading');
    
    if ((referrer && referrer.startsWith(currentDomain)) || shouldShowLoading) {
      sessionStorage.removeItem('showAdminLoading');
      
      this.show('Loading Page', 'Loading content...');
      
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
      if (e.target.closest('.swal2-container, .swal2-popup, .swal2-modal, .modal, .modal-content, .fc-event, #calendar')) {
        return;
      }
      
      const link = e.target.closest('a');
      if (link && !link.hasAttribute('data-no-loading') && 
          link.href && !link.href.startsWith('javascript:') &&
          !link.href.startsWith('#') && !link.href.startsWith('mailto:') &&
          !link.href.startsWith('tel:')) {
        
        try {
          const linkUrl = new URL(link.href);
          const currentUrl = new URL(window.location.href);
          
          if (linkUrl.origin !== currentUrl.origin) {
            return; 
          }
          
          if (linkUrl.pathname === currentUrl.pathname) {
            return;
          }
          
        } catch (e) {
          return; 
        }
        
        e.preventDefault();
        
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
            progress = 90; 
          }
          loading.updateProgress(Math.min(progress, 90));
        }, 150);
        
        const minLoadTime = 1200;
        
        setTimeout(() => {
          loading.updateProgress(100);
          setTimeout(() => {
            window.location.href = link.href;
          }, 300);
        }, minLoadTime);
      }
    });

    document.addEventListener('submit', (e) => {
      if (e.target.closest('.swal2-container, .swal2-popup, .modal')) {
        return;
      }
      
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

document.addEventListener('DOMContentLoaded', () => {
  AdminLoading.init();
  
  const loadingGif = document.querySelector('.loading-gif');
  if (loadingGif) {
    loadingGif.style.transition = 'opacity 0.7s ease 0.3s';
  }
  
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      setTimeout(() => {
        AdminLoading.hideManual();
      }, 500);
    }
  });
});

window.AdminLoading = AdminLoading;
</script>
    </body>
    </html>