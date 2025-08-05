    <?php
    require_once __DIR__ . '/include/check_access.php';
    checkAccess(); // No role needed—logic is handled internally
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance Scheduling</title>
        
        <link rel="stylesheet" href="include/css/sidenav.css">
        <link rel="stylesheet" href="include/maintenancestyle.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    <style>

          .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    background-color: #B82132;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: fixed;
    width: 100%;
    max-height: 40px;
    top: 0;
    left: 0;
    z-index: 1200;

}
    
        table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 2px;

    }
    
    .edit-reasons-section {
        display: none;
        margin-top: 20px;
        padding: 15px;

        border-radius: 5px;
        border: 1px solid #ddd;
        margin-bottom:15px;
    }

.form-group2{
   
        margin-top: 10px;
        padding: 15px;

        border-radius: 5px;
        border: 1px solid #ddd;
        margin-bottom:15px;
}

    .reasons-container {
        display: flex;
        flex-direction: column;

    }


    .reason-option {
        display: flex;
        align-items: center;
        background-color: #fff;
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #ddd;

    }

    .reason-option label {
        display: block;
        cursor: pointer;
        margin: 0;
        font-size: 14px;
        flex-grow: 1;
        word-break: break-word;
        margin-top:10px;
        margin-bottom:-10px;
    }


  .reason-option input[type="checkbox"],
.remark-option input[type="checkbox"] {
    margin-right: 0; /* Remove the excessive right margin */
    position: static; /* Remove positioning that was causing issues */
    top: auto; /* Reset positioning */
}



    .other-reason {
        margin-top: 10px;
        padding-left: 20px;

    }   

    .other-reason label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .other-reason textarea {
        width: 90%;
        padding: 8px;

        border-radius: 4px;
        resize: vertical;
        min-height: 60px;
    }

        .view-remarks-btn {
        background-color: #4b77deff;
        color: white;
        border: none;
        padding: 7px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        margin-top: 5px;
        font-weight:bold;
    }



    .view-remarks-btn:hover {
        background-color: #45a049;
    }

    .remarks-modal-content {
        padding: 10px;
        background: white;
        border-radius: 5px;
    }

    .remarks-modal-content h3 {
        margin-top: 0;
        color: #333;
    }

    .remarks-modal-content ul {
        padding-left: 0px;
    }

    .remarks-modal-content li {
        margin-bottom: 8px;
    }

    .remarks-modal-content button {
        background-color: #9f1515ff;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 15px;
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%; 
        max-width:800px;
        border-radius: 20px;
        box-shadow: rgba(0, 0, 0, 0.25) 0px 54px 55px, rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.12) 0px 4px 6px, rgba(0, 0, 0, 0.17) 0px 12px 13px, rgba(0, 0, 0, 0.09) 0px -3px 5px;
        overflow-x: hidden; 
        max-height: 80vh; 
        overflow-y: auto; 

    }


    .remarks-modal-content button:hover {
        background-color: #45a049;
    }

        body{
            margin-top:50px;
            font-family: Arial, sans-serif;
             background-color:#FCFAEE;
        }

        .profile {
    display: flex;
    align-items: center;
    position: relative;
    right: 70px;
    color: #FAF7F3;
}
        @media (max-width: 768px) {
            .sidebar {
                display: none;
                position: absolute;
                z-index: 999;
                background-color: #fff;
                width: 250px;
                height: 100%;
                box-shadow: 2px 0 5px rgba(0,0,0,0.2);
            }

            .sidebar.show {
                display: block;
            }
        }

        .toggle-sidebar-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: white;
            z-index: 1300;
        }


        .sidebar {
            position: fixed;
            top: 1rem;
            left: 0;
            width: 300px; 
            height: 100%;
            background-color: #edf1ed;
            color: #161616 !important;
            padding: 20px;
            box-sizing: border-box;
            overflow-x: hidden;
            overflow-y: auto;
            z-index: 1100;
            border-right: 2px solid #16161627;
            transform: translateX(-100%); 
            transition: transform 0.3s ease;
        }


        .sidebar.expanded {
            transform: translateX(0);
        }

        .sidebar.expanded .sidebar-item a,
        .sidebar.expanded .sidebar-item span {
            visibility: visible;
            opacity: 1;
        }
        .main-content3 {
            margin-top: 80px;
            margin-left: 10px; 
            margin-right: 20px; 
            width: calc(100% - 50px); 
            height: auto;
            max-height: 150vh;
            min-height:150vh;
            transition: margin-left 0.3s ease;
            overflow-y: hidden;
            padding:20px;
        }

        th:nth-child(9), td:nth-child(9) {
            width: 5%; 
        }

        input:invalid, select:invalid {
        border: 1px solid red;
    }

    .error-message {
        color: red;
        font-size: 12px;
        margin-top: 5px;
    }

    .status-filter-container {
        margin: 10px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        
    }

    .status-filter-container label {
        font-weight: bold;
        color: #333;
    }

    .status-filter-container select {
        padding: 5px 10px;
        border-radius: 4px;
        border: 1px solid #ddd;
        background-color: white;
        cursor: pointer;
    }

    .status-filter-container select:focus {
        outline: none;
        border-color: #4b77de;
    }

    /* Update these styles in your CSS section */
    #maintenanceTable th:nth-child(1), 
    #maintenanceTable td:nth-child(1) {
        width: 8%; /* Truck ID */
    }

    #maintenanceTable th:nth-child(2), 
    #maintenanceTable td:nth-child(2) {
        width: 10%; /* License Plate */
    }

    #maintenanceTable th:nth-child(3), 
    #maintenanceTable td:nth-child(3) {
        width: 10%; /* Date of Inspection */
    }

    #maintenanceTable th:nth-child(4), 
    #maintenanceTable td:nth-child(4) {
        width: 15%; /* Remarks */
    }

    #maintenanceTable th:nth-child(5), 
    #maintenanceTable td:nth-child(5) {
        width: 10%; /* Status */
    }

    #maintenanceTable th:nth-child(6), 
    #maintenanceTable td:nth-child(6) {
        width: 12%; /* Supplier */
    }

    #maintenanceTable th:nth-child(7), 
    #maintenanceTable td:nth-child(7) {
        width: 10%; /* Cost */
    }

    #maintenanceTable th:nth-child(8), 
    #maintenanceTable td:nth-child(8) {
        width: 15%; /* Last Modified */
    }

