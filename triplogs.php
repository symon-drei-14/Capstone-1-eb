    <?php
    require_once __DIR__ . '/include/check_access.php';
    checkAccess(); // No role needed—logic is handled internally


    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Trip logs</title>
        <link rel="stylesheet" href="include/css/sidenav.css">
        <link rel="stylesheet" href="include/triplogs.css">

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.0/css/all.css">

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



    #editReasonsModal {
    align-items: left;
        justify-content: center;
        z-index: 99999; 

    }

    #editModal .modal-content {
        max-height: 80vh; /* Limit height to 80% of viewport */
        overflow-y: auto; /* Enable vertical scrolling */
        padding: 20px;
        width: 90%;
        max-width: 600px;
        position: relative;
    }


    .edit-reasons-section {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        margin-top: 15px;
    }



    #editForm .edit-reasons-section {
        margin-top: 15px;
        padding: 15px;
        background-color: #ffffffff;
        border-radius: 5px;
        border: 1px solid #ddd;
        width:23em;

    }

    #editForm .reasons-container {
        display: flex;
        flex-direction: column;

    }

    #editForm .reason-option {
        display: flex;
        align-items: center;
        background-color: #fff;
        padding: 17px 12px;
        border-radius: 4px;
        border: 1px solid #ddd;

    }

    .reason-option {
        transition: background-color 0.2s;
    }

    .reason-option:hover {
        background-color: #f1f1f1 !important;
    }

    .reason-option input[type="checkbox"] {
        cursor: pointer;
    }

    .other-reason textarea {
        min-height: 80px;
        font-family: inherit;
        font-size: 14px;
    }

    #editForm .reason-option input[type="checkbox"] {
        margin-right: -12em;

        flex-shrink: 0;
    position:relative;   
    }

    .reason-option label {
        display: block;
        cursor: pointer;
        margin: 0;
        font-size: 14px;
        flex-grow: 1;
        word-break: break-word;
        text-align:left;
        margin-top:10px;
        margin-bottom:-10px;
        margin-left:-25px;
    }


    #editForm .other-reason {
        margin-top: 10px;
        padding: 10px;
        background-color: #fff;
        border-radius: 4px;
        border: 1px solid #ccc;
    }

    #editForm .other-reason label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    #editForm .other-reason textarea {
        width: 90%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
        min-height: 60px;
    }
        .event-item{
            padding:20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        /* .event-details-container {
            width: 500px;
            height: auto; 
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
        
            display: relative;
            margin: 50px;
            margin-top: 100px;
            line-height: 30px;
            max-height: 600px;
            overflow-y: auto;
        }
        
                .event-details-container h4 {
                    margin-top: 0;
                }
        
                .event-details-container p {
                    margin: 5px 0;
                }
        
                .event-list {
                    list-style-type: none;
                    padding: 0;
                }
        
                .event-list li {
                    margin: 10px 0;
                } */
        
            
        
                #calendar {
                    width: 700px;
                    background-color: #ffffff;
                    padding: 30px;
                    border-radius: 20px;
                    box-shadow: rgba(0, 0, 0, 0.15) 0px 15px 25px, rgba(0, 0, 0, 0.05) 0px 5px 10px;
                height:35rem;
                overflow: hidden;
                }
        
                #noEventsMessage {
                    display: block;
                }

                /* .events-table {
        width: 100%;
        table-layout: auto;
        border-collapse: collapse;
        margin-top: 10px;
        margin-bottom: 20px;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        word-wrap: break-word;
    }

    .events-table th, .events-table td {
        padding: 8px 10px;
        text-align: center;
        border-radius: 1px;
        font-size: 14px;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    } */
        
        
                .events-table {
            table-layout: fixed;        
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        
        
        }
        
        .events-table th {
            padding: 12px;
            text-align: center;
            border-radius: 1px;
            word-wrap: break-word;
        
            
        }
        .events-table td{
        
            padding: 10px;
            text-align: center;
            border-radius: 1px;
            word-wrap: break-word;
            font-size: 15px;
        }
        
        .events-table th {
            background-color: #ffffff;
            font-weight: bold;
            position: relative;
            box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px;
            border-bottom: 5px double #d3d1d15c;
            z-index: 1;
        
        
        }

        .events-table th:nth-child(1), 
        .events-table td:nth-child(1) { /* Plate No */
            width: 1rem;
        }

        .events-table th:nth-child(2), 
        .events-table td:nth-child(2) { /* Date */
            width: 1.3rem;
        }

        .events-table th:nth-child(3), 
        .events-table td:nth-child(3) { /* Time */
            width: 1rem;
        }

        .events-table th:nth-child(4), 
        .events-table td:nth-child(4) { /* Driver */
            width: 1.2rem;
        }

        .events-table th:nth-child(5), 
        .events-table td:nth-child(5) { /* Helper */
            width: 1.2rem;
        }

        .events-table th:nth-child(6), 
        .events-table td:nth-child(6) { /* Dispatcher */
            width: 1.7rem;
        }

        .events-table th:nth-child(7), 
        .events-table td:nth-child(7) { /* Container No */
            width: 1.5rem;
        }

        .events-table th:nth-child(8), 
        .events-table td:nth-child(8) { /* Client */
            width: 1rem;
        }

        .events-table th:nth-child(9), 
        .events-table td:nth-child(9) { /* Destination */
            width: 1.2rem;
        }

        .events-table th:nth-child(10), 
        .events-table td:nth-child(10) { /* Shipping Line */
            width: 1.2rem;
        }

        .events-table th:nth-child(11), 
        .events-table td:nth-child(11) { /* Consignee */
            width: 1.7rem;
        }

        .events-table th:nth-child(12), 
        .events-table td:nth-child(12) { /* Size */
            width: 0.5rem;
        }

        .events-table th:nth-child(13), 
        .events-table td:nth-child(13) { /* Cash Advance */
            width: 0.8rem;
        }

        .events-table th:nth-child(14), 
        .events-table td:nth-child(14) { /* Status */
            width: 1rem;

        }

        .events-table th:nth-child(15), 
        .events-table td:nth-child(15) { /* Last Modified */
            width: 1.5rem;

        }

        .events-table th:nth-child(16), 
        .events-table td:nth-child(16) { /* Action */
            width: 1.5rem;
            white-space: nowrap; /* Keep buttons on one line */
        }

        /* Ensure text wraps and doesn't overflow */
        .events-table td {
            word-wrap: break-word;
            white-space: normal;
        }

        .events-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .events-table tr:nth-child(odd) {
            background-color: #ffffff;
        }
        
        .events-table td {
            color: #333;
        }
        
        .events-table tr:hover {
            background-color: #f1f1f1;
            cursor: pointer;
        }
        
        /* .events-table td {
            font-size: 13px;
            color: #555;
        } */
        
        .events-table td a {
            color: #000000ff;
            text-decoration: none;
        }
        
        .events-table td a:hover {
            text-decoration: underline;
        }
        
        .events-table .description-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        @media screen and (max-width: 768px) {
            .events-table {
                width: 100%;
                font-size: 12px;
            }
        
            .events-table th, .events-table td {
                padding: 8px 10px;
            }
        }
        
                /* Button styling */
                .toggle-btns {
                    display: flex;
                    gap: 0px;
                    margin-bottom: 20px;
                
                }
        
                .toggle-btn {
                    padding: 10px 20px;
                    border: none;
                    background-color: #e4e4e4;
                    cursor: pointer;
                    border-radius: 5px;
                    font-size: 14px;
        
                }
        
                .toggle-btn.active {
                    background-color: #1b1963;
                    color: #fff;
                }
                .status {
            display: inline-block;
            padding: 5px 5px;
            border-radius: 5px;
            
        }
        .status.completed {
        background-color: #28a745; /* Green */
        color: white;

    }

    .status.pending {
        background-color: #ffc107; /* Yellow */
        color: black;

    }

    .status.cancelled {
        background-color: #dc3545; /* Red */
        color: white;
    
    }

    .status.enroute, 
    .status.en-route {
        background-color: #007bff; /* Blue */
        color: white;
        width:70px;
    
    }
        
     
        
        .page-numbers {
            display: inline-flex;
            gap: 5px;
            align-items: center;
        }
        
        .page-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid #ccc;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-weight: bold;
            background-color: rgb(255, 255, 255);
            transition: background-color 0.3s, color 0.3s;
        }
        
        .page-number:hover {
            background-color: rgba(183, 181, 181, 0.95);
            color: #fff;
        }
        
        .page-number.active {
            background-color: rgba(255, 255, 255, 0.82);
            color: black;
            border-color: rgb(26, 97, 12);
            border-width: 2px;
        }

        /* pagination */
        
    .fc-event {
        max-width: 120px !important; /* Narrower width */
        border: none !important;
        border-radius: 4px !important;
        padding: 2px 4px !important;
        margin: 1px 0 !important;
        font-size: 0.85em !important;
        line-height: 1.2;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        display: inline-block;
    }

    .fc-event .fc-content {
        display: flex;
        flex-direction: column;
    }

    .fc-event .fc-time {
        font-weight: bold;
        margin-right: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .fc-event .fc-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }

        .fc-day-selected {
            background-color: #d5d5d8 !important;
            color: white !important;
        }
        
        .fc-day:hover {
            background-color: #d5d5d8 ;
            color: white ;
        }
        
        .edit-btn{
            background-color: #28a745   ;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            border: none;
            margin-bottom: 2px;
            width: 80px;
        }

        .edit-btn2{
            background-color: #28a7a7   ;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            border: none;
            margin-bottom: 2px;
            width: 80px;
            font-size: 10px;
        }
        
        .delete-btn{
            background-color: #cc4141   ;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            border: none;
        }
        
        /* ---------------------- */
    .modal {
        display: none;
        position: fixed;
        z-index: 11000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }


    .modal-content {
        left: 450;
        top: 15;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
        position: relative;
    }

        .modal-content h2 {
        margin-bottom: 20px;
        color: #333;
    }

    .close {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 24px;
        font-weight: bold;
        color: #aaa;
        cursor: pointer;
    }

    .close:hover {
        color: #333;
    }
        
        .close:focus {
            color: rgb(255, 255, 255);
            text-decoration: none;
            cursor: pointer;
            background-color: #730707;
        
        }
        
        .modal button {
            background-color:#f44336;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .add-schedule-btn {
            padding: 10px 20px;


            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            
        }
        
        .add-schedule-btn:hover {
            background-color: #45a049;
        }

        #addScheduleForm input,
    #addScheduleForm select {
        width: 100%;
        padding: 8px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    #addScheduleForm input:focus,
    #addScheduleForm select:focus {
        outline: none;
        border-color: #4843a4ff;
        box-shadow: 0 0 0 2px rgba(52, 109, 174, 0.26);
    }

    .cancel-btn {
        background-color: #f443360c;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
        width:100px;
        height:40px;
        margin-top:0.7em;
    }

    .cancel-btn:hover {
        background-color: #d32f2f;
    }

    .save-btn{
        height:40px;
        width:100px;
        cursor: pointer;
        transition: background-color 0.3s;
    align-items:center;
    justify-items:center;
    display:flex;
    }
    .save-btn:hover {
        background-color: #ffffffff;


    }
        /* ---------------------- */
        
        label {
            font-size: 16px;
            color: #333;
            font-weight: bold;
            margin-bottom: 5px; /* Add space between label and input */
            text-align: left;
            width: 100%; /* Ensure label takes up full width */
        }

        
        input, select {
            padding: 10px;
            font-size: 14px;
            border-radius: 5px;
            border: 1px solid #ccc;
            outline: none;
            transition: border-color 0.3s;
            width: 90%; /* Make inputs fill the container */
            max-width: 400px; /* Limit input width */
            margin-bottom: 10px; /* Add spacing between inputs */
        }
        
        input:focus, select:focus {
            border-color: #4CAF50;
        }
        
        input[type="datetime-local"] {
            padding: 8px;
        }

        #addScheduleForm label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }


        #editForm button[type="submit"],
        #addScheduleForm button[type="submit"] {
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        button.close {
            margin-top: 10px;
            background-color: #f44336;
            color: white;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
        }
        
        .cancel-btn:hover {
            background-color:rgb(104, 20, 14);
        
        }
        
        
    .event-details-container {
            width: 500px;
            height: auto; 
            padding: 30px;
            background-color: #ffffff;
            border-radius: 8px;
            display: relative;
            margin: 50px;
            margin-top: 100px;
            line-height: 30px;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .event-details-container h4 {
            margin-top: 0;
        }
        
        .event-details-container p {
            margin: 5px 0;
        }
        
        .event-list {
            list-style-type: none;
            padding: 0;
        }
        
        .event-list li {
            margin: 10px 0;
        }

        .event-thumbnail {
            cursor: pointer;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius:  5px 5px 0px 0px;
            margin-bottom: 10px;
        }

        .event-details {
            display: none;
            padding: 10px;
            border-top: 1px solid white;
            border: 1px solid #ccc;
            border-radius: 0px 0px 5px 5px;
            margin-top: -11px;
            margin-bottom:5px;
        }
            .toggle-sidebar-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        margin-left: 1rem;
        color: white;
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



    .sidebar {
        position: fixed;
        top: 1.7rem;
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
    .main-container{
            background-color: rgb(255, 255, 255);
            margin-left:10px;
            margin-top: 40px;
            padding:10px;
            border-radius:20px;
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            width: calc(100% - 40px); 
        min-height: calc(100vh - 100px); 
            height: auto;

        }
            .calendar-container {
                    display: flex;
                    justify-content: space-between;
                    gap: 10px;
                    padding: 20px;
                    height:auto;
                        overflow-y: auto; /* Enable scrolling if content overflows */
        
                }
    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        background-color:#FCFAEE;
        overflow-y: auto; /* or just remove this; auto is default */
        overflow-x:hidden;
        height: auto; /* allow content to grow */
    }

    .edit-btn2.view-reasons-btn {
        background-color: #5287bfff; 
        color: white;
        border: none;
        padding: 5px 5px;
        font-size: 12px;
        border-radius: 6px;
        cursor: pointer;
    margin-top:5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .edit-btn2.view-reasons-btn:hover {
        background-color: #0056b3;
    }

    .edit-btn2.view-reasons-btn:active {
        transform: scale(0.98);
        background-color: #004a99;
    }

    .event-details .view-reasons-btn {
        background-color: #5287bfff;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        margin-bottom: 10px;
    }

    .event-details .view-reasons-btn:hover {
        background-color: #3a6ea5;
    }

    .event-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .filter-row {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #ffffffff;
        border-bottom:1px solid #88888831;
        flex-wrap: nowrap;
        width: 98%;
        
    }

    .status-filter-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-right: 10px;
        flex: 0 0 auto; 
    }


    .status-filter-container label {
        font-weight: bold;
        white-space: nowrap;
    }

    .status-filter-container select {
    padding: 8px 12px 8px 45px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background-color: white;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 12px center;
    background-size: 16px;

}


    .status-filter-container select:focus {
        outline: none;
        border-color: #4b77de;
    }
#statusFilter{
width:140px;
}
    #deletedTripsTable tr {
        opacity: 0.7;
        background-color: #ffecec !important;
    }

    #deletedTripsTable tr:hover {
        opacity: 1;
        background-color: #ffdddd !important;
    }

    .restore-btn {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }

    .restore-btn:hover {
        background-color: #45a049;
    }


    #eventsTable, #eventTableBody, .pagination-container {
        display: none;
    }

    .deleted-row {
        opacity: 0.9;
        background-color: #ffffffff !important;
    }

    .deleted-row:hover {
        opacity: 1;
        background-color: #ffdddd !important;
    }

    .restore-btn {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }

    .restore-btn:hover {
        background-color: #45a049;
    }

    .filter-row {
        display: none; 
    }
    #deleteConfirmModal .modal-content2 {
        background-color: #fff;
        margin: 10% auto;
        padding: 30px;
        border-radius: 15px;
        width: 80%;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        text-align: center;
    }

    #deleteConfirmModal h3 {
        color: #dc3545;
        margin-top: 0;
        font-size: 24px;
    }

    #deleteConfirmModal p {
        margin: 20px 0;
        font-size: 16px;
        color: #333;
    }

    #deleteConfirmModal label {
        display: block;
        margin-bottom: 10px;
        font-weight: bold;
        text-align: left;
    }

    #deleteConfirmModal textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        resize: vertical;
        min-height: 100px;
        margin-bottom: 20px;
    }

    #deleteConfirmModal button {
        padding: 10px 25px;
        margin: 0 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s;
    }

    #confirmDeleteBtn {
        background-color: #dc3545;
        color: white;
    }

    #confirmDeleteBtn:hover {
        background-color: #c82333;
    }

    #deleteConfirmModal .cancel-btn {
        background-color: #6c757d;
        color: white;
    }

    #deleteConfirmModal .cancel-btn:hover {
        background-color: #5a6268;
    }

    #deleteConfirmModal .button-group {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    .search-container {
        position: relative;
        display: flex;
        align-items: center;
        flex: 1;
        max-width: 300px;
    }


    .search-container .fa-search {
        position: absolute;
        left: 10px;
        top: 35%; /* Reduced from 50% to move it up */
        transform: translateY(-50%);
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


    .actions {
        display: flex;
        flex-wrap: nowrap; 
        gap: 5px; 
        justify-content: center;
        align-items: center;
    margin:20px
    }


    .icon-btn {
        background: none;
        border: none;
        padding: 5px;
        margin: 0;
        cursor: pointer;
        font-size: 16px;
        color: #555;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px; 
        height: 24px; 
        border-radius: 4px;
    
    }

    .icon-btn:hover {
        background-color: #f0f0f0;
        transform: scale(1.1);
    }

    /* Specific icon colors */
    .icon-btn.edit {
        color: rgba(8, 89, 18, 1);
    }

    .icon-btn.delete {
        color: #dc3545;
    }

    .icon-btn.restore {
        color: #28a745;
    }

    .icon-btn.view-reasons {
        color: #17a2b8;
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

    /* --------------------------------- */

    .stats-container {
        display: flex;
        gap: 15px;
        position: absolute;
        right: 30px;
        top: 150px;
        z-index: 1000;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 15px 15px;
        box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 120px;
    }

    .stat-icon {
        font-size: 20px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-content {
        display: flex;
        flex-direction: column;
    }

    .stat-value {
        font-weight: bold;
        font-size: 18px;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
    
    }

    .icon-total {
        background-color: #6c757d;
        color: white;
    }   

    .icon-pending {
        background-color: #ffc107; 
        color: white;
    }

    .icon-enroute {
        background-color: #007bff; 
        color: white;
    }

    .icon-completed {
        background-color: #28a745; 
        color: white;
    }

    .icon-cancelled {
        background-color: #dc3545; 
        color: white;
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
    .restore::after {
        background-color: #0dbd33ff; 
    }
    .full-delete::after {
        background-color: #dc3545; 
    }


    .view-reasons::after {
        background-color: #0d7cbdff; 
    left:10%;
    }

    .show-deleted-container {
        display: flex;
        align-items: center;
        gap: 5px;   
        white-space: nowrap;
        flex: 0 0 auto;

    }
    .full-delete {
    
        color: #dc3545;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
    }

    .full-delete:hover {
        transform: scale(1.1);
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

    .swal2-container {
        z-index: 99999 !important;
    }

    .rows-per-page-container {
        display: none; 
        align-items: center;
        gap: 8px;
        margin-right: 20px;
        margin-bottom: 15px;
        justify-content: flex-end;
    
    }
    .table-view .rows-per-page-container {
        display: flex;
    }
   #rowsPerPage{
    width:5em;
    
   }

    .rows-per-page select {
        padding: 5px 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: white;
         width: 10px !important;
    }

    .rows-per-page label {
        font-size: 14px;
        color: #333;
    }

    

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 20px;
    gap: 5px;
}

.pagination button {
    background-color: transparent;
    color: #000;
    border: none;
    padding: 2px 5px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 15%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 2px;
    transition: all 0.3s ease;
}

.pagination button:hover {
    background-color: #ffffffff;
    
    color: black;
    font-weight:bold;
    transform: scale(1.4); 
}

.pagination button.active {
    background-color: #ffffff6a;
    color: #cb1a2fff;
    border-color: #ffffffff;    
    font-weight: bolder;
    font-size:20px;
}   

.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination .nav-btn {
    border-radius: 15%;
    width: auto;
    font-size:14px;
    border:none;
}
.pagination .nav-btn:hover {
     transform: scale(1.6); 
      background-color: #ffffffff;
      font-weight:bold;
      border:none;  
}


.pagination .ellipsis {
    display: flex;
    align-items: center;
    padding: 0 5px;
}


.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
 
}

.table-info {
    font-size: 14px;
    color: #555;
}


.rows-per-page-container {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-right:30px;
}

.rows-per-page-container label {
    font-size: 14px;
    color: #333;
    margin-right: 5px;
    border-radius:20px;
}
.rows-per-page-container select {
    font-size: 14px;
    color: black;
    border-color: #33333328;
    margin-right: 5px;
    border-radius:10px;
    padding: 5px;
    margin: 5px;
    max-width:200px;
    width:auto;
}
    </style>
    <body>
        <?php
        require 'include/handlers/dbhandler.php';
        require 'include/handlers/triplogstats.php';


    $tripStats = getTripStatistics($conn);
        session_start();

    
    $sql = "SELECT a.*, t.plate_no as truck_plate_no, t.capacity as truck_capacity, a.edit_reasons,d.driver_id
            FROM assign a
            LEFT JOIN drivers_table d ON a.driver = d.name
            LEFT JOIN truck_table t ON d.assigned_truck_id = t.truck_id
            WHERE a.is_deleted = 0"; // Add this WHERE clause
    $result = $conn->query($sql);
    $eventsData = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $eventsData[] = [
                'id' => $row['trip_id'],
                'plateNo' => $row['plate_no'],
                'date' => $row['date'],
                'driver' => $row['driver'],
                'driver_id' => $row['driver_id'], // eto yung driver_id
                'helper' => $row['helper'],
                'dispatcher' => $row['dispatcher'],
                'containerNo' => $row['container_no'],
                'client' => $row['client'],
                'destination' => $row['destination'],
                'shippingLine' => $row['shippine_line'],
                'consignee' => $row['consignee'],
                'size' => $row['size'],
                'cashAdvance' => $row['cash_adv'],
                'status' => $row['status'],
                'modifiedby' => $row['last_modified_by'],
                'modifiedat' => $row['last_modified_at'],
                'truck_plate_no' => $row['truck_plate_no'],
                'truck_capacity' => $row['truck_capacity'],
                'edit_reasons' => $row['edit_reasons'] 
            ];
        }
    }

    // Fetch drivers with their assigned truck capacity
    $driverQuery = "SELECT d.driver_id, d.name, t.plate_no as truck_plate_no, t.capacity, d.assigned_truck_id 
                    FROM drivers_table d
                    LEFT JOIN truck_table t ON d.assigned_truck_id = t.truck_id";
    $driverResult = $conn->query($driverQuery);
    $driversData = [];

    if ($driverResult->num_rows > 0) {
        while($driverRow = $driverResult->fetch_assoc()) {
            $driversData[] = [
                'id' => $driverRow['driver_id'],
                'name' => $driverRow['name'],
                'capacity' => $driverRow['capacity'],
                'truck_plate_no' => $driverRow['truck_plate_no'],
                'assigned_truck_id' => $driverRow['assigned_truck_id']
            ];
        }
    }

        
        $eventsDataJson = json_encode($eventsData);
        $driversDataJson = json_encode($driversData);
        ?>

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
    <div class="main-container">
    
        <div class="calendar-container">
            <section class="calendar-section">
                <h2>Trip Management</h2>

                
                <div class="toggle-btns">
                    <button id="calendarViewBtn" class="toggle-btn active"> <i class="fa fa-calendar"> Calendar</i></button>
                    <button id="tableViewBtn" class="toggle-btn">  <i class="fa fa-tasks"> Table</i></button>
                
                </div>
                <button id="addScheduleBtnTable" class="toggle-btn">Add Schedule</button>
        
                <div id="calendar"></div>
            </section>
            
            <section class="event-details-container" id="eventDetails">
                <h4>Event Details</h4>
                <p id="noEventsMessage" style="display: none;">No scheduled trips for this date</p>
                <ul id="eventList" class="event-list"></ul>
            </section>
        </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: scroll;">
            <span class="close">&times;</span>
            <h3 style="margin-top: 0;">Edit Trip</h3>
            <form id="editForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; overflow: hidden;">
                <input type="hidden" id="editEventId" name="eventId">
                
                <!-- Column 1 -->
                <div style="display: flex; flex-direction: column;">
                    <label for="editEventSize">Shipment Size:</label>
                    <select id="editEventSize" name="eventSize" required style="width: 100%;">
                        <option value="">Select Size</option>
                        <option value="20ft">20ft</option>
                        <option value="40ft">40ft</option>
                        <option value="40ft HC">40ft HC</option>
                        <option value="45ft">45ft</option>
                    </select>

                    <label for="editEventPlateNo">Plate No.:</label>
                    <input type="text" id="editEventPlateNo" name="eventPlateNo" required style="width: 100%;">

                    <label for="editEventDate">Date & Time:</label>
                    <input type="datetime-local" id="editEventDate" name="editEventDate" required style="width: 100%;">

                    <label for="editEventDriver">Driver:</label>
                    <select id="editEventDriver" name="eventDriver" required style="width: 100%;">
                        <option value="">Select Driver</option>
                    </select>

                    <label for="editEventHelper">Helper:</label>
                    <input type="text" id="editEventHelper" name="eventHelper" required style="width: 100%;">
                </div>

                <!-- Column 2 -->
                <div style="display: flex; flex-direction: column;">
                    <label for="editEventDispatcher">Dispatcher:</label>
                    <input type="text" id="editEventDispatcher" name="eventDispatcher" required style="width: 100%;">

                    <label for="editEventContainerNo">Container No.:</label>
                    <input type="text" id="editEventContainerNo" name="eventContainerNo" required style="width: 100%;">

                    <label for="editEventClient">Client:</label>
                    <select id="editEventClient" name="eventClient" required style="width: 100%;">
                        <option value="">Select Client</option>
                        <option value="Maersk">Maersk</option>
                        <option value="MSC">MSC</option>
                        <option value="COSCO">COSCO</option>
                        <option value="CMA CGM">CMA CGM</option>
                        <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                        <option value="Evergreen">Evergreen</option>
                    </select>

                    <label for="editEventDestination">Destination:</label>
                    <select id="editEventDestination" name="eventDestination" required style="width: 100%;">
                        <option value="">Select Destination</option>
                        <option value="Manila Port">Manila Port</option>
                        <option value="Batangas Port">Batangas Port</option>
                        <option value="Subic Port">Subic Port</option>
                        <option value="Cebu Port">Cebu Port</option>
                        <option value="Davao Port">Davao Port</option>
                    </select>

                    <label for="editEventStatus">Status:</label>
                    <select id="editEventStatus" name="eventStatus" required style="width: 100%;">
                        <option value="Pending">Pending</option>
                        <option value="En Route">En Route</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Full width fields -->
                <div style="grid-column: span 2;">
                    <label for="editEventShippingLine">Shipping Line:</label>
                    <select id="editEventShippingLine" name="eventShippingLine" required style="width: 35%;">
                        <option value="">Select Shipping Line</option>
                        <option value="Maersk Line">Maersk Line</option>
                        <option value="Mediterranean Shipping Co.">Mediterranean Shipping Co.</option>
                        <option value="COSCO Shipping">COSCO Shipping</option>
                        <option value="CMA CGM">CMA CGM</option>
                        <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                    </select>
                
                    <label for="editEventConsignee">Consignee:</label>
                    <input type="text" id="editEventConsignee" name="eventConsignee" required style="width: 25%;">
                    <br>
                    <label for="editEventCashAdvance">Cash Advance:</label>
                    <input type="text" id="editEventCashAdvance" name="eventCashAdvance" required style="width: 20%;">
                </div>

                
        <div class="edit-reasons-section" style="grid-column: span 2; margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; border: 1px solid #ddd; width: 100%;">
        <h4 style="margin-top: 0; margin-bottom: 15px; color: #333;">Reason for Edit</h4>
        <p style="margin-top: 0; margin-bottom: 10px; color: #666;">Select all that apply:</p>
        
        <div class="reasons-container" style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
            <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                <label for="reason1" style="margin: 0; cursor: pointer; flex: 1;">Changed schedule as per client request</label>
                <input type="checkbox" name="editReason" value="Changed schedule as per client request" id="reason1" style="margin-left: 10px;">
            </div>
            
            <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                <label for="reason2" style="margin: 0; cursor: pointer; flex: 1;">Updated driver assignment due to availability</label>
                <input type="checkbox" name="editReason" value="Updated driver assignment due to availability" id="reason2" style="margin-left: 10px;">
            </div>
            
            <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                <label for="reason3" style="margin: 0; cursor: pointer; flex: 1;">Modified vehicle assignment for capacity requirements</label>
                <input type="checkbox" name="editReason" value="Modified vehicle assignment for capacity requirements" id="reason3" style="margin-left: 10px;">
            </div>
            
            <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                <label for="reason4" style="margin: 0; cursor: pointer; flex: 1;">Adjusted destination based on new instructions</label>
                <input type="checkbox" name="editReason" value="Adjusted destination based on new instructions" id="reason4" style="margin-left: 10px;">
            </div>
            
            <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                <label for="reason5" style="margin: 0; cursor: pointer; flex: 1;">Updated container details for accuracy</label>
                <input type="checkbox" name="editReason" value="Updated container details for accuracy" id="reason5" style="margin-left: 10px;">
            </div>
            
            <div class="reason-option" style="display: flex; justify-content: space-between; align-items: center; background-color: #fff; padding: 12px; border-radius: 4px; border: 1px solid #ddd; transition: background-color 0.2s; width: 90%;">
                <label for="reason6" style="margin: 0; cursor: pointer; flex: 1;">Other (please specify below)</label>
                <input type="checkbox" name="editReason" value="Other" id="reason6" style="margin-left: 10px;">
            </div>
            
            <div class="other-reason" style="width: 90%;" id="otherReasonContainer">
                <label for="otherReasonText" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Specify other reason:</label>
                <textarea id="otherReasonText" name="otherReasonText" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; min-height: 80px;"></textarea>
            </div>
        </div>
    </div>

                <!-- Form buttons -->
                <div style="grid-column: span 2; display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                    <button type="button" id="viewChecklistBtn" class="save-btn" style="background-color: #17a2b8; display: none;">
    View Driver Checklist
