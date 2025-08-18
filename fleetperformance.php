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
    <link rel="stylesheet" href="include/css/fleetperformance.css">
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
        <a href="fleetperformance.php">Fleet Performance Analytics</a>
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

 <h3 class="title"><i class="fa-solid fa-chart-line"></i>Fleet Performance Analytics</h3>
<div class="dashboard-section">
    <!-- Operational Analytics -->
    <div class="card-large">
        <h3>Operational Analytics</h3>
        <div id="operational"></div>
    </div>

    <!-- Cost Trends -->
    <div class="card-large">
        <h3>Cost Trends</h3>
        <div id="costtrendchart"></div>
    </div>

    <!-- Trip Duration -->
    <div class="card-large">
        <h3>Trip Duration</h3>
        <div id="tripduration"></div>
    </div>
</div>

<div class="dashboard-section">
    <!-- Maintenance Frequency -->
    <div class="card-large">
        <h3>Maintenance Frequency</h3>
        <div id="maintenance"></div>
    </div>

    <!-- Trip Number -->
    <div class="card-large">
        <h3>Number of Trips</h3>
        <div id="tripnumber"></div>
    </div>
</div>

<script>

      function updateDateTime() {
        const now = new Date();
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, options);
        
        document.getElementById('current-time').textContent = now.toLocaleTimeString();
    }

    // Update immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);
    
// Replace the existing cost trends chart initialization with this code

// Global variables for the cost trends functionality
let costTrendsChart = null;
let currentView = 'current'; // 'current', 'monthly', 'yearly'

// Initialize cost trends chart
function initializeCostTrendsChart() {
    loadCostTrendsData();
    
    // Add view toggle buttons
    addCostTrendsControls();
}

function addCostTrendsControls() {
    const costTrendsCard = document.querySelector('#costtrendchart').closest('.card-large');
    const header = costTrendsCard.querySelector('h3');
    
    // Create control buttons
    const controlsDiv = document.createElement('div');
    controlsDiv.style.cssText = `
        display: flex; 
        gap: 10px; 
        margin: 10px 0; 
        flex-wrap: wrap;
    `;
    
    const buttonStyle = `
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: #f8f9fa;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
    `;
    
    const activeButtonStyle = `
        background: #B82132;
        color: white;
        border-color: #B82132;
    `;
    
    const buttons = [
        { id: 'current', text: 'Current Year' },
        { id: 'monthly', text: 'Monthly View' },
        { id: 'yearly', text: 'Yearly View' }
    ];
    
    buttons.forEach(btn => {
        const button = document.createElement('button');
        button.textContent = btn.text;
        button.style.cssText = buttonStyle;
        button.onclick = () => switchCostView(btn.id);
        button.id = `btn-${btn.id}`;
        controlsDiv.appendChild(button);
    });
    
    // Insert controls after the header
    header.insertAdjacentElement('afterend', controlsDiv);
    
    // Set active button
    document.getElementById('btn-current').style.cssText = buttonStyle + activeButtonStyle;
}

function switchCostView(view) {
    currentView = view;
    
    // Update active button
    document.querySelectorAll('[id^="btn-"]').forEach(btn => {
        btn.style.cssText = `
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        `;
    });
    
    document.getElementById(`btn-${view}`).style.cssText = `
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: #B82132;
        color: white;
        border-color: #B82132;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
    `;
    
    // Load appropriate data
    switch(view) {
        case 'current':
            loadCostTrendsData();
            break;
        case 'monthly':
            loadMonthlyTrendsData();
            break;
        case 'yearly':
            loadYearlyTrendsData();
            break;
    }
}

function loadCostTrendsData() {
    fetch('include/handlers/analytics_handler.php?action=cost_trends')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderCostTrendsChart(data, 'donut', 'Cost Distribution - Current Year');
            } else {
                console.error('Error loading cost trends:', data.error);
                // Fallback to original static data if there's an error
                renderDefaultCostChart();
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            renderDefaultCostChart();
        });
}

