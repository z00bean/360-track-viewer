<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$path = "./datasets";

$datasetNames = scandir($path);

$datasetNames = array_filter(scandir($path), function ($item) {
    return $item[0] !== '.';
});

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>360 Image Viewer and GPS Map</title>
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
        main {
            max-width: 960px;
            margin: 0 auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            /* border-bottom: 1px solid rgba(255, 255, 255, 0.1); */
            margin: 3em 0;
        }

        body {
            font-size: 16px;
            background: #121212;
            color: rgba(255, 255, 255, 0.8);
            background: rgb(2, 0, 36);
            background: linear-gradient(0deg,
                    rgba(2, 0, 36, 1) 0%,
                    rgba(46, 46, 46, 1) 100%);
            height: 100vh;
        }

        #stepSize {
            background: none;
            border: 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.4em;
        }

        button {
            border-radius: 0.5em;
            border: none;
            background: none;
            color: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        button:hover {
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.1);
        }

        #viewer {
            height: 400px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5em 0.5em 0 0;
            box-sizing: border-box;
        }

        .map-container {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }

        .map-subcontainers {
            width: 47.5%;
        }

        .footnote {
            margin-bottom: 0.75em;
            color: rgba(255, 255, 255, 0.5);
        }

        #map {
            height: 400px;
            margin-right: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5em;
        }

        #map2 {
            height: 400px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5em;
        }

        #controls {
            display: flex;
            justify-content: center;
            justify-content: space-between;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 2em;
            border-radius: 0 0 0.5em 0.5em;
            border-top: 0;
        }

        #controls button {
            padding: 10px 20px;
            margin: 0 10px;
            font-size: 16px;
            cursor: pointer;
        }

        body {
            font-family: Arial, sans-serif;
        }

        .blue-dot {
            background-color: rgb(34, 34, 250);
            border-radius: 50%;
            width: 8px;
            height: 8px;
            border: 1px solid rgb(180, 180, 209);
        }

        .pnlm-container {
            background: none;
        }

        .test-block {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        h1 {
            margin: 0;
        }

        select {
            background: none;
            outline: none;
            border: 0;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0em 1em;
            border-radius: 0.5em;
            color: rgba(255, 255, 255, 0.6);
        }

        select:hover {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <main>
        <header>
            <h1>360 Image Viewer and GPS Map</h1>

        </header>
        <div id="viewer" class="test-block">
            <p class="test-item footnote">No preview available</p>
        </div>
        <div id="controls">
            <div>
                <button id="prevButton">&lt;</button>
                <button id="nextButton">&gt;</button>
                &nbsp; &nbsp; &nbsp; &nbsp;
                <button id="prevXButton">&lt;&lt;</button>
                <input
                    style="text-align: center"
                    type="number"
                    id="stepSize"
                    value="5"
                    min="2"
                    max="20"
                    style="margin-left: 10px" />
                <button id="nextXButton">&gt;&gt;</button>
            </div>
            <select id="dropdown" name="dataset">
                <option value="-">Choose dataset</option>
                <?php
                foreach ($datasetNames as $datasetName) {
                    echo '<option value="' . $datasetName . '">' . $datasetName . '</option>';
                }
                ?>
            </select>
        </div>

        <!--<div id="map"></div>-->
        <div class="map-container">
            <div class="map-subcontainers">
                <p class="footnote">TRACK MAP</p>
                <div id="map" class="test-block">
                    <p class="test-item footnote">Map data unavailable</p>
                </div>
            </div>
            <div class="map-subcontainers">
                <p class="footnote">SITE OF RECORDING</p>
                <div id="map2" class="test-block">
                    <p class="test-item footnote">Map data unavailable</p>
                </div>
            </div>
        </div>

        <!--<h1>Sorted Filenames</h1> -->
        <ul id="sortedFilesList"></ul>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.js"></script>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exif-js/2.3.0/exif.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script>
        let currentIndex = 0;
        let uniquePoints = [];
        let markers = [];
        let map;
        let map2;


        let marker2;

        let startPointLat = -9999.0;
        let startPointLon = -9999.0;

        const defaultIcon = L.icon({
            iconUrl: "https://unpkg.com/leaflet@1.9.3/dist/images/marker-icon.png",
            shadowUrl: "https://unpkg.com/leaflet@1.9.3/dist/images/marker-shadow.png",
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        });

        var blueDotIcon = L.divIcon({
            className: "blue-dot",
            iconSize: [6, 6],
            popupAnchor: [0, -5],
        });

        const maroonIcon = L.icon({
            iconUrl: "current-location-pin.png",
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
            //iconUrl: 'https://chart.googleapis.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|800000' // Maroon pin color
        });

        function convertDMSToDD(degrees, minutes, seconds, direction) {
            let dd = degrees + minutes / 60 + seconds / 3600;
            if (direction === "S" || direction === "W") {
                dd = dd * -1;
            }
            return dd;
        }

        function arePointsClose(lat1, lon1, lat2, lon2, threshold = 0.0001) {
            return (
                Math.abs(lat1 - lat2) < threshold && Math.abs(lon1 - lon2) < threshold
            );
        }

        function showImage(index) {
            const point = uniquePoints[index];

            pannellum.viewer("viewer", {
                type: "equirectangular",
                panorama: point.imageSrc,
                autoLoad: true,
                yaw: 270,
                pitch: -10,
                hfov: 120,
            });

            highlightMarker(index);
        }

        function highlightMarker(index) {
            markers.forEach((marker, i) => {
                if (i === index) {
                    marker.setIcon(maroonIcon);
                } else {
                    //marker.setIcon(defaultIcon);
                    marker.setIcon(blueDotIcon); //zubin
                }
            });
        }

        function showNextImage() {
            // currentIndex = (currentIndex + 1) % uniquePoints.length;
            // showImage(currentIndex);
            currentIndex = currentIndex + 1;
            if (currentIndex < uniquePoints.length) {
                showImage(currentIndex);
            } else {
                currentIndex = uniquePoints.length - 1;
                showImage(uniquePoints.length - 1); // Jump to the last image if out of bounds
            }
        }

        function showPreviousImage() {
            // currentIndex = (currentIndex - 1 + uniquePoints.length) % uniquePoints.length;
            // showImage(currentIndex);
            currentIndex = currentIndex - 1;
            if (currentIndex >= 0) {
                showImage(currentIndex);
            } else {
                currentIndex = 0;
                showImage(0); // Jump to the first image if out of bounds
            }
        }

        function showNextXImage() {
            const stepSize =
                parseInt(document.getElementById("stepSize").value) || 5;
            currentIndex = currentIndex + stepSize;
            if (currentIndex < uniquePoints.length) {
                showImage(currentIndex);
            } else {
                currentIndex = uniquePoints.length - 1;
                showImage(uniquePoints.length - 1); // Jump to the last image if out of bounds
            }
        }

        function showPreviousXImage() {
            const stepSize =
                parseInt(document.getElementById("stepSize").value) || 5;
            currentIndex = currentIndex - stepSize;
            if (currentIndex >= 0) {
                showImage(currentIndex);
            } else {
                currentIndex = 0;
                showImage(0); // Jump to the first image if out of bounds
            }
        }

        function highlightMarkerOnClick(name) {
            let indexOfMarker = 0;

            uniquePoints.map((point, index) => {
                if (name === point.name) {
                    indexOfMarker = index;
                }
            });

            currentIndex = indexOfMarker;
            showImage(indexOfMarker);
        }

        /*
          New Code for Dropdown Selector
        */
        async function changeDataset(datasetName) {

            if (datasetName === "-") {
                return;
            }
            clear();
            await fetch('get_markers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        dataset_name: datasetName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    generateUniquePoints(data, datasetName);
                    initMap1();
                    initMap2();
                    generateMarkers();
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        }

        function clear() {
            uniquePoints = [];
            markers = [];
            if (map) {
                map.remove();
            }
            if (map2) {
                map2.remove();
            }
        }

        function generateUniquePoints(dataPoints, datasetName) {
            dataPoints.map((point) => {
                uniquePoints.push({
                    name: point.image_file,
                    lat: point.lat,
                    lon: point.lon,
                    imageSrc: `./datasets/${datasetName}/${point.image_file}`
                });
            });
        }

        function generateMarkers() {
            uniquePoints.map((point) => {
                const marker = L.marker([point.lat, point.lon], {
                        icon: blueDotIcon,
                        name: point.name
                    })
                    .on("click", (e) => {
                        highlightMarkerOnClick(marker.options.name);
                    })
                    .addTo(map)
                    .bindPopup(
                        `${point.name}<br>Latitude: ${point.lat}<br>Longitude: ${point.lon}`
                    );
                markers.push(marker);
            });

            const group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds());
        }

        function initMap1() {
            map = L.map("map").setView([0, 0], 2);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                maxZoom: 18,
            }).addTo(map);
        }

        function initMap2() {
            map2 = L.map("map2", {
                center: [uniquePoints[0].lat, uniquePoints[0].lon],
                zoom: 8,
                zoomControl: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                //dragging: false,
                keyboard: false,
            });

            marker2 = L.marker([uniquePoints[0].lat, uniquePoints[0].lon]);
            marker2.addTo(map2).bindPopup(`Location of recording.`).openPopup();

            L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map2);
            showImage(0);
        }

        document
            .getElementById("dropdown")
            .addEventListener("change", e => changeDataset(e.target.value));

        document
            .getElementById("nextButton")
            .addEventListener("click", showNextImage);

        document
            .getElementById("prevButton")
            .addEventListener("click", showPreviousImage);


        document
            .getElementById("nextXButton")
            .addEventListener("click", showNextXImage);

        document
            .getElementById("prevXButton")
            .addEventListener("click", showPreviousXImage);
    </script>
</body>

</html>