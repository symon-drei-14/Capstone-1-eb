<div class="sidebar <?php echo ($_SESSION['role'] ?? '') === 'Full Admin' ? 'is-full-admin' : ''; ?>">
    <?php
    // The navigation items for the sidebar
    $navItems = [
        ['href' => 'dashboard.php', 'icon' => '🏠', 'text' => 'Home'],
        ['href' => 'drivermanagement.php', 'icon' => '🚗', 'text' => 'Driver Management'],
        ['href' => 'fleetmanagement.php', 'icon' => '🚛', 'text' => 'Fleet Management'],
        ['href' => 'triplogs.php', 'icon' => '📋', 'text' => 'Trip Management'],
        ['href' => 'tracking.php', 'icon' => '📍', 'text' => 'Tracking'],
        ['href' => 'maintenance.php', 'icon' => '🔧', 'text' => 'Maintenance Scheduling'],
        ['href' => 'informationmanagement.php', 'icon' => '📈', 'text' => 'Information Management'],
    ];
    
    $adminItem = ['href' => 'adminmanagement.php', 'icon' => '⚙️', 'text' => 'Admin Management'];
    $userRole = $_SESSION['role'] ?? 'guest';
    global $allowedRoles;

    foreach ($navItems as $item) {
        $page = $item['href'];

        $hasAccess = (
            $userRole === 'Full Admin' ||
            !isset($allowedRoles[$page]) ||
            (isset($allowedRoles[$page]) && in_array($userRole, $allowedRoles[$page]))
        );

        if ($hasAccess) {
            echo '<div class="sidebar-item">';
            echo '    <i class="icon2">' . htmlspecialchars($item['icon']) . '</i>';
            echo '    <a href="' . htmlspecialchars($item['href']) . '">' . htmlspecialchars($item['text']) . '</a>';
            echo '</div>';
        }
    }
    
    if ($userRole === 'Full Admin') {
  
        echo '<div class="sidebar-item">';
        echo '    <i class="icon2">' . htmlspecialchars($adminItem['icon']) . '</i>';
        echo '    <a href="' . htmlspecialchars($adminItem['href']) . '">' . htmlspecialchars($adminItem['text']) . '</a>';
        echo '</div>';
    }
    ?>
     <div class="logout-section">
            <hr> 

   <div class="sidebar-item logout-item">
   
    <i class="icon2">🚪</i>
    <a href="include/handlers/logout.php" data-no-loading="true">Logout</a>
</div>
</div>
</div>