function loadMonthlyTrendsData() {
    fetch('include/handlers/analytics_handler.php?action=monthly_trends')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderMonthlyChart(data);
            } else {
                console.error('Error loading monthly trends:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

function loadYearlyTrendsData() {
    fetch('include/handlers/analytics_handler.php?action=yearly_trends')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderYearlyChart(data);
            } else {
                console.error('Error loading yearly trends:', data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
        });
}

function renderCostTrendsChart(data, chartType = 'donut', title = 'Cost Trends') {
    if(costTrendsChart) {
        costTrendsChart.destroy();
    }
    
    const options = {
        series: data.data,
        chart: {
            width: 580,
            type: chartType,
        },
        labels: data.labels,
        plotOptions: {
            pie: {
                startAngle: -90,
                endAngle: 270
            }
        },
        dataLabels: {
            enabled: false
        },
        fill: {
            type: 'gradient',
        },
        legend: {
            formatter: function (val, opts) {
                let series = opts.w.globals.series;
                let total = series.reduce((a, b) => a + b, 0);
                let value = series[opts.seriesIndex];
                let percent = ((value / total) * 100).toFixed(1);
                return `${val} - ${percent}% (₱${value.toLocaleString()})`;
            }
        },
        tooltip: {
            y: {
                formatter: function (value, opts) {
                    let series = opts.w.globals.series;
                    let total = series.reduce((a, b) => a + b, 0);
                    let percent = ((value / total) * 100).toFixed(1);
                    return `₱${value.toLocaleString()} (${percent}%)`;
                }
            }
        },
        title: {
            text: title
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };
    
    costTrendsChart = new ApexCharts(document.querySelector("#costtrendchart"), options);
    costTrendsChart.render();
}

function renderMonthlyChart(data) {
    if(costTrendsChart) {
        costTrendsChart.destroy();
    }
    
    // Prepare data for line chart
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                   'July', 'August', 'September', 'October', 'November', 'December'];
    
    const series = [];
    const colors = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'];
    
    data.expenseTypes.forEach((type, index) => {
        const monthlyAmounts = months.map(month => {
            return data.monthlyData[month] && data.monthlyData[month][type] 
                ? data.monthlyData[month][type] 
                : 0;
        });
        
        series.push({
            name: ucfirst(type),
            type: 'line',
            data: monthlyAmounts
        });
    });
    
    const options = {
        series: series,
        chart: {
            height: 350,
            type: 'line',
            stacked: false,
            width: 580,
        },
        stroke: {
            width: [2, 2, 2, 2, 2],
            curve: 'smooth'
        },
        plotOptions: {
            bar: {
                columnWidth: '50%'
            }
        },
        fill: {
            opacity: [0.85, 0.25, 1],
            gradient: {
                inverseColors: false,
                shade: 'light',
                type: "vertical",
                opacityFrom: 0.85,
                opacityTo: 0.55,
                stops: [0, 100, 100, 100]
            }
        },
        labels: months,
        markers: {
            size: 0
        },
        xaxis: {
            type: 'category'
        },
        yaxis: {
            title: {
                text: 'Amount (₱)',
            },
            labels: {
                formatter: function (val) {
                    return '₱' + val.toLocaleString();
                }
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y) {
                    if (typeof y !== "undefined") {
                        return "₱" + y.toLocaleString();
                    }
                    return y;
                }
            }
        },
        title: {
            text: 'Monthly Cost Trends - ' + new Date().getFullYear()
        },
        colors: colors
    };
    
    costTrendsChart = new ApexCharts(document.querySelector("#costtrendchart"), options);
    costTrendsChart.render();
}