</button>
                    <button type="button" class="close-btn cancel-btn" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="button" id="viewExpensesBtn" class="save-btn" style="padding: 8px 15px; background-color: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; display: none;">Expense Reports</button>
                    <button type="submit" class="save-btn"style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="stats-container" id="statsContainer">
        <div class="stat-card">
            <div class="stat-icon icon-pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-enroute">
                <i class="fas fa-truck-moving"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['enroute']; ?></div>
                <div class="stat-label">En Route</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-completed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-cancelled">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['cancelled']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-total">
                <i class="fas fa-list-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $tripStats['total']; ?></div>
                <div class="stat-label">Total Trips</div>
            </div>
        </div>
    </div>



    <div id="expensesModal" class="modal">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <span class="close">&times;</span>
            <h3 style="margin-top: 0;">Expense Reports</h3>
            <div id="expensesContent">
                <table class="events-table" style="width: 100%; margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Expense Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTableBody"></tbody>
                </table>
            </div>
            <button type="button" class="close-btn cancel-btn" style="margin-top: 20px;">Close</button>
        </div>
    </div>

        <div id="addScheduleModal" class="modal">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow: hidden;">
            <span class="close">&times;</span>
            <h2 style="margin-top: 0;">Add Schedule</h2>
            <form id="addScheduleForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; overflow: hidden;">
                <!-- Column 1 -->
                <div style="display: flex; flex-direction: column;">
                    <label for="addEventSize">Shipment Size:</label>
                    <select id="addEventSize" name="eventSize" required style="width: 100%;">
                        <option value="">Select Size</option>
                        <option value="20ft">20ft</option>
                        <option value="40ft">40ft</option>
                        <option value="40ft HC">40ft HC</option>
                        <option value="45ft">45ft</option>
                    </select>

                    <label for="addEventPlateNo">Plate No.:</label>
                    <input type="text" id="addEventPlateNo" name="eventPlateNo" required style="width: 100%;">

                    <label for="addEventDate">Date & Time:</label>
                    <input type="datetime-local" id="addEventDate" name="eventDate" required style="width: 100%;">

                    <label for="addEventDriver">Driver:</label>
                    <select id="addEventDriver" name="eventDriver" required style="width: 100%;">
                        <option value="">Select Driver</option>
                    </select>

                    <label for="addEventHelper">Helper:</label>
                    <input type="text" id="addEventHelper" name="eventHelper" required style="width: 100%;">
                </div>

                <!-- Column 2 -->
                <div style="display: flex; flex-direction: column;">
                    <label for="addEventDispatcher">Dispatcher:</label>
                    <input type="text" id="addEventDispatcher" name="eventDispatcher" required style="width: 100%;">

                    <label for="addEventContainerNo">Container No.:</label>
                    <input type="text" id="addEventContainerNo" name="eventContainerNo" required style="width: 100%;">

                    <label for="addEventClient">Client:</label>
                    <select id="addEventClient" name="eventClient" required style="width: 100%;">
                        <option value="">Select Client</option>
                        <option value="Maersk">Maersk</option>
                        <option value="MSC">MSC</option>
                        <option value="COSCO">COSCO</option>
                        <option value="CMA CGM">CMA CGM</option>
                        <option value="Hapag-Lloyd">Hapag-Lloyd</option>
                        <option value="Evergreen">Evergreen</option>
                    </select>

                    <label for="addEventDestination">Destination:</label>
                    <select id="addEventDestination" name="eventDestination" required style="width: 100%;">
                        <option value="">Select Destination</option>
                        <option value="Manila Port">Manila Port</option>
                        <option value="Batangas Port">Batangas Port</option>
                        <option value="Subic Port">Subic Port</option>
                        <option value="Cebu Port">Cebu Port</option>
                        <option value="Davao Port">Davao Port</option>
                    </select>

                    <label for="addEventStatus">Status:</label>
                    <select id="addEventStatus" name="eventStatus" required style="width: 100%;">
                        <option value="Pending">Pending</option>
                        <option value="En Route">En Route</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

            <div style="grid-column: span 2; display: flex; gap: 15px; align-items: flex-end;">
        <div style="flex: 1;">
            <label for="addEventShippingLine">Shipping Line:</label>
            <select id="addEventShippingLine" name="eventShippingLine" required style="width: 100%;">
                <option value="">Select Shipping Line</option>
                <option value="Maersk Line">Maersk Line</option>
                <option value="Mediterranean Shipping Co.">Mediterranean Shipping Co.</option>
                <option value="COSCO Shipping">COSCO Shipping</option>
                <option value="CMA CGM">CMA CGM</option>
                <option value="Hapag-Lloyd">Hapag-Lloyd</option>
            </select>
        </div>
        
        <div style="flex: 1;">
            <label for="addEventConsignee">Consignee:</label>
            <input type="text" id="addEventConsignee" name="eventConsignee" required style="width: 100%;">
        </div>
    </div>

    <div style="grid-column: span 2;">
        <label for="addEventCashAdvance">Cash Advance:</label>
        <input type="text" id="addEventCashAdvance" name="eventCashAdvance" required style="width: 100%;">
    </div>

                <!-- Form buttons -->
                <div style="grid-column: span 2; display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                    <button type="button" class="close-btn cancel-btn" style="padding: 5px 10px;">Cancel</button>
                    <button type="submit" class="save-btn" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px;">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <div id="checklistModal" class="modal">
    <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <span class="close">&times;</span>
        <h3 style="margin-top: 0;">Driver Checklist</h3>
        <div id="checklistContent">
            <table class="events-table" style="width: 100%; margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody id="checklistTableBody"></tbody>
            </table>
        </div>
        <button type="button" class="close-btn cancel-btn" style="margin-top: 20px;">Close</button>
    </div>
