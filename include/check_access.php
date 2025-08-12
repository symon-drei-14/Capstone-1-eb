<?php
function checkAccess($requiredRole = null) {
    session_start();
    
    // Redirect to login if not logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: ../login.php");
        exit();
    }

    // Store user role in a JavaScript-accessible variable
    echo '<script>window.userRole = "' . ($_SESSION['role'] ?? '') . '";</script>';

    // Full Admin bypasses all checks
    if ($_SESSION['role'] === 'Full Admin') {
        return true;
    }

    // Role-based access control
    $allowedRoles = [
        'fleetmanagement.php'    => ['Full Admin', 'Fleet Manager'],
        'drivermanagement.php'   => ['Full Admin', 'Operations Manager'],
        'triplogs.php'           => ['Full Admin', 'Operations Manager'],
        'tracking.php'           => ['Full Admin', 'Operations Manager'],
        'maintenance.php'        => ['Full Admin', 'Fleet Manager'],
        'fleetperformance.php'   => ['Full Admin', 'Fleet Manager'],
        'adminmanagement.php'    => ['Full Admin'] // Only Full Admin
    ];

    $currentPage = basename($_SERVER['PHP_SELF']);

    // Check if user's role is allowed for the current page
    if (isset($allowedRoles[$currentPage]) && !in_array($_SESSION['role'], $allowedRoles[$currentPage])) {
        header("Location: dashboard.php");
        exit();
    }

    // Additional specific role checks if needed
    if ($requiredRole !== null && $_SESSION['role'] !== $requiredRole) {
        header("Location: dashboard.php");
        exit();
    }

    return true;
}
?>