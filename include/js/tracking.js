let map;
let markers = {};
let updateTimer;
let allDrivers = {};
let routeLayer = null;
let routeMarkers = [];
let currentSearchTerm = '';
let currentFilter = 'All';

const sampleData = {
    "drivers": {
        "GpVwz7fj6EoZquvkdFrN": {
            "assigned_truck_id": 1,
            "driver_id": "GpVwz7fj6EoZquvkdFrN",
            "email": "charles@gmail.com",
            "location": {
                "last_updated": 1743941547705,
                "latitude": 14.7874657,
                "longitude": 121.0040989
            },
            "name": "Charles Cahilig",
            "assigned_trip_id": "trip_1743941164224",
            "destination": "Sample Destination A", 
            "status": {
                "last_status_update": 1743941547705,
                "status": "online"
            }
        },
        "m5ugaxPnkaXIPYIkOePE": {
            "assigned_truck_id": 2,
            "driver_id": "m5ugaxPnkaXIPYIkOePE",
            "email": "Andrew@gmail.com",
            "location": {
                "last_updated": 1743931958718,
                "latitude": 14.7874641,
                "longitude": 121.0040972
            },
            "name": "Andrew cahilig",
            "assigned_trip_id": "trip_1743931815461",
            "destination": "Sample Destination B",
            "status": {
                "last_status_update": 1743931958718,
                "status": "offline"
            }
        }
    }
};

async function getDriverHistory(driverId, driverName) {
    try {
        const response = await fetch('include/handlers/trip_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_driver_history',
                driver_id: driverId,
                driver_name: driverName
            }),
        });

        const data = await response.json();
        console.log('Driver history response:', data);

        if (data.success) {
            return data.trips || [];
        }
    } catch (error) {
        console.error('Error fetching driver history:', error);
    }
    return [];
}

async function getTripRoute(tripId) {
    try {
        const response = await fetch('include/handlers/trip_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_trip_route',
                trip_id: tripId
            }),
        });

        const data = await response.json();
        console.log('Trip route response:', data);

        if (data.success && data.route) {
            return data.route;
        }
    } catch (error) {
        console.error('Error fetching trip route:', error);
    }
    return null;
}

function clearRoute() {
    if (routeLayer) {
        map.removeLayer(routeLayer);
        routeLayer = null;
    }

    routeMarkers.forEach(marker => {
        map.removeLayer(marker);
    });
    routeMarkers = [];
}