</div>
        
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content2">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete this trip?</p>
            <input type="hidden" id="deleteEventId">
            <label for="deleteReason">Reason for deletion:</label>
            <textarea id="deleteReason" rows="4" style="width: 100%; margin: 10px 0;"></textarea>
            <button id="confirmDeleteBtn">Yes, Delete</button>
            <button type="button" class="close-btn cancel-btn">Cancel</button>
        </div>
    </div>

    
        <div class="filter-row">
        <div class="status-filter-container">
            <select id="statusFilter" onchange="filterTableByStatus()">
                <option value="" disabled selected>Status Filter</option>
    <option value="all">All Statuses</option>
    <option value="Pending">Pending</option>
    <option value="En Route">En Route</option>
    <option value="Completed">Completed</option>
    <option value="Cancelled">Cancelled</option>
    <option value="deleted">Deleted</option>
</select>
    <div class="search-container">
            <i class="fa fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search trips..." onkeyup="searchTrips()">
    </div>
    </div>
    </div>
    <div class="table-controls">
    <div class="table-info" id="showingInfo"></div>
    
    <div class="rows-per-page-container">
        <label for="rowsPerPage">Rows per page:</label>
        <select id="rowsPerPage" onchange="changeRowsPerPage()">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>
</div>


            <table class="events-table" id="eventsTable"> 
                <thead>
                    <tr>
                        <th>Plate No.</th>
                    <th>
                Date 
                <button id="dateSortBtn" style="background: none; border: none; cursor: pointer;">
                    <i class="fa fa-sort"></i>
                </button>
            </th>
                        <th>Time</th>
                        <th>Driver</th>
                        <th>Helper</th>
                        <th>Dispatcher</th>
                        <th>Container No.</th>
                        <th>Client</th>
                        <th>Destination</th>
                        <th>Shipping Line</th>
                        <th>Consignee</th>
                        <th>Size</th>
                        <th>Cash Advance</th>
                        <th>Status</th>
                        <th>Last Modified</th>
                            
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="eventTableBody"></tbody>
            </table>
            <div class="pagination-container">
                <div class="pagination">
                    <button class="prev" id="prevPageBtn">&laquo</button> 
                    <div id="page-numbers" class="page-numbers"></div>
                    <button class="next" id="nextPageBtn">&raquo</button>
                </div>
            </div>
        </div>

    <div id="editReasonsModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close">&times;</span>
            <h3>Edit Remarks</h3>
            <div id="editReasonsContent">
            
            </div>
            <button type="button" class="close-btn cancel-btn" style="margin-top: 20px;">Close</button>
        </div>
    </div>
    </div>



    <script>

         function formatDateTime(datetimeString) {
        if (!datetimeString) return 'N/A';
        const date = new Date(datetimeString);
        return date.toLocaleString(); 
    }
 let currentPage = 1;
