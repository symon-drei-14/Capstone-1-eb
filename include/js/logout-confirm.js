// include/js/logout-confirm.js
document.addEventListener('DOMContentLoaded', function() {
    // Find all logout links in the page
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
    
    // Add click event listeners to each logout link
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent immediate navigation
            
            // Show confirmation dialog
            Swal.fire({
                title: 'Logout Confirmation',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#B82132',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If confirmed, proceed with logout
                    window.location.href = link.href;
                }
            });
        });
    });
});