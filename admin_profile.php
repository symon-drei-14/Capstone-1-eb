<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess();

// Assume the logged-in admin's ID is stored in the session
$loggedInAdminId = $_SESSION['admin_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="include/css/sidenav.css">
    <link rel="stylesheet" href="include/css/loading.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="include/css/adminmanagement.css">
    <link rel="stylesheet" href="include/css/admin_profile.css"> <!-- New Profile Styles -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <div class="profile" onclick="window.location.href='admin_profile.php'" style="cursor: pointer;"> <!-- Added click event to go to profile page -->
     <?php 
     if (isset($_SESSION['admin_pic']) && !empty($_SESSION['admin_pic'])) {
         echo '<img id="headerProfilePhoto" src="data:image/jpeg;base64,' . $_SESSION['admin_pic'] . '" alt="Admin Profile" class="profile-icon">';
     } else {
         echo '<img id="headerProfilePhoto" src="include/img/profile.png" alt="Admin Profile" class="profile-icon">';
     }
     ?>
     <div class="profile-name" id="headerProfileName">
         <?php 
             echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
         ?>
     </div>
</div>
</div>
</header>
 <?php require_once __DIR__ . '/include/sidebar.php'; ?>
     <div id="sidebar-backdrop" class="backdrop"></div>

     <!-- Main Profile Content -->
     <div class="profile-main-content">
         <h3><i class="fa-solid fa-user-gear"></i> My Admin Profile</h3>
         
         <div class="profile-card">
             <div class="profile-photo-display">
                 <!-- Profile photo dynamically loaded by JS -->
                 <img id="currentProfilePhoto" src="include/img/profile.png" alt="Admin Photo" class="profile-photo">
             </div>

             <div class="profile-info-grid">
                 <div class="info-item">
                     <label>Username</label>
                     <p id="displayUsername"></p>
                 </div>
                 <div class="info-item">
                     <label>Email</label>
                     <p id="displayEmail"></p>
                 </div>
                 <div class="info-item">
                     <label>Role</label>
                     <p id="displayRole"></p>
                 </div>
                 <div class="info-item">
                     <label>Password (Hidden for Security)</label>
                     <p class="password-display">************</p>
                 </div>
             </div>

             <button class="edit-profile-btn" onclick="openAdminProfileModal()">
                 <i class="fas fa-edit"></i> Edit Profile
             </button>
         </div>

     </div>

     <!-- EDIT PROFILE MODAL (Copied and adapted from adminmanagement.php) -->
     <div id="adminProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="profileModalTitle">Edit My Profile</h2>
                <span class="close" onclick="closeModal('adminProfileModal')">&times;</span>
            </div>

            <form id="profileForm">
                <input type="hidden" id="profileAdminId" name="adminId">

                <div class="form-group">
                    <label for="profileAdminProfile">Profile Photo (Max 2MB)</label>
                    <input type="file" id="profileAdminProfile" name="adminProfile" accept="image/*">
                    <div id="profileAdminProfilePreview" style="margin-top: 10px;"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="profileUsername">Username *</label>
                        <input type="text" id="profileUsername" name="username" class="form-control" required>
                    </div>
                    <!-- Role is only displayed on the profile page, not editable here as it's sensitive -->
                    <div class="form-group">
                         <label for="profileRole">Role</label>
                         <input type="text" id="profileRole" name="role" class="form-control" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label for="profileAdminEmail">Email *</label>
                    <input type="email" id="profileAdminEmail" name="adminEmail" class="form-control" required placeholder="admin@example.com">
                </div>

                 <!-- REQUIRED FIELDS FOR PASSWORD UPDATE -->
                 <div id="oldPasswordGroup" class="form-group">
                    <label for="profileOldPassword" id="oldPasswordLabel">Current Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="profileOldPassword" name="oldPassword" class="form-control">
                        <i class="fa-regular fa-eye toggle-password"></i>
                    </div>
                    <small id="oldPasswordHelp" style="color: #d33; font-weight: 500;">Only required if you are changing your password.</small>
                 </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="profilePassword" id="profilePasswordLabel">New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="profilePassword" name="password" class="form-control">
                            <i class="fa-regular fa-eye toggle-password"></i>
                        </div>
                        <small id="profilePasswordHelp">Leave blank to keep current password.</small>
                    </div>
                    <div class="form-group">
                        <label for="profileConfirmPassword">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="profileConfirmPassword" name="confirmPassword" class="form-control">
                            <i class="fa-regular fa-eye toggle-password"></i>
                        </div>
                    </div>
                </div>
            </form>

            <div class="modal-footer">
                <div class="button-group">
                    <button type="button" class="cancel-btn" onclick="closeModal('adminProfileModal')">Cancel</button>
                    <button type="button" class="save-btn" onclick="saveAdminProfile()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    
     <!-- OTP Verification Modal (Reusing existing OTP styles) -->
    <div id="otpModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Verify Changes (OTP)</h2>
                <span class="close" onclick="closeModal('otpModal')">&times;</span>
            </div>
            <div style="padding: 20px 25px;">
                <p style="text-align: center; margin-bottom: 20px;">A 6-digit verification code has been sent to your **registered email address**.</p>
                <div class="otp-container">
                    <input type="number" id="otp-1" class="otp-input" maxlength="1" oninput="moveToNext(this, 'otp-2')" onfocus="this.select()" />
                    <input type="number" id="otp-2" class="otp-input" maxlength="1" oninput="moveToNext(this, 'otp-3')" onfocus="this.select()" />
                    <input type="number" id="otp-3" class="otp-input" maxlength="1" oninput="moveToNext(this, 'otp-4')" onfocus="this.select()" />
                    <input type="number" id="otp-4" class="otp-input" maxlength="1" oninput="moveToNext(this, 'otp-5')" onfocus="this.select()" />
                    <input type="number" id="otp-5" class="otp-input" maxlength="1" oninput="moveToNext(this, 'otp-6')" onfocus="this.select()" />
                    <input type="number" id="otp-6" class="otp-input" maxlength="1" oninput="validateOtpInputs()" onfocus="this.select()" />
                </div>
                <div id="otp-error" class="otp-error"></div>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                 <button type="button" class="save-btn" onclick="confirmOtp()">Verify Code</button>
            </div>
        </div>
    </div>


    <!-- Loading and Footer (Copied from adminmanagement.php) -->
    <script>
         // --- GLOBAL FUNCTIONS (Modal/Loading) ---
         function openModal(modalId) {
             document.getElementById(modalId).style.display = "block";
         }
         
         function closeModal(modalId) {
             const modalToClose = document.getElementById(modalId);
             if (!modalToClose) return;

             // Reset OTP fields when closing the OTP modal
             if (modalId === 'otpModal') {
                 document.querySelectorAll('.otp-input').forEach(input => {
                     input.value = '';
                     input.classList.remove('is-invalid');
                 });
                 document.getElementById('otp-error').classList.remove('show');
             }

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
         
         // Helper function to move focus between OTP inputs
         function moveToNext(currentInput, nextInputId) {
             if (currentInput.value.length === currentInput.maxLength) {
                 const nextInput = document.getElementById(nextInputId);
                 if (nextInput) {
                     nextInput.focus();
                 } else {
                     validateOtpInputs();
                 }
             }
         }
         // Helper to check if all OTP fields are filled
         function validateOtpInputs() {
             const otpInputs = document.querySelectorAll('#otpModal .otp-input');
             let otp = '';
             otpInputs.forEach(input => otp += input.value);
             return otp.length === 6;
         }

         // --- PROFILE SPECIFIC FUNCTIONS ---
         
         let pendingFormData = null; // To hold form data during OTP flow

         function fetchAdminProfile() {
             const adminId = <?php echo json_encode($loggedInAdminId); ?>;
             if (!adminId) {
                 Swal.fire('Error', 'Admin ID not found in session.', 'error');
                 return;
             }

             // Using the existing get_admin.php handler
             fetch(`include/handlers/get_admin.php?id=${adminId}`)
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         const admin = data.admin;
                         // Display values on the main page
                         document.getElementById('displayUsername').textContent = admin.username;
                         document.getElementById('displayEmail').textContent = admin.admin_email || 'N/A';
                         document.getElementById('displayRole').textContent = admin.role || 'N/A';
                         
                         // Update profile photo
                         const photoEl = document.getElementById('currentProfilePhoto');
                         if (admin.admin_pic) {
                             photoEl.src = 'data:image/jpeg;base64,' + admin.admin_pic;
                         } else {
                             photoEl.src = 'include/img/profile.png';
                         }
                         
                         // Set values in the modal form
                         document.getElementById('profileAdminId').value = admin.admin_id;
                         document.getElementById('profileUsername').value = admin.username;
                         document.getElementById('profileAdminEmail').value = admin.admin_email || '';
                         document.getElementById('profileRole').value = admin.role || 'N/A';
                     } else {
                         Swal.fire('Error', `Error fetching profile: ${data.message}`, 'error');
                     }
                 })
                 .catch(error => console.error('Error:', error));
         }
         
         // --- NEW FUNCTION: Manually update the header (name/photo) ---
         function updateHeaderProfile(updatedUsername, updatedPhotoBase64) {
            const headerNameEl = document.getElementById('headerProfileName');
            const headerPhotoEl = document.getElementById('headerProfilePhoto');
            
            if (headerNameEl && updatedUsername) {
                headerNameEl.textContent = updatedUsername;
            }
            
            if (headerPhotoEl && updatedPhotoBase64) {
                headerPhotoEl.src = 'data:image/jpeg;base64,' + updatedPhotoBase64;
            } else if (headerPhotoEl && updatedPhotoBase64 === '') {
                 // Reset to default if photo was somehow cleared (though unlikely here)
                 headerPhotoEl.src = 'include/img/profile.png';
            }
         }
         // Helper to update the label for 'Current Password' dynamically
         function updateOldPasswordLabel(isPasswordChanging) {
             const label = document.getElementById('oldPasswordLabel');
             const help = document.getElementById('oldPasswordHelp');
             const input = document.getElementById('profileOldPassword');
             
             if (isPasswordChanging) {
                 label.innerHTML = 'Current Password <span style="color:red;">*</span> (Required to change password)';
                 help.style.display = 'none';
                 input.setAttribute('required', 'required');
             } else {
                 label.innerHTML = 'Current Password (Only required if changing password)';
                 help.style.display = 'block';
                 input.removeAttribute('required');
             }
         }
         
         // Event listener to trigger label update when new password changes
         document.addEventListener('DOMContentLoaded', () => {
             const newPasswordInput = document.getElementById('profilePassword');
             if (newPasswordInput) {
                 newPasswordInput.addEventListener('input', () => {
                     const isPasswordChanging = newPasswordInput.value.length > 0;
                     updateOldPasswordLabel(isPasswordChanging);
                 });
             }
             // Initial setup for when modal opens
             updateOldPasswordLabel(false);
         });

         function openAdminProfileModal() {
             const form = document.getElementById('profileForm');
             form.reset();
             
             // Reset preview section
             const previewEl = document.getElementById('profileAdminProfilePreview');
             const photoSrc = document.getElementById('currentProfilePhoto').src;
             const initialPhotoHtml = `
                <div class="current-profile-section">
                    <h4>Current Profile Picture:</h4>
                    <div class="large-profile-display">
                        <img src="${photoSrc}" class="large-profile-preview" alt="Current Admin Photo">
                    </div>
                </div>`;
             previewEl.innerHTML = initialPhotoHtml;
             
             // Fetch and populate data (though already fetched for the display, this ensures freshness)
             fetchAdminProfile(); 
             
             // Reset password fields and visibility
             document.getElementById('profilePassword').required = false;
             document.getElementById('profileConfirmPassword').required = false;
             document.getElementById('profileOldPassword').required = false; 
             document.getElementById('profileOldPassword').value = '';
             
             // Ensure label is initially set to optional state
             updateOldPasswordLabel(false); 
             
             openModal('adminProfileModal');
         }

        function saveAdminProfile() {
            const form = document.getElementById('profileForm');
            const username = document.getElementById('profileUsername').value;
            const adminEmail = document.getElementById('profileAdminEmail').value;
            const oldPassword = document.getElementById('profileOldPassword').value;
            const newPassword = document.getElementById('profilePassword').value;
            const confirmPassword = document.getElementById('profileConfirmPassword').value;
            const profileInput = document.getElementById('profileAdminProfile');

            if (!username || !adminEmail) {
                Swal.fire('Validation Error', 'Username and email are required.', 'warning');
                return;
            }
            if (newPassword !== confirmPassword) {
                Swal.fire('Validation Error', 'New passwords do not match.', 'warning');
                return;
            }

            // Determine if a sensitive change (email or password) is being made
            const isEmailChanging = adminEmail !== document.getElementById('displayEmail').textContent;
            const isPasswordChanging = newPassword.length > 0;
            
            // The overall trigger for OTP is EITHER email or password change
            const requiresOtp = isEmailChanging || isPasswordChanging;

            // Check for current password ONLY if password is changing
            if (isPasswordChanging) {
                if (!oldPassword) {
                    Swal.fire('Security Required', 'Please enter your **Current Password** to set a new one.', 'warning');
                    document.getElementById('profileOldPassword').focus();
                    return;
                }
            }
            
            // Disable button and show saving status in the button itself (not the Mansar overlay)
            const saveButton = document.querySelector('#adminProfileModal .save-btn');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            pendingFormData = new FormData(form);
            pendingFormData.delete('confirmPassword'); // We don't need this on the server
            pendingFormData.delete('role'); // Role is readonly

            // If the user isn't changing email or password, but just name/photo, we skip OTP and submit directly
            if (!requiresOtp) {
                // Remove oldPassword from FormData if not needed to avoid server processing
                pendingFormData.delete('oldPassword'); 
                submitProfileUpdate(pendingFormData, saveButton);
            } else {
                // Sensitive change: submit for OTP initiation
                Swal.fire({
                    title: 'Sending Verification Code...',
                    text: 'Please wait while we send the OTP to your registered email.', 
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                        
                        fetch('include/handlers/admin_profile_handler.php', {
                            method: 'POST',
                            body: pendingFormData
                        })
                        .then(response => response.json())
                        .then(data => {
                            Swal.close(); 
                            if (data.success) {
                                if (data.otp_required) {
                                    closeModal('adminProfileModal');
                                    openModal('otpModal');
                                    Swal.fire('Security Check', data.message, 'info'); 
                                } else {
                                    Swal.fire('Success!', data.message, 'success').then(() => {
                                        closeModal('adminProfileModal');                                
                                        fetchAdminProfile();
                                        
                                    });
                                }
                            } else {
                                Swal.fire('Error!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            Swal.close();
                            console.error('Error:', error);
                            Swal.fire('Request Failed', 'A network error occurred during verification.', 'error');
                        })
                        .finally(() => {
                            // Re-enable the Save button in the profile modal after this first step
                            saveButton.disabled = false;
                            saveButton.innerHTML = 'Save Changes';
                        });
                    }
                });
            }
        }
        
        function submitProfileUpdate(formData, saveButton) {
            
            // We'll use a Swal for the final update step for consistency
            Swal.fire({
                title: 'Updating Profile...',
                text: 'Applying final changes to your account.',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('include/handlers/admin_profile_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close(); // Close the "Updating Profile" Swal
                if (data.success) {
                    
                    Swal.fire('Success!', data.message, 'success').then(() => {
                        
                        // Close modals and refresh profile data immediately
                        closeModal('adminProfileModal');
                        closeModal('otpModal');
                        fetchAdminProfile();
                        
                        // *** FIX: DO NOT RELOAD THE PAGE/SHOW MANSAR LOADER ***
                        // Instead, manually update the header using the returned data.
                        if (data.updated_username || data.updated_photo_base64) {
                            updateHeaderProfile(data.updated_username, data.updated_photo_base64);
                        }
                        
                        // Since we are no longer reloading the page, we don't need AdminLoading here.
                        
                        // if (data.reload_required) {
                        //    AdminLoading.startAction('Reloading Page', 'Updating navigation bar...');
                        //    setTimeout(() => location.reload(), 1000); 
                        // }
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire('Request Failed', 'A network error occurred while saving the profile.', 'error');
            })
            .finally(() => {
                // Ensure the OTP verification button is re-enabled if the second step fails.
                const verifyButton = document.querySelector('#otpModal .save-btn');
                if (verifyButton) {
                    verifyButton.disabled = false;
                    verifyButton.innerHTML = 'Verify Code';
                }
                
                // Re-enable the main profile modal button if it was a non-sensitive update
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.innerHTML = 'Save Changes';
                }
            });
        }


        function confirmOtp() {
             const otpInputs = document.querySelectorAll('#otpModal .otp-input');
             let otp = '';
             otpInputs.forEach(input => otp += input.value);
             
             if (otp.length !== 6) {
                 const errorEl = document.getElementById('otp-error');
                 errorEl.textContent = 'Please enter the complete 6-digit OTP.';
                 errorEl.classList.add('show');
                 document.getElementById('otp-1').focus();
                 document.getElementById('otpModal').classList.add('swal2-shake');
                 setTimeout(() => document.getElementById('otpModal').classList.remove('swal2-shake'), 500);
                 return;
             }
             
             const verifyButton = document.querySelector('#otpModal .save-btn');
             verifyButton.disabled = true;
             verifyButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

             // Add OTP to the pending form data
             const finalFormData = pendingFormData || new FormData();
             finalFormData.append('otp', otp);
             finalFormData.append('adminId', document.getElementById('profileAdminId').value);


             submitProfileUpdate(finalFormData, null); 
        }

        // --- GENERAL SETUP ---
        document.addEventListener('DOMContentLoaded', () => {
             // Initial fetch
             fetchAdminProfile();
             
             // Bind image change handler to the modal file input
             document.getElementById('profileAdminProfile').addEventListener('change', (e) => {
                  handleProfileImageChange(e, document.getElementById('profileAdminProfilePreview'));
             });
             
             // Bind password toggles for the modal
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

             // Bind OTP inputs for smooth typing
             const otpInputs = document.querySelectorAll('.otp-input');
             otpInputs.forEach((input, index) => {
                 input.addEventListener('keydown', (e) => {
                     if (e.key === 'Backspace' && input.value === '') {
                         const prevInput = otpInputs[index - 1];
                         if (prevInput) {
                             prevInput.focus();
                         }
                     }
                 });
             });
             
             // Initialize loading animation logic
             AdminLoading.init();
             
             // Sidebar and Date/Time logic (copied from adminmanagement.php)
             updateDateTime();
             setInterval(updateDateTime, 1000);

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
         
        function handleProfileImageChange(e, previewElement) {
            const file = e.target.files[0];
            const maxFileSize = 2 * 1024 * 1024; 

            const previewImg = previewElement.querySelector('.large-profile-preview');
            const titleHeader = previewElement.querySelector('h4');
            const headerPhotoEl = document.getElementById('headerProfilePhoto');

            const originalSrc = document.getElementById('currentProfilePhoto').src;

            if (!previewImg || !titleHeader || !headerPhotoEl) {
                console.error("Preview elements could not be found!");
                return;
            }

            if (file) {

                if (file.size > maxFileSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Please select an image smaller than 2MB.'
                    });
                    e.target.value = ''; 

                    previewImg.src = originalSrc;
                    titleHeader.textContent = 'Current Profile Picture:';
                    headerPhotoEl.src = originalSrc;
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    const newImageSrc = event.target.result;
                    previewImg.src = event.target.result;
                    titleHeader.textContent = 'New Profile Preview:';
                    headerPhotoEl.src = newImageSrc;
                };
                reader.readAsDataURL(file);
            } 
            else {
                previewImg.src = originalSrc;
                titleHeader.textContent = 'Current Profile Picture:';
                headerPhotoEl.src = newImageSrc;
            }
        }

         function updateDateTime() {
             const now = new Date();
             const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
             document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
             document.getElementById('current-time').textContent = now.toLocaleTimeString();
         }

     </script>
     
     <!-- Include the same loading script logic -->
    <script>

  const AdminLoading = {
  init() {
    this.loadingEl = document.getElementById('admin-loading');
    this.titleEl = this.loadingEl.querySelector('.loading-title');
    this.messageEl = this.loadingEl.querySelector('.loading-message');
    this.progressBar = this.loadingEl.querySelector('.progress-bar');
    this.progressText = this.loadingEl.querySelector('.progress-text');
    
    this.checkForIncomingNavigation();
    this.setupNavigationInterception();
  },
  
  checkForIncomingNavigation() {
    const shouldShowLoading = sessionStorage.getItem('showAdminLoading');
    
    if (shouldShowLoading) {
      sessionStorage.removeItem('showAdminLoading');
      this.show('Loading Page', 'Loading content...');
      
      let progress = 0;
      const progressInterval = setInterval(() => {
        progress += Math.random() * 25 + 10;
        this.updateProgress(Math.min(progress, 100));
        
        if (progress >= 100) {
          clearInterval(progressInterval);
          setTimeout(() => this.hide(), 600);
        }
      }, 180);
    }
  },
  
  show(title = 'Processing Request', message = 'Please wait...') {
    if (!this.loadingEl) return;
    this.titleEl.textContent = title;
    this.messageEl.textContent = message;
    this.updateProgress(0);
    this.loadingEl.style.display = 'flex';
    setTimeout(() => this.loadingEl.classList.add('active'), 50);
  },
  
  hide() {
    if (!this.loadingEl) return;
    this.loadingEl.classList.remove('active');
    setTimeout(() => this.loadingEl.style.display = 'none', 800);
  },
  
  updateProgress(percent) {
    if (this.progressBar) this.progressBar.style.width = `${percent}%`;
    if (this.progressText) this.progressText.textContent = `${Math.round(percent)}%`;
  },
  
  setupNavigationInterception() {
    document.addEventListener('click', (e) => {
      // Exclude clicks inside modals, dropdowns, etc.
      if (e.target.closest('.swal2-container, .modal, .dropdown')) {
        return;
      }
      
      const link = e.target.closest('a');
      if (link && !link.hasAttribute('data-no-loading') && link.href && !link.href.startsWith('javascript:') && !link.href.startsWith('#')) {
        try {
          const linkUrl = new URL(link.href);
          const currentUrl = new URL(window.location.href);
          
          if (linkUrl.origin !== currentUrl.origin) return; // External link
          if (linkUrl.pathname === currentUrl.pathname) return; // Same page
          
        } catch (err) {
          return; // Invalid URL
        }
        
        e.preventDefault();
        sessionStorage.setItem('showAdminLoading', 'true');
        
        const loading = this.startAction('Loading Page', `Preparing ${link.textContent.trim() || 'page'}...`);
        let progress = 0;
        const progressInterval = setInterval(() => {
          progress += Math.random() * 40;
          if (progress >= 90) clearInterval(progressInterval);
          loading.updateProgress(Math.min(progress, 100));
        }, 300);
        
        setTimeout(() => {
          loading.updateProgress(100);
          setTimeout(() => window.location.href = link.href, 300);
        }, 1200);
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
 
      // Initialize when DOM is loaded
      document.addEventListener('DOMContentLoaded', () => {
          AdminLoading.init();
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


<footer class="site-footer">
     <div class="footer-bottom">
         <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
     </div>
</footer>
</body>
</html>