let rowsPerPage = 5; 
let totalPages = 1;
let totalItems = 0;
  let currentStatusFilter = 'all';
  let dateSortOrder = 'desc';
let filteredEvents = [];
        
       $(document).ready(function() {
    rowsPerPage = parseInt($('#rowsPerPage').val());
    let now = new Date();
    let formattedNow = now.toISOString().slice(0,16); 
    $('#rowsPerPage').val(rowsPerPage);
    updateTableInfo(totalItems, 0);
    $('#statusFilter').on('change', filterTableByStatus);
    $('#editEventDate').attr('min', formattedNow);
    $('#addEventDate').attr('min', formattedNow); 
    $('#rowsPerPage').on('change', function() {
        rowsPerPage = parseInt($(this).val());
        currentPage = 1;
        renderTable();
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

    function renderTable() {
    const showDeleted = currentStatusFilter === 'deleted';
    const rowsPerPage = parseInt($('#rowsPerPage').val()); 
    
    let action;
    if (showDeleted) {
        action = 'get_deleted_trips';
    } else {
        action = 'get_active_trips';
    }
    
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ 
            action: action, 
            statusFilter: currentStatusFilter === 'deleted' ? 'all' : currentStatusFilter, 
            sortOrder: dateSortOrder,
            page: currentPage,
            perPage: rowsPerPage
        }),
        success: function(response) {
            if (response.success) {
                $('#eventTableBody').empty();
                
                if (response.trips.length === 0) {
                    $('#eventTableBody').html('<tr><td colspan="16">No trips found</td></tr>');
                } else {
                    renderTripRows(response.trips, showDeleted);
                }
                
                totalItems = response.total;
                totalPages = Math.ceil(totalItems / rowsPerPage);
                updatePagination(totalItems);
                updateTableInfo(totalItems, response.trips.length);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Server error occurred while loading trips');
        }
    });
}
    


    function checkMaintenanceConflict(plateNo, tripDate, callback) {
        $.ajax({
            url: 'include/handlers/maintenance_handler.php',
            type: 'GET',
            data: {
                action: 'checkMaintenance',
                plateNo: plateNo,
                tripDate: tripDate
            },
            success: function(response) {
                if (response.success && response.hasConflict) {
                    // Show warning modal
                    Swal.fire({
                        title: 'Maintenance Conflict',
                        html: `This truck has scheduled maintenance on <strong>${response.maintenanceDate}</strong>.<br><br>
                            Maintenance Type: <strong>${response.maintenanceType}</strong><br>
                            Remarks: <strong>${response.remarks}</strong>`,
                        icon: 'warning',
                        confirmButtonText: 'Continue Anyway',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        callback(result.isConfirmed);
                    });
                } else {
                    callback(true); // No conflict, proceed
                }
            },
            error: function() {
                console.error('Error checking maintenance');
                callback(true); // On error, proceed anyway
            }
        });
    }


    function renderTripRows(trips, showDeleted) {
        trips.forEach(function(trip) {
            const dateObj = new Date(trip.date);
            const formattedDate = dateObj.toLocaleDateString();
            const formattedTime = moment(dateObj).format('h:mm A');
            
            let statusCell = '';
            let actionCell = '';
            
            if (showDeleted || trip.is_deleted == 1) {
                statusCell = `<td><span class="status cancelled">Deleted</span></td>`;
                actionCell = `
                    <td class="actions">
                        <button class="icon-btn restore" data-tooltip="Restore" data-id="${trip.trip_id}">
                        <i class="fas fa-trash-restore"></i>
                        ${window.userRole === 'Full Admin' ? 
                        `<button class="icon-btn full-delete" data-tooltip="Permanently Delete">
                            <i class="fa-solid fa-ban"></i>
                        </button>` : ''}
                        </button>
                    </td>
                `;
            } else {
                statusCell = `<td><span class="status ${trip.status.toLowerCase().replace(/\s+/g, '')}">${trip.status}</span></td>`;
                actionCell = `
                    <td class="actions">
                        <button class="icon-btn edit" data-tooltip="Edit" data-id="${trip.trip_id}"><i class="fas fa-edit"></i></button>
                        <button class="icon-btn delete" data-tooltip="Delete" data-id="${trip.trip_id}"><i class="fas fa-trash-alt"></i></button>
                        ${trip.edit_reasons && trip.edit_reasons !== 'null' && trip.edit_reasons !== '' ? 
                        `<button class="icon-btn view-reasons" data-tooltip="View Edit History" data-id="${trip.trip_id}">
                            <i class="fas fa-history"></i>
                        </button>` : ''}
                    </td>
                `;
            }

            const row = `
                 <tr class="${showDeleted || trip.is_deleted == 1 ? 'deleted-row' : ''}">
                    <td>${trip.plate_no || 'N/A'}</td>
                    <td>${formattedDate}</td>
                    <td>${formattedTime}</td>
                    <td>${trip.driver || 'N/A'}</td>
                    <td>${trip.helper || 'N/A'}</td>
                    <td>${trip.dispatcher || 'N/A'}</td>
                    <td>${trip.container_no || 'N/A'}</td>
                    <td>${trip.client || 'N/A'}</td>
                    <td>${trip.destination || 'N/A'}</td>
                    <td>${trip.shippine_line || 'N/A'}</td>
                    <td>${trip.consignee || 'N/A'}</td>
                    <td>${trip.size || 'N/A'}</td>
                    <td>${trip.cash_adv || 'N/A'}</td>
                    ${statusCell}
                    <td>
                        <strong>${trip.last_modified_by || 'System'}</strong><br>
                        ${formatDateTime(trip.last_modified_at)}
                    </td>
                    ${actionCell}
                </tr>
            `;
            $('#eventTableBody').append(row);
        });
    }



            var eventsData = <?php echo $eventsDataJson; ?>;
            var driversData = <?php echo $driversDataJson; ?>;
            



            // Populate driver dropdowns
    function populateDriverDropdowns(selectedSize = '', currentDriver = '') {
        // First get the list of all trucks with their statuses
        $.ajax({
            url: 'include/handlers/truck_handler.php?action=getTrucks',
            type: 'GET',
            async: false, // We need to wait for this response
            success: function(truckResponse) {
                if (truckResponse.success) {
                    // Identify unavailable trucks (In Repair, Overdue, or Deleted)
                    var unavailableTruckIds = truckResponse.trucks
                        .filter(truck => 
                            truck.display_status === 'In Repair' || 
                            truck.display_status === 'Overdue' ||
                            truck.is_deleted == 1
                        )
                        .map(truck => truck.truck_id.toString());
                    
                    var driverOptions = '<option value="">Select Driver</option>';
                    
                    driversData.forEach(function(driver) {
                        // Skip drivers assigned to unavailable trucks
                        if (driver.assigned_truck_id && 
                            unavailableTruckIds.includes(driver.assigned_truck_id.toString())) {
                            return; // skip this driver
                        }
                        
                        // Filter drivers based on selected size if specified
                        if (!selectedSize || !driver.capacity || 
                            (selectedSize.includes('20') && driver.capacity === '20') ||
                            (selectedSize.includes('40') && driver.capacity === '40')) {
                            
                            // Check if this is the current driver being edited
                            var selectedAttr = (driver.name === currentDriver) ? ' selected' : '';
                            
                            // Include truck_plate_no as a data attribute
                            driverOptions += `
                                <option 
                                    value="${driver.name}" 
                                    data-plate-no="${driver.truck_plate_no || ''}"
                                    data-driver-id="${driver.id || ''}"
                                    ${selectedAttr}
                                >
                                    ${driver.name}
                                    ${driver.truck_plate_no ? ` (${driver.truck_plate_no})` : ''}
                                    ${driver.capacity ? ` [${driver.capacity}ft]` : ''}
                                </option>
                            `;
                        }
                    });
                    
                    // Add a disabled option for unavailable drivers if any were filtered out
                    var unavailableDrivers = driversData.filter(driver => 
                        driver.assigned_truck_id && 
                        unavailableTruckIds.includes(driver.assigned_truck_id.toString())
                    );
                    
                    if (unavailableDrivers.length > 0) {
                        driverOptions += '<optgroup label="Unavailable Drivers">';
                        unavailableDrivers.forEach(function(driver) {
                            var status = truckResponse.trucks.find(t => 
                                t.truck_id.toString() === driver.assigned_truck_id.toString()
                            ).display_status;
                            
                            driverOptions += `
                                <option 
                                    disabled
                                    title="Assigned truck is ${status === 'Deleted' ? 'deleted' : status.toLowerCase()}"
                                >
                                    ${driver.name} (Unavailable)
                                </option>
                            `;
                        });
                        driverOptions += '</optgroup>';
                    }
                    
                    $('#editEventDriver').html(driverOptions);
                    $('#addEventDriver').html(driverOptions);
                } else {
                    console.error('Error fetching truck data:', truckResponse.message);
                   
                    populateAllDrivers(selectedSize, currentDriver);
                }
            },
            error: function() {
                console.error('Error fetching truck data');
                
                populateAllDrivers(selectedSize, currentDriver);
            }
        });
    }

  


    $('#dateSortBtn').on('click', function() {
        dateSortOrder = dateSortOrder === 'desc' ? 'asc' : 'desc';
        renderTable();
    });

    // Fallback function to show all drivers
    function populateAllDrivers(selectedSize = '', currentDriver = '') {
        var driverOptions = '<option value="">Select Driver</option>';
        driversData.forEach(function(driver) {
            if (!selectedSize || !driver.capacity || 
                (selectedSize.includes('20') && driver.capacity === '20') ||
                (selectedSize.includes('40') && driver.capacity === '40')) {
                
                var selectedAttr = (driver.name === currentDriver) ? ' selected' : '';
                driverOptions += `
                    <option 
                        value="${driver.name}" 
                        data-plate-no="${driver.truck_plate_no || ''}"
                        data-driver-id="${driver.id || ''}"
                        ${selectedAttr}
                    >
                        ${driver.name}
                        ${driver.truck_plate_no ? ` (${driver.truck_plate_no})` : ''}
                        ${driver.capacity ? ` [${driver.capacity}ft]` : ''}
                    </option>
                `;
            }
        });
        $('#editEventDriver').html(driverOptions);
        $('#addEventDriver').html(driverOptions);
    }

    $(document).on('change', '#addEventDriver, #editEventDriver', function() {
            var selectedOption = $(this).find('option:selected');
            var plateNo = selectedOption.data('plate-no');
            
            // Determine which form we're in (add or edit)
            var isAddForm = $(this).attr('id') === 'addEventDriver';
            var plateNoField = isAddForm ? '#addEventPlateNo' : '#editEventPlateNo';
            
            $(plateNoField).val(plateNo || '');
        });

    // Add event listener for size dropdown changes
    $('#addEventSize, #editEventSize').on('change', function() {
        var selectedSize = $(this).val();
        var isAddForm = $(this).attr('id') === 'addEventSize';
        populateDriverDropdowns(selectedSize, isAddForm);
    });


    $('#viewDeletedBtn').on('click', function() {
        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            data: JSON.stringify({ action: 'get_deleted_trips' }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('#deletedTripsBody').empty();
                    response.trips.forEach(function(trip) {
                        var row = `
                            <tr>
                                <td>${trip.plate_no}</td>
                                <td>${formatDateTime(trip.date)}</td>
                                <td>${trip.driver}</td>
                                <td>${trip.destination}</td>
                                <td>${trip.last_modified_by}</td>
                                <td>${formatDateTime(trip.last_modified_at)}</td>
                                <td>${trip.delete_reason || 'No reason provided'}</td>
                            </tr>
                        `;
                        $('#deletedTripsBody').append(row);
                    });
                    $('#deletedTripsModal').show();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Server error occurred');
            }
        });
    });


    $(document).on('change', '#addEventDriver, #editEventDriver', function() {
        var selectedDriverName = $(this).val();
        var driver = driversData.find(function(d) { 
            return d.name === selectedDriverName; 
        });
        
        if (driver && driver.truck_plate_no) {
            // Determine which form we're in (add or edit)
            var isAddForm = $(this).attr('id') === 'addEventDriver';
            var plateNoField = isAddForm ? '#addEventPlateNo' : '#editEventPlateNo';
            
            $(plateNoField).val(driver.truck_plate_no);
        } else {
            // Clear the plate number if driver has no assigned truck
            var isAddForm = $(this).attr('id') === 'addEventDriver';
            var plateNoField = isAddForm ? '#addEventPlateNo' : '#editEventPlateNo';
            $(plateNoField).val('');
        }
    });
            
            // Format events for calendar
                var calendarEvents = eventsData.map(function(event) {
        return {
            id: event.id,
            title: event.client + ' - ' + event.destination,
            start: event.date,
            plateNo: event.plateNo,
            driver: event.driver,
            driver_id: event.driver_id, // eto yung driver_id
            helper: event.helper,
                dispatcher: event.dispatcher,
            containerNo: event.containerNo,
            client: event.client,
            destination: event.destination,
            shippingLine: event.shippingLine,
            consignee: event.consignee,
            size: event.size,
            cashAdvance: event.cashAdvance,
            status: event.status,
            modifiedby: event.modifiedby,
            modifiedat: event.modifiedat,
            truck_plate_no: event.truck_plate_no,
            truck_capacity: event.truck_capacity,
            edit_reasons: event.edit_reasons 
        };
    });

    function resetAddScheduleForm() {
        $('#addEventPlateNo').val('');
        $('#addEventDate').val('');
        $('#addEventDriver').val('').trigger('change');
        $('#addEventHelper').val('');
        $('#addEventContainerNo').val('');
        $('#addEventClient').val('');
        $('#addEventDestination').val('');
        $('#addEventShippingLine').val('');
        $('#addEventConsignee').val('');
        $('#addEventSize').val('');
        $('#addEventCashAdvance').val('');
        $('#addEventStatus').val('Pending'); // Set default status
    }
            // Close modal handlers
        $('.close, .close-btn.cancel-btn').on('click', function() {
        $('.modal').hide();
        if ($(this).closest('#addScheduleModal').length) {
            resetAddScheduleForm();
        }
    });

    // Also reset when clicking outside the modal
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').hide();
            if ($(event.target).is('#addScheduleModal')) {
                resetAddScheduleForm();
            }
        }
    });
            
        $('#addScheduleBtnTable').on('click', function() {
        resetAddScheduleForm(); // Clear the form first
        populateDriverDropdowns(); // Repopulate drivers
        $('#addScheduleModal').show();
    });

       function filterTableByStatus() {
    currentStatusFilter = document.getElementById('statusFilter').value;
    currentPage = 1; 
    renderTable();
}

        function updatePagination(totalItems) {
    const pageNumbers = $('#page-numbers');
    pageNumbers.empty();
    

    $('#prevPageBtn').prop('disabled', currentPage === 1);
    
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    

    if (startPage > 1) {
        const firstPageBtn = $('<button>')
            .text('1')
            .addClass('page-number')
            .attr('data-page', 1);
        if (currentPage === 1) firstPageBtn.addClass('active');
        firstPageBtn.on('click', function() {
            goToPage(parseInt($(this).attr('data-page')));
        });
        pageNumbers.append(firstPageBtn);
        
        if (startPage > 2) {
            pageNumbers.append('<span class="ellipsis">...</span>');
        }
    }
    

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = $('<button>')
            .text(i)
            .addClass('page-number')
            .attr('data-page', i);
        if (i === currentPage) pageBtn.addClass('active');
        pageBtn.on('click', function() {
            goToPage(parseInt($(this).attr('data-page')));
        });
        pageNumbers.append(pageBtn);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            pageNumbers.append('<span class="ellipsis">...</span>');
        }
        
        const lastPageBtn = $('<button>')
            .text(totalPages)
            .addClass('page-number')
            .attr('data-page', totalPages);
        if (currentPage === totalPages) lastPageBtn.addClass('active');
        lastPageBtn.on('click', function() {
            goToPage(parseInt($(this).attr('data-page')));
        });
        pageNumbers.append(lastPageBtn);
    }
    
    // Next button
    $('#nextPageBtn').prop('disabled', currentPage === totalPages);
}

            function goToPage(page) {
                currentPage = page;
                renderTable();
            }

            function changePage(step) {
                var newPage = currentPage + step;
                if (newPage >= 1 && newPage <= totalPages) {
                    currentPage = newPage;
                    renderTable();
                }
            }

            $('#prevPageBtn').on('click', function() {
                if (currentPage > 1) {
                goToPage(currentPage - 1);
              }
            });

            $('#nextPageBtn').on('click', function() {
                 if (currentPage < totalPages) {
                goToPage(currentPage + 1);
                 }
            });

            // Initialize calendar
           $('#calendar').fullCalendar({
    header: { 
        left: 'prev,next today', 
        center: 'title', 
        right: 'month,agendaWeek,agendaDay' 
    },
    events: calendarEvents,
    timeFormat: 'h:mm A', 
    displayEventTime: true, 
    displayEventEnd: false, 
    eventRender: function(event, element) {
        element.find('.fc-title').css({
            'white-space': 'normal',
            'overflow': 'visible'
        });
        
        element.find('.fc-title').html(event.client + ' - ' + event.destination);
        
        var statusClass = 'status ' + event.status.toLowerCase().replace(/\s+/g, '');
        element.addClass(statusClass);
    },
    // Add this to automatically select today's date
    viewRender: function(view, element) {
        if (view.name === 'month') {
            // Trigger a click on today's cell
            setTimeout(function() {
                $('.fc-today').trigger('click');
            }, 100);
        }
    },
    dayClick: function(date, jsEvent, view) {
        var clickedDay = $(this);
        
        $('.fc-day').removeClass('fc-day-selected');
        clickedDay.addClass('fc-day-selected');
        
        var eventsOnDay = $('#calendar').fullCalendar('clientEvents', function(event) {
            return moment(event.start).isSame(date, 'day');
        });
        
        var formattedDate = moment(date).format('MMMM D, YYYY');
        $('#eventDetails h4').text('Event Details - ' + formattedDate);
        
        $('#eventList').empty();
        $('#noEventsMessage').hide();
        
        if (eventsOnDay.length > 0) {
            eventsOnDay.forEach(function(event) {
                var hasEditReasons = event.edit_reasons && event.edit_reasons !== 'null' && event.edit_reasons !== '';
                var viewRemarksButton = hasEditReasons ? 
                    `<button class="edit-btn2 view-reasons-btn" data-id="${event.id}" style="margin-top: 10px;">View Remarks</button>` : 
                    '';
                
                var eventThumbnail = `
                    <div class="event-thumbnail">
                        <strong>Date:</strong> ${moment(event.start).format('MMMM D, YYYY')}<br>
                        <strong>Plate No:</strong> ${event.plateNo}<br>
                        <strong>Destination:</strong> ${event.destination}
                    </div>
                    <div class="event-details">
                        <p><strong>Driver:</strong> ${event.driver}</p>
                        <p><strong>Helper:</strong> ${event.helper}</p>
                        <p><strong>Dispatcher:</strong> ${event.dispatcher || 'N/A'}</p>
                        <p><strong>Client:</strong> ${event.client}</p>
                        <p><strong>Container No.:</strong> ${event.containerNo}</p>
                        <td> <p><strong>Status:</strong> <span class="status ${event.status.toLowerCase().replace(/\s+/g, '')}">${event.status}</span></p></td> 
                        <p><strong>Cash Advance:</strong> ${event.cashAdvance}</p>
                        <p><strong>Last modified by: </strong>${event.modifiedby}<br>
                        <strong>Last Modified at: </strong>${formatDateTime(event.modifiedat)}</p>
                        <div class="event-actions" style="margin-top: 15px;">
                            <button class="icon-btn edit" data-tooltip="Edit" data-id="${event.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="icon-btn delete" data-tooltip="Delete" data-id="${event.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                            ${hasEditReasons ? 
                                `<button class="icon-btn view-reasons" data-tooltip="View Edit History" data-id="${event.id}">
                                    <i class="fas fa-history"></i>
                                </button>` : ''}
                        </div>
                    </div>
                `;
                $('#eventList').append(eventThumbnail);
            });
        } else {
            $('#noEventsMessage').show();
        }

        // Toggle event details on thumbnail click
        $('.event-thumbnail').on('click', function() {
            $(this).next('.event-details').toggle();
        });
    }
});

