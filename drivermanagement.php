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
    </header>
<?php require_once __DIR__ . '/include/sidebar.php'; ?>
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
                                <th>Created At</th>
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
            <span class="close" onclick="closeModal()">×</span>
            <h2 id="modalTitle">Add Driver</h2>

    

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
    
                
       <div class="form-group" id="oldPasswordGroup" style="display: none;">
    <label for="oldPassword">Current Password *</label>
    <div class="password-wrapper">
        <input type="password" id="oldPassword" name="oldPassword">
        <i class="fa-regular fa-eye toggle-password"></i>
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

        // Update immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

       function openAddDriverModal() {
    document.getElementById("modalTitle").textContent = "Add New Driver";
    document.getElementById("modalMode").value = "add";
    document.getElementById("saveButtonText").textContent = "Add Driver";
    document.getElementById("passwordHelp").style.display = "none";
    document.getElementById("password").required = true;
    document.getElementById("oldPasswordGroup").style.display = "none";
    document.getElementById("password").required = true;
    document.getElementById("confirmPassword").required = true;
    
    // Clear all fields
    document.getElementById("driverId").value = "";
    document.getElementById("driverName").value = "";
    document.getElementById("driverEmail").value = "";
    document.getElementById("driverContact").value = "";
    
    // Explicitly clear all password fields
    document.getElementById("oldPassword").value = "";
    document.getElementById("password").value = "";
    document.getElementById("confirmPassword").value = "";
    
    document.getElementById("assignedTruck").value = "";
    document.getElementById("driverProfile").value = ""; // Clear file input
    document.getElementById("profilePreview").innerHTML = ""; // Clear preview

    // Populate available trucks for a new driver
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
    // Clear existing highlights
    document.querySelectorAll('.highlight').forEach(el => {
        el.outerHTML = el.innerHTML;
    });
    
    $('#driverTableBody').empty();
    
    // Calculate pagination
    totalPages = Math.ceil(driversData.length / rowsPerPage);
    var startIndex = (currentPage - 1) * rowsPerPage;
    var endIndex = startIndex + rowsPerPage;
    var pageData = driversData.slice(startIndex, endIndex);
    
    if (pageData.length > 0) {
        pageData.forEach(function(driver) {
            let formattedLastLogin = formatTime(driver.last_login);
            
       var row = "<tr>" +
                "<td data-label='Profile'>" + (driver.driver_pic ? '<img src="data:image/jpeg;base64,' + driver.driver_pic + '" class="driver-photo">' : '<i class="fa-solid fa-circle-user profile-icon"></i>') + "</td>" +
                "<td data-label='ID'>" + driver.driver_id + "</td>" +
                "<td data-label='Name'>" + driver.name + "</td>" +
                "<td data-label='Email'>" + driver.email + "</td>" +
                "<td data-label='Contact No.'>" + (driver.contact_no || 'N/A') + "</td>" +
                "<td data-label='Assigned Truck'>" + (driver.assigned_truck_id || 'None') + "</td>" +
                "<td data-label='Total Trips'>" + (driver.total_completed || 0) + "</td>" +
                "<td data-label='Monthly Trips'>" + (driver.monthly_completed || 0) + "</td>" +
                "<td data-label='Created At'>" + formatDateWithTime(driver.created_at) + "</td>" +
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
    
    // Ensure current page is within bounds
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
    // Remove any existing event listeners first
    $('#prevPageBtn').off('click');
    $('#nextPageBtn').off('click');
    
    // Previous button
    $('#prevPageBtn').on('click', function() {
        changePage(-1);
    });

    // Next button
    $('#nextPageBtn').on('click', function() {
        changePage(1);
    });
    
    // FIXED: Page number clicks using event delegation with data attribute
    $(document).off('click', '.page-number');
    $(document).on('click', '.page-number', function() {
        var page = parseInt($(this).data('page'));
        if (!isNaN(page) && page !== currentPage) {
            goToPage(page);
        }
    });
    
    // Initial load
    fetchDrivers();
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
                
                document.getElementById("oldPasswordGroup").style.display = "block";
                document.getElementById("password").required = false;
                document.getElementById("confirmPassword").required = false;
                
                document.getElementById("driverId").value = driver.driver_id;
                document.getElementById("driverContact").value = driver.contact_no || '';
                document.getElementById("driverName").value = driver.name;
                document.getElementById("driverEmail").value = driver.email;
                
                // Clear password fields every time
                document.getElementById("oldPassword").value = '';
                document.getElementById("password").value = '';
                document.getElementById("confirmPassword").value = '';
                
                // Populate available trucks, passing the driver's ID and their currently assigned truck
                populateAvailableTrucks(driver.driver_id, driver.assigned_truck_id);
                
                // Display existing profile picture if it exists
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
                
                // Clear the file input
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
    
    // Validate passwords FIRST
    if (!validatePassword()) {
        return;
    }
    
    const mode = document.getElementById("modalMode").value;
    const formData = new FormData();
    
    formData.append("name", document.getElementById("driverName").value);
    formData.append("email", document.getElementById("driverEmail").value);
    formData.append("password", document.getElementById("password").value);
    formData.append("confirmPassword", document.getElementById("confirmPassword").value); // Add this
    formData.append("assigned_truck_id", document.getElementById("assignedTruck").value);
    formData.append("contact_no", document.getElementById("driverContact").value);
    formData.append("mode", mode);
    
    // Add old password for edit mode
    if (mode === 'edit') {
        formData.append("oldPassword", document.getElementById("oldPassword").value); // Add this
        formData.append("driverId", document.getElementById("driverId").value);
    }
    
    // Add profile picture if selected
    const profileInput = document.getElementById("driverProfile");
    if (profileInput.files.length > 0) {
        formData.append("driverProfile", profileInput.files[0]);
    }
    
    if (mode === 'add') {
        // For adding new driver, use the same structure as register.js
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
            }
        });
    } else {
        // For editing existing driver
        $.ajax({
            url: 'include/handlers/save_driver.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
    
    // Reset to first page when searching
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
                "<td data-label='Profile'>" + (driver.driver_pic ? '<img src="data:image/jpeg;base64,' + driver.driver_pic + '" class="driver-photo">' : '<i class="fa-solid fa-circle-user profile-icon"></i>') + "</td>" +
                "<td data-label='ID'>" + highlightText(driver.driver_id) + "</td>" +
                "<td data-label='Name'>" + highlightText(driver.name) + "</td>" +
                "<td data-label='Email'>" + highlightText(driver.email) + "</td>" +
                "<td data-label='Contact No.'>" + highlightText(driver.contact_no || 'N/A') + "</td>" +
                "<td data-label='Assigned Truck'>" + highlightText(driver.assigned_truck_id || 'None') + "</td>" +
                "<td data-label='Total Trips'>" + highlightText(driver.total_completed || 0) + "</td>" +
                "<td data-label='Monthly Trips'>" + highlightText(driver.monthly_completed || 0) + "</td>" +
                "<td data-label='Created At'>" + highlightText(formatDateWithTime(driver.created_at)) + "</td>" +
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
    
    // Hide pagination during search
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
    
                const existingContent = profilePreview.querySelector('.current-profile-section');
                
                let newPreviewHtml = `
                    <div class="new-profile-section">
                        <h4>New Profile Picture:</h4>
                        <div class="large-profile-display">
                            <img src="${event.target.result}" 
                                 class="large-profile-preview" 
                                 alt="New Driver Photo">
                            <p>New image selected</p>
                        </div>
                    </div>
                `;
                
                if (existingContent) {
                    // In edit mode - show both existing and new
                    profilePreview.innerHTML = existingContent.outerHTML + newPreviewHtml;
                } else {
                    // In add mode - show only new
                    profilePreview.innerHTML = newPreviewHtml;
                }
            };
            reader.readAsDataURL(file);
        } else {
            // If no file selected and in edit mode, restore original preview
            const mode = document.getElementById("modalMode").value;
            if (mode === 'edit') {
                // Keep existing image display only
                const existingContent = profilePreview.querySelector('.current-profile-section');
                if (existingContent) {
                    profilePreview.innerHTML = existingContent.outerHTML;
                }
            } else {
                // Clear preview in add mode
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
          const toggleBtn = document.getElementById('toggleSidebarBtn');
const sidebar = document.querySelector('.sidebar');

    document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('expanded');
    });

    document.addEventListener('click', function (e) {
    if (
        sidebar.classList.contains('expanded') &&
        !sidebar.contains(e.target) && 
        !toggleBtn.contains(e.target)   
    ) {
        sidebar.classList.remove('expanded');
    }
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
    const oldPassword = document.getElementById('oldPassword').value;
    
    // For edit mode, if password is being changed, old password is required
    if (mode === 'edit' && password && !oldPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Current password is required to set a new password'
        });
        return false;
    }
    
    // Check if passwords match
    if (password !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Passwords do not match'
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
    </body>
    </html>