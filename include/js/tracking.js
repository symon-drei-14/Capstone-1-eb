let map;
let markers = {};
let updateTimer;
let allDrivers = {};

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
                    <i class="fas fa-exclamation-triangle"></i> 
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
            
            return [driverId, {
                ...driverData,
                assigned_trip_id: activeTripData?.trip_id || driverData.assigned_trip_id || null,
                destination: activeTripData?.destination || driverData.destination || null,
                origin: activeTripData?.origin || driverData.origin || null,
                trip_status: activeTripData?.status || null
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
        let truckId = driverData.assigned_truck_id || 'N/A';
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
                        <i class="fas fa-truck" style="color: #007bff; width: 16px; margin-right: 8px;"></i>
                        <span><strong>Truck ID:</strong> ${truckId}</span>
                    </div>
                    ${destination ? `
                    <div style="margin: 4px 0; display: flex; align-items: flex-start;">
                        <i class="fas fa-map-marker-alt" style="color: #28a745; width: 16px; margin-right: 8px; margin-top: 2px;"></i>
                        <div>
                            <strong>Destination:</strong><br>
                            <span style="font-size: 12px; line-height: 1.3;">${destination}</span>
                            ${tripId ? `<br><small style="color: #6c757d; font-size: 10px;">Trip: ${tripId}</small>` : ''}
                        </div>
                    </div>` : `
                    <div style="margin: 4px 0; display: flex; align-items: center;">
                        <i class="fas fa-pause-circle" style="color: #6c757d; width: 16px; margin-right: 8px;"></i>
                        <span style="color: #6c757d;"><em>No active destination</em></span>
                    </div>`}
                    <div style="margin: 4px 0; display: flex; align-items: center;">
                        <i class="fas fa-clock" style="color: #ffc107; width: 16px; margin-right: 8px;"></i>
                        <span><strong>Last Update:</strong> ${timeSinceUpdate}</span>
                    </div>
                    <div style="margin: 4px 0; display: flex; align-items: center;">
                        <i class="fas fa-crosshairs" style="color: #dc3545; width: 16px; margin-right: 8px;"></i>
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
    if (!drivers || !driversListContainer) {
        driversListContainer.innerHTML = '<div class="alert alert-warning">No drivers found</div>';
        return;
    }
    
    let html = '';
    let hasDrivers = false;
    let onlineCount = 0;
    let offlineCount = 0;
    let busyCount = 0;

    const sortedDrivers = Object.entries(drivers).sort(([, a], [, b]) => {
        const statusA = getDriverStatus(a);
        const statusB = getDriverStatus(b);
        
        if (statusA === 'online' && statusB !== 'online') return -1;
        if (statusA !== 'online' && statusB === 'online') return 1;
        
        return (a.name || '').localeCompare(b.name || '');
    });
    
    sortedDrivers.forEach(([driverId, driverData]) => {
        if (!driverData.location) return;
        
        hasDrivers = true;
        
        const driverStatus = getDriverStatus(driverData);
        const statusText = getStatusDisplayText(driverStatus);
        const statusColor = getStatusColor(driverStatus);
        const driverName = driverData.name || "Unknown Driver";
        const truckId = driverData.assigned_truck_id || 'N/A';
        const destination = driverData.destination;
        const tripId = driverData.assigned_trip_id;

        if (driverStatus === 'online') {
            onlineCount++;
        } else if (driverStatus === 'busy' || driverStatus === 'on-trip') {
            busyCount++;
        } else {
            offlineCount++;
        }

        const timeSinceUpdate = getTimeSinceLastUpdate(driverData.location.last_updated);
        
        const statusClass = driverStatus === 'online' ? 'status-online' : 'status-offline';
        const indicatorClass = driverStatus === 'online' ? 'online' : 'offline';
        
        html += `
            <div class="driver-info" data-driver-id="${driverId}" onclick="centerOnDriver('${driverId}')">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold" style="color: #333;">${driverName}</div>
                    <div class="d-flex align-items-center">
                        <span class="status-indicator ${indicatorClass}" style="background-color: ${statusColor};"></span>
                        <span class="${statusClass}" style="color: ${statusColor}; font-weight: bold; font-size: 11px;">
                            ${statusText}
                        </span>
                    </div>
                </div>
                <div class="small mb-1">
                    <i class="fas fa-truck text-primary me-1"></i>
                    <strong>Truck:</strong> ${truckId}
                </div>
                <div class="small text-muted mb-1">
                    <i class="fas fa-clock me-1"></i>
                    <strong>Last seen:</strong> ${timeSinceUpdate}
                </div>
                ${destination ? `
                <div class="small mb-1"">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <strong>Destination:</strong> <span title="${destination}">${truncateDestination(destination, 25)}</span>
                </div>` : 
                '<div class="small text-secondary"><i class="fas fa-pause-circle me-1"></i>No active Trip</div>'}
            </div>
        `;
    });
    
    if (!hasDrivers) {
        html = '<div class="alert alert-warning">No drivers found</div>';
    } else {
        const summaryHtml = `
            <div class="alert alert-info mb-3" style="background-color: #f8f9fa; border: 1px solid #dee2e6;">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-bold text-success" style="font-size: 18px;">${onlineCount}</div>
                        <small style="color: #28a745;">Online</small>
                    </div>
                    ${busyCount > 0 ? `
                    <div class="col-4">
                        <div class="fw-bold" style="color: #ffc107; font-size: 18px;">${busyCount}</div>
                        <small style="color: #ffc107;">Busy</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-danger" style="font-size: 18px;">${offlineCount}</div>
                        <small style="color: #dc3545;">Offline</small>
                    </div>` : `
                    <div class="col-4">
                        <div class="fw-bold text-danger" style="font-size: 18px;">${offlineCount}</div>
                        <small style="color: #dc3545;">Offline</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-muted" style="font-size: 18px;">${onlineCount + offlineCount}</div>
                        <small style="color: #6c757d;">Total</small>
                    </div>`}
                </div>
            </div>
        `;
        html = summaryHtml + html;
    }
    
    driversListContainer.innerHTML = html;
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