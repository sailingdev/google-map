<?php
require_once 'dbConfig.php';
$imei = $_GET['imei'];
$start_time = $_GET['start_time'] ?? "";
$end_time = $_GET['end_time'] ?? "";
$query = "SELECT * FROM gs_object_data_" . $imei. " ORDER BY dt_tracker ASC LIMIT 109";

define("STOPPED_SPEED", 5);

function getStatus($speed){
    return $speed <= STOPPED_SPEED ? "Stopped" : "Moving";
}

function diff_seconds($dt1, $dt2){
    $start = new DateTime($dt1);
    $end = new DateTime($dt2);
    $diff = $start->diff($end);
    $daysInSecs = $diff->format('%r%a') * 24 * 60 * 60;
    $hoursInSecs = $diff->h * 60 * 60;
    $minsInSecs = $diff->i * 60;
    $seconds = $daysInSecs + $hoursInSecs + $minsInSecs + $diff->s;
    return $seconds;
}

$delta = 0;
function secondsToLetterTime($seconds){
    $delta = $seconds;
    // calculate (and subtract) whole days
     $days = round($delta / 86400);
    $delta -= $days * 86400;

    // calculate (and subtract) whole hours
     $hours = round($delta / 3600) % 24;
     if ($hours < 0)
         $hours = $hours + 24;
    $delta -= $hours * 3600;

    // calculate (and subtract) whole minutes
     $minutes = round($delta / 60) % 60;
    if ($minutes < 0)
        $minutes = $minutes + 60;
    $delta -= $minutes * 60;

    // what's left is seconds
     $s = $delta % 60;  // in theory the modulus is not required
    if ($s < 0)
        $s = $s + 60;
    
    if ($seconds >= 86400)
        $text = $days . " days " . $hours . " hrs " . $minutes . " min " . $s . " s";
    else if ($seconds >= 3600)
        $text = $hours . " hrs " . $minutes . " min " . $s . " s";
    else if ($seconds >= 60)
        $text =  $minutes . " min " . $s . " s";
    else
        $text = $s . " s";
    return $text;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report</title>
</head>
<body>

<table id="datatable-buttons" class="display" style="width:100%; text-align: center">
    <thead>
    <tr>
        <th>Status</th>
        <th>Start</th>
        <th>End</th>
        <th>Duration</th>
        <th>Length</th>
        <th>Stop position<br>Top speed</th>
        <th>Average speed</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $result = $db->query($query);
    $data = array();
    $m = 0;
    if ($result->num_rows > 0){
        $previous_item = null;
        $i = 0;
        $j = 0;
        $average_speed = 0;
        $length = 0;
        $moving_duration = 0;
        $stop_duration = 0;
        while($row = $result->fetch_assoc()){
            if ($i > 0){
                if ($row['speed'] > STOPPED_SPEED){
                    $j++;
                    $average_speed += $row['speed'];
                    $length += round(diff_seconds($row['dt_tracker'], $previous_item['dt_tracker']) * $row['speed'] / 3600, 2);
                    $moving_duration += diff_seconds($row['dt_tracker'], $previous_item['dt_tracker']);
                    $stop_duration = 0;
                }else{
                    $length = 0;
                    $moving_duration = 0;
                    $stop_duration += diff_seconds($row['dt_tracker'], $previous_item['dt_tracker']);
                    $j = 0;
                    $average_speed = 0;
                }


                $item = array(
                        'status' => getStatus($row['speed']),
                    'start' => $previous_item['dt_tracker'],
                    'end' => $row['dt_tracker'],
                    'duration' =>  $moving_duration == 0? $stop_duration: $moving_duration,
                    'length' => $length == 0? "": round($length, 2),
                    'speed' => $row['speed'] <= STOPPED_SPEED? "<span style='color: blue'>".$row['lat']."°, ".$row['lng']."°</span>" : $row['speed'],
                    'average_speed' => $j == 0? "" : round($average_speed/$j, 2),
                );
                array_push($data, $item);
            }
            $previous_item = $row;
            $i++;
        }

        $temp = $data[0]['speed'];
        $stop_total_duration = 0;
        $move_total_duration = 0;
        $route_length = 0;
        $top_speed = $data[0]['speed'];
        for ($k = 1; $k < count($data); $k++){
            $m++;
            $temp = $temp > $data[$k-1]['speed']? $temp : $data[$k-1]['speed'];

            if ($data[$k-1]['speed'] > STOPPED_SPEED){
                $top_speed = $top_speed > $data[$k-1]['speed']? $top_speed : $data[$k-1]['speed'];
            }

            if ($data[$k-1]['status'] != $data[$k]['status']){
                $move_total_duration += $data[$k-1]['speed'] <= STOPPED_SPEED ? 0 :  $data[$k-1]['duration'];
                $stop_total_duration += $data[$k-1]['speed'] > STOPPED_SPEED ? 0 :  $data[$k-1]['duration'];
                $route_length += $data[$k-1]['speed'] <= STOPPED_SPEED ? 0 :  $data[$k-1]['length'];
                ?>
                <tr>
                    <td> <?php echo $data[$k-1]['status']; ?> </td>
                    <td> <?php echo $data[$k-$m]['start']; ?> </td>
                    <td> <?php echo $data[$k-1]['end']; ?> </td>
                    <td> <?php echo secondsToLetterTime($data[$k-1]['duration']); ?> </td>
                    <td> <?php echo $data[$k-1]['length']?> </td>
                    <td> <?php echo $temp; ?> </td>
                    <td> <?php echo $data[$k-1]['average_speed']; ?> </td>
                </tr>
                <?php
                $m = 0;
                $temp = 0;
            }
        }
    }?>
    </tbody>
</table>
<div>
    <strong>Move duration: </strong> <?php echo secondsToLetterTime($move_total_duration)?>
    <br>
    <strong>Stop duration: </strong> <?php echo secondsToLetterTime($stop_total_duration)?>
    <br>
    <strong>Route length: </strong> <?php echo $route_length?>
    <br>
    <strong>Top speed: </strong> <?php echo $top_speed?>
</div>
</body>
</html>