#maintenanceTable th:nth-child(9), 
#maintenanceTable td:nth-child(9) {
    width: 120px; 
    min-width: 120px;
    max-width: 120px;
}

    .remarks-modal-content h4 {
        margin-top: 15px;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }

    .deleted-row {
        background-color: #ffeeee;
    }

    .deleted-row td {
        opacity: 0.7;
    }

    .deleted-row .status-pending,
    .deleted-row .status-completed,
    .deleted-row .status-in-progress,
    .deleted-row .status-overdue {
        text-decoration: line-through;
    }

    .restore {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
    }

    .restore:hover {
        background-color: #45a049;
    }

    .full-delete {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
        margin-top: 5px;
        display: inline-block;
    }

    .full-delete:hover {
        background-color: #c82333;
    }

    .filter-controls {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 10px 0;
        flex-wrap: wrap;
    }

    .status-filter-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }


    .status-filter-container label {
        font-weight: bold;
        color: #333;
    }

    .status-filter-container select {
        padding: 5px 10px;
        border-radius: 4px;
        border: 1px solid #ddd;
        background-color: white;
        cursor: pointer;
    }

    .search-container {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-container .fa-search {
        position: absolute;
        left: 10px;
        color: #aaa;
        pointer-events: none;
    }

    #searchInput {
        padding: 5px 10px 5px 30px;
        border-radius: 4px;
        border: 1px solid #ddd;
        width: 200px;
    }

    #searchInput:focus {
        outline: none;
        border-color: #4b77de;
    }


    .show-deleted-container {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-left: 10px;
    }

    .show-deleted-container label {
        cursor: pointer;
        user-select: none;
    }

    /* Update the actions section */
 .actions {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    justify-content: center;
    align-items: center;
    padding: 0;
    margin: 20;
}


.actions button {
    background: transparent !important;
    border: none !important;
    padding: 0 !important;
    margin: 10px !important;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
}


.actions button:hover {
    transform: scale(1.2);
   
}

.actions button.edit {
    color: rgba(8, 89, 18, 1) !important;
}

.actions button.delete {
    color: #dc3545 !important;
}

.actions button.restore {
    color: #28a745 !important;
}

.actions button.history {
    color: #17a2b8 !important;
}

.actions button.full-delete {
    color: #dc3545 !important;
}


    .datetime-container {
        display: inline-flex;
        flex-direction: row;
        align-items: right;
        justify-content: right;
        margin-left: 45em;
        gap: 20px;  
    }
    
    .date-display {
        font-size: 14px;
        color: #DDDAD0;
        font-weight:bold;   
    }
    
    .time-display {
        font-size: 14px;
        color: #DDDAD0;
          font-weight:bold;   
    }
    
    .profile {
    display: flex;
    align-items: center;
    position: relative;
    right: 50px;
    
}

   .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .header-left {
        flex: 1;
        min-width: 300px;
    }

.stats-cards {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: flex-end;
    margin-top: 20px;
}

.stat-card {
    background: white;
    border-radius: 8px;
   padding: 15px 15px;
    width: 140px;
    box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.stat-card .icon {
    font-size: 18px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff !important;
}

.stat-card.total .icon {
    background-color: #6c757d;
}

.stat-card.pending .icon {
    background-color: #ffc107;
}

.stat-card.in-progress .icon {
    background-color: #17a2b8;
}

.stat-card.completed .icon {
    background-color: #28a745;
}

.stat-card.overdue .icon {
    background-color: #dc3545;
}

.stat-card .content {
    display: flex;
    flex-direction: column;
}

.stat-card .value {
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
}

.stat-card .label {
    font-size: 14px;
    color: #6c757d;
    margin-top: 5px;
}

@media (max-width: 1200px) {
    .stats-cards {
        justify-content: flex-start;
    }
    
    .stat-card {
        width: calc(50% - 10px);
    }
}

@media (max-width: 768px) {
    .stat-card {
        width: 100%;
    }
}

 .remarks-container {
        display: flex;
        flex-direction: column;
        margin-top: 10px;
    }

    .remark-option {
        display: flex;
        align-items: center;
        background-color: #fff;
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #ddd;
        margin-bottom: 5px;
    }

    .remark-option label {
        display: block;
        cursor: pointer;
        margin: 0;
        font-size: 14px;
        flex-grow: 1;
        word-break: break-word;


    }

 

    .other-remark {
        margin-top: 20px;
        padding-left: 20px;
    }

    .other-remark label {
        display: block;
        margin-bottom: 500px;
        font-weight: bold;
    }

    .other-remark textarea {
        width: 90%;
        padding: 8px;
        border-radius: 4px;
        resize: vertical;
        min-height: 60px;
    }

     .icon-btn {
    position: relative; 
}

.icon-btn::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 70%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
    z-index: 9999; 
    pointer-events: none;
    font-family: Arial, sans-serif;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    margin-bottom: 5px;
}

.icon-btn:hover::after {
    opacity: 1;
    visibility: visible;
}


.icon-btn::before {
    content: '';
    position: absolute;
    bottom: calc(100% - 5px);
    left: 50%;
    transform: translateX(-50%);
    border-width: 5px;
    border-style: solid;
    border-color: #333 transparent transparent transparent;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
    z-index: 9999;
}

.icon-btn:hover::before {
    opacity: 1;
    visibility: visible;
}

.edit::after {
    background-color: #085912; 
}

.delete::after {
    background-color: #bd0d1f; 
}
.history::after {
    background-color: #17a2b8; 
}
.restore::after {
    background-color: #28a745; 
}
.full-delete::after {
    background-color: #dc3545; 
}
.company {
    margin-left:-90px;
    height: 110px;
}