setTimeout(function() {
    var today = moment().startOf('day');
    var eventsToday = $('#calendar').fullCalendar('clientEvents', function(event) {
        return moment(event.start).isSame(today, 'day');
    });
    
    var formattedDate = today.format('MMMM D, YYYY');
    $('#eventDetails h4').text('Event Details - ' + formattedDate);
    
    $('#eventList').empty();
    $('#noEventsMessage').hide();
    
    if (eventsToday.length > 0) {
        eventsToday.forEach(function(event) {
            var hasEditReasons = event.edit_reasons && event.edit_reasons !== 'null' && event.edit_reasons !== '';
            var viewRemarksButton = hasEditReasons ? 
                `<button class="edit-btn2 view-reasons-btn" data-id="${event.id}" style="margin-top: 10px;">View Remarks</button>` : 
                '';
            
            var eventThumbnail = `
                <div class="event-thumbnail">
                    <strong>Date:</strong> ${moment(event.start).format('MMMM D, YYYY')}<br>
                    <strong>Plate No:</strong> ${event.plateNo}<br>
                    <strong>Destination:</strong> ${event.destination}
                </div>
                <div class="event-details">
                    <p><strong>Driver:</strong> ${event.driver}</p>
                    <p><strong>Helper:</strong> ${event.helper}</p>
                    <p><strong>Dispatcher:</strong> ${event.dispatcher || 'N/A'}</p>
                    <p><strong>Client:</strong> ${event.client}</p>
                    <p><strong>Container No.:</strong> ${event.containerNo}</p>
                    <td> <p><strong>Status:</strong> <span class="status ${event.status.toLowerCase().replace(/\s+/g, '')}">${event.status}</span></p></td> 
                    <p><strong>Cash Advance:</strong> ${event.cashAdvance}</p>
                    <p><strong>Last modified by: </strong>${event.modifiedby}<br>
                    <strong>Last Modified at: </strong>${formatDateTime(event.modifiedat)}</p>
                    <div class="event-actions" style="margin-top: 15px;">
                        <button class="icon-btn edit" data-tooltip="Edit" data-id="${event.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="icon-btn delete" data-tooltip="Delete" data-id="${event.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                        ${hasEditReasons ? 
                            `<button class="icon-btn view-reasons" data-tooltip="View Edit History" data-id="${event.id}">
                                <i class="fas fa-history"></i>
                            </button>` : ''}
                    </div>
                </div>
            `;
            $('#eventList').append(eventThumbnail);
        });
    } else {
        $('#noEventsMessage').show();
    }
    
    // Highlight today's date
    $('.fc-today').addClass('fc-day-selected');
}, 500);
            
            // View toggle buttons
    $('#calendarViewBtn').on('click', function() {
        $(this).addClass('active');
        $('#tableViewBtn').removeClass('active');
        $('#calendar').show();
        $('#eventDetails').show();
        $('#eventsTable, #eventTableBody, .pagination-container, .filter-row').hide();
        $('body').removeClass('table-view'); 
        $('#calendar').fullCalendar('render');
    });

    $('#tableViewBtn').on('click', function() {
        $(this).addClass('active');
        $('#calendarViewBtn').removeClass('active');
        $('#calendar').hide();
        $('#eventDetails').hide();
        $('#eventsTable, #eventTableBody, .pagination-container, .filter-row, .rows-per-page-container').show(); 
        $('body').addClass('table-view');
        currentPage = 1;
        renderTable();
    });
            // Edit button click handler
   $(document).on('click', '.icon-btn.edit', function() {
    var eventId = $(this).data('id');
    var event = eventsData.find(function(e) { return e.id == eventId; });
    
    if (event) {
        $('#editEventId').val(event.id);
        $('#editEventPlateNo').val(event.truck_plate_no || event.plateNo);
        
        var eventDate = event.date.includes(':00.') 
            ? event.date.replace(/:00\.\d+Z$/, '') 
            : event.date;
        $('#editEventDate').val(eventDate);

        populateDriverDropdowns(event.size);
        setTimeout(() => {
            $('#editEventDriver').val(event.driver);
        }, 100);
        
        $('#editEventHelper').val(event.helper);
        $('#editEventDispatcher').val(event.dispatcher || '');
        $('#editEventContainerNo').val(event.containerNo);
        $('#editEventClient').val(event.client);
        $('#editEventDestination').val(event.destination);
        $('#editEventShippingLine').val(event.shippingLine);
        $('#editEventConsignee').val(event.consignee);
        $('#editEventSize').val(event.size);
        $('#editEventCashAdvance').val(event.cashAdvance);
        $('#editEventStatus').val(event.status);

        // Check for both Completed status AND if driver_id exists
        if (event.status === 'Completed') {
    $('#viewExpensesBtn').show();
} else {
    $('#viewExpensesBtn').hide();
}

// Show checklist button if we have a driver_id and status is not Cancelled
if (event.driver_id && event.status !== 'Cancelled') {
    $('#viewChecklistBtn').show();
} else {
    $('#viewChecklistBtn').hide();
}
       
        
        $('#editModal').show();
    }
});

    $(document).on('click', '#viewExpensesBtn', function() {
        var tripId = $('#editEventId').val();
        
        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'get_expenses',
                tripId: tripId
            }),
            success: function(response) {
                if (response.success) {
                    $('#expensesTableBody').empty();
                    
                    if (response.expenses.length > 0) {
                        response.expenses.forEach(function(expense) {
                            var row = `
                                <tr>
                                    <td>${expense.expense_type}</td>
                                    <td>${expense.amount}</td>
                                </tr>
                            `;
                            $('#expensesTableBody').append(row);
                        });
                    } else {
                        $('#expensesTableBody').html('<tr><td colspan="2" style="text-align: center;">No expenses recorded for this trip</td></tr>');
                    }
                    
                    $('#expensesModal').show();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Server error occurred while loading expenses');
            }
        });
    });
            

            $(document).on('click', '.icon-btn.delete', function() {
                var eventId = $(this).data('id');
                $('#deleteEventId').val(eventId);
                $('#deleteConfirmModal').show();
            });
            
        $('#addScheduleForm').on('submit', function(e) {
        e.preventDefault();

        var selectedDriver = $('#addEventDriver').val();
        var driver = driversData.find(d => d.name === selectedDriver);
        var truckPlateNo = driver && driver.truck_plate_no ? driver.truck_plate_no : $('#addEventPlateNo').val();
        var tripDate = $('#addEventDate').val();

        checkMaintenanceConflict(truckPlateNo, tripDate, function(shouldProceed) {
            if (!shouldProceed) {
                return; // User cancelled after seeing maintenance warning
            }
        
        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'add',
                plateNo: truckPlateNo,
                date: $('#addEventDate').val(),
                driver: selectedDriver,
                helper: $('#addEventHelper').val(),
                dispatcher: $('#addEventDispatcher').val(),
                containerNo: $('#addEventContainerNo').val(),
                client: $('#addEventClient').val(),
                destination: $('#addEventDestination').val(),
                shippingLine: $('#addEventShippingLine').val(),
                consignee: $('#addEventConsignee').val(),
                size: $('#addEventSize').val(),
                cashAdvance: $('#addEventCashAdvance').val(),
                status: $('#addEventStatus').val()
            }),
            success: function(response) {
                console.log('Raw response:', response);
                if (response.success) {
                    alert('Trip added successfully!');
                    $('#addScheduleModal').hide();
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('XHR:', xhr);
                console.log('Status:', status);
                console.log('Error:', error);
                console.log('Response Text:', xhr.responseText);
                alert('Server error occurred. Check console for details.');
            }
        });

        });
    });

    $(document).on('click', '.icon-btn.view-reasons', function() {
        var eventId = $(this).data('id');
        var event = eventsData.find(function(e) { return e.id == eventId; });
        
        if (event && event.edit_reasons) {
            try {
                var reasons = JSON.parse(event.edit_reasons);
                var html = '<div style="padding: 10px; background: #f9f9f9; border-radius: 5px; margin-bottom: 10px;">';
                html += '<ul style="list-style-type: none; padding-left: 5px;">';
                
                reasons.forEach(function(reason) {
                    // Format the reason with a bullet point and proper spacing
                    html += '<li style="margin-bottom: 8px; padding-left: 15px; position: relative;">';
                    html += '<span style="position: absolute; left: 0;">•</span> ' + reason;
                    html += '</li>';
                });
                
                html += '</ul>';
                html += '<p style="font-style: italic; margin-top: 10px; color: #666;">';
                html += 'Last modified by: ' + (event.modifiedby || 'System') + '<br>';
                html += 'On: ' + formatDateTime(event.modifiedat);
                html += '</p></div>';
                
                $('#editReasonsContent').html(html);
                $('#editReasonsModal').show();
            } catch (e) {
                console.error('Error parsing edit reasons:', e);
                $('#editReasonsContent').html('<div style="padding: 15px; background: #fff8f8; border: 1px solid #ffdddd;">'+
                    '<p>Error displaying edit history</p></div>');
                $('#editReasonsModal').show();
            }
        } else {
            $('#editReasonsContent').html('<div style="padding: 15px; background: #f5f5f5; border-radius: 5px;">'+
                '<p>No edit remarks recorded for this trip</p></div>');
            $('#editReasonsModal').show();
        }
    });
            // Edit form submit handler
        $('#editForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get selected driver to find their assigned truck
        var selectedDriver = $('#editEventDriver').val();
        var driver = driversData.find(d => d.name === selectedDriver);
        var truckPlateNo = driver && driver.truck_plate_no ? driver.truck_plate_no : $('#editEventPlateNo').val();
        var tripDate = $('#editEventDate').val();

        checkMaintenanceConflict(truckPlateNo, tripDate, function(shouldProceed) {
            if (!shouldProceed) {
                return; // User cancelled after seeing maintenance warning
            }

        var editReasons = [];
        $('input[name="editReason"]:checked').each(function() {
            editReasons.push($(this).val());
        });
        
        // Add other reason if specified
        var otherReason = $('#otherReasonText').val();
        if (otherReason && editReasons.includes('Other')) {
            editReasons[editReasons.indexOf('Other')] = 'Other: ' + otherReason;
        }
        
        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'edit',
                id: $('#editEventId').val(),
                plateNo: truckPlateNo,
                date: $('#editEventDate').val(),
                driver: selectedDriver,
                helper: $('#editEventHelper').val(),
                dispatcher: $('#editEventDispatcher').val(),
                containerNo: $('#editEventContainerNo').val(),
                client: $('#editEventClient').val(),
                destination: $('#editEventDestination').val(),
                shippingLine: $('#editEventShippingLine').val(),
                consignee: $('#editEventConsignee').val(),
                size: $('#editEventSize').val(),
                cashAdvance: $('#editEventCashAdvance').val(),
                status: $('#editEventStatus').val(),
                editReasons: editReasons
            }),
            success: function(response) {
                if (response.success) {
                    alert('Trip updated successfully!');
                    $('#editModal').hide();
                    location.reload(); 
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Server error occurred');
            }
        });
        });
    });
                
        $('#confirmDeleteBtn').on('click', function() {
        var eventId = $('#deleteEventId').val();
        var deleteReason = $('#deleteReason').val();
        
        if (!deleteReason) {
            alert('Please provide a reason for deletion');
            return;
        }
        
        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'delete',
                id: eventId,
                reason: deleteReason
            }),
            success: function(response) {
                if (response.success) {
                    alert('Trip marked as deleted successfully!');
                    $('#deleteConfirmModal').hide();
                    location.reload(); 
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Server error occurred');
            }
        });
    });
            // Initial render
            renderTable();
        });

        document.getElementById('toggleSidebarBtn').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('expanded');
        });

        $('#otherReasonText').on('input', function() {
        if ($(this).val().trim() !== '') {
            $('#reason6').prop('checked', true);
        }
    });


    $('#reason6').on('change', function() {
        if (!$(this).is(':checked')) {
            $('#otherReasonText').val('');
        }
    });

    function searchTrips() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const table = document.getElementById('eventsTable');
        const rows = table.getElementsByTagName('tr');
        
        // Skip the header row (index 0)
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            let found = false;
            
            // Check each cell in the row (skip the last one which has actions)
            for (let j = 0; j < row.cells.length - 1; j++) {
                const cell = row.cells[j];
                if (cell.textContent.toLowerCase().includes(searchTerm)) {
                    found = true;
                    break;
                }
            }
            
            if (found || searchTerm === '') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }


    $(document).on('click', '.icon-btn.full-delete', function() {
        const tripId = $(this).closest('.icon-btn.restore').data('id');
        const $row = $(this).closest('tr');

        Swal.fire({
            title: 'Permanently Delete Trip?',
            text: "This action cannot be undone! The trip will be permanently deleted from the database.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete permanently!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'include/handlers/trip_operations.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        action: 'full_delete',
                        id: tripId
                    }),
                    success: function(response) {
                        if (response.success) {
                            // Update stats cards with the returned data
                            $('.stat-value').eq(0).text(response.stats.pending);
                            $('.stat-value').eq(1).text(response.stats.enroute);
                            $('.stat-value').eq(2).text(response.stats.completed);
                            $('.stat-value').eq(3).text(response.stats.cancelled);
                            $('.stat-value').eq(4).text(response.stats.total);

                            // Remove the row from the table
                            $row.remove();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'The trip has been permanently deleted.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to delete trip'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Server error occurred'
                        });
                    }
                });
            }
        });
    });

    function loadDeletedTrips() {
        $.ajax({
            url: 'include/handlers/trip_operations.php',
            type: 'POST',
            data: JSON.stringify({ action: 'get_deleted_trips' }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('#deletedTripsBody').empty();
                    response.trips.forEach(function(trip) {
                        var row = `
                            <tr>
                                <td>${trip.plate_no || 'N/A'}</td>
                                <td>${formatDateTime(trip.date)}</td>
                                <td>${trip.driver || 'N/A'}</td>
                                <td>${trip.helper || 'N/A'}</td>
                                <td>${trip.dispatcher || 'N/A'}</td>
                                <td>${trip.container_no || 'N/A'}</td>
                                <td>${trip.client || 'N/A'}</td>
                                <td>${trip.destination || 'N/A'}</td>
                                <td>${trip.shippine_line || 'N/A'}</td>
                                <td>${trip.consignee || 'N/A'}</td>
                                <td>${trip.size || 'N/A'}</td>
                                <td>${trip.cash_adv || 'N/A'}</td>
                                <td><span class="status ${trip.status ? trip.status.toLowerCase().replace(/\s+/g, '') : ''}">${trip.status || 'N/A'}</span></td>
                                <td>${trip.last_modified_by || 'System'}</td>
                                <td>${formatDateTime(trip.last_modified_at)}</td>
                                <td>${trip.delete_reason || 'No reason provided'}</td>
                                <td><button class="restore-btn" data-id="${trip.trip_id}">Restore</button></td>
                            </tr>
                        `;
                        $('#deletedTripsBody').append(row);
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Server error occurred while loading deleted trips');
            }
        });
    }

    $(document).on('click', '.icon-btn.restore', function() {
        const tripId = $(this).data('id');
    const $row = $(this).closest('tr');

        if (confirm('Are you sure you want to restore this trip?')) {
            $.ajax({
                url: 'include/handlers/trip_operations.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'restore',
                    id: tripId
                }),
                success: function(response) {
                    if (response.success) {
                        // Update stats cards with the returned data
                        $('.stat-value').eq(0).text(response.stats.pending);
                        $('.stat-value').eq(1).text(response.stats.enroute);
                        $('.stat-value').eq(2).text(response.stats.completed);
                        $('.stat-value').eq(3).text(response.stats.cancelled);
                        $('.stat-value').eq(4).text(response.stats.total);

                        $row.remove();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Trip restored successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        renderTable(); // Refresh the table
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Server error occurred');
                }
            });
        }
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


    document.getElementById('reason6').addEventListener('change', function() {
        const otherReasonContainer = document.getElementById('otherReasonContainer');
        otherReasonContainer.style.display = this.checked ? 'block' : 'none';
        
        
        if (!this.checked) {
            document.getElementById('otherReasonText').value = '';
        }
    });


    document.getElementById('otherReasonText').addEventListener('input', function() {
        if (this.value.trim() !== '') {
            document.getElementById('reason6').checked = true;
            document.getElementById('otherReasonContainer').style.display = 'block';
        }
    });


    document.querySelector('form').addEventListener('submit', function(e) {
        const otherCheckbox = document.getElementById('reason6');
        const otherReasonText = document.getElementById('otherReasonText').value.trim();
        
        if (otherCheckbox.checked && otherReasonText === '') {
            e.preventDefault();
            alert('Please specify the other reason');
            document.getElementById('otherReasonText').focus();
        }
    });

$('#viewChecklistBtn').on('click', function() {
    var tripId = $('#editEventId').val();
    
    $.ajax({
        url: 'include/handlers/trip_operations.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'get_checklist',
            trip_id: tripId
        }),
        success: function(response) {
            if (response.success && response.checklist) {
                $('#checklistTableBody').empty();
                
                var checklist = response.checklist;
                var rows = [
                    { question: 'No body fatigue?', response: checklist.no_fatigue ? 'Yes' : 'No' },
                    { question: 'Did not take illegal drugs?', response: checklist.no_drugs ? 'Yes' : 'No' },
                    { question: 'No mental distractions?', response: checklist.no_distractions ? 'Yes' : 'No' },
                    { question: 'No body illness?', response: checklist.no_illness ? 'Yes' : 'No' },
                    { question: 'Fit to work?', response: checklist.fit_to_work ? 'Yes' : 'No' },
                    { question: 'Alcohol test reading', response: checklist.alcohol_test },
                    { question: 'Hours of sleep', response: checklist.hours_sleep },
                    { question: 'Submitted at', response: formatDateTime(checklist.submitted_at) }
                ];
                
                rows.forEach(function(row) {
                    $('#checklistTableBody').append(`
                        <tr>
                            <td>${row.question}</td>
                            <td>${row.response}</td>
                        </tr>
                    `);
                });
                
                $('#checklistModal').show();
            } else {
                alert('No checklist data found for this trip');
            }
        },
        error: function() {
            alert('Server error occurred while loading checklist');
        }
    });
});

// Show/hide the checklist button based on trip status
$('#editEventStatus').on('change', function() {
    if ($(this).val() !== 'Cancelled') {
        $('#viewChecklistBtn').show();
    } else {
        $('#viewChecklistBtn').hide();
    }
});

    function updateStats() {
        $.ajax({
            url: 'include/handlers/triplogstats.php',
            type: 'GET',
            dataType: 'json',
            success: function(stats) {
                $('.stat-value').eq(0).text(stats.pending);
                $('.stat-value').eq(1).text(stats.enroute);
                $('.stat-value').eq(2).text(stats.completed);
                $('.stat-value').eq(3).text(stats.cancelled);
                $('.stat-value').eq(4).text(stats.total);
            }
        });
    }

    updateStats();
    setInterval(updateStats, 300000);
    console.log("Filtering by:", currentStatusFilter, "Found:", filteredEvents.length, "events");


function goToPage(page) {
    currentPage = page;
    renderTable();
}

function changeRowsPerPage() {
    rowsPerPage = parseInt($('#rowsPerPage').val());
    currentPage = 1;
    renderTable();
}

function updateTableInfo(totalItems, currentItemsCount) {
    const tableInfo = $('.table-info');
    
    if (!totalItems || totalItems === 0) {
        tableInfo.text('No entries found');
        return;
    }

 const start = ((currentPage - 1) * rowsPerPage) + 1;
    const end = start + currentItemsCount - 1;

    tableInfo.text(`Showing ${start} to ${end} of ${totalItems} entries`);
 
}   



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
