<?php
// Include the database configuration file
require_once 'dbConfig.php';

$imei = $_GET['imei'];
$reg =$_POST['reg'];
$token = $_GET['token'];

// Fetch the marker info from the database
$result = $db->query("SELECT * FROM gs_object_data_" . $imei . " ORDER BY dt_tracker DESC LIMIT 50 ");

// Fetch the info-window data from the database
$result2 = $db->query("SELECT * FROM gs_object_data_" . $imei . " ORDER BY dt_tracker DESC LIMIT 50 ");
?>
<script src="https://maps.googleapis.com/maps/api/js?key=*****"></script>
<script>
    function initMap() {
        var map;
        var bounds = new google.maps.LatLngBounds();
        var mapOptions = {
            mapTypeId: 'hybrid',
            streetViewControl: true
        };

        // Display a map on the web page
        map = new google.maps.Map(document.getElementById("mapCanvas"), mapOptions);
        map.setTilt(50);

        // Multiple markers location, latitude, and longitude
        var markers = [
            <?php if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                echo '["'.$row['notes'].'", '.$row['lat'].', '.$row['lng'].'],';
            }
        }
            ?>
        ];

        // Info window content
        var infoWindowContent = [
            <?php if($result2->num_rows > 0){
            while($row = $result2->fetch_assoc()){ ?>
            ['<div class="info_content">' +
            '<h3><?php echo $row['name']; ?></h3>' +
            '<p><?php echo $row['dt_tracker']; ?> / <?php echo $row['speed']; ?> Km/h</p>'],
            <?php }
            }
            ?>
        ];

        // Add multiple markers to map
        var infoWindow = new google.maps.InfoWindow(), marker, i;

        // Place each marker on the map
        for( i = 0; i < markers.length; i++ ) {
            var position = new google.maps.LatLng(markers[i][1], markers[i][2]);
            bounds.extend(position);
            marker = new google.maps.Marker({
                position: position,
                map: map,
                title: markers[i][0]
            });

            // Add info window to marker
            google.maps.event.addListener(marker, 'click', (function(marker, i) {
                return function() {
                    infoWindow.setContent(infoWindowContent[i][0]);
                    infoWindow.open(map, marker);
                }
            })(marker, i));

            // Center the map to fit all markers on the screen
            map.fitBounds(bounds);
        }

        // Set zoom level
        var boundsListener = google.maps.event.addListener((map), 'bounds_changed', function(event) {
            this.setZoom(14);
            google.maps.event.removeListener(boundsListener);
        });

    }

    // Load initialize function
    google.maps.event.addDomListener(window, 'load', initMap);
</script>

<style type="text/css">
    #mapCanvas {
        width: 100%;
        height: 400px;
    }
</style>
<body>

<div id="mapContainer">
    <div id="mapCanvas"></div>
</div>