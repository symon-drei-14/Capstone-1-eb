let map;
let markers = {};
let updateTimer;

const firebase_url = "https://mansartrucking1-default-rtdb.asia-southeast1.firebasedatabase.app";
const firebase_auth = "Xtnh1Zva11o8FyDEA75gzep6NUeNJLMZiCK6mXB7";

document.addEventListener('DOMContentLoaded', function() {
    initMap();
});

function initMap() {
    map = L.map('map').setView([14.7874696, 121.0040994], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    fetchDriverData();

    updateTimer = setInterval(fetchDriverData, 10000);

    document.getElementById('refresh-btn').addEventListener('click', function() {
        fetchDriverData();
        console.log('Data manually refreshed');
    });
}

function fetchDriverData() {
    fetch(`${firebase_url}/drivers.json?auth=${firebase_auth}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            updateMap(data);
            updateDriversList(data);
            updateTrackingTable(data);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            document.getElementById('drivers-list').innerHTML = `
                <div style="padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px;">
                    Error loading data: ${error.message}
                </div>
            `;
        });
}

function updateMap(data) {
    if (!data) return;

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
                <p><strong>Truck ID:</strong> ${location.truck_id || 'N/A'}</p>
                <p><strong>Last Updated:</strong> ${location.timestamp || 'Unknown'}</p>
                <p><strong>Coordinates:</strong> ${position[0].toFixed(6)}, ${position[1].toFixed(6)}</p>
                ${location.destination ? `<p><strong>Destination:</strong> ${location.destination}</p>` : ''}
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
}

function updateDriversList(data) {
    if (!data) {
        document.getElementById('drivers-list').innerHTML = '<div style="padding: 15px; background-color: #fff3cd; color: #856404; border-radius: 5px;">No drivers found</div>';
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
                <div style="font-size: 14px; margin-bottom: 5px;">Truck: ${truckId}</div>
                <div style="font-size: 12px; color: #6c757d;">Last update: ${timestamp}</div>
            </div>
        `;
    });
    
    document.getElementById('drivers-list').innerHTML = html || '<div style="padding: 15px; background-color: #fff3cd; color: #856404; border-radius: 5px;">No drivers found</div>';
}

function updateTrackingTable(data) {
    if (!data) {
        document.getElementById('tracking-table-body').innerHTML = `
            <tr>
                <td colspan="7" class="text-center">No vehicle data available</td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    
    Object.entries(data).forEach(([driverId, driverData]) => {
        if (!driverData.current_location) return;
        
        const location = driverData.current_location;
        const driverName = location.driver_name || driverId;
        const truckId = location.truck_id || 'N/A';
        const currentLocation = `${location.latitude?.toFixed(6)}, ${location.longitude?.toFixed(6)}`;
        const destination = location.destination || 'Not specified';
        const status = location.status || 'On route';
        const progress = Math.floor(Math.random() * 100);
        
        html += `
            <tr>
                <td><i class="fas fa-truck icon-bg2"></i></td>
                <td>${driverName}</td>
                <td>${truckId}</td>
                <td>${currentLocation}</td>
                <td>${destination}</td>
                <td>${status}</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: ${progress}%;">${progress}%</div>
                    </div>
                </td>
            </tr>
        `;
    });
    
    if (html) {
        document.getElementById('tracking-table-body').innerHTML = html;
    } else {
        document.getElementById('tracking-table-body').innerHTML = `
            <tr>
                <td colspan="7" class="text-center">No vehicle data available</td>
            </tr>
        `;
    }
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

window.centerOnDriver = centerOnDriver;