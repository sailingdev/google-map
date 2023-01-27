<?php
$imei = $_GET['imei'];
?>
<!--352094084608741-->
<!DOCTYPE html>
<html>
<head>
    <title>Track</title>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <style type="text/css">
        html {
            height: 100%
        }
        body {
            height: 100%;
            margin: 0;
            padding: 0
        }
        #map {
            height: 100%
        }
        strong{
            font-size: 13pt;
        }
        .whiteBox div{
            padding: 5px;
        }
        .whiteBox span{
            font-size: 13pt;
        }
        .whiteBox div:first-child span{
            color: #0000ff;
        }

    </style>

</head>
<body>
<div id="map"></div>
</body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyATD6C5HPaQ71YYOrIORgm6NdYHYrkjpsc&libraries=geometry"></script>

<script>
    let trilPath, marker, markerPosition, path, repetition_cnt = 1;
    let map, parked_markers = [], vehicle_speed = 0, before_speed = 1;
    let start_time = '', end_time = '';

    const PATH_LENGTH = 50;
    const UPDATE_INTERVAL_TIME = 5000;
    const TRACE_COLOR = '#FF0000';

    function diff_minutes(dt2, dt1)
    {
        var diff =(dt2.getTime() - dt1.getTime()) / 1000;

        delta = diff;
        // calculate (and subtract) whole days
        var days = Math.floor(delta / 86400);
        delta -= days * 86400;

        // calculate (and subtract) whole hours
        var hours = Math.floor(delta / 3600) % 24;
        delta -= hours * 3600;

        // calculate (and subtract) whole minutes
        var minutes = Math.floor(delta / 60) % 60;
        delta -= minutes * 60;

        // what's left is seconds
        var seconds = delta % 60;  // in theory the modulus is not required

        let text = "";
        if (diff >= 86400)
            text = days + " days " + hours + " hrs " + minutes + " min " + seconds + " s";
        else if (diff >= 3600)
            text = hours + " hrs " + minutes + " min " + seconds + " s";
        else if (diff >= 60)
            text =  minutes + " min " + seconds + " s";
        else
            text = seconds + " s";
        return text;
    }


    let whiteBox = function (data, nextData) {
        dt1 = new Date(data.dt_tracker);
        dt2 = new Date(nextData.dt_tracker);
        let diff = diff_minutes(dt2, dt1);
        return '<div class="whiteBox">\
                <div><strong>Position: </strong>&nbsp;&nbsp;&nbsp;&nbsp;<span>'+ data.lat + ' °, ' +'</span><span>'+ data.lng +' °</span></div>\
                <div><strong>Altitude: </strong>&nbsp;&nbsp;&nbsp;&nbsp;<span>'+ data.altitude +' ⅿ<span></div>\
                <div><strong>Angle: </strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>'+ data.angle +' °<span></div>\
                <div><strong>Arrived: </strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>'+ data.dt_tracker +'<span></div>\
                <div><strong>Departed: </strong>&nbsp;&nbsp;&nbsp;&nbsp;<span>'+ nextData.dt_tracker +'<span></div>\
                <div><strong>Duration: </strong>&nbsp;&nbsp;&nbsp;&nbsp;<span>' + diff + '<span></div>\
                </div>';
    }

    async function getPositions(){
        return await new Promise((resolve, reject) => {
            $.ajax({
                url: "./controller.php",
                method: 'post',
                data: {
                    imei: <?php echo $imei ?>,
                    page: repetition_cnt++,
                    start_time: start_time,
                    end_time: end_time
                },
            }).done(function (data){
                resolve(data);
            }).fail((err) => reject(err));
        });
    }

    async function initialize() {
        path = [];
        let coords = [];

        await getPositions()
            .then((data) => {
                coords = JSON.parse(data);
            })
            .catch((err) => console.log(err));

        let start_lat = parseFloat(coords[0].lat);
        let start_lng = parseFloat(coords[0].lng);

        var mapOptions = {
            zoom: 6,
            center: {lat: start_lat, lng: start_lng},
            mapTypeId: google.maps.MapTypeId.ROADMAP
        }
        map = new google.maps.Map(document.getElementById("map"), mapOptions);

        trilPath = new google.maps.Polyline({
            path: path,
            strokeColor: TRACE_COLOR,
            strokeOpacity: 1.0,
            strokeWeight: 1,
            geodesic: true,
            map: map
        });
        markerPosition = new google.maps.LatLng(start_lat, start_lng);
        marker = new google.maps.Marker({
            position: markerPosition,
            map: map,
            title: "",
            icon: {
                path: google.maps.SymbolPath.FORWARD_OPEN_ARROW,
                scale: 4,
                strokeColor: TRACE_COLOR,
                rotation: 0,
            }
        });
    }

    async function updateMap() {
        // update trail path
        path = [];
        let coords = [];
        await getPositions()
            .then((data) => {
                coords = JSON.parse(data);
            })
            .catch((err) => console.log(err));

        if (coords[0].speed == 0 && before_speed != coords[0].speed){
            clearParkedMarkers(parked_markers);
            addAllParkedMarkers(coords);
            addPath(coords);
            trilPath.setPath(path);

            // update latest position as a marker
            let lastPosition = coords[0];
            markerPosition = new google.maps.LatLng(lastPosition.lat, lastPosition.lng);
            marker.setPosition(markerPosition);
            rotateMarker(marker, lastPosition.angle);

        }else if (coords[0].speed != 0) {
            clearParkedMarkers(parked_markers);
            addPath(coords);
            trilPath.setPath(path);

            // update latest position as a marker
            let lastPosition = coords[0];
            markerPosition = new google.maps.LatLng(lastPosition.lat, lastPosition.lng);
            marker.setPosition(markerPosition);
            rotateMarker(marker, lastPosition.angle);
        }
        before_speed = coords[0].speed;
    }

    let animation_parked_mark = new google.maps.Marker();
    function addAllParkedMarkers(coords){
        let infoWindow = new google.maps.InfoWindow;
        for (let i = 0; i < coords.length; i++) {
            if (coords[i].speed == 0){
                // add markers where the speed of vehicle is 0
                let parked_marker = new google.maps.Marker({
                    position: new google.maps.LatLng(coords[i].lat, coords[i].lng),
                    map: map,
                });
                parked_marker.setIcon("../assets/images/parking-marker.png");
                google.maps.event.addListener(parked_marker, 'click', (function (a, i) {
                    return function () {
                        animation_parked_mark.setAnimation(null);
                        a.setAnimation(google.maps.Animation.BOUNCE);
                        animation_parked_mark = a;
                        // setPickedPoint(row.River, row.Lat, row.Long,  row.River_Mile);
                        infoWindow.setContent(whiteBox(coords[i], coords[i - 1]));
                        infoWindow.open(map, a);
                        // map.panTo(new google.maps.LatLng(coords[i].lat, coords[i].lng));
                    }
                })(parked_marker, i));
                parked_markers.push(parked_marker);
            }
        }
    }
    function clearParkedMarkers(markers){
        $.each(markers, function (i, marker) {
            marker.setMap(null);
        });
    }

    function addPath(coords){
        for (let i = 0; i < coords.length; i++)
            path.push(new google.maps.LatLng(coords[i].lat, coords[i].lng));
    }

    function rotateMarker(marker, angle){
        let icon = marker.getIcon();
        icon.rotation = parseFloat(angle);
        marker.setIcon(icon);
    }

    google.maps.event.addDomListener(window, 'load', function () {
        initialize();
        setInterval(updateMap, UPDATE_INTERVAL_TIME);
    });

</script>
</html>