function renderYearlyChart(data) {
    if(costTrendsChart) {
        costTrendsChart.destroy();
    }
    
    // Get all years from the data
    const years = Object.keys(data.yearlyData).sort();
    
    const series = [];
    const colors = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'];
    
    data.expenseTypes.forEach((type, index) => {
        const yearlyAmounts = years.map(year => {
            return data.yearlyData[year] && data.yearlyData[year][type] 
                ? data.yearlyData[year][type] 
                : 0;
        });
        
        series.push({
            name: ucfirst(type),
            type: 'column',
            data: yearlyAmounts
        });
    });
    
    const options = {
        series: series,
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            width: 580,
        },
        plotOptions: {
            bar: {
                horizontal: false,
                dataLabels: {
                    total: {
                        enabled: true,
                        style: {
                            fontSize: '13px',
                            fontWeight: 900
                        }
                    }
                }
            },
        },
        stroke: {
            width: 1,
            colors: ['#fff']
        },
        title: {
            text: 'Yearly Cost Trends'
        },
        xaxis: {
            categories: years,
            labels: {
                formatter: function (val) {
                    return val;
                }
            }
        },
        yaxis: {
            title: {
                text: 'Amount (₱)'
            },
            labels: {
                formatter: function (val) {
                    return '₱' + val.toLocaleString();
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return '₱' + val.toLocaleString();
                }
            }
        },
        fill: {
            opacity: 1
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            offsetX: 40
        },
        colors: colors
    };
    
    costTrendsChart = new ApexCharts(document.querySelector("#costtrendchart"), options);
    costTrendsChart.render();
}

