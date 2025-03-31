<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/drivermanagement.css">
</head>
<body>
<?php
    // Sample data for trips - in a real app, this would come from a database
    $trips = [
        [
            'Vehicle' => 'Vehicle 1',
            'Driver' => 'Driver 1',
            'DriverAssistant' => 'Driver Assistant 1',
            'Client' => 'Client A',
            'ContainerNumber' => 'CONT123456',
            'Destination' => 'Location X',
            'DepartureTime' => '2025-03-29 10:00:00',
            'EstimatedArrivalTime' => '2025-03-30 14:00:00',
            'Status' => 'In Progress'
        ],
        [
            'Vehicle' => 'Vehicle 2',
            'Driver' => 'Driver 2',
            'DriverAssistant' => 'Driver Assistant 2',
            'Client' => 'Client B',
            'ContainerNumber' => 'CONT789012',
            'Destination' => 'Location Y',
            'DepartureTime' => '2025-03-28 08:30:00',
            'EstimatedArrivalTime' => '2025-03-28 18:30:00',
            'Status' => 'Completed'
        ]
    ];

    ?>
    <header class="header">
        <div class="logo-container">
            <img src="/img/logo.png" alt="Company Logo" class="logo">
            <img src="/img/mansar.png" alt="Company Name" class="company">
        </div>

        <div class="search-container">
            <input type="text" placeholder="Search..." class="search-bar">
        </div>

        <div class="profile">
            <i class="icon">✉</i>
            <img src="/img/profile.png" alt="Admin Profile" class="profile-icon">
            <div class="profile-name">Jesus Christ</div>
        </div>
    </header>

    <div class="sidebar">

    <div class="sidebar-item">
        <i class="icon2">🏠</i>
        <a asp-area="" asp-controller="Home" asp-action="LandingPage">Home</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚗</i>
        <span class="text">Driver Management</span>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚛</i>
        <a asp-area="" asp-controller="Home" asp-action="FleetManagement">Fleet Management</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">📋</i>
        <a asp-area="" asp-controller="Home" asp-action="TripLogs">Trip Logs</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">📍</i>
        <span class="text">Tracking</span>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🔧</i>
        <a asp-area="" asp-controller="Home" asp-action="PreventiveMaintenance" class="text">Maintenance Scheduling</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2"> 📈  </i>
        <span class="text">Fleet Performance Analytics</span>
    </div>
    <hr>
    <div class="sidebar-item">
        <i class="icon2"> ⚙️ </i>
        <span class="text">Settings</span>
    </div>
    <div class="sidebar-item">
        <i class="icon2"> 🚪 </i>
        <a asp-area="" asp-controller="Home" asp-action="Login" class="text">Logout</a>
    </div>
