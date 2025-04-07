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
    <title>Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="include/tracking.css">
    <link rel="stylesheet" href="include/sidenav.css">
    <link rel="stylesheet" href="include/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>


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
        <a href="settings.php">Settings</a>
    </div>
    <div class="sidebar-item">
        <i class="icon2">🚪</i>
        <a href="include/handlers/logout.php">Logout</a>
    </div>
</div>

    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col">
                <h2>Tracking</h2>
            </div>
        </div>

        <div class="row">
            <div class="col-md-9">
                <div class="map-container">
                    <div id="map"></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Drivers</h5>
                    </div>
                    <div class="card-body" style="max-height: 555px; overflow-y: auto;">
                        <div id="drivers-list">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading drivers...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <script>
        let map;
        let markers = {};
        let updateTimer;

        function initMap() {
            const mapElement = document.getElementById('map');
            if (!mapElement) return;

            map = L.map('map').setView([14.7874696, 121.0040994], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            fetchDriverData();
            updateTimer = setInterval(fetchDriverData, 10000); 

            document.getElementById('refresh-btn').addEventListener('click', function() {
                fetchDriverData();
            });
        }

        function fetchDriverData() {
            fetch('include/config/firebase.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.drivers) {
                        updateMap(data.drivers);
                        updateDriversList(data.drivers);
                    } else {
                        document.getElementById('drivers-list').innerHTML = `
                            <div class="alert alert-warning">
                                No driver data found.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('drivers-list').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading data: ${error.message}
                        </div>
                    `;
                });
        }

        function updateMap(data) {
            if (!data || !map) return;
            
            const bounds = [];
            const driversFound = new Set();

            Object.entries(data).forEach(([driverId, driverData]) => {
                if (!driverData.current_location) return;
                
                driversFound.add(driverId);
                
                const location = driverData.current_location;
                const position = [
                    parseFloat(location.latitude),
                    parseFloat(location.longitude)
                ];

                if (isNaN(position[0]) || isNaN(position[1])) return;

                bounds.push(position);

                const driverName = location.driver_name || driverId;

                const popupContent = `
                    <div style="width: 200px; padding: 10px;">
                        <h6>${driverName}</h6>
                        <p>Assigned Truck: ${location.truck_id || 'N/A'}</p>
                        <p>Last Updated: ${location.timestamp || 'Unknown'}</p>
                        <p>Coordinates: ${position[0].toFixed(6)}, ${position[1].toFixed(6)}</p>
                    </div>
                `;

                if (markers[driverId]) {
                    markers[driverId].setLatLng(position);
                    markers[driverId].getPopup().setContent(popupContent);
                } else {
                    const truckIcon = L.divIcon({
                        html: '🚚',
                        className: 'driver-marker-icon',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });

                    const marker = L.marker(position, {
                        title: driverName,
                        icon: truckIcon
                    }).addTo(map);
                    marker.bindPopup(popupContent);
                    markers[driverId] = marker;
                }
            });

            Object.keys(markers).forEach(driverId => {
                if (!driversFound.has(driverId)) {
                    map.removeLayer(markers[driverId]);
                    delete markers[driverId];
                }
            });

            if (bounds.length > 0) {
                map.fitBounds(bounds);
                if (map.getZoom() > 16) {
                    map.setZoom(16);
                }
            }

            setTimeout(() => {
                map.invalidateSize();
            }, 100);
        }

        function updateDriversList(data) {
            if (!data) {
                document.getElementById('drivers-list').innerHTML = '<div class="alert alert-warning">No drivers found</div>';
                return;
            }
            
            let html = '';
            
            Object.entries(data).forEach(([driverId, driverData]) => {
                if (!driverData.current_location) return;
                
                const location = driverData.current_location;
                const timestamp = location.timestamp || 'Unknown';
                const truckId = location.truck_id || 'N/A';
                const driverName = location.driver_name || driverId;
                
                html += `
                    <div class="driver-info" onclick="centerOnDriver('${driverId}')">
                        <div class="fw-bold">${driverName}</div>
                        <div class="small mb-1">Assigned Truck: ${truckId}</div>
                        <div class="small text-muted">Last update: ${timestamp}</div>
                    </div>
                `;
            });
            
            document.getElementById('drivers-list').innerHTML = html || '<div class="alert alert-warning">No drivers found</div>';
        }

        function centerOnDriver(driverId) {
            const marker = markers[driverId];
            if (marker) {
                map.setView(marker.getLatLng(), 16);
                map.closePopup();
                marker.openPopup();

                document.querySelectorAll('.driver-info').forEach(el => {
                    el.style.backgroundColor = '';
                });

                document.querySelectorAll('.driver-info').forEach(el => {
                    if (el.textContent.includes(driverId)) {
                        el.style.backgroundColor = '#e2e6ea';
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initMap, 500);
            window.addEventListener('resize', function() {
                if (map) {
                    map.invalidateSize();
                }
            });
        });
    </script>
</body>
</html>