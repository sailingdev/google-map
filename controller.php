<?php
require_once 'dbConfig.php';
$imei = $_POST['imei'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];

// Fetch the marker info from the database
$result = $db->query("SELECT lat, lng, speed, angle, altitude, dt_tracker FROM gs_object_data_" . $imei . " ORDER BY dt_tracker DESC LIMIT 50 ");

$positions = array();
if ($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $coord = array(
            'lat' => $row['lat'],
            'lng' => $row['lng'],
            'speed' => $row['speed'],
            'angle' => $row['angle'],
            'altitude' => $row['altitude'],
            'dt_tracker' => $row['dt_tracker']
        );
        array_push($positions, $coord);
    }
}

if ($start_time != "" && $end_time != "")
    if ($positions[0]['speed'] == 0){
        $query="SELECT lat, lng, speed, angle, altitude, dt_tracker FROM gs_object_data_"
            . $imei." WHERE dt_tracker >= '".$start_time."' AND dt_tracker <= '".$end_time."' ORDER BY dt_tracker DESC";
        $result = $db->query($query);
        $positions = array();
        if ($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $coord = array(
                    'lat' => $row['lat'],
                    'lng' => $row['lng'],
                    'speed' => $row['speed'],
                    'angle' => $row['angle'],
                    'altitude' => $row['altitude'],
                    'dt_tracker' => $row['dt_tracker']
                );
                array_push($positions, $coord);
            }
        }
    }
echo json_encode($positions);