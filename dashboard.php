<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<style>
    .on-route {
    background-color:rgba(69, 137, 63, 0.13);
    color: rgb(27, 123, 19);
}


.error {
    background-color:rgba(255, 0, 0, 0.2);
    color: rgb(209, 25, 25);
}


.late {
    background-color:rgba(255, 202, 8, 0.18);
    color: rgb(187, 148, 7);
}


.deviated {
    background-color:rgba(230, 121, 49, 0.19);
    color: rgb(230, 121, 49);
}
    </style>
<body>
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
    <a asp-area="" asp-controller="Home" asp-action="TripLogs">Trip Management</a>
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

<div class="dashboard-grid">
    <div class="grid-item card statistic on-route">
        <div class="content">
            <p>On Going Deliveries</p>
            <h2>42,420</h2>
            <p class="trend">+18.2% than last week</p>
        </div>
        <div class="icon-container">
            <i class="fa fa-truck"></i>
        </div>
    </div>
    <div class="grid-item card statistic error">
        <div class="content2">
            <p>Damaged Vehicles</p>
            <h2>890</h2>
            <p class="trend">-8.7% than last week</p>
        </div>
        <div class="icon-container2">
            <i class="fa fa-wrench"></i>
        </div>
    </div>
    <div class="grid-item card statistic late">
        <div class="content3">
            <p>Late Deliveries</p>
            <h2>13,890</h2>
            <p class="trend">-2.5% than last week</p>
        </div>
        <div class="icon-container3">
            <i class="fa fa-hourglass-end"></i>
        </div>
    </div>
    <div class="grid-item card statistic deviated">
        <div class="content4">
            <p>Unchecked Vehicles</p>
            <h2>27,711</h2>
            <p class="trend">+4.3% than last week</p>
        </div>
        <div class="icon-container4">
            <i class="fa fa-cogs"></i>
        </div>
    </div>
</div>


<section class="weekly-trip-schedule">
    <div class="card-large">
        <h3>Weekly Schedule</h3>
        <div class="week-schedule">
            <div class="day">
                <h4>Monday</h4>
                <ul>
                    <li>Trip 1: 10:00 AM - Delivery sa bahay ni kuya</li>
                    <li>Trip 2: 02:00 PM - Delivery sa toro house</li>
                </ul>
            </div>
            <div class="day">
                <h4>Tuesday</h4>
                <ul>
                    <li>Trip 1: 09:00 AM - Delivery fee</li>
                    <li>Trip 2: 01:00 PM - Delivery sa fine dining restaurant</li>
                </ul>
            </div>
            <div class="day">
                <h4>Wednesday</h4>
                <ul>
                    <li>Trip 1: 11:00 AM - Where to the house</li>
                </ul>
            </div>
            <div class="day">
                <h4>Thursday</h4>
                <ul>
                    <li>Trip 1: 12:00 PM - Birthday ni Gemma</li>
                </ul>
            </div>
            <div class="day">
                <h4>Friday</h4>
                <ul>
                    <li>Trip 1: 08:00 AM - Tripping langs</li>
                    <li>Trip 2: 04:00 AM - Night Rides</li>
                </ul>
            </div>
            <div class="day">
                <h4>Saturday</h4>
                <ul>
                    <li>Trip 1: 10:00 AM - Trip to busan</li>
                </ul>
            </div>
            <div class="day">
                <h4>Sunday</h4>
                <ul>
                    <li>Trip 1: 09:00 AM - Betty Go Belmonte</li>
                </ul>
            </div>
            <a asp-area="" asp-controller="Home" asp-action="FleetManagement">
                <button>View More</button>
            </a>
        </div>
    </div>
</section>


