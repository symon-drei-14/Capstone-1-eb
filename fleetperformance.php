<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Not logged in, redirect to login page
    header("Location: login.php");
    exit();
}
// User is logged in, continue with the page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/fleetperformance.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
</head>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 80px;
    background-color: rgb(241, 241, 244);
}

.dashboard-section {
    display: flex;
    padding: 20px;
    flex-wrap: wrap;  /* Allow wrapping for smaller screen sizes */
}

.card-large {
    flex: 1;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    margin: 10px;
}

.card-large h3 {
    margin-bottom: 15px;
}
</style>

<body>
<header class="header">
        <div class="logo-container">
            <img src="include/img/logo.png" alt="Company Logo" class="logo">
            <img src="include/img/mansar.png" alt="Company Name" class="company">
        </div>

    

        <div class="profile">
            <i class="icon">✉</i>
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
  var options = {
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
        text: 'Cost Trends'
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


    var costtrendchart = new ApexCharts(document.querySelector("#costtrendchart"), options);
    costtrendchart.render();

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

</body>
</html>
