<?php
// Database configuration
//$dbHost     = "154.0.166.48";
//$dbUsername = "root";
//$dbPassword = "b4zre9Trevor74@";
//$dbName     = "gs";

$dbHost     = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName     = "google_map";

// Create database connection
$db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}