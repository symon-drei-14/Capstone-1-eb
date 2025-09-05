<?php
function checkAccess($requiredRole = null) {
    session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
ini_set('session.use_strict_mode', 1);
session_start();
    
    // Check session validity
    if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > 1800)) {
        // Session expires after 30 minutes of inactivity
        session_unset();
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
    
    $_SESSION['last_activity'] = time(); // Update last activity time
    
    // Redirect to login if not logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: ../login.php");
        exit();
    }

    // Store user role in a JavaScript-accessible variable with escaping
    echo '<script>window.userRole = "' . htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8') . '";</script>';

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
        'informationmanagement.php'   => ['Full Admin', 'Fleet Manager'],
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