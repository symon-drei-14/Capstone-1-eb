<?php
require_once __DIR__ . '/include/check_access.php';
checkAccess(); // No role needed—logic is handled internally
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="include/css/sidenav.css">
    <link rel="stylesheet" href="include/css/loading.css">
    <link rel="stylesheet" href="include/css/informationmanagement.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
</head>
<style>

</style>

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
<footer class="site-footer">

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
    </div>
</footer>
</body>
</html>