</div>

    <div class="main-content3">
        <section class="dashboard">
            <div class="container">
                <h2>Fleet Management</h2>
                <div class="button-row">
                    <button class="add_driver" onclick="openModal()">Assign Trip</button>
                </div>
                <!-- <br />

                <div class="overview">
                <div class="counter">
                    <div class="circle" id="total-vehicles">
                        <span class="count">15</span>
                    </div>
                    <p>Total Vehicles</p>
                </div>
                <div class="counter">
                    <div class="circle2" id="active-trips">
                        <span class="count"><?php echo countActiveTrips($trips); ?></span>
                    </div>
                    <p>Active Trips</p>
                </div>
                <div class="counter">
                    <div class="circle3" id="damaged-vehicles">
                        <span class="count">3</span>
                    </div>
                    <p>Damaged Vehicles</p>
                </div>
            </div> -->
        </section>

        <section id="assigned-trips">
            <h2>Assigned Trips</h2>
            <table>
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Driver Assistant</th>
                        <th>Client</th>
                        <th>Container No.</th>
                        <th>Destination</th>
                        <th>Departure Time</th>
                        <th>Estimated Arrival Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?php echo $trip['Vehicle']; ?></td>
                            <td><?php echo $trip['Driver']; ?></td>
                            <td><?php echo $trip['DriverAssistant']; ?></td>
                            <td><?php echo $trip['Client']; ?></td>
                            <td><?php echo $trip['ContainerNumber']; ?></td>
                            <td><?php echo $trip['Destination']; ?></td>
                            <td><?php echo date('F d, Y h:i A', strtotime($trip['DepartureTime'])); ?></td>
                            <td><?php echo date('F d, Y h:i A', strtotime($trip['EstimatedArrivalTime'])); ?></td>
                            <td><?php echo $trip['Status']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section id="view-vehicles">
            <h2>Vehicle Status</h2>
            <table>
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Last Trip</th>
                        <th>Location</th>
                        <th>Maintenance</th>
                        <th>Report Damage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Vehicle 1</td>
                        <td><?php echo isVehicleInUse($trips, 'Vehicle 1') ? 'In-Use' : 'Available'; ?></td>
                        <td><?php echo getLastTripDate($trips, 'Vehicle 1'); ?></td>
                        <td>Location A</td>
                        <td>Completed</td>
                        <td><button class="damage-report-button" data-vehicle="Vehicle 1">Report Damage</button></td>
                    </tr>
                    <tr>
                        <td>Vehicle 2</td>
                        <td><?php echo isVehicleInUse($trips, 'Vehicle 2') ? 'In-Use' : 'Available'; ?></td>
                        <td><?php echo getLastTripDate($trips, 'Vehicle 2'); ?></td>
                        <td>Location B</td>
                        <td>Completed</td>
                        <td><button class="damage-report-button" data-vehicle="Vehicle 2">Report Damage</button></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Assign Trip Modal -->
        <div id="assign-trip-modal" class="modal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h2>Assign Trip</h2>
                <form action="process_trip.php" method="post" id="assign-trip-form">
                    <?php
                    // PHP equivalent of AntiForgeryToken
                    $csrf_token = bin2hex(random_bytes(32));
                    $_SESSION['csrf_token'] = $csrf_token;
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="text-danger" id="validation-summary"></div>

                    <label for="Vehicle">Select Vehicle:</label>
                    <select name="Vehicle" id="vehicle">
                        <option value="Vehicle 1">Vehicle 1</option>
                        <option value="Vehicle 2">Vehicle 2</option>
                    </select>

                    <label for="Driver">Select Driver:</label>
                    <select name="Driver" id="driver">
                        <option value="Driver 1">Driver 1</option>
                        <option value="Driver 2">Driver 2</option>
                    </select>

                    <label for="DriverAssistant">Select Assistant:</label>
                    <select name="DriverAssistant" id="assistant">
                        <option value="Driver Assistant 1">Assistant 1</option>
                        <option value="Driver Assistant 2">Assistant 2</option>
                    </select>

                    <label for="Client">Client:</label>
                    <input name="Client" id="client" required />

                    <label for="ContainerNumber">Container No.:</label>
                    <input name="ContainerNumber" id="container" required />

                    <label for="Destination">Destination:</label>
                    <input name="Destination" id="destination" required />

                    <label for="DepartureTime">Departure Date and Time:</label>
                    <input name="DepartureTime" type="datetime-local" id="departure-time" required />

                    <label for="EstimatedArrivalTime">Estimated Arrival Date and Time:</label>
                    <input name="EstimatedArrivalTime" type="datetime-local" id="arrival-time" required />

                    <button type="submit">Assign Trip</button>
                </form>
            </div>
        </div>

        <!-- Damage Report Modal -->
        <div id="damage-modal" class="modal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h2>Report Damage for <span id="vehicle-name"></span></h2>
                <form id="damage-form" action="process_damage.php" method="post" enctype="multipart/form-data">
                    <label for="damage-type">Type of Damage:</label>
                    <select id="damage-type" name="damage-type">
                        <option value="engine">Engine</option>
                        <option value="tires">Tires</option>
                        <option value="body">Body</option>
                    </select>

                    <label for="damage-description">Description:</label>
                    <textarea id="damage-description" name="damage-description" required></textarea>

                    <label for="damage-photo">Upload Photo:</label>
                    <input type="file" id="damage-photo" name="damage-photo">

                    <label for="driver_damage">Driver in Charge:</label>
                    <select id="driver_damage" name="driver_damage">
                        <option value="driver1">Driver 1</option>
                        <option value="driver2">Driver 2</option>
                    </select>

                    <button type="submit">Submit Damage Report</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const minDate = "2025-03-28T00:00";
            
            document.getElementById('departure-time').setAttribute('min', minDate);
            document.getElementById('arrival-time').setAttribute('min', minDate);

            const counters = document.querySelectorAll('.count');
            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.innerText;
                    const count = +counter.innerText;
                    const increment = target / 200;

                    if (count < target) {
                        counter.innerText = Math.ceil(count + increment);
                        setTimeout(updateCount, 1);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCount();
            });

            // Modal functionality
            const tripModal = document.getElementById('assign-trip-modal');
            const damageModal = document.getElementById('damage-modal');
            const tripButton = document.getElementById('trip-assign-button');
            const closeBtns = document.querySelectorAll('.close-btn');
            const damageButtons = document.querySelectorAll('.damage-report-button');
            const vehicleNameSpan = document.getElementById('vehicle-name');

            tripButton.addEventListener('click', function() {
                tripModal.style.display = 'block';
            });

            damageButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const vehicle = this.getAttribute('data-vehicle');
                    vehicleNameSpan.textContent = vehicle;
                    damageModal.style.display = 'block';
                });
            });

            closeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    tripModal.style.display = 'none';
                    damageModal.style.display = 'none';
                });
            });

            window.addEventListener('click', function(event) {
                if (event.target == tripModal) {
                    tripModal.style.display = 'none';
                }
                if (event.target == damageModal) {
                    damageModal.style.display = 'none';
                }
            });
        });
        </body>
        </html>