<div class="dashboard-section">
    <div class="card-large">
        <h3>Shipment Statistics</h3>
        <p>Total deliveries: 23.8k</p>
        <div id="shipmentStatisticsChart"></div> <!-- Added for line chart -->
    </div>
    <div class="card-small">
        <h3>Driving Drivers</h3>
        <div class="performance">
            <i class="fa fa-user icon-bg"></i>
            <p>Enter Name Here - Destination: Los Angeles</p>
        </div>
        <div class="performance">
            <i class="fa fa-user icon-bg"></i>
            <p>Enter Name Here - Destination: Los Angeles</p>
        </div>
        <div class="performance">
            <i class="fa fa-user icon-bg"></i>
            <p>Enter Name Here - Destination: Los Angeles</p>
        </div>
        <div class="performance">
            <i class="fa fa-user icon-bg"></i>
            <p>Enter Name Here - Destination: Los Angeles</p>
        </div>
        <div class="performance">
            <i class="fa fa-user icon-bg"></i>
            <p>Enter Name Here - Destination: Los Angeles</p>
        </div>
        <div class="performance">
            <i class="fa fa-user icon-bg"></i>
            <p>Enter Name Here - Destination: Los Angeles</p>
        </div>
    </div>
</div>


<div class="card-large">
    <div class="table-container">
        <h3>Ongoing Vehicles</h3>
        <table>
            <tr>
                <th></th>
                <th> Vehicle ID</th>
                <th>Driver</th>
                <th>Starting Route</th>
                <th>Delivery Address</th>
                <th>Current Progress</th>
            </tr>
            <tr>
                <td><i class="fa fa-automobile icon-bg2"></i></td>
                <td>V001</td>
                <td>Si Driver</td>
                <td>New York</td>
                <td>Los Angeles</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 50%;">50%</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td><i class="fa fa-automobile icon-bg2"></i></td>
                <td>V002</td>
                <td>Si Driver</td>
                <td>Chicago</td>
                <td>Houston</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 70%;">70%</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td><i class="fa fa-automobile icon-bg2"></i></td>
                <td>V003</td>
                <td>Si Driver</td>
                <td>San Francisco</td>
                <td>Seattle</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 30%;">30%</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>


<script>
    // Shipment Statistics Line Chart
    var shipmentOptions = {
        series: [{
            name: 'Income',
            type: 'column',
            data: [1.4, 2, 2.5, 1.5, 2.5, 2.8, 3.8, 4.6]
        }, {
            name: 'Cashflow',
            type: 'column',
            data: [1.1, 3, 3.1, 4, 4.1, 4.9, 6.5, 8.5]
        }, {
            name: 'Revenue',
            type: 'line',
            data: [20, 29, 37, 36, 44, 45, 50, 58]
        }],
        chart: {
            height: 350,
            type: 'line',
            stacked: false
        },
        xaxis: {
            categories: [2009, 2010, 2011, 2012, 2013, 2014, 2015, 2016],
        },
        yaxis: [{
            seriesName: 'Income',
            axisTicks: { show: true },
            axisBorder: { show: true, color: '#008FFB' },
            labels: { style: { colors: '#008FFB' } },
            title: { text: "Income (thousand crores)", style: { color: '#008FFB' } }
        }, {
            seriesName: 'Cashflow',
            opposite: true,
            axisTicks: { show: true },
            axisBorder: { show: true, color: '#00E396' },
            labels: { style: { colors: '#00E396' } },
            title: { text: "Operating Cashflow (thousand crores)", style: { color: '#00E396' } }
        }, {
            seriesName: 'Revenue',
            opposite: true,
            axisTicks: { show: true },
            axisBorder: { show: true, color: '#FEB019' },
            labels: { style: { colors: '#FEB019' } },
            title: { text: "Revenue (thousand crores)", style: { color: '#FEB019' } }
        }],
        tooltip: { fixed: { enabled: true, position: 'topLeft', offsetY: 30, offsetX: 60 } },
        legend: { horizontalAlign: 'left', offsetX: 40 }
    };


    var shipmentChart = new ApexCharts(document.querySelector("#shipmentStatisticsChart"), shipmentOptions);
    shipmentChart.render();


    var vehicleOptions = {
        series: [{
            name: 'On the way',
            data: [39.7]
        }, {
            name: 'Unloading',
            data: [28.3]
        }, {
            name: 'Loading',
            data: [17.4]
        }, {
            name: 'Waiting',
            data: [14.6]
        }],
        chart: {
            height: 350,
            type: 'pie'
        },
        labels: ['On the way', 'Unloading', 'Loading', 'Waiting']
    };
</script>
</body>
</html>