function displayRoute(routeData) {
    clearRoute();
    
    if (!routeData || !routeData.coordinates || routeData.coordinates.length === 0) {
        alert('No route data available for this trip');
        return;
    }
    
    const coordinates = routeData.coordinates;

    const routeCoords = coordinates.map(coord => [coord.latitude, coord.longitude]);
    routeLayer = L.polyline(routeCoords, {
        color: '#007bff',
        weight: 4,
        opacity: 0.8
    }).addTo(map);

    if (coordinates.length > 0) {
        const startMarker = L.marker([coordinates[0].latitude, coordinates[0].longitude], {
            icon: L.divIcon({
                className: 'route-marker start-marker',
                html: '<i class="fas fa-play" style="color: #28a745; font-size: 16px;"></i>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).addTo(map);
        
        startMarker.bindPopup(`
            <div style="font-family: Arial, sans-serif;">
                <strong>Trip Start</strong><br>
                Time: ${new Date(coordinates[0].timestamp).toLocaleString()}<br>
                Location: ${coordinates[0].latitude.toFixed(6)}, ${coordinates[0].longitude.toFixed(6)}
            </div>
        `);
        
        routeMarkers.push(startMarker);
    }

    if (coordinates.length > 1) {
        const endIndex = coordinates.length - 1;
        const endMarker = L.marker([coordinates[endIndex].latitude, coordinates[endIndex].longitude], {
            icon: L.divIcon({
                className: 'route-marker end-marker',
                html: '<i class="fas fa-stop" style="color: #dc3545; font-size: 16px;"></i>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).addTo(map);
        
        endMarker.bindPopup(`
            <div style="font-family: Arial, sans-serif;">
                <strong>Trip End</strong><br>
                Time: ${new Date(coordinates[endIndex].timestamp).toLocaleString()}<br>
                Location: ${coordinates[endIndex].latitude.toFixed(6)}, ${coordinates[endIndex].longitude.toFixed(6)}
            </div>
        `);
        
        routeMarkers.push(endMarker);
    }

    if (coordinates.length > 2) {
        const step = Math.max(1, Math.floor(coordinates.length / 10));
        for (let i = step; i < coordinates.length - 1; i += step) {
            const waypoint = L.circleMarker([coordinates[i].latitude, coordinates[i].longitude], {
                radius: 4,
                fillColor: '#007bff',
                color: '#ffffff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map);
            
            waypoint.bindPopup(`
                <div style="font-family: Arial, sans-serif;">
                    <strong>Waypoint</strong><br>
                    Time: ${new Date(coordinates[i].timestamp).toLocaleString()}<br>
                    Location: ${coordinates[i].latitude.toFixed(6)}, ${coordinates[i].longitude.toFixed(6)}
                </div>
            `);
            
            routeMarkers.push(waypoint);
        }
    }

    const bounds = L.latLngBounds(routeCoords);
    map.fitBounds(bounds, { padding: [20, 20] });
}

async function showDriverHistory(driverId, driverName) {
    const history = await getDriverHistory(driverId, driverName);
    
    let historyHtml = `
        <div class="modal fade" id="historyModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Trip History - ${driverName}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
    `;
    
    if (history.length === 0) {
        historyHtml += `
            <div class="text-center py-4">
                <h5 class="text-muted">No trip history found</h5>
                <p class="text-muted">This driver has no completed trips yet.</p>
            </div>
        `;
    } else {
        historyHtml += `
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        Click on any trip to view its route on the map
                    </div>
                </div>
            </div>
        `;
        
        history.forEach(trip => {
            const statusColor = trip.status === 'Completed' ? '#28a745' : '#007bff';
            
            historyHtml += `
                <div class="card mb-2 trip-history-item" onclick="viewTripRoute('${trip.trip_id}')" style="cursor: pointer;">
                    <div class="card-body py-2">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <strong>Trip ${trip.trip_id}</strong><br>
                                <small class="text-muted">${trip.container_no || 'N/A'}</small>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Date</small><br>
                                ${new Date(trip.trip_date).toLocaleDateString()}
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Destination</small><br>
                                ${trip.destination || 'Unknown'}
                            </div>
                            <div class="col-md-3 text-end">
                                <span class="badge" style="background-color: ${statusColor};">
                                    ${trip.status}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    historyHtml += `
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const existingModal = document.getElementById('historyModal');
    if (existingModal) {
        existingModal.remove();
    }

    document.body.insertAdjacentHTML('beforeend', historyHtml);

    const modal = new bootstrap.Modal(document.getElementById('historyModal'));
    modal.show();

    document.getElementById('historyModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

async function getDriverPlateNumber(driverId, assignedTruckId) {
    try {
        const response = await fetch('include/handlers/truck_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'getPlateByTruckId',
                truck_id: assignedTruckId
            }),
        });

        const data = await response.json();
        console.log('Plate number response:', data);

        if (data.success && data.plate_no) {
            return data.plate_no;
        }
    } catch (error) {
        console.error('Error fetching plate number for driver:', driverId, error);
    }
    return null;
}

async function viewTripRoute(tripId) {
    const routeData = await getTripRoute(tripId);
    if (routeData) {
        displayRoute(routeData);

        const modal = bootstrap.Modal.getInstance(document.getElementById('historyModal'));
        if (modal) {
            modal.hide();
        }
    } else {
        alert('No route data available for this trip');
    }
}

window.showDriverHistory = showDriverHistory;
window.viewTripRoute = viewTripRoute;
window.clearRoute = clearRoute;

async function getTripDetails(tripId) {
    if (!tripId) return null;
    
    try {
        const response = await fetch('include/handlers/trip_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_trips_with_drivers', 
                trip_id: tripId
            }),
        });

        const data = await response.json();
        console.log('Trip details response:', data);

        if (data.success && data.trips && data.trips.length > 0) {
            const trip = data.trips.find(t => t.trip_id == tripId);
            if (trip) {
                return {
                    destination: trip.destination || 'Unknown Destination',
                    origin: trip.client || 'Unknown Origin',
                    status: trip.status || 'Unknown Status'
                };
            }
        }
    } catch (error) {
        console.error('Error fetching trip details:', error);
    }
    return null;
}

async function getActiveTrip(driverId, driverName) {
    try {
        const response = await fetch('include/handlers/trip_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_driver_current_trip',
                driver_id: driverId,
                driver_name: driverName,
            }),
        });

        const data = await response.json();
        console.log('Active trip response for', driverName, ':', data);

        if (data.success && data.trip) {
            const trip = data.trip;
            return {
                trip_id: trip.trip_id,
                destination: trip.destination || 'Unknown Destination',
                origin: trip.client || 'Unknown Origin',
                port_name: trip.port_name || 'Unknown Port', 
                status: trip.status || 'En Route'
            };
        }
    } catch (error) {
        console.error('Error fetching active trip for driver:', driverId, error);
    }
    return null;
}

function initMap() {
    const mapElement = document.getElementById('map');
    if (!mapElement) return;

    map = L.map('map').setView([14.7874696, 121.0040994], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    fetchDriverData();

    const refreshBtn = document.getElementById('refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            
            fetchDriverData();

            setTimeout(() => {
                icon.classList.remove('fa-spin');
            }, 1000);
        });
    }
}

async function fetchDriverData() {
    try {
        const response = await fetch('include/config/firebase.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log("API Response:", data);
        
        if (data && data.drivers) {
            const enhancedDrivers = await enhanceDriversWithDestinations(data.drivers);
            allDrivers = enhancedDrivers;
            updateMap(enhancedDrivers);
            updateDriversList(enhancedDrivers);
        } else {
            console.log("Using sample data (no drivers in API response)");
            allDrivers = sampleData.drivers;
            updateMap(sampleData.drivers);
            updateDriversList(sampleData.drivers);
        }
    } catch (error) {
        console.error('Error fetching data:', error);
        console.log("Using sample data due to API error");
        allDrivers = sampleData.drivers;
        updateMap(sampleData.drivers);
        updateDriversList(sampleData.drivers);
        
        const driversListContainer = document.getElementById('drivers-list');
        if (driversListContainer && !driversListContainer.innerHTML.includes('drivers-content')) {
            driversListContainer.innerHTML = `
                <div class="alert alert-warning">
                    API connection failed. Showing sample data.
                </div>
                <div id="drivers-content"></div>
            `;
        }
    }
}

async function enhanceDriversWithDestinations(drivers) {
    const enhancedDrivers = {};

    const driverPromises = Object.entries(drivers).map(async ([driverId, driverData]) => {
        try {
            const activeTripData = await getActiveTrip(driverId, driverData.name);

            let plateNumber = null;
            if (driverData.assigned_truck_id) {
                plateNumber = await getDriverPlateNumber(driverId, driverData.assigned_truck_id);
            }
            
            return [driverId, {
                ...driverData,
                assigned_trip_id: activeTripData?.trip_id || driverData.assigned_trip_id || null,
                destination: activeTripData?.destination || driverData.destination || null,
                origin: activeTripData?.origin || driverData.origin || null,
                port_name: activeTripData?.port_name || driverData.port_name || null, 
                trip_status: activeTripData?.status || null,
                plate_no: plateNumber || driverData.plate_no || null
            }];
        } catch (error) {
            console.error('Error enhancing driver data for:', driverId, error);
            return [driverId, driverData];
        }
    });
    
    const results = await Promise.all(driverPromises);
    
    results.forEach(([driverId, driverData]) => {
        enhancedDrivers[driverId] = driverData;
    });
    
    return enhancedDrivers;
}

function getDriverStatus(driverData) {
    if (driverData.status && driverData.status.status) {
        const explicitStatus = driverData.status.status.toLowerCase();
        if (explicitStatus === 'offline' || explicitStatus === 'inactive') {
            return 'offline';
        }
    }

    let isRecentlyActive = false;
    if (driverData.location && driverData.location.last_updated) {
        const lastUpdate = parseInt(driverData.location.last_updated);
        const currentTime = Date.now();
        const timeDiff = currentTime - lastUpdate;

        if (timeDiff >= 300000) {
            return 'offline';
        }

        isRecentlyActive = true;
    }

    if (isRecentlyActive && driverData.status && driverData.status.status) {
        return driverData.status.status.toLowerCase();
    }

    if (isRecentlyActive) {
        return 'online';
    }

    return 'offline';
}

function getStatusColor(status) {
    switch (status.toLowerCase()) {
        case 'online':
        case 'active':
            return '#28a745';
        case 'offline':
        case 'inactive':
            return '#dc3545';
        case 'busy':
        case 'on-trip':
            return '#ffc107';
        default:
            return '#6c757d';
    }
}

function getStatusDisplayText(status) {
    switch (status.toLowerCase()) {
        case 'online':
            return 'Online';
        case 'offline':
            return 'Offline';
        case 'busy':
            return 'Busy';
        case 'on-trip':
            return 'On Trip';
        case 'active':
            return 'Active';
        case 'inactive':
            return 'Inactive';
        default:
            return status.charAt(0).toUpperCase() + status.slice(1);
    }
}

function getTimeSinceLastUpdate(timestamp) {
    if (!timestamp) return 'Unknown';
    
    const now = Date.now();
    const lastUpdate = parseInt(timestamp);
    const diffMs = now - lastUpdate;
    
    const diffMinutes = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffMinutes < 1) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes} min ago`;
    if (diffHours < 24) return `${diffHours} hr ago`;
    return `${diffDays} days ago`;
}

function truncateDestination(destination, maxLength = 30) {
    if (!destination || destination.length <= maxLength) {
        return destination || 'No destination';
    }
    return destination.substring(0, maxLength) + '...';
}

function updateMap(drivers) {
    if (!drivers || !map) return;
    
    const bounds = [];
    const driversFound = new Set();
    
    Object.entries(drivers).forEach(([driverId, driverData]) => {
        if (!driverData.location || !driverData.location.latitude || !driverData.location.longitude) return;
        
        driversFound.add(driverId);
        
        const position = [
            parseFloat(driverData.location.latitude),
            parseFloat(driverData.location.longitude)
        ];

        if (isNaN(position[0]) || isNaN(position[1])) return;

        bounds.push(position);

        const driverName = driverData.name || "Unknown Driver";
        const driverStatus = getDriverStatus(driverData);
        const statusColor = getStatusColor(driverStatus);
        const statusText = getStatusDisplayText(driverStatus);
        let truckLicense = driverData.plate_no || driverData.assigned_truck_id || 'N/A';
        
        // Get all the data points we need
        const origin = driverData.origin;
        const portName = driverData.port_name;
        const destination = driverData.destination;
        const tripId = driverData.assigned_trip_id;

        const lastUpdated = driverData.location.last_updated ? 
            new Date(parseInt(driverData.location.last_updated)).toLocaleString() : 
            'Unknown';
            
        const timeSinceUpdate = getTimeSinceLastUpdate(driverData.location.last_updated);

        const popupContent = `
            <div style="width: 270px; padding: 15px; font-family: Arial, sans-serif;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                    <h6 style="margin: 0; font-size: 16px; font-weight: bold;">${driverName}</h6>
                    <div style="display: flex; align-items: center;">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: ${statusColor}; margin-right: 6px; box-shadow: 0 0 0 2px ${statusColor}33;"></div>
                        <span style="background: ${statusColor}; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                            ${statusText}
                        </span>
                    </div>
                </div>
                <div style="margin-bottom: 8px;">
                    <div style="margin: 4px 0; display: flex; align-items: center;">
                        <span><strong>Truck License:</strong> ${truckLicense}</span>
                    </div>
                    ${origin ? `
                    <div style="margin: 4px 0; display: flex; align-items: flex-start;">
                        <div>
                            <strong>Client:</strong><br>
                            <span style="font-size: 12px; line-height: 1.3;">${origin}</span>
                        </div>
                    </div>` : ''}
                    ${portName ? `
                    <div style="margin: 4px 0; display: flex; align-items: flex-start;">
                        <div>
                            <strong>Port:</strong><br>
                            <span style="font-size: 12px; line-height: 1.3;">${portName}</span>
                        </div>
                    </div>` : ''}
                    ${destination ? `
                    <div style="margin: 4px 0; display: flex; align-items: flex-start;">
                        <div>
                            <strong>Destination:</strong><br>
                            <span style="font-size: 12px; line-height: 1.3;">${destination}</span>
                            ${tripId ? `<br><small style="color: #6c757d; font-size: 10px;">Trip: ${tripId}</small>` : ''}
                        </div>
                    </div>` : `
                    <div style="margin: 4px 0; display: flex; align-items: center;">
                        <span style="color: #6c757d;"><em>No active trip</em></span>
                    </div>`}
                    <div style="margin: 4px 0; display: flex; align-items: center;">
                        <span><strong>Last Update:</strong> ${timeSinceUpdate}</span>
                    </div>
                    <div style="margin: 4px 0; display: flex; align-items: center;">
                        <span style="font-size: 11px; color: #6c757d;">${position[0].toFixed(6)}, ${position[1].toFixed(6)}</span>
                    </div>
                </div>
                <div style="font-size: 10px; color: #999; text-align: center; margin-top: 8px; border-top: 1px solid #eee; padding-top: 5px;">
                    Full update: ${lastUpdated}
                </div>
            </div>
        `;

        if (markers[driverId]) {
            markers[driverId].setLatLng(position);
            markers[driverId].getPopup().setContent(popupContent);

            const iconHtml = `<i class="fas fa-truck" style="font-size: 20px; color: ${statusColor}; filter: drop-shadow(1px 1px 1px rgba(0,0,0,0.3));"></i>`;
            markers[driverId].setIcon(L.divIcon({
                className: 'truck-icon',
                html: iconHtml,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            }));
        } else {
            const truckIcon = L.divIcon({
                className: 'truck-icon',
                html: `<i class="fas fa-truck" style="font-size: 20px; color: ${statusColor}; filter: drop-shadow(1px 1px 1px rgba(0,0,0,0.3));"></i>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
            
            const marker = L.marker(position, {
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
        map.fitBounds(bounds, { padding: [20, 20] });
        if (bounds.length === 1) {
            map.setZoom(15); 
        }
    }

    setTimeout(() => {
        map.invalidateSize();
    }, 100);
}

function updateDriversList(drivers) {
    const driversListContainer = document.getElementById('drivers-content') || document.getElementById('drivers-list');
    if (!driversListContainer) return;

    let controlsContainer = document.getElementById('drivers-controls');
    let driversContainer = document.getElementById('drivers-container');

    if (!controlsContainer || !driversContainer) {
        driversListContainer.style.cssText = `
            display: flex;
            flex-direction: column;
            height: calc(100vh - 50px);
            overflow: hidden;
        `;
        
        driversListContainer.innerHTML = `
            <div id="drivers-controls" style="
                flex-shrink: 0;
                background: white;
                z-index: 100;
                padding: 15px;
                border-bottom: 1px solid #e9ecef;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            "></div>
            <div id="drivers-container" style="
                flex: 1;
                overflow-y: auto;
                padding: 0 15px;
                background: white;
                min-height: 0;
            "></div>
        `;
        controlsContainer = document.getElementById('drivers-controls');
        driversContainer = document.getElementById('drivers-container');

        setupDriversControls(controlsContainer);
    }

    if (!drivers) {
        driversContainer.innerHTML = '<div class="alert alert-warning">No drivers found</div>';
        return;
    }
    
    let html = '';
    let hasDrivers = false;
    let onlineCount = 0;
    let offlineCount = 0;
    let busyCount = 0;

    let totalOnlineCount = 0;
    let totalOfflineCount = 0;
    let totalBusyCount = 0;
    
    Object.values(drivers).forEach(driverData => {
        if (!driverData.location) return;
        const status = getDriverStatus(driverData);
        if (status === 'online') totalOnlineCount++;
        else if (status === 'busy' || status === 'on-trip') totalBusyCount++;
        else totalOfflineCount++;
    });

    updateDriversControlsHeader(totalOnlineCount, totalOfflineCount, totalBusyCount);

    const filteredDrivers = Object.entries(drivers).filter(([driverId, driverData]) => {
        if (!driverData.location) return false;
        
        const driverName = (driverData.name || "").toLowerCase();
        const truckLicense = (driverData.plate_no || driverData.assigned_truck_id || "").toString().toLowerCase();
        const driverStatus = getDriverStatus(driverData);

        const matchesSearch = currentSearchTerm === '' || 
                             driverName.includes(currentSearchTerm.toLowerCase()) ||
                             truckLicense.includes(currentSearchTerm.toLowerCase());

        let matchesFilter = true;
        if (currentFilter === 'Online') {
            matchesFilter = driverStatus === 'online';
        } else if (currentFilter === 'Offline') {
            matchesFilter = driverStatus === 'offline';
        }
        
        return matchesSearch && matchesFilter;
    });

    const sortedDrivers = filteredDrivers.sort(([, a], [, b]) => {
        const statusA = getDriverStatus(a);
        const statusB = getDriverStatus(b);
        
        if (statusA === 'online' && statusB !== 'online') return -1;
        if (statusA !== 'online' && statusB === 'online') return 1;
        
        return (a.name || '').localeCompare(b.name || '');
    });
    
    sortedDrivers.forEach(([driverId, driverData]) => {
        hasDrivers = true;
        
        const driverStatus = getDriverStatus(driverData);
        const statusText = getStatusDisplayText(driverStatus);
        const statusColor = getStatusColor(driverStatus);
        const driverName = driverData.name || "Unknown Driver";
        const truckLicense = driverData.plate_no || driverData.assigned_truck_id || 'N/A';
        const destination = driverData.destination;

        if (driverStatus === 'online') {
            onlineCount++;
        } else if (driverStatus === 'busy' || driverStatus === 'on-trip') {
            busyCount++;
        } else {
            offlineCount++;
        }

        const timeSinceUpdate = getTimeSinceLastUpdate(driverData.location.last_updated);

        html += `
            <div class="driver-card" data-driver-id="${driverId}" style="
                background: white;
                border: none;
                border-bottom: 1px solid #e9ecef;
                padding: 15px 0;
                margin: 0;
                transition: background-color 0.2s ease;
                cursor: pointer;
            " onmouseover="this.style.backgroundColor='#f8f9fa';" 
               onmouseout="this.style.backgroundColor='white';">
                
                <!-- Status indicator and driver name -->
                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                    <div style="
                        width: 8px; 
                        height: 8px; 
                        border-radius: 50%; 
                        background-color: ${statusColor}; 
                        margin-right: 8px;
                        flex-shrink: 0;
                    "></div>
                    <span style="
                        font-weight: 600; 
                        font-size: 14px; 
                        color: #333;
                        flex-grow: 1;
                    ">${driverName}</span>
                </div>

                <!-- Status and truck info -->
                <div style="
                    margin-bottom: 6px;
                    margin-left: 16px;
                ">
                    <span style="
                        color: ${statusColor}; 
                        font-size: 12px; 
                        font-weight: 500;
                        text-transform: capitalize;
                    ">${statusText}</span>
                    <span style="
                        margin-left: 12px;
                        color: #6c757d; 
                        font-size: 12px;
                    ">Truck License ${truckLicense}</span>
                </div>

                <!-- Last seen -->
                <div style="
                    display: flex; 
                    align-items: center; 
                    margin-bottom: ${destination ? '6px' : '12px'};
                    margin-left: 16px;
                    color: #6c757d; 
                    font-size: 12px;
                ">
                    Last seen: ${timeSinceUpdate}
                </div>

                ${destination ? `
                <!-- Destination -->
                <div style="
                    display: flex; 
                    align-items: center; 
                    margin-bottom: 12px;
                    margin-left: 16px;
                    color: #6c757d; 
                    font-size: 12px;
                ">
                    Destination: ${truncateDestination(destination, 25)}
                </div>` : ''}

                <!-- Action buttons -->
                <div style="
                    display: flex; 
                    gap: 8px; 
                    margin-left: 16px;
                    margin-top: 8px;
                ">
                    <button class="btn-locate" onclick="centerOnDriver('${driverId}')" style="
                        background: none;
                        border: 1px solid #dee2e6;
                        border-radius: 4px;
                        padding: 4px 12px;
                        font-size: 11px;
                        color: #6c757d;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        display: flex;
                        align-items: center;
                        gap: 4px;
                    " onmouseover="this.style.borderColor='#007bff'; this.style.color='#007bff';" 
                       onmouseout="this.style.borderColor='#dee2e6'; this.style.color='#6c757d';">
                        Locate
                    </button>
                    
                    <button class="btn-history" onclick="showDriverHistory('${driverId}', '${driverName}')" style="
                        background: none;
                        border: 1px solid #dee2e6;
                        border-radius: 4px;
                        padding: 4px 12px;
                        font-size: 11px;
                        color: #6c757d;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        display: flex;
                        align-items: center;
                        gap: 4px;
                    " onmouseover="this.style.borderColor='#6c757d'; this.style.color='#495057';" 
                       onmouseout="this.style.borderColor='#dee2e6'; this.style.color='#6c757d';">
                        History
                    </button>
                </div>
            </div>
        `;
    });
    
    if (!hasDrivers) {
        html = `
            <div style="
                text-align: center; 
                padding: 40px 20px; 
                color: #6c757d;
            ">
                <h6 style="margin-bottom: 8px; color: #495057;">No drivers found</h6>
                <p style="font-size: 12px; margin: 0;">
                    ${currentSearchTerm ? `No results for "${currentSearchTerm}"` : 'Try adjusting your search or filter'}
                </p>
            </div>
        `;
    }

    driversContainer.innerHTML = html;
}

function setupDriversControls(controlsContainer) {
    controlsContainer.innerHTML = `
        <div>
            <!-- Header with refresh button -->
            <div style="
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                margin-bottom: 16px;
            ">
                <span id="drivers-count" style="
                    font-weight: 600; 
                    color: #333; 
                    font-size: 16px;
                ">Drivers (0)</span>
                <button id="refresh-btn" style="
                    background: none;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    padding: 6px 10px;
                    color: #6c757d;
                    cursor: pointer;
                    font-size: 12px;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                " onmouseover="this.style.borderColor='#007bff'; this.style.color='#007bff';" 
                   onmouseout="this.style.borderColor='#dee2e6'; this.style.color='#6c757d';">
                    <i class="fas fa-sync-alt" style="font-size: 10px;"></i>
                    Refresh
                </button>
            </div>
            
            <!-- Search Box -->
            <div style="margin-bottom: 12px;">
                <div style="position: relative;">
                    <input type="text" id="driver-search" placeholder="Search drivers or trucks..." style="
                        width: 100%;
                        padding: 8px 8px 8px 28px;
                        border: 1px solid #dee2e6;
                        border-radius: 4px;
                        font-size: 12px;
                        background: white;
                        transition: border-color 0.2s ease;
                        box-sizing: border-box;
                    " value="${currentSearchTerm || ''}">
                </div>
            </div>
            
            <!-- Status Summary and Filter -->
            <div style="
                display: flex; 
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0;
            ">
                <div id="status-summary" style="
                    display: flex; 
                    align-items: center; 
                    gap: 12px; 
                    font-size: 12px;
                ">
                    <!-- Status counts will be updated here -->
                </div>
                
                <!-- Filter Dropdown -->
                <select id="driver-filter" style="
                    padding: 4px 8px;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    font-size: 12px;
                    background: white;
                    color: #333;
                    cursor: pointer;
                    min-width: 80px;
                ">
                    <option value="All" ${(currentFilter === 'All' || !currentFilter) ? 'selected' : ''}>All</option>
                    <option value="Online" ${currentFilter === 'Online' ? 'selected' : ''}>Online</option>
                    <option value="Offline" ${currentFilter === 'Offline' ? 'selected' : ''}>Offline</option>
                </select>
            </div>
        </div>
    `;

    setupSearchAndFilterListeners();
    
    const refreshBtn = document.getElementById('refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            
            fetchDriverData();

            setTimeout(() => {
                icon.classList.remove('fa-spin');
            }, 1000);
        });
    }
}

function updateDriversControlsHeader(totalOnlineCount, totalOfflineCount, totalBusyCount) {
    const driversCount = document.getElementById('drivers-count');
    const statusSummary = document.getElementById('status-summary');
    
    if (driversCount) {
        const totalCount = totalOnlineCount + totalOfflineCount + totalBusyCount;
        driversCount.textContent = `Drivers (${totalCount})`;
    }
    
    if (statusSummary) {
        statusSummary.innerHTML = `
            <div style="display: flex; align-items: center;">
                <div style="
                    width: 6px; 
                    height: 6px; 
                    border-radius: 50%; 
                    background: #28a745; 
                    margin-right: 4px;
                "></div>
                <span>Online: ${totalOnlineCount}</span>
            </div>
            <div style="display: flex; align-items: center;">
                <div style="
                    width: 6px; 
                    height: 6px; 
                    border-radius: 50%; 
                    background: #6c757d; 
                    margin-right: 4px;
                "></div>
                <span>Offline: ${totalOfflineCount}</span>
            </div>
        `;
    }
}

function setupSearchAndFilterListeners() {
    const searchInput = document.getElementById('driver-search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearchTerm = e.target.value;
                updateDriversList(allDrivers);
            }, 300);
        });

        searchInput.addEventListener('focus', function() {
            this.style.borderColor = '#007bff';
            this.style.boxShadow = '0 0 0 2px rgba(0, 123, 255, 0.1)';
        });

        searchInput.addEventListener('blur', function() {
            this.style.borderColor = '#dee2e6';
            this.style.boxShadow = 'none';
        });
    }

    const filterSelect = document.getElementById('driver-filter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function(e) {
            currentFilter = e.target.value;
            updateDriversList(allDrivers);
        });

        filterSelect.addEventListener('focus', function() {
            this.style.borderColor = '#007bff';
            this.style.boxShadow = '0 0 0 2px rgba(0, 123, 255, 0.1)';
        });

        filterSelect.addEventListener('blur', function() {
            this.style.borderColor = '#dee2e6';
            this.style.boxShadow = 'none';
        });
    }
}

function centerOnDriver(driverId) {
    const marker = markers[driverId];
    if (marker) {
        map.setView(marker.getLatLng(), 17);
        map.closePopup();
        marker.openPopup();

        document.querySelectorAll('.driver-info').forEach(el => {
            el.classList.remove('active');
        });
        
        const driverElement = document.querySelector(`.driver-info[data-driver-id="${driverId}"]`);
        if (driverElement) {
            driverElement.classList.add('active');
            driverElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initMap, 500);

    window.addEventListener('resize', function() {
        if (map) {
            setTimeout(() => {
                map.invalidateSize();
            }, 100);
        }
    });
});

window.addEventListener('beforeunload', function() {
    if (updateTimer) {
        clearInterval(updateTimer);
    }
});