function renderDefaultCostChart() {
    // Fallback to your original static chart if data loading fails
    const options = {
        series: [44, 55, 41, 17, 15],
        chart: {
            width: 580,
            type: 'donut',
        },
        labels: ['Fuel', 'Toll Gate', 'Food', 'Emergency', 'Others'],
        plotOptions: {
            pie: {
                startAngle: -90,
                endAngle: 270
            }
        },
        dataLabels: {
            enabled: false
        },
        fill: {
            type: 'gradient',
        },
        legend: {
            formatter: function (val, opts) {
                let series = opts.w.globals.series;
                let total = series.reduce((a, b) => a + b, 0);
                let value = series[opts.seriesIndex];
                let percent = ((value / total) * 100).toFixed(1);
                return `${val} - ${percent}%`;
            }
        },
        tooltip: {
            y: {
                formatter: function (value, opts) {
                    let series = opts.w.globals.series;
                    let total = series.reduce((a, b) => a + b, 0);
                    let percent = ((value / total) * 100).toFixed(1);
                    return `${percent}%`;
                }
            }
        },
        title: {
            text: 'Cost Trends (Demo Data)'
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };
    
    if(costTrendsChart) {
        costTrendsChart.destroy();
    }
    
    costTrendsChart = new ApexCharts(document.querySelector("#costtrendchart"), options);
    costTrendsChart.render();
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure ApexCharts is ready
    setTimeout(() => {
        initializeCostTrendsChart();
    }, 500);
});

    var options2 = {
        series: [
            {
                data: [
                    {
                        x: 'Trip to Jerusalem',
                        y: [
                            new Date('2019-02-27').getTime(),
                            new Date('2019-03-04').getTime()
                        ],
                        fillColor: '#008FFB'
                    },
                    {
                        x: 'To Batangas',
                        y: [
                            new Date('2019-03-04').getTime(),
                            new Date('2019-03-08').getTime()
                        ],
                        fillColor: '#00E396'
                    },
                    {
                        x: 'To Pasay ',
                        y: [
                            new Date('2019-03-07').getTime(),
                            new Date('2019-03-10').getTime()
                        ],
                        fillColor: '#775DD0'
                    },
                    {
                        x: 'To Paranaque',
                        y: [
                            new Date('2019-03-08').getTime(),
                            new Date('2019-03-12').getTime()
                        ],
                        fillColor: '#FEB019'
                    },
                    {
                        x: 'To El Nido',
                        y: [
                            new Date('2019-03-12').getTime(),
                            new Date('2019-03-17').getTime()
                        ],
                        fillColor: '#FF4560'
                    }
                ]
            }
        ],
        chart: {
            height: 350,
            type: 'rangeBar',
            width: 600
        },
        plotOptions: {
            bar: {
                horizontal: true,
                distributed: true,
                dataLabels: {
                    hideOverflowingLabels: false
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                var label = opts.w.globals.labels[opts.dataPointIndex]
                var a = moment(val[0])
                var b = moment(val[1])
                var diff = b.diff(a, 'days')
                return label + ': ' + diff + (diff > 1 ? ' days' : ' day')
            },
            style: {
                colors: ['#f3f4f5', '#fff']
            }
        },
        xaxis: {
            type: 'datetime'
        },
        yaxis: {
            show: false
        },
        grid: {
            row: {
                colors: ['#f3f4f5', '#fff'],
                opacity: 1
            }
        }
    };

    var tripduration = new ApexCharts(document.querySelector("#tripduration"), options2);
    tripduration.render();

    var options3 = {
        series: [{
            name: "Number of Trips",
            data: [23, 45, 56, 67, 89, 23, 45]
        }],
        chart: {
            type: 'area',
            height: 350,
            zoom: {
                enabled: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'straight'
        },
      
       
        labels: ['2020-01-01', '2020-02-01', '2020-03-01', '2020-04-01', '2020-05-01', '2020-06-01', '2020-07-01'],
        xaxis: {
            type: 'datetime',
        },
        yaxis: {
            opposite: true
        },
        legend: {
            horizontalAlign: 'left'
        }
    };

    var tripnumber = new ApexCharts(document.querySelector("#tripnumber"), options3);
    tripnumber.render();

    var options4 = {
        series: [{
            name: 'TEAM A',
            type: 'column',
            data: [23, 11, 22, 27, 13, 22, 37, 21, 44, 22, 30]
        }, {
            name: 'TEAM B',
            type: 'area',
            data: [44, 55, 41, 67, 22, 43, 21, 41, 56, 27, 43]
        }, {
            name: 'TEAM C',
            type: 'line',
            data: [30, 25, 36, 30, 45, 35, 64, 52, 59, 36, 39]
        }],
        chart: {
            height: 350,
            type: 'line',
            stacked: false,
            width:1200,
        },
        stroke: {
            width: [0, 2, 5],
            curve: 'smooth'
        },
        fill: {
            opacity: [0.85, 0.25, 1],
            gradient: {
                inverseColors: false,
                shade: 'light',
                type: "vertical",
                opacityFrom: 0.85,
                opacityTo: 0.55,
                stops: [0, 100, 100, 100]
            }
        },
        labels: ['01/01/2003', '02/01/2003', '03/01/2003', '04/01/2003', '05/01/2003', '06/01/2003', '07/01/2003',
            '08/01/2003', '09/01/2003', '10/01/2003', '11/01/2003'
        ],
        markers: {
            size: 0
        },
        xaxis: {
            type: 'datetime'
        },
        yaxis: {
            title: {
                text: 'Points',
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y) {
                    if (typeof y !== "undefined") {
                        return y.toFixed(0) + " points";
                    }
                    return y;
                }
            }
        }
    };

    var operational = new ApexCharts(document.querySelector("#operational"), options4);
    operational.render();

    var options = {
          series: [{
          name: 'Truck 1',
          data: [44, 55, 41, 37, 22, 43, 21]
        }, {
          name: 'Truck 2',
          data: [53, 32, 33, 52, 13, 43, 32]
        }, {
          name: 'Truck 3',
          data: [12, 17, 11, 9, 15, 11, 20]
        }, {
          name: 'Truck 4',
          data: [9, 7, 5, 8, 6, 9, 4]
        }, {
          name: 'Truck 5',
          data: [25, 12, 19, 32, 25, 24, 10]
        }],
          chart: {
          type: 'bar',
          height: 350,
          stacked: true,
        },
        plotOptions: {
          bar: {
            horizontal: true,
            dataLabels: {
              total: {
                enabled: true,
                offsetX: 0,
                style: {
                  fontSize: '13px',
                  fontWeight: 900
                }
              }
            }
          },
        },
        stroke: {
          width: 1,
          colors: ['#fff']
        },
    
        xaxis: {
          categories: [2019,2020, 2021, 2022, 2023, 2024, 2025],
          labels: {
            formatter: function (val) {
              return val 
            }
          }
        },
        yaxis: {
          title: {
            text: undefined
          },
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val 
            }
          }
        },
        fill: {
          opacity: 1
        },
        legend: {
          position: 'top',
          horizontalAlign: 'left',
          offsetX: 40
        }
        };

        var maintenance = new ApexCharts(document.querySelector("#maintenance"), options);
        maintenance.render();


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
    
    this.setupNavigationInterception();
  },
  
  show(title = 'Processing Request', message = 'Please wait while we complete this action...') {
    this.titleEl.textContent = title;
    this.messageEl.textContent = message;
    
    // Start the sequence with longer delays
    this.loadingEl.style.display = 'flex';
    setTimeout(() => {
      this.loadingEl.classList.add('active');
    }, 50);
  },
  
  hide() {
    // Longer fade out
    this.loadingEl.classList.remove('active');
    setTimeout(() => {
      this.loadingEl.style.display = 'none';
    }, 800); 
  },
  
  updateProgress(percent) {
    this.progressBar.style.width = `${percent}%`;
    this.progressText.textContent = `${percent}%`;
  },
  
   setupNavigationInterception() {
  document.addEventListener('click', (e) => {
    // Skip if click is inside SweetAlert modal
    if (e.target.closest('.swal2-container, .swal2-popup, .swal2-modal')) {
      return;
    }
    
    // Skip if click is on any modal element
    if (e.target.closest('.modal, .modal-content')) {
      return;
    }
    
    const link = e.target.closest('a');
    if (link && !link.hasAttribute('data-no-loading') && 
        link.href && !link.href.startsWith('javascript:') &&
        !link.href.startsWith('#')) {
      e.preventDefault();
      
      const loading = this.startAction(
        'Loading Page', 
        `Preparing ${link.textContent.trim()}...`
      );
      
      let progress = 0;
      const progressInterval = setInterval(() => {
        progress += Math.random() * 40; 
        if (progress >= 90) clearInterval(progressInterval);
        loading.updateProgress(Math.min(progress, 100));
      }, 300); 
      
      const minLoadTime = 2000;
      const startTime = Date.now();
      
      setTimeout(() => {
        window.location.href = link.href;
      }, minLoadTime);
    }
  });

  document.addEventListener('submit', (e) => {
    // Skip if form is inside SweetAlert or modal
    if (e.target.closest('.swal2-container, .swal2-popup, .modal')) {
      return;
    }
    
    const loading = this.startAction(
      'Submitting Form', 
      'Processing your data...'
    );
    
    setTimeout(() => {
      loading.complete();
    }, 1500);
  });

    
    if (window.jQuery) {
      $(document).ajaxStart(() => {
        this.show('Processing', 'Communicating with server...');
      }).ajaxComplete(() => {
        setTimeout(() => {
          this.hide();
        }, 1000);
      });
    }
  }
  
  
  startAction(actionName, message) {
    this.show(actionName, message);
    return {
      updateProgress: (percent) => this.updateProgress(percent),
      updateMessage: (message) => {
        this.messageEl.textContent = message;
        this.messageEl.style.opacity = 0;
        setTimeout(() => {
          this.messageEl.style.opacity = 1;
          this.messageEl.style.transition = 'opacity 0.5s ease';
        }, 50);
      },
      complete: () => {

        this.updateProgress(100);
        this.updateMessage('Done!');
        setTimeout(() => this.hide(), 800);
      }
    };
  }
};

document.addEventListener('DOMContentLoaded', () => {
  AdminLoading.init();
  
  // Add smooth transition to the GIF
  const loadingGif = document.querySelector('.loading-gif');
  if (loadingGif) {
    loadingGif.style.transition = 'opacity 0.7s ease 0.3s';
  }
});
</script>
<footer class="site-footer">

    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Mansar Logistics. All rights reserved.</p>
    </div>
</footer>
</body>
</html>
