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
            "assigned_trip_id": "trip_1743941164224"
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
            "assigned_trip_id": "trip_1743931815461"
        }
    }
};

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
            console.log("API Response:", data);
            if (data && data.drivers) {
                allDrivers = data.drivers;
                updateMap(data.drivers);
                updateDriversList(data.drivers);
            } else {
                console.log("Using sample data (no drivers in API response)");
                allDrivers = sampleData.drivers;
                updateMap(sampleData.drivers);
                updateDriversList(sampleData.drivers);
            }
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            console.log("Using sample data due to API error");
            allDrivers = sampleData.drivers;
            updateMap(sampleData.drivers);
            updateDriversList(sampleData.drivers);
            
            document.getElementById('drivers-list').innerHTML = `
                <div id="drivers-content"></div>
            `;
        });
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

        let truckId = driverData.assigned_truck_id || 'N/A';

        const tripId = driverData.assigned_trip_id || null;

        const lastUpdated = driverData.location.last_updated ? 
            new Date(parseInt(driverData.location.last_updated)).toLocaleString() : 
            'Unknown';

        const popupContent = `
            <div style="width: 200px; padding: 10px;">
                <h6>${driverName}</h6>
                <p>Assigned Truck: ${truckId}</p>
                <p>Last Updated: ${lastUpdated}</p>
                <p>Coordinates: ${position[0].toFixed(6)}, ${position[1].toFixed(6)}</p>
                ${tripId ? `<p>Trip ID: ${tripId}</p>` : ''}
            </div>
        `;

        if (markers[driverId]) {
            markers[driverId].setLatLng(position);
            markers[driverId].getPopup().setContent(popupContent);
        } else {
            const truckIcon = L.divIcon({
                className: 'truck-icon',
                html: '<i class="fas fa-truck" style="font-size: 20px; color: #3388ff;"></i>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
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
        map.fitBounds(bounds);
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
    
    Object.entries(drivers).forEach(([driverId, driverData]) => {
        if (!driverData.location) return;
        
        hasDrivers = true;

        const lastUpdated = driverData.location.last_updated ? 
            new Date(parseInt(driverData.location.last_updated)).toLocaleString() : 
            'Unknown';

        let truckId = driverData.assigned_truck_id || 'N/A';

        const driverName = driverData.name || "Unknown Driver";

        const tripId = driverData.assigned_trip_id || null;
        
        html += `
            <div class="driver-info" data-driver-id="${driverId}" onclick="centerOnDriver('${driverId}')">
                <div class="d-flex justify-content-between">
                    <div class="fw-bold">${driverName}</div>
                    <div><i class="fas fa-truck text-primary"></i></div>
                </div>
                <div class="small mb-1">Truck: ${truckId}</div>
                <div class="small text-muted">Last update: ${lastUpdated}</div>
                ${tripId ? `<div class="small">Trip: ${tripId}</div>` : ''}
            </div>
        `;
    });
    
    if (!hasDrivers) {
        html = '<div class="alert alert-warning">No drivers found</div>';
    }
    
    driversListContainer.innerHTML = html;
}

function centerOnDriver(driverId) {
    const marker = markers[driverId];
    if (marker) {
        map.setView(marker.getLatLng(), 16);
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
            map.invalidateSize();
        }
    });
});