.site-footer {
    background-color: #B82132;
    color: white;
    padding: 30px 0 0;
    margin-top: 40px;
    position: relative;
    bottom: 0;
    width: 100%;
}

.footer-bottom {
    text-align: center;
    display:block;
    justify-items:center;
    align-items:center;
    padding: 10px 0;

    
}

.footer-bottom p {
    margin: 0;
    color: #ddd;
    font-size: 16px;
    display:block;
    
}

.form-row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .form-group {
        flex: 1;
        min-width: 200px;
        padding: 0 10px;
        margin-bottom: 15px;
    }
    
    .form-group.full-width {
        flex: 0 0 00%;
    }
    
    .modal-content label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #333;
    }
    
    .modal-content select,
    .modal-content input[type="text"],
    .modal-content input[type="date"],
    .modal-content input[type="number"],
    .modal-content textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
        font-size: 14px;
    }
    
    .modal-content textarea {
        min-height: 80px;
    }
    
    .modal-content .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    
    .modal-content button.submitbtn {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    
    .modal-content button.cancelbtn {
        background-color: #f44336;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    
    .modal-content button:hover {
        opacity: 0.9;
    }
    
.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 10px;
    margin-top: 20px;
    margin-left:2em;
    padding:10px;
  
}
    
  .checkbox-item {
    display: flex;
    align-items: center;
    gap: 20px; 
}

   .checkbox-item input[type="checkbox"] {
    margin: 0; 
    width: auto; 
    position: static; 
}
.checkbox-item label {
    margin: 0; 
    font-weight: normal;
    cursor: pointer; 
    display: inline; 
    vertical-align: middle; 
}

.reminder-item {
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 5px;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.reminder-item:hover {
    transform: translateY(-2px);
}

.reminder-item.overdue {
    border-left: 4px solid #dc3545;
    background-color: #fff5f5;
}

.reminder-item.due-today {
    border-left: 4px solid #ffc107;
    background-color: #fffdf5;
}

.reminder-item.upcoming {
    border-left: 4px solid #17a2b8;
    background-color: #f5fdff;
}

/* Loading animation */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.reminders-list p {
    animation: fadeIn 0.3s ease;
    text-align: center;
    padding: 20px;
    color: #666;
}
   
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
            <a href="fleetperformance.php">Fleet Performance Analytics</a>
        </div>
        <hr>
        <div class="sidebar-item">
            <i class="icon2">⚙️</i>
            <a href="adminmanagement.php">Admin Management</a>
        </div>
        <div class="sidebar-item">
            <i class="icon2">🚪</i>
            <a href="include/handlers/logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content3">
            <div class="dashboard-header">
    <div class="header-left">
        <h2>Preventive Maintenance Scheduling</h2>
        <div class="button-row">
            <button class="add_sched" onclick="openModal('add')">Add Maintenance Schedule</button>
            <button class="reminder_btn" onclick="openRemindersModal()">Maintenance Reminders</button>
        </div>
    </div>
    
  <div class="stats-cards">
    <div class="stat-card pending">
        <div class="icon"><i class="fas fa-clock"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">Pending</div>
        </div>
    </div>
    <div class="stat-card in-progress">
        <div class="icon"><i class="fas fa-spinner"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">In Progress</div>
        </div>
    </div>
    <div class="stat-card completed">
        <div class="icon"><i class="fas fa-check-circle"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">Completed this month</div>
        </div>
    </div>
    <div class="stat-card overdue">
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">Overdue</div>
        </div>
    </div>
    <div class="stat-card total">
        <div class="icon"><i class="fas fa-tools"></i></div>
        <div class="content">
            <div class="value"></div>
            <div class="label">Total</div>
        </div>
    </div>
</div>
</div>
            <div class="filter-controls">
        <div class="status-filter-container">
            <label for="statusFilter">Show:</label>
            <select id="statusFilter" onchange="filterTableByStatus()">
                <option value="all">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="In Progress">In Progress</option>
                <option value="Overdue">Overdue</option>
            </select>
        </div>
        
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search..." onkeyup="searchMaintenance()">
        </div>
        
        <div class="show-deleted-container">
            <input type="checkbox" id="showDeletedCheckbox" onchange="toggleDeletedRecords()">
            <label for="showDeletedCheckbox">Show Deleted Records</label>
        </div>
    </div>
            <br/>
            <br/>
                    <div class="table-container">
                        <table id="maintenanceTable">
                            <thead>
                                <tr>
                                <th onclick="sortByTruckId()" style="cursor:pointer;">
        Truck ID <span id="truckIdSortIcon">⬍</span>
    </th>
                                    <th>License Plate</th>
                                    <th onclick="sortByDate()" style="cursor:pointer;">
                                Date of <br /> Inspection <span id="dateSortIcon">⬍</span>
                                </th>
                                    <th>Remarks</th>
                                    <th>Status</th>
                                    <th>Supplier</th>
                                    <th>Cost</th>
                                    <th>Last Modified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                
                        <div class="pagination">
                        <button class="prev" onclick="changePage(-1)">◄</button> 
                        <div id="page-numbers" class="page-numbers"></div>
                        <button class="next" onclick="changePage(1)">►</button> 
    </div>
                </div>
            </section>
            </div>
        </div>

        <!-- Add/Edit Maintenance Modal -->
       <div id="maintenanceModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Add Maintenance Schedule</h2>
        <form id="maintenanceForm">
            <input type="hidden" id="maintenanceId" name="maintenanceId">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="maintenanceType">Maintenance Type:</label>
                    <select id="maintenanceType" name="maintenanceType" required>
                        <option value="preventive">Preventive Maintenance</option>
                        <option value="emergency">Emergency Repair</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="truckId">Truck ID:</label>
                    <select id="truckId" name="truckId" required>
                        <!-- Will be populated by JavaScript -->
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="licensePlate">License Plate:</label>
                    <input type="text" id="licensePlate" name="licensePlate" readonly>
                </div>
                
                <div class="form-group">
                    <label for="date">Date of Inspection:</label>
                    <input type="date" id="date" name="date" required>
                </div>
            </div>
            
            <div class="form-group2">
                <label>Remarks:</label>
                <div class="checkbox-grid">
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Change Oil" id="remark-oil">
                        <label for="remark-oil">Change Oil</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Change Tires" id="remark-tires">
                        <label for="remark-tires">Change Tires</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Brake Inspection" id="remark-brake">
                        <label for="remark-brake">Brake Inspection</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Engine Check" id="remark-engine">
                        <label for="remark-engine">Engine Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Transmission Check" id="remark-transmission">
                        <label for="remark-transmission">Transmission Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Electrical System Check" id="remark-electrical">
                        <label for="remark-electrical">Electrical System Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Suspension Check" id="remark-suspension">
                        <label for="remark-suspension">Suspension Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="remarks[]" value="Other" id="remark-other">
                        <label for="remark-other">Other</label>
                    </div>
                </div>
                
                <div class="other-remark">
                    <label for="otherRemarkText">Specify other remark:</label>
                    <textarea id="otherRemarkText" name="otherRemarkText" rows="3" placeholder="Enter specific remark"></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Pending" selected>Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="supplier">Supplier:</label>
                    <input type="text" id="supplier" name="supplier">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cost">Cost:</label>
                    <input type="number" id="cost" name="cost" step="0.01">
                </div>
            </div>
            
            <div class="edit-reasons-section">
                <label>Reason for Edit (select all that apply):</label>
                <div class="checkbox-grid">
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Changed maintenance schedule as per supplier availability" id="reason-supplier">
                        <label for="reason-supplier">Supplier availability</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Updated maintenance type based on vehicle condition" id="reason-condition">
                        <label for="reason-condition">Vehicle condition</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Adjusted date due to parts availability" id="reason-parts">
                        <label for="reason-parts">Parts availability</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Updated cost estimate after inspection" id="reason-cost">
                        <label for="reason-cost">Updated cost</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Changed status based on work progress" id="reason-progress">
                        <label for="reason-progress">Work progress</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Updated supplier information" id="reason-supplier-info">
                        <label for="reason-supplier-info">Supplier info</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Corrected data entry error" id="reason-error">
                        <label for="reason-error">Data correction</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" name="editReason" value="Other" id="reason-other">
                        <label for="reason-other">Other</label>
                    </div>
                </div>
                
                <div class="other-reason">
                    <label for="otherReasonText">Specify other reason:</label>
                    <textarea id="otherReasonText" name="otherReasonText" rows="3" placeholder="Enter specific reason for edit"></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="cancelbtn" onclick="closeModal()">Cancel</button>
                <button type="button" class="submitbtn" onclick="saveMaintenanceRecord()">Submit</button>
            </div>
        </form>
    </div>
</div>

        <!-- Maintenance History Modal -->
        <div id="historyModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeHistoryModal()">&times;</span>
                <h2>Maintenance History</h2>
                <div class="history-list" id="historyList">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
        
        <!-- Maintenance Reminders Modal -->
        <div id="remindersModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeRemindersModal()">&times;</span>
                <h2>Maintenance Reminders</h2>
                <div class="reminders-list" id="remindersList">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <div id="remarksModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div id="remarksModalContent"></div>
        </div>
    </div>

        <script>
        
    document.getElementById('otherReasonText').addEventListener('input', function() {
        const otherCheckbox = document.querySelector('input[name="editReason"][value="Other"]');
        if (this.value.trim() !== '') {
            otherCheckbox.checked = true;
        }
    });


    document.querySelector('input[name="editReason"][value="Other"]').addEventListener('change', function() {
        if (!this.checked) {
            document.getElementById('otherReasonText').value = '';
        }
    });
            let currentPage = 1;
            let totalPages = 1;
            let currentTruckId = 0;
            let isEditing = false;
            let trucksList = [];
            let sortTruckIdAsc = true; 
            
        function getLocalDate() {
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000; // offset in milliseconds
        const localISOTime = (new Date(now - offset)).toISOString().slice(0, 10);
        return localISOTime;
    }

    $(document).ready(function() {
        loadMaintenanceData();
        fetchTrucksList();
         updateStatsCards();
    });

      function updateDateTime() {
        const now = new Date();
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
        
        document.getElementById('current-time').textContent = now.toLocaleTimeString();
    }

   
    updateDateTime();
    setInterval(updateDateTime, 1000);

            function validateMaintenanceForm() {
    const requiredFields = [
        {id: 'truckId', name: 'Truck ID'},
        {id: 'date', name: 'Date of Inspection'},
        {id: 'status', name: 'Status'},
        {id: 'maintenanceType', name: 'Maintenance Type'}
    ];
    
    for (const field of requiredFields) {
        const element = document.getElementById(field.id);
        if (!element || !element.value) {
            alert(`Please fill in the ${field.name} field`);
            if (element) element.focus();
            return false;
        }
    }
    
    // Check at least one remark is selected
    const remarkCheckboxes = document.querySelectorAll('input[name="remarks[]"]:checked');
    if (remarkCheckboxes.length === 0) {
        alert("Please select at least one maintenance remark.");
        return false;
    }
    
    // Additional validation - check if date is in the future for new records
    if (!isEditing) {
        const today = new Date();
        const inspectionDate = new Date(document.getElementById("date").value);
        if (inspectionDate < today) {
            alert("Inspection date must be today or in the future");
            document.getElementById("date").focus();
            return false;
        }
    }
    
    return true;
}


            
        function fetchTrucksList() {
        fetch('include/handlers/truck_handler.php?action=getActiveTrucks') // Changed endpoint
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    trucksList = data.trucks;
                    populateTruckDropdown();
                }
            })
            .catch(error => {
                console.error("Error loading trucks:", error);
            });
    }
            
            function populateTruckDropdown() {
                const truckDropdown = document.getElementById('truckId');
                truckDropdown.innerHTML = '';
                
                trucksList.forEach(truck => {
                    const option = document.createElement('option');
                    option.value = truck.truck_id;
                    option.textContent = truck.truck_id ;
                    option.setAttribute('data-plate-no', truck.plate_no);
                    truckDropdown.appendChild(option);
                });
                
                // Add event listener for truck selection change
                truckDropdown.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const plateNo = selectedOption.getAttribute('data-plate-no');
                    document.getElementById('licensePlate').value = plateNo || '';
                });
            }

            let currentStatusFilter = 'all';

    function filterTableByStatus() {
        currentStatusFilter = document.getElementById('statusFilter').value;
        currentPage = 1; 
        loadMaintenanceData();
    }

    
    function loadMaintenanceData() {
        const showDeleted = document.getElementById('showDeletedCheckbox').checked;
        let url = `include/handlers/maintenance_handler.php?action=getRecords&page=${currentPage}`;
        
        if (currentStatusFilter !== 'all') {
            url += `&status=${encodeURIComponent(currentStatusFilter)}`;
        }
        
        if (showDeleted) {
            url += `&showDeleted=1`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(response => {
                renderTable(response.records || []);
                totalPages = response.totalPages || 1;
                currentPage = response.currentPage || 1;
                updatePagination();
            })
            .catch(error => {
                console.error("Error loading data:", error);
                const tableBody = document.querySelector("#maintenanceTable tbody");
                tableBody.innerHTML = '<tr><td colspan="9" class="text-center">Error loading data</td></tr>';
            });
    }

    function fetchAllRecordsForSearch() {
        let url = `include/handlers/maintenance_handler.php?action=getAllRecordsForSearch`;
        
        if (currentStatusFilter !== 'all') {
            url += `&status=${encodeURIComponent(currentStatusFilter)}`;
        }
        
        return fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            });
    }

        function renderTable(data) {
        const tableBody = document.querySelector("#maintenanceTable tbody");
        tableBody.innerHTML = ""; 
        
        if (data.length === 0) {
            const tr = document.createElement("tr");
            tr.innerHTML = '<td colspan="9" class="text-center">No maintenance records found</td>';
            tableBody.appendChild(tr);
            return;
        }

        data.forEach(row => {
            const tr = document.createElement("tr");
            tr.setAttribute('data-status', row.status);
            if (row.is_deleted) {
                tr.classList.add('deleted-row');
            }   
            const actionsCell = row.is_deleted 
                ? `
                    <button class="icon-btn restore" data-tooltip="Restore" onclick="restoreMaintenance(${row.maintenance_id})">
                       <i class="fas fa-trash-restore"></i>
                    </button>
                    ${window.userRole === 'Full Admin' ? 
                    `<button class="icon-btn full-delete" data-tooltip="Permanently Delete" onclick="fullDeleteMaintenance(${row.maintenance_id})">
                        <i class="fa-solid fa-ban"></i>
                    </button>` : ''}
                    <button class="icon-btn history" data-tooltip="View History" onclick="openHistoryModal(${row.truck_id})">
                        <i class="fas fa-history"></i>
                    </button>
                `
                : `
                    <button class="icon-btn edit" data-tooltip="Edit" onclick="openEditModal(${row.maintenance_id}, ${row.truck_id}, '${row.licence_plate || ''}', '${row.date_mtnce}', '${row.remarks}', '${row.status}', '${row.supplier || ''}', ${row.cost}, '${row.maintenance_type || 'preventive'}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="icon-btn delete" data-tooltip="Delete" onclick="deleteMaintenance(${row.maintenance_id})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                    <button class="icon-btn history" data-tooltip="View History" onclick="openHistoryModal(${row.truck_id})">
                        <i class="fas fa-history"></i>
                    </button>
                `;
            
            tr.innerHTML = `
                <td>${row.truck_id}</td>
                <td>${row.licence_plate || 'N/A'}</td>
                <td>${formatDate(row.date_mtnce)}</td>
                <td>${row.remarks}</td>
                <td><span class="status-${row.status.toLowerCase().replace(" ", "-")}">${row.status}</span></td>
                <td>${row.supplier || 'N/A'}</td>
                <td>₱ ${parseFloat(row.cost).toFixed(2)}</td>
                <td>
                    <strong>${row.last_modified_by}</strong><br>
                    ${formatDateTime(row.last_modified_at)}<br>
                    ${(row.edit_reasons || row.delete_reason) ? 
                    `<button class="view-remarks-btn" 
                        data-reasons='${JSON.stringify({
                            editReasons: row.edit_reasons ? JSON.parse(row.edit_reasons) : null,
                            deleteReason: row.delete_reason
                        })}'>View Remarks</button>` : ''}
                </td>
                <td class="actions">
                    ${actionsCell}
                </td>
            `;
            tableBody.appendChild(tr);
        });

        document.querySelectorAll('.view-remarks-btn').forEach(button => {
            button.addEventListener('click', function() {
                showEditRemarks(this.getAttribute('data-reasons'));
            });
        });
    }

    // Add this new function for full delete
    function fullDeleteMaintenance(id) {
        if (confirm("Are you sure you want to PERMANENTLY delete this maintenance record? This cannot be undone!")) {
            fetch(`include/handlers/maintenance_handler.php?action=fullDelete&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Maintenance record permanently deleted!");
                    loadMaintenanceData();
                } else {
                    alert("Error: " + (data.message || "Unknown error"));
                }
            })
            .catch(error => {
                console.error("Error deleting record: " + error);
                alert("Failed to delete maintenance record.");
            });
        }
    }


            function formatDateTime(datetimeString) {
                if (!datetimeString) return 'N/A';
                const date = new Date(datetimeString);
                return date.toLocaleString(); 
            }
        
            function formatDate(dateString) {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            }
            
            function updatePagination() {
                const pageNumbersContainer = document.getElementById("page-numbers");
                pageNumbersContainer.innerHTML = "";

                const createPageButton = (page) => {
                    const pageBtn = document.createElement("div");
                    pageBtn.classList.add("page-number");
                    if (page === currentPage) {
                        pageBtn.classList.add("active");
                    }
                    pageBtn.textContent = page;
                    pageBtn.onclick = () => {
                        if (page !== currentPage) {
                            currentPage = page;
                            loadMaintenanceData();
                        }
                    };
                    pageNumbersContainer.appendChild(pageBtn);
                };

                const addEllipsis = () => {
                    const ellipsis = document.createElement("div");
                    ellipsis.classList.add("page-number");
                    ellipsis.textContent = "...";
                    ellipsis.style.pointerEvents = "none";
                    pageNumbersContainer.appendChild(ellipsis);
                };

                if (totalPages <= 7) {
                    for (let i = 1; i <= totalPages; i++) {
                        createPageButton(i);
                    }
                } else {
                    createPageButton(1);

                    if (currentPage > 4) {
                        addEllipsis();
                    }

                    let startPage = Math.max(2, currentPage - 1);
                    let endPage = Math.min(totalPages - 1, currentPage + 1);

                    for (let i = startPage; i <= endPage; i++) {
                        createPageButton(i);
                    }

                    if (currentPage < totalPages - 3) {
                        addEllipsis();
                    }

                    createPageButton(totalPages);
                }
            }
            
            function changePage(direction) {
                const newPage = currentPage + direction;
                
                if (newPage < 1 || newPage > totalPages) {
                    return;
                }
                
                currentPage = newPage;
                loadMaintenanceData();
            }
            
    function openModal(mode) {
        document.getElementById("maintenanceModal").style.display = "block";
        
        if (mode === 'add') {
            isEditing = false;
            document.getElementById("modalTitle").textContent = "Add Maintenance Schedule";
            document.getElementById("maintenanceForm").reset();
            document.getElementById("maintenanceId").value = "";
            
            // Use the new getLocalDate() function here
            document.getElementById("date").value = getLocalDate();
            document.getElementById("date").setAttribute("min", getLocalDate());
            
            document.getElementById("status").value = "Pending";
            document.getElementById("status").disabled = true;
            
            // Hide edit reasons section for add mode
            document.querySelector('.edit-reasons-section').style.display = 'none';
        }
    }

   function openEditModal(id, truckId, licensePlate, date, remarks, status, supplier, cost, maintenanceType) {
    isEditing = true;
    document.getElementById("modalTitle").textContent = "Edit Maintenance Schedule";
    document.getElementById("maintenanceId").value = id;
    document.getElementById("truckId").value = truckId;
    document.getElementById("licensePlate").value = licensePlate || '';
    document.getElementById("date").value = date;
    document.getElementById("status").value = status;
    document.getElementById("supplier").value = supplier;
    document.getElementById("cost").value = cost;
    document.getElementById("maintenanceType").value = maintenanceType || 'preventive';
    
    // Parse the remarks and set checkboxes
    if (remarks) {
        try {
            // Try to parse as JSON first
            let remarksArray;
            try {
                remarksArray = JSON.parse(remarks);
            } catch (e) {
                // If not JSON, treat as comma-separated string
                remarksArray = remarks.split(',').map(item => item.trim());
            }
            
            // Uncheck all checkboxes first
            document.querySelectorAll('input[name="remarks[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check the appropriate checkboxes
            remarksArray.forEach(remark => {
                // Check if remark starts with "Other:"
                if (typeof remark === 'string' && remark.startsWith("Other:")) {
                    document.querySelector('input[name="remarks[]"][value="Other"]').checked = true;
                    document.getElementById('otherRemarkText').value = remark.replace("Other:", "").trim();
                } else {
                    // Try to find exact match first
                    const exactMatch = document.querySelector(`input[name="remarks[]"][value="${remark}"]`);
                    if (exactMatch) {
                        exactMatch.checked = true;
                    } else {
                        // If no exact match, check if it's one of our standard options
                        const standardRemarks = [
                            "Change Oil", "Change Tires", "Brake Inspection", 
                            "Engine Check", "Transmission Check", 
                            "Electrical System Check", "Suspension Check"
                        ];
                        
                        if (standardRemarks.includes(remark)) {
                            document.querySelector(`input[name="remarks[]"][value="${remark}"]`).checked = true;
                        } else {
                            // If not a standard option, put in Other
                            document.querySelector('input[name="remarks[]"][value="Other"]').checked = true;
                            document.getElementById('otherRemarkText').value = remark;
                        }
                    }
                }
            });
        } catch (e) {
            console.error("Error parsing remarks:", e);
            // Fallback: if parsing fails, treat as single value
            const checkbox = document.querySelector(`input[name="remarks[]"][value="${remarks}"]`);
            if (checkbox) {
                checkbox.checked = true;
            } else if (remarks) {
                document.querySelector('input[name="remarks[]"][value="Other"]').checked = true;
                document.getElementById('otherRemarkText').value = remarks;
            }
        }
    } else {
        // Reset if no remarks
        document.querySelectorAll('input[name="remarks[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('otherRemarkText').value = '';
    }
    
    document.getElementById("status").disabled = false;
    document.querySelector('.edit-reasons-section').style.display = 'block';
    document.querySelectorAll('input[name="editReason"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('otherReasonText').value = '';
    
    document.getElementById("maintenanceModal").style.display = "block";
}


    function showEditRemarks(reasonsJson) {
        try {
            const reasons = JSON.parse(reasonsJson);
            let html = '<div class="remarks-modal-content"><h3>Record Details</h3>';
            
            if (reasons.editReasons && reasons.editReasons.length > 0) {
                html += '<h4>Edit Reasons:</h4><ul>';
                reasons.editReasons.forEach(reason => {
                    html += `<li>${reason}</li>`;
                });
                html += '</ul>';
            }
            
            if (reasons.deleteReason) {
                html += '<h4>Delete Reason:</h4>';
                html += `<p>${reasons.deleteReason}</p>`;
            }
            
            html += '<button onclick="document.getElementById(\'remarksModal\').style.display=\'none\'">Close</button></div>';
            
            document.getElementById('remarksModalContent').innerHTML = html;
            document.getElementById('remarksModal').style.display = 'block';
        } catch (e) {
            console.error('Error parsing remarks:', e);
            document.getElementById('remarksModalContent').innerHTML = 
                '<div class="remarks-modal-content"><p>Error displaying remarks</p></div>';
            document.getElementById('remarksModal').style.display = 'block';
        }
    }     
        function closeModal() {
        document.getElementById("maintenanceModal").style.display = "none";
        // Always enable the status dropdown when closing the modal
        document.getElementById("status").disabled = false;
        // Hide edit reasons section when closing
        document.querySelector('.edit-reasons-section').style.display = 'none';
    }
            
        function saveMaintenanceRecord() {
        if (!validateMaintenanceForm()) {
            return;
        }

        let editReasons = [];
        if (isEditing) {
            const checkboxes = document.querySelectorAll('input[name="editReason"]:checked');
            checkboxes.forEach(checkbox => {
                if (checkbox.value === "Other") {
                    const otherReason = document.getElementById('otherReasonText').value.trim();
                    if (otherReason) {
                        editReasons.push("Other: " + otherReason);
                    }
                } else {
                    editReasons.push(checkbox.value);
                }
            });
            
            if (editReasons.length === 0) {
                alert("Please select at least one reason for editing this record.");
                return;
            }
        }
        
        // Collect remarks
        let remarks = [];
        const remarkCheckboxes = document.querySelectorAll('input[name="remarks[]"]:checked');
        remarkCheckboxes.forEach(checkbox => {
            if (checkbox.value === "Other") {
                const otherRemark = document.getElementById('otherRemarkText').value.trim();
                if (otherRemark) {
                    remarks.push("Other: " + otherRemark);
                }
            } else {
                remarks.push(checkbox.value);
            }
        });
        
        if (remarks.length === 0) {
            alert("Please select at least one maintenance remark.");
            return;
        }
        
        const form = document.getElementById("maintenanceForm");
        const maintenanceId = document.getElementById("maintenanceId").value;
        const action = isEditing ? 'edit' : 'add';
        
        const formData = {
            maintenanceId: maintenanceId ? parseInt(maintenanceId) : null,
            truckId: parseInt(document.getElementById("truckId").value),
            licensePlate: document.getElementById("licensePlate").value,
            date: document.getElementById("date").value,
            remarks: JSON.stringify(remarks), // Convert array to JSON string
            status: document.getElementById("status").value,
            supplier: document.getElementById("supplier").value,
            cost: parseFloat(document.getElementById("cost").value || 0),
            maintenanceType: document.getElementById("maintenanceType").value,
            editReasons: editReasons
        };
        
        $.ajax({
            url: 'include/handlers/maintenance_handler.php?action=' + action,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    closeModal();
                    loadMaintenanceData();
                    updateStatsCards();
                    alert(isEditing ? "Maintenance record updated successfully!" : "Maintenance record added successfully!");
                } else {
                    alert("Error: " + (response.message || "Unknown error"));
                }
            },
            error: function(xhr, status, error) {
                console.error("Error saving record: " + error);
                alert("Failed to save maintenance record. Please check console for details.");
            }
        });
    }

    document.getElementById('otherRemarkText').addEventListener('input', function() {
        const otherCheckbox = document.querySelector('input[name="remarks[]"][value="Other"]');
        if (this.value.trim() !== '') {
            otherCheckbox.checked = true;
        }
    });
    

    document.querySelector('input[name="remarks[]"][value="Other"]').addEventListener('change', function() {
        if (!this.checked) {
            document.getElementById('otherRemarkText').value = '';
        }
    });

    function searchMaintenance() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        
        if (searchTerm.trim() === '') {
            // If search is empty, reload normal paginated data
            currentPage = 1;
            loadMaintenanceData();
            return;
        }
        
        // Fetch all records for searching
        fetchAllRecordsForSearch()
            .then(data => {
                const filteredRecords = data.records.filter(record => {
                    return (
                        String(record.truck_id).toLowerCase().includes(searchTerm) ||
                        (record.licence_plate && record.licence_plate.toLowerCase().includes(searchTerm)) ||
                        (record.date_mtnce && formatDate(record.date_mtnce).toLowerCase().includes(searchTerm)) ||
                        (record.remarks && record.remarks.toLowerCase().includes(searchTerm)) ||
                        (record.status && record.status.toLowerCase().includes(searchTerm)) ||
                        (record.supplier && record.supplier.toLowerCase().includes(searchTerm)) ||
                        (record.cost && String(record.cost).toLowerCase().includes(searchTerm))
                    );
                });
                
                renderTable(filteredRecords);
                // Hide pagination during search
                document.querySelector('.pagination').style.display = 'none';
            })
            .catch(error => {
                console.error("Error searching records:", error);
                alert("Failed to search maintenance records.");
            });
    }
            
    function deleteMaintenance(id) {
        if (!confirm("Are you sure you want to delete this maintenance record?")) {
            return;
        }
        
        const deleteReason = prompt("Please enter the reason for deleting this record:");
        if (deleteReason === null) return; // User cancelled
        if (deleteReason.trim() === "") {
            alert("You must provide a reason for deletion.");
            return;
        }
        
        $.ajax({
            url: `include/handlers/maintenance_handler.php?action=delete&id=${id}&reason=${encodeURIComponent(deleteReason)}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    loadMaintenanceData();
                    updateStatsCards();
                    alert("Maintenance record deleted successfully!");
                } else {
                    alert("Error: " + (response.message || "Unknown error"));
                }
            },
            error: function(xhr, status, error) {
                console.error("Error deleting record: " + error);
                alert("Failed to delete maintenance record.");
            }
        });
    }

    function updateStatsCards() {
    fetch('include/handlers/maintenance_handler.php?action=getCounts')
        .then(response => response.json())
        .then(data => {
            document.querySelector('.stat-card.total .value').textContent = data.total || 0;
            document.querySelector('.stat-card.pending .value').textContent = data.pending || 0;
            document.querySelector('.stat-card.in-progress .value').textContent = data.in_progress || 0;
            document.querySelector('.stat-card.completed .value').textContent = data.completed_this_month || 0;
            document.querySelector('.stat-card.overdue .value').textContent = data.overdue || 0;
        })
        .catch(error => {
            console.error("Error loading stats:", error);
        });
}

            function openHistoryModal(truckId) {
                currentTruckId = truckId;
                
                $.ajax({
                    url: 'include/handlers/maintenance_handler.php?action=getHistory&truckId=' + truckId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        const historyList = document.getElementById("historyList");
                        historyList.innerHTML = ""; 
                        
                        if (response.history.length === 0) {
                            historyList.innerHTML = "<p>No maintenance history found for this truck.</p>";
                        } else {
                            response.history.forEach(item => {
                                const historyItem = document.createElement("div");
                                historyItem.className = "history-item";
                            historyItem.innerHTML = `
                                    <strong>Date of Inspection:</strong> ${formatDate(item.date_mtnce)}<br>
                                    <strong>Remarks:</strong> ${item.remarks}<br>
                                    <strong>Status:</strong> ${item.status}<br>
                                    <strong>Supplier:</strong> ${item.supplier || 'N/A'}<br>
                                    <strong>Cost:</strong> ₱ ${parseFloat(item.cost).toFixed(2)}<br>
                                    <strong>Last Modified By:</strong> ${item.last_modified_by} on ${formatDateTime(item.last_modified_at)}<br>
                                    <hr>
                                `;
                                historyList.appendChild(historyItem);
                            });
                        }
                        
                        document.getElementById("historyModal").style.display = "block";
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading history: " + error);
                        alert("Failed to load maintenance history.");
                    }
                });
            }
            
            function closeHistoryModal() {
                document.getElementById("historyModal").style.display = "none";
            }
            
            function openRemindersModal() {
    const modal = document.getElementById("remindersModal");
    const list = document.getElementById("remindersList");
    
    // Show loading state
    list.innerHTML = "<p>Loading reminders...</p>";
    modal.style.display = "block";
    
    fetch('include/handlers/maintenance_handler.php?action=getReminders')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.reminders.length === 0) {
                list.innerHTML = "<p>No upcoming maintenance reminders.</p>";
                return;
            }
            
            // Use document fragment for better performance
            const fragment = document.createDocumentFragment();
            
            data.reminders.forEach(item => {
                const daysRemaining = parseInt(item.days_remaining);
                let statusClass, daysText;
                
                if (daysRemaining < 0) {
                    statusClass = 'overdue';
                    daysText = `<span class="overdue">OVERDUE by ${Math.abs(daysRemaining)} days</span>`;
                } else if (daysRemaining === 0) {
                    statusClass = 'due-today';
                    daysText = `<span class="due-today">DUE TODAY</span>`;
                } else {
                    statusClass = 'upcoming';
                    daysText = `<span class="upcoming">Due in ${daysRemaining} days</span>`;
                }
                
                const reminderItem = document.createElement("div");
                reminderItem.className = `reminder-item ${statusClass}`;
                reminderItem.innerHTML = `
                    <strong>Truck:</strong> ${item.truck_id} (${item.licence_plate || 'N/A'})<br>
                    <strong>Maintenance:</strong> ${item.remarks}<br>
                    <strong>Due Date:</strong> ${formatDate(item.date_mtnce)} - ${daysText}<br>
                    <strong>Status:</strong> ${item.status}<br>
                    <hr>
                `;
                fragment.appendChild(reminderItem);
            });
            
            list.innerHTML = ""; // Clear loading message
            list.appendChild(fragment);
        })
        .catch(error => {
            console.error("Error loading reminders:", error);
            list.innerHTML = "<p>Failed to load reminders. Please try again.</p>";
        });
}
            
            function closeRemindersModal() {
                document.getElementById("remindersModal").style.display = "none";
            }

            let sortDateAsc = true; 

            function sortByDate() {
                const tableBody = document.querySelector("#maintenanceTable tbody");
                const rows = Array.from(tableBody.querySelectorAll("tr"));

                const sortedRows = rows.sort((a, b) => {
                    const dateA = new Date(a.children[2].textContent.trim());
                    const dateB = new Date(b.children[2].textContent.trim());

                    return sortDateAsc ? dateA - dateB : dateB - dateA;
                });

                sortDateAsc = !sortDateAsc;

                const icon = document.getElementById("dateSortIcon");
                icon.textContent = sortDateAsc ? '⬆' : '⬇';

                tableBody.innerHTML = '';
                sortedRows.forEach(row => tableBody.appendChild(row));
            }

            function sortByTruckId() {
        const tableBody = document.querySelector("#maintenanceTable tbody");
        const rows = Array.from(tableBody.querySelectorAll("tr"));

        const sortedRows = rows.sort((a, b) => {
            const truckIdA = parseInt(a.children[0].textContent.trim());
            const truckIdB = parseInt(b.children[0].textContent.trim());

            return sortTruckIdAsc ? truckIdA - truckIdB : truckIdB - truckIdA;
        });

        sortTruckIdAsc = !sortTruckIdAsc;

        const icon = document.getElementById("truckIdSortIcon");
        icon.textContent = sortTruckIdAsc ? '⬆' : '⬇';

        tableBody.innerHTML = '';
        sortedRows.forEach(row => tableBody.appendChild(row));
    }

            function restoreMaintenance(id) {
        if (!confirm("Are you sure you want to restore this maintenance record?")) {
            return;
        }
        
        $.ajax({
            url: `include/handlers/maintenance_handler.php?action=restore&id=${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    loadMaintenanceData();
                    updateStatsCards();
                    alert("Maintenance record restored successfully!");
                } else {
                    alert("Error: " + (response.message || "Unknown error"));
                }
            },
            error: function(xhr, status, error) {
                console.error("Error restoring record: " + error);
                alert("Failed to restore maintenance record.");
            }
        });
    }

    function toggleDeletedRecords() {
        loadMaintenanceData();
    }
        </script>
        <script>
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
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="include/js/logout-confirm.js"></script>

<footer class="site-footer">

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
    </div>
</footer>
    </